<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ABHA Card — <?= esc($patient['p_fname'] ?? '') ?></title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #f2f6fb;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    padding: 30px 14px;
    color: #1f2937;
  }
  .abha-card {
    width: 430px;
    max-width: 100%;
    background: #ffffff;
    border: 1px solid #d7e3f3;
    border-radius: 16px;
    padding: 18px;
    box-shadow: 0 8px 24px rgba(15, 40, 77, 0.12);
  }
  .top-strip {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    border-bottom: 1px solid #e4edf8;
    padding-bottom: 12px;
    margin-bottom: 14px;
  }
  .gov-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
  }
  .gov-emblem {
    width: 38px;
    height: 38px;
    background: #0b4fa8;
    color: #fff;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 13px;
    flex-shrink: 0;
  }
  .gov-text {
    font-size: 13px;
    font-weight: 700;
    color: #0c3d83;
    line-height: 1.3;
  }
  .gov-text span {
    display: block;
    font-size: 11px;
    color: #5b6b82;
    font-weight: 500;
  }
  .eatria-brand {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid #d6e3f6;
    border-radius: 10px;
    padding: 6px 9px;
    background: #f8fbff;
    flex-shrink: 0;
  }
  .eatria-brand img {
    width: 24px;
    height: 24px;
    object-fit: contain;
  }
  .eatria-brand .title {
    font-size: 11px;
    font-weight: 800;
    color: #114a92;
    line-height: 1.15;
  }
  .eatria-brand .title span {
    display: block;
    color: #5f6f87;
    font-size: 10px;
    font-weight: 600;
  }
  .hospital-name {
    margin-bottom: 12px;
    background: #f3f8ff;
    border: 1px solid #dce9f8;
    border-radius: 10px;
    padding: 8px 10px;
    font-size: 12px;
    color: #214268;
  }
  .hospital-name strong {
    color: #0a3f84;
  }
  .card-body {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    margin-bottom: 12px;
  }
  .patient-photo {
    width: 82px;
    height: 82px;
    border-radius: 50%;
    border: 3px solid #d5e4f7;
    object-fit: cover;
    background: #ecf4ff;
    flex-shrink: 0;
  }
  .patient-photo-placeholder {
    width: 82px;
    height: 82px;
    border-radius: 50%;
    border: 3px solid #d5e4f7;
    background: #ecf4ff;
    display: flex; align-items: center; justify-content: center;
    font-size: 34px;
    flex-shrink: 0;
    color: #3465a7;
  }
  .patient-info { flex: 1; min-width: 0; }
  .patient-name {
    font-size: 21px;
    font-weight: 800;
    text-transform: uppercase;
    color: #0f2f5f;
    margin-bottom: 4px;
    word-break: break-word;
  }
  .patient-detail {
    font-size: 13px;
    color: #425771;
    margin-bottom: 3px;
  }
  .abha-number-block {
    margin-top: 6px;
    background: #edf5ff;
    border: 1px solid #d5e6fb;
    border-radius: 10px;
    padding: 10px 12px;
  }
  .abha-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #5f7391;
    margin-bottom: 3px;
  }
  .abha-number {
    font-size: 34px;
    font-weight: 800;
    letter-spacing: 3px;
    color: #0a3d86;
    line-height: 1.12;
    word-break: break-word;
  }
  .barcode-box {
    margin-top: 12px;
    border: 1px solid #dbe7f6;
    border-radius: 10px;
    padding: 8px 10px;
    background: #fff;
  }
  .barcode-caption {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #5f7391;
    margin-bottom: 5px;
  }
  .barcode-visual {
    width: 100%;
    overflow: hidden;
  }
  .barcode-id {
    margin-top: 5px;
    font-size: 12px;
    font-weight: 700;
    color: #1e3f69;
    text-align: center;
  }
  .card-footer {
    margin-top: 10px;
    font-size: 10px;
    color: #637590;
    text-align: right;
  }
  .actions {
    margin-top: 22px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: center;
  }
  .btn-print {
    background: #0b4ea8;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 18px;
    font-size: 14px;
    cursor: pointer;
    font-weight: 700;
    text-decoration: none;
  }
  .btn-print:hover { background: #083d84; }
  .btn-abdm {
    background: #fff;
    color: #0b4ea8;
    border: 2px solid #0b4ea8;
    border-radius: 8px;
    padding: 9px 16px;
    font-size: 14px;
    cursor: pointer;
    font-weight: 700;
    text-decoration: none;
  }
  .btn-abdm:hover { background: #e8f0fe; }
  .bridge-help-btn {
    background: #f6fbff;
    color: #0c4b95;
    border: 1px solid #bdd4f2;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 13px;
    cursor: pointer;
    font-weight: 700;
  }
  .bridge-help-btn:hover { background: #eaf4ff; }
  .note {
    margin-top: 14px;
    font-size: 12px;
    color: #4f5f75;
    max-width: 430px;
    text-align: center;
    line-height: 1.6;
  }
  .bridge-guide {
    max-width: 430px;
    margin-top: 14px;
    background: #ffffff;
    border: 1px solid #d7e6f8;
    border-radius: 12px;
    padding: 14px;
    font-size: 13px;
    line-height: 1.55;
    color: #304760;
  }
  .bridge-guide h3 {
    margin-bottom: 8px;
    font-size: 14px;
    color: #0a3f84;
  }
  .bridge-guide ol {
    margin-left: 18px;
  }
  .bridge-guide li {
    margin-bottom: 6px;
  }
  .bridge-links {
    margin-top: 10px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }
  .bridge-links a {
    color: #0a4a96;
    text-decoration: none;
    font-weight: 700;
  }
  .bridge-links a:hover { text-decoration: underline; }

  @media print {
    body { background: #fff; padding: 0; }
    .abha-card { box-shadow: none; }
    .actions, .note, .bridge-guide { display: none; }
  }
</style>
</head>
<body>

<?php
$name       = esc($patient['p_fname'] ?? '');
$abhaNumDisp = esc($abha_num ?? '');
$photoData  = !empty($patient['abha_photo']) ? $patient['abha_photo'] : '';
$genderDisp = esc($gender ?? '');
$dobDisp    = esc($dob ?? '');
$hospitalName = esc($hospital_name ?? 'E-Atria Hospital');
$hospitalLogo = esc($hospital_logo_url ?? base_url('assets/img/logo.png'));
$brandName = esc($brand_name ?? 'E-Atria');
$hmsId = esc($hms_id ?? '');
$barcodeSvg = (string) ($hms_barcode_svg ?? '');
$abhaRaw = esc($abha_raw ?? '');
$officialCardUrl = esc($official_card_url ?? 'https://abha.abdm.gov.in/abha/v3/');
$bridgePortalUrl = esc($bridge_portal_url ?? 'https://abdm-bridge.e-atria.in/');
$officialCardApiUrl = base_url('abha/card/official/' . ($abhaRaw !== '' ? $abhaRaw : preg_replace('/\D/', '', (string) ($abha_num ?? ''))));
?>

<div class="abha-card">
  <div class="top-strip">
    <div class="gov-brand">
      <div class="gov-emblem">NHA</div>
      <div class="gov-text">Ayushman Bharat Digital Mission
        <span>ABHA Health Account Card</span>
      </div>
    </div>
    <div class="eatria-brand">
      <img src="<?= $hospitalLogo ?>" alt="<?= $brandName ?> Logo">
      <div class="title"><?= $brandName ?><span>Digital Health Network</span></div>
    </div>
  </div>

  <div class="hospital-name">
    Hospital: <strong><?= $hospitalName ?></strong>
  </div>

  <div class="card-body">
    <?php if ($photoData && str_starts_with($photoData, 'data:image')): ?>
      <img class="patient-photo" src="<?= $photoData ?>" alt="Patient Photo">
    <?php elseif ($photoData): ?>
      <img class="patient-photo" src="data:image/jpeg;base64,<?= esc($photoData) ?>" alt="Patient Photo">
    <?php else: ?>
      <div class="patient-photo-placeholder">👤</div>
    <?php endif; ?>

    <div class="patient-info">
      <div class="patient-name"><?= $name ?: '—' ?></div>
      <?php if ($genderDisp): ?>
        <div class="patient-detail">Gender: <?= $genderDisp ?></div>
      <?php endif; ?>
      <?php if ($dobDisp): ?>
        <div class="patient-detail">DOB: <?= $dobDisp ?></div>
      <?php endif; ?>
      <?php if ($hmsId): ?>
        <div class="patient-detail" style="margin-top:4px;">HMS ID: <?= $hmsId ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="abha-number-block">
    <div class="abha-label">ABHA Number</div>
    <div class="abha-number"><?= $abhaNumDisp ?: '—' ?></div>
  </div>

  <?php if ($barcodeSvg !== '' && $hmsId !== ''): ?>
  <div class="barcode-box">
    <div class="barcode-caption">HMS ID Barcode</div>
    <div class="barcode-visual"><?= $barcodeSvg ?></div>
    <div class="barcode-id"><?= $hmsId ?></div>
  </div>
  <?php endif; ?>

  <div class="card-footer">Government of India | Ministry of Health &amp; Family Welfare</div>
</div>

<div class="actions">
  <button class="btn-print" onclick="window.print()">Print / Save PDF</button>
  <button type="button" class="btn-abdm" id="downloadOfficialCardBtn" data-fetch-url="<?= esc($officialCardApiUrl) ?>">
    Download Official Card
  </button>
  <a class="bridge-help-btn" href="<?= $bridgePortalUrl ?>" target="_blank" rel="noopener noreferrer">
    Open ABDM Bridge Portal
  </a>
</div>

<p class="note" id="officialCardStatus" style="display:none;"></p>

<p class="note">
  This is hospital-branded ABHA print card. For QR-enabled official card, use the ABDM official flow below.
</p>

<div class="bridge-guide">
  <h3>Official Card Download and Print (via ABDM Bridge assisted flow)</h3>
  <ol>
    <li>Click <strong>Download Official Card</strong> to fetch the official PNG card via ABDM Bridge API.</li>
    <li>A print window opens automatically with the fetched official card image.</li>
    <li>If bridge session/auth is invalid, open <strong>ABDM Bridge Portal</strong> and complete login/configuration.</li>
    <li>Retry <strong>Download Official Card</strong> after bridge auth is active.</li>
  </ol>
  <div class="bridge-links">
    <a href="<?= $bridgePortalUrl ?>" target="_blank" rel="noopener noreferrer">ABDM Bridge Portal</a>
    <a href="<?= $officialCardUrl ?>" target="_blank" rel="noopener noreferrer">Official ABDM Card Website</a>
  </div>
  <p style="margin-top:8px;">
    Official card will be downloaded for ABHA
  <strong><?= $abhaNumDisp ?></strong>.
  </p>
</div>

<script>
(function() {
  var btn = document.getElementById('downloadOfficialCardBtn');
  var statusEl = document.getElementById('officialCardStatus');
  if (!btn || !statusEl) {
    return;
  }

  function setStatus(msg, isError) {
    statusEl.style.display = 'block';
    statusEl.style.color = isError ? '#9f1239' : '#14532d';
    statusEl.style.background = isError ? '#ffe4e6' : '#dcfce7';
    statusEl.style.border = '1px solid ' + (isError ? '#fecdd3' : '#bbf7d0');
    statusEl.style.borderRadius = '8px';
    statusEl.style.padding = '8px 10px';
    statusEl.textContent = msg;
  }

  function openPrintImage(base64Png) {
    var imageSrc = 'data:image/png;base64,' + base64Png;
    var w = window.open('', '_blank');
    if (!w) {
      setStatus('Popup blocked by browser. Please allow popups and try again.', true);
      return;
    }

    var html = ''
      + '<!DOCTYPE html><html><head><title>Official ABHA Card</title>'
      + '<meta name="viewport" content="width=device-width, initial-scale=1" />'
      + '<style>body{margin:0;padding:18px;font-family:Arial,sans-serif;background:#fff;text-align:center;}'
      + 'img{max-width:100%;height:auto;border:1px solid #d4d4d8;box-shadow:0 4px 14px rgba(0,0,0,.08);}'
      + '.hint{margin-top:12px;color:#475569;font-size:12px;}</style></head><body>'
      + '<img src="' + imageSrc + '" alt="Official ABHA Card" />'
      + '<div class="hint">Use browser print to save or print the official card.</div>'
      + '<script>window.onload=function(){window.focus();window.print();};<\/script>'
      + '</body></html>';

    w.document.open();
    w.document.write(html);
    w.document.close();
  }

  btn.addEventListener('click', function() {
    var url = btn.getAttribute('data-fetch-url') || '';
    if (!url) {
      setStatus('Official card endpoint missing. Please contact admin.', true);
      return;
    }

    btn.disabled = true;
    btn.textContent = 'Fetching...';
    setStatus('Fetching official card from ABDM Bridge...', false);

    fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json'
      }
    })
      .then(function(res) {
        return res.json().then(function(body) {
          return { ok: res.ok, body: body };
        });
      })
      .then(function(result) {
        var body = result.body || {};
        if (!result.ok || Number(body.ok || 0) !== 1 || !body.card_data) {
          var err = body.error_text || 'Unable to fetch official card from bridge.';
          setStatus(err, true);
          return;
        }

        setStatus('Official card fetched. Opening print window...', false);
        openPrintImage(String(body.card_data));
      })
      .catch(function() {
        setStatus('Network/server error while fetching official card.', true);
      })
      .finally(function() {
        btn.disabled = false;
        btn.textContent = 'Download Official Card';
      });
  });
})();
</script>

</body>
</html>
