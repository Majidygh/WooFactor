<?php
/**
 * Woo Factor Invoice - Helpers
 */
defined('ABSPATH') || exit;

function woo_factor_options() {
    $defaults = woo_factor_default_options();
    $opts = get_option('woo_factor_options', []);
    return wp_parse_args($opts, $defaults);
}

/**
 * Calculate a safe payable amount for a manually created invoice.
 * A discount can never make the final payable amount negative.
 */
function woo_factor_calculate_manual_grand_total($subtotal, $discount = 0, $shipping = 0, $tax = 0) {
    $subtotal = max(0, (float) $subtotal);
    $discount = min($subtotal, max(0, (float) $discount));
    $shipping = max(0, (float) $shipping);
    $tax = max(0, (float) $tax);

    return (float) max(0, $subtotal - $discount + $shipping + $tax);
}

/**
 * Keep the configurable brand color safe before it is injected into template CSS.
 */
function woo_factor_normalize_color($color, $fallback = '#0F766E') {
    $color = strtoupper(trim((string) $color));
    $fallback = strtoupper(trim((string) $fallback));

    return preg_match('/^#[A-F0-9]{6}$/', $color) ? $color : $fallback;
}

/**
 * Lighten (positive %) or darken (negative %) a hex color for gradients.
 */
function woo_factor_color_shade($color, $percent, $fallback = '#0F766E') {
    $fallback = strtoupper(trim((string) $fallback));
    if (!preg_match('/^#[A-F0-9]{6}$/', $fallback)) {
        $fallback = '#0F766E';
    }

    $color = trim((string) $color);
    if (!preg_match('/^#[A-F0-9]{6}$/i', $color)) {
        return $fallback;
    }

    $percent = max(-100, min(100, (float) $percent));
    $hex = substr($color, 1);
    $out = '#';

    for ($i = 0; $i < 3; $i++) {
        $channel = hexdec(substr($hex, $i * 2, 2));
        if ($percent >= 0) {
            $channel = $channel + ((255 - $channel) * $percent / 100);
        } else {
            $channel = $channel * (1 + $percent / 100);
        }
        $out .= str_pad(dechex((int) round(min(255, max(0, $channel)))), 2, '0', STR_PAD_LEFT);
    }

    return strtoupper($out);
}

/**
 * Build an invoice URL that lets a guest customer view only their own order.
 * WooCommerce order keys are unguessable and are validated server-side.
 */
function woo_factor_invoice_url($order) {
    if (!$order || !is_a($order, 'WC_Order')) {
        return '';
    }

    return add_query_arg([
        'action'   => 'woo_factor_view_invoice',
        'order_id' => $order->get_id(),
        'key'      => $order->get_order_key(),
    ], admin_url('admin-ajax.php'));
}

function woo_factor_templates() {
    return [
        'modern' => [
            'name' => 'قالب مدرن و استاندارد (پیش‌فرض)',
            'desc' => 'طراحی شرکتی و فروشگاهی مدرن، ساختاریافته، بسیار خوانا و بهینه‌سازی‌شده برای چاپ رسمی',
            'file' => WOO_FACTOR_DIR . 'templates/modern.php',
        ],
        'classic' => [
            'name' => 'قالب رسمی دارایی (ماده ۱۹ مالیاتی)',
            'desc' => 'صورتحساب رسمی استاندارد سازمان امور مالیاتی کشور با تفکیک حقیقی/حقوقی، کد اقتصادی و شناسه ملی',
            'file' => WOO_FACTOR_DIR . 'templates/classic.php',
        ],
        'thermal' => [
            'name' => 'قالب فیش‌پرینتر حرارتی (Thermal 80mm)',
            'desc' => 'مخصوص دستگاه‌های صدور فیش و پوز فروشگاهی با عرض ۸۰ میلی‌متر و کمترین مصرف کاغذ',
            'file' => WOO_FACTOR_DIR . 'templates/thermal.php',
        ],
    ];
}

function woo_factor_ensure_invoice_dir() {
    $upload_dir = wp_upload_dir();
    $dir = trailingslashit($upload_dir['basedir']) . 'woo-factor-invoices';
    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
        file_put_contents($dir . '/index.php', '<?php // Silence is golden');
        file_put_contents($dir . '/.htaccess', 'deny from all');
    }
    return $dir;
}

function woo_factor_get_logo_url() {
    $opts = woo_factor_options();
    if (!empty($opts['logo_id'])) {
        $src = wp_get_attachment_image_src($opts['logo_id'], 'full');
        if ($src) return $src[0];
    }
    if (!empty($opts['logo_url'])) {
        return $opts['logo_url'];
    }
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $src = wp_get_attachment_image_src($custom_logo_id, 'full');
        if ($src) return $src[0];
    }
    return '';
}

function woo_factor_get_stamp_url() {
    $opts = woo_factor_options();
    if (!empty($opts['stamp_image_id'])) {
        $src = wp_get_attachment_image_src($opts['stamp_image_id'], 'full');
        if ($src) return $src[0];
    }
    if (!empty($opts['stamp_image_url'])) {
        return $opts['stamp_image_url'];
    }
    return '';
}

/**
 * Sanitize callback for the woo_factor_options setting (registered in admin/settings.php).
 * Preserve saved extensions; accept only known fields from the submitted form.
 */
// Versions before 2.2.0 defaulted customer legal fields on; reset that one flag once, keep every other setting.
function woo_factor_migrate_customer_fields_default_off() {
    if (get_option('woo_factor_customer_fields_default_off')) {
        return;
    }
    $opts = get_option('woo_factor_options', []);
    if (is_array($opts) && ($opts['enable_checkout_customer_type'] ?? '') === 'yes') {
        $opts['enable_checkout_customer_type'] = 'no';
        update_option('woo_factor_options', $opts);
    }
    update_option('woo_factor_customer_fields_default_off', WOO_FACTOR_VERSION);
}

function woo_factor_sanitize_options($input) {
    $defaults = woo_factor_default_options();
    $input = is_array($input) ? $input : [];
    $saved = get_option('woo_factor_options', []);
    $clean = is_array($saved) ? $saved : [];
    $checkboxes = ['attach_woocommerce', 'myaccount_page', 'show_barcode', 'show_product_image', 'show_sku', 'show_tax_column', 'show_discount_column', 'show_watermark', 'show_signature', 'enable_checkout_customer_type', 'sms_enabled'];
    foreach ($defaults as $key => $default) {
        if (in_array($key, $checkboxes, true)) {
            $clean[$key] = ($input[$key] ?? 'no') === 'yes' ? 'yes' : 'no';
            continue;
        }
        if (!array_key_exists($key, $input)) {
            $clean[$key] = $clean[$key] ?? $default;
            continue;
        }
        $val = is_scalar($input[$key]) ? $input[$key] : '';
        if (is_bool($default) || in_array($val, ['1', '0', 1, 0, true, false], true) && in_array($key, ['require_login', 'attach_to_emails', 'show_in_myaccount'], true)) {
            $clean[$key] = (bool) $val;
        } elseif (in_array($key, ['footer_note', 'shop_address', 'invoice_terms'], true)) {
            $clean[$key] = sanitize_textarea_field($val);
        } elseif ($key === 'theme_color' || $key === 'color') {
            $clean[$key] = woo_factor_normalize_color($val);
        } elseif (in_array($key, ['logo_id', 'stamp_image_id'], true)) {
            $clean[$key] = absint($val);
        } else {
            $clean[$key] = sanitize_text_field($val);
        }
    }
    return $clean;
}
