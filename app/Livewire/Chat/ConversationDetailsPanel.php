<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ConversationDetailsPanel extends Component
{
    #[Locked]
    public int $conversationId;

    public function mount(int $conversationId): void
    {
        $this->conversationId = $conversationId;
    }

    #[Computed]
    public function conversation(): Conversation
    {
        $conversation = Conversation::query()
            ->forUser(Auth::user())
            ->with([
                'participants' => fn ($query) => $query
                    ->select(['id', 'conversation_id', 'user_id', 'role', 'joined_at'])
                    ->with('user:id,name,email'),
            ])
            ->withCount('messages')
            ->findOrFail($this->conversationId);

        Gate::authorize('view', $conversation);

        return $conversation;
    }

    public function title(): string
    {
        if ($this->conversation->isGroup()) {
            return $this->conversation->name ?? __('Untitled group');
        }

        return $this->conversation->participants
            ->first(fn (ConversationParticipant $participant) => $participant->user_id !== Auth::id())
            ?->user?->name ?? __('Direct conversation');
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->title()))
            ->filter()
            ->take(2)
            ->map(fn (string $word) => mb_substr($word, 0, 1))
            ->implode('');
    }

    public function render(): View
    {
        return view('livewire.chat.conversation-details-panel');
    }
}
