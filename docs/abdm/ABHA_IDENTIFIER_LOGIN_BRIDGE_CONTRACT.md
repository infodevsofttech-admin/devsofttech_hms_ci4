# ABHA Identifier Login Bridge Contract

Version: 1.0

This contract supports the HMS `/billing/patient` modal flow:

1. Enter ABHA number or ABHA address.
2. Choose an authentication method returned by ABDM.
3. Send and verify OTP with a 60-second resend cooldown.
4. Review the verified ABHA profile and official ABHA card.
5. Compare with an existing HMS patient before linking or updating.

Every request requires:

- `Authorization: Bearer {hospital-token}`
- `Content-Type: application/json`
- `hfr_id` in the request body

The Bridge must never return an ABDM session or X-token to the browser-facing HMS response.

## 1. Search ABHA Login Account

Existing endpoint:

`POST /api/v3/abha/login/search`

Request:

```json
{
  "type": "abha-address",
  "value": "patient@sbx",
  "hfr_id": "IN0510000828"
}
```

`type` must be `abha-address` or `abha-number`.

Required success response:

```json
{
  "ok": 1,
  "data": {
    "txnId": "search-transaction-id",
    "status": "ACTIVE",
    "authMethods": ["MOBILE_OTP", "AADHAAR_OTP"],
    "blockedAuthMethods": [],
    "maskedMobile": "******1369",
    "accounts": [
      {
        "name": "Ashish Soni",
        "ABHANumber": "91201531584567",
        "abhaAddress": "ashishtest6@sbx",
        "status": "ACTIVE"
      }
    ]
  },
  "request_id": "REQ-..."
}
```

The search transaction must remain valid for the next OTP request. Do not replace it with a new mobile-search transaction.

## 2. Request Account-Bound OTP

New endpoint:

`POST /api/v3/abha/login/request-otp`

Request:

```json
{
  "txn_id": "search-transaction-id",
  "auth_method": "MOBILE_OTP",
  "abha_id": "91201531584567",
  "abha_address": "ashishtest6@sbx",
  "hfr_id": "IN0510000828"
}
```

Rules:

- `auth_method` must have been returned by the search response.
- Supported HMS methods are `MOBILE_OTP` and `AADHAAR_OTP`.
- `MOBILE_OTP` means the mobile registered with the ABHA account.
- `AADHAAR_OTP` means the mobile linked with Aadhaar.
- The operator must not enter a mobile number.
- Reject blocked or unavailable methods.
- Apply Bridge-side rate limiting in addition to the HMS timer.

Required success response:

```json
{
  "ok": 1,
  "data": {
    "txnId": "otp-transaction-id",
    "authMethod": "MOBILE_OTP",
    "maskedMobile": "******1369",
    "resendAfter": 60
  },
  "request_id": "REQ-..."
}
```

For rate limiting, return HTTP `429` with `retry_after` when appropriate.

## 3. Verify Account-Bound OTP

New endpoint:

`POST /api/v3/abha/login/verify-otp`

Request:

```json
{
  "txn_id": "otp-transaction-id",
  "auth_method": "MOBILE_OTP",
  "otp": "123456",
  "hfr_id": "IN0510000828"
}
```

Bridge responsibilities after successful ABDM OTP verification:

1. Use the server-side ABDM session/X-token to fetch the official profile.
2. Call the ABDM profile endpoint equivalent to `GET /profile/account`.
3. Call the official card endpoint equivalent to `GET /profile/account/abha-card`.
4. Normalize the profile and card into one response.
5. Persist only required audit/profile fields according to Bridge policy.
6. Do not return session tokens, access tokens, Aadhaar numbers, or unneeded raw ABDM payloads.

Required success response:

```json
{
  "ok": 1,
  "data": {
    "profile": {
      "ABHANumber": "91201531584567",
      "preferredAddress": "ashishtest6@sbx",
      "name": "Ashish Soni",
      "gender": "M",
      "dob": "2001-02-19",
      "mobile": "9098221369",
      "address": "Rathkhana School Road, Gwalior",
      "districtName": "Gwalior",
      "stateName": "Madhya Pradesh",
      "pinCode": "474001",
      "profilePhoto": "base64-jpeg-without-data-prefix"
    },
    "card_base64": "base64-png-or-pdf-without-data-prefix",
    "card_content_type": "image/png"
  },
  "request_id": "REQ-..."
}
```

The response may also use `gateway_patient` for profile data because HMS already normalizes that shape.

## 4. Standard Errors

Use the Bridge standard shape:

```json
{
  "ok": 0,
  "error_code": "OTP_RATE_LIMITED",
  "message": "Wait before requesting another OTP.",
  "details": {
    "retry_after": 42
  },
  "request_id": "REQ-..."
}
```

Recommended codes:

- `ABHA_NOT_FOUND` (`404`)
- `AUTH_METHOD_UNAVAILABLE` (`422`)
- `AUTH_METHOD_BLOCKED` (`423`)
- `OTP_INVALID` (`422`)
- `OTP_EXPIRED` (`410`)
- `OTP_RATE_LIMITED` (`429`)
- `PROFILE_FETCH_FAILED` (`502`)
- `CARD_FETCH_FAILED` (`502`) only when card failure should block; otherwise return profile with an empty card and a warning

## 5. Security and Audit

- Redact OTP, Aadhaar, tokens, and profile/card base64 from logs.
- Bind OTP transactions to the hospital, HFR ID, ABHA account, and selected auth method.
- Expire transactions according to ABDM rules.
- Enforce request throttling per hospital, ABHA identity, and transaction.
- Return a `request_id` for all failures.
- Keep the full ABDM session and card fetch server-side.