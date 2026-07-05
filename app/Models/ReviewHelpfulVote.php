<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewHelpfulVote extends Model
{
    protected $fillable = ['review_id', 'user_id'];

    public function review()
    {
        return $this->belongsTo(CourseReview::class, 'review_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
