<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Property;
use App\Notifications\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Communication hub. A conversation is about one property, between the client and the owner,
 * and the staff of that property's branch can read and reply to all of it (mediated supervision).
 */
class MessageController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('messages.index', [
            'threads' => MessageThread::when(
                $user->isStaff(),
                fn ($q) => $q->where('branch_id', $this->branchId()),
                fn ($q) => $q->whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
            )->with('property', 'createdBy')->withCount('messages')->latest('updated_at')->paginate(20),
        ]);
    }

    /** A client starts a conversation about a listed property; an owner about one of their own. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer'],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $property = Property::where('verification_status', 'approved')
            ->when($user->hasRole('owner'), fn ($q) => $q->where('owner_user_id', $user->id))
            ->findOrFail($data['property_id']);

        $thread = DB::transaction(function () use ($data, $user, $property) {
            $thread = new MessageThread(['subject' => $data['subject']]);
            $thread->branch_id = $property->branch_id;
            $thread->property_id = $property->id;
            $thread->created_by_user_id = $user->id;
            $thread->save();
            $thread->participants()->sync(array_unique([$user->id, $property->owner_user_id]));
            $this->post($thread, $user->id, $data['body']);

            return $thread;
        });

        return redirect()->route('messages.show', $thread)->with('status', 'Message sent.');
    }

    public function show(Request $request, MessageThread $thread)
    {
        $this->allowed($request, $thread);

        $thread->participants()->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);

        return view('messages.show', ['thread' => $thread->load('property', 'messages.sender.role')]);
    }

    public function reply(Request $request, MessageThread $thread)
    {
        $this->allowed($request, $thread);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $this->post($thread, $request->user()->id, $data['body']);

        return back();
    }

    private function post(MessageThread $thread, int $senderId, string $body): void
    {
        $message = new Message(['body' => $body]);
        $message->thread_id = $thread->id;
        $message->sender_user_id = $senderId;
        $message->save();
        $thread->touch();

        Notice::to(
            $thread->participants()->where('users.id', '!=', $senderId)->get(),
            "New message: {$thread->subject}",
            str($body)->limit(120),
            route('messages.show', $thread)
        );
    }

    private function allowed(Request $request, MessageThread $thread): void
    {
        $user = $request->user();

        abort_unless(
            $user->isStaff() ? $user->branchId() === $thread->branch_id : $thread->participants()->where('users.id', $user->id)->exists(),
            404
        );
    }
}
