<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    /**
     * Tambah comment pada post
     */
    public function store(Request $request, $postId)
    {
        $post = Post::find($postId);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $comment = Comment::create([
                'post_id' => $postId,
                'user_id' => $request->user()->id,
                'comment' => $request->input('comment'),
            ]);

            $comment->load('user:id,name,email,profile_photo');

            return response()->json([
                'success' => true,
                'message' => 'Comment berhasil ditambahkan',
                'data' => [
                    'id' => $comment->id,
                    'post_id' => $comment->post_id,
                    'comment' => $comment->comment,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                        'email' => $comment->user->email,
                        'profile_photo_url' => $comment->user->profile_photo ? asset('storage/' . $comment->user->profile_photo) : null,
                    ],
                    'created_at' => $comment->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan comment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get semua comments untuk post tertentu
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

        $comments = Comment::with('user:id,name,email,profile_photo')
            ->where('post_id', $postId)
            ->latest()
            ->paginate(10);

        $comments->getCollection()->transform(function ($comment) {
            return [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'email' => $comment->user->email,
                    'profile_photo_url' => $comment->user->profile_photo ? asset('storage/' . $comment->user->profile_photo) : null,
                ],
                'created_at' => $comment->created_at,
                'updated_at' => $comment->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'post_id' => $postId,
                'comments_count' => $post->comments()->count(),
                'comments' => $comments,
            ],
        ]);
    }

    /**
     * Update comment
     */
    public function update(Request $request, $postId, $commentId)
    {
        $comment = Comment::where('post_id', $postId)
            ->where('id', $commentId)
            ->first();

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment tidak ditemukan',
            ], 404);
        }

        // Check authorization menggunakan policy
        $this->authorize('update', $comment);

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $comment->update([
                'comment' => $request->input('comment'),
            ]);

            $comment->load('user:id,name,email,profile_photo');

            return response()->json([
                'success' => true,
                'message' => 'Comment berhasil diupdate',
                'data' => [
                    'id' => $comment->id,
                    'post_id' => $comment->post_id,
                    'comment' => $comment->comment,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                        'email' => $comment->user->email,
                        'profile_photo_url' => $comment->user->profile_photo ? asset('storage/' . $comment->user->profile_photo) : null,
                    ],
                    'updated_at' => $comment->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate comment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete comment
     */
    public function destroy(Request $request, $postId, $commentId)
    {
        $comment = Comment::where('post_id', $postId)
            ->where('id', $commentId)
            ->first();

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment tidak ditemukan',
            ], 404);
        }

        // Check authorization menggunakan policy
        $this->authorize('delete', $comment);

        try {
            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comment berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus comment: ' . $e->getMessage(),
            ], 500);
        }
    }
}
