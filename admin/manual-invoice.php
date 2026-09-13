<?php
/**
 * Manual Invoice Creation, Editing, Storage, and Management
 */
defined('ABSPATH') || exit;

/**
 * Get or create the dedicated directory for storing manual invoices as compact JSON files.
 */
function woo_factor_get_manual_invoices_dir() {
    $upload_dir = wp_upload_dir();
    $dir = trailingslashit($upload_dir['basedir']) . 'woo-factor/manual-invoices';
    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
        @file_put_contents($dir . '/index.php', '<?php // Silence is golden');
        @file_put_contents($dir . '/.htaccess', 'deny from all');
    }
    return $dir;
}

/**
 * Sanitize invoice number for safe filename usage.
 */
function woo_factor_sanitize_invoice_filename($inv_num) {
    $clean = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$inv_num);
    return !empty($clean) ? $clean : ('MAN-' . wp_rand(10000, 99999));
}

/**
 * Save manual invoice data to a compact JSON file in the dedicated folder.
 */
function woo_factor_save_manual_invoice_file($inv_num, $data) {
    $dir = woo_factor_get_manual_invoices_dir();
    $safe_name = woo_factor_sanitize_invoice_filename($inv_num);
    $file = $dir . '/' . $safe_name . '.json';
    return file_put_contents($file, wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

/**
 * Retrieve a saved manual invoice by invoice number.
 */
function woo_factor_get_manual_invoice_file($inv_num) {
    $dir = woo_factor_get_manual_invoices_dir();
    $safe_name = woo_factor_sanitize_invoice_filename($inv_num);
    $file = $dir . '/' . $safe_name . '.json';
    if (!file_exists($file)) {
        return null;
    }
    $content = file_get_contents($file);
    if (empty($content)) {
        return null;
    }
    return json_decode($content, true);
}

/**
 * Delete a saved manual invoice file.
 */
function woo_factor_delete_manual_invoice_file($inv_num) {
    $dir = woo_factor_get_manual_invoices_dir();
    $safe_name = woo_factor_sanitize_invoice_filename($inv_num);
    $file = $dir . '/' . $safe_name . '.json';
    if (file_exists($file)) {
        return unlink($file);
    }
    return false;
}

/**
 * List all saved manual invoices sorted by newest first.
 */
function woo_factor_list_manual_invoice_files() {
    $dir = woo_factor_get_manual_invoices_dir();
    $files = glob($dir . '/*.json');
    if (!$files) {
        return [];
    }

    $list = [];
    foreach ($files as $file) {
        $basename = basename($file, '.json');
        $content = file_get_contents($file);
        if (!$content) continue;
        $json = json_decode($content, true);
        if (!is_array($json)) continue;

        $payment_status = $json['payment_status'] ?? ($json['status'] ?? 'paid');
        $status_name = $json['status_name'] ?? ($payment_status === 'unpaid' ? 'تسویه نشده' : ($payment_status === 'proforma' ? 'پیش‌فاکتور' : ($payment_status === 'none' ? 'صادر شد' : 'تسویه شده')));

        $list[] = [
            'invoice_number' => $json['invoice_number'] ?? $basename,
            'buyer_name'     => $json['buyer']['name'] ?? 'مشتری محترم',
            'buyer_phone'    => $json['buyer']['phone'] ?? '-',
            'jalali_date'    => $json['jalali_date'] ?? '-',
            'items_count'    => count($json['items'] ?? []),
            'grand_total'    => $json['totals']['grand_total'] ?? 0,
            'currency'       => $json['totals']['currency'] ?? 'تومان',
            'template'       => $json['template'] ?? 'classic',
            'payment_status' => $payment_status,
            'status_name'    => $status_name,
            'created_at'     => $json['created_at'] ?? filemtime($file),
        ];
    }

    usort($list, function ($a, $b) {
        return ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0);
    });

    return $list;
}

/**
 * Render the Manual Invoice Page with Create/Edit Form and Archive Table.
 */
function woo_factor_render_manual_invoice_page() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die('دسترسی غیرمجاز.');
    }

    $opts = woo_factor_options();
    $notice = '';

    // Handle Deletion Action
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['inv'])) {
        $inv_to_del = sanitize_text_field(wp_unslash($_GET['inv']));
        if (check_admin_referer('woo_factor_del_manual_' . $inv_to_del)) {
            if (woo_factor_delete_manual_invoice_file($inv_to_del)) {
                $notice = '<div class="notice notice-success is-dismissible" style="margin: 15px 0;"><p>فاکتور دستی <strong>' . esc_html($inv_to_del) . '</strong> با موفقیت از آرشیو حذف شد.</p></div>';
            }
        }
    }

    // Handle Edit Mode Loading
    $is_edit = false;
    $edit_data = null;
    if (isset($_GET['edit']) && !empty($_GET['edit'])) {
        $edit_inv = sanitize_text_field(wp_unslash($_GET['edit']));
        $edit_data = woo_factor_get_manual_invoice_file($edit_inv);
        if ($edit_data) {
            $is_edit = true;
        }
    }

    $default_inv_num = $is_edit ? $edit_data['invoice_number'] : ('MAN-' . wp_rand(10000, 99999));
    $default_template = $is_edit ? ($edit_data['template'] ?? 'classic') : ($opts['template'] ?? 'classic');
    $default_currency = $is_edit ? ($edit_data['currency'] ?? 'تومان') : 'تومان';
    $default_payment_status = $is_edit ? ($edit_data['payment_status'] ?? ($edit_data['status'] ?? 'paid')) : 'paid';
    $default_watermark_mode = $is_edit ? ($edit_data['watermark_mode'] ?? 'auto') : 'auto';
    $default_custom_watermark = $is_edit ? ($edit_data['custom_watermark'] ?? ($edit_data['watermark_text'] ?? '')) : '';
    $buyer = $edit_data['buyer'] ?? [];
    $items = $edit_data['items'] ?? [];
    $totals = $edit_data['totals'] ?? [];

    if (empty($items)) {
        $items = [
            [
                'title'       => '',
                'sku'         => '',
                'qty'         => 1,
                'price'       => 0,
                'tax_percent' => 0,
            ]
        ];
    }

    $saved_invoices = woo_factor_list_manual_invoice_files();
    ?>
    <div class="wrap" style="direction: rtl; text-align: right; max-width: 1100px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <div>
                <h1 style="display: flex; align-items: center; gap: 10px; margin: 0 0 5px 0;">
                    <span class="dashicons dashicons-edit-page" style="font-size: 32px; width: 32px; height: 32px; color: #0f766e;"></span>
                    <span>صدور و مدیریت فاکتورهای دستی</span>
                </h1>
                <p class="description" style="margin: 0;">صدور فاکتور مستقل برای مشتریان حضوری، تلفنی یا خدمات بدون نیاز به ثبت سفارش در ووکامرس (همراه با ذخیره‌سازی سبک در پوشه اختصاصی).</p>
            </div>
            <div>
                <?php if ($is_edit): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=woo-factor-manual-invoice')); ?>" class="button button-secondary">➕ صدور فاکتور جدید</a>
                <?php endif; ?>
                <a href="#saved-invoices-archive" class="button button-secondary">📁 مشاهده آرشیو فاکتورها (<?php echo count($saved_invoices); ?>)</a>
            </div>
        </div>

        <?php echo $notice; ?>

        <?php if ($is_edit): ?>
            <div class="notice notice-info" style="padding: 12px 16px; margin: 15px 0; border-right: 4px solid #0284c7; background: #f0f9ff; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong style="color: #0369a1; font-size: 13px;">✏️ در حال ویرایش فاکتور شماره: <?php echo esc_html($default_inv_num); ?></strong>
                    <div style="font-size: 11.5px; color: #475569; margin-top: 3px;">پس از اعمال تغییرات، دکمه ذخیره و چاپ را بزنید تا نسخه به‌روزرسانی‌شده در پوشه ذخیره گردد.</div>
                </div>
                <a href="<?php echo esc_url(admin_url('admin.php?page=woo-factor-manual-invoice')); ?>" class="button button-small">انصراف و فاکتور جدید</a>
            </div>
        <?php endif; ?>

        <form id="woo_factor_manual_form" method="post" target="_blank" action="<?php echo esc_url(admin_url('admin-ajax.php?action=woo_factor_generate_manual_invoice')); ?>">
            <?php wp_nonce_field('woo_factor_manual_nonce'); ?>
            <input type="hidden" name="is_edit" value="<?php echo $is_edit ? '1' : '0'; ?>">

            <!-- Customer & Invoice Info -->
            <div style="background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #cbd5e1; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                <h3 style="color: #0f172a; margin: 0 0 12px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-businessman" style="color: #0f766e;"></span>
                    <span>اطلاعات پایه فاکتور و خریدار</span>
                </h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                    <div>
                        <label><strong>شماره فاکتور:</strong></label><br>
                        <input type="text" name="invoice_number" value="<?php echo esc_attr($default_inv_num); ?>" class="regular-text" style="width: 100%; font-weight: bold; background: #f8fafc;" <?php echo $is_edit ? 'readonly' : ''; ?> required>
                    </div>
                    <div>
                        <label><strong>نام خریدار / شرکت:</strong></label><br>
                        <input type="text" name="buyer_name" value="<?php echo esc_attr($buyer['name'] ?? ''); ?>" placeholder="مثال: مهندس کاظمی" class="regular-text" style="width: 100%;" required>
                    </div>
                    <div>
                        <label><strong>کد ملی / شناسه خریدار:</strong></label><br>
                        <input type="text" name="buyer_national_id" value="<?php echo esc_attr($buyer['national_id'] ?? ''); ?>" placeholder="۱۰ رقمی یا شناسه ملی" class="regular-text" style="width: 100%;">
                    </div>
                    <div>
                        <label><strong>شماره تماس:</strong></label><br>
                        <input type="text" name="buyer_phone" value="<?php echo esc_attr($buyer['phone'] ?? ''); ?>" placeholder="۰۹۱۲..." class="regular-text" style="width: 100%;">
                    </div>
                    <div>
                        <label><strong>قالب فاکتور:</strong></label><br>
                        <select name="template" style="width: 100%;">
                            <option value="classic" <?php selected($default_template, 'classic'); ?>>قالب رسمی دارایی (Classic Tax)</option>
                            <option value="modern" <?php selected($default_template, 'modern'); ?>>قالب مدرن و لوکس (Modern Clean)</option>
                            <option value="thermal" <?php selected($default_template, 'thermal'); ?>>قالب فیش‌پرینتر حرارتی (Thermal 80mm)</option>
                        </select>
                    </div>
                    <div>
                        <label><strong>واحد پول:</strong></label><br>
                        <select name="currency" style="width: 100%;">
                            <option value="تومان" <?php selected($default_currency, 'تومان'); ?>>تومان</option>
                            <option value="ریال" <?php selected($default_currency, 'ریال'); ?>>ریال</option>
                            <option value="هزار تومان" <?php selected($default_currency, 'هزار تومان'); ?>>هزار تومان</option>
                        </select>
                    </div>
                    <div>
                        <label><strong>وضعیت تسویه و پرداخت:</strong></label><br>
                        <select name="payment_status" id="manual_payment_status" style="width: 100%; font-weight: bold;">
                            <option value="paid" <?php selected($default_payment_status, 'paid'); ?>>🟢 پرداخت شده / تسویه کامل</option>
                            <option value="unpaid" <?php selected($default_payment_status, 'unpaid'); ?>>🟡 پرداخت نشده / در انتظار تسویه</option>
                            <option value="proforma" <?php selected($default_payment_status, 'proforma'); ?>>🔵 پیش‌فاکتور (غیرقطعی)</option>
                            <option value="none" <?php selected($default_payment_status, 'none'); ?>>⚪ فاکتور عادی بدون وضعیت پرداخت</option>
                        </select>
                    </div>
                    <div>
                        <label><strong>متن واترمارک پس‌زمینه:</strong></label><br>
                        <select name="watermark_mode" id="manual_watermark_mode" style="width: 100%;">
                            <option value="auto" <?php selected($default_watermark_mode, 'auto'); ?>>خودکار بر اساس وضعیت پرداخت</option>
                            <option value="none" <?php selected($default_watermark_mode, 'none'); ?>>بدون واترمارک (خام و بدون مهر متن)</option>
                            <option value="custom" <?php selected($default_watermark_mode, 'custom'); ?>>متن دلخواه...</option>
                        </select>
                    </div>
                    <div id="manual_custom_watermark_wrap" style="<?php echo $default_watermark_mode === 'custom' ? '' : 'display: none;'; ?>">
                        <label><strong>متن دلخواه واترمارک:</strong></label><br>
                        <input type="text" name="custom_watermark" value="<?php echo esc_attr($default_custom_watermark); ?>" placeholder="مثال: تسویه شد / امانی / نسخه بایگانی" class="regular-text" style="width: 100%;">
                    </div>
                </div>
                <div style="margin-top: 12px;">
                    <label><strong>نشانی کامل تحویل / خریدار:</strong></label><br>
                    <textarea name="buyer_address" rows="2" style="width: 100%;" placeholder="استان، شهر، خیابان، پلاک..."><?php echo esc_textarea($buyer['address'] ?? ($buyer['full_address'] ?? '')); ?></textarea>
                </div>
            </div>

            <!-- Items Table with SKU and VAT Percentage -->
            <div style="background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #cbd5e1; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 12px;">
                    <h3 style="color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-cart" style="color: #0f766e;"></span>
                        <span>اقلام، کد محصول (SKU) و مالیات بر ارزش افزوده</span>
                    </h3>
                    <span style="font-size: 11px; color: #64748b;">💡 در صورت خالی بودن کد کالا، ستون SKU در خروجی چاپی خودکار مخفی می‌شود.</span>
                </div>

                <table id="woo_factor_items_table" style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    <thead>
                        <tr style="background: #0f172a; color: #ffffff; text-align: center;">
                            <th style="padding: 10px 8px; border: 1px solid #334155; width: 30%;">شرح کالا یا خدمت</th>
                            <th style="padding: 10px 8px; border: 1px solid #334155; width: 14%;">کد محصول (SKU)</th>
                            <th style="padding: 10px 8px; border: 1px solid #334155; width: 8%;">تعداد</th>
                            <th style="padding: 10px 8px; border: 1px solid #334155; width: 16%;">قیمت واحد</th>
                            <th style="padding: 10px 8px; border: 1px solid #334155; width: 12%;">مالیات (٪)</th>
                            <th style="padding: 10px 8px; border: 1px solid #334155; width: 14%;">جمع سطر با مالیات</th>
                            <th style="padding: 10px 8px; border: 1px solid #334155; width: 6%;">حذف</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $idx => $it): 
                            $it_qty = max(1, (float)($it['qty'] ?? 1));
                            $it_price = max(0, (float)($it['price'] ?? ($it['unit_price'] ?? 0)));
                            $it_tax_pct = (float)($it['tax_percent'] ?? 0);
                            $it_subtotal = $it_qty * $it_price;
                            $it_tax_amt = $it_subtotal * ($it_tax_pct / 100);
                            $it_total = $it_subtotal + $it_tax_amt;
                        ?>
                            <tr>
                                <td style="padding: 6px; border: 1px solid #cbd5e1;">
                                    <input type="text" name="items[<?php echo $idx; ?>][title]" value="<?php echo esc_attr($it['title'] ?? ''); ?>" placeholder="عنوان کالا یا خدمات..." style="width: 100%;" required>
                                </td>
                                <td style="padding: 6px; border: 1px solid #cbd5e1;">
                                    <input type="text" name="items[<?php echo $idx; ?>][sku]" value="<?php echo esc_attr($it['sku'] ?? ''); ?>" placeholder="اختیاری (مثال: PRD-101)" class="item-sku" style="width: 100%; text-align: center;">
                                </td>
                                <td style="padding: 6px; border: 1px solid #cbd5e1;">
                                    <input type="number" name="items[<?php echo $idx; ?>][qty]" value="<?php echo esc_attr($it_qty); ?>" min="1" step="any" class="item-qty" style="width: 100%; text-align: center;">
                                </td>
                                <td style="padding: 6px; border: 1px solid #cbd5e1;">
                                    <input type="number" name="items[<?php echo $idx; ?>][price]" value="<?php echo esc_attr($it_price); ?>" min="0" step="any" class="item-price" style="width: 100%; text-align: center;">
                                </td>
                                <td style="padding: 6px; border: 1px solid #cbd5e1; text-align: center;">
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                                        <input type="number" name="items[<?php echo $idx; ?>][tax_percent]" value="<?php echo esc_attr($it_tax_pct); ?>" min="0" max="100" step="0.5" class="item-tax-pct" style="width: 65px; text-align: center;">
                                        <span>٪</span>
                                    </div>
                                    <input type="hidden" name="items[<?php echo $idx; ?>][tax_amount]" class="item-tax-amount" value="<?php echo esc_attr($it_tax_amt); ?>">
                                </td>
                                <td style="padding: 6px; border: 1px solid #cbd5e1; text-align: center; font-weight: bold;">
                                    <span class="item-row-total"><?php echo number_format($it_total); ?></span>
                                </td>
                                <td style="padding: 6px; border: 1px solid #cbd5e1; text-align: center;">
                                    <button type="button" class="button remove-row-btn" style="color: #dc2626;">✖</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="margin-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                    <button type="button" id="add_item_row_btn" class="button button-secondary">+ افزودن سطر کالا / خدمت جدید</button>
                    <div style="font-size: 11px; color: #475569;">
                        درصدهای رایج مالیات: <span style="cursor: pointer; text-decoration: underline; color: #0284c7;" onclick="jQuery('.item-tax-pct').val(10).trigger('input');">۱۰٪ (قانون جدید)</span> | <span style="cursor: pointer; text-decoration: underline; color: #0284c7;" onclick="jQuery('.item-tax-pct').val(9).trigger('input');">۹٪ (سابق)</span> | <span style="cursor: pointer; text-decoration: underline; color: #0284c7;" onclick="jQuery('.item-tax-pct').val(0).trigger('input');">۰٪ (معاف)</span>
                    </div>
                </div>
            </div>

            <!-- Totals and Notes -->
            <div style="background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #cbd5e1; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                <h3 style="color: #0f172a; margin: 0 0 12px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-calculator" style="color: #0f766e;"></span>
                    <span>هزینه‌های جانبی و جمع کل نهایی</span>
                </h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px;">
                    <div>
                        <label><strong>جمع اقلام (بدون مالیات):</strong></label><br>
                        <input type="text" id="summary_subtotal" value="0" class="regular-text" style="width: 100%; font-weight: bold; background: #f8fafc;" readonly>
                    </div>
                    <div>
                        <label><strong>تخفیف کلی:</strong></label><br>
                        <input type="number" name="discount" id="summary_discount" value="<?php echo esc_attr($totals['discount'] ?? 0); ?>" min="0" class="regular-text" style="width: 100%;">
                    </div>
                    <div>
                        <label><strong>هزینه ارسال / حمل:</strong></label><br>
                        <input type="number" name="shipping" id="summary_shipping" value="<?php echo esc_attr($totals['shipping'] ?? 0); ?>" min="0" class="regular-text" style="width: 100%;">
                    </div>
                    <div>
                        <label><strong>مجموع مالیات بر ارزش افزوده:</strong></label><br>
                        <input type="number" name="tax" id="summary_tax" value="<?php echo esc_attr($totals['tax'] ?? 0); ?>" min="0" class="regular-text" style="width: 100%; font-weight: bold;">
                    </div>
                </div>

                <div style="margin-top: 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 18px; display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 14px; color: #166534; font-weight: bold;">
                        مبلغ قابل پرداخت فاکتور:
                    </div>
                    <div style="font-size: 20px; font-weight: 900; color: #15803d;">
                        <span id="summary_grand_total">۰</span>
                        <span style="font-size: 13px; font-weight: normal;"><?php echo esc_html($default_currency); ?></span>
                    </div>
                </div>

                <div style="margin-top: 14px;">
                    <label><strong>توضیحات یا شرایط فاکتور:</strong></label><br>
                    <textarea name="note" rows="2" style="width: 100%;" placeholder="توضیحات یا یادداشت دلخواه جهت درج در انتهای فاکتور..."><?php echo esc_textarea($edit_data['note'] ?? ($edit_data['customer_note'] ?? '')); ?></textarea>
                </div>
            </div>

            <!-- Submit Button -->
            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 30px;">
                <button type="submit" class="button button-primary button-hero" style="background: #0f766e; border-color: #0f766e; padding: 8px 24px;">
                    🖨️ <?php echo $is_edit ? 'ذخیره تغییرات و چاپ فاکتور' : 'ذخیره در آرشیو و پیش‌نمایش چاپ فاکتور'; ?>
                </button>
                <span style="color: #64748b; font-size: 11.5px;">فاکتور به صورت خودکار با فرمت سبک در پوشه <code>wp-content/uploads/woo-factor/manual-invoices/</code> ذخیره می‌شود.</span>
            </div>
        </form>

        <!-- Saved Invoices Archive Section -->
        <div id="saved-invoices-archive" style="background: #fff; padding: 22px; border-radius: 10px; border: 1px solid #cbd5e1; box-shadow: 0 2px 8px rgba(0,0,0,0.02); margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px;">
                <h2 style="color: #0f172a; margin: 0; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-archive" style="color: #0284c7;"></span>
                    <span>آرشیو فاکتورهای دستی ذخیره‌شده</span>
                    <span style="background: #0284c7; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 11px;"><?php echo count($saved_invoices); ?> فاکتور</span>
                </h2>
                <span style="font-size: 11px; color: #64748b;">برای ویرایش یا چاپ مجدد هر فاکتور، از دکمه‌های روبروی آن استفاده کنید.</span>
            </div>

            <?php if (empty($saved_invoices)): ?>
                <div style="text-align: center; padding: 30px; color: #94a3b8; font-size: 13px;">
                    هنوز فاکتور دستی ذخیره نشده است. با پر کردن فرم بالا و صدور فاکتور، اولین فاکتور در این لیست ذخیره خواهد شد.
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">
                    <thead>
                        <tr>
                            <th style="width: 14%;">شماره فاکتور</th>
                            <th style="width: 18%;">نام خریدار</th>
                            <th style="width: 13%;">شماره تماس</th>
                            <th style="width: 12%;">تاریخ صدور</th>
                            <th style="width: 13%; text-align: center;">وضعیت تسویه</th>
                            <th style="width: 7%; text-align: center;">اقلام</th>
                            <th style="width: 11%;">مبلغ کل</th>
                            <th style="width: 12%; text-align: center;">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($saved_invoices as $inv): 
                            $view_url = add_query_arg([
                                'action'   => 'woo_factor_view_saved_manual_invoice',
                                'inv'      => $inv['invoice_number'],
                                '_wpnonce' => wp_create_nonce('woo_factor_view_manual_' . $inv['invoice_number'])
                            ], admin_url('admin-ajax.php'));

                            $edit_url = add_query_arg([
                                'page' => 'woo-factor-manual-invoice',
                                'edit' => $inv['invoice_number']
                            ], admin_url('admin.php'));

                            $delete_url = wp_nonce_url(
                                add_query_arg([
                                    'page'   => 'woo-factor-manual-invoice',
                                    'action' => 'delete',
                                    'inv'    => $inv['invoice_number']
                                ], admin_url('admin.php')),
                                'woo_factor_del_manual_' . $inv['invoice_number']
                            );
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($inv['invoice_number']); ?></strong>
                                    <div style="font-size: 10px; color: #64748b;">قالب: <?php echo esc_html($inv['template']); ?></div>
                                </td>
                                <td><strong><?php echo esc_html($inv['buyer_name']); ?></strong></td>
                                <td><?php echo woo_factor_fa_digits($inv['buyer_phone']); ?></td>
                                <td><?php echo woo_factor_fa_digits($inv['jalali_date']); ?></td>
                                <td style="text-align: center;">
                                    <?php 
                                    $p_status = $inv['payment_status'] ?? 'paid';
                                    if ($p_status === 'paid') {
                                        echo '<span style="background: #dcfce7; color: #15803d; padding: 2px 7px; border-radius: 4px; font-size: 10.5px; font-weight: bold;">✔ تسویه شده</span>';
                                    } elseif ($p_status === 'unpaid') {
                                        echo '<span style="background: #fef3c7; color: #b45309; padding: 2px 7px; border-radius: 4px; font-size: 10.5px; font-weight: bold;">⏳ تسویه نشده</span>';
                                    } elseif ($p_status === 'proforma') {
                                        echo '<span style="background: #e0f2fe; color: #0369a1; padding: 2px 7px; border-radius: 4px; font-size: 10.5px; font-weight: bold;">📄 پیش‌فاکتور</span>';
                                    } else {
                                        echo '<span style="background: #f1f5f9; color: #475569; padding: 2px 7px; border-radius: 4px; font-size: 10.5px; font-weight: bold;">' . esc_html($inv['status_name']) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td style="text-align: center;"><?php echo woo_factor_fa_digits($inv['items_count']); ?> قلم</td>
                                <td><strong><?php echo number_format($inv['grand_total']); ?></strong> <small><?php echo esc_html($inv['currency']); ?></small></td>
                                <td style="text-align: center;">
                                    <a href="<?php echo esc_url($view_url); ?>" target="_blank" class="button button-small" title="مشاهده و چاپ">🖨️ چاپ</a>
                                    <a href="<?php echo esc_url($edit_url); ?>" class="button button-small" title="ویرایش اطلاعات">✏️ ویرایش</a>
                                    <a href="<?php echo esc_url($delete_url); ?>" class="button button-small" style="color: #dc2626;" onclick="return confirm('آیا از حذف این فاکتور مطمئن هستید؟');" title="حذف">🗑️</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Live Dynamic Calculation Script -->
    <script>
    jQuery(document).ready(function($){
        var rowIdx = <?php echo count($items); ?>;

        function recalcTotals() {
            var subtotal = 0;
            var totalTax = 0;

            $('#woo_factor_items_table tbody tr').each(function(){
                var tr = $(this);
                var qty = parseFloat(tr.find('.item-qty').val()) || 0;
                var price = parseFloat(tr.find('.item-price').val()) || 0;
                var taxPct = parseFloat(tr.find('.item-tax-pct').val()) || 0;

                var rowSubtotal = qty * price;
                var rowTax = rowSubtotal * (taxPct / 100);
                var rowTotal = rowSubtotal + rowTax;

                tr.find('.item-tax-amount').val(rowTax);
                tr.find('.item-row-total').text(Math.round(rowTotal).toLocaleString('fa-IR'));

                subtotal += rowSubtotal;
                totalTax += rowTax;
            });

            $('#summary_subtotal').val(Math.round(subtotal).toLocaleString('fa-IR'));

            // Auto-update summary tax field if not manually overridden
            $('#summary_tax').val(Math.round(totalTax));

            var discount = parseFloat($('#summary_discount').val()) || 0;
            var shipping = parseFloat($('#summary_shipping').val()) || 0;
            var tax = parseFloat($('#summary_tax').val()) || 0;

            var grandTotal = Math.max(0, subtotal - discount + shipping + tax);
            $('#summary_grand_total').text(Math.round(grandTotal).toLocaleString('fa-IR'));
        }

        // Trigger on load
        recalcTotals();

        // Listen for input changes in items
        $(document).on('input change', '.item-qty, .item-price, .item-tax-pct', function(){
            recalcTotals();
        });

        // Listen for input changes in global discounts/shipping/tax
        $(document).on('input change', '#summary_discount, #summary_shipping, #summary_tax', function(){
            var subtotal = 0;
            $('#woo_factor_items_table tbody tr').each(function(){
                var qty = parseFloat($(this).find('.item-qty').val()) || 0;
                var price = parseFloat($(this).find('.item-price').val()) || 0;
                subtotal += (qty * price);
            });
            var discount = parseFloat($('#summary_discount').val()) || 0;
            var shipping = parseFloat($('#summary_shipping').val()) || 0;
            var tax = parseFloat($('#summary_tax').val()) || 0;
            var grandTotal = Math.max(0, subtotal - discount + shipping + tax);
            $('#summary_grand_total').text(Math.round(grandTotal).toLocaleString('fa-IR'));
        });

        // Add Row
        $('#add_item_row_btn').on('click', function(){
            var rowHtml = '<tr>' +
                '<td style="padding: 6px; border: 1px solid #cbd5e1;"><input type="text" name="items['+rowIdx+'][title]" placeholder="عنوان کالا یا خدمات..." style="width: 100%;" required></td>' +
                '<td style="padding: 6px; border: 1px solid #cbd5e1;"><input type="text" name="items['+rowIdx+'][sku]" placeholder="اختیاری" class="item-sku" style="width: 100%; text-align: center;"></td>' +
                '<td style="padding: 6px; border: 1px solid #cbd5e1;"><input type="number" name="items['+rowIdx+'][qty]" value="1" min="1" step="any" class="item-qty" style="width: 100%; text-align: center;"></td>' +
                '<td style="padding: 6px; border: 1px solid #cbd5e1;"><input type="number" name="items['+rowIdx+'][price]" value="0" min="0" step="any" class="item-price" style="width: 100%; text-align: center;"></td>' +
                '<td style="padding: 6px; border: 1px solid #cbd5e1; text-align: center;">' +
                    '<div style="display: flex; align-items: center; justify-content: center; gap: 4px;">' +
                        '<input type="number" name="items['+rowIdx+'][tax_percent]" value="0" min="0" max="100" step="0.5" class="item-tax-pct" style="width: 65px; text-align: center;">' +
                        '<span>٪</span>' +
                    '</div>' +
                    '<input type="hidden" name="items['+rowIdx+'][tax_amount]" class="item-tax-amount" value="0">' +
                '</td>' +
                '<td style="padding: 6px; border: 1px solid #cbd5e1; text-align: center; font-weight: bold;"><span class="item-row-total">۰</span></td>' +
                '<td style="padding: 6px; border: 1px solid #cbd5e1; text-align: center;"><button type="button" class="button remove-row-btn" style="color: #dc2626;">✖</button></td>' +
            '</tr>';
            $('#woo_factor_items_table tbody').append(rowHtml);
            rowIdx++;
            recalcTotals();
        });

        // Remove Row
        $(document).on('click', '.remove-row-btn', function(){
            if ($('#woo_factor_items_table tbody tr').length > 1) {
                $(this).closest('tr').remove();
                recalcTotals();
            } else {
                alert('حداقل یک سطر کالا باید در فاکتور وجود داشته باشد.');
            }
        });

        // Watermark Mode Toggle
        $('#manual_watermark_mode').on('change', function(){
            if ($(this).val() === 'custom') {
                $('#manual_custom_watermark_wrap').slideDown(150);
            } else {
                $('#manual_custom_watermark_wrap').slideUp(150);
            }
        });
    });
    </script>
    <?php
}

