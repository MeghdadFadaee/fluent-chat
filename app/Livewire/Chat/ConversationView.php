<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ConversationView extends Component
{
    public int $conversationId;

    public int $messageLimit = 40;

    public bool $messageSearchOpen = false;

    public string $messageSearch = '';

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

    #[On('conversation-search-toggled')]
    public function toggleMessageSearch(int $conversationId): void
    {
        if ($conversationId !== $this->conversationId) {
            return;
        }

        $this->messageSearchOpen = ! $this->messageSearchOpen;

        if (! $this->messageSearchOpen) {
            $this->clearMessageSearch();
        }
    }

    public function updatedMessageSearch(): void
    {
        $this->messageLimit = 40;

        unset($this->messages, $this->hasMoreMessages, $this->searchResultsCount);
    }

    public function clearMessageSearch(): void
    {
        $this->messageSearch = '';
        $this->messageLimit = 40;

        unset($this->messages, $this->hasMoreMessages, $this->searchResultsCount);
    }

    public function closeMessageSearch(): void
    {
        $this->messageSearchOpen = false;

        $this->clearMessageSearch();
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

    #[On('conversation-updated')]
    public function refreshConversation(int $conversationId): void
    {
        if ($conversationId === $this->conversationId) {
            unset($this->conversation);
        }
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
        return $this->messageQuery()
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
        return $this->messageQuery()
            ->count() > $this->messageLimit;
    }

    #[Computed]
    public function searchResultsCount(): int
    {
        if (! $this->messageSearchIsActive()) {
            return 0;
        }

        return $this->messageQuery()->count();
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

    public function messageSearchIsActive(): bool
    {
        return $this->messageSearchOpen && trim($this->messageSearch) !== '';
    }

    public function highlightedText(string $text): HtmlString
    {
        $escapedText = e($text);
        $search = trim($this->messageSearch);

        if (! $this->messageSearchIsActive() || $search === '') {
            return new HtmlString($escapedText);
        }

        $escapedSearch = e($search);
        $highlighted = preg_replace(
            '/('.preg_quote($escapedSearch, '/').')/iu',
            '<mark class="rounded bg-amber-200 px-0.5 text-zinc-950">$1</mark>',
            $escapedText,
        );

        return new HtmlString($highlighted ?? $escapedText);
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

    /**
     * @return Builder<Message>
     */
    private function messageQuery(): Builder
    {
        $search = trim($this->messageSearch);

        return Message::query()
            ->where('conversation_id', $this->conversationId)
            ->when($this->messageSearchIsActive(), fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('body', 'like', "%{$search}%")
                    ->orWhereHas('sender', fn (Builder $sender) => $sender
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            }));
    }

    public function render(): View
    {
        return view('livewire.chat.conversation-view');
    }
}
