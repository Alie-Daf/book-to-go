<?php

namespace App\Http\Controllers;
use App\Models\UserDocument;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HostProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('host.profile', ['host' => $request->user()->load('verification', 'documents')]);
    }

    // Phone and payment instructions (GCash number or account)
    public function update(Request $request)
    {
        $data = $request->validate([
            'phone_number' => 'nullable|max:30',
            'payment_instructions' => 'required|max:500',
        ]);

        $request->user()->update($data);

        return ['message' => 'Profile saved.'];
    }

    // Upload a Valid ID or Business Permit. This sends the host to the admin for verification.
    // Upload (or replace) a verification document. This sends the host to the admin for review.
    public function uploadDocument(Request $request)
    {
        $data = $request->validate([
            'document_type' => ['required', Rule::in(array_keys(UserDocument::HOST_DOCUMENTS))],
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        $request->user()->submitDocument($data['document_type'], $request->file('file'));

        return ['message' => 'Document uploaded. The admin will review it.'];
    }
    
}