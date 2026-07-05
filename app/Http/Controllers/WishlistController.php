<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wishlist;
use App\Http\Resources\CourseResource;

class WishlistController extends Controller
{
    // Get user's wishlist
    public function index(Request $request)
    {
        $wishlists = Wishlist::where('user_id', $request->user()->id)
            ->with(['course.teacher:id,name', 'course.category:id,name'])
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => CourseResource::collection($wishlists->pluck('course')),
            'meta' => [
                'current_page' => $wishlists->currentPage(),
                'last_page' => $wishlists->lastPage(),
                'total' => $wishlists->total(),
            ]
        ]);
    }

    // Toggle course in wishlist
    public function toggle(Request $request, $courseId)
    {
        $user = $request->user();
        
        $wishlist = Wishlist::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            return response()->json([
                'success' => true,
                'message' => 'Course removed from wishlist'
            ]);
        }

        Wishlist::create([
            'user_id' => $user->id,
            'course_id' => $courseId
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Course added to wishlist'
        ]);
    }
}
