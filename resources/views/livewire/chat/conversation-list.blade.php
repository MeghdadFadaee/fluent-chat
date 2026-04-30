<div class="flex h-full w-full flex-col">
    <div class="shrink-0 border-b border-zinc-200 bg-white/70 p-4 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/40">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <flux:heading size="lg">{{ __('Inbox') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ trans_choice(':count conversation|:count conversations', $this->conversations->count(), ['count' => $this->conversations->count()]) }}
                </flux:text>
            </div>

            <div class="flex items-center gap-2">
                <flux:badge color="emerald" size="sm">{{ __('Live') }}</flux:badge>

                <flux:dropdown position="bottom" align="end">
                    <flux:profile
                        :name="auth()->user()->name"
                        :initials="auth()->user()->initials()"
                        icon-trailing="chevron-down"
                        aria-label="{{ __('Account menu') }}"
                    />

                    <flux:menu>
                        <div class="p-2">
                            <div class="flex items-center gap-3 rounded-lg bg-zinc-50 p-2 dark:bg-zinc-900">
                                <flux:avatar
                                    circle
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="min-w-0">
                                    <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ auth()->user()->name }}</div>
                                    <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ auth()->user()->email }}</div>
                                </div>
                            </div>
                        </div>

                        <flux:menu.separator />

                        <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate>
                            {{ __('Profile settings') }}
                        </flux:menu.item>

                        <flux:menu.item :href="route('security.edit')" icon="shield-check" wire:navigate>
                            {{ __('Password and 2FA') }}
                        </flux:menu.item>

                        <flux:menu.item :href="route('appearance.edit')" icon="swatch" wire:navigate>
                            {{ __('Appearance') }}
                        </flux:menu.item>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item
                                as="button"
                                type="submit"
                                icon="arrow-right-start-on-rectangle"
                                class="w-full cursor-pointer"
                            >
                                {{ __('Log out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>

        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            :placeholder="__('Search conversations')"
            aria-label="{{ __('Search conversations') }}"
        />
    </div>

    <div class="relative min-h-0 flex-1 overflow-y-auto p-2" role="list" aria-label="{{ __('Conversations') }}">
        <div wire:loading.delay wire:target="search" class="space-y-2 p-2">
            @for ($index = 0; $index < 6; $index++)
                <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900">
                    <flux:skeleton class="size-11 rounded-full" />
                    <div class="min-w-0 flex-1 space-y-2">
                        <flux:skeleton class="h-4 w-2/3" />
                        <flux:skeleton class="h-3 w-full" />
                    </div>
                </div>
            @endfor
        </div>

        <div wire:loading.remove wire:target="search" class="space-y-1">
            @forelse ($this->conversations as $conversation)
                @php
                    $selected = $selectedConversationId === $conversation->id;
                    $unreadCount = (int) ($conversation->unread_messages_count ?? 0);
                @endphp

                <button
                    type="button"
                    wire:key="conversation-{{ $conversation->id }}"
                    wire:click="selectConversation({{ $conversation->id }})"
                    wire:loading.attr="disabled"
                    wire:target="selectConversation({{ $conversation->id }})"
                    aria-pressed="{{ $selected ? 'true' : 'false' }}"
                    @class([
                        'group relative grid w-full grid-cols-[auto_1fr_auto] items-center gap-3 rounded-lg border p-3 text-left transition duration-150 data-loading:cursor-wait data-loading:opacity-80 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-white disabled:pointer-events-none dark:focus-visible:ring-offset-zinc-950',
                        'border-zinc-900 bg-zinc-900 text-white shadow-sm dark:border-white dark:bg-white dark:text-zinc-950' => $selected,
                        'border-transparent hover:border-zinc-200 hover:bg-white hover:shadow-sm dark:hover:border-zinc-800 dark:hover:bg-zinc-900' => ! $selected,
                    ])
                >
                    <div
                        wire:loading.flex
                        wire:target="selectConversation({{ $conversation->id }})"
                        class="absolute inset-0 z-10 hidden items-center justify-center rounded-lg bg-white/70 backdrop-blur-[2px] dark:bg-zinc-950/70"
                        aria-hidden="true"
                    >
                        <div class="flex items-center gap-2 rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                            <flux:icon.loading class="size-4" />
                            <span>{{ __('Opening') }}</span>
                        </div>
                    </div>

                    <div class="relative">
                        @if ($conversation->isGroup())
                            <div @class([
                                'flex size-11 items-center justify-center rounded-lg text-sm font-semibold',
                                'bg-white/15 text-white dark:bg-zinc-950/10 dark:text-zinc-950' => $selected,
                                'bg-gradient-to-br from-sky-500 to-emerald-500 text-white' => ! $selected,
                            ])>
                                {{ $this->initialsFor($conversation) }}
                            </div>
                        @elseif ($this->isOnline($conversation))
                            <flux:avatar
                                circle
                                badge
                                badge:color="green"
                                :name="$this->titleFor($conversation)"
                                :initials="$this->initialsFor($conversation)"
                            />
                        @else
                            <flux:avatar
                                circle
                                :name="$this->titleFor($conversation)"
                                :initials="$this->initialsFor($conversation)"
                            />
                        @endif
                    </div>

                    <div class="min-w-0">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="truncate text-sm font-semibold">{{ $this->titleFor($conversation) }}</span>
                            @if ($conversation->isGroup())
                                <span @class([
                                    'rounded-full px-1.5 py-0.5 text-[10px] font-medium uppercase',
                                    'bg-white/15 text-white/80 dark:bg-zinc-950/10 dark:text-zinc-700' => $selected,
                                    'bg-sky-50 text-sky-700 dark:bg-sky-400/10 dark:text-sky-300' => ! $selected,
                                ])>{{ __('Group') }}</span>
                            @endif
                        </div>

                        <p @class([
                            'mt-1 truncate text-sm',
                            'text-white/75 dark:text-zinc-700' => $selected,
                            'text-zinc-500 dark:text-zinc-400' => ! $selected,
                        ])>
                            {{ $this->previewFor($conversation) }}
                        </p>

                        <p @class([
                            'mt-1 text-xs',
                            'text-white/60 dark:text-zinc-600' => $selected,
                            'text-zinc-400 dark:text-zinc-500' => ! $selected,
                        ])>
                            {{ $this->participantSummaryFor($conversation) }}
                        </p>
                    </div>

                    <div class="flex h-full flex-col items-end justify-between gap-2">
                        <span @class([
                            'text-xs',
                            'text-white/60 dark:text-zinc-600' => $selected,
                            'text-zinc-400 dark:text-zinc-500' => ! $selected,
                        ])>
                            {{ $this->timeFor($conversation->latestMessage?->created_at) }}
                        </span>

                        @if ($unreadCount > 0)
                            <span class="flex min-w-5 items-center justify-center rounded-full bg-emerald-500 px-1.5 text-xs font-semibold text-white">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                        @else
                            <span class="size-2 rounded-full bg-transparent transition group-hover:bg-zinc-300 dark:group-hover:bg-zinc-700"></span>
                        @endif
                    </div>
                </button>
            @empty
                <div class="flex h-full min-h-72 items-center justify-center p-8 text-center">
                    <div>
                        <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-lg border border-dashed border-zinc-300 text-zinc-400 dark:border-zinc-700">
                            <flux:icon.magnifying-glass class="size-6" />
                        </div>
                        <flux:heading size="sm">{{ __('No conversations found') }}</flux:heading>
                        <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Try a different name, email, or group title.') }}
                        </flux:text>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>
