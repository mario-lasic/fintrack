<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $household_id
 * @property int $user_id
 * @property string $role
 * @property-read Household|null $household
 * @property-read User|null $user
 * @property Carbon|null $deactivated_at
 */
class HouseholdMembership extends Model
{
    protected function casts(): array
    {
        return [
            'deactivated_at' => 'datetime',
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }
}
