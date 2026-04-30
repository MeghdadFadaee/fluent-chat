<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ConversationView extends Component
{
    public int $conversationId;

    public int $messageLimit = 40;

    public function mount(int $conversationId): void
    {
        $this->conversationId = $conversationId;

        $this->authorizeConversation();
        $this->markAsRead();
    }

    public function loadEarlier(): void
    {
        $this->messageLimit += 25;
    }

    #[On('message-created')]
    public function refreshMessages(int $conversationId): void
    {
        if ($conversationId !== $this->conversationId) {
            return;
        }

        unset($this->messages, $this->hasMoreMessages);

        $this->markAsRead();
    }

    #[Computed]
    public function conversation(): Conversation
    {
        $conversation = Conversation::query()
            ->forUser(Auth::user())
            ->with([
                'participants' => fn ($query) => $query
                    ->select(['id', 'conversation_id', 'user_id', 'role', 'last_read_at'])
                    ->with('user:id,name,email'),
            ])
            ->findOrFail($this->conversationId);

        Gate::authorize('view', $conversation);

        return $conversation;
    }

    /**
     * @return Collection<int, Message>
     */
    #[Computed]
    public function messages(): Collection
    {
        return Message::query()
            ->where('conversation_id', $this->conversationId)
            ->with('sender:id,name,email')
            ->latest()
            ->limit($this->messageLimit)
            ->get()
            ->reverse()
            ->values();
    }

    #[Computed]
    public function hasMoreMessages(): bool
    {
        return Message::query()
            ->where('conversation_id', $this->conversationId)
            ->count() > $this->messageLimit;
    }

    public function dateLabel(CarbonInterface $timestamp): string
    {
        if ($timestamp->isToday()) {
            return __('Today');
        }

        if ($timestamp->isYesterday()) {
            return __('Yesterday');
        }

        return $timestamp->format('F j, Y');
    }

    private function authorizeConversation(): void
    {
        $conversation = Conversation::query()
            ->forUser(Auth::user())
            ->findOrFail($this->conversationId);

        Gate::authorize('view', $conversation);
    }

    private function markAsRead(): void
    {
        ConversationParticipant::query()
            ->where('conversation_id', $this->conversationId)
            ->where('user_id', Auth::id())
            ->update(['last_read_at' => now()]);
    }

    public function render(): View
    {
        return view('livewire.chat.conversation-view');
    }
}
