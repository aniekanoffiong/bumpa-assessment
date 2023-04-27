<?php

namespace App\Http\Controllers;

use App\Contracts\Services\AchievementServiceInterface;
use App\Http\Resources\UserAchievementResource;
use App\Models\User;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    private AchievementServiceInterface $achievementService;

    public function __construct(AchievementServiceInterface $achievementService)
    {
        $this->achievementService = $achievementService;
    }
    
    public function list(User $user): UserAchievementResource
    {
        $achievements = $this->achievementService->userAchievements($user);
        return new UserAchievementResource($achievements);
    }
}
