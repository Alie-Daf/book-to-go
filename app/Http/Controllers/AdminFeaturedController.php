<?php

namespace App\Http\Controllers;

use App\Models\FeaturedListing;
use App\Models\AuditLog;
use App\Notifications\AppNotice;
use Illuminate\Http\Request;

class AdminFeaturedController extends Controller
{
    public function index()
    {
        $listings = FeaturedListing::with(['unit.host', 'invoice'])->latest('featured_id')->get();

        return view('admin.featured', compact('listings'));
    }

    // Approve: the listing turns Active on its start date (the date check does this). Reject: it never shows.
    public function decide(Request $request, FeaturedListing $featured, string $decision)
    {
        abort_unless(in_array($decision, ['approve', 'reject']), 404);
        abort_unless($featured->featured_status === 'Pending' && $featured->invoice->status === 'Paid', 422, 'The invoice must be paid first.');

        if ($decision === 'approve') {
            $featured->update([
                'approved_by' => $request->user()->user_id,
                'featured_status' => $featured->start_date->lte(today()) ? 'Active' : 'Pending',
            ]);
        } else {
            $featured->update(['approved_by' => $request->user()->user_id, 'featured_status' => 'Rejected']);
        }

                $featured->unit->host->notify(new AppNotice(
            "Your featured listing for \"{$featured->unit->title}\" was ".($decision === 'approve' ? 'approved.' : 'rejected.'),
            route('host.featured', [], false)
        ));
        AuditLog::record('Featured '.$decision, $featured);
        
        return ['message' => 'Featured request '.($decision === 'approve' ? 'approved.' : 'rejected.')];
    }
}