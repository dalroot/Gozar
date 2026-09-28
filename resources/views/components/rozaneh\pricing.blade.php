<!-- Pricing Section -->
        <section id="pricing" class="py-16 sm:py-24 bg-[var(--color-bg)] border-t-3 border-[var(--color-border)]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-8">
                    <div class="badge badge-primary mx-auto mb-4">
                        <span>💰</span><span>اشتراک‌های دسترسی</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold text-[var(--color-text)] mb-4">
                        قیمت‌گذاری <span class="gradient-text">ساده و مشخص</span>
                    </h2>
                    <p class="text-base sm:text-lg text-[var(--color-text-muted)] max-w-2xl mx-auto font-medium">
                        بدون هزینه‌های پنهان. بهترین دوره و پکیج را متناسب با نیاز خود انتخاب کنید.
                    </p>
                </div>

                <!-- Interactive Duration Toggle -->
                <div class="flex justify-center mb-12">
                    <div class="pricing-period-selector">
                        <button class="pricing-period-btn" data-period="monthly">
                            <span>۱ ماهه</span>
                        </button>
                        <button class="pricing-period-btn active" data-period="quarterly">
                            <span>۳ ماهه</span>
                            <span class="discount-badge">تخفیف ویژه 🔥</span>
                        </button>
                        <button class="pricing-period-btn" data-period="yearly">
                            <span>سالانه</span>
                            <span class="discount-badge secondary">۲۵٪ تخفیف 🌟</span>
                        </button>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-3 gap-6 sm:gap-8 max-w-5xl mx-auto">
                    <!-- Core Plan -> Tabesh -->
                    <div class="pricing-card" id="card-core">
                        <div class="text-center mb-4">
                            <span class="text-5xl">🌱</span>
                            <h3 class="text-2xl font-black text-[var(--color-text)] mt-3 mb-1">روزنه تابش</h3>
                            <p class="text-xs text-[var(--color-text-muted)] font-semibold">مناسب وب‌گردی و امور روزمره</p>
                        </div>

                        <!-- Price box -->
                        <div class="text-center mb-5 py-3 bg-[var(--color-bg-alt)] rounded-2xl border-2 border-[var(--color-border)]">
                            <span class="text-3xl font-black text-[var(--color-primary)] price-val" id="price-core">۱۷۵,۰۰۰</span>
                            <span class="text-xs text-[var(--color-text-muted)] font-bold block mt-0.5 price-period" id="period-core">تومان / ۳ ماهه</span>
                        </div>

                        <!-- Specifications List -->
                        <div class="space-y-2 mb-6 text-right">
                            <!-- Volume -->
                            <div class="flex justify-between items-center py-2 border-b border-dashed border-[var(--color-border)]">
                                <span class="text-xs font-semibold text-[var(--color-text-muted)] flex items-center gap-1.5">
                                    <i class="ph-fill ph-hard-drive text-[var(--color-primary)] text-sm"></i> حجم ترافیک
                                </span>
                                <span class="text-xs font-black text-green-600 bg-green-50 px-2 py-0.5 rounded-lg border border-green-200">نامحدود (FUP)</span>
                            </div>
                            <!-- Devices -->
                            <div class="flex justify-between items-center py-2 border-b border-dashed border-[var(--color-border)]">
                                <span class="text-xs font-semibold text-[var(--color-text-muted)] flex items-center gap-1.5">
                                    <i class="ph-fill ph-users text-[var(--color-primary)] text-sm"></i> دستگاه همزمان
                                </span>
                                <span class="text-xs font-bold text-[var(--color-text)]">۲ دستگاه همزمان</span>
                            </div>
                            <!-- Locations -->
                            <div class="flex justify-between items-center py-2 border-b border-dashed border-[var(--color-border)]">
                                <span class="text-xs font-semibold text-[var(--color-text-muted)] flex items-center gap-1.5">
                                    <i class="ph-fill ph-globe-hemisphere-west text-[var(--color-primary)] text-sm"></i> لوکیشن‌های فعال
                                </span>
                                <div class="flex gap-1 items-center">
                                    <img src="https://flagcdn.com/w40/de.png" alt="آلمان" class="pricing-flag" title="آلمان">
                                    <img src="https://flagcdn.com/w40/nl.png" alt="هلند" class="pricing-flag" title="هلند">
                                    <img src="https://flagcdn.com/w40/us.png" alt="آمریکا" class="pricing-flag" title="آمریکا">
                                </div>
                            </div>
                            <!-- Security Shield Features -->
                            <div class="flex justify-between items-center py-2">
                                <span class="text-xs font-semibold text-[var(--color-text-muted)] flex items-center gap-1.5">
                                    <i class="ph-fill ph-shield-check text-[var(--color-primary)] text-sm"></i> امنیت روزنه
                                </span>
                                <span class="text-[10px] font-bold text-slate-700 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-lg" title="ضد نشت IP & DNS، مسدودکننده بدافزار و تبلیغات، کنترل والدین اختیاری">سپر کامل فعال</span>
                            </div>
                        </div>

                        <!-- Platforms -->
                        <div class="flex justify-center gap-3 text-sm text-[var(--color-text-muted)] mb-5">
                            <i class="ph-bold ph-android-logo" title="Android"></i>
                            <i class="ph-bold ph-apple-logo" title="iOS & macOS"></i>
                            <i class="ph-bold ph-windows-logo" title="Windows"></i>
                            <i class="ph-bold ph-file-code" title="Linux"></i>
                        </div>

                        <button class="w-full py-3.5 rounded-2xl font-bold transition-all cursor-pointer bg-[var(--color-bg-alt)] text-[var(--color-text)] border-3 border-[var(--color-border)] hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] buy-btn" id="btn-core" data-package="روزنه تابش" data-price="۱۷۵,۰۰۰ تومان ۳ ماهه">خرید پلن</button>
                    </div>
                    
                    <!-- Pro Plan -> Ofogh -->
                    <div class="pricing-card featured" id="card-pro">
                        <div class="badge badge-primary mb-4"><span>پرطرفدارترین اشتراک 🔥</span></div>
                        <div class="text-center mb-4">
                            <span class="text-5xl">🌟</span>
                            <h3 class="text-2xl font-black text-[var(--color-text)] mt-3 mb-1">روزنه افق</h3>
                            <p class="text-xs text-[var(--color-text-muted)] font-semibold">مخصوص توسعه‌دهندگان، بورس و گیمرها</p>
                        </div>

                        <!-- Price box -->
                        <div class="text-center mb-5 py-3 bg-orange-50 rounded-2xl border-2 border-orange-200">
                            <span class="text-3xl font-black text-[var(--color-primary)] price-val" id="price-pro">۱۶۵,۰۰۰</span>
                            <span class="text-xs text-[var(--color-text-muted)] font-bold block mt-0.5 price-period" id="period-pro">تومان / ۳ ماهه</span>
                        </div>

                        <!-- Specifications List -->
                        <div class="space-y-2 mb-6 text-right">
                            <!-- Volume -->
                            <div class="flex justify-between items-center py-2 border-b border-dashed border-[var(--color-border)]">
                                <span class="text-xs font-semibold text-[var(--color-text-muted)] flex items-center gap-1.5">
                                    <i class="ph-fill ph-hard-drive text-[var(--color-primary)] text-sm"></i> حجم ترافیک
                                </span>
                                <span class="text-xs font-black text-green-600 bg-green-50 px-2 py-0.5 rounded-lg border border-green-200">نامحدود (FUP)</span>
                            </div>
                            <!-- Devices -->
                            <div class="flex justify-between items-center py-2 border-b border-dashed border-[var(--color-border)]">
                                <span class="text-xs font-semibold text-[var(--color-text-muted)] flex items-center gap-1.5">
                                    <i class="ph-fill ph-users text-[var(--color-primary)] text-sm"></i> دستگاه همزمان
                                </span>
                                <span class="text-xs font-bold text-[var(--color-text)]">۴ دستگاه همزمان</span>
                            </div>
                            <!-- Locations -->
                            <div class="flex justify-between items-center py-2 border-b border-dashed border-[var(--color-border)]">
                                <span class="text-xs font-semibold text-[var(--color-text-muted)] flex items-center gap-1.5">
                                    <i class="ph-fill ph-globe-hemisphere-west text-[var(--color-primary)] text-sm"></i> لوکیشن‌های فعال
                                </span>
                                <div class="flex gap-1 items-center">
                                    <img src="https://flagcdn.com/w40/de.png" alt="آلمان" class="pricing-flag" title="آلمان">
                                    <img src="https://flagcdn.com/w40/nl.png" alt="هلند" class="pricing-flag" title="هلند">
                                    <img src="https://flagcdn.com/w40/us.png" alt="آمریکا" class="pricing-flag" title="آمریکا">
                                    <img src="https://flagcdn.com/w40/gb.png" alt="بریتانیا" class="pricing-flag" title="بریتانیا">
                                    <img src="https://flagcdn.com/w40/tr.png" alt="ترکیه" class="pricing-flag" title="ترکیه">
                                </div>
                            </div>
                            <!-- Security Shield Features -->
                            <div class="flex justify-between items-center py-2">
                                <span class="text-xs font-semibold text-[var(--color-text-muted)] flex items-center gap-1.5">
                                    <i class="ph-fill ph-shield-check text-[var(--color-primary)] text-sm"></i> امنیت روزنه
                                </span>
                                <span class="text-[10px] font-bold text-slate-700 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-lg" title="ضد نشت IP & DNS، مسدودکننده بدافزار و تبلیغات، کنترل والدین اختیاری">سپر کامل فعال</span>
                            </div>
                        </div>

                        <!-- Platforms -->
                        <div class="flex justify-center gap-3 text-sm text-[var(--color-text-muted)] mb-5">
                            <i class="ph-bold ph-android-logo" title="Android"></i>
                            <i class="ph-bold ph-apple-logo" title="iOS & macOS"></i>
                            <i class="ph-bold ph-windows-logo" title="Windows"></i>
                            <i class="ph-bold ph-file-code" title="Linux"></i>
                        </div>

                        <button class="w-full py-3.5 rounded-2xl font-bold transition-all cursor-pointer btn-primary buy-btn" id="btn-pro" data-package="روزنه افق" data-price="۱۶۵,۰۰۰ تومان ۳ ماهه">خرید پلن</button>
                    </div>
                    
                    <!-- Enterprise Plan -> Binahayat -->
                    <div class="pricing-card" id="card-enterprise">
                        <div class="text-center mb-4">
                            <span class="text-5xl">👑</span>
                            <h3 class="text-2xl font-black text-[var(--color-text)] mt-3 mb-1">روزنه بینهایت</h3>
                            <p class="text-xs text-[var(--color-text-muted)] font-semibold">برای کارهای فوق حساس تیمی و صرافی</p>
                        </div>

                        <!-- Price box -->
                        <div class="text-center mb-5 py-3 bg-[var(--color-bg-alt)] rounded-2xl border-2 border-[var(--color-border)]">
                            <span class="text-3xl font-black text-[var(--color-primary)] price-val" id="price-enterprise">۴۹۰,۰۰۰</span>
                            <span class="text-xs text-[var(--color-text-muted)] font-bold block mt-0.5 price-period" id="period-enterprise">تومان / ۳ ماهه</span>
                        </div>

                        <!-- Specifications List -->
                        <div class="space-y-2 mb-6 text-right">
                            <!-- Volume -->
                            <div class="flex justify-between items-center py-2 border-b border-dashed border-[var(--color-border)]">
                                <span class="text-xs font-semibold text-[var(--color-text-muted)] flex items-center gap-1.5">
                                    <i class="ph-fill ph-hard-drive text-[var(--color-primary)] text-sm"></i> حجم ترافیک
                                </span>
                                <span class="text-xs font-black text-green-600 bg-green-50 px-2 py-0.5 rounded-lg border border-green-200">نامحدود (FUP)</span>
                            </div>
                            <!-- Devices -->
                            <div class="flex justify-between items-center py-2 border-b border-dashed border-[var(--color-border)]">
                                <span class="text-xs font-semibold text-[var(--color-text-muted)] flex items-center gap-1.5">
                                    <i class="ph-fill ph-users text-[var(--color-primary)] text-sm"></i> دستگاه همزمان
                                </span>
                                <span class="text-xs font-bold text-[var(--color-text)]">۶ دستگاه همزمان</span>
                            </div>
                            <!-- Locations -->
                            <div class="flex justify-between items-center py-2 border-b border-dashed border-[var(--color-border)]">
                                <span class="text-xs font-semibold text-[var(--color-text-muted)] flex items-center gap-1.5">
                                    <i class="ph-fill ph-globe-hemisphere-west text-[var(--color-primary)] text-sm"></i> لوکیشن‌های فعال
                                </span>
                                <div class="flex gap-1 items-center flex-wrap max-w-[130px] justify-end">
                                    <img src="https://flagcdn.com/w40/de.png" alt="آلمان" class="pricing-flag" title="آلمان">
                                    <img src="https://flagcdn.com/w40/nl.png" alt="هلند" class="pricing-flag" title="هلند">
                                    <img src="https://flagcdn.com/w40/us.png" alt="آمریکا" class="pricing-flag" title="آمریکا">
                                    <img src="https://flagcdn.com/w40/gb.png" alt="بریتانیا" class="pricing-flag" title="بریتانیا">
                                    <img src="https://flagcdn.com/w40/tr.png" alt="ترکیه" class="pricing-flag" title="ترکیه">
                                    <img src="https://flagcdn.com/w40/ca.png" alt="کانادا" class="pricing-flag" title="کانادا">
                                    <img src="https://flagcdn.com/w40/fi.png" alt="فنلاند" class="pricing-flag" title="فنلاند">
                                    <img src="https://flagcdn.com/w40/ae.png" alt="دبی" class="pricing-flag" title="دبی">
                                </div>
                            </div>
                            <!-- Security Shield Features -->
                            <div class="flex justify-between items-center py-2">
                                <span class="text-xs font-semibold text-[var(--color-text-muted)] flex items-center gap-1.5">
                                    <i class="ph-fill ph-shield-check text-[var(--color-primary)] text-sm"></i> امنیت روزنه
                                </span>
                                <span class="text-[10px] font-bold text-slate-700 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-lg" title="ضد نشت IP & DNS، مسدودکننده بدافزار و تبلیغات، کنترل والدین اختیاری">سپر کامل فعال</span>
                            </div>
                        </div>

                        <!-- Platforms -->
                        <div class="flex justify-center gap-3 text-sm text-[var(--color-text-muted)] mb-5">
                            <i class="ph-bold ph-android-logo" title="Android"></i>
                            <i class="ph-bold ph-apple-logo" title="iOS & macOS"></i>
                            <i class="ph-bold ph-windows-logo" title="Windows"></i>
                            <i class="ph-bold ph-file-code" title="Linux"></i>
                        </div>

                        <button class="w-full py-3.5 rounded-2xl font-bold transition-all cursor-pointer bg-[var(--color-bg-alt)] text-[var(--color-text)] border-3 border-[var(--color-border)] hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] buy-btn" id="btn-enterprise" data-package="روزنه بینهایت" data-price="۴۹۰,۰۰۰ تومان ۳ ماهه">خرید پلن</button>
                    </div>
                </div>
                <p class="text-center text-xs text-[var(--color-text-muted)] mt-8 font-medium">* تمامی اشتراک‌ها بر روی پروتکل‌های فوق مدرن Vless با تونل رمزنگاری اختصاصی ارائه می‌شوند.</p>
            </div>
        </section>