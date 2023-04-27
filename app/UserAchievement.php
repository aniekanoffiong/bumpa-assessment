<?php

namespace App;

use App\Models\Badge;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserAchievement 
{
    protected User $user;
    protected Collection $nextAchievements;
    protected Badge $nextBadge;
    protected Collection $remainingToNextBadge;

    public function __construct(User $user, Collection $nextAchievements, Badge $nextBadge, Collection $remainingToNextBadge)
    {
        $this->user = $user;
        $this->nextAchievements = $nextAchievements;
        $this->nextBadge = $nextBadge;
        $this->remainingToNextBadge = $remainingToNextBadge;
    }

    public function getUnlockedAchievements(): Collection
    {
        return $this->user->achievements;
    }

    public function getNextAchievements(): Collection
    {
        return $this->nextAchievements;
    }

    public function getCurrentBadge(): Badge|null
    {
        return $this->user->lastestBadges->first();
    }

    public function getNextBadge(): Badge
    {
        return $this->nextBadge;
    }

    public function getRemainingToUnlockNextBadge(): Collection
    {
        return $this->remainingToNextBadge;
    }

    public function toArray(): array
    {
        return [
            'unlocked_achievements' => $this->getUnlockedAchievements()->pluck('name')->all(),
            'next_available_achievements' => $this->getNextAchievements()->pluck('name')->all(),
            'current_badge' => $this->getCurrentBadge()?->name,
            'next_badge' => $this->getNextBadge()->name,
            'remaining_to_unlock_next_badge' => $this->getRemainingToUnlockNextBadge()->count()
        ];
    }
}

