<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ConversationHeader extends Component
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
                    ->select(['id', 'conversation_id', 'user_id', 'role'])
                    ->with('user:id,name,email'),
            ])
            ->findOrFail($this->conversationId);

        Gate::authorize('view', $conversation);

        return $conversation;
    }

    public function title(): string
    {
        if ($this->conversation->isGroup()) {
            return $this->conversation->name ?? __('Untitled group');
        }

        return $this->otherParticipant()?->name ?? __('Direct conversation');
    }

    public function subtitle(): string
    {
        if ($this->conversation->isGroup()) {
            return trans_choice(':count member|:count members', $this->conversation->participants->count(), [
                'count' => $this->conversation->participants->count(),
            ]);
        }

        return $this->isOnline() ? __('Online') : __('Recently active');
    }

    public function initials(): string
    {
        if (! $this->conversation->isGroup()) {
            return $this->otherParticipant()?->initials() ?? 'DC';
        }

        return collect(explode(' ', $this->title()))
            ->filter()
            ->take(2)
            ->map(fn (string $word) => mb_substr($word, 0, 1))
            ->implode('');
    }

    public function isOnline(): bool
    {
        $participant = $this->otherParticipant();

        return $participant instanceof User && $participant->id % 3 !== 0;
    }

    public function closeConversation(): void
    {
        $this->dispatch('conversation-closed');
    }

    public function toggleDetails(): void
    {
        $this->dispatch('conversation-details-toggled');
    }

    private function otherParticipant(): ?User
    {
        return $this->conversation->participants
            ->first(fn (ConversationParticipant $participant) => $participant->user_id !== Auth::id())
            ?->user;
    }

    public function render(): View
    {
        return view('livewire.chat.conversation-header');
    }
}
