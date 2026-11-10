<?php
require '/root/wp-test/wp-load.php';
function wf_check($ok, $label) { if (!$ok) throw new RuntimeException($label); echo "PASS: $label\n"; }
$opts = woo_factor_options();
wf_check($opts['enable_checkout_customer_type'] === 'no', 'real WP defaults off');
$fields = apply_filters('woocommerce_billing_fields', []);
wf_check(!isset($fields['billing_customer_type'], $fields['billing_national_code'], $fields['billing_economic_code']), 'real Woo billing fields unchanged');
$opts['shop_name'] = 'فروشگاه نمونه آزمایشی';
$opts['shop_address'] = 'تهران، خیابان آزمایشی، پلاک ۱۲';
$opts['shop_postal_code'] = '1234567890';
$opts['shop_phone'] = '02100000000';
$opts['sender_mobile'] = '09120000000';
update_option('woo_factor_options', $opts);
$id = wc_get_product_id_by_sku('WF-TEST');
$product = $id ? wc_get_product($id) : new WC_Product_Simple();
$product->set_name('کالای نمونه آزمایشی برای بررسی چاپ');
$product->set_sku('WF-TEST'); $product->set_regular_price(125000); $product->save();
$order_id = get_option('wf_test_order');
$order = $order_id ? wc_get_order($order_id) : wc_create_order();
if (!$order_id) {
    $order->add_product($product, 2);
    $address = ['first_name'=>'خریدار', 'last_name'=>'آزمایشی', 'address_1'=>'خیابان نمونه، کوچه آزمایش، پلاک ۲۴', 'city'=>'تهران', 'state'=>'THR', 'country'=>'IR', 'postcode'=>'9876543210', 'phone'=>'09121111111'];
    $order->set_address($address, 'billing'); $order->set_address($address, 'shipping');
    $order->set_currency('IRR'); $order->set_payment_method_title('پرداخت آزمایشی');
    $order->calculate_totals(); $order->save(); update_option('wf_test_order', $order->get_id());
}
set_error_handler(function ($severity, $message) { throw new RuntimeException($message); });
$data = Woo_Factor_Invoice_Builder::build_from_order($order->get_id());
wf_check($data['seller']['sender_mobile'] === '09120000000', 'sender mobile reaches label data');
$order->update_meta_data('_billing_national_code', '0012345678'); $order->save();
$data = Woo_Factor_Invoice_Builder::build_from_order($order->get_id());
wf_check($data['seller']['name'] === $opts['shop_name'], 'edited sender reaches order builder');
wf_check(count($data['items']) === 1 && $data['totals']['grand_total'] === 250000.0, 'real Woo order totals');
$dir = '/root/woofactor-artifacts'; if (!is_dir($dir)) mkdir($dir);
foreach (['classic','modern','thermal'] as $template) {
    $html = Woo_Factor_Renderer::render_html($data, $template);
    wf_check(strpos($html, $opts['shop_name']) !== false && strpos($html, 'کالای نمونه') !== false, "$template real order rendering");
    file_put_contents("$dir/$template.html", $html);
}
file_put_contents("$dir/shipping-label.html", Woo_Factor_Renderer::render_shipping_label($data));
require_once WOO_FACTOR_DIR . 'admin/meta-boxes.php';
ob_start(); woo_factor_render_order_meta_box($order); $box = ob_get_clean();
wf_check(strpos($box, 'action=woo_factor_view_invoice') !== false && strpos($box, 'action=woo_factor_view_label') !== false, 'admin order invoice and postal actions');
file_put_contents("$dir/order-box.html", '<html lang="fa" dir="rtl"><meta charset="utf-8"><body>' . $box . '</body></html>');
file_put_contents("$dir/order.json", json_encode(['id'=>$order->get_id(), 'key'=>$order->get_order_key()]));
delete_option('woo_factor_options');
$def_opts = woo_factor_default_options();
wf_check($def_opts['template'] === 'classic', 'default template is classic');
$def_render = Woo_Factor_Renderer::render_html($data, $def_opts['template']);
wf_check(strpos($def_render, 'inv-header') !== false, 'renderer default template renders classic');
echo 'WordPress ' . get_bloginfo('version') . '; WooCommerce ' . WC_VERSION . "\n";
