<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\UserSkill;
use App\Services\SkillService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SkillController extends Controller
{
    public function __construct(private readonly SkillService $service) {}

    /**
     * GET /api/v1/skills  (public)
     */
    public function index(Request $request)
    {
        $skills = Cache::remember('all_skills', 600, fn() =>
            Skill::active()->orderByDesc('users_count')->get()
        );

        return response()->json(['success' => true, 'data' => $skills]);
    }

    /**
     * GET /api/v1/skills/trending  (public)
     */
    public function trending()
    {
        $skills = Cache::remember('trending_skills', 300, fn() =>
            $this->service->getTrending(12)
        );

        return response()->json(['success' => true, 'data' => $skills]);
    }

    /**
     * GET /api/v1/user/skills  (auth)
     */
    public function mySkills(Request $request)
    {
        $skills = UserSkill::where('user_id', $request->user()->id)
            ->with(['skill', 'earnedViaCourse:id,title', 'earnedViaPath:id,title'])
            ->orderByDesc('earned_at')
            ->get();

        return response()->json(['success' => true, 'data' => $skills]);
    }

    /**
     * GET /api/v1/skills/recommended  (auth)
     */
    public function recommended(Request $request)
    {
        $skills = $this->service->getRecommended($request->user());

        return response()->json(['success' => true, 'data' => $skills]);
    }
}
