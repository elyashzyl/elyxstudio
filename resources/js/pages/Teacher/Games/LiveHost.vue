<template>
    <Head :title="`Host: ${room.game.title} (PIN: ${room.pin})`" />

    <div class="mx-auto max-w-6xl space-y-6">
        <!-- Top Status Bar -->
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[var(--gl-border)] bg-[var(--gl-surface)] p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <Link
                    href="/teacher/games"
                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-[var(--gl-border)] transition-colors hover:bg-[var(--gl-surface-2)]"
                >
                    <ArrowLeft class="h-5 w-5" style="color: var(--gl-text-secondary)" />
                </Link>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            <span class="h-2 w-2 animate-ping rounded-full bg-emerald-500"></span>
                            LIVE ARENA
                        </span>
                        <span class="text-xs uppercase font-medium tracking-wide text-[var(--gl-text-muted)]">
                            {{ room.game.type }} · Grade {{ room.game.grade || 'All' }}
                        </span>
                    </div>
                    <h1 class="text-xl font-bold text-[var(--gl-text-primary)]">
                        {{ room.game.title }}
                    </h1>
                </div>
            </div>

            <!-- PIN Display Box -->
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-3 rounded-2xl border border-emerald-500/30 bg-emerald-500/5 px-5 py-2.5">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Game PIN</div>
                        <div class="text-2xl font-extrabold tracking-widest text-[var(--gl-text-primary)]">
                            {{ room.pin }}
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="copyPin"
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-600 text-white transition-transform hover:scale-105 active:scale-95"
                        title="Copy PIN"
                    >
                        <Check v-if="copied" class="h-4 w-4" />
                        <Copy v-else class="h-4 w-4" />
                    </button>
                </div>

                <a
                    :href="`/student/games/rooms/${encodeURIComponent(room.pin)}`"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center gap-1.5 rounded-xl border border-indigo-500/30 bg-indigo-500/10 px-4 py-2.5 text-sm font-semibold text-indigo-600 dark:text-indigo-400 transition-colors hover:bg-indigo-500/20 active:scale-95"
                    title="Open student player view in a new tab"
                >
                    <ExternalLink class="h-4 w-4" />
                    <span class="hidden sm:inline">View Player Screen</span>
                    <span class="sm:hidden">Player View</span>
                </a>

                <button
                    type="button"
                    @click="showHostLeaderboardModal = true"
                    class="flex items-center gap-1.5 rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-2.5 text-sm font-bold text-amber-600 dark:text-amber-400 transition-colors hover:bg-amber-500/20 active:scale-95 shadow-sm"
                    title="View Top 10 Live Standings"
                >
                    <Trophy class="h-4 w-4 text-amber-500" />
                    <span>Top 10</span>
                </button>

                <button
                    v-if="status === 'active'"
                    type="button"
                    @click="endGame"
                    class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-2.5 text-sm font-semibold text-amber-600 transition-colors hover:bg-amber-500/20"
                >
                    End Match
                </button>

                <button
                    type="button"
                    @click="deleteRoom"
                    class="flex items-center gap-1.5 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-2.5 text-sm font-semibold text-red-600 transition-colors hover:bg-red-500/20 active:scale-95"
                    title="Delete and close this live arena session"
                >
                    <Trash2 class="h-4 w-4" />
                    <span>Delete Session</span>
                </button>
            </div>
        </div>

        <!-- 1. WAITING LOBBY -->
        <div v-if="status === 'waiting'" class="space-y-6">
            <div class="relative overflow-hidden rounded-3xl border border-[var(--gl-border)] bg-[var(--gl-surface)] p-8 text-center shadow-lg sm:p-12">
                <div class="mx-auto max-w-xl space-y-4">
                    <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600">
                        <Users class="h-8 w-8" />
                    </div>
                    <h2 class="text-3xl font-extrabold text-[var(--gl-text-primary)]">
                        Waiting for Players to Join...
                    </h2>
                    <p class="text-sm text-[var(--gl-text-secondary)]">
                        Tell students to go to <span class="font-bold text-emerald-600">Arcade &gt; Join Live Match</span> and enter PIN:
                    </p>
                    <div class="inline-block rounded-2xl bg-[var(--gl-surface-2)] px-8 py-3 font-mono text-4xl font-extrabold tracking-widest text-[var(--gl-text-primary)]">
                        {{ room.pin }}
                    </div>

                    <div class="pt-4 flex flex-wrap items-center justify-center gap-3">
                        <button
                            type="button"
                            @click="startGame"
                            :disabled="starting"
                            class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-8 py-4 text-base font-bold text-white shadow-lg shadow-emerald-600/30 transition-all hover:scale-105 hover:bg-emerald-500 active:scale-95 disabled:opacity-50"
                        >
                            <Play class="h-5 w-5 fill-current" />
                            Start Match ({{ connectedPlayers.length }} Ready)
                        </button>
                        <a
                            :href="`/student/games/rooms/${encodeURIComponent(room.pin)}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 rounded-2xl border border-indigo-500/40 bg-indigo-500/10 px-6 py-4 text-base font-bold text-indigo-600 dark:text-indigo-400 transition-all hover:bg-indigo-500/20 active:scale-95"
                            title="Open the student player view in a new tab"
                        >
                            <ExternalLink class="h-5 w-5" />
                            <span>Preview Player Screen</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Connected Players Grid -->
            <div class="rounded-2xl border border-[var(--gl-border)] bg-[var(--gl-surface)] p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-[var(--gl-text-muted)]">
                        <Users class="h-4 w-4" /> Connected Players ({{ connectedPlayers.length }})
                    </h3>
                </div>

                <div v-if="connectedPlayers.length === 0" class="py-10 text-center text-sm text-[var(--gl-text-muted)]">
                    No students have joined yet. Waiting on channel...
                </div>

                <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                    <div
                        v-for="player in connectedPlayers"
                        :key="player.id"
                        class="flex items-center gap-2.5 rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] p-2.5 transition-transform hover:scale-105"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/20 text-xs font-bold text-emerald-600">
                            {{ player.name.charAt(0) }}
                        </div>
                        <span class="truncate text-xs font-semibold text-[var(--gl-text-primary)]">
                            {{ player.name }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. ACTIVE QUESTION SCREEN -->
        <div v-else-if="status === 'active'" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left: Current Question Area -->
            <div class="space-y-6 lg:col-span-2">
                <!-- First Solver Announcement Banner -->
                <div
                    v-if="currentFirstSolver"
                    class="gl-fade-in flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-500/40 bg-amber-500/10 p-4 text-xs font-bold text-amber-700 dark:text-amber-400 shadow-sm"
                >
                    <div class="flex items-center gap-2">
                        <Zap class="h-4 w-4 fill-current text-amber-500 animate-bounce" />
                        <span>First Correct Answer: <strong>{{ currentFirstSolver.user_name }}</strong></span>
                    </div>
                    <span class="rounded-full bg-amber-500/20 px-2.5 py-0.5 text-[11px] font-black text-amber-600 dark:text-amber-300">
                        +500 Bonus Awarded
                    </span>
                </div>

                <div class="rounded-3xl border border-[var(--gl-border)] bg-[var(--gl-surface)] p-6 shadow-md sm:p-8">
                    <!-- Progress Header with 15s Countdown -->
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-[var(--gl-surface-2)] px-3 py-1 text-xs font-bold text-[var(--gl-text-secondary)]">
                                Question {{ currentCardIndex + 1 }} of {{ room.total_cards }}
                            </span>
                            <div
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 font-mono text-xs font-bold border transition-all"
                                :class="[
                                    autoAdvancePaused
                                        ? 'border-amber-500/30 bg-amber-500/10 text-amber-600 dark:text-amber-400'
                                        : timeLeft <= 5
                                        ? 'border-red-500/40 bg-red-500/10 text-red-600 animate-pulse'
                                        : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                ]"
                            >
                                <Timer class="h-3.5 w-3.5" />
                                <span>{{ autoAdvancePaused ? 'Auto-Next Paused' : `${timeLeft}s` }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="toggleAutoAdvance"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] px-3 py-1 text-xs font-bold text-[var(--gl-text-secondary)] transition-colors hover:text-[var(--gl-text-primary)]"
                                :title="autoAdvancePaused ? 'Resume 15s automatic progression' : 'Pause automatic progression'"
                            >
                                <Pause v-if="!autoAdvancePaused" class="h-3.5 w-3.5" />
                                <Play v-else class="h-3.5 w-3.5 fill-current text-emerald-600" />
                                <span>{{ autoAdvancePaused ? 'Resume Auto' : 'Pause Auto' }}</span>
                            </button>
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                <CheckCircle class="h-4 w-4" />
                                {{ answeredCount }} answered
                            </div>
                        </div>
                    </div>

                    <!-- 15s Countdown Progress Bar -->
                    <div class="mb-6 h-1.5 w-full overflow-hidden rounded-full bg-[var(--gl-surface-2)]">
                        <div
                            class="h-full transition-all duration-1000 ease-linear"
                            :class="[
                                autoAdvancePaused
                                    ? 'bg-amber-500'
                                    : timeLeft <= 5
                                    ? 'bg-red-500'
                                    : 'bg-emerald-500'
                            ]"
                            :style="{ width: `${autoAdvancePaused ? 100 : (timeLeft / 15) * 100}%` }"
                        ></div>
                    </div>

                    <!-- Question Prompt -->
                    <div class="mb-8 rounded-2xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] p-6 text-center">
                        <h2 class="text-xl font-bold leading-relaxed text-[var(--gl-text-primary)] sm:text-2xl">
                            {{ currentCard?.question }}
                        </h2>
                    </div>

                    <!-- Options Grid (if quiz / multiple choice) -->
                    <div v-if="currentCard?.options && currentCard.options.length" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div
                            v-for="(opt, idx) in currentCard.options"
                            :key="idx"
                            class="relative flex items-center gap-3 rounded-2xl border p-4 text-left transition-all"
                            :class="[
                                showAnswer && opt === currentCard?.answer
                                    ? 'border-emerald-500 bg-emerald-500/15 text-[var(--gl-text-primary)] ring-2 ring-emerald-500 shadow-sm'
                                    : 'border-[var(--gl-border)] bg-[var(--gl-surface-2)] text-[var(--gl-text-primary)]'
                            ]"
                        >
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-xs font-black text-white shadow-sm"
                                :style="getOptionBadgeStyle(idx)"
                            >
                                {{ ['A', 'B', 'C', 'D'][idx % 4] }}
                            </span>
                            <span class="flex-1 text-sm font-bold break-words leading-snug">
                                {{ opt }}
                            </span>
                            <span
                                v-if="showAnswer && opt === currentCard?.answer"
                                class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-2 py-0.5 text-[11px] font-extrabold text-emerald-600 dark:text-emerald-400"
                            >
                                <Check class="h-3 w-3" /> Correct
                            </span>
                        </div>
                    </div>

                    <!-- Answer reveal strip -->
                    <div class="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-[var(--gl-border)] pt-6">
                        <button
                            type="button"
                            @click="showAnswer = !showAnswer"
                            class="text-xs font-semibold text-[var(--gl-text-muted)] hover:text-[var(--gl-text-primary)]"
                        >
                            {{ showAnswer ? 'Hide Correct Answer' : 'Peek Correct Answer' }}
                        </button>
                        <div v-if="showAnswer" class="text-sm font-bold text-emerald-600">
                            Answer: {{ currentCard?.answer }}
                        </div>

                        <div class="flex items-center gap-3">
                            <span v-if="!autoAdvancePaused && timeLeft > 0" class="text-xs font-medium text-[var(--gl-text-muted)]">
                                Auto-advancing in <strong>{{ timeLeft }}s</strong>
                            </span>
                            <button
                                type="button"
                                @click="manualNextQuestion"
                                :disabled="advancing"
                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white transition-transform hover:scale-105 active:scale-95 disabled:opacity-50"
                            >
                                <SkipForward class="h-4 w-4" />
                                {{ currentCardIndex + 1 >= room.total_cards ? 'Finish Game' : 'Next Question' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Real-time Leaderboard -->
            <div class="rounded-3xl border border-[var(--gl-border)] bg-[var(--gl-surface)] p-6 shadow-md">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-[var(--gl-text-muted)]">
                        <Trophy class="h-4 w-4 text-amber-500" /> Top 10 Live Standings
                    </h3>
                    <span v-if="leaderboard.length" class="rounded-full bg-[var(--gl-surface-2)] px-2 py-0.5 text-[10px] font-extrabold text-[var(--gl-text-muted)]">
                        {{ Math.min(10, leaderboard.length) }} of {{ leaderboard.length }}
                    </span>
                </div>

                <div v-if="!leaderboard.length" class="py-10 text-center text-xs text-[var(--gl-text-muted)]">
                    No points scored yet.
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="entry in leaderboard.slice(0, 10)"
                        :key="entry.user_id"
                        class="flex items-center justify-between rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] p-2.5 transition-all"
                        :class="[
                            entry.rank === 1 ? 'border-amber-500/30 bg-amber-500/5' :
                            entry.rank === 2 ? 'border-slate-400/30 bg-slate-400/5' :
                            entry.rank === 3 ? 'border-amber-700/30 bg-amber-700/5' : ''
                        ]"
                    >
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-xs font-black shadow-sm"
                                :class="[
                                    entry.rank === 1 ? 'bg-amber-500 text-white' :
                                    entry.rank === 2 ? 'bg-slate-400 text-white' :
                                    entry.rank === 3 ? 'bg-amber-700 text-white' :
                                    'bg-[var(--gl-surface)] text-[var(--gl-text-secondary)] border border-[var(--gl-border)]'
                                ]"
                            >
                                {{ entry.rank }}
                            </span>
                            <span class="truncate text-xs font-semibold text-[var(--gl-text-primary)]">
                                {{ entry.name }}
                            </span>
                            <span v-if="entry.streak > 1" class="inline-flex shrink-0 items-center gap-0.5 text-[10px] font-bold text-orange-500">
                                <Flame class="h-3 w-3 fill-current" /> {{ entry.streak }}x
                            </span>
                        </div>
                        <span class="font-mono text-xs font-bold text-emerald-600 dark:text-emerald-400 shrink-0 ml-2">
                            {{ entry.score.toLocaleString() }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. FINISHED PODIUM SCREEN -->
        <div v-else-if="status === 'finished'" class="space-y-6">
            <div class="rounded-3xl border border-[var(--gl-border)] bg-[var(--gl-surface)] p-8 text-center shadow-lg sm:p-12">
                <div class="mx-auto max-w-xl space-y-4">
                    <Crown class="mx-auto h-12 w-12 text-amber-500 animate-bounce" />
                    <h2 class="text-3xl font-extrabold text-[var(--gl-text-primary)]">
                        Match Completed!
                    </h2>
                    <p class="text-sm text-[var(--gl-text-secondary)]">
                        Podium XP rewards have been automatically awarded to top participants.
                    </p>
                </div>

                <!-- Podium Standings -->
                <div class="mt-10 flex flex-wrap items-end justify-center gap-4">
                    <!-- 2nd Place -->
                    <div v-if="leaderboard[1]" class="w-36 rounded-2xl border border-slate-400/30 bg-slate-400/10 p-4 text-center">
                        <div class="text-2xl font-black text-slate-400">2nd</div>
                        <div class="mt-2 truncate text-sm font-bold text-[var(--gl-text-primary)]">{{ leaderboard[1].name }}</div>
                        <div class="mt-1 font-mono text-xs font-semibold text-emerald-600">{{ leaderboard[1].score }} pts</div>
                        <div class="mt-2 inline-block rounded-full bg-slate-400/20 px-2 py-0.5 text-[10px] font-bold text-slate-500">+XP Awarded</div>
                    </div>

                    <!-- 1st Place -->
                    <div v-if="leaderboard[0]" class="w-44 rounded-3xl border-2 border-amber-500/50 bg-amber-500/10 p-6 text-center shadow-lg">
                        <Crown class="mx-auto h-7 w-7 text-amber-500" />
                        <div class="text-3xl font-black text-amber-500">1st</div>
                        <div class="mt-2 truncate text-base font-extrabold text-[var(--gl-text-primary)]">{{ leaderboard[0].name }}</div>
                        <div class="mt-1 font-mono text-sm font-bold text-emerald-600">{{ leaderboard[0].score }} pts</div>
                        <div class="mt-2 inline-block rounded-full bg-amber-500/20 px-2.5 py-0.5 text-[10px] font-bold text-amber-600">Champion XP</div>
                    </div>

                    <!-- 3rd Place -->
                    <div v-if="leaderboard[2]" class="w-36 rounded-2xl border border-amber-700/30 bg-amber-700/10 p-4 text-center">
                        <div class="text-2xl font-black text-amber-700">3rd</div>
                        <div class="mt-2 truncate text-sm font-bold text-[var(--gl-text-primary)]">{{ leaderboard[2].name }}</div>
                        <div class="mt-1 font-mono text-xs font-semibold text-emerald-600">{{ leaderboard[2].score }} pts</div>
                        <div class="mt-2 inline-block rounded-full bg-amber-700/20 px-2 py-0.5 text-[10px] font-bold text-amber-700">+XP Awarded</div>
                    </div>
                </div>

                <!-- Top 10 Session Standings -->
                <div v-if="leaderboard.length > 0" class="mx-auto mt-12 max-w-2xl text-left">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="flex items-center gap-2 text-sm font-extrabold uppercase tracking-wider text-[var(--gl-text-primary)]">
                            <Trophy class="h-4 w-4 text-amber-500" />
                            Top 10 Session Standings
                        </h3>
                        <span class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[11px] font-bold text-emerald-600">
                            {{ Math.min(10, leaderboard.length) }} of {{ leaderboard.length }} Participants
                        </span>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] shadow-sm">
                        <div class="divide-y divide-[var(--gl-border)]">
                            <div
                                v-for="entry in leaderboard.slice(0, 10)"
                                :key="entry.user_id"
                                class="flex items-center justify-between p-3.5 transition-colors hover:bg-[var(--gl-surface)]"
                                :class="[
                                    entry.rank === 1 ? 'bg-amber-500/5' :
                                    entry.rank === 2 ? 'bg-slate-400/5' :
                                    entry.rank === 3 ? 'bg-amber-700/5' : ''
                                ]"
                            >
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl text-xs font-black shadow-sm"
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
                                        <div class="flex items-center gap-2">
                                            <span class="truncate text-xs font-bold text-[var(--gl-text-primary)]">
                                                {{ entry.name }}
                                            </span>
                                            <span v-if="entry.rank <= 3" class="text-xs shrink-0">
                                                {{ entry.rank === 1 ? '🥇' : entry.rank === 2 ? '🥈' : '🥉' }}
                                            </span>
                                        </div>
                                        <span v-if="entry.streak > 1" class="inline-flex items-center gap-1 text-[10px] font-bold text-orange-500">
                                            <Flame class="h-2.5 w-2.5 fill-current" /> {{ entry.streak }}x max streak
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 ml-3">
                                    <div class="font-mono text-sm font-extrabold text-emerald-600 dark:text-emerald-400">
                                        {{ entry.score.toLocaleString() }} pts
                                    </div>
                                    <span class="text-[10px] font-semibold text-[var(--gl-text-muted)]">
                                        {{ entry.rank === 1 ? 'Champion XP' : entry.rank <= 3 ? 'Podium XP' : 'Participation XP' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <Link
                        href="/teacher/games"
                        class="rounded-xl bg-emerald-600 px-6 py-3 text-sm font-bold text-white transition-transform hover:scale-105"
                    >
                        Back to Learning Games
                    </Link>
                    <button
                        type="button"
                        @click="deleteRoom"
                        class="flex items-center gap-2 rounded-xl border border-red-500/30 bg-red-500/10 px-6 py-3 text-sm font-bold text-red-600 transition-all hover:bg-red-500/20 active:scale-95"
                        title="Delete this live arena session permanently"
                    >
                        <Trash2 class="h-4 w-4" />
                        <span>Delete Session</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Top 10 Live Standings Modal for Host -->
        <Teleport to="body">
            <div
                v-if="showHostLeaderboardModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(2px);"
                @click.self="showHostLeaderboardModal = false"
            >
                <div class="w-full max-w-lg rounded-3xl border border-[var(--gl-border)] bg-[var(--gl-surface)] p-6 shadow-2xl">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="flex items-center gap-2 text-base font-extrabold text-[var(--gl-text-primary)]">
                            <Trophy class="h-5 w-5 text-amber-500" />
                            Top 10 Live Standings
                        </h3>
                        <button
                            type="button"
                            @click="showHostLeaderboardModal = false"
                            class="rounded-xl p-1.5 text-[var(--gl-text-muted)] hover:bg-[var(--gl-surface-2)] hover:text-[var(--gl-text-primary)] transition-colors"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div v-if="!leaderboard.length" class="py-10 text-center text-xs text-[var(--gl-text-muted)]">
                        No points scored yet in this live session.
                    </div>

                    <div v-else class="space-y-2 max-h-[60vh] overflow-y-auto pr-1">
                        <div
                            v-for="entry in leaderboard.slice(0, 10)"
                            :key="entry.user_id"
                            class="flex items-center justify-between rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] p-3 transition-all"
                            :class="[
                                entry.rank === 1 ? 'border-amber-500/40 bg-amber-500/10' :
                                entry.rank === 2 ? 'border-slate-400/40 bg-slate-400/10' :
                                entry.rank === 3 ? 'border-amber-700/40 bg-amber-700/10' : ''
                            ]"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <span
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl text-xs font-black shadow-sm"
                                    :class="[
                                        entry.rank === 1 ? 'bg-amber-500 text-white shadow-amber-500/30' :
                                        entry.rank === 2 ? 'bg-slate-400 text-white shadow-slate-400/30' :
                                        entry.rank === 3 ? 'bg-amber-700 text-white shadow-amber-700/30' :
                                        'bg-[var(--gl-surface)] text-[var(--gl-text-secondary)] border border-[var(--gl-border)]'
                                    ]"
                                >
                                    {{ entry.rank }}
                                </span>
                                <span class="truncate text-xs font-bold text-[var(--gl-text-primary)]">
                                    {{ entry.name }}
                                </span>
                                <span v-if="entry.streak > 1" class="inline-flex shrink-0 items-center gap-0.5 text-[10px] font-bold text-orange-500">
                                    <Flame class="h-3 w-3 fill-current" /> {{ entry.streak }}x
                                </span>
                            </div>
                            <span class="font-mono text-sm font-black text-emerald-600 dark:text-emerald-400 shrink-0 ml-3">
                                {{ entry.score.toLocaleString() }} pts
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-[var(--gl-border)] flex items-center justify-between text-xs text-[var(--gl-text-muted)]">
                        <span>{{ Math.min(10, leaderboard.length) }} of {{ leaderboard.length }} Participants</span>
                        <button
                            type="button"
                            @click="showHostLeaderboardModal = false"
                            class="rounded-xl bg-[var(--gl-surface-2)] px-4 py-2 font-bold text-[var(--gl-text-primary)] hover:bg-[var(--gl-surface)] transition-colors border border-[var(--gl-border)]"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Users, Play, Pause, SkipForward, Trophy, Flame, CheckCircle,
    Copy, Check, ArrowLeft, Crown, Zap, Trash2, ExternalLink, Timer, X
} from '@lucide/vue';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { getEcho } from '@/lib/echo';

