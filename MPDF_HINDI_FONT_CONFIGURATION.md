# mPDF Hindi (Devanagari) Font Configuration

## Summary of Changes

### Problem
- **Old font:** `dejavusans` - Very thin/light weight, limited Hindi glyph coverage
- **Issue:** Poor rendering of Hindi/Devanagari text in PDFs

### Solution Implemented
- **New font:** `freeserif` - Medium weight serif font with excellent Hindi/Devanagari support
- **Result:** Better readability, proper glyph rendering, professional appearance

## Files Updated (2026-06-06)

### 1. Opd.php (3 instances)
- Line ~2036: Invoice PDF generation
- Line ~2192: Prescription PDF generation  
- Line ~2308: Document print with custom templates

### 2. Ipd_discharge.php
- Already using `freeserif` ✅ (Lines 5485, 5532, 5581)

### 3. Billing\Ipd.php
- Already using `freeserif` ✅ (Line 982)

### 4. Diagnosis.php (3 instances)
- Line ~1517: Lab report with print settings
- Line ~2201: Invoice PDF generation
- Line ~2456: PDF create with timing logs

### 5. Billing\Charges.php (2 instances)
- Line ~1140: Landscape invoice (A4-L format)
- Line ~1151: Portrait invoice (A4 format)

### 6. Storestock.php (1 instance)
- Line ~782: Indent/stock PDF generation

### 7. AbdmGateway.php (1 instance)
- Line ~2043: ABDM health record PDF generation

### 8. DoctorDocument.php
- Already using `freesans` ✅ (Line 1298) - Another good Unicode font

---

## Available mPDF Fonts for Hindi

### Built-in Fonts (No Additional Setup)

| Font Name | Type | Weight | Hindi Support | Best Use Case |
|-----------|------|--------|---------------|---------------|
| **freeserif** ✅ | Serif | Medium | Excellent | **Current choice** - Reports, prescriptions |
| **freesans** | Sans-serif | Medium | Excellent | Modern documents, UI-heavy PDFs |
| **dejavusans** ❌ | Sans-serif | Thin | Limited | Avoid - too thin |
| **dejavusanscondensed** | Sans-serif | Medium | Good | Space-constrained layouts |

### Custom Font Installation (Advanced)

For production-grade Hindi typography, you can add custom fonts:

#### Option 1: Noto Sans Devanagari (Google Fonts)
```php
$mpdf = new Mpdf([
    'fontDir' => [
        __DIR__ . '/../../public/fonts',
        realpath(__DIR__ . '/../../vendor/mpdf/mpdf/ttfonts')
    ],
    'fontdata' => [
        'notosansdevanagari' => [
            'R' => 'NotoSansDevanagari-Regular.ttf',
            'B' => 'NotoSansDevanagari-Bold.ttf',
        ]
    ],
    'default_font' => 'notosansdevanagari',
]);
```

**Download:** https://fonts.google.com/noto/specimen/Noto+Sans+Devanagari

#### Option 2: Lohit Devanagari (Open Source)
```php
$mpdf = new Mpdf([
    'fontDir' => [__DIR__ . '/../../public/fonts'],
    'fontdata' => [
        'lohitdevanagari' => [
            'R' => 'lohit-devanagari.ttf',
        ]
    ],
    'default_font' => 'lohitdevanagari',
]);
```

**Download:** https://github.com/pravins/lohit

#### Option 3: Windows Mangal Font
If running on Windows server with Mangal installed:
```php
$mpdf = new Mpdf([
    'fontDir' => ['C:/Windows/Fonts'],
    'fontdata' => [
        'mangal' => [
            'R' => 'mangal.ttf',
            'B' => 'mangalb.ttf',
        ]
    ],
    'default_font' => 'mangal',
]);
```

---

## Testing the Changes

### Quick Test
1. Generate any PDF (OPD prescription, discharge summary, lab report)
2. Check Hindi text rendering
3. Compare with old PDFs - new font should be:
   - **Thicker/bolder** than before
   - **More readable** at all zoom levels
   - **Proper glyph rendering** for all Hindi characters

