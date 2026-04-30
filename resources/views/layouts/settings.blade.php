<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-dvh bg-zinc-100 text-zinc-950 antialiased dark:bg-zinc-950 dark:text-zinc-50">
        <div class="min-h-dvh lg:grid lg:grid-cols-[18rem_1fr]">
            <aside class="border-b border-zinc-200 bg-white/90 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/80 lg:sticky lg:top-0 lg:h-dvh lg:border-b-0 lg:border-e">
                <div class="flex h-full flex-col gap-6 p-4 lg:p-5">
                    <div class="flex items-center justify-between gap-3">
                        <a href="{{ route('dashboard') }}" wire:navigate class="flex min-w-0 items-center gap-3 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-zinc-900">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-zinc-950 text-white dark:bg-white dark:text-zinc-950">
                                <x-app-logo-icon class="size-5 fill-current" />
                            </span>
                            <span class="truncate text-sm font-semibold">{{ __('Fluent Chat') }}</span>
                        </a>

                        <flux:button
                            :href="route('dashboard')"
                            wire:navigate
                            variant="ghost"
                            icon="x-mark"
                            aria-label="{{ __('Back to chat') }}"
                        />
                    </div>

                    <div>
                        <flux:heading size="xl">{{ __('Account') }}</flux:heading>
                        <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Manage your profile, password, and sign-in security.') }}
                        </flux:text>
                    </div>

                    <flux:navlist aria-label="{{ __('Account settings') }}" class="grid gap-1">
                        <flux:navlist.item
                            :href="route('profile.edit')"
                            :current="request()->routeIs('profile.edit')"
                            icon="user-circle"
                            wire:navigate
                        >
                            {{ __('Profile') }}
                        </flux:navlist.item>

                        <flux:navlist.item
                            :href="route('security.edit')"
                            :current="request()->routeIs('security.edit')"
                            icon="shield-check"
                            wire:navigate
                        >
                            {{ __('Security') }}
                        </flux:navlist.item>

                        <flux:navlist.item
                            :href="route('appearance.edit')"
                            :current="request()->routeIs('appearance.edit')"
                            icon="swatch"
                            wire:navigate
                        >
                            {{ __('Appearance') }}
                        </flux:navlist.item>
                    </flux:navlist>

                    <div class="mt-auto hidden lg:block">
                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-950">
                            <div class="flex items-center gap-3">
                                <flux:avatar
                                    circle
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-medium">{{ auth()->user()->name }}</div>
                                    <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ auth()->user()->email }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <main class="min-w-0 px-4 py-6 sm:px-6 lg:px-10 lg:py-10">
                <div class="mx-auto w-full max-w-4xl">
                    {{ $slot }}
                </div>
            </main>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
