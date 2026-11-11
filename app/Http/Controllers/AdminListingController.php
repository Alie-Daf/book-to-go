<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\AuditLog;
use App\Notifications\AppNotice;
use Illuminate\Http\Request;

class AdminListingController extends Controller
{
    public function index()
    {
        $units = Unit::with(['host.verification', 'images', 'rates'])
            ->orderByRaw("listing_status = 'Pending' desc")->latest('unit_id')->get();

        return view('admin.listings', compact('units'));
    }

    // Approve puts the unit live. Reject takes it offline.
    // Approve puts the unit live. Reject takes it offline.
    public function decide(Request $request, Unit $unit, string $decision)
    {
        abort_unless(in_array($decision, ['approve', 'reject']), 404);

        if ($decision === 'approve') {
            abort_unless($unit->host->is_verified, 422, 'Verify the host first (Verifications page).');
            abort_unless($unit->title_document, 422, 'This unit has no title document.');
            abort_if($unit->category === 'Condo' && ! $unit->permission_document, 422, 'A condo needs the condo/HOA permission to rent it out.');

            $unit->update(['listing_status' => 'Approved', 'approved_by' => $request->user()->user_id, 'approved_at' => now()]);
            $message = "Your listing \"{$unit->title}\" is approved and now live.";
        } else {
            $unit->update(['listing_status' => 'Inactive', 'approved_by' => null, 'approved_at' => null]);
            $message = "Your listing \"{$unit->title}\" was not approved. Please check your documents.";
        }

        $unit->host->notify(new AppNotice($message, route('host.units.index', [], false)));
        AuditLog::record('Listing '.$unit->listing_status, $unit);

        return ['message' => 'Listing is now '.$unit->listing_status.'.'];
    }
}