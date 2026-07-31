<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

// Single place that shapes every JSON response, so the envelope stays consistent
// across the whole API (see CLAUDE.md §8). Messages passed in should already be
// localized (use trans()/__()).
class ApiResponse
{
    public static function success(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json(array_filter([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], fn ($value) => ! is_null($value)), $status);
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public static function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
