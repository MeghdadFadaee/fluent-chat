<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class ConversationDetailsPanel extends Component
{
    #[Locked]
    public int $conversationId;

    public bool $showAddMembersModal = false;

    public bool $showFilesModal = false;

    public string $memberSearch = '';

    /**
     * @var array<int, int>
     */
    public array $selectedMemberIds = [];

    public string $groupName = '';

    public function mount(int $conversationId): void
    {
        $this->conversationId = $conversationId;
    }

    #[On('conversation-updated')]
    public function refreshConversation(int $conversationId): void
    {
        if ($conversationId === $this->conversationId) {
            unset($this->conversation);
        }
    }

    #[On('message-created')]
    public function refreshFiles(int $conversationId): void
    {
        if ($conversationId === $this->conversationId) {
            unset($this->conversation, $this->files);
        }
    }

    #[Computed]
    public function conversation(): Conversation
    {
        $conversation = Conversation::query()
            ->forUser(Auth::user())
            ->with([
                'participants' => fn ($query) => $query
                    ->select(['id', 'conversation_id', 'user_id', 'role', 'joined_at', 'muted_until', 'pinned_at'])
                    ->with('user:id,name,email'),
            ])
            ->withCount([
                'messages',
                'messages as files_count' => fn (Builder $messages) => $messages->where('type', Message::TypeFile),
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

    public function canAddMembers(): bool
    {
        return Gate::allows('addMembers', $this->conversation);
    }

    public function currentParticipant(): ConversationParticipant
    {
        $participant = $this->conversation->participants
            ->first(fn (ConversationParticipant $participant) => $participant->user_id === Auth::id());

        abort_unless($participant instanceof ConversationParticipant, 404);

        return $participant;
    }

    public function isMuted(): bool
    {
        return $this->currentParticipant()->isMuted();
    }

    public function isPinned(): bool
    {
        return $this->currentParticipant()->isPinned();
    }

    public function closeDetails(): void
    {
        $this->dispatch('conversation-details-toggled');
    }

    public function toggleMute(): void
    {
        Gate::authorize('view', $this->conversation);

        $participant = $this->currentParticipant();
        $wasMuted = $participant->isMuted();

        $participant->forceFill([
            'muted_until' => $wasMuted ? null : now()->addYear(),
        ])->save();

        unset($this->conversation);

        Flux::toast(
            variant: 'success',
            text: $wasMuted ? __('Conversation unmuted.') : __('Conversation muted.'),
        );

        $this->dispatch('conversation-updated', conversationId: $this->conversationId);
    }

    public function togglePin(): void
    {
        Gate::authorize('view', $this->conversation);

        $participant = $this->currentParticipant();
        $wasPinned = $participant->isPinned();

        $participant->forceFill([
            'pinned_at' => $wasPinned ? null : now(),
        ])->save();

        unset($this->conversation);

        Flux::toast(
            variant: 'success',
            text: $wasPinned ? __('Conversation unpinned.') : __('Conversation pinned.'),
        );

        $this->dispatch('conversation-updated', conversationId: $this->conversationId);
    }

    public function openAddMembers(): void
    {
        Gate::authorize('addMembers', $this->conversation);

        $this->resetAddMembersForm();
        $this->groupName = $this->conversation->isGroup() ? '' : $this->suggestedGroupName();
        $this->showAddMembersModal = true;
    }

    public function openFiles(): void
    {
        Gate::authorize('view', $this->conversation);

        unset($this->files);

        $this->showFilesModal = true;
    }

    public function toggleMember(int $userId): void
    {
        if ($userId === Auth::id()) {
            return;
        }

        if ($this->conversation->participants->contains('user_id', $userId)) {
            return;
        }

        if (! User::query()->whereKey($userId)->exists()) {
            return;
        }

        if (in_array($userId, $this->selectedMemberIds, true)) {
            $this->selectedMemberIds = array_values(array_diff($this->selectedMemberIds, [$userId]));
        } else {
            $this->selectedMemberIds[] = $userId;
        }

        $this->resetValidation('selectedMemberIds');

        unset($this->availableUsers, $this->selectedMembers);
    }

    public function removeSelectedMember(int $userId): void
    {
        $this->selectedMemberIds = array_values(array_diff($this->selectedMemberIds, [$userId]));

        unset($this->availableUsers, $this->selectedMembers);
    }

    public function addMembers(): void
    {
        $conversation = $this->conversation;

        Gate::authorize('addMembers', $conversation);

        $validated = $this->validate([
            'selectedMemberIds' => ['required', 'array', 'min:1'],
            'selectedMemberIds.*' => ['integer', Rule::exists('users', 'id')],
            'groupName' => ['nullable', 'string', 'max:80'],
        ]);

        $existingMemberIds = $conversation->participants->pluck('user_id');
        $memberIds = User::query()
            ->whereKey($validated['selectedMemberIds'])
            ->whereKeyNot(Auth::id())
            ->whereNotIn('id', $existingMemberIds)
            ->pluck('id')
            ->values();

        if ($memberIds->isEmpty()) {
            $this->addError('selectedMemberIds', __('Choose at least one new person.'));

            return;
        }

        DB::transaction(function () use ($conversation, $memberIds): void {
            if (! $conversation->isGroup()) {
                $conversation->forceFill([
                    'type' => Conversation::TypeGroup,
                    'name' => trim($this->groupName) ?: $this->suggestedGroupName($memberIds),
                ])->save();

                ConversationParticipant::query()
                    ->where('conversation_id', $conversation->id)
                    ->where('user_id', Auth::id())
                    ->update(['role' => ConversationParticipant::RoleAdmin]);
            }

            $memberIds->each(fn (int $memberId) => ConversationParticipant::query()->firstOrCreate([
                'conversation_id' => $conversation->id,
                'user_id' => $memberId,
            ], [
                'role' => ConversationParticipant::RoleMember,
                'joined_at' => now(),
            ]));

            $conversation->touch();
        });

        $this->resetAddMembersForm();
        $this->showAddMembersModal = false;

        unset($this->conversation);

        Flux::toast(variant: 'success', text: __('Members added.'));

        $this->dispatch('conversation-updated', conversationId: $this->conversationId);
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function availableUsers(): Collection
    {
        $search = trim($this->memberSearch);
        $participantIds = $this->conversation->participants->pluck('user_id')->all();

        return User::query()
            ->select(['id', 'name', 'email'])
            ->whereNotIn('id', $participantIds)
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

    /**
     * @return Collection<int, Message>
     */
    #[Computed]
    public function files(): Collection
    {
        Gate::authorize('view', $this->conversation);

        return Message::query()
            ->where('conversation_id', $this->conversationId)
            ->where('type', Message::TypeFile)
            ->with('sender:id,name,email')
            ->latest()
            ->limit(50)
            ->get();
    }

    private function suggestedGroupName(?Collection $newMemberIds = null): string
    {
        $participantNames = $this->conversation->participants
            ->pluck('user.name')
            ->filter();

        if ($newMemberIds) {
            $participantNames = $participantNames->merge(
                User::query()
                    ->whereKey($newMemberIds->all())
                    ->orderBy('name')
                    ->pluck('name')
            );
        }

        return $participantNames
            ->unique()
            ->take(4)
            ->join(', ');
    }

    private function resetAddMembersForm(): void
    {
        $this->reset('memberSearch', 'selectedMemberIds', 'groupName');
        $this->resetValidation();

        unset($this->availableUsers, $this->selectedMembers);
    }

    public function render(): View
    {
        return view('livewire.chat.conversation-details-panel');
    }
}
