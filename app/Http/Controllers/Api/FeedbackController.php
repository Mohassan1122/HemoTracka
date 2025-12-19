<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Display a listing of feedback.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Feedback::with(['user', 'target']);

        if ($request->has('target_type')) {
            $query->where('target_type', $request->target_type);
        }

        if ($request->has('target_id')) {
            $query->where('target_id', $request->target_id);
        }

        $feedback = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($feedback);
    }

    /**
     * Store a newly created feedback.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_type' => ['required', 'in:App\\Models\\Organization,App\\Models\\User'],
            'target_id' => ['required', 'integer'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
        ]);

        $feedback = Feedback::create([
            'user_id' => $request->user()->id,
            'target_type' => $validated['target_type'],
            'target_id' => $validated['target_id'],
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return response()->json([
            'message' => 'Feedback submitted successfully',
            'feedback' => $feedback->load(['user', 'target']),
        ], 201);
    }

    /**
     * Display the specified feedback.
     */
    public function show(Feedback $feedback): JsonResponse
    {
        return response()->json([
            'feedback' => $feedback->load(['user', 'target']),
        ]);
    }

    /**
     * Update the specified feedback.
     */
    public function update(Request $request, Feedback $feedback): JsonResponse
    {
        if ($feedback->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
        ]);

        $feedback->update($validated);

        return response()->json([
            'message' => 'Feedback updated successfully',
            'feedback' => $feedback->fresh(),
        ]);
    }

    /**
     * Remove the specified feedback.
     */
    public function destroy(Request $request, Feedback $feedback): JsonResponse
    {
        if ($feedback->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $feedback->delete();

        return response()->json([
            'message' => 'Feedback deleted successfully',
        ]);
    }

    /**
     * Get average rating for a target.
     */
    public function averageRating(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_type' => ['required', 'string'],
            'target_id' => ['required', 'integer'],
        ]);

        $average = Feedback::where('target_type', $validated['target_type'])
            ->where('target_id', $validated['target_id'])
            ->avg('rating');

        $count = Feedback::where('target_type', $validated['target_type'])
            ->where('target_id', $validated['target_id'])
            ->count();

        return response()->json([
            'average_rating' => round($average, 2) ?? 0,
            'total_reviews' => $count,
        ]);
    }
}
