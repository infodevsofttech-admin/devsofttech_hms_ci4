<?php

function cleanHtml($html) {
    // 1. Remove empty inner section heading stub:
    // <h4 class="discharge-section-heading">Discharge Summary</h4> (possibly followed by <div class="discharge-status"><strong>Discharge Summary</strong></div>)
    // when there is no .discharge-summary-content immediately after it
    $pattern = '/<h[1-6]\b[^>]*class="[^"]*discharge-section-heading[^"]*"[^>]*>\s*Discharge\s+Summary\s*<\/h[1-6]>\s*(?:<div class="discharge-status">\s*<strong>\s*Discharge\s+Summary\s*<\/strong>\s*<\/div>\s*)?(?!<div class="discharge-summary-content">)/i';
    $html = preg_replace($pattern, '', $html);

    // 2. Also remove any standalone <div class="discharge-status"><strong>Discharge Summary</strong></div> if it repeats standard title
    $html = preg_replace('/<div class="discharge-status">\s*<strong>\s*Discharge\s+Summary\s*<\/strong>\s*<\/div>/i', '', $html);

    return trim($html);
}

// Case 1: Template 1 with Empty summary inside {{CONTENT}}
$case1 = '<h3 style="margin:0 0 8px 0;">Discharge Summary</h3><table border="1"><tr><td>Patient info</td></tr></table><div><h4 class="discharge-section-heading">Discharge Summary</h4><div class="discharge-status"><strong>Discharge Summary</strong></div><p><b>Presenting Complaints</b> :<br /> Abdominal Pain</p></div>';

echo "=== Case 1: Template 1 with Empty summary inside {{CONTENT}} ===\n";
echo cleanHtml($case1) . "\n\n";

// Case 2: Template with Non-empty summary with doctor notes
$case2 = '<h3 style="margin:0 0 8px 0;">Discharge Summary</h3><table border="1"><tr><td>Patient info</td></tr></table><div><h4 class="discharge-section-heading">Discharge Summary</h4><div class="discharge-summary-content"><p>Patient admitted with fever and responded well to IV antibiotics.</p></div><p><b>Presenting Complaints</b> :<br /> Abdominal Pain</p></div>';

echo "=== Case 2: Non-empty summary ===\n";
echo cleanHtml($case2) . "\n";
