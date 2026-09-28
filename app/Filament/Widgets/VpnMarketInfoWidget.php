<?php


namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class VpnMarketInfoWidget extends Widget
{
    protected static ?int $sort = -5;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.vpn-market-info-widget';
}
