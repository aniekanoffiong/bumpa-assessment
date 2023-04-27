<?php

namespace App\Models;

use App\Enums\AchievementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'unlocked_at'
    ];

    protected $casts = [
        'type' => AchievementType::class
    ];

    public function badge(): HasOne
    {
        return $this->hasOne(Badge::class, 'unlocked_by_achievement_id');
    }
}
