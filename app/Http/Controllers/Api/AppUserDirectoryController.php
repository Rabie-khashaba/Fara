<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use Illuminate\Http\JsonResponse;

class AppUserDirectoryController extends Controller
{
    public function index(): JsonResponse
    {
        $users = AppUser::query()
            ->select(['id', 'name', 'profile_image'])
            ->orderBy('name')
            ->get()
            ->map(fn (AppUser $appUser) => [
                'id' => $appUser->id,
                'name' => $appUser->name,
                'image' => $appUser->profile_image_url,
            ]);

        return response()->json([
            'status' => true,
            'data' => $users,
        ]);
    }
}
