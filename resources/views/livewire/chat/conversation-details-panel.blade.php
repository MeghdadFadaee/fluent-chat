<aside class="flex h-full w-full flex-col overflow-y-auto">
    <div class="border-b border-zinc-200 p-5 dark:border-zinc-800">
        <div class="flex items-start justify-between gap-3">
            <div>
                <flux:heading>{{ __('Details') }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $this->conversation->isGroup() ? __('Group conversation') : __('Direct conversation') }}
                </flux:text>
            </div>

            <div class="flex items-center gap-2">
                <flux:badge color="zinc" size="sm">
                    {{ trans_choice(':count message|:count messages', $this->conversation->messages_count, ['count' => $this->conversation->messages_count]) }}
                </flux:badge>

                <flux:button
                    type="button"
                    variant="ghost"
                    icon="x-mark"
                    wire:click="closeDetails"
                    aria-label="{{ __('Close details') }}"
                />
            </div>
        </div>

        <div class="mt-6 flex flex-col items-center text-center">
            <div class="flex size-20 items-center justify-center rounded-lg bg-gradient-to-br from-sky-500 to-emerald-500 text-xl font-semibold text-white shadow-sm">
                {{ $this->initials() }}
            </div>

            <flux:heading size="lg" class="mt-4 max-w-full truncate">{{ $this->title() }}</flux:heading>

            @if ($this->conversation->description)
                <flux:text class="mt-2 text-balance text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $this->conversation->description }}
                </flux:text>
            @endif
        </div>
    </div>

    <div class="space-y-6 p-5">
        <section>
            <flux:heading size="sm">{{ __('Quick actions') }}</flux:heading>

            <div class="mt-3 grid grid-cols-3 gap-2">
                <flux:tooltip :content="__('Mute')" position="top">
                    <flux:button type="button" variant="filled" icon="bell-slash" aria-label="{{ __('Mute') }}" />
                </flux:tooltip>

                <flux:tooltip :content="__('Pin')" position="top">
                    <flux:button type="button" variant="filled" icon="bookmark" aria-label="{{ __('Pin') }}" />
                </flux:tooltip>

                <flux:tooltip :content="__('Files')" position="top">
                    <flux:button type="button" variant="filled" icon="folder" aria-label="{{ __('Files') }}" />
                </flux:tooltip>
            </div>
        </section>

        <flux:separator />

        <section>
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <flux:heading size="sm">{{ __('Participants') }}</flux:heading>
                    <flux:badge size="sm" color="zinc">{{ $this->conversation->participants->count() }}</flux:badge>
                </div>

                @if ($this->canAddMembers())
                    <flux:button
                        type="button"
                        size="sm"
                        variant="filled"
                        icon="user-plus"
                        wire:click="openAddMembers"
                    >
                        {{ __('Add') }}
                    </flux:button>
                @endif
            </div>

            <div class="mt-3 space-y-3">
                @foreach ($this->conversation->participants as $participant)
                    <div wire:key="details-participant-{{ $participant->id }}" class="flex items-center gap-3">
                        <flux:avatar
                            circle
                            :name="$participant->user->name"
                            :initials="$participant->user->initials()"
                        />

                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $participant->user->name }}
                            </div>
                            <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $participant->user->email }}
                            </div>
                        </div>

                        @if ($participant->role === \App\Models\ConversationParticipant::RoleAdmin)
                            <flux:badge size="sm" color="amber">{{ __('Admin') }}</flux:badge>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        <flux:separator />

        <section class="space-y-3">
            <flux:heading size="sm">{{ __('Conversation health') }}</flux:heading>

            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Members') }}</div>
                    <div class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $this->conversation->participants->count() }}</div>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Messages') }}</div>
                    <div class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $this->conversation->messages_count }}</div>
                </div>
            </div>
        </section>
    </div>

    <flux:modal wire:model="showAddMembersModal" class="w-full max-w-xl">
        <form wire:submit="addMembers" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Add people') }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    @if ($this->conversation->isGroup())
                        {{ __('Invite more teammates into this group conversation.') }}
                    @else
                        {{ __('Adding people will turn this direct chat into a group conversation.') }}
                    @endif
                </flux:text>
            </div>

            @unless ($this->conversation->isGroup())
                <flux:input
                    wire:model="groupName"
                    :label="__('Group name')"
                    :placeholder="__('Name this group')"
                    autocomplete="off"
                />
            @endunless

            <div class="space-y-3">
                <flux:input
                    wire:model.live.debounce.250ms="memberSearch"
                    icon="magnifying-glass"
                    :label="__('People')"
                    :placeholder="__('Search people not already here')"
                    autocomplete="off"
                />

                @error('selectedMemberIds')
                    <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror

                @if ($this->selectedMembers->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->selectedMembers as $member)
                            <button
                                type="button"
                                wire:key="add-selected-member-{{ $member->id }}"
                                wire:click="removeSelectedMember({{ $member->id }})"
                                class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white py-1 pe-2 ps-1 text-sm text-zinc-700 shadow-sm transition hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-600"
                            >
                                <span class="flex size-6 items-center justify-center rounded-full bg-zinc-100 text-[10px] font-semibold dark:bg-zinc-800">
                                    {{ $member->initials() }}
                                </span>
                                <span>{{ $member->name }}</span>
                                <flux:icon.x-mark class="size-3 text-zinc-400" />
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="max-h-72 overflow-y-auto rounded-lg border border-zinc-200 bg-zinc-50 p-1 dark:border-zinc-800 dark:bg-zinc-900/60">
                    @forelse ($this->availableUsers as $candidate)
                        <button
                            type="button"
                            wire:key="add-candidate-{{ $candidate->id }}"
                            wire:click="toggleMember({{ $candidate->id }})"
                            class="flex w-full items-center gap-3 rounded-md p-3 text-left transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-accent dark:hover:bg-zinc-900"
                        >
                            <flux:avatar
                                circle
                                :name="$candidate->name"
                                :initials="$candidate->initials()"
                            />

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $candidate->name }}</span>
                                <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $candidate->email }}</span>
                            </span>

                            <span class="flex size-7 items-center justify-center rounded-full border border-zinc-200 bg-white text-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300">
                                <flux:icon.plus class="size-4" />
                            </span>
                        </button>
                    @empty
                        <div class="flex min-h-32 items-center justify-center p-6 text-center">
                            <div>
                                <div class="mx-auto mb-3 flex size-10 items-center justify-center rounded-lg border border-dashed border-zinc-300 text-zinc-400 dark:border-zinc-700">
                                    <flux:icon.user-plus class="size-5" />
                                </div>
                                <flux:heading size="sm">{{ __('No people available') }}</flux:heading>
                                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('Everyone matching your search is already in this conversation.') }}
                                </flux:text>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button
                    type="submit"
                    variant="primary"
                    icon="user-plus"
                    wire:loading.attr="disabled"
                    wire:target="addMembers"
                    :disabled="count($selectedMemberIds) === 0"
                >
                    {{ __('Add selected') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</aside>
