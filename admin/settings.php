<?php
/**
 * Admin Settings Page for WooFactor Invoice (فاکتورساز حرفه‌ای ووفاکتور)
 */
defined('ABSPATH') || exit;

add_action('admin_menu', function () {
    add_menu_page(
        __('فاکتورساز حرفه‌ای ووفاکتور', 'woo-factor'),
        __('فاکتورساز ووفاکتور', 'woo-factor'),
        'manage_woocommerce',
        'woo-factor',
        'woo_factor_render_settings_page',
        'dashicons-media-spreadsheet',
        56
    );

    add_submenu_page(
        'woo-factor',
        __('تنظیمات فاکتور', 'woo-factor'),
        __('تنظیمات فاکتور', 'woo-factor'),
        'manage_woocommerce',
        'woo-factor',
        'woo_factor_render_settings_page'
    );

    add_submenu_page(
        'woo-factor',
        __('صدور فاکتور دستی', 'woo-factor'),
        __('صدور فاکتور دستی', 'woo-factor'),
        'manage_woocommerce',
        'woo-factor-manual-invoice',
        'woo_factor_render_manual_invoice_page'
    );
});

add_action('admin_init', function () {
    register_setting('woo_factor_settings_group', 'woo_factor_options', [
        'sanitize_callback' => 'woo_factor_sanitize_options',
        'default' => [],
    ]);
});

add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'woo_factor') !== false || strpos($hook, 'woo-factor') !== false) {
        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }
});

