<?php

namespace App\Services;

use App\Models\Link;
use App\Models\LinkClick;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use stdClass;

class LinkStats
{
    /**
     * Resumen de clics de un link entre dos fechas (por defecto, los últimos 30 días).
     *
     * @return array<string, mixed>
     */
    public function for(Link $link, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $to = ($to ?? now())->copy()->endOfDay();
        $from = ($from ?? $to->copy()->subDays(29))->copy()->startOfDay();

        $base = $link->clicks()->whereBetween('clicked_at', [$from, $to]);

        $perDay = (clone $base)
            ->selectRaw('DATE(clicked_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $days = [];
        for ($d = $from->copy(); $d->lte($to); $d = $d->addDay()) {
            $key = $d->toDateString();
            $days[] = ['date' => $key, 'clicks' => (int) ($perDay[$key] ?? 0)];
        }

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'total_clicks' => $link->clicks_count,
            'period_clicks' => (clone $base)->count(),
            'unique_visitors' => (clone $base)->distinct()->count('ip_hash'),
            'last_clicked_at' => $link->last_clicked_at?->toIso8601String(),
            'per_day' => $days,
            'referers' => $this->top(clone $base, 'referer_host', 'Directo'),
            'devices' => $this->top(clone $base, 'device', 'Desconocido'),
            'browsers' => $this->top(clone $base, 'browser', 'Desconocido'),
            'os' => $this->top(clone $base, 'os', 'Desconocido'),
            'countries' => $this->top(clone $base, 'country', 'Desconocido'),
        ];
    }

    /**
     * @param  HasMany<LinkClick, Link>  $query
     * @return list<array{label: string, clicks: int}>
     */
    private function top(HasMany $query, string $column, string $nullLabel, int $limit = 10): array
    {
        return array_values($query->toBase()
            ->select($column, DB::raw('COUNT(*) as total'))
            ->groupBy($column)
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn (stdClass $row): array => [
                'label' => is_string($row->{$column}) ? $row->{$column} : $nullLabel,
                'clicks' => (int) $row->total,
            ])
            ->all());
    }
}
