<?php

namespace App\Listeners;

use App\Contracts\Services\PayoutServiceInterface;
use App\Enums\AchievementType;
use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Events\PaymentSuccessful;
use App\Models\Payment;
use App\Services\AchievementService;
use App\Services\BadgeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class VerifyAchievementReached
{

    protected AchievementService $achievementService;
    protected BadgeService $badgeService;
    protected PayoutServiceInterface $payoutService;
    /**
     * Create the event listener.
     */
    public function __construct(
        AchievementService $achievementService,
        BadgeService $badgeService,
        PayoutServiceInterface $payoutService
    )
    {
        $this->achievementService = $achievementService;
        $this->badgeService = $badgeService;
        $this->payoutService = $payoutService;
    }

    /**
     * Handle the event.
     * @param PaymentSuccessful $event
     * @return void
     */
    public function handle(PaymentSuccessful $event): void
    {
        $paymentUser = $event->payment->user;
        $paymentsCount = Payment::where('user_id', $paymentUser->id)->count();
        $achievement = $this->achievementService->validateAchievementReached(AchievementType::PAYMENT, $paymentsCount);
        if ($achievement) {
            $paymentUser->achievements()->attach($achievement->id);
            AchievementUnlocked::dispatch($achievement->name, $paymentUser);

            $badge = $this->badgeService->validateBadgeReached($achievement);
            if ($badge) {
                $paymentUser->badges()->attach($badge->id);
                BadgeUnlocked::dispatch($badge->name, $paymentUser);
                $this->payoutService->makePayoutForBadgeReached($paymentUser);
            }
        }
    }
}
