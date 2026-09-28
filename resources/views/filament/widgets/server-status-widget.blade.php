<x-filament-widgets::widget class="fi-filament-server-status-widget">
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <span class="text-xl">🖥️</span>
                <span>وضعیت و ظرفیت سرورهای مرزبان و X-UI</span>
            </div>
        </x-slot>

        @php
            $servers = $this->getServers();
        @endphp

        @if(empty($servers))
            <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                هیچ سروری در سیستم ثبت نشده است.
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">
                @foreach($servers as $server)
                    <div class="server-status-card p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30 flex flex-col justify-between">
                        
                        <!-- Header: Name & Flag -->
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                                    <span class="text-lg">{{ $server['flag'] }}</span>
                                    <span>{{ $server['name'] }}</span>
                                </h4>
                                <p class="text-xs text-gray-400 mt-0.5" dir="ltr">{{ $server['ip'] }}</p>
                            </div>

                            <!-- Connection Status Badge -->
                            <div>
                                @if(!$server['is_active'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        غیرفعال
                                    </span>
                                @elseif($server['is_online'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full ml-1.5 animate-pulse"></span>
                                        آنلاین
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400">
                                        <span class="w-1.5 h-1.5 bg-rose-500 rounded-full ml-1.5"></span>
                                        قطع ارتباط
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Body: Load Stats & Progress Bar -->
                        <div class="mt-4">
                            <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                                <span>بار ترافیکی سرور</span>
                                <span class="font-semibold">{{ $server['current'] }} / {{ $server['capacity'] }} کاربر ({{ $server['load_percent'] }}%)</span>
                            </div>

                            <!-- Progress Bar Container -->
                            <div class="w-full bg-gray-200 dark:bg-gray-700 h-2 rounded-full overflow-hidden">
                                @if($server['load_color'] == 'success')
                                    <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: {{ $server['load_percent'] }}%"></div>
                                @elseif($server['load_color'] == 'warning')
                                    <div class="bg-amber-500 h-full rounded-full transition-all duration-500" style="width: {{ $server['load_percent'] }}%"></div>
                                @else
                                    <div class="bg-rose-500 h-full rounded-full transition-all duration-500" style="width: {{ $server['load_percent'] }}%"></div>
                                @endif
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
