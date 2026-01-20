<?php

// use Database\Seeders\UserTypeSeeder;
// use Database\Seeders\StatusSeeder;
// use Database\Seeders\PaymentOptionSeeder;

// beforeEach(function () {
//     $this->seed(UserTypeSeeder::class);
//     $this->seed(StatusSeeder::class);
//     $this->seed(PaymentOptionSeeder::class);
// });

// test('registration screen can be rendered', function () {
//     $response = $this->get(route('register'));

//     $response->assertStatus(200);
// });

// test('new users can register', function () {
//     $response = $this->post(route('register.store'), [
//         'user_type_id' => 1,
//         'name' => 'Test User',
//         'email' => 'test@example.com',
//         'password' => 'password',
//         'password_confirmation' => 'password',
//     ]);

//     $this->assertAuthenticated();
//     $response->assertRedirect(route('dashboard', absolute: false));
// });

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
});
