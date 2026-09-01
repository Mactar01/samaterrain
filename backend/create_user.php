<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
App\Models\User::where('email', 'test@test.com')->delete();
$u = App\Models\User::create([
    'name' => 'Joueur Test',
    'email' => 'test@test.com',
    'password' => Illuminate\Support\Facades\Hash::make('password'),
    'role' => 'player',
    'phone' => '+221770000000',
    'is_active' => true
]);
echo "Created: " . $u->email;
