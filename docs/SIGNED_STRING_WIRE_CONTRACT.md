# Signed-string wire contract

This document is authoritative for the version 1 signed-string protocol. A signed string transports a nested `Spatie\LaravelData\Data` object as one authenticated, expiring string. Version 1 provides authentication and expiry only: it has no nonce and no replay store. A verifier may therefore accept the same valid token more than once until it expires.

## Registering a signed property

Apply `WithCastAndTransformer` with `SignedStringCastTransformer` to the nested `Data` property. The attribute must configure the class as both the cast and transformer:

```php
use App\Data\SignedPayloadData;
use NicoAndra\OpenApiGenerator\Data\Cast\SignedStringCastTransformer;
use Spatie\LaravelData\Attributes\WithCastAndTransformer;
use Spatie\LaravelData\Data;

class LinkData extends Data
{
    public function __construct(
        #[WithCastAndTransformer(SignedStringCastTransformer::class)]
        public ?SignedPayloadData $payload,
    ) {}
}
```

The target property type must be a class extending `Spatie\LaravelData\Data`. Nullable properties accept `null` and preserve it through input and output. A property configured with only the cast or only the transformer is unsupported and causes schema generation to fail.

## Encode/decode lifecycle

On output, Laravel Data invokes the transformer. It requires a `Data` object (or `null`), calls `toArray()`, and the codec creates a token from that associative data. The same input is encoded deterministically, so repeated transformations produce the same token while the current issue timestamp and configured TTL remain the same.

On input, Laravel Data invokes the cast. It requires a string (or `null`), and the codec verifies the token before the target class is hydrated with `TargetData::from($decodedData)`. This ordering prevents an unauthenticated payload from being hydrated. Codec validation failures use `RuntimeException('Invalid signed string')`. Invalid cast/transformer values and an incompatible target property use `InvalidArgumentException`; errors raised by Laravel Data while hydrating the verified data remain framework-specific.

### Trust boundary for construction

Request-bound signed fields must be hydrated through Laravel Data request handling. This preserves the request field as a string token so the cast receives it and verifies it before hydration. Do not use `SignedClass::from($request->toArray())`: converting the request to an ordinary array removes the distinction between request data and trusted code at this boundary.

For trusted programmatic construction, use `new SignedClass(...)` with the nested `Data` object, not `SignedClass::from(array [...])`. This is a documented usage rule, not automatic origin detection: the cast has no reliable request-origin marker and does not receive the original payload.

## Wire format

A token has exactly three dot-separated ASCII segments:

```text
v1.<payload-base64url>.<mac-base64url>
```

`payload-base64url` is unpadded URL-safe Base64 of canonical UTF-8 JSON. `mac-base64url` is unpadded URL-safe Base64 of the 32-byte raw HMAC-SHA-256 digest. Padding, standard Base64, whitespace, extra segments, and non-alphabet characters are rejected. The complete token is limited to 16,384 bytes.

The payload JSON is an object with exactly these members, in canonical order:

```json
{
  "data": { "email": "ada@example.test", "name": "Ada" },
  "exp": 1735776000,
  "iat": 1735689600,
  "kid": "2025-01",
  "v": 1
}
```

- `data` is the associative object produced by the nested Data object's `toArray()` representation. A top-level list is not accepted.
- `iat` and `exp` are integer Unix timestamps, with `exp > iat`.
- `kid` is a non-empty printable ASCII identifier of at most 64 bytes.
- `v` is the integer `1` and agrees with the `v1` prefix.
- No other envelope members are accepted. In particular, v1 has no nonce or replay claim.

## Canonical JSON and signing

The MAC input is exactly the canonical envelope JSON bytes. Object members are recursively sorted by string order; array order is preserved. Encoding uses `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION`, with no insignificant whitespace or trailing newline. The JSON must re-encode byte-for-byte identically. Invalid JSON/UTF-8, BOMs, trailing or non-canonical bytes, unsupported values, invalid envelope shapes, and excessive nesting are rejected.

The codec computes `HMAC-SHA-256(key, canonical-envelope-json-bytes)` and compares the raw digest with `hash_equals`. The signature covers the complete envelope, including `data`, timestamps, and key ID. Any change to those values, member ordering, escaping, or Base64 representation invalidates the token.

## Keys and TTL

Configuration is in `config/openapi-generator.php`:

```php
'signed_string' => [
    'default_ttl'   => 86400,
    'active_key_id' => 'app',
    'key_ring'      => [],
],
```

When `key_ring` is empty, the codec derives one key from `config('app.key')`, under the configured active ID (normally `app`). With a key ring, each entry is `key ID => key`; issuance uses only `active_key_id`, while verification selects the key using the token's `kid`.

Keys may be Laravel-style `base64:<standard-base64>` values, which are strictly decoded before use, or raw UTF-8 strings. The resulting key must be valid UTF-8 and at least 32 bytes. Key IDs must be printable ASCII, non-empty, and at most 64 bytes. An invalid key ring or active ID fails codec construction with `Invalid signed string`.

Issuance uses the current Unix second. The configured default TTL and every accepted TTL are capped at 24 hours. Positive integer TTLs and parseable `CarbonInterval` strings are accepted. Invalid or non-positive per-call overrides use the configured default; a non-positive configured default resolves to the 24-hour maximum. Verification permits 60 seconds of future-clock skew, rejects expired tokens, and rejects lifetimes greater than 24 hours.

## Rotation, versioning, replay, and expiration

To rotate keys, add the new key to `key_ring`, set it as `active_key_id`, and retain the old key entry for verification. Remove the old entry only after tokens issued with it can no longer be valid (including the 60-second clock-skew window). Removing it earlier makes those tokens fail as unknown-key tokens. There is no revocation list or replay protection in v1; expiration is the only built-in lifetime control.

The `v1` prefix and envelope `v: 1` are wire-protocol version markers. They are independent of the application's API version and cannot be changed without defining and implementing a compatible protocol version. A future version must not be assumed to be accepted by this codec.

## Verification and failure behavior

The codec checks the token size and grammar, decodes strict Base64url, validates canonical JSON and the exact envelope shape, resolves `kid`, verifies the HMAC, and then checks time validity. Malformed, oversized, unknown-key/version, cryptographic, claim, canonicalization, and expiry failures intentionally expose the same `Invalid signed string` message. The codec does not read or write replay state.

## OpenAPI representation and limitations

A property with the signed cast and transformer is emitted as an OpenAPI `type: string` property (with `nullable: true` when the PHP Data property is nullable or optional), not as the nested Data schema. The generated schema does not describe the `v1` wire grammar, HMAC, key ID, TTL, replay behavior, or the token's encoded contents. It also does not generate a token example automatically.

For a `GET` route, the generator turns request Data properties into query parameters rather than a request body, so a signed property is documented as a string query parameter. For other request methods it is a string field in the generated request-body schema. Add descriptions, examples, or transport-specific details with an overlay spec when consumers need them; the generated value remains authoritative for keys it already defines.

## URL, query, and transport considerations

The token's Base64url segments avoid `+`, `/`, and `=`; the token still contains literal dots between segments. When placing it in a URL query, use the client's normal URL-component encoding rather than concatenating unescaped input. Prefer a request body or header when practical, because URLs can be logged, cached, bookmarked, or exposed in referrers and intermediary systems.

The 16,384-byte codec limit is not a guarantee that every proxy, server, browser, or framework accepts a URL or header of that size; those components may impose lower limits. Treat the token as opaque transport data and do not decode, re-encode, trim, or normalize it in transit.
