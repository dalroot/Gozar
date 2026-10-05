<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'trial_notified_80_at')) {
                $table->timestamp('trial_notified_80_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('orders', 'trial_notified_100_at')) {
                $table->timestamp('trial_notified_100_at')->nullable()->after('trial_notified_80_at');
            }
            if (!Schema::hasColumn('orders', 'trial_notified_idle_at')) {
                $table->timestamp('trial_notified_idle_at')->nullable()->after('trial_notified_100_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'trial_notified_80_at',
                'trial_notified_100_at',
                'trial_notified_idle_at',
            ]);
        });
    }
};
