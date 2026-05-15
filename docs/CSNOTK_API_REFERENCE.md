# CSNOtk v9.0 API Reference & Integration Guide

**Status:** Finalized from Java source code analysis (v9.0 from C-DAC)  
**Date:** May 12, 2026  
**Context:** CSNOtk SearchController, CompositeDescription DTO analysis

---

## 1. REST API Endpoints

### Base URL Format
```
http://localhost:8080/csnoserv/rest/
```

### Main Search Endpoints

#### `/search/suggest` - Suggest/Search Terms
- **Method:** GET
- **Primary endpoint for diagnosis search**
- **Returns:** Array of `CompositeDescription` objects

**Query Parameters:**
```
GET /search/suggest?term=headache&state=active&semantictag=all&acceptability=all&returnlimit=20
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `term` | string | REQUIRED | Search text/query |
| `state` | string | `both` | `active` / `inactive` / `both` |
| `semantictag` | string | `all` | `all` / `disorder` / `finding` / `procedure` / etc. (combined with `++`) |
| `acceptability` | string | `all` | `preferred` / `preferredexcludingfsn` / `synonyms` / `acceptable` / `all` |
| `returnlimit` | int | `-1` | Max results. `-1` = all, e.g., `20`, `50` |
| `refsetid` | string | null | Optional refset filter |
| `parentid` | string | null | Optional parent concept ID (may conflict with refsetid) |
| `excludeparentconcept` | boolean | `false` | If `true`, excludes descriptions from parent ID |
| `callback` | string | null | JSONP callback for cross-domain requests |

**Response Status Codes:**
- `200 OK` – Success, returns JSON array
- `400 Bad Request` – Invalid parameter (e.g., invalid semantic tag, state, acceptability)
- `500 Internal Server Error` – Server error, check logs

---

#### `/lookup` - Lookup by Concept ID
- **Method:** GET
- **Returns:** `CompositeDescription` for a specific concept

**Example:**
```
GET /search/lookup?conceptid=25064002&semantictag=all&acceptability=all
```

---

#### `/validate/id` - Validate Concept ID
- **Method:** GET
- **Returns:** Boolean or status indicating if concept exists

**Example:**
```
GET /search/validate/id?id=25064002
```

**Response:**
```json
{
  "valueBoolean": true
}
```
or
```json
{
  "valid": true
}
```

---

#### `/map` - SNOMED CT to ICD-10 Mapping
- **Method:** GET
- **Optional: Only if ICD-10 map refset is configured**

---

## 2. Response Object: CompositeDescription

### JSON Structure Example

```json
[
  {
    "id": "900000000003001",
    "conceptId": "25064002",
    "term": "Headache",
    "typeId": "900000000000003001",
    "activeStatus": 1,
    "languageCode": "en",
    "caseSignificanceId": "900000000000017005",
    "hierarchy": "disorder",
    "isPreferredTerm": "true",
    "conceptState": "active",
    "conceptFsn": "Headache (disorder)",
    "definitionStatus": "FULLY_DEFINED",
    "moduleId": "900000000000207008",
    "effectiveTime": "2020-01-31",
    "refSetLangMember": {
      "id": "...",
      "refsetId": "..."
    }
  }
]
```

### Field Definitions

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Description ID (SNOMED CT identifier) |
| `conceptId` | string | **Concept ID (primary SNOMED code)** |
| `term` | string | **Description text/term** |
| `typeId` | string | Description type ID (FSN or Synonym) |
| `activeStatus` | int | `1` = active, `0` = inactive |
| `languageCode` | string | Language code (e.g., `en`) |
| `caseSignificanceId` | string | Case significance ID |
| `hierarchy` | string | **Semantic tag** (e.g., `disorder`, `finding`, `procedure`) |
| `isPreferredTerm` | string | `"true"` or `"false"` – Preferred terminology flag |
| `conceptState` | string | `active` or `inactive` |
| `conceptFsn` | string | **Fully Specified Name** (e.g., `"Headache (disorder)"`) – Use for display |
| `definitionStatus` | string | `FULLY_DEFINED` or `PRIMITIVE` |
| `moduleId` | string | Module identifier |
| `effectiveTime` | string | Date of last update (YYYY-MM-DD) |
| `refSetLangMember` | object | Language/region refset (e.g., US vs UK) |

### **Key Fields for HMS Integration:**

1. **`conceptId`** – Store in `snomed_concept` table (primary key for diagnosis)
2. **`term`** – Display in OPD prescription autocomplete
3. **`conceptFsn`** – Use for **full display** (includes hierarchy, e.g., "Headache (disorder)")
4. **`hierarchy`** – Semantic tag for filtering/categorization
5. **`isPreferredTerm`** – Sort preferred terms first in UI
6. **`activeStatus`** – Skip inactive concepts (status check)

---

## 3. PHP Integration: CsnotkTerminologyService

### Configuration (.env)

```dotenv
snomed.csnotk.enabled=true
snomed.csnotk.baseUrl=http://localhost:8080/csnoserv
snomed.csnotk.timeoutSec=10
```

### Usage in OPD_Prescription Controller

```php
use App\Libraries\CsnotkTerminologyService;

