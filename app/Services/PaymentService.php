<?php

namespace App\Services;

use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Inbound;
use App\Services\MarzbanService;
use App\Services\XUIService;
use App\Services\PasargadService;
use App\Services\RemnawaveService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Keyboard\Keyboard;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaymentService
{
    /**
     * ✅ تایید و فعال‌سازی سفارش (خرید، تمدید یا شارژ کیف پول)
     */
    public static function approveOrder(Order $order): bool
    {
        if ($order->status === 'paid') {
            return true;
        }

        return DB::transaction(function () use ($order) {
            $settings = Setting::all()->pluck('value', 'key');
            $user = $order->user;
            $plan = $order->plan;

            // --- 1. شارژ کیف پول ---
            if (!$plan) {
                $order->update(['status' => 'paid']);
                $user->increment('balance', $order->amount);
                
                Transaction::create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'amount' => $order->amount,
                    'type' => 'deposit',
                    'status' => 'completed',
                    'description' => "شارژ کیف پول (تایید دستی فیش)"
                ]);

                $user->notifications()->create([
                    'type' => 'wallet_charged_approved',
                    'title' => 'کیف پول شارژ شد',
                    'message' => "مبلغ " . number_format($order->amount) . " تومان اضافه شد.",
                    'link' => route('dashboard', ['tab' => 'order_history'])
                ]);

                if ($user->telegram_chat_id) {
                    try {
                        $msg = "✅ *فیش واریزی شما تایید شد\\!*\n\n";
                        $msg .= "💰 کیف پول شما با موفقیت شارژ گردید\\.\n";
                        $msg .= "💵 *مبلغ:* " . number_format($order->amount) . " تومان\n";
                        $msg .= "👛 *موجودی فعلی:* " . number_format($user->fresh()->balance) . " تومان\n\n";
                        $msg .= "💡 *راهنما:* اکنون می‌توانید با ارسال دستور /start به ربات، به منوی اصلی رفته و از بخش خرید سرویس، پلن مورد نظر خود را تهیه کنید\\.";
                        
                        Telegram::setAccessToken($settings->get('telegram_bot_token'));
                        Telegram::sendMessage([
                            'chat_id' => $user->telegram_chat_id, 
                            'text' => $msg, 
                            'parse_mode' => 'MarkdownV2'
                        ]);
                    } catch (\Exception $e) {
                        Log::error("TG Notify Wallet Charge Error: " . $e->getMessage());
                    }
                }
                return true;
            }

            // --- 2. تمدید یا خرید سرویس ---
            Log::info("Approving order {$order->id} for user {$user->id}");

            $isRenewal = (bool)$order->renews_order_id;
            $originalOrder = $isRenewal ? Order::find($order->renews_order_id) : null;

            if ($isRenewal && !$originalOrder) {
                throw new \Exception('سفارش اصلی جهت تمدید یافت نشد.');
            }

            $uniqueUsername = $order->panel_username ?? "user-{$user->id}-order-" . ($isRenewal ? $originalOrder->id : $order->id);
            $uniqueUsername = trim($uniqueUsername);

            $newExpiresAt = $isRenewal 
                ? (new \DateTime($originalOrder->expires_at))->modify("+{$plan->duration_days} days") 
                : now()->addDays($plan->duration_days);

            // تشخیص سرور در حالت مالتی لوکیشن
            $isMultiLocationEnabled = filter_var($settings->get('enable_multilocation', false), FILTER_VALIDATE_BOOLEAN);
            $panelType = $settings->get('panel_type');
            $targetServer = null;

            $xuiHost = $settings->get('xui_host'); 
            $xuiUser = $settings->get('xui_user'); 
            $xuiPass = $settings->get('xui_pass'); 
            $inboundId = (int)$settings->get('xui_default_inbound_id');

            $targetServerId = $order->server_id;
            if (!$targetServerId && $isRenewal && $originalOrder) {
                $targetServerId = $originalOrder->server_id;
            }

            if ($isMultiLocationEnabled && class_exists('Modules\MultiServer\Models\Server') && $targetServerId) {
                $targetServer = \Modules\MultiServer\Models\Server::find($targetServerId);
                if ($targetServer && $targetServer->is_active) {
                    $panelType = 'xui'; 
                    $xuiHost = $targetServer->full_host; 
                    $xuiUser = $targetServer->username; 
                    $xuiPass = $targetServer->password; 
                    $inboundId = $targetServer->inbound_id;

                    if ($isRenewal && !$order->server_id) {
                        $order->server_id = $targetServerId;
                    }
                }
            }

            $success = false;
            $finalConfig = '';
            $finalUuid = null;
            $finalSubId = null;

            try {
            // مارزبان
            if ($panelType === 'marzban') {
                $marzbanService = new MarzbanService(
                    (string) $settings->get('marzban_host'),
                    (string) $settings->get('marzban_sudo_username'),
                    (string) $settings->get('marzban_sudo_password'),
                    (string) $settings->get('marzban_node_hostname')
                );
                $userData = ['expire' => $newExpiresAt->getTimestamp(), 'data_limit' => $plan->volume_gb * 1073741824];
                if ($isRenewal) {
                    $response = $marzbanService->updateUser($uniqueUsername, $userData);
                    $marzbanService->resetUserTraffic($uniqueUsername);
                } else {
                    $response = $marzbanService->createUser(array_merge($userData, ['username' => $uniqueUsername]));
                }
                if ($response && (isset($response['subscription_url']) || isset($response['username']))) {
                    $finalConfig = $marzbanService->generateSubscriptionLink($response);
                    $success = true;
                } else throw new \Exception('خطا در ارتباط با پنل مرزبان');

            // رمنایو
            } elseif ($panelType === 'remnawave') {
                $remnawaveService = new RemnawaveService(
                    (string) $settings->get('remnawave_host'),
                    (string) $settings->get('remnawave_api_token'),
                    (string) $settings->get('remnawave_node_hostname')
                );
                $userData = ['expire' => $newExpiresAt->getTimestamp(), 'data_limit' => $plan->volume_gb * 1073741824];
                if ($isRenewal) {
                    $response = $remnawaveService->updateUser($uniqueUsername, $userData);
                    $remnawaveService->resetTraffic($uniqueUsername);
                } else {
                    $response = $remnawaveService->createUser(array_merge($userData, [
                        'username' => $uniqueUsername,
                        'squad_uuid' => $settings->get('remnawave_squad_uuid'),
                    ]));
                }
                if ($response && (isset($response['subscriptionUrl']) || isset($response['username']))) {
                    $finalConfig = $remnawaveService->generateSubscriptionLink($response);
                    $success = true;
                } else throw new \Exception('خطا در پنل Remnawave');

            // پاسارگاد
            } elseif ($panelType === 'pasargad') {
                $pasargadService = new PasargadService(
                    (string) $settings->get('pasargad_host'),
                    (string) $settings->get('pasargad_sudo_username'),
                    (string) $settings->get('pasargad_sudo_password'),
                    (string) $settings->get('pasargad_node_hostname')
                );

                if ($isRenewal) {
                    $response = $pasargadService->updateUser($uniqueUsername, [
                        'expire' => $newExpiresAt->getTimestamp(),
                        'data_limit' => $plan->volume_gb * 1073741824,
                        'status' => 'active',
                    ]);
                    $resetRes = $pasargadService->resetUserTraffic($uniqueUsername);
                    if ($response !== null && $resetRes !== false) {
                        $finalConfig = $originalOrder->config_details;
                        $success = true;
                    } else throw new \Exception('خطا در تمدید کاربر در پنل پاسارگاد');
                } else {
                    $response = $pasargadService->createUser([
                        'username' => $uniqueUsername,
                        'expire' => $newExpiresAt->getTimestamp(),
                        'data_limit' => $plan->volume_gb * 1073741824,
                        'group_ids' => [(int)($plan->pasargad_group_id ?? $settings->get('pasargad_paid_group_id') ?? 1)],
                    ]);
                    if ($response && (isset($response['subscription_url']) || isset($response['username']))) {
                        $finalConfig = $response['subscription_url'] ?? $pasargadService->generateSubscriptionLink($uniqueUsername);
                        $success = true;
                    } else throw new \Exception('خطا در ساخت کاربر در پنل پاسارگاد');
                }

            // X-UI (Sanaei)
            } elseif ($panelType === 'xui') {
                $xui = new XUIService($xuiHost, $xuiUser, $xuiPass);
                if (!$xui->login()) throw new \Exception('خطا در لاگین X-UI');

                $inboundData = null;
                if ($targetServer) {
                    $inbounds = $xui->getInbounds();
                    if (is_array($inbounds)) {
                        foreach ($inbounds as $i) {
                            if (($i['id'] ?? null) == $inboundId) {
                                $inboundData = $i;
                                break;
                            }
                        }
                    }
                } else {
                    $im = null;
                    if (!empty($inboundId)) {
                        $im = Inbound::whereJsonContains('inbound_data->id', (int)$inboundId)->first() ?: Inbound::find($inboundId);
                    }
                    if (!$im) {
                        $im = Inbound::first();
                    }
                    if ($im) {
                        $inboundData = is_string($im->inbound_data) ? json_decode($im->inbound_data, true) : $im->inbound_data;
                    }
                }

                if (!$inboundData) {
                    $liveInbounds = $xui->getInbounds();
                    if (!empty($liveInbounds) && is_array($liveInbounds)) {
                        $inboundData = $liveInbounds[0];
                    }
                }
                if (!$inboundData) throw new \Exception('اینباند در سرور یافت نشد.');

                $linkType = $targetServer ? ($targetServer->link_type ?? 'single') : $settings->get('xui_link_type', 'single');
                $clientData = ['email' => $uniqueUsername, 'total' => $plan->volume_gb * 1073741824, 'expiryTime' => $newExpiresAt->getTimestamp() * 1000];

                if ($isRenewal) {
                    $clients = $xui->getClients($inboundData['id']);
                    $client = collect($clients)->first(function ($c) use ($uniqueUsername) {
                        return strtolower(trim($c['email'])) === strtolower(trim($uniqueUsername));
                    });

                    if ($client) {
                        $clientData['id'] = $client['id'];
                        $clientData['subId'] = $client['subId'] ?? Str::random(16);
                        $upRes = $xui->updateClient($inboundData['id'], $client['id'], $clientData);
                        if ($upRes && ($upRes['success'] ?? false)) {
                            $xui->resetClientTraffic($inboundData['id'], $uniqueUsername);
                            $finalUuid = $client['id'];
                            $finalSubId = $clientData['subId'];
                        } else throw new \Exception('خطا در آپدیت کاربر X-UI');
                    } else throw new \Exception("کاربر {$uniqueUsername} یافت نشد.");
                } else {
                    $clients = $xui->getClients($inboundData['id']);
                    $existingClient = collect($clients)->first(function ($c) use ($uniqueUsername) {
                        return strtolower(trim($c['email'])) === strtolower(trim($uniqueUsername));
                    });

                    if ($existingClient) {
                        $clientData['id'] = $existingClient['id'];
                        $clientData['subId'] = $existingClient['subId'] ?? Str::random(16);
                        $upRes = $xui->updateClient($inboundData['id'], $existingClient['id'], $clientData);
                        if ($upRes && ($upRes['success'] ?? false)) {
                            $xui->resetClientTraffic($inboundData['id'], $uniqueUsername);
                            $finalUuid = $existingClient['id'];
                            $finalSubId = $clientData['subId'];
                        } else throw new \Exception('خطا در آپدیت کاربر موجود');
                    } else {
                        if ($linkType === 'subscription') $clientData['subId'] = Str::random(16);
                        $addRes = $xui->addClient($inboundData['id'], $clientData);
                        if ($addRes && ($addRes['success'] ?? false)) {
                            $finalUuid = $addRes['generated_uuid'] ?? json_decode($addRes['obj']['settings'], true)['clients'][0]['id'];
                            $finalSubId = $addRes['generated_subId'] ?? $clientData['subId'];
                            if ($targetServer) $targetServer->increment('current_users');
                        } else throw new \Exception('خطا در ساخت کاربر: ' . ($addRes['msg'] ?? 'Unknown error'));
                    }
                }

                $stream = json_decode($inboundData['streamSettings'] ?? '{}', true);
                $proto = $inboundData['protocol'] ?? 'vless';
                $port = $inboundData['port'] ?? 443;

                switch ($linkType) {
                    case 'subscription':
                        $subUrl = $targetServer ? ($targetServer->subscription_domain ?? parse_url($xuiHost, PHP_URL_HOST)) : $settings->get('xui_subscription_url_base');
                        $subPort = $targetServer ? ($targetServer->subscription_port ?? 2053) : '';
                        $prot = ($targetServer && !$targetServer->is_https) ? 'http' : 'https';
                        $base = rtrim($subUrl, '/');
                        if($subPort && !Str::contains($base, ":$subPort")) $base .= ":$subPort";
                        if(!Str::startsWith($base, 'http')) $base = "$prot://$base";
                        $parsedSub = parse_url($base);
                        $subPath = trim($parsedSub['path'] ?? '', '/');
                        $finalConfig = !empty($subPath) 
                            ? ("$base/$finalSubId") 
                            : ("$base" . ($targetServer->subscription_path ?? '/sub/') . $finalSubId);
                        break;

                    case 'tunnel':
                        $tunAddr = $targetServer->tunnel_address;
                        $tunPort = $targetServer->tunnel_port ?? 443;
                        $tls = filter_var($targetServer->tunnel_is_https, FILTER_VALIDATE_BOOLEAN);

                        $p = ['type' => $stream['network'] ?? 'tcp'];
                        if ($tls) {
                            $p['security'] = 'tls';
                            $p['sni'] = $tunAddr;
                        } else {
                            $p['security'] = 'none';
                            if($proto === 'vless') $p['encryption'] = 'none';
                        }
                        if (($p['type'] ?? '') === 'ws') {
                            $p['path'] = $stream['wsSettings']['path'] ?? '/';
                            $p['host'] = $stream['wsSettings']['headers']['Host'] ?? $tunAddr;
                        }
                        $remark = ($targetServer->location->flag ?? "🏳️") . "-" . $uniqueUsername;
                        $qs = http_build_query($p);
                        $finalConfig = "vless://{$finalUuid}@{$tunAddr}:{$tunPort}?{$qs}#" . rawurlencode($remark);
                        break;

                    default:
                        if (!$finalUuid) throw new \Exception("UUID پیدا نشد");
                        $p = ['type' => $stream['network'] ?? 'tcp', 'security' => $stream['security'] ?? 'none'];
                        if ($p['security'] === 'tls') $p['sni'] = parse_url($xuiHost, PHP_URL_HOST);
                        $qs = http_build_query(array_filter($p));
                        $finalConfig = "vless://{$finalUuid}@" . parse_url($xuiHost, PHP_URL_HOST) . ":{$inboundId}?{$qs}#" . rawurlencode($plan->name);
                }
                $success = true;
            }
            } catch (\Throwable $e) {
                 Log::error("Provisioning failed due to panel error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                 // Fallback to Wallet Deposit
                 $order->update(['status' => 'paid', 'plan_id' => null, 'renews_order_id' => null]);
                 $user->increment('balance', $order->amount);
                 Transaction::create([
                     'user_id' => $user->id,
                     'order_id' => $order->id,
                     'amount' => $order->amount,
                     'type' => 'deposit',
                     'status' => 'completed',
                     'description' => "شارژ کیف پول (قطعی سرور هنگام تایید فیش)"
                 ]);
                 if ($user->telegram_chat_id) {
                     try {
                         $msg = "✅ *فیش شما تایید شد\\!*\n\n⚠️ متاسفانه در این لحظه ارتباط با سرور برای ساخت کانفیگ برقرار نشد\\. مبلغ پرداختی به عنوان شارژ به کیف پول شما در ربات اضافه گردید\\.\n💡 *شما می‌توانید از طریق ربات و با موجودی کیف پول خود، اقدام به خرید کنید\\.*";
                         Telegram::setAccessToken($settings->get('telegram_bot_token'));
                         Telegram::sendMessage(['chat_id' => $user->telegram_chat_id, 'text' => $msg, 'parse_mode' => 'MarkdownV2']);
                     } catch (\Exception $ex) {}
                 }
                 return true;
            }

            if ($success) {
                $dataToUpdate = [
                    'config_details' => $finalConfig,
                    'expires_at' => $newExpiresAt,
                    'panel_username' => $uniqueUsername,
                    'panel_client_id' => $finalUuid,
                    'panel_sub_id' => $finalSubId
                ];

                if($isRenewal) {
                    $originalOrder->update($dataToUpdate);
                    $user->update(['show_renewal_notification' => true]);
                    $user->notifications()->create(['type'=>'renew','title'=>'تمدید شد','message'=>"تمدید {$plan->name}",'link'=>route('dashboard')]);
                } else {
                    $order->update($dataToUpdate);
                    $user->notifications()->create(['type'=>'activate','title'=>'فعال شد','message'=>"خرید {$plan->name}",'link'=>route('dashboard')]);
                }

                $order->update(['status' => 'paid']);
                $description = ($isRenewal ? "تمدید سرویس" : "خرید سرویس") . " {$plan->name}";
                
                Transaction::create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'amount' => $plan->price,
                    'type' => 'purchase',
                    'status' => 'completed',
                    'description' => $description
                ]);

                if (class_exists(OrderPaid::class)) {
                    OrderPaid::dispatch($order);
                }

                // ارسال نوتیفیکیشن تلگرام به کاربر
                if ($user->telegram_chat_id) {
                    try {
                        Telegram::setAccessToken($settings->get('telegram_bot_token'));

                        $displayOrder = $isRenewal ? $originalOrder : $order;
                        $displayOrder->load(['server.location', 'plan']);

                        $server = $displayOrder->server;
                        $serverName = $server?->name ?? 'سرور اصلی';
                        $locationFlag = $server?->location?->flag ?? '🏳️';
                        $locationName = $server?->location?->name ?? 'نامشخص';
                        $planModel = $displayOrder->plan;

                        $escape = function($text) {
                            $chars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
                            return str_replace($chars, array_map(fn($c) => '\\' . $c, $chars), $text);
                        };

                        $escapeCode = function($text) {
                            return str_replace(['\\', '`'], ['\\\\', '\`'], $text);
                        };

                        $msgTitle = $isRenewal ? "تمدید موفق\\!" : "خرید موفق\\!";
                        $msgText = "✅ *" . $msgTitle . "*\n\n";
                        $msgText .= "📦 *پلن:* `" . $escapeCode($planModel->name) . "`\n";

                        if (!$isRenewal) {
                            $msgText .= "🌍 *موقعیت:* {$locationFlag} " . $escape($locationName) . "\n";
                            $msgText .= "🖥 *سرور:* " . $escape($serverName) . "\n";
                        }

                        $msgText .= "💾 *حجم:* " . $escape($planModel->volume_gb . ' گیگابایت') . "\n";
                        $msgText .= "📅 *مدت:* " . $escape($planModel->duration_days . ' روز') . "\n";
                        $msgText .= "⏳ *انقضا:* `" . $displayOrder->expires_at->format('Y/m/d H:i') . "`\n";
                        $msgText .= "👤 *یوزرنیم:* `" . $escapeCode($displayOrder->panel_username) . "`\n\n";
                        $msgText .= "🔗 *لینک کانفیگ شما:*\n";
                        $msgText .= "`" . $escapeCode($finalConfig) . "`\n\n";
                        $msgText .= $escape("⚠️ روی لینک بالا کلیک کنید تا کپی شود") . "\n\n";
                        $msgText .= $escape("💡 راهنما: با ارسال دستور /start به ربات، می‌توانید سرویس‌ها، گزارش تراکنش‌ها و بخش پشتیبانی خود را مدیریت کنید.");

                        $keyboard = Keyboard::make()->inline()
                            ->row([
                                Keyboard::inlineButton(['text' => '📋 دریافت لینک اشتراک', 'callback_data' => "copy_link_{$displayOrder->id}"]),
                                Keyboard::inlineButton(['text' => '🔗 کانفیگ‌های مستقیم', 'callback_data' => "direct_configs_order_{$displayOrder->id}"])
                            ])
                            ->row([
                                Keyboard::inlineButton(['text' => '📱 بارکد QR', 'callback_data' => "qrcode_order_{$displayOrder->id}"]),
                                Keyboard::inlineButton(['text' => '🛠 سرویس‌های من', 'callback_data' => '/my_services'])
                            ])
                            ->row([
                                Keyboard::inlineButton(['text' => '🏠 منوی اصلی', 'callback_data' => '/start'])
                            ]);

                        Telegram::sendMessage([
                            'chat_id' => $user->telegram_chat_id,
                            'text' => $msgText,
                            'parse_mode' => 'MarkdownV2',
                            'reply_markup' => $keyboard
                        ]);

                    } catch (\Exception $e) {
                        Log::error('Error sending TG success message: ' . $e->getMessage());
                    }
                }
                return true;
            }
            return false;
        });
    }

    /**
     * ❌ رد پرداخت (توسط ادمین تلگرام یا پنل)
     */
    public static function rejectOrder(Order $order, string $reason = ''): bool
    {
        if ($order->status !== 'pending') {
            return false;
        }

        $order->update(['status' => 'expired']);
        $user = $order->user;
        $settings = Setting::all()->pluck('value', 'key');

        if ($user->telegram_chat_id) {
            try {
                $msg = "❌ *پرداخت شما تایید نشد\\!*\n\n";
                $msg .= "سفارش \\#{$order->id} شما رد شد\\.\n";
                if ($reason) {
                    $msg .= "⚠️ *علت رد:* " . str_replace(['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'], array_map(fn($c) => '\\' . $c, ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!']), $reason) . "\n\n";
                } else {
                    $msg .= "⚠️ رسید پرداخت ارسالی نامعتبر است یا مبلغ به حساب واریز نشده است\\.\n\n";
                }
                $msg .= "📞 در صورت وجود ابهام، از طریق بخش پشتیبانی (دستور /start) با ما در ارتباط باشید\\.";

                Telegram::setAccessToken($settings->get('telegram_bot_token'));
                Telegram::sendMessage([
                    'chat_id' => $user->telegram_chat_id,
                    'text' => $msg,
                    'parse_mode' => 'MarkdownV2'
                ]);
            } catch (\Exception $e) {
                Log::error("TG Notify Wallet Reject Error: " . $e->getMessage());
            }
        }
        return true;
    }
}
