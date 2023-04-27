<?php

namespace App\Services;

use App\Contracts\Services\PayoutServiceInterface;
use App\Exceptions\PaystackTransferException;
use App\Exceptions\PaystackTransferRecipientException;
use App\Models\BankAccount;
use App\Models\PayoutRecord;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PayStackPayoutService implements PayoutServiceInterface
{

    const BADGE_PAYOUT_AMOUNT_KOBO = 30000;
    const BADGE_PAYOUT_REASON = "Payout for Unlocking Badge";
    const PAYOUT_SOURCE_BALANCE = "balance";
    protected PendingRequest $httpClient;

    public function __construct()
    {
        $this->httpClient = Http::withHeaders([
            'Authorization' => "Bearer " . config('paystackpayout.paystack_secret_key'),
        ])->baseUrl(config('paystackpayout.payout_endpoint'));
    }

    public function makePayoutForBadgeReached(User $user): void
    {
        try {
            $bankAccount = $user->bankAccount;
            if (!$bankAccount->paystack_transfer_user_id) {
                $bankAccount = $this->createTransferRecipient($bankAccount);
            }
            $this->makePayoutToAccount($bankAccount);
        } catch (PaystackTransferRecipientException | RequestException | ConnectionException $ex) {
            Log::warning(sprintf("Failed to create Transfer Recipient for user %s", $bankAccount->user->id));
        }
    }

    protected function createTransferRecipient(BankAccount $bankAccount): BankAccount
    {
        $request = [
            "type" => "nuban",
            "name" => $bankAccount->user->name, 
            "account_number" => $bankAccount->account_number, 
            "bank_code" => $bankAccount->bank_code, 
            "currency" => "NGN"
        ];

        $response = $this->httpClient->post('transferrecipient', $request);
        $response->throw();

        return $this->updatedBankAccount($response, $bankAccount);
    }

    protected function updatedBankAccount(Response $response, BankAccount $bankAccount): BankAccount
    {
        if ($response->ok()) {
            $data = $response->json();
            $bankAccount->paystack_transfer_user_id = $data['data']['recipient_code'];
            $bankAccount->save();
            return $bankAccount;
        }

        throw new PaystackTransferRecipientException(sprintf("Failed to create Transfer Recipient for user %s", $bankAccount->user->id));
    }

    protected function makePayoutToAccount(BankAccount $bankAccount): PayoutRecord
    {
        $payoutReference = Str::uuid() . $bankAccount->user->id;
        $request = [
            "source" => self::PAYOUT_SOURCE_BALANCE,
            "reason" => self::BADGE_PAYOUT_REASON,
            "amount" => self::BADGE_PAYOUT_AMOUNT_KOBO,
            "reference" => $payoutReference,
            "recipient" => $bankAccount->paystack_transfer_user_id
        ];

        $response = $this->httpClient->post('transfer', $request);
        $response->throw();

        return $this->createPayoutRecord($response, $bankAccount, $payoutReference);
    }

    protected function createPayoutRecord(Response $response, BankAccount $bankAccount, string $payoutReference): PayoutRecord
    {
        if ($response->ok()) {
            $data = $response->json();
            return PayoutRecord::create([
                'account_id' => $bankAccount->id,
                'payout_reference' => $payoutReference,
                'payout_provider_reference' => $data['data']['transfer_code'],
                'status' => $data['data']['status']
            ]);
        }

        throw new PaystackTransferException(sprintf("Failed to payout to user %s", $bankAccount->user->id));
    }
}
