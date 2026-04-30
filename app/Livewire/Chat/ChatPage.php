<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::chat')]
#[Title('Chat')]
class ChatPage extends Component
{
    public ?int $selectedConversationId = null;

    public bool $detailsPanelOpen = true;

    #[On('conversation-selected')]
    public function selectConversation(int $conversationId): void
    {
        $conversation = Conversation::query()
            ->forUser(Auth::user())
            ->findOrFail($conversationId);

        Gate::authorize('view', $conversation);

        $this->selectedConversationId = $conversation->id;
        $this->detailsPanelOpen = true;
    }

    #[On('conversation-closed')]
    public function clearConversation(): void
    {
        $this->selectedConversationId = null;
        $this->detailsPanelOpen = false;
    }

    #[On('conversation-details-toggled')]
    public function toggleDetailsPanel(): void
    {
        $this->detailsPanelOpen = ! $this->detailsPanelOpen;
    }

    public function render(): View
    {
        return view('livewire.chat.chat-page');
    }
}
