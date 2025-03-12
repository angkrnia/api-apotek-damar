<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception): JsonResponse
    {
        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'code' => 404,
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'code' => 404,
                'status' => false,
                'message' => 'Endpoint tidak ditemukan',
            ], 404);
        }

        // 🔹 1. Tangani Validasi Error (422)
        if ($exception instanceof ValidationException) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $exception->errors(),
            ], 422);
        }

        // 🔹 2. Tangani Data Tidak Ditemukan (404)
        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'code' => 404,
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        // 🔹 3. Tangani Endpoint Tidak Ditemukan (404)
        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'code' => 404,
                'status' => false,
                'message' => 'Endpoint tidak ditemukan',
            ], 404);
        }

        // 🔹 4. Tangani Metode HTTP Tidak Diizinkan (405)
        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'code' => 405,
                'status' => false,
                'message' => 'Metode tidak diizinkan untuk endpoint ini',
            ], 405);
        }

        // 🔹 5. Tangani User Tidak Autentikasi (401)
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'code' => 401,
                'status' => false,
                'message' => 'Anda harus login terlebih dahulu',
            ], 401);
        }

        // 🔹 6. Tangani User Tidak Punya Izin (403)
        if ($exception instanceof AuthorizationException) {
            return response()->json([
                'code' => 403,
                'status' => false,
                'message' => 'Anda tidak memiliki izin untuk melakukan tindakan ini',
            ], 403);
        }

        // 🔹 7. Tangani Limit API Terlampaui (429)
        if ($exception instanceof ThrottleRequestsException) {
            return response()->json([
                'code' => 429,
                'status' => false,
                'message' => 'Terlalu banyak permintaan, coba lagi nanti',
            ], 429);
        }

        // 🔹 8. Tangani Error Umum (500)
        return response()->json([
            'code' => 500,
            'status' => false,
            'message' => 'Terjadi kesalahan pada server',
            'error' => env('APP_DEBUG') ? $exception->getMessage() : 'Silakan hubungi administrator',
        ], 500);

        return parent::render($request, $exception);
    }
}
