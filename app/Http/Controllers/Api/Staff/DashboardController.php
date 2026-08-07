<?php

namespace App\Http\Controllers\Api\Staff;

use App\Enums\RecitationStatus;
use App\Http\Controllers\Controller;
use App\Models\Recitation;
use App\Models\Reciter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $recitationQuery = Recitation::query();
        $reciterQuery = Reciter::query();

        if (! $user->isReviewer()) {
            $recitationQuery->where('created_by', $user->id);
            $reciterQuery->where('created_by', $user->id);
        }

        $counts = [
            'reciters' => (clone $reciterQuery)->count(),
            'drafts' => (clone $recitationQuery)->where('status', RecitationStatus::Draft)->count(),
            'rejected' => (clone $recitationQuery)->where('status', RecitationStatus::Rejected)->count(),
            'pending_review' => (clone $recitationQuery)->where('status', RecitationStatus::PendingReview)->count(),
            'approved' => (clone $recitationQuery)->where('status', RecitationStatus::Approved)->count(),
        ];

        if ($user->isReviewer()) {
            $counts['queue_pending'] = Recitation::query()
                ->where('status', RecitationStatus::PendingReview)
                ->count();
        }

        return response()->json([
            'data' => [
                'roles' => $user->getRoleNames()->values()->all(),
                'counts' => $counts,
            ],
        ]);
    }
}
