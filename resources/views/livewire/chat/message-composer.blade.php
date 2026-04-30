<form wire:submit="sendMessage" class="shrink-0 border-t border-zinc-200 bg-white/90 p-3 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/90 sm:p-4">
    <div class="flex items-end gap-2 rounded-lg border border-zinc-200 bg-white p-2 shadow-sm transition focus-within:border-zinc-300 focus-within:ring-2 focus-within:ring-accent focus-within:ring-offset-2 focus-within:ring-offset-white dark:border-zinc-800 dark:bg-zinc-900 dark:focus-within:border-zinc-700 dark:focus-within:ring-offset-zinc-950">
        <flux:tooltip :content="__('Attach file')" position="top">
            <flux:button type="button" variant="ghost" icon="paper-clip" aria-label="{{ __('Attach file') }}" />
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
            :disabled="blank(trim($body))"
            wire:loading.attr="disabled"
            wire:target="sendMessage"
            aria-label="{{ __('Send message') }}"
        />
    </div>

    @error('body')
        <flux:text class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
    @enderror
</form>
