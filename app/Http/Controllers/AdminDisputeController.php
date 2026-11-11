<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\AuditLog;
use App\Notifications\AppNotice;
use Illuminate\Http\Request;

class AdminDisputeController extends Controller
{
    public function index()
    {
        $reports = Report::with(['reporter', 'unit', 'review.guest'])
            ->orderByRaw("status = 'Open' desc")->latest('report_id')->get();

        return view('admin.disputes', compact('reports'));
    }

    public function resolve(Request $request, Report $report)
    {
        $data = $request->validate(['notes' => 'required|max:1000']);

        $report->update([
            'status' => 'Resolved',
            'resolution_notes' => $data['notes'],
            'resolved_by' => $request->user()->user_id,
            'resolved_at' => now(),
        ]);

        $report->reporter->notify(new AppNotice('Your report was resolved: '.$data['notes']));
        AuditLog::record('Report resolved', $report, $data['notes']);

        return ['message' => 'Report resolved.'];
    }
}