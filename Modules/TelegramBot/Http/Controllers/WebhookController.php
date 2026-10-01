<?php

namespace Modules\TelegramBot\Http\Controllers;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\TelegramBotSetting;
use App\Services\XUIService;
use App\Models\User;
use App\Services\MarzbanService;
use App\Services\PasargadService;
use App\Services\RemnawaveService;
use App\Models\Inbound;
use Modules\Ticketing\Events\TicketCreated;
use Modules\Ticketing\Events\TicketReplied;
use Modules\Ticketing\Models\Ticket;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http; // ✅ اضافه شده
use Telegram\Bot\Laravel\Facades\Telegram;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Keyboard\Keyboard;
use Illuminate\Support\Str;
use App\Models\DiscountCode;
use App\Models\DiscountCodeUsage;
use Carbon\Carbon;
use Telegram\Bot\FileUpload\InputFile;
use Modules\TelegramBot\Services\RozanehExperience;
use Modules\TelegramBot\Services\PlanCatalogService;

class WebhookController extends Controller
{
    protected $settings;

    /**
     * ✅ اضافه شده: کانستراکتور برای اطمینان از مقداردهی settings
     */
    public function __construct()
    {
        $this->settings = collect();
    }

    public function sendBroadcastMessage(string $chatId, string $message): bool
    {
        try {
            if ($this->settings->isEmpty()) { // ✅ اصلاح: استفاده از isEmpty() به جای null check
                $this->settings = Setting::all()->pluck('value', 'key');
            }

            $botToken = $this->settings->get('telegram_bot_token');
            if (!$botToken) {
                Log::error('❌ Cannot send broadcast message: bot token is not set.');
                return false;
            }

            // ✅ اصلاح: استفاده از Telegram facade بدون بک‌اسلش اضافی
            Telegram::setAccessToken($botToken);

            // بررسی هوشمند نوع فرمت پیام (اگر شامل تگ‌های HTML باشد)
            $isHtml = (str_contains($message, '<tg-emoji') || str_contains($message, '<b>') || str_contains($message, '<i>') || str_contains($message, '<code>'));

            if ($isHtml) {
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                ]);
            } else {
                $title = "📢 <b>اعلان ویژه از سوی تیم مدیریت</b>";
                $divider = str_repeat('━', 20);
                $footer = "💠 <b>با تشکر از همراهی شما</b> 💠";

                $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
                $fullMessage = "{$title}\n\n{$divider}\n\n📝 <b>{$safeMessage}</b>\n\n{$divider}\n\n{$footer}";

                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => $fullMessage,
                    'parse_mode' => 'HTML',
                ]);
            }

            Log::info("✅ Broadcast message sent successfully to chat {$chatId}");
            return true;
        } catch (\Exception $e) {
            Log::warning("⚠️ Failed to send broadcast message to user {$chatId}: " . $e->getMessage());
            return false;
        }
    }

    public function sendBroadcastToChannel(string $channelId, string $message): bool
    {
        try {
            if ($this->settings->isEmpty()) {
                $this->settings = Setting::all()->pluck('value', 'key');
            }

            $botToken = $this->settings->get('telegram_bot_token');
            if (!$botToken) {
                Log::error('❌ Cannot send channel broadcast: bot token is not set.');
                return false;
            }

            Telegram::setAccessToken($botToken);

            // ارسال پیام مستقیم به کانال با پارس مد HTML برای پشتیبانی از تگ‌های اموجی پرمیوم <tg-emoji>
            Telegram::sendMessage([
                'chat_id' => $channelId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            Log::info("✅ Channel broadcast message sent successfully to channel {$channelId}");
            return true;
        } catch (\Exception $e) {
            Log::warning("⚠️ Failed to send broadcast message to channel {$channelId}: " . $e->getMessage());
            return false;
        }
    }

    public function sendToLogChannel(string $message, string $parseMode = 'HTML', $replyMarkup = null): bool
    {
        try {
            if ($this->settings->isEmpty()) {
                $this->settings = Setting::all()->pluck('value', 'key');
            }

            $logChannelId = $this->settings->get('telegram_log_channel_id');
            $botToken = $this->settings->get('telegram_bot_token');

            if (!$logChannelId || !$botToken) {
                return false;
            }

            Telegram::setAccessToken(trim($botToken, '"\' '));

            $params = [
                'chat_id' => $logChannelId,
                'text' => $message,
                'parse_mode' => $parseMode,
                'disable_web_page_preview' => true,
            ];
            if ($replyMarkup) {
                $params['reply_markup'] = $replyMarkup;
            }

            Telegram::sendMessage($params);

            Log::info("✅ Log notification sent successfully to channel {$logChannelId}");
            return true;
        } catch (\Throwable $e) {
            Log::warning("⚠️ Failed to send log message to channel: " . $e->getMessage());
            return false;
        }
    }

    public function sendSingleMessageToUser(string $chatId, string $message): bool
    {
        try {
            if ($this->settings->isEmpty()) { // ✅ اصلاح
                $this->settings = Setting::all()->pluck('value', 'key');
            }
            $botToken = $this->settings->get('telegram_bot_token');
            if (!$botToken) {
                Log::error('Cannot send single Telegram message: bot token is not set.');
                return false;
            }
            Telegram::setAccessToken($botToken);

            $header = "📢 *پیام فوری از مدیریت*";
            // ✅ اصلاح: نقطه در MarkdownV2 باید escape شود اما توی کپشن نیاز نیست
            $notice = "⚠️ این یک پیام اطلاع‌رسانی یک‌طرفه از پنل ادمین است و پاسخ دادن به آن در این چت، پیگیری نخواهد شد.";

            $adminMessageLines = explode("\n", $message);
            $formattedMessage = implode("\n", array_map(fn($line) => "> " . trim($line), $adminMessageLines));

            $fullMessage = "{$header}\n\n{$this->escape($notice)}\n\n{$formattedMessage}";

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $fullMessage,
                'parse_mode' => 'MarkdownV2',
            ]);

            Log::info("Admin sent message to user {$chatId}.", ['message' => $message]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send single Telegram message: ' . $e->getMessage(), ['chat_id' => $chatId, 'message' => $message]);
            return false;
        }
    }

    public function handle(Request $request)
    {
        Log::info("BOT_WEBHOOK_RECEIVED", ['ip' => $request->ip()]);
        try {
            $this->settings = Setting::all()->pluck('value', 'key');
            $botToken = $this->settings->get('telegram_bot_token');

            if ($botToken) {
                // پاکسازی کوتیشن‌های احتمالی از توکن
                $botToken = trim($botToken, '"\' ');
            }
            
            if (!$botToken) {
                $botToken = config('telegrambot.bot_token');
                if ($botToken) $botToken = trim($botToken, '"\' ');
            }

            if (!$botToken) {
                Log::warning('Telegram bot token is not set.');
                return response('ok', 200);
            }

            Log::info("BOT_TOKEN_FOUND", ['token_prefix' => substr($botToken, 0, 5)]);
            
            Telegram::setAccessToken($botToken);
            $update = Telegram::getWebhookUpdate();
            
            Log::info("UPDATE_OBJECT_CREATED", [
                'type' => $update->detectType(),
                'has_message' => $update->has('message'),
                'is_callback' => $update->isType('callback_query')
            ]);

            if ($update->isType('callback_query')) {
                Log::info("PROCESSING_CALLBACK_QUERY");
                $this->handleCallbackQuery($update);
            } elseif ($update->has('message')) {
                Log::info("PROCESSING_MESSAGE");
                $message = $update->getMessage();
                if ($message->has('text')) {
                    Log::info("MESSAGE_HAS_TEXT", ['text' => $message->getText()]);
                    $this->handleTextMessage($update);
                } elseif ($message->has('photo')) {
                    Log::info("MESSAGE_HAS_PHOTO");
                    $this->handlePhotoMessage($update);
                }
            } else {
                Log::info("UPDATE_TYPE_NOT_HANDLED", ['type' => $update->detectType()]);
            }
        } catch (\Exception $e) {
            file_put_contents(storage_path('logs/bot_debug.log'), date('Y-m-d H:i:s') . " - EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
            Log::error('Telegram Bot Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => substr($e->getTraceAsString(), 0, 500)
            ]);
        }
        
        Log::info("WEBHOOK_FINISHED");
        return response('ok', 200);
    }

    protected function handleTextMessage($update)
    {
        $message = $update->getMessage();
        $chatId = $message->getChat()->getId();
        $text = trim($message->getText() ?? '');

        Log::info("HTM_START", ['chat_id' => $chatId, 'text' => $text]);

        // تشخیص اموجی پرمیوم ارسالی برای نمایش شناسه (Emoji ID)
        if ($message->has('entities')) {
            try {
                $entities = $message->getEntities();
                if ($entities) {
                    foreach ($entities as $entity) {
                        if (isset($entity['type']) && $entity['type'] === 'custom_emoji') {
                            $emojiId = $entity['custom_emoji_id'] ?? null;
                            if ($emojiId) {
                                Telegram::sendMessage([
                                    'chat_id' => $chatId,
                                    'text' => "💎 *شناسه اموجی پرمیوم ارسالی شما:*\n\n" .
                                              "کد شناسایی: `" . $this->escape($emojiId) . "`\n\n" .
                                              "💡 _می‌توانید این کد عددی را کپی کرده و در پنل ادمین برای دکمه یا پیام دلخواه خود تنظیم کنید._",
                                    'parse_mode' => 'MarkdownV2',
                                    'reply_to_message_id' => $message->getMessageId()
                                ]);
                                return;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Error parsing entities: ' . $e->getMessage());
            }
        }
        
        $user = User::where('telegram_chat_id', $chatId)->first();
        Log::info("HTM_USER_CHECK", ['found' => (bool)$user]);

        if ($user && !Str::startsWith($user->bot_state, 'captcha_')) {
            $member = $this->isUserMemberOfChannel($user);
            Log::info("HTM_MEMBERSHIP_CHECK", ['is_member' => $member]);
            if (!$member) {
                Log::info("HTM_MEMBERSHIP_REQUIRED_STOP");
                $this->showChannelRequiredMessage($chatId);
                return;
            }
        }

        if (!$user) {
            Log::info("HTM_CREATING_NEW_USER");
            $userFirstName = $message->getFrom()->getFirstName() ?? 'کاربر';
            $password = Str::random(10);
            
            $referrerId = null;
            if (Str::startsWith($text, '/start ')) {
                $referralCode = Str::after($text, '/start ');
                $referrer = User::where('referral_code', $referralCode)->first();
                if ($referrer) {
                    $referrerId = $referrer->id;
                }
            }

            $user = User::create([
                'name' => $userFirstName,
                'email' => $chatId . '@telegram.user',
                'password' => Hash::make($password),
                'telegram_chat_id' => $chatId,
                'referral_code' => Str::random(8),
                'bot_state' => 'captcha_lang|' . ($referrerId ?? ''),
            ]);

            $refInfo = $referrerId ? " (معرف: <code>{$referrerId}</code>)" : " (مستقیم)";
            $this->sendToLogChannel(
                "👤 <b>عضویت کاربر جدید در ربات</b>\n\n" .
                "🔹 <b>نام کاربر:</b> <a href=\"tg://user?id={$chatId}\">" . htmlspecialchars($userFirstName) . "</a>\n" .
                "🔹 <b>آیدی تلگرام:</b> <code>{$chatId}</code>\n" .
                "🔹 <b>نحوه ورود:</b> {$refInfo}\n" .
                "⏰ <b>زمان:</b> " . now()->format('Y-m-d H:i:s')
            );

            $this->sendCaptchaLanguageSelection($chatId, $referrerId);
            return;
        }

        if ($user->bot_state) {
            Log::info("HTM_BOT_STATE_ACTIVE", ['state' => $user->bot_state]);
            
            if (Str::startsWith($user->bot_state, 'captcha_')) {
                if (Str::startsWith($text, '/start')) {
                    $parts = explode('|', $user->bot_state);
                    $referrerId = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : null;
                    
                    if (Str::startsWith($text, '/start ')) {
                        $referralCode = Str::after($text, '/start ');
                        $referrer = User::where('referral_code', $referralCode)->first();
                        if ($referrer && $referrer->id !== $user->id) {
                            $referrerId = $referrer->id;
                            $user->update(['bot_state' => 'captcha_lang|' . $referrerId]);
                        }
                    }
                    $this->sendCaptchaLanguageSelection($chatId, $referrerId);
                    return;
                }

                $lang = Str::contains($user->bot_state, '|en|') ? 'en' : 'fa';
                $msg = $lang === 'en'
                    ? "⚠️ Please solve the captcha first."
                    : "⚠️ لطفاً ابتدا کپچا را حل کنید.";
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => $msg
                ]);
                return;
            }

            // هر deep-link معتبر /start باید وضعیت نیمه‌کاره قبلی را پاک کند.
            if (Str::startsWith($text, '/start')) {
                Log::info("HTM_RESETTING_STATE_BY_START");
                $user->update(['bot_state' => null]);
            } 

            else {
                if ($user->bot_state === 'awaiting_deposit_amount') {
                    $this->processDepositAmount($user, $text);
                    return;
                } elseif (Str::startsWith($user->bot_state, 'awaiting_new_ticket_') || Str::startsWith($user->bot_state, 'awaiting_ticket_reply')) {
                    $this->processTicketConversation($user, $text, $update);
                    return;
                } elseif (Str::startsWith($user->bot_state, 'awaiting_discount_code|')) {
                    $orderId = Str::after($user->bot_state, 'awaiting_discount_code|');
                    $this->processDiscountCode($user, $orderId, $text);
                    return;
                } elseif (Str::startsWith($user->bot_state, 'awaiting_username_for_order|')) {
                    $planId = Str::after($user->bot_state, 'awaiting_username_for_order|');
                    $this->processUsername($user, $planId, $text);
                    return;
                } elseif (Str::startsWith($user->bot_state, 'waiting_receipt_')) {
                    Log::info("HTM_WAITING_RECEIPT_TEXT_SUBMIT");
                    $orderId = Str::after($user->bot_state, 'waiting_receipt_');
                    $this->processTextReceiptSubmission($user, $orderId, $text, $chatId);
                    return;
                }
                
                Log::info("HTM_BOT_STATE_UNKNOWN_STOP");
                return;
            }
        }

        // نرمال‌سازی متن برای دکمه‌هایی که ممکن است نیم‌فاصله داشته باشند یا نداشته باشند
        $normalizedText = str_replace(['‌', ' ', 'ـ'], '', $text);

        Log::info("HTM_SWITCH_START", ['normalized' => $normalizedText]);

        $normalizedCommand = Str::lower($text);

        if (in_array($normalizedCommand, ['status', '/status'], true) || $normalizedText === 'وضعیت') {
            $this->sendMyServices($user);
        } elseif (str_contains($normalizedText, 'تهیهاشتراک') || str_contains($normalizedText, 'خریدسرویس') || $text === '/plans' || $text === '/shop') {
            $this->sendPlans($chatId);
        } elseif (str_contains($normalizedText, 'سرویسهایمن') || str_contains($normalizedText, 'سرویس‌هایمن') || $text === '/myservices') {
            $this->sendMyServices($user);
        } elseif (str_contains($normalizedText, 'کیفپول') || $text === '/wallet') {
            $this->sendWalletMenu($user);
        } elseif (str_contains($normalizedText, 'تراکنش') || $text === '/transactions') {
            $this->sendTransactions($user);
        } elseif (str_contains($normalizedText, 'پشتیبانی') || $text === '/support') {
            $this->showSupportMenu($user);
        } elseif (str_contains($normalizedText, 'کسبدرآمد') || str_contains($normalizedText, 'دعوتازدوس') || $text === '/referral') {
            $this->sendReferralMenu($user);
        } elseif (str_contains($normalizedText, 'آموزشاتصال') || str_contains($normalizedText, 'راهنمایاتصال') || $text === '/tutorials') {
            $this->sendTutorialsMenu($chatId);
        } elseif (str_contains($normalizedText, 'تسترایگان') || str_contains($normalizedText, 'اکانتتست') || $text === '/trial') {
            $telegramUsername = $message->getFrom()->getUsername();
            $this->handleTrialRequest($user, $telegramUsername);
        } elseif ($text === '/profile') {
            $this->sendProfile($user);
        } elseif ($text === '/about' || str_contains($normalizedText, 'درباره')) {
            $this->sendAbout($chatId);
        } elseif ($text === '/start trial') {
            $telegramUsername = $message->getFrom()->getUsername();
            $this->handleTrialRequest($user, $telegramUsername);
        } elseif (preg_match('/^\/start\s+plan_(\d+)$/', $text, $matches)) {
            $plan = Plan::whereKey((int) $matches[1])->where('is_active', true)->first();
            if (!$plan) {
                $this->sendPlans($chatId);
                return;
            }

            $isMultiLocationEnabled = filter_var(
                $this->settings->get('enable_multilocation', false),
                FILTER_VALIDATE_BOOLEAN
            );
            if ($isMultiLocationEnabled && class_exists('Modules\MultiServer\Models\Location')) {
                $this->promptForLocation($user, $plan->id, null);
                return;
            }

            $autoUsername = 'u' . $user->id . 'o' . Str::random(5);
            $this->startPurchaseProcess($user, $plan->id, $autoUsername);
            return;
        } elseif ($text === '/start shop') {
            $this->sendPlans($chatId);
        } elseif (Str::startsWith($text, '/start')) {
            Log::info("HTM_HANDLING_START_COMMAND");
            $this->sendToLogChannel(
                "🚀 <b>ورود کاربر به منوی اصلی (/start)</b>\n\n" .
                "🔹 <b>کاربر:</b> <a href=\"tg://user?id={$chatId}\">" . htmlspecialchars($user->name ?? 'کاربر') . "</a>\n" .
                "🔹 <b>آیدی:</b> <code>{$chatId}</code>\n" .
                "⏰ <b>زمان:</b> " . now()->format('Y-m-d H:i:s')
            );
            // پاکسازی منوهای قبلی + حذف کیبورد قدیمی (بی‌صدا)
            $this->cleanUiMessages($chatId);
            $this->removeReplyKeyboard($chatId);
            $this->sendHome($user, $chatId);
            Log::info("HTM_START_COMMAND_FINISHED");
        } else {
            // اگر کاربر مستقیماً عدد فرستاد، آن را به عنوان درخواست شارژ کیف پول پردازش کن
            $cleanNumber = preg_replace('/[^\d]/', '', $text);
            if (is_numeric($cleanNumber) && (int)$cleanNumber >= 10000) {
                Log::info("HTM_FALLBACK_NUMBER_DEPOSIT", ['amount' => $cleanNumber]);
                $this->processDepositAmount($user, $cleanNumber);
                return;
            }

            Log::info("HTM_DEFAULT_CASE");
            $this->sendOrEditMessage(
                $chatId,
                "\u{200F}" . $this->escape("دستور نامشخصه. از منو استفاده کن."),
                $this->getMainMenuKeyboard()
            );
        }
    }

    protected function processUsername($user, $planId, $username)
    {
        $username = trim($username);

        if (strlen($username) < 3) {
            Telegram::sendMessage([
                'chat_id' => $user->telegram_chat_id,
                'text' => $this->escape("❌ نام کاربری باید حداقل ۳ کاراکتر باشد."),
                'parse_mode' => 'MarkdownV2'
            ]);
            $this->promptForUsername($user, $planId);
            return;
        }

        if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
            Telegram::sendMessage([
                'chat_id' => $user->telegram_chat_id,
                'text' => $this->escape("❌ نام کاربری فقط می‌تواند شامل حروف انگلیسی و اعداد باشد."),
                'parse_mode' => 'MarkdownV2'
            ]);
            $this->promptForUsername($user, $planId);
            return;
        }

        // بررسی یکتا بودن نام کاربری (فقط در سفارش‌های پرداخت شده)
        $existingOrder = Order::where('panel_username', $username)->where('status', 'paid')->first();
        if ($existingOrder) {
            Telegram::sendMessage([
                'chat_id' => $user->telegram_chat_id,
                'text' => $this->escape("❌ این نام کاربری قبلاً استفاده شده است. لطفاً نام دیگری وارد کنید."),
                'parse_mode' => 'MarkdownV2'
            ]);
            $this->promptForUsername($user, $planId);
            return;
        }

        $this->startPurchaseProcess($user, $planId, $username);
    }

    protected function promptForUsername($user, $planId, $messageId = null, $locationId = null)
    {
        $newState = 'awaiting_username_for_order|' . $planId;

        if ($locationId) {
            $newState .= '|selected_loc:' . $locationId;
        }
        elseif ($user->bot_state && Str::contains($user->bot_state, 'selected_loc:')) {
            $parts = explode('|', $user->bot_state);
            foreach ($parts as $part) {
                if (Str::startsWith($part, 'selected_loc:')) {
                    $newState .= '|' . $part;
                    break;
                }
            }
        }

        $user->update(['bot_state' => $newState]);

        $keyboard = Keyboard::make()->inline()->row([$this->makeInlineButton(['text' => '❌ انصراف', 'callback_data' => '/cancel_action', 'style' => 'danger'])]);
        
        $message = "👤 *شناسه اختصاصی شما*\n\n";
        $message .= $this->escape("برای ساخت سرویس روزنه، یک نام کاربری (شناسه) منحصربه‌فرد انتخاب کنید. این نام هویت شما در شبکه است.") . "\n\n";
        $message .= "⚖️ *" . $this->escape("قوانین ثبت شناسه:") . "*\n";
        $message .= "▫️ " . $this->escape("مجاز به استفاده از حروف انگلیسی و اعداد.") . "\n";
        $message .= "▫️ " . $this->escape("حداقل شامل ۳ کاراکتر باشد.") . "\n\n";
        $message .= "💡 *" . $this->escape("نمونه‌های پیشنهادی:") . "* `looka123` " . $this->escape("یا") . " `irangozar`\n\n";
        $message .= "✏️ *" . $this->escape("لطفاً هم‌اکنون شناسه مدنظر خود را تایپ و ارسال کنید:") . "*";

        $this->sendOrEditMessage($user->telegram_chat_id, $message, $keyboard, $messageId);
    }

    /**
     * ارسال مجدد لینک اکانت تست (برای کپی آسان)
     */
    protected function handleTrialCopyLink($user, $messageId = null)
    {
        try {
            $link = \Illuminate\Support\Facades\Cache::get("trial_link_{$user->id}");

            if (!$link) {
                Telegram::sendMessage([
                    'chat_id' => $user->telegram_chat_id,
                    'text' => $this->escape("❌ لینک اکانت تست منقضی شده یا یافت نشد.\nلطفاً اکانت تست جدیدی دریافت کنید."),
                    'parse_mode' => 'MarkdownV2',
                    'reply_markup' => Keyboard::make()->inline()->row([
                        $this->makeInlineButton(['text' => '🧪 دریافت اکانت تست', 'callback_data' => 'trial_request'])
                    ])
                ]);
                return;
            }

            Telegram::sendMessage([
                'chat_id' => $user->telegram_chat_id,
                'text' => "📋 *لینک اکانت تست شما:*\n\n`{$link}`\n\n" . $this->escape("روی لینک بالا کلیک کنید تا کپی شود."),
                'parse_mode' => 'MarkdownV2',
                'reply_markup' => Keyboard::make()->inline()->row([
                    $this->makeInlineButton(['text' => '⬅️ بازگشت به منو', 'callback_data' => '/start'])
                ])
            ]);

        } catch (\Exception $e) {
            Log::error('Trial copy link error: ' . $e->getMessage());
        }
    }

    /**
     * ارسال QR Code برای اکانت تست
     */
    protected function sendTrialQRCode($user, $messageId = null)
    {
        try {
            $link = \Illuminate\Support\Facades\Cache::get("trial_link_{$user->id}");

            if (!$link) {
                Telegram::sendMessage([
                    'chat_id' => $user->telegram_chat_id,
                    'text' => $this->escape("❌ لینک اکانت تست منقضی شده."),
                    'parse_mode' => 'MarkdownV2'
                ]);
                return;
            }

            $tempFile = null;
            try {
                $qrParams = [
                    'size' => '400x400',
                    'data' => $link,
                    'ecc' => 'M',
                    'margin' => 10,
                    'format' => 'png'
                ];

                $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?" . http_build_query($qrParams);

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $qrUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_TIMEOUT => 30
                ]);

                $qrData = curl_exec($ch);
                curl_close($ch);

                if (!$qrData) throw new \Exception("QR generation failed");

                $tempDir = storage_path('app/temp');
                if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);

                $tempFile = $tempDir . '/qr_trial_' . $user->id . '_' . time() . '.png';
                file_put_contents($tempFile, $qrData);

                Telegram::sendPhoto([
                    'chat_id' => $user->telegram_chat_id,
                    'photo' => InputFile::create($tempFile),
                    'caption' => $this->escape("📱 QR Code اکانت تست\n\nلینک:\n`{$link}`"),
                    'parse_mode' => 'MarkdownV2'
                ]);

            } finally {
                if ($tempFile && file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }

        } catch (\Exception $e) {
            Log::error('Trial QR error: ' . $e->getMessage());
            Telegram::sendMessage([
                'chat_id' => $user->telegram_chat_id,
                'text' => $this->escape("❌ خطا در ساخت QR Code"),
                'parse_mode' => 'MarkdownV2'
            ]);
        }
    }

    protected function handleCallbackQuery($update)
    {
        $callbackQuery = $update->getCallbackQuery();
        $chatId = $callbackQuery->getMessage()->getChat()->getId();
        $messageId = $callbackQuery->getMessage()->getMessageId();
        $data = $callbackQuery->getData();

        // 1. Process Admin action callbacks immediately to bypass the User check (needed for channels/groups)
        if (Str::startsWith($data, 'admin_approve_order_')) {
            $orderId = Str::after($data, 'admin_approve_order_');
            $this->handleAdminApproveOrder($chatId, $orderId, $callbackQuery->getId(), $messageId, $callbackQuery->getFrom());
            return;
        } elseif (Str::startsWith($data, 'admin_reject_order_')) {
            $orderId = Str::after($data, 'admin_reject_order_');
            $this->handleAdminRejectOrder($chatId, $orderId, $callbackQuery->getId(), $messageId, $callbackQuery->getFrom());
            return;
        } elseif (Str::startsWith($data, 'admin_reason_')) {
            $payload = Str::after($data, 'admin_reason_');
            $orderId = Str::before($payload, '_');
            $reasonKey = Str::after($payload, '_');
            $this->handleAdminReasonSelection($chatId, $orderId, $reasonKey, $callbackQuery->getId(), $messageId, $callbackQuery->getFrom());
            return;
        }

        if (Str::startsWith($data, 'captcha_set_lang|')) {
            $this->handleCaptchaSetLang($chatId, $data, $messageId, $callbackQuery->getId());
            return;
        } elseif (Str::startsWith($data, 'captcha_verify|')) {
            $this->handleCaptchaVerify($chatId, $data, $messageId, $callbackQuery->getId());
            return;
        } elseif (Str::startsWith($data, 'captcha_change_lang|')) {
            $referrerId = Str::after($data, 'captcha_change_lang|');
            $user = User::where('telegram_chat_id', $chatId)->first();
            if ($user) {
                $user->update(['bot_state' => 'captcha_lang|' . $referrerId]);
                try {
                    Telegram::answerCallbackQuery(['callback_query_id' => $callbackQuery->getId()]);
                } catch (\Exception $e) {}
                $this->sendCaptchaLanguageSelection($chatId, $referrerId, $messageId);
            }
            return;
        }

        $user = User::where('telegram_chat_id', $chatId)->first();

        if ($user && Str::startsWith($user->bot_state, 'captcha_')) {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId(),
                'text' => Str::contains($user->bot_state, '|en|') 
                    ? '⚠️ Please solve the captcha first.' 
                    : '⚠️ لطفاً ابتدا کپچا را حل کنید.',
                'show_alert' => true
            ]);
            return;
        }

        if ($user && !$this->isUserMemberOfChannel($user)) {
            $this->showChannelRequiredMessage($chatId, $messageId);
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId(),
                'text' => 'ابتدا باید در کانال عضو شوید!',
                'show_alert' => true
            ]);
            return;
        }

        if (Str::startsWith($data, 'show_duration_')) {
            $durationDays = (int)Str::after($data, 'show_duration_');
            $this->sendPlansByDuration($chatId, $durationDays, $messageId);
            return;
        }



        if (!$user) {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => $this->escape("❌ کاربر یافت نشد. لطفاً با دستور /start ربات را مجدداً راه‌اندازی کنید."), 'parse_mode' => 'MarkdownV2']);
            return;
        }

        try {
            Telegram::answerCallbackQuery(['callback_query_id' => $callbackQuery->getId()]);
        } catch (\Exception $e) { Log::warning('Could not answer callback query: ' . $e->getMessage()); }

        if (!Str::startsWith($data, ['/deposit_custom', '/support_new', 'reply_ticket_', 'enter_discount_'])) {
            $user->update(['bot_state' => null]);
        }

        if (Str::startsWith($data, 'select_loc_')) {
            $parts = explode('_', $data);

            if (count($parts) >= 5) {
                $locationId = $parts[2];
                $planId = $parts[4];

                if (class_exists('Modules\MultiServer\Models\Location')) {
                    $location = \Modules\MultiServer\Models\Location::find($locationId);
                    if ($location) {
                        $totalCapacity = $location->servers()->where('is_active', true)->sum('capacity');
                        $totalUsed = $location->servers()->where('is_active', true)->sum('current_users');

                        if ($totalUsed >= $totalCapacity) {
                            $settings = Setting::all()->pluck('value', 'key');
                            $msg = $settings->get('ms_full_location_message') ?? "❌ ظرفیت تکمیل است.";

                            Telegram::answerCallbackQuery([
                                'callback_query_id' => $callbackQuery->getId(),
                                'text' => $msg,
                                'show_alert' => true
                            ]);
                            return;
                        }
                    }
                }
                
                $autoUsername = 'u' . $user->id . 'o' . Str::random(5);
                $user->update(['bot_state' => "selected_loc:{$locationId}"]);
                $this->startPurchaseProcess($user, $planId, $autoUsername, $messageId);
                return;
            }
        }

        if (Str::startsWith($data, 'buy_plan_')) {
            $planId = Str::after($data, 'buy_plan_');

            $isMultiLocationEnabled = filter_var(
                $this->settings->get('enable_multilocation', false),
                FILTER_VALIDATE_BOOLEAN
            );

            if ($isMultiLocationEnabled && class_exists('Modules\MultiServer\Models\Location')) {
                $this->promptForLocation($user, $planId, $messageId);
                return;
            }

            $autoUsername = 'u' . $user->id . 'o' . Str::random(5);
            $this->startPurchaseProcess($user, $planId, $autoUsername, $messageId);
            return;
        }
        elseif ($data === 'trial_request') {
            $telegramUsername = $callbackQuery->getFrom()->getUsername();
            $this->handleTrialRequest($user, $telegramUsername, $callbackQuery->getId(), $messageId);
            return;
        }
        elseif (Str::startsWith($data, 'pay_wallet_')) {
            $input = Str::after($data, 'pay_wallet_');
            $this->processWalletPayment($user, $input, $messageId);
        } elseif (Str::startsWith($data, 'pay_card_')) {
            $orderId = Str::after($data, 'pay_card_');
            $this->sendCardPaymentInfo($chatId, $orderId, $messageId);
        } elseif (Str::startsWith($data, 'pay_bank_')) {
            $orderId = Str::after($data, 'pay_bank_');
            $this->sendBankGatewayInfo($chatId, $orderId, $messageId);
        } elseif (Str::startsWith($data, 'pay_crypto_')) {
            $orderId = Str::after($data, 'pay_crypto_');
            $this->sendGatewayPaymentInfo($chatId, $orderId, 'crypto', $messageId);
        } elseif (Str::startsWith($data, 'pay_methods_')) {
            $orderId = Str::after($data, 'pay_methods_');
            $order = Order::find($orderId);
            if ($order && $order->user_id === $user->id) {
                $this->showInvoice($user, $order, $messageId);
            }
        } // Already handled at the beginning of handleCallbackQuery
        elseif (Str::startsWith($data, 'show_service_')) {
             $orderId = Str::after($data, 'show_service_');
             // اگر کاربر روی سرویس کلیک کرد، مستقیماً QR را نشان بده
             $this->showServiceDetailsWithQR($user, $orderId, $messageId);
        }

        elseif (Str::startsWith($data, 'copy_trial_link_')) {
            $userId = Str::after($data, 'copy_trial_link_');
            $this->handleTrialCopyLink($user, $messageId);
        }
        elseif (Str::startsWith($data, 'qr_trial_')) {
            $this->sendTrialQRCode($user, $messageId);
        }
        elseif ($data === 'get_referral_banner') {
            $this->sendReferralBanner($chatId, $user);
        }

        elseif (Str::startsWith($data, 'enter_discount_')) {
            $orderId = Str::after($data, 'enter_discount_');
            $this->promptForDiscount($user, $orderId, $messageId);
        }
        elseif (Str::startsWith($data, 'copy_link_')) {
            $orderId = Str::after($data, 'copy_link_');
            $this->handleCopyLinkRequest($user, $orderId);
        }
        elseif (Str::startsWith($data, 'direct_configs_order_')) {
            $orderId = Str::after($data, 'direct_configs_order_');
            $this->sendDirectConfigsForOrder($user, $orderId);
        }
        elseif (Str::startsWith($data, 'direct_configs_trial_')) {
            $this->sendDirectConfigsForTrial($user);
        }

        elseif (Str::startsWith($data, 'remove_discount_')) {
            $orderId = Str::after($data, 'remove_discount_');
            $this->removeDiscount($user, $orderId, $messageId);
        } elseif (Str::startsWith($data, 'qrcode_order_')) {
            $orderId = Str::after($data, 'qrcode_order_');
            $this->sendQRCodeForOrder($user, $orderId);
        } elseif (Str::startsWith($data, 'renew_order_')) {
            $originalOrderId = Str::after($data, 'renew_order_');
            $this->startRenewalPurchaseProcess($user, $originalOrderId, $messageId);
        } elseif (Str::startsWith($data, 'renew_pay_wallet_')) {
            $originalOrderId = Str::after($data, 'renew_pay_wallet_');
            $this->processRenewalWalletPayment($user, $originalOrderId, $messageId);
        } elseif (Str::startsWith($data, 'renew_pay_card_')) {
            $originalOrderId = Str::after($data, 'renew_pay_card_');
            $this->handleRenewCardPayment($user, $originalOrderId, $messageId);
        } elseif (Str::startsWith($data, 'deposit_amount_')) {
            $amount = Str::after($data, 'deposit_amount_');
            $this->processDepositAmount($user, $amount, $messageId);
        } elseif ($data === '/deposit_custom') {
            $this->promptForCustomDeposit($user, $messageId);
        } elseif (Str::startsWith($data, 'close_ticket_')) {
            $ticketId = Str::after($data, 'close_ticket_');
            $this->closeTicket($user, $ticketId, $messageId, $callbackQuery->getId());
        } elseif (Str::startsWith($data, 'reply_ticket_')) {
            $ticketId = Str::after($data, 'reply_ticket_');
            $this->promptForTicketReply($user, $ticketId, $messageId);
        } elseif ($data === '/support_new') {
            $this->promptForNewTicket($user, $messageId);
        } elseif ($data === 'transfer_ref_traffic') {
            $this->handleTransferTrafficRequest($user, $messageId, $callbackQuery->getId());
        } elseif (Str::startsWith($data, 'do_transfer_ref_')) {
            $orderId = Str::after($data, 'do_transfer_ref_');
            $this->processTrafficTransfer($user, $orderId, $messageId, $callbackQuery->getId());
        } else {
            switch ($data) {
                case '/start':
                    $this->removeReplyKeyboard($chatId);
                    $this->sendHome($user, $chatId, $messageId);
                    break;
                case '/profile':
                    $this->sendProfile($user, $messageId);
                    break;
                case '/more':
                    $this->sendMoreMenu($chatId, $messageId);
                    break;
                case '/plans': $this->sendPlans($chatId, $messageId); break;
                case '/my_services': $this->sendMyServices($user, $messageId); break;
                case '/wallet': $this->sendWalletMenu($user, $messageId); break;
                case '/referral': $this->sendReferralMenu($user, $messageId); break;
                case '/support_menu': $this->showSupportMenu($user, $messageId); break;
                case '/deposit': $this->showDepositOptions($user, $messageId); break;
                case '/transactions': $this->sendTransactions($user, $messageId); break;
                case '/tutorials': $this->sendTutorialsMenu($chatId, $messageId); break;
                case '/about': $this->sendAbout($chatId, $messageId); break;
                case '/tutorial_android': $this->sendTutorial('android', $chatId, $messageId); break;
                case '/tutorial_ios': $this->sendTutorial('ios', $chatId, $messageId); break;
                case '/tutorial_windows': $this->sendTutorial('windows', $chatId, $messageId); break;
                case '/check_membership':
                    if ($this->isUserMemberOfChannel($user)) {
                        Telegram::answerCallbackQuery([
                            'callback_query_id' => $callbackQuery->getId(),
                            'text' => 'عضویت شما تأیید شد!',
                            'show_alert' => false
                        ]);
                        try { Telegram::deleteMessage(['chat_id' => $chatId, 'message_id' => $messageId]); } catch (\Exception $e) {}
                        Telegram::sendMessage([
                            'chat_id' => $chatId,
                            'text' => 'خوش آمدید! حالا می‌توانید از ربات استفاده کنید.',
                            'reply_markup' => $this->getMainMenuKeyboard()
                        ]);
                    } else {
                        Telegram::answerCallbackQuery([
                            'callback_query_id' => $callbackQuery->getId(),
                            'text' => 'هنوز عضو کانال نشده‌اید. لطفاً اول عضو شوید.',
                            'show_alert' => true
                        ]);
                        $this->showChannelRequiredMessage($chatId, $messageId);
                    }
                    break;

                case '/cancel_action':
                    $user->update(['bot_state' => null]);
                    try { Telegram::deleteMessage(['chat_id' => $chatId, 'message_id' => $messageId]); } catch (\Exception $e) {}
                    Telegram::sendMessage([
                        'chat_id' => $chatId,
                        'text' => '✅ عملیات لغو شد.',
                        'reply_markup' => $this->getMainMenuKeyboard(),
                    ]);
                    break;
                default:
                    Log::warning('Unknown callback data received:', ['data' => $data, 'chat_id' => $chatId]);
                    Telegram::sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'دستور نامعتبر.',
                        'reply_markup' => $this->getMainMenuKeyboard(),
                    ]);
                    break;
            }
        }
    }

    protected function promptForLocation($user, $planId, $messageId)
    {
        $settings = Setting::all()->pluck('value', 'key');
        $showCapacity = filter_var($settings->get('ms_show_capacity', true), FILTER_VALIDATE_BOOLEAN);
        $hideFull = filter_var($settings->get('ms_hide_full_locations', false), FILTER_VALIDATE_BOOLEAN);

        $locations = \Modules\MultiServer\Models\Location::where('is_active', true)->with('servers')->get();

        $keyboard = Keyboard::make()->inline();
        $hasAvailableLocation = false;

        foreach ($locations as $loc) {
            $totalCapacity = $loc->servers->where('is_active', true)->sum('capacity');
            $totalUsed = $loc->servers->where('is_active', true)->sum('current_users');
            $remained = max(0, $totalCapacity - $totalUsed);
            $isFull = $remained <= 0;

            if ($isFull && $hideFull) {
                continue;
            }

            $hasAvailableLocation = true;
            $flag = $loc->flag ?? '🏳️';
            $btnText = "$flag {$loc->name}";

            if ($isFull) {
                $btnText .= " (تکمیل 🔒)";
            } elseif ($showCapacity) {
                $btnText .= " ({$remained} عدد)";
            }

            $keyboard->row([
                $this->makeInlineButton([
                    'text' => $btnText,
                    'callback_data' => "select_loc_{$loc->id}_plan_{$planId}"
                ])
            ]);
        }

        if (!$hasAvailableLocation) {
            Telegram::sendMessage([
                'chat_id' => $user->telegram_chat_id,
                'text' => $this->escape("❌ متأسفانه ظرفیت تمام سرورها تکمیل شده است."),
                'parse_mode' => 'MarkdownV2'
            ]);
            return;
        }

        $keyboard->row([$this->makeInlineButton(['text' => '❌ انصراف', 'callback_data' => '/cancel_action', 'style' => 'danger'])]);

        $this->sendOrEditMessage($user->telegram_chat_id, "🌍 *انتخاب لوکیشن*\n\nلطفاً کشور مورد نظر خود را انتخاب کنید:", $keyboard, $messageId);
    }

    protected function handlePhotoMessage($update)
    {
        $message = $update->getMessage();
        $chatId = $message->getChat()->getId();
        $user = User::where('telegram_chat_id', $chatId)->first();

        if ($user && !$this->isUserMemberOfChannel($user)) {
            $this->showChannelRequiredMessage($chatId);
            return;
        }

        if (!$user || !$user->bot_state) {
            $this->sendOrEditMainMenu($chatId, "❌ لطفاً ابتدا یک عملیات (مانند ثبت تیکت یا رسید) را شروع کنید.");
            return;
        }

        if (Str::startsWith($user->bot_state, 'awaiting_ticket_reply|') || Str::startsWith($user->bot_state, 'awaiting_new_ticket_message|')) {
            $text = $message->getCaption() ?? '[📎 فایل پیوست شد]';
            $this->processTicketConversation($user, $text, $update);
            return;
        }

        if (Str::startsWith($user->bot_state, 'waiting_receipt_')) {
            $orderId = Str::after($user->bot_state, 'waiting_receipt_');
            $order = Order::find($orderId);

            if ($order && $order->user_id === $user->id && $order->status === 'pending') {
                try {
                    $fileName = $this->savePhotoAttachment($update, 'receipts');
                    if (!$fileName) throw new \Exception("Failed to save photo attachment.");

                    $order->update(['card_payment_receipt' => $fileName]);
                    $user->update(['bot_state' => null]);

                    $this->sendUserReceiptConfirmation($chatId, $orderId);

                    $userLink = "<a href=\"tg://user?id={$user->telegram_chat_id}\">" . htmlspecialchars($user->name ?: 'کاربر') . " ({$user->telegram_chat_id})</a>";
                    $this->sendToLogChannel(
                        "🧾 <b>ثبت فیش واریزی جدید (تصویر فیش)</b>\n\n" .
                        "🔹 <b>شماره سفارش:</b> #{$orderId}\n" .
                        "🔹 <b>کاربر:</b> {$userLink}\n" .
                        "🔹 <b>مبلغ:</b> " . number_format($order->amount) . " تومان\n" .
                        "🔹 <b>نوع:</b> " . ($order->renews_order_id ? 'تمدید سرویس' : ($order->plan_id ? 'خرید سرویس' : 'شارژ کیف پول')) . "\n" .
                        "🔹 <b>وضعیت:</b> ⏳ ارسال شد به کانال تایید فیش‌ها برای تایید مدیریت\n" .
                        "⏰ <b>زمان:</b> " . now()->format('Y-m-d H:i:s')
                    );

                    // اطلاع‌رسانی به کانال فیش‌ها (در صورت تنظیم) یا به چت ادمین
                    $receiptChannelId = $this->settings->get('telegram_receipt_channel_id');
                    $adminChatId = $this->settings->get('telegram_admin_chat_id');
                    $targetChatId = !empty($receiptChannelId) ? $receiptChannelId : $adminChatId;
                    $destinations = array_filter([$targetChatId]);

                    $orderType = $order->renews_order_id ? 'تمدید سرویس' : ($order->plan_id ? 'خرید سرویس' : 'شارژ کیف پول');
                    $userLink = "<a href=\"tg://user?id={$user->telegram_chat_id}\">" . htmlspecialchars($user->name ?: 'کاربر') . " (لمس برای ورود به پی‌وی)</a>";

                    $adminCaption = "🧾 <b>رسید پرداخت جدید برای سفارش #{$orderId}</b>\n\n";
                    $adminCaption .= "👤 <b>کاربر:</b> {$userLink} (<code>{$user->telegram_chat_id}</code>)\n";
                    $adminCaption .= "💵 <b>مبلغ:</b> <code>" . number_format($order->amount) . " تومان</code>\n";
                    $adminCaption .= "📦 <b>نوع سفارش:</b> {$orderType}\n\n";
                    $adminCaption .= "👇 <i>جهت بررسی فیش از دکمه‌های زیر استفاده کنید:</i>";

                    $keyboardRows = [
                        [
                            $this->makeInlineButton(['text' => '✅ تایید پرداخت', 'callback_data' => "admin_approve_order_{$orderId}"]),
                            $this->makeInlineButton(['text' => '❌ رد پرداخت', 'callback_data' => "admin_reject_order_{$orderId}"])
                        ]
                    ];
                    if (!empty($user->username)) {
                        $keyboardRows[] = [
                            $this->makeInlineButton(['text' => '💬 پی‌وی کاربر (@' . ltrim($user->username, '@') . ')', 'url' => 'https://t.me/' . ltrim($user->username, '@')])
                        ];
                    }
                    $keyboard = Keyboard::make()->inline();
                    foreach ($keyboardRows as $r) {
                        $keyboard->row($r);
                    }

                    $photoPath = Storage::disk('public')->path($fileName);

                    foreach ($destinations as $targetChatId) {
                        if (!is_numeric($targetChatId)) continue;
                        try {
                            Telegram::sendPhoto([
                                'chat_id'      => $targetChatId,
                                'photo'        => InputFile::create($photoPath),
                                'caption'      => $adminCaption,
                                'parse_mode'   => 'HTML',
                                'reply_markup' => $keyboard
                            ]);
                        } catch (\Exception $e) {
                            Log::error("Failed to send receipt to {$targetChatId}: " . $e->getMessage());
                        }
                    }

                } catch (\Exception $e) {
                    Log::error("Receipt processing failed for order {$orderId}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                    Telegram::sendMessage(['chat_id' => $chatId, 'text' => "❌ خطا در پردازش رسید. لطفاً دوباره تلاش کنید."]);
                }
            } else {
                Telegram::sendMessage(['chat_id' => $chatId, 'text' => "❌ سفارش نامعتبر است یا در انتظار پرداخت نیست."]);
            }
        }
    }

    protected function sendUserReceiptConfirmation($chatId, $orderId = null)
    {
        $notice = \App\Services\ShiftScheduleService::getReceiptNotice();
        $orderInfo = $orderId ? "🔹 <b>شماره سفارش:</b> #{$orderId}\n" : "";

        $text = "✅ <b>رسید شما با موفقیت ثبت شد</b>\n\n" .
                $orderInfo .
                "⏳ سفارش شما در صف بررسی واحد مالی قرار گرفت.\n" .
                "به محض تایید پرداخت، سرویس شما فعال شده و مشخصات اتصال در همین چت برای شما ارسال خواهد شد.\n\n" .
                $notice . "\n\n" .
                "<i>از همراهی و شکیبایی شما سپاسگزاریم.</i> 🌸";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(['text' => '👨‍💻 ارتباط با پشتیبانی', 'url' => 'https://t.me/RoozanehHelp']),
                $this->makeInlineButton(['text' => '🏠 منوی اصلی', 'callback_data' => '/start', 'style' => 'primary']),
            ]);

        try {
            Telegram::sendMessage([
                'chat_id'      => $chatId,
                'text'         => $text,
                'parse_mode'   => 'HTML',
                'reply_markup' => $keyboard
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to send receipt confirmation to user: " . $e->getMessage());
        }
    }

    protected function processTextReceiptSubmission($user, $orderId, $text, $chatId)
    {
        $order = Order::find($orderId);

        if ($order && $order->user_id === $user->id && $order->status === 'pending') {
            try {
                $order->update(['card_payment_receipt' => 'text_receipt:' . $text]);
                $user->update(['bot_state' => null]);

                $this->sendUserReceiptConfirmation($chatId, $orderId);

                $userLink = "<a href=\"tg://user?id={$user->telegram_chat_id}\">" . htmlspecialchars($user->name ?: 'کاربر') . " ({$user->telegram_chat_id})</a>";
                $this->sendToLogChannel(
                    "🧾 <b>ثبت فیش واریزی جدید (متنی / شماره پیگیری)</b>\n\n" .
                    "🔹 <b>شماره سفارش:</b> #{$orderId}\n" .
                    "🔹 <b>کاربر:</b> {$userLink}\n" .
                    "🔹 <b>مبلغ:</b> " . number_format($order->amount) . " تومان\n" .
                    "🔹 <b>متن/کد پیگیری:</b> <code>" . htmlspecialchars($text) . "</code>\n" .
                    "🔹 <b>وضعیت:</b> ⏳ در انتظار تایید مدیریت\n" .
                    "⏰ <b>زمان:</b> " . now()->format('Y-m-d H:i:s')
                );

                // اطلاع‌رسانی به کانال فیش‌ها (در صورت تنظیم) یا به چت ادمین
                $receiptChannelId = $this->settings->get('telegram_receipt_channel_id');
                $adminChatId = $this->settings->get('telegram_admin_chat_id');
                $targetChatId = !empty($receiptChannelId) ? $receiptChannelId : $adminChatId;
                $destinations = array_filter([$targetChatId]);

                $orderType = $order->renews_order_id ? 'تمدید سرویس' : ($order->plan_id ? 'خرید سرویس' : 'شارژ کیف پول');

                $userLink = "<a href=\"tg://user?id={$user->telegram_chat_id}\">" . htmlspecialchars($user->name ?: 'کاربر') . " (لمس برای ورود به پی‌وی)</a>";
                $adminMessage = "🧾 <b>رسید متنی جدید برای سفارش #{$orderId}</b>\n\n";
                $adminMessage .= "👤 <b>کاربر:</b> {$userLink} (<code>{$user->telegram_chat_id}</code>)\n";
                $adminMessage .= "💵 <b>مبلغ:</b> <code>" . number_format($order->amount) . " تومان</code>\n";
                $adminMessage .= "📦 <b>نوع سفارش:</b> {$orderType}\n";
                $adminMessage .= "📝 <b>متن فیش:</b> <code>" . htmlspecialchars($text) . "</code>\n\n";
                $adminMessage .= "👇 <i>جهت بررسی فیش از دکمه‌های زیر استفاده کنید:</i>";

                $keyboardRows = [
                    [
                        $this->makeInlineButton(['text' => '✅ تایید پرداخت', 'callback_data' => "admin_approve_order_{$orderId}"]),
                        $this->makeInlineButton(['text' => '❌ رد پرداخت', 'callback_data' => "admin_reject_order_{$orderId}"])
                    ]
                ];
                if (!empty($user->username)) {
                    $keyboardRows[] = [
                        $this->makeInlineButton(['text' => '💬 پی‌وی کاربر (@' . ltrim($user->username, '@') . ')', 'url' => 'https://t.me/' . ltrim($user->username, '@')])
                    ];
                }
                $keyboard = Keyboard::make()->inline();
                foreach ($keyboardRows as $r) {
                    $keyboard->row($r);
                }

                foreach ($destinations as $targetChatId) {
                    if (!is_numeric($targetChatId)) continue;
                    try {
                        Telegram::sendMessage([
                            'chat_id'      => $targetChatId,
                            'text'         => $adminMessage,
                            'parse_mode'   => 'HTML',
                            'reply_markup' => $keyboard
                        ]);
                    } catch (\Exception $e) {
                        Log::error("Failed to send text receipt to {$targetChatId}: " . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                Log::error("Text receipt processing failed for order {$orderId}: " . $e->getMessage());
                Telegram::sendMessage(['chat_id' => $chatId, 'text' => "❌ خطا در پردازش رسید. لطفاً دوباره تلاش کنید."]);
            }
        } else {
            Telegram::sendMessage(['chat_id' => $chatId, 'text' => "❌ سفارش نامعتبر است یا در انتظار پرداخت نیست."]);
        }
    }

    // ========================================================================
    // 🛒 سیستم خرید و تخفیف
    // ========================================================================

    protected function startPurchaseProcess($user, $planId, $username, $messageId = null)
    {
        $plan = Plan::whereKey($planId)->where('is_active', true)->first();
        if (!$plan) {
            $this->sendPlans($user->telegram_chat_id, $messageId);
            return;
        }

        $serverId = null;
        $isMultiLocationEnabled = filter_var(
            $this->settings->get('enable_multilocation', false),
            FILTER_VALIDATE_BOOLEAN
        );

        if ($user->bot_state && Str::contains($user->bot_state, 'selected_loc:')) {
            preg_match('/selected_loc:(\d+)/', $user->bot_state, $matches);
            if (!empty($matches[1])) {
                $locationId = (int) $matches[1];
            } else {
                $locationId = null;
            }

            if ($locationId) {
                // پیدا کردن خلوت‌ترین سرور فعال
                $bestServer = \Modules\MultiServer\Models\Server::where('location_id', $locationId)
                    ->where('is_active', true)
                    ->whereRaw('current_users < capacity')
                    ->orderBy('current_users', 'asc')
                    ->first();

                if ($bestServer) {
                    $serverId = $bestServer->id;
                } else {
                    $user->update(['bot_state' => null]);
                    Telegram::sendMessage([
                        'chat_id' => $user->telegram_chat_id,
                        'text' => $this->escape("❌ متأسفانه ظرفیت سرورهای این لوکیشن تکمیل شده است."),
                        'parse_mode' => 'MarkdownV2'
                    ]);
                    return;
                }
            }
        }

        // A double click or a temporary Telegram rendering failure must not create
        // multiple payable orders for the same plan. Re-open the most recent safe
        // pending order instead.
        $order = $user->orders()
            ->where('plan_id', $plan->id)
            ->where('status', 'pending')
            ->where('source', 'telegram')
            ->whereNull('card_payment_receipt')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->latest()
            ->first();

        if (!$order) {
            $order = $user->orders()->create([
                'plan_id' => $plan->id,
                'server_id' => $serverId,
                'status' => 'pending',
                'source' => 'telegram',
                'amount' => $plan->price,
                'discount_amount' => 0,
                'discount_code_id' => null,
                'panel_username' => $username
            ]);
        }

        app(\App\Services\BotEventLogger::class)->record($order->wasRecentlyCreated ? 'order_created' : 'order_reopened', 'sales_bot', [
            'chat_id' => $user->telegram_chat_id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'plan_id' => $plan->id,
            'status' => $order->status,
            'source' => $order->source,
        ]);

        if ($order->wasRecentlyCreated) {
            $userLink = "<a href=\"tg://user?id={$user->telegram_chat_id}\">" . htmlspecialchars($user->name) . "</a>";
            $this->sendToLogChannel(
                "🛒 <b>سفارش خرید جدید ایجاد شد</b>\n\n" .
                "🔹 <b>سفارش:</b> #{$order->id}\n" .
                "🔹 <b>کاربر:</b> {$userLink} (<code>{$user->telegram_chat_id}</code>)\n" .
                "🔹 <b>پلن:</b> " . htmlspecialchars($plan->name) . "\n" .
                "🔹 <b>مبلغ:</b> " . number_format($plan->price) . " تومان\n" .
                "🔹 <b>نام کاربری انتخابی:</b> <code>{$username}</code>\n" .
                "⏰ <b>زمان:</b> " . now()->format('Y-m-d H:i:s')
            );
        }

        $user->update(['bot_state' => null]);
        $this->showInvoice($user, $order, $messageId);
    }


    /**
     * آیا درگاه فعال است؟
     */
    protected function isPayMethodEnabled(string $method): bool
    {
        return app(\App\Services\PaymentAvailabilityService::class)
            ->isEnabled($method, $this->settings);
    }

    /**
     * کیبورد انتخاب درگاه — رنگی و خلاقانه
     */
    protected function buildPaymentMethodsKeyboard(Order $order, $user = null): Keyboard
    {
        $keyboard = Keyboard::make()->inline();
        $oid = $order->id;
        $balance = (float) (($user->balance ?? 0));

        // تخفیف
        if (!$order->discount_code_id && $order->plan_id) {
            $keyboard->row([
                $this->makeInlineButton(['text' => '🎫 کد تخفیف', 'callback_data' => "enter_discount_{$oid}", 'style' => 'primary']),
            ]);
        } elseif ($order->discount_code_id) {
            $keyboard->row([
                $this->makeInlineButton(['text' => '❌ حذف تخفیف', 'callback_data' => "remove_discount_{$oid}", 'style' => 'danger']),
            ]);
        }

        if ($this->isPayMethodEnabled('wallet') && $balance >= (float) $order->amount) {
            $keyboard->row([
                $this->makeInlineButton([
                    'text' => '👛 پرداخت با موجودی کیف‌پول',
                    'callback_data' => "pay_wallet_order_{$oid}",
                    'style' => 'success',
                ]),
            ]);
        } elseif ($this->isPayMethodEnabled('wallet')) {
            $keyboard->row([
                $this->makeInlineButton([
                    'text' => '👛 موجودی ناکافی — شارژ کیف‌پول',
                    'callback_data' => '/deposit',
                    'style' => 'primary',
                ]),
            ]);
        }

        if ($this->isPayMethodEnabled('card')) {
            $keyboard->row([
                $this->makeInlineButton([
                    'text' => '💳 کارت‌به‌کارت ریالی',
                    'callback_data' => "pay_card_{$oid}",
                    'style' => 'primary',
                ]),
            ]);
        }

        if ($this->isPayMethodEnabled('bank_gateway')) {
            $keyboard->row([
                $this->makeInlineButton([
                    'text' => '🏦 درگاه بانکی آنلاین',
                    'callback_data' => "pay_bank_{$oid}",
                    'style' => 'primary',
                ]),
            ]);
        }

        if ($this->isPayMethodEnabled('crypto')) {
            $keyboard->row([
                $this->makeInlineButton([
                    'text' => '🪙 ارز دیجیتال (Crypto)',
                    'callback_data' => "pay_crypto_{$oid}",
                    'style' => 'success',
                ]),
            ]);
        }

        if ($this->isPayMethodEnabled('paypal')) {
            $keyboard->row([
                $this->makeInlineButton([
                    'text' => '🅿️ PayPal',
                    'callback_data' => "pay_paypal_{$oid}",
                    'style' => 'primary',
                ]),
            ]);
        }

        if ($this->isPayMethodEnabled('intl')) {
            $keyboard->row([
                $this->makeInlineButton([
                    'text' => '💎 Visa / MasterCard',
                    'callback_data' => "pay_intl_{$oid}",
                    'style' => 'primary',
                ]),
            ]);
        }

        $keyboard->row([
            $this->makeInlineButton(['text' => '🛍 بازگشت فروشگاه', 'callback_data' => '/plans', 'style' => 'danger']),
            $this->makeInlineButton(['text' => '🏠 خانه', 'callback_data' => '/start', 'style' => 'danger']),
        ]);

        return $keyboard;
    }

    protected function showInvoice($user, Order $order, $messageId = null)
    {
        $plan = $order->plan;
        $balance = $user->balance ?? 0;
        $r = "\u{200F}";

        $message = "🧾 *" . $this->escape("خلاصه سفارش #{$order->id}") . "*\n\n";
        
        if ($plan) {
            $message .= "📦 *" . $this->escape("بسته:") . "* `" . $this->escape($plan->name) . "`\n";
            $message .= "🗓 *" . $this->escape("مدت:") . "* `" . $this->escape($plan->duration_label) . "`\n";
            $message .= "💾 *" . $this->escape("حجم:") . "* `" . $this->escape($plan->volume_gb . ' گیگابایت') . "`\n";
        } else {
            $message .= "💰 *" . $this->escape("نوع سفارش:") . "* `" . $this->escape("شارژ کیف پول") . "`\n";
        }
        if ($order->panel_username) {
            $message .= "👤 *" . $this->escape("شناسه سرویس:") . "* `" . $this->escape($order->panel_username) . "`\n";
        }
        $message .= "───────────────────\n";

        if ($order->discount_amount > 0 && $plan) {
            $message .= "🏷 *" . $this->escape("قیمت اصلی:") . "* " . $this->escape(number_format($plan->price) . " تومان") . "\n";
            $message .= "🎉 *" . $this->escape("تخفیف اعمال شده:") . "* " . $this->escape(number_format($order->discount_amount) . " تومان") . "\n";
            $message .= "✅ *" . $this->escape("مبلغ نهایی:") . "* `" . $this->escape(number_format($order->amount) . " تومان") . "`\n";
        } else {
            $message .= "💵 *" . $this->escape("مبلغ قابل پرداخت:") . "* `" . $this->escape(number_format($order->amount) . " تومان") . "`\n";
        }

        $message .= "👛 *" . $this->escape("موجودی کیف پول:") . "* " . $this->escape(number_format($balance) . " تومان") . "\n";
        $message .= "───────────────────\n\n";
        $message .= $this->escape("قیمت و مشخصات را بررسی کنید؛ سپس روش پرداخت را انتخاب کنید. پس از پرداخت موفق، سرویس و لینک اتصال به‌صورت خودکار برایتان ارسال می‌شود.") . "\n\n";
        $message .= "👇 *" . $this->escape("انتخاب روش پرداخت") . "*";

        $this->sendOrEditMessage($user->telegram_chat_id, $message, $this->buildPaymentMethodsKeyboard($order, $user), $messageId);
    }

    /**
     * صفحه پرداخت کریپتو / لینک خارجی
     */
    protected function sendGatewayPaymentInfo($chatId, $orderId, string $gateway, $messageId = null)
    {
        $order = Order::with('user', 'plan')->find($orderId);
        if (!$order || $order->status !== 'pending') {
            $this->sendOrEditMessage($chatId, "\u{200F}" . $this->escape("❌ سفارش معتبر نیست."), $this->getMainMenuKeyboard(), $messageId);
            return;
        }

        $user = $order->user;
        $r = "\u{200F}";
        $amount = number_format($order->amount);
        $keyboard = Keyboard::make()->inline();

        if ($gateway === 'crypto') {
            $user->update(['bot_state' => 'waiting_receipt_' . $orderId]);
            $order->update(['payment_method' => 'crypto']);

            $usdtTrc = $this->settings->get('crypto_usdt_trc20', '');
            $usdtBep = $this->settings->get('crypto_usdt_bep20', '');
            $btc = $this->settings->get('crypto_btc', '');
            $extra = $this->settings->get('crypto_instructions', 'پس از واریز، اسکرین‌شات رسید را همین‌جا بفرستید.');

            $message = $r . "🪙 *" . $this->escape("پرداخت رمزارز") . "*\n\n";
            $message .= $r . "💵 " . $this->escape("مبلغ تقریبی سفارش: ") . "*" . $this->escape($amount . " تومان") . "*\n";
            $message .= $r . $this->escape("معادل USDT را طبق نرخ روز واریز کنید.") . "\n\n";

            if ($usdtTrc) {
                $message .= $r . "🔹 *USDT \\(TRC20\\)*\n`" . $this->escapeCode($usdtTrc) . "`\n\n";
            }
            if ($usdtBep) {
                $message .= $r . "🔹 *USDT \\(BEP20\\)*\n`" . $this->escapeCode($usdtBep) . "`\n\n";
            }
            if ($btc) {
                $message .= $r . "🔹 *BTC*\n`" . $this->escapeCode($btc) . "`\n\n";
            }
            if (!$usdtTrc && !$usdtBep && !$btc) {
                $message .= $r . $this->escape("⚠️ آدرس ولت هنوز توسط ادمین تنظیم نشده.") . "\n\n";
            }
            $message .= $r . "📸 " . $this->escape($extra);

            $keyboard->row([
                $this->makeInlineButton(['text' => '⬅️ تغییر درگاه', 'callback_data' => "pay_methods_{$orderId}", 'style' => 'primary']),
            ])->row([
                $this->makeInlineButton(['text' => '❌ انصراف', 'callback_data' => '/cancel_action', 'style' => 'danger']),
            ]);
            $this->sendOrEditMessage($chatId, $message, $keyboard, $messageId);
            return;
        }

        if ($gateway === 'paypal') {
            $order->update(['payment_method' => 'paypal']);
            $link = trim((string) $this->settings->get('paypal_link', ''));
            $email = trim((string) $this->settings->get('paypal_email', ''));
            $extra = $this->settings->get('paypal_instructions', 'بعد از پرداخت، رسید/اسکرین را ارسال کنید.');

            $message = $r . "🅿️ *" . $this->escape("پرداخت PayPal") . "*\n\n";
            $message .= $r . "💵 " . $this->escape("مبلغ: ") . "*" . $this->escape($amount . " تومان") . "*\n";
            if ($email) {
                $message .= $r . "📧 " . $this->escape("ایمیل: ") . "`" . $this->escapeCode($email) . "`\n";
            }
            $message .= "\n" . $r . "📸 " . $this->escape($extra);

            if ($link && str_starts_with($link, 'http')) {
                $keyboard->row([
                    $this->makeInlineButton(['text' => '🌐 پرداخت در PayPal', 'url' => $link, 'style' => 'success']),
                ]);
            }
            $user->update(['bot_state' => 'waiting_receipt_' . $orderId]);
            $keyboard->row([
                $this->makeInlineButton(['text' => '⬅️ تغییر درگاه', 'callback_data' => "pay_methods_{$orderId}", 'style' => 'primary']),
            ])->row([
                $this->makeInlineButton(['text' => '❌ انصراف', 'callback_data' => '/cancel_action', 'style' => 'danger']),
            ]);
            $this->sendOrEditMessage($chatId, $message, $keyboard, $messageId);
            return;
        }

        if ($gateway === 'intl') {
            $order->update(['payment_method' => 'intl_card']);
            $link = trim((string) $this->settings->get('intl_payment_link', ''));
            $extra = $this->settings->get('intl_payment_instructions', 'از درگاه بین‌المللی پرداخت کنید و رسید را بفرستید.');

            $message = $r . "💎 *" . $this->escape("Visa / MasterCard") . "*\n\n";
            $message .= $r . "💵 " . $this->escape("مبلغ: ") . "*" . $this->escape($amount . " تومان") . "*\n\n";
            $message .= $r . "📸 " . $this->escape($extra);

            if ($link && str_starts_with($link, 'http')) {
                $keyboard->row([
                    $this->makeInlineButton(['text' => '🌐 ورود به درگاه امن', 'url' => $link, 'style' => 'success']),
                ]);
            } else {
                $message .= "\n" . $r . $this->escape("⚠️ لینک درگاه توسط ادمین تنظیم نشده.");
            }
            $user->update(['bot_state' => 'waiting_receipt_' . $orderId]);
            $keyboard->row([
                $this->makeInlineButton(['text' => '⬅️ تغییر درگاه', 'callback_data' => "pay_methods_{$orderId}", 'style' => 'primary']),
            ])->row([
                $this->makeInlineButton(['text' => '❌ انصراف', 'callback_data' => '/cancel_action', 'style' => 'danger']),
            ]);
            $this->sendOrEditMessage($chatId, $message, $keyboard, $messageId);
            return;
        }
    }


    /**
     * 🏦 اطلاعات درگاه بانکی (stub — به زودی متصل می‌شود)
     */
    protected function sendBankGatewayInfo($chatId, $orderId, $messageId = null)
    {
        $order = Order::find($orderId);
        if (!$order || $order->status !== 'pending') {
            $this->sendOrEditMessage($chatId, "\u{200F}" . $this->escape("❌ سفارش معتبر نیست."), $this->getMainMenuKeyboard(), $messageId);
            return;
        }

        $r = "\u{200F}";
        $gatewayName = $this->settings->get('bank_gateway_name', 'درگاه بانکی آنلاین');
        $amount = number_format($order->amount);

        $message  = $r . "🏦 *" . $this->escape("پرداخت از طریق " . $gatewayName) . "*\n\n";
        $message .= $r . "💵 " . $this->escape("مبلغ: ") . "*" . $this->escape($amount . " تومان") . "*\n\n";
        $message .= $r . "⏳ " . $this->escape("این درگاه در حال راه‌اندازی است و به زودی فعال خواهد شد.") . "\n";
        $message .= $r . $this->escape("در حال حاضر لطفاً از روش کارت‌به‌کارت استفاده کنید.");

        $keyboard = Keyboard::make()->inline()
            ->row([$this->makeInlineButton(['text' => '💳 پرداخت با کارت‌به‌کارت', 'callback_data' => "pay_card_{$orderId}", 'style' => 'primary'])])
            ->row([$this->makeInlineButton(['text' => '⬅️ تغییر درگاه', 'callback_data' => "pay_methods_{$orderId}", 'style' => 'danger'])]);

        $this->sendOrEditMessage($chatId, $message, $keyboard, $messageId);
    }

    protected function promptForDiscount($user, $orderId, $messageId)
    {
        $user->update(['bot_state' => 'awaiting_discount_code|' . $orderId]);
        $keyboard = Keyboard::make()->inline()->row([$this->makeInlineButton(['text' => '❌ انصراف', 'callback_data' => '/cancel_action', 'style' => 'danger'])]);
        $this->sendOrEditMessage($user->telegram_chat_id, "🎫 لطفاً کد تخفیف خود را ارسال کنید:", $keyboard, $messageId);
    }

    protected function processDiscountCode($user, $orderId, $codeText)
    {
        $order = Order::find($orderId);
        if (!$order || $order->status !== 'pending') {
            $user->update(['bot_state' => null]);
            $this->sendOrEditMainMenu($user->telegram_chat_id, "❌ سفارش منقضی شده است.");
            return;
        }

        $code = DiscountCode::where('code', $codeText)->first();
        $error = null;

        if (!$code) $error = '❌ کد تخفیف نامعتبر است.';
        elseif (!$code->is_active) $error = '❌ کد تخفیف غیرفعال است.';
        elseif ($code->starts_at && $code->starts_at > now()) $error = '❌ زمان استفاده از کد نرسیده است.';
        elseif ($code->expires_at && $code->expires_at < now()) $error = '❌ کد تخفیف منقضی شده است.';
        else {
            $totalAmount = $order->plan_id ? $order->plan->price : $order->amount;
            // ⚠️ نکته: اطمینان حاصل کنید که مدل DiscountCode متدهای isValidForOrder و calculateDiscount را دارد
            if (!$code->isValidForOrder($totalAmount, $order->plan_id, !$order->plan_id, (bool)$order->renews_order_id)) {
                $error = '❌ کد تخفیف شامل شرایط این سفارش نمی‌شود.';
            }
        }

        if ($error) {
            Telegram::sendMessage(['chat_id' => $user->telegram_chat_id, 'text' => $this->escape($error), 'parse_mode' => 'MarkdownV2']);
            return;
        }

        $discountAmount = $code->calculateDiscount($order->plan->price ?? $order->amount);
        $finalAmount = ($order->plan->price ?? $order->amount) - $discountAmount;

        $order->update([
            'discount_amount' => $discountAmount,
            'discount_code_id' => $code->id,
            'amount' => $finalAmount
        ]);

        $user->update(['bot_state' => null]);
        Telegram::sendMessage(['chat_id' => $user->telegram_chat_id, 'text' => $this->escape("✅ کد تخفیف اعمال شد!"), 'parse_mode' => 'MarkdownV2']);
        $this->showInvoice($user, $order);
    }

    protected function removeDiscount($user, $orderId, $messageId)
    {
        $order = Order::find($orderId);
        if ($order && $order->status === 'pending') {
            $originalPrice = $order->plan->price ?? ($order->amount + $order->discount_amount);
            $order->update([
                'discount_amount' => 0,
                'discount_code_id' => null,
                'amount' => $originalPrice
            ]);
            $this->showInvoice($user, $order, $messageId);
        }
    }


    protected function processWalletPayment($user, $input, $messageId)
    {
        $order = null;
        $plan = null;

        try {
            DB::transaction(function () use ($user, $input, &$order, &$plan) { // ✅ اضافه کردن &
                // 🔒 قفل کردن رکورد کاربر برای جلوگیری از دسترسی همزمان
                $lockedUser = User::lockForUpdate()->find($user->id);

                if (!$lockedUser) {
                    throw new \Exception('User not found');
                }

                // تشخیص سفارش موجود یا ساخت سفارش جدید
                if (Str::startsWith($input, 'order_')) {
                    $orderId = Str::after($input, 'order_');
                    $order = Order::where('id', $orderId)
                        ->where('user_id', $lockedUser->id)
                        ->where('status', 'pending')
                        ->first();

                    if (!$order) {
                        throw new \Exception('سفارش نامعتبر است یا منقضی شده.');
                    }

                    $plan = $order->plan;
                } else {
                    $planId = $input;
                    $plan = Plan::find($planId);

                    if (!$plan) {
                        throw new \Exception('پلن مورد نظر یافت نشد.');
                    }

                    // ساخت سفارش داخل تراکنش
                    $order = $lockedUser->orders()->create([
                        'plan_id' => $plan->id,
                        'status' => 'pending',
                        'source' => 'telegram',
                        'amount' => $plan->price,
                        'discount_amount' => 0,
                        'discount_code_id' => null,
                    ]);
                }

                // ✅ بررسی موجودی داخل تراکنش (با رکورد قفل شده)
                if ($lockedUser->balance < $order->amount) {
                    throw new \Exception('موجودی کافی نیست');
                }

                // کسر موجودی (Atomic)
                $lockedUser->decrement('balance', $order->amount);

                // بروزرسانی سفارش به پرداخت شده
                $order->update([
                    'status' => 'paid',
                    'payment_method' => 'wallet',
                    'expires_at' => now()->addDays($plan->duration_days)
                ]);

                // ثبت استفاده از کد تخفیف
                if ($order->discount_code_id) {
                    $dc = DiscountCode::lockForUpdate()->find($order->discount_code_id);
                    if ($dc) {
                        DiscountCodeUsage::create([
                            'discount_code_id' => $dc->id,
                            'user_id' => $lockedUser->id,
                            'order_id' => $order->id,
                            'discount_amount' => $order->discount_amount,
                            'original_amount' => $plan->price
                        ]);
                        $dc->increment('used_count');
                    }
                }

                // ثبت تراکنش مالی
                Transaction::create([
                    'user_id' => $lockedUser->id,
                    'order_id' => $order->id,
                    'amount' => -$order->amount,
                    'type' => 'purchase',
                    'status' => 'completed',
                    'description' => "خرید سرویس {$plan->name} از طریق کیف پول"
                ]);

                // ساخت اکانت در پنل (X-UI یا Marzban)
                $provisionData = $this->provisionUserAccount($order, $plan);

                if ($provisionData && $provisionData['link']) {
                    $order->update([
                        'config_details' => $provisionData['link'],
                        'panel_username' => $provisionData['username'],
                        'panel_client_id' => $provisionData['panel_client_id'] ?? null,
                        'panel_sub_id' => $provisionData['panel_sub_id'] ?? null,
                    ]);
                } else {
                    throw new \Exception('خطا در ایجاد کانفیگ در پنل. لطفاً با پشتیبانی تماس بگیرید.');
                }
            });

            // ارسال پیام موفقیت (خارج از تراکنش)
            // ✅ اصلاح: حالا $order و $plan در دسترس هستند چون با & پاس شده‌اند
            $link = $order->config_details;

            // بارگذاری اطلاعات کامل سفارش
            $order->load(['server.location', 'plan']);

            // آماده‌سازی اطلاعات سرور و کشور
            $serverName = 'سرور اصلی';
            $locationFlag = '🏳️';
            $locationName = 'نامشخص';

            if ($order->server) {
                $serverName = $order->server->name;
                if ($order->server->location) {
                    $locationFlag = $order->server->location->flag ?? '🏳️';
                    $locationName = $order->server->location->name;
                }
            } elseif ($this->settings->get('panel_type') === 'pasargad') {
                $serverName = 'PasarGuard Eagle';
                $locationName = 'سرویس Eagle';
                $locationFlag = '🦅';
            }

            $userNameDisplay = htmlspecialchars($user->name ?: 'کاربر');
            $userLink = "<a href=\"tg://user?id={$user->telegram_chat_id}\">{$userNameDisplay} ({$user->telegram_chat_id})</a>";
            $logKeyboard = Keyboard::make()->inline()->row([
                $this->makeInlineButton(['text' => '👤 ارسال پیام به کاربر (پی‌وی)', 'url' => "tg://user?id={$user->telegram_chat_id}"])
            ]);
            $this->sendToLogChannel(
                "🛍 <b>خرید و فعال‌سازی سرویس با کیف پول</b>\n\n" .
                "🔹 <b>سفارش:</b> #{$order->id}\n" .
                "🔹 <b>کاربر:</b> {$userLink}\n" .
                "🔹 <b>پلن:</b> " . htmlspecialchars($order->plan->name ?? $plan->name) . "\n" .
                "🔹 <b>مبلغ پرداختی:</b> " . number_format($order->amount) . " تومان\n" .
                "🔹 <b>نام کاربری پنل:</b> <code>{$order->panel_username}</code>\n" .
                "🔹 <b>سرور:</b> {$locationFlag} {$locationName}\n" .
                "⏰ <b>زمان:</b> " . now()->format('Y-m-d H:i:s'),
                'HTML',
                $logKeyboard
            );

            // ساخت پیام کامل با ظاهر پریمیوم
            $message = "🔑 *اشتراک شما با موفقیت فعال شد*\n\n";
            $message .= "📦 *پلن:* `{$this->escape($order->plan->name)}`\n";
            $message .= "🌍 *موقعیت:* {$locationFlag} {$this->escape($locationName)}\n";
            $message .= "👤 *نام کاربری:* `{$order->panel_username}`\n";
            $message .= "💾 *حجم:* {$order->plan->volume_gb} گیگابایت\n";
            $message .= "⏳ *انقضا:* `{$order->expires_at->format('Y/m/d H:i')}`\n\n";
            $message .= "🔗 *لینک اشتراک اختصاصی:*\n";
            $message .= "`{$link}`\n\n";
            $message .= "👆🏻 " . $this->escape("برای کپی روی لینک بالا بزنید!") . "\n\n";
            $message .= $this->escape("⚠️ توصیه می‌شود از اپلیکیشن رسمی پیشنهادی استفاده کنید.");

            // کیبورد با دکمه کپی لینک
            $keyboard = Keyboard::make()->inline()
                ->row([
                    $this->makeInlineButton(['text' => '📋 کپی لینک اشتراک', 'callback_data' => "copy_link_{$order->id}"]),
                    $this->makeInlineButton(['text' => '🔗 دریافت لینک‌های اتصال', 'callback_data' => "direct_configs_order_{$order->id}"])
                ])
                ->row([
                    $this->makeInlineButton(['text' => '🛠 سرویس‌های من', 'callback_data' => '/my_services']),
                    $this->makeInlineButton(['text' => '🏠 خانه', 'callback_data' => '/start', 'style' => 'danger'])
                ]);

            $this->sendPaidActivationPhoto($user, $order, $keyboard);

        } catch (\Exception $e) {
            Log::error('Wallet Payment Failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'input' => $input,
                'trace' => $e->getTraceAsString()
            ]);

            $errorMsg = $e->getMessage();
            $keyboard = Keyboard::make()->inline();

            // تشخیص نوع خطا و نمایش پیام مناسب
            if ($errorMsg === 'موجودی کافی نیست') {
                $keyboard->row([
                    $this->makeInlineButton(['text' => '💳 شارژ کیف پول', 'callback_data' => '/deposit']),
                    $this->makeInlineButton(['text' => '⬅️ بازگشت فروشگاه', 'callback_data' => '/plans', 'style' => 'danger'])
                ]);
                $this->sendOrEditMessage(
                    $user->telegram_chat_id,
                    $this->escape("❌ موجودی کیف پول شما کافی نیست.\n\n💡 لطفاً ابتدا کیف پول خود را شارژ کنید."),
                    $keyboard,
                    $messageId
                );
            } elseif ($errorMsg === 'سفارش نامعتبر است یا منقضی شده.') {
                $keyboard->row([$this->makeInlineButton(['text' => '🛒 مشاهده پلن‌ها', 'callback_data' => '/plans'])]);
                $this->sendOrEditMessage(
                    $user->telegram_chat_id,
                    $this->escape("❌ " . $errorMsg),
                    $keyboard,
                    $messageId
                );
            } else {
                // خطای عمومی یا خطای پر کردن اکانت
                $keyboard->row([$this->makeInlineButton(['text' => '💬 تماس با پشتیبانی', 'callback_data' => '/support_menu'])]);
                $this->sendOrEditMessage(
                    $user->telegram_chat_id,
                    $this->escape("⚠️ خطایی در پردازش خرید رخ داد: {$errorMsg}\n\nلطفاً با پشتیبانی تماس بگیرید."),
                    $keyboard,
                    $messageId
                );
            }
        }
    }

    protected function sendCardPaymentInfo($chatId, $orderId, $messageId)
    {
        $order = Order::find($orderId);
        if (!$order->server_id) {

            $user = $order->user;
            if ($user->bot_state && Str::contains($user->bot_state, 'selected_loc:')) {
                preg_match('/selected_loc:(\d+)/', $user->bot_state, $matches);
                if (!empty($matches[1])) {
                    $locationId = (int) $matches[1];


                    if (class_exists('Modules\MultiServer\Models\Server')) {
                        $bestServer = \Modules\MultiServer\Models\Server::where('location_id', $locationId)
                            ->where('is_active', true)
                            ->whereRaw('current_users < capacity')
                            ->orderBy('current_users', 'asc')
                            ->first();

                        if ($bestServer) {
                            $order->update(['server_id' => $bestServer->id]);
                        }
                    }
                }
            }
        }

        $user = $order->user;
        $user->update(['bot_state' => 'waiting_receipt_' . $orderId]);
        $user = $order->user;
        $user->update(['bot_state' => 'waiting_receipt_' . $orderId]);

        $cardNumber = $this->settings->get('payment_card_number', 'شماره کارتی تنظیم نشده');
        $cardHolder = $this->settings->get('payment_card_holder_name', 'صاحب حسابی تنظیم نشده');
        $amountToPay = number_format($order->amount);

        $message = "💳 <b>پرداخت به صورت کارت به کارت</b>\n\n";
        $message .= "👇 لطفاً مبلغ مشخص شده را به شماره کارت زیر واریز نمایید:\n\n";
        $message .= "💵 <b>مبلغ دقیق واریزی:</b> <code>" . number_format($order->amount) . " تومان</code>\n";
        $message .= "👤 <b>به نام:</b> " . htmlspecialchars($cardHolder) . "\n";
        $message .= "💳 <b>شماره کارت (برای کپی لمس کنید):</b>\n<code>" . htmlspecialchars($cardNumber) . "</code>\n\n";
        $message .= "───────────────────\n";
        $cardNotice = \App\Services\ShiftScheduleService::getCardNotice();
        $message .= "🔔 <b>راهنمای ارسال رسید:</b>\n";
        $message .= "👉 <i>پس از انجام تراکنش، لطفاً تصویر فیش واریزی (اسکرین‌شات) یا اطلاعات متنی رسید خود (مانند شماره پیگیری، تاریخ و نام واریزکننده) را در همین چت ارسال نمایید تا سفارش شما بررسی و فعال گردد.</i>\n\n";
        $message .= "⏱ <i>" . htmlspecialchars($cardNotice) . "</i>";

        $keyboard = Keyboard::make()->inline()
            ->row([$this->makeInlineButton(['text' => '⬅️ تغییر درگاه پرداخت', 'callback_data' => "pay_methods_{$orderId}", 'style' => 'primary'])])
            ->row([$this->makeInlineButton(['text' => '❌ انصراف از سفارش', 'callback_data' => '/cancel_action', 'style' => 'danger'])]);

        $this->sendOrEditMessage($chatId, $message, $keyboard, $messageId);
    }

    // ========================================================================
    // سایر متدها (پلان‌ها، تمدید، تیکت، آموزش و ...)
    // ========================================================================

    protected function sendPlans($chatId, $messageId = null)
    {
        try {
            $catalog = app(PlanCatalogService::class);
            $activePlans = $catalog->activePlans();

            app(\App\Services\BotEventLogger::class)->record('plans_viewed', 'sales_bot', [
                'chat_id' => $chatId,
                'status' => $activePlans->isEmpty() ? 'empty' : 'available',
            ]);

            if ($activePlans->isEmpty()) {
                $keyboard = Keyboard::make()->inline()
                    ->row([$this->makeInlineButton(['text' => '⬅️ بازگشت', 'callback_data' => '/start'])]);
                $this->sendOrEditMessage($chatId, $this->escape("⚠️ هیچ پلن فعالی در دسترس نیست."), $keyboard, $messageId);
                return;
            }

            $durations = $catalog->durations();

            $message = "🛍 <b>فروشگاه رسمی اشتراک روزنه</b>\n\n" .
                "<blockquote>⚡️ <b>شبکه هوشمند و ضد فیلتر روزنه:</b>\n" .
                "اتصال پایدار و فعال هم در شرایط اینترنت بین‌الملل و هم در زمان نت ملی.</blockquote>\n\n" .
                "✨ <b>ویژگی‌های فعال در تمامی بسته‌ها:</b>\n" .
                "▫️ اتصال پایدار به ۲ لوکیشن اختصاصی (🇩🇪 آلمان · 🇳🇱 هلند)\n" .
                "▫️ دسترسی همزمان به اینترنت ملی و بین‌المللی\n" .
                "▫️ بهینه‌شده برای سرویس‌های هوش مصنوعی (بدون بلاک و ارور)\n" .
                "▫️ استریم باکیفیت و روان ویدیو و یوتیوب\n" .
                "▫️ پشتیبانی انسانی ۲۴ ساعته در ۷ روز هفته (24/7)\n" .
                "▫️ ضمانت بازگشت وجه ۲۴ ساعته در صورت عدم رضایت\n\n" .
                "👇 <b>جهت مشاهده تعرفه‌ها، لطفاً مدت اشتراک را انتخاب فرمایید:</b>";

            $keyboard = Keyboard::make()->inline();

            foreach ($durations as $durationDays) {
                $buttonText = $catalog->durationLabel((int) $durationDays);
                $keyboard->row([
                    $this->makeInlineButton([
                        'text' => $buttonText,
                        'callback_data' => "show_duration_{$durationDays}",
                        'style' => 'primary',
                    ])
                ]);
            }

            $keyboard->row([
                $this->makeInlineButton(['text' => '⚡️ تست رایگان', 'callback_data' => 'trial_request', 'style' => 'success']),
            ]);
            $keyboard->row([$this->makeInlineButton(['text' => '🏠 بازگشت به خانه', 'callback_data' => '/start', 'style' => 'danger'])]);

            $this->sendOrEditMessage($chatId, $message, $keyboard, $messageId);

        } catch (\Exception $e) {
            Log::error('Error in sendPlans: ' . $e->getMessage(), [
                'chat_id' => $chatId,
                'trace' => $e->getTraceAsString()
            ]);

            $keyboard = Keyboard::make()->inline()
                ->row([$this->makeInlineButton(['text' => '🏠 بازگشت به منوی اصلی', 'callback_data' => '/start'])]);

            $this->sendOrEditMessage($chatId, "❌ خطایی در بارگذاری پلن‌ها رخ داد.", $keyboard, $messageId);
        }
    }

    protected function generateDurationLabel(int $days): string
    {
        return app(PlanCatalogService::class)->durationLabel($days);
    }

    protected function sendPlansByDuration($chatId, $durationDays, $messageId = null)
    {
        try {
            $catalog = app(PlanCatalogService::class);
            $plans = $catalog->plansForDuration((int) $durationDays);

            if ($plans->isEmpty()) {
                $keyboard = Keyboard::make()->inline()
                    ->row([$this->makeInlineButton(['text' => '⬅️ بازگشت فروشگاه', 'callback_data' => '/plans', 'style' => 'danger'])]);
                $this->sendOrEditMessage($chatId, $this->escape("⚠️ پلنی با این مدت‌زمان یافت نشد."), $keyboard, $messageId);
                return;
            }

            $durationLabel = $catalog->durationLabel((int) $durationDays);
            
            $message = "📦 <b>بسته‌های اشتراک {$durationLabel}</b>\n\n" .
                "<blockquote>⚡️ <b>مشخصات سرویس:</b> اتصال اختصاصی ۲ لوکیشن (🇩🇪 آلمان · 🇳🇱 هلند) با تانل پرسرعت ملی.</blockquote>\n\n" .
                "👇 <b>لطفاً حجم موردنیازتان را انتخاب کنید:</b>\n" .
                "<i>مبلغ نهایی و پیش‌فاکتور پیش از پرداخت دوباره نمایش داده می‌شود.</i>";

            $keyboard = Keyboard::make()->inline();

            foreach ($plans as $plan) {
                $buttonText = $catalog->planButtonLabel($plan);
                $keyboard->row([
                    $this->makeInlineButton([
                        'text' => $buttonText,
                        'callback_data' => "buy_plan_{$plan->id}",
                        'style' => 'success',
                    ])
                ]);
            }

            $keyboard->row([
                $this->makeInlineButton(['text' => '⬅️ تغییر مدت زمان', 'callback_data' => '/plans', 'style' => 'primary']),
                $this->makeInlineButton(['text' => '🏠 خانه', 'callback_data' => '/start', 'style' => 'danger'])
            ]);

            $this->sendOrEditMessage($chatId, $message, $keyboard, $messageId);

        } catch (\Exception $e) {
            Log::error('Error in sendPlansByDuration: ' . $e->getMessage(), [
                'duration_days' => $durationDays,
                'chat_id' => $chatId,
                'trace' => $e->getTraceAsString()
            ]);

            $keyboard = Keyboard::make()->inline()
                ->row([$this->makeInlineButton(['text' => '🏠 بازگشت به منوی اصلی', 'callback_data' => '/start'])]);

            $this->sendOrEditMessage($chatId, "❌ خطایی در بارگذاری پلن‌ها رخ داد.", $keyboard, $messageId);
        }
    }


    protected function sendPaidActivationPhoto($user, Order $order, $keyboard = null): void
    {
        app(\App\Services\TelegramServiceDeliveryService::class)->send($user, $order, $keyboard);
    }

    protected function sendQRCodeForOrder($user, $orderId)
    {
        $order = $user->orders()->find($orderId);

        if (!$order) {
            Telegram::sendMessage([
                'chat_id' => $user->telegram_chat_id,
                'text' => $this->escape("❌ سرویس یافت نشد."),
                'parse_mode' => 'MarkdownV2'
            ]);
            return;
        }

        if (empty($order->config_details) || !is_string($order->config_details)) {
            Telegram::sendMessage([
                'chat_id' => $user->telegram_chat_id,
                'text' => $this->escape("❌ لینک کانفیگ هنوز آماده نشده است."),
                'parse_mode' => 'MarkdownV2'
            ]);
            return;
        }

        $configLink = trim($order->config_details);

        // ✅ اعتبارسنجی فرمت لینک
        if (empty($configLink)) {
            Telegram::sendMessage([
                'chat_id' => $user->telegram_chat_id,
                'text' => $this->escape("❌ لینک کانفیگ خالی است."),
                'parse_mode' => 'MarkdownV2'
            ]);
            return;
        }

        $this->sendPaidActivationPhoto($user, $order);
        return;

        $tempFile = null;

        try {

            $qrParams = [
                'size' => '400x400',
                'data' => $configLink,
                'ecc' => 'M',
                'margin' => 10,
                'color' => '000000',
                'bgcolor' => 'FFFFFF',
                'format' => 'png'
            ];

            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?" . http_build_query($qrParams);


            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $qrUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; TelegramBot/1.0)'
            ]);

            $qrData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($qrData === false || $httpCode !== 200 || empty($qrData)) {
                throw new \Exception("HTTP {$httpCode} - {$curlError}");
            }


            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempFile = $tempDir . '/qr_' . $order->id . '_' . time() . '.png';

            if (file_put_contents($tempFile, $qrData) === false) {
                throw new \Exception("عدم توانایی در ذخیره فایل موقت");
            }

            // ✅ ساخت کیبورد
            $keyboard = Keyboard::make()->inline()
                ->row([
                    $this->makeInlineButton(['text' => '🔄 تمدید سرویس', 'callback_data' => "renew_order_{$order->id}"]),
                    $this->makeInlineButton(['text' => '⬅️ بازگشت به جزئیات', 'callback_data' => "show_service_{$order->id}"])
                ])
                ->row([
                    $this->makeInlineButton(['text' => '⬅️ بازگشت به لیست سرویس‌ها', 'callback_data' => '/my_services'])
                ]);

            // ✅ ارسال عکس با InputFile (Premium Look)
            $photoCaption = "🔑 *📱 QR Code اشتراک \\#{$order->id}*\n\n";
            $photoCaption .= "👤 *نام کاربری:* `" . $this->escapeCode($order->panel_username) . "`\n";
            $photoCaption .= "🔗 *لینک اشتراک:*\n";
            $photoCaption .= "`" . $this->escapeCode($configLink) . "`\n\n";
            $photoCaption .= "👆🏻 " . $this->escape("برای کپی سریع روی لینک بالا بزنید!") . "\n\n";
            $photoCaption .= $this->escape("⚠️ این کد را در اپلیکیشن خود اسکن یا لینک را وارد کنید.");

            Telegram::sendPhoto([
                'chat_id' => $user->telegram_chat_id,
                'photo' => InputFile::create($tempFile, "qr_code_{$order->id}.png"),
                'caption' => $photoCaption,
                'parse_mode' => 'MarkdownV2',
                'reply_markup' => $keyboard
            ]);

        } catch (\Exception $e) {
            Log::error('QR Code Generation Failed', [
                'order_id' => $orderId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'config_length' => strlen($configLink ?? ''),
                'trace' => $e->getTraceAsString()
            ]);


            $keyboard = Keyboard::make()->inline()
                ->row([
                    $this->makeInlineButton(['text' => '🔄 تمدید سرویس', 'callback_data' => "renew_order_{$order->id}"]),
                    $this->makeInlineButton(['text' => '⬅️ بازگشت', 'callback_data' => "show_service_{$order->id}"])
                ]);

            Telegram::sendMessage([
                'chat_id' => $user->telegram_chat_id,
                'text' => $this->escape("❌ خطا در تولید QR Code.\n\n🔧 لطفاً از لینک زیر استفاده کنید:") . "\n`" . $this->escapeCode($configLink) . "`",
                'parse_mode' => 'MarkdownV2',
                'reply_markup' => $keyboard
            ]);

        } finally {

            if ($tempFile && file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }
    protected function sendMyServices($user, $messageId = null)
    {
        // فقط سفارش‌هایی که plan_id دارند را بگیر
        $orders = $user->orders()
            ->where('status', 'paid')
            ->whereNotNull('plan_id')
            ->with('plan')
            ->get();
        
        Log::info("SERVICES_QUERY", [
            'user_id' => $user->id,
            'total_paid_orders' => $user->orders()->where('status', 'paid')->count(),
            'orders_with_plan' => $orders->count(),
            'order_ids' => $orders->pluck('id')->toArray(),
            'plan_ids' => $orders->pluck('plan_id')->toArray()
        ]);

        // لاگ دیباگ برای بررسی orders بدون plan
        $ordersWithoutPlan = $user->orders()
            ->where('status', 'paid')
            ->whereNull('plan_id')
            ->get();
        
        if ($ordersWithoutPlan->isNotEmpty()) {
            Log::warning("ORDERS_WITHOUT_PLAN", [
                'user_id' => $user->id,
                'count' => $ordersWithoutPlan->count(),
                'order_ids' => $ordersWithoutPlan->pluck('id')->toArray(),
                'details' => $ordersWithoutPlan->map(function($o) {
                    return [
                        'id' => $o->id,
                        'amount' => $o->amount,
                        'created_at' => $o->created_at,
                        'panel_username' => $o->panel_username
                    ];
                })->toArray()
            ]);
        }

        if ($orders->isEmpty()) {
            $keyboard = Keyboard::make()->inline()->row([
                $this->makeInlineButton(['text' => '🛒 خرید سرویس جدید', 'callback_data' => '/plans']),
                $this->makeInlineButton(['text' => '🏠 خانه', 'callback_data' => '/start', 'style' => 'danger']),
            ]);
            $this->sendOrEditMessage($user->telegram_chat_id, $this->escape("⚠️ شما هیچ سرویس فعالی ندارید."), $keyboard, $messageId);
            return;
        }

    $message = "🎛 *کنترل‌پنل سرویس‌های شما*\n\n";
    $message .= $this->escape("اینجا مرکز فرماندهی شماست. وضعیت اتصال و تاریخ انقضای سرویس‌های خود را بررسی کنید.") . "\n\n";
    $message .= $this->escape("👇 روی هر کانکشن کلیک کنید تا جزئیات و راه‌های اتصال آن نمایش داده شود:") . "\n";

    $keyboard = Keyboard::make()->inline();
    $validServicesCount = 0;

    foreach ($orders as $order) {
        // چون با whereNotNull و with گرفتیم، همه باید plan داشته باشند
        if (!$order->plan) {
            Log::error("SERVICE_MISSING_PLAN", [
                'order_id' => $order->id,
                'plan_id' => $order->plan_id
            ]);
            continue;
        }

        $validServicesCount++;
        $expiresAt = Carbon::parse($order->expires_at);
        $now = now();
        $statusIcon = '🟢';

        if ($expiresAt->isPast()) {
            $statusIcon = '⚫️';
        } elseif ($expiresAt->diffInDays($now) <= 7) {
            $statusIcon = '🟡';
        }

        $username = $order->panel_username ?: "سرویس-{$order->id}";
        $buttonText = "{$statusIcon} {$username} (ID: #{$order->id})";

        $keyboard->row([
            $this->makeInlineButton([
                'text' => $buttonText,
                'callback_data' => "show_service_{$order->id}"
            ])
        ]);
    }

    Log::info("SERVICES_DISPLAYED", [
        'user_id' => $user->id,
        'valid_services' => $validServicesCount
    ]);

    $keyboard->row([
        $this->makeInlineButton(['text' => '🛒 خرید سرویس جدید', 'callback_data' => '/plans']),
        $this->makeInlineButton(['text' => '🏠 خانه', 'callback_data' => '/start', 'style' => 'danger'])
    ]);

    $this->sendOrEditMessage($user->telegram_chat_id, $message, $keyboard, $messageId);
}

    protected function showServiceDetails($user, $orderId, $messageId = null)
    {
        $order = $user->orders()->with('plan')->find($orderId);

        if (!$order || !$order->plan || $order->status !== 'paid') {
            $this->sendOrEditMainMenu($user->telegram_chat_id, "❌ سرویس مورد نظر یافت نشد یا معتبر نیست.", $messageId);
            return;
        }

        $panelUsername = $order->panel_username;
        if (empty($panelUsername)) {
            $panelUsername = "user-{$user->id}-order-{$order->id}";
        }

        $expiresAt = Carbon::parse($order->expires_at);
        $now = now();
        $statusIcon = '🟢';

        $daysRemaining = $now->diffInDays($expiresAt, false);
        $daysRemaining = (int) $daysRemaining;

        if ($expiresAt->isPast()) {
            $statusIcon = '⚫️';
            $remainingText = "*منقضی شده*";
        } elseif ($daysRemaining <= 7) {
            $statusIcon = '🟡';
            $remainingText = "*" . $this->escape($daysRemaining . ' روز') . "* باقی‌مانده \\(تمدید کنید\\)";
        } else {
            $remainingText = "*" . $this->escape($daysRemaining . ' روز') . "* باقی‌مانده";
        }

        $locationFlag = '🏳️';
        $locationName = 'نامشخص';
        // بهبود تشخیص لوکیشن
        $panelType = $this->settings->get('panel_type');
        if ($order->plan && ($panelType === 'pasargad' || !$panelType)) {
             $locationFlag = '🦅';
             $locationName = 'سرویس Eagle';
        }

        $message = "☀️ *" . $this->escape("روزنه | راهکار پایدار دسترسی آزاد") . "*\n\n";
        $message .= "🎫 *شناسه:* `" . $this->escapeCode($panelUsername) . "`\n";
        $message .= "🌍 *موقعیت سرور:* {$locationFlag} " . $this->escape($locationName) . "\n";
        $message .= "🎚 *طرح اشتراک:* " . $this->escape($order->plan->name) . "\n";
        $message .= "📦 *ترافیک کل:* `" . $this->escape($order->plan->volume_gb) . "` " . $this->escape("گیگابایت") . "\n";
        $message .= "⏳ *زمان باقی‌مانده:* " . $remainingText . "\n";
        $message .= "\n\n";
        
        if (!empty($order->config_details)) {
            // فقط گرفتن خود لینک (حذف هرگونه کاراکتر اضافه) برای کپی تمیز
            $pureUrl = trim(preg_replace('/^.*?(http|vless|vmess|trojan|ss)(:\/\/[^\s]+).*$/is', '$1$2', $order->config_details));
            // اگر مچ نشد همون دیتایل اصلی رو میذاریم
            if (empty($pureUrl)) {
                 $pureUrl = trim($order->config_details);
            }
            
            $message .= "🎯 *مسیر اتصالِ شما \\(لمس برای کپی\\):*\n";
            $message .= "`" . $this->escapeCode($pureUrl) . "`\n\n";
            $message .= "💡 *" . $this->escape("راهنما:") . "* " . $this->escape("لینک بالا را کپی کرده و در برنامه V2Box (آیفون) یا v2rayNG (اندروید) اضافه کنید.") . "\n\n";
            $message .= "📢 " . $this->escape("کانال:") . " [Rozaneh](https://t.me/rozaneh) \\| 👨🏻‍💻 " . $this->escape("پشتیبانی:") . " [RozanehSupport](https://t.me/rozaneh_support)\n";
        } else {
            $message .= "⏳ " . $this->escape("در حال آماده‌سازی کانفیگ...");
        }

        $keyboard = Keyboard::make()->inline();

        if (!empty($order->config_details)) {
            $keyboard->row([
                $this->makeInlineButton(['text' => "⚡️ کانفیگ مستقیم (VLESS/VMESS)", 'callback_data' => "direct_configs_order_{$order->id}"]),
            ]);
            $keyboard->row([
                $this->makeInlineButton(['text' => "📱 دریافت QR Code", 'callback_data' => "qrcode_order_{$order->id}"]),
                $this->makeInlineButton(['text' => "📋 کپی سابسکریپشن", 'callback_data' => "copy_link_{$order->id}"])
            ]);
        }

        $keyboard->row([
            $this->makeInlineButton(['text' => "🔄 تمدید اشتراک", 'callback_data' => "renew_order_{$order->id}"])
        ]);

        $keyboard->row([
            $this->makeInlineButton(['text' => '⬅️ بازگشت به لیست سرویس‌ها', 'callback_data' => '/my_services']),
            $this->makeInlineButton(['text' => '🏠 خانه', 'callback_data' => '/start', 'style' => 'danger'])
        ]);

        $this->sendOrEditMessage($user->telegram_chat_id, $message, $keyboard, $messageId);
    }

    protected function showServiceDetailsWithQR($user, $orderId, $messageId = null)
    {
        // 1. اگر پیام قبلی وجود دارد، آن را حذف کن (چون نمی‌توان متن را به عکس تبدیل کرد)
        if ($messageId) {
            try {
                Telegram::deleteMessage([
                    'chat_id' => $user->telegram_chat_id,
                    'message_id' => $messageId
                ]);
            } catch (\Exception $e) {
                // اگر پیام قبلاً حذف شده بود، مشکلی نیست
            }
        }

        $order = $user->orders()->with('plan')->find($orderId);

        if (!$order || !$order->plan || $order->status !== 'paid') {
            $this->sendOrEditMainMenu($user->telegram_chat_id, "❌ سرویس مورد نظر یافت نشد یا معتبر نیست.", null);
            return;
        }

        // اگر کانفیگ ندارد، همان متن ساده را بفرست
        if (empty($order->config_details)) {
             $this->showServiceDetails($user, $orderId, null);
             return;
        }

        $configLink = trim($order->config_details);
        $panelUsername = $order->panel_username ?: (($user->telegram_username ? "@" . $user->telegram_username : "user-" . $user->id) . "-order-" . $order->id);
        
        $expiresAt = Carbon::parse($order->expires_at);
        $now = now();
        $daysRemaining = (int) $now->diffInDays($expiresAt, false);
        
        if ($expiresAt->isPast()) {
            $remainingText = "*منقضی شده*";
            $statusIcon = '⚫️';
        } elseif ($daysRemaining <= 7) {
            $remainingText = "*" . $this->escape($daysRemaining . ' روز') . "* باقی‌مانده \\(تمدید کنید\\)";
            $statusIcon = '🟡';
        } else {
            $remainingText = "*" . $this->escape($daysRemaining . ' روز') . "* باقی‌مانده";
            $statusIcon = '🟢';
        }

        $locationFlag = '🏳️';
        $locationName = 'نامشخص';
        $panelType = $this->settings->get('panel_type');
        if ($order->plan && ($panelType === 'pasargad' || !$panelType)) {
             $locationFlag = '🦅';
             $locationName = 'سرویس Eagle';
        }

        $pureUrl = trim(preg_replace('/^.*?(http|vless|vmess|trojan|ss)(:\/\/[^\s]+).*$/is', '$1$2', $configLink));
        if (empty($pureUrl)) {
             $pureUrl = trim($configLink);
        }

        // متن کپشن (مشابه showServiceDetails)
        $caption = "☁️ <b>سرویس روزنه</b>\n\n";
        $caption .= "🎫 <b>شناسه:</b> <code>" . htmlspecialchars($panelUsername, ENT_QUOTES) . "</code>\n";
        $caption .= "🌍 <b>موقعیت سرور:</b> {$locationFlag} " . htmlspecialchars($locationName, ENT_QUOTES) . "\n";
        $caption .= "🎚 <b>طرح اشتراک:</b> " . htmlspecialchars($order->plan->name, ENT_QUOTES) . "\n";
        $caption .= "📦 <b>ترافیک کل:</b> <code>" . htmlspecialchars($order->plan->volume_gb, ENT_QUOTES) . "</code> گیگابایت\n";
        
        $daysRemainingSafe = htmlspecialchars((string)$daysRemaining, ENT_QUOTES);
        if ($expiresAt->isPast()) {
            $remainingText = "<b>منقضی شده</b>";
        } elseif ($daysRemaining <= 7) {
            $remainingText = "<b>{$daysRemainingSafe} روز</b> باقی‌مانده (تمدید کنید)";
        } else {
            $remainingText = "<b>{$daysRemainingSafe} روز</b> باقی‌مانده";
        }
        $caption .= "⏳ <b>زمان باقی‌مانده:</b> " . $remainingText . "\n\n";
        
        $caption .= "🎯 <b>مسیر اتصالِ شما:</b>\n";
        $caption .= "<pre><code class=\"language-txt\">" . htmlspecialchars($pureUrl, ENT_QUOTES) . "</code></pre>\n\n";
        $caption .= "💡 <b>راهنما:</b> روی کادر بالا بزنید تا کپی شود و سپس در برنامه V2Box یا v2rayNG اضافه کنید.\n\n";
        $caption .= "📢 کانال: <a href=\"https://t.me/lookanet\">روزنه</a> | 👨🏻‍💻 پشتیبانی: <a href=\"https://t.me/lookanet_support\">پشتیبانی روزنه</a>\n";

        // کیبورد
        $keyboard = Keyboard::make()->inline();
        $keyboard->row([
            $this->makeInlineButton(['text' => "⚡️ کانفیگ مستقیم (VLESS)", 'callback_data' => "direct_configs_order_{$order->id}"]),
        ]);
        $keyboard->row([
            $this->makeInlineButton(['text' => "📋 کپی سابسکریپشن", 'callback_data' => "copy_link_{$order->id}"]),
            $this->makeInlineButton(['text' => "🔄 تمدید اشتراک", 'callback_data' => "renew_order_{$order->id}"])
        ]);
        $keyboard->row([
            $this->makeInlineButton(['text' => '⬅️ بازگشت به لیست سرویس‌ها', 'callback_data' => '/my_services']),
            $this->makeInlineButton(['text' => '🏠 خانه', 'callback_data' => '/start', 'style' => 'danger'])
        ]);

        // تولید و ارسال QR
        $tempFile = null;
        try {
            $qrParams = [
                'size' => '400x400',
                'data' => $configLink,
                'ecc' => 'M',
                'margin' => 10,
                'color' => '000000',
                'bgcolor' => 'FFFFFF',
                'format' => 'png'
            ];
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?" . http_build_query($qrParams);
            
            // دانلود فایل با چک کردن HTTP Code
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $qrUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30, // افزایش تایم‌اوت
                CURLOPT_CONNECTTIMEOUT => 10
            ]);
            $qrData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || empty($qrData)) {
                throw new \Exception("QR Service Failed with HTTP Code: $httpCode");
            }

            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
            $tempFile = $tempDir . '/qr_auto_' . $order->id . '_' . time() . '.png';
            if (file_put_contents($tempFile, $qrData) === false) {
                 throw new \Exception("Could not write temp file");
            }

            Telegram::sendPhoto([
                'chat_id' => $user->telegram_chat_id,
                'photo' => InputFile::create($tempFile, "qr_{$order->id}.png"),
                'caption' => $caption,
                'parse_mode' => 'MarkdownV2',
                'reply_markup' => $keyboard
            ]);

        } catch (\Exception $e) {
            Log::error("QR Auto-Send Failed: " . $e->getMessage());
            // فال‌بک به متن معمولی
            $this->showServiceDetails($user, $orderId, null); // null messageId to force new message
        } finally {
            if ($tempFile && file_exists($tempFile)) @unlink($tempFile);
        }
    }

    protected function sendWalletMenu($user, $messageId = null)
    {
        $botSettings = TelegramBotSetting::pluck('value', 'key');
        $emojiDeposit = $botSettings->get('emoji_deposit');
        $emojiOrders = $botSettings->get('emoji_orders');
        $emojiProfile = $botSettings->get('emoji_profile');

        $balance = number_format((float) ($user->balance ?? 0));
        
        $message = "<tg-emoji emoji-id=\"5472250091332993630\">💳</tg-emoji> <b>کیف پول روزنه</b>\n\n";
        $message .= "<tg-emoji emoji-id=\"5382164415019768638\">🪙</tg-emoji> <b>موجودی شما:</b> <code>{$balance} تومان</code>\n\n";
        $message .= "💡 <i>یک‌بار شارژ کن؛ بعدش خرید و تمدید با یک ضربه.</i>\n";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton([
                    'text' => '➕ افزایش موجودی / شارژ', 
                    'callback_data' => '/deposit', 
                    'style' => 'success',
                ]),
                $this->makeInlineButton([
                    'text' => '🧾 گزارش تراکنش‌ها', 
                    'callback_data' => '/transactions', 
                    'style' => 'primary',
                ]),
            ])
            ->row([
                $this->makeInlineButton([
                    'text' => '👤 حساب کاربری من', 
                    'callback_data' => '/profile', 
                    'style' => 'primary',
                ]),
                $this->makeInlineButton(['text' => '🏠 بازگشت به خانه', 'callback_data' => '/start', 'style' => 'danger']),
            ]);

        $this->sendOrEditMessage($user->telegram_chat_id, $message, $keyboard, $messageId);
    }

    protected function sendReferralBanner($chatId, $user)
    {
        try {
            $botInfo = Telegram::getMe();
            $botUsername = $botInfo->getUsername();
        } catch (\Exception $e) {
            Log::error("Could not get bot username for banner: " . $e->getMessage());
            $botUsername = 'gozartest2026bot';
        }

        $referralCode = $user->referral_code ?? Str::random(8);
        $referralLink = "https://t.me/{$botUsername}?start={$referralCode}";
        
        $name = htmlspecialchars($user->name ?: 'دوستتان', ENT_QUOTES, 'UTF-8');

        // Highly-converting premium copy
        $caption = "<b>روزنه | اتصال پایدار و قابل اعتماد</b>\n\n";
        $caption .= "⚡️ <b>بزرگترین هدیه اینترنت بدون سانسور:</b>\n";
        $caption .= "دوست شما <b>{$name}</b> شما را به یک اتصال امن، بدون محدودیت و با سرعت نور دعوت کرده است!\n\n";
        $caption .= "🎁 <b>هدیه ویژه عضویت:</b>\n";
        $caption .= "همین حالا وارد ربات شو و با حل یک تست ساده ریاضی، <b>۲ گیگابایت اشتراک ماهیانه رایگان</b> برای تست اولیه دریافت کن! 🔐\n\n";
        $caption .= "🔹 سازگار با همراه اول، ایرانسل، رایتل و اینترنت خانگی\n";
        $caption .= "🔹 مناسب برای اینستاگرام، یوتیوب، تلگرام، وب‌گردی و گیمینگ 🎮\n\n";
        $caption .= "👇 جهت ورود و دریافت هدیه خود روی دکمه زیر کلیک کنید:";

        $photoCacheKey = "ref_banner_file_id_v2";
        $photoFileId = Cache::get($photoCacheKey);
        $photoSource = $photoFileId ? $photoFileId : InputFile::create(public_path('referral_banner.jpg'), 'referral_banner.jpg');

        $shareText = "اتصال پایدار روزنه را تجربه کنید.\n\nبا این لینک وارد ربات شوید و تست رایگان را دریافت کنید:\n";
        $shareUrl = "https://t.me/share/url?url=" . urlencode($referralLink) . "&text=" . urlencode($shareText);

        try {
            $sent = Telegram::sendPhoto([
                'chat_id'      => $chatId,
                'photo'        => $photoSource,
                'caption'      => $caption,
                'parse_mode'   => 'HTML',
                'reply_markup' => Keyboard::make()->inline()
                    ->row([
                        $this->makeInlineButton([
                            'text' => '🎁 دریافت هدیه ۲ گیگابایتی رایگان',
                            'url'  => $referralLink
                        ])
                    ])
                    ->row([
                        $this->makeInlineButton([
                            'text' => '📣 اشتراک‌گذاری سریع',
                            'url'  => $shareUrl
                        ])
                    ])
            ]);

            if (!$photoFileId && $sent && isset($sent['photo'])) {
                $photoArray = $sent['photo'];
                $highestPhoto = end($photoArray);
                if (isset($highestPhoto['file_id'])) {
                    Cache::put($photoCacheKey, $highestPhoto['file_id'], now()->addYears(10));
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to send referral banner: " . $e->getMessage());
            // Fallback to text message
            Telegram::sendMessage([
                'chat_id'    => $chatId,
                'text'       => $caption,
                'parse_mode' => 'HTML',
                'reply_markup' => Keyboard::make()->inline()
                    ->row([
                        $this->makeInlineButton([
                            'text' => '🎁 دریافت هدیه ۲ گیگابایتی رایگان',
                            'url'  => $referralLink
                        ])
                    ])
                    ->row([
                        $this->makeInlineButton([
                            'text' => '📣 اشتراک‌گذاری سریع',
                            'url'  => $shareUrl
                        ])
                    ])
            ]);
        }
    }    protected function sendReferralMenu($user, $messageId = null)
    {
        try {
            $botInfo = Telegram::getMe();
            $botUsername = $botInfo->getUsername();
        } catch (\Exception $e) {
            Log::error("Could not get bot username: " . $e->getMessage());
            $this->sendOrEditMainMenu($user->telegram_chat_id, "❌ خطایی در دریافت اطلاعات ربات رخ داد.", $messageId);
            return;
        }

        $referralCode = $user->referral_code ?? Str::random(8);
        if (!$user->referral_code) {
            $user->update(['referral_code' => $referralCode]);
        }

        $referralLink = "https://t.me/{$botUsername}?start={$referralCode}";
        
        // Stats
        $level1Count = User::where('referrer_id', $user->id)->count();
        $level2Count = User::whereIn('referrer_id', User::where('referrer_id', $user->id)->pluck('id'))->count();
        $level3Count = User::whereIn('referrer_id', User::whereIn('referrer_id', User::where('referrer_id', $user->id)->pluck('id'))->pluck('id'))->count();
        
        $totalEarnedMB = \App\Models\ReferralLog::where('referrer_id', $user->id)
            ->where('is_suspicious', false)
            ->sum('traffic_reward');
        $totalEarnedGB = round($totalEarnedMB / 1024, 2);

        $balanceMB = $user->referral_traffic_balance ?? 0.0;
        $balanceGB = round($balanceMB / 1024, 2);

        $anticheatStatus = $user->referral_banned 
            ? "❌ مسدود شده (فعالیت غیرمجاز)" 
            : "✅ نرمال (فعال)";

        $message = "📦 <b>هدایای معرفی روزنه</b>\n\n";
        $message .= "با دعوت از دوستانتان به روزنه، حجم هدیه دریافت کنید.\n\n";
        
        $message .= "⚖️ <b>فرمول پاداش‌دهی ۳ سطحی صندوقچه:</b>\n";
        $message .= "├ 👤 سطح ۱ (دعوت مستقیم): <code>۱.۵ گیگابایت</code> (آنی)\n";
        $message .= "├ 👥 سطح ۲ (زیرمجموعه دوستتان): <code>۱.۰ گیگابایت</code> (بعد از خرید)\n";
        $message .= "└ 👥 سطح ۳ (زیرمجموعه سطح ۲): <code>۵۰۰ مگابایت</code> (بعد از خرید)\n\n";
        
        $message .= "📊 <b>آمار صندوقچه شما:</b>\n";
        $message .= "├ 👤 زیرمجموعه‌های سطح ۱: <code>{$level1Count} نفر</code>\n";
        $message .= "├ 👥 زیرمجموعه‌های سطح ۲: <code>{$level2Count} نفر</code>\n";
        $message .= "├ 👥 زیرمجموعه‌های سطح ۳: <code>{$level3Count} نفر</code>\n";
        $message .= "├ 💎 کل حجم استخراج شده: <code>{$totalEarnedGB} گیگابایت</code>\n";
        $message .= "├ 📥 حجم آماده انتقال: <code>{$balanceGB} گیگابایت</code>\n";
        $message .= "└ 🛡️ وضعیت آنتی‌چیت: <b>{$anticheatStatus}</b>\n\n";
        
        $message .= "🔗 <b>لینک دعوت اختصاصی شما:</b>\n";
        $message .= "<code>{$referralLink}</code>\n\n";
        $message .= "💡 <i>مهم: حجم آماده انتقال را می‌توانید در هر زمان مستقیماً به اشتراک فعال خود منتقل کرده و آن را شارژ کنید.</i>";

        $shareText = "اتصال پایدار روزنه را تجربه کنید.\n\nبا این لینک وارد ربات شوید و تست رایگان را دریافت کنید:\n";
        $shareUrl = "https://t.me/share/url?url=" . urlencode($referralLink) . "&text=" . urlencode($shareText);

        $botSettings = TelegramBotSetting::pluck('value', 'key');
        $emojiReferral = $botSettings->get('emoji_referral');
        $emojiProfile = $botSettings->get('emoji_profile');
        $emojiDeposit = $botSettings->get('emoji_deposit');

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton([
                    'text' => '📣 اشتراک‌گذاری لینک دعوت', 
                    'url' => $shareUrl,
                ]),
            ])
            ->row([
                $this->makeInlineButton([
                    'text' => "📥 انتقال ترافیک هدیه ({$balanceGB} GB)",
                    'callback_data' => 'transfer_ref_traffic',
                ]),
            ])
            ->row([
                $this->makeInlineButton([
                    'text' => '🖼 دریافت بنر اختصاصی دعوت',
                    'callback_data' => 'get_referral_banner',
                    'style' => 'success'
                ]),
            ])
            ->row([
                $this->makeInlineButton([
                    'text' => '👤 حساب کاربری من', 
                    'callback_data' => '/profile', 
                    'style' => 'primary',
                ]),
                $this->makeInlineButton([
                    'text' => '💳 کیف پول و پرداخت', 
                    'callback_data' => '/wallet', 
                    'style' => 'success',
                ]),
            ])
            ->row([
                $this->makeInlineButton(['text' => '🏠 بازگشت به خانه', 'callback_data' => '/start', 'style' => 'danger']),
            ]);
            
        $this->sendOrEditMessage($user->telegram_chat_id, $message, $keyboard, $messageId);
    }

    protected function handleTransferTrafficRequest($user, $messageId, $callbackQueryId)
    {
        $balanceMB = $user->referral_traffic_balance ?? 0.0;
        if ($balanceMB <= 0) {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '❌ صندوقچه ترافیک شما خالی است! ابتدا دوستان خود را دعوت کنید.',
                'show_alert' => true
            ]);
            return;
        }

        $orders = $user->orders()
            ->where('status', 'paid')
            ->whereNotNull('plan_id')
            ->with('plan')
            ->get();

        if ($orders->isEmpty()) {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '❌ شما هیچ اشتراک فعالی ندارید. ابتدا باید یک اشتراک بخرید تا بتوانید حجم هدیه را به آن منتقل کنید.',
                'show_alert' => true
            ]);
            return;
        }

        $balanceGB = round($balanceMB / 1024, 2);

        if ($orders->count() === 1) {
            // انتقال مستقیم
            $this->processTrafficTransfer($user, $orders->first()->id, $messageId, $callbackQueryId);
            return;
        }

        // انتخاب از بین چندین اشتراک
        $message = "📥 <b>انتخاب اشتراک مقصد:</b>\n\n";
        $message .= "شما دارای <code>{$balanceGB} گیگابایت</code> ترافیک هدیه آماده انتقال هستید.\n";
        $message .= "لطفاً اشتراکی که می‌خواهید حجم به آن اضافه شود را انتخاب کنید:\n";

        $keyboard = Keyboard::make()->inline();
        foreach ($orders as $order) {
            $planName = $order->plan ? $order->plan->name : 'پلن نامشخص';
            $username = $order->panel_username ?? 'بدون نام کاربری';
            $keyboard->row([
                $this->makeInlineButton([
                    'text' => "📦 {$planName} ({$username})",
                    'callback_data' => "do_transfer_ref_{$order->id}"
                ])
            ]);
        }

        $keyboard->row([
            $this->makeInlineButton(['text' => '🔙 بازگشت', 'callback_data' => '/referral']),
        ]);

        $this->sendOrEditMessage($user->telegram_chat_id, $message, $keyboard, $messageId);
    }

    protected function processTrafficTransfer($user, $orderId, $messageId, $callbackQueryId)
    {
        $balanceMB = $user->referral_traffic_balance ?? 0.0;
        if ($balanceMB <= 0) {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '❌ صندوقچه ترافیک شما خالی است.',
                'show_alert' => true
            ]);
            return;
        }

        $order = $user->orders()->find($orderId);
        if (!$order) {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '❌ اشتراک مورد نظر یافت نشد.',
                'show_alert' => true
            ]);
            return;
        }

        try {
            $success = $this->addTrafficToAccount($order, $balanceMB);
            if ($success) {
                $user->update(['referral_traffic_balance' => 0.0]);
                $balanceGB = round($balanceMB / 1024, 2);

                Telegram::answerCallbackQuery([
                    'callback_query_id' => $callbackQueryId,
                    'text' => "✅ انتقال موفقیت‌آمیز بود! مقدار {$balanceGB} GB به اشتراک شما اضافه شد.",
                    'show_alert' => true
                ]);

                // بازگشت به منوی رفرال
                $this->sendReferralMenu($user, $messageId);
            } else {
                Telegram::answerCallbackQuery([
                    'callback_query_id' => $callbackQueryId,
                    'text' => '❌ خطایی در ارتباط با سرور رخ داد. لطفا کمی بعد تلاش کنید.',
                    'show_alert' => true
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Traffic transfer failed: " . $e->getMessage());
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '❌ خطا: ' . $e->getMessage(),
                'show_alert' => true
            ]);
        }
    }

    protected function addTrafficToAccount(Order $order, float $trafficMB)
    {
        $settings = $this->settings;
        $uniqueUsername = $order->panel_username ?? "user-{$order->user_id}-order-{$order->id}";
        $panelType = $settings->get('panel_type') ?? 'marzban';
        $trafficBytesToAdd = (int) ($trafficMB * 1024 * 1024);

        $isMultiLocationEnabled = filter_var(
            $settings->get('enable_multilocation', false),
            FILTER_VALIDATE_BOOLEAN
        );
        $isMultiServer = false;
        $targetServer = null;

        $xuiHost = $settings->get('xui_host');
        $xuiUser = $settings->get('xui_user');
        $xuiPass = $settings->get('xui_pass');
        $inboundId = (int) $settings->get('xui_default_inbound_id');

        if ($isMultiLocationEnabled && class_exists('Modules\MultiServer\Models\Server') && $order->server_id) {
            $targetServer = \Modules\MultiServer\Models\Server::find($order->server_id);
            if ($targetServer && $targetServer->is_active) {
                $isMultiServer = true;
                $panelType = 'xui';
                $xuiHost = $targetServer->full_host;
                $xuiUser = $targetServer->username;
                $xuiPass = $targetServer->password;
                $inboundId = $targetServer->inbound_id;
            }
        }

        if ($panelType === 'marzban') {
            $marzban = new MarzbanService(
                $settings->get('marzban_host'),
                $settings->get('marzban_sudo_username'),
                $settings->get('marzban_sudo_password'),
                $settings->get('marzban_node_hostname')
            );

            if (!$marzban->login()) {
                throw new \Exception("خطا در لاگین به پنل مرزبان");
            }

            $token = null;
            $loginResponse = Http::asForm()->post($settings->get('marzban_host') . '/api/admin/token', [
                'username' => $settings->get('marzban_sudo_username'),
                'password' => $settings->get('marzban_sudo_password'),
            ]);
            if ($loginResponse->successful()) {
                $token = $loginResponse->json()['access_token'];
            }

            if (!$token) {
                throw new \Exception("کد امنیتی مرزبان یافت نشد.");
            }

            $userResponse = Http::withToken($token)
                ->get($settings->get('marzban_host') . "/api/user/{$uniqueUsername}");

            if (!$userResponse->successful()) {
                throw new \Exception("کاربر در مرزبان یافت نشد.");
            }

            $userData = $userResponse->json();
            $currentLimit = (int) ($userData['data_limit'] ?? 0);
            $newLimit = $currentLimit + $trafficBytesToAdd;

            $updateResponse = $marzban->updateUser($uniqueUsername, [
                'expire' => $userData['expire'],
                'data_limit' => $newLimit,
            ]);

            return $updateResponse !== null;
        }
        elseif ($panelType === 'xui') {
            if ($inboundId <= 0) {
                throw new \Exception("Inbound ID نامعتبر");
            }

            $xui = new XUIService($xuiHost, $xuiUser, $xuiPass);
            if (!$xui->login()) {
                throw new \Exception("خطا در لاگین به پنل X-UI");
            }

            $inboundData = null;
            if ($isMultiServer) {
                $allInbounds = $xui->getInbounds();
                foreach ($allInbounds as $remoteInbound) {
                    if ($remoteInbound['id'] == $inboundId) {
                        $inboundData = $remoteInbound;
                        break;
                    }
                }
                if (!$inboundData) throw new \Exception("اینباند یافت نشد.");
            } else {
                $inboundModel = Inbound::whereJsonContains('inbound_data->id', (int)$inboundId)->first();
                if ($inboundModel) {
                    $inboundData = is_string($inboundModel->inbound_data) ? json_decode($inboundModel->inbound_data, true) : $inboundModel->inbound_data;
                } else {
                    throw new \Exception("اینباند پیش‌فرض یافت نشد.");
                }
            }

            $clients = $xui->getClients($inboundData['id']);
            $client = collect($clients)->firstWhere('email', $uniqueUsername);

            if (!$client) {
                throw new \Exception("کلاینت یافت نشد.");
            }

            $linkType = ($isMultiServer && $targetServer) ? ($targetServer->link_type ?? 'single') : $settings->get('xui_link_type', 'single');

            $currentLimit = (int) ($client['total'] ?? 0);
            $newLimit = $currentLimit + $trafficBytesToAdd;

            $clientData = [
                'id' => $client['id'],
                'email' => $uniqueUsername,
                'total' => $newLimit,
                'expiryTime' => $client['expiryTime'] ?? 0,
            ];

            if ($linkType === 'subscription' && isset($client['subId'])) {
                $clientData['subId'] = $client['subId'];
            }

            $response = $xui->updateClient($inboundData['id'], $client['id'], $clientData);
            return $response && isset($response['success']) && $response['success'];
        }

        return false;
    }

    protected function sendTransactions($user, $messageId = null)
    {
        $transactions = $user->transactions()->with('order.plan')->latest()->take(10)->get();

        $message = "🧾 *گزارش تراکنش‌های مالی*\n\n";

        if ($transactions->isEmpty()) {
            $message .= $this->escape("تاکنون هیچ گردش مالی در سیستم برای شما ثبت نشده است.");
        } else {
            foreach ($transactions as $transaction) {
                $type = 'نامشخص';
                switch ($transaction->type) {
                    case 'deposit': $type = 'شارژ کیف پول'; break;
                    case 'purchase':
                        if ($transaction->order?->renews_order_id) {
                            $type = 'تمدید سرویس';
                        } else {
                            $type = 'خرید سرویس';
                        }
                        break;
                    case 'referral_reward': $type = 'پاداش دعوت'; break;
                    case 'withdraw': $type = 'برداشت وجه'; break;
                    case 'refund': $type = 'بازگشت وجه'; break;
                    case 'manual adjustment': $type = 'اصلاح دستی'; break;
                }

                $status = '⚪️';
                switch ($transaction->status) {
                    case 'completed': $status = '✅'; break;
                    case 'pending': $status = '⏳'; break;
                    case 'failed': $status = '❌'; break;
                }

                $amount = number_format(abs($transaction->amount));
                $date = Carbon::parse($transaction->created_at)->format('Y/m/d');

                $message .= "{$status} *" . $this->escape($type) . "*\n";
                $message .= "   💸 *مبلغ:* `" . $this->escape($amount) . "` *" . $this->escape("تومان") . "*\n";
                $message .= "   📅 *تاریخ:* `" . $this->escape($date) . "`\n";
                if ($transaction->order && $transaction->order->plan) {
                    $message .= "   🏷 *سرویس:* " . $this->escape($transaction->order->plan->name) . "\n";
                }
                $message .= "〰️〰️〰️〰️〰️〰️〰️\n";
            }
        }

        $keyboard = Keyboard::make()->inline()->row([
            $this->makeInlineButton(['text' => '⬅️ کیف پول', 'callback_data' => '/wallet', 'style' => 'danger'])
        ]);

        $this->sendRawMarkdownMessage($user->telegram_chat_id, $message, $keyboard, $messageId);
    }

    protected function sendTutorialsMenu($chatId, $messageId = null)
    {
        $message = "📚 *راهنمای اتصال*\n";
        $message .= "━━━━━━━━━━━━━━━\n\n";
        $message .= "لطفاً سیستم‌عامل خود را برای دریافت راهنما انتخاب کنید:";
        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(['text' => '📱 اندروید', 'callback_data' => '/tutorial_android']),
                $this->makeInlineButton(['text' => '🍏 آیفون', 'callback_data' => '/tutorial_ios']),
            ])
            ->row([
                $this->makeInlineButton(['text' => '💻 ویندوز', 'callback_data' => '/tutorial_windows']),
                $this->makeInlineButton(['text' => '🏠 خانه', 'callback_data' => '/start', 'style' => 'danger']),
            ]);
        $this->sendOrEditMessage($chatId, $message, $keyboard, $messageId);
    }

    protected function sendTutorial($platform, $chatId, $messageId = null)
    {
        $telegramSettings = TelegramBotSetting::pluck('value', 'key');

        $settingKey = match($platform) {
            'android' => 'tutorial_android',
            'ios' => 'tutorial_ios',
            'windows' => 'tutorial_windows',
            default => null
        };

        $message = $settingKey ? ($telegramSettings->get($settingKey) ?? "آموزشی برای این پلتفرم یافت نشد.")
            : "پلتفرم نامعتبر است.";

        if ($message === "آموزشی برای این پلتفرم یافت نشد.") {
            $fallbackTutorials = [
                'android' => "*راهنمای اندروید \\(V2rayNG\\)*\n\n1\\. برنامه V2rayNG را از [این لینک](https://github.com/2dust/v2rayNG/releases) دانلود و نصب کنید\\.\n2\\. لینک کانفیگ را از بخش *سرویس‌های من* کپی کنید\\.\n3\\. در برنامه، روی علامت `+` بزنید و `Import config from Clipboard` را انتخاب کنید\\.\n4\\. کانفیگ اضافه شده را انتخاب و دکمه اتصال \\(V شکل\\) پایین صفحه را بزنید\\.",
                'ios' => "*راهنمای آیفون \\(V2Box\\)*\n\n1\\. برنامه V2Box را از [اپ استور](https://apps.apple.com/us/app/v2box-v2ray-client/id6446814690) نصب کنید\\.\n2\\. لینک کانفیگ را از بخش *سرویس‌های من* کپی کنید\\.\n3\\. در برنامه، وارد بخش `Configs` شوید، روی `+` بزنید و `Import from clipboard` را انتخاب کنید\\.\n4\\. برای اتصال، به بخش `Home` بروید و دکمه اتصال را بزنید \\(ممکن است نیاز به تایید VPN در تنظیمات گوشی باشد\\)\\.",
                'windows' => "*راهنمای ویندوز \\(V2rayN\\)*\n\n1\\. برنامه v2rayN را از [این لینک](https://github.com/2dust/v2rayN/releases) دانلود \\(فایل `v2rayN-With-Core.zip`\\) و از حالت فشرده خارج کنید\\.\n2\\. فایل `v2rayN.exe` را اجرا کنید\\.\n3\\. لینک کانفیگ را از بخش *سرویس‌های من* کپی کنید\\.\n4\\. در برنامه V2RayN، کلیدهای `Ctrl+V` را فشار دهید تا سرور اضافه شود\\.\n5\\. روی آیکون برنامه در تسک‌بار \\(کنار ساعت\\) راست کلیک کرده، از منوی `System Proxy` گزینه `Set system proxy` را انتخاب کنید تا تیک بخورد\\.\n6\\. برای اتصال، دوباره روی آیکون راست کلیک کرده و از منوی `Servers` کانفیگ اضافه شده را انتخاب کنید\\.",
            ];
            $message = $fallbackTutorials[$platform] ?? "آموزشی برای این پلتفرم یافت نشد.";
        }

        $keyboard = Keyboard::make()->inline()->row([$this->makeInlineButton(['text' => '⬅️ بازگشت به آموزش‌ها', 'callback_data' => '/tutorials'])]);

        $payload = [
            'chat_id'      => $chatId,
            'text'         => $message,
            'parse_mode'   => 'MarkdownV2',
            'reply_markup' => $keyboard,
            'disable_web_page_preview' => true
        ];

        try {
            if ($messageId) {
                $payload['message_id'] = $messageId;
                Telegram::editMessageText($payload);
            } else {
                Telegram::sendMessage($payload);
            }
        } catch (\Exception $e) {
            Log::warning("Could not edit/send tutorial message: " . $e->getMessage());
            if($messageId) {
                unset($payload['message_id']);
                try { Telegram::sendMessage($payload); } catch (\Exception $e2) {
                    Log::error("Failed fallback send tutorial: " . $e2->getMessage());
                }
            }
        }
    }

    /**
     * ⚠️ نکته: اطمینان حاصل کنید که XUIService و MarzbanService وجود دارند و متدهای لازم را دارند
     */
    protected function provisionUserAccount(Order $order, Plan $plan)
    {
        $settings = $this->settings;
        $uniqueUsername = $order->panel_username ?? "user-{$order->user_id}-order-{$order->id}";
        $configData = [
            'link' => null,
            'username' => null,
            'panel_client_id' => null,
            'panel_sub_id' => null
        ];

        $isMultiLocationEnabled = filter_var(
            $settings->get('enable_multilocation', false),
            FILTER_VALIDATE_BOOLEAN
        );

        $isMultiServer = false;
        $panelType = $settings->get('panel_type') ?? 'marzban';
        $targetServer = null; // ✅ تعریف اولیه

        // مقادیر پیش‌فرض
        $xuiHost = $settings->get('xui_host');
        $xuiUser = $settings->get('xui_user');
        $xuiPass = $settings->get('xui_pass');
        $inboundId = (int) $settings->get('xui_default_inbound_id');

        // بررسی مولتی سرور
        if ($isMultiLocationEnabled && class_exists('Modules\MultiServer\Models\Server') && $order->server_id) {
            $targetServer = \Modules\MultiServer\Models\Server::find($order->server_id);
            if ($targetServer && $targetServer->is_active) {
                $isMultiServer = true;
                $panelType = 'xui';
                $xuiHost = $targetServer->full_host;
                $xuiUser = $targetServer->username;
                $xuiPass = $targetServer->password;
                $inboundId = $targetServer->inbound_id;

                Log::info("🚀 Provisioning on MultiServer", [
                    'server_name' => $targetServer->name,
                    'server_id' => $targetServer->id,
                    'host' => parse_url($xuiHost, PHP_URL_HOST),
                    'link_type' => $targetServer->link_type ?? 'not set'
                ]);
            }
        }

        try {
            // ==========================================
            // پنل MARZBAN
            // ==========================================
            if ($panelType === 'marzban' && !$isMultiServer) {
                $marzban = new MarzbanService(
                    $settings->get('marzban_host'),
                    $settings->get('marzban_sudo_username'),
                    $settings->get('marzban_sudo_password'),
                    $settings->get('marzban_node_hostname')
                );
                $response = $marzban->createUser([
                    'username' => $uniqueUsername,
                    'proxies' => (object) [],
                    'expire' => $order->expires_at->timestamp,
                    'data_limit' => $plan->volume_gb * 1024 * 1024 * 1024,
                ]);

                if (!empty($response['subscription_url'])) {
                    $configData['link'] = $response['subscription_url'];
                    $configData['username'] = $uniqueUsername;
                } else {
                    Log::error('Marzban user creation failed.', ['response' => $response]);
                    return null;
                }
            }
            // ==========================================
            // پنل Remnawave
            // ==========================================
            elseif ($panelType === 'remnawave' && !$isMultiServer) {
                $remnawave = new RemnawaveService(
                    $settings->get('remnawave_host'),
                    $settings->get('remnawave_api_token'),
                    $settings->get('remnawave_node_hostname')
                );
                $response = $remnawave->createUser([
                    'username' => $uniqueUsername,
                    'expire' => $order->expires_at->timestamp,
                    'data_limit' => $plan->volume_gb * 1024 * 1024 * 1024,
                    'squad_uuid' => $settings->get('remnawave_squad_uuid'),
                ]);

                if (!empty($response['subscriptionUrl'])) {
                    $configData['link'] = $remnawave->generateSubscriptionLink($response);
                    $configData['username'] = $uniqueUsername;
                } else {
                    Log::error('Remnawave user creation failed.', ['response' => $response]);
                    return null;
                }
            }
            // ==========================================
            // پنل X-UI
            // ==========================================
            elseif ($panelType === 'xui') {
                $xui = new XUIService($xuiHost, $xuiUser, $xuiPass);

                if (!$xui->login()) {
                    throw new \Exception("❌ خطا در لاگین به پنل X-UI");
                }

                // تعیین اینباندهای هدف
                $targetInboundIds = [3, 7, 11, 14];
                if ($isMultiServer && $targetServer) {
                    $targetInboundIds = [$inboundId];
                } else {
                    $settingInbounds = $settings->get('xui_target_inbounds');
                    if (!empty($settingInbounds)) {
                        $parsed = is_array($settingInbounds) ? $settingInbounds : json_decode($settingInbounds, true);
                        if (!is_array($parsed)) {
                            $parsed = array_filter(array_map('trim', explode(',', (string)$settingInbounds)));
                        }
                        if (!empty($parsed)) {
                            $targetInboundIds = array_values(array_unique(array_map('intval', $parsed)));
                        }
                    }
                }

                // دریافت اطلاعات اینباند جهت لینک‌های تکی یا تانل
                $primaryInboundId = $targetInboundIds[0] ?? $inboundId;
                $inboundData = null;
                $allInbounds = $xui->getInbounds();
                if (is_array($allInbounds)) {
                    foreach ($allInbounds as $remoteInbound) {
                        if (($remoteInbound['id'] ?? null) == $primaryInboundId) {
                            $inboundData = $remoteInbound;
                            break;
                        }
                    }
                    if (!$inboundData && !empty($allInbounds)) {
                        $inboundData = $allInbounds[0];
                    }
                }

                // تعیین نوع لینک
                $linkType = ($isMultiServer && $targetServer) ? ($targetServer->link_type ?? 'single') : $settings->get('xui_link_type', 'single');

                $clientData = [
                    'email' => $uniqueUsername,
                    'total' => $plan->volume_gb * 1024 * 1024 * 1024,
                    'expiryTime' => $order->expires_at->timestamp * 1000,
                ];

                if ($linkType === 'subscription') {
                    $clientData['subId'] = Str::random(16);
                }

                Log::info("Creating XUI client across inbounds", [
                    'email' => $uniqueUsername,
                    'inbounds' => $targetInboundIds,
                    'link_type' => $linkType
                ]);

                // ساخت کاربر در پنل
                $response = $xui->addClient($targetInboundIds, $clientData);

                if ($response && isset($response['success']) && $response['success']) {
                    // استخراج اطلاعات
                    $uuid = $response['generated_uuid'] ?? null;
                    if (!$uuid && isset($response['obj']['settings'])) {
                        $rawSettings = $response['obj']['settings'];
                        $cSettings = is_array($rawSettings) ? $rawSettings : json_decode($rawSettings, true);
                        $uuid = $cSettings['clients'][0]['id'] ?? null;
                    }
                    $subId = $response['generated_subId'] ?? $clientData['subId'] ?? null;

                    $rawStream = $inboundData['streamSettings'] ?? '{}';
                    $streamSettings = is_array($rawStream) ? $rawStream : json_decode($rawStream, true);
                    $protocol = $inboundData['protocol'] ?? 'vless';
                    $inboundPort = $inboundData['port'] ?? 443;
                    $serverAddress = parse_url($xuiHost, PHP_URL_HOST);

                    switch ($linkType) {
                        case 'subscription':
                            if ($isMultiServer && $targetServer) {
                                $subDomain = $targetServer->subscription_domain ?? $serverAddress;
                                $subPort = $targetServer->subscription_port ?? 2053;
                                $subPath = $targetServer->subscription_path ?? '/sub/';
                                $isHttps = $targetServer->is_https ?? true;

                                $baseUrl = rtrim($subDomain, '/');
                                // اگر پورت هست اضافه کن
                                if ($subPort) $baseUrl .= ":{$subPort}";
                                // پروتکل
                                $protocolScheme = $isHttps ? 'https' : 'http';

                                $configLink = "{$protocolScheme}://{$baseUrl}" . rtrim($subPath, '/') . '/' . $subId;
                            } else {
                                $subBaseUrl = trim($settings->get('xui_subscription_url_base') ?? '');
                                if (!empty($subBaseUrl)) {
                                    $parsedSub = parse_url($subBaseUrl);
                                    $subPath = trim($parsedSub['path'] ?? '', '/');
                                    $configLink = !empty($subPath) 
                                        ? (rtrim($subBaseUrl, '/') . '/' . $subId) 
                                        : (rtrim($subBaseUrl, '/') . '/sub/' . $subId);
                                } else {
                                    $parsedHost = parse_url($settings->get('xui_host') ?? '');
                                    $subScheme = $parsedHost['scheme'] ?? 'https';
                                    $subHostName = $parsedHost['host'] ?? 'panel.cinemapluss.ir';
                                    $subPortNum = isset($parsedHost['port']) ? ':' . $parsedHost['port'] : '';
                                    $configLink = "{$subScheme}://{$subHostName}{$subPortNum}/sub/{$subId}";
                                }
                            }
                            break;

                        case 'tunnel':
                            if (!$uuid) throw new \Exception("UUID missing for tunnel link");

                            $tunnelAddress = $targetServer->tunnel_address;
                            $tunnelPort = $targetServer->tunnel_port ?? 443;

                            // 🔥 اصلاح مهم: خواندن وضعیت دقیق HTTPS از دیتابیس
                            $tunnelHasTls = filter_var($targetServer->tunnel_is_https, FILTER_VALIDATE_BOOLEAN);

                            $params = [];
                            $params['type'] = $streamSettings['network'] ?? 'tcp';

                            if ($tunnelHasTls) {
                                $params['security'] = 'tls';
                                $params['sni'] = $tunnelAddress;
                            } else {
                                $params['security'] = 'none';
                                // 🔥 اگر TLS خاموش است، حتما این گزینه اضافه شود
                                if ($protocol === 'vless') {
                                    $params['encryption'] = 'none';
                                }
                            }

                            if ($params['type'] === 'ws' && isset($streamSettings['wsSettings'])) {
                                $params['path'] = $streamSettings['wsSettings']['path'] ?? '/';
                                $params['host'] = $streamSettings['wsSettings']['headers']['Host'] ?? $tunnelAddress;
                            }



                            $locFlag = $targetServer->location->flag ?? '🏳️';
                            $remarkText = $locFlag . "-" . $uniqueUsername;

                            $queryString = http_build_query($params);
                            // ساخت لینک نهایی
                            $configLink = "vless://{$uuid}@{$tunnelAddress}:{$tunnelPort}?{$queryString}#" . rawurlencode($remarkText);
                            break;
                        default: // single
                            if (!$uuid) throw new \Exception("UUID missing for single link");

                            $params = [];
                            $params['type'] = $streamSettings['network'] ?? 'tcp';
                            $params['security'] = $streamSettings['security'] ?? 'none';

                            if ($params['type'] === 'ws' && isset($streamSettings['wsSettings'])) {
                                $params['path'] = $streamSettings['wsSettings']['path'] ?? '/';
                                $params['host'] = $streamSettings['wsSettings']['headers']['Host'] ?? $serverAddress;
                            }

                            if ($params['security'] === 'tls' && isset($streamSettings['tlsSettings'])) {
                                $params['sni'] = $streamSettings['tlsSettings']['serverName'] ?? $serverAddress;
                            }

                            $queryString = http_build_query(array_filter($params));
                            $configLink = "vless://{$uuid}@{$serverAddress}:{$inboundPort}?{$queryString}#" . rawurlencode($plan->name);
                            break;
                    }

                    $configData['link'] = $configLink;
                    $configData['username'] = $uniqueUsername;
                    $configData['panel_client_id'] = $uuid;
                    $configData['panel_sub_id'] = $subId;

                }
            }
            // ==========================================
            // پنل PASARGAD
            // ==========================================
            elseif ($panelType === 'pasargad') {
                $pasargad = new PasargadService(
                    $settings->get('pasargad_host'),
                    $settings->get('pasargad_sudo_username'),
                    $settings->get('pasargad_sudo_password'),
                    $settings->get('pasargad_node_hostname')
                );
                
                $response = $pasargad->createUser([
                    'username' => $uniqueUsername,
                    'expire' => $order->expires_at->timestamp,
                    'data_limit' => $plan->volume_gb * 1024 * 1024 * 1024,
                    'group_ids' => [(int)($plan->pasargad_group_id ?? $settings->get('pasargad_paid_group_id') ?? 1)],
                ]);

                if (!empty($response['subscription_url'])) {
                    $configData['link'] = $response['subscription_url'];
                    $configData['username'] = $uniqueUsername;
                } else {
                    Log::error('Pasargad user creation failed.', ['response' => $response]);
                    return null;
                }
            } else {
                throw new \Exception("Panel type not supported: {$panelType}");
            }

            if ($isMultiServer && isset($targetServer)) {
                $targetServer->increment('current_users');
            }

        } catch (\Exception $e) {
            Log::error("Failed to provision account for Order {$order->id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'server_id' => $order->server_id ?? null
            ]);

            if ($isMultiServer && isset($targetServer)) {
                $targetServer->decrement('current_users');
            }
            return null;
        }

        return $configData;
    }

    protected function showDepositOptions($user, $messageId)
    {
        $message = "💳 *شارژ کیف پول*\n\nلطفاً مبلغ مورد نظر برای شارژ را انتخاب کنید یا مبلغ دلخواه خود را وارد نمایید:";
        $keyboard = Keyboard::make()->inline();

        $telegramSettings = TelegramBotSetting::pluck('value', 'key');
        $depositAmountsJson = $telegramSettings->get('deposit_amounts', '[]');
        $depositAmountsData = json_decode($depositAmountsJson, true);

        $depositAmounts = [];
        if (is_array($depositAmountsData)) {
            foreach ($depositAmountsData as $item) {
                if (isset($item['amount']) && is_numeric($item['amount'])) {
                    $depositAmounts[] = (int)$item['amount'];
                }
            }
        }

        if (empty($depositAmounts)) {
            $depositAmounts = [50000, 100000, 200000, 500000];
        }

        sort($depositAmounts);

        foreach (array_chunk($depositAmounts, 2) as $row) {
            $rowButtons = [];
            foreach ($row as $amount) {
                $rowButtons[] = $this->makeInlineButton([
                    'text' => '💰 ' . number_format($amount) . ' ت',
                    'callback_data' => 'deposit_amount_' . $amount,
                    'style' => 'success',
                ]);
            }
            $keyboard->row($rowButtons);
        }

        $keyboard->row([$this->makeInlineButton(['text' => '✍️ مبلغ دلخواه', 'callback_data' => '/deposit_custom', 'style' => 'primary'])])
            ->row([$this->makeInlineButton(['text' => '⬅️ کیف پول', 'callback_data' => '/wallet', 'style' => 'danger'])]);

        $this->sendOrEditMessage($user->telegram_chat_id, $message, $keyboard, $messageId);
    }

    protected function promptForCustomDeposit($user, $messageId)
    {
        $user->update(['bot_state' => 'awaiting_deposit_amount']);
        $keyboard = Keyboard::make()->inline()->row([$this->makeInlineButton(['text' => '❌ انصراف', 'callback_data' => '/cancel_action', 'style' => 'danger'])]);
        $this->sendOrEditMessage($user->telegram_chat_id, "💳 لطفاً مبلغ دلخواه خود را (به تومان، حداقل ۱۰,۰۰۰) در یک پیام ارسال کنید:", $keyboard, $messageId);
    }

    protected function processDepositAmount($user, $amount, $messageId = null)
    {
        $amount = (int) preg_replace('/[^\d]/', '', $amount);
        $minDeposit = (int) $this->settings->get('min_deposit_amount', 10000);

        if ($amount < $minDeposit) {
            Telegram::sendMessage([
                'chat_id' => $user->telegram_chat_id,
                'text' => $this->escape("❌ مبلغ نامعتبر است. لطفاً مبلغی حداقل " . number_format($minDeposit) . " تومان وارد کنید."),
                'parse_mode' => 'MarkdownV2'
            ]);
            $this->promptForCustomDeposit($user, null);
            return;
        }

        $order = $user->orders()->create([
            'plan_id' => null, 'status' => 'pending', 'source' => 'telegram_deposit', 'amount' => $amount
        ]);

        $userLink = "<a href=\"tg://user?id={$user->telegram_chat_id}\">" . htmlspecialchars($user->name) . "</a>";
        $this->sendToLogChannel(
            "💳 <b>درخواست شارژ کیف پول ثبت شد</b>\n\n" .
            "🔹 <b>سفارش:</b> #{$order->id}\n" .
            "🔹 <b>کاربر:</b> {$userLink} (<code>{$user->telegram_chat_id}</code>)\n" .
            "🔹 <b>مبلغ درخواستی:</b> " . number_format($amount) . " تومان\n" .
            "⏰ <b>زمان:</b> " . now()->format('Y-m-d H:i:s')
        );

        $user->update(['bot_state' => null]);
        // انتخاب درگاه برای شارژ
        $this->showInvoice($user, $order, $messageId);
    }

    protected function sendRawMarkdownMessage($chatId, $text, $keyboard, $messageId = null, $disablePreview = false)
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'MarkdownV2',
            'reply_markup' => $keyboard,
            'disable_web_page_preview' => $disablePreview
        ];

        try {
            if ($messageId) {
                $payload['message_id'] = $messageId;
                Telegram::editMessageText($payload);
            } else {
                Telegram::sendMessage($payload);
            }
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            Log::warning('sendRawMarkdownMessage edit failed, falling back to sendMessage: ' . $errorMsg, [
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ]);

            // اگر پیام قبلی عکس/مدیا بود یا پیدا نشد، یک پیام جدید ارسال کن
            if ($messageId && (
                Str::contains($errorMsg, 'not found') ||
                Str::contains($errorMsg, 'no text in the message') ||
                Str::contains($errorMsg, 'message to edit not found') ||
                Str::contains($errorMsg, 'MESSAGE_ID_INVALID')
            )) {
                unset($payload['message_id']);
                try {
                    Telegram::sendMessage($payload);
                } catch (\Exception $ex) {
                    Log::error('sendRawMarkdownMessage fallback sendMessage also failed: ' . $ex->getMessage(), [
                        'chat_id' => $chatId,
                        'text' => substr($text, 0, 200),
                    ]);
                }
            } else {
                Log::error('sendRawMarkdownMessage unhandled error: ' . $errorMsg, [
                    'chat_id' => $chatId,
                    'text' => substr($text, 0, 200),
                ]);
            }
        }
    }


    protected function startRenewalPurchaseProcess($user, $originalOrderId, $messageId)
    {
        $originalOrder = $user->orders()->with('plan')->find($originalOrderId);

        if (!$originalOrder || !$originalOrder->plan || $originalOrder->status !== 'paid') {
            $this->sendOrEditMainMenu($user->telegram_chat_id, "❌ سرویس مورد نظر برای تمدید یافت نشد یا معتبر نیست.", $messageId);
            return;
        }

        $plan = $originalOrder->plan;
        $balance = $user->balance ?? 0;
        $expiresAt = Carbon::parse($originalOrder->expires_at);

        $message = "🔄 *تایید تمدید سرویس*\n\n";
        $message .= "▫️ سرویس: *{$this->escape($plan->name)}*\n";
        $message .= "▫️ تاریخ انقضای فعلی: *" . $this->escape($expiresAt->format('Y/m/d')) . "*\n";
        $message .= "▫️ هزینه تمدید ({$plan->duration_days} روز): *" . number_format($plan->price) . " تومان*\n";
        $message .= "▫️ موجودی کیف پول: *" . number_format($balance) . " تومان*\n\n";
        $message .= "لطفاً روش پرداخت برای تمدید را انتخاب کنید:";

        $keyboard = Keyboard::make()->inline();
        if ($balance >= $plan->price) {
            $keyboard->row([$this->makeInlineButton(['text' => '✅ تمدید با کیف پول (آنی)', 'callback_data' => "renew_pay_wallet_{$originalOrderId}", 'style' => 'success'])]);
        }
        $keyboard->row([$this->makeInlineButton(['text' => '💳 تمدید با کارت به کارت', 'callback_data' => "renew_pay_card_{$originalOrderId}", 'style' => 'primary'])])
            ->row([$this->makeInlineButton(['text' => '⬅️ بازگشت به سرویس‌ها', 'callback_data' => '/my_services'])]);

        $this->sendOrEditMessage($user->telegram_chat_id, $message, $keyboard, $messageId);
    }

    /**
     * ✅ اصلاح: استفاده از & برای دسترسی به متغیرها پس از تراکنش
     */
    protected function processRenewalWalletPayment($user, $originalOrderId, $messageId)
    {
        $originalOrder = $user->orders()->with('plan')->find($originalOrderId);
        $newRenewalOrder = null; // ✅ تعریف اولیه
        $provisionData = null;   // ✅ تعریف اولیه

        // بررسی‌های اولیه
        if (!$originalOrder || !$originalOrder->plan || $originalOrder->status !== 'paid') {
            $this->sendOrEditMainMenu($user->telegram_chat_id, "❌ سرویس مورد نظر برای تمدید یافت نشد.", $messageId);
            return;
        }

        $plan = $originalOrder->plan;

        // بررسی موجودی قبل از هر کاری
        if ($user->balance < $plan->price) {
            $keyboard = Keyboard::make()->inline()
                ->row([
                    $this->makeInlineButton(['text' => '💳 شارژ کیف پول', 'callback_data' => '/deposit']),
                    $this->makeInlineButton(['text' => '⬅️ بازگشت', 'callback_data' => '/my_services'])
                ]);
            $this->sendOrEditMessage($user->telegram_chat_id, "❌ موجودی کیف پول شما برای تمدید کافی نیست.", $keyboard, $messageId);
            return;
        }

        try {
            DB::transaction(function () use ($user, $originalOrder, $plan, &$newRenewalOrder, &$provisionData) { // ✅ & اضافه شد

                $user->decrement('balance', $plan->price);

                $newRenewalOrder = $user->orders()->create([
                    'plan_id' => $plan->id,
                    'status' => 'paid',

                    'source' => 'telegram_renewal',
                    'amount' => $plan->price,
                    'expires_at' => null,
                    'payment_method' => 'wallet',
                    'panel_username' => $originalOrder->panel_username,
                ]);

                $newRenewalOrder->renews_order_id = $originalOrder->id;
                $newRenewalOrder->save();

                Transaction::create([
                    'user_id' => $user->id,
                    'order_id' => $newRenewalOrder->id,
                    'amount' => -$plan->price,
                    'type' => 'purchase',
                    'status' => 'completed',
                    'description' => "تمدید سرویس {$plan->name} (سفارش اصلی #{$originalOrder->id})"
                ]);

                $provisionData = $this->renewUserAccount($originalOrder, $plan);

                if (!$provisionData) {
                    throw new \Exception('تمدید در پنل با خطا مواجه شد.');
                }
            });

            // حالا متغیرها پر شده‌اند
            $newExpiryDate = Carbon::parse($originalOrder->refresh()->expires_at);
            $daysText = $this->escape($plan->duration_days . ' روز');
            $dateText = $this->escape($newExpiryDate->format('Y/m/d'));
            $planName = $this->escape($plan->name);

            $linkCode = $provisionData['link'];

            $successMessage = "⚡️ *سرویس شما با قدرت تمدید شد!* ⚡️\n\n";
            $successMessage .= "💎 *پلن:* {$planName}\n";
            $successMessage .= "⏳ *مدت افزوده شده:* {$daysText}\n";
            $successMessage .= "📅 *انقضای جدید:* {$dateText}\n\n";
            $successMessage .= "🔗 *لینک اتصال شما (بدون تغییر):*\n";
            $successMessage .= "👇 _برای کپی روی لینک زیر ضربه بزنید_\n";
            $successMessage .= "{$linkCode}";
            $userLink = "<a href=\"tg://user?id={$user->telegram_chat_id}\">" . htmlspecialchars($user->name) . "</a>";
            $this->sendToLogChannel(
                "🔄 <b>تمدید موفق اشتراک با کیف پول</b>\n\n" .
                "🔹 <b>سفارش اصلی:</b> #{$originalOrderId}\n" .
                "🔹 <b>سفارش تمدید:</b> #{$newRenewalOrder->id}\n" .
                "🔹 <b>کاربر:</b> {$userLink} (<code>{$user->telegram_chat_id}</code>)\n" .
                "🔹 <b>پلن:</b> " . htmlspecialchars($plan->name) . "\n" .
                "🔹 <b>مبلغ تمدید:</b> " . number_format($plan->price) . " تومان\n" .
                "🔹 <b>نام کاربری:</b> <code>{$originalOrder->panel_username}</code>\n" .
                "🔹 <b>انقضای جدید:</b> " . $newExpiryDate->format('Y/m/d') . "\n" .
                "⏰ <b>زمان:</b> " . now()->format('Y-m-d H:i:s')
            );

            $this->sendOrEditMessage($user->telegram_chat_id, $successMessage, $keyboard, $messageId);

        } catch (\Exception $e) {
            Log::error('Renewal Wallet Payment Failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'original_order_id' => $originalOrderId,
                'user_id' => $user->id
            ]);

            if ($newRenewalOrder) {
                try {
                    $user->increment('balance', $plan->price);
                } catch (\Exception $refundEx) {
                    Log::critical("Failed to refund user {$user->id}: " . $refundEx->getMessage());
                }
                $newRenewalOrder->delete();
            }

            $errorKeyboard = Keyboard::make()->inline()->row([
                $this->makeInlineButton(['text' => '💬 پشتیبانی', 'callback_data' => '/support_menu'])
            ]);

            $errorMessage = $this->escape("⚠️ تمدید با خطا مواجه شد. مبلغ {$plan->price} تومان به کیف پول شما بازگردانده شد.");
            $this->sendOrEditMessage($user->telegram_chat_id, $errorMessage, $errorKeyboard, $messageId);
        }
    }

    /**
     * ارسال لینک خام (بدون فرمت) برای کپی آسان
     */
    protected function handleCopyLinkRequest($user, $orderId, $messageId = null)
    {
        try {
            $order = $user->orders()->with('plan')->find($orderId);

            if (!$order || $order->status !== 'paid') {
                Telegram::sendMessage([
                    'chat_id' => $user->telegram_chat_id,
                    'text' => $this->escape("❌ سفارش یافت نشد یا معتبر نیست."),
                    'parse_mode' => 'MarkdownV2'
                ]);
                return;
            }

            if (empty($order->config_details)) {
                Telegram::sendMessage([
                    'chat_id' => $user->telegram_chat_id,
                    'text' => $this->escape("❌ لینک کانفیگ هنوز آماده نیست."),
                    'parse_mode' => 'MarkdownV2'
                ]);
                return;
            }

            // ارسال لینک خالی (بدون markdown) که کاربر بتواند کپی کند
            Telegram::sendMessage([
                'chat_id' => $user->telegram_chat_id,
                'text' => $order->config_details, // فقط لینک خالی بدون هیچ فرمتی
                'reply_markup' => Keyboard::make()->inline()->row([
                    $this->makeInlineButton(['text' => '⬅️ بازگشت به جزئیات سرویس', 'callback_data' => "show_service_{$orderId}"])
                ])
            ]);

        } catch (\Exception $e) {
            Log::error('Copy link error: ' . $e->getMessage());
            Telegram::sendMessage([
                'chat_id' => $user->telegram_chat_id,
                'text' => $this->escape("❌ خطا در ارسال لینک."),
                'parse_mode' => 'MarkdownV2'
            ]);
        }
    }


    protected function handleRenewCardPayment($user, $originalOrderId, $messageId)
    {
        $originalOrder = $user->orders()->with('plan')->find($originalOrderId);
        if (!$originalOrder || !$originalOrder->plan || $originalOrder->status !== 'paid') {
            $this->sendOrEditMainMenu($user->telegram_chat_id, "❌ سرویس مورد نظر برای تمدید یافت نشد.", $messageId);
            return;
        }
        $plan = $originalOrder->plan;

        $newRenewalOrder = $user->orders()->create([
            'plan_id' => $plan->id,
            'server_id' => $originalOrder->server_id,
            'status' => 'pending',
            'source' => 'telegram_renewal',
            'amount' => $plan->price,
            'expires_at' => null,
            'panel_username' => $originalOrder->panel_username,
        ]);

        $newRenewalOrder->renews_order_id = $originalOrder->id;
        $newRenewalOrder->save();

        $this->sendCardPaymentInfo($user->telegram_chat_id, $newRenewalOrder->id, $messageId);
    }

    /**
     * ⚠️ نکته: اطمینان حاصل کنید که متدهای updateUser و resetUserTraffic در MarzbanService
     * و updateClient و resetClientTraffic در XUIService وجود دارند
     */
    protected function renewUserAccount(Order $originalOrder, Plan $plan)
    {
        $settings = $this->settings;
        $user = $originalOrder->user;
        $uniqueUsername = $originalOrder->panel_username ?? "user-{$user->id}-order-{$originalOrder->id}";

        $isMultiLocationEnabled = filter_var(
            $settings->get('enable_multilocation', false),
            FILTER_VALIDATE_BOOLEAN
        );

        $currentExpiresAt = Carbon::parse($originalOrder->expires_at);
        $baseDate = $currentExpiresAt->isPast() ? now() : $currentExpiresAt;
        $newExpiryDate = $baseDate->copy()->addDays($plan->duration_days);

        $isMultiServer = false;
        $panelType = $settings->get('panel_type') ?? 'marzban';
        $targetServer = null;

        $xuiHost = $settings->get('xui_host');
        $xuiUser = $settings->get('xui_user');
        $xuiPass = $settings->get('xui_pass');
        $inboundId = (int) $settings->get('xui_default_inbound_id');

        // بررسی مولتی سرور
        if ($isMultiLocationEnabled && class_exists('Modules\MultiServer\Models\Server') && $originalOrder->server_id) {
            $targetServer = \Modules\MultiServer\Models\Server::find($originalOrder->server_id);
            if ($targetServer && $targetServer->is_active) {
                $isMultiServer = true;
                $panelType = 'xui';
                $xuiHost = $targetServer->full_host;
                $xuiUser = $targetServer->username;
                $xuiPass = $targetServer->password;
                $inboundId = $targetServer->inbound_id;
            }
        }

        try {
            // --- MARZBAN ---
            if ($panelType === 'marzban') {
                $marzban = new MarzbanService(
                    $settings->get('marzban_host'),
                    $settings->get('marzban_sudo_username'),
                    $settings->get('marzban_sudo_password'),
                    $settings->get('marzban_node_hostname')
                );

                $updateResponse = $marzban->updateUser($uniqueUsername, [
                    'expire' => $newExpiryDate->timestamp,
                    'data_limit' => $plan->volume_gb * 1073741824,
                ]);
                $resetResponse = $marzban->resetUserTraffic($uniqueUsername);

                if ($updateResponse !== null && $resetResponse !== null) {
                    $originalOrder->update(['expires_at' => $newExpiryDate]);
                    return [
                        'link' => $originalOrder->config_details,
                        'username' => $uniqueUsername
                    ];
                } else {
                    return null;
                }
            }
            // --- REMNAWAVE ---
            elseif ($panelType === 'remnawave') {
                $remnawave = new RemnawaveService(
                    $settings->get('remnawave_host'),
                    $settings->get('remnawave_api_token'),
                    $settings->get('remnawave_node_hostname')
                );

                $updateResponse = $remnawave->updateUser($uniqueUsername, [
                    'expire' => $newExpiryDate->timestamp,
                    'data_limit' => $plan->volume_gb * 1073741824,
                ]);
                $resetResponse = $remnawave->resetTraffic($uniqueUsername);

                if ($updateResponse !== null && $resetResponse !== null) {
                    $originalOrder->update(['expires_at' => $newExpiryDate]);
                    return [
                        'link' => $originalOrder->config_details, // or regenerate
                        'username' => $uniqueUsername
                    ];
                } else {
                    return null;
                }
            }
            // --- X-UI (SANAEI) ---
            elseif ($panelType === 'xui') {
                if ($inboundId <= 0) {
                    throw new \Exception("❌ Inbound ID نامعتبر: {$inboundId}");
                }

                $xui = new XUIService($xuiHost, $xuiUser, $xuiPass);

                if (!$xui->login()) {
                    throw new \Exception("❌ خطا در لاگین به پنل X-UI");
                }

                // گرفتن اطلاعات اینباند
                $inboundData = null;
                if ($isMultiServer) {
                    $allInbounds = $xui->getInbounds();
                    foreach ($allInbounds as $remoteInbound) {
                        if ($remoteInbound['id'] == $inboundId) {
                            $inboundData = $remoteInbound;
                            break;
                        }
                    }
                    if (!$inboundData) throw new \Exception("اینباند در سرور یافت نشد.");
                } else {
                    $inboundModel = Inbound::whereJsonContains('inbound_data->id', (int)$inboundId)->first();
                    if ($inboundModel) {
                        $inboundData = is_string($inboundModel->inbound_data) ? json_decode($inboundModel->inbound_data, true) : $inboundModel->inbound_data;
                    } else {
                        throw new \Exception("اینباند پیش‌فرض یافت نشد.");
                    }
                }

                // پیدا کردن کلاینت قبلی
                $clients = $xui->getClients($inboundData['id']);
                $client = collect($clients)->firstWhere('email', $uniqueUsername);

                if (!$client) {
                    throw new \Exception("❌ کلاینت با ایمیل {$uniqueUsername} یافت نشد.");
                }

                $linkType = ($isMultiServer && $targetServer) ? ($targetServer->link_type ?? 'single') : $settings->get('xui_link_type', 'single');

                $clientData = [
                    'id' => $client['id'],
                    'email' => $uniqueUsername,
                    'total' => $plan->volume_gb * 1073741824, // حجم جدید بر حسب بایت
                    'expiryTime' => $newExpiryDate->timestamp * 1000, // زمان انقضای جدید
                ];

                if ($linkType === 'subscription' && isset($client['subId'])) {
                    $clientData['subId'] = $client['subId'];
                }

                // ۱. آپدیت کردن زمان و حجم کلی
                $response = $xui->updateClient($inboundData['id'], $client['id'], $clientData);

                if ($response && isset($response['success']) && $response['success']) {

                    // 🔥 ۲. ریست کردن ترافیک مصرفی (مهم برای تمدید) 🔥
                    $resetResult = $xui->resetClientTraffic($inboundData['id'], $uniqueUsername);

                    if ($resetResult) {
                        Log::info("Traffic reset successful for user: $uniqueUsername");
                    } else {
                        Log::warning("Traffic reset FAILED for user: $uniqueUsername");
                    }

                    $originalOrder->update(['expires_at' => $newExpiryDate]);
                    return [
                        'link' => $originalOrder->config_details,
                        'username' => $uniqueUsername
                    ];
                }
            }
            // --- PASARGAD ---
            elseif ($panelType === 'pasargad') {
                $pasargad = new PasargadService(
                    $settings->get('pasargad_host'),
                    $settings->get('pasargad_sudo_username'),
                    $settings->get('pasargad_sudo_password'),
                    $settings->get('pasargad_node_hostname')
                );

                $updateResponse = $pasargad->updateUser($uniqueUsername, [
                    'expire' => $newExpiryDate->timestamp,
                    'data_limit' => $plan->volume_gb * 1073741824,
                    'status' => 'active',
                ]);
                $resetResponse = $pasargad->resetUserTraffic($uniqueUsername);

                if ($updateResponse !== null && $resetResponse !== false) {
                    $originalOrder->update(['expires_at' => $newExpiryDate]);
                    return [
                        'link' => $originalOrder->config_details,
                        'username' => $uniqueUsername
                    ];
                } else {
                    Log::error('Pasargad renewal failed.', ['update' => $updateResponse, 'reset' => $resetResponse]);
                    return null;
                }
            } else {
                throw new \Exception("❌ نوع پنل پشتیبانی نمی‌شود: {$panelType}");
            }
        } catch (\Exception $e) {
            Log::error("❌ تمدید انجام نشد ({$uniqueUsername}): " . $e->getMessage(), [
                'is_multi_server' => $isMultiServer,
                'server_id' => $originalOrder->server_id ?? null
            ]);
            return null;
        }
    }
    protected function showSupportMenu($user, $messageId = null)
    {
        $tickets = $user->tickets()->latest()->take(5)->get();
        $message = "🎧 *" . $this->escape("پشتیبانی روزنه") . "*\n\n";
        $message .= $this->escape("مشکلی هست؟ تیکت بزن، سریع پیگیری می‌کنیم.") . "\n\n";
        if ($tickets->isEmpty()) {
            $message .= "🍃 " . $this->escape("در حال حاضر هیچ مکاتبه‌ای (تیکت) از سوی شما در سیستم ثبت نشده است.");
        } else {
            $message .= "📂 *" . $this->escape("سوابق مکاتبات شما با کارشناسان:") . "*\n";
            foreach ($tickets as $ticket) {
                $status = match ($ticket->status) {
                    'open' => '🔵 باز',
                    'answered' => '🟢 پاسخ ادمین',
                    'closed' => '⚪️ بسته',
                    default => '⚪️ نامشخص',
                };
                $ticketIdEscaped = $ticket->id;
                $message .= "\n📌 *تیکت \\#{$ticketIdEscaped}* \\| " . $this->escape($status) . "\n";
                $message .= "*موضوع:* " . $this->escape($ticket->subject) . "\n";
                $message .= "_{$this->escape($ticket->updated_at->diffForHumans())}_";
            }
        }

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton([
                    'text' => '👨🏻‍💻 پشتیبانی آنلاین تلگرام (@RoozanehHelp)',
                    'url' => 'https://t.me/RoozanehHelp',
                    'style' => 'success'
                ]),
            ])
            ->row([
                $this->makeInlineButton(['text' => '📝 ثبت تیکت جدید', 'callback_data' => '/support_new', 'style' => 'primary']),
                $this->makeInlineButton(['text' => '📖 راهنما و آموزش‌ها', 'callback_data' => '/tutorials', 'style' => 'primary'])
            ]);
        foreach ($tickets as $ticket) {
            if ($ticket->status !== 'closed') {
                $keyboard->row([
                    $this->makeInlineButton(['text' => "✏️ پاسخ/مشاهده تیکت #{$ticket->id}", 'callback_data' => "reply_ticket_{$ticket->id}"]),
                    $this->makeInlineButton(['text' => "❌ بستن تیکت #{$ticket->id}", 'callback_data' => "close_ticket_{$ticket->id}"]),
                ]);
            }
        }
        $keyboard->row([$this->makeInlineButton(['text' => '🏠 بازگشت به خانه', 'callback_data' => '/start', 'style' => 'danger'])]);
        $this->sendOrEditMessage($user->telegram_chat_id, $message, $keyboard, $messageId);
    }

    protected function promptForNewTicket($user, $messageId)
    {
        $user->update(['bot_state' => 'awaiting_new_ticket_subject']);
        $keyboard = Keyboard::make()->inline()->row([$this->makeInlineButton(['text' => '❌ انصراف', 'callback_data' => '/cancel_action', 'style' => 'danger'])]);
        $this->sendOrEditMessage($user->telegram_chat_id, "📝 *" . $this->escape("لطفاً در یک پیام کوتاه، موضوع درخواست یا مشکل خود را مطرح کنید (مثال: تنظیمات آیفون):") . "*", $keyboard, $messageId);
    }

    protected function promptForTicketReply($user, $ticketId, $messageId)
    {
        $ticketIdEscaped = $this->escape($ticketId);
        $user->update(['bot_state' => 'awaiting_ticket_reply|' . $ticketId]);
        $keyboard = Keyboard::make()->inline()->row([$this->makeInlineButton(['text' => '❌ انصراف', 'callback_data' => '/cancel_action', 'style' => 'danger'])]);
        $this->sendOrEditMessage($user->telegram_chat_id, "✏️ *" . $this->escape("کارشناس ما منتظر پاسخ است. لطفاً ادامه پیام خود را برای این تیکت بنویسید (ارسال اسکرین‌شات از مشکل هم مجاز است):") . "*", $keyboard, $messageId);
    }

    protected function closeTicket($user, $ticketId, $messageId, $callbackQueryId)
    {
        $ticket = $user->tickets()->where('id', $ticketId)->first();
        if ($ticket && $ticket->status !== 'closed') {
            $ticket->update(['status' => 'closed']);
            try {
                Telegram::answerCallbackQuery([
                    'callback_query_id' => $callbackQueryId,
                    'text' => "تیکت #{$ticketId} بسته شد.",
                    'show_alert' => false
                ]);
            } catch (\Exception $e) { Log::warning("Could not answer close ticket query: ".$e->getMessage());}
            $this->showSupportMenu($user, $messageId);
        } else {
            try { Telegram::answerCallbackQuery(['callback_query_id' => $callbackQueryId, 'text' => "تیکت یافت نشد یا قبلا بسته شده.", 'show_alert' => true]); } catch (\Exception $e) {}
        }
    }

    protected function processTicketConversation($user, $text, $update)
    {
        $state = $user->bot_state;
        $chatId = $user->telegram_chat_id;

        try {
            if ($state === 'awaiting_new_ticket_subject') {
                if (mb_strlen($text) < 3) {
                    Telegram::sendMessage(['chat_id' => $chatId, 'text' => $this->escape("❌ موضوع باید حداقل ۳ حرف باشد. لطفا دوباره تلاش کنید."), 'parse_mode' => 'MarkdownV2']);
                    return;
                }
                $user->update(['bot_state' => 'awaiting_new_ticket_message|' . $text]);
                Telegram::sendMessage(['chat_id' => $chatId, 'text' => $this->escape("✅ موضوع دریافت شد.\n\nحالا *متن پیام* خود را وارد کنید (می‌توانید همراه پیام، عکس هم ارسال کنید):"), 'parse_mode' => 'MarkdownV2']);

            } elseif (Str::startsWith($state, 'awaiting_new_ticket_message|')) {
                $subject = Str::after($state, 'awaiting_new_ticket_message|');
                $isPhotoOnly = $update->getMessage()->has('photo') && (empty(trim($text)) || $text === '[📎 فایل پیوست شد]');
                $messageText = $isPhotoOnly ? '[📎 پیوست تصویر]' : $text;

                if (empty(trim($messageText))) {
                    Telegram::sendMessage(['chat_id' => $chatId, 'text' => $this->escape("❌ متن پیام نمی‌تواند خالی باشد. لطفا پیام خود را وارد کنید:"), 'parse_mode' => 'MarkdownV2']);
                    return;
                }

                $ticket = $user->tickets()->create([
                    'subject' => $subject,
                    'message' => $messageText,
                    'priority' => 'medium', 'status' => 'open', 'source' => 'telegram', 'user_id' => $user->id
                ]);

                $replyData = ['user_id' => $user->id, 'message' => $messageText];
                if ($update->getMessage()->has('photo')) {
                    try { $replyData['attachment_path'] = $this->savePhotoAttachment($update, 'ticket_attachments'); }
                    catch (\Exception $e) { Log::error("Error saving photo for new ticket {$ticket->id}: " . $e->getMessage()); }
                }
                $reply = $ticket->replies()->create($replyData);

                $user->update(['bot_state' => null]);
                Telegram::sendMessage(['chat_id' => $chatId, 'text' => $this->escape("✅ تیکت #{$ticket->id} با موفقیت ثبت شد."), 'parse_mode' => 'MarkdownV2']);
                $this->sendOrEditMainMenu($chatId, "پشتیبانی به زودی پاسخ شما را خواهد داد\.");

                event(new TicketCreated($ticket));

            } elseif (Str::startsWith($state, 'awaiting_ticket_reply|')) {
                $ticketId = Str::after($state, 'awaiting_ticket_reply|');
                $ticket = $user->tickets()->find($ticketId);

                if (!$ticket) {
                    $this->sendOrEditMainMenu($chatId, "❌ تیکت مورد نظر یافت نشد.");
                    return;
                }

                $isPhotoOnly = $update->getMessage()->has('photo') && (empty(trim($text)) || $text === '[📎 فایل پیوست شد]');
                $messageText = $isPhotoOnly ? '[📎 پیوست تصویر]' : $text;

                if (empty(trim($messageText))) {
                    Telegram::sendMessage(['chat_id' => $chatId, 'text' => $this->escape("❌ متن پاسخ نمی‌تواند خالی باشد."), 'parse_mode' => 'MarkdownV2']);
                    return;
                }

                $replyData = ['user_id' => $user->id, 'message' => $messageText];
                if ($update->getMessage()->has('photo')) {
                    try { $replyData['attachment_path'] = $this->savePhotoAttachment($update, 'ticket_attachments'); }
                    catch (\Exception $e) { Log::error("Error saving photo for ticket reply {$ticketId}: " . $e->getMessage()); }
                }
                $reply = $ticket->replies()->create($replyData);
                $ticket->update(['status' => 'open']);

                $user->update(['bot_state' => null]);
                Telegram::sendMessage(['chat_id' => $chatId, 'text' => $this->escape("✅ پاسخ شما برای تیکت #{$ticketId} ثبت شد."), 'parse_mode' => 'MarkdownV2']);
                $this->sendOrEditMainMenu($chatId, "پشتیبانی به زودی پاسخ شما را خواهد داد\.");

                event(new TicketReplied($reply));
            }
        } catch (\Exception $e) {
            Log::error('Failed to process ticket conversation: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $user->update(['bot_state' => null]);
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $this->escape("❌ خطایی در پردازش پیام شما رخ داد. لطفاً دوباره تلاش کنید."),
                'parse_mode' => 'MarkdownV2'
            ]);
        }
    }

    /**
     * ✅ اصلاح: حذف فاصله اضافی از URL و اضافه کردن import Http facade
     */
    protected function isUserMemberOfChannel($user)
    {
        $forceJoin = $this->settings->get('force_join_enabled', '0');

        if (!in_array($forceJoin, ['1', 1, true, 'on'], true)) {
            return true;
        }

        $channelId = $this->settings->get('telegram_required_channel_id');
        if (empty($channelId)) {
            Log::error('FORCE JOIN IS ENABLED BUT NO CHANNEL ID IS SET!');
            return false;
        }

        try {
            $botToken = $this->settings->get('telegram_bot_token');
            // ✅ اصلاح: حذف space بین bot و token



            $apiUrl = "https://api.telegram.org/bot{$botToken}/getChatMember";


            $response = Http::timeout(10)->get($apiUrl, [
                'chat_id' => $channelId,
                'user_id' => $user->telegram_chat_id,
            ]);

            if (!$response->successful()) {
                return false;
            }

            $data = $response->json();
            $status = $data['result']['status'] ?? 'left';

            return in_array($status, ['member', 'administrator', 'creator'], true);

        } catch (\Exception $e) {
            Log::error("Membership check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ اصلاح: حذف فاصله اضافی از URL
     */
    protected function showChannelRequiredMessage($chatId, $messageId = null)
    {
        $channelId = $this->settings->get('telegram_required_channel_id');

        if (empty($channelId)) {
            $message = "❌ خطا: کانال عضویت اجباری تنظیم نشده است.";
            $this->sendOrEditMessage($chatId, $message, null, $messageId);
            return;
        }

        $channelLink = null;
        $channelDisplayName = $channelId;

        if (str_starts_with($channelId, '@')) {
            $username = ltrim($channelId, '@');
            // ✅ اصلاح: حذف space بعد از t.me/



            $channelLink = "https://t.me/{$username}";


            $channelDisplayName = "@" . $username;
        } elseif (preg_match('/^-100\d+$/', $channelId)) {
            $channelDisplayName = "کانال خصوصی";
            $channelLink = $this->settings->get('telegram_private_channel_invite_link');
        }

        $message = "⛔️ *عضویت در کانال الزامی است!*\n\n";
        $message .= "برای ادامه استفاده از ربات، باید در کانال زیر عضو شوید:\n\n";
        $message .= "📢 {$channelDisplayName}\n\n";
        $message .= "🔹 پس از عضویت، روی دکمه «✅ بررسی عضویت» بزنید.";

        $keyboard = Keyboard::make()->inline();

        if (!empty($channelLink)) {
            $keyboard->row([$this->makeInlineButton(['text' => '📲 عضویت در کانال', 'url' => $channelLink])]);
        }

        $keyboard->row([$this->makeInlineButton(['text' => '✅ بررسی عضویت', 'callback_data' => '/check_membership'])]);

        $this->sendOrEditMessage($chatId, $message, $keyboard, $messageId);
    }

    /**
     * ✅ اصلاح: حذف فاصله اضافی از URL دانلود فایل
     */

    /**
     * ارسال پیام موفقیت‌آمیز بودن خرید با دکمه‌ها
     * این متد هم برای پرداخت کیف پول و هم کارت به کارت استفاده می‌شه
     */
    protected function sendPurchaseSuccessMessage($user, Order $order, $messageId = null)
    {
        // بارگذاری اطلاعات کامل سفارش
        $order->load(['server.location', 'plan']);

        $link = $order->config_details;

        // آماده‌سازی اطلاعات سرور و کشور
        $serverName = 'سرور اصلی';
        $locationFlag = '🏳️';
        $locationName = 'نامشخص';

        if ($order->server) {
            $serverName = $order->server->name;
            if ($order->server->location) {
                $locationFlag = $order->server->location->flag ?? '🏳️';
                $locationName = $order->server->location->name;
            }
        }

        // ساخت پیام کامل و خفن
        $message = "✅ *خرید موفق!*\n\n";
        $message .= "📦 *پلن:* `{$this->escape($order->plan->name)}`\n";
        $message .= "🌍 *موقعیت:* {$locationFlag} {$this->escape($locationName)}\n";
        $message .= "🖥 *سرور:* {$this->escape($serverName)}\n";
        $message .= "💾 *حجم:* {$order->plan->volume_gb} گیگابایت\n";
        $message .= "📅 *مدت:* {$order->plan->duration_days} روز\n";
        $message .= "⏳ *انقضا:* `{$order->expires_at->format('Y/m/d H:i')}`\n";
        $message .= "👤 *یوزرنیم:* `{$order->panel_username}`\n\n";
        $message .= "🔗 *لینک کانفیگ شما:*\n";
        $message .= "`{$link}`\n\n";
        $message .= "⚠️ روی لینک بالا کلیک کنید تا کپی شود";

        // کیبورد با دکمه‌های کاربردی
        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(['text' => '📋 کپی لینک کانفیگ', 'callback_data' => "copy_link_{$order->id}"]),
                $this->makeInlineButton(['text' => '📱 QR Code', 'callback_data' => "qrcode_order_{$order->id}"])
            ])
            ->row([
                $this->makeInlineButton(['text' => '🛠 سرویس‌های من', 'callback_data' => '/my_services']),
                $this->makeInlineButton(['text' => '🏠 خانه', 'callback_data' => '/start', 'style' => 'danger'])
            ]);

        try {
            if ($messageId) {
                // ویرایش پیام قبلی (اگر وجود داشته باشه)
                Telegram::editMessageText([
                    'chat_id' => $user->telegram_chat_id,
                    'message_id' => $messageId,
                    'text' => $message,
                    'parse_mode' => 'MarkdownV2',
                    'reply_markup' => $keyboard
                ]);
            } else {
                // ارسال پیام جدید
                Telegram::sendMessage([
                    'chat_id' => $user->telegram_chat_id,
                    'text' => $message,
                    'parse_mode' => 'MarkdownV2',
                    'reply_markup' => $keyboard
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending purchase success message: ' . $e->getMessage());
            // اگر خطا بود، بدون کیبورد بفرست (fallback)
            Telegram::sendMessage([
                'chat_id' => $user->telegram_chat_id,
                'text' => $message,
                'parse_mode' => 'MarkdownV2'
            ]);
        }
    }

    protected function savePhotoAttachment($update, $directory)
    {
        $photo = collect($update->getMessage()->getPhoto())->last();
        if(!$photo) return null;

        $botToken = $this->settings->get('telegram_bot_token');
        if (!$botToken) $botToken = config('telegrambot.bot_token');
        $botToken = trim($botToken, '"\' ');

        try {
            $file = Telegram::getFile(['file_id' => $photo->getFileId()]);
            // Handle both array and object responses from Telegram SDK
            if (is_array($file)) {
                $filePath = $file['file_path'] ?? null;
            } else {
                $filePath = method_exists($file, 'getFilePath') ? $file->getFilePath() : ($file['file_path'] ?? null);
            }
            
            if(!$filePath) { throw new \Exception('File path not found in Telegram response.'); }

            $url = "https://api.telegram.org/file/bot{$botToken}/{$filePath}";
            
            // Use stream context for better error handling
            $opts = [
                "http" => [
                    "method" => "GET",
                    "header" => "User-Agent: VPNMarketBot/1.0\r\n"
                ]
            ];
            $context = stream_context_create($opts);
            $fileContents = file_get_contents($url, false, $context);
            
            if ($fileContents === false) { 
                $error = error_get_last();
                throw new \Exception('Failed to download file content from: ' . $url . ' - Error: ' . ($error['message'] ?? 'Unknown'));
            }

            $storagePath = storage_path("app/public/{$directory}");
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            $extension = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'jpg';
            $fileName = $directory . '/' . Str::random(40) . '.' . $extension;
            
            // Explicitly use public disk
            $success = Storage::disk('public')->put($fileName, $fileContents);

            if (!$success) { throw new \Exception('Failed to save file to storage.'); }

            return $fileName;

        } catch (\Exception $e) {
            Log::error('Error saving photo attachment: ' . $e->getMessage(), ['file_id' => $photo->getFileId()]);
            return null;
        }
    }


    protected function handleTrialRequest($user, $extraUsername = null, $callbackQueryId = null, $messageId = null)
    {
        $settings = $this->settings;
        $chatId = $user->telegram_chat_id;

        // جلوگیری از پردازش تکراری (وقتی تلگرام webhook را چندین بار می‌زند)
        $lockKey = "trial_processing_{$user->id}";
        if (\Illuminate\Support\Facades\Cache::has($lockKey)) {
            Log::info('Trial duplicate webhook blocked', ['user_id' => $user->id]);
            if ($callbackQueryId) {
                try {
                    Telegram::answerCallbackQuery([
                        'callback_query_id' => $callbackQueryId,
                        'text' => '⏳ در حال ساخت اکانت، لطفاً شکیبا باشید...',
                        'show_alert' => false
                    ]);
                } catch (\Exception $e) {}
            }
            return;
        }
        \Illuminate\Support\Facades\Cache::put($lockKey, true, 30);

        try {
            $this->processTrialRequest($user, $extraUsername, $callbackQueryId, $messageId);
        } finally {
            \Illuminate\Support\Facades\Cache::forget($lockKey);
        }
    }

    protected function processTrialRequest($user, $extraUsername = null, $callbackQueryId = null, $messageId = null)
    {
        $settings = $this->settings;
        $chatId = $user->telegram_chat_id;

        Log::info('Trial request initiated', [
            'user_id' => $user->id,
            'trial_enabled' => $settings->get('trial_enabled'),
        ]);

        $trialEnabled = filter_var($settings->get('trial_enabled') ?? '0', FILTER_VALIDATE_BOOLEAN);
        if (!$trialEnabled) {
            $msg = '❌ قابلیت دریافت اکانت تست در حال حاضر غیرفعال است.';
            if ($callbackQueryId) {
                try {
                    Telegram::answerCallbackQuery([
                        'callback_query_id' => $callbackQueryId,
                        'text' => $msg,
                        'show_alert' => true
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Error answering callback: ' . $e->getMessage());
                }
            } else {
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => $this->escape($msg),
                    'parse_mode' => 'MarkdownV2'
                ]);
            }
            return;
        }

        $limit = (int) $settings->get('trial_limit_per_user', 1);
        $currentTrials = $user->trial_accounts_taken ?? 0;

        if ($currentTrials >= $limit) {
            $msg = '❗️ شما قبلاً از اکانت تست رایگان خود استفاده کرده‌اید و سهمیه شما به پایان رسیده است.';
            if ($callbackQueryId) {
                try {
                    Telegram::answerCallbackQuery([
                        'callback_query_id' => $callbackQueryId,
                        'text' => $msg,
                        'show_alert' => true
                    ]);
                } catch (\Exception $e) {}
            }
            try {
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => $this->escape("❗️ *سهمیه اکانت تست شما به پایان رسیده است.*\n\nبرای استفاده از سرویس‌های پرسرعت روزنه می‌توانید از بخش زیر سرویس اختصاصی تهیه فرمایید:"),
                    'parse_mode' => 'MarkdownV2',
                    'reply_markup' => Keyboard::make()->inline()->row([
                        $this->makeInlineButton(['text' => '🛒 خرید سرویس اختصاصی', 'callback_data' => '/plans']),
                        $this->makeInlineButton(['text' => '🏠 منوی اصلی', 'callback_data' => '/start'])
                    ])
                ]);
            } catch (\Exception $e) {}
            return;
        }

        // اگر شرایط برقرار بود، پیغامی شیک در حال پردازش نشان بده
        if ($callbackQueryId) {
            try {
                Telegram::answerCallbackQuery([
                    'callback_query_id' => $callbackQueryId,
                    'text' => '⏳ در حال بررسی و ساخت کانفیگ تست شما... لطفاً چند لحظه شکیبا باشید.',
                    'show_alert' => false
                ]);
            } catch (\Exception $e) {
                Log::warning('Error answering callback: ' . $e->getMessage());
            }
        }

        $loadingMsgId = null;
        try {
            $loadingMsg = Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => "⚙️ *" . $this->escape("در حال آماده‌سازی سرویس اختصاصی روزنه") . "*\n\n`[█░░░░░░░░░] 10%`\n\n📌 " . $this->escape("شروع بررسی سهمیه کاربر..."),
                'parse_mode' => 'MarkdownV2'
            ]);
            $loadingMsgId = $loadingMsg['message_id'] ?? null;
        } catch (\Exception $ex) {}

        try {
            $volumeMB = (int) $settings->get('trial_volume_mb', 1024);
            $durationHours = 0; // تست حجمی است و محدودیت زمانی ندارد.

            // ایجاد یوزرنیم: اولویت با یوزرنیم تلگرام، وگرنه آیدی عددی
            $usernameBase = !empty($extraUsername) ? $extraUsername : $user->telegram_chat_id;
            
            // تمیز کردن یوزرنیم (حذف کاراکترهای غیرمجاز)
            $usernameBase = preg_replace('/[^a-zA-Z0-9_]/', '', $usernameBase);
            
            $uniqueUsername = "trial_" . $usernameBase;
            if ($currentTrials > 0 || strlen($usernameBase) < 3) {
                $uniqueUsername .= "_" . ($currentTrials + 1);
            } else {
                $uniqueUsername .= "_" . rand(100, 999);
            }

            $expiresAt = null;
            $dataLimitBytes = $volumeMB * 1024 * 1024;

            $configLink = null;
            $locationFlag = '🏳️';
            $locationName = 'نامشخص';

            $this->updateProgressMessage($chatId, $loadingMsgId, 40, 'اتصال به پنل X-UI و ثبت کاربر...');

            $configLink = $this->createVpnAccount($uniqueUsername, $volumeMB, $durationHours, $locationName, $locationFlag);

            if ($configLink) {
                $user->increment('trial_accounts_taken');
                \Illuminate\Support\Facades\Cache::put("trial_link_{$user->id}", $configLink, now()->addMinutes(10));

                $userLink = "<a href=\"tg://user?id={$user->telegram_chat_id}\">" . htmlspecialchars($user->name) . "</a>";
                $this->sendToLogChannel(
                    "🧪 <b>اکانت تست رایگان جدید فعال شد</b>\n\n" .
                    "🔹 <b>کاربر:</b> {$userLink} (<code>{$user->telegram_chat_id}</code>)\n" .
                    "🔹 <b>نام کاربری کانفیگ:</b> <code>{$uniqueUsername}</code>\n" .
                    "🔹 <b>حجم:</b> {$volumeMB} مگابایت\n" .
                    "🔹 <b>اعتبار زمانی:</b> بدون محدودیت\n" .
                    "🔹 <b>سرور:</b> {$locationFlag} {$locationName}\n" .
                    "⏰ <b>زمان:</b> " . now()->format('Y-m-d H:i:s')
                );

                // ثبت سرویس تست/هدیه در سفارش‌های کاربر جهت امکان مشاهده در بخش «سرویس‌های من»
                try {
                    $trialPlan = \App\Models\Plan::firstOrCreate(
                        ['name' => 'سرویس هدیه / تست رایگان'],
                        [
                            'price' => 0,
                            'volume_gb' => (int) ceil($volumeMB / 1024),
                            'duration_days' => 0,
                            'features' => 'اکانت هدیه روزنه',
                            'is_active' => false,
                        ]
                    );

                    \App\Models\Order::create([
                        'user_id' => $user->id,
                        'plan_id' => $trialPlan->id,
                        'status' => 'paid',
                        'expires_at' => $expiresAt,
                        'payment_method' => 'trial',
                        'config_details' => $configLink,
                        'amount' => 0,
                        'source' => 'telegram',
                        'panel_username' => $uniqueUsername,
                    ]);
                } catch (\Exception $ordEx) {
                    Log::warning('Failed to record trial order: ' . $ordEx->getMessage());
                }

                    $pureUrl = trim(preg_replace('/^.*?(http|vless|vmess|trojan|ss)(:\/\/[^\s]+).*$/is', '$1$2', $configLink));
                    if (empty($pureUrl)) {
                         $pureUrl = trim($configLink);
                    }

                     // ساخت پیام کامل با هویت بصری برند روزنه
                    $message = "☀️ *" . $this->escape("روزنه | راهکار پایدار دسترسی آزاد") . "*\n\n";
                    $message .= "🎁 *" . $this->escape("سرویس هدیه و تست اختصاصی شما متصل شد!") . "*\n\n";
                    $message .= "👤 *نام کاربری:* `" . $this->escapeCode($uniqueUsername) . "`\n";
                    $message .= "🌍 *موقعیت سرور:* {$locationFlag} " . $this->escape($locationName) . "\n";
                    $message .= "📦 *حجم مجاز:* `" . $this->escape($volumeMB) . "` " . $this->escape("مگابایت") . "\n";
                    $message .= "⏳ *زمان اعتبار:* " . $this->escape("بدون محدودیت زمانی") . "\n";
                    $message .= "\n\n";
                    $message .= "🎯 *لینک اتصال یک‌بار مصرف \\(کپی با یک لمس\\):*\n";
                    $message .= "`" . $this->escapeCode($pureUrl) . "`\n\n";
                    $message .= "💡 *" . $this->escape("راهنما:") . "* " . $this->escape("این لینک را در برنامه V2Box (آیفون) یا v2rayNG (اندروید) Paste کنید.") . "\n\n";
                    $message .= "📢 " . $this->escape("کانال:") . " [Rozaneh](https://t.me/rozaneh) \\| 👨🏻‍💻 " . $this->escape("پشتیبانی:") . " [RozanehSupport](https://t.me/rozaneh_support)\n";

                    // کیبورد با دکمه کپی، QR و کانفیگ مستقیم
                    $keyboard = Keyboard::make()->inline()
                        ->row([
                            $this->makeInlineButton(['text' => '⚡️ کانفیگ مستقیم (VLESS)', 'callback_data' => "direct_configs_trial_{$user->id}"]),
                            $this->makeInlineButton(['text' => '📱 QR Code مجدد', 'callback_data' => "qr_trial_{$user->id}"])
                        ])
                        ->row([
                            $this->makeInlineButton(['text' => '📋 کپی لینک سابسکریپشن', 'callback_data' => "copy_trial_link_{$user->id}"]),
                            $this->makeInlineButton(['text' => '🛒 خرید سرویس دائمی', 'callback_data' => '/plans'])
                        ])
                        ->row([
                            $this->makeInlineButton(['text' => '🏠 خانه', 'callback_data' => '/start', 'style' => 'danger'])
                        ]);

                    $this->updateProgressMessage($chatId, $loadingMsgId, 80, 'تولید پوستر اختصاصی ۹:۱۶ روزنه...');

                    // تولید پوستر QR Code اختصاصی 9:16 روزنه
                    $qrUrl = $this->generateBrandedQrCode($configLink, 'test');
                    
                    $this->updateProgressMessage($chatId, $loadingMsgId, 100, '✅ اکانت آماده شد! در حال ارسال...');

                    // ارسال عکس با کپشن کامل (تمام اطلاعات + لینک تک لمسی در ۱ پیام واحد)
                    $photoSent = false;
                    try {
                        if ($qrUrl && file_exists($qrUrl)) {
                            $photoInput = \Telegram\Bot\FileUpload\InputFile::create($qrUrl);
                        } elseif ($qrUrl && filter_var($qrUrl, FILTER_VALIDATE_URL)) {
                            $tmpPath = sys_get_temp_dir() . '/qr_' . md5($qrUrl) . '.jpg';
                            $ch2 = curl_init($qrUrl);
                            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch2, CURLOPT_TIMEOUT, 5);
                            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
                            $imgData = curl_exec($ch2);
                            curl_close($ch2);
                            if ($imgData) {
                                file_put_contents($tmpPath, $imgData);
                                $photoInput = \Telegram\Bot\FileUpload\InputFile::create($tmpPath);
                            } else {
                                $photoInput = null;
                            }
                        } else {
                            $photoInput = null;
                        }

                        if ($photoInput) {
                            Telegram::sendPhoto([
                                'chat_id' => $chatId,
                                'photo' => $photoInput,
                                'caption' => $message,
                                'parse_mode' => 'MarkdownV2',
                                'reply_markup' => $keyboard
                            ]);
                            $photoSent = true;
                        }
                    } catch (\Exception $qrEx) {
                        Log::warning('sendPhoto failed: ' . $qrEx->getMessage());
                    }

                    // اگر ارسال عکس با خطا مواجه شد، پیام متنی کامل بفرست
                    if (!$photoSent) {
                        try {
                            Telegram::sendMessage([
                                'chat_id' => $chatId,
                                'text' => $message,
                                'parse_mode' => 'MarkdownV2',
                                'reply_markup' => $keyboard
                            ]);
                        } catch (\Exception $fallbackEx) {}
                    }

                    // فرایند تمیزکاری: حذف پیام لودینگ و پیام منوی قبلی
                    if ($loadingMsgId) {
                        try {
                            Telegram::deleteMessage([
                                'chat_id' => $chatId,
                                'message_id' => $loadingMsgId
                            ]);
                        } catch (\Exception $delEx) {}
                    }

                    if ($messageId && $messageId != $loadingMsgId) {
                        try {
                            Telegram::deleteMessage([
                                'chat_id' => $chatId,
                                'message_id' => $messageId
                            ]);
                        } catch (\Exception $delEx) {}
                    }

                    Log::info('Trial account created successfully with QR', ['user_id' => $user->id, 'username' => $uniqueUsername]);
            }
        } catch (\Exception $e) {
            if ($loadingMsgId) {
                try {
                    Telegram::deleteMessage([
                        'chat_id' => $chatId,
                        'message_id' => $loadingMsgId
                    ]);
                } catch (\Exception $delEx) {}
            }

            Log::error('Trial Account Creation Failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $this->escape('❌ خطا در ساخت اکانت تست. لطفاً بعداً تلاش کنید.'),
                'parse_mode' => 'MarkdownV2'
            ]);
        }
    }

    protected function createVpnAccount($uniqueUsername, $volumeMB, $durationHours, &$locationName, &$locationFlag)
    {
        $settings = $this->settings;
        $expiresAt = $durationHours > 0 ? now()->addHours($durationHours) : null;
        $dataLimitBytes = $volumeMB * 1024 * 1024;
        $configLink = null;
        $panelType = $settings->get('panel_type') ?? 'marzban';
        $targetServer = null;
        $locationFlag = '🏳️';
        $locationName = 'نامشخص';

        $isMultiLocationEnabled = filter_var($settings->get('enable_multilocation', false), FILTER_VALIDATE_BOOLEAN);

        $xuiHost = $settings->get('xui_host');
        $xuiUser = $settings->get('xui_user');
        $xuiPass = $settings->get('xui_pass');
        $inboundId = (int) $settings->get('xui_default_inbound_id');
        $linkType = $settings->get('xui_link_type', 'single');

        if ($isMultiLocationEnabled && class_exists('Modules\MultiServer\Models\Server')) {
            $forcedServerId = $settings->get('trial_server_id');
            if (!empty($forcedServerId)) {
                $targetServer = \Modules\MultiServer\Models\Server::where('id', $forcedServerId)
                    ->where('is_active', true)
                    ->first();
            }
            if (!$targetServer) {
                $targetServer = \Modules\MultiServer\Models\Server::where('is_active', true)
                    ->whereRaw('current_users < capacity')
                    ->first();
            }
            if ($targetServer) {
                $panelType = 'xui';
                $xuiHost = $targetServer->full_host;
                $xuiUser = $targetServer->username;
                $xuiPass = $targetServer->password;
                $inboundId = $targetServer->inbound_id;
                $linkType = $targetServer->link_type ?? 'single';
                if ($targetServer->location) {
                    $locationFlag = $targetServer->location->flag ?? '🏳️';
                    $locationName = $targetServer->location->name;
                }
            }
        }

        if ($panelType === 'pasargad' && $locationName === 'نامشخص') {
            $locationName = 'سرویس Eagle';
            $locationFlag = '🦅';
        }

        if ($panelType === 'marzban') {
            $marzbanService = new MarzbanService(
                $settings->get('marzban_host'),
                $settings->get('marzban_sudo_username'),
                $settings->get('marzban_sudo_password'),
                $settings->get('marzban_node_hostname')
            );
            $response = $marzbanService->createUser([
                'username' => $uniqueUsername,
                'expire' => $expiresAt?->timestamp ?? 0,
                'data_limit' => $dataLimitBytes,
            ]);
            if ($response && !empty($response['subscription_url'])) {
                $configLink = $response['subscription_url'];
            } else {
                throw new \Exception('خطا در ارتباط با پنل مرزبان.');
            }
        } elseif ($panelType === 'remnawave') {
            $remnawaveService = new RemnawaveService(
                $settings->get('remnawave_host'),
                $settings->get('remnawave_api_token'),
                $settings->get('remnawave_node_hostname')
            );
            $response = $remnawaveService->createUser([
                'username' => $uniqueUsername,
                'expire' => $expiresAt?->timestamp ?? 0,
                'data_limit' => $dataLimitBytes,
                'squad_uuid' => $settings->get('remnawave_squad_uuid'),
            ]);
            if ($response && !empty($response['subscriptionUrl'])) {
                $configLink = $remnawaveService->generateSubscriptionLink($response);
            } else {
                throw new \Exception('خطا در ارتباط با پنل Remnawave.');
            }
        } elseif ($panelType === 'xui') {
            $xuiService = new XUIService($xuiHost, $xuiUser, $xuiPass);
            if (!$xuiService->login()) {
                throw new \Exception('خطا در لاگین به پنل X-UI.');
            }
            $inboundData = null;
            if ($targetServer) {
                $inbounds = $xuiService->getInbounds();
                foreach ($inbounds as $rem) {
                    if ($rem['id'] == $inboundId) { $inboundData = $rem; break; }
                }
            } else {
                // 1. Priority: check trial_inbound_ids array from settings
                $trialInboundIdsRaw = $settings->get('trial_inbound_ids');
                $trialInboundIds = is_string($trialInboundIdsRaw) ? json_decode($trialInboundIdsRaw, true) : (is_array($trialInboundIdsRaw) ? $trialInboundIdsRaw : []);

                $inboundModel = null;
                if (!empty($trialInboundIds) && is_array($trialInboundIds)) {
                    $inboundModel = Inbound::whereIn('id', $trialInboundIds)->inRandomOrder()->first();
                }

                if (!$inboundModel) {
                    $singleTrialId = $settings->get('trial_inbound_id');
                    if ($singleTrialId) {
                        $inboundModel = Inbound::find($singleTrialId);
                    }
                }

                if (!$inboundModel && !empty($inboundId)) {
                    $inboundModel = Inbound::whereJsonContains('inbound_data->id', (int)$inboundId)->first();
                }

                if (!$inboundModel) {
                    $inboundModel = Inbound::first();
                }

                if ($inboundModel) {
                    $inboundData = is_string($inboundModel->inbound_data) ? json_decode($inboundModel->inbound_data, true) : $inboundModel->inbound_data;
                }
            }

            if (!$inboundData) {
                $liveInbounds = $xuiService->getInbounds();
                if (!empty($liveInbounds)) {
                    $inboundData = $liveInbounds[0];
                }
            }

            if (!$inboundData) throw new \Exception('اینباند مورد نظر یافت نشد.');
            $clientData = [
                'email' => $uniqueUsername,
                'total' => $dataLimitBytes,
                'expiryTime' => $expiresAt ? $expiresAt->timestamp * 1000 : 0,
            ];
            if ($linkType === 'subscription') $clientData['subId'] = Str::random(16);
            $response = $xuiService->addClient($inboundData['id'], $clientData);
            if ($response && isset($response['success']) && $response['success']) {
                $uuid = $response['generated_uuid'] ?? null;
                if (!$uuid && isset($response['obj']['settings'])) {
                    $rawSettings = $response['obj']['settings'];
                    $cSettings = is_array($rawSettings) ? $rawSettings : json_decode($rawSettings, true);
                    $uuid = $cSettings['clients'][0]['id'] ?? null;
                }
                $subId = $response['generated_subId'] ?? $clientData['subId'] ?? null;
                $rawStream = $inboundData['streamSettings'] ?? '{}';
                $streamSettings = is_array($rawStream) ? $rawStream : json_decode($rawStream, true);
                $protocol = $inboundData['protocol'] ?? 'vless';
                $inboundPort = $inboundData['port'] ?? 443;
                $serverAddress = parse_url($xuiHost, PHP_URL_HOST);

                switch ($linkType) {
                    case 'subscription':
                        if ($targetServer) {
                            $subDomain = $targetServer->subscription_domain ?? $serverAddress;
                            $subPort = $targetServer->subscription_port ?? 2053;
                            $subPath = $targetServer->subscription_path ?? '/sub/';
                            $isHttps = $targetServer->is_https ?? true;
                            $baseUrl = rtrim($subDomain, '/');
                            if ($subPort) $baseUrl .= ":{$subPort}";
                            $prot = $isHttps ? 'https' : 'http';
                            $configLink = "{$prot}://{$baseUrl}" . rtrim($subPath, '/') . '/' . $subId;
                        } else {
                            $subBaseUrl = trim($settings->get('xui_subscription_url_base') ?? '');
                            if (!empty($subBaseUrl)) {
                                $parsedSub = parse_url($subBaseUrl);
                                $subPath = trim($parsedSub['path'] ?? '', '/');
                                $configLink = !empty($subPath) 
                                    ? (rtrim($subBaseUrl, '/') . '/' . $subId) 
                                    : (rtrim($subBaseUrl, '/') . '/sub/' . $subId);
                            } else {
                                $parsedHost = parse_url($settings->get('xui_host') ?? '');
                                $subScheme = $parsedHost['scheme'] ?? 'https';
                                $subHostName = $parsedHost['host'] ?? 'panel.cinemapluss.ir';
                                $subPortNum = isset($parsedHost['port']) ? ':' . $parsedHost['port'] : '';
                                $configLink = "{$subScheme}://{$subHostName}{$subPortNum}/sub/{$subId}";
                            }
                        }
                        break;
                    case 'tunnel':
                        if (!$uuid) throw new \Exception("UUID extracted failed");
                        $tunnelAddress = $targetServer->tunnel_address;
                        $tunnelPort = $targetServer->tunnel_port ?? 443;
                        $tls = filter_var($targetServer->tunnel_is_https, FILTER_VALIDATE_BOOLEAN);
                        $params = ['type' => $streamSettings['network'] ?? 'tcp'];
                        if ($tls) {
                            $params['security'] = 'tls';
                            $params['sni'] = $tunnelAddress;
                        } else {
                            $params['security'] = 'none';
                            if($protocol === 'vless') $params['encryption'] = 'none';
                        }
                        if (($params['type'] ?? '') === 'ws') {
                            $params['path'] = $streamSettings['wsSettings']['path'] ?? '/';
                            $params['host'] = $streamSettings['wsSettings']['headers']['Host'] ?? $tunnelAddress;
                        }
                        $remarkText = $locationFlag . "-" . $uniqueUsername;
                        $qs = http_build_query($params);
                        $configLink = "vless://{$uuid}@{$tunnelAddress}:{$tunnelPort}?{$qs}#" . rawurlencode($remarkText);
                        break;
                    default:
                        if (!$uuid) throw new \Exception("UUID extracted failed");
                        $params = ['type' => $streamSettings['network'] ?? 'tcp', 'security' => $streamSettings['security'] ?? 'none'];
                        if ($params['security'] === 'tls') $params['sni'] = $serverAddress;
                        $qs = http_build_query(array_filter($params));
                        $configLink = "vless://{$uuid}@{$serverAddress}:{$inboundPort}?{$qs}#" . rawurlencode("Vip Account");
                        break;
                }
                if ($targetServer) $targetServer->increment('current_users');
            } else {
                throw new \Exception($response['msg'] ?? 'خطا در ساخت کاربر در پنل X-UI');
            }
        } elseif ($panelType === 'pasargad') {
            $pasargad = new PasargadService(
                $settings->get('pasargad_host'),
                $settings->get('pasargad_sudo_username'),
                $settings->get('pasargad_sudo_password'),
                $settings->get('pasargad_node_hostname')
            );
            $response = $pasargad->createUser([
                'username' => $uniqueUsername,
                'expire' => $expiresAt?->timestamp ?? 0,
                'data_limit' => $dataLimitBytes,
                'group_ids' => $settings->get('pasargad_trial_group_id') ? [(int)$settings->get('pasargad_trial_group_id')] : [1],
            ]);
            if ($response && !empty($response['subscription_url'])) {
                $configLink = $response['subscription_url'];
            } else {
                throw new \Exception('خطا در ارتباط با پنل پاسارگاد.');
            }
        } else {
            throw new \Exception('نوع پنل در تنظیمات مشخص نشده است.');
        }

        return $configLink;
    }

    protected function sendOrEditMessage($chatId, $text, $keyboard, $messageId = null, $linkPreviewOptions = null)
    {
        $parseMode = 'MarkdownV2';
        if (Str::contains($text, ['<tg-emoji', '<b>', '<i>', '<u>', '<s>', '<a href', '<code>', '<pre>', '<span', '<blockquote'])) {
            $parseMode = 'HTML';
        }

        $photoCacheKey = "user_home_photo_msg_{$chatId}";
        $cachedPhotoMsgId = Cache::get($photoCacheKey);

        $payload = [
            'chat_id'      => $chatId,
            'parse_mode'   => $parseMode,
            'reply_markup' => $keyboard
        ];

        if ($linkPreviewOptions) {
            $payload['link_preview_options'] = json_encode($linkPreviewOptions);
        }

        $finalId = null;
        try {
            if ($messageId) {
                $payload['message_id'] = $messageId;
                
                // اگر شناسه پیام منطبق بر پیام عکس ذخیره‌شده است، کپشن آن را ویرایش می‌کنیم
                if ($cachedPhotoMsgId && (int)$messageId === (int)$cachedPhotoMsgId) {
                    $payload['caption'] = $text;
                    Telegram::editMessageCaption($payload);
                } else {
                    $payload['text'] = $text;
                    Telegram::editMessageText($payload);
                }
                $finalId = (int) $messageId;
            } else {
                $this->cleanUiMessages($chatId);
                $payload['text'] = $text;
                $sent = Telegram::sendMessage($payload);
                $finalId = $this->extractMessageId($sent);
            }
        } catch (\Telegram\Bot\Exceptions\TelegramResponseException $e) {
            if (Str::contains($e->getMessage(), ['message is not modified'])) {
                $finalId = $messageId ? (int) $messageId : null;
                Log::info("Message not modified.", ['chat_id' => $chatId]);
            } elseif (Str::contains($e->getMessage(), ['message to edit not found', 'message identifier is not specified', "message can't be edited", 'no text in the message', 'MESSAGE_ID_INVALID'])) {
                Log::warning("Could not edit message {$messageId}. Sending new.", ['error' => $e->getMessage()]);
                
                // فراموش کردن کش عکس در صورت بروز خطای عدم امکان ویرایش
                if ($cachedPhotoMsgId && (int)$messageId === (int)$cachedPhotoMsgId) {
                    Cache::forget($photoCacheKey);
                }
                
                unset($payload['message_id']);
                unset($payload['caption']);
                $payload['text'] = $text;
                try {
                    $this->cleanUiMessages($chatId, $messageId);
                    $sent = Telegram::sendMessage($payload);
                    $finalId = $this->extractMessageId($sent);
                } catch (\Exception $e2) {
                    Log::error("Failed to send new message after edit failure: " . $e2->getMessage());
                }
            } else {
                Log::error("Telegram API error: " . $e->getMessage(), ['payload_chat' => $chatId]);
                if ($messageId) {
                    if ($cachedPhotoMsgId && (int)$messageId === (int)$cachedPhotoMsgId) {
                        Cache::forget($photoCacheKey);
                    }
                    unset($payload['message_id']);
                    unset($payload['caption']);
                    $payload['text'] = $text;
                    try {
                        $this->cleanUiMessages($chatId, $messageId);
                        $sent = Telegram::sendMessage($payload);
                        $finalId = $this->extractMessageId($sent);
                    } catch (\Exception $e2) {
                        Log::error("Failed to send new message after API error: " . $e2->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("General error during send/edit message: " . $e->getMessage(), ['chat_id' => $chatId]);
            if ($messageId) {
                if ($cachedPhotoMsgId && (int)$messageId === (int)$cachedPhotoMsgId) {
                    Cache::forget($photoCacheKey);
                }
                unset($payload['message_id']);
                unset($payload['caption']);
                $payload['text'] = $text;
                try {
                    $this->cleanUiMessages($chatId, $messageId);
                    $sent = Telegram::sendMessage($payload);
                    $finalId = $this->extractMessageId($sent);
                } catch (\Exception $e2) {
                    Log::error("Failed to send new message after general failure: " . $e2->getMessage());
                }
            }
        }

        if ($finalId) {
            $this->trackUiMessage($chatId, $finalId);
            // پاکسازی منوهای قبلی؛ فقط پیام فعلی بماند
            $this->cleanUiMessages($chatId, $finalId);
        }

        return $finalId;
    }

    protected function extractMessageId($sent): ?int
    {
        try {
            if (is_object($sent)) {
                // SDK Message: message_id via toArray / magic, not always getMessageId()
                if (method_exists($sent, 'toArray')) {
                    $arr = $sent->toArray();
                    if (isset($arr['message_id'])) {
                        return (int) $arr['message_id'];
                    }
                }
                if (method_exists($sent, 'getMessageId')) {
                    $id = $sent->getMessageId();
                    if ($id) return (int) $id;
                }
                if (isset($sent->message_id)) {
                    return (int) $sent->message_id;
                }
                if (isset($sent['message_id'])) {
                    return (int) $sent['message_id'];
                }
            }
            if (is_array($sent) && isset($sent['message_id'])) {
                return (int) $sent['message_id'];
            }
        } catch (\Throwable $e) {
            Log::warning('extractMessageId failed: ' . $e->getMessage());
        }
        return null;
    }

    protected function uiCacheKey($chatId): string
    {
        return 'lookanet_tg_ui_' . $chatId;
    }

    protected function trackUiMessage($chatId, $messageId): void
    {
        if (!$messageId) return;
        $key = $this->uiCacheKey($chatId);
        $ids = Cache::get($key, []);
        if (!is_array($ids)) $ids = [];
        $ids[] = (int) $messageId;
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_slice($ids, -20);
        Cache::put($key, $ids, now()->addDays(14));
    }

    /**
     * حذف پیام‌های UI قبلی ربات برای تجربه تمیز (تک‌منو)
     */
    protected function cleanUiMessages($chatId, $exceptId = null): void
    {
        $key = $this->uiCacheKey($chatId);
        $ids = Cache::get($key, []);
        if (!is_array($ids) || empty($ids)) return;

        $kept = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($exceptId && $id === (int) $exceptId) {
                $kept[] = $id;
                continue;
            }
            try {
                Telegram::deleteMessage([
                    'chat_id' => $chatId,
                    'message_id' => $id,
                ]);
            } catch (\Throwable $e) {
                // پیام قدیمی/حذف‌شده — نادیده
            }
        }
        Cache::put($key, $kept, now()->addDays(14));
    }

    protected function escape(string $text): string
    {
        $chars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        // اول بک‌اسلش را اسکیپ می‌کنیم تا تداخل پیش نیاید
        $text = str_replace('\\', '\\\\', $text);
        return str_replace($chars, array_map(fn($char) => '\\' . $char, $chars), $text);
    }

    protected function escapeCode(string $text): string
    {
        return str_replace(['\\', '`'], ['\\\\', '\\`'], $text);
    }

    /**
     * یک نقطه مرکزی برای ظاهر تمام دکمه‌های شیشه‌ای ربات فروش.
     * استفاده از ایموجی‌های استاندارد و پایدار جهت سازگاری ۱۰۰٪ با تمامی کلاینت‌های تلگرام.
     */
    protected function makeInlineButton(array $params): array
    {
        $callback = mb_strtolower((string) ($params['callback_data'] ?? ''), 'UTF-8');
        $label = mb_strtolower((string) ($params['text'] ?? ''), 'UTF-8');
        $signal = $callback . ' ' . $label;

        if (empty($params['style'])) {
            if ($this->buttonSignalContains($signal, [
                'reject', 'cancel', 'close', 'delete', 'remove', 'revoke',
                'لغو', 'انصراف', 'حذف', 'رد فیش', 'بستن',
            ])) {
                $params['style'] = 'danger';
            } elseif ($this->buttonSignalContains($signal, [
                'approve', 'confirm', 'pay_', 'buy_plan', 'renew_', 'trial_request',
                'support_new', 'deposit_amount', 'ثبت', 'تأیید', 'پرداخت', 'خرید',
                'تمدید', 'دریافت', 'تیکت جدید',
            ])) {
                $params['style'] = 'success';
            } else {
                $params['style'] = 'primary';
            }
        }

        // جلوگیری از ارسال ایموجی کاستوم برای حفظ حداکثر سازگاری در تمامی دستگاه‌ها
        unset($params['icon_custom_emoji_id']);

        // اضافه کردن خودکار ایموجی استاندارد اگر دکمه از قبل ایموجی نداشته باشد
        $text = (string) ($params['text'] ?? '');
        $hasEmoji = (bool) preg_match('/[\p{So}\p{Sk}\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $text);
        if (!$hasEmoji && trim($text) !== '') {
            $standardEmoji = $this->resolveStandardEmoji($signal);
            if ($standardEmoji !== '') {
                $params['text'] = $standardEmoji . ' ' . $text;
            }
        }

        return array_filter($params, fn($val) => $val !== null);
    }

    protected function resolveStandardEmoji(string $signal): string
    {
        if ($this->buttonSignalContains($signal, ['home', 'خانه', '/start'])) return '🏠';
        if ($this->buttonSignalContains($signal, ['plans', 'shop', 'خرید', 'فروشگاه', 'تمدید'])) return '🛍️';
        if ($this->buttonSignalContains($signal, ['trial', 'تست'])) return '🎁';
        if ($this->buttonSignalContains($signal, ['service', 'سرویس', 'config', 'کانفیگ'])) return '📦';
        if ($this->buttonSignalContains($signal, ['wallet', 'کیف پول', 'deposit', 'شارژ'])) return '💳';
        if ($this->buttonSignalContains($signal, ['referral', 'دعوت', 'زیرمجموعه', 'کسب درآمد', 'gift', 'هدیه'])) return '👥';
        if ($this->buttonSignalContains($signal, ['tutorial', 'آموزش', 'راهنما'])) return '📖';
        if ($this->buttonSignalContains($signal, ['support', 'پشتیبان', 'ticket', 'تیکت', 'فرایدی'])) return '👨🏻‍💻';
        if ($this->buttonSignalContains($signal, ['about', 'درباره'])) return '✈️';
        if ($this->buttonSignalContains($signal, ['profile', 'حساب کاربری', 'پروفایل'])) return '👤';
        if ($this->buttonSignalContains($signal, ['transaction', 'تراکنش', 'فاکتور'])) return '🧾';
        if ($this->buttonSignalContains($signal, ['channel', 'کانال'])) return '📢';
        if ($this->buttonSignalContains($signal, ['approve', 'تایید', 'تأیید', 'ثبت'])) return '✅';
        if ($this->buttonSignalContains($signal, ['reject', 'cancel', 'لغو', 'انصراف', 'رد'])) return '❌';
        if ($this->buttonSignalContains($signal, ['back', 'بازگشت', 'قبلی'])) return '🔙';
        if ($this->buttonSignalContains($signal, ['next', 'بعدی'])) return '🔜';
        return '';
    }

    protected function buttonSignalContains(string $signal, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($signal, $needle)) {
                return true;
            }
        }
        return false;
    }

    protected function resolveButtonIcon(string $signal): string
    {
        if ($this->buttonSignalContains($signal, ['cancel', 'reject', 'close', 'delete', 'remove', 'revoke', 'لغو', 'انصراف', 'حذف', 'بستن'])) {
            return 'secure';
        }
        if ($this->buttonSignalContains($signal, ['/start', 'خانه', 'بازگشت'])) {
            return 'home';
        }
        if ($this->buttonSignalContains($signal, ['trial', 'تست'])) {
            return 'trial';
        }
        if ($this->buttonSignalContains($signal, ['support', 'ticket', 'پشتیبان', 'پشتیبانی', 'تیکت'])) {
            return 'support';
        }
        if ($this->buttonSignalContains($signal, ['wallet', 'deposit', 'transaction', 'pay_', 'invoice', 'receipt', 'card', 'bank', 'crypto', 'discount', 'کیف پول', 'شارژ', 'تراکنش', 'پرداخت', 'فیش', 'تخفیف'])) {
            return 'wallet';
        }
        if ($this->buttonSignalContains($signal, ['referral', 'gift', 'transfer_ref', 'دعوت', 'هدیه', 'معرفی'])) {
            return 'gift';
        }
        if ($this->buttonSignalContains($signal, ['profile', 'account', 'حساب'])) {
            return 'profile';
        }
        if ($this->buttonSignalContains($signal, ['service', 'order', 'link', 'config', 'qrcode', 'qr_', 'subscription', 'سرویس', 'سفارش', 'لینک', 'کانفیگ'])) {
            return 'link';
        }
        if ($this->buttonSignalContains($signal, ['plans', 'plan_', 'duration', 'renew', 'buy_', 'فروشگاه', 'بسته', 'خرید', 'تمدید'])) {
            return 'plans';
        }
        if ($this->buttonSignalContains($signal, ['status', 'check_', 'membership', 'وضعیت', 'بررسی'])) {
            return 'status';
        }
        if ($this->buttonSignalContains($signal, ['secure', 'captcha', 'location', 'loc_', 'امنیت', 'احراز'])) {
            return 'secure';
        }

        return 'brand';
    }

    protected function getEmojiCaptchaChallenge()
    {
        $correctEmoji = '🔑';
        $pool = ['🚗', '🍎', '⭐️', '🎁', '🍦', '🐱', '⚽️', '🎈', '🍕', '🚀', '🎸', '⏰', '🔒', '💡'];
        
        // Pick 4 random unique emojis from the pool
        $wrongEmojis = array_rand(array_flip($pool), 4);
        
        $options = array_merge([$correctEmoji], $wrongEmojis);
        shuffle($options);
        
        return [
            'correct' => $correctEmoji,
            'options' => $options
        ];
    }

    protected function sendCaptchaLanguageSelection($chatId, $referrerId = null, $messageId = null)
    {
        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(['text' => '🇮🇷 فارسی', 'callback_data' => 'captcha_set_lang|fa|' . $referrerId]),
                $this->makeInlineButton(['text' => '🇬🇧 English', 'callback_data' => 'captcha_set_lang|en|' . $referrerId])
            ]);

        $message = "🌍 <b>Select Language / انتخاب زبان</b>\n\n" .
                   "Please select your language to start verification:\n" .
                   "لطفاً جهت شروع فرآیند احراز هویت، زبان خود را انتخاب کنید:";

        if ($messageId) {
            try {
                Telegram::editMessageText([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $keyboard
                ]);
                return;
            } catch (\Exception $e) {
                Log::warning('Could not edit captcha language selection: ' . $e->getMessage());
            }
        }

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard
        ]);
    }

    protected function sendCaptchaChallenge($user, $chatId, $referrerId, $lang, $prefix = '', $messageId = null)
    {
        $challenge = $this->getEmojiCaptchaChallenge();
        $correctIndex = array_search($challenge['correct'], $challenge['options']);
        $token = Str::random(6);

        $newState = "captcha_solve|{$referrerId}|{$lang}|{$correctIndex}|{$token}";
        $user->update(['bot_state' => $newState]);

        $buttons = [];
        foreach ($challenge['options'] as $index => $option) {
            $buttons[] = $this->makeInlineButton([
                'text' => $option,
                'callback_data' => "captcha_verify|{$index}|{$token}"
            ]);
        }

        $keyboard = Keyboard::make()->inline()
            ->row($buttons)
            ->row([
                $this->makeInlineButton([
                    'text' => $lang === 'en' ? '🔄 Change Language' : '🔄 تغییر زبان',
                    'callback_data' => "captcha_change_lang|" . $referrerId
                ])
            ]);

        $questionText = $lang === 'en' 
            ? "Please select the key 🔑 from the options below:"
            : "لطفاً برای تایید انسانی، از بین گزینه‌های زیر شکل کلید 🔑 را انتخاب کنید:";
            
        $fullText = ($prefix ? $prefix . "\n\n" : "") . "🛡️ <b>" . ($lang === 'en' ? 'Human Verification' : 'تایید هویت انسانی') . "</b>\n\n" . $questionText;

        if ($messageId) {
            try {
                Telegram::editMessageText([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'text' => $fullText,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $keyboard
                ]);
                return;
            } catch (\Exception $e) {
                Log::warning('Could not edit captcha challenge: ' . $e->getMessage());
            }
        }

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $fullText,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard
        ]);
    }

    protected function handleCaptchaSetLang($chatId, $data, $messageId, $callbackQueryId)
    {
        $user = User::where('telegram_chat_id', $chatId)->first();
        if (!$user) {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => 'User not found. / کاربر یافت نشد.',
                'show_alert' => true
            ]);
            return;
        }

        $parts = explode('|', $data);
        $lang = $parts[1] ?? 'fa';
        $referrerId = (isset($parts[2]) && is_numeric($parts[2])) ? (int)$parts[2] : '';

        try {
            Telegram::answerCallbackQuery(['callback_query_id' => $callbackQueryId]);
        } catch (\Exception $e) {}

        $this->sendCaptchaChallenge($user, $chatId, $referrerId, $lang, '', $messageId);
    }

    protected function handleCaptchaVerify($chatId, $data, $messageId, $callbackQueryId)
    {
        $user = User::where('telegram_chat_id', $chatId)->first();
        if (!$user) {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => 'User not found. / کاربر یافت نشد.',
                'show_alert' => true
            ]);
            return;
        }

        $parts = explode('|', $data);
        $clickedIndex = (int) ($parts[1] ?? -1);
        $token = $parts[2] ?? '';

        $stateParts = explode('|', $user->bot_state ?? '');
        if (count($stateParts) < 5 || $stateParts[0] !== 'captcha_solve') {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => 'Invalid session. Please /start again. / نشست نامعتبر است. مجدداً ربات را /start کنید.',
                'show_alert' => true
            ]);
            return;
        }

        $referrerId = is_numeric($stateParts[1]) ? (int) $stateParts[1] : null;
        $lang = $stateParts[2];
        $correctIndex = (int) $stateParts[3];
        $storedToken = $stateParts[4];

        if ($token !== $storedToken) {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => 'Challenge expired. / چالش منقضی شده است.',
                'show_alert' => true
            ]);
            $this->sendCaptchaChallenge($user, $chatId, $referrerId, $lang, '', $messageId);
            return;
        }

        if ($clickedIndex === $correctIndex) {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => $lang === 'en' ? '✅ Verification successful!' : '✅ احراز هویت با موفقیت انجام شد!',
                'show_alert' => false
            ]);

            try {
                Telegram::deleteMessage([
                    'chat_id' => $chatId,
                    'message_id' => $messageId
                ]);
            } catch (\Exception $e) {}

            $this->completeUserRegistration($user, $chatId, $referrerId, $lang);
        } else {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => $lang === 'en' ? '❌ Incorrect. Try again!' : '❌ اشتباه است. دوباره تلاش کنید!',
                'show_alert' => true
            ]);

            $prefix = $lang === 'en' 
                ? '⚠️ <b>Wrong answer! Please try again.</b>' 
                : '⚠️ <b>پاسخ اشتباه بود! لطفاً دوباره تلاش کنید.</b>';

            $this->sendCaptchaChallenge($user, $chatId, $referrerId, $lang, $prefix, $messageId);
        }
    }

    protected function completeUserRegistration($user, $chatId, $referrerId, $lang = 'fa')
    {
        $user->update(['bot_state' => null]);

        if (!$this->isUserMemberOfChannel($user)) {
            Log::info("HTM_NEW_USER_MEMBERSHIP_REQUIRED_STOP");
            $this->showChannelRequiredMessage($chatId);
            return;
        }

        $telegramSettings = TelegramBotSetting::pluck('value', 'key');
        
        if ($lang === 'en') {
            $defaultWelcome = "Welcome to Rozaneh {userFirstName}!
            
Free, fast and borderless internet.
I am here to build the most secure and stable connection path for you.

👇 Start from the inline menu below:";
        } else {
            $defaultWelcome = "به روزنه خوش آمدی {userFirstName}!

اینترنت آزاد، سریع و بدون مرز.
من اینجام تا امن‌ترین و پایدارترین مسیر اتصال رو برات بسازم.

👇 از منوی شیشه‌ای زیر شروع کن:";
        }
        
        $welcomeMessage = $telegramSettings->get('welcome_message', $defaultWelcome);
        $welcomeMessage = str_replace('{userFirstName}', $user->name, $welcomeMessage);

        if ($referrerId) {
            $referrer = User::find($referrerId);
            if ($referrer && $referrer->id !== $user->id) {
                $user->referrer_id = $referrer->id;
                $user->save();
                
                // Create Free 2GB 30-Day VPN Account for Referee (user who signed up)
                try {
                    $uniqueUsername = "ref_u" . $user->id . "_" . Str::random(4);
                    $locationName = 'نامشخص';
                    $locationFlag = '🏳️';
                    // 2GB = 2048 MB, 30 days = 720 hours
                    $configLink = $this->createVpnAccount($uniqueUsername, 2048, 720, $locationName, $locationFlag);
                    
                    if ($configLink) {
                        $pureUrl = trim(preg_replace('/^.*?(http|vless|vmess|trojan|ss)(:\/\/[^\s]+).*$/is', '$1$2', $configLink));
                        if (empty($pureUrl)) {
                            $pureUrl = trim($configLink);
                        }

                        $welcomeMessage .= "\n\n🎁 <b>هدیه ویژه معرف (اشتراک ۲ گیگابایت یک‌ماهه رایگان):</b>\n";
                        $welcomeMessage .= "👤 نام کاربری: <code>{$uniqueUsername}</code>\n";
                        $welcomeMessage .= "🌍 سرور: {$locationFlag} {$locationName}\n";
                        $welcomeMessage .= "🎯 لینک اتصال (کپی با یک ضربه):\n";
                        $welcomeMessage .= "<code>" . $pureUrl . "</code>\n\n";
                        $welcomeMessage .= "💡 <i>لینک فوق را کپی کرده و در نرم‌افزار اتصال خود وارد کنید.</i>";
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to create free referral VPN account: " . $e->getMessage());
                }

                $welcomeGift = (int) $this->settings->get('referral_welcome_gift', 0);
                if ($welcomeGift > 0) {
                    $user->increment('balance', $welcomeGift);
                    $giftMsg = $lang === 'en' 
                        ? "\n\n🎁 Welcome gift: " . number_format($welcomeGift) . " Tomans added to your wallet."
                        : "\n\n🎁 هدیه نقدی خوش‌آمدگویی: " . number_format($welcomeGift) . " تومان به کیف پول شما اضافه شد.";
                    $welcomeMessage .= $giftMsg;
                }
                
                if ($referrer->telegram_chat_id) {
                    $referrerMessage = "👤 *خبر خوب!*\n\nکاربر جدیدی با نام «{$user->name}» با لینک دعوت شما به ربات پیوست و یک اشتراک ۲ گیگابایتی هدیه گرفت.\n\n💡 پس از انجام اولین خرید توسط این کاربر، پاداش نقدی شما واریز خواهد شد.";
                    try {
                        Telegram::sendMessage([
                            'chat_id' => $referrer->telegram_chat_id,
                            'text' => $this->escape($referrerMessage),
                            'parse_mode' => 'MarkdownV2'
                        ]);
                    } catch (\Exception $e) {
                        Log::error("Failed to send referral notification: " . $e->getMessage());
                    }
                }
            }
        }

        Log::info("HTM_SENDING_WELCOME");
        $this->removeReplyKeyboard($chatId);
        
        // Send the onboarding welcome message
        try {
            Telegram::sendMessage([
                'chat_id'    => $chatId,
                'text'       => $welcomeMessage,
                'parse_mode' => 'HTML'
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send welcome message: " . $e->getMessage());
        }

        $user->refresh();
        
        $welcomeNotice = $lang === 'en' ? 'Welcome 👋' : 'خوش اومدی 👋';
        $this->sendHome($user, $chatId, null, $welcomeNotice);
    }

    protected function getMainMenuKeyboard(): Keyboard
    {
        return Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton([
                    'text' => '🛍️ خرید یا تمدید اشتراک',
                    'callback_data' => '/plans',
                    'style' => 'primary',
                ]),
            ])
            ->row([
                $this->makeInlineButton([
                    'text' => '🎁 دریافت تست رایگان',
                    'callback_data' => 'trial_request',
                    'style' => 'success',
                ]),
                $this->makeInlineButton([
                    'text' => '📦 سرویس‌های من',
                    'callback_data' => '/my_services',
                    'style' => 'primary',
                ]),
            ])
            ->row([
                $this->makeInlineButton([
                    'text' => '💳 کیف پول',
                    'callback_data' => '/wallet',
                    'style' => 'primary',
                ]),
                $this->makeInlineButton([
                    'text' => '🤖 پشتیبانی هوشمند',
                    'callback_data' => '/support_menu',
                    'style' => 'success',
                ]),
            ])
            ->row([
                $this->makeInlineButton([
                    'text' => '📖 راهنمای اتصال',
                    'callback_data' => '/tutorials',
                    'style' => 'primary',
                ]),
                $this->makeInlineButton([
                    'text' => '✨ امکانات بیشتر',
                    'callback_data' => '/more',
                    'style' => 'primary',
                ]),
            ]);
    }

    protected function sendMoreMenu($chatId, $messageId = null): void
    {
        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton([
                    'text' => '👥 کسب درآمد و دعوت از دوستان',
                    'callback_data' => '/referral',
                    'style' => 'success',
                ]),
                $this->makeInlineButton([
                    'text' => '✈️ درباره روزنه',
                    'callback_data' => '/about',
                    'style' => 'primary',
                ]),
            ]);

        try {
            $channel = $this->settings->get('telegram_channel_url')
                ?: $this->settings->get('required_channel_link')
                ?: $this->settings->get('telegram_channel');
            $channel = trim((string) $channel);
            if ($channel !== '' && !str_starts_with($channel, 'http')) {
                $channel = 'https://t.me/' . ltrim($channel, '@');
            }
            if (str_starts_with($channel, 'http')) {
                $keyboard->row([
                    $this->makeInlineButton([
                        'text' => '📢 عضویت در کانال اطلاع‌رسانی',
                        'url' => $channel,
                        'style' => 'success',
                    ]),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Channel link is unavailable in more menu', ['error' => $e->getMessage()]);
        }

        $keyboard->row([
            $this->makeInlineButton([
                'text' => '🏠 بازگشت به خانه',
                'callback_data' => '/start',
                'style' => 'danger',
            ]),
        ]);

        $text = "✈️ <b>امکانات بیشتر روزنه</b>\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "هدایای معرفی، کانال رسمی و اطلاعات روزنه از این بخش در دسترس شماست.";
        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }
    protected function handleAdminRejectOrder($adminChatId, $orderId, $callbackQueryId, $messageId, $adminUser = null)
    {
        $adminConfigId = (string) $this->settings->get('telegram_admin_chat_id');
        $receiptChannelId = (string) $this->settings->get('telegram_receipt_channel_id');
        $fromId = $adminUser ? (string) $adminUser->getId() : '';

        $isAuthorized = false;
        if (($adminConfigId !== '' && (string)$adminChatId === $adminConfigId) ||
            ($receiptChannelId !== '' && (string)$adminChatId === $receiptChannelId) ||
            ($adminConfigId !== '' && $fromId === $adminConfigId)) {
            $isAuthorized = true;
        } else if ($fromId !== '') {
            $adminDbUser = User::where('telegram_chat_id', $fromId)->first();
            if ($adminDbUser && ($adminDbUser->is_admin || (method_exists($adminDbUser, 'hasRole') && $adminDbUser->hasRole('admin')))) {
                $isAuthorized = true;
            }
        }

        if (!$isAuthorized) {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '❌ شما دسترسی ادمین ندارید.',
                'show_alert' => true
            ]);
            return;
        }

        $order = Order::find($orderId);
        if (!$order || $order->status !== 'pending') {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '❌ سفارش قبلاً پردازش شده یا یافت نشد.',
                'show_alert' => true
            ]);
            return;
        }

        try {
            // Show rejection reason choices instead of rejecting immediately
            $keyboard = Keyboard::make()->inline()
                ->row([
                    $this->makeInlineButton(['text' => '❌ فیش نامعتبر', 'callback_data' => "admin_reason_{$orderId}_invalid"]),
                    $this->makeInlineButton(['text' => '❌ مبلغ مغایر', 'callback_data' => "admin_reason_{$orderId}_amount"])
                ])
                ->row([
                    $this->makeInlineButton(['text' => '❌ فیش تکراری', 'callback_data' => "admin_reason_{$orderId}_duplicate"]),
                    $this->makeInlineButton(['text' => '🔙 انصراف', 'callback_data' => "admin_reason_{$orderId}_cancel"])
                ]);

            $orderType = $order->renews_order_id ? 'تمدید سرویس' : ($order->plan_id ? 'خرید سرویس' : 'شارژ کیف پول');
            $user = $order->user;
            $userPvLink = $user->telegram_chat_id 
                ? "[{$this->escape($user->name)}](tg://user?id={$user->telegram_chat_id})" 
                : $this->escape($user->name);

            $msgText = "🧾 *انتخاب علت رد سفارش \\#{$orderId}*\n\n";
            $msgText .= "*کاربر:* {$userPvLink} \\(ID: `{$user->id}`\\)\n";
            $msgText .= "*مبلغ:* " . $this->escape(number_format($order->amount) . ' تومان') . "\n";
            $msgText .= "*نوع سفارش:* " . $this->escape($orderType) . "\n\n";
            $msgText .= "⚠️ *لطفاً دلیل رد فیش را انتخاب کنید:*";

            if ($order->card_payment_receipt && !str_starts_with($order->card_payment_receipt, 'text_receipt:')) {
                Telegram::editMessageCaption([
                    'chat_id' => $adminChatId,
                    'message_id' => $messageId,
                    'caption' => $msgText,
                    'parse_mode' => 'MarkdownV2',
                    'reply_markup' => $keyboard
                ]);
            } else {
                Telegram::editMessageText([
                    'chat_id' => $adminChatId,
                    'message_id' => $messageId,
                    'text' => $msgText,
                    'parse_mode' => 'MarkdownV2',
                    'reply_markup' => $keyboard
                ]);
            }

            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => 'لطفاً علت رد تراکنش را انتخاب کنید.'
            ]);
        } catch (\Exception $e) {
            Log::error("Telegram Admin Reject Action Error: " . $e->getMessage());
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '❌ خطایی رخ داد: ' . $e->getMessage(),
                'show_alert' => true
            ]);
        }
    }

    /**
     * 🛑 پردازش علت رد فیش توسط ادمین
     */
    protected function handleAdminReasonSelection($adminChatId, $orderId, $reasonKey, $callbackQueryId, $messageId, $adminUser)
    {
        $adminConfigId = (string) $this->settings->get('telegram_admin_chat_id');
        $receiptChannelId = (string) $this->settings->get('telegram_receipt_channel_id');
        $fromId = $adminUser ? (string) $adminUser->getId() : '';

        $isAuthorized = false;
        if (($adminConfigId !== '' && (string)$adminChatId === $adminConfigId) ||
            ($receiptChannelId !== '' && (string)$adminChatId === $receiptChannelId) ||
            ($adminConfigId !== '' && $fromId === $adminConfigId)) {
            $isAuthorized = true;
        } else if ($fromId !== '') {
            $adminDbUser = User::where('telegram_chat_id', $fromId)->first();
            if ($adminDbUser && ($adminDbUser->is_admin || (method_exists($adminDbUser, 'hasRole') && $adminDbUser->hasRole('admin')))) {
                $isAuthorized = true;
            }
        }

        if (!$isAuthorized) {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '❌ شما دسترسی ادمین ندارید.',
                'show_alert' => true
            ]);
            return;
        }

        $order = Order::find($orderId);
        if (!$order) {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '❌ سفارش یافت نشد.',
                'show_alert' => true
            ]);
            return;
        }

        // If canceled, restore the original Approve/Reject buttons
        if ($reasonKey === 'cancel') {
            if ($order->status !== 'pending') {
                Telegram::answerCallbackQuery([
                    'callback_query_id' => $callbackQueryId,
                    'text' => '❌ سفارش قبلاً پردازش شده است.',
                    'show_alert' => true
                ]);
                return;
            }

            $orderType = $order->renews_order_id ? 'تمدید سرویس' : ($order->plan_id ? 'خرید سرویس' : 'شارژ کیف پول');
            $user = $order->user;
            $userPvLink = $user->telegram_chat_id 
                ? "[{$this->escape($user->name)}](tg://user?id={$user->telegram_chat_id})" 
                : $this->escape($user->name);

            $adminMessage = "🧾 *رسید " . ($order->card_payment_receipt && str_starts_with($order->card_payment_receipt, 'text_receipt:') ? 'متنی ' : '') . "جدید برای سفارش \\#{$orderId}*\n\n";
            $adminMessage .= "*کاربر:* {$userPvLink} \\(ID: `{$user->id}`\\)\n";
            $adminMessage .= "*مبلغ:* " . $this->escape(number_format($order->amount) . ' تومان') . "\n";
            $adminMessage .= "*نوع سفارش:* " . $this->escape($orderType) . "\n";
            if ($order->card_payment_receipt && str_starts_with($order->card_payment_receipt, 'text_receipt:')) {
                $text = Str::after($order->card_payment_receipt, 'text_receipt:');
                $adminMessage .= "*متن رسید:* " . $this->escape($text) . "\n\n";
            } else {
                $adminMessage .= "\n";
            }

            $keyboard = Keyboard::make()->inline()
                ->row([
                    $this->makeInlineButton(['text' => '✅ تایید پرداخت', 'callback_data' => "admin_approve_order_{$orderId}"]),
                    $this->makeInlineButton(['text' => '❌ رد پرداخت', 'callback_data' => "admin_reject_order_{$orderId}"])
                ]);

            if ($order->card_payment_receipt && !str_starts_with($order->card_payment_receipt, 'text_receipt:')) {
                Telegram::editMessageCaption([
                    'chat_id' => $adminChatId,
                    'message_id' => $messageId,
                    'caption' => $adminMessage,
                    'parse_mode' => 'MarkdownV2',
                    'reply_markup' => $keyboard
                ]);
            } else {
                Telegram::editMessageText([
                    'chat_id' => $adminChatId,
                    'message_id' => $messageId,
                    'text' => $adminMessage,
                    'parse_mode' => 'MarkdownV2',
                    'reply_markup' => $keyboard
                ]);
            }

            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => 'انصراف داده شد.'
            ]);
            return;
        }

        // Check if order is already processed
        if ($order->status !== 'pending') {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '❌ سفارش قبلاً پردازش شده یا یافت نشد.',
                'show_alert' => true
            ]);
            return;
        }

        // Map reason key to actual reason text
        $reasonMap = [
            'invalid' => 'رسید پرداخت ارسالی نامعتبر است یا خوانا نیست',
            'amount' => 'مبلغ واریزی فیش با مبلغ فاکتور مغایرت دارد',
            'duplicate' => 'فیش ارسالی قبلاً ثبت شده و تکراری است'
        ];
        $reasonText = $reasonMap[$reasonKey] ?? 'رسید پرداخت ارسالی نامعتبر است';

        try {
            if (\App\Services\PaymentService::rejectOrder($order, $reasonText)) {
                Telegram::answerCallbackQuery([
                    'callback_query_id' => $callbackQueryId,
                    'text' => '❌ سفارش رد شد و پیام به کاربر ارسال گردید.',
                    'show_alert' => false
                ]);

                $user = $order->user;
                $userLink = "<a href=\"tg://user?id={$user->telegram_chat_id}\">" . htmlspecialchars($user->name) . "</a>";
                $adminName = $adminUser->getUsername() ? '@' . $adminUser->getUsername() : $adminUser->getFirstName();
                $this->sendToLogChannel(
                    "❌ <b>فیش سفارش #{$orderId} توسط ادمین رد شد</b>\n\n" .
                    "🔹 <b>کاربر:</b> {$userLink} (<code>{$user->telegram_chat_id}</code>)\n" .
                    "🔹 <b>مبلغ:</b> " . number_format($order->amount) . " تومان\n" .
                    "🔹 <b>دلیل رد:</b> " . htmlspecialchars($reasonText) . "\n" .
                    "🔹 <b>ادمین بررسی‌کننده:</b> " . htmlspecialchars($adminName) . "\n" .
                    "⏰ <b>زمان:</b> " . now()->format('Y-m-d H:i:s')
                );

                // Update Admin message in channel
                $adminName = $adminUser->getFirstName() . ($adminUser->getLastName() ? ' ' . $adminUser->getLastName() : '');
                $adminUsername = $adminUser->getUsername() ? '@' . $adminUser->getUsername() : $adminName;
                
                $orderType = $order->renews_order_id ? 'تمدید سرویس' : ($order->plan_id ? 'خرید سرویس' : 'شارژ کیف پول');
                $user = $order->user;
                $userPvLink = $user->telegram_chat_id 
                    ? "[{$this->escape($user->name)}](tg://user?id={$user->telegram_chat_id})" 
                    : $this->escape($user->name);

                $updatedMessage = "🧾 *سفارش \\#{$orderId} رد شد\\.*\n\n";
                $updatedMessage .= "*کاربر:* {$userPvLink} \\(ID: `{$user->id}`\\)\n";
                $updatedMessage .= "*مبلغ:* " . $this->escape(number_format($order->amount) . ' تومان') . "\n";
                $updatedMessage .= "*نوع سفارش:* " . $this->escape($orderType) . "\n\n";
                $updatedMessage .= "🔴 *وضعیت:* `رد شد توسط ادمین {$this->escape($adminUsername)}`\n";
                $updatedMessage .= "⚠️ *علت رد:* `{$this->escape($reasonText)}`";

                if ($order->card_payment_receipt && !str_starts_with($order->card_payment_receipt, 'text_receipt:')) {
                    Telegram::editMessageCaption([
                        'chat_id' => $adminChatId,
                        'message_id' => $messageId,
                        'caption' => $updatedMessage,
                        'parse_mode' => 'MarkdownV2',
                        'reply_markup' => json_encode(['inline_keyboard' => []])
                    ]);
                } else {
                    Telegram::editMessageText([
                        'chat_id' => $adminChatId,
                        'message_id' => $messageId,
                        'text' => $updatedMessage,
                        'parse_mode' => 'MarkdownV2',
                        'reply_markup' => json_encode(['inline_keyboard' => []])
                    ]);
                }
            } else {
                throw new \Exception('PaymentService::rejectOrder return false');
            }
        } catch (\Exception $e) {
            Log::error("Telegram Admin Reason Action Error: " . $e->getMessage());
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '❌ خطایی رخ داد: ' . $e->getMessage(),
                'show_alert' => true
            ]);
        }
    }


    /**
     * خانه مدرن: کارت حساب (شبیه ربات‌های فروش) + منوی تمیز
     */
    protected function sendHome($user, $chatId, $messageId = null, ?string $note = null): void
    {
        $name = htmlspecialchars($user->name ?: 'کاربر', ENT_QUOTES, 'UTF-8');
        $balance = number_format((float) ($user->balance ?? 0));

        $experience = app(RozanehExperience::class);
        $text = $experience->customEmoji('brand', '✈️') . "  <b>روزنه</b>\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "سلام <b>{$name}</b> عزیز، خوش آمدید.\n";
        $text .= "<i>" . RozanehExperience::TAGLINE . "</i>\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        if ($note) {
            $text .= "🔔 <b>" . htmlspecialchars($note, ENT_QUOTES, 'UTF-8') . "</b>\n";
            $text .= "━━━━━━━━━━━━━━━━━━━━\n\n";
        }

        $text .= $experience->customEmoji('wallet', '🪙') . " <b>موجودی کیف پول:</b> <code>{$balance} تومان</code>\n\n";
        $text .= "از اینجا می‌توانید سرویس بخرید یا تمدید کنید، تست رایگان بگیرید و سرویس‌هایتان را مدیریت کنید. برای بررسی هوشمند مشکل یا ارتباط با پشتیبان انسانی هم «پشتیبانی فرایدی» در دسترس شماست.\n\n";
        $text .= "👇 <b>چه کاری می‌خواهید انجام دهید؟</b>";

        // ── استراتژی هوشمند: فقط یک‌بار عکس بنر ارسال می‌شود ──────────────────────────
        // کلید کش برای ذخیره message_id پیام عکس هر کاربر
        $photoCacheKey      = "user_home_photo_msg_{$chatId}";
        $photoFileIdKey     = 'telegram_menu_photo_file_id';
        $cachedPhotoMsgId   = Cache::get($photoCacheKey);
        $photoFileId        = Cache::get($photoFileIdKey);
        $keyboard           = $this->getMainMenuKeyboard();

        // اگر قبلاً یک پیام عکس برای این کاربر فرستادیم، فقط Caption و Keyboard را ویرایش می‌کنیم
        if ($cachedPhotoMsgId) {
            try {
                // حذف پیام متنی قبلی (منوهای دیگر) بدون ایجاد خطا
                if ($messageId && $messageId !== $cachedPhotoMsgId) {
                    try {
                        Telegram::deleteMessage(['chat_id' => $chatId, 'message_id' => $messageId]);
                    } catch (\Throwable $th) {}
                }
                $this->cleanUiMessages($chatId);

                // ویرایش Caption – هیچ بایتی از عکس مجدداً لود نمی‌شود
                Telegram::editMessageCaption([
                    'chat_id'      => $chatId,
                    'message_id'   => $cachedPhotoMsgId,
                    'caption'      => $text,
                    'parse_mode'   => 'HTML',
                    'reply_markup' => $keyboard,
                ]);
                return; // ← کار تمام شد، نیازی به ارسال مجدد نیست
            } catch (\Throwable $e) {
                // پیام عکس توسط کاربر حذف شده → کش را پاک می‌کنیم تا دوباره ارسال شود
                Cache::forget($photoCacheKey);
                $cachedPhotoMsgId = null;
                Log::info("Photo message {$cachedPhotoMsgId} deleted by user, will resend.");
            }
        }

        // ── ارسال اولیه عکس (فقط یک‌بار در طول عمر پیام) ──────────────────────────
        try {
            // حذف پیام متنی قبلی
            if ($messageId) {
                try {
                    Telegram::deleteMessage(['chat_id' => $chatId, 'message_id' => $messageId]);
                } catch (\Throwable $th) {}
            }
            $this->cleanUiMessages($chatId);

            // تعیین منبع عکس: file_id کش‌شده (سریع) یا آپلود اولیه از URL
            if ($photoFileId) {
                $photoSource = $photoFileId;
            } else {
                $photoSource = InputFile::create(public_path('menu_banner.jpg'), 'menu_banner.jpg');
            }

            $sent = Telegram::sendPhoto([
                'chat_id'      => $chatId,
                'photo'        => $photoSource,
                'caption'      => $text,
                'parse_mode'   => 'HTML',
                'reply_markup' => $keyboard,
            ]);

            // ذخیره file_id (برای کاربران بعدی که اولین باری ارسال می‌شود)
            if (!$photoFileId && $sent && isset($sent['photo'])) {
                $photoArray = $sent['photo'];
                $highestPhoto = end($photoArray);
                if (isset($highestPhoto['file_id'])) {
                    Cache::put($photoFileIdKey, $highestPhoto['file_id'], now()->addYears(10));
                }
            }

            // ذخیره message_id پیام عکس این کاربر برای ویرایش در آینده
            if ($sent && isset($sent['message_id'])) {
                Cache::put($photoCacheKey, $sent['message_id'], now()->addDays(30));
            }
        } catch (\Throwable $e) {
            Log::error("Failed to send photo dashboard banner, falling back to text: " . $e->getMessage());
            // فال‌بک به پیام متنی در صورت بروز هرگونه خطا
            $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
        }
    }


    protected function sendAbout($chatId, $messageId = null): void
    {
        $text = "<tg-emoji emoji-id=\"6028346797368283073\">✈️</tg-emoji> <b>درباره روزنه</b>\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "<blockquote expandable>";
        $text .= "<b>روزنه | راهکار پایدار، امن و هوشمند دسترسی به اینترنت آزاد</b>\n\n";
        $text .= "روزنه با هدف ارائه بستری پایدار، پرسرعت و امن برای برقراری ارتباط بدون دغدغه و دسترسی آزاد به خدمات و اطلاعات در سراسر جهان شکل گرفته است.\n\n";
        $text .= "✨ <b>ویژگی‌ها و مزایای روزنه:</b>\n";
        $text .= "🚀 <b>کیفیت پایدار شبکه:</b> سرورهای اختصاصی پرسرعت و ترافیک بهینه‌شده با کمترین تاخیر (Ping)\n";
        $text .= "⚡ <b>تحویل تمام‌خودکار و آنی:</b> دریافت مشخصات سرویس بلافاصله پس از پرداخت\n";
        $text .= "🛡 <b>امنیت و حریم خصوصی:</b> بهره‌گیری از به‌روزترین پروتکل‌های رمزنگاری شده جهت حفاظت از اطلاعات کاربران\n";
        $text .= "🤖 <b>پشتیبانی هوشمند و آنلاین:</b> پاسخگویی سریع به سوالات و مشکلات اتصال در تمام ساعات شبانه‌روز\n";
        $text .= "📱 <b>سازگاری کامل:</b> آموزش و راهنمای راه‌اندازی آسان برای Android, iOS, Windows, macOS\n\n";
        $text .= "<i>ما همواره در تلاشیم تا با ارائه بالاترین کیفیت شبکه، ارتباطی بدون قطعی و امن را برای شما فراهم سازیم. اعتماد و همراهی شما سرمایه ماست.</i>";
        $text .= "</blockquote>\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━";

        $botSettings = TelegramBotSetting::pluck('value', 'key');
        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton([
                    'text' => '🛍️ فروشگاه و پلن‌ها', 
                    'callback_data' => '/plans', 
                    'style' => 'success',
                ]),
                $this->makeInlineButton([
                    'text' => '📖 راهنمای اتصال', 
                    'callback_data' => '/tutorials', 
                    'style' => 'primary',
                ]),
            ])
            ->row([
                $this->makeInlineButton(['text' => '🏠 بازگشت به منوی اصلی', 'callback_data' => '/start', 'style' => 'danger']),
            ]);

        $this->sendOrEditMessage($chatId, $text, $keyboard, $messageId);
    }

    /**
     * پروفایل کامل کاربر
     */
    protected function sendProfile($user, $messageId = null): void
    {
        $name = (string) ($user->name ?: 'کاربر');
        $uid = (string) ($user->telegram_chat_id ?: '-');
        $balance = number_format((float) ($user->balance ?? 0));
        $ref = (string) ($user->referral_code ?: '-');
        $joined = $user->created_at ? $user->created_at->format('Y/m/d') : '-';
        $active = 0;
        $totalOrders = 0;
        try {
            $totalOrders = $user->orders()->count();
            $active = $user->orders()->whereIn('status', ['active', 'paid', 'completed'])->count();
        } catch (\Throwable $e) {}

        $text = "<tg-emoji emoji-id=\"6053202116707622090\">👤</tg-emoji> <b>حساب کاربری من</b>\n\n";
        $text .= "🏷 <b>نام:</b> " . htmlspecialchars($name, ENT_QUOTES) . "\n";
        $text .= "🆔 <b>آیدی:</b> <code>" . htmlspecialchars($uid, ENT_QUOTES) . "</code>\n";
        $text .= "💰 <b>موجودی:</b> <code>" . htmlspecialchars($balance, ENT_QUOTES) . "</code> تومان\n";
        $text .= "📡 <b>سرویس فعال:</b> <code>" . htmlspecialchars((string) $active, ENT_QUOTES) . "</code>\n";
        $text .= "📦 <b>کل سفارش‌ها:</b> <code>" . htmlspecialchars((string) $totalOrders, ENT_QUOTES) . "</code>\n";
        $text .= "🔗 <b>کد دعوت:</b> <code>" . htmlspecialchars($ref, ENT_QUOTES) . "</code>\n";
        $text .= "📅 <b>عضویت:</b> <code>" . htmlspecialchars($joined, ENT_QUOTES) . "</code>\n";

        $keyboard = Keyboard::make()->inline()
            ->row([
                $this->makeInlineButton(['text' => '👛 کیف پول', 'callback_data' => '/wallet', 'style' => 'success']),
                $this->makeInlineButton(['text' => '📡 سرویس‌ها', 'callback_data' => '/my_services', 'style' => 'primary']),
            ])
            ->row([
                $this->makeInlineButton(['text' => '🧾 تراکنش‌ها', 'callback_data' => '/transactions', 'style' => 'primary']),
                $this->makeInlineButton(['text' => '🎁 دعوت', 'callback_data' => '/referral', 'style' => 'success']),
            ])
            ->row([
                $this->makeInlineButton(['text' => '🏠 خانه', 'callback_data' => '/start', 'style' => 'danger']),
            ]);

        $this->sendOrEditMessage($user->telegram_chat_id, $text, $keyboard, $messageId);
    }

    /**
     * Remove classic reply keyboard from user's chat (bottom phone keyboard).
     */
    protected function removeReplyKeyboard($chatId): void
    {
        // فقط یک‌بار برای هر چت — جلوگیری از اسپم ساعت‌شنی
        $flagKey = 'lookanet_kb_removed_' . $chatId;
        if (Cache::get($flagKey)) {
            return;
        }

        try {
            // متن نامرئی (نه ⏳) تا اگر delete لحظه‌ای fail شد هم زشت نباشد
            $msg = Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => "\u{2060}",
                'reply_markup' => Keyboard::make([
                    'remove_keyboard' => true,
                    'selective' => false,
                ]),
            ]);
            $messageId = $this->extractMessageId($msg);
            if ($messageId) {
                try {
                    Telegram::deleteMessage([
                        'chat_id' => $chatId,
                        'message_id' => $messageId,
                    ]);
                } catch (\Throwable $e) {
                    // اگر حذف نشد، در صف پاکسازی UI بماند
                    $this->trackUiMessage($chatId, $messageId);
                }
            } else {
                Log::warning('removeReplyKeyboard: could not read message_id');
            }
            Cache::put($flagKey, 1, now()->addDays(30));
        } catch (\Exception $e) {
            Log::warning('Failed to remove reply keyboard: ' . $e->getMessage());
        }
    }

    protected function sendOrEditMainMenu($chatId, $text, $messageId = null)
    {
        $this->sendOrEditMessage($chatId, $text, $this->getMainMenuKeyboard(), $messageId);
    }

    protected function getReplyMainMenu(): Keyboard
    {
        try {
            $webAppUrl = route('webapp.index');
            $webAppUrl = trim($webAppUrl);

            // Force HTTPS for Telegram WebApp
            if (str_starts_with($webAppUrl, 'http://')) {
                $webAppUrl = str_replace('http://', 'https://', $webAppUrl);
            }
        } catch (\Exception $e) {
            Log::warning('Route webapp.index not found', ['error' => $e->getMessage()]);
            $webAppUrl = null;
        }

        $keyboard = [
            ['🛒  تهیه اشتراک جدید', '🎛  سرویس‌های من'],
            ['💳  کیف پول و مالی', '🧾  تراکنش‌ها'],
            ['👨🏻‍💻  پشتیبانی VIP', '🎁  کسب درآمد'],
            ['📚  آموزش اتصال', '🧪  تست رایگان'],
        ];

        if ($webAppUrl) {
            array_unshift($keyboard, [
                ['text' => '📱 مدیریت حساب (Mini App)', 'web_app' => ['url' => $webAppUrl]]
            ]);
        }

        return Keyboard::make([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ]);
    }

    /**
     * ✅ تایید پرداخت فیش از تلگرام ادمین
     */
    protected function handleAdminApproveOrder($adminChatId, $orderId, $callbackQueryId, $messageId, $adminUser = null)
    {
        $adminConfigId = (string) $this->settings->get('telegram_admin_chat_id');
        $receiptChannelId = (string) $this->settings->get('telegram_receipt_channel_id');
        $fromId = $adminUser ? (string) $adminUser->getId() : '';

        $isAuthorized = false;
        if (($adminConfigId !== '' && (string)$adminChatId === $adminConfigId) ||
            ($receiptChannelId !== '' && (string)$adminChatId === $receiptChannelId) ||
            ($adminConfigId !== '' && $fromId === $adminConfigId)) {
            $isAuthorized = true;
        } else if ($fromId !== '') {
            $adminDbUser = User::where('telegram_chat_id', $fromId)->first();
            if ($adminDbUser && ($adminDbUser->is_admin || (method_exists($adminDbUser, 'hasRole') && $adminDbUser->hasRole('admin')))) {
                $isAuthorized = true;
            }
        }

        if (!$isAuthorized) {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '❌ شما دسترسی ادمین ندارید.',
                'show_alert' => true
            ]);
            return;
        }

        $order = Order::find($orderId);
        if (!$order || $order->status !== 'pending') {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '❌ سفارش قبلاً پردازش شده یا یافت نشد.',
                'show_alert' => true
            ]);
            return;
        }

        try {
            if (\App\Services\PaymentService::approveOrder($order)) {
                Telegram::answerCallbackQuery([
                    'callback_query_id' => $callbackQueryId,
                    'text' => '✅ سفارش با موفقیت تایید و فعال شد.',
                    'show_alert' => false
                ]);

                $user = $order->user;
                $userLink = "<a href=\"tg://user?id={$user->telegram_chat_id}\">" . htmlspecialchars($user->name ?: 'کاربر') . " ({$user->telegram_chat_id})</a>";
                $adminName = $adminUser ? ($adminUser->getUsername() ? '@' . $adminUser->getUsername() : $adminUser->getFirstName()) : 'مدیریت';
                $orderType = $order->renews_order_id ? 'تمدید سرویس' : ($order->plan_id ? 'خرید سرویس' : 'شارژ کیف پول');

                $this->sendToLogChannel(
                    "✅ <b>فیش سفارش #{$orderId} توسط ادمین تایید و فعال شد</b>\n\n" .
                    "🔹 <b>کاربر:</b> {$userLink}\n" .
                    "🔹 <b>مبلغ:</b> " . number_format($order->amount) . " تومان\n" .
                    "🔹 <b>نوع:</b> {$orderType}\n" .
                    "🔹 <b>ادمین تاییدکننده:</b> " . htmlspecialchars($adminName) . "\n" .
                    "⏰ <b>زمان:</b> " . now()->format('Y-m-d H:i:s')
                );

                // ویرایش پیام رسید با فرمت HTML استاندارد و غیرشکننده
                $adminString = " توسط " . htmlspecialchars($adminName);

                $updatedMessage = "🧾 <b>سفارش #{$orderId} تایید شد.</b>\n\n";
                $updatedMessage .= "👤 <b>کاربر:</b> {$userLink} (ID: <code>{$user->id}</code>)\n";
                $updatedMessage .= "💵 <b>مبلغ:</b> <code>" . number_format($order->amount) . " تومان</code>\n";
                $updatedMessage .= "📦 <b>نوع سفارش:</b> " . htmlspecialchars($orderType) . "\n\n";
                $updatedMessage .= "🟢 <b>وضعیت:</b> <code>تایید شد{$adminString}</code>";

                if ($order->card_payment_receipt && !str_starts_with($order->card_payment_receipt, 'text_receipt:')) {
                    Telegram::editMessageCaption([
                        'chat_id' => $adminChatId,
                        'message_id' => $messageId,
                        'caption' => $updatedMessage,
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode(['inline_keyboard' => []])
                    ]);
                } else {
                    Telegram::editMessageText([
                        'chat_id' => $adminChatId,
                        'message_id' => $messageId,
                        'text' => $updatedMessage,
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode(['inline_keyboard' => []])
                    ]);
                }
            } else {
                throw new \Exception('PaymentService return false');
            }
        } catch (\Throwable $e) {
            Log::error("Telegram Admin Approve Action Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQueryId,
                'text' => '❌ خطا در تایید پرداخت: ' . $e->getMessage(),
                'show_alert' => true
            ]);
        }
    }

    protected function generateBrandedQrCode($configLink, $type = 'test')
    {
        try {
            $bgFile = $type === 'paid' ? 'qr_paid_bg.jpg' : 'qr_test_bg.jpg';
            $bgPath = public_path("images/{$bgFile}");

            if (!file_exists($bgPath)) {
                $qrParams = ['size' => '400x400', 'data' => $configLink, 'ecc' => 'M', 'margin' => 10, 'format' => 'png'];
                return "https://api.qrserver.com/v1/create-qr-code/?" . http_build_query($qrParams);
            }

            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=450x450&data=" . urlencode($configLink);
            $ch = curl_init($qrUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $qrContent = curl_exec($ch);
            curl_close($ch);

            if (!$qrContent) {
                $qrParams = ['size' => '400x400', 'data' => $configLink, 'ecc' => 'M', 'margin' => 10, 'format' => 'png'];
                return "https://api.qrserver.com/v1/create-qr-code/?" . http_build_query($qrParams);
            }

            $bg = @imagecreatefromjpeg($bgPath);
            $qr = @imagecreatefromstring($qrContent);

            if (!$bg || !$qr) {
                $qrParams = ['size' => '400x400', 'data' => $configLink, 'ecc' => 'M', 'margin' => 10, 'format' => 'png'];
                return "https://api.qrserver.com/v1/create-qr-code/?" . http_build_query($qrParams);
            }

            $bgW = imagesx($bg);
            $bgH = imagesy($bg);
            $qrW = imagesx($qr);
            $qrH = imagesy($qr);

            $targetSize = 460;
            $destX = (int)(($bgW - $targetSize) / 2);
            $destY = $type === 'paid' ? 405 : 465;

            imagecopyresampled($bg, $qr, $destX, $destY, 0, 0, $targetSize, $targetSize, $qrW, $qrH);

            $fileName = "qr_" . md5($configLink . '_' . $type) . ".jpg";
            $storageDir = public_path("qrcodes");

            if (!file_exists($storageDir)) {
                @mkdir($storageDir, 0777, true);
            }

            $outputPath = $storageDir . '/' . $fileName;
            imagejpeg($bg, $outputPath, 90);
            imagedestroy($bg);
            imagedestroy($qr);

            return url("qrcodes/{$fileName}");
        } catch (\Exception $e) {
            Log::warning('Branded QR generation failed: ' . $e->getMessage());
            $qrParams = ['size' => '400x400', 'data' => $configLink, 'ecc' => 'M', 'margin' => 10, 'format' => 'png'];
            return "https://api.qrserver.com/v1/create-qr-code/?" . http_build_query($qrParams);
        }
    }

    protected function updateProgressMessage($chatId, $messageId, $percent, $statusText)
    {
        if (!$messageId) return;

        $barLength = 10;
        $filled = (int) round(($percent / 100) * $barLength);
        $empty = $barLength - $filled;
        $bar = str_repeat("█", $filled) . str_repeat("░", $empty);

        $text = "⚙️ *" . $this->escape("در حال آماده‌سازی سرویس اختصاصی روزنه") . "*\n\n";
        $text .= "`[" . $bar . "] " . $percent . "%`\n\n";
        $text .= "📌 " . $this->escape($statusText);

        try {
            Telegram::editMessageText([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'MarkdownV2'
            ]);
        } catch (\Exception $e) {}
    }

    protected function sendDirectConfigsForOrder($user, $orderId)
    {
        $order = $user->orders()->find($orderId);
        if (!$order || empty($order->config_details)) {
            Telegram::sendMessage([
                'chat_id' => $user->telegram_chat_id,
                'text'    => "❌ اطلاعات یا لینک این سفارش یافت نشد.",
            ]);
            return;
        }

        $configs = $this->fetchDirectConfigsFromSubUrl($order->config_details);
        $this->sendDirectConfigsToUser($user, $configs, $order->config_details);
    }

    protected function sendDirectConfigsForTrial($user)
    {
        $order = $user->orders()->where('payment_method', 'trial')->latest()->first();
        $subUrl = $order ? $order->config_details : \Illuminate\Support\Facades\Cache::get("trial_link_{$user->id}");

        if (empty($subUrl)) {
            $order = $user->orders()->whereIn('status', ['paid', 'active', 'completed'])->latest()->first();
            $subUrl = $order ? $order->config_details : null;
        }

        if (empty($subUrl)) {
            Telegram::sendMessage([
                'chat_id' => $user->telegram_chat_id,
                'text'    => "❌ اکانت تستی برای شما یافت نشد. لطفاً از منوی اصلی دکمه «🎁 تست رایگان» را لمس کنید.",
            ]);
            return;
        }

        $configs = $this->fetchDirectConfigsFromSubUrl($subUrl);
        $this->sendDirectConfigsToUser($user, $configs, $subUrl);
    }

    protected function fetchDirectConfigsFromSubUrl($subUrl)
    {
        $subUrlStr = trim((string) $subUrl);
        if (empty($subUrlStr)) {
            return [];
        }

        if (preg_match('/^(vless|vmess|trojan|ss|tuic|hysteria2|hy2):\/\//i', $subUrlStr)) {
            return [$subUrlStr];
        }

        // اگر ساب‌آدرس دارای آدرس قدیمی یا نامعتبر بود ولی subId مشخص داشت، به آدرس جدید مپ کن
        if (preg_match('/\/([a-zA-Z0-9_\-]{8,64})$/', $subUrlStr, $subMatches)) {
            $subId = $subMatches[1];
            $subBaseUrl = trim($this->settings->get('xui_subscription_url_base') ?? '');
            if (!empty($subBaseUrl) && (!str_contains($subUrlStr, 'sub.cinemapluss.ir') || str_contains($subUrlStr, 'irn.one'))) {
                $subUrlStr = rtrim($subBaseUrl, '/') . '/' . $subId;
            }
        }

        // استخراج آدرس تمیز از داخل متن
        if (preg_match('/(https?:\/\/[^\s"\'<>]+)/i', $subUrlStr, $urlMatches)) {
            $pureUrl = $urlMatches[1];
        } else {
            $pureUrl = $subUrlStr;
        }

        if (!filter_var($pureUrl, FILTER_VALIDATE_URL)) {
            return [];
        }

        try {
            $rawResponse = null;

            // تلاش اول: file_get_contents با استریم اختصاصی (بدون خطای unexpected EOF در OpenSSL 3)
            try {
                $ctx = stream_context_create([
                    'ssl' => [
                        'verify_peer'      => false,
                        'verify_peer_name' => false,
                    ],
                    'http' => [
                        'header'  => "User-Agent: v2rayNG/1.8.5\r\n",
                        'timeout' => 8,
                    ],
                ]);
                $rawResponse = @file_get_contents($pureUrl, false, $ctx);
            } catch (\Throwable $e) {}

            // تلاش دوم: باینری محلی curl
            if (empty($rawResponse)) {
                $cmd = "curl -s -k -L -A 'v2rayNG/1.8.5' --max-time 6 " . escapeshellarg($pureUrl);
                $rawResponse = @shell_exec($cmd);
            }

            // تلاش سوم: cURL داخلی PHP
            if (empty($rawResponse)) {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL            => $pureUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_TIMEOUT        => 8,
                    CURLOPT_CONNECTTIMEOUT => 4,
                    CURLOPT_USERAGENT      => 'v2rayNG/1.8.5',
                ]);
                $rawResponse = @curl_exec($ch);
                curl_close($ch);
            }

            if (empty($rawResponse)) {
                Log::warning("Fetch direct configs failed all attempts for {$pureUrl}");
                return [];
            }

            $decoded = @base64_decode(trim($rawResponse), true);
            $content = ($decoded !== false && preg_match('/(vless|vmess|trojan|ss|tuic|hy2|hysteria2):\/\//i', $decoded)) ? $decoded : $rawResponse;

            $lines = preg_split('/\r\n|\r|\n/', $content);
            $configs = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match('/^(vless|vmess|trojan|ss|tuic|hysteria2|hy2):\/\//i', $line)) {
                    $configs[] = $line;
                }
            }
            return array_values(array_unique($configs));
        } catch (\Exception $e) {
            Log::warning('Fetch direct configs failed: ' . $e->getMessage());
            return [];
        }
    }

    protected function sendDirectConfigsToUser($user, array $configs, $fallbackSubUrl = null)
    {
        $chatId = $user->telegram_chat_id;

        if (empty($configs)) {
            $text = "⚠️ <b>کانفیگ مستقیم استخراج نشد</b>\n\n";
            $text .= "لطفاً از لینک سابسکریپشن هوشمند زیر استفاده فرمایید که به صورت خودکار کانفیگ‌ها را در نرم‌افزار ایمپورت می‌کند:";
            if ($fallbackSubUrl) {
                $text .= "\n\n🎯 <b>لینک سابسکریپشن شما:</b>\n<code>" . htmlspecialchars($fallbackSubUrl) . "</code>";
            }

            $keyboard = Keyboard::make()->inline()->row([
                $this->makeInlineButton(['text' => '🏠 بازگشت به خانه', 'callback_data' => '/start', 'style' => 'primary'])
            ]);

            Telegram::sendMessage([
                'chat_id'      => $chatId,
                'text'         => $text,
                'parse_mode'   => 'HTML',
                'reply_markup' => $keyboard,
            ]);
            return;
        }

        $count = count($configs);
        $text = "⚡️ <b>کانفیگ‌های مستقیم اختصاصی شما ({$count} کانفیگ)</b>\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "<i>👇 روی کادر هر کانفیگ لمس کنید تا بلافاصله کپی شود:</i>\n\n";

        foreach ($configs as $idx => $cfg) {
            $num = $idx + 1;
            $remark = '';
            if (str_contains($cfg, '#')) {
                $rawRemark = substr($cfg, strpos($cfg, '#') + 1);
                $decodedRemark = trim(rawurldecode($rawRemark));
                if (!empty($decodedRemark)) {
                    $remark = ' (' . htmlspecialchars($decodedRemark) . ')';
                }
            }

            $protocol = strtoupper(strtok($cfg, ':'));
            $text .= "🔹 <b>کانفیگ {$num} [{$protocol}]{$remark}:</b>\n";
            $text .= "<code>" . htmlspecialchars($cfg) . "</code>\n\n";
        }

        $text .= "━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "💡 <b>راهنمای اتصال سریع:</b>\n";
        $text .= "۱. کانفیگ موردنظر را با لمس روی کادر آن کپی کنید.\n";
        $text .= "۲. برنامه <b>v2rayNG</b> (اندروید) یا <b>V2Box / Streisand</b> (آیفون) را باز کنید.\n";
        $text .= "۳. روی علامت <b>+</b> زده و گزینه <b>Import config from clipboard</b> را انتخاب فرمایید.";

        $keyboard = Keyboard::make()->inline()->row([
            $this->makeInlineButton(['text' => '📖 آموزش اتصال', 'callback_data' => '/tutorials', 'style' => 'primary']),
            $this->makeInlineButton(['text' => '🏠 منوی اصلی', 'callback_data' => '/start', 'style' => 'danger']),
        ]);

        try {
            Telegram::sendMessage([
                'chat_id'      => $chatId,
                'text'         => $text,
                'parse_mode'   => 'HTML',
                'reply_markup' => $keyboard,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to send direct configs: " . $e->getMessage());
            if (str_contains($e->getMessage(), 'message is too long')) {
                foreach ($configs as $idx => $cfg) {
                    $num = $idx + 1;
                    Telegram::sendMessage([
                        'chat_id'    => $chatId,
                        'text'       => "🔹 <b>کانفیگ {$num}:</b>\n<code>" . htmlspecialchars($cfg) . "</code>",
                        'parse_mode' => 'HTML',
                    ]);
                }
            }
        }
    }
}
