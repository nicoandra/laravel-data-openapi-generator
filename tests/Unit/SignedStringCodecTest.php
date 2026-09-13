<?php

use Carbon\Carbon;
use NicoAndra\OpenApiGenerator\SignedString\SignedStringCodec;

beforeEach(fn () => Carbon::setTestNow(Carbon::create(2025, 1, 1, 0, 0, 0, 'UTC')));
afterEach(fn () => Carbon::setTestNow());

it('round trips canonical signed data with the configured lifetime', function () {
    $codec = new SignedStringCodec(keyRing: ['2025-01' => str_repeat('a', 32)], activeKeyId: '2025-01');

    $token  = $codec->encode(['name' => 'Ada', 'email' => 'ada@example.test']);
    $result = $codec->decode($token);

    expect($result)->toBe(['email' => 'ada@example.test', 'name' => 'Ada']);
    expect($codec->inspect($token))->toBe([
        'data' => ['email' => 'ada@example.test', 'name' => 'Ada'],
        'exp'  => 1_735_776_000,
        'iat'  => 1_735_689_600,
        'kid'  => '2025-01',
        'v'    => 1,
    ]);
});

it('round trips simple and nested values without changing their shape', function () {
    $codec = new SignedStringCodec(['k' => str_repeat('b', 32)], 'k');
    $data  = [
        'name'     => 'Ada',
        'profile'  => ['roles' => ['admin', 'writer'], 'active' => true],
        'optional' => null,
    ];

    expect($codec->decode($codec->encode($data)))->toEqual($data);
});

it('produces deterministic tokens for repeated transformations', function () {
    $codec = new SignedStringCodec(['k' => str_repeat('b', 32)], 'k');
    $data  = ['z' => ['b' => 2, 'a' => 1], 'a' => 'first'];

    expect($codec->encode($data))->toBe($codec->encode($data));
});

it('allows retrying the same valid token', function () {
    $codec = new SignedStringCodec(['k' => str_repeat('b', 32)], 'k');
    $token = $codec->encode(['ok' => true], '3600 seconds');

    expect($codec->decode($token))->toBe(['ok' => true]);
    expect($codec->decode($token))->toBe(['ok' => true]);
});

it('caps the default and rejects invalid ttl overrides', function (mixed $ttl) {
    $codec = new SignedStringCodec(['k' => str_repeat('c', 32)], 'k');
    $token = $codec->encode(['ok' => true], $ttl);

    expect($codec->inspect($token)['exp'])->toBe(1_735_776_000);
})->with([null, 0, -1, 'nonsense', '1 week']);

it('rejects tampering, malformed encoding, expired, and unsupported tokens', function (string $mutator) {
    $codec   = new SignedStringCodec(['k' => str_repeat('d', 32)], 'k');
    $token   = $codec->encode(['ok' => true], 60);
    $parts   = explode('.', $token);
    $mutated = match ($mutator) {
        'payload' => 'v1.' . rtrim(strtr(base64_encode('{"data":{"ok":false},"exp":1735689660,"iat":1735689600,"kid":"k","v":1}'), '+/', '-_'), '=') . '.' . $parts[2],
        'mac'     => $parts[0] . '.' . $parts[1] . '.bad',
        'base64'  => $parts[0] . '.=' . $parts[1] . '.' . $parts[2],
        'padding' => $parts[0] . '.' . $parts[1] . '=.' . $parts[2],
        'extra'   => $token . '.extra',
        'version' => 'v2.' . $parts[1] . '.' . $parts[2],
    };

    expect(fn () => $codec->decode($mutated))->toThrow(RuntimeException::class, 'Invalid signed string');
})->with(['payload', 'mac', 'base64', 'padding', 'extra', 'version']);

it('rejects tokens signed with a different key', function () {
    $token    = (new SignedStringCodec(['k' => str_repeat('f', 32)], 'k'))->encode(['ok' => true]);
    $verifier = new SignedStringCodec(['k' => str_repeat('g', 32)], 'k');

    expect(fn () => $verifier->decode($token))->toThrow(RuntimeException::class, 'Invalid signed string');
});

it('rejects invalid JSON and invalid envelope shapes', function (string $json) {
    $token = signedStringTokenFor($json, str_repeat('h', 32));

    expect(fn () => (new SignedStringCodec(['k' => str_repeat('h', 32)], 'k'))->decode($token))
        ->toThrow(RuntimeException::class, 'Invalid signed string');
})->with([
    '{"data":{"ok":true},"exp":1735776000,"iat":1735689600,"kid":"k","v":1} trailing',
    '[]',
    '{"data":[],"exp":1735776000,"iat":1735689600,"kid":"k","v":1}',
    '{"data":{"ok":true},"exp":1735776000,"iat":1735689600,"kid":"unknown","v":1}',
    '{"data":{"ok":true},"exp":1735776000,"iat":1735689600,"kid":"k","v":2}',
]);

it('accepts the maximum lifetime and rejects an oversized token', function () {
    $codec = new SignedStringCodec(['k' => str_repeat('i', 32)], 'k');
    $token = $codec->encode(['ok' => true], 86400);

    expect(strlen($token))->toBeLessThanOrEqual(16384)
        ->and($codec->inspect($token)['exp'])->toBe(1_735_776_000);
    expect(fn () => $codec->encode(['value' => str_repeat('x', 20000)]))
        ->toThrow(RuntimeException::class, 'Invalid signed string');
});

it('rejects expired, future, and overlong lifetime claims', function (int $iat, int $exp) {
    $json = json_encode([
        'data' => ['ok' => true],
        'exp'  => $exp,
        'iat'  => $iat,
        'kid'  => 'k',
        'v'    => 1,
    ], JSON_THROW_ON_ERROR);
    $token = signedStringTokenFor($json, str_repeat('h', 32));

    expect(fn () => (new SignedStringCodec(['k' => str_repeat('h', 32)], 'k'))->decode($token))
        ->toThrow(RuntimeException::class, 'Invalid signed string');
})->with([
    [1735689500, 1735689599],
    [1735689600, 1735776001],
    [1735689661, 1735689721],
]);

it('uses raw and base64 app keys when no key ring is configured', function (string $key) {
    config()->set('openapi-generator.signed_string.key_ring', []);
    config()->set('openapi-generator.signed_string.active_key_id', 'app');
    config()->set('app.key', $key);

    $codec = new SignedStringCodec();
    $token = $codec->encode(['configured' => true]);

    expect($codec->decode($token))->toBe(['configured' => true]);
})->with([
    str_repeat('j', 32),
    'base64:' . base64_encode(str_repeat('k', 32)),
]);

function signedStringTokenFor(string $json, string $key): string
{
    $encode = fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    $mac    = hash_hmac('sha256', $json, $key, true);

    return 'v1.' . $encode($json) . '.' . $encode($mac);
}

it('rejects an envelope containing a nonce', function () {
    $codec   = new SignedStringCodec(['k' => str_repeat('e', 32)], 'k');
    $token   = $codec->encode(['ok' => true]);
    $parts   = explode('.', $token);
    $json    = base64_decode(strtr($parts[1], '-_', '+/'), true);
    $json    = str_replace('"v":1}', '"nonce":"unexpected","v":1}', $json);
    $payload = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    $mac     = rtrim(strtr(base64_encode(hash_hmac('sha256', $json, str_repeat('e', 32), true)), '+/', '-_'), '=');

    expect(fn () => $codec->decode("v1.{$payload}.{$mac}"))->toThrow(RuntimeException::class, 'Invalid signed string');
});