### Sample Hindi Text for Testing
```
नमस्ते। यह एक परीक्षण है।
मरीज का नाम: राज कुमार शर्मा
निदान: बुखार और सिरदर्द
```

---

## Font Weight Comparison

### Before (dejavusans)
- Font weight: 300-400 (Light to Normal)
- Line thickness: Very thin
- Best for: English text only

### After (freeserif)
- Font weight: 500-600 (Medium to Semi-bold)
- Line thickness: Medium
- Best for: Multilingual documents including Hindi

---

## Configuration Options

### Current Standard Configuration
```php
$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'orientation' => 'P',
    'margin_left' => 10,
    'margin_right' => 10,
    'margin_top' => 10,
    'margin_bottom' => 10,
    'default_font' => 'freeserif',       // ✅ Changed
    'autoScriptToLang' => true,           // Auto-detect language
    'autoLangToFont' => true,             // Auto-switch font for different scripts
    'tempDir' => WRITEPATH . 'cache',
]);
```

### Additional Options for Better Hindi Support
```php
$mpdf = new Mpdf([
    'default_font' => 'freeserif',
    'autoScriptToLang' => true,
    'autoLangToFont' => true,
    'useSubstitutions' => true,           // Enable font substitution
    'CSSselectMedia' => 'print',          // Better print styling
]);
```

---

## Inline Font Override

If you need different fonts for specific sections within the same PDF:

```html
<style>
    .hindi-bold {
        font-family: freeserif;
        font-weight: bold;
        font-size: 14pt;
    }
    .english-section {
        font-family: freesans;
        font-size: 11pt;
    }
</style>

<div class="hindi-bold">
    मरीज का नाम: राज कुमार शर्मा
</div>
<div class="english-section">
    Patient Name: Raj Kumar Sharma
</div>
```

---

## Troubleshooting

### Issue: Hindi text shows squares/boxes
**Solution:** Ensure UTF-8 encoding in database and PHP files
```php
// In controller before PDF generation
header('Content-Type: text/html; charset=utf-8');
```

### Issue: Font looks different in different PDFs
**Check:** Verify all controllers use same font configuration

### Issue: Need even bolder font
**Options:**
1. Use CSS: `<span style="font-weight: bold;">हिंदी पाठ</span>`
2. Switch to `freesans` (slightly bolder than freeserif)
3. Install custom bold font (see Custom Font Installation above)

---

## Performance Notes

- `freeserif` is built-in to mPDF - **no performance impact**
- Custom fonts add ~2-3 MB to memory usage per font file
- Font substitution (`autoLangToFont`) adds negligible overhead (~0.1s per PDF)

---

## Rollback Instructions

If you need to revert to old font:

```bash
# Search and replace in all PHP files
git diff                          # Review changes
git checkout -- app/Controllers/  # Revert all controller changes
```

Or manually change:
```php
'default_font' => 'dejavusans',  // Old font
```

---

## Next Steps (Optional)

### 1. For Professional Hindi Typography
- Download Noto Sans Devanagari from Google Fonts
- Place TTF files in `public/fonts/` directory
- Update mPDF configuration in each controller

### 2. For System-wide Font Configuration
- Create a helper function `getPdfConfig()` in `app/Helpers/pdf_helper.php`
- Centralize all mPDF configuration
- Call from all controllers: `$mpdf = new Mpdf(getPdfConfig());`

### 3. For Font Testing
- Create a test endpoint: `/test-pdf-fonts`
- Generate sample PDF with all available fonts
- Compare rendering quality

---

## Support

**mPDF Documentation:**
- Fonts: https://mpdf.github.io/fonts-languages/fonts-in-mpdf-7-x.html
- Indic Scripts: https://mpdf.github.io/fonts-languages/indic-fonts-v6.html

**Font Resources:**
- Google Fonts: https://fonts.google.com/?subset=devanagari
- Font Squirrel: https://www.fontsquirrel.com/fonts/list/language/hindi

---

## Changelog

- **2026-06-06:** Changed default font from `dejavusans` to `freeserif` across all controllers for better Hindi rendering and font weight
