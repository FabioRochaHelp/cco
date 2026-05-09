<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body
        class="cco-shell min-h-screen antialiased"
        @auth
            data-broadcast-operations="{{ auth()->user()?->hasOperationalAbility('dispatch.view') ? '1' : '0' }}"
        @endauth
    >
        @auth
            @if (auth()->user()?->hasOperationalAbility('dispatch.view'))
                <livewire:operations.operational-call-intake-bridge />
            @endif
        @endauth
        <flux:sidebar sticky :collapsible="true" class="cco-sidebar border-e border-slate-200/90 bg-white shadow-sm shadow-slate-900/5 dark:border-zinc-800/80 dark:bg-zinc-950 dark:shadow-none">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse
                    class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2"
                    :tooltip="__('Recolher ou expandir o menu')"
                />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                @can('viewAny', \App\Models\User::class)
                    <flux:sidebar.group :heading="__('Administração')" class="grid">
                        <flux:sidebar.item
                            icon="users"
                            :href="route('operations.admin.users')"
                            :current="request()->routeIs('operations.admin.users')"
                            wire:navigate
                        >
                            {{ __('Usuários') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan

                <flux:sidebar.group :heading="__('Operações')" class="grid">
                    <flux:sidebar.item
                        icon="radio"
                        :href="route('operations.dispatch')"
                        :current="request()->routeIs('operations.dispatch')"
                        wire:navigate
                    >
                        {{ __('Central operacional') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item
                        icon="rectangle-stack"
                        :href="route('operations.incidents.index')"
                        :current="request()->routeIs('operations.incidents.index') || request()->routeIs('operations.incidents.show') || request()->routeIs('operations.incidents.nurse-report') || request()->routeIs('operations.incidents.victims.*')"
                        wire:navigate
                    >
                        {{ __('Ocorrências') }}
                    </flux:sidebar.item>
                    @if (auth()->user()?->hasOperationalAbility('incident.create'))
                        <flux:sidebar.item
                            icon="plus-circle"
                            :href="route('operations.incidents.start')"
                            :current="request()->routeIs('operations.incidents.start') || request()->routeIs('operations.incidents.create')"
                            wire:navigate
                        >
                            {{ __('Nova ocorrência') }}
                        </flux:sidebar.item>
                    @endif
                    <flux:sidebar.item
                        icon="truck"
                        :href="route('operations.fleet')"
                        :current="request()->routeIs('operations.fleet')"
                        wire:navigate
                    >
                        {{ __('Turnos e viaturas') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                @if (auth()->user()?->hasOperationalAbility('dispatch.view'))
                    <flux:sidebar.group
                        expandable
                        :expanded="request()->routeIs('operations.cadastro.*') || request()->routeIs('operations.catalog.*')"
                        icon="folder-open"
                        :heading="__('Cadastro')"
                        class="grid"
                    >
                        @if (auth()->user()?->isOperationalCentral())
                            <flux:sidebar.item
                                icon="building-office-2"
                                :href="route('operations.cadastro.bases')"
                                :current="request()->routeIs('operations.cadastro.bases')"
                                wire:navigate
                            >
                                {{ __('Bases') }}
                            </flux:sidebar.item>
                        @endif
                        <flux:sidebar.item
                            icon="cube"
                            :href="route('operations.cadastro.vehicles')"
                            :current="request()->routeIs('operations.cadastro.vehicles') || request()->routeIs('operations.catalog.vehicles')"
                            wire:navigate
                        >
                            {{ __('Viaturas') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item
                            icon="users"
                            :href="route('operations.cadastro.staff')"
                            :current="request()->routeIs('operations.cadastro.staff') || request()->routeIs('operations.catalog.staff')"
                            wire:navigate
                        >
                            {{ __('Efetivo') }}
                        </flux:sidebar.item>
                        @can('viewAny', \App\Models\Shift::class)
                            <flux:sidebar.item
                                icon="clock"
                                :href="route('operations.cadastro.shifts')"
                                :current="request()->routeIs('operations.cadastro.shifts')"
                                wire:navigate
                            >
                                {{ __('Turnos de serviço') }}
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endif

                @if (auth()->user()?->isOperationalCentral())
                    <flux:sidebar.group
                        expandable
                        :expanded="request()->routeIs('operations.parameters.*')"
                        icon="adjustments-horizontal"
                        :heading="__('Parâmetros da Ocorrência')"
                        class="grid"
                    >
                        <flux:sidebar.item
                            icon="squares-2x2"
                            :href="route('operations.parameters.accessories')"
                            :current="request()->routeIs('operations.parameters.accessories')"
                            wire:navigate
                        >
                            {{ __('Acessório') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item
                            icon="squares-2x2"
                            :href="route('operations.parameters.operational-supports')"
                            :current="request()->routeIs('operations.parameters.operational-supports')"
                            wire:navigate
                        >
                            {{ __('Apoio') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item
                            icon="squares-2x2"
                            :href="route('operations.parameters.care-locals')"
                            :current="request()->routeIs('operations.parameters.care-locals')"
                            wire:navigate
                        >
                            {{ __('Local') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item
                            icon="squares-2x2"
                            :href="route('operations.parameters.natures')"
                            :current="request()->routeIs('operations.parameters.natures')"
                            wire:navigate
                        >
                            {{ __('Natureza') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item
                            icon="squares-2x2"
                            :href="route('operations.parameters.procedures')"
                            :current="request()->routeIs('operations.parameters.procedures')"
                            wire:navigate
                        >
                            {{ __('Procedimento') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item
                            icon="squares-2x2"
                            :href="route('operations.parameters.victim-types')"
                            :current="request()->routeIs('operations.parameters.victim-types')"
                            wire:navigate
                        >
                            {{ __('Tipo Vitima') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item
                            icon="squares-2x2"
                            :href="route('operations.parameters.health-units')"
                            :current="request()->routeIs('operations.parameters.health-units')"
                            wire:navigate
                        >
                            {{ __('Unidades de Atendimento') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            @auth
                <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
            @endauth
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="sticky top-0 z-30 border-b border-slate-200/90 bg-white/95 backdrop-blur-xl dark:border-cyan-500/10 dark:bg-slate-950/90 lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            @auth
                <flux:dropdown position="top" align="end">
                    <flux:profile
                        :initials="auth()->user()->initials()"
                        icon-trailing="chevron-down"
                    />

                    <flux:menu>
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <flux:avatar
                                        :name="auth()->user()->name"
                                        :initials="auth()->user()->initials()"
                                    />

                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                        <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.heading>{{ __('Tema') }}</flux:menu.heading>
                        <flux:menu.radio.group>
                            <flux:menu.item as="button" type="button" icon="sun" x-data x-on:click.prevent="window.Flux.applyAppearance('light')">
                                {{ __('Claro') }}
                            </flux:menu.item>
                            <flux:menu.item as="button" type="button" icon="moon" x-data x-on:click.prevent="window.Flux.applyAppearance('dark')">
                                {{ __('Escuro') }}
                            </flux:menu.item>
                            <flux:menu.item as="button" type="button" icon="computer-desktop" x-data x-on:click.prevent="window.Flux.applyAppearance('system')">
                                {{ __('Sistema') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                {{ __('Settings') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item
                                as="button"
                                type="submit"
                                icon="arrow-right-start-on-rectangle"
                                class="w-full cursor-pointer"
                                data-test="logout-button"
                            >
                                {{ __('Log out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            @else
                <flux:dropdown position="top" align="end">
                    <flux:button variant="ghost" size="sm" icon="paint-brush" inset="top bottom" />
                    <flux:menu>
                        <flux:menu.heading>{{ __('Tema') }}</flux:menu.heading>
                        <flux:menu.radio.group>
                            <flux:menu.item as="button" type="button" icon="sun" x-data x-on:click.prevent="window.Flux.applyAppearance('light')">
                                {{ __('Claro') }}
                            </flux:menu.item>
                            <flux:menu.item as="button" type="button" icon="moon" x-data x-on:click.prevent="window.Flux.applyAppearance('dark')">
                                {{ __('Escuro') }}
                            </flux:menu.item>
                            <flux:menu.item as="button" type="button" icon="computer-desktop" x-data x-on:click.prevent="window.Flux.applyAppearance('system')">
                                {{ __('Sistema') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>
                    </flux:menu>
                </flux:dropdown>
            @endauth
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
