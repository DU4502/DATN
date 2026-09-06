<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DashboardDrilldownRequest;
use App\Services\DashboardDrilldownService;
use Illuminate\Http\JsonResponse;

class DashboardDrilldownController extends Controller
{
    public function __invoke(DashboardDrilldownRequest $request, DashboardDrilldownService $service): JsonResponse
    {
        return response()->json($service->detail($request, $request->validated()));
    }
}
