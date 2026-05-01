<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.head', ['title' => 'Fluent Chat'])
    </head>

    <body class="bg-zinc-950 font-sans text-white antialiased">
        <a
            href="#content"
            class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-3 focus:py-2 focus:text-sm focus:font-medium focus:text-zinc-950"
        >
            {{ __('Skip to content') }}
        </a>

        <main id="content" class="min-h-dvh overflow-hidden">
            <section class="relative isolate min-h-[86svh] overflow-hidden">
                <div aria-hidden="true" class="absolute inset-0 bg-zinc-950">
                    <div class="absolute inset-0 bg-[linear-gradient(120deg,rgba(20,184,166,0.18),rgba(250,204,21,0.10)_34%,rgba(244,63,94,0.12)_68%,rgba(10,10,10,0.96))]"></div>
                    <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.06)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.05)_1px,transparent_1px)] bg-[size:72px_72px] opacity-35"></div>

                    <div class="absolute left-1/2 top-24 w-[72rem] max-w-none -translate-x-[42%] rotate-[-2deg] opacity-80 sm:top-16">
                        <div class="overflow-hidden rounded-lg border border-white/15 bg-zinc-950/75 shadow-2xl shadow-black/40 backdrop-blur">
                            <div class="flex items-center justify-between border-b border-white/10 bg-white/5 px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="size-2 rounded-full bg-rose-400"></span>
                                    <span class="size-2 rounded-full bg-amber-300"></span>
                                    <span class="size-2 rounded-full bg-emerald-400"></span>
                                </div>
                                <div class="h-2 w-40 rounded-full bg-white/10"></div>
                            </div>

                            <div class="grid h-[36rem] grid-cols-[18rem_1fr_17rem]">
                                <div class="border-r border-white/10 bg-zinc-950/60 p-4">
                                    <div class="mb-5 flex items-center justify-between">
                                        <div class="h-6 w-28 rounded-md bg-white/15"></div>
                                        <div class="size-8 rounded-md bg-teal-300/90"></div>
                                    </div>

                                    <div class="space-y-3">
                                        @foreach ([
                                            ['Launch Room', 'Final asset bundle is attached.', '12'],
                                            ['Mina Partner', 'Let me check the new mockup.', ''],
                                            ['Ops Standup', 'Deployment note is ready.', '4'],
                                            ['Design Review', 'Updated spacing pass shipped.', ''],
                                        ] as [$name, $preview, $count])
                                            <div class="rounded-lg border border-white/10 bg-white/[0.07] p-3">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex size-9 items-center justify-center rounded-full bg-white/15 text-xs font-semibold">{{ mb_substr($name, 0, 1) }}</div>
                                                    <div class="min-w-0 flex-1">
                                                        <div class="truncate text-sm font-semibold text-white">{{ $name }}</div>
                                                        <div class="truncate text-xs text-zinc-300">{{ $preview }}</div>
                                                    </div>
                                                    @if ($count !== '')
                                                        <div class="flex size-5 items-center justify-center rounded-full bg-emerald-300 text-[10px] font-bold text-zinc-950">{{ $count }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="flex flex-col bg-zinc-900/70">
                                    <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex size-10 items-center justify-center rounded-full bg-gradient-to-br from-teal-300 to-emerald-400 text-sm font-bold text-zinc-950">LR</div>
                                            <div>
                                                <div class="text-sm font-semibold">Launch Room</div>
                                                <div class="text-xs text-emerald-200">8 members online</div>
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <div class="size-8 rounded-md bg-white/10"></div>
                                            <div class="size-8 rounded-md bg-white/10"></div>
                                        </div>
                                    </div>

                                    <div class="flex-1 space-y-4 p-6">
                                        <div class="max-w-md rounded-lg border border-white/10 bg-white/10 p-4 text-sm text-zinc-100">
                                            The campaign room is clean now. I pinned the launch notes and grouped the latest files.
                                        </div>
                                        <div class="ml-auto max-w-sm rounded-lg bg-white p-4 text-sm font-medium text-zinc-950 shadow-xl shadow-black/20">
                                            Perfect. Drop the handoff PDF here and I will send it to product.
                                        </div>
                                        <div class="max-w-md rounded-lg border border-white/10 bg-white/10 p-4">
                                            <div class="mb-3 flex items-center gap-3">
                                                <div class="flex size-10 items-center justify-center rounded-md bg-white/10">
                                                    <flux:icon.document class="size-5 text-amber-200" />
                                                </div>
                                                <div>
                                                    <div class="text-sm font-semibold">handoff-notes.pdf</div>
                                                    <div class="text-xs text-zinc-300">2.4 MB &middot; shared now</div>
                                                </div>
                                            </div>
                                            <div class="h-2 overflow-hidden rounded-full bg-white/10">
                                                <div class="h-full w-4/5 rounded-full bg-emerald-300"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="border-t border-white/10 p-4">
                                        <div class="flex items-center gap-3 rounded-lg border border-white/10 bg-white/10 p-3">
                                            <div class="size-8 rounded-md bg-white/10"></div>
                                            <div class="h-3 flex-1 rounded-full bg-white/20"></div>
                                            <div class="flex size-9 items-center justify-center rounded-md bg-emerald-300 text-zinc-950">
                                                <flux:icon.paper-airplane class="size-4" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-l border-white/10 bg-zinc-950/65 p-5">
                                    <div class="mb-5 flex flex-col items-center text-center">
                                        <div class="mb-3 flex size-16 items-center justify-center rounded-lg bg-gradient-to-br from-teal-300 to-amber-200 text-lg font-bold text-zinc-950">LR</div>
                                        <div class="font-semibold">Launch Room</div>
                                        <div class="text-xs text-zinc-400">Files, people, context</div>
                                    </div>

                                    <div class="space-y-3 text-sm">
                                        <div class="rounded-lg border border-white/10 bg-white/[0.07] p-3">
                                            <div class="text-xs text-zinc-400">Shared files</div>
                                            <div class="mt-1 text-2xl font-semibold">18</div>
                                        </div>
                                        <div class="rounded-lg border border-white/10 bg-white/[0.07] p-3">
                                            <div class="text-xs text-zinc-400">Members</div>
                                            <div class="mt-1 text-2xl font-semibold">8</div>
                                        </div>
                                        <div class="rounded-lg border border-white/10 bg-white/[0.07] p-3">
                                            <div class="text-xs text-zinc-400">Unread</div>
                                            <div class="mt-1 text-2xl font-semibold">4</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="absolute inset-0 bg-zinc-950/55"></div>
                <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-zinc-950 to-transparent"></div>

                <header class="relative z-10 mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-5 sm:px-8">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-white text-sm font-bold text-zinc-950">FC</span>
                        <span class="text-sm font-semibold text-white">Fluent Chat</span>
                    </a>

                    <nav class="flex items-center gap-2">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-md bg-white px-4 py-2 text-sm font-semibold text-zinc-950 shadow-sm transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70">
                                {{ __('Dashboard') }}
                                <flux:icon.arrow-right class="size-4" />
                            </a>
                        @else
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="rounded-md px-3 py-2 text-sm font-medium text-white/85 transition hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70">
                                    {{ __('Sign in') }}
                                </a>
                            @endif

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="hidden rounded-md bg-white px-4 py-2 text-sm font-semibold text-zinc-950 shadow-sm transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70 sm:inline-flex">
                                    {{ __('Create account') }}
                                </a>
                            @endif
                        @endauth
                    </nav>
                </header>

                <div class="relative z-10 mx-auto flex min-h-[68svh] max-w-7xl items-center px-5 pb-16 pt-10 sm:px-8">
                    <div class="max-w-3xl">
                        <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-sm font-medium text-emerald-100 backdrop-blur">
                            <span class="size-2 rounded-full bg-emerald-300"></span>
                            {{ __('Private team messaging with files built in') }}
                        </div>

                        <h1 class="max-w-3xl text-5xl font-semibold leading-none text-white">
                            Fluent Chat
                        </h1>

                        <p class="mt-6 max-w-2xl text-lg leading-8 text-zinc-200">
                            A polished workspace for direct messages, group conversations, secure file sharing, and fast team context without the weight of a noisy collaboration suite.
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-3">
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-md bg-emerald-300 px-5 py-3 text-sm font-semibold text-zinc-950 shadow-lg shadow-emerald-950/30 transition hover:bg-emerald-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-100">
                                    {{ __('Open dashboard') }}
                                    <flux:icon.arrow-right class="size-4" />
                                </a>
                            @else
                                @if (Route::has('login'))
                                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-md bg-emerald-300 px-5 py-3 text-sm font-semibold text-zinc-950 shadow-lg shadow-emerald-950/30 transition hover:bg-emerald-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-100">
                                        {{ __('Enter workspace') }}
                                        <flux:icon.arrow-right class="size-4" />
                                    </a>
                                @endif

                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-md border border-white/20 bg-white/10 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70">
                                        {{ __('Create account') }}
                                    </a>
                                @endif
                            @endauth
                        </div>

                        <dl class="mt-10 grid max-w-2xl grid-cols-2 gap-3 text-sm text-zinc-200 sm:grid-cols-4">
                            <div class="rounded-lg border border-white/10 bg-white/10 p-3 backdrop-blur">
                                <dt class="text-zinc-400">{{ __('Rooms') }}</dt>
                                <dd class="mt-1 font-semibold text-white">{{ __('Direct + group') }}</dd>
                            </div>
                            <div class="rounded-lg border border-white/10 bg-white/10 p-3 backdrop-blur">
                                <dt class="text-zinc-400">{{ __('Files') }}</dt>
                                <dd class="mt-1 font-semibold text-white">{{ __('Private storage') }}</dd>
                            </div>
                            <div class="rounded-lg border border-white/10 bg-white/10 p-3 backdrop-blur">
                                <dt class="text-zinc-400">{{ __('Security') }}</dt>
                                <dd class="mt-1 font-semibold text-white">{{ __('2FA ready') }}</dd>
                            </div>
                            <div class="rounded-lg border border-white/10 bg-white/10 p-3 backdrop-blur">
                                <dt class="text-zinc-400">{{ __('Speed') }}</dt>
                                <dd class="mt-1 font-semibold text-white">{{ __('Livewire powered') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </section>

            <section class="bg-zinc-950 px-5 pb-16 pt-4 text-white sm:px-8">
                <div class="mx-auto max-w-7xl">
                    <div class="grid gap-4 md:grid-cols-3">
                        <article class="rounded-lg border border-white/10 bg-white/[0.06] p-5 shadow-sm">
                            <div class="mb-4 flex size-10 items-center justify-center rounded-lg bg-emerald-300 text-zinc-950">
                                <flux:icon.chat-bubble-left-right class="size-5" />
                            </div>
                            <h2 class="text-base font-semibold">{{ __('Focused conversations') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-zinc-300">
                                Searchable direct and group rooms with clear unread state, smooth selection, and a layout built for daily use.
                            </p>
                        </article>

                        <article class="rounded-lg border border-white/10 bg-white/[0.06] p-5 shadow-sm">
                            <div class="mb-4 flex size-10 items-center justify-center rounded-lg bg-amber-200 text-zinc-950">
                                <flux:icon.folder class="size-5" />
                            </div>
                            <h2 class="text-base font-semibold">{{ __('Files in context') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-zinc-300">
                                Attachments live beside the conversation and remain available from the details panel when the thread moves on.
                            </p>
                        </article>

                        <article class="rounded-lg border border-white/10 bg-white/[0.06] p-5 shadow-sm">
                            <div class="mb-4 flex size-10 items-center justify-center rounded-lg bg-rose-300 text-zinc-950">
                                <flux:icon.shield-check class="size-5" />
                            </div>
                            <h2 class="text-base font-semibold">{{ __('Account protection') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-zinc-300">
                                Profile settings, password updates, and two-factor authentication are ready for a real team workspace.
                            </p>
                        </article>
                    </div>
                </div>
            </section>
        </main>

        @fluxScripts
    </body>
</html>
