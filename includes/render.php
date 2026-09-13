<?php
/**
 * Render Engine for WooFactor Invoices
 */
defined('ABSPATH') || exit;

class Woo_Factor_Renderer {

    const FONT_CSS = 'assets/fonts/woo-factor-fonts.css';

    /**
     * Persian font faces (weight, script subset) shipped with the plugin.
     */
    protected static function font_faces() {
        return [
            ['400', 'arabic', 'U+0600-06FF, U+0750-077F, U+0870-088E, U+0890-0891, U+0897-08E1, U+08E3-08FF, U+200C-200E, U+2010-2011, U+204F, U+2E41, U+FB50-FDFF, U+FE70-FEFC'],
            ['700', 'arabic', 'U+0600-06FF, U+0750-077F, U+0870-088E, U+0890-0891, U+0897-08E1, U+08E3-08FF, U+200C-200E, U+2010-2011, U+204F, U+2E41, U+FB50-FDFF, U+FE70-FEFC'],
            ['900', 'arabic', 'U+0600-06FF, U+0750-077F, U+0870-088E, U+0890-0891, U+0897-08E1, U+08E3-08FF, U+200C-200E, U+2010-2011, U+204F, U+2E41, U+FB50-FDFF, U+FE70-FEFC'],
            ['400', 'latin', 'U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD'],
            ['700', 'latin', 'U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD'],
            ['900', 'latin', 'U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD'],
        ];
    }

    /**
     * Build font CSS using local plugin assets with WOFF2 and TTF formats.
     */
    public static function font_css() {
        static $css = null;
        if ($css !== null) {
            return $css;
        }

        $css = '';
        $base_url = WOO_FACTOR_URL . 'assets/fonts/';

        foreach (self::font_faces() as [$weight, $script, $range]) {
            $woff2_url = esc_url($base_url . "vazirmatn-{$script}-{$weight}-normal.woff2");
            $ttf_url = esc_url($base_url . "vazirmatn-{$script}-{$weight}-normal.ttf");

            $css .= "@font-face {\n"
                  . "  font-family: 'Vazirmatn';\n"
                  . "  font-style: normal;\n"
                  . "  font-weight: {$weight};\n"
                  . "  font-display: swap;\n"
                  . "  src: url('{$woff2_url}') format('woff2'), url('{$ttf_url}') format('truetype');\n"
                  . "  unicode-range: {$range};\n"
                  . "}\n";
        }

        $base = WOO_FACTOR_DIR . self::FONT_CSS;
        if (is_readable($base)) {
            $css .= (string) file_get_contents($base);
        }

        return $css;
    }

