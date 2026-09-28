<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request for admin authorization.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. If not logged in, redirect directly to login page
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'لطفاً ابتدا وارد حساب کاربری شوید.'], 401);
            }
            return redirect()->route('login')->with('error', 'لطفاً ابتدا وارد حساب کاربری شوید.');
        }

        $user = auth()->user();
        $adminEmails = array_filter(array_map('trim', explode(',', env('ADMIN_EMAILS', ''))));

        // 2. Check if user is admin or user ID is 1 or email in ADMIN_EMAILS
        $isAdmin = $user->is_admin || $user->id === 1 || in_array($user->email, $adminEmails);

        if (!$isAdmin) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'دسترسی غیرمجاز به پنل مدیریت'], 403);
            }
            return redirect()->route('dashboard')->with('error', 'شما دسترسی لازم برای ورود به پنل مدیریت را ندارید.');
        }

        return $next($request);
    }
}
