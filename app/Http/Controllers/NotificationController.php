<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->limit(50)->get();

        // Loaded first so "New" still shows on this visit, then marked as read.
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return view('notifications', ['notifications' => $notifications]);
    }
}
