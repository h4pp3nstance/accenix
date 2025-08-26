<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

trait SelfRegisterHandler
{
    /**
     * Handle self-registration logic and call WSO2 IS self-register API.
     */
    public function handleSelfRegister($request)
    {
        // Only allow browser-based requests (not direct API/curl)
        if (!$request->hasHeader('referer') || !$request->hasHeader('origin')) {
            return response()->json(['error' => 'Registration via API is not allowed.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => [
                'required',
                'regex:/^\+\d{8,15}$/',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:30',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,30}$/',
            ],
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'password.regex' => 'Password must be 8-30 characters, include at least 1 uppercase, 1 lowercase, 1 number, and 1 special character.',
            'phone.regex' => 'Phone number must start with + and include country code, e.g. +628123456789.'
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Prepare addresses (if any future use)
        $addresses = $request->input('addresses', []);

        // Handle profile picture upload (optional, persistent like admin)
        $photoUrl = null;
        if ($request->hasFile('profile_picture')) {
            $image = $request->file('profile_picture');
            $filename = 'user_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $manager = new \Intervention\Image\ImageManager(\Intervention\Image\Drivers\Gd\Driver::class);
            $img = $manager->read($image->getRealPath())
                ->cover(256, 256)
                ->toJpeg(80);
            $path = 'img-users/' . $filename;
            \Storage::disk('public')->put($path, $img);
            $photoUrl = '/storage/' . $path;
        }

        // Build payload for WSO2 IS self-register API (use username field for username)
        $payload = [
            'user' => [
                'username' => $request->input('username'),
                'password' => $request->input('password'),
                'claims' => [
                    [ 'uri' => 'http://wso2.org/claims/givenname', 'value' => $request->input('first_name') ],
                    [ 'uri' => 'http://wso2.org/claims/lastname', 'value' => $request->input('last_name') ],
                    [ 'uri' => 'http://wso2.org/claims/emailaddress', 'value' => $request->input('email') ],
                    [ 'uri' => 'http://wso2.org/claims/telephone', 'value' => $request->input('phone') ],
                ],
            ],
            'properties' => [],
        ];
        // Add addresses
        foreach ($addresses as $address) {
            if (!empty($address['value'])) {
                $payload['user']['claims'][] = [
                    'uri' => 'http://wso2.org/claims/streetaddress',
                    'value' => $address['value']
                ];
            }
        }
        // Add photo URL if uploaded
        if ($photoUrl) {
            $payload['user']['claims'][] = [
                'uri' => 'http://wso2.org/claims/photo',
                'value' => $photoUrl
            ];
        }

        // Call WSO2 IS self-register API
        $apiUrl = env('USER_URL');
        $clientId = env('IS_CLIENT_ID');
        $clientSecret = env('IS_CLIENT_SECRET');
        $response = Http::withBasicAuth($clientId, $clientSecret)
            ->acceptJson()
            ->withOptions(['verify' => false])
            ->post($apiUrl, $payload);

        if ($response->successful()) {
            return redirect()->route('login')->with('success', 'Registration successful! Please check your email for confirmation.');
        } else {
            $error = $response->json('message') ?? 'Registration failed.';
            return back()->withErrors($error)->withInput();
        }
    }
}
