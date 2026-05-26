<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => 1,
            'is_active' => 1,
        ];

        foreach (['phone', 'address', 'area'] as $field) {
            if (Schema::hasColumn('users', $field)) {
                $userData[$field] = $request->input($field);
            }
        }

        $user = User::create($userData);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->forget('url.intended');

        return redirect()->route('home');
    }
}
