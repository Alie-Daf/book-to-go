<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\HostVerification;
use App\Models\UserDocument;
use App\Notifications\AppNotice;
use Illuminate\Http\Request;

class AdminVerificationController extends Controller
{
    public function index()
    {
        return view('admin.verifications', [
            'hosts' => HostVerification::with('host.documents')->orderByRaw("status = 'Pending' desc")->latest('submitted_at')->get(),
            'ids' => UserDocument::with('user')->whereHas('user', fn ($q) => $q->where('role', 'Guest'))
                ->latest('document_id')->take(30)->get(),
        ]);
    }

    // Approve gives the host the "Verified" badge
    // Approve gives the host the "Verified" badge, but only when every required document was uploaded
    public function decideHost(Request $request, HostVerification $verification, string $decision)
    {
        abort_unless(in_array($decision, ['approve', 'reject']), 404);
        $approved = $decision === 'approve';

        $request->validate(['notes' => $approved ? 'nullable|max:1000' : 'required|max:1000']);

        $uploaded = $verification->host->documents()->pluck('document_type')->all();
        abort_if($approved && array_diff(array_keys(UserDocument::HOST_DOCUMENTS), $uploaded), 422,
            'The host must upload all the required documents first.');

        $verification->update([
            'status' => $approved ? 'Verified' : 'Rejected',
            'reviewed_by' => $request->user()->user_id,
            'reviewed_at' => now(),
            'reviewer_notes' => $request->input('notes'),
        ]);

        UserDocument::where('user_id', $verification->host_id)->update([
            'verification_status' => $approved ? 'Approved' : 'Rejected',
            'reviewed_by' => $request->user()->user_id,
            'reviewed_at' => now(),
            'reviewer_notes' => $request->input('notes'),
        ]);

            $verification->host->notify(new AppNotice(
            $approved ? 'Your host account is verified. You can add units now.' : 'Your host verification was rejected: '.$request->input('notes'),
            route('host.profile', [], false)
        ));
        AuditLog::record('Host '.($approved ? 'verified' : 'rejected'), $verification, $request->input('notes'));
        return ['message' => 'Host '.($approved ? 'verified.' : 'rejected.')];
    }

    // Guest valid ID (age verification)
    public function decideDocument(Request $request, UserDocument $document, string $decision)
    {
        abort_unless(in_array($decision, ['approve', 'reject']), 404);

        $document->update([
            'verification_status' => $decision === 'approve' ? 'Approved' : 'Rejected',
            'reviewed_by' => $request->user()->user_id,
            'reviewed_at' => now(),
            'reviewer_notes' => $request->input('notes'),
        ]);

        $document->user->notify(new AppNotice('Your valid ID was '.strtolower($document->verification_status).'.', route('profile', [], false)));
        AuditLog::record('Guest ID '.strtolower($document->verification_status), $document);
        
        return ['message' => 'ID '.strtolower($document->verification_status).'.'];
    }
}