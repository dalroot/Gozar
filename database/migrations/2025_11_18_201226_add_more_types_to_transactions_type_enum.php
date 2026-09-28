<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMoreTypesToTransactionsTypeEnum extends Migration
{
    public function up()
    {
        // MySQL-specific ALTER TABLE MODIFY — no-op on SQLite
        if (config('database.default') !== 'sqlite') {
            \DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('deposit', 'purchase', 'referral_reward', 'withdraw', 'manual adjustment') DEFAULT 'deposit'");
        }
    }

    public function down()
    {
        if (config('database.default') !== 'sqlite') {
            \DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('deposit', 'purchase', 'referral_reward') DEFAULT 'deposit'");
        }
    }
}
