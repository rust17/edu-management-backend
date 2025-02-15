<?php

namespace App\Traits;

/**
 * Unify the response to this format, the business code is 0 for success, non-0 for failure, and the HTTP status code can be customized
 * {
 *     "code": 200,
 *     "message": "Operation successful",
 *     "data": null
 * }
 *
 * How to use:
 * return $this->success('Operation successful');
 * return $this->success('Operation successful', $data);
 * return $this->error('Operation failed');
 * return $this->error('Operation failed', 2, 200, $data);
 */
trait JsonResponse
{
    /**
     * Success response
     *
     * @param string $message Message
     * @param mixed $data Data
     * @param int $code Business code
     * @param int $status HTTP status code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success(
        string $message = 'Operation successful',
        $data = null,
        int $code = 0,
        int $status = 200
    ) {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Error response
     *
     * @param string $message Message
     * @param int $code Business code
     * @param int $status HTTP status code
     * @param mixed $data Data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error(
        string $message = 'Operation failed',
        int $code = 1,
        int $status = 400,
        $data = null
    ) {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data
        ], $status);
    }
}
