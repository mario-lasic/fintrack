<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
/**
 * @property int $household_id
 * @property int $user_id
 * @property string $role
 * @property-read Household|null $household
 * @property-read User|null $user
 */
class HouseholdMembership extends Model
{
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
