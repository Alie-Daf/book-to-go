<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function index(Request $request)
    {
        $units = $request->user()->bookmarks()->with(['images', 'rates'])->withAvg('reviews', 'rating')->get();

        return view('bookmarks.index', ['units' => $units, 'bookmarked' => $units->pluck('unit_id')->all()]);
    }

    // Add or remove a bookmark (AJAX)
    public function toggle(Request $request, Unit $unit)
    {
        $result = $request->user()->bookmarks()->toggle($unit->unit_id);

        return ['bookmarked' => count($result['attached']) > 0];
    }
}