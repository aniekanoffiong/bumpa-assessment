<?php

namespace App\Services;

use App\Contracts\Services\AchievementServiceInterface;
use App\Enums\AchievementType;
use App\Models\Achievement;
use App\Models\Badge;
use App\Models\User;
use App\UserAchievement;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AchievementService implements AchievementServiceInterface
{

    protected BadgeService $badgeService;

    public function __construct(BadgeService $badgeService)
    {
        $this->badgeService = $badgeService;
    }
    
    /**
     * @param User $user
     * @return UserAchievement
     */
    public function userAchievements(User $user): UserAchievement
    {
        $nextAchievements = $this->nextAchievements($user->achievements);
        $nextBadge = $this->badgeService->nextBadge($user->achievements);
        $remainingToNextBadge = $this->achievementsUntilNextBadge($user, $nextBadge);
        return new UserAchievement($user, $nextAchievements, $nextBadge, $remainingToNextBadge);
    }

    public function validateAchievementReached(AchievementType $type, int $count): Achievement
    {
        return Achievement::where('type', $type)->where('unlocked_at', $count)->first();
    }

    /**
     * @param Collection $achievements
     * @return Collection
     */
    protected function nextAchievements(Collection $achievements): Collection
    {
        $whenAchievementAvailable = $achievements->isNotEmpty() ? $achievements : false;
        $subQuery = Achievement::from("achievements as a1")->select('a1.type', DB::raw('min(a1.unlocked_at)'))
            ->when($whenAchievementAvailable, function(QueryBuilder $query, Collection $achievements) {
                $query->whereNotIn('a1.id', $achievements->pluck('id')->all());
            })->groupBy('a1.type');

        return Achievement::from("achievements as a0")->whereIn(DB::raw('(a0.type, a0.unlocked_at)'), $subQuery)
            ->when($whenAchievementAvailable, function(QueryBuilder $query, Collection $achievements) {
                $query->whereNotIn('a0.id', $achievements->pluck('id')->all());
            })->get();
    }

    /**
     * @param User $user
     * @param Badge $nextBadge
     * @return Collection
     */
    protected function achievementsUntilNextBadge(User $user, Badge $nextBadge): Collection
    {
        $nextBadgeAchievement = $nextBadge->achievement;
        $latestAchievement = $user->latestAchievements()->first() ?: $this->firstAchievementOfType($nextBadgeAchievement->type);
        return Achievement::whereBetween('unlocked_at', [$latestAchievement->unlocked_at, $nextBadgeAchievement->unlocked_at])
            ->where('type', $nextBadgeAchievement->type)
            ->get();
    }

    protected function firstAchievementOfType(AchievementType $achievementType): Achievement
    {
        return Achievement::where('type', $achievementType)->orderBy('unlocked_at', 'asc')->first();
    }

}
