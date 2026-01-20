<?php

use App\Models\User;
use App\Models\UserType;
use Database\Seeders\UserTypeSeeder;
use Database\Seeders\StatusSeeder;
use Database\Seeders\PaymentOptionSeeder;

beforeEach(function () {
    $this->seed(UserTypeSeeder::class);
    $this->seed(StatusSeeder::class);
    $this->seed(PaymentOptionSeeder::class);
});



test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});


test('authenticated users can visit their own dashboard')
    ->with(dashboards())
    ->tap(function ($type, $dashboardRoute) {
        $user = createUserWithType($type);

        $this->actingAs($user);

        $response = $this->get(route($dashboardRoute));
        $response->assertStatus(200);
    });

