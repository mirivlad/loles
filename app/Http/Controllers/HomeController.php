<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // Получаем публичные курсы (или просто активные)
        $courses = Course::where('is_active', true)
            ->with('category')
            ->limit(6)
            ->get();
        
        return view('welcome', compact('courses'));
    }
}
