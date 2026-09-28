<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Laravel\Facades\Telegram;

class SendDatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vpnmarket:send-backup {--force : Force backup regardless of schedule interval}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a compressed MySQL database dump and send it to the Telegram backup channel.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database backup process...');

        $settings = Setting::all()->pluck('value', 'key');
        $backupChannelId = $settings->get('telegram_backup_channel_id');
        $botToken = $settings->get('telegram_bot_token');
        $backupEnabled = filter_var($settings->get('backup_enabled', true), FILTER_VALIDATE_BOOLEAN);
        $intervalHours = (int) $settings->get('backup_interval_hours', 6);
        if ($intervalHours <= 0) {
            $intervalHours = 6;
        }

        if (!$backupChannelId || !$botToken) {
            $this->error('❌ Telegram backup channel ID or bot token is not configured in settings.');
            return Command::FAILURE;
        }

        $isForced = $this->option('force');

        if (!$isForced && !$backupEnabled) {
            $this->info('ℹ️ Backup is disabled in settings. Skipping.');
            return Command::SUCCESS;
        }

        $lastRun = Cache::get('last_backup_sent_at');
        if (!$isForced && $lastRun) {
            $nextRun = Carbon::parse($lastRun)->addHours($intervalHours);
            if (now()->lessThan($nextRun)) {
                $this->info("ℹ️ Next backup scheduled at {$nextRun->toDateTimeString()}. Skipping.");
                return Command::SUCCESS;
            }
        }

        // DB configuration
        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = config('database.connections.mysql.port', '3306');
        $dbName = config('database.connections.mysql.database', 'vpnmarket');
        $dbUser = config('database.connections.mysql.username', 'vpnmarket');
        $dbPass = config('database.connections.mysql.password', '');

        $timestamp = date('Y-m-d_H-i-s');
        $tempSqlPath = sys_get_temp_dir() . "/backup_{$dbName}_{$timestamp}.sql";
        $tempGzPath = "{$tempSqlPath}.gz";

        try {
            // Build mysqldump command
            $passParam = !empty($dbPass) ? "-p" . escapeshellarg($dbPass) : '';
            $dumpCmd = sprintf(
                'mysqldump --host=%s --port=%s --user=%s %s --single-transaction --quick --skip-lock-tables %s > %s 2>&1',
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbUser),
                $passParam,
                escapeshellarg($dbName),
                escapeshellarg($tempSqlPath)
            );

            exec($dumpCmd, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($tempSqlPath) || filesize($tempSqlPath) === 0) {
                $errorMsg = implode("\n", $output);
                throw new \Exception("mysqldump failed with code {$returnCode}: {$errorMsg}");
            }

            // Gzip compress
            exec("gzip -9 -f " . escapeshellarg($tempSqlPath), $gzipOutput, $gzipCode);
            if ($gzipCode !== 0 || !file_exists($tempGzPath)) {
                throw new \Exception("gzip compression failed with code {$gzipCode}");
            }

            $fileSizeBytes = filesize($tempGzPath);
            $fileSizeMB = round($fileSizeBytes / (1024 * 1024), 2);
            $fileSizeKB = round($fileSizeBytes / 1024, 1);
            $sizeDisplay = $fileSizeMB >= 1 ? "{$fileSizeMB} MB" : "{$fileSizeKB} KB";

            // Stats
            $totalUsers = User::count();
            $totalOrders = Order::count();
            $paidOrders = Order::where('status', 'paid')->count();

            // Format caption
            $caption = "📦 <b>بکاپ کامل دیتابیس لوکانت (LookaNet)</b>\n\n";
            $caption .= "🗄 <b>نام دیتابیس:</b> <code>{$dbName}</code>\n";
            $caption .= "💾 <b>حجم فایل فشرده:</b> <code>{$sizeDisplay}</code>\n";
            $caption .= "👥 <b>تعداد کل کاربران:</b> <code>{$totalUsers}</code> نفر\n";
            $caption .= "🛒 <b>کل سفارش‌ها:</b> <code>{$totalOrders}</code> (موفق: <code>{$paidOrders}</code>)\n";
            $caption .= "⏱ <b>بازه زمانی خودکار:</b> هر {$intervalHours} ساعت یک‌بار\n";
            $caption .= "📅 <b>تاریخ و زمان:</b> " . now()->format('Y/m/d H:i:s') . "\n\n";
            $caption .= "🔐 <i>فایل به صورت فشرده Gzip آماده و ارسال شده است.</i>";

            // Send to Telegram
            Telegram::setAccessToken(trim($botToken, '"\' '));

            Telegram::sendDocument([
                'chat_id' => $backupChannelId,
                'document' => InputFile::create($tempGzPath, "backup_{$dbName}_{$timestamp}.sql.gz"),
                'caption' => $caption,
                'parse_mode' => 'HTML',
            ]);

            Cache::put('last_backup_sent_at', now()->toDateTimeString(), now()->addDays(30));

            $this->info("✅ Database backup successfully sent to channel {$backupChannelId} ({$sizeDisplay})");
            Log::info("Database backup sent successfully to channel {$backupChannelId}");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("❌ Backup failed: " . $e->getMessage());
            Log::error("Database backup failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return Command::FAILURE;
        } finally {
            if (file_exists($tempSqlPath)) {
                @unlink($tempSqlPath);
            }
            if (file_exists($tempGzPath)) {
                @unlink($tempGzPath);
            }
        }
    }
}
