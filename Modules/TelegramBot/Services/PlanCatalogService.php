<?php

namespace Modules\TelegramBot\Services;

use App\Models\Plan;
use Illuminate\Support\Collection;

final class PlanCatalogService
{
    public function activePlans(): Collection
    {
        return Plan::query()
            ->where('is_active', true)
            ->orderBy('duration_days')
            ->orderBy('volume_gb')
            ->orderBy('price')
            ->get();
    }

    public function durations(): Collection
    {
        return $this->activePlans()
            ->pluck('duration_days')
            ->map(fn ($days) => (int) $days)
            ->unique()
            ->sort()
            ->values();
    }

    public function plansForDuration(int $days): Collection
    {
        return Plan::query()
            ->where('is_active', true)
            ->where('duration_days', $days)
            ->orderBy('volume_gb')
            ->orderBy('price')
            ->get();
    }

    public function durationLabel(int $days): string
    {
        return match ($days) {
            30 => '۱ ماهه (۳۰ روزه)',
            60 => '۲ ماهه (۶۰ روزه)',
            90 => '۳ ماهه (۹۰ روزه)',
            180 => '۶ ماهه (۱۸۰ روزه)',
            365 => '۱ ساله',
            730 => '۲ ساله',
            default => $days . ' روزه',
        };
    }

    public function planButtonLabel(Plan $plan): string
    {
        $volume = $plan->volume_gb ? $plan->volume_gb . ' گیگابایت' : 'حجم سفارشی';
        $label = $volume . '  |  ' . number_format((float) $plan->price) . ' تومان';
        return $plan->is_popular ? $label . '  •  پیشنهاد روزنه' : $label;
    }
}
