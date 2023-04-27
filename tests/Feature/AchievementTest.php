<?php

namespace Tests\Feature;

use App\Enums\AchievementType;
use App\Enums\PaymentStatus;
use App\Models\Achievement;
use App\Models\Badge;
use App\Models\BankAccount;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    use RefreshDatabase;

    protected Collection $paymentAchievements;
    protected Collection $commentAchievements;
    protected Collection $badgeCollection;

    protected function setUp(): void
    {
        parent::setup();

        Artisan::call('migrate');
        User::factory()->create();
        $this->badgeCollection = new Collection();
        $this->paymentAchievements = $this->generateAchievementsAndBadges(AchievementType::PAYMENT);
        $this->commentAchievements = $this->generateAchievementsAndBadges(AchievementType::COMMENT);
    }

    public function test_data_returned_when_no_achievements(): void
    {
        $user = User::first();
        $response = $this->get("/users/{$user->id}/achievements");

        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('unlocked_achievements', [])
                ->where('next_available_achievements', [
                    $this->paymentAchievements->get(0)->name,
                    $this->commentAchievements->get(0)->name
                ])
                ->where('current_badge', null)
                ->where('next_badge', $this->badgeCollection->get(0)->name)
                ->where('remaining_to_unlock_next_badge', 1)
        );
    }

    public function test_data_returned_when_single_achievement_created(): void
    {
        $user = User::first();
        $user->achievements()->attach($this->paymentAchievements->get(0)->id);
        $response = $this->get("/users/{$user->id}/achievements");

        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('unlocked_achievements', [
                $this->paymentAchievements->get(0)->name
            ])
                ->where('next_available_achievements', [
                    $this->paymentAchievements->get(1)->name,
                    $this->commentAchievements->get(0)->name
                ])
                ->where('current_badge', null)
                ->where('next_badge', $this->badgeCollection->get(0)->name)
                ->where('remaining_to_unlock_next_badge', 1)
        );
    }

    public function test_data_returned_when_achievement_across_multiple_types_unlocked(): void
    {
        $user = User::first();
        $user->achievements()->attach($this->paymentAchievements->get(0)->id);
        $user->achievements()->attach($this->commentAchievements->get(0)->id);
        $response = $this->get("/users/{$user->id}/achievements");

        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('unlocked_achievements', [
                $this->paymentAchievements->get(0)->name,
                $this->commentAchievements->get(0)->name
            ])
                ->where('next_available_achievements', [
                    $this->paymentAchievements->get(1)->name,
                    $this->commentAchievements->get(1)->name
                ])
                ->where('current_badge', null)
                ->where('next_badge', $this->badgeCollection->get(0)->name)
                ->where('remaining_to_unlock_next_badge', 1)
        );
    }

    public function test_data_returned_when_achievement_across_multiple_types_unlocked_and_badge_unlocked(): void
    {
        $user = User::first();
        $user->achievements()->attach($this->paymentAchievements->get(0)->id);
        $user->achievements()->attach($this->paymentAchievements->get(1)->id);
        $user->achievements()->attach($this->commentAchievements->get(0)->id);
        $user->achievements()->attach($this->commentAchievements->get(1)->id);
        $user->badges()->attach($this->badgeCollection->get(0)->id);
        $response = $this->get("/users/{$user->id}/achievements");

        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('unlocked_achievements', [
                $this->paymentAchievements->get(0)->name,
                $this->paymentAchievements->get(1)->name,
                $this->commentAchievements->get(0)->name,
                $this->commentAchievements->get(1)->name,
            ])
                ->where('next_available_achievements', [
                    $this->paymentAchievements->get(2)->name,
                    $this->commentAchievements->get(2)->name
                ])
                ->where('current_badge', $this->badgeCollection->get(0)->name)
                ->where('next_badge', $this->badgeCollection->get(1)->name)
                ->where('remaining_to_unlock_next_badge', 2)
        );
    }

    public function test_data_returned_when_payment_is_made(): void
    {
        $user = User::first();
        $this->createPayment($user);
        $response = $this->get("/users/{$user->id}/achievements");

        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('unlocked_achievements', [
                $this->paymentAchievements->get(0)->name,
            ])
                ->where('next_available_achievements', [
                    $this->paymentAchievements->get(1)->name,
                    $this->commentAchievements->get(0)->name
                ])
                ->where('current_badge', null)
                ->where('next_badge', $this->badgeCollection->get(0)->name)
                ->where('remaining_to_unlock_next_badge', 1)
        );
    }

    public function test_data_returned_when_multiple_payments_are_made_and_badge_unlocked(): void
    {
        Config::set('paystackpayout.payout_endpoint', 'https://api.paystack.co/');
        $user = User::first();
        $data = $this->someTransferStatusResponse();
        $bankAccount = $this->createUserBankAccount($user);
        Http::fake([
            'https://api.paystack.co/*' => Http::sequence()
                    ->push($this->someTransferRecipientResponse(), 200)
                    ->push($this->someTransferStatusResponse(), 200),
        ]);
        $this->createPayment($user);
        $this->createPayment($user);
        $response = $this->get("/users/{$user->id}/achievements");

        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('unlocked_achievements', [
                $this->paymentAchievements->get(0)->name,
                $this->paymentAchievements->get(1)->name,
            ])
                ->where('next_available_achievements', [
                    $this->paymentAchievements->get(2)->name,
                    $this->commentAchievements->get(0)->name
                ])
                ->where('current_badge', $this->badgeCollection->get(0)->name)
                ->where('next_badge', $this->badgeCollection->get(3)->name)
                ->where('remaining_to_unlock_next_badge', 1)
        );

        $this->assertDatabaseHas('bank_accounts', [
            'paystack_transfer_user_id' => 'RCP_hkfjaqotsudv3de',
        ]);
        $this->assertDatabaseCount('payout_records', 1);
        $this->assertDatabaseHas('payout_records', [
            'account_id' => $bankAccount->id,
            'payout_provider_reference' => $data['data']['transfer_code'],
            'status' => $data['data']['status']
        ]);
    }

    private function generateAchievementsAndBadges(AchievementType $type): Collection
    {
        $achievements = new Collection();
        for($i = 0; $i < 6; $i++) {
            $achievement =  Achievement::create([
                'name' => Str::random(15),
                'type' => $type,
                'unlocked_at' => $i == 0 ? 1 : $i * 2,
            ]);
            if (($i + 1) % 2 == 0) {
                $this->badgeCollection->push(
                    Badge::create([
                        'unlocked_by_achievement_id' => $achievement->id,
                        'name' => Str::random(10)
                    ])
                );
            }
            $achievements->push($achievement);
        }
        return $achievements;
    }

    private function createPayment(User $user)
    {
        Payment::create([
            'user_id' => $user->id,
            'title' => Str::random(10),
            'amount' => 5000,
            'status' => PaymentStatus::SUCCESSFUL
        ]);
    }

    private function createUserBankAccount(User $user): BankAccount
    {
        return BankAccount::create([
            'user_id' => $user->id,
            'bank_code' => 012,
            'account_number' => 9389487290,
        ]);
    }

    private function someTransferRecipientResponse(): array
    {
        return [
            "status" => true,
            "message" => "Transfer recipient created successfully",
            "data" => [
                "active" => true,
                "currency" => "NGN",
                "id" => 53191171,
                "name" => "Aniekan Offiong",
                "recipient_code" => "RCP_hkfjaqotsudv3de",
                "type" => "nuban",
            ]
        ];
    }

    private function someTransferStatusResponse(): array
    {
        return [
            "event" => "transfer.pending",
            "data" => [
                "amount" => 30000,
                "currency" => "NGN",
                "id" => 37272792,
                "reason" => "Payout for Unlocking Badge",
                "reference" => "1jhbs3ozmen0k7y5efmw",
                "source" => "balance",
                "source_details" => null,
                "status" => "pending",
                "transfer_code" => "TRF_wpl1dem4967avzm",
            ]
        ];
    }
}
