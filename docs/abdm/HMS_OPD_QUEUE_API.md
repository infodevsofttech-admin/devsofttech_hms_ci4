# HMS OPD Queue API — Integration Guide

This document describes how your HMS software can read and write to the ABDM Bridge Gateway's OPD Token Queue via REST API.

**Base URL:** `https://abdm-bridge.e-atria.in`  
**Auth:** Bearer token or HTTP Basic — same credentials used for ABHA APIs  
**Content-Type:** `application/json`

---

## Overview

The OPD Queue holds two types of entries:

| Source | How created |
|---|---|
| **Scan & Share** | Patient scans the Health Facility QR with ABHA app — gateway creates token automatically |
| **Manual / Walk-in** | HMS calls `POST /api/v3/opd/token` for patients without ABHA |

Your HMS should:
1. **Poll** `GET /api/v3/opd/queue` to display the queue on the OPD screen
2. **Create** manual tokens with `POST /api/v3/opd/token` for walk-in patients
3. **Update** status with `PATCH /api/v3/opd/token/{id}` as the doctor calls/completes each patient

---

## Authentication

All three endpoints require the same Bearer token or Basic Auth used for ABHA APIs.

**Bearer Token:**
```http
Authorization: Bearer <your_api_token>
```

**Basic Auth:**
```http
Authorization: Basic <base64(username:password)>
```

---

## API Reference

### 1. Fetch OPD Queue

Retrieve the token queue for a date, with optional status filter and pagination.

**Request**
```
GET /api/v3/opd/queue
```

**Query Parameters**

| Parameter | Type | Default | Description |
|---|---|---|---|
| `date` | string | today | Queue date in `YYYY-MM-DD` format |
| `status` | string | (all) | Filter: `PENDING`, `CALLED`, `COMPLETED`, `CANCELLED` |
| `page` | integer | 1 | Page number (1-based) |
| `limit` | integer | 50 | Rows per page (max 100) |

**Example Request**
```bash
curl -X GET "https://abdm-bridge.e-atria.in/api/v3/opd/queue?date=2026-05-16&status=PENDING" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response `200 OK`**
```json
{
  "ok": 1,
  "date": "2026-05-16",
  "pagination": {
    "total": 2,
    "page": 1,
    "limit": 50,
    "pages": 1
  },
  "summary": {
    "pending": 2,
    "called": 0,
    "completed": 0,
    "cancelled": 0
  },
  "tokens": [
    {
      "id": 1,
      "token_number": 1,
      "patient_name": "Test Patient (QR Scan)",
      "abha_number": "91-9999-0001-0001",
      "abha_address": "testpatient@abdm",
      "gender": "M",
      "dob": "1990-06-15",
      "phone": "9000000001",
      "department": "OPD",
      "status": "PENDING",
      "source": "scan_share",
      "created_at": "2026-05-16 09:12:44",
      "updated_at": "2026-05-16 09:12:44"
    },
    {
      "id": 2,
      "token_number": 2,
      "patient_name": "Devender Singh",
      "abha_number": "91-5101-6530-5101",
      "abha_address": null,
      "gender": "M",
      "dob": null,
      "phone": "9720958717",
      "department": "Orthopaedics",
      "status": "PENDING",
      "source": "scan_share",
      "created_at": "2026-05-16 09:14:01",
      "updated_at": "2026-05-16 09:14:01"
    }
  ]
}
```

**Token Object Fields**

| Field | Type | Description |
|---|---|---|
| `id` | integer | Database row ID (use for status update) |
| `token_number` | integer | Sequential number for the day (1, 2, 3…) |
| `patient_name` | string | Full patient name |
| `abha_number` | string \| null | 14-digit ABHA number (null for manual walk-ins) |
| `abha_address` | string \| null | ABHA address handle (e.g. `name@abdm`) |
| `gender` | string \| null | `M`, `F`, or `O` |
| `dob` | string \| null | Date of birth `YYYY-MM-DD` |
| `phone` | string \| null | Mobile number |
| `department` | string \| null | Clinical context / department |
| `status` | string | `PENDING`, `CALLED`, `COMPLETED`, `CANCELLED` |
| `source` | string | `scan_share` (from ABHA QR) or `manual` (walk-in) |
| `created_at` | string | ISO datetime when token was created |
| `updated_at` | string | ISO datetime of last update |

---

### 2. Create Manual Token (Walk-in)

Add a token for a walk-in patient who does not use the ABHA QR scan.

**Request**
```
POST /api/v3/opd/token
```

**Body (JSON)**

| Field | Type | Required | Description |
|---|---|---|---|
| `patient_name` | string | **Yes** | Full name of the patient |
| `phone` | string | No | Mobile number |
| `abha_number` | string | No | If the patient knows their ABHA number |
| `gender` | string | No | `M`, `F`, or `O` |
| `department` | string | No | Department or clinical context (default: `General OPD`) |
| `date` | string | No | Token date `YYYY-MM-DD` (default: today) |

**Example Request**
```bash
curl -X POST "https://abdm-bridge.e-atria.in/api/v3/opd/token" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "patient_name": "Sanjay Mehra",
    "phone": "9812345678",
    "gender": "M",
    "department": "Cardiology"
  }'