/**
 * Handle Manual Invoice Generation AJAX (saves JSON & outputs printable HTML).
 */
add_action('wp_ajax_woo_factor_generate_manual_invoice', function () {
    if (!current_user_can('manage_woocommerce')) {
        wp_die('دسترسی غیرمجاز.');
    }
    check_admin_referer('woo_factor_manual_nonce');

    $opts = woo_factor_options();
    $template = sanitize_text_field($_POST['template'] ?? 'classic');
    $currency = sanitize_text_field($_POST['currency'] ?? 'تومان');
    $inv_num  = sanitize_text_field(wp_unslash($_POST['invoice_number'] ?? ('MAN-' . rand(10000, 99999))));
    $inv_num  = preg_replace('/[^A-Za-z0-9\\-_]/', '', $inv_num);
    if ($inv_num === '') {
        $inv_num = 'MAN-' . wp_rand(10000, 99999);
    }

    $seller = [
        'name'            => !empty($opts['shop_name']) ? $opts['shop_name'] : get_bloginfo('name'),
        'phone'           => $opts['shop_phone'] ?? '',
        'email'           => !empty($opts['shop_email']) ? $opts['shop_email'] : get_bloginfo('admin_email'),
        'national_id'     => $opts['shop_national_id'] ?? '',
        'economic_id'     => $opts['shop_economic_code'] ?? '',
        'economic_code'   => $opts['shop_economic_code'] ?? '',
        'registration_no' => $opts['shop_registration_no'] ?? '',
        'postal_code'     => $opts['shop_postal_code'] ?? '',
        'address'         => $opts['shop_address'] ?? '',
        'website'         => home_url(),
        'logo_url'        => woo_factor_get_logo_url(),
        'stamp_url'       => woo_factor_get_stamp_url(),
    ];

    $buyer = [
        'name'              => sanitize_text_field($_POST['buyer_name'] ?? 'مشتری محترم'),
        'company'           => '',
        'national_id'       => sanitize_text_field($_POST['buyer_national_id'] ?? ''),
        'national_code'     => sanitize_text_field($_POST['buyer_national_id'] ?? ''),
        'economic_id'       => '',
        'registration_no'   => '',
        'phone'             => sanitize_text_field($_POST['buyer_phone'] ?? ''),
        'email'             => '',
        'postcode'          => '',
        'full_address'      => sanitize_textarea_field($_POST['buyer_address'] ?? ''),
        'address'           => sanitize_textarea_field($_POST['buyer_address'] ?? ''),
        'shipping_address'  => sanitize_textarea_field($_POST['buyer_address'] ?? ''),
        'shipping_name'     => sanitize_text_field($_POST['buyer_name'] ?? 'مشتری محترم'),
        'shipping_phone'    => sanitize_text_field($_POST['buyer_phone'] ?? ''),
        'shipping_postcode' => '',
        'customer_type'     => !empty($_POST['buyer_national_id']) && strlen($_POST['buyer_national_id']) === 11 ? 'legal' : 'natural',
    ];

    $items_raw = $_POST['items'] ?? [];
    $items = [];
    $subtotal = 0;
    $calculated_tax = 0;
    $idx = 1;

    foreach ($items_raw as $it) {
        $title = sanitize_text_field($it['title'] ?? '');
        if (empty($title)) continue;
        $sku = sanitize_text_field($it['sku'] ?? '');
        $qty = max(1, (float)($it['qty'] ?? 1));
        $price = max(0, (float)($it['price'] ?? 0));
        $tax_percent = max(0, (float)($it['tax_percent'] ?? 0));

        $item_subtotal = $qty * $price;
        $item_tax = $item_subtotal * ($tax_percent / 100);
        $item_total = $item_subtotal + $item_tax;

        $subtotal += $item_subtotal;
        $calculated_tax += $item_tax;

        $items[] = [
            'index'         => $idx++,
            'id'            => 0,
            'sku'           => $sku,
            'title'         => $title,
            'qty'           => $qty,
            'price'         => $price,
            'unit_price'    => $price,
            'tax_percent'   => $tax_percent,
            'tax'           => $item_tax,
            'subtotal'      => $item_subtotal,
            'discount'      => 0,
            'total'         => $item_total,
            'thumbnail_url' => '',
            'meta'          => '',
        ];
    }

    $discount = max(0, (float)($_POST['discount'] ?? 0));
    $shipping = max(0, (float)($_POST['shipping'] ?? 0));
    $tax_input = isset($_POST['tax']) ? (float)$_POST['tax'] : $calculated_tax;
    $final_tax = max(0, $tax_input);
    $grand = max(0, $subtotal - $discount + $shipping + $final_tax);

    $payment_status   = sanitize_text_field($_POST['payment_status'] ?? 'paid');
    $watermark_mode   = sanitize_text_field($_POST['watermark_mode'] ?? 'auto');
    $custom_watermark = sanitize_text_field($_POST['custom_watermark'] ?? '');

    switch ($payment_status) {
        case 'unpaid':
            $status_key           = 'pending';
            $status_name          = 'تسویه نشده';
            $default_wm           = 'تسویه نشده';
            $payment_method_label = 'در انتظار تسویه / پرداخت نشده';
            break;
        case 'proforma':
            $status_key           = 'proforma';
            $status_name          = 'پیش‌فاکتور';
            $default_wm           = 'پیش‌فاکتور';
            $payment_method_label = 'پیش‌فاکتور (غیرقطعی)';
            break;
        case 'none':
            $status_key           = 'issued';
            $status_name          = 'صادر شد';
            $default_wm           = '';
            $payment_method_label = 'تسویه مستقیم';
            break;
        case 'paid':
        default:
            $status_key           = 'completed';
            $status_name          = 'تسویه شده';
            $default_wm           = 'پرداخت شد';
            $payment_method_label = 'نقدی / تسویه کامل';
            break;
    }

    if ($watermark_mode === 'none') {
        $final_watermark = '';
    } elseif ($watermark_mode === 'custom') {
        $final_watermark = $custom_watermark;
    } else {
        $final_watermark = $default_wm;
    }

    $totals = [
        'subtotal'          => $subtotal,
        'discount'          => $discount,
        'shipping'          => $shipping,
        'shipping_method'   => $shipping > 0 ? 'پیک / پست' : 'تحویل حضوری',
        'tax'               => $final_tax,
        'grand_total'       => $grand,
        'grand_total_words' => woo_factor_number_to_words($grand),
        'currency'          => $currency,
        'payment_method'    => $payment_method_label,
        'transaction_id'    => '',
    ];

    $customer_note = sanitize_textarea_field($_POST['note'] ?? '');

    $data = [
        'type'                 => 'manual',
        'order_id'             => 0,
        'order_number'         => $inv_num,
        'invoice_number'       => $inv_num,
        'jalali_date'          => woo_factor_jdate(current_time('timestamp'), false),
        'jalali_time'          => woo_factor_jdate(current_time('timestamp'), true),
        'status'               => $status_key,
        'status_name'          => $status_name,
        'payment_status'       => $payment_status,
        'watermark_mode'       => $watermark_mode,
        'custom_watermark'     => $custom_watermark,
        'seller'               => $seller,
        'buyer'                => $buyer,
        'items'                => $items,
        'totals'               => $totals,
        'barcode_svg'          => Woo_Factor_Barcode_128::get_svg($inv_num, 40, 1.5),
        'customer_note'        => $customer_note,
        'note'                 => $customer_note,
        'footer_note'          => $opts['footer_note'] ?? 'از خرید شما سپاسگزاریم.',
        'invoice_terms'        => $opts['invoice_terms'] ?? '',
        'signature_stamp'      => $opts['signature_stamp'] ?? 'مهر و امضای فروشگاه',
        'stamp_url'            => $seller['stamp_url'],
        'color'                => woo_factor_normalize_color($opts['color'] ?? ''),
        'watermark_text'       => $final_watermark,
        'template'             => $template,
        'currency'             => $currency,
        'created_at'           => current_time('timestamp'),
        'show_barcode'         => ($opts['show_barcode'] ?? 'yes') === 'yes',
        'show_product_image'   => false,
        'show_sku'             => true,
        'show_tax_column'      => ($opts['show_tax_column'] ?? 'yes') === 'yes',
        'show_discount_column' => ($opts['show_discount_column'] ?? 'yes') === 'yes',
        'show_watermark'       => !empty($final_watermark),
        'show_signature'       => ($opts['show_signature'] ?? 'yes') === 'yes',
    ];

    // Save to lightweight JSON file in dedicated manual-invoices folder
    woo_factor_save_manual_invoice_file($inv_num, $data);

    // Render Invoice HTML
    echo Woo_Factor_Renderer::render_html($data, $template);
    exit;
});

/**
 * Handle Viewing Saved Manual Invoice from Archive.
 */
add_action('wp_ajax_woo_factor_view_saved_manual_invoice', function () {
    if (!current_user_can('manage_woocommerce')) {
        wp_die('دسترسی غیرمجاز.');
    }

    $inv_num = sanitize_text_field($_GET['inv'] ?? '');
    check_admin_referer('woo_factor_view_manual_' . $inv_num);

    $data = woo_factor_get_manual_invoice_file($inv_num);
    if (!$data) {
        wp_die('فاکتور مورد نظر در آرشیو یافت نشد.');
    }

    $template = $data['template'] ?? 'classic';

    // Ensure barcode SVG is generated
    if (empty($data['barcode_svg'])) {
        $data['barcode_svg'] = Woo_Factor_Barcode_128::get_svg($inv_num, 40, 1.5);
    }

    echo Woo_Factor_Renderer::render_html($data, $template);
    exit;
});
