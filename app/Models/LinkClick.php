<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $link_id
 * @property Carbon $clicked_at
 * @property string|null $referer_host
 * @property string|null $device
 * @property string|null $browser
 * @property string|null $os
 * @property string|null $country
 * @property string|null $ip_hash
 */
#[Fillable(['clicked_at', 'referer_host', 'device', 'browser', 'os', 'country', 'ip_hash'])]
class LinkClick extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Link, $this>
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
