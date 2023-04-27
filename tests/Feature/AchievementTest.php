<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setup();

        Artisan::call('migrate');
        Artisan::call('db:seed');
    }
    /**
     * A basic feature test example.
     */
    public function test_data_returned_when_no_achievements(): void
    {
        $user = User::first();
        $response = $this->get("/users/{$user->id}/achievements");

        $response->assertStatus(200);
        $response->assertJson([

        ]);
    }
}
