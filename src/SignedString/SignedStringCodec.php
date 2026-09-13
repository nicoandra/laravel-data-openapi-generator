<?php

namespace NicoAndra\OpenApiGenerator\SignedString;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use JsonException;
use RuntimeException;
use Throwable;

final class SignedStringCodec
{
    private const MAX_BYTES = 16384;
    private const MAX_TTL   = 86400;
    private const SKEW      = 60;

    /** @var array<string, string> */
    private array $keys;
    private string $activeKeyId;
    private int $defaultTtl;

    /** @param null|array<string, string> $keyRing */
    public function __construct(
        ?array $keyRing = null,
        ?string $activeKeyId = null,
        ?int $defaultTtl = null,
    ) {
        $settings = function_exists('config') ? (array) config('openapi-generator.signed_string', []) : [];
        $keyRing ??= (array) ($settings['key_ring'] ?? []);
        if ([] === $keyRing) {
            $appKey  = function_exists('config') ? config('app.key') : null;
            $keyRing = [(string) ($settings['active_key_id'] ?? 'app') => $appKey];
        }
        $this->keys = [];
        foreach ($keyRing as $id => $key) {
            if (! is_string($id) || '' === $id || strlen($id) > 64 || ! preg_match('/^[\x21-\x7e]+$/', $id)) {
                $this->invalid();
            }
            $this->keys[$id] = self::resolveKey($key);
        }
        $this->activeKeyId = $activeKeyId ?? (string) ($settings['active_key_id'] ?? array_key_first($this->keys));
        if (! isset($this->keys[$this->activeKeyId])) {
            $this->invalid();
        }
        $configuredDefault = $defaultTtl ?? (int) ($settings['default_ttl'] ?? self::MAX_TTL);
        $this->defaultTtl  = self::safeTtl($configuredDefault);
    }

    /** @param array<string, mixed>|object $data */
    public function encode(array|object $data, mixed $ttl = null): string
    {
        if (is_object($data)) {
            if (! method_exists($data, 'toArray')) {
                $this->invalid();
            }
            $data = $data->toArray();
        }
        if (array_is_list($data)) {
            $this->invalid();
        }
        $iat      = Carbon::now()->timestamp;
        $envelope = [
            'data' => $data,
            'exp'  => $iat + self::ttl($ttl, $this->defaultTtl),
            'iat'  => $iat,
            'kid'  => $this->activeKeyId,
            'v'    => 1,
        ];
        $json  = self::canonical($envelope);
        $token = 'v1.' . self::b64($json) . '.' . self::b64(hash_hmac('sha256', $json, $this->keys[$this->activeKeyId], true));
        if (strlen($token) > self::MAX_BYTES) {
            $this->invalid();
        }

        return $token;
    }

    /** @return array<string, mixed> */
    public function inspect(string $token): array
    {
        [$json, $envelope, $mac] = $this->verified($token);
        unset($json, $mac);

        return $envelope;
    }

    /** @return array<string, mixed> */
    public function decode(string $token): array
    {
        return $this->verified($token)[1]['data'];
    }

