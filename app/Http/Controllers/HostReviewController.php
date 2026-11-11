<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Review;
use Illuminate\Http\Request;

class HostReviewController extends Controller
{
    public function index(Request $request)
    {
        $mine = fn () => Review::whereHas('unit', fn ($q) => $q->where('host_id', auth()->id()));

        $reviews = $mine()->with(['guest', 'unit'])
            ->when($request->q, fn ($q, $v) => $q->where(fn ($w) => $w->where('comment', 'like', "%$v%")
                ->orWhereHas('guest', fn ($g) => $g->search($v))))
            ->when($request->unit_id, fn ($q, $v) => $q->where('unit_id', $v))
            ->when($request->rating, fn ($q, $v) => $q->where('rating', $v))
            ->orderBy('created_at', $request->sort === 'oldest' ? 'asc' : 'desc')
            ->get();

        if ($request->ajax()) {
            return view('host.reviews-list', compact('reviews'));
        }

        return view('host.reviews', [
            'reviews' => $reviews,
            'count' => $mine()->count(),
            'average' => round((float) $mine()->avg('rating'), 1),
            'spread' => $mine()->selectRaw('rating, count(*) as total')->groupBy('rating')->pluck('total', 'rating'),
            'units' => $request->user()->units()->orderBy('title')->get(),
        ]);
    }

    public function reply(Request $request, Review $review)
    {
        $this->own($review);
        $data = $request->validate(['reply' => 'required|max:1000']);

        $review->update(['host_reply' => $data['reply']]);

        return ['message' => 'Reply posted.'];
    }

    // Report a review to the admin (goes to Disputes)
    public function report(Request $request, Review $review)
    {
        $this->own($review);
        $data = $request->validate(['reason' => 'required|max:1000']);

        Report::create([
            'reporter_id' => $request->user()->user_id,
            'review_id' => $review->review_id,
            'unit_id' => $review->unit_id,
            'reason' => $data['reason'],
        ]);

        return ['message' => 'Review reported to the admin.'];
    }

    private function own(Review $review): void
    {
        abort_unless($review->unit->host_id == auth()->id(), 403);
    }
}