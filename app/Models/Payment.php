<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Events\PaymentSuccessful;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'amount',
        'status',
    ];

    protected $casts = [
        'status' => PaymentStatus::class
    ];

    protected $dispatchesEvents = [
        'saved' => PaymentSuccessful::class
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
