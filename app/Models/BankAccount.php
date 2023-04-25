<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'user_id',
        'account_number',
        'bank'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
