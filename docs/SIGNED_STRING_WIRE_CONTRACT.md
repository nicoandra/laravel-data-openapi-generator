# Signed-string wire contract

This document defines the canonical signed-string representation for Laravel Data objects. It is a protocol contract, not an implementation guide for a particular controller or transport. A codec implementing this document must not add fields, alternate encodings, or compatibility fallbacks without a new protocol version.

## Wire format

A signed string has exactly three dot-separated ASCII segments:

```text
v1.<payload-base64url>.<mac-base64url>
```

- `v1` is the wire version and is currently the only supported version.
- `payload-base64url` is the unpadded URL-safe Base64 encoding of the canonical UTF-8 JSON envelope.
- `mac-base64url` is the unpadded URL-safe Base64 encoding of the raw HMAC digest.
- Dots, whitespace, standard Base64, and Base64 padding are not accepted in a segment. The complete string must be ASCII and no longer than 16,384 bytes.

The version appears both in the prefix and in the envelope. They must agree. The duplicated value makes dispatch explicit while ensuring that the signed content declares its own version.

## Envelope

The decoded JSON envelope is an object with these required members:

```json
{
  "data": { "...": "..." },
  "exp": 1735689900,
  "iat": 1735689600,
  "kid": "2025-01",
  "nonce": "...",
  "v": 1
}
```

- `v` is the integer `1`.
- `data` is the serialized Laravel Data object. It must be a JSON object, not a scalar or top-level array.
- `iat` and `exp` are Unix timestamps in integer seconds. `exp` must be later than `iat`.
- `kid` is a non-empty ASCII key identifier, at most 64 bytes. It selects the verification key; it is not secret.
- `nonce` is exactly 16 cryptographically random bytes encoded as unpadded Base64url (22 ASCII characters). It identifies this issuance and supports replay prevention.

The envelope is deliberately small and self-contained. It does not contain a class name: the receiving endpoint chooses the expected Data class and maps `data` into that class after verification.

## Canonical JSON and signed bytes

The MAC input is **exactly** the UTF-8 byte sequence produced from the canonical envelope JSON. It is not the decoded values, a re-encoded wire string, or a JSON string containing the envelope.

Canonicalization rules are:

1. Decode JSON with object and array distinctions preserved and with a maximum nesting depth of 32.
2. Recursively sort every JSON object member by its UTF-8 member name, ascending by raw byte order. Array element order is preserved. The envelope therefore has the member order `data`, `exp`, `iat`, `kid`, `nonce`, `v`; object member order inside `data` is sorted the same way.
3. Member names and string values are encoded as valid UTF-8 JSON strings. JSON escaping uses `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`; control characters and quotes are still escaped as required by JSON.
4. Encode integers as JSON integers. Floating-point values are allowed only when finite and are encoded using PHP's `json_encode` representation with `JSON_PRESERVE_ZERO_FRACTION`. The codec must reject NaN and infinity rather than emitting non-standard JSON.
5. Emit no insignificant whitespace, no trailing newline, and no BOM. The result must be valid UTF-8 JSON.
6. `data` comes from the Data object's array representation (`toArray()` semantics). Unsupported values, resources, recursion, invalid UTF-8, and JSON encoding errors are rejected rather than silently converted.

This is intentionally a narrow PHP/Laravel contract. Canonical object ordering and fixed encoding flags prevent semantically identical PHP arrays from producing different MAC inputs.

## HMAC and digest encoding

Use HMAC-SHA-256:

```text
mac = HMAC-SHA-256(key, canonical-envelope-json-bytes)
```

The digest is the 32-byte raw HMAC output. Compare it with a constant-time comparison (`hash_equals` in PHP). Encode the raw digest with unpadded Base64url for the third segment. Hex, padded Base64, standard Base64, truncated MACs, and SHA-1 are not valid alternatives.

## Laravel application keys

The default key ring is derived from Laravel's `config('app.key')`:

- If the value begins with the literal prefix `base64:`, strictly Base64-decode the remainder. The decoded bytes are the HMAC key; the prefix is not part of the key.
- Otherwise, use the UTF-8 bytes of the configured string directly.
- A missing, empty, malformed, or non-UTF-8 key is an error. The decoded/direct key must contain at least 32 bytes. Do not hash, trim, lowercase, or otherwise transform it.

