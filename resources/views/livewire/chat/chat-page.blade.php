<section class="mx-auto flex h-[calc(100vh-5.5rem)] min-h-[620px] w-full max-w-[1600px] overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
    <aside
        @class([
            'h-full w-full shrink-0 border-zinc-200 bg-zinc-50/80 dark:border-zinc-800 dark:bg-zinc-900/70 md:flex md:w-[22rem] lg:w-96',
            'hidden border-e' => $selectedConversationId,
            'flex md:border-e' => ! $selectedConversationId,
        ])
    >
        <livewire:chat.conversation-list
            :selected-conversation-id="$selectedConversationId"
            :key="'conversation-list-'.$selectedConversationId"
        />
    </aside>

    <main
        @class([
            'min-w-0 flex-1 bg-white dark:bg-zinc-950 md:flex',
            'flex' => $selectedConversationId,
            'hidden' => ! $selectedConversationId,
        ])
    >
        @if ($selectedConversationId)
            <livewire:chat.conversation-view
                :conversation-id="$selectedConversationId"
                :key="'conversation-view-'.$selectedConversationId"
            />
        @else
            <div class="hidden h-full flex-1 items-center justify-center p-8 md:flex">
                <div class="max-w-md text-center">
                    <div class="mx-auto mb-6 flex size-16 items-center justify-center rounded-lg border border-zinc-200 bg-white text-zinc-500 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                        <flux:icon.chat-bubble-left-right class="size-8" />
                    </div>

                    <flux:heading size="xl">{{ __('Choose a conversation') }}</flux:heading>
                    <flux:text class="mt-3 text-balance text-zinc-500 dark:text-zinc-400">
                        {{ __('Your messages, teammates, and shared context will appear here.') }}
                    </flux:text>
                </div>
            </div>
        @endif
    </main>

    @if ($selectedConversationId && $detailsPanelOpen)
        <aside class="hidden h-full w-[21rem] shrink-0 border-s border-zinc-200 bg-zinc-50/70 dark:border-zinc-800 dark:bg-zinc-900/60 2xl:flex">
            <livewire:chat.conversation-details-panel
                :conversation-id="$selectedConversationId"
                :key="'conversation-details-'.$selectedConversationId"
            />
        </aside>
    @endif
</section>
