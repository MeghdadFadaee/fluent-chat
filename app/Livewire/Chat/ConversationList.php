<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Carbon\CarbonInterface;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ConversationList extends Component
{
    public ?int $selectedConversationId = null;

    public string $search = '';

    public bool $showCreateConversationModal = false;

    public string $createType = Conversation::TypeDirect;

    public string $memberSearch = '';

    /**
     * @var array<int, int>
     */
    public array $selectedMemberIds = [];

    public string $groupName = '';

    public string $groupDescription = '';

    #[On('message-created')]
    #[On('conversation-created')]
    #[On('conversation-updated')]
    public function refreshConversations(): void
    {
        unset($this->conversations);
    }

    public function selectConversation(int $conversationId): void
    {
        $this->dispatch('conversation-selected', conversationId: $conversationId);
    }

    public function openCreateConversation(): void
    {
        $this->resetCreateConversationForm();

        $this->showCreateConversationModal = true;
    }

    public function updatedCreateType(string $createType): void
    {
        if ($createType === Conversation::TypeDirect && count($this->selectedMemberIds) > 1) {
            $this->selectedMemberIds = [array_values($this->selectedMemberIds)[0]];
        }

        $this->resetValidation();

        unset($this->candidateUsers, $this->selectedMembers);
    }

    public function toggleMember(int $userId): void
    {
        if ($userId === Auth::id()) {
            return;
        }

        if (! User::query()->whereKey($userId)->exists()) {
            return;
        }

        if ($this->createType === Conversation::TypeDirect) {
            $this->selectedMemberIds = [$userId];
        } elseif (in_array($userId, $this->selectedMemberIds, true)) {
            $this->selectedMemberIds = array_values(array_diff($this->selectedMemberIds, [$userId]));
        } else {
            $this->selectedMemberIds[] = $userId;
        }

        $this->resetValidation('selectedMemberIds');

        unset($this->candidateUsers, $this->selectedMembers);
    }

    public function removeSelectedMember(int $userId): void
    {
        $this->selectedMemberIds = array_values(array_diff($this->selectedMemberIds, [$userId]));

        unset($this->candidateUsers, $this->selectedMembers);
    }

    public function createConversation(): void
    {
        Gate::authorize('create', Conversation::class);

        $validated = $this->validate([
            'createType' => ['required', Rule::in([Conversation::TypeDirect, Conversation::TypeGroup])],
            'selectedMemberIds' => ['required', 'array', 'min:1'],
            'selectedMemberIds.*' => ['integer', Rule::exists('users', 'id')],
            'groupName' => [
                Rule::requiredIf($this->createType === Conversation::TypeGroup),
                'nullable',
                'string',
                'max:80',
            ],
            'groupDescription' => ['nullable', 'string', 'max:180'],
        ]);

        $memberIds = User::query()
            ->whereKey($validated['selectedMemberIds'])
            ->whereKeyNot(Auth::id())
            ->pluck('id')
            ->map(fn (int $id) => $id)
            ->values();

        if ($memberIds->isEmpty()) {
            $this->addError('selectedMemberIds', __('Choose at least one person.'));

            return;
        }

        if ($this->createType === Conversation::TypeDirect && $memberIds->count() !== 1) {
            $this->addError('selectedMemberIds', __('Choose one person for a direct conversation.'));

            return;
        }

        $conversation = DB::transaction(function () use ($memberIds, $validated): Conversation {
            if ($this->createType === Conversation::TypeDirect) {
                $existingConversation = $this->findExistingDirectConversation($memberIds->first());

                if ($existingConversation) {
                    return $existingConversation;
                }
            }

            $conversation = Conversation::query()->create([
                'created_by_id' => Auth::id(),
                'type' => $this->createType,
                'name' => $this->createType === Conversation::TypeGroup ? trim((string) $validated['groupName']) : null,
                'description' => $this->createType === Conversation::TypeGroup ? trim((string) ($validated['groupDescription'] ?? '')) ?: null : null,
            ]);

            ConversationParticipant::query()->create([
                'conversation_id' => $conversation->id,
                'user_id' => Auth::id(),
                'role' => ConversationParticipant::RoleAdmin,
                'joined_at' => now(),
                'last_read_at' => now(),
            ]);

            $memberIds->each(fn (int $memberId) => ConversationParticipant::query()->create([
                'conversation_id' => $conversation->id,
                'user_id' => $memberId,
                'role' => ConversationParticipant::RoleMember,
                'joined_at' => now(),
            ]));

            return $conversation;
        });

        $this->resetCreateConversationForm();

        $this->showCreateConversationModal = false;

        unset($this->conversations);

        Flux::toast(variant: 'success', text: __('Conversation ready.'));

        $this->dispatch('conversation-created', conversationId: $conversation->id);
        $this->dispatch('conversation-selected', conversationId: $conversation->id);
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
                    ->select(['id', 'conversation_id', 'user_id', 'role', 'last_read_at', 'muted_until', 'pinned_at'])
                    ->with('user:id,name,email'),
                'latestMessage' => fn ($query) => $query
                    ->select(['messages.id', 'messages.conversation_id', 'messages.user_id', 'messages.type', 'messages.body', 'messages.metadata', 'messages.created_at']),
                'latestMessage.sender:id,name,email',
            ])
            ->addSelect([
                'current_participant_pinned_at' => ConversationParticipant::query()
                    ->select('pinned_at')
                    ->whereColumn('conversation_participants.conversation_id', 'conversations.id')
                    ->where('conversation_participants.user_id', $user->id)
                    ->limit(1),
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
            ->orderByDesc('current_participant_pinned_at')
            ->orderByDesc('messages_max_created_at')
            ->orderByDesc('updated_at')
            ->limit(40)
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function candidateUsers(): Collection
    {
        $search = trim($this->memberSearch);

        return User::query()
            ->select(['id', 'name', 'email'])
            ->whereKeyNot(Auth::id())
            ->whereNotIn('id', $this->selectedMemberIds)
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->limit(10)
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function selectedMembers(): Collection
    {
        return User::query()
            ->select(['id', 'name', 'email'])
            ->whereKey($this->selectedMemberIds)
            ->get()
            ->sortBy(fn (User $user) => array_search($user->id, $this->selectedMemberIds, true))
            ->values();
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

        $preview = $conversation->latestMessage->isFile()
            ? __('Sent a file: :name', ['name' => $conversation->latestMessage->attachmentName()])
            : $conversation->latestMessage->body;

        return str($prefix.$preview)
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

    public function isPinnedFor(Conversation $conversation): bool
    {
        return (bool) $this->currentParticipantFor($conversation)?->isPinned();
    }

    public function isMutedFor(Conversation $conversation): bool
    {
        return (bool) $this->currentParticipantFor($conversation)?->isMuted();
    }

    public function isOnline(Conversation $conversation): bool
    {
        $participant = $this->otherParticipant($conversation);

        return $participant instanceof User && $participant->id % 3 !== 0;
    }

    private function currentParticipantFor(Conversation $conversation): ?ConversationParticipant
    {
        return $conversation->participants
            ->first(fn (ConversationParticipant $participant) => $participant->user_id === Auth::id());
    }

    private function otherParticipant(Conversation $conversation): ?User
    {
        return $conversation->participants
            ->first(fn (ConversationParticipant $participant) => $participant->user_id !== Auth::id())
            ?->user;
    }

    private function findExistingDirectConversation(int $memberId): ?Conversation
    {
        return Conversation::query()
            ->forUser(Auth::user())
            ->where('type', Conversation::TypeDirect)
            ->whereHas('participants', fn (Builder $participants) => $participants->where('user_id', $memberId))
            ->withCount('participants')
            ->get()
            ->first(fn (Conversation $conversation) => (int) $conversation->participants_count === 2);
    }

    private function resetCreateConversationForm(): void
    {
        $this->reset(
            'createType',
            'memberSearch',
            'selectedMemberIds',
            'groupName',
            'groupDescription',
        );

        $this->resetValidation();

        unset($this->candidateUsers, $this->selectedMembers);
    }

    public function render(): View
    {
        return view('livewire.chat.conversation-list');
    }
}
