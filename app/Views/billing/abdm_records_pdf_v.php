<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= esc($title ?? 'ABDM Fetched Health Records') ?></title>
    <style>
        body {
            font-family: 'freeserif', 'DejaVu Sans', sans-serif;
            font-size: 9.5pt;
            color: #1e293b;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }

        /* Header Layout */
        table.header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.header-table td {
            vertical-align: top;
            padding: 0;
        }
        .hospital-logo {
            max-height: 48px;
            max-width: 170px;
            margin-bottom: 4px;
        }
        .hospital-name {
            font-size: 15pt;
            font-weight: bold;
            color: #0f2d59;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }
        .hospital-meta {
            font-size: 8pt;
            color: #475569;
            line-height: 1.35;
            margin-top: 2px;
        }

        .abdm-badge-box {
            background-color: #f0f7ff;
            border: 1px solid #bae6fd;
            padding: 6px 10px;
            text-align: right;
            border-radius: 4px;
        }
        .abdm-badge-title {
            font-size: 8pt;
            font-weight: bold;
            color: #0284c7;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .abdm-badge-sub {
            font-size: 7.5pt;
            color: #0369a1;
            margin-top: 1px;
        }
        .abdm-report-type {
            font-size: 10.5pt;
            font-weight: bold;
            color: #0f172a;
            margin-top: 3px;
        }
        .report-meta-text {
            font-size: 8pt;
            color: #64748b;
            margin-top: 3px;
        }

        .accent-divider {
            height: 3px;
            background-color: #0284c7;
            margin: 6px 0 10px 0;
        }

        /* Patient Info Card */
        table.patient-card {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
        }
        table.patient-card td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            font-size: 8.5pt;
            vertical-align: top;
        }
        .p-lbl {
            font-size: 7pt;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            display: block;
            margin-bottom: 2px;
        }
        .p-val {
            font-size: 9pt;
            font-weight: bold;
            color: #0f172a;
        }
        .p-val-lg {
            font-size: 10pt;
            font-weight: bold;
            color: #0f2d59;
            text-transform: uppercase;
        }
        .abha-val {
            color: #0284c7;
            font-weight: bold;
        }
        .badge-verified {
            display: inline-block;
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
            font-size: 7pt;
            font-weight: bold;
            padding: 1px 4px;
            border-radius: 3px;
            margin-left: 4px;
        }

        /* Document Card */
        .doc-card {
            margin-bottom: 14px;
            border: 1px solid #cbd5e1;
            page-break-inside: avoid;
        }
        table.doc-header {
            width: 100%;
            border-collapse: collapse;
            background-color: #0f2d59;
            color: #ffffff;
        }
        table.doc-header td {
            padding: 6px 10px;
            vertical-align: middle;
        }
        .doc-title {
            font-size: 11pt;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 0.3px;
        }
        .doc-badge {
            background-color: #0284c7;
            color: #ffffff;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
            text-transform: uppercase;
            margin-right: 6px;
        }
        .doc-date {
            font-size: 8.5pt;
            color: #e0f2fe;
            text-align: right;
        }

        table.doc-submeta {
            width: 100%;
            border-collapse: collapse;
            background-color: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
        }
        table.doc-submeta td {
            padding: 4px 10px;
            font-size: 8.5pt;
            color: #334155;
        }

        .doc-body {
            padding: 8px 10px;
            background-color: #ffffff;
        }

        /* Section Styling */
        .section-wrap {
            margin-bottom: 10px;
        }
        .section-heading {
            font-size: 9pt;
            font-weight: bold;
            color: #0f2d59;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            background-color: #f0f7ff;
            border-left: 3px solid #0284c7;
            padding: 3px 6px;
            margin-bottom: 4px;
        }

        /* Lists */
        ul.section-list {
            margin: 3px 0 6px 16px;
            padding: 0;
        }
        ul.section-list li {
            margin-bottom: 2px;
            font-size: 8.5pt;
            color: #1e293b;
        }

        /* Tables (Vitals, Medications, Lab) */
        table.grid-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0 8px 0;
            border: 1px solid #cbd5e1;
        }
        table.grid-table th {
            background-color: #f8fafc;
            color: #334155;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            text-align: left;
            padding: 4px 8px;
            border: 1px solid #cbd5e1;
        }
        table.grid-table td {
            padding: 4px 8px;
            font-size: 8.5pt;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        table.grid-table tr:nth-child(even) td {
            background-color: #fbfcfe;
        }

        /* Diagnosis Highlight Box */
        .diagnosis-box {
            background-color: #fefce8;
            border: 1px solid #fde047;
            border-left: 3px solid #ca8a04;
            padding: 6px 8px;
            margin: 4px 0 8px 0;
            border-radius: 2px;
        }
        .diagnosis-item {
            font-size: 9pt;
            font-weight: bold;
            color: #713f12;
            margin-bottom: 2px;
        }

        /* Care Plan / Follow-up Box */
        .careplan-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-left: 3px solid #16a34a;
            padding: 6px 8px;
            margin: 4px 0 8px 0;
            border-radius: 2px;
        }

        /* Attached files note */
        .attachments-box {
            background-color: #f8fafc;
            border: 1px dashed #94a3b8;
            padding: 5px 8px;
            margin-top: 6px;
            font-size: 8pt;
            color: #475569;
        }

        .no-records-box {
            border: 1px solid #cbd5e1;
            padding: 24px;
            text-align: center;
            color: #64748b;
            background-color: #f8fafc;
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <!-- Hospital Header -->
    <table class="header-table">
        <tr>
            <td style="width: 58%;">
                <?php if (!empty($logoSrc)): ?>
                    <img src="<?= $logoSrc ?>" class="hospital-logo" alt="Logo" /><br>
                <?php endif; ?>
                <div class="hospital-name"><?= esc($hospitalName) ?></div>
                <div class="hospital-meta">
                    <?php if (!empty($hospitalAddress)): ?>
                        <?= esc($hospitalAddress) ?><br>
                    <?php endif; ?>
                    <?php
                    $contactParts = [];
                    if (!empty($hospitalPhone)) { $contactParts[] = 'Phone: ' . esc($hospitalPhone); }
                    if (!empty($hospitalEmail)) { $contactParts[] = 'Email: ' . esc($hospitalEmail); }
                    echo implode(' | ', $contactParts);
                    ?>
                </div>
            </td>
            <td style="width: 42%;">
                <div class="abdm-badge-box">
                    <div class="abdm-badge-title">Ayushman Bharat Digital Mission (ABDM)</div>
                    <div class="abdm-badge-sub">Health Information User (HIU) Service</div>
                    <div class="abdm-report-type">Fetched Health Records</div>
                    <div class="report-meta-text">
                        Generated: <strong><?= esc($printedOn) ?></strong>
                        <?php if (!empty($consentRequestId)): ?>
                            <br>Consent Ref: <strong><?= esc($consentRequestId) ?></strong>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="accent-divider"></div>

    <!-- Patient Demographic Banner -->
    <table class="patient-card">
        <tr>
            <td style="width: 32%;">
                <span class="p-lbl">Patient Name</span>
                <span class="p-val-lg"><?= esc($patientName ?: 'Unknown Patient') ?></span>
            </td>
            <td style="width: 18%;">
                <span class="p-lbl">UHID / Patient ID</span>
                <span class="p-val"><?= esc($patientCode ?: ('#' . $patientId)) ?></span>
            </td>
            <td style="width: 25%;">
                <span class="p-lbl">Age / Gender</span>
                <span class="p-val"><?= esc($ageGender ?: '-') ?></span>
            </td>
            <td style="width: 25%;">
                <span class="p-lbl">Contact / Mobile</span>
                <span class="p-val"><?= esc($mobile ?: '-') ?></span>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="background-color: #f0f7ff;">
                <span class="p-lbl">ABHA Address (PHR)</span>
                <span class="p-val abha-val"><?= esc($abhaAddress ?: 'Not Linked') ?></span>
                <?php if (!empty($abhaAddress)): ?>
                    <span class="badge-verified">&check; VERIFIED</span>
                <?php endif; ?>
            </td>
            <td colspan="2" style="background-color: #f0f7ff;">
                <span class="p-lbl">ABHA Number</span>
                <span class="p-val"><?= esc($abhaNumber ?: '-') ?></span>
            </td>
        </tr>
    </table>

    <!-- Fetched Documents Loop -->
    <?php if (empty($documents)): ?>
        <div class="no-records-box">
            <h4 style="margin: 0 0 4px 0; color: #334155;">No Fetched Health Records Found</h4>
            <p style="margin: 0; font-size: 8.5pt;">There are no clinical records stored under this consent request for the patient.</p>
        </div>
    <?php else: ?>
        <?php foreach ($documents as $docIdx => $doc): ?>
            <?php
            $summary = $doc['summary'] ?? [];
            $sections = is_array($summary['sections'] ?? null) ? $summary['sections'] : [];
            $docTitle = trim((string) ($doc['document_title'] ?? 'Health Record'));
            $docType = trim((string) ($doc['bundle_type'] ?? 'RECORD'));
            $docDate = !empty($doc['document_date']) ? date('d-m-Y h:i A', strtotime($doc['document_date'])) : (!empty($doc['created_at']) ? date('d-m-Y h:i A', strtotime($doc['created_at'])) : '-');
            $docFacility = trim((string) ($doc['organization_name'] ?? ''));
            $docDoctor = trim((string) ($doc['practitioner_name'] ?? ''));
            $careContext = trim((string) ($doc['care_context_reference'] ?? ''));
            $attachments = is_array($summary['attachments'] ?? null) ? $summary['attachments'] : [];
            ?>

            <div class="doc-card">
                <!-- Document Header -->
                <table class="doc-header">
                    <tr>
                        <td style="width: 70%;">
                            <span class="doc-badge"><?= esc($docType) ?></span>
                            <span class="doc-title"><?= esc($docTitle) ?></span>
                        </td>
                        <td style="width: 30%;" class="doc-date">
                            Encounter Date: <strong><?= esc($docDate) ?></strong>
                        </td>
                    </tr>
                </table>

                <!-- Submeta info -->
                <table class="doc-submeta">
                    <tr>
                        <td>
                            <strong>Facility:</strong> <?= esc($docFacility ?: 'Healthcare Facility') ?>
                            &nbsp;&bull;&nbsp;
                            <strong>Doctor:</strong> <?= esc($docDoctor ?: 'Treating Physician') ?>
                            <?php if ($careContext !== ''): ?>
                                &nbsp;&bull;&nbsp;
                                <strong>Context Ref:</strong> <?= esc($careContext) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <!-- Document Body -->
                <div class="doc-body">
                    <?php if (empty($sections)): ?>
                        <div style="font-size: 8.5pt; color: #64748b; font-style: italic;">
                            No structured clinical sections were returned in this bundle.
                        </div>
                    <?php else: ?>
                        <?php foreach ($sections as $section): ?>
                            <?php
                            $secTitle = trim((string) ($section['title'] ?? 'Clinical Section'));
                            $items = is_array($section['items'] ?? null) ? $section['items'] : [];
                            $narrative = trim((string) ($section['narrative'] ?? ''));
                            $secLower = strtolower($secTitle);

                            if (empty($items) && $narrative === '') {
                                continue;
                            }
                            ?>

                            <div class="section-wrap">
                                <div class="section-heading"><?= esc($secTitle) ?></div>

                                <?php if (str_contains($secLower, 'vital') || str_contains($secLower, 'examination')): ?>
                                    <!-- Vitals / Physical Examination Table -->
                                    <table class="grid-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 58%;">Parameter / Vital Sign</th>
                                                <th style="width: 42%;">Finding / Measurement</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($items as $item): ?>
                                                <?php
                                                $itemStr = trim((string) $item);
                                                $param = $itemStr;
                                                $val = '';
                                                if (str_contains($itemStr, ':')) {
                                                    $parts = explode(':', $itemStr, 2);
                                                    $param = trim($parts[0]);
                                                    $val = trim($parts[1] ?? '');
                                                }
                                                ?>
                                                <tr>
                                                    <td><strong><?= esc($param) ?></strong></td>
                                                    <td><?= esc($val !== '' ? $val : '-') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>

                                <?php elseif (str_contains($secLower, 'medication') || str_contains($secLower, 'prescription')): ?>
                                    <!-- Medications / Prescription Table -->
                                    <table class="grid-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 6%; text-align: center;">#</th>
                                                <th style="width: 50%;">Medicine / Formulation</th>
                                                <th style="width: 44%;">Dosage / Instructions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($items as $mIdx => $item): ?>
                                                <?php
                                                $itemStr = trim((string) $item);
                                                $medName = $itemStr;
                                                $dosage = '';
                                                if (str_contains($itemStr, '—')) {
                                                    $parts = explode('—', $itemStr, 2);
                                                    $medName = trim($parts[0]);
                                                    $dosage = trim($parts[1] ?? '');
                                                } elseif (str_contains($itemStr, ' - ')) {
                                                    $parts = explode(' - ', $itemStr, 2);
                                                    $medName = trim($parts[0]);
                                                    $dosage = trim($parts[1] ?? '');
                                                }
                                                ?>
                                                <tr>
                                                    <td style="text-align: center; color: #64748b;"><?= $mIdx + 1 ?></td>
                                                    <td><strong style="color: #0f2d59;"><?= esc($medName) ?></strong></td>
                                                    <td><?= esc($dosage !== '' ? $dosage : 'As directed') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>

                                <?php elseif (str_contains($secLower, 'diagnos') || str_contains($secLower, 'problem')): ?>
                                    <!-- Diagnoses Highlight Box -->
                                    <div class="diagnosis-box">
                                        <?php if (!empty($items)): ?>
                                            <?php foreach ($items as $item): ?>
                                                <div class="diagnosis-item">&bull; <?= esc($item) ?></div>
                                            <?php endforeach; ?>
                                        <?php elseif ($narrative !== ''): ?>
                                            <div class="diagnosis-item"><?= nl2br(esc($narrative)) ?></div>
                                        <?php endif; ?>
                                    </div>

                                <?php elseif (str_contains($secLower, 'care plan') || str_contains($secLower, 'follow up') || str_contains($secLower, 'advice')): ?>
                                    <!-- Care Plan / Follow-up Box -->
                                    <div class="careplan-box">
                                        <?php if (!empty($items)): ?>
                                            <ul class="section-list" style="margin-left: 12px;">
                                                <?php foreach ($items as $item): ?>
                                                    <li><?= esc($item) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php elseif ($narrative !== ''): ?>
                                            <div style="font-size: 8.5pt; color: #14532d;"><?= nl2br(esc($narrative)) ?></div>
                                        <?php endif; ?>
                                    </div>

                                <?php else: ?>
                                    <!-- Standard List or Narrative -->
                                    <?php if (!empty($items)): ?>
                                        <ul class="section-list">
                                            <?php foreach ($items as $item): ?>
                                                <li><?= esc($item) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php elseif ($narrative !== ''): ?>
                                        <div style="font-size: 8.5pt; color: #334155; margin-left: 4px;"><?= nl2br(esc($narrative)) ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Attached files note -->
                    <?php if (!empty($attachments)): ?>
                        <div class="attachments-box">
                            <strong>Attached Files / Scanned Documents:</strong>
                            <?= count($attachments) ?> file(s) attached in digital record
                            <?php
                            $attTitles = [];
                            foreach ($attachments as $att) {
                                if (!empty($att['title'])) {
                                    $attTitles[] = esc($att['title']);
                                }
                            }
                            if (!empty($attTitles)) {
                                echo ' (' . implode(', ', $attTitles) . ')';
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Page Break between major documents (except after last document) -->
            <?php if ($docIdx < count($documents) - 1): ?>
                <pagebreak />
            <?php endif; ?>

        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>
