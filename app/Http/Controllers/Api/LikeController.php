<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Toggle like/unlike pada post
     */
    public function toggle(Request $request, $postId)
    {
        $post = Post::find($postId);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post tidak ditemukan',
            ], 404);
        }

        $userId = $request->user()->id;

        // Cek apakah user sudah like post ini
        $like = Like::where('post_id', $postId)
            ->where('user_id', $userId)
            ->first();

        if ($like) {
            // Unlike (hapus like)
            $like->delete();
            $isLiked = false;
            $message = 'Post di-unlike';
        } else {
            // Like (tambah like)
            Like::create([
                'post_id' => $postId,
                'user_id' => $userId,
            ]);
            $isLiked = true;
            $message = 'Post di-like';
        }

        // Get updated likes count
        $likesCount = Like::where('post_id', $postId)->count();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'post_id' => $postId,
                'is_liked' => $isLiked,
                'likes_count' => $likesCount,
            ],
        ]);
    }

    /**
     * Get semua likes untuk post tertentu
     */
    public function index(Request $request, $postId)
    {
        $post = Post::find($postId);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post tidak ditemukan',
            ], 404);
        }

        $likes = Like::with('user:id,name,email,profile_photo')
            ->where('post_id', $postId)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'post_id' => $postId,
                'likes_count' => $likes->count(),
                'likes' => $likes->map(function ($like) {
                    return [
                        'id' => $like->id,
                        'user' => [
                            'id' => $like->user->id,
                            'name' => $like->user->name,
                            'email' => $like->user->email,
                            'profile_photo_url' => $like->user->profile_photo ? asset('storage/' . $like->user->profile_photo) : null,
                        ],
                        'created_at' => $like->created_at,
                    ];
                }),
            ],
        ]);
    }
}
