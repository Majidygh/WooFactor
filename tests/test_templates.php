<?php
require '/root/wp-test/wp-load.php';
function template_check($ok, $label) { if (!$ok) throw new RuntimeException($label); echo "PASS: $label\n"; }
$data = Woo_Factor_Invoice_Builder::build_from_order(get_option('wf_test_order'));
template_check(is_array($data), 'real order fixture exists');
set_error_handler(function ($severity, $message) { throw new RuntimeException($message); });
$data['invoice_terms'] = 'شرایط آزمایشی فروشنده: بررسی کالا هنگام تحویل.';
$data['customer_note'] = 'یادداشت آزمایشی خریدار';
$data['totals']['transaction_id'] = 'TEST-123456';
$data['items'][0]['discount'] = 1000;
$data['seller']['name'] = 'فروشگاه نمونه آزمایشی';
$data['seller']['address'] = 'تهران، خیابان آزمایشی، پلاک ۱۲';
$data['seller']['phone'] = '02100000000';
$data['seller']['postal_code'] = '1234567890';
$data['totals']['discount'] = 1000;
$data['totals']['grand_total'] -= 1000;
$data['totals']['grand_total_words'] = woo_factor_number_to_words($data['totals']['grand_total']);
$data['items'][0]['total'] -= 1000;
foreach (['modern', 'classic'] as $template) {
    $html = Woo_Factor_Renderer::render_html($data, $template);
    template_check(strpos($html, $data['totals']['grand_total_words']) !== false, "$template amount in words");
    template_check(strpos($html, $data['invoice_terms']) !== false && strpos($html, 'TEST-123456') !== false, "$template terms and transaction preserved");
    for ($mask = 0; $mask < 16; $mask++) {
        $variant = $data;
        foreach (['show_sku', 'show_tax_column', 'show_discount_column', 'show_signature'] as $bit => $flag) $variant[$flag] = (bool)($mask & (1 << $bit));
        $html = Woo_Factor_Renderer::render_html($variant, $template);
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new DOMXPath($doc);
        $headers = $xpath->query('//table[@class="items-table"]/thead/tr/th')->length;
        $cells = $xpath->query('//table[@class="items-table"]/tbody/tr/td')->length;
        template_check($headers === $cells && $headers === 5 + (int)$variant['show_sku'] + (int)$variant['show_tax_column'] + (int)$variant['show_discount_column'], "$template columns mask $mask");
        template_check((strpos($html, 'WF-TEST') !== false) === $variant['show_sku'] && ($xpath->query('//div[@class="signature"]')->length > 0) === $variant['show_signature'], "$template visibility mask $mask");
    }
    $empty = $data; $empty['items'] = [];
    $marked = $data; $marked['watermark_text'] = 'متن اختصاصی آزمایشی';
    template_check(strpos(Woo_Factor_Renderer::render_html($marked, $template), $marked['watermark_text']) !== false, "$template custom watermark retained");
    $marked['show_watermark'] = false;
    template_check(strpos(Woo_Factor_Renderer::render_html($marked, $template), $marked['watermark_text']) === false, "$template watermark toggle");
    template_check(strpos(Woo_Factor_Renderer::render_html($empty, $template), 'کالایی در این فاکتور ثبت نشده') !== false, "$template empty state");
    $proforma = $data; $proforma['type'] = 'proforma';
    template_check(strpos(Woo_Factor_Renderer::render_html($proforma, $template), '<h1 class="inv-doc-title">پیش‌فاکتور</h1>') !== false, "$template proforma title");
    $unsafe = $data; $unsafe['buyer']['name'] = '<script>alert(1)</script>';
    template_check(strpos(Woo_Factor_Renderer::render_html($unsafe, $template), '<script>') === false, "$template escapes buyer input");
    file_put_contents('/root/woofactor-artifacts/' . $template . '.html', Woo_Factor_Renderer::render_html($data, $template));
    $long = $data; $long['items'] = array_fill(0, 45, $data['items'][0]);
    foreach (['subtotal', 'discount', 'grand_total'] as $key) $long['totals'][$key] *= 45;
    $long['totals']['grand_total_words'] = woo_factor_number_to_words($long['totals']['grand_total']);
    $long['items'][44]['title'] = 'آخرین کالای آزمایشی';
    file_put_contents('/root/woofactor-artifacts/' . $template . '-long.html', Woo_Factor_Renderer::render_html($long, $template));
}
$before = get_option('woo_factor_options');
update_option('woo_factor_options', array_merge(woo_factor_options(), ['template' => 'classic']));
template_check(strpos(Woo_Factor_Renderer::render_html($data), 'body class="classic"') !== false, 'stored classic preference preserved');
if ($before === false) delete_option('woo_factor_options'); else update_option('woo_factor_options', $before);
template_check(strpos(Woo_Factor_Renderer::render_html($data, 'missing'), 'body class="modern"') !== false, 'invalid template falls back to modern');