function woo_factor_render_settings_page() {
    $opts = woo_factor_options();
    $templates = woo_factor_templates();
    ?>
    <div class="wrap wf-admin-wrap" style="direction: rtl; text-align: right; max-width: 1100px; margin: 20px auto 40px auto; font-family: 'Vazirmatn', Tahoma, sans-serif;">
        
        <!-- Header Panel -->
        <div class="wf-header-card">
            <div class="wf-header-info">
                <div class="wf-logo-icon">🧾</div>
                <div>
                    <h1 class="wf-title">ووفاکتور <span class="wf-badge">نسخه <?php echo esc_html(woo_factor_fa_digits(WOO_FACTOR_VERSION)); ?></span></h1>
                    <p class="wf-subtitle">مدیریت، صدور و شخصی‌سازی فاکتورهای رسمی دارایی، مدرن و رسیدهای حرارتی ووکامرس</p>
                </div>
            </div>
            <div class="wf-header-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=woo-factor-manual-invoice')); ?>" class="button button-secondary">✍️ صدور فاکتور دستی</a>
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=shop_order')); ?>" class="button button-secondary">📦 لیست سفارشات</a>
            </div>
        </div>

        <?php if (isset($_GET['settings-updated'])): ?>
            <div class="notice notice-success is-dismissible" style="margin: 15px 0; border-radius: 8px; border-right-color: #0f766e;"><p>تنظیمات فاکتورساز ووفاکتور با موفقیت ذخیره شد.</p></div>
        <?php endif; ?>

        <form method="post" action="options.php" id="wf_settings_form">
            <?php settings_fields('woo_factor_settings_group'); ?>

            <!-- Navigation Tabs -->
            <div class="wf-tabs-nav">
                <button type="button" class="wf-tab-btn active" data-tab="tab-templates">🎨 ۱. قالب و رنگ‌بندی</button>
                <button type="button" class="wf-tab-btn" data-tab="tab-shop">🏢 ۲. اطلاعات فروشگاه و دارایی</button>
                <button type="button" class="wf-tab-btn" data-tab="tab-checkout">👥 ۳. مشتریان حقیقی / حقوقی</button>
                <button type="button" class="wf-tab-btn" data-tab="tab-sms">📲 ۴. سامانه پیامک هوشمند</button>
                <button type="button" class="wf-tab-btn" data-tab="tab-elements">⚙️ ۵. ستون‌ها، واترمارک و چاپ</button>
            </div>

            <!-- Tab 1: Templates & Styling -->
            <div class="wf-tab-content active" id="tab-templates">
                <div class="wf-card">
                    <h2 class="wf-card-title">انتخاب قالب پیش‌فرض فاکتور</h2>
                    <p class="wf-card-desc">طرح مورد نظر خود را برای چاپ و نمایش فاکتورها انتخاب کنید:</p>

                    <div class="wf-templates-grid">
                        <?php foreach ($templates as $key => $tpl): 
                            $is_checked = ($opts['template'] === $key);
                        ?>
                            <label class="wf-template-card <?php echo $is_checked ? 'active' : ''; ?>">
                                <div class="wf-tpl-radio">
                                    <input type="radio" name="woo_factor_options[template]" value="<?php echo esc_attr($key); ?>" <?php checked($opts['template'], $key); ?>>
                                    <strong><?php echo esc_html($tpl['name']); ?></strong>
                                </div>
                                <p class="wf-tpl-desc"><?php echo esc_html($tpl['desc']); ?></p>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="wf-field-group" style="margin-top: 24px;">
                        <label class="wf-label"><strong>رنگ سازمانی و تم شاخص فاکتورها:</strong></label>
                        <p class="wf-help">این رنگ در هدرها، خطوط تفکیکی و بج‌های فاکتور به زیبایی اعمال می‌شود.</p>
                        <input type="text" name="woo_factor_options[color]" value="<?php echo esc_attr($opts['color'] ?? '#0f766e'); ?>" class="woo_factor-color-field" data-default-color="#0f766e">
                    </div>
                </div>

                <div class="wf-card">
                    <h2 class="wf-card-title">لوگو و مهر اختصاصی فروشگاه</h2>
                    <div class="wf-two-col">
                        <div class="wf-col-box">
                            <label class="wf-label"><strong>لوگوی اختصاصی فروشگاه:</strong></label>
                            <input type="hidden" name="woo_factor_options[logo_id]" id="woo_factor_logo_id" value="<?php echo esc_attr($opts['logo_id']); ?>">
                            <div id="woo_factor_logo_preview" class="wf-media-preview">
                                <?php if (!empty($opts['logo_id'])): 
                                    $img = wp_get_attachment_image_src($opts['logo_id'], 'medium');
                                    if ($img): ?>
                                        <img src="<?php echo esc_url($img[0]); ?>">
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="wf-btn-group">
                                <button type="button" class="button" id="woo_factor_upload_logo_btn">انتخاب / تغییر لوگو</button>
                                <button type="button" class="button button-link-delete" id="woo_factor_remove_logo_btn" style="<?php echo empty($opts['logo_id']) ? 'display:none;' : ''; ?>">حذف</button>
                            </div>
                        </div>

                        <div class="wf-col-box">
                            <label class="wf-label"><strong>تصویر مهر و امضای رسمی (PNG شفاف):</strong></label>
                            <input type="hidden" name="woo_factor_options[stamp_image_id]" id="woo_factor_stamp_id" value="<?php echo esc_attr($opts['stamp_image_id'] ?? ''); ?>">
                            <div id="woo_factor_stamp_preview" class="wf-media-preview wf-stamp-preview">
                                <?php if (!empty($opts['stamp_image_id'])): 
                                    $stamp_img = wp_get_attachment_image_src($opts['stamp_image_id'], 'medium');
                                    if ($stamp_img): ?>
                                        <img src="<?php echo esc_url($stamp_img[0]); ?>">
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="wf-btn-group">
                                <button type="button" class="button" id="woo_factor_upload_stamp_btn">انتخاب تصویر مهر و امضا</button>
                                <button type="button" class="button button-link-delete" id="woo_factor_remove_stamp_btn" style="<?php echo empty($opts['stamp_image_id']) ? 'display:none;' : ''; ?>">حذف</button>
                            </div>
                            <p class="wf-help">برای بهترین خروجی، از تصویر PNG بدون پس‌زمینه (Transparent) استفاده کنید.</p>
                        </div>
                    </div>

                    <div class="wf-field-group" style="margin-top: 16px;">
                        <label class="wf-label">عنوان کادر مهر و امضا:</label>
                        <input name="woo_factor_options[signature_stamp]" type="text" value="<?php echo esc_attr($opts['signature_stamp'] ?? 'مهر و امضای فروشگاه'); ?>" class="regular-text">
                    </div>
                </div>
            </div>

            <!-- Tab 2: Shop & Legal Info -->
            <div class="wf-tab-content" id="tab-shop">
                <div class="wf-card">
                    <h2 class="wf-card-title">مشخصات هویتی و ثبتی فروشنده (مودی مالیاتی)</h2>
                    <p class="wf-card-desc">نام نمایشی، نشانی و اطلاعات تماس فرستنده در فاکتور و برچسب پستی استفاده می‌شود. مقادیر اولیه از تنظیمات سایت و ووکامرس گرفته می‌شوند؛ می‌توانید همه را ویرایش کنید. مشخصات حقیقی یا حقوقی فروشنده مستقل از فیلدهای مشتری است.</p>

                    <div class="wf-grid-2">
                        <div class="wf-input-item">
                            <label class="wf-label">نام فروشگاه / نام شرکت رسمی:</label>
                            <input name="woo_factor_options[shop_name]" type="text" value="<?php echo esc_attr($opts['shop_name']); ?>" class="large-text">
                        </div>
                        <div class="wf-input-item">
                            <label class="wf-label">کد اقتصادی (۱۲ یا ۱۴ رقمی):</label>
                            <input name="woo_factor_options[shop_economic_code]" type="text" value="<?php echo esc_attr($opts['shop_economic_code'] ?? ''); ?>" class="large-text" placeholder="مثال: ۴۱۱۴۵۸۹۲۳۱">
                        </div>
                        <div class="wf-input-item">
                            <label class="wf-label">شناسه ملی / کد ملی:</label>
                            <input name="woo_factor_options[shop_national_id]" type="text" value="<?php echo esc_attr($opts['shop_national_id'] ?? ''); ?>" class="large-text" placeholder="مثال: ۱۰۱۰۳۴۵۶۷۸۹">
                        </div>
                        <div class="wf-input-item">
                            <label class="wf-label">شماره ثبت / مجوز کسب:</label>
                            <input name="woo_factor_options[shop_registration_no]" type="text" value="<?php echo esc_attr($opts['shop_registration_no'] ?? ''); ?>" class="large-text" placeholder="شماره ثبت شرکت یا پروانه کسب">
                        </div>
                        <div class="wf-input-item">
                            <label class="wf-label">کد پستی ۱۰ رقمی فروشگاه:</label>
                            <input name="woo_factor_options[shop_postal_code]" type="text" value="<?php echo esc_attr($opts['shop_postal_code'] ?? ''); ?>" class="large-text" placeholder="مثال: ۱۹۸۵۷۱۲۳۴۵">
                        </div>
                        <div class="wf-input-item">
                            <label class="wf-label">تلفن ثابت پشتیبانی:</label>
                            <input name="woo_factor_options[shop_phone]" type="text" value="<?php echo esc_attr($opts['shop_phone']); ?>" class="large-text" placeholder="مثال: ۰۲۱-۸۸۹۹۰۰۱۱">
                        </div>
                        <div class="wf-input-item">
                            <label class="wf-label">شماره موبایل فرستنده (برچسب پستی):</label>
                            <input name="woo_factor_options[sender_mobile]" type="text" value="<?php echo esc_attr($opts['sender_mobile'] ?? ''); ?>" class="large-text" placeholder="مثال: ۰۹۱۲۳۴۵۶۷۸۹">
                            <p class="wf-help">برای تماس مامور پست یا پیک در زمان ارسال مرسوله.</p>
                        </div>
                        <div class="wf-input-item">
                            <label class="wf-label">ایمیل رسمی فروشگاه:</label>
                            <input name="woo_factor_options[shop_email]" type="email" value="<?php echo esc_attr($opts['shop_email']); ?>" class="large-text">
                        </div>
                    </div>

                    <div class="wf-field-group" style="margin-top: 15px;">
                        <label class="wf-label">نشانی کامل پستی فروشگاه:</label>
                        <textarea name="woo_factor_options[shop_address]" rows="3" class="large-text" placeholder="استان، شهر، خیابان، پلاک، طبقه، واحد"><?php echo esc_textarea($opts['shop_address']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Checkout & Entity Type -->
            <div class="wf-tab-content" id="tab-checkout">
                <div class="wf-card">
                    <h2 class="wf-card-title">تنظیمات تسویه حساب و فیلدهای مشتری حقوقی / حقیقی</h2>
                    <p class="wf-card-desc">اختیاری و به‌صورت پیش‌فرض خاموش است. در حالت خاموش، ووفاکتور هیچ فیلدی به تسویه حساب اضافه نمی‌کند و اطلاعات سفارش‌های قبلی حفظ می‌شود.</p>

                    <div class="wf-switch-item">
                        <label class="wf-switch">
                            <input aria-label="فعال‌سازی فیلدهای مشتری حقوقی" name="woo_factor_options[enable_checkout_customer_type]" type="checkbox" value="yes" <?php checked($opts['enable_checkout_customer_type'] ?? 'no', 'yes'); ?>>
                            <span class="wf-slider"></span>
                        </label>
                        <div>
                            <strong>فعال‌سازی انتخاب هوشمند مشتری حقیقی یا حقوقی (شرکت) در صفحه تسویه حساب</strong>
                            <p class="wf-help">در تسویه حساب کلاسیک، نوع مشتری، کد ملی / شناسه ملی و کد اقتصادی نمایش داده می‌شود. نام شرکت از فیلد استاندارد ووکامرس خوانده می‌شود.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 4: SMS Notifications -->
            <div class="wf-tab-content" id="tab-sms">
                <div class="wf-card">
                    <h2 class="wf-card-title">ارسال پیامک هوشمند فاکتور (الگو محور / خدماتی)</h2>
                    <p class="wf-card-desc">اتصال مستقیم به برترین وب‌سرویس‌های پیامکی کشور با ارسال فوری از خطوط خدماتی (عبور از بلک‌لیست):</p>

                    <div class="wf-switch-item" style="margin-bottom: 20px;">
                        <label class="wf-switch">
                            <input name="woo_factor_options[sms_enabled]" type="checkbox" value="yes" <?php checked($opts['sms_enabled'] ?? 'no', 'yes'); ?>>
                            <span class="wf-slider"></span>
                        </label>
                        <div>
                            <strong>ارسال خودکار پیامک حاوی لینک و مشخصات فاکتور به مشتری</strong>
                        </div>
                    </div>

                    <div class="wf-grid-2">
                        <div class="wf-input-item">
                            <label class="wf-label">انتخاب سامانه پیامک:</label>
                            <select name="woo_factor_options[sms_gateway]" class="large-text">
                                <option value="ippanel" <?php selected($opts['sms_gateway'] ?? 'ippanel', 'ippanel'); ?>>فراز اس‌ام‌اس / IPPanel (پترن خدماتی)</option>
                                <option value="kavenegar" <?php selected($opts['sms_gateway'] ?? '', 'kavenegar'); ?>>کاوه‌نگار - Kavenegar (Verify Lookup)</option>
                                <option value="melipayamak" <?php selected($opts['sms_gateway'] ?? '', 'melipayamak'); ?>>ملی‌پیامک - Melipayamak (BaseService)</option>
                                <option value="smsir" <?php selected($opts['sms_gateway'] ?? '', 'smsir'); ?>>SMS.ir (ارسال سریع Verify)</option>
                            </select>
                        </div>
                        <div class="wf-input-item">
                            <label class="wf-label">ارسال در وضعیت سفارش:</label>
                            <select name="woo_factor_options[sms_trigger_status]" class="large-text">
                                <option value="completed" <?php selected($opts['sms_trigger_status'] ?? 'completed', 'completed'); ?>>تکمیل شده (Completed)</option>
                                <option value="processing" <?php selected($opts['sms_trigger_status'] ?? '', 'processing'); ?>>در حال انجام (Processing)</option>
                                <option value="on-hold" <?php selected($opts['sms_trigger_status'] ?? '', 'on-hold'); ?>>در انتظار بررسی (On-Hold)</option>
                            </select>
                        </div>
                        <div class="wf-input-item">
                            <label class="wf-label">کلید API / نام کاربری پنل پیامک:</label>
                            <input name="woo_factor_options[sms_api_key]" type="text" value="<?php echo esc_attr($opts['sms_api_key'] ?? ''); ?>" class="large-text" placeholder="API Key وب‌سرویس">
                        </div>
                        <div class="wf-input-item">
                            <label class="wf-label">شماره خط اختصاصی فرستنده (اختیاری):</label>
                            <input name="woo_factor_options[sms_sender]" type="text" value="<?php echo esc_attr($opts['sms_sender'] ?? ''); ?>" class="large-text" placeholder="مثال: +983000505">
                        </div>
                        <div class="wf-input-item" style="grid-column: span 2;">
                            <label class="wf-label">کد الگو / شناسه پترن تایید شده در سامانه (Pattern Code):</label>
                            <input name="woo_factor_options[sms_pattern]" type="text" value="<?php echo esc_attr($opts['sms_pattern'] ?? ''); ?>" class="large-text" placeholder="مثال: 12345">
                            <p class="wf-help">متغیرهای ارسالی در قالب پترن: <code>order_id</code> (شماره سفارش)، <code>name</code> (نام مشتری)، <code>total</code> (مبلغ) و <code>invoice_url</code> (لینک امن فاکتور).</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 5: Elements, Print & Advanced -->
            <div class="wf-tab-content" id="tab-elements">
                <div class="wf-card">
                    <h2 class="wf-card-title">شخصی‌سازی ستون‌ها و المان‌های چاپی</h2>
                    <p class="wf-card-desc">المان‌های مورد نظر خود را در ردیف‌های فاکتور روشن یا خاموش کنید:</p>

                    <div class="wf-switches-list">
                        <div class="wf-switch-item">
                            <label class="wf-switch">
                                <input name="woo_factor_options[show_barcode]" type="checkbox" value="yes" <?php checked($opts['show_barcode'] ?? 'yes', 'yes'); ?>>
                                <span class="wf-slider"></span>
                            </label>
                            <div><strong>بارکد خطی استاندارد Code128:</strong> درج بارکد شماره سفارش جهت بارکدخوان و انبارداری سریع</div>
                        </div>

                        <div class="wf-switch-item">
                            <label class="wf-switch">
                                <input name="woo_factor_options[show_product_image]" type="checkbox" value="yes" <?php checked($opts['show_product_image'] ?? 'yes', 'yes'); ?>>
                                <span class="wf-slider"></span>
                            </label>
                            <div><strong>نمایش تصویر شاخص کالا:</strong> نمایش بندانگشتی محصول در ردیف‌های جدول اقلام</div>
                        </div>

                        <div class="wf-switch-item">
                            <label class="wf-switch">
                                <input name="woo_factor_options[show_sku]" type="checkbox" value="yes" <?php checked($opts['show_sku'] ?? 'yes', 'yes'); ?>>
                                <span class="wf-slider"></span>
                            </label>
                            <div><strong>کد محصول (SKU):</strong> درج شناسه انبارداری محصول در ردیف فاکتور</div>
                        </div>

                        <div class="wf-switch-item">
                            <label class="wf-switch">
                                <input name="woo_factor_options[show_discount_column]" type="checkbox" value="yes" <?php checked($opts['show_discount_column'] ?? 'yes', 'yes'); ?>>
                                <span class="wf-slider"></span>
                            </label>
                            <div><strong>ستون تخفیف:</strong> تفکیک مبالغ تخفیف در جدول اقلام</div>
                        </div>

                        <div class="wf-switch-item">
                            <label class="wf-switch">
                                <input name="woo_factor_options[show_tax_column]" type="checkbox" value="yes" <?php checked($opts['show_tax_column'] ?? 'yes', 'yes'); ?>>
                                <span class="wf-slider"></span>
                            </label>
                            <div><strong>ستون مالیات و عوارض:</strong> تفکیک ارزش افزوده در جدول اقلام فاکتور</div>
                        </div>

                        <div class="wf-switch-item">
                            <label class="wf-switch">
                                <input name="woo_factor_options[show_watermark]" type="checkbox" value="yes" <?php checked($opts['show_watermark'] ?? 'yes', 'yes'); ?>>
                                <span class="wf-slider"></span>
                            </label>
                            <div><strong>واترمارک وضعیت:</strong> نمایش مهر پس‌زمینه («پرداخت شد»، «در انتظار پرداخت»، «باطل شد»)</div>
                        </div>
                    </div>

                    <div class="wf-grid-2" style="margin-top: 20px;">
                        <div class="wf-input-item">
                            <label class="wf-label">پیشوند شماره فاکتور (اختیاری):</label>
                            <input name="woo_factor_options[invoice_prefix]" type="text" value="<?php echo esc_attr($opts['invoice_prefix'] ?? ''); ?>" class="large-text" placeholder="مثال: INV-">
                        </div>
                        <div class="wf-input-item">
                            <label class="wf-label">متن دلخواه واترمارک (اختیاری):</label>
                            <input name="woo_factor_options[watermark_text]" type="text" value="<?php echo esc_attr($opts['watermark_text'] ?? ''); ?>" class="large-text" placeholder="در صورت خالی بودن خودکار تعیین می‌شود">
                        </div>
                    </div>

                    <div class="wf-field-group" style="margin-top: 15px;">
                        <label class="wf-label">شرایط، گارانتی و تعهدات خرید (انتهای فاکتور):</label>
                        <textarea name="woo_factor_options[invoice_terms]" rows="3" class="large-text" placeholder="متن قوانین و تعهدات پشتیبانی یا مرجوعی کالا..."><?php echo esc_textarea($opts['invoice_terms'] ?? ''); ?></textarea>
                    </div>

                    <div class="wf-field-group" style="margin-top: 15px;">
                        <label class="wf-label">متن تشکر و پاورقی فاکتور:</label>
                        <textarea name="woo_factor_options[footer_note]" rows="2" class="large-text"><?php echo esc_textarea($opts['footer_note']); ?></textarea>
                    </div>
                </div>

                <div class="wf-card">
                    <h2 class="wf-card-title">ادغام با ایمیل و حساب کاربری</h2>
                    <div class="wf-switches-list">
                        <div class="wf-switch-item">
                            <label class="wf-switch">
                                <input name="woo_factor_options[attach_woocommerce]" type="checkbox" value="yes" <?php checked($opts['attach_woocommerce'] ?? 'yes', 'yes'); ?>>
                                <span class="wf-slider"></span>
                            </label>
                            <div><strong>درج دکمه فاکتور در ایمیل‌های ووکامرس:</strong> امکان چاپ و مشاهده آنلاین فاکتور در ایمیل ارسال شده به مشتری</div>
                        </div>
                        <div class="wf-switch-item">
                            <label class="wf-switch">
                                <input name="woo_factor_options[myaccount_page]" type="checkbox" value="yes" <?php checked($opts['myaccount_page'] ?? 'yes', 'yes'); ?>>
                                <span class="wf-slider"></span>
                            </label>
                            <div><strong>نمایش در حساب کاربری خریدار:</strong> اضافه شدن دکمه دریافت فاکتور در صفحه سفارشات کاربر</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sticky Save Bar -->
            <div class="wf-save-bar">
                <div class="wf-save-info">تغییرات خود را با کلیک روی دکمه ذخیره نمایید.</div>
                <button type="submit" class="button button-primary button-hero wf-submit-btn">💾 ذخیره تغییرات فاکتورساز</button>
            </div>
        </form>
    </div>

    <!-- Admin Panel Styles -->
    <style>
    .wf-admin-wrap {
        color: #0f172a;
    }
    .wf-header-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px 28px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }
    .wf-header-info {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .wf-logo-icon {
        font-size: 38px;
        line-height: 1;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        padding: 10px;
        border-radius: 12px;
    }
    .wf-title {
        margin: 0 0 6px 0;
        font-size: 20px;
        font-weight: 900;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .wf-badge {
        font-size: 11px;
        font-weight: bold;
        background: #0f766e;
        color: #ffffff;
        padding: 3px 10px;
        border-radius: 20px;
    }
    .wf-subtitle {
        margin: 0;
        color: #64748b;
        font-size: 12.5px;
    }
    .wf-header-actions {
        display: flex;
        gap: 10px;
    }
    .wf-tabs-nav {
        display: flex;
        gap: 6px;
        background: #e2e8f0;
        padding: 6px;
        border-radius: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .wf-tab-btn {
        background: transparent;
        border: none;
        padding: 10px 18px;
        font-size: 13px;
        font-weight: bold;
        color: #475569;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .wf-tab-btn:hover {
        color: #0f172a;
        background: rgba(255,255,255,0.6);
    }
    .wf-tab-btn.active {
        background: #ffffff;
        color: #0f766e;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    }
    .wf-tab-content {
        display: none;
    }
    .wf-tab-content.active {
        display: block;
    }
    .wf-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px 28px;
        margin-bottom: 20px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    }
    .wf-card-title {
        margin: 0 0 6px 0;
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 10px;
    }
    .wf-card-desc {
        color: #64748b;
        font-size: 12px;
        margin: 0 0 20px 0;
    }
    .wf-templates-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    .wf-template-card {
        border: 2px solid #e2e8f0;
        background: #fafafa;
        border-radius: 10px;
        padding: 16px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .wf-template-card:hover {
        border-color: #cbd5e1;
        background: #ffffff;
    }
    .wf-template-card.active {
        border-color: #0f766e;
        background: #f0fdf4;
    }
    .wf-tpl-radio {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }
    .wf-tpl-desc {
        font-size: 11.5px;
        color: #64748b;
        line-height: 1.5;
        margin: 0;
    }
    .wf-two-col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .wf-col-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 16px;
    }
    .wf-media-preview {
        min-height: 60px;
        margin-bottom: 10px;
    }
    .wf-media-preview img {
        max-height: 75px;
        max-width: 180px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #ffffff;
        padding: 4px;
        display: block;
    }
    .wf-stamp-preview img {
        background: #fff;
        border-style: dashed;
    }
    .wf-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    .wf-input-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .wf-label {
        font-size: 12px;
        font-weight: bold;
        color: #334155;
    }
    .wf-help {
        font-size: 11px;
        color: #64748b;
        margin: 2px 0 0 0;
    }
    .wf-field-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .wf-switches-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .wf-switch-item {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px 16px;
    }
    .wf-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        flex-shrink: 0;
    }
    .wf-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .wf-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        transition: .3s;
        border-radius: 24px;
    }
    .wf-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
    }
    input:checked + .wf-slider {
        background-color: #0f766e;
    }
    input:checked + .wf-slider:before {
        transform: translateX(20px);
    }
    .wf-save-bar {
        position: sticky;
        bottom: 20px;
        background: #0f172a;
        color: #ffffff;
        border-radius: 12px;
        padding: 14px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        z-index: 100;
        margin-top: 24px;
    }
    .wf-save-info {
        font-size: 12.5px;
        color: #cbd5e1;
    }
    .wf-submit-btn {
        background: #0f766e !important;
        border-color: #0f766e !important;
        color: #ffffff !important;
        font-weight: bold !important;
        padding: 8px 24px !important;
        border-radius: 8px !important;
        cursor: pointer;
    }
    .wf-submit-btn:hover {
        background: #115e59 !important;
    }
    </style>

    <script>
    jQuery(document).ready(function($){
        // Tab switching
        $('.wf-tab-btn').on('click', function(){
            var tab_id = $(this).data('tab');
            $('.wf-tab-btn').removeClass('active');
            $(this).addClass('active');
            $('.wf-tab-content').removeClass('active');
            $('#' + tab_id).addClass('active');
        });

        // Template card selection
        $('.wf-template-card input[type="radio"]').on('change', function(){
            $('.wf-template-card').removeClass('active');
            $(this).closest('.wf-template-card').addClass('active');
        });

        // Color picker
        if ($.fn.wpColorPicker) {
            $('.woo_factor-color-field').wpColorPicker();
        }

        // Media uploader for Logo
        var logo_frame;
        $('#woo_factor_upload_logo_btn').on('click', function(e){
            e.preventDefault();
            if (logo_frame) { logo_frame.open(); return; }
            logo_frame = wp.media({
                title: 'انتخاب لوگوی فروشگاه',
                button: { text: 'استفاده به عنوان لوگو' },
                multiple: false
            });
            logo_frame.on('select', function(){
                var att = logo_frame.state().get('selection').first().toJSON();
                $('#woo_factor_logo_id').val(att.id);
                $('#woo_factor_logo_preview').html('<img src="' + att.url + '">');
                $('#woo_factor_remove_logo_btn').show();
            });
            logo_frame.open();
        });

        $('#woo_factor_remove_logo_btn').on('click', function(){
            $('#woo_factor_logo_id').val('');
            $('#woo_factor_logo_preview').html('');
            $(this).hide();
        });

        // Media uploader for Stamp
        var stamp_frame;
        $('#woo_factor_upload_stamp_btn').on('click', function(e){
            e.preventDefault();
            if (stamp_frame) { stamp_frame.open(); return; }
            stamp_frame = wp.media({
                title: 'انتخاب تصویر مهر و امضا (PNG شفاف)',
                button: { text: 'استفاده به عنوان مهر' },
                multiple: false
            });
            stamp_frame.on('select', function(){
                var att = stamp_frame.state().get('selection').first().toJSON();
                $('#woo_factor_stamp_id').val(att.id);
                $('#woo_factor_stamp_preview').html('<img src="' + att.url + '">');
                $('#woo_factor_remove_stamp_btn').show();
            });
            stamp_frame.open();
        });

        $('#woo_factor_remove_stamp_btn').on('click', function(){
            $('#woo_factor_stamp_id').val('');
            $('#woo_factor_stamp_preview').html('');
            $(this).hide();
        });
    });
    </script>
    <?php
}

require_once WOO_FACTOR_DIR . 'admin/manual-invoice.php';
