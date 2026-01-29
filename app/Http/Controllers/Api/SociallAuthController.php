<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SocialAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'provider' => 'google',
                    'provider_id' => $googleUser->getId(),
                    'password' => Hash::make(uniqid()),
                    'role' => 'user', // default role
                ]
            );

            $token = $user->createToken('auth_token')->plainTextToken;

            // 🔥 REDIRECT BACK TO FLUTTER
            return redirect()->to(
                "agroapp://login-success?token=$token&role={$user->role}&name={$user->name}&id={$user->id}"
            );

        } catch (\Exception $e) {
            return redirect()->to("agroapp://login-error");
        }
    }
}
