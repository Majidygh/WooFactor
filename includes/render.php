<?php
defined('ABSPATH') || exit;

class Woo_Factor_Renderer {
    const FONT_CSS = 'assets/fonts/woo-factor-fonts.css';

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

    public static function font_css() {
        static $css = null;
        if ($css !== null) return $css;
        $css = '';
        $base_url = WOO_FACTOR_URL . 'assets/fonts/';
        foreach (self::font_faces() as [$weight, $script, $range]) {
            $woff2_url = esc_url($base_url . "vazirmatn-{$script}-{$weight}-normal.woff2");
            $ttf_url = esc_url($base_url . "vazirmatn-{$script}-{$weight}-normal.ttf");
            $css .= "@font-face { font-family: 'Vazirmatn'; font-style: normal; font-weight: {$weight}; font-display: swap; src: url('{$woff2_url}') format('woff2'), url('{$ttf_url}') format('truetype'); unicode-range: {$range}; }\n";
        }
        $base = WOO_FACTOR_DIR . self::FONT_CSS;
        if (is_readable($base)) $css .= (string)file_get_contents($base);
        return $css;
    }

    public static function get_toolbar_html($data, $current_template = 'modern') {
        $url = remove_query_arg(['template']);
        $html = '<nav class="no-print woo-factor-toolbar" aria-label="ابزار فاکتور"><button type="button" onclick="window.print()" class="wf-btn wf-print">چاپ / ذخیره PDF</button>';
        foreach (['modern' => 'مدرن', 'classic' => 'کلاسیک', 'thermal' => 'حرارتی'] as $key => $label) {
            $html .= '<a class="wf-btn" href="' . esc_url(add_query_arg('template', $key, $url)) . '"' . ($key === $current_template ? ' aria-current="page"' : '') . '>' . $label . '</a>';
        }
        $html .= '</nav><style>
            .woo-factor-toolbar { max-width:900px; margin:0 auto 12px; display:flex; flex-wrap:wrap; gap:8px; direction:rtl; font-family:"Vazirmatn",Tahoma,sans-serif; }
            .woo-factor-toolbar .wf-btn { box-sizing:border-box; display:inline-flex; justify-content:center; align-items:center; min-height:44px; min-width:44px; padding:8px 16px; background:#fff; color:#202b30; border:1px solid #59635f; border-radius:4px; font:inherit; font-size:13px; text-decoration:none; cursor:pointer; }
            .woo-factor-toolbar .wf-print { background:#243f36; color:#fff; border-color:#243f36; margin-left:auto; }
            .woo-factor-toolbar [aria-current] { border-bottom:3px solid #243f36; font-weight:700; }
            .woo-factor-toolbar .wf-btn:hover { text-decoration:underline; }
            .woo-factor-toolbar .wf-btn:active { transform:translateY(1px); }
            .woo-factor-toolbar .wf-btn:focus-visible { outline:3px solid #315c4e; outline-offset:3px; }
            .woo-factor-toolbar .wf-btn:disabled { cursor:not-allowed; text-decoration:line-through; }
            @media screen and (max-width:600px) { .woo-factor-toolbar { gap:6px; } .woo-factor-toolbar .wf-btn { flex:1; padding:8px; } .woo-factor-toolbar .wf-print { flex-basis:100%; margin:0; } }
            @media print { .woo-factor-toolbar, .no-print { display:none !important; } }
        </style>';
        return $html;
    }

    public static function inject_fonts($html) {
        $style = '<meta name="robots" content="noindex, nofollow"><style id="woo-factor-fonts">' . self::font_css() . '</style>';
        if (stripos($html, '<head>') !== false) return preg_replace('/<head>/i', '<head>' . $style, $html, 1);
        return $style . $html;
    }

    public static function render_html($data, $template_key = null) {
        $opts = woo_factor_options();
        if (empty($template_key)) $template_key = $opts['template'] ?? 'classic';
        $templates = woo_factor_templates();
        if (!isset($templates[$template_key]) || !file_exists($templates[$template_key]['file'])) $template_key = 'modern';
        $template_file = $templates[$template_key]['file'];
        ob_start();
        include $template_file;
        $html = ob_get_clean();
        $toolbar = self::get_toolbar_html($data, $template_key);
        if (stripos($html, '<body') !== false) {
            $html = preg_replace('/(<body[^>]*>)/i', '$1' . $toolbar, $html, 1);
        } else {
            $html = $toolbar . $html;
        }
        return self::inject_fonts($html);
    }

    public static function render_shipping_label($data) {
        ob_start();
        include WOO_FACTOR_DIR . 'templates/shipping-label.php';
        return self::inject_fonts(ob_get_clean());
    }
}