// In provisional_diagnosis_search() method:
$service = new CsnotkTerminologyService();

if ($service->isEnabled()) {
    $csnotkResults = $service->searchDiagnosis($query, 20);
    // Returns: [ ['concept_id' => '25064002', 'term' => 'Headache', ...], ... ]
}
```

### Expected Service Response Format

```php
[
    [
        'concept_id' => '25064002',
        'term' => 'Headache',
        'hierarchy' => 'disorder',
        'is_preferred' => 1,
        'source' => 'csnotk'
    ],
    ...
]
```

---

## 4. Testing & Validation

### Live Service Test URL

```
http://localhost:8080/csnoserv/
```

(Configure SNOMED CT files via web UI on initial setup)

### Test Search Query (via curl)

```bash
curl -X GET "http://localhost:8080/csnoserv/rest/search/suggest?term=headache&state=active&semantictag=all&acceptability=all&returnlimit=20"
```

### Expected Response

```json
[
  {
    "id": "900000000003001",
    "conceptId": "25064002",
    "term": "Headache",
    "hierarchy": "disorder",
    "isPreferredTerm": "true",
    "conceptFsn": "Headache (disorder)",
    "activeStatus": 1
  }
]
```

---

## 5. Deployment Checklist

- [ ] Deploy `csnoserv.war` to Tomcat webapps folder
- [ ] Configure JVM memory: `-Xms50% -Xmx80%` of available RAM
- [ ] Start Tomcat
- [ ] Access `http://localhost:8080/csnoserv/` 
- [ ] Provide SNOMED CT RF2 snapshot folder path on configuration page
- [ ] Wait for indexing (may take 5-30 min depending on file size)
- [ ] Test `/rest/search/suggest?term=test&returnlimit=5`
- [ ] Update HMS `.env` with CSNOServ base URL
- [ ] Test OPD diagnosis autocomplete

---

## 6. Semantic Tags Reference

Common SNOMED CT semantic tags:
- `disorder` – Diseases and conditions
- `finding` – Clinical findings
- `procedure` – Procedures
- `event` – Clinical events
- `organism` – Biological organisms
- `substance` – Chemical substances
- `physical object` – Physical items
- `specimen` – Specimen types
- `body structure` – Anatomical structures

Use in query: `/search/suggest?semantictag=disorder++finding` (combined with `++`)

---

## 7. Error Handling

| Scenario | HTTP Status | Response |
|----------|------------|----------|
| Invalid term parameter | 400 | Bad request |
| Invalid semantic tag | 400 | Bad request with error message |
| Database not initialized | 500 | Server error (check CSNOServ logs) |
| Timeout (no response) | timeout | (Network error) |

**Fallback Strategy:**  
If CSNOtk is unavailable or timeout, OPD_Prescription controller falls back to local SNOMED database query (snomed_description table).

---

## 8. Version Information

- **CSNOtk Suite Version:** 9.0
- **REST API Version:** Compatible with CSNOServ.war v9.0
- **Java Version Required:** OpenJRE 11 or later
- **Tomcat Version Tested:** 10.0, 10.1
- **SNOMED CT RF2 Release:** International Release or National Extensions supported

---

## 9. Additional Resources

- **CSNOServ README:** [d:\Workplace\HMS_CI4_OLD\CDAC\CSNOServ_v9.0\README.md](../CDAC/CSNOServ_v9.0/README.md)
- **CSNOLib Documentation:** [d:\Workplace\HMS_CI4_OLD\CDAC\CSNOLib_v9.0\README.md](../CDAC/CSNOLib_v9.0/README.md)
- **Java Source Code:** [d:\Workplace\HMS_CI4_OLD\CDAC\CSNOtk-Source-Code-v9.0\](../CDAC/CSNOtk-Source-Code-v9.0/)
- **C-DAC Official:** https://cdac.in/
