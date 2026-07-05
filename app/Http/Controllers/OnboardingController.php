<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\LearningPath;
use App\Models\Skill;
use App\Models\UserInterest;
use App\Models\UserSkillGoal;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * OnboardingController
 *
 * Guides a new user through:
 *  Step 1: GET  /onboarding/categories  — list available categories for interest selection
 *  Step 2: GET  /onboarding/skills      — list skills (optionally filtered by selected categories)
 *  Step 3: POST /onboarding/complete    — save interests + skill goals, mark onboarding done
 *  Step 4: GET  /onboarding/recommendations — first personalised set of paths/courses
 */
class OnboardingController extends Controller
{
    public function __construct(private readonly RecommendationService $recommendations) {}

    /** Step 1 — available categories with course counts */
    public function categories()
    {
        $categories = Category::withCount(['courses' => fn($q) =>
            $q->where('status', 'published')
        ])
        ->having('courses_count', '>', 0)
        ->orderByDesc('courses_count')
        ->get(['id', 'name', 'icon', 'image_url']);

        return response()->json(['success' => true, 'data' => $categories]);
    }

    /** Step 2 — skills, optionally filtered to selected category courses */
    public function skills(Request $request)
    {
        $categoryIds = $request->input('category_ids', []);

        $query = Skill::where('is_active', true)->orderByDesc('users_count');

        if (!empty($categoryIds)) {
            // Return skills that appear on courses in the selected categories
            $courseIds = \App\Models\Course::whereIn('category_id', $categoryIds)
                ->where('status', 'published')
                ->pluck('id');
            $skillIds = DB::table('course_skills')->whereIn('course_id', $courseIds)->pluck('skill_id')->unique();
            $query->whereIn('id', $skillIds);
        }

        return response()->json(['success' => true, 'data' => $query->limit(24)->get(['id', 'name', 'category'])]);
    }

    /**
     * Step 3 — save interests + skill goals, mark onboarding complete.
     * POST /onboarding/complete
     * Body: { category_ids: [1,2], skill_ids: [3,4] }
     */
    public function complete(Request $request)
    {
        $validated = $request->validate([
            'category_ids'   => 'required|array|min:1|max:8',
            'category_ids.*' => 'integer|exists:categories,id',
            'skill_ids'      => 'nullable|array|max:10',
            'skill_ids.*'    => 'integer|exists:skills,id',
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $validated) {
            // Persist interests
            $interestData = collect($validated['category_ids'])->mapWithKeys(
                fn($id) => [$id => ['created_at' => now(), 'updated_at' => now()]]
            )->all();
            DB::table('user_interests')->where('user_id', $user->id)->delete();
            DB::table('user_interests')->insert(
                collect($validated['category_ids'])->map(fn($id) => [
                    'user_id' => $user->id, 'category_id' => $id,
                    'created_at' => now(), 'updated_at' => now(),
                ])->toArray()
            );

            // Persist skill goals
            if (!empty($validated['skill_ids'])) {
                DB::table('user_skill_goals')->where('user_id', $user->id)->delete();
                DB::table('user_skill_goals')->insert(
                    collect($validated['skill_ids'])->map(fn($id) => [
                        'user_id' => $user->id, 'skill_id' => $id,
                        'created_at' => now(), 'updated_at' => now(),
                    ])->toArray()
                );
            }

            // Mark onboarding complete
            $user->update(['onboarding_completed' => true]);
        });

        return response()->json(['success' => true, 'message' => 'تم إعداد ملفك التعليمي بنجاح!']);
    }

    /** Step 4 — first personalised recommendation after onboarding */
    public function recommendations(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'paths'   => $this->recommendations->recommendPaths($user->id, 3),
                'courses' => $this->recommendations->recommendCourses($user->id, 6),
                'skills'  => $this->recommendations->recommendSkills($user->id, 6),
            ],
        ]);
    }
}
