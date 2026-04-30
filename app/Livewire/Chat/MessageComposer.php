<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class MessageComposer extends Component
{
    #[Locked]
    public int $conversationId;

    public string $body = '';

    public function mount(int $conversationId): void
    {
        $this->conversationId = $conversationId;

        $this->conversation();
    }

    public function sendMessage(): void
    {
        $this->body = trim($this->body);

        $validated = $this->validate([
            'body' => ['required', 'string', 'max:4000'],
        ], [
            'body.required' => __('Write a message before sending.'),
        ]);

        $conversation = $this->conversation();

        Gate::authorize('sendMessage', $conversation);

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => Auth::id(),
            'type' => Message::TypeText,
            'body' => $validated['body'],
        ]);

        $conversation->touch();

        ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', Auth::id())
            ->update(['last_read_at' => now()]);

        $this->reset('body');
        $this->resetValidation();

        $this->dispatch('message-created', conversationId: $conversation->id, messageId: $message->id);
    }

    private function conversation(): Conversation
    {
        $conversation = Conversation::query()
            ->forUser(Auth::user())
            ->findOrFail($this->conversationId);

        Gate::authorize('sendMessage', $conversation);

        return $conversation;
    }

    public function render(): View
    {
        return view('livewire.chat.message-composer');
    }
}
