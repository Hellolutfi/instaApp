<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UploadController extends Controller
{
    /**
     * Upload gambar dan text
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'content' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Upload gambar
            $image = $request->file('image');
            $imagePath = $image->store('posts', 'public');

            // Simpan ke database
            $post = Post::create([
                'user_id' => $request->user()->id,
                'content' => $request->input('content'),
                'image_path' => $imagePath,
            ]);

            // Load user dengan profile_photo
            $post->load('user:id,name,email,profile_photo');

            return response()->json([
                'success' => true,
                'message' => 'Upload berhasil',
                'data' => [
                    'id' => $post->id,
                    'content' => $post->content,
                    'image_url' => asset('storage/' . $post->image_path),
                    'user' => [
                        'id' => $post->user->id,
                        'name' => $post->user->name,
                        'email' => $post->user->email,
                        'profile_photo_url' => $post->user->profile_photo ? asset('storage/' . $post->user->profile_photo) : null,
                    ],
                    'created_at' => $post->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get semua posts
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $posts = Post::with(['user:id,name,email,profile_photo', 'likes', 'comments'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate(10);

        $posts->getCollection()->transform(function ($post) use ($userId) {
            // Cek apakah user sudah like post ini
            $isLiked = $post->likes()->where('user_id', $userId)->exists();

            return [
                'id' => $post->id,
                'content' => $post->content,
                'image_url' => $post->image_path ? asset('storage/' . $post->image_path) : null,
                'user' => [
                    'id' => $post->user->id,
                    'name' => $post->user->name,
                    'email' => $post->user->email,
                    'profile_photo_url' => $post->user->profile_photo ? asset('storage/' . $post->user->profile_photo) : null,
                ],
                'likes_count' => $post->likes_count,
                'comments_count' => $post->comments_count,
                'is_liked' => $isLiked,
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $posts,
        ]);
    }

    /**
     * Get post by ID
     */
    public function show(Request $request, $id)
    {
        $userId = $request->user()->id;

        $post = Post::with(['user:id,name,email,profile_photo', 'likes', 'comments'])
            ->withCount(['likes', 'comments'])
            ->find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post tidak ditemukan',
            ], 404);
        }

        // Cek apakah user sudah like post ini
        $isLiked = $post->likes()->where('user_id', $userId)->exists();
        
        // Cek apakah user adalah pemilik post
        $isOwner = $post->user_id === $userId;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $post->id,
                'content' => $post->content,
                'image_url' => $post->image_path ? asset('storage/' . $post->image_path) : null,
                'user' => [
                    'id' => $post->user->id,
                    'name' => $post->user->name,
                    'email' => $post->user->email,
                    'profile_photo_url' => $post->user->profile_photo ? asset('storage/' . $post->user->profile_photo) : null,
                ],
                'likes_count' => $post->likes_count,
                'comments_count' => $post->comments_count,
                'is_liked' => $isLiked,
                'is_owner' => $isOwner,
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ],
        ]);
    }

    /**
     * Update post
     */
    public function update(Request $request, $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post tidak ditemukan',
            ], 404);
        }

        // Check authorization menggunakan policy
        $this->authorize('update', $post);

        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Update content saja (gambar tidak bisa diubah)
            if ($request->has('content')) {
                $post->content = $request->input('content');
            }
            
            $post->save();

            return response()->json([
                'success' => true,
                'message' => 'Post berhasil diupdate',
                'data' => [
                    'id' => $post->id,
                    'content' => $post->content,
                    'image_url' => $post->image_path ? asset('storage/' . $post->image_path) : null,
                    'updated_at' => $post->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate post: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete post
     */
    public function destroy(Request $request, $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post tidak ditemukan',
            ], 404);
        }

        // Check authorization menggunakan policy
        $this->authorize('delete', $post);

        try {
            // Hapus gambar dari storage
            if ($post->image_path) {
                Storage::disk('public')->delete($post->image_path);
            }

            // Hapus post (likes dan comments akan terhapus otomatis karena cascade)
            $post->delete();

            return response()->json([
                'success' => true,
                'message' => 'Post berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus post: ' . $e->getMessage(),
            ], 500);
        }
    }
}
