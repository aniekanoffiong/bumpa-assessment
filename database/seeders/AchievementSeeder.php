<?php

namespace Database\Seeders;

use App\Enums\AchievementType;
use App\Models\Achievement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Achievement::factory()
                ->count(10)
                ->sequence(
                    ['type' => AchievementType::PAYMENT],
                    ['type' => AchievementType::COMMENT],
                )
                ->sequence(fn (Sequence $sequence) => ['unlocked_at' => $sequence->index == 0 ? 1 : $sequence->index * 5])
                ->create();
    }
}