const props = defineProps<{
    room: {
        id: number;
        pin: string;
        status: string;
        current_card_index: number;
        total_cards: number;
        game: any;
        current_card: any;
        participants: any[];
        leaderboard: any[];
    };
}>();

const page = usePage();
const status = ref(props.room.status);
const currentCardIndex = ref(props.room.current_card_index);
const currentCard = ref(props.room.current_card);
const currentFirstSolver = ref<any>((props.room as any).first_solver || null);
const leaderboard = ref(props.room.leaderboard || []);
const connectedPlayers = ref<any[]>(props.room.participants || []);
const showHostLeaderboardModal = ref(false);

const copied = ref(false);
const showAnswer = ref(false);
const starting = ref(false);
const advancing = ref(false);
const answeredCount = ref(0);

const timeLeft = ref(15);
const autoAdvancePaused = ref(false);
let autoTimerInterval: any = null;

function startAutoTimer() {
    stopAutoTimer();
    timeLeft.value = 15;

    if (status.value !== 'active' || autoAdvancePaused.value) {
return;
}

    autoTimerInterval = setInterval(() => {
        if (autoAdvancePaused.value || status.value !== 'active') {
return;
}

        if (timeLeft.value > 1) {
            timeLeft.value -= 1;
        } else {
            timeLeft.value = 0;
            stopAutoTimer();
            autoAdvanceNext();
        }
    }, 1000);
}

