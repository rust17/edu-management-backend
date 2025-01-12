<?php

namespace App\Traits;

/**
 * 将响应统一为这种格式，业务码为 0 表示成功，非 0 表示失败，并且可以自定义 HTTP 状态码
 * {
 *     "code": 200,
 *     "message": "操作成功",
 *     "data": null
 * }
 *
 * 使用方法：
 * return $this->success('操作成功');
 * return $this->success('操作成功', $data);
 * return $this->error('操作失败');
 * return $this->error('操作失败', 2, 200, $data);
 */
trait JsonResponse
{
    /**
     * 成功响应
     *
     * @param string $message 消息
     * @param mixed $data 数据
     * @param int $code 业务码
     * @param int $status HTTP 状态码
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success(
        string $message = '操作成功',
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
     * 失败响应
     *
     * @param string $message 消息
     * @param int $code 业务码
     * @param int $status HTTP 状态码
     * @param mixed $data 数据
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error(
        string $message = '操作失败',
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
