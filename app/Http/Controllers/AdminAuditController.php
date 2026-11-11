<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;

class AdminAuditController extends Controller
{
    public function index()
    {
        return view('admin.audit', ['logs' => AuditLog::with('user')->latest('log_id')->take(200)->get()]);
    }
}