function stopAutoTimer() {
    if (autoTimerInterval) {
        clearInterval(autoTimerInterval);
        autoTimerInterval = null;
    }
}

function toggleAutoAdvance() {
    autoAdvancePaused.value = !autoAdvancePaused.value;

    if (!autoAdvancePaused.value) {
        if (timeLeft.value <= 0) {
            timeLeft.value = 15;
        }

        startAutoTimer();
    } else {
        stopAutoTimer();
    }
}

function manualNextQuestion() {
    stopAutoTimer();
    nextQuestion();
}

function autoAdvanceNext() {
    if (advancing.value || status.value !== 'active') {
return;
}

    nextQuestion();
}

watch(currentCardIndex, (newIdx, oldIdx) => {
    if (newIdx !== oldIdx && status.value === 'active') {
        startAutoTimer();
    }
});

let presenceChannel: any = null;
let pollInterval: any = null;

function getOptionBadgeStyle(idx: number): { background: string } {
    const gradients = [
        'linear-gradient(135deg, #ef4444, #dc2626)',
        'linear-gradient(135deg, #3b82f6, #2563eb)',
        'linear-gradient(135deg, #f59e0b, #d97706)',
        'linear-gradient(135deg, #10b981, #059669)',
    ];

    return { background: gradients[idx % gradients.length] };
}

