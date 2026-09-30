<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminMessageController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:120']]);
        $conversations = Conversation::query()
            ->with(['booking.traveler:id,name,email', 'participants.user:id,name,email'])
            ->withCount('messages')
            ->withMax('messages', 'created_at')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhereHas('messages', fn (Builder $message): Builder => $message->where('body', 'like', '%'.$search.'%'))
                    ->orWhereHas('participants.user', fn (Builder $user): Builder => $user->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
            }))
            ->orderByDesc('messages_max_created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.messages.index', compact('conversations', 'filters'));
    }

    public function show(Conversation $conversation): View
    {
        $conversation->load([
            'booking.traveler:id,name,email',
            'participants.user:id,name,email',
            'messages.sender:id,name,email',
        ]);
        $conversation->setRelation('messages', $conversation->messages->sortBy('created_at')->values());

        return view('admin.messages.show', compact('conversation'));
    }
}
