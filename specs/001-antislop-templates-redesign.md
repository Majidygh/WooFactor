# SPEC-001: Anti-Slop Redesign of Invoices & Manual Invoice Template Picker

## Overview
Redesign WooFactor invoice templates (`modern.php`, `classic.php`) to eliminate AI-generated styling artifacts (unnecessary gradients, glowing pill capsules, pulsing animations, ink-wasting banners). Set the redesigned modern template as the universal default and allow seamless template selection during manual invoice creation/editing.

## Key Changes
1. **Default Template**:
   - `woo_factor_default_options()` set to `'template' => 'modern'`.
   - `Woo_Factor_Renderer::render_html()` falls back to `modern`.
   - Manual invoice creation and viewing fallback to `modern`.
2. **Template Redesign (`modern.php`)**:
   - Clean corporate layout with Vazirmatn typography.
   - Symmetrical seller/buyer cards with structured key-value lines.
   - High-contrast, tabular-aligned items table.
   - Clean summary block with bold grand total and words-total banner.
   - Ink-friendly print stylesheet (`@media print`) that avoids heavy ink consumption.
3. **Template Polish (`classic.php`)**:
   - Replaced rounded pill badges with official rectangular tax badges conforming to Article 19 VAT standards.
4. **Manual Invoices (`admin/manual-invoice.php`)**:
   - Template selection field defaults to `modern`.
   - Supports selecting between modern, classic tax, and 80mm thermal.
   - Persists chosen template and supports toolbar template switching.

## Verification
- Integration test suite passes against WordPress 7.1.4 and WooCommerce 11.2.2.
