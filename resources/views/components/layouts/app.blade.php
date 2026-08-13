@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title.' — '.config('app.name') : config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-full bg-surface font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-white">

    {{-- Skip link: first tab stop, lets keyboard users bypass the nav (§38). --}}
    <a
        href="#main-content"
        class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-white"
    >
        {{ __('Aller au contenu principal') }}
    </a>

    {{--
        `collapsible` (not the deprecated `stashable`) gives us both behaviours
        the spec asks for: a rail that collapses on desktop, and an overlay
        drawer under `lg` driven by the header hamburger (§14, §16).
    --}}
    <flux:sidebar
        sticky
        collapsible
        class="border-e border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
    >
        <flux:sidebar.header>
            <x-brand :href="route('dashboard')" wire:navigate />
            <flux:sidebar.collapse class="max-lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.item
                icon="home"
                :href="route('dashboard')"
                :current="request()->routeIs('dashboard')"
                wire:navigate
            >
                {{ __('navigation.dashboard') }}
            </flux:sidebar.item>

            <flux:sidebar.group :heading="__('navigation.groups.workflow')" expandable :expanded="true">
                <flux:sidebar.item icon="folder" :href="route('projects.index')" :current="request()->routeIs('projects.*')" wire:navigate>
                    {{ __('navigation.projects') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="document-text" :href="route('documents.index')" :current="request()->routeIs('documents.*')" wire:navigate>
                    {{ __('navigation.documents') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="eye" :href="route('reviews.index')" :current="request()->routeIs('reviews.*')" wire:navigate>
                    {{ __('navigation.reviews') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="check-badge" :href="route('approvals.index')" :current="request()->routeIs('approvals.*')" wire:navigate>
                    {{ __('navigation.approvals') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="clipboard-document-check" :href="route('tasks.index')" :current="request()->routeIs('tasks.*')" wire:navigate>
                    {{ __('navigation.tasks') }}
                </flux:sidebar.item>
            </flux:sidebar.group>

            <flux:sidebar.group :heading="__('navigation.groups.analysis')" expandable :expanded="true">
                <flux:sidebar.item icon="chart-bar" :href="route('reports.index')" :current="request()->routeIs('reports.*')" wire:navigate>
                    {{ __('navigation.reports') }}
                </flux:sidebar.item>

                <flux:sidebar.item
                    icon="bell"
                    :href="route('notifications.index')"
                    :current="request()->routeIs('notifications.*')"
                    :badge="($unread = auth()->user()->unreadNotifications()->count()) ? $unread : null"
                    badge-color="red"
                    wire:navigate
                >
                    {{ __('navigation.notifications') }}
                </flux:sidebar.item>
            </flux:sidebar.group>

            @canany([
                \App\Support\Permissions::USERS_MANAGE,
                \App\Support\Permissions::SETTINGS_MANAGE,
                \App\Support\Permissions::DISCIPLINES_MANAGE,
                \App\Support\Permissions::WORKFLOWS_MANAGE,
            ])
                <flux:sidebar.group :heading="__('navigation.groups.admin')" expandable :expanded="request()->routeIs('admin.*')">
                    @can(\App\Support\Permissions::USERS_MANAGE)
                        <flux:sidebar.item icon="users" :href="route('admin.users')" :current="request()->routeIs('admin.users')" wire:navigate>
                            {{ __('navigation.admin.users') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="key" :href="route('admin.roles')" :current="request()->routeIs('admin.roles')" wire:navigate>
                            {{ __('navigation.admin.roles') }}
                        </flux:sidebar.item>
                    @endcan

                    @can(\App\Support\Permissions::DISCIPLINES_MANAGE)
                        <flux:sidebar.item icon="squares-2x2" :href="route('admin.disciplines')" :current="request()->routeIs('admin.disciplines')" wire:navigate>
                            {{ __('navigation.admin.disciplines') }}
                        </flux:sidebar.item>
                    @endcan

                    @can(\App\Support\Permissions::WORKFLOWS_MANAGE)
                        <flux:sidebar.item icon="check-badge" :href="route('admin.workflows')" :current="request()->routeIs('admin.workflows')" wire:navigate>
                            {{ __('navigation.admin.workflows') }}
                        </flux:sidebar.item>
                    @endcan

                    @can(\App\Support\Permissions::SETTINGS_MANAGE)
                        <flux:sidebar.item icon="cog-6-tooth" :href="route('admin.settings')" :current="request()->routeIs('admin.settings')" wire:navigate>
                            {{ __('navigation.settings') }}
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
            @endcanany
        </flux:sidebar.nav>

        <flux:sidebar.spacer />

        <flux:sidebar.nav>
            <flux:sidebar.item icon="user-circle" :href="route('profile')" :current="request()->routeIs('profile')" wire:navigate>
                {{ __('navigation.profile') }}
            </flux:sidebar.item>
        </flux:sidebar.nav>
    </flux:sidebar>

    <flux:header
        sticky
        class="border-b border-zinc-200 bg-white/90 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/90"
    >
        <flux:sidebar.toggle class="lg:hidden" icon="bars-3" inset="left" />

        {{-- Global search (§31) lands in the Advanced Search phase; inert until then. --}}
        <flux:input
            variant="filled"
            :placeholder="__('navigation.search_placeholder')"
            icon="magnifying-glass"
            class="max-w-md max-sm:hidden"
            disabled
        />

        <flux:spacer />

        <livewire:notifications.bell />

        <flux:dropdown position="bottom" align="end">
            <flux:profile
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
                :avatar="auth()->user()->avatarUrl()"
                circle
            />

            <flux:menu>
                <div class="px-2 py-1.5">
                    <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ auth()->user()->name }}</div>
                    <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ auth()->user()->email }}</div>

                    @if ($role = auth()->user()->primaryRole())
                        <flux:badge size="sm" color="zinc" class="mt-1.5">
                            {{ __('enums.role.'.$role) }}
                        </flux:badge>
                    @endif
                </div>

                <flux:menu.separator />

                <flux:menu.item icon="user-circle" :href="route('profile')" wire:navigate>
                    {{ __('navigation.profile') }}
                </flux:menu.item>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        {{ __('navigation.logout') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    <flux:main id="main-content" container>
        {{ $slot }}
    </flux:main>

    {{-- Global toast host: any component may fire Flux::toast() (§37). --}}
    <flux:toast position="bottom end" />

    {{-- Replays a session flash as a toast after a redirect, so an action that
         navigates still confirms itself to the user. --}}
    @if (session('toast'))
        <div
            x-data
            x-init="$dispatch('toast-show', { text: @js(session('toast')), variant: 'success' })"
            aria-live="polite"
            class="sr-only"
        >{{ session('toast') }}</div>
    @endif

    @fluxScripts
</body>
</html>
