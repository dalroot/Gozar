<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'invoice_number')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedInteger('invoice_number')->nullable()->after('id')->index();
            });
        }

        // مقداردهی ترتیبی ۱۳ خرید واقعی انجام‌شده در گذشته (فاکتورهای ۱ تا ۱۳)
        $realOrderIds = [19, 20, 25, 26, 31, 34, 39, 57, 65, 66, 71, 74, 85];
        foreach ($realOrderIds as $index => $orderId) {
            DB::table('orders')->where('id', $orderId)->update([
                'invoice_number' => $index + 1
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'invoice_number')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('invoice_number');
            });
        }
    }
};
