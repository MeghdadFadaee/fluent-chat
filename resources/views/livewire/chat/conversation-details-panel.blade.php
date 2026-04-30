<aside class="flex h-full w-full flex-col overflow-y-auto">
    <div class="border-b border-zinc-200 p-5 dark:border-zinc-800">
        <div class="flex items-start justify-between gap-3">
            <div>
                <flux:heading>{{ __('Details') }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $this->conversation->isGroup() ? __('Group conversation') : __('Direct conversation') }}
                </flux:text>
            </div>

            <flux:badge color="zinc" size="sm">
                {{ trans_choice(':count message|:count messages', $this->conversation->messages_count, ['count' => $this->conversation->messages_count]) }}
            </flux:badge>
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
                <flux:heading size="sm">{{ __('Participants') }}</flux:heading>
                <flux:badge size="sm" color="zinc">{{ $this->conversation->participants->count() }}</flux:badge>
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
</aside>