function copyPin() {
    navigator.clipboard.writeText(props.room.pin);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
}

function startGame() {
    starting.value = true;
    router.post(`/teacher/games/rooms/${props.room.pin}/start`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            status.value = 'active';
            starting.value = false;
            startAutoTimer();
        },
        onError: () => {
            starting.value = false;
        },
    });
}

function nextQuestion() {
    advancing.value = true;
    showAnswer.value = false;
    router.post(`/teacher/games/rooms/${props.room.pin}/next`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            advancing.value = false;
            answeredCount.value = 0;

            if (status.value === 'active') {
                startAutoTimer();
            }
        },
        onError: () => {
            advancing.value = false;
        },
    });
}

function endGame() {
    stopAutoTimer();

    if (!confirm('End the match now and finalize podium rankings?')) {
return;
}

    router.post(`/teacher/games/rooms/${props.room.pin}/end`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            status.value = 'finished';
        },
    });
}

function deleteRoom() {
    stopAutoTimer();

    if (!confirm(`Delete Live Match Arena session (PIN: ${props.room.pin})? Students will be disconnected.`)) {
return;
}

    if (pollInterval) {
        clearInterval(pollInterval);
    }

    const echo = getEcho();

    if (echo) {
        echo.leave(`game.room.${props.room.pin}`);
    }

    router.delete(`/teacher/games/rooms/${props.room.pin}`);
}

