@extends('layouts.app')
@section('title', 'Messages')
@section('nav', 'messages')

@section('content')
    <h1 class="page-title">Messages</h1>
    <p class="page-sub">Direct host-guest communication</p>

    <div class="chat-layout card-box p-0" data-list-url="{{ route('messages.list') }}"
         data-thread-url="{{ url('/messages') }}" data-open="{{ $open }}">

        <section class="chat-list">
            <input type="search" id="conversationSearch" class="form-control rounded-pill mb-3" placeholder="Search...">
            <div id="conversationList">@include('messages.list')</div>
        </section>

        <section class="chat-panel">
            <div id="thread" class="chat-thread">
                <div class="chat-empty">Select a conversation, or press + to start a new one.</div>
            </div>

            <form id="messageForm" class="chat-compose" action="{{ route('messages.send') }}">
                <button type="button" class="icon-btn" data-bs-toggle="modal" data-bs-target="#newChat" title="New conversation"><i class="bi bi-chat-dots"></i></button>
                <div class="compose-input">
                    <input type="text" id="messageBody" class="form-control" placeholder="Message" maxlength="2000" autocomplete="off" disabled>
                 <button type="submit" id="sendBtn" class="icon-btn send-btn" title="Send" disabled><i class="bi bi-send-fill"></i></button>
                </div>
            </form>
        </section>
    </div>

    <div class="modal fade" id="newChat" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header"><h2 class="modal-title fs-5">New conversation</h2><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    @foreach ($contacts as $contact)
                        <button type="button" class="conversation start-chat" data-id="{{ $contact->user_id }}" data-bs-dismiss="modal">
                            <span class="avatar"><i class="bi bi-person-fill"></i></span>
                            <span class="grow text-start"><strong>{{ $contact->full_name }}</strong><small class="d-block text-muted">{{ $contact->role }}</small></span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection