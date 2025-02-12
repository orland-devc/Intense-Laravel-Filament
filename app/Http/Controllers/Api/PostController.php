<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_KEY_PREFIX = 'post_';
    private const POSTS_PER_PAGE = 10;

    public function index(): JsonResponse
    {
        try {
            $cacheKey = 'posts_page_' . request()->get('page', 1);
            
            $posts = Cache::remember($cacheKey, self::CACHE_TTL, function () {
                return Post::latest()->paginate(self::POSTS_PER_PAGE);
            });

            return response()->json([
                'status' => 'success',
                'data' => PostResource::collection($posts),
                'meta' => [
                    'total' => $posts->total(),
                    'per_page' => $posts->perPage(),
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching posts: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch posts'
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255|unique:posts',
                'content' => 'required|string|min:10',
                'status' => 'required|in:draft,published',
                'featured_image' => 'nullable|image|max:2048' // 2MB max
            ]);

            if ($request->hasFile('featured_image')) {
                $path = $request->file('featured_image')->store('posts/images', 'public');
                $validated['featured_image'] = $path;
            }

            $post = Post::create($validated);
            
            // Clear relevant caches
            $this->clearPostCaches();

            return response()->json([
                'status' => 'success',
                'message' => 'Post created successfully',
                'data' => new PostResource($post)
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating post: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create post'
            ], 500);
        }
    }

    public function show(Post $post): JsonResponse
    {
        try {
            $cacheKey = self::CACHE_KEY_PREFIX . $post->id;
            
            $cachedPost = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($post) {
                return new PostResource($post);
            });

            return response()->json([
                'status' => 'success',
                'data' => $cachedPost
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching post: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch post'
            ], 500);
        }
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255|unique:posts,title,' . $post->id,
                'content' => 'sometimes|string|min:10',
                'status' => 'sometimes|in:draft,published',
                'featured_image' => 'nullable|image|max:2048'
            ]);

            if ($request->hasFile('featured_image')) {
                // Delete old image if exists
                if ($post->featured_image) {
                    Storage::disk('public')->delete($post->featured_image);
                }
                
                $path = $request->file('featured_image')->store('posts/images', 'public');
                $validated['featured_image'] = $path;
            }

            $post->update($validated);
            
            // Clear relevant caches
            $this->clearPostCaches($post->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Post updated successfully',
                'data' => new PostResource($post)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating post: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update post'
            ], 500);
        }
    }

    public function destroy(Post $post): JsonResponse
    {
        try {
            // Delete featured image if exists
            if ($post->featured_image) {
                Storage::disk('public')->delete($post->featured_image);
            }

            $post->delete();
            
            // Clear relevant caches
            $this->clearPostCaches($post->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Post deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting post: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete post'
            ], 500);
        }
    }

    private function clearPostCaches(?int $postId = null): void
    {
        // Clear pagination cache
        for ($i = 1; $i <= 10; $i++) { // Clear first 10 pages
            Cache::forget('posts_page_' . $i);
        }
        
        // Clear specific post cache if ID provided
        if ($postId) {
            Cache::forget(self::CACHE_KEY_PREFIX . $postId);
        }
    }
}