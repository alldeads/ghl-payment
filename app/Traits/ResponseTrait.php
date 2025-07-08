<?php

namespace App\Traits;

trait ResponseTrait {
    /**
     * Generate a standardized JSON response.
     *
     * @param mixed $data The data to include in the response.
     * @param string $message A message to include in the response.
     * @param int $status The HTTP status code for the response.
     * @return \Illuminate\Http\JsonResponse
     */
    public function response($data = null, $message = 'Success', $status = 200) {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], $status);
    }
}
