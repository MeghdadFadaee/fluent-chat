<form
    wire:submit="sendMessage"
    x-data="{ attachmentsOpen: {{ count($attachments) > 0 ? 'true' : 'false' }} }"
    class="shrink-0 border-t border-zinc-200 bg-white/90 p-3 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/90 sm:p-4"
>
    <input
        x-ref="fileInput"
        type="file"
        wire:model="attachments"
        x-on:change="attachmentsOpen = true"
        multiple
        class="hidden"
    >

    <div
        x-cloak
        x-show="attachmentsOpen || {{ count($attachments) > 0 ? 'true' : 'false' }}"
        x-transition.opacity.duration.150ms
        class="mb-2 rounded-lg border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
    >
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Attachments') }}</div>
                <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Up to 5 files, 10 MB each') }}</div>
            </div>

            <flux:button
                type="button"
                size="sm"
                variant="ghost"
                icon="plus"
                x-on:click="$refs.fileInput.click()"
            >
                {{ __('Add files') }}
            </flux:button>
        </div>

        <div wire:loading.flex wire:target="attachments" class="mt-3 items-center gap-3 rounded-md border border-dashed border-zinc-300 bg-zinc-50 p-3 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-400">
            <flux:icon.arrow-path class="size-4 animate-spin" />
            <span>{{ __('Uploading files...') }}</span>
        </div>

        @if (count($attachments) > 0)
            <div class="mt-3 grid gap-2">
                @foreach ($attachments as $index => $attachment)
                    <div wire:key="composer-attachment-{{ $index }}" class="flex items-center gap-3 rounded-md border border-zinc-200 bg-zinc-50 p-2 dark:border-zinc-800 dark:bg-zinc-950">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-md bg-white text-zinc-500 shadow-sm dark:bg-zinc-900 dark:text-zinc-300">
                            <flux:icon.document class="size-5" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $attachment->getClientOriginalName() }}
                            </div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ \Illuminate\Support\Number::fileSize((int) $attachment->getSize()) }}
                            </div>
                        </div>

                        <flux:button
                            type="button"
                            size="sm"
                            variant="ghost"
                            icon="x-mark"
                            wire:click="removeAttachment({{ $index }})"
                            aria-label="{{ __('Remove attachment') }}"
                        />
                    </div>
                @endforeach
            </div>
        @endif

        @error('attachments')
            <flux:text class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
        @enderror

        @error('attachments.*')
            <flux:text class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
        @enderror
    </div>

    <div class="flex items-end gap-2 rounded-lg border border-zinc-200 bg-white p-2 shadow-sm transition focus-within:border-zinc-300 focus-within:ring-2 focus-within:ring-accent focus-within:ring-offset-2 focus-within:ring-offset-white dark:border-zinc-800 dark:bg-zinc-900 dark:focus-within:border-zinc-700 dark:focus-within:ring-offset-zinc-950">
        <flux:tooltip :content="__('Attach file')" position="top">
            <flux:button
                type="button"
                variant="ghost"
                icon="paper-clip"
                x-on:click="$refs.fileInput.click()"
                aria-label="{{ __('Attach file') }}"
            />
        </flux:tooltip>

        <flux:textarea
            wire:model.live.debounce.150ms="body"
            rows="1"
            :placeholder="__('Write a message...')"
            aria-label="{{ __('Message') }}"
            class="max-h-36 min-h-11 flex-1 resize-none border-0! bg-transparent! shadow-none! ring-0!"
            x-on:keydown.enter.exact.prevent="$wire.sendMessage()"
            x-on:keydown.shift.enter.stop
        />

        <flux:tooltip :content="__('Emoji')" position="top">
            <flux:button type="button" variant="ghost" icon="face-smile" aria-label="{{ __('Emoji') }}" />
        </flux:tooltip>

        <flux:button
            type="submit"
            variant="primary"
            icon="paper-airplane"
            :disabled="blank(trim($body)) && count($attachments) === 0"
            wire:loading.attr="disabled"
            wire:target="attachments,sendMessage"
            aria-label="{{ __('Send message') }}"
        />
    </div>

    @error('body')
        <flux:text class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
    @enderror
</form>
