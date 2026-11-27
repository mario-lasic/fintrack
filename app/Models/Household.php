<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 * @property int $id
 */
class Household extends Model
{
    public function memberships(): HasMany
    {
        return $this->hasMany(HouseholdMembership::class);
    }

    public function activeOwnerMemberships(): HasMany
    {
        return $this->memberships()->whereRole('owner')->whereNull('deactivated_at');
    }

    protected function casts(): array
    {
        return [
            'deactivated_at' => 'datetime',
        ];
    }
}
