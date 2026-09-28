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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('referral_banned')->default(false)->after('referrer_id');
            $table->double('referral_traffic_balance')->default(0.0)->after('referral_banned'); // stores MBs of traffic
        });

        Schema::create('referral_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('referee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('level')->default(1);
            $table->double('traffic_reward')->default(0.0); // stores MBs
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('is_suspicious')->default(false);
            $table->string('suspicion_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_logs');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['referral_banned', 'referral_traffic_balance']);
        });
    }
};
