<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return view('profile', [
            'user' => $user,
            'bookingCount' => $user->bookings()->count(),
            'reviewCount' => $user->reviews()->count(),
            'idDocument' => $user->documents()->where('document_type', 'Valid ID')->latest('document_id')->first(),
        ]);
    }

    // Valid ID upload (also acts as age verification)
    public function uploadId(Request $request)
    {
        $request->validate(['valid_id' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096']);

        $request->user()->documents()->create([
            'document_type' => 'Valid ID',
            'file_reference' => $request->file('valid_id')->store('valid-ids'),   // private storage
        ]);

        return ['message' => 'Valid ID uploaded. The admin will review it.'];
    }
}