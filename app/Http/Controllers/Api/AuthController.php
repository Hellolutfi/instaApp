<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Register user baru
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registrasi gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Get current authenticated user dengan posts
     */
    public function me(Request $request)
    {
        $user = $request->user();

        // Get posts dengan pagination (default 10 per page)
        $perPage = $request->input('per_page', 10);
        $posts = $user->posts()
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate($perPage);

        // Transform posts data
        $posts->getCollection()->transform(function ($post) use ($user) {
            return [
                'id' => $post->id,
                'content' => $post->content,
                'image_url' => $post->image_path ? asset('storage/' . $post->image_path) : null,
                'likes_count' => $post->likes_count,
                'comments_count' => $post->comments_count,
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_photo_url' => $user->profile_photo ? asset('storage/' . $user->profile_photo) : null,
                    'created_at' => $user->created_at,
                ],
                'posts' => [
                    'data' => $posts->items(),
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                    'from' => $posts->firstItem(),
                    'to' => $posts->lastItem(),
                ],
            ],
        ]);
    }

    /**
     * Get profile user lain dengan posts
     */
    public function profile(Request $request, $userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        $currentUser = $request->user();

        // Get posts dengan pagination (default 10 per page)
        $perPage = $request->input('per_page', 10);
        $posts = $user->posts()
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate($perPage);

        // Transform posts data
        $posts->getCollection()->transform(function ($post) use ($currentUser) {
            // Cek apakah current user sudah like post ini
            $isLiked = $post->likes()->where('user_id', $currentUser->id)->exists();

            return [
                'id' => $post->id,
                'content' => $post->content,
                'image_url' => $post->image_path ? asset('storage/' . $post->image_path) : null,
                'likes_count' => $post->likes_count,
                'comments_count' => $post->comments_count,
                'is_liked' => $isLiked,
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_photo_url' => $user->profile_photo ? asset('storage/' . $user->profile_photo) : null,
                    'created_at' => $user->created_at,
                ],
                'is_own_profile' => $currentUser->id === $user->id,
                'posts' => [
                    'data' => $posts->items(),
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                    'from' => $posts->firstItem(),
                    'to' => $posts->lastItem(),
                ],
            ],
        ]);
    }

    /**
     * Update profile user
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8',
            'profile_photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Update name jika ada
            if ($request->has('name')) {
                $user->name = $request->input('name');
            }

            // Update email jika ada
            if ($request->has('email')) {
                $user->email = $request->input('email');
            }

            // Update password jika ada
            if ($request->has('password')) {
                $user->password = Hash::make($request->input('password'));
            }

            // Update profile photo jika ada
            if ($request->hasFile('profile_photo')) {
                // Hapus foto lama jika ada
                if ($user->profile_photo) {
                    Storage::disk('public')->delete($user->profile_photo);
                }

                // Upload foto baru
                $profilePhoto = $request->file('profile_photo');
                $profilePhotoPath = $profilePhoto->store('profiles', 'public');
                $user->profile_photo = $profilePhotoPath;
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile berhasil diupdate',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_photo_url' => $user->profile_photo ? asset('storage/' . $user->profile_photo) : null,
                    'updated_at' => $user->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate profile: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
        ]);
    }
}
