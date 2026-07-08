<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class TeacherAnalyticsApiController extends Controller
{
    public function __construct(
        protected AnalyticsService $analytics
    ) {}

    public function index(Request $request)
    {
        $stats = $this->analytics->getTeacherStats($request->user()->id);
        return response()->json(['success' => true, 'data' => $stats]);
    }
}
