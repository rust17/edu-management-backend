<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Get student list
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = User::query()->where('role', User::ROLE_STUDENT);

        // Search by keyword
        if ($request->filled('keyword')) {
            $query->where(
                fn ($query) => $query->where('name', 'like', "%{$request->keyword}%")
                    ->orWhere('email', 'like', "%{$request->keyword}%")
                );
        }

        return $this->success('Get successfully', $query->get()->map(
            fn ($student) => $student->only(['id', 'name']))
        );
    }
}
