<?php

namespace App\Models;

use App\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'account_id',
        'payout_reference',
        'payout_provider_reference',
        'status',
    ];

    protected $casts = [
        'status' => PayoutStatus::class
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'account_id');
    }
}
