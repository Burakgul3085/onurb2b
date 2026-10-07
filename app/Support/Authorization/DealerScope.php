<?php

namespace App\Support\Authorization;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DealerScope
{
    /**
     * A user linked to a dealer firm can only read that firm's rows.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public static function restrict(Builder $query, User $actor, string $column): Builder
    {
        if ($actor->dealer_id !== null) {
            $query->where($column, $actor->dealer_id);
        }

        return $query;
    }
}
