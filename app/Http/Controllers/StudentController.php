<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * 获取学生列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = User::query()->where('role', User::ROLE_STUDENT);

        // 按关键词搜索
        if ($request->filled('keyword')) {
            $query->where(
                fn ($query) => $query->where('name', 'like', "%{$request->keyword}%")
                    ->orWhere('email', 'like', "%{$request->keyword}%")
                );
        }

        return $this->success('获取成功', $query->get()->map(
            fn ($student) => $student->only(['id', 'name']))
        );
    }
}
