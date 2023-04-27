<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'unlocked_by_achievement_id',
        'name',
    ];

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'unlocked_by_achievement_id');
    }
}
