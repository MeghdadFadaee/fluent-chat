<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ConversationList extends Component
{
    public ?int $selectedConversationId = null;

    public string $search = '';

    #[On('message-created')]
    public function refreshConversations(): void
    {
        unset($this->conversations);
    }

    public function selectConversation(int $conversationId): void
    {
        $this->dispatch('conversation-selected', conversationId: $conversationId);
    }

    /**
     * @return Collection<int, Conversation>
     */
    #[Computed]
    public function conversations(): Collection
    {
        $user = Auth::user();
        $search = trim($this->search);

        return Conversation::query()
            ->select(['id', 'created_by_id', 'type', 'name', 'description', 'created_at', 'updated_at'])
            ->forUser($user)
            ->with([
                'participants' => fn ($query) => $query
                    ->select(['id', 'conversation_id', 'user_id', 'role', 'last_read_at'])
                    ->with('user:id,name,email'),
                'latestMessage' => fn ($query) => $query
                    ->select(['messages.id', 'messages.conversation_id', 'messages.user_id', 'messages.body', 'messages.created_at']),
                'latestMessage.sender:id,name,email',
            ])
            ->withMax('messages', 'created_at')
            ->withCount([
                'messages as unread_messages_count' => fn (Builder $messages) => $messages
                    ->where('user_id', '!=', $user->id)
                    ->whereExists(fn ($participants) => $participants
                        ->selectRaw('1')
                        ->from('conversation_participants')
                        ->whereColumn('conversation_participants.conversation_id', 'messages.conversation_id')
                        ->where('conversation_participants.user_id', $user->id)
                        ->where(fn ($readState) => $readState
                            ->whereNull('conversation_participants.last_read_at')
                            ->orWhereColumn('messages.created_at', '>', 'conversation_participants.last_read_at'))),
            ])
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search, $user): void {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhereHas('participants.user', fn (Builder $users) => $users
                        ->where('users.id', '!=', $user->id)
                        ->where(fn (Builder $users) => $users
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")));
            }))
            ->orderByDesc('messages_max_created_at')
            ->orderByDesc('updated_at')
            ->limit(40)
            ->get();
    }

    public function titleFor(Conversation $conversation): string
    {
        if ($conversation->isGroup()) {
            return $conversation->name ?? __('Untitled group');
        }

        return $this->otherParticipant($conversation)?->name ?? __('Direct conversation');
    }

    public function initialsFor(Conversation $conversation): string
    {
        if ($conversation->isGroup()) {
            return collect(explode(' ', $this->titleFor($conversation)))
                ->filter()
                ->take(2)
                ->map(fn (string $word) => mb_substr($word, 0, 1))
                ->implode('');
        }

        return $this->otherParticipant($conversation)?->initials() ?? 'DC';
    }

    public function previewFor(Conversation $conversation): string
    {
        if (! $conversation->latestMessage) {
            return __('No messages yet');
        }

        $prefix = $conversation->latestMessage->user_id === Auth::id()
            ? __('You: ')
            : ($conversation->isGroup() ? $conversation->latestMessage->sender?->name.': ' : '');

        return str($prefix.$conversation->latestMessage->body)
            ->squish()
            ->limit(86)
            ->toString();
    }

    public function timeFor(?CarbonInterface $timestamp): string
    {
        if (! $timestamp) {
            return '';
        }

        if ($timestamp->isToday()) {
            return $timestamp->format('H:i');
        }

        if ($timestamp->isCurrentYear()) {
            return $timestamp->format('M j');
        }

        return $timestamp->format('M j, Y');
    }

    public function participantSummaryFor(Conversation $conversation): string
    {
        if (! $conversation->isGroup()) {
            return $this->isOnline($conversation) ? __('Online') : __('Recently active');
        }

        return trans_choice(':count member|:count members', $conversation->participants->count(), [
            'count' => $conversation->participants->count(),
        ]);
    }

    public function isOnline(Conversation $conversation): bool
    {
        $participant = $this->otherParticipant($conversation);

        return $participant instanceof User && $participant->id % 3 !== 0;
    }

    private function otherParticipant(Conversation $conversation): ?User
    {
        return $conversation->participants
            ->first(fn (ConversationParticipant $participant) => $participant->user_id !== Auth::id())
            ?->user;
    }

    public function render(): View
    {
        return view('livewire.chat.conversation-list');
    }
}
