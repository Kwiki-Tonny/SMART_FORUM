<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AffinityCalculator;
use Illuminate\Http\Request;

class RecommendationsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $calculator = new AffinityCalculator();

        $affinity = $calculator->getAffinity($user->id);
        $recommendations = $calculator->getRecommendations($user->id, 10);

        return view('recommendations', compact('affinity', 'recommendations'));
    }
}