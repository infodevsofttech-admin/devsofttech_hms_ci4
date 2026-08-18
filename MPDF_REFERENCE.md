# mPDF Reference — HMS E-Atria Project

Source: https://mpdf.github.io/
Version used: mpdf/mpdf (composer, see vendor/)

---

## 1. Core Pattern — Single WriteHTML Call (used in this project)

mPDF does NOT require a full HTML document (`<!doctype>`, `<html>`, `<head>`, `<body>`).
Pass a plain HTML fragment to `WriteHTML()`.

### Correct structure for a templated PDF with header/footer:

```
<style>
@page {
    margin-top: 3cm;
    margin-bottom: 2cm;
    margin-left: 0.8cm;
    margin-right: 0.8cm;
    margin-header: 1cm;
    margin-footer: 0.5cm;
    header: html_myHeader;   /* activates the named header on every page */
    footer: html_myFooter;
}

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 11pt;
}
table { width:100%; border-collapse: collapse; }
th, td { border: 1px solid #000; padding: 6px; }
</style>

<htmlpageheader name="myHeader">
    <div style="text-align:center; font-weight:bold;">Hospital Name</div>
    <hr>
</htmlpageheader>

<htmlpagefooter name="myFooter">
    <div style="text-align:right; font-size:9pt;">Page {PAGENO} of {nbpg}</div>
</htmlpagefooter>

<h1>Discharge Summary</h1>
<p>Patient content here…</p>
```

**Rules:**
- `@page` block MUST be in a `<style>` tag placed BEFORE the named header/footer blocks.
- `header: html_myHeader;` in `@page` binds the named block to every page.
- Named headers use `html_` prefix when referenced from `@page` / `AddPage()`.
- Do NOT name any header/footer starting with `html_` when defining it — the prefix is reserved for referencing only.
- `margin-header` = space between the top of the physical page and the top of the header content.
- `margin-top` = space between the bottom of the header and the start of the body content.

---

## 2. PHP Constructor (new Mpdf\Mpdf([...]))

```php
$mpdf = new Mpdf([
    'mode'          => 'utf-8',
    'format'        => 'A4',          // A4, A4-L, A5, A6, LETTER, LEGAL, or [width_mm, height_mm]
    'margin_top'    => 30,            // mm — used as fallback if no @page CSS
    'margin_bottom' => 20,
    'margin_left'   => 8,
    'margin_right'  => 8,
    'margin_header' => 10,            // mm — space reserved for header
    'margin_footer' => 5,
    'default_font'  => 'freeserif',   // project default for Hindi/Unicode support
    'tempDir'       => WRITEPATH . 'cache',
    'autoScriptToLang' => true,       // required for Hindi/Devanagari
    'autoLangToFont'   => true,
]);
```

If `@page` CSS is present, CSS margins override the constructor margins.
The constructor `format` always controls the page size (not settable via `@page`).

---

## 3. Named HTML Header/Footer — Method 4 (recommended)

**Define** using custom HTML tags in the WriteHTML content:
```html
<htmlpageheader name="myHeader">
    <div>Header content with {PAGENO} {DATE j-m-Y}</div>
</htmlpageheader>
```

**Activate** using `@page` in `<style>`:
```css
@page {
    header: html_myHeader;   /* Note: "html_" prefix ONLY in reference, not in name="" */
    footer: html_myFooter;
}
```

**Alternative activate** via PHP (for runtime change):
```php
$mpdf->SetHTMLHeaderByName('myHeader');
$mpdf->SetHTMLFooterByName('myFooter');
```

**Dynamic variables inside headers/footers:**
- `{PAGENO}` — current page number
- `{nbpg}` — total number of pages
- `{DATE j-m-Y}` — date formatted using PHP date() format

---

## 4. Output Methods

```php
// Inline (stream to browser)
$mpdf->Output('filename.pdf', 'I');

// Download
$mpdf->Output('filename.pdf', 'D');

// Save to file
$mpdf->Output('/path/to/file.pdf', 'F');

// Return as string (used in this project)
$binary = $mpdf->Output('filename.pdf', \Mpdf\Output\Destination::STRING_RETURN);
```

---

## 5. Page Numbers / Date in Footers

```html
Page {PAGENO} of {nbpg}         — recommended
Page {PAGENO}/{nb}              — also works
Date: {DATE d-m-Y}
```

Set alias for {nb}:
```php
$mpdf->AliasNbPages('{nbpg}');  // optional, default is {nbpg}
```

---

## 6. Supported CSS — Key Properties

### @page selector
```css
@page {
    size: A4;                        /* sets page size (alternative to constructor format) */
    margin-top: 3cm;
    margin-bottom: 2cm;
    margin-left: 0.8cm;
    margin-right: 0.8cm;
    margin-header: 1cm;
    margin-footer: 0.5cm;
    header: html_myHeader;          /* HTML named header (use html_ prefix) */
    footer: html_myFooter;
    odd-header-name: html_myHeader; /* alternative: for odd pages only */
    odd-footer-name: html_myFooter;
}
@page :first { ... }               /* first page only */
@page :right { ... }               /* right/odd pages */
@page :left  { ... }               /* left/even pages */
@page named  { ... }               /* named page selector, activated by page: named; on a block */
```

