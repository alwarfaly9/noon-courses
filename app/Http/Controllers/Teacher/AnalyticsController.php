<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        protected AnalyticsService $analytics
    ) {}

    public function index(Request $request)
    {
        $teacherId = $request->user()->id;
        $stats = $this->analytics->getTeacherStats($teacherId);
        return view('teacher.analytics', compact('stats'));
    }
}
