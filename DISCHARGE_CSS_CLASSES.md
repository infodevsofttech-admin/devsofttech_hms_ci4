# Discharge Summary CSS Classes Reference

This document lists all the semantic CSS classes used in the discharge summary HTML, allowing you to style them separately without inline styles.

---

## CSS Class List

### Section Headings
- **`.discharge-title`** - Main discharge title (H2)
- **`.discharge-section-heading`** - Section headers (H4)

### Lists
- **`.discharge-list`** - Main lists (ul/ol) for items
- **`.discharge-sublist`** - Nested sublists (ul)
- **`.no-bullet`** - List item with no bullet point

### Text Elements
- **`.discharge-remark`** - Remark/secondary text (span)

### Tables
- **`.discharge-table`** - Standard data tables with borders
- **`.discharge-info-table`** - Patient info table
- **`.discharge-medicine-table`** - Medicine list table
- **`.discharge-signature-table`** - Signature block table

### Containers
- **`.discharge-summary-info`** - Discharge summary info block (div)
- **`.discharge-section`** - Generic section container (div)
- **`.discharge-item`** - Individual item within a section (div)
- **`.discharge-field`** - Field with label (div) - margin-bottom
- **`.discharge-footer`** - Footer text (div) - margin-top
- **`.nabh-guidance`** - NABH guidance note (div)

### Separators
- **`.discharge-separator`** - Horizontal rule (hr)

---

## Suggested CSS Stylesheet

Copy this to your discharge template `template_css` field or add to your global styles:

```css
/* Section Headings */
.discharge-title {
    text-align: center;
    margin: 10px 0;
    padding: 0;
    font-size: 18pt;
    font-weight: bold;
}

.discharge-section-heading {
    margin: 16px 0 8px 0;
    font-size: 12pt;
    font-weight: bold;
    color: #000;
}

/* Lists */
.discharge-list {
    margin: 0 0 10px 20px;
    padding: 0;
}

.discharge-sublist {
    margin: 4px 0 8px 20px;
    padding: 0;
}

.no-bullet {
    list-style: none;
}

/* Text */
.discharge-remark {
    color: #475569;
    font-style: italic;
}

/* Tables */
.discharge-table {
    width: 100%;
    border-collapse: collapse;
    margin: 4px 0 8px 0;
}

.discharge-info-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
}

.discharge-medicine-table {
    width: 100%;
    border-style: inset;
    margin-bottom: 10px;
}

.discharge-medicine-table th:first-child {
    width: 40px;
}

.discharge-medicine-table th:nth-child(3) {
    width: 90px;
}

.discharge-medicine-table th:nth-child(4) {
    width: 100px;
}

.discharge-signature-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.discharge-signature-table td {
    width: 33.33%;
    vertical-align: bottom;
    text-align: center;
}

/* Containers */
.discharge-summary-info {
    margin-bottom: 10px;
}

.discharge-section {
    margin-bottom: 10px;
}

.discharge-item {
    margin-bottom: 6px;
}

.discharge-field {
    margin-bottom: 8px;
}

.discharge-footer {
    margin-top: 6px;
}

.nabh-guidance {
    font-size: 11px;
    color: #334155;
    margin-bottom: 10px;
    padding: 8px;
    background-color: #f8fafc;
    border-left: 3px solid #3b82f6;
}

/* Separators */
.discharge-separator {
    margin: 1px 0;
    padding: 0;
    border: none;
    border-top: 1px solid #000;
}
```

---

## Responsive Adjustments (Optional)

Add these for print-optimized styles:

```css
@media print {
    .discharge-title {
        font-size: 16pt;
        page-break-after: avoid;
    }
    
    .discharge-section-heading {
        page-break-after: avoid;
        font-size: 11pt;
    }
    
    .discharge-table,
    .discharge-info-table,
    .discharge-medicine-table,
    .discharge-signature-table {
        page-break-inside: avoid;
    }
}
```

---

## How to Apply Styles

### Method 1: Template CSS Field
1. Go to discharge template settings
2. Paste the CSS into the `template_css` field
3. Save template

### Method 2: Global Styles
1. Add to your print CSS file
2. Include in the PDF generation header
3. Apply site-wide

---

## Example Usage in Template

```html
<h2 class="discharge-title">Discharge Summary</h2>
<hr class="discharge-separator" />

<table class="discharge-info-table" border="1" cellpadding="6">
    <tr>
        <td><b>Name</b>: {{PATIENT_NAME}}</td>
        <td><b>UHID</b>: {{UHID}}</td>
    </tr>
</table>

<div class="discharge-section">{{DISCHARGE_SUMMARY}}</div>
<div class="discharge-section">{{PRESENTING_COMPLAINTS}}</div>
<div class="discharge-section">{{FINAL_DIAGNOSIS}}</div>

<h4 class="discharge-section-heading">Discharge Medications</h4>
<ol class="discharge-list">
    <li>Medicine 1 <span class="discharge-remark">(Take after food)</span></li>
    <li>Medicine 2 <span class="discharge-remark">[7 days]</span></li>
</ol>

<table class="discharge-signature-table" border="0" cellpadding="10">
    <tr>
        <td>____________________<br>Consultant Signature</td>
        <td>____________________<br>Medical Officer</td>
        <td>____________________<br>Patient/Receiver</td>
    </tr>
</table>
```

---

## Customization Tips

1. **Change Colors**: Modify `.discharge-remark` color from `#475569` to your brand color
2. **Adjust Spacing**: Increase/decrease margins in heading classes
3. **Font Sizes**: Change section heading sizes for emphasis
4. **Table Borders**: Adjust border styles on table classes
5. **Print Optimization**: Use `@media print` for print-specific styles

---

## Benefits

✅ **No inline styles** - All styling controlled via CSS  
✅ **Easy customization** - Change once, applies everywhere  
✅ **Consistent look** - Uniform styling across all sections  
✅ **Print-friendly** - Optimized for PDF generation  
✅ **Maintainable** - Update styles without touching PHP code  

---

**Generated:** 2026-06-07  
**File Location:** `d:\Workplace\HMS_CI4_OLD\DISCHARGE_CSS_CLASSES.md`
