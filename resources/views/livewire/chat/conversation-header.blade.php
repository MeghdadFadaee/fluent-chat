<header class="sticky top-0 z-10 flex h-[4.5rem] shrink-0 items-center justify-between gap-3 border-b border-zinc-200 bg-white/85 px-3 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/85 sm:px-5">
    <div class="flex min-w-0 items-center gap-3">
        <flux:button
            type="button"
            variant="ghost"
            icon="chevron-left"
            class="md:hidden"
            wire:click="closeConversation"
            aria-label="{{ __('Back to conversations') }}"
        />

        @if ($this->conversation->isGroup())
            <div class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-sky-500 to-emerald-500 text-sm font-semibold text-white">
                {{ $this->initials() }}
            </div>
        @elseif ($this->isOnline())
            <flux:avatar
                circle
                badge
                badge:color="green"
                :name="$this->title()"
                :initials="$this->initials()"
            />
        @else
            <flux:avatar
                circle
                :name="$this->title()"
                :initials="$this->initials()"
            />
        @endif

        <div class="min-w-0">
            <div class="flex min-w-0 items-center gap-2">
                <flux:heading class="truncate">{{ $this->title() }}</flux:heading>
                @if ($this->conversation->isGroup())
                    <flux:badge size="sm" color="sky">{{ __('Group') }}</flux:badge>
                @endif
            </div>

            <flux:text class="truncate text-sm text-zinc-500 dark:text-zinc-400">
                {{ $this->subtitle() }}
            </flux:text>
        </div>
    </div>

    <div class="flex items-center gap-1">
        <flux:tooltip :content="__('Search messages')" position="bottom">
            <flux:button type="button" variant="ghost" icon="magnifying-glass" aria-label="{{ __('Search messages') }}" />
        </flux:tooltip>

        <flux:tooltip :content="__('Conversation details')" position="bottom">
            <flux:button type="button" variant="ghost" icon="information-circle" wire:click="toggleDetails" aria-label="{{ __('Conversation details') }}" />
        </flux:tooltip>
    </div>
</header>
