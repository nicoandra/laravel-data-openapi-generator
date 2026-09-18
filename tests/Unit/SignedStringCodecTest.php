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
        'version' => 'v2.' . $parts[1] . '.' . $parts[2],
    };

    expect(fn () => $codec->decode($mutated))->toThrow(RuntimeException::class, 'Invalid signed string');
})->with(['payload', 'mac', 'base64', 'version']);

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