For rotation, `kid` maps to a key ring. The current `app.key` is registered under the active key ID; previous keys may remain under their old IDs for the maximum token lifetime plus clock skew. Verification selects only the key named by `kid`; an unknown ID fails. Issuance always uses the active ID and key. Rotation is therefore an explicit configuration change, not an attempt to try every available key.

## JSON decoding and verification

A decoder must perform all of these checks before constructing or returning a Data object:

1. Validate the ASCII shape and exact three-segment format.
2. Decode both Base64url segments strictly, rejecting padding, non-alphabet characters, and non-canonical encodings.
3. Reject payload JSON with a BOM, duplicate object keys, invalid UTF-8, trailing bytes, excessive depth, or a non-object envelope.
4. Re-encode the decoded value using the canonical rules above and require those bytes to be byte-for-byte identical to the payload bytes. This rejects alternate whitespace, member ordering, escaping, or numeric representations instead of normalizing them before verification.
5. Validate the envelope schema and types, including `v`, timestamps, key ID, nonce, and object `data`.
6. Resolve `kid`, compute HMAC over the original decoded canonical JSON bytes, and compare with `hash_equals`.
7. Check time validity. Allow 60 seconds of clock skew; reject tokens where `iat` is more than 60 seconds in the future, `exp` is at or before the current time, or `exp - iat` exceeds 24 hours.
8. Atomically consume `nonce` in the application replay store. A nonce already consumed is a replay failure.
9. Only then hydrate the expected Laravel Data class from `data` and apply its normal validation rules.

Malformed input, unknown version/key, invalid JSON, invalid claims, expired/not-yet-valid input, a MAC mismatch, a replay, and Data hydration failure all result in the same public failure category: `Invalid signed string`. Do not return partial data or reveal which check failed. Log only through an application-controlled channel and never log the signed value, key, or full payload by default. The replay-store write occurs only after successful cryptographic and temporal checks and must be atomic.

Issuers must generate a fresh nonce with a cryptographically secure random source and set `iat` to the current Unix second and `exp` to the requested expiry subject to the 24-hour maximum. A verifier without a durable atomic replay store must reject this protocol rather than silently accepting replayable tokens.

## Encode example

For a `ProfileData` object whose serialized data is `{ "email": "ada@example.test", "name": "Ada" }`, assume the key ID is `2025-01`, the nonce bytes are the ASCII value `1234567890abcdef`, and the HMAC key for this example is `0123456789abcdef0123456789abcdef` (a test-only key, not a production key).

The canonical JSON bytes are:

```text
{"data":{"email":"ada@example.test","name":"Ada"},"exp":1735689900,"iat":1735689600,"kid":"2025-01","nonce":"MTIzNDU2Nzg5MGFiY2RlZg","v":1}
```

The resulting signed string is:

```text
v1.eyJkYXRhIjp7ImVtYWlsIjoiYWRhQGV4YW1wbGUudGVzdCIsIm5hbWUiOiJBZGEifSwiZXhwIjoxNzM1Njg5OTAwLCJpYXQiOjE3MzU2ODk2MDAsImtpZCI6IjIwMjUtMDEiLCJub25jZSI6Ik1USXpORFUyTnpnNU1HRmlZMlJsWmciLCJ2IjoxfQ.adu1Vw0E3TOoO_ze5Ug36V4JXbf25-LG_iGYbAQbXgw
```

The rationale for signing the complete envelope is that expiry, nonce, and key selection cannot be modified independently of the Data values.

## Decode example

A receiver splits the string, decodes the middle segment to the exact JSON bytes above, reads `kid` as `2025-01`, and obtains that key from the configured ring. It recomputes HMAC-SHA-256 over those bytes and compares the result to the final segment. After the timestamp and nonce checks succeed, it passes only `data` to `ProfileData::from(...)` (or the project's equivalent Data hydration API). Any change to `name`, `exp`, `kid`, nonce, JSON ordering, escaping, or Base64 representation causes rejection.

## Deferred security features

Version 1 does not provide confidentiality: the payload is authenticated but readable by anyone holding the string. Encryption, asymmetric signatures, audience/issuer binding, endpoint binding, device binding, and nested Data-object signatures are explicitly deferred to a new version rather than being optional flags in version 1. Compression is also deferred to avoid size side channels and multiple byte representations. A replay store, expiry enforcement, key rotation, and the 16 KiB limit are part of this contract and are not deferred.