    /** @return array{0:string,1:array<string,mixed>,2:string} */
    private function verified(string $token): array
    {
        if (strlen($token) > self::MAX_BYTES || ! preg_match('/^v1\.([A-Za-z0-9_-]+)\.([A-Za-z0-9_-]+)$/D', $token, $m)) {
            $this->invalid();
        }
        $json = self::unb64($m[1]);
        $mac  = self::unb64($m[2]);
        if (32 !== strlen($mac) || ! self::isCanonicalJson($json)) {
            $this->invalid();
        }

        try {
            $decoded      = json_decode($json, false, 32, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
            $envelope     = is_object($decoded) ? get_object_vars($decoded) : null;
            $dataIsObject = is_object($decoded) && isset($decoded->data) && is_object($decoded->data);
            if (is_array($envelope)) {
                $envelope['data'] = self::normalize($envelope['data']);
            }
        } catch (JsonException) {
            $this->invalid();
        }
        if (! is_array($envelope) || array_keys($envelope) !== ['data', 'exp', 'iat', 'kid', 'v'] || ! $dataIsObject || ! is_array($envelope['data'])
            || 1 !== $envelope['v'] || ! is_int($envelope['iat']) || ! is_int($envelope['exp']) || $envelope['exp'] <= $envelope['iat']
            || ! is_string($envelope['kid']) || ! isset($this->keys[$envelope['kid']])) {
            $this->invalid();
        }
        if (! hash_equals(hash_hmac('sha256', $json, $this->keys[$envelope['kid']], true), $mac)) {
            $this->invalid();
        }
        $now = Carbon::now()->timestamp;
        if ($envelope['iat'] > $now + self::SKEW || $envelope['exp'] <= $now || $envelope['exp'] - $envelope['iat'] > self::MAX_TTL) {
            $this->invalid();
        }

        return [$json, $envelope, $mac];
    }

    private static function canonical(mixed $value): string
    {
        if (is_array($value) && ! array_is_list($value)) {
            ksort($value, SORT_STRING);
            foreach ($value as &$v) {
                $v = self::canonicalValue($v);
            } unset($v);
        } else {
            $value = self::canonicalValue($value);
        }

        try {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException('Invalid signed string');
        }
    }

    private static function normalize(mixed $value): mixed
    {
        if (is_object($value)) {
            $result = [];
            foreach (get_object_vars($value) as $key => $item) {
                $result[$key] = self::normalize($item);
            }

            return $result;
        }
        if (is_array($value)) {
            foreach ($value as &$item) {
                $item = self::normalize($item);
            }
        }

        return $value;
    }

    private static function canonicalValue(mixed $value): mixed
    {
        if (is_object($value)) {
            $value = get_object_vars($value);
            ksort($value, SORT_STRING);
            foreach ($value as &$v) {
                $v = self::canonicalValue($v);
            } unset($v);

            return (object) $value;
        }
        if (is_array($value) && ! array_is_list($value)) {
            ksort($value, SORT_STRING);
            foreach ($value as &$v) {
                $v = self::canonicalValue($v);
            } unset($v);
        } elseif (is_array($value)) {
            foreach ($value as &$v) {
                $v = self::canonicalValue($v);
            }
        }

        return $value;
    }

    private static function b64(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function unb64(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if (false === $decoded || self::b64($decoded) !== $value) {
            self::fail();
        }

        return $decoded;
    }

    private static function isCanonicalJson(string $json): bool
    {
        if ('' === $json || str_starts_with($json, "\xEF\xBB\xBF")) {
            return false;
        }

        try {
            return self::canonical(json_decode($json, false, 32, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING)) === $json;
        } catch (JsonException|RuntimeException) {
            return false;
        }
    }

    private static function resolveKey(mixed $key): string
    {
        if (! is_string($key) || '' === $key) {
            self::fail();
        }
        if (str_starts_with($key, 'base64:')) {
            $encoded = substr($key, 7);
            $key     = base64_decode($encoded, true);
            if (false === $key || base64_encode($key) !== $encoded) {
                self::fail();
            }
        }
        if (! is_string($key) || strlen($key) < 32 || ! mb_check_encoding($key, 'UTF-8')) {
            self::fail();
        }

        return $key;
    }

    private static function safeTtl(int $ttl): int
    {
        return $ttl > 0 ? min($ttl, self::MAX_TTL) : self::MAX_TTL;
    }

    private static function ttl(mixed $ttl, int $default): int
    {
        if (null === $ttl) {
            return $default;
        }
        if (is_int($ttl) && $ttl > 0) {
            return min($ttl, self::MAX_TTL);
        }
        if (is_string($ttl)) {
            try {
                $seconds = (int) CarbonInterval::make($ttl)->totalSeconds;
                if ($seconds > 0) {
                    return min($seconds, self::MAX_TTL);
                }
            } catch (Throwable) {
            }
        }

        return $default;
    }

    private function invalid(): never
    {
        throw new RuntimeException('Invalid signed string');
    }

    private static function fail(): never
    {
        throw new RuntimeException('Invalid signed string');
    }
}
