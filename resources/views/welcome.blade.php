<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="cco-shell antialiased">

        @auth
            <script>window.location.href = "{{ route('dashboard') }}";</script>
        @endauth

        <div class="flex min-h-screen flex-col lg:grid lg:grid-cols-2">

            {{-- ── Coluna esquerda: apresentação ───────────────────────────────── --}}
            <aside class="relative flex flex-col justify-between overflow-hidden bg-slate-900 px-8 py-10 lg:px-12 lg:py-14">

                {{-- Gradiente decorativo de fundo --}}
                <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                    <div class="absolute -top-32 -left-20 h-[480px] w-[480px] rounded-full bg-cyan-500/10 blur-3xl"></div>
                    <div class="absolute bottom-0 right-0 h-64 w-64 rounded-full bg-cyan-600/[.08] blur-2xl"></div>
                </div>

                {{-- Cabeçalho: logo + nome --}}
                <div class="relative z-10">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-400 to-cyan-600 shadow-lg shadow-cyan-500/30">
                            <x-app-logo-icon class="size-6 fill-current text-slate-950" />
                        </div>
                        <span class="text-xl font-bold tracking-tight text-white">{{ config('app.name', 'CCO') }}</span>
                    </div>
                </div>

                {{-- Conteúdo central --}}
                <div class="relative z-10 my-10 space-y-8">
                    <div class="space-y-3">
                        <p class="text-xs font-semibold uppercase tracking-widest text-cyan-400">{{ __('Sistema de Gestão Operacional') }}</p>
                        <h1 class="text-3xl font-bold leading-tight tracking-tight text-white lg:text-4xl">
                            {{ __('Central de Operações e Despacho') }}
                        </h1>
                        <p class="text-base leading-relaxed text-slate-400">
                            {{ __('Plataforma integrada para coordenação de emergências, rastreamento de viaturas e gestão de ocorrências em tempo real.') }}
                        </p>
                    </div>

                    {{-- Destaques --}}
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 ring-1 ring-cyan-500/20">
                                <flux:icon.radio class="size-4 text-cyan-400" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-white">{{ __('Despacho em tempo real') }}</p>
                                <p class="text-sm text-slate-400">{{ __('Gerencie filas, turnos e empenho de viaturas com atualização contínua.') }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 ring-1 ring-cyan-500/20">
                                <flux:icon.map class="size-4 text-cyan-400" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-white">{{ __('Rastreamento de viaturas') }}</p>
                                <p class="text-sm text-slate-400">{{ __('Mapa tático com posições e status de todas as unidades operacionais.') }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 ring-1 ring-cyan-500/20">
                                <flux:icon.rectangle-stack class="size-4 text-cyan-400" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-white">{{ __('Gestão de ocorrências') }}</p>
                                <p class="text-sm text-slate-400">{{ __('Registro, kanban operacional e relatórios por tipo e modalidade de atendimento.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Rodapé da coluna --}}
                <div class="relative z-10">
                    <p class="text-xs text-slate-600">
                        &copy; {{ date('Y') }} {{ config('app.name', 'CCO') }}. {{ __('Todos os direitos reservados.') }}
                    </p>
                </div>
            </aside>

            {{-- ── Coluna direita: formulário de login ─────────────────────────── --}}
            <main class="flex items-center justify-center bg-white px-8 py-12 dark:bg-slate-950">
                <div class="w-full max-w-sm space-y-7">

                    {{-- Cabeçalho do form --}}
                    <div class="space-y-1">
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
                            {{ __('Acesse o sistema') }}
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ __('Informe suas credenciais para continuar.') }}
                        </p>
                    </div>

                    {{-- Status de sessão (ex: após redefinição de senha) --}}
                    <x-auth-session-status :status="session('status')" />

                    {{-- Formulário --}}
                    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                        @csrf

                        <flux:input
                            name="email"
                            :label="__('E-mail')"
                            :value="old('email')"
                            type="email"
                            required
                            autofocus
                            autocomplete="email"
                            placeholder="voce@exemplo.com.br"
                            :invalid="$errors->has('email')"
                        />
                        @error('email')
                            <p class="-mt-3 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <div class="relative">
                            <flux:input
                                name="password"
                                :label="__('Senha')"
                                type="password"
                                required
                                autocomplete="current-password"
                                :placeholder="__('Sua senha')"
                                viewable
                                :invalid="$errors->has('password')"
                            />
                            @if (Route::has('password.request'))
                                <flux:link class="absolute top-0 end-0 text-sm" :href="route('password.request')">
                                    {{ __('Esqueceu a senha?') }}
                                </flux:link>
                            @endif
                        </div>
                        @error('password')
                            <p class="-mt-3 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <flux:checkbox
                            name="remember"
                            :label="__('Manter conectado')"
                            :checked="old('remember')"
                        />

                        <flux:button variant="primary" type="submit" class="w-full">
                            {{ __('Entrar') }}
                        </flux:button>
                    </form>
                </div>
            </main>

        </div>

        @fluxScripts
    </body>
</html>
