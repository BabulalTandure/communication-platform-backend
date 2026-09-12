<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function successResponse($data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data ?? (object)[],
        ];

        return response()->json($response, $statusCode);
    }

    protected function errorResponse(string $message = 'Error occurred', $errors = null, int $statusCode = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'errors' => $errors ?? (object)[],
        ];

        return response()->json($response, $statusCode);
    }
}
