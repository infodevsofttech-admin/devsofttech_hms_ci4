<?php

$jsonStr = <<<'JSON'
{
  "resourceType": "Bundle",
  "id": "discharge-A26090000009-v1788660473",
  "meta": {
    "versionId": "1",
    "lastUpdated": "2026-09-06T07:37:53+05:30",
    "profile": [
      "https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentBundle"
    ],
    "security": [
      {
        "system": "http://terminology.hl7.org/CodeSystem/v3-Confidentiality",
        "code": "V",
        "display": "very restricted"
      }
    ]
  },
  "identifier": {
    "system": "https://hms.local/fhir/document",
    "value": "discharge-A26090000009-v1788660473"
  },
  "type": "document",
  "timestamp": "2026-09-06T07:37:53+05:30",
  "entry": [
    {
      "fullUrl": "urn:uuid:fdf46837-ab78-42a7-a550-8d64a35b6508",
      "resource": {
        "resourceType": "Composition",
        "id": "fdf46837-ab78-42a7-a550-8d64a35b6508",
        "status": "final",
        "type": {
          "coding": [
            {
              "system": "http://snomed.info/sct",
              "code": "373942005",
              "display": "Discharge summary"
            }
          ],
          "text": "Discharge Summary"
        },
        "title": "Discharge Summary",
        "date": "2026-09-06T07:37:53+05:30",
        "subject": {
          "reference": "urn:uuid:11cef8a6-4cd1-4733-afd0-92898eb57901",
          "display": "DEVENDER SINGH"
        },
        "author": [
          {
            "reference": "urn:uuid:0c561894-fc78-4bb9-ab25-a2aa05c739e6",
            "display": "Dr. R.K.SUNDRIYAL"
          }
        ],
        "custodian": {
          "reference": "urn:uuid:41fddd40-0707-47b6-a22b-17a70e068116",
          "display": "E-Atria Hospital"
        },
        "encounter": {
          "reference": "urn:uuid:ad844f8c-3803-4f39-a00e-5d908e580b55"
        },
        "meta": {
          "versionId": "1",
          "lastUpdated": "2026-09-06T07:37:53+05:30",
          "profile": [
            "https://nrces.in/ndhm/fhir/r4/StructureDefinition/DischargeSummaryRecord"
          ]
        },
        "language": "en-IN",
        "confidentiality": "N",
        "text": {
          "status": "generated",
          "div": "<div xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en-IN\" lang=\"en-IN\"><div style=\"border-bottom:2px solid #0284c7;padding-bottom:6px;margin-bottom:12px;\"><h2 style=\"margin:0 0 4px 0;color:#0369a1;\">E-Atria Hospital</h2><div style=\"font-size:14pt;font-weight:bold;color:#0284c7;\">IPD Discharge Summary</div></div><table style=\"width:100%;border-collapse:collapse;margin-bottom:14px;background:#f8fafc;font-size:10pt;\"><tr><td style=\"border:1px solid #cbd5e1;padding:6px;\"><strong>Patient:</strong> DEVENDER SINGH</td><td style=\"border:1px solid #cbd5e1;padding:6px;\"><strong>IPD No:</strong> A26090000009-v1788660473</td></tr><tr><td style=\"border:1px solid #cbd5e1;padding:6px;\"><strong>Gender / DOB:</strong> Male / 1979-03-28</td><td style=\"border:1px solid #cbd5e1;padding:6px;\"><strong>ABHA Number:</strong> 91510165305101</td></tr><tr><td style=\"border:1px solid #cbd5e1;padding:6px;\"><strong>Admission Date:</strong> 2026-08-28T10:00:00+05:30</td><td style=\"border:1px solid #cbd5e1;padding:6px;\"><strong>Discharge Date:</strong> 2026-09-06T07:37:53+05:30</td></tr><tr><td style=\"border:1px solid #cbd5e1;padding:6px;\"><strong>Location / Bed:</strong> Ward: General Ward, Bed: GW-12</td><td style=\"border:1px solid #cbd5e1;padding:6px;\"><strong>Doctor:</strong> Dr. R.K.SUNDRIYAL</td></tr></table></div>"
        },
        "section": [
          {
            "title": "Chief complaints",
            "code": {
              "coding": [
                {
                  "system": "http://snomed.info/sct",
                  "code": "422843007",
                  "display": "Chief complaint section"
                }
              ]
            },
            "entry": [
              {
                "reference": "urn:uuid:ee9837f1-95a6-41fc-aa4f-f245744fc8c6",
                "display": "Abdominal Pain"
              },
              {
                "reference": "urn:uuid:e6d1cece-4973-46d4-ace5-d96047401b20",
                "display": "Cough"
              },
              {
                "reference": "urn:uuid:a712dc95-6feb-4b11-a7ea-5699b8845317",
                "display": "Vomiting"
              }
            ]
          },
          {
            "title": "Medical History",
            "code": {
              "coding": [
                {
                  "system": "http://snomed.info/sct",
                  "code": "1003642006",
                  "display": "Past medical history section"
                }
              ]
            },
            "entry": [
              {
                "reference": "urn:uuid:3a84e6c1-a483-4cde-a4a3-9b3ba8d5ebf7",
                "display": "VIRAL DISEASE"
              }
            ]
          },
          {
            "title": "Procedures",
            "code": {
              "coding": [
                {
                  "system": "http://snomed.info/sct",
                  "code": "1003640003",
                  "display": "History of past procedure section"
                }
              ]
            },
            "entry": [
              {
                "reference": "urn:uuid:95411403-5e4f-4c93-acee-fe7457d5acf5",
                "display": "Laparoscopy of rectum"
              }
            ]
          },
          {
            "title": "Medications",
            "code": {
              "coding": [
                {
                  "system": "http://snomed.info/sct",
                  "code": "1003606003",
                  "display": "Medication history section"
                }
              ]
            },
            "entry": [
              {
                "reference": "urn:uuid:a89d0861-d785-4103-aa88-9cb79c119e6d",
                "display": "ACILOC"
              },
              {
                "reference": "urn:uuid:657d375d-6fa1-4971-a112-a789d9b2904d",
                "display": "PANTOP DSR"
              }
            ]
          },
          {
            "title": "Care Plan",
            "code": {
              "coding": [
                {
                  "system": "http://snomed.info/sct",
                  "code": "734163000",
                  "display": "Care plan"
                }
              ]
            },
            "entry": [
              {
                "reference": "urn:uuid:1b57be60-c849-4bd7-a090-7da51d4ab193",
                "display": "Follow Up"
              }
            ]
          },
          {
            "title": "Document Reference",
            "code": {
              "coding": [
                {
                  "system": "http://snomed.info/sct",
                  "code": "373942005",
                  "display": "Discharge summary"
                }
              ]
            },
            "entry": [
              {
                "reference": "urn:uuid:77c657dd-25fa-4ae0-af23-b46918820bb9",
                "display": "IPD Discharge Summary"
              }
            ]
          }
        ]
      }
    },
    {
      "fullUrl": "urn:uuid:11cef8a6-4cd1-4733-afd0-92898eb57901",
      "resource": {
        "resourceType": "Patient",
        "id": "11cef8a6-4cd1-4733-afd0-92898eb57901",
        "meta": {
          "profile": [
            "https://nrces.in/ndhm/fhir/r4/StructureDefinition/Patient"
          ]
        },
        "text": {
          "status": "generated",
          "div": "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p><b>Patient:</b> DEVENDER SINGH (Male)</p></div>"
        },
        "identifier": [
          {
            "type": {
              "coding": [
                {
                  "system": "http://terminology.hl7.org/CodeSystem/v2-0203",
                  "code": "MR",
                  "display": "Medical record number"
                }
              ]
            },
            "system": "https://hms.local/patient-id",
            "value": "11"
          },
          {
            "type": {
              "coding": [
                {
                  "system": "http://terminology.hl7.org/CodeSystem/v2-0203",
                  "code": "SB",
                  "display": "Social Beneficiary Identifier"
                }
              ]
            },
            "system": "https://healthid.ndhm.gov.in",
            "value": "91510165305101"
          }
        ],
        "name": [
          {
            "use": "official",
            "text": "DEVENDER SINGH"
          }
        ],
        "gender": "male",
        "birthDate": "1979-03-28"
      }
    },
    {
      "fullUrl": "urn:uuid:0c561894-fc78-4bb9-ab25-a2aa05c739e6",
      "resource": {
        "resourceType": "Practitioner",
        "id": "0c561894-fc78-4bb9-ab25-a2aa05c739e6",
        "meta": {
          "profile": [
            "https://nrces.in/ndhm/fhir/r4/StructureDefinition/Practitioner"
          ]
        },
        "text": {
          "status": "generated",
          "div": "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p><b>Practitioner:</b> Dr. R.K.SUNDRIYAL</p></div>"
        },
        "identifier": [
          {
            "type": {
              "coding": [
                {
                  "system": "http://terminology.hl7.org/CodeSystem/v2-0203",
                  "code": "MD",
                  "display": "Medical License number"
                }
              ]
            },
            "system": "https://doctor.ndhm.gov.in",
            "value": "HPR-4"
          }
        ],
        "name": [
          {
            "use": "official",
            "text": "Dr. R.K.SUNDRIYAL"
          }
        ]
      }
    },
    {
      "fullUrl": "urn:uuid:41fddd40-0707-47b6-a22b-17a70e068116",
      "resource": {
        "resourceType": "Organization",
        "id": "41fddd40-0707-47b6-a22b-17a70e068116",
        "meta": {
          "profile": [
            "https://nrces.in/ndhm/fhir/r4/StructureDefinition/Organization"
          ]
        },
        "text": {
          "status": "generated",
          "div": "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p><b>Organization:</b> E-Atria Hospital</p></div>"
        },
        "name": "E-Atria Hospital",
        "identifier": [
          {
            "type": {
              "coding": [
                {
                  "system": "http://terminology.hl7.org/CodeSystem/v2-0203",
                  "code": "PRN",
                  "display": "Provider number"
                }
              ]
            },
            "system": "https://facility.ndhm.gov.in",
            "value": "IN0510000871"
          }
        ]
      }
    },
    {
      "fullUrl": "urn:uuid:ee9837f1-95a6-41fc-aa4f-f245744fc8c6",
      "resource": {
        "resourceType": "Condition",
        "id": "ee9837f1-95a6-41fc-aa4f-f245744fc8c6",
        "meta": {
          "profile": [
            "https://nrces.in/ndhm/fhir/r4/StructureDefinition/Condition"
          ]
        },
        "text": {
          "status": "generated",
          "div": "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p><b>Condition:</b> Abdominal Pain</p></div>"
        },
        "clinicalStatus": {
          "coding": [
            {
              "system": "http://terminology.hl7.org/CodeSystem/condition-clinical",
              "code": "active",
              "display": "Active"
            }
          ]
        },
        "code": {
          "coding": [
            {
              "system": "http://snomed.info/sct",
              "code": "21522000",
              "display": "Abdominal pain"
            }
          ],
          "text": "Abdominal Pain"
        },
        "subject": {
          "reference": "urn:uuid:11cef8a6-4cd1-4733-afd0-92898eb57901",
          "display": "DEVENDER SINGH"
        }
      }
    },
    {
      "fullUrl": "urn:uuid:e6d1cece-4973-46d4-ace5-d96047401b20",
      "resource": {
        "resourceType": "Condition",
        "id": "e6d1cece-4973-46d4-ace5-d96047401b20",
        "meta": {
          "profile": [
            "https://nrces.in/ndhm/fhir/r4/StructureDefinition/Condition"
          ]
        },
        "text": {
          "status": "generated",
          "div": "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p><b>Condition:</b> Cough</p></div>"
        },
        "clinicalStatus": {
          "coding": [
            {
              "system": "http://terminology.hl7.org/CodeSystem/condition-clinical",
              "code": "active",
              "display": "Active"
            }
          ]
        },
        "code": {
          "coding": [
            {
              "system": "http://snomed.info/sct",
              "code": "49727002",
              "display": "Cough"
            }
          ],
          "text": "Cough"
        },
        "subject": {
          "reference": "urn:uuid:11cef8a6-4cd1-4733-afd0-92898eb57901",
          "display": "DEVENDER SINGH"
        }
      }
    },
    {
      "fullUrl": "urn:uuid:a712dc95-6feb-4b11-a7ea-5699b8845317",
      "resource": {
        "resourceType": "Condition",
        "id": "a712dc95-6feb-4b11-a7ea-5699b8845317",
        "meta": {
          "profile": [
            "https://nrces.in/ndhm/fhir/r4/StructureDefinition/Condition"
          ]
        },
        "text": {
          "status": "generated",
          "div": "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p><b>Condition:</b> Vomiting</p></div>"
        },
        "clinicalStatus": {
          "coding": [
            {
              "system": "http://terminology.hl7.org/CodeSystem/condition-clinical",
              "code": "active",
              "display": "Active"
            }
          ]
        },
        "code": {
          "coding": [
            {
              "system": "http://snomed.info/sct",
              "code": "422400008",
              "display": "Vomiting"
            }
          ],
          "text": "Vomiting"
        },
        "subject": {
          "reference": "urn:uuid:11cef8a6-4cd1-4733-afd0-92898eb57901",
          "display": "DEVENDER SINGH"
        }
      }
    },
    {
      "fullUrl": "urn:uuid:3a84e6c1-a483-4cde-a4a3-9b3ba8d5ebf7",
      "resource": {
        "resourceType": "Condition",
        "id": "3a84e6c1-a483-4cde-a4a3-9b3ba8d5ebf7",
        "meta": {
          "profile": [
            "https://nrces.in/ndhm/fhir/r4/StructureDefinition/Condition"
          ]
        },
        "text": {
          "status": "generated",
          "div": "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p><b>Condition:</b> VIRAL DISEASE</p></div>"
        },
        "clinicalStatus": {
          "coding": [
            {
              "system": "http://terminology.hl7.org/CodeSystem/condition-clinical",
              "code": "active",
              "display": "Active"
            }
          ]
        },
        "code": {
          "coding": [
            {
              "system": "http://snomed.info/sct",
              "code": "404684003",
              "display": "Clinical finding"
            }
          ],
          "text": "VIRAL DISEASE"
        },
        "subject": {
          "reference": "urn:uuid:11cef8a6-4cd1-4733-afd0-92898eb57901",
          "display": "DEVENDER SINGH"
        }
      }
    },
    {
      "fullUrl": "urn:uuid:ad844f8c-3803-4f39-a00e-5d908e580b55",
      "resource": {
        "resourceType": "Encounter",
        "id": "ad844f8c-3803-4f39-a00e-5d908e580b55",
        "meta": {
          "profile": [
            "https://nrces.in/ndhm/fhir/r4/StructureDefinition/Encounter"
          ]
        },
        "text": {
          "status": "generated",
          "div": "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p><b>Encounter:</b> inpatient encounter A26090000009-v1788660473</p></div>"
        },
        "identifier": [
          {
            "system": "https://hms.local/encounter-id",
            "value": "A26090000009-v1788660473"
          },
          {
            "system": "https://hms.local/ipd-number",
            "value": "A26090000009-v1788660473"
          }
        ],
        "status": "finished",
        "class": {
          "system": "http://terminology.hl7.org/CodeSystem/v3-ActCode",
          "code": "IMP",
          "display": "inpatient encounter"
        },
        "subject": {
          "reference": "urn:uuid:11cef8a6-4cd1-4733-afd0-92898eb57901",
          "display": "DEVENDER SINGH"
        },
        "period": {
          "start": "2026-08-28T10:00:00+05:30",
          "end": "2026-09-06T07:37:53+05:30"
        },
        "hospitalization": {
          "dischargeDisposition": {
            "coding": [
              {
                "system": "http://terminology.hl7.org/CodeSystem/discharge-disposition",
                "code": "home",
                "display": "Home"
              }
            ],
            "text": "Discharged to Home Care"
          }
        }
      }
    },
    {
      "fullUrl": "urn:uuid:95411403-5e4f-4c93-acee-fe7457d5acf5",
      "resource": {
        "resourceType": "Procedure",
        "id": "95411403-5e4f-4c93-acee-fe7457d5acf5",
        "meta": {
          "profile": [
            "https://nrces.in/ndhm/fhir/r4/StructureDefinition/Procedure"
          ]
        },
        "text": {
          "status": "generated",
          "div": "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p><b>Procedure:</b> Laparoscopy of rectum</p></div>"
        },
        "status": "completed",
        "code": {
          "coding": [
            {
              "system": "http://snomed.info/sct",
              "code": "86174004",
              "display": "Laparoscopy"
            }
          ],
          "text": "Laparoscopy of rectum"
        },
        "subject": {
          "reference": "urn:uuid:11cef8a6-4cd1-4733-afd0-92898eb57901",
          "display": "DEVENDER SINGH"
        },
        "performedDateTime": "2026-08-28T00:00:00+05:30"
      }
    },
    {
      "fullUrl": "urn:uuid:a89d0861-d785-4103-aa88-9cb79c119e6d",
      "resource": {
        "resourceType": "MedicationRequest",
        "id": "a89d0861-d785-4103-aa88-9cb79c119e6d",
        "meta": {
          "profile": [
            "https://nrces.in/ndhm/fhir/r4/StructureDefinition/MedicationRequest"
          ]
        },
        "text": {
          "status": "generated",
          "div": "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p><b>Medication:</b> ACILOC - EMPTY STOMACH | BD |    |     (BD)</p></div>"
        },
        "status": "active",
        "intent": "order",
        "subject": {
          "reference": "urn:uuid:11cef8a6-4cd1-4733-afd0-92898eb57901",
          "display": "DEVENDER SINGH"
        },
        "authoredOn": "2026-09-06T07:37:53+05:30",
        "medicationCodeableConcept": {
          "coding": [
            {
              "system": "http://snomed.info/sct",
              "code": "105904009",
              "display": "Type of drug"
            }
          ],
          "text": "ACILOC"
        },
        "requester": {
          "reference": "urn:uuid:0c561894-fc78-4bb9-ab25-a2aa05c739e6",
          "display": "Dr. R.K.SUNDRIYAL"
        },
        "dosageInstruction": [
          {
            "text": "Take 1 Tablet Twice daily before food / empty stomach for 5 days",
            "timing": {
              "repeat": {
                "frequency": 2,
                "period": 1,
                "periodUnit": "d"
              }
            },
            "route": {
              "coding": [
                {
                  "system": "http://snomed.info/sct",
                  "code": "26643006",
                  "display": "Oral Route"
                }
              ]
            },
            "additionalInstruction": [
              {
                "coding": [
                  {
                    "system": "http://snomed.info/sct",
                    "code": "307165006",
                    "display": "Before meals"
                  }
                ],
                "text": "Before food / Empty stomach"
              }
            ]
          }
        ]
      }
    },
    {
      "fullUrl": "urn:uuid:657d375d-6fa1-4971-a112-a789d9b2904d",
      "resource": {
        "resourceType": "MedicationRequest",
        "id": "657d375d-6fa1-4971-a112-a789d9b2904d",
        "meta": {
          "profile": [
            "https://nrces.in/ndhm/fhir/r4/StructureDefinition/MedicationRequest"
          ]
        },
        "text": {
          "status": "generated",
          "div": "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p><b>Medication:</b> PANTOP DSR - EMPTY STOMACH | OD |    |     (OD)</p></div>"
        },
        "status": "active",
        "intent": "order",
        "subject": {
          "reference": "urn:uuid:11cef8a6-4cd1-4733-afd0-92898eb57901",
          "display": "DEVENDER SINGH"
        },
        "authoredOn": "2026-09-06T07:37:53+05:30",
        "medicationCodeableConcept": {
          "coding": [
            {
              "system": "http://snomed.info/sct",
              "code": "105904009",
              "display": "Type of drug"
            }
          ],
          "text": "PANTOP DSR"
        },
        "requester": {
          "reference": "urn:uuid:0c561894-fc78-4bb9-ab25-a2aa05c739e6",
          "display": "Dr. R.K.SUNDRIYAL"
        },
        "dosageInstruction": [
          {
            "text": "Take 1 Tablet Once daily before food / empty stomach for 5 days",
            "timing": {
              "repeat": {
                "frequency": 1,
                "period": 1,
                "periodUnit": "d"
              }
            },
            "route": {
              "coding": [
                {
                  "system": "http://snomed.info/sct",
                  "code": "26643006",
                  "display": "Oral Route"
                }
              ]
            },
            "additionalInstruction": [
              {
                "coding": [
                  {
                    "system": "http://snomed.info/sct",
                    "code": "307165006",
                    "display": "Before meals"
                  }
                ],
                "text": "Before food / Empty stomach"
              }
            ]
          }
        ]
      }
    },
    {
      "fullUrl": "urn:uuid:1b57be60-c849-4bd7-a090-7da51d4ab193",
      "resource": {
        "resourceType": "CarePlan",
        "id": "1b57be60-c849-4bd7-a090-7da51d4ab193",
        "meta": {
          "profile": [
            "https://nrces.in/ndhm/fhir/r4/StructureDefinition/CarePlan"
          ]
        },
        "text": {
          "status": "generated",
          "div": "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p><b>Follow Up:</b> Review After 1 Month (02-10-2026) or as and when required</p></div>"
        },
        "status": "active",
        "intent": "plan",
        "title": "Follow Up",
        "description": "Review After 1 Month (02-10-2026) or as and when required",
        "subject": {
          "reference": "urn:uuid:11cef8a6-4cd1-4733-afd0-92898eb57901",
          "display": "DEVENDER SINGH"
        },
        "category": [
          {
            "coding": [
              {
                "system": "http://snomed.info/sct",
                "code": "734163000",
                "display": "Care plan"
              }
            ]
          }
        ]
      }
    },
    {
      "fullUrl": "urn:uuid:77c657dd-25fa-4ae0-af23-b46918820bb9",
      "resource": {
        "resourceType": "DocumentReference",
        "id": "77c657dd-25fa-4ae0-af23-b46918820bb9",
        "meta": {
          "profile": [
            "https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentReference"
          ]
        },
        "text": {
          "status": "generated",
          "div": "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p><b>Attached Document:</b> IPD Discharge Summary</p></div>"
        },
        "status": "current",
        "docStatus": "final",
        "type": {
          "coding": [
            {
              "system": "http://snomed.info/sct",
              "code": "373942005",
              "display": "Discharge summary"
            }
          ],
          "text": "IPD Discharge Summary"
        },
        "subject": {
          "reference": "urn:uuid:11cef8a6-4cd1-4733-afd0-92898eb57901",
          "display": "DEVENDER SINGH"
        },
        "description": "IPD Discharge Summary",
        "content": [
          {
            "attachment": {
              "contentType": "application/pdf",
              "language": "en-IN",
              "data": "JVBERi0xLjQK...",
              "title": "IPD Discharge Summary",
              "creation": "2026-09-06T07:37:53+05:30"
            }
          }
        ]
      }
    }
  ]
}
JSON;