    /**
     * Build floating action toolbar for web view.
     */
    public static function get_toolbar_html($data, $current_template = 'classic') {
        $order_id = $data['order_id'] ?? 0;
        $order_num = $data['order_number'] ?? '';
        $current_url = remove_query_arg(['template']);

        $classic_url = add_query_arg('template', 'classic', $current_url);
        $modern_url = add_query_arg('template', 'modern', $current_url);
        $thermal_url = add_query_arg('template', 'thermal', $current_url);

        $html = '<div class="no-print woo-factor-toolbar">'
              . '<div class="wf-tb-inner">'
              . '<div class="wf-tb-right">'
              . '<button type="button" onclick="window.print();" class="wf-btn wf-btn-primary" title="چاپ مستقیم یا ذخیره به صورت فایل PDF">🖨️ چاپ و ذخیره PDF (Print / PDF)</button>'
              . '<span class="wf-sep"></span>'
              . '<span class="wf-lbl">انتخاب قالب:</span>'
              . '<a href="' . esc_url($classic_url) . '" class="wf-btn ' . ($current_template === 'classic' ? 'wf-btn-active' : '') . '">رسمی دارایی</a>'
              . '<a href="' . esc_url($modern_url) . '" class="wf-btn ' . ($current_template === 'modern' ? 'wf-btn-active' : '') . '">مدرن شیک</a>'
              . '<a href="' . esc_url($thermal_url) . '" class="wf-btn ' . ($current_template === 'thermal' ? 'wf-btn-active' : '') . '">فیش‌پرینتر حرارتی</a>'
              . '</div>'
              . '<div class="wf-tb-left">'
              . '<span class="wf-hint" title="راهنمای خروجی PDF">💡 برای ذخیره PDF، مقصد چاپ (Destination) را روی <strong>Save as PDF</strong> بگذارید</span>'
              . '<span class="wf-sep"></span>'
              . '<span class="wf-info">فاکتور: <strong>' . woo_factor_fa_digits($data['invoice_number'] ?? $order_num) . '</strong></span>'
              . '</div>'
              . '</div>'
              . '</div>';

        $html .= '<style>
            .woo-factor-toolbar {
                position: sticky;
                top: 0;
                left: 0;
                right: 0;
                background: #0f172a;
                color: #ffffff;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 99999;
                padding: 8px 16px;
                font-family: "Vazirmatn", Tahoma, sans-serif;
                font-size: 12px;
                direction: rtl;
                margin-bottom: 12px;
            }
            .wf-tb-inner {
                max-width: 860px;
                margin: 0 auto;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 10px;
            }
            .wf-tb-right {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
            }
            .wf-btn {
                display: inline-flex;
                align-items: center;
                padding: 6px 12px;
                border-radius: 6px;
                background: #1e293b;
                color: #e2e8f0;
                text-decoration: none;
                font-weight: bold;
                font-size: 11px;
                border: 1px solid #334155;
                cursor: pointer;
                transition: all 0.2s;
            }
            .wf-btn:hover {
                background: #334155;
                color: #ffffff;
            }
            .wf-btn-primary {
                background: #0284c7;
                color: #ffffff;
                border-color: #0284c7;
            }
            .wf-btn-primary:hover {
                background: #0369a1;
            }
            .wf-btn-active {
                background: #0f766e;
                color: #ffffff;
                border-color: #0f766e;
            }
            .wf-sep {
                width: 1px;
                height: 20px;
                background: #334155;
                margin: 0 4px;
            }
            .wf-lbl {
                color: #94a3b8;
                font-size: 11px;
            }
            .wf-hint {
                color: #fef08a;
                font-size: 11px;
                background: rgba(254, 240, 138, 0.1);
                border: 1px solid rgba(254, 240, 138, 0.25);
                padding: 3px 8px;
                border-radius: 4px;
            }
            .wf-info {
                color: #cbd5e1;
                font-size: 11.5px;
            }
            @media screen and (max-width: 768px) {
                .woo-factor-toolbar {
                    padding: 8px 10px;
                }
                .wf-tb-inner {
                    flex-direction: column;
                    align-items: stretch;
                    gap: 8px;
                }
                .wf-tb-right {
                    justify-content: center;
                    width: 100%;
                }
                .wf-tb-left {
                    justify-content: center;
                    width: 100%;
                    flex-direction: column;
                    align-items: center;
                    gap: 6px;
                }
                .wf-hint {
                    text-align: center;
                    width: 100%;
                    box-sizing: border-box;
                }
            }
            @media print {
                .woo-factor-toolbar, .no-print {
                    display: none !important;
                }
            }
        </style>';

        return $html;
    }

    /**
     * Inject shared font/base styles right after <head> (or prepend if missing).
     */
    public static function inject_fonts($html) {
        $meta_noindex = '<meta name="robots" content="noindex, nofollow">';
        $style = $meta_noindex . '<style id="woo-factor-fonts">' . self::font_css() . '</style>';

        if (stripos($html, '<head>') !== false) {
            return preg_replace('/<head>/i', '<head>' . $style, $html, 1);
        }

        return $style . $html;
    }

    public static function render_html($data, $template_key = null) {
        $opts = woo_factor_options();
        if (empty($template_key)) {
            $template_key = $opts['template'] ?? 'classic';
        }

        $templates = woo_factor_templates();
        $template_file = $templates[$template_key]['file'] ?? $templates['classic']['file'];

        if (!file_exists($template_file)) {
            $template_file = WOO_FACTOR_DIR . 'templates/classic.php';
        }

        ob_start();
        include $template_file;
        $html = ob_get_clean();

        // Inject toolbar right after <body>
        $toolbar = self::get_toolbar_html($data, $template_key);
        if (stripos($html, '<body') !== false) {
            $html = preg_replace('/(<body[^>]*>)/i', '$1' . $toolbar, $html, 1);
        } else {
            $html = $toolbar . $html;
        }

        return self::inject_fonts($html);
    }

    public static function render_shipping_label($data) {
        $label_file = WOO_FACTOR_DIR . 'templates/shipping-label.php';
        ob_start();
        include $label_file;
        $html = ob_get_clean();

        return self::inject_fonts($html);
    }
}
