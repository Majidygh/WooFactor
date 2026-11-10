<?php
defined('ABSPATH') || exit;
$seller = $data['seller'] ?? [];
$buyer = $data['buyer'] ?? [];
$items = $data['items'] ?? [];
$totals = $data['totals'] ?? [];
$currency = $totals['currency'] ?? 'تومان';
$proforma = ($data['type'] ?? '') === 'proforma' || ($data['status'] ?? '') === 'proforma' || ($data['payment_status'] ?? '') === 'proforma' || !empty($data['is_proforma']);
$title = $data['invoice_title'] ?? ($proforma ? 'پیش‌فاکتور' : ($classic ? 'صورتحساب فروش کالا و خدمات' : 'فاکتور فروش'));
$number = $data['invoice_number'] ?? $data['order_number'] ?? '';
$sku = ($data['show_sku'] ?? true) && count(array_filter(array_column($items, 'sku'), static function ($v) { return trim($v) !== '' && trim($v) !== '-'; })) > 0;
$discount = ($data['show_discount_column'] ?? true) && count(array_filter(array_column($items, 'discount'))) > 0;
$tax = $data['show_tax_column'] ?? true;
$columns = 5 + (int)$sku + (int)$discount + (int)$tax;
$logo = $seller['logo_url'] ?? '';
$stamp = $data['stamp_url'] ?? $seller['stamp_url'] ?? '';
$accent = sanitize_hex_color($data['color'] ?? '') ?: '#0f766e';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html($title . ' ' . $number); ?></title>
<style><?php include __DIR__ . '/invoice.css'; ?></style>
</head>
<body class="<?php echo $classic ? 'classic' : 'modern'; ?>" style="--merchant-color:<?php echo esc_attr($accent); ?>">
<main class="invoice-sheet">
<header class="inv-header">
    <div class="identity">
        <?php if ($logo): ?><img class="logo" src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($seller['name'] ?? ''); ?>" width="100" height="56"><?php endif; ?>
        <div class="shop-name"><?php echo esc_html($seller['name'] ?? ''); ?></div>
        <h1 class="inv-doc-title"><?php echo esc_html($title); ?></h1>
    </div>
    <dl class="document-meta">
        <div><dt>شماره</dt><dd><bdi><?php echo esc_html(woo_factor_fa_digits($number)); ?></bdi></dd></div>
        <div><dt>تاریخ</dt><dd><?php echo esc_html(woo_factor_fa_digits($data['jalali_date'] ?? '')); ?></dd></div>
        <?php if (!empty($data['jalali_time'])): ?><div><dt>ساعت</dt><dd><?php echo esc_html(woo_factor_fa_digits($data['jalali_time'])); ?></dd></div><?php endif; ?>
        <?php if (!empty($data['status_name'])): ?><div><dt>وضعیت</dt><dd><?php echo esc_html($data['status_name']); ?></dd></div><?php endif; ?>
    </dl>
</header>
<?php if (($data['show_watermark'] ?? true) && !empty($data['watermark_text']) && $data['watermark_text'] !== ($data['status_name'] ?? '') && (empty($data['status_name']) || ($data['watermark_mode'] ?? '') === 'custom' || !in_array($data['watermark_text'], ['پرداخت شد', 'در انتظار پرداخت', 'باطل شد', 'مرجوع شد', 'پیش‌فاکتور', 'تسویه نشده'], true))): ?>
<p class="document-status"><?php echo esc_html($data['watermark_text']); ?></p>
<?php endif; ?>
<div class="parties">
<?php foreach (['seller' => 'فروشنده', 'buyer' => 'خریدار'] as $role => $label):
    $party = $role === 'seller' ? $seller : $buyer;
    $fields = [
        'نام' => !empty($party['company']) ? $party['company'] . ' (' . ($party['name'] ?? '') . ')' : ($party['name'] ?? ''),
        'کد / شناسه ملی' => $party['national_id'] ?? $party['national_code'] ?? '',
        'کد اقتصادی' => $party['economic_code'] ?? $party['economic_id'] ?? '',
        'شماره ثبت' => $party['registration_no'] ?? '',
        'کد پستی' => $party['postal_code'] ?? $party['postcode'] ?? '',
        'تلفن' => $party['phone'] ?? '',
        'نشانی' => $party['full_address'] ?? $party['address'] ?? '',
    ]; ?>
<section class="party"><h2><?php echo esc_html($label); ?><?php if (!empty($party['customer_type'])): ?><span><?php echo $party['customer_type'] === 'legal' ? 'حقوقی' : 'حقیقی'; ?></span><?php endif; ?></h2>
<dl><?php foreach ($fields as $label => $value): if ($value === '' && !$classic) continue; ?>
<div><dt><?php echo esc_html($label); ?></dt><dd><?php echo $value !== '' ? esc_html(woo_factor_fa_digits($value)) : 'ثبت نشده'; ?></dd></div>
<?php endforeach; ?></dl></section>
<?php endforeach; ?>
</div>
<table class="items-table">
<caption>اقلام فاکتور <span>(مبالغ به <?php echo esc_html($currency); ?>)</span></caption>
<thead><tr><th scope="col">ردیف</th><th scope="col">شرح کالا یا خدمات</th><?php if ($sku): ?><th scope="col">کد کالا</th><?php endif; ?><th scope="col">تعداد</th><th scope="col">مبلغ واحد</th><?php if ($discount): ?><th scope="col">تخفیف</th><?php endif; ?><?php if ($tax): ?><th scope="col">مالیات</th><?php endif; ?><th scope="col">مبلغ کل</th></tr></thead>
<tbody>
<?php foreach ($items as $index => $item):
    $qty = (float)($item['qty'] ?? 1);
    $unit = $item['unit_price'] ?? $item['price'] ?? ($qty > 0 ? ($item['subtotal'] ?? $item['total'] ?? 0) / $qty : 0);
