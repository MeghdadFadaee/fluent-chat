<div
    class="flex h-full min-w-0 flex-1 flex-col"
    x-data="{
        scrollToBottom() {
            this.$nextTick(() => {
                this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight
            })
        }
    }"
    x-init="scrollToBottom()"
    x-on:message-created.window="if ($event.detail.conversationId === @js($conversationId)) scrollToBottom()"
>
    <livewire:chat.conversation-header
        :conversation-id="$conversationId"
        :key="'conversation-header-'.$conversationId"
    />

    <div x-ref="messages" class="min-h-0 flex-1 overflow-y-auto scroll-smooth bg-zinc-50/40 px-4 py-6 dark:bg-zinc-950 sm:px-6">
        @if ($this->hasMoreMessages)
            <div class="mb-6 flex justify-center">
                <flux:button
                    size="sm"
                    variant="filled"
                    icon="arrow-up"
                    wire:click.preserve-scroll="loadEarlier"
                    wire:loading.attr="disabled"
                    wire:target="loadEarlier"
                >
                    {{ __('Load earlier') }}
                </flux:button>
            </div>
        @endif

        @if ($this->messages->isEmpty())
            <div class="flex h-full min-h-96 items-center justify-center text-center">
                <div class="max-w-sm">
                    <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-lg border border-dashed border-zinc-300 text-zinc-400 dark:border-zinc-700">
                        <flux:icon.chat-bubble-left-ellipsis class="size-6" />
                    </div>
                    <flux:heading size="sm">{{ __('No messages yet') }}</flux:heading>
                    <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Start the conversation with a short note.') }}
                    </flux:text>
                </div>
            </div>
        @else
            <div class="space-y-6">
                @php
                    $lastDate = null;
                @endphp

                @foreach ($this->messages as $message)
                    @php
                        $messageDate = $message->created_at->toDateString();
                        $isMine = $message->user_id === auth()->id();
                        $senderName = $message->sender?->name ?? __('Deleted user');
                    @endphp

                    @if ($messageDate !== $lastDate)
                        <div class="flex items-center gap-3" wire:key="date-{{ $messageDate }}">
                            <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-800"></div>
                            <span class="rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-500 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                                {{ $this->dateLabel($message->created_at) }}
                            </span>
                            <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-800"></div>
                        </div>

                        @php
                            $lastDate = $messageDate;
                        @endphp
                    @endif

                    <div wire:key="message-{{ $message->id }}" @class([
                        'flex items-end gap-2',
                        'justify-end' => $isMine,
                        'justify-start' => ! $isMine,
                    ])>
                        @unless ($isMine)
                            <div class="mb-6 flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                {{ $message->sender?->initials() ?? 'DU' }}
                            </div>
                        @endunless

                        <div @class([
                            'max-w-[min(82%,42rem)]',
                            'items-end text-right' => $isMine,
                            'items-start text-left' => ! $isMine,
                        ])>
                            @if (! $isMine && $this->conversation->isGroup())
                                <div class="mb-1 px-1 text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    {{ $senderName }}
                                </div>
                            @endif

                            <div @class([
                                'rounded-lg px-4 py-3 text-sm leading-6 shadow-sm',
                                'bg-zinc-900 text-white dark:bg-white dark:text-zinc-950' => $isMine,
                                'border border-zinc-200 bg-white text-zinc-800 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100' => ! $isMine,
                            ])>
                                <p class="whitespace-pre-wrap break-words">{{ $message->body }}</p>
                            </div>

                            <div @class([
                                'mt-1 px-1 text-[11px] text-zinc-400 dark:text-zinc-500',
                                'text-right' => $isMine,
                            ])>
                                {{ $message->created_at->format('H:i') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <livewire:chat.message-composer
        :conversation-id="$conversationId"
        :key="'message-composer-'.$conversationId"
    />
</div>
