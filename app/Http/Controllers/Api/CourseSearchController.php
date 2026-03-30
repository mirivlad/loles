<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseSearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = $request->get('q', '');
        
        $courses = Course::query()
            ->with('category')
            ->where('title', 'like', "%{$query}%")
            ->orderBy('title')
            ->limit(50)
            ->get()
            ->map(function($course) {
                return [
                    'id' => $course->id,
                    'text' => $course->title . ($course->category ? " ({$course->category->name})" : ''),
                ];
            });
        
        return response()->json($courses);
    }
}