```

**Response `201 Created`**
```json
{
  "ok": 1,
  "token_number": 3,
  "token_id": 7,
  "patient_name": "Sanjay Mehra",
  "date": "2026-05-16",
  "status": "PENDING",
  "token": {
    "id": 7,
    "hospital_id": 1,
    "patient_name": "Sanjay Mehra",
    "phone": "9812345678",
    "abha_number": null,
    "gender": "M",
    "context": "Cardiology",
    "token_number": 3,
    "token_date": "2026-05-16",
    "status": "PENDING",
    "created_at": "2026-05-16 10:03:22",
    "updated_at": "2026-05-16 10:03:22"
  }
}
```

---

### 3. Update Token Status

Mark a token as called, completed, or cancelled.

**Request**
```
PATCH /api/v3/opd/token/{id}
```

**Path Parameter**

| Parameter | Description |
|---|---|
| `id` | Token `id` from the queue list response |

**Body (JSON)**

| Field | Type | Required | Description |
|---|---|---|---|
| `status` | string | **Yes** | `CALLED`, `COMPLETED`, `CANCELLED`, or `PENDING` |

**Status Meanings**

| Status | When to use |
|---|---|
| `PENDING` | Patient is waiting (default) |
| `CALLED` | Doctor/nurse has called the patient |
| `COMPLETED` | Consultation is done |
| `CANCELLED` | Patient did not show up or appointment cancelled |

**Example Request**
```bash
curl -X PATCH "https://abdm-bridge.e-atria.in/api/v3/opd/token/7" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "CALLED"}'
```

**Response `200 OK`**
```json
{
  "ok": 1,
  "token_id": 7,
  "token_number": 3,
  "patient_name": "Sanjay Mehra",
  "status": "CALLED"
}
```

---

## Error Responses

All errors return `ok: 0` with an `error` code string.

| HTTP Code | `error` value | Meaning |
|---|---|---|
| `400` | `patient_name_required` | `patient_name` missing in token create |
| `400` | `invalid_date_format` | Date must be `YYYY-MM-DD` |
| `400` | `invalid_status` | Status value not in allowed list |
| `401` | `unauthorized` | Missing or invalid auth token |
| `403` | `hospital_not_resolved` | Token valid but no hospital linked |
| `404` | `token_not_found` | Token ID not found for your hospital |

**Example error:**
```json
{
  "ok": 0,
  "error": "invalid_status",
  "allowed": ["PENDING", "CALLED", "COMPLETED", "CANCELLED"]
}
```

---

## Integration Workflow Examples

### A — OPD Screen Auto-Refresh

Poll every 30 seconds to keep the OPD display current:

```javascript
// JavaScript / fetch example
async function refreshQueue() {
  const today = new Date().toISOString().split('T')[0];
  const res = await fetch(
    `https://abdm-bridge.e-atria.in/api/v3/opd/queue?date=${today}&status=PENDING`,
    { headers: { 'Authorization': 'Bearer YOUR_TOKEN' } }
  );
  const data = await res.json();
  // data.tokens is the live queue
  renderQueue(data.tokens, data.summary);
}
setInterval(refreshQueue, 30000);
refreshQueue();
```

### B — Doctor Calls Next Patient

```javascript
async function callPatient(tokenId) {
  await fetch(`https://abdm-bridge.e-atria.in/api/v3/opd/token/${tokenId}`, {
    method: 'PATCH',
    headers: {
      'Authorization': 'Bearer YOUR_TOKEN',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ status: 'CALLED' }),
  });
}
```

### C — Register Walk-in Patient from HMS Reception Screen

```javascript
async function addWalkIn(name, phone, dept) {
  const res = await fetch('https://abdm-bridge.e-atria.in/api/v3/opd/token', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer YOUR_TOKEN',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ patient_name: name, phone, department: dept }),
  });
  const data = await res.json();
  alert(`Token #${data.token_number} assigned to ${name}`);
}
```

### D — PHP / cURL Example

```php
<?php
$base = 'https://abdm-bridge.e-atria.in';
$token = 'YOUR_TOKEN';

// Fetch today's queue
$ch = curl_init("$base/api/v3/opd/queue?date=" . date('Y-m-d'));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ["Authorization: Bearer $token"],
]);
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

foreach ($response['tokens'] as $t) {
    echo "#{$t['token_number']} {$t['patient_name']} — {$t['status']}\n";
}
```

---

## Notes

- The gateway automatically resolves the hospital from the Bearer/Basic credentials. You do **not** send a `hospital_id` parameter.
- Scan & Share tokens (created when a patient scans the Facility QR with the ABHA app) appear in the queue automatically — no HMS action needed for creation.
- `source: "scan_share"` tokens will have `abha_number` and demographic data pre-filled from ABHA.
- `source: "manual"` tokens have only the fields your HMS provided.
- The `id` field is the stable identifier to use for status updates. `token_number` is the human-facing sequential number for the day.
