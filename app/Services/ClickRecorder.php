<?php

namespace App\Services;

use App\Models\Link;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

/**
 * Registra un clic en un link corto: un INSERT liviano más el contador.
 * Los bots y los previews de apps de mensajería no cuentan.
 */
class ClickRecorder
{
    /**
     * Previews de links que no siempre detecta CrawlerDetect.
     */
    private const PREVIEW_AGENTS = [
        'whatsapp', 'facebookexternalhit', 'slackbot', 'telegrambot', 'twitterbot',
        'discordbot', 'linkedinbot', 'skypeuripreview', 'googlebot', 'bingbot', 'embedly',
    ];

    public function record(Link $link, Request $request): void
    {
        if (! $this->shouldCount($request)) {
            return;
        }

        $agent = new Agent($request->headers->all(), (string) $request->userAgent());

        $link->clicks()->create([
            'clicked_at' => now(),
            'referer_host' => $this->refererHost($request),
            'device' => $this->device($agent),
            'browser' => $agent->browser() ?: null,
            'os' => $agent->platform() ?: null,
            'country' => $this->country($request),
            'ip_hash' => $request->ip() ? hash('sha256', config('app.key').$request->ip()) : null,
        ]);

        $link->newQuery()->whereKey($link->id)->update([
            'clicks_count' => $link->clicks_count + 1,
            'last_clicked_at' => now(),
        ]);
    }

    public function shouldCount(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        $ua = strtolower((string) $request->userAgent());

        if ($ua === '') {
            return false;
        }

        foreach (self::PREVIEW_AGENTS as $needle) {
            if (str_contains($ua, $needle)) {
                return false;
            }
        }

        return ! (new Agent($request->headers->all(), $ua))->isRobot();
    }

    private function refererHost(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        return $host ? substr(preg_replace('/^www\./', '', strtolower($host)), 0, 255) : null;
    }

    private function device(Agent $agent): string
    {
        return match (true) {
            $agent->isTablet() => 'tablet',
            $agent->isMobile() => 'mobile',
            default => 'desktop',
        };
    }

    private function country(Request $request): ?string
    {
        $country = strtoupper((string) $request->headers->get('CF-IPCountry'));

        return preg_match('/^[A-Z]{2}$/', $country) && $country !== 'XX' ? $country : null;
    }
}
