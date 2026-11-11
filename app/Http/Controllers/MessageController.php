<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Models\Booking;
use App\Support\MessageMasker;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user();

        return view('messages.index', [
            'conversations' => $this->conversations($me->user_id),
            // the people this role can start a chat with
            'contacts' => User::whereIn('account_status', ['Active', 'Hold'])->where('user_id', '!=', $me->user_id)
                ->whereIn('role', $this->canMessage($me->role))->orderBy('first_name')->get(),
            'open' => $request->query('with'),
        ]);
    }

    private function canMessage(string $role): array
    {
        return ['Guest' => ['Host'], 'Host' => ['Guest', 'Admin'], 'Admin' => ['Host', 'Guest']][$role];
    }

    // Conversation list (AJAX refresh and search)
    public function list(Request $request)
    {
        return view('messages.list', ['conversations' => $this->conversations($request->user()->user_id, $request->q)]);
    }

    // One chat thread (AJAX)
    public function thread(Request $request, User $user)
    {
        $me = $request->user()->user_id;

        Message::where('sender_id', $user->user_id)->where('receiver_id', $me)->whereNull('read_at')->update(['read_at' => now()]);

        $messages = Message::where(fn ($q) => $q->where('sender_id', $me)->where('receiver_id', $user->user_id))
            ->orWhere(fn ($q) => $q->where('sender_id', $user->user_id)->where('receiver_id', $me))
            ->orderBy('message_id')->get();

        // contacts stay hidden until a booking between the two is Confirmed (the original text stays stored)
        $unlocked = $this->unlocked($request->user(), $user);
        $messages->each(fn ($m) => $m->display = $unlocked ? $m->body : MessageMasker::mask($m->body));

        return view('messages.thread', compact('user', 'messages'));
    }

    public function send(Request $request)
    {
        $data = $request->validate(['receiver_id' => 'required|exists:users,user_id', 'body' => 'required|max:2000']);

        Message::create(['sender_id' => $request->user()->user_id] + $data);

        return ['ok' => true];
    }

    // One row per person the guest has chatted with, newest first
    // One row per person the user has chatted with, newest first
    private function conversations(int $me, ?string $search = null)
    {
        return Message::where('sender_id', $me)->orWhere('receiver_id', $me)->latest('message_id')->get()
            ->groupBy(fn ($m) => $m->sender_id == $me ? $m->receiver_id : $m->sender_id)
            ->map(function ($messages, $otherId) use ($me) {
                $other = User::find($otherId);
                $last = $messages->first();

                return (object) [
                    'user' => $other,
                    'last' => $last,
                    'text' => $this->unlocked(auth()->user(), $other) ? $last->body : MessageMasker::mask($last->body),
                    'unread' => $messages->where('receiver_id', $me)->whereNull('read_at')->count(),
                ];
            })
            ->when($search, fn ($list) => $list->filter(fn ($c) => stripos($c->user->full_name, $search) !== false))
            ->values();
    }

    // True when the chat may show phone numbers, emails and links:
    // a Confirmed booking exists between this guest and host (chats with the admin are never masked)
    private function unlocked(User $a, User $b): bool
    {
        if ($a->role === 'Admin' || $b->role === 'Admin') {
            return true;
        }

        return Booking::where('booking_status', 'Confirmed')->where(fn ($q) => $q
            ->where(fn ($w) => $w->where('guest_id', $a->user_id)->whereHas('unit', fn ($u) => $u->where('host_id', $b->user_id)))
            ->orWhere(fn ($w) => $w->where('guest_id', $b->user_id)->whereHas('unit', fn ($u) => $u->where('host_id', $a->user_id))))
            ->exists();
    }
}