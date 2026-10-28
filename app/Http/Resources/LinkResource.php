<?php

namespace App\Http\Resources;

use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Link
 */
class LinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'short_url' => $this->short_url,
            'original_url' => $this->original_url,
            'is_custom' => $this->is_custom,
            'is_active' => $this->is_active,
            'is_expired' => $this->isExpired(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'clicks' => $this->clicks_count,
            'last_clicked_at' => $this->last_clicked_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
