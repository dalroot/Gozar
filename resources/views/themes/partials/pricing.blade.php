        <!-- Pricing Section (UI Pro-Max Studio-Grade Cards) -->
        <section id="pricing" class="py-16 sm:py-24 bg-[var(--color-bg)]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Section Header -->
                <div class="text-center mb-10 sm:mb-14">
                    <div class="badge badge-primary mx-auto mb-4">
                        <i class="ph-bold ph-tag text-base text-[var(--color-primary)]"></i>
                        <span>پلن‌ها و اشتراک‌های روزنه</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold text-[var(--color-text)] mb-4 leading-tight">
                        قیمت‌گذاری ساده و مشخص
                    </h2>
                    <p class="text-base sm:text-lg text-[var(--color-text-muted)] max-w-2xl mx-auto font-medium leading-relaxed">
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
                            <span class="discount-badge secondary">۳۵٪ تخفیف ☀️</span>
                        </button>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-3 gap-6 sm:gap-8 max-w-5xl mx-auto">
                    
                    <!-- Plan 1: Tabesh (Core Plan) -->
                    <div class="pricing-card" id="card-core">
                        <div>
                            <div class="text-center mb-4">
                                <div class="w-14 h-14 rounded-2xl bg-orange-50 border-2 border-[#FFE4CB] flex items-center justify-center text-3xl mx-auto mb-3 shadow-xs">
                                    <span>🌱</span>
                                </div>
                                <h3 class="text-2xl font-black text-[#38140B] mb-1">روزنه تابش</h3>
                                <p class="text-xs text-[var(--color-text-muted)] font-bold">مناسب وب‌گردی و امور روزمره</p>
                            </div>

                            <!-- Price box -->
                            <div class="price-box-wrapper">
                                <span class="text-3xl font-black text-[#38140B] price-val" id="price-core"><bdi dir="ltr">۱۷۵,۰۰۰</bdi></span>
                                <span class="text-xs text-slate-500 font-bold block mt-0.5 price-period" id="period-core">تومان / ۳ ماهه</span>
                            </div>

                            <!-- Specifications List -->
                            <div class="space-y-3 mb-6 text-right">
                                <div class="flex justify-between items-center py-2 border-b border-dashed border-[#FFE4CB]">
                                    <span class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                                        <i class="ph-bold ph-hard-drive text-[var(--color-primary)] text-sm"></i> حجم ترافیک
                                    </span>
                                    <span class="text-xs font-black text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">نامحدود (FUP)</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-dashed border-[#FFE4CB]">
                                    <span class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                                        <i class="ph-bold ph-users text-[var(--color-primary)] text-sm"></i> دستگاه همزمان
                                    </span>
                                    <span class="text-xs font-black text-[#38140B]">۲ دستگاه همزمان</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-dashed border-[#FFE4CB]">
                                    <span class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                                        <i class="ph-bold ph-globe-hemisphere-west text-[var(--color-primary)] text-sm"></i> لوکیشن‌های فعال
                                    </span>
                                    <div class="flex gap-1 items-center">
                                        <img src="https://flagcdn.com/w40/de.png" alt="آلمان" class="pricing-flag" title="آلمان">
                                        <img src="https://flagcdn.com/w40/nl.png" alt="هلند" class="pricing-flag" title="هلند">
                                        <img src="https://flagcdn.com/w40/us.png" alt="آمریکا" class="pricing-flag" title="آمریکا">
                                    </div>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                                        <i class="ph-bold ph-shield-check text-[var(--color-primary)] text-sm"></i> امنیت شبکه
                                    </span>
                                    <span class="text-[10px] font-black text-slate-700 bg-slate-100 border border-slate-200 px-2.5 py-0.5 rounded-full">سپر کامل فعال</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <!-- Platforms -->
                            <div class="flex justify-center gap-3 text-base text-slate-400 mb-5">
                                <i class="ph-bold ph-android-logo" title="Android"></i>
                                <i class="ph-bold ph-apple-logo" title="iOS & macOS"></i>
                                <i class="ph-bold ph-windows-logo" title="Windows"></i>
                                <i class="ph-bold ph-file-code" title="Linux"></i>
                            </div>

                            <button class="w-full py-3.5 rounded-2xl font-black transition-all cursor-pointer bg-white text-[#38140B] border-2 border-[#FFE4CB] hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] buy-btn shadow-xs" id="btn-core" data-package="روزنه تابش" data-price="۱۷۵,۰۰۰ تومان ۳ ماهه">خرید پلن</button>
                        </div>
                    </div>
                    
                    <!-- Plan 2: Ofogh (Featured / Most Popular Plan) -->
                    <div class="pricing-card featured" id="card-pro">
                        <div>
                            <div class="badge badge-primary mx-auto mb-3 shadow-xs">
                                <span>🔥 پرفروش‌ترین اشتراک</span>
                            </div>
                            <div class="text-center mb-4">
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-100 to-amber-100 border-2 border-orange-300 flex items-center justify-center text-3xl mx-auto mb-3 shadow-xs">
                                    <span>⚡</span>
                                </div>
                                <h3 class="text-2xl font-black text-[#38140B] mb-1">روزنه افق</h3>
                                <p class="text-xs text-rose-600 font-bold">مخصوص پینگ پایین، گیمینگ و توسعه‌دهندگان</p>
                            </div>

                            <!-- Price box -->
                            <div class="price-box-wrapper">
                                <span class="text-3.5xl font-black text-[var(--color-primary)] price-val" id="price-pro"><bdi dir="ltr">۲۶۵,۰۰۰</bdi></span>
                                <span class="text-xs text-slate-600 font-bold block mt-0.5 price-period" id="period-pro">تومان / ۳ ماهه</span>
                            </div>

                            <!-- Specifications List -->
                            <div class="space-y-3 mb-6 text-right">
                                <div class="flex justify-between items-center py-2 border-b border-dashed border-orange-200">
                                    <span class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                                        <i class="ph-bold ph-hard-drive text-[var(--color-primary)] text-sm"></i> حجم ترافیک
                                    </span>
                                    <span class="text-xs font-black text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">نامحدود (FUP)</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-dashed border-orange-200">
                                    <span class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                                        <i class="ph-bold ph-users text-[var(--color-primary)] text-sm"></i> دستگاه همزمان
                                    </span>
                                    <span class="text-xs font-black text-[#38140B]">۴ دستگاه همزمان</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-dashed border-orange-200">
                                    <span class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                                        <i class="ph-bold ph-globe-hemisphere-west text-[var(--color-primary)] text-sm"></i> لوکیشن‌های فعال
                                    </span>
                                    <div class="flex gap-1 items-center">
                                        <img src="https://flagcdn.com/w40/de.png" alt="آلمان" class="pricing-flag" title="آلمان">
                                        <img src="https://flagcdn.com/w40/nl.png" alt="هلند" class="pricing-flag" title="هلند">
                                        <img src="https://flagcdn.com/w40/us.png" alt="آمریکا" class="pricing-flag" title="آمریکا">
                                        <img src="https://flagcdn.com/w40/gb.png" alt="بریتانیا" class="pricing-flag" title="بریتانیا">
                                        <img src="https://flagcdn.com/w40/tr.png" alt="ترکیه" class="pricing-flag" title="ترکیه">
                                    </div>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                                        <i class="ph-bold ph-lightning text-[var(--color-primary)] text-sm"></i> مسیریابی اختصاصی
                                    </span>
                                    <span class="text-[10px] font-black text-orange-700 bg-orange-50 border border-orange-200 px-2.5 py-0.5 rounded-full">پینگ پایین فعال</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <!-- Platforms -->
                            <div class="flex justify-center gap-3 text-base text-slate-400 mb-5">
                                <i class="ph-bold ph-android-logo" title="Android"></i>
                                <i class="ph-bold ph-apple-logo" title="iOS & macOS"></i>
                                <i class="ph-bold ph-windows-logo" title="Windows"></i>
                                <i class="ph-bold ph-file-code" title="Linux"></i>
                            </div>

                            <button class="w-full py-3.5 rounded-2xl font-black transition-all cursor-pointer btn-primary buy-btn shadow-lg shadow-orange-500/25" id="btn-pro" data-package="روزنه افق" data-price="۲۶۵,۰۰۰ تومان ۳ ماهه">خرید پلن پرفروش</button>
                        </div>
                    </div>
                    
                    <!-- Plan 3: Binahayat (Enterprise / VIP Plan) -->
                    <div class="pricing-card" id="card-enterprise">
                        <div>
                            <div class="badge badge-accent mx-auto mb-3 shadow-xs">
                                <span>👑 VIP و اختصاصی</span>
                            </div>
                            <div class="text-center mb-4">
                                <div class="w-14 h-14 rounded-2xl bg-purple-50 border-2 border-purple-200 flex items-center justify-center text-3xl mx-auto mb-3 shadow-xs">
                                    <span>👑</span>
                                </div>
                                <h3 class="text-2xl font-black text-[#38140B] mb-1">روزنه بینهایت</h3>
                                <p class="text-xs text-purple-700 font-bold">برای کارهای حساس، ترید و IP ثابت صرافی</p>
                            </div>

                            <!-- Price box -->
                            <div class="price-box-wrapper">
                                <span class="text-3xl font-black text-slate-900 price-val" id="price-enterprise"><bdi dir="ltr">۴۹۰,۰۰۰</bdi></span>
                                <span class="text-xs text-slate-500 font-bold block mt-0.5 price-period" id="period-enterprise">تومان / ۳ ماهه</span>
                            </div>

                            <!-- Specifications List -->
                            <div class="space-y-3 mb-6 text-right">
                                <div class="flex justify-between items-center py-2 border-b border-dashed border-[#FFE4CB]">
                                    <span class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                                        <i class="ph-bold ph-hard-drive text-[var(--color-primary)] text-sm"></i> حجم ترافیک
                                    </span>
                                    <span class="text-xs font-black text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">نامحدود (FUP)</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-dashed border-[#FFE4CB]">
                                    <span class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                                        <i class="ph-bold ph-users text-[var(--color-primary)] text-sm"></i> دستگاه همزمان
                                    </span>
                                    <span class="text-xs font-black text-[#38140B]">۶ دستگاه همزمان</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-dashed border-[#FFE4CB]">
                                    <span class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                                        <i class="ph-bold ph-globe-hemisphere-west text-[var(--color-primary)] text-sm"></i> لوکیشن‌های فعال
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
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                                        <i class="ph-bold ph-key text-[var(--color-primary)] text-sm"></i> آی‌پی اختصاصی
                                    </span>
                                    <span class="text-[10px] font-black text-purple-700 bg-purple-50 border border-purple-200 px-2.5 py-0.5 rounded-full">IP ثابت صرافی</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <!-- Platforms -->
                            <div class="flex justify-center gap-3 text-base text-slate-400 mb-5">
                                <i class="ph-bold ph-android-logo" title="Android"></i>
                                <i class="ph-bold ph-apple-logo" title="iOS & macOS"></i>
                                <i class="ph-bold ph-windows-logo" title="Windows"></i>
                                <i class="ph-bold ph-file-code" title="Linux"></i>
                            </div>

                            <button class="w-full py-3.5 rounded-2xl font-black transition-all cursor-pointer bg-slate-900 text-white hover:bg-slate-800 buy-btn shadow-md" id="btn-enterprise" data-package="روزنه بینهایت" data-price="۴۹۰,۰۰۰ تومان ۳ ماهه">خرید پلن VIP</button>
                        </div>
                    </div>

                </div>

                <p class="text-center text-xs text-[var(--color-text-muted)] mt-8 font-medium">* تمامی اشتراک‌ها بر روی پروتکل‌های فوق مدرن Vless با تونل رمزنگاری اختصاصی ارائه می‌شوند.</p>
            </div>
        </section>
