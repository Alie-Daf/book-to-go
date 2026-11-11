<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('notifications', ['notices' => $request->user()->notifications()->take(50)->get()]);
    }

    // open a notice: mark it as read, then go to its page
    public function open(Request $request, string $id)
    {
        $notice = $request->user()->notifications()->findOrFail($id);
        $notice->markAsRead();

        return redirect($notice->data['url'] ?? route('notifications'));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return ['message' => 'All notifications marked as read.'];
    }
}