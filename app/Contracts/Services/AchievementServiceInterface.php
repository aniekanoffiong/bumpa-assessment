<?php

namespace App\Contracts\Services;

use App\Models\User;

interface AchievementServiceInterface {
    
    public function userAchievements(User $user);

}
