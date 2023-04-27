<?php

namespace App\Contracts\Services;

use App\Models\User;

interface PayoutServiceInterface {
    
    public function makePayoutForBadgeReached(User $user);

}
