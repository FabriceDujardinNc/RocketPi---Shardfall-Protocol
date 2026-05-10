<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mission;
use App\Services\MissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MissionApiController extends Controller
{
    public function __construct(private readonly MissionService $service) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'daily'  => $this->service->listForUser($user, 'daily'),
            'weekly' => $this->service->listForUser($user, 'weekly'),
        ]);
    }

    public function claim(Request $request, int $id): JsonResponse
    {
        $mission = Mission::findOrFail($id);

        try {
            $result = $this->service->claim($request->user(), $mission, $request->ip());
            return response()->json($result);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
