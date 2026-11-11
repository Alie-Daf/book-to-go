<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserDocument;
use App\Services\BillingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    // letters, spaces, dots, apostrophes and hyphens only (example: Dela Cruz, O'Neil, Ana-Marie)
    private const NAME_RULE = 'regex:/^[\pL][\pL\s.\'\-]*$/u';

    public function showLogin() { return view('auth.login'); }
    public function showRegister() { return view('auth.register'); }

    // Login asks for email + password
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $credentials['email'] = Str::lower(trim($credentials['email']));

        // after 5 wrong tries the same email and IP must wait a minute (stops password guessing)
        $key = $credentials['email'].'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.'])
                ->onlyInput('email');
        }

        // Active and Hold accounts can log in (a host on hold must reach Billing to pay)
        if (! Auth::attempt($credentials + [fn (Builder $query) => $query->whereIn('account_status', ['Active', 'Hold'])])) {
            RateLimiter::hit($key, 60);

            return back()->withErrors(['email' => 'Wrong email or password.'])->onlyInput('email');
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        BillingService::checkOverdue();   // event: someone logged in

        return redirect('/');
    }

    // Registration asks for first name and last name in separate boxes
    public function register(Request $request)
    {
        $docTypes = array_keys(UserDocument::HOST_DOCUMENTS);

        // a host must send every verification document
        $docRules = [];
        foreach ($docTypes as $i => $type) {
            $docRules["documents.$i"] = 'required_if:role,Host|file|mimes:jpg,jpeg,png,pdf|max:4096';
        }

        $data = $request->validate([
            'first_name' => ['required', 'max:80', self::NAME_RULE, Rule::unique('users', 'first_name')->where('last_name', $request->last_name)],
            'last_name' => ['required', 'max:80', self::NAME_RULE],
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:Guest,Host',
            'privacy' => 'accepted',
        ] + $docRules, [
            'first_name.unique' => 'Someone with this first and last name is already registered. Add a middle initial, for example "Maria S.".',
            'first_name.regex' => 'The first name may only contain letters.',
            'last_name.regex' => 'The last name may only contain letters.',
            'documents.*.required_if' => 'Hosts must upload all the required documents.',
            'privacy.accepted' => 'You must agree to the data privacy notice.',
        ]);

        // the account and its documents are saved together or not at all
        $user = DB::transaction(function () use ($data, $request, $docTypes) {
            $user = User::create([
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'email' => $data['email'],
                'password_hash' => Hash::make($data['password']),   // password hashing
                'role' => $data['role'],
                'privacy_consent_at' => now(),                      // proof of consent (Data Privacy Act)
            ]);

            if ($user->role === 'Host') {
                foreach ($docTypes as $i => $type) {
                    $user->submitDocument($type, $request->file("documents.$i"));
                }
            }

            return $user;
        });

        Auth::login($user);

        return $user->role === 'Host'
            ? redirect()->route('host.profile')->with('warning', 'Thank you! The admin will review your documents. You can add units once your account is verified.')
            : redirect('/');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}