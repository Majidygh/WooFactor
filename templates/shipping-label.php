<?php
/**
 * Shipping Label Template (برچسب پستی مرسوله)
 * Conforming to Post and Courier delivery standards with customer note & sender mobile.
 */
defined('ABSPATH') || exit;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <title>برچسب پستی سفارش <?php echo esc_html($data['order_number']); ?></title>
    <style>
        @page { size: A5 landscape; margin: 8mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body {
            font-family: 'Vazirmatn', 'Tahoma', sans-serif;
            background: #f8fafc;
            direction: rtl;
            padding: 15px;
            font-size: 12px;
        }
        .label-box {
            width: 100%;
            max-width: 680px;
            margin: 0 auto;
            border: 2px dashed #0f172a;
            padding: 14px;
            background: #fff;
            border-radius: 6px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header-table td { vertical-align: middle; }
        .sender-box-wrap {
            width: 100%;
            margin-bottom: 8px;
            display: flex;
            justify-content: flex-end; /* In RTL, flex-end pushes to the left */
        }
        .sender-box {
            width: 50%;
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            border-radius: 4px;
            background: #f8fafc;
            text-align: right;
            direction: rtl;
        }
        .receiver-box {
            width: 100%;
            border: 1px solid #86efac;
            padding: 8px 10px;
            border-radius: 4px;
            margin-bottom: 8px;
            background: #f0fdf4;
            text-align: right;
            direction: rtl;
        }
        .box-title {
            font-weight: 800;
            font-size: 12px;
            margin-bottom: 4px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
        }
        .receiver-box .box-title {
            color: #166534;
            border-color: #86efac;
        }
        .info-line {
            margin-bottom: 3px;
            line-height: 1.5;
        }
        .postcode-tag {
            font-size: 13px;
            font-weight: 900;
            background: #166534;
            color: #fff;
            padding: 2px 8px;
            border-radius: 4px;
            display: inline-block;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            color: #475569;
            margin-top: 4px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="max-width: 680px; margin: 0 auto 10px; text-align: left;">
        <button onclick="window.print();" style="padding: 6px 14px; background: #0f172a; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-family: inherit; font-weight: bold;">🖨️ چاپ برچسب پستی</button>
    </div>

    <div class="label-box">
        <table class="header-table">
            <tr>
                <td style="width: 50%;">
                    <div style="font-size: 15px; font-weight: 900; color: #0f172a;">📮 مرسوله پستی / پیک اکسپرس</div>
                    <div style="font-size: 11px; color: #475569; margin-top: 2px;">سفارش شماره: <strong><?php echo woo_factor_fa_digits($data['order_number']); ?></strong></div>
                </td>
                <td style="width: 50%; text-align: left;">
                    <?php if (!empty($data['barcode_svg'])): ?>
                        <?php echo $data['barcode_svg']; ?>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <!-- Sender Box: Placed at top, starting from center to the left (50% width) -->
        <div class="sender-box-wrap">
            <div class="sender-box">
                <div class="box-title">فرستنده: <?php echo esc_html($data['seller']['name']); ?></div>
                <div class="info-line">نشانی: <?php echo esc_html($data['seller']['address'] ?: 'دفتر مرکزی فروشگاه'); ?></div>
                <div class="info-line">
                    تلفن / همراه: <strong><?php echo esc_html(woo_factor_fa_digits(!empty($data['seller']['sender_mobile']) ? $data['seller']['sender_mobile'] : ($data['seller']['phone'] ?: '-'))); ?></strong>
                    <?php if (!empty($data['seller']['postal_code'])): ?>
                        &nbsp;|&nbsp; کد پستی: <?php echo woo_factor_fa_digits($data['seller']['postal_code']); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Receiver Box: Full width, positioned below sender, aligned towards the right -->
        <div class="receiver-box">
            <div class="box-title">گیرنده: <?php echo esc_html($data['buyer']['shipping_name']); ?></div>
            <div class="info-line" style="font-size: 13px; font-weight: bold; color: #0f172a;">نشانی کامل: <?php echo esc_html($data['buyer']['shipping_address'] ?: $data['buyer']['full_address']); ?></div>
            <div class="info-line" style="margin-top: 5px;">
                کد پستی ۱۰ رقمی: <span class="postcode-tag"><?php echo woo_factor_fa_digits($data['buyer']['shipping_postcode'] ?: '-'); ?></span>
                &nbsp;&nbsp;&nbsp;&nbsp;
                شماره همراه گیرنده: <strong><?php echo woo_factor_fa_digits($data['buyer']['shipping_phone'] ?: '-'); ?></strong>
            </div>
        </div>

        <!-- Customer Note -->
        <?php if (!empty($data['customer_note'])): ?>
            <div style="border: 1px dashed #f59e0b; background: #fffbeb; border-radius: 4px; padding: 6px 10px; margin-bottom: 8px; font-size: 10.5px; color: #92400e;">
                <strong>یادداشت خریدار:</strong> <?php echo esc_html($data['customer_note']); ?>
            </div>
        <?php endif; ?>

        <!-- Packed Items -->
        <?php if (!empty($data['items'])): ?>
            <div style="border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px 8px; margin-bottom: 8px; font-size: 10px; background: #fff;">
                <strong>اقلام مرسوله:</strong>
                <?php 
                $item_summaries = [];
                foreach ($data['items'] as $it) {
                    $item_summaries[] = esc_html($it['title']) . ' (' . woo_factor_fa_digits($it['qty']) . ' عدد)';
                }
                echo implode(' ، ', $item_summaries);
                ?>
            </div>
        <?php endif; ?>

        <table class="footer-table">
            <tr>
                <td>روش ارسال: <strong><?php echo esc_html($data['totals']['shipping_method']); ?></strong></td>
                <td style="text-align: left;">تاریخ ثبت: <strong><?php echo woo_factor_fa_digits($data['jalali_date']); ?></strong></td>
            </tr>
        </table>
    </div>
</body>
</html>
