<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'phone' => [
                'required',
                'regex:/^(08[0-9]{0,13}|628[0-9]{0,12})$/',
                'digits_between:10,15',
            ],
            'password' => ['required', 'string', 'max:255']
        ], [
            'phone.required' => 'No HP wajib diisi.',
            'phone.regex' => 'No HP harus diawali dengan 08 atau 628.',
            'phone.digits_between' => 'No HP harus terdiri dari antara 10 hingga 15 digit.',
            'password.required' => 'Password wajib diisi.',
            'password.max' => 'Password tidak boleh lebih dari 255 karakter.',
        ]);

        try {
            if (preg_match('/^08/',  $request->phone)) {
                $phone = '628' . substr($request->phone, 2);
                $request->merge(['phone' => $phone]);
            }

            $credentials = $request->only('phone', 'password');
            $token = Auth::attempt($credentials);

            if (!$token) {
                return response()->json([
                    'code' => 401,
                    'status' => false,
                    'message' => 'No Whatsapp atau password salah.',
                ], 401);
            }

            $user = Auth::user();
            $refreshToken = JWTAuth::fromUser($user);

            $user->update([
                'refresh_token' => $refreshToken
            ]);

            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Login berhasil.',
                'data' => [
                    'token' => $token,
                    'refresh_token' => $refreshToken,
                    'user' => $user
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'code'    => 500,
                'status'  => false,
                'message' => $th->getMessage() ?: '',
            ], 500);
        }
    }

    public function register(Request $request)
    {
        $request->validate([
            'fullname' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'regex:/^(08[0-9]{0,13}|628[0-9]{0,12})$/', // Memastikan diawali dengan 08 atau 628
                'digits_between:10,15', // Memastikan panjang antara 10 sampai 15 angka
                Rule::unique('users', 'phone'),
            ],
            'password' => ['required', 'min:6'],
        ], [
            'fullname.required' => 'Nama Lengkap wajib diisi.',
            'phone.required' => 'No HP wajib diisi.',
            'phone.regex' => 'No HP harus diawali dengan 08 atau 628.',
            'phone.digits_between' => 'No HP harus terdiri dari antara 10 hingga 15 digit.',
            'password.required' => 'Password wajib diisi.',
            'password.max' => 'Password tidak boleh lebih dari 255 karakter.',
            'phone.unique' => 'No HP sudah pernah terdaftar.',
        ]);

        try {
            if (preg_match('/^08/',  $request->phone)) {
                $phone = '628' . substr($request->phone, 2);
                $request->merge(['phone' => $phone]);
            }

            $request->validate([
                'phone' => [
                    'required',
                    'regex:/^(08[0-9]{0,13}|628[0-9]{0,12})$/', // Memastikan diawali dengan 08 atau 628
                    'digits_between:10,15', // Memastikan panjang antara 10 sampai 15 angka
                    Rule::unique('users', 'phone'),
                ],
            ], [
                'phone.required' => 'No HP wajib diisi.',
                'phone.regex' => 'No HP harus diawali dengan 08 atau 628.',
                'phone.digits_between' => 'No HP harus terdiri dari antara 10 hingga 15 digit.',
                'phone.unique' => 'No HP sudah pernah terdaftar.',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'code'    => 422,
                'status'  => false,
                'message' => $th->getMessage() ?: 'Registrasi gagal.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $user = User::create($request->all());

            DB::commit();

            return response()->json([
                'code'      => 201,
                'status'    => true,
                'message'   => 'Registrasi berhasil.',
                'data'      => $user,
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'code'    => 500,
                'status'  => false,
                'message' => $th->getMessage() ?: 'Registrasi gagal.',
            ], 500);
        }
    }

    public function refreshToken(Request $request)
    {
        $request->validate([
            'refresh_token' => ['required', 'string', 'exists:users,refresh_token']
        ]);

        $user = User::where('refresh_token', $request->refresh_token)->first();

        if ($user) {
            Auth::login($user);

            $newToken = JWTAuth::fromUser($user);

            return response()->json([
                'code' => 200,
                'status' => true,
                'data' => [
                    'token' => $newToken,
                ]
            ]);
        } else {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }
    }
}
