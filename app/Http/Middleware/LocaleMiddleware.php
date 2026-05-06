// app/Http/Middleware/LocaleMiddleware.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    private const SUPPORTED_LOCALES = ['ja', 'en', 'zh-CN', 'zh-TW', 'ko', 'my'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);
        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        // Accept-Language ヘッダーから取得
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            foreach (explode(',', $acceptLanguage) as $lang) {
                $lang = trim(explode(';', $lang)[0]);
                if (in_array($lang, self::SUPPORTED_LOCALES)) {
                    return $lang;
                }
                // zh-cn -> zh-CN の正規化
                $normalizedLang = $this->normalizeLang($lang);
                if (in_array($normalizedLang, self::SUPPORTED_LOCALES)) {
                    return $normalizedLang;
                }
            }
        }

        // クエリパラメータから
        $queryLang = $request->query('lang');
        if ($queryLang && in_array($queryLang, self::SUPPORTED_LOCALES)) {
            return $queryLang;
        }

        return config('hotel.default_locale', 'ja');
    }

    private function normalizeLang(string $lang): string
    {
        return match (strtolower($lang)) {
            'zh-cn', 'zh_cn' => 'zh-CN',
            'zh-tw', 'zh_tw' => 'zh-TW',
            default => strtolower($lang),
        };
    }
}