# Signed-string wire contract

This document is authoritative for the version 1 signed-string protocol. Version 1 uses authentication and expiry only: it intentionally has no nonce and no replay store. Clients may retry the same valid token; a verifier checks only the signature and TTL (plus the protocol validations below).

## Wire format

A signed string has exactly three dot-separated ASCII segments:

```text
v1.<payload-base64url>.<mac-base64url>
```

`payload-base64url` is unpadded URL-safe Base64 of canonical UTF-8 JSON. `mac-base64url` is unpadded URL-safe Base64 of the 32-byte raw HMAC-SHA-256 digest. Padding, standard Base64, whitespace, extra segments, and non-alphabet characters are rejected. The complete token is at most 16,384 bytes.

## Envelope

The payload JSON is an object with exactly these members, in canonical order:

```json
{
  "data": { "...": "..." },
  "exp": 1735776000,
  "iat": 1735689600,
  "kid": "2025-01",
  "v": 1
}
```

- `v` is the integer `1` and agrees with the prefix.
- `data` is a JSON object from the Data object's `toArray()` representation.
- `iat` and `exp` are integer Unix timestamps, with `exp > iat`.
- `kid` is a non-empty printable ASCII key identifier of at most 64 bytes.
- No other envelope members are accepted. In particular, v1 has no nonce or replay claim.

## Canonical JSON and signing

The MAC input is exactly the canonical envelope JSON bytes. Decode with object/array distinctions preserved, `JSON_BIGINT_AS_STRING`, and maximum depth 32; reject invalid UTF-8, duplicate keys, BOMs, trailing bytes, non-object envelopes, unsupported values, and encoding errors. Recursively sort object members by raw UTF-8 byte order while preserving array order. Encode with `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION`, with no insignificant whitespace or trailing newline. Re-encoding must match the payload bytes exactly.

Compute `HMAC-SHA-256(key, canonical-envelope-json-bytes)` and compare with `hash_equals`. The signed payload is authenticated in full, including expiry and key selection.

## Keys and TTL

The default key is `config('app.key')`. A `base64:` value is strictly decoded and the decoded bytes are used directly; otherwise the configured UTF-8 string is used directly. Keys must be non-empty, valid UTF-8, and at least 32 bytes. A key ring selects the verification key by `kid`; issuance uses only the active key ID.

Issuance uses the current Unix second (`Carbon::now()`). The default and maximum TTL is 24 hours. Positive integer TTLs are accepted; parseable `CarbonInterval` strings are accepted and capped at 24 hours. Invalid or non-positive overrides use the configured default. Verification allows 60 seconds of clock skew, rejects future `iat`, expired `exp`, and lifetimes greater than 24 hours.

## Verification order and failures

The decoder strictly validates the three segments and Base64url, validates canonical JSON and the exact envelope schema, resolves `kid`, verifies HMAC, and checks time validity before returning `data`. All malformed, unknown-key/version, cryptographic, claim, expiry, size, and hydration failures use the same public `Invalid signed string` failure category. No replay state is read or written. Retry of an otherwise valid, unexpired token is supported.

## Example

For `data = {"email":"ada@example.test","name":"Ada"}`, `kid = "2025-01"`, `iat = 1735689600`, and a 24-hour TTL, canonical JSON is:

```text
{"data":{"email":"ada@example.test","name":"Ada"},"exp":1735776000,"iat":1735689600,"kid":"2025-01","v":1}
```

The HMAC key is configured separately and is never included in the envelope. Any change to data, timestamps, key ID, member order, escaping, or Base64 representation causes rejection.