### Units supported
`px`, `pt`, `cm`, `mm`, `in`, `em`, `rem`, `ex`, `%`

### Not supported
- CSS Grid, Flexbox
- CSS Variables (--custom-property)
- Most CSS3 transitions/animations
- `position: sticky`, `position: relative` (only `fixed` and `absolute` supported)

---

## 7. Fonts — Hindi / Devanagari (used in discharge medicines)

For Hindi text (dose descriptions like "हर दो घंटे पर"):

```php
$mpdf = new Mpdf([
    'default_font'     => 'freeserif',
    'autoScriptToLang' => true,
    'autoLangToFont'   => true,
]);
```

`freeserif` is bundled with mPDF and contains Devanagari glyphs.
For better rendering use `dejavusans` or configure a custom font.

Custom font example:
```php
$mpdf = new Mpdf([
    'fontDir'  => [__DIR__ . '/fonts'],
    'fontdata' => $defaultFontData + [
        'myfont' => ['R' => 'MyFont-Regular.ttf'],
    ],
    'default_font' => 'myfont',
]);
```

---

## 8. Images in Headers

For images in `<htmlpageheader>`, use absolute server path:
```html
<htmlpageheader name="myHeader">
    <img src="/var/www/html/hms_etria/public/assets/images/logo.png" style="width:80px;">
</htmlpageheader>
```

Do NOT use relative paths or `url()`. The `{{H_logo_abs}}` token in this project
resolves to `FCPATH . 'assets/images/' . $hLogo`.

---

## 9. Tables in PDF

```html
<table width="100%" border="0" cellpadding="4" cellspacing="0">
    <thead>
        <tr style="background:#f2f2f2;">
            <th>Medicine</th><th>Dose</th><th>Days</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>Paracetamol</td><td>1 tab BD</td><td>5</td></tr>
    </tbody>
</table>
```

- Use `border-collapse: collapse` in CSS for clean borders.
- `border="0"` attribute overrides CSS borders — use CSS instead.
- Tables are not CSS `display:table` — they use mPDF's own table engine.

---

## 10. How Discharge PDF is Built in This Project

File: `app/Controllers/Ipd_discharge.php` — method `show_discharge()`

```
1. Load template settings from ipd_discharge_templates (id = requested ?tpl=N or is_default=1)
2. Read / auto-generate discharge content (token-replaced template_html)
3. Build mPDF constructor with: format, margins (all from template settings)
4. Build WriteHTML fragment:
     <style>@page { margins; header: html_myHeader; }</style>
     <style>template_css</style>
     <htmlpageheader name="myHeader">header_html (tokens replaced)</htmlpageheader>
     <htmlpagefooter name="myFooter">footer_html (tokens replaced)</htmlpagefooter>
     rendered template body (template_html with tokens replaced)
5. $mpdf->WriteHTML($pdfHtml)
6. Output as binary PDF string → CI4 response
```

**Token replacement**: `{{TOKEN_NAME}}` or `{{token_name}}` — case-insensitive.
Available tokens: H_Name, H_logo_abs, PATIENT_NAME, UHID, IPD_CODE, ADMIT_DATE,
DISCHARGE_DATE, FINAL_DIAGNOSIS, SURGERY, DISCHARGE_MEDICATIONS, DISCHARGE_SUMMARY,
CONTENT (all sections), and many more — see placeholder list in template editor.

**Debug**: `/Ipd_discharge/show_discharge/{ipdId}/1?tpl={tplId}&html=1`
Shows the exact HTML string passed to `WriteHTML()`.

---

## 11. Common Issues and Fixes

| Problem | Cause | Fix |
|---|---|---|
| Header not repeating on every page | Missing `header: html_myHeader` in `@page` | Add `header: html_myHeader;` to `@page` block in CSS textarea |
| CSS appears as visible text | CSS injected inside body `<div>`, not in `<style>` before content | Put all CSS in template CSS field; system puts it in `<style>` |
| `{PAGENO}` shows literally | Token not supported in body (only in headers/footers) | Move to `<htmlpagefooter>` |
| Hindi/Unicode text missing | Wrong font set | Use `freeserif` or `dejavusans`; enable `autoScriptToLang` |
| Image not loading | Relative path | Use absolute server path or `{{H_logo_abs}}` token |
| Page size wrong | Using `size:` in @page | Use `format` in Mpdf constructor instead |
| Margins not applied | `@page` CSS absent or conflicting constructor values | When using `@page` CSS, constructor margins act as fallback only |

---

## 12. References

- Full docs: https://mpdf.github.io/
- Named HTML headers (Method 4): https://mpdf.github.io/headers-footers/method-4.html
- Supported CSS: https://mpdf.github.io/css-stylesheets/supported-css.html
- @page CSS: https://mpdf.github.io/paging/using-page.html
- Fonts in mPDF 7+: https://mpdf.github.io/fonts-languages/fonts-in-mpdf-7-x.html
- WriteHTML: https://mpdf.github.io/reference/mpdf-functions/writehtml.html