$bundle = json_decode($jsonStr, true);
if (!$bundle) {
    echo "JSON decode error: " . json_last_error_msg() . "\n";
    exit(1);
}

echo "JSON decode SUCCESS!\n";
echo "Resource count: " . count($bundle['entry']) . "\n";

// Check all internal references
$urls = [];
foreach ($bundle['entry'] as $e) {
    $urls[$e['fullUrl']] = $e['resource']['resourceType'] . '/' . $e['resource']['id'];
}

echo "\n--- INTERNAL REFERENCES CHECK ---\n";
$missingRefs = [];
array_walk_recursive($bundle, function($value, $key) use ($urls, &$missingRefs) {
    if ($key === 'reference' && str_starts_with($value, 'urn:uuid:')) {
        if (!isset($urls[$value])) {
            $missingRefs[] = $value;
        }
    }
});

if (empty($missingRefs)) {
    echo "All references resolve perfectly! (0 missing)\n";
} else {
    echo "Missing references: " . implode(", ", $missingRefs) . "\n";
}

// Check each resource type and profile
echo "\n--- RESOURCES & PROFILES ---\n";
foreach ($bundle['entry'] as $idx => $e) {
    $r = $e['resource'];
    $profile = $r['meta']['profile'][0] ?? 'NONE';
    echo "[$idx] {$r['resourceType']} (id: {$r['id']}) -> profile: $profile\n";
}

// Compare composition sections against NRCES standard
$comp = $bundle['entry'][0]['resource'];
echo "\n--- COMPOSITION SECTIONS ---\n";
foreach ($comp['section'] as $s) {
    $code = $s['code']['coding'][0]['code'] ?? 'no code';
    $entriesCount = count($s['entry'] ?? []);
    echo "Section: '{$s['title']}' | code: $code | entries: $entriesCount\n";
}
