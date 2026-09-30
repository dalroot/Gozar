<x-filament-panels::page>
    <div class="rz-ops" dir="rtl">
        <section class="rz-ops__hero">
            <div>
                <p class="rz-ops__eyebrow"><i class="rz-ops__pulse"></i> مرکز عملیات روزنه</p>
                <h1>تصویر روشن از فروش، پشتیبانی و تحویل سرویس</h1>
                <p class="rz-ops__hero-copy">
                    شاخص‌های مهم و موارد نیازمند اقدام در یک صفحه جمع شده‌اند؛ هیچ عملیاتی بدون تأیید شما انجام نمی‌شود.
                </p>
            </div>
            <button class="rz-ops__refresh" type="button" wire:click="refreshDashboard" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="refreshDashboard">به‌روزرسانی اطلاعات</span>
                <span wire:loading wire:target="refreshDashboard">در حال به‌روزرسانی…</span>
            </button>
        </section>

        <section class="rz-ops__metrics" aria-label="شاخص‌های کلیدی امروز">
            <a class="rz-ops__metric rz-ops__metric--green" href="{{ url('/admin/orders') }}">
                <span class="rz-ops__metric-label">درآمد امروز</span>
                <strong class="rz-ops__metric-value">{{ number_format($stats['revenue_today']) }}</strong>
                <span class="rz-ops__metric-hint">تومان از سفارش‌های پرداخت‌شده</span>
            </a>
            <a class="rz-ops__metric rz-ops__metric--indigo" href="{{ url('/admin/orders') }}">
                <span class="rz-ops__metric-label">فروش موفق امروز</span>
                <strong class="rz-ops__metric-value">{{ number_format($stats['paid_today']) }}</strong>
                <span class="rz-ops__metric-hint">سفارش تکمیل‌شده</span>
            </a>
            <a class="rz-ops__metric rz-ops__metric--amber" href="{{ url('/admin/orders?tableFilters[status][value]=pending') }}">
                <span class="rz-ops__metric-label">سفارش‌های منتظر</span>
                <strong class="rz-ops__metric-value">{{ number_format($stats['pending_orders']) }}</strong>
                <span class="rz-ops__metric-hint">نیازمند پرداخت یا بررسی</span>
            </a>
            <a class="rz-ops__metric rz-ops__metric--sky" href="{{ url('/admin/tickets?tableFilters[status][value]=open') }}">
                <span class="rz-ops__metric-label">تیکت‌های باز</span>
                <strong class="rz-ops__metric-value">{{ number_format($stats['open_tickets']) }}</strong>
                <span class="rz-ops__metric-hint">در صف رسیدگی پشتیبانی</span>
            </a>
            <a class="rz-ops__metric rz-ops__metric--violet" href="{{ url('/admin/users') }}">
                <span class="rz-ops__metric-label">مشتری جدید امروز</span>
                <strong class="rz-ops__metric-value">{{ number_format($stats['new_users_today']) }}</strong>
                <span class="rz-ops__metric-hint">ثبت‌نام جدید در سامانه</span>
            </a>
            <a class="rz-ops__metric rz-ops__metric--rose" href="{{ url('/admin/orders') }}">
                <span class="rz-ops__metric-label">بازگشت وجه امروز</span>
                <strong class="rz-ops__metric-value">{{ number_format($stats['refunds_today']) }}</strong>
                <span class="rz-ops__metric-hint">تومان ثبت‌شده در تراکنش‌ها</span>
            </a>
        </section>

        <section class="rz-ops__actions" aria-label="صف اقدامات ضروری">
            <a class="rz-ops__action" href="{{ url('/admin/orders') }}">
                <div><strong>رسیدهای منتظر تأیید</strong><span>بررسی پرداخت‌های کارت‌به‌کارت</span></div>
                <b class="rz-ops__count">{{ number_format($stats['pending_receipts']) }}</b>
            </a>
            <a class="rz-ops__action" href="{{ url('/admin/orders') }}">
                <div><strong>تحویل‌های نیازمند بررسی</strong><span>سفارش پرداخت‌شده بدون اطلاعات اتصال</span></div>
                <b class="rz-ops__count">{{ number_format($stats['failed_provisioning']) }}</b>
            </a>
            <a class="rz-ops__action" href="{{ url('/admin/tickets') }}">
                <div><strong>پشتیبانی خارج از SLA</strong><span>تیکت باز بدون بروزرسانی در ۴۸ ساعت</span></div>
                <b class="rz-ops__count">{{ number_format($stats['overdue_tickets']) }}</b>
            </a>
            <a class="rz-ops__action" href="{{ url('/admin/tickets') }}">
                <div><strong>ارجاع انسانی فرایدی</strong><span>درخواست‌های باز منتقل‌شده از دستیار</span></div>
                <b class="rz-ops__count">{{ number_format($stats['human_handoffs']) }}</b>
            </a>
        </section>

        <section class="rz-ops__charts" aria-label="روندهای سی روزه">
            @livewire(\App\Filament\Widgets\OrdersChart::class)
            @livewire(\App\Filament\Widgets\UsersChart::class)
        </section>

        <section class="rz-ops__bottom">
            <article class="rz-ops__panel">
                <header class="rz-ops__panel-head">
                    <div><h2>آخرین سفارش‌ها</h2><p>مرور سریع وضعیت فروش و تحویل سرویس</p></div>
                    <a href="{{ url('/admin/orders') }}">مشاهده همه سفارش‌ها</a>
                </header>
                <div class="rz-ops__table-scroll">
                    <table class="rz-ops__table">
                        <thead><tr><th>سفارش</th><th>مشتری</th><th>بسته</th><th>مبلغ</th><th>وضعیت</th><th>زمان</th></tr></thead>
                        <tbody>
                        @forelse ($recentOrders as $order)
                            @php($status = ['paid' => ['پرداخت‌شده', 'paid'], 'pending' => ['در انتظار', 'pending'], 'expired' => ['ناموفق/منقضی', 'expired']][$order['status']] ?? [$order['status'], 'other'])
                            <tr>
                                <td><strong>#{{ $order['id'] }}</strong></td>
                                <td>{{ $order['user'] }}</td>
                                <td>{{ $order['plan'] }}</td>
                                <td>{{ $order['amount'] }}</td>
                                <td><span class="rz-ops__status rz-ops__status--{{ $status[1] }}">{{ $status[0] }}</span></td>
                                <td>{{ $order['created_at'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="padding:32px;text-align:center;color:var(--rz-muted)">هنوز سفارشی ثبت نشده است.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <aside class="rz-ops__panel rz-ops__quick">
                <h2>دسترسی سریع</h2>
                <p>مسیرهای اصلی کار روزانه</p>
                <div class="rz-ops__quick-list">
                    <a href="{{ url('/admin/tickets') }}"><strong>صندوق پشتیبانی</strong><span>تیکت‌ها و ارجاع‌های انسانی فرایدی</span></a>
                    <a href="{{ url('/admin/orders') }}"><strong>سفارش و رسید</strong><span>پرداخت‌ها، فیش‌ها و تحویل سرویس</span></a>
                    <a href="{{ url('/admin/users') }}"><strong>مشتریان</strong><span>حساب، کیف پول و سرویس‌های کاربران</span></a>
                    <a href="{{ url('/admin/plans') }}"><strong>پلن‌ها و قیمت‌گذاری</strong><span>مدیریت حجم، زمان و قیمت بسته‌ها</span></a>
                    <a href="{{ url('/admin/theme-settings') }}"><strong>روش‌های پرداخت</strong><span>کارت، کیف پول، درگاه و رمزارز</span></a>
                    <a href="{{ url('/admin/inbounds') }}"><strong>اینباندها و زیرساخت</strong><span>همگام‌سازی و وضعیت مسیرهای اتصال</span></a>
                </div>
            </aside>
        </section>
    </div>
</x-filament-panels::page>

