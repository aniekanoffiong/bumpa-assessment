<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Badge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BadgeService 
{

    public function nextBadge(Collection $achievements): Badge
    {
        $whenCheck = $achievements->isNotEmpty() ? $achievements : false;
        return Badge::leftJoin('achievements', 'badges.unlocked_by_achievement_id', '=', 'achievements.id')
            ->when($whenCheck, function(Builder $queryWhen, Collection $achievements) {
                $queryWhen->whereNotIn('achievements.id', $achievements->pluck('id')->all());
            })->orderBy('achievements.unlocked_at', 'asc')
            ->select('badges.*')->first();
    }

    public function validateBadgeReached(Achievement $achievement): Badge|null
    {
        return Badge::where('unlocked_by_achievement_id', $achievement->id)->first();
    }

}