async function fetchHostRoomStatus() {
    try {
        const res = await fetch(`/teacher/games/rooms/${props.room.pin}/status`, {
            headers: { Accept: 'application/json' },
        });

        if (!res.ok) {
return;
}

        const data = await res.json();

        if (!data.ok) {
return;
}

        if (data.status) {
status.value = data.status;
}

        if (typeof data.current_card_index === 'number' && data.current_card_index !== currentCardIndex.value) {
            currentCardIndex.value = data.current_card_index;

            if (data.card) {
currentCard.value = data.card;
}

            currentFirstSolver.value = data.first_solver || null;
            answeredCount.value = 0;
            showAnswer.value = false;
        } else if (data.card && !currentCard.value) {
            currentCard.value = data.card;
        }

        if (data.first_solver && !currentFirstSolver.value) {
            currentFirstSolver.value = data.first_solver;
        }

        if (data.leaderboard) {
            leaderboard.value = data.leaderboard;
        }
    } catch {}
}

onMounted(() => {
    if (status.value === 'active') {
        startAutoTimer();
    }

    // Start periodic polling fallback
    pollInterval = setInterval(fetchHostRoomStatus, 2500);

    const echo = getEcho((page.props as any).broadcasting?.reverb);

    if (echo) {
        presenceChannel = echo.join(`game.room.${props.room.pin}`)
            .here((users: any[]) => {
                const students = (users || []).filter((u: any) => u.role === 'student');

                if (students.length > 0) {
                    connectedPlayers.value = students;
                }
            })
            .joining((user: any) => {
                if (user.role === 'student' && !connectedPlayers.value.some((p: any) => p.id === user.id)) {
                    connectedPlayers.value.push(user);
                }
            })
            .leaving((user: any) => {
                connectedPlayers.value = connectedPlayers.value.filter((p: any) => p.id !== user.id);
            })
            .listen('.game.room.updated', (e: any) => {
                if (e.status) {
status.value = e.status;
}

                if (typeof e.current_card_index === 'number') {
currentCardIndex.value = e.current_card_index;
}

                if (e.card) {
currentCard.value = e.card;
}

                currentFirstSolver.value = e.first_solver || null;

                if (e.leaderboard) {
leaderboard.value = e.leaderboard;
}

                answeredCount.value = 0;
            })
            .listen('.game.room.answered', (e: any) => {
                if (e.is_first_solver) {
                    currentFirstSolver.value = {
                        user_id: e.user_id,
                        user_name: e.user_name,
                    };
                }

                if (e.leaderboard) {
leaderboard.value = e.leaderboard;
}

                answeredCount.value += 1;
            });
    }
});

onUnmounted(() => {
    stopAutoTimer();

    if (pollInterval) {
        clearInterval(pollInterval);
    }

    const echo = getEcho();

    if (echo) {
        echo.leave(`game.room.${props.room.pin}`);
    }
});
</script>
