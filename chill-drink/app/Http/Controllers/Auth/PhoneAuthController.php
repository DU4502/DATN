<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class PhoneAuthController extends Controller
{
    /**
     * Verify a Firebase Phone Auth ID token and log in or register the user.
     */
    public function verifyPhone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'max:30'],
            'firebase_uid' => ['nullable', 'string', 'max:128'],
            'firebase_id_token' => ['required', 'string'],
        ]);

        try {
            $firebaseUser = $this->verifyFirebaseIdToken($validated['firebase_id_token']);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Không thể xác minh mã SMS. Vui lòng thử lại.',
            ], 401);
        }

        $verifiedPhone = (string) ($firebaseUser->phone_number ?? '');

        if ($verifiedPhone === '') {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản Firebase chưa có số điện thoại đã xác minh.',
            ], 422);
        }

        if ($this->formatInternationalPhone($validated['phone_number']) !== $this->formatInternationalPhone($verifiedPhone)) {
            return response()->json([
                'success' => false,
                'message' => 'Số điện thoại không khớp với mã OTP đã xác minh.',
            ], 422);
        }

        $phoneIntl = $this->formatInternationalPhone($verifiedPhone);
        $phoneLocal = $this->formatLocalPhone($verifiedPhone);

        $user = User::query()
            ->where('phone', $phoneLocal)
            ->orWhere('phone', $phoneIntl)
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => 'Khách hàng '.$phoneLocal,
                'email' => $this->makePhoneEmail($phoneLocal),
                'phone' => $phoneLocal,
                'password' => Hash::make(Str::random(40)),
                'role_id' => 1,
                'is_active' => 1,
                'email_verified_at' => now(),
            ]);
        }

        if (Schema::hasColumn('users', 'is_active') && ! (bool) $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản của bạn đã bị khóa.',
            ], 403);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập bằng SMS OTP thành công.',
            'redirect' => $this->redirectUrlFor($user),
        ]);
    }

    private function verifyFirebaseIdToken(string $idToken): object
    {
        $projectId = (string) config('services.firebase.phone_auth.project_id');

        if ($projectId === '') {
            throw new \RuntimeException('Firebase project id is not configured.');
        }

        $oldLeeway = JWT::$leeway;
        JWT::$leeway = 60;

        try {
            $payload = JWT::decode($idToken, $this->firebasePublicKeys());
        } finally {
            JWT::$leeway = $oldLeeway;
        }

        $expectedIssuer = 'https://securetoken.google.com/'.$projectId;

        if (($payload->aud ?? null) !== $projectId || ($payload->iss ?? null) !== $expectedIssuer) {
            throw new \UnexpectedValueException('Firebase token issuer or audience is invalid.');
        }

        if (blank($payload->sub ?? '') || strlen((string) $payload->sub) > 128) {
            throw new \UnexpectedValueException('Firebase token subject is invalid.');
        }

        return $payload;
    }

    /**
     * @return array<string, Key>
     */
    private function firebasePublicKeys(): array
    {
        $certificates = Cache::remember('firebase_phone_auth_certificates', now()->addHour(), function (): array {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get('https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com');

            if (! $response->successful() || ! is_array($response->json())) {
                throw new \RuntimeException('Unable to fetch Firebase public certificates.');
            }

            return $response->json();
        });

        $keys = [];

        foreach ($certificates as $keyId => $certificate) {
            if (is_string($keyId) && is_string($certificate) && $certificate !== '') {
                $keys[$keyId] = new Key($certificate, 'RS256');
            }
        }

        if ($keys === []) {
            throw new \RuntimeException('Firebase public certificates are empty.');
        }

        return $keys;
    }

    private function makePhoneEmail(string $phoneLocal): string
    {
        $email = $phoneLocal.'@chilldrink.local';

        if (! User::where('email', $email)->exists()) {
            return $email;
        }

        do {
            $email = 'phone_'.Str::random(10).'@chilldrink.local';
        } while (User::where('email', $email)->exists());

        return $email;
    }

    private function redirectUrlFor(User $user): string
    {
        if ($user->isSuperAdmin()) {
            request()->session()->forget('url.intended');

            return route('admin.super-admin', absolute: false);
        }

        if ($user->isAdmin()) {
            request()->session()->forget('url.intended');

            return route('admin.dashboard', absolute: false);
        }

        if ($user->isCskh()) {
            request()->session()->forget('url.intended');

            return route('admin.chat.index', absolute: false);
        }

        if ($user->isShipper()) {
            request()->session()->forget('url.intended');

            return route('shipper.dashboard', absolute: false);
        }

        if ($user->isStaffOnly()) {
            request()->session()->forget('url.intended');

            return route('staff.dashboard', absolute: false);
        }

        $intended = (string) request()->session()->pull('url.intended', '');

        if ($intended !== '' && ! str_contains($intended, '/chat')) {
            return $intended;
        }

        return route('home', absolute: false);
    }

    private function formatInternationalPhone(string $phone): string
    {
        $cleaned = preg_replace('/[^\d+]/', '', $phone) ?: '';

        if (str_starts_with($cleaned, '0')) {
            return '+84'.substr($cleaned, 1);
        }

        if (str_starts_with($cleaned, '84')) {
            return '+'.$cleaned;
        }

        if (! str_starts_with($cleaned, '+')) {
            return '+'.$cleaned;
        }

        return $cleaned;
    }

    private function formatLocalPhone(string $phone): string
    {
        $cleaned = preg_replace('/\D/', '', $phone) ?: '';

        if (str_starts_with($cleaned, '84')) {
            return '0'.substr($cleaned, 2);
        }

        if (! str_starts_with($cleaned, '0')) {
            return '0'.$cleaned;
        }

        return $cleaned;
    }
}
