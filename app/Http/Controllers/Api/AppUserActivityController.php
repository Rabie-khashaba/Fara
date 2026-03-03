<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppUserActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();

        $activities = $appUser->activities()
            ->with(['post', 'subjectAppUser'])
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $activities,
        ]);
    }
}
