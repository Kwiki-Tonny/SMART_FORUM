<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function toggle(Post $post)
    {
        $user = auth()->user();
        $liked = $post->toggleLike($user);
        return response()->json([
            'liked' => $liked,
            'count' => $post->likes_count,
        ]);
    }
}