?>
<tr>
<td data-label="ردیف"><?php echo esc_html(woo_factor_fa_digits($index + 1)); ?></td>
<td class="item-description" data-label="شرح">
<?php if (!empty($data['show_product_image']) && !empty($item['thumbnail_url'])): ?><img src="<?php echo esc_url($item['thumbnail_url']); ?>" alt="" width="40" height="40"><?php endif; ?>
<strong><?php echo esc_html($item['title'] ?? ''); ?></strong>
<?php if (!empty($item['meta'])): ?><small><?php echo esc_html($item['meta']); ?></small><?php endif; ?>
</td>
<?php if ($sku): ?><td data-label="کد کالا"><bdi><?php echo esc_html($item['sku'] ?? ''); ?></bdi></td><?php endif; ?>
<td data-label="تعداد"><?php echo esc_html(woo_factor_fa_digits($qty)); ?></td>
<td data-label="مبلغ واحد"><?php echo woo_factor_number_format($unit); ?></td>
<?php if ($discount): ?><td data-label="تخفیف"><?php echo woo_factor_number_format($item['discount'] ?? 0); ?></td><?php endif; ?>
<?php if ($tax): ?><td data-label="مالیات"><?php echo woo_factor_number_format($item['tax'] ?? 0); ?><?php if (!empty($item['tax_percent'])): ?><small><?php echo esc_html(woo_factor_fa_digits($item['tax_percent'])); ?>٪</small><?php endif; ?></td><?php endif; ?>
<td data-label="مبلغ کل" class="line-total"><?php echo woo_factor_number_format($item['total'] ?? ($unit * $qty)); ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$items): ?><tr><td class="empty" colspan="<?php echo (int)$columns; ?>">کالایی در این فاکتور ثبت نشده است.</td></tr><?php endif; ?>
</tbody></table>
<div class="settlement">
<div class="notes">
<?php foreach (['روش پرداخت' => $data['payment_method'] ?? $totals['payment_method'] ?? '', 'شناسه تراکنش' => $totals['transaction_id'] ?? '', 'روش ارسال' => $totals['shipping_method'] ?? '', 'یادداشت خریدار' => $data['customer_note'] ?? ''] as $label => $value): if ($value === '') continue; ?>
<p><strong><?php echo esc_html($label); ?>:</strong> <bdi><?php echo nl2br(esc_html($value)); ?></bdi></p>
<?php endforeach; ?>
</div>
<table class="totals-table"><caption class="sr-only">خلاصه مبالغ</caption><tbody>
<?php foreach (['subtotal' => 'جمع اقلام', 'discount' => 'تخفیف', 'shipping' => 'هزینه ارسال', 'tax' => 'مالیات'] as $key => $label): if ($key !== 'subtotal' && empty($totals[$key])) continue; ?>
<tr><th scope="row"><?php echo esc_html($label); ?></th><td><?php echo woo_factor_number_format($totals[$key] ?? 0); ?></td></tr>
<?php endforeach; ?>
<tr class="grand-total"><th scope="row">مبلغ نهایی <small><?php echo esc_html($currency); ?></small></th><td><?php echo woo_factor_number_format($totals['grand_total'] ?? 0); ?></td></tr>
</tbody></table>
</div>
<?php if (!empty($totals['grand_total_words'])): ?><p class="amount-words">به حروف: <strong><?php echo esc_html($totals['grand_total_words'] . ' ' . $currency); ?></strong></p><?php endif; ?>
<footer>
<?php if (!empty($data['invoice_terms'])): ?><section class="terms"><h2>شرایط فاکتور</h2><p><?php echo nl2br(esc_html($data['invoice_terms'])); ?></p></section><?php endif; ?>
<?php if (!empty($data['footer_note'])): ?><p class="footer-note"><?php echo nl2br(esc_html($data['footer_note'])); ?></p><?php endif; ?>
<div class="endorsements">
<?php if (!empty($data['show_barcode']) && !empty($data['barcode_svg'])): ?><div class="barcode"><?php echo $data['barcode_svg']; ?><div><?php echo esc_html(woo_factor_fa_digits($data['order_number'] ?? $number)); ?></div></div><?php endif; ?>
<?php if ($data['show_signature'] ?? true): ?><div class="signature"><span><?php echo esc_html($data['signature_stamp'] ?? 'مهر و امضای فروشنده'); ?></span><?php if ($stamp): ?><img src="<?php echo esc_url($stamp); ?>" alt="مهر فروشنده" width="120" height="64"><?php endif; ?></div><?php endif; ?>
</div>
</footer>
</main>
</body>
</html>
