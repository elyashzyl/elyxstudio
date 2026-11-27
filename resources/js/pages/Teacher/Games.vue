<template>
    <Head title="Games" />

    <div
        v-if="flash?.success"
        class="gl-fade-in mb-6 rounded-xl px-4 py-3 text-sm"
        style="
            background: var(--gl-success-bg);
            color: var(--gl-success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        "
    >
        {{ flash.success }}
    </div>

    <!-- Header -->
    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p
                class="text-xs font-semibold tracking-widest uppercase"
                style="color: var(--gl-text-muted)"
            >
                Arcade
            </p>
            <h1
                class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                Learning Games
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                {{ localGames.length }} games · {{ totalCards }} cards
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <div class="relative">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2"
                    :stroke-width="2"
                    style="color: var(--gl-text-muted)"
                />
                <input
                    v-model="query"
                    type="text"
                    placeholder="Search"
                    class="rounded-full border py-2.5 pr-4 pl-9 text-sm transition-all outline-none focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                    style="
                        background: var(--gl-surface);
                        color: var(--gl-text-primary);
                        border-color: var(--gl-border);
                        width: 180px;
                    "
                />
            </div>
            <select
                v-if="props.gradeLevels?.length"
                v-model="gradeFilter"
                class="rounded-full border py-2 px-3 text-xs font-semibold outline-none transition-all"
                style="
                    background: var(--gl-surface);
                    color: var(--gl-text-primary);
                    border-color: var(--gl-border);
                "
            >
                <option value="">All Grades</option>
                <option v-for="gl in props.gradeLevels" :key="gl.id" :value="gl.name">{{ gl.name }}</option>
            </select>
            <select
                v-if="props.quarters?.length"
                v-model="quarterFilter"
                class="rounded-full border py-2 px-3 text-xs font-semibold outline-none transition-all"
                style="
                    background: var(--gl-surface);
                    color: var(--gl-text-primary);
                    border-color: var(--gl-border);
                "
            >
                <option value="">All Quarters</option>
                <option v-for="q in props.quarters" :key="q.id" :value="q.id">{{ q.name }}</option>
            </select>
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                <button
                    v-for="t in visTabs"
                    :key="t.value"
                    @click="visTab = t.value"
                    class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-2 text-xs font-bold transition-all shrink-0"
                    :style="
                        visTab === t.value
                            ? { background: '#1F2937', color: '#fff' }
                            : {
                                  background: 'var(--gl-surface)',
                                  border: '1px solid var(--gl-border)',
                                  color: 'var(--gl-text-secondary)',
                              }
                    "
                >
                    <Zap v-if="t.value === 'live'" class="h-3 w-3 text-emerald-400 fill-current" />
                    <span>{{ t.label }}</span>
                    <span
                        v-if="t.count !== undefined"
                        class="rounded-full px-1.5 py-0.2 text-[10px] font-black"
                        :style="
                            visTab === t.value
                                ? { background: 'rgba(255, 255, 255, 0.2)', color: '#fff' }
                                : { background: 'var(--gl-surface-2)', color: 'var(--gl-text-muted)' }
                        "
                    >
                        {{ t.count }}
                    </span>
                </button>
            </div>
            <Link
                href="/teacher/games/live/create"
                class="flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold text-white shadow-md transition-all hover:scale-105 active:scale-95"
                style="
                    background: linear-gradient(135deg, #10b981, #059669);
                    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
                "
                title="Create a new live multiplayer match"
            >
                <Zap class="h-4 w-4" />
                <span class="hidden sm:inline">Live Arena</span>
            </Link>
            <button
                @click="openCreate"
                class="flex h-10 w-10 items-center justify-center rounded-xl text-white transition-all hover:scale-105"
                style="
                    background: linear-gradient(135deg, #ff6a45, #f94f2b);
                    box-shadow: 0 4px 14px rgba(249, 79, 43, 0.4);
                "
                title="New game"
            >
                <Plus class="h-5 w-5" :stroke-width="2.5" />
            </button>
        </div>
    </div>

    <!-- Active Live Arena Rooms Banner -->
    <div
        v-if="activeRoomsList.length > 0"
        class="mb-6 space-y-3"
    >
        <div
            v-for="room in activeRoomsList"
            :key="room.id"
            class="gl-fade-in relative overflow-hidden rounded-3xl p-5 sm:p-6"
            style="
                background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(59, 130, 246, 0.08));
                border: 1px solid rgba(16, 185, 129, 0.35);
                box-shadow: 0 4px 20px rgba(16, 185, 129, 0.12);
            "
        >
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-white shadow-md"
                        style="background: linear-gradient(135deg, #10b981, #059669);"
                    >
                        <Zap class="h-6 w-6 fill-current animate-bounce" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-[11px] font-black text-emerald-600">
                                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-ping"></span>
                                ACTIVE LIVE MATCH ARENA
                            </span>
                            <span
                                v-if="room.status === 'waiting'"
                                class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold text-amber-600"
                            >
                                Lobby (Waiting for Students)
                            </span>
                            <span
                                v-else
                                class="rounded-full bg-blue-500/15 px-2 py-0.5 text-[10px] font-bold text-blue-600"
                            >
                                Questions In Progress
                            </span>
                        </div>
                        <h2 class="mt-1 text-base font-extrabold text-[var(--gl-text-primary)] sm:text-lg">
                            {{ room.game?.title || 'Live Game Match' }}
                        </h2>
                        <p class="text-xs text-[var(--gl-text-secondary)]">
                            <span v-if="isSuperadmin && room.teacher?.name">Hosted by <strong>{{ room.teacher.name }}</strong> · </span>
                            {{ room.game?.subject || 'Arcade' }} · Grade {{ room.game?.grade || 'All' }}
                            · <strong class="text-emerald-600">{{ room.participants_count ?? 0 }} student(s) joined</strong>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- PIN display with copy -->
                    <div
                        class="flex items-center gap-2.5 rounded-2xl border px-4 py-2 sm:px-5 sm:py-2.5"
                        style="background: var(--gl-surface); border-color: rgba(16, 185, 129, 0.4);"
                    >
                        <div>
                            <p class="text-[10px] font-extrabold tracking-wider uppercase text-emerald-600">
                                Share PIN With Students
                            </p>
                            <p class="font-mono text-xl font-black tracking-widest text-[var(--gl-text-primary)] sm:text-2xl">
                                {{ room.pin }}
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="copyRoomPin(room.pin)"
                            class="flex h-8 w-8 items-center justify-center rounded-xl transition-all hover:bg-emerald-500/20 active:scale-95 text-emerald-600"
                            title="Copy PIN"
                        >
                            <Check v-if="copiedPin === room.pin" class="h-4 w-4" />
                            <Copy v-else class="h-4 w-4" />
                        </button>
                    </div>

                    <Link
                        :href="`/teacher/games/rooms/${room.pin}/host`"
                        class="flex items-center gap-2 rounded-2xl px-5 py-3 text-sm font-extrabold text-white shadow-lg transition-all hover:scale-105 active:scale-95"
                        style="background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);"
                    >
                        <Play class="h-4 w-4 fill-current" />
                        <span>Resume Host Screen</span>
                    </Link>

                    <a
                        :href="`/student/games/rooms/${encodeURIComponent(room.pin)}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center gap-2 rounded-2xl border border-indigo-500/30 bg-indigo-500/10 px-4 py-3 text-xs font-bold text-indigo-600 dark:text-indigo-400 transition-all hover:bg-indigo-500/20 active:scale-95"
                        title="Preview student screen in a new tab"
                    >
                        <ExternalLink class="h-4 w-4" />
                        <span>View Player Screen</span>
                    </a>

                    <button
                        type="button"
                        @click="openTop10ForRoom(room)"
                        class="flex items-center gap-2 rounded-2xl border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-xs font-bold text-amber-600 dark:text-amber-400 transition-all hover:bg-amber-500/20 active:scale-95 shadow-sm"
                        title="View Top 10 Live Standings for this session"
                    >
                        <Trophy class="h-4 w-4 text-amber-500" />
                        <span>Top 10</span>
                    </button>

                    <button
                        type="button"
                        @click="deleteLiveRoom(room)"
                        class="flex items-center gap-1.5 rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-xs font-bold text-red-600 transition-all hover:bg-red-500/20 active:scale-95"
                        title="Delete and close this live arena match session"
                    >
                        <Trash2 class="h-4 w-4" />
                        <span>Delete Session</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Completed Live Arena Sessions (shown when on Live Arena tab) -->
    <div
        v-if="visTab === 'live' && recentFinishedRoomsList.length > 0"
        class="mb-6 space-y-3"
    >
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <Trophy class="h-4 w-4 text-amber-500" />
                <h3 class="text-xs font-black uppercase tracking-wider text-[var(--gl-text-secondary)]">
                    Completed Live Arena Sessions ({{ recentFinishedRoomsList.length }})
                </h3>
            </div>
            <span class="text-[11px] text-[var(--gl-text-muted)]">
                Finished multiplayer matches & standings
            </span>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="room in recentFinishedRoomsList"
                :key="room.id"
                class="rounded-2xl border p-4 shadow-sm transition-all hover:border-amber-500/40"
                style="background: var(--gl-surface); border-color: var(--gl-border);"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-500/10 px-2 py-0.5 text-[10px] font-bold text-slate-500 dark:text-slate-400">
                            FINISHED
                        </span>
                        <h4 class="mt-1 truncate text-sm font-extrabold text-[var(--gl-text-primary)]">
                            {{ room.title }}
                        </h4>
                        <p class="text-[11px] text-[var(--gl-text-muted)]">
                            {{ room.created_at }} · {{ room.total_participants }} player(s)
                        </p>
                    </div>
                    <div class="rounded-xl bg-[var(--gl-surface-2)] px-2.5 py-1 text-center border border-[var(--gl-border)]">
                        <span class="block text-[9px] font-bold uppercase text-[var(--gl-text-muted)]">PIN</span>
                        <span class="font-mono text-xs font-black text-emerald-600 dark:text-emerald-400">{{ room.pin }}</span>
                    </div>
                </div>

                <div v-if="room.winner" class="mt-3 flex items-center gap-2 rounded-xl bg-amber-500/10 px-2.5 py-1.5 text-xs text-amber-700 dark:text-amber-400">
                    <Crown class="h-3.5 w-3.5 text-amber-500 shrink-0" />
                    <span class="truncate font-semibold">1st: <strong>{{ room.winner.name }}</strong> ({{ room.winner.score }} pts)</span>
                </div>

                <div class="mt-3 flex items-center justify-between gap-2 border-t pt-2.5" style="border-color: var(--gl-border);">
                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            @click="openTop10ForRoom(room)"
                            class="inline-flex items-center gap-1 rounded-xl border border-amber-500/40 bg-amber-500/10 px-2.5 py-1.5 text-xs font-bold text-amber-600 dark:text-amber-400 transition-all hover:bg-amber-500/20 active:scale-95 shadow-sm"
                            title="View Top 10 Standings"
                        >
                            <Trophy class="h-3.5 w-3.5 text-amber-500" />
                            <span>Top 10</span>
                        </button>
                        <Link
                            :href="`/teacher/games/rooms/${room.pin}/host`"
                            class="inline-flex items-center gap-1 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-2.5 py-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 transition-all hover:bg-emerald-500/20 active:scale-95"
                            title="View Podium Screen"
                        >
                            <Crown class="h-3.5 w-3.5" />
                            <span>Podium</span>
                        </Link>
                    </div>

                    <button
                        type="button"
                        @click="deleteLiveRoom(room)"
                        class="inline-flex items-center gap-1 rounded-xl border border-red-500/30 bg-red-500/10 px-2.5 py-1.5 text-xs font-bold text-red-600 transition-all hover:bg-red-500/20 active:scale-95"
                        title="Delete this live arena session"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                        <span>Delete Session</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div
            class="rounded-2xl p-4"
            style="
                background: rgba(34, 211, 238, 0.08);
                border: 1px solid rgba(34, 211, 238, 0.18);
            "
        >
            <div
                class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                style="background: rgba(34, 211, 238, 0.16)"
            >
                <Gamepad2
                    class="h-[18px] w-[18px]"
                    style="color: #22d3ee"
                    :stroke-width="2"
                />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ localGames.length }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Total games
            </p>
        </div>
        <div
            class="rounded-2xl p-4"
            style="
                background: rgba(16, 185, 129, 0.08);
                border: 1px solid rgba(16, 185, 129, 0.18);
            "
        >
            <div
                class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                style="background: rgba(16, 185, 129, 0.16)"
            >
                <Eye
                    class="h-[18px] w-[18px]"
                    style="color: var(--gl-success)"
                    :stroke-width="2"
                />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ visibleCount }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Visible
            </p>
        </div>
        <div
            class="rounded-2xl p-4"
            style="
                background: rgba(251, 191, 36, 0.1);
                border: 1px solid rgba(251, 191, 36, 0.2);
            "
        >
            <div
                class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                style="background: rgba(251, 191, 36, 0.2)"
            >
                <EyeOff
                    class="h-[18px] w-[18px]"
                    style="color: var(--gl-accent)"
                    :stroke-width="2"
                />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ hiddenCount }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Hidden
            </p>
        </div>
        <div
            class="rounded-2xl p-4"
            style="
                background: rgba(124, 58, 237, 0.08);
                border: 1px solid rgba(124, 58, 237, 0.18);
            "
        >
            <div
                class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                style="background: rgba(124, 58, 237, 0.16)"
            >
                <Zap
                    class="h-[18px] w-[18px]"
                    style="color: var(--gl-secondary)"
                    :stroke-width="2"
                />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ totalXp }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Total XP rewards
            </p>
        </div>
    </div>

    <div
        v-if="!filtered.length"
        class="gl-glow-card flex flex-col items-center justify-center px-8 py-16 text-center"
    >
        <div
            class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl"
            style="background: var(--gl-surface-2)"
        >
            <Gamepad2
                class="h-6 w-6"
                style="color: var(--gl-text-muted)"
                :stroke-width="1.75"
            />
        </div>
        <p class="text-sm font-medium" style="color: var(--gl-text-secondary)">
            {{ localGames.length ? 'No matches' : 'No games yet' }}
        </p>
        <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
            {{
                localGames.length
                    ? 'Try a different search or filter.'
                    : 'Create your first learning game for students.'
            }}
        </p>
        <button
            v-if="!localGames.length"
            @click="openCreate"
            class="mt-4 flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02]"
            style="
                background: linear-gradient(135deg, #10b981, var(--gl-primary));
                box-shadow: 0 0 12px rgba(16, 185, 129, 0.3);
            "
        >
            <Plus class="h-4 w-4" :stroke-width="2" /> New Game
        </button>
    </div>

    <div v-else>
        <label
            class="mb-3 flex cursor-pointer items-center gap-2 text-xs font-medium"
            style="color: var(--gl-text-muted)"
        >
            <input
                type="checkbox"
                :checked="allChecked"
                @change="toggleAll"
                class="gl-check"
            />
            Select all {{ filtered.length }} games
        </label>
        <div
            v-if="selected.length"
            class="mb-3 flex flex-wrap items-center gap-2 rounded-2xl px-4 py-3"
            style="
                background: var(--gl-surface);
                border: 1px solid var(--gl-border);
            "
        >
            <span
                class="text-xs font-semibold"
                style="color: var(--gl-text-primary)"
                >{{ selected.length }} selected</span
            >
            <button
                @click="bulkVisible(false)"
                class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
                style="
                    background: var(--gl-success-bg);
                    color: var(--gl-success);
                    border: 1px solid rgba(16, 185, 129, 0.25);
                "
            >
                <Eye class="h-3.5 w-3.5" :stroke-width="2" /> Show
            </button>
            <button
                @click="bulkVisible(true)"
                class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
                style="
                    background: var(--gl-surface-2);
                    color: var(--gl-text-muted);
                    border: 1px solid var(--gl-border);
                "
            >
                <EyeOff class="h-3.5 w-3.5" :stroke-width="2" /> Hide
            </button>
            <button
                @click="bulkAutoReopen(true)"
                class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
                style="
                    background: var(--gl-success-bg);
                    color: var(--gl-success);
                    border: 1px solid rgba(16, 185, 129, 0.25);
                "
                title="Enable auto-reopen for selected games"
            >
                <RotateCcw class="h-3.5 w-3.5" :stroke-width="2" /> Enable Auto-Reopen
            </button>
            <button
                @click="bulkAutoReopen(false)"
                class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
                style="
                    background: var(--gl-surface-2);
                    color: var(--gl-text-muted);
                    border: 1px solid var(--gl-border);
                "
                title="Disable auto-reopen for selected games"
            >
                <RotateCcw class="h-3.5 w-3.5" :stroke-width="2" /> Disable Auto-Reopen
            </button>
            <button
                @click="bulkDelete"
                class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
                style="
                    background: var(--gl-danger-bg);
                    color: var(--gl-danger);
                    border: 1px solid rgba(239, 68, 68, 0.25);
                "
            >
                <Trash2 class="h-3.5 w-3.5" :stroke-width="2" /> Delete
            </button>
            <button
                @click="selected = []"
                class="ml-auto text-xs font-medium transition-colors hover:opacity-80"
                style="color: var(--gl-text-muted)"
            >
                Clear
            </button>
        </div>
        <div
            class="mb-1 hidden grid-cols-[minmax(0,1fr)_110px_64px_64px_88px_110px_112px] items-center gap-3 px-4 pb-2 text-[10px] font-semibold tracking-wider uppercase sm:grid"
            style="color: var(--gl-text-muted)"
        >
            <span>Title</span><span>Subject</span
            ><span class="text-center">Cards</span
            ><span class="text-center">XP</span
            ><span class="text-center">Players</span
            ><span class="text-center">Auto-Reopen</span
            ><span class="text-right">Actions</span>
        </div>
        <div class="space-y-2">
            <div v-for="g in filtered" :key="g.id"
                class="grid cursor-pointer grid-cols-[auto_1fr_auto] items-center gap-3 rounded-2xl px-4 py-3 transition-all sm:grid-cols-[minmax(0,1fr)_110px_64px_64px_88px_110px_112px]"
                :style="g.hidden
                    ? { background: 'var(--gl-surface)', border: '1px solid var(--gl-border)', opacity: 0.65 }
                    : { background: 'rgba(34,211,238,0.05)', border: '1px solid rgba(34,211,238,0.16)' }"
                @click="openEdit(g)">

                <div class="flex min-w-0 items-center gap-3">
                    <input
                        type="checkbox"
                        :value="g.id"
                        v-model="selected"
                        @click.stop
                        class="gl-check"
                        :title="'Select ' + g.title"
                    />
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                        style="
                            background: linear-gradient(
                                135deg,
                                #22d3ee,
                                #3b82f6
                            );
                            box-shadow: 0 0 10px rgba(34, 211, 238, 0.3);
                        "
                    >
                        <Gamepad2
                            class="h-4 w-4 text-white"
                            :stroke-width="2"
                        />
                    </div>
                    <div class="min-w-0">
                        <p
                            class="truncate text-xs font-semibold flex flex-wrap items-center gap-1.5"
                            style="color: var(--gl-text-primary)"
                        >
                            <span>{{ g.title }}</span>
                            <span
                                v-if="activeRoomsMap[g.id]"
                                class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-black text-emerald-600"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                LIVE · PIN: {{ activeRoomsMap[g.id].pin }}
                            </span>
                            <span
                                v-else-if="isLiveGame(g)"
                                class="inline-flex items-center gap-1 rounded-full bg-indigo-500/15 px-2 py-0.5 text-[10px] font-black text-indigo-600 dark:text-indigo-400"
                            >
                                <Zap class="h-3 w-3 fill-current" />
                                LIVE ARENA
                            </span>
                            <span
                                v-if="g.hidden && !isLiveGame(g)"
                                class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-muted);
                                "
                                >Hidden</span
                            >
                        </p>
                        <p
                            class="truncate text-[11px]"
                            style="color: var(--gl-text-muted)"
                        >
                            <span v-if="isSuperadmin && g.teacher?.name">{{ g.teacher.name }} · </span>
                            {{ g.grade || 'All grades' }} ·
                            {{ TYPE_LABELS[g.type] ?? g.type }}
                            <span
                                v-if="g.auto_reopen"
                                class="ml-1 inline-flex items-center rounded-full bg-emerald-500/15 px-1.5 py-0.2 text-[9px] font-bold text-emerald-600 dark:text-emerald-400 border border-emerald-500/25"
                                title="Auto-reopen enabled: student retake requests are approved immediately"
                            >
                                <Zap class="mr-1 h-3 w-3" :stroke-width="2.5" />
                                Auto-Reopen
                            </span>
                        </p>
                        <button
                            type="button"
                            @click.stop="toggleAutoReopen(g)"
                            class="inline-flex sm:hidden items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold border transition-all mt-1"
                            :style="g.auto_reopen
                                ? { background: 'rgba(16, 185, 129, 0.15)', color: 'var(--gl-success)', borderColor: 'rgba(16, 185, 129, 0.4)' }
                                : { background: 'var(--gl-surface-2)', color: 'var(--gl-text-muted)', borderColor: 'var(--gl-border)' }"
                            :title="g.auto_reopen ? 'Auto-reopen is ON: click to turn OFF' : 'Auto-reopen is OFF: click to turn ON'"
                        >
                            <RotateCcw class="h-2.5 w-2.5" :stroke-width="2.5" />
                            <span>Auto-Reopen: <strong>{{ g.auto_reopen ? 'ON' : 'OFF' }}</strong></span>
                        </button>
                    </div>
                </div>
                <span
                    class="hidden truncate text-xs sm:block"
                    style="color: var(--gl-text-secondary)"
                    >{{ g.subject }}</span
                >
                <span
                    class="hidden text-center text-xs font-semibold tabular-nums sm:block"
                    style="color: var(--gl-text-primary)"
                    >{{ g.cards?.length ?? 0 }}</span
                >
                <span
                    class="hidden text-center text-xs font-extrabold tabular-nums sm:block"
                    style="color: var(--gl-accent)"
                    >{{ g.xp_reward }}</span
                >
                <div class="hidden justify-center sm:flex">
                    <button
                        type="button"
                        @click.stop="openPlayersModal(g)"
                        class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[11px] font-bold transition-all hover:scale-105 active:scale-95"
                        :style="
                            g.players_count > 0
                                ? {
                                      background: 'rgba(16, 185, 129, 0.12)',
                                      color: 'var(--gl-success)',
                                      border: '1px solid rgba(16, 185, 129, 0.3)',
                                  }
                                : {
                                      background: 'var(--gl-surface-2)',
                                      color: 'var(--gl-text-muted)',
                                      border: '1px solid var(--gl-border)',
                                  }
                        "
                        :title="'View players and attempts for ' + g.title"
                    >
                        <Users class="h-3 w-3" :stroke-width="2" />
                        <span>{{ g.players_count ?? 0 }}</span>
                        <span
                            v-if="g.pending_reopen_requests_count > 0"
                            class="ml-1 inline-flex items-center rounded-full bg-amber-500/20 px-1.5 py-0.2 text-[10px] font-black text-amber-600 dark:text-amber-400 border border-amber-500/30"
                            :title="`${g.pending_reopen_requests_count} student(s) requested game retake`"
                        >
                            {{ g.pending_reopen_requests_count }} req
                        </span>
                    </button>
                </div>
                <div class="hidden justify-center sm:flex">
                    <button
                        type="button"
                        @click.stop="toggleAutoReopen(g)"
                        class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[11px] font-bold transition-all hover:scale-105 active:scale-95 border"
                        :style="g.auto_reopen
                            ? { background: 'rgba(16, 185, 129, 0.15)', color: 'var(--gl-success)', borderColor: 'rgba(16, 185, 129, 0.4)' }
                            : { background: 'var(--gl-surface-2)', color: 'var(--gl-text-muted)', borderColor: 'var(--gl-border)' }"
                        :title="g.auto_reopen ? 'Auto-reopen is ON: click to turn OFF' : 'Auto-reopen is OFF: click to turn ON'"
                    >
                        <RotateCcw class="h-3 w-3" :stroke-width="2.5" />
                        <span>{{ g.auto_reopen ? 'ON' : 'OFF' }}</span>
                    </button>
                </div>
                <div class="relative flex w-full items-center justify-end gap-1">
                    <button
                        type="button"
                        @click.stop="openTop10ForGame(g)"
                        class="flex h-8 w-8 items-center justify-center rounded-full border border-amber-500/40 bg-amber-500/10 text-amber-600 dark:text-amber-400 transition-all hover:bg-amber-500/20 hover:scale-105 active:scale-95 shadow-sm"
                        :title="'Top 10 Leaderboard for ' + g.title"
                    >
                        <Trophy class="h-4 w-4 text-amber-500" />
                    </button>

                    <Link
                        v-if="isLiveGame(g) && activeRoomsMap[g.id]"
                        :href="`/teacher/games/rooms/${activeRoomsMap[g.id].pin}/host`"
                        @click.stop
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-white shadow-sm transition-all hover:scale-105 active:scale-95"
                        title="Resume Host Screen"
                    >
                        <Play class="h-4 w-4 fill-current" />
                    </Link>
                    <button
                        v-else-if="isLiveGame(g)"
                        type="button"
                        @click.stop="hostLiveGame(g)"
                        class="flex h-8 w-8 items-center justify-center rounded-full border border-emerald-500/40 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 transition-all hover:bg-emerald-500/20 hover:scale-105 active:scale-95"
                        title="Host Live Match"
                    >
                        <Zap class="h-4 w-4" />
                    </button>

                    <button
                        type="button"
                        @click.stop="openMenu = openMenu === g.id ? null : g.id"
                        title="Actions"
                        class="flex h-8 w-8 items-center justify-center rounded-full transition-all hover:scale-105"
                        :style="{
                            background:
                                openMenu === g.id
                                    ? 'var(--gl-surface-2)'
                                    : 'transparent',
                            color:
                                openMenu === g.id
                                    ? 'var(--gl-text-primary)'
                                    : 'var(--gl-text-muted)',
                            border: '1px solid var(--gl-border)',
                        }"
                    >
                        <Ellipsis class="h-4 w-4" :stroke-width="2.5" />
                    </button>
                    <div v-if="openMenu === g.id"
                        class="absolute right-0 top-9 z-20 w-44 overflow-hidden rounded-xl py-1"
                        style="background: var(--gl-surface); border: 1px solid var(--gl-border); box-shadow: 0 8px 28px rgba(0,0,0,0.18);">
                        <Link
                            v-if="activeRoomsMap[g.id]"
                            :href="`/teacher/games/rooms/${activeRoomsMap[g.id].pin}/host`"
                            @click.stop="openMenu = null"
                            class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-semibold text-emerald-600 transition-colors hover:bg-emerald-500/10"
                        >
                            <Play class="h-3.5 w-3.5 fill-current" /> Resume Host Screen
                        </Link>
                        <button
                            v-else
                            @click.stop="openMenu = null; hostLiveGame(g)"
                            class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-semibold text-emerald-600 transition-colors hover:bg-emerald-500/10"
                        >
                            <Zap class="h-3.5 w-3.5" /> Host Live Match
                        </button>
                        <a
                            v-if="activeRoomsMap[g.id]"
                            :href="`/student/games/rooms/${encodeURIComponent(activeRoomsMap[g.id].pin)}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            @click.stop="openMenu = null"
                            class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 transition-colors hover:bg-indigo-500/10"
                        >
                            <ExternalLink class="h-3.5 w-3.5" /> View Player Screen
                        </a>
                        <button
                            @click.stop="openMenu = null; openTop10ForGame(g)"
                            class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-semibold text-amber-600 dark:text-amber-400 transition-colors hover:bg-amber-500/10"
                        >
                            <Trophy class="h-3.5 w-3.5 text-amber-500" :stroke-width="2" /> Top 10 Standings
                        </button>
                        <button @click.stop="openMenu = null; openPlayersModal(g)" class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-semibold text-emerald-600 transition-colors hover:bg-emerald-500/10">
                            <Users class="h-3.5 w-3.5" :stroke-width="2" /> Players & Attempts ({{ g.players_count ?? 0 }})
                        </button>
                        <button @click.stop="openMenu = null; openEdit(g)" class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(59,130,246,0.08)]" style="color: var(--gl-text-primary);"><Pencil class="h-3.5 w-3.5" style="color: var(--gl-primary);" :stroke-width="2" /> Edit</button>
                        <button
                            type="button"
                            @click.stop="openMenu = null; toggleAutoReopen(g)"
                            class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-amber-500/10"
                            :class="g.auto_reopen ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-300'"
                        >
                            <RotateCcw class="h-3.5 w-3.5" :stroke-width="2" />
                            <span>{{ g.auto_reopen ? 'Auto-Reopen: ON' : 'Auto-Reopen: OFF' }}</span>
                        </button>
                        <button @click.stop="openMenu = null; toggleHidden(g)" class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(16,185,129,0.08)]" style="color: var(--gl-text-primary);">
                            <Eye v-if="g.hidden" class="h-3.5 w-3.5" style="color: var(--gl-success);" :stroke-width="2" />
                            <EyeOff v-else class="h-3.5 w-3.5" style="color: var(--gl-text-muted);" :stroke-width="2" />
                            {{ g.hidden ? 'Show' : 'Hide' }}
                        </button>
                        <button @click.stop="openMenu = null; confirmDelete(g)" class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[var(--gl-danger-bg)]" style="color: var(--gl-danger);"><Trash2 class="h-3.5 w-3.5" :stroke-width="2" /> Delete</button>
                        <div
                            v-if="teachers?.length"
                            class="border-t px-3.5 py-2"
                            style="border-color: var(--gl-border)"
                        >
                            <label
                                class="mb-1 block text-[10px] font-semibold"
                                style="color: var(--gl-text-muted)"
                                >Reassign:</label
                            >
                            <select
                                :value="g.teacher_id"
                                @change="reassign(g, ($event.target as HTMLSelectElement).value)"
                                @click.stop
                                class="w-full rounded-lg border px-2 py-1 text-xs outline-none"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            >
                                <option
                                    v-if="!teachers.some((t: any) => t.id === g.teacher_id)"
                                    :value="g.teacher_id"
                                    disabled
                                >
                                    {{ g.teacher?.name || 'Admin / Unassigned' }}
                                </option>
                                <option
                                    v-for="t in teachers"
                                    :key="t.id"
                                    :value="t.id"
                                >
                                    {{ t.name }}
                                </option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <p class="mt-4 text-xs" style="color: var(--gl-text-muted)">
            {{ filtered.length }} of {{ localGames.length }} games
        </p>
    </div>

    <div v-if="openMenu !== null" class="fixed inset-0 z-10" style="background: rgba(0,0,0,0.25);" @click="openMenu = null"></div>


    <!-- Create/Edit Modal -->
    <Teleport to="body">
        <div
            v-if="showForm"
            class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 overflow-y-auto"
            style="background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px)"
            @click.self="showForm = false"
        >
            <div
                class="w-full max-w-5xl xl:max-w-6xl max-h-[92vh] flex flex-col rounded-3xl overflow-hidden shadow-2xl my-auto"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.45);
                "
            >
                <!-- Modal Header -->
                <div
                    class="flex shrink-0 items-center justify-between border-b px-6 py-4"
                    style="border-color: var(--gl-border); background: var(--gl-surface-2);"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl"
                            style="background: linear-gradient(135deg, #22D3EE, #3B82F6); box-shadow: 0 0 14px rgba(34,211,238,0.35);">
                            <Gamepad2 class="h-5 w-5 text-white" :stroke-width="2" />
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold tracking-tight" style="color: var(--gl-text-primary)">
                                {{ editingId ? 'Edit' : 'New' }} Game
                            </h3>
                            <p class="text-[11px]" style="color: var(--gl-text-muted)">{{ editingId ? 'Tweak the cards and rewards' : 'Build it card by card' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="editingId"
                            type="button"
                            @click="openTop10ForGame(localGames.find(g => g.id === editingId))"
                            class="flex items-center gap-1.5 rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 py-1.5 text-xs font-bold text-amber-600 dark:text-amber-400 transition-all hover:bg-amber-500/20 active:scale-95 shadow-sm"
                            title="View Top 10 Leaderboard"
                        >
                            <Trophy class="h-3.5 w-3.5 text-amber-500" />
                            <span>Top 10</span>
                        </button>
                        <button
                            @click="showForm = false"
                            class="flex h-8 w-8 items-center justify-center rounded-full transition-colors hover:bg-[var(--gl-surface)]"
                            style="color: var(--gl-text-muted)"
                        >
                            <X class="h-4 w-4" :stroke-width="2.5" />
                        </button>
                    </div>
                </div>

                <!-- Form Wrapping 2-Column Body & Footer -->
                <form @submit.prevent="save" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                    <div class="flex-1 overflow-y-auto p-4 sm:p-6 no-scrollbar">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                            <!-- Column 1: Game details -->
                            <div class="lg:col-span-5 space-y-4 lg:sticky lg:top-0">
                                <div class="rounded-2xl p-4" style="background: rgba(34,211,238,0.05); border: 1px solid rgba(34,211,238,0.16);">
                                    <div class="mb-3 flex items-center gap-2">
                                        <div class="flex h-7 w-7 items-center justify-center rounded-lg" style="background: rgba(34,211,238,0.14);">
                                            <Gamepad2 class="h-3.5 w-3.5" style="color: #22D3EE;" :stroke-width="2" />
                                        </div>
                                        <h4 class="text-sm font-bold" style="color: var(--gl-text-primary)">Game details</h4>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div class="sm:col-span-2">
                                            <label
                                                class="mb-1.5 block text-xs font-medium"
                                                style="color: var(--gl-text-secondary)"
                                                >Title</label
                                            ><input
                                                v-model="form.title"
                                                type="text"
                                                required
                                                class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                                style="
                                                    background: var(--gl-surface-2);
                                                    color: var(--gl-text-primary);
                                                    border-color: var(--gl-border);
                                                "
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="mb-1.5 block text-xs font-medium"
                                                style="color: var(--gl-text-secondary)"
                                                >Subject</label
                                            ><select
                                                v-model="form.subject_id"
                                                required
                                                class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                                style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);"
                                            >
                                                <option :value="null">Select subject</option>
                                                <option v-if="form.subject_id === null && form.subject" :value="null">{{ form.subject }} (legacy)</option>
                                                <option v-for="s in subjects || []" :key="s.id" :value="s.id">{{ s.name }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label
                                                class="mb-1.5 block text-xs font-medium"
                                                style="color: var(--gl-text-secondary)"
                                                >Grade (optional)</label
                                            >
                                            <select
                                                v-model="form.grade"
                                                class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                                style="
                                                    background: var(--gl-surface-2);
                                                    color: var(--gl-text-primary);
                                                    border-color: var(--gl-border);
                                                "
                                            >
                                                <option value="">All Grades / General</option>
                                                <option
                                                    v-if="form.grade && !props.gradeLevels?.some((gl: any) => gl.name === form.grade)"
                                                    :value="form.grade"
                                                >
                                                    {{ form.grade }} (Current)
                                                </option>
                                                <option
                                                    v-for="gl in props.gradeLevels || []"
                                                    :key="gl.id"
                                                    :value="gl.name"
                                                >
                                                    {{ gl.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <div v-if="props.quarters?.length">
                                            <label
                                                class="mb-1.5 block text-xs font-medium"
                                                style="color: var(--gl-text-secondary)"
                                                >Quarter (optional)</label
                                            >
                                            <select
                                                v-model="form.quarter_id"
                                                class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                                style="
                                                    background: var(--gl-surface-2);
                                                    color: var(--gl-text-primary);
                                                    border-color: var(--gl-border);
                                                "
                                            >
                                                <option :value="null">All / General (No quarter)</option>
                                                <option
                                                    v-for="q in props.quarters"
                                                    :key="q.id"
                                                    :value="q.id"
                                                >
                                                    {{ q.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <div v-if="teachers?.length" class="sm:col-span-2">
                                            <label
                                                class="mb-1.5 block text-xs font-semibold"
                                                style="color: var(--gl-text-secondary)"
                                                >Assign to teacher</label
                                            >
                                            <select
                                                v-model="form.teacher_id"
                                                class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                                style="
                                                    background: var(--gl-surface-2);
                                                    color: var(--gl-text-primary);
                                                    border-color: var(--gl-border);
                                                "
                                            >
                                                <option :value="null">Select a teacher</option>
                                                <option
                                                    v-for="t in teachers"
                                                    :key="t.id"
                                                    :value="t.id"
                                                >
                                                    {{ t.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label
                                                class="mb-1.5 block text-xs font-medium"
                                                style="color: var(--gl-text-secondary)"
                                                >XP Reward</label
                                            ><input
                                                v-model.number="form.xp_reward"
                                                type="number"
                                                min="1"
                                                required
                                                class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                                style="
                                                    background: var(--gl-surface-2);
                                                    color: var(--gl-text-primary);
                                                    border-color: var(--gl-border);
                                                "
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="mb-1.5 block text-xs font-medium"
                                                style="color: var(--gl-text-secondary)"
                                                >Game Type</label
                                            ><select
                                                v-model="form.type"
                                                class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                                style="
                                                    background: var(--gl-surface-2);
                                                    color: var(--gl-text-primary);
                                                    border-color: var(--gl-border);
                                                "
                                            >
                                                <option value="flashcard">Flashcard</option>
                                                <option value="quiz">Multiple Choice</option>
                                                <option value="fillblank">
                                                    Fill in the Blank
                                                </option>
                                                <option value="truefalse">True / False</option>
                                                <option value="wordjumble">Word Jumble</option>
                                                <option value="colorharmony">
                                                    Color Harmony
                                                </option>
                                                <option value="memorymatch">
                                                    Memory Match
                                                </option>
                                                <option value="hangman">Hangman</option>
                                                <option value="speedquiz">Speed Quiz</option>
                                                <option value="dragdrop">
                                                    Drag &amp; Drop Matching
                                                </option>
                                                <option value="ordering">
                                                    Order the Steps
                                                </option>
                                                <option value="fourpics">4 Pics 1 Word</option>
                                                <option value="crossword">Crossword</option>
                                                <option value="wordsearch">Word Search</option>
                                                <option value="codepuzzle">Code Puzzle</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label
                                            class="mb-1.5 block text-xs font-medium"
                                            style="color: var(--gl-text-secondary)"
                                            >Description</label
                                        ><textarea
                                            v-model="form.description"
                                            rows="2"
                                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                            style="
                                                background: var(--gl-surface-2);
                                                color: var(--gl-text-primary);
                                                border-color: var(--gl-border);
                                            "
                                        ></textarea>
                                    </div>
                                    <div class="mt-3 rounded-xl border p-3 flex items-center justify-between" style="background: var(--gl-surface-2); border-color: var(--gl-border);">
                                        <div>
                                            <label class="block text-xs font-semibold" style="color: var(--gl-text-primary)">
                                                Auto-Approve Reopen Requests
                                            </label>
                                            <p class="text-[11px]" style="color: var(--gl-text-muted)">
                                                Allow students to retake this game immediately without waiting for teacher approval.
                                            </p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer ml-3 shrink-0">
                                            <input type="checkbox" v-model="form.auto_reopen" class="sr-only peer" />
                                            <div class="w-10 h-5 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Column 2: Questions / Cards -->
                            <div class="lg:col-span-7 space-y-4">
                                <div class="rounded-2xl p-4" style="background: rgba(124,58,237,0.05); border: 1px solid rgba(124,58,237,0.16);">
                                    <div class="mb-3 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-7 w-7 items-center justify-center rounded-lg" style="background: rgba(124,58,237,0.14);">
                                                <Layers class="h-3.5 w-3.5" style="color: var(--gl-secondary);" :stroke-width="2" />
                                            </div>
                                            <h4 class="text-sm font-bold" style="color: var(--gl-text-primary)">{{ cardSectionLabel }}</h4>
                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold tabular-nums" style="background: var(--gl-surface-2); color: var(--gl-text-muted);">{{ form.cards.length }}</span>
                                        </div>
                                        <button
                                            type="button"
                                            @click="addCard"
                                            class="flex h-8 w-8 items-center justify-center rounded-full text-white transition-all hover:scale-105"
                                            style="background: linear-gradient(135deg, var(--gl-secondary), var(--gl-primary)); box-shadow: 0 0 10px var(--gl-secondary-glow);"
                                            :title="addCardLabel"
                                        >
                                            <Plus class="h-4 w-4" :stroke-width="2.5" />
                                        </button>
                                    </div>
                                    <p
                                        class="mb-3 text-[11px]"
                                        style="color: var(--gl-text-muted)"
                                    >
                                        {{ typeHint }}
                                    </p>
                                    <div
                                        v-for="(c, i) in form.cards"
                                        :key="i"
                                class="mb-3 flex flex-col gap-1.5 pb-3"
                                :style="{
                                    borderBottom:
                                        i < form.cards.length - 1
                                            ? '1px solid var(--gl-border)'
                                            : 'none',
                                }"
                            >
                                <div class="flex items-start gap-2">
                                    <span
                                        class="mt-2.5 text-xs"
                                        style="color: var(--gl-text-muted)"
                                        >{{ i + 1 }}.</span
                                    >
                                    <input
                                        v-model="c.question"
                                        type="text"
                                        :placeholder="
                                            form.type === 'crossword'
                                                ? 'Clue / Definition (e.g. A step-by-step problem-solving procedure)'
                                                : form.type === 'wordsearch'
                                                  ? 'Category / Theme hint (e.g. Science, Space)'
                                                  : form.type === 'codepuzzle'
                                                    ? 'Line description / step comment (e.g. Initialize counter)'
                                                    : form.type === 'fourpics'
                                                ? 'Clue / Category (optional, e.g. Design, Action)'
                                                : form.type === 'ordering'
                                                ? 'Step text (in correct order)'
                                                : form.type === 'memorymatch'
                                                  ? 'Term (front of card)'
                                                  : form.type === 'dragdrop'
                                                    ? 'Term (what students drag)'
                                                    : form.type ===
                                                            'wordjumble' ||
                                                        form.type === 'hangman'
                                                      ? 'Hint / clue'
                                                      : form.type ===
                                                          'flashcard'
                                                        ? 'Front (question)'
                                                        : form.type ===
                                                            'fillblank'
                                                          ? 'Sentence with ___ blank'
                                                          : form.type ===
                                                              'truefalse'
                                                            ? 'Statement to judge'
                                                            : form.type ===
                                                                'colorharmony'
                                                              ? 'Label (optional)'
                                                              : 'Question'
                                        "
                                        class="flex-1 rounded-lg border px-3 py-1.5 text-xs outline-none"
                                        style="
                                            background: var(--gl-surface-2);
                                            color: var(--gl-text-primary);
                                            border-color: var(--gl-border);
                                        "
                                    />
                                    <input
                                        v-if="
                                            form.type !== 'quiz' &&
                                            form.type !== 'speedquiz' &&
                                            form.type !== 'truefalse' &&
                                            form.type !== 'colorharmony'
                                        "
                                        v-model="c.answer"
                                        type="text"
                                        :placeholder="
                                            form.type === 'crossword'
                                                ? 'Target word (e.g. ALGORITHM)'
                                                : form.type === 'wordsearch'
                                                  ? 'Word to find (e.g. GRAVITY)'
                                                  : form.type === 'codepuzzle'
                                                    ? 'Code line (e.g. let count = 0;)'
                                                    : form.type === 'fourpics'
                                                ? 'Target word (e.g. BALANCE)'
                                                : form.type === 'wordjumble'
                                                ? 'Answer (single word, letters scrambled)'
                                                : form.type === 'hangman'
                                                  ? 'Answer (single word, guessed letter by letter)'
                                                  : form.type === 'memorymatch'
                                                    ? 'Matching definition (back)'
                                                    : form.type === 'dragdrop'
                                                      ? 'Definition (drop target)'
                                                      : form.type === 'ordering'
                                                        ? 'Step label (optional)'
                                                        : form.type ===
                                                            'flashcard'
                                                          ? 'Back (answer)'
                                                          : form.type ===
                                                              'fillblank'
                                                            ? 'Answer (fills the blank)'
                                                            : 'Answer'
                                        "
                                        class="flex-1 rounded-lg border px-3 py-1.5 text-xs outline-none"
                                        style="
                                            background: var(--gl-surface-2);
                                            color: var(--gl-text-primary);
                                            border-color: var(--gl-border);
                                        "
                                    />
                                    <template
                                        v-else-if="form.type === 'truefalse'"
                                    >
                                        <div class="flex items-center gap-3">
                                            <label
                                                class="flex items-center gap-1.5 text-xs"
                                                style="
                                                    color: var(
                                                        --gl-text-secondary
                                                    );
                                                "
                                                ><input
                                                    type="radio"
                                                    :name="'tf_' + i"
                                                    :checked="
                                                        c.answer === 'True'
                                                    "
                                                    @change="c.answer = 'True'"
                                                />
                                                True</label
                                            >
                                            <label
                                                class="flex items-center gap-1.5 text-xs"
                                                style="
                                                    color: var(
                                                        --gl-text-secondary
                                                    );
                                                "
                                                ><input
                                                    type="radio"
                                                    :name="'tf_' + i"
                                                    :checked="
                                                        c.answer === 'False'
                                                    "
                                                    @change="c.answer = 'False'"
                                                />
                                                False</label
                                            >
                                        </div>
                                    </template>
                                    <button
                                        v-if="form.cards.length > 1"
                                        type="button"
                                        @click="form.cards.splice(i, 1)"
                                        class="mt-1.5 rounded-lg p-1 transition-colors hover:bg-[var(--gl-danger-bg)]"
                                        style="color: var(--gl-danger)"
                                    >
                                        <X class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                                <template
                                    v-if="
                                        form.type === 'quiz' ||
                                        form.type === 'speedquiz'
                                    "
                                >
                                    <div class="ml-6 space-y-1">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <span
                                                class="text-[10px]"
                                                style="
                                                    color: var(--gl-text-muted);
                                                "
                                                >Options:</span
                                            >
                                            <div
                                                v-for="(opt, oi) in c.options ||
                                                []"
                                                :key="oi"
                                                class="flex items-center gap-1"
                                            >
                                                <input
                                                    v-model="c.options[oi]"
                                                    type="text"
                                                    placeholder="Option {{ oi + 1 }}"
                                                    class="w-32 rounded-lg border px-2 py-1 text-[10px] outline-none"
                                                    style="
                                                        background: var(
                                                            --gl-surface-2
                                                        );
                                                        color: var(
                                                            --gl-text-primary
                                                        );
                                                        border-color: var(
                                                            --gl-border
                                                        );
                                                    "
                                                />
                                                <label
                                                    class="flex items-center gap-0.5 text-[10px]"
                                                    style="
                                                        color: var(
                                                            --gl-text-muted
                                                        );
                                                    "
                                                    ><input
                                                        type="radio"
                                                        :name="'correct_' + i"
                                                        :checked="
                                                            c.answer ===
                                                            c.options[oi]
                                                        "
                                                        @change="
                                                            c.answer =
                                                                c.options[oi]
                                                        "
                                                    />
                                                    Correct</label
                                                >
                                                <button
                                                    v-if="c.options.length > 2"
                                                    type="button"
                                                    @click="
                                                        c.options.splice(oi, 1);
                                                        if (c.answer === opt)
                                                            c.answer = '';
                                                    "
                                                    class="text-[10px]"
                                                    style="
                                                        color: var(--gl-danger);
                                                    "
                                                >
                                                    <X
                                                        class="inline h-2.5 w-2.5"
                                                    />
                                                </button>
                                            </div>
                                        </div>
                                        <button
                                            v-if="(c.options?.length || 0) < 4"
                                            type="button"
                                            @click="
                                                if (!c.options) c.options = [];
                                                c.options.push('');
                                            "
                                            class="text-[10px] font-medium"
                                            style="color: var(--gl-primary)"
                                        >
                                            + Add option
                                        </button>
                                    </div>
                                </template>
                                <template
                                    v-else-if="form.type === 'colorharmony'"
                                >
                                    <div
                                        class="mt-1.5 ml-6 flex items-center gap-3"
                                    >
                                        <label
                                            class="flex items-center gap-2 text-[10px]"
                                            style="color: var(--gl-text-muted)"
                                        >
                                            <span class="font-semibold"
                                                >Base:</span
                                            >
                                            <input
                                                type="color"
                                                v-model="c.color"
                                                class="h-7 w-10 cursor-pointer rounded-md border"
                                                style="
                                                    border-color: var(
                                                        --gl-border
                                                    );
                                                    padding: 1px;
                                                "
                                            />
                                            <span class="font-mono">{{
                                                c.color
                                            }}</span>
                                        </label>
                                        <button
                                            type="button"
                                            @click="generatePalette(c)"
                                            class="rounded-lg px-2 py-1 text-[10px] font-medium transition-colors"
                                            style="
                                                background: rgba(
                                                    139,
                                                    92,
                                                    246,
                                                    0.12
                                                );
                                                color: var(--gl-secondary);
                                            "
                                        >
                                            Auto-generate palette
                                        </button>
                                    </div>
                                    <div class="mt-2 ml-6 space-y-1">
                                        <span
                                            class="text-[10px]"
                                            style="color: var(--gl-text-muted)"
                                            >Choices (mark the correct
                                            one):</span
                                        >
                                        <div
                                            v-for="(opt, oi) in c.options || []"
                                            :key="oi"
                                            class="flex items-center gap-2"
                                        >
                                            <input
                                                type="color"
                                                v-model="c.options[oi]"
                                                class="h-6 w-9 cursor-pointer rounded-md border"
                                                style="
                                                    border-color: var(
                                                        --gl-border
                                                    );
                                                    padding: 1px;
                                                "
                                            />
                                            <span
                                                class="font-mono text-[10px]"
                                                style="
                                                    color: var(--gl-text-muted);
                                                "
                                                >{{ c.options[oi] }}</span
                                            >
                                            <label
                                                class="flex items-center gap-1 text-[10px]"
                                                style="
                                                    color: var(--gl-text-muted);
                                                "
                                                ><input
                                                    type="radio"
                                                    :name="'ch_' + i"
                                                    :checked="
                                                        c.answer ===
                                                        c.options[oi]
                                                    "
                                                    @change="
                                                        c.answer = c.options[oi]
                                                    "
                                                />
                                                Correct</label
                                            >
                                            <button
                                                v-if="c.options.length > 2"
                                                type="button"
                                                @click="
                                                    c.options.splice(oi, 1);
                                                    if (c.answer === opt)
                                                        c.answer =
                                                            c.options[0] || '';
                                                "
                                                class="text-[10px]"
                                                style="color: var(--gl-danger)"
                                            >
                                                <X class="inline h-2.5 w-2.5" />
                                            </button>
                                        </div>
                                        <button
                                            v-if="(c.options?.length || 0) < 6"
                                            type="button"
                                            @click="
                                                if (!c.options) c.options = [];
                                                c.options.push(randomHex());
                                            "
                                            class="text-[10px] font-medium"
                                            style="color: var(--gl-secondary)"
                                        >
                                            + Add choice
                                        </button>
                                    </div>
                                </template>
                                <template
                                    v-else-if="form.type === 'fourpics'"
                                >
                                    <div class="mt-2 ml-6 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span
                                                class="text-[11px] font-semibold"
                                                style="color: var(--gl-text-secondary)"
                                                >4 Images (Direct image links or URLs)</span
                                            >
                                            <span
                                                class="text-[10px]"
                                                style="color: var(--gl-text-muted)"
                                                >4 direct image URLs</span
                                            >
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            <div
                                                v-for="imgIdx in 4"
                                                :key="imgIdx"
                                                class="flex flex-col gap-1.5 rounded-xl border p-2.5 transition-all"
                                                style="background: var(--gl-surface-2); border-color: var(--gl-border);"
                                            >
                                                <div class="flex items-center gap-1.5">
                                                    <span
                                                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold"
                                                        style="background: rgba(124,58,237,0.15); color: var(--gl-secondary);"
                                                    >
                                                        {{ imgIdx }}
                                                    </span>
                                                    <input
                                                        v-model="c.options[imgIdx - 1]"
                                                        type="text"
                                                        :placeholder="'Image URL ' + imgIdx + ' (https://...)'"
                                                        class="flex-1 rounded-lg border px-2.5 py-1 text-xs outline-none"
                                                        style="background: var(--gl-surface-1); color: var(--gl-text-primary); border-color: var(--gl-border);"
                                                    />
                                                </div>
                                                <div
                                                    v-if="c.options && c.options[imgIdx - 1]"
                                                    class="relative mt-1 h-20 w-full overflow-hidden rounded-lg border"
                                                    style="border-color: var(--gl-border); background: var(--gl-surface-1);"
                                                >
                                                    <img
                                                        :src="c.options[imgIdx - 1]"
                                                        :alt="'Pic ' + imgIdx"
                                                        class="h-full w-full object-cover"
                                                        @error="(e: any) => (e.target.style.opacity = '0.3')"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div
                class="flex shrink-0 items-center justify-end gap-2.5 border-t px-6 py-3.5"
                style="
                    background: var(--gl-surface-2);
                    border-color: var(--gl-border);
                "
            >
                <button
                    type="button"
                    @click="showForm = false"
                    class="rounded-full px-5 py-2.5 text-xs font-bold transition-all hover:scale-[1.02]"
                    style="
                        background: var(--gl-surface);
                        color: var(--gl-text-primary);
                        border: 1px solid var(--gl-border);
                    "
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    :disabled="saving"
                    class="rounded-full px-5 py-2.5 text-xs font-bold text-white transition-all hover:scale-[1.02] disabled:opacity-50"
                    style="
                        background: linear-gradient(
                            135deg,
                            #22D3EE,
                            #3B82F6
                        );
                        box-shadow: 0 0 14px rgba(34, 211, 238, 0.35);
                    "
                >
                    {{ editingId ? 'Update' : 'Create' }} Game
                </button>
            </div>
        </form>

    </div>
</div>
    </Teleport>

    <!-- Game Players & Attempts Modal -->
    <Teleport to="body">
        <div
            v-if="showPlayersModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
            style="background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(3px);"
            @click.self="closePlayersModal"
        >
            <div
                class="relative flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-3xl border shadow-2xl"
                style="
                    background: var(--gl-surface);
                    border-color: var(--gl-border);
                "
            >
                <!-- Header -->
                <div
                    class="flex shrink-0 items-center justify-between border-b px-6 py-4"
                    style="border-color: var(--gl-border); background: var(--gl-surface-2);"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl text-white shadow-md"
                            style="background: linear-gradient(135deg, #10B981, #059669);"
                        >
                            <Users class="h-5 w-5" :stroke-width="2" />
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold tracking-tight" style="color: var(--gl-text-primary)">
                                Players & Submissions
                            </h3>
                            <p class="text-xs" style="color: var(--gl-text-muted)">
                                {{ activeGameForPlayers?.title }} · {{ activeGameForPlayers?.subject }} · {{ activeGameForPlayers?.cards?.length }} items
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="activeGameForPlayers"
                            type="button"
                            @click="toggleAutoReopen(activeGameForPlayers)"
                            class="flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-bold transition-all hover:scale-105 active:scale-95 shadow-sm"
                            :style="activeGameForPlayers.auto_reopen
                                ? { background: 'rgba(16, 185, 129, 0.15)', color: 'var(--gl-success)', borderColor: 'rgba(16, 185, 129, 0.4)' }
                                : { background: 'var(--gl-surface-2)', color: 'var(--gl-text-secondary)', borderColor: 'var(--gl-border)' }"
                            :title="activeGameForPlayers.auto_reopen ? 'Auto-reopen is ON: click to turn OFF' : 'Auto-reopen is OFF: click to turn ON'"
                        >
                            <RotateCcw class="h-3.5 w-3.5" :stroke-width="2.5" />
                            <span>Auto-Reopen: <strong class="uppercase">{{ activeGameForPlayers.auto_reopen ? 'ON' : 'OFF' }}</strong></span>
                        </button>
                        <button
                            type="button"
                            @click="openTop10ForGame(activeGameForPlayers)"
                            class="flex items-center gap-1.5 rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 py-1.5 text-xs font-bold text-amber-600 dark:text-amber-400 transition-all hover:bg-amber-500/20 active:scale-95"
                            title="View Top 10 Leaderboard"
                        >
                            <Trophy class="h-3.5 w-3.5 text-amber-500" />
                            <span>Top 10 Standings</span>
                        </button>
                        <button
                            @click="closePlayersModal"
                            class="flex h-8 w-8 items-center justify-center rounded-full transition-colors hover:bg-[var(--gl-surface)]"
                            style="color: var(--gl-text-muted)"
                        >
                            <X class="h-4 w-4" :stroke-width="2.5" />
                        </button>
                    </div>
                </div>

                <!-- Search & Filter Controls -->
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b px-6 py-3"
                    style="border-color: var(--gl-border);"
                >
                    <div class="relative flex-1 min-w-[200px]">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2"
                            style="color: var(--gl-text-muted)"
                        />
                        <input
                            v-model="playerSearch"
                            type="text"
                            placeholder="Search student name, email, or grade..."
                            class="w-full rounded-xl border py-2 pr-3 pl-8 text-xs outline-none"
                            style="
                                background: var(--gl-surface-2);
                                border-color: var(--gl-border);
                                color: var(--gl-text-primary);
                            "
                        />
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button
                            v-for="tab in [
                                { id: 'all', label: 'All (' + playersList.length + ')' },
                                { id: 'completed', label: 'Completed' },
                                { id: 'inprogress', label: 'In Progress' },
                            ]"
                            :key="tab.id"
                            @click="playerStatusFilter = tab.id"
                            class="rounded-lg px-2.5 py-1.5 text-xs font-semibold transition-all"
                            :style="
                                playerStatusFilter === tab.id
                                    ? { background: '#1F2937', color: '#fff' }
                                    : { background: 'var(--gl-surface-2)', color: 'var(--gl-text-secondary)', border: '1px solid var(--gl-border)' }
                            "
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </div>

                <!-- Auto-Reopen Status Bar in Players Modal -->
                <div
                    v-if="activeGameForPlayers"
                    class="border-b px-6 py-2.5 flex items-center justify-between text-xs transition-colors"
                    :style="activeGameForPlayers.auto_reopen
                        ? { background: 'rgba(16, 185, 129, 0.08)', borderColor: 'rgba(16, 185, 129, 0.25)' }
                        : { background: 'var(--gl-surface-2)', borderColor: 'var(--gl-border)' }"
                >
                    <div class="flex items-center gap-2">
                        <RotateCcw class="h-3.5 w-3.5" :class="activeGameForPlayers.auto_reopen ? 'text-emerald-500' : 'text-slate-400'" />
                        <span style="color: var(--gl-text-primary)">
                            Student Retakes:
                            <strong :class="activeGameForPlayers.auto_reopen ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-500 font-bold'">
                                <template v-if="activeGameForPlayers.auto_reopen">
                                    <Zap class="mr-1 inline h-3.5 w-3.5" :stroke-width="2.5" />
                                    Auto-Approved (Students can replay immediately)
                                </template>
                                <template v-else>Manual Approval Required</template>
                            </strong>
                        </span>
                    </div>
                    <button
                        type="button"
                        @click="toggleAutoReopen(activeGameForPlayers)"
                        class="text-xs font-bold hover:underline"
                        :class="activeGameForPlayers.auto_reopen ? 'text-rose-500' : 'text-emerald-600 dark:text-emerald-400'"
                    >
                        {{ activeGameForPlayers.auto_reopen ? 'Turn OFF' : 'Turn ON Auto-Approve' }}
                    </button>
                </div>

                <!-- Players Body / List -->
                <div class="flex-1 overflow-y-auto p-6">
                    <!-- Live Sessions Top 10 Standings Preview -->
                    <div
                        v-if="playersLiveRooms.length > 0 && !loadingPlayers"
                        class="mb-6 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-4"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Trophy class="h-4 w-4 text-amber-600" />
                                <h4 class="text-xs font-extrabold uppercase tracking-wider text-amber-700 dark:text-amber-400">
                                    Live Match Sessions Top 10 ({{ playersLiveRooms.length }})
                                </h4>
                            </div>
                            <button
                                type="button"
                                @click="openTop10ForGame(activeGameForPlayers)"
                                class="text-xs font-bold text-amber-700 dark:text-amber-400 hover:underline"
                            >
                                View Detailed Leaderboards &rarr;
                            </button>
                        </div>
                        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                            <div
                                v-for="room in playersLiveRooms.slice(0, 4)"
                                :key="room.id"
                                class="rounded-xl border border-amber-500/20 bg-[var(--gl-surface)] p-3 shadow-sm flex items-center justify-between"
                            >
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-black text-[var(--gl-text-primary)]">
                                            PIN: {{ room.pin }}
                                        </span>
                                        <span
                                            class="rounded-full px-1.5 py-0.2 text-[9px] font-black uppercase"
                                            :class="room.status === 'active' ? 'bg-emerald-500/20 text-emerald-600' : 'bg-slate-500/20 text-[var(--gl-text-muted)]'"
                                        >
                                            {{ room.status }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-[var(--gl-text-muted)] mt-0.5">
                                        {{ room.total_participants }} player(s) · {{ room.created_at }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        @click="openTop10ForGame(activeGameForPlayers, room.pin)"
                                        class="rounded-lg bg-amber-500/15 px-2.5 py-1 text-xs font-bold text-amber-700 dark:text-amber-400 hover:bg-amber-500/25"
                                    >
                                        Top 10
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteLiveRoom(room)"
                                        class="rounded-lg border border-red-500/30 bg-red-500/10 p-1 text-red-600 transition-all hover:bg-red-500/20 active:scale-95"
                                        title="Delete this live arena session"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Reopen Requests Section -->
                    <div
                        v-if="pendingRequestsList.length > 0 && !loadingPlayers"
                        class="mb-6 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-4"
                    >
                        <div class="mb-3 flex items-center justify-between flex-wrap gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <RotateCcw class="h-4 w-4 text-amber-600" />
                                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-amber-700 dark:text-amber-400">
                                        Pending Reopen Requests ({{ pendingRequestsList.length }})
                                    </h4>
                                </div>
                                <span class="text-[11px] font-semibold text-amber-700 dark:text-amber-400">
                                    Students waiting to retake this game
                                </span>
                            </div>
                            <button
                                v-if="activeGameForPlayers"
                                type="button"
                                @click="toggleAutoReopen(activeGameForPlayers)"
                                class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold border transition-all"
                                :class="activeGameForPlayers.auto_reopen ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-600 dark:text-emerald-400' : 'bg-slate-500/10 border-slate-500/20 text-slate-500'"
                                title="Toggle auto-approval for student reopen requests on this game"
                            >
                                <span>Auto-Reopen:</span>
                                <strong class="uppercase">{{ activeGameForPlayers.auto_reopen ? 'ON' : 'OFF' }}</strong>
                            </button>
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="req in pendingRequestsList"
                                :key="req.id"
                                class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-500/20 bg-[var(--gl-surface)] p-3 shadow-sm"
                            >
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-[var(--gl-text-primary)] flex items-center gap-2 flex-wrap">
                                        <span>{{ req.student_name }}</span>
                                        <span class="text-[11px] font-normal text-[var(--gl-text-muted)]">
                                            · {{ req.student_grade }} · {{ req.created_at }}
                                        </span>
                                        <span
                                            v-if="req.earned_xp > 0"
                                            class="inline-flex items-center gap-0.5 rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-extrabold text-amber-600 dark:text-amber-400 border border-amber-500/25"
                                            title="XP earned from this game that will be deducted on reopen"
                                        >
                                            -{{ req.earned_xp }} XP on reopen
                                        </span>
                                    </p>
                                    <p v-if="req.reason" class="mt-0.5 text-xs italic text-[var(--gl-text-secondary)]">
                                        "{{ req.reason }}"
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button
                                        type="button"
                                        @click="approveRequestAndReopen(req)"
                                        class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white transition-all hover:bg-emerald-500 active:scale-95"
                                    >
                                        <CheckCircle2 class="h-3.5 w-3.5" />
                                        <span>Approve &amp; Reopen</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="rejectRequest(req)"
                                        class="rounded-lg border border-[var(--gl-border)] px-2.5 py-1.5 text-xs font-semibold text-[var(--gl-text-muted)] transition-colors hover:text-red-500 hover:bg-red-500/10"
                                    >
                                        Reject
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Loading State -->
                    <div v-if="loadingPlayers" class="py-12 text-center">
                        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-emerald-500 border-t-transparent"></div>
                        <p class="mt-3 text-xs" style="color: var(--gl-text-muted)">Loading players data...</p>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="!filteredPlayers.length" class="py-12 text-center">
                        <Users class="mx-auto h-12 w-12 text-[var(--gl-text-muted)] opacity-30" />
                        <p class="mt-3 text-sm font-bold" style="color: var(--gl-text-primary)">
                            No student attempts found
                        </p>
                        <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                            {{ playerSearch ? 'Try clearing your search query.' : 'Students who play this game will appear here with their scores and completion time.' }}
                        </p>
                    </div>

                    <!-- Players List -->
                    <div v-else class="space-y-3">
                        <div
                            v-for="player in filteredPlayers"
                            :key="player.user_id"
                            class="rounded-2xl border transition-all"
                            :style="{
                                background: 'var(--gl-surface-2)',
                                borderColor: 'var(--gl-border)',
                            }"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl font-black text-sm text-white"
                                        :style="{
                                            background: player.completed
                                                ? 'linear-gradient(135deg, #10B981, #059669)'
                                                : 'linear-gradient(135deg, #F59E0B, #D97706)',
                                        }"
                                    >
                                        {{ (player.student_name || 'U')[0].toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold" style="color: var(--gl-text-primary)">
                                            {{ player.student_name }}
                                        </p>
                                        <p class="truncate text-xs" style="color: var(--gl-text-muted)">
                                            {{ player.student_grade || 'No grade' }} · {{ player.student_email || 'No email' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <!-- Score & Status -->
                                    <div class="text-right">
                                        <p class="text-sm font-extrabold tabular-nums" style="color: var(--gl-text-primary)">
                                            {{ playerEffectiveScore(player) }} / {{ player.total }}
                                            <span class="text-xs font-semibold text-emerald-500">({{ playerPercentage(player) }}%)</span>
                                        </p>
                                        <p class="text-[11px]" style="color: var(--gl-text-muted)">
                                            <span
                                                class="inline-block h-1.5 w-1.5 rounded-full mr-1"
                                                :style="{ background: player.completed ? '#10B981' : '#F59E0B' }"
                                            ></span>
                                            {{ player.completed ? 'Completed ' + player.completed_at : 'In Progress' }}
                                        </p>
                                    </div>

                                    <!-- Actions (Icons only) -->
                                    <div class="flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="expandedPlayerId = expandedPlayerId === player.user_id ? null : player.user_id"
                                            class="flex h-8 w-8 items-center justify-center rounded-xl border transition-all hover:scale-105 active:scale-95"
                                            :style="
                                                expandedPlayerId === player.user_id
                                                    ? {
                                                          background: 'rgba(34, 211, 238, 0.15)',
                                                          color: '#06b6d4',
                                                          borderColor: 'rgba(34, 211, 238, 0.4)',
                                                      }
                                                    : {
                                                          background: 'var(--gl-surface)',
                                                          color: 'var(--gl-text-secondary)',
                                                          borderColor: 'var(--gl-border)',
                                                      }
                                            "
                                            :title="expandedPlayerId === player.user_id ? 'Hide item breakdown' : 'View item breakdown'"
                                            :aria-label="expandedPlayerId === player.user_id ? 'Hide item breakdown' : 'View item breakdown'"
                                        >
                                            <ListChecks class="h-4 w-4" :stroke-width="2" />
                                        </button>

                                        <button
                                            type="button"
                                            @click="confirmReopen(player)"
                                            class="flex h-8 w-8 items-center justify-center rounded-xl transition-all hover:scale-105 active:scale-95"
                                            style="
                                                background: rgba(239, 68, 68, 0.12);
                                                color: var(--gl-danger);
                                                border: 1px solid rgba(239, 68, 68, 0.25);
                                            "
                                            title="Reset progress and allow student to replay"
                                            aria-label="Reset progress and allow student to replay"
                                        >
                                            <RotateCcw class="h-4 w-4" :stroke-width="2" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Collapsible Item Breakdown -->
                            <div
                                v-if="expandedPlayerId === player.user_id"
                                class="border-t px-4 py-3 space-y-2"
                                style="border-color: var(--gl-border); background: var(--gl-surface);"
                            >
                                <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-[var(--gl-text-muted)]">
                                    <span>Item Breakdown ({{ playerEffectiveScore(player) }}/{{ player.total }} correct)</span>
                                    <span>Earned XP: +{{ player.earned_xp }}</span>
                                </div>

                                <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                                    <div
                                        v-for="(card, cIdx) in (playersGameDetails?.cards || activeGameForPlayers?.cards || [])"
                                        :key="card.id"
                                        class="flex items-center justify-between gap-2 rounded-xl p-2 text-xs border"
                                        :style="
                                            (player.correct_card_ids || []).includes(card.id)
                                                ? { background: 'rgba(16, 185, 129, 0.05)', borderColor: 'rgba(16, 185, 129, 0.2)' }
                                                : { background: 'rgba(239, 68, 68, 0.05)', borderColor: 'rgba(239, 68, 68, 0.2)' }
                                        "
                                    >
                                        <div class="flex items-center gap-2 min-w-0 flex-1">
                                            <span class="font-mono text-[10px] text-[var(--gl-text-muted)]">#{{ cIdx + 1 }}</span>
                                            <span class="truncate font-semibold text-[var(--gl-text-primary)]">{{ card.question || 'Item #' + (cIdx + 1) }}</span>
                                            <span class="hidden sm:inline text-[11px] text-[var(--gl-text-muted)]">· Answer: <strong class="text-[var(--gl-text-primary)]">{{ card.answer }}</strong></span>
                                        </div>
                                        <span
                                            class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                            :style="
                                                (player.correct_card_ids || []).includes(card.id)
                                                    ? { background: 'rgba(16, 185, 129, 0.15)', color: 'var(--gl-success)' }
                                                    : { background: 'rgba(239, 68, 68, 0.15)', color: 'var(--gl-danger)' }
                                            "
                                        >
                                            {{ (player.correct_card_ids || []).includes(card.id) ? '✓ Correct' : '✗ Incorrect' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="flex shrink-0 items-center justify-between border-t px-6 py-4"
                    style="border-color: var(--gl-border); background: var(--gl-surface-2);"
                >
                    <p class="text-xs" style="color: var(--gl-text-muted)">
                        Reopening a game resets student's attempt and lets them retake the game.
                    </p>
                    <button
                        type="button"
                        @click="closePlayersModal"
                        class="rounded-xl px-4 py-2 text-xs font-bold transition-all hover:bg-[var(--gl-surface)]"
                        style="
                            background: var(--gl-surface);
                            color: var(--gl-text-primary);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Reopen Confirmation Dialog -->
    <Teleport to="body">
        <div
            v-if="reopenTargetPlayer"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4"
            style="background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(2px);"
            @click.self="reopenTargetPlayer = null"
        >
            <div
                class="w-full max-w-md rounded-3xl border p-6 shadow-2xl"
                style="
                    background: var(--gl-surface);
                    border-color: var(--gl-border);
                "
            >
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-500">
                    <RotateCcw class="h-6 w-6" :stroke-width="2" />
                </div>
                <h3 class="text-lg font-bold" style="color: var(--gl-text-primary)">
                    Reopen Game for Student?
                </h3>
                <p class="mt-2 text-xs leading-relaxed" style="color: var(--gl-text-secondary)">
                    This will reset the progress, score, and recorded answers for
                    <strong class="text-[var(--gl-text-primary)]">{{ reopenTargetPlayer.student_name }}</strong>
                    on <strong>{{ activeGameForPlayers?.title }}</strong>.
                    <span v-if="reopenTargetPlayer.earned_xp > 0" class="block mt-2 font-semibold text-amber-600 dark:text-amber-400">
                        <AlertTriangle class="mr-1 inline h-3.5 w-3.5" :stroke-width="2.5" />
                        {{ reopenTargetPlayer.earned_xp }} earned XP will be deducted from this student's total.
                    </span>
                    The student will be permitted to play and submit again fresh.
                </p>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="reopenTargetPlayer = null"
                        class="rounded-xl px-4 py-2 text-xs font-semibold transition-all hover:bg-[var(--gl-surface-2)]"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-primary);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="reopening"
                        @click="executeReopen"
                        class="flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-bold text-white transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                        style="
                            background: linear-gradient(135deg, #ef4444, #dc2626);
                            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35);
                        "
                    >
                        <RotateCcw v-if="!reopening" class="h-3.5 w-3.5" :stroke-width="2" />
                        <span>{{ reopening ? 'Reopening...' : 'Yes, Reopen Game' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Dedicated Top 10 Leaderboard Modal -->
    <Teleport to="body">
        <div
            v-if="showTop10Modal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
            style="background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(3px);"
            @click.self="closeTop10Modal"
        >
            <div
                class="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border shadow-2xl"
                style="
                    background: var(--gl-surface);
                    border-color: var(--gl-border);
                "
            >
                <!-- Modal Header -->
                <div
                    class="flex shrink-0 items-center justify-between border-b px-6 py-4"
                    style="border-color: var(--gl-border); background: var(--gl-surface-2);"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl text-white shadow-md"
                            style="background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 0 12px rgba(245, 158, 11, 0.35);"
                        >
                            <Trophy class="h-5 w-5" :stroke-width="2" />
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold tracking-tight" style="color: var(--gl-text-primary)">
                                Top 10 Leaderboard
                            </h3>
                            <p class="text-xs" style="color: var(--gl-text-muted)">
                                {{ selectedTop10Game?.title }} · {{ selectedTop10Game?.subject }}
                            </p>
                        </div>
                    </div>
                    <button
                        @click="closeTop10Modal"
                        class="flex h-8 w-8 items-center justify-center rounded-full transition-colors hover:bg-[var(--gl-surface)]"
                        style="color: var(--gl-text-muted)"
                    >
                        <X class="h-4 w-4" :stroke-width="2.5" />
                    </button>
                </div>

                <!-- Session Selector (if multiple live sessions exist) -->
                <div
                    v-if="top10LiveRooms.length > 1"
                    class="border-b px-6 py-2.5 flex items-center gap-2 overflow-x-auto no-scrollbar"
                    style="border-color: var(--gl-border); background: var(--gl-surface-2);"
                >
                    <span class="text-[11px] font-bold shrink-0" style="color: var(--gl-text-muted)">Session PIN:</span>
                    <button
                        v-for="r in top10LiveRooms"
                        :key="r.id"
                        type="button"
                        @click="selectedRoomPin = r.pin"
                        class="shrink-0 rounded-xl px-3 py-1 text-xs font-bold transition-all"
                        :class="selectedRoomPin === r.pin
                            ? 'bg-amber-500 text-white shadow-sm'
                            : 'border border-[var(--gl-border)] bg-[var(--gl-surface)] text-[var(--gl-text-secondary)] hover:text-[var(--gl-text-primary)]'"
                    >
                        PIN {{ r.pin }}
                        <span class="ml-1 text-[10px] opacity-75">({{ r.total_participants }}p)</span>
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="flex-1 overflow-y-auto p-6">
                    <!-- Loading state -->
                    <div v-if="loadingTop10" class="py-12 text-center">
                        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-amber-500 border-t-transparent"></div>
                        <p class="mt-3 text-xs" style="color: var(--gl-text-muted)">Loading leaderboard standings...</p>
                    </div>

                    <!-- Live Room Leaderboard -->
                    <div v-else-if="currentSelectedRoom">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] p-3.5">
                            <div class="flex items-center gap-2">
                                <span class="rounded-lg bg-emerald-500/15 px-2 py-0.5 font-mono text-xs font-black text-emerald-600">
                                    PIN: {{ currentSelectedRoom.pin }}
                                </span>
                                <span class="text-xs font-semibold" style="color: var(--gl-text-secondary)">
                                    {{ currentSelectedRoom.created_at }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold" style="color: var(--gl-text-muted)">
                                    {{ currentSelectedRoom.total_participants }} participant(s)
                                </span>
                                <Link
                                    v-if="currentSelectedRoom.status !== 'finished'"
                                    :href="`/teacher/games/rooms/${currentSelectedRoom.pin}/host`"
                                    class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-bold text-white transition-all hover:bg-emerald-500"
                                >
                                    <Play class="h-3 w-3 fill-current" /> Host
                                </Link>
                                <Link
                                    v-else
                                    :href="`/teacher/games/rooms/${currentSelectedRoom.pin}/host`"
                                    class="inline-flex items-center gap-1 rounded-lg border border-amber-500/40 bg-amber-500/10 px-2.5 py-1 text-xs font-bold text-amber-600 dark:text-amber-400 transition-all hover:bg-amber-500/20 shadow-sm"
                                    title="Open the Host Podium presentation screen"
                                >
                                    <Crown class="h-3 w-3 text-amber-500" /> Podium
                                </Link>
                                <button
                                    type="button"
                                    @click="deleteLiveRoom(currentSelectedRoom)"
                                    class="inline-flex items-center gap-1 rounded-lg border border-red-500/30 bg-red-500/10 px-2.5 py-1 text-xs font-bold text-red-600 transition-all hover:bg-red-500/20 active:scale-95 shadow-sm"
                                    title="Delete this live arena session"
                                >
                                    <Trash2 class="h-3 w-3" /> Delete
                                </button>
                            </div>
                        </div>

                        <!-- Empty state for session -->
                        <div v-if="!currentSelectedRoom.leaderboard?.length" class="py-10 text-center text-xs" style="color: var(--gl-text-muted)">
                            <Trophy class="mx-auto h-10 w-10 text-amber-500/40 mb-2" />
                            No scores recorded yet for this session.
                        </div>

                        <!-- Leaderboard entries -->
                        <div v-else class="space-y-2">
                            <div
                                v-for="entry in currentSelectedRoom.leaderboard"
                                :key="entry.user_id || entry.rank"
                                class="flex items-center justify-between rounded-2xl border p-3.5 transition-all"
                                :class="[
                                    entry.rank === 1 ? 'border-amber-500/40 bg-amber-500/10' :
                                    entry.rank === 2 ? 'border-slate-400/40 bg-slate-400/10' :
                                    entry.rank === 3 ? 'border-amber-700/40 bg-amber-700/10' :
                                    'border-[var(--gl-border)] bg-[var(--gl-surface-2)]'
                                ]"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <span
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-xs font-black shadow-sm"
                                        :class="[
                                            entry.rank === 1 ? 'bg-amber-500 text-white shadow-amber-500/30' :
                                            entry.rank === 2 ? 'bg-slate-400 text-white shadow-slate-400/30' :
                                            entry.rank === 3 ? 'bg-amber-700 text-white shadow-amber-700/30' :
                                            'bg-[var(--gl-surface)] text-[var(--gl-text-secondary)] border border-[var(--gl-border)]'
                                        ]"
                                    >
                                        {{ entry.rank }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold" style="color: var(--gl-text-primary)">
                                            {{ entry.name }}
                                        </p>
                                        <p class="truncate text-xs" style="color: var(--gl-text-muted)">
                                            {{ entry.grade || 'No grade' }} · {{ entry.email || '' }}
                                        </p>
                                    </div>
                                    <span v-if="entry.streak > 1" class="inline-flex shrink-0 items-center gap-0.5 rounded-full bg-orange-500/15 px-2 py-0.5 text-[10px] font-extrabold text-orange-600">
                                        🔥 {{ entry.streak }}x
                                    </span>
                                </div>
                                <span class="font-mono text-base font-black text-emerald-600 dark:text-emerald-400 shrink-0 ml-3">
                                    {{ Number(entry.score).toLocaleString() }} pts
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Fallback: Arcade Top 10 if no live rooms exist but arcade players exist -->
                    <div v-else-if="arcadeTop10.length > 0">
                        <div class="mb-4 rounded-2xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] p-3">
                            <p class="text-xs font-semibold" style="color: var(--gl-text-secondary)">
                                Top scoring students from solo arcade attempts:
                            </p>
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="(entry, idx) in arcadeTop10"
                                :key="entry.id || entry.user_id"
                                class="flex items-center justify-between rounded-2xl border p-3.5 transition-all"
                                :class="[
                                    idx === 0 ? 'border-amber-500/40 bg-amber-500/10' :
                                    idx === 1 ? 'border-slate-400/40 bg-slate-400/10' :
                                    idx === 2 ? 'border-amber-700/40 bg-amber-700/10' :
                                    'border-[var(--gl-border)] bg-[var(--gl-surface-2)]'
                                ]"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <span
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-xs font-black shadow-sm"
                                        :class="[
                                            idx === 0 ? 'bg-amber-500 text-white shadow-amber-500/30' :
                                            idx === 1 ? 'bg-slate-400 text-white shadow-slate-400/30' :
                                            idx === 2 ? 'bg-amber-700 text-white shadow-amber-700/30' :
                                            'bg-[var(--gl-surface)] text-[var(--gl-text-secondary)] border border-[var(--gl-border)]'
                                        ]"
                                    >
                                        {{ idx + 1 }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold" style="color: var(--gl-text-primary)">
                                            {{ entry.student_name }}
                                        </p>
                                        <p class="truncate text-xs" style="color: var(--gl-text-muted)">
                                            {{ entry.student_grade }} · {{ entry.completed_at || 'In progress' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 ml-3">
                                    <p class="font-mono text-sm font-black text-emerald-600 dark:text-emerald-400">
                                        {{ entry.score }} / {{ entry.total }} ({{ entry.percentage }}%)
                                    </p>
                                    <p v-if="entry.earned_xp > 0" class="text-[11px] font-bold text-amber-600">
                                        +{{ entry.earned_xp }} XP
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No records at all -->
                    <div v-else class="py-12 text-center">
                        <Trophy class="mx-auto h-12 w-12 text-[var(--gl-text-muted)] opacity-30 mb-3" />
                        <p class="text-sm font-bold" style="color: var(--gl-text-primary)">
                            No Top 10 standings yet
                        </p>
                        <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                            Host a live match or wait for students to complete this game.
                        </p>
                        <button
                            v-if="selectedTop10Game && isLiveGame(selectedTop10Game)"
                            type="button"
                            @click="closeTop10Modal(); hostLiveGame(selectedTop10Game)"
                            class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-500"
                        >
                            <Zap class="h-3.5 w-3.5" /> Host Live Match
                        </button>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="border-t px-6 py-3 flex items-center justify-between text-xs"
                    style="border-color: var(--gl-border); background: var(--gl-surface-2);"
                >
                    <span style="color: var(--gl-text-muted)">
                        Top 10 Session Leaderboards
                    </span>
                    <button
                        type="button"
                        @click="closeTop10Modal"
                        class="rounded-xl border px-4 py-1.5 font-bold transition-colors hover:bg-[var(--gl-surface)]"
                        style="
                            border-color: var(--gl-border);
                            color: var(--gl-text-primary);
                            background: var(--gl-surface);
                        "
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, X, Gamepad2, Eye, EyeOff, Search, Ellipsis, Zap, AlertTriangle, Layers, Play, Users, RotateCcw, CheckCircle2, ListChecks, Copy, Check, ExternalLink, Trophy, Crown } from '@lucide/vue';

import { computed, reactive, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    games: any[];
    activeRooms?: any[];
    recentFinishedRooms?: any[];
    isSuperadmin?: boolean;
    teachers?: any[];
    subjects?: any[];
    gradeLevels?: any[];
    quarters?: any[];
}>();
const page = usePage();
const flash = computed(() => (page.props as any).flash);

const activeRoomsList = computed(() => props.activeRooms || []);
const recentFinishedRoomsList = computed(() => props.recentFinishedRooms || []);
const activeRoomsMap = computed(() => {
    const map: Record<number, any> = {};

    for (const r of activeRoomsList.value) {
        if (r.game_id) {
map[r.game_id] = r;
}
    }

    return map;
});

const copiedPin = ref<string | null>(null);
function copyRoomPin(pin: string) {
    navigator.clipboard.writeText(pin);
    copiedPin.value = pin;
    toast.success(`Game PIN ${pin} copied to clipboard!`);
    setTimeout(() => {
        if (copiedPin.value === pin) {
copiedPin.value = null;
}
    }, 2000);
}

function deleteLiveRoom(room: any) {
    if (!confirm(`Delete Live Match Arena (PIN: ${room.pin})? Students will no longer see this live room.`)) {
return;
}

    router.delete(`/teacher/games/rooms/${room.pin}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Live room (PIN: ${room.pin}) deleted.`);
            playersLiveRooms.value = playersLiveRooms.value.filter((r) => r.pin !== room.pin && r.id !== room.id);
            top10LiveRooms.value = top10LiveRooms.value.filter((r) => r.pin !== room.pin && r.id !== room.id);

            if (selectedRoomPin.value === room.pin) {
                selectedRoomPin.value = top10LiveRooms.value.length > 0 ? top10LiveRooms.value[0].pin : null;
            }
        },
    });
}

const localGames = ref<any[]>([...(props.games || [])]);

watch(
    () => props.games,
    (val) => {
        localGames.value = [...(val || [])];
    },
    { deep: true },
);

const TYPE_LABELS: Record<string, string> = {
    flashcard: 'Flashcards',
    quiz: 'Quiz',
    speedquiz: 'Speed Quiz',
    fillblank: 'Fill Blanks',
    truefalse: 'True / False',
    wordjumble: 'Word Jumble',
    colorharmony: 'Color Harmony',
    memorymatch: 'Memory Match',
    hangman: 'Hangman',
    dragdrop: 'Drag & Drop',
    ordering: 'Ordering',
    fourpics: '4 Pics 1 Word',
    crossword: 'Crossword',
    wordsearch: 'Word Search',
    codepuzzle: 'Code Puzzle',
};

const query = ref('');
const visTab = ref('all');
const gradeFilter = ref('');
const quarterFilter = ref('');
const openMenu = ref<number | null>(null);

// Players Modal State
const showPlayersModal = ref(false);
const activeGameForPlayers = ref<any>(null);
const loadingPlayers = ref(false);
const playersList = ref<any[]>([]);
const playersLiveRooms = ref<any[]>([]);
const pendingRequestsList = ref<any[]>([]);
const playersGameDetails = ref<any>(null);
const playerSearch = ref('');
const playerStatusFilter = ref('all');
const expandedPlayerId = ref<number | null>(null);

// Reopen Confirmation State
const reopenTargetPlayer = ref<any>(null);
const reopening = ref(false);

function playerEffectiveScore(player: any): number {
    const rawScore = Number(player?.score ?? 0);
    const correctCount = Array.isArray(player?.correct_card_ids) ? player.correct_card_ids.length : 0;

    return Math.max(rawScore, correctCount);
}

function playerPercentage(player: any): number {
    const total = Number(player?.total ?? 0);

    if (!total) {
return 0;
}

    const score = playerEffectiveScore(player);

    return Math.min(100, Math.round((score / total) * 100));
}

async function openPlayersModal(game: any) {
    activeGameForPlayers.value = game;
    showPlayersModal.value = true;
    loadingPlayers.value = true;
    playersList.value = [];
    playersGameDetails.value = null;
    playerSearch.value = '';
    playerStatusFilter.value = 'all';
    expandedPlayerId.value = null;

    try {
        const res = await fetch(`/teacher/games/${game.id}/players`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (res.ok) {
            const data = await res.json();
            playersList.value = data.players || [];
            playersLiveRooms.value = data.live_rooms || [];
            pendingRequestsList.value = data.pending_requests || [];
            playersGameDetails.value = data.game || null;
            const completedCount = playersList.value.filter((p) => p.completed).length;
            game.players_count = completedCount;
            const lg = localGames.value.find((g) => g.id === game.id);

            if (lg) {
lg.players_count = completedCount;
}
        }
    } catch {
        /* silent */
    } finally {
        loadingPlayers.value = false;
    }
}

function closePlayersModal() {
    showPlayersModal.value = false;
    activeGameForPlayers.value = null;
    playersList.value = [];
    playersLiveRooms.value = [];
    pendingRequestsList.value = [];
    expandedPlayerId.value = null;
    reopenTargetPlayer.value = null;
}

// Top 10 Leaderboard Modal State
const showTop10Modal = ref(false);
const selectedTop10Game = ref<any>(null);
const loadingTop10 = ref(false);
const top10LiveRooms = ref<any[]>([]);
const top10ArcadePlayers = ref<any[]>([]);
const selectedRoomPin = ref<string>('');

const currentSelectedRoom = computed(() => {
    if (!top10LiveRooms.value.length) {
return null;
}

    return top10LiveRooms.value.find((r) => r.pin === selectedRoomPin.value) || top10LiveRooms.value[0];
});

const arcadeTop10 = computed(() => {
    return [...top10ArcadePlayers.value]
        .sort((a, b) => (b.score ?? 0) - (a.score ?? 0))
        .slice(0, 10);
});

async function openTop10ForGame(game: any, defaultPin?: string) {
    if (!game) {
return;
}

    selectedTop10Game.value = game;
    showTop10Modal.value = true;
    loadingTop10.value = true;
    top10LiveRooms.value = [];
    top10ArcadePlayers.value = [];
    selectedRoomPin.value = defaultPin || '';

    // Pre-populate immediately from finished rooms if available for instant display
    const matchingRooms = recentFinishedRoomsList.value.filter((r: any) => r.game_id === game.id);

    if (matchingRooms.length > 0) {
        top10LiveRooms.value = matchingRooms.map((r: any) => ({
            id: r.id,
            pin: r.pin,
            status: r.status,
            total_participants: r.total_participants ?? (r.leaderboard?.length || 0),
            created_at: r.created_at,
            leaderboard: r.leaderboard || [],
        }));

        if (!selectedRoomPin.value) {
            selectedRoomPin.value = top10LiveRooms.value[0].pin;
        }
    }

    try {
        const res = await fetch(`/teacher/games/${game.id}/players`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (res.ok) {
            const data = await res.json();

            if (data.live_rooms && data.live_rooms.length > 0) {
                top10LiveRooms.value = data.live_rooms;

                if (!selectedRoomPin.value) {
                    selectedRoomPin.value = data.live_rooms[0].pin;
                }
            }

            top10ArcadePlayers.value = data.players || [];
        }
    } catch {
        /* silent */
    } finally {
        loadingTop10.value = false;
    }
}

function openTop10ForRoom(room: any) {
    const game = localGames.value.find((g: any) => g.id === room.game_id) || room.game || { id: room.game_id, title: room.title || room.game?.title || 'Live Match' };

    if (room.leaderboard && room.leaderboard.length > 0) {
        top10LiveRooms.value = [{
            id: room.id,
            pin: room.pin,
            status: room.status,
            total_participants: room.total_participants ?? room.leaderboard.length,
            created_at: room.created_at,
            leaderboard: room.leaderboard,
        }];
        selectedRoomPin.value = room.pin;
    }

    openTop10ForGame(game, room.pin);
}

function closeTop10Modal() {
    showTop10Modal.value = false;
    selectedTop10Game.value = null;
    top10LiveRooms.value = [];
    top10ArcadePlayers.value = [];
    selectedRoomPin.value = '';
}

const filteredPlayers = computed(() => {
    const q = playerSearch.value.trim().toLowerCase();

    return playersList.value.filter((p) => {
        if (playerStatusFilter.value === 'completed' && !p.completed) {
return false;
}

        if (playerStatusFilter.value === 'inprogress' && p.completed) {
return false;
}

        if (!q) {
return true;
}

        return (
            (p.student_name || '').toLowerCase().includes(q) ||
            (p.student_email || '').toLowerCase().includes(q) ||
            (p.student_grade || '').toLowerCase().includes(q)
        );
    });
});

function confirmReopen(player: any) {
    reopenTargetPlayer.value = player;
}

async function approveRequestAndReopen(req: any) {
    if (!activeGameForPlayers.value) {
return;
}

    const game = activeGameForPlayers.value;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    try {
        const res = await fetch(`/teacher/games/${game.id}/reopen/${req.user_id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        });

        if (res.ok) {
            const data = await res.json().catch(() => null);
            pendingRequestsList.value = pendingRequestsList.value.filter((r) => r.id !== req.id);
            playersList.value = playersList.value.filter((p) => p.user_id !== req.user_id);

            if (typeof game.pending_reopen_requests_count === 'number' && game.pending_reopen_requests_count > 0) {
                game.pending_reopen_requests_count--;
            }

            const lg = localGames.value.find((g) => g.id === game.id);

            if (lg && typeof lg.pending_reopen_requests_count === 'number' && lg.pending_reopen_requests_count > 0) {
                lg.pending_reopen_requests_count--;
            }

            toast.success(data?.message || `Game reopened for ${req.student_name}.`);
        } else {
            toast.error('Failed to reopen game for student.');
        }
    } catch {
        toast.error('An error occurred while reopening game.');
    }
}

async function rejectRequest(req: any) {
    if (!activeGameForPlayers.value) {
return;
}

    const game = activeGameForPlayers.value;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    try {
        const res = await fetch(`/teacher/games/${game.id}/reopen-requests/${req.id}/reject`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        });

        if (res.ok) {
            pendingRequestsList.value = pendingRequestsList.value.filter((r) => r.id !== req.id);

            if (typeof game.pending_reopen_requests_count === 'number' && game.pending_reopen_requests_count > 0) {
                game.pending_reopen_requests_count--;
            }

            const lg = localGames.value.find((g) => g.id === game.id);

            if (lg && typeof lg.pending_reopen_requests_count === 'number' && lg.pending_reopen_requests_count > 0) {
                lg.pending_reopen_requests_count--;
            }

            toast.info(`Reopen request rejected.`);
        } else {
            toast.error('Failed to reject reopen request.');
        }
    } catch {
        toast.error('An error occurred while rejecting request.');
    }
}

async function executeReopen() {
    if (!reopenTargetPlayer.value || !activeGameForPlayers.value) {
return;
}

    reopening.value = true;
    const player = reopenTargetPlayer.value;
    const game = activeGameForPlayers.value;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    try {
        const res = await fetch(`/teacher/games/${game.id}/reopen/${player.user_id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        });

        if (res.ok) {
            const data = await res.json().catch(() => null);
            playersList.value = playersList.value.filter((p) => p.user_id !== player.user_id);

            if (typeof game.players_count === 'number' && game.players_count > 0) {
                game.players_count--;
            }

            const lg = localGames.value.find((g) => g.id === game.id);

            if (lg && typeof lg.players_count === 'number' && lg.players_count > 0) {
                lg.players_count--;
            }

            toast.success(data?.message || `Game reopened for ${player.student_name}.`);
            reopenTargetPlayer.value = null;
        } else {
            toast.error('Failed to reopen game.');
        }
    } catch {
        toast.error('An error occurred while reopening game.');
    } finally {
        reopening.value = false;
    }
}
function isLiveGame(g: any): boolean {
    return Boolean(
        g?.is_live ||
        (g?.live_rooms_count ?? 0) > 0 ||
        activeRoomsMap.value[g?.id] ||
        (g?.description && typeof g.description === 'string' && g.description.toLowerCase().includes('live match arena')) ||
        (g?.description && typeof g.description === 'string' && g.description.toLowerCase().includes('live arena'))
    );
}

const liveArenaCount = computed(
    () => localGames.value.filter((g: any) => isLiveGame(g)).length,
);
const visibleCount = computed(
    () => localGames.value.filter((g: any) => !g.hidden && !isLiveGame(g)).length,
);
const hiddenCount = computed(
    () => localGames.value.filter((g: any) => g.hidden && !isLiveGame(g)).length,
);

const visTabs = computed(() => [
    { value: 'all', label: 'All', count: localGames.value.length },
    { value: 'visible', label: 'Arcade', count: visibleCount.value },
    { value: 'live', label: 'Live Arena', count: liveArenaCount.value },
    { value: 'hidden', label: 'Hidden', count: hiddenCount.value },
]);

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();

    return localGames.value.filter((g: any) => {
        if (visTab.value === 'visible') {
            if (g.hidden || isLiveGame(g)) {
return false;
}
        }

        if (visTab.value === 'live') {
            if (!isLiveGame(g)) {
return false;
}
        }

        if (visTab.value === 'hidden') {
            if (!g.hidden || isLiveGame(g)) {
return false;
}
        }

        if (gradeFilter.value && (g.grade || '') !== gradeFilter.value) {
            return false;
        }

        if (quarterFilter.value && Number(g.quarter_id) !== Number(quarterFilter.value)) {
            return false;
        }

        if (
            q &&
            !(g.title || '').toLowerCase().includes(q) &&
            !(g.subject || '').toLowerCase().includes(q) &&
            !(g.grade || '').toLowerCase().includes(q)
        ) {
return false;
}

        return true;
    });
});

watch([query, visTab, gradeFilter, quarterFilter], () => {
    openMenu.value = null;
});
const totalCards = computed(() =>
    localGames.value.reduce((a: number, g: any) => a + (g.cards?.length ?? 0), 0),
);
const totalXp = computed(() =>
    localGames.value.reduce((a: number, g: any) => a + (g.xp_reward ?? 0), 0),
);

const selected = ref<number[]>([]);
const allChecked = computed(
    () =>
        filtered.value.length > 0 &&
        selected.value.length === filtered.value.length,
);

function toggleAll() {
    selected.value = allChecked.value
        ? []
        : filtered.value.map((g: any) => g.id);
}

function bulkVisible(hidden: boolean) {
    if (!selected.value.length) {
return;
}

    router.patch(
        '/teacher/games/bulk-visibility',
        { ids: selected.value, hidden },
        {
            preserveScroll: true,
            onSuccess: () => {
                selected.value = [];
            },
        },
    );
}

async function bulkAutoReopen(enabled: boolean) {
    if (!selected.value.length) {
return;
}

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const ids = [...selected.value];

    try {
        const res = await fetch('/teacher/games/bulk-auto-reopen', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ids, auto_reopen: enabled }),
        });
        const data = await res.json();

        if (!res.ok || !data.ok) {
            toast.error(data?.message || 'Failed to update auto-reopen settings.');

            return;
        }

        const selectedIds = new Set(ids);
        localGames.value.forEach((game: any) => {
            if (selectedIds.has(game.id)) {
game.auto_reopen = data.auto_reopen;
}
        });

        if (activeGameForPlayers.value && selectedIds.has(activeGameForPlayers.value.id)) {
            activeGameForPlayers.value.auto_reopen = data.auto_reopen;
        }

        selected.value = [];
        toast.success(data.message || `Auto-reopen ${enabled ? 'enabled' : 'disabled'} for selected games.`);
    } catch {
        toast.error('An error occurred while updating auto-reopen settings.');
    }
}

function bulkDelete() {
    if (!selected.value.length) {
return;
}

    if (
        !confirm(
            `Delete ${selected.value.length} game(s)? This cannot be undone.`,
        )
    ) {
return;
}

    const toDeleteIds = new Set(selected.value);
    const backup = [...localGames.value];
    localGames.value = localGames.value.filter((g: any) => !toDeleteIds.has(g.id));
    const idsToDelete = [...selected.value];
    selected.value = [];
    router.delete('/teacher/games/bulk', {
        data: { ids: idsToDelete },
        preserveScroll: true,
        onError: () => {
            localGames.value = backup;
        },
    });
}

const showForm = ref(false);
const editingId = ref<number | null>(null);
const saving = ref(false);

const form = reactive({
    title: '',
    subject: '',
    subject_id: null as number | null,
    grade: '',
    quarter_id: null as number | null,
    description: '',
    xp_reward: 10,
    type: 'flashcard',
    auto_reopen: false,
    teacher_id: null as number | null,
    cards: [{ question: '', answer: '', color: '', options: [] as string[] }],
});

const typeMeta: Record<string, { section: string; add: string; hint: string }> =
    {
        flashcard: {
            section: 'Flashcards',
            add: 'Add Flashcard',
            hint: 'Question on the front, answer on the back.',
        },
        quiz: {
            section: 'Questions',
            add: 'Add Question',
            hint: 'Students pick the correct option.',
        },
        fillblank: {
            section: 'Fill in the Blank',
            add: 'Add Sentence',
            hint: 'Use ___ in the question where the answer goes.',
        },
        truefalse: {
            section: 'Statements',
            add: 'Add Statement',
            hint: 'Students judge each statement true or false.',
        },
        wordjumble: {
            section: 'Words',
            add: 'Add Word',
            hint: 'Give a hint; the answer letters are scrambled.',
        },
        colorharmony: {
            section: 'Color Pairs',
            add: 'Add Color Pair',
            hint: 'Students pick the color that harmonizes with the base color.',
        },
        memorymatch: {
            section: 'Matching Pairs',
            add: 'Add Pair',
            hint: 'Each term is matched with its answer card.',
        },
        hangman: {
            section: 'Hangman Words',
            add: 'Add Word',
            hint: 'Give a hint; students guess the word letter by letter (max 6 mistakes).',
        },
        speedquiz: {
            section: 'Questions',
            add: 'Add Question',
            hint: 'Students answer before the 15-second timer runs out.',
        },
        dragdrop: {
            section: 'Matching Pairs',
            add: 'Add Pair',
            hint: 'Students drag each term onto its definition.',
        },
        ordering: {
            section: 'Steps',
            add: 'Add Step',
            hint: 'List the steps in the correct order — students rearrange them.',
        },
        fourpics: {
            section: 'Puzzles',
            add: 'Add Puzzle',
            hint: 'Provide 4 image URLs and the target answer word (students guess the word from 4 pictures).',
        },
        crossword: {
            section: 'Clues & Words',
            add: 'Add Word / Clue',
            hint: 'Enter clue in Question and answer word in Answer (letters are dynamically placed into the crossword grid).',
        },
        wordsearch: {
            section: 'Vocabulary Words',
            add: 'Add Word',
            hint: 'Enter category or hint in Question and search word in Answer (placed into the letter matrix).',
        },
        codepuzzle: {
            section: 'Code Lines (Execution Order)',
            add: 'Add Code Line',
            hint: 'Enter code lines in correct execution order in Answer. Question is an optional line description/comment.',
        },
    };

const typeMetaFor = computed(() => typeMeta[form.type] ?? typeMeta.flashcard);
const cardSectionLabel = computed(() => typeMetaFor.value.section);
const addCardLabel = computed(() => typeMetaFor.value.add);
const typeHint = computed(() => typeMetaFor.value.hint);

function randomHex() {
    return (
        '#' +
        Math.floor(Math.random() * 16777215)
            .toString(16)
            .padStart(6, '0')
            .toUpperCase()
    );
}
function hexToHsl(hex: string): [number, number, number] {
    const hx = hex.replace('#', '');
    const r = parseInt(hx.slice(0, 2), 16) / 255,
        g = parseInt(hx.slice(2, 4), 16) / 255,
        b = parseInt(hx.slice(4, 6), 16) / 255;
    const max = Math.max(r, g, b),
        min = Math.min(r, g, b);
    let h = 0,
        s = 0;
    const l = (max + min) / 2;

    if (max !== min) {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);

        switch (max) {
            case r:
                h = (g - b) / d + (g < b ? 6 : 0);
                break;
            case g:
                h = (b - r) / d + 2;
                break;
            default:
                h = (r - g) / d + 4;
        }

        h *= 60;
    }

    return [h, s, l];
}
function hslToHex(h: number, s: number, l: number): string {
    h = ((h % 360) + 360) % 360;
    s = Math.max(0, Math.min(1, s));
    l = Math.max(0, Math.min(1, l));
    const c = (1 - Math.abs(2 * l - 1)) * s;
    const x = c * (1 - Math.abs(((h / 60) % 2) - 1));
    const m = l - c / 2;
    let rgb: [number, number, number];

    if (h < 60) {
        rgb = [c, x, 0];
    } else if (h < 120) {
        rgb = [x, c, 0];
    } else if (h < 180) {
        rgb = [0, c, x];
    } else if (h < 240) {
        rgb = [0, x, c];
    } else if (h < 300) {
        rgb = [x, 0, c];
    } else {
        rgb = [c, 0, x];
    }

    const toHex = (v: number) =>
        Math.round((v + m) * 255)
            .toString(16)
            .padStart(2, '0')
            .toUpperCase();

    return '#' + toHex(rgb[0]) + toHex(rgb[1]) + toHex(rgb[2]);
}
function shuffleArray(arr: string[]) {
    const a = [...arr];

    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }

    return a;
}
function generatePalette(c: any) {
    if (!c.color || !/^#[0-9A-Fa-f]{6}$/.test(c.color)) {
        c.color = randomHex();
    }

    const [h, s, l] = hexToHsl(c.color);
    const candidates = [
        hslToHex((h + 180) % 360, s, l),
        hslToHex((h + 30) % 360, Math.min(0.85, s + 0.12), l),
        hslToHex((h - 30 + 360) % 360, Math.min(0.85, s + 0.12), l),
        hslToHex((h + 120) % 360, s, l),
        hslToHex(h, s, Math.min(0.85, l + 0.18)),
    ];
    const unique = [...new Set(candidates)];
    c.options = shuffleArray(unique);
    c.answer = c.options[Math.floor(Math.random() * c.options.length)];
}
function addCard() {
    if (form.type === 'colorharmony') {
        const c: any = {
            question: '',
            answer: '',
            color: randomHex(),
            options: [],
        };
        generatePalette(c);
        form.cards.push(c);
    } else if (form.type === 'fourpics') {
        form.cards.push({
            question: '',
            answer: '',
            color: '',
            options: ['', '', '', ''],
        });
    } else {
        form.cards.push({
            question: '',
            answer: '',
            color: '',
            options: [] as string[],
        });
    }
}

watch(
    () => form.type,
    (t) => {
        if (t === 'flashcard' && !editingId.value && form.xp_reward === 10) {
            form.xp_reward = 2;
        }

        if (t === 'colorharmony') {
            form.cards = form.cards.map((c: any) => {
                if (!c.color || !/^#[0-9A-Fa-f]{6}$/.test(c.color)) {
                    c.color = randomHex();
                }

                if (!c.options || !c.options.length) {
                    const [h, s, l] = hexToHsl(c.color);
                    c.options = shuffleArray([
                        hslToHex((h + 180) % 360, s, l),
                        hslToHex((h + 120) % 360, s, l),
                        hslToHex(h, s, Math.min(0.85, l + 0.18)),
                        hslToHex((h + 60) % 360, Math.min(0.85, s + 0.12), l),
                    ]);
                }

                if (!c.answer) {
                    c.answer = c.options[0];
                }

                return c;
            });
        }

        if (t === 'fourpics') {
            form.cards.forEach((c: any) => {
                if (!Array.isArray(c.options) || c.options.length < 4) {
                    c.options = [
                        c.options?.[0] || '',
                        c.options?.[1] || '',
                        c.options?.[2] || '',
                        c.options?.[3] || '',
                    ];
                }
            });
        }
    },
);

function openCreate() {
    editingId.value = null;
    form.title = '';
    form.subject = '';
    form.subject_id = null;
    form.grade = '';
    form.quarter_id = null;
    form.description = '';
    form.xp_reward = 10;
    form.type = 'flashcard';
    form.auto_reopen = false;
    form.teacher_id = null;
    form.cards = [{ question: '', answer: '', color: '', options: [] }];
    form.xp_reward = 2;
    showForm.value = true;
}

function hostLiveGame(g: any) {
    router.post(`/teacher/games/${g.id}/rooms`);
}

function openEdit(g: any) {
    editingId.value = g.id;
    form.title = g.title;
    form.subject = g.subject;
    form.subject_id = g.subject_id ?? null;
    form.grade = g.grade ?? '';
    form.quarter_id = g.quarter_id ?? null;
    form.description = g.description ?? '';
    form.xp_reward = g.xp_reward;
    form.type = g.type || 'flashcard';
    form.auto_reopen = Boolean(g.auto_reopen);
    form.teacher_id = g.teacher_id ?? null;
    form.cards = (g.cards || []).map((c: any) => ({
        question: c.question,
        answer: c.answer,
        color: c.color || randomHex(),
        options: c.options || [],
    }));

    if (form.type === 'colorharmony') {
        form.cards = form.cards.map((c: any) => {
            if (!/^#[0-9A-Fa-f]{6}$/.test(c.color)) {
                c.color = randomHex();
            }

            c.options =
                c.options && c.options.length
                    ? c.options
                    : [randomHex(), randomHex(), randomHex(), randomHex()];

            if (!c.answer) {
                c.answer = c.options[0];
            }

            return c;
        });
    }

    if (form.type === 'fourpics') {
        form.cards.forEach((c: any) => {
            if (!Array.isArray(c.options) || c.options.length < 4) {
                c.options = [
                    c.options?.[0] || '',
                    c.options?.[1] || '',
                    c.options?.[2] || '',
                    c.options?.[3] || '',
                ];
            }
        });
    }

    showForm.value = true;
}

function save() {
    if (form.type === 'fourpics') {
        form.cards.forEach((c: any) => {
            c.answer = (c.answer || '').trim().toUpperCase();
            c.question = (c.question || '').trim() || 'What word connects these 4 pictures?';

            if (!Array.isArray(c.options)) {
                c.options = ['', '', '', ''];
            }
        });
    } else if (form.type === 'crossword' || form.type === 'wordsearch') {
        form.cards.forEach((c: any) => {
            c.answer = (c.answer || '').trim().toUpperCase();

            if (!c.question || !c.question.trim()) {
                c.question = form.type === 'crossword' ? 'Crossword Clue' : 'Word Search';
            }
        });
    } else if (form.type === 'codepuzzle') {
        form.cards.forEach((c: any) => {
            c.answer = (c.answer || '').trim();

            if (!c.question || !c.question.trim()) {
                c.question = 'Code Step';
            }
        });
    }

    saving.value = true;
    const method = editingId.value ? 'put' : 'post';
    const url = editingId.value
        ? `/teacher/games/${editingId.value}`
        : '/teacher/games';
    router[method](url, form as any, {
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false;
            resetForm();
        },
        onFinish: () => {
            saving.value = false;
        },
    });
}

function resetForm() {
    editingId.value = null;
    form.title = '';
    form.subject = '';
    form.subject_id = null;
    form.grade = '';
    form.description = '';
    form.xp_reward = 10;
    form.type = 'flashcard';
    form.auto_reopen = false;
    form.teacher_id = null;
    form.cards = [{ question: '', answer: '', color: '', options: [] }];
}

async function toggleAutoReopen(g: any) {
    if (!g) {
return;
}

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    try {
        const res = await fetch(`/teacher/games/${g.id}/toggle-auto-reopen`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        });
        const data = await res.json();

        if (res.ok && data.ok) {
            g.auto_reopen = data.auto_reopen;
            const lg = localGames.value.find((item: any) => item.id === g.id);

            if (lg) {
lg.auto_reopen = data.auto_reopen;
}

            if (activeGameForPlayers.value && activeGameForPlayers.value.id === g.id) {
                activeGameForPlayers.value.auto_reopen = data.auto_reopen;
            }

            toast.success(data.message || `Auto-reopen ${data.auto_reopen ? 'enabled' : 'disabled'}`);
        } else {
            toast.error(data?.message || 'Failed to toggle auto-reopen.');
        }
    } catch {
        toast.error('An error occurred while toggling auto-reopen.');
    }
}

function reassign(g: any, teacherId: string | number) {
    if (!teacherId || Number(teacherId) === g.teacher_id) {
return;
}

    openMenu.value = null;
    router.patch(
        `/teacher/games/${g.id}/reassign`,
        { teacher_id: Number(teacherId) },
        { preserveScroll: true },
    );
}

function confirmDelete(g: any) {
    if (!confirm(`Delete "${g.title}"?`)) {
return;
}

    openMenu.value = null;
    const backup = [...localGames.value];
    localGames.value = localGames.value.filter((item: any) => item.id !== g.id);
    selected.value = selected.value.filter((id) => id !== g.id);
    router.delete(`/teacher/games/${g.id}`, {
        preserveScroll: true,
        onError: () => {
            localGames.value = backup;
        },
    });
}

function toggleHidden(g: any) {
    const prev = g.hidden;
    g.hidden = !g.hidden;
    router.patch(
        `/teacher/games/${g.id}/toggle-hidden`,
        {},
        {
            preserveScroll: true,
            onError: () => {
                g.hidden = prev;
            },
        },
    );
}
</script>
