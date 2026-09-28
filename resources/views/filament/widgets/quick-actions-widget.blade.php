<x-filament-widgets::widget class="fi-filament-quick-actions-widget">
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <span class="text-xl">⚡</span>
                <span>دسترسی سریع و میانبرها</span>
            </div>
        </x-slot>

        @php
            $actions = $this->getActions();
        @endphp

        @if(empty($actions))
            <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                هیچ دسترسی سریعی تعریف نشده است.
            </div>
        @else
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                @foreach($actions as $action)
                    <a href="{{ $action['url'] }}" class="quick-action-btn flex items-center justify-between p-4 rounded-xl shadow-sm transition-all duration-200 {{ $action['color'] }}">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-white/20 dark:bg-black/10 rounded-lg">
                                @if($action['icon'] == 'heroicon-o-plus-circle')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @elseif($action['icon'] == 'heroicon-o-user-plus')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"></path>
                                    </svg>
                                @elseif($action['icon'] == 'heroicon-o-ticket')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-12v12m.75-12h7.5a1.5 1.5 0 011.5 1.5v1.5a1.5 1.5 0 00-1.5 1.5v1.5a1.5 1.5 0 001.5 1.5v1.5a1.5 1.5 0 00-1.5 1.5v1.5a1.5 1.5 0 01-1.5 1.5h-7.5a1.5 1.5 0 01-1.5-1.5V7.5A1.5 1.5 0 016 6z"></path>
                                    </svg>
                                @elseif($action['icon'] == 'heroicon-o-megaphone')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.68-.68-.86-1.72-.49-2.58l.68-1.59c.3-.7.3-1.5 0-2.2l-.68-1.59c-.37-.86-.19-1.9.49-2.58L11 4.5h.09c1.65 0 3.19.64 4.34 1.8l.61.61c1.16 1.15 1.8 2.69 1.8 4.34v.09c0 1.65-.64 3.19-1.8 4.34l-.61.61c-1.15 1.16-2.69 1.8-4.34 1.8H11l-.66-.66z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 10.5h.008v.008H6V10.5zm0 3h.008v.008H6v-.008zm0 3h.008v.008H6v-.008zM19.5 9.75h.008v.008h-.008V9.75zm0 4.5h.008v.008h-.008v-.008z"></path>
                                    </svg>
                                @else
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"></path>
                                    </svg>
                                @endif
                            </div>
                            <span class="font-bold text-sm">{{ $action['label'] }}</span>
                        </div>
                        <svg class="w-4 h-4 opacity-60 hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"></path>
                        </svg>
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
