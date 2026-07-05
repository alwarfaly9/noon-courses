<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image ? url("storage/{$this->image}") : null,
            'video_intro' => $this->video_intro ? url("storage/{$this->video_intro}") : null,
            'price' => (float) $this->price,
            'discount_price' => $this->discount_price ? (float) $this->discount_price : null,
            'level' => $this->level,
            'language' => $this->language,
            'status' => $this->status,
            'rating' => (float) $this->rating,
            'reviews_count' => (int) $this->reviews_count,
            'students_count' => (int) $this->students_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'teacher' => new UserResource($this->whenLoaded('teacher')),
        ];
    }
}
