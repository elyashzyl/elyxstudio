<template>
    <Head title="Learning Games" />

    <!-- Marketplace header -->
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
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
                Game Hub
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                {{ games.length }} games · {{ grabXp.toLocaleString() }} XP up
                for grabs
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button
                type="button"
                @click="showJoinModal = true; livePin = ''; joinError = ''"
                class="flex items-center gap-2 rounded-full px-4 py-2 text-xs font-bold text-white shadow-md transition-all hover:scale-105 active:scale-95"
                style="background: linear-gradient(135deg, #10b981, #059669);"
            >
                <Users class="h-3.5 w-3.5" />
                <span>Join Live Match</span>
                <span
                    v-if="activeRoomsList.length > 0"
                    class="ml-1 inline-flex items-center rounded-full bg-red-500 px-1.5 py-0.5 text-[9px] font-black text-white animate-pulse"
                >
                    {{ activeRoomsList.length }} LIVE
                </span>
            </button>
            <div class="relative">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2"
                    :stroke-width="2"
                    style="color: var(--gl-text-muted)"
                />
                <input
                    v-model="query"
                    type="text"
                    placeholder="Search games..."
                    class="rounded-full border py-2.5 pr-4 pl-9 text-sm transition-all outline-none focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                    style="
                        background: var(--gl-surface);
                        color: var(--gl-text-primary);
                        border-color: var(--gl-border);
                        width: 220px;
                    "
                />
            </div>
        </div>
    </div>

    <!-- Active Live Arena Match in Progress Banner -->
    <div
        v-if="activeRoomsList.length > 0"
        class="mb-6 space-y-3"
    >
        <div
            v-for="room in activeRoomsList"
            :key="room.id"
            class="gl-fade-in relative overflow-hidden rounded-3xl p-5 sm:p-6 transition-all"
            style="
                background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(6, 182, 212, 0.1));
                border: 1px solid rgba(16, 185, 129, 0.35);
                box-shadow: 0 4px 20px rgba(16, 185, 129, 0.15);
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
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-500/15 px-2.5 py-0.5 text-[11px] font-black text-red-500 dark:text-red-400">
                                <span class="h-2 w-2 rounded-full bg-red-500 animate-ping"></span>
                                LIVE ARENA MATCH
                            </span>
                            <span
                                v-if="room.status === 'waiting'"
                                class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold text-emerald-600"
                            >
                                Waiting in Lobby
                            </span>
                            <span
                                v-else
                                class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold text-amber-600"
                            >
                                Match in Progress
                            </span>
                        </div>
                        <h2 class="mt-1 text-base font-extrabold text-[var(--gl-text-primary)] sm:text-lg">
                            {{ room.game?.title || 'Live Match Arena' }}
                        </h2>
                        <p class="text-xs text-[var(--gl-text-secondary)]">
                            Hosted by <strong class="text-[var(--gl-text-primary)]">{{ room.teacher?.name || 'Teacher' }}</strong>
                            <template v-if="room.game?.subject"> · {{ room.game.subject }}</template>
                            <template v-if="room.game?.grade"> · {{ room.game.grade }}</template>
                            <template v-if="room.participants_count"> · {{ room.participants_count }} joined</template>
                        </p>
                    </div>
                </div>

                <!-- Big PIN & Join Button -->
                <div class="flex flex-wrap items-center gap-3">
                    <div
                        class="flex items-center gap-2.5 rounded-2xl border px-4 py-2 sm:px-5 sm:py-2.5"
                        style="background: var(--gl-surface); border-color: rgba(16, 185, 129, 0.4);"
                    >
                        <div>
                            <p class="text-[10px] font-extrabold tracking-wider uppercase text-emerald-600">
                                Room Code / PIN
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

                    <a
                        :href="`/student/games/rooms/${encodeURIComponent(room.pin)}`"
                        class="flex items-center gap-2 rounded-2xl px-5 py-3 text-sm font-extrabold text-white shadow-lg transition-all hover:scale-105 active:scale-95"
                        style="background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);"
                    >
                        <Play class="h-4 w-4 fill-current" />
                        <span>Join Live Match</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Category tabs -->
    <div v-if="games.length" class="mb-6 flex flex-wrap gap-2">
        <button
            v-for="t in tabs"
            :key="t"
            @click="activeTab = t"
            class="rounded-full px-4 py-1.5 text-xs font-bold transition-all"
            :style="
                activeTab === t
                    ? {
                          background:
                              'linear-gradient(135deg, #22D3EE, #3B82F6)',
                          color: '#fff',
                          boxShadow: '0 2px 12px rgba(34,211,238,0.4)',
                      }
                    : {
                          background: 'var(--gl-surface)',
                          border: '1px solid var(--gl-border)',
                          color: 'var(--gl-text-secondary)',
                      }
            "
        >
            {{ t === 'all' ? 'All' : (TYPE_LABELS[t] ?? t) }}
        </button>
    </div>

    <!-- Games Grid -->
    <div
        v-if="!games.length"
        class="gl-glow-card flex flex-col items-center justify-center px-8 py-16 text-center"
    >
        <div
            class="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl"
            style="
                background: linear-gradient(
                    135deg,
                    rgba(16, 185, 129, 0.15),
                    rgba(59, 130, 246, 0.1)
                );
            "
        >
            <Gamepad2
                class="h-10 w-10"
                style="color: var(--gl-primary)"
                :stroke-width="1.5"
            />
        </div>
        <h3 class="text-lg font-semibold" style="color: var(--gl-text-primary)">
            No Games Available Yet
        </h3>
        <p
            class="mt-2 max-w-md text-sm"
            style="color: var(--gl-text-secondary)"
        >
            Your teacher hasn't published any learning games yet. Check back
            later to play and earn XP.
        </p>
    </div>

    <div v-else class="grid grid-cols-1 items-start gap-5 xl:grid-cols-3">
        <div
            class="grid grid-cols-1 content-start gap-4 sm:grid-cols-2 xl:col-span-2"
        >
            <div
                v-if="!filteredGames.length"
                class="rounded-2xl p-8 text-center sm:col-span-2"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <p
                    class="text-sm font-medium"
                    style="color: var(--gl-text-secondary)"
                >
                    No games match your search
                </p>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    Try a different term or category.
                </p>
            </div>
            <div
                v-for="game in filteredGames"
                :key="game.id"
                @click="game.progress?.completed ? openResult(game) : openGame(game)"
                class="group flex flex-col overflow-hidden rounded-2xl transition-all duration-200 cursor-pointer hover:-translate-y-1"
                :style="{
                    background: 'var(--gl-surface)',
                    border: '1px solid var(--gl-border)',
                    opacity: game.progress?.completed ? 0.75 : 1,
                    boxShadow: 'none',
                }"
            >
                <!-- Cover art -->
                <div
                    class="relative h-32 shrink-0 overflow-hidden"
                    :style="{ background: cover(game) }"
                >
                    <div
                        class="absolute -top-6 -right-6 h-28 w-28 rounded-full"
                        style="background: rgba(255, 255, 255, 0.12)"
                    ></div>
                    <div
                        class="absolute -bottom-8 left-10 h-24 w-24 rounded-full"
                        style="background: rgba(0, 0, 0, 0.15)"
                    ></div>
                    <component
                        :is="game.icon"
                        class="absolute right-3 bottom-2 h-16 w-16 text-white opacity-30"
                        :stroke-width="1.5"
                    />
                    <span
                        class="absolute top-2.5 left-2.5 rounded-full px-2.5 py-1 text-[10px] font-extrabold tracking-wider uppercase"
                        style="
                            background: rgba(0, 0, 0, 0.45);
                            color: #fff;
                            backdrop-filter: blur(2px);
                        "
                    >
                        {{ TYPE_LABELS[game.type] ?? game.type }}
                    </span>
                    <span
                        v-if="game.reopen_pending || game.progress?.reopen_pending"
                        class="absolute top-2.5 right-2.5 flex items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-bold"
                        style="
                            background: rgba(245, 158, 11, 0.95);
                            color: #fff;
                            backdrop-filter: blur(2px);
                        "
                    >
                        <RotateCcw class="h-3 w-3 animate-spin" :stroke-width="2.5" /> Reopen Pending
                    </span>
                    <span
                        v-else-if="game.progress?.completed"
                        class="absolute top-2.5 right-2.5 flex items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-bold"
                        style="
                            background: rgba(0, 0, 0, 0.45);
                            color: #34d399;
                            backdrop-filter: blur(2px);
                        "
                    >
                        <Lock class="h-3 w-3" :stroke-width="2.5" /> Completed
                    </span>
                    <span
                        v-else
                        class="absolute right-2.5 bottom-2.5 rounded-full px-2.5 py-1 text-[10px] font-bold tabular-nums"
                        style="
                            background: rgba(0, 0, 0, 0.45);
                            color: #fff;
                            backdrop-filter: blur(2px);
                        "
                    >
                        {{ game.cards.length }} items
                    </span>
                </div>
                <!-- Body -->
                <div class="flex flex-1 flex-col p-4">
                    <h3
                        class="truncate text-sm font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ game.title }}
                    </h3>
                    <p
                        class="mt-0.5 truncate text-[11px]"
                        style="color: var(--gl-text-muted)"
                    >
                        {{ game.subject
                        }}{{ game.grade ? ' · ' + game.grade : '' }}
                    </p>
                    <div
                        class="mt-2.5 flex items-center justify-between text-[11px]"
                    >
                        <span
                            class="font-semibold tabular-nums"
                            style="color: var(--gl-text-secondary)"
                        >
                            <template
                                v-if="
                                    game.progress &&
                                    !game.progress.completed &&
                                    game.progress.done_count > 0
                                "
                            >
                                {{ game.progress.done_count }}/{{
                                    game.cards.length
                                }}
                                done
                            </template>
                            <template v-else-if="game.progress?.completed"
                                >Done</template
                            >
                            <template v-else>Not started</template>
                        </span>
                        <span
                            class="flex items-center gap-1 font-bold"
                            style="color: var(--gl-accent)"
                        >
                            <Zap class="h-3.5 w-3.5" :stroke-width="2" />
                            {{ game.xp }} XP
                        </span>
                    </div>
                    <div
                        v-if="
                            game.progress &&
                            !game.progress.completed &&
                            game.progress.done_count > 0
                        "
                        class="mt-1.5 h-1.5 overflow-hidden rounded-full"
                        style="background: var(--gl-surface-2)"
                    >
                        <div
                            class="h-full rounded-full"
                            :style="{
                                width: progressPct(game) + '%',
                                background:
                                    'linear-gradient(90deg, #22D3EE, #3B82F6)',
                            }"
                        ></div>
                    </div>
                    <button
                        @click.stop="
                            game.progress?.completed ? openResult(game) : openGame(game)
                        "
                        class="mt-3 flex w-full items-center justify-center gap-1.5 rounded-xl px-4 py-2.5 text-xs font-bold transition-all hover:scale-[1.02] active:scale-95"
                        :style="
                            game.progress?.completed
                                ? {
                                      background: 'rgba(16, 185, 129, 0.12)',
                                      color: 'var(--gl-success)',
                                      border: '1px solid rgba(16, 185, 129, 0.3)',
                                  }
                                : {
                                      background:
                                          'linear-gradient(135deg, #22D3EE, #3B82F6)',
                                      color: '#fff',
                                      boxShadow:
                                          '0 2px 12px rgba(34,211,238,0.35)',
                                  }
                        "
                    >
                        <Eye
                            v-if="game.progress?.completed"
                            class="h-3.5 w-3.5"
                            :stroke-width="2.5"
                        />
                        <Play
                            v-else
                            class="h-3.5 w-3.5"
                            :stroke-width="2.5"
                        />
                        {{
                            game.progress?.completed ? 'View Result' : 'Play Now'
                        }}
                    </button>
                </div>
            </div>
        </div>

        <!-- My Hub -->
        <aside
            class="overflow-hidden rounded-2xl"
            style="
                background: var(--gl-surface);
                border: 1px solid var(--gl-border);
            "
        >
            <div class="p-5 pb-3">
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl"
                        style="
                            background: linear-gradient(
                                135deg,
                                #22d3ee,
                                #3b82f6
                            );
                            box-shadow: 0 0 14px rgba(34, 211, 238, 0.35);
                        "
                    >
                        <Gamepad2
                            class="h-5 w-5 text-white"
                            :stroke-width="2"
                        />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-bold"
                            style="color: var(--gl-text-primary)"
                        >
                            My Hub
                        </h3>
                        <p
                            class="text-[11px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Your arcade stats
                        </p>
                    </div>
                </div>
                <div class="mt-3 grid grid-cols-3 gap-2">
                    <div
                        class="rounded-xl p-2 text-center"
                        style="
                            background: rgba(16, 185, 129, 0.08);
                            border: 1px solid rgba(16, 185, 129, 0.18);
                        "
                    >
                        <p
                            class="text-base font-extrabold tabular-nums"
                            style="color: var(--gl-success)"
                        >
                            {{ completedCount }}
                        </p>
                        <p
                            class="text-[10px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Done
                        </p>
                    </div>
                    <div
                        class="rounded-xl p-2 text-center"
                        style="
                            background: rgba(251, 191, 36, 0.08);
                            border: 1px solid rgba(251, 191, 36, 0.18);
                        "
                    >
                        <p
                            class="text-base font-extrabold tabular-nums"
                            style="color: var(--gl-accent)"
                        >
                            {{ inProgressCount }}
                        </p>
                        <p
                            class="text-[10px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Playing
                        </p>
                    </div>
                    <div
                        class="rounded-xl p-2 text-center"
                        style="
                            background: rgba(59, 130, 246, 0.08);
                            border: 1px solid rgba(59, 130, 246, 0.18);
                        "
                    >
                        <p
                            class="text-base font-extrabold tabular-nums"
                            style="color: var(--gl-primary)"
                        >
                            {{ grabXp }}
                        </p>
                        <p
                            class="text-[10px]"
                            style="color: var(--gl-text-muted)"
                        >
                            XP left
                        </p>
                    </div>
                </div>
            </div>
            <div
                class="p-5 pt-2"
                style="border-top: 1px solid var(--gl-border)"
            >
                <p
                    class="mb-2 text-[11px] font-bold tracking-widest uppercase"
                    style="color: var(--gl-text-muted)"
                >
                    Top rewards
                </p>
                <div
                    v-if="!topRewards.length"
                    class="py-2 text-center text-xs"
                    style="color: var(--gl-text-muted)"
                >
                    All games completed!
                </div>
                <div v-else class="space-y-2">
                    <div
                        v-for="g in topRewards"
                        :key="g.id"
                        class="flex items-center gap-2.5 rounded-xl p-2"
                        style="background: var(--gl-surface-2)"
                    >
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                            :style="{ background: cover(g) }"
                        >
                            <component
                                :is="g.icon"
                                class="h-4 w-4 text-white"
                                :stroke-width="2"
                            />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-xs font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ g.title }}
                            </p>
                            <p
                                class="text-[10px] tabular-nums"
                                style="color: var(--gl-accent)"
                            >
                                Up to {{ g.xp }} XP
                            </p>
                        </div>
                        <button
                            @click="openGame(g)"
                            title="Play"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-white transition-all hover:scale-105"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    #22d3ee,
                                    #3b82f6
                                );
                            "
                        >
                            <Play
                                class="ml-0.5 h-3.5 w-3.5"
                                :stroke-width="2.5"
                            />
                        </button>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <!-- Game Modal -->
    <Teleport to="body">
        <div
            v-if="currentGame"
            class="fixed inset-0 z-50 flex items-center justify-center"
            :class="[
                isBoardGame ? 'overflow-hidden' : 'overflow-y-auto',
                isFullscreen ? 'p-0' : 'p-2 sm:p-4 md:p-6',
            ]"
            style="
                background: rgba(0, 0, 0, 0.65);
                backdrop-filter: blur(4px);
                user-select: none;
                -webkit-user-select: none;
            "
        >
            <div
                class="flex w-full items-center justify-center"
                :class="isBoardGame ? 'h-full min-h-0 flex-col py-0' : 'min-h-full py-2 sm:py-4'"
            >
                <div
                    class="mx-auto flex w-full flex-col items-center transition-all duration-200"
                    :class="[
                        gameModalMaxWidthClass,
                        isBoardGame ? 'h-full min-h-0' : 'my-auto',
                    ]"
                >
                    <div
                        v-if="tabWarning && leaveCount < 3"
                        class="gl-fade-in mb-3 flex w-full max-w-lg flex-col items-center gap-1 rounded-2xl px-5 py-3 text-center"
                        style="
                            background: rgba(251, 191, 36, 0.12);
                            border: 1px solid rgba(251, 191, 36, 0.35);
                            color: var(--gl-accent);
                        "
                    >
                        <p class="flex items-center gap-1.5 text-sm font-bold">
                            <ShieldAlert class="h-4 w-4" :stroke-width="2.5" />
                            <span>Don't leave the game</span>
                        </p>
                        <p class="text-xs" style="color: var(--gl-text-secondary)">
                            Switching tabs, apps, or going back counts as a violation.
                            This is warning {{ leaveCount }} of 3 — the next one
                            auto-submits your game.
                        </p>
                    </div>
                    <div
                        v-if="leaveCount >= 3"
                        class="gl-fade-in mb-3 w-full max-w-lg rounded-2xl px-5 py-3 text-center text-sm font-bold"
                        style="
                            background: var(--gl-danger-bg);
                            border: 1px solid rgba(239, 68, 68, 0.35);
                            color: var(--gl-danger);
                        "
                    >
                        Game closed after multiple warnings — submitting your game.
                    </div>
                    <div
                        class="gl-fade-in flex w-full flex-col overflow-hidden rounded-2xl"
                        :class="isBoardGame
                            ? 'h-full max-h-full min-h-0 flex-1 rounded-none sm:rounded-2xl'
                            : 'max-h-[calc(100vh-2rem)] sm:max-h-[88vh]'"
                        style="
                            background: var(--gl-surface);
                            border: 1px solid var(--gl-border);
                            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.5);
                        "
                    >
                        <!-- Game Header -->
                        <div
                            class="flex shrink-0 items-center justify-between border-b px-5 py-3.5 sm:px-6 sm:py-4"
                            style="border-color: var(--gl-border)"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-lg"
                                    :style="{ background: currentGame.bg }"
                                >
                                    <component
                                        :is="currentGame.icon"
                                        class="h-4.5 w-4.5"
                                        :style="{ color: currentGame.color }"
                                        :stroke-width="2"
                                    />
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-semibold"
                                        style="color: var(--gl-text-primary)"
                                    >
                                        {{ currentGame.title }}
                                    </p>
                                    <p
                                        class="text-[10px]"
                                        style="color: var(--gl-text-muted)"
                                    >
                                        {{ gameProgressLabel }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    v-if="currentGame.progress?.hints_used"
                                    class="hidden sm:flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    style="background: rgba(234, 179, 8, 0.12); color: #EAB308; border: 1px solid rgba(234, 179, 8, 0.25);"
                                    :title="`Hints used: ${currentGame.progress.hints_used} (-${currentGame.progress.hints_used * hintCostAmount} XP)`"
                                >
                                    <Lightbulb class="h-3 w-3" />
                                    <span>{{ currentGame.progress.hints_used }} hint{{ currentGame.progress.hints_used > 1 ? 's' : '' }} (-{{ currentGame.progress.hints_used * hintCostAmount }} XP)</span>
                                </span>
                                <span
                                    class="hidden items-center gap-1 rounded-full px-2 py-1 text-[10px] font-semibold sm:flex"
                                    :style="isFullscreen
                                        ? 'background: var(--gl-success-bg); color: var(--gl-success);'
                                        : 'background: var(--gl-danger-bg); color: var(--gl-danger);'"
                                    :title="isFullscreen
                                        ? 'The game is locked in fullscreen mode.'
                                        : 'Fullscreen could not be enabled. Keep this game open and visible.'"
                                >
                                    <Lock class="h-3 w-3" :stroke-width="2.5" />
                                    {{ isFullscreen ? 'Locked' : fullscreenDenied ? 'Fullscreen unavailable' : 'Locking...' }}
                                </span>
                                <button
                                    @click="finishGame()"
                                    title="End game and submit current progress"
                                    class="flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-semibold transition-all hover:scale-105"
                                    style="
                                        background: var(--gl-surface-2);
                                        color: var(--gl-text-secondary);
                                        border: 1px solid var(--gl-border);
                                    "
                                >
                                    <Flag class="h-3 w-3" :stroke-width="2" />
                                    End
                                </button>
                                <span
                                    v-if="
                                        currentGame.progress &&
                                        currentGame.progress.done_count > 0 &&
                                        !currentGame.progress.completed
                                    "
                                    class="rounded-full px-2 py-1 text-[10px] font-medium"
                                    style="
                                        background: rgba(59, 130, 246, 0.1);
                                        color: var(--gl-primary);
                                    "
                                >
                                    Resumed
                                </span>
                            </div>
                        </div>

                        <!-- Game Content Area -->
                        <div
                            class="flex-1 overscroll-contain"
                            :class="isBoardGame
                                ? 'min-h-0 overflow-hidden'
                                : 'overflow-y-auto'"
                        >

                <!-- Flashcard - flip style -->
                <div
                    v-if="currentGame.type === 'flashcard'"
                    class="p-6"
                    style="min-height: 280px"
                >
                    <div
                        class="relative min-h-[15rem] sm:min-h-[17rem] h-60 sm:h-64 cursor-pointer"
                        @click="flipped = !flipped"
                        style="perspective: 1000px"
                    >
                        <div
                            class="absolute inset-0 transition-transform duration-500"
                            :style="{
                                transform: flipped
                                    ? 'rotateY(180deg)'
                                    : 'rotateY(0deg)',
                                transformStyle: 'preserve-3d',
                            }"
                        >
                            <div
                                class="absolute inset-0 flex flex-col items-center justify-center rounded-2xl p-6 text-center"
                                style="
                                    background: var(--gl-surface-2);
                                    border: 1px solid var(--gl-border);
                                    backface-visibility: hidden;
                                "
                            >
                                <div
                                    class="mb-3 flex h-12 w-12 items-center justify-center rounded-full"
                                    style="background: rgba(59, 130, 246, 0.1)"
                                >
                                    <HelpCircle
                                        class="h-6 w-6"
                                        style="color: var(--gl-primary)"
                                        :stroke-width="2"
                                    />
                                </div>
                                <p
                                    class="mb-1 text-sm font-medium"
                                    style="color: var(--gl-text-muted)"
                                >
                                    Question
                                </p>
                                <p
                                    class="text-base font-semibold max-h-36 overflow-y-auto px-2 break-words"
                                    style="color: var(--gl-text-primary)"
                                >
                                    {{
                                        currentGame.cards[currentCard]?.question
                                    }}
                                </p>
                                <p
                                    class="mt-4 text-xs"
                                    style="color: var(--gl-text-muted)"
                                >
                                    Tap to reveal answer
                                </p>
                            </div>
                            <div
                                class="absolute inset-0 flex flex-col items-center justify-center rounded-2xl p-6 text-center"
                                style="
                                    background: linear-gradient(
                                        135deg,
                                        rgba(16, 185, 129, 0.08),
                                        rgba(59, 130, 246, 0.04)
                                    );
                                    border: 1px solid rgba(16, 185, 129, 0.2);
                                    backface-visibility: hidden;
                                    transform: rotateY(180deg);
                                "
                            >
                                <div
                                    class="mb-3 flex h-12 w-12 items-center justify-center rounded-full"
                                    style="background: rgba(16, 185, 129, 0.1)"
                                >
                                    <CheckCircle2
                                        class="h-6 w-6"
                                        style="color: var(--gl-success)"
                                        :stroke-width="2"
                                    />
                                </div>
                                <p
                                    class="mb-1 text-sm font-medium"
                                    style="color: var(--gl-success)"
                                >
                                    Answer
                                </p>
                                <p
                                    class="text-base font-semibold max-h-36 overflow-y-auto px-2 break-words"
                                    style="color: var(--gl-text-primary)"
                                >
                                    {{ currentGame.cards[currentCard]?.answer }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quiz - multiple choice -->
                <div
                    v-else-if="currentGame.type === 'quiz'"
                    class="p-6"
                    style="min-height: 280px"
                >
                    <div class="mb-6 text-center">
                        <span class="gl-eyebrow">Multiple Choice</span>
                        <div class="gl-q-row">
                            <span class="gl-q-num">{{ currentCard + 1 }}</span>
                            <span class="gl-q-sep">/</span>
                            <span class="gl-q-total">{{
                                currentGame.cards.length
                            }}</span>
                        </div>
                        <h3 class="gl-question">
                            {{ currentGame.cards[currentCard]?.question }}
                        </h3>
                    </div>
                    <div v-if="!answered" class="space-y-2.5">
                        <button
                            v-for="(opt, oi) in currentGame.cards[currentCard]
                                ?.options || []"
                            :key="oi"
                            @click="checkAnswer(opt)"
                            class="w-full rounded-xl border px-4 py-3 text-left text-sm transition-all hover:bg-[rgba(59,130,246,0.04)] break-words"
                            :style="
                                selectedOption === opt &&
                                opt !== currentGame.cards[currentCard]?.answer
                                    ? 'border-color: var(--gl-danger); background: var(--gl-danger-bg); color: var(--gl-text-primary);'
                                    : selectedOption === opt &&
                                        opt ===
                                            currentGame.cards[currentCard]
                                                ?.answer
                                      ? 'border-color: var(--gl-success); background: var(--gl-success-bg); color: var(--gl-text-primary);'
                                      : 'border-color: var(--gl-border); color: var(--gl-text-secondary);'
                            "
                        >
                            {{ opt }}
                        </button>
                    </div>
                    <div v-else class="text-center">
                        <span
                            class="gl-feedback"
                            :class="
                                selectedOption ===
                                currentGame.cards[currentCard]?.answer
                                    ? 'correct'
                                    : 'wrong'
                            "
                        >
                            <CheckCircle2
                                v-if="
                                    selectedOption ===
                                    currentGame.cards[currentCard]?.answer
                                "
                                class="h-4 w-4"
                                :stroke-width="2.5"
                            />
                            <X v-else class="h-4 w-4" :stroke-width="2.5" />
                            {{
                                selectedOption ===
                                currentGame.cards[currentCard]?.answer
                                    ? 'Correct!'
                                    : 'Wrong!'
                            }}
                        </span>
                        <p
                            v-if="selectedOption === currentGame.cards[currentCard]?.answer"
                            class="gl-answer-reveal select-none"
                        >
                            The correct answer is:
                            <strong style="color: var(--gl-success)">{{
                                currentGame.cards[currentCard]?.answer
                            }}</strong>
                        </p>
                        <p v-else class="gl-answer-reveal text-[var(--gl-text-muted)] italic select-none">
                            Answer hidden to prevent copying
                        </p>
                    </div>
                </div>

                <!-- Fill in the Blank -->
                <div
                    v-else-if="currentGame.type === 'fillblank'"
                    class="p-6"
                    style="min-height: 280px"
                >
                    <div class="mb-6 text-center">
                        <span class="gl-eyebrow">Fill in the Blank</span>
                        <div class="gl-q-row">
                            <span class="gl-q-num">{{ currentCard + 1 }}</span>
                            <span class="gl-q-sep">/</span>
                            <span class="gl-q-total">{{
                                currentGame.cards.length
                            }}</span>
                        </div>
                        <h3 class="gl-question">
                            {{ currentGame.cards[currentCard]?.question }}
                        </h3>
                    </div>
                    <div v-if="!answered">
                        <input
                            v-model="typedAnswer"
                            type="text"
                            placeholder="Type your answer..."
                            class="w-full rounded-xl border px-4 py-3 text-center text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                            @keyup.enter="checkFillBlank"
                        />
                        <button
                            @click="checkFillBlank"
                            class="mt-4 w-full rounded-xl py-2.5 text-sm font-semibold text-white transition-all"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                            "
                        >
                            Submit
                        </button>
                    </div>
                    <div v-else class="text-center">
                        <span
                            class="gl-feedback"
                            :class="fillCorrect ? 'correct' : 'wrong'"
                        >
                            <CheckCircle2
                                v-if="fillCorrect"
                                class="h-4 w-4"
                                :stroke-width="2.5"
                            />
                            <X v-else class="h-4 w-4" :stroke-width="2.5" />
                            {{ fillCorrect ? 'Correct!' : 'Not quite' }}
                        </span>
                        <p v-if="fillCorrect" class="gl-answer-reveal select-none">
                            Answer:
                            <strong style="color: var(--gl-success)">{{
                                currentGame.cards[currentCard]?.answer
                            }}</strong>
                        </p>
                        <p v-else class="gl-answer-reveal text-[var(--gl-text-muted)] italic select-none">
                            Answer hidden to prevent copying
                        </p>
                    </div>
                </div>

                <!-- True / False -->
                <div
                    v-else-if="currentGame.type === 'truefalse'"
                    class="p-6"
                    style="min-height: 280px"
                >
                    <div class="mb-6 text-center">
                        <span class="gl-eyebrow">True or False</span>
                        <div class="gl-q-row">
                            <span class="gl-q-num">{{ currentCard + 1 }}</span>
                            <span class="gl-q-sep">/</span>
                            <span class="gl-q-total">{{
                                currentGame.cards.length
                            }}</span>
                        </div>
                        <h3 class="gl-question">
                            {{ currentGame.cards[currentCard]?.question }}
                        </h3>
                    </div>
                    <div v-if="!answered" class="flex gap-3">
                        <button
                            @click="checkTrueFalse(true)"
                            class="flex-1 rounded-xl py-3 text-sm font-semibold transition-all hover:scale-[1.02]"
                            style="
                                background: rgba(16, 185, 129, 0.1);
                                color: var(--gl-success);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            True
                        </button>
                        <button
                            @click="checkTrueFalse(false)"
                            class="flex-1 rounded-xl py-3 text-sm font-semibold transition-all hover:scale-[1.02]"
                            style="
                                background: rgba(239, 68, 68, 0.1);
                                color: var(--gl-danger);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            False
                        </button>
                    </div>
                    <div v-else class="text-center">
                        <span
                            class="gl-feedback"
                            :class="answerResult ? 'correct' : 'wrong'"
                        >
                            <CheckCircle2
                                v-if="answerResult"
                                class="h-4 w-4"
                                :stroke-width="2.5"
                            />
                            <X v-else class="h-4 w-4" :stroke-width="2.5" />
                            {{ answerResult ? 'Correct!' : 'Wrong!' }}
                        </span>
                        <p v-if="answerResult" class="gl-answer-reveal select-none">
                            Answer:
                            <strong style="color: var(--gl-success)">{{
                                currentGame.cards[currentCard]?.answer
                            }}</strong>
                        </p>
                        <p v-else class="gl-answer-reveal text-[var(--gl-text-muted)] italic select-none">
                            Answer hidden to prevent copying
                        </p>
                    </div>
                </div>

                <!-- Word Jumble -->
                <div
                    v-else-if="currentGame.type === 'wordjumble'"
                    class="p-6"
                    style="min-height: 280px"
                >
                    <div class="mb-6 text-center">
                        <span class="gl-eyebrow">Word Unscramble</span>
                        <div class="gl-q-row">
                            <span class="gl-q-num">{{ currentCard + 1 }}</span>
                            <span class="gl-q-sep">/</span>
                            <span class="gl-q-total">{{
                                currentGame.cards.length
                            }}</span>
                        </div>
                        <h3 class="gl-question">
                            {{ currentGame.cards[currentCard]?.question }}
                        </h3>
                    </div>
                    <template v-if="!answered">
                        <div
                            class="mb-4 flex min-h-[2.5rem] flex-wrap items-center justify-center gap-2 rounded-xl p-2"
                            style="
                                background: var(--gl-surface-2);
                                border: 1px dashed var(--gl-border);
                            "
                        >
                            <span
                                v-if="!jumbleAnswer.length"
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                                >Tap letters below to unscramble the
                                answer</span
                            >
                            <span
                                v-for="(l, li) in jumbleAnswer"
                                :key="li"
                                @click="removeJumbleLetter(li)"
                                class="cursor-pointer rounded-lg border px-3 py-1.5 text-sm font-bold uppercase transition-all"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-primary);
                                    border-color: var(--gl-primary);
                                "
                                >{{ l }}</span
                            >
                        </div>
                        <div class="flex flex-wrap justify-center gap-2">
                            <button
                                v-for="(l, li) in jumbleLetters"
                                :key="li"
                                @click="addJumbleLetter(li)"
                                class="rounded-lg border px-3 py-1.5 text-sm font-bold uppercase transition-all hover:scale-105"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            >
                                {{ l }}
                            </button>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <button
                                type="button"
                                @click="requestHintWordJumble"
                                :disabled="hintDeducting"
                                class="flex items-center justify-center gap-1.5 rounded-xl border px-3.5 py-2.5 text-xs font-semibold transition-all hover:bg-[var(--gl-surface-2)] active:scale-95 disabled:opacity-50"
                                style="border-color: rgba(234,179,8,0.3); background: rgba(234,179,8,0.08); color: #EAB308;"
                                :title="`Place next correct letter (-${hintCostAmount} XP)`"
                            >
                                <Lightbulb class="h-3.5 w-3.5" />
                                Hint (-{{ hintCostAmount }} XP)
                            </button>
                            <button
                                @click="checkWordJumble"
                                :disabled="!jumbleAnswer.length"
                                class="flex-1 rounded-xl py-2.5 text-sm font-semibold text-white transition-all disabled:opacity-50"
                                style="
                                    background: linear-gradient(
                                        135deg,
                                        var(--gl-primary),
                                        var(--gl-secondary)
                                    );
                                "
                            >
                                Check
                            </button>
                        </div>
                    </template>
                    <div v-else class="text-center">
                        <span
                            class="gl-feedback"
                            :class="answerResult ? 'correct' : 'wrong'"
                        >
                            <CheckCircle2
                                v-if="answerResult"
                                class="h-4 w-4"
                                :stroke-width="2.5"
                            />
                            <X v-else class="h-4 w-4" :stroke-width="2.5" />
                            {{ answerResult ? 'Correct!' : 'Not quite' }}
                        </span>
                        <p v-if="answerResult" class="gl-answer-reveal select-none">
                            Answer:
                            <strong style="color: var(--gl-success)">{{
                                currentGame.cards[currentCard]?.answer
                            }}</strong>
                        </p>
                        <p v-else class="gl-answer-reveal text-[var(--gl-text-muted)] italic select-none">
                            Answer hidden to prevent copying
                        </p>
                    </div>
                </div>

                <!-- Color Harmony -->
                <div
                    v-else-if="currentGame.type === 'colorharmony'"
                    class="p-6"
                    style="min-height: 280px"
                >
                    <div class="mb-5 text-center">
                        <span class="gl-eyebrow">Color Harmony</span>
                        <div class="gl-q-row">
                            <span class="gl-q-num">{{ currentCard + 1 }}</span>
                            <span class="gl-q-sep">/</span>
                            <span class="gl-q-total">{{
                                currentGame.cards.length
                            }}</span>
                        </div>
                        <h3 class="gl-question mb-4">
                            {{ currentGame.cards[currentCard]?.question }}
                        </h3>
                        <div class="flex flex-col items-center gap-1.5">
                            <span
                                class="text-[10px] font-semibold tracking-widest uppercase"
                                style="color: var(--gl-text-muted)"
                                >Reference Color</span
                            >
                            <div
                                class="h-16 w-16 rounded-2xl border-2"
                                :style="{
                                    background:
                                        currentGame.cards[currentCard]?.color,
                                    borderColor: 'var(--gl-border)',
                                    boxShadow: '0 6px 18px rgba(0,0,0,0.15)',
                                }"
                            ></div>
                        </div>
                    </div>
                    <div
                        v-if="!answered"
                        class="flex flex-wrap justify-center gap-2.5"
                    >
                        <button
                            v-for="(opt, oi) in currentGame.cards[currentCard]
                                ?.options || []"
                            :key="oi"
                            @click="checkAnswer(opt)"
                            class="flex flex-col items-center gap-1 rounded-xl p-2 transition-all hover:scale-105"
                            :style="
                                selectedOption === opt
                                    ? 'border: 1px solid var(--gl-primary); box-shadow: 0 0 0 2px var(--gl-primary-glow); background: var(--gl-surface-2);'
                                    : 'border: 1px solid var(--gl-border); background: var(--gl-surface-2);'
                            "
                        >
                            <span
                                class="h-11 w-14 rounded-lg border"
                                :style="{
                                    background: opt,
                                    borderColor: 'rgba(0,0,0,0.08)',
                                }"
                            ></span>
                            <span
                                class="font-mono text-[9px] uppercase"
                                style="color: var(--gl-text-muted)"
                                >{{ opt }}</span
                            >
                        </button>
                    </div>
                    <div v-else class="text-center">
                        <span
                            class="gl-feedback"
                            :class="
                                selectedOption ===
                                currentGame.cards[currentCard]?.answer
                                    ? 'correct'
                                    : 'wrong'
                            "
                        >
                            <CheckCircle2
                                v-if="
                                    selectedOption ===
                                    currentGame.cards[currentCard]?.answer
                                "
                                class="h-4 w-4"
                                :stroke-width="2.5"
                            />
                            <X v-else class="h-4 w-4" :stroke-width="2.5" />
                            {{
                                selectedOption ===
                                currentGame.cards[currentCard]?.answer
                                    ? 'Correct!'
                                    : 'Wrong!'
                            }}
                        </span>
                        <p
                            v-if="selectedOption === currentGame.cards[currentCard]?.answer"
                            class="gl-answer-reveal select-none"
                        >
                            Correct color:
                            <strong style="color: var(--gl-success)">{{
                                currentGame.cards[currentCard]?.answer
                            }}</strong>
                        </p>
                        <p v-else class="gl-answer-reveal text-[var(--gl-text-muted)] italic select-none">
                            Answer hidden to prevent copying
                        </p>
                    </div>
                </div>

                <!-- Memory Match -->
                <div
                    v-else-if="currentGame.type === 'memorymatch'"
                    class="memory-match-board h-full min-h-0 gap-2 p-3 sm:gap-3 sm:p-4"
                    style="min-height: 0"
                >
                    <div class="shrink-0 text-center">
                        <span class="gl-eyebrow">Memory Match</span>
                        <p
                            class="mt-1 text-xs"
                            style="color: var(--gl-text-secondary)"
                        >
                            Flip tiles to match each question or term with its answer. Click any tile to inspect its full text.
                        </p>
                    </div>

                    <!-- Active Inspected Tile Banner (Guarantees 100% full text readability) -->
                    <div
                        v-if="activeMemoryTile"
                        class="memory-description shrink-0 rounded-2xl border p-2.5 shadow-sm transition-all flex items-start gap-2 sm:gap-3 sm:p-3.5"
                        :style="
                            activeMemoryTile.matched
                                ? 'border-color: rgba(16,185,129,0.3); background: rgba(16,185,129,0.08);'
                                : 'border-color: rgba(99,102,241,0.3); background: var(--gl-surface-2);'
                        "
                    >
                        <div
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-xs font-black"
                            :style="
                                activeMemoryTile.matched
                                    ? 'background: rgba(16,185,129,0.2); color: #10b981;'
                                    : activeMemoryTile.kind === 'q'
                                      ? 'background: rgba(6,182,212,0.2); color: #06b6d4;'
                                      : 'background: rgba(139,92,246,0.2); color: #8b5cf6;'
                            "
                        >
                            {{ activeMemoryTile.kind === 'q' ? 'Q' : 'A' }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-[var(--gl-text-muted)]">
                                    {{ activeMemoryTile.kind === 'q' ? 'Question / Concept' : 'Answer / Definition' }}
                                </span>
                                <span
                                    v-if="activeMemoryTile.matched"
                                    class="text-[10px] font-bold text-emerald-500"
                                >
                                    ✓ Matched Pair
                                </span>
                            </div>
                            <p class="mt-1 text-xs sm:text-sm font-semibold text-[var(--gl-text-primary)] break-words leading-relaxed select-text">
                                {{ activeMemoryTile.text }}
                            </p>
                        </div>
                    </div>

                    <!-- Memory Tiles Grid -->
                    <div :class="[memoryGridClass, 'memory-tiles memory-grid min-h-0']">
                        <button
                            v-for="tile in memoryTiles"
                            :key="tile.id"
                            type="button"
                            @click="memoryFlip(tile)"
                            class="memory-tile group relative flex min-h-0 h-full flex-col items-center justify-center rounded-2xl border p-1.5 text-center transition-all duration-200 select-none hover:scale-[1.02] active:scale-95 sm:p-2.5"
                            :class="[
                                activeMemoryTile && activeMemoryTile.id === tile.id ? 'ring-2 ring-indigo-500 shadow-md' : ''
                            ]"
                            :style="
                                tile.matched
                                    ? 'border-color: rgba(16,185,129,0.45); background: rgba(16,185,129,0.08);'
                                    : tile.flipped
                                      ? 'border-color: var(--gl-primary); background: var(--gl-surface); box-shadow: 0 4px 14px rgba(0,0,0,0.05);'
                                      : 'border-color: var(--gl-border); background: var(--gl-surface-2);'
                            "
                            :title="tile.flipped || tile.matched ? tile.text : 'Click to flip tile'"
                            :aria-label="tile.flipped || tile.matched ? tile.text : 'Flip card'"
                        >
                            <!-- Matched Content -->
                            <div
                                v-if="tile.matched"
                                class="flex flex-col items-center justify-center w-full h-full space-y-1"
                            >
                                <div class="flex items-center gap-1 text-emerald-500">
                                    <CheckCircle2 class="h-3.5 w-3.5 shrink-0" :stroke-width="2.5" />
                                    <span class="text-[9px] font-extrabold uppercase tracking-wider">
                                        {{ tile.kind === 'q' ? 'Q' : 'A' }}
                                    </span>
                                </div>
                                <div class="memory-tile-copy w-full px-1">
                                    <p
                                        class="break-words font-semibold leading-snug"
                                        :class="[
                                            tile.text.length > 70
                                                ? 'text-[10px] sm:text-[11px]'
                                                : tile.text.length > 35
                                                  ? 'text-[11px] sm:text-xs'
                                                  : 'text-xs sm:text-sm'
                                        ]"
                                        style="color: var(--gl-success)"
                                    >
                                        {{ tile.text }}
                                    </p>
                                </div>
                            </div>

                            <!-- Flipped Content -->
                            <div
                                v-else-if="tile.flipped"
                                class="flex flex-col items-center justify-center w-full h-full space-y-1"
                            >
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                    :style="
                                        tile.kind === 'q'
                                            ? 'background: rgba(6,182,212,0.15); color: #06b6d4;'
                                            : 'background: rgba(139,92,246,0.15); color: #8b5cf6;'
                                    "
                                >
                                    {{ tile.kind === 'q' ? 'Question' : 'Answer' }}
                                </span>
                                <div class="memory-tile-copy w-full px-1">
                                    <p
                                        class="break-words font-semibold leading-snug"
                                        :class="[
                                            tile.text.length > 70
                                                ? 'text-[10px] sm:text-[11px]'
                                                : tile.text.length > 35
                                                  ? 'text-[11px] sm:text-xs'
                                                  : 'text-xs sm:text-sm'
                                        ]"
                                        style="color: var(--gl-text-primary)"
                                    >
                                        {{ tile.text }}
                                    </p>
                                </div>
                            </div>

                            <!-- Face Down Card -->
                            <div
                                v-else
                                class="flex flex-col items-center justify-center gap-1.5 py-2"
                            >
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-xl shadow-sm transition-transform group-hover:scale-110"
                                    style="
                                        background: linear-gradient(
                                            135deg,
                                            var(--gl-primary),
                                            var(--gl-secondary)
                                        );
                                    "
                                >
                                    <HelpCircle
                                        class="h-4 w-4 text-white"
                                        :stroke-width="2"
                                    />
                                </span>
                                <span class="text-[10px] font-bold text-[var(--gl-text-muted)] tracking-wider uppercase">
                                    Tile
                                </span>
                            </div>
                        </button>
                    </div>

                    <div
                        v-if="memoryMatches === currentGame.cards.length"
                        class="memory-complete mt-4 text-center"
                    >
                        <span class="gl-feedback correct"
                            >All pairs matched!</span
                        >
                    </div>
                </div>

                <!-- Hangman -->
                <div
                    v-else-if="currentGame.type === 'hangman'"
                    class="p-6"
                    style="min-height: 280px"
                >
                    <div class="mb-5 text-center">
                        <span class="gl-eyebrow">Hangman</span>
                        <div class="gl-q-row">
                            <span class="gl-q-num">{{ currentCard + 1 }}</span>
                            <span class="gl-q-sep">/</span>
                            <span class="gl-q-total">{{
                                currentGame.cards.length
                            }}</span>
                        </div>
                        <h3 class="gl-question">
                            {{ currentGame.cards[currentCard]?.question }}
                        </h3>
                    </div>
                    <div class="mb-4 flex justify-center">
                        <div
                            class="flex items-center justify-between gap-3 rounded-xl px-5 py-2.5"
                            style="
                                background: var(--gl-surface-2);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            <span
                                class="text-[10px] font-bold tracking-widest uppercase"
                                style="color: var(--gl-text-muted)"
                                >Mistakes</span
                            >
                            <div class="flex gap-1">
                                <span
                                    v-for="m in HANGMAN_MAX"
                                    :key="m"
                                    class="h-2.5 w-2.5 rounded-full"
                                    :style="
                                        m <= hangmanMistakes
                                            ? 'background: var(--gl-danger);'
                                            : 'background: var(--gl-border);'
                                    "
                                ></span>
                            </div>
                        </div>
                    </div>
                    <div
                        class="mb-5 flex flex-wrap items-center justify-center gap-x-1.5 gap-y-1"
                    >
                        <span
                            v-for="(ch, ci) in hangmanWord.split('')"
                            :key="ci"
                            class="flex h-9 w-7 items-end justify-center border-b-2 pb-1 text-sm font-bold"
                            style="
                                border-color: var(--gl-border);
                                color: var(--gl-text-primary);
                            "
                            >{{
                                hangmanGuessed.includes(ch)
                                    ? ch
                                    : answered && hangmanMistakes >= HANGMAN_MAX
                                      ? ch
                                      : ''
                            }}</span
                        >
                    </div>
                    <div
                        v-if="!answered"
                        class="mx-auto flex max-w-sm flex-wrap justify-center gap-1"
                    >
                        <button
                            v-for="letter in HANGMAN_LETTERS"
                            :key="letter"
                            @click="hangmanGuess(letter)"
                            :disabled="
                                hangmanGuessed.includes(letter) &&
                                !hangmanWord.includes(letter)
                            "
                            class="flex h-8 w-8 items-center justify-center rounded-lg border text-xs font-bold uppercase transition-all hover:scale-105 disabled:opacity-30"
                            :style="
                                hangmanGuessed.includes(letter)
                                    ? hangmanWord.includes(letter)
                                        ? 'border-color: var(--gl-success); color: var(--gl-success);'
                                        : 'border-color: var(--gl-danger); color: var(--gl-danger);'
                                    : 'background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);'
                            "
                        >
                            {{ letter }}
                        </button>
                    </div>
                    <div v-if="!answered" class="mt-3.5 flex justify-center">
                        <button
                            type="button"
                            @click="requestHintHangman"
                            :disabled="hintDeducting"
                            class="flex items-center gap-1.5 rounded-xl border px-3.5 py-1.5 text-xs font-semibold transition-all hover:bg-[var(--gl-surface-2)] active:scale-95 disabled:opacity-50"
                            style="border-color: rgba(234,179,8,0.3); background: rgba(234,179,8,0.08); color: #EAB308;"
                            :title="`Reveal an unguessed letter (-${hintCostAmount} XP)`"
                        >
                            <Lightbulb class="h-3.5 w-3.5" />
                            Hint (-{{ hintCostAmount }} XP)
                        </button>
                    </div>
                    <div v-else class="text-center">
                        <span
                            class="gl-feedback"
                            :class="answerResult ? 'solved' : 'wrong'"
                        >
                            <CheckCircle2
                                v-if="answerResult"
                                class="h-4 w-4"
                                :stroke-width="2.5"
                            />
                            <X v-else class="h-4 w-4" :stroke-width="2.5" />
                            {{ answerResult ? 'Solved!' : 'Out of tries!' }}
                        </span>
                        <p v-if="answerResult" class="gl-answer-reveal select-none">
                            Answer:
                            <strong style="color: var(--gl-success)">{{
                                currentGame.cards[currentCard]?.answer
                            }}</strong>
                        </p>
                        <p v-else class="gl-answer-reveal text-[var(--gl-text-muted)] italic select-none">
                            Answer hidden to prevent copying
                        </p>
                    </div>
                </div>

                <!-- Speed Quiz -->
                <div
                    v-else-if="currentGame.type === 'speedquiz'"
                    class="p-6"
                    style="min-height: 280px"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <span class="gl-eyebrow">Speed Quiz</span>
                        <span
                            class="flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold"
                            :style="
                                speedTimeLeft <= 5
                                    ? 'background: var(--gl-danger-bg); color: var(--gl-danger);'
                                    : 'background: rgba(59,130,246,0.1); color: var(--gl-primary);'
                            "
                        >
                            <Timer class="h-3 w-3" :stroke-width="2" />
                            {{ speedTimeLeft }}s
                        </span>
                    </div>
                    <div class="mb-4">
                        <div
                            class="h-1.5 overflow-hidden rounded-full"
                            style="background: var(--gl-surface-2)"
                        >
                            <div
                                class="h-full rounded-full transition-all duration-1000 ease-linear"
                                :style="{
                                    width:
                                        (speedTimeLeft / SPEED_SECONDS) * 100 +
                                        '%',
                                    background:
                                        speedTimeLeft <= 5
                                            ? 'var(--gl-danger)'
                                            : 'var(--gl-success)',
                                }"
                            ></div>
                        </div>
                    </div>
                    <div class="mb-4 text-center">
                        <div class="gl-q-row">
                            <span class="gl-q-num">{{ currentCard + 1 }}</span>
                            <span class="gl-q-sep">/</span>
                            <span class="gl-q-total">{{
                                currentGame.cards.length
                            }}</span>
                        </div>
                        <h3 class="gl-question">
                            {{ currentGame.cards[currentCard]?.question }}
                        </h3>
                    </div>
                    <div v-if="!answered" class="space-y-2.5">
                        <button
                            v-for="(opt, oi) in currentGame.cards[currentCard]
                                ?.options || []"
                            :key="oi"
                            @click="checkAnswer(opt)"
                            class="w-full rounded-xl border px-4 py-3 text-left text-sm transition-all hover:bg-[rgba(59,130,246,0.04)] break-words"
                            :style="
                                selectedOption === opt &&
                                opt !== currentGame.cards[currentCard]?.answer
                                    ? 'border-color: var(--gl-danger); background: var(--gl-danger-bg); color: var(--gl-text-primary);'
                                    : selectedOption === opt &&
                                        opt ===
                                            currentGame.cards[currentCard]
                                                ?.answer
                                      ? 'border-color: var(--gl-success); background: var(--gl-success-bg); color: var(--gl-text-primary);'
                                      : 'border-color: var(--gl-border); color: var(--gl-text-secondary);'
                            "
                        >
                            {{ opt }}
                        </button>
                    </div>
                    <div v-else class="text-center">
                        <span
                            class="gl-feedback"
                            :class="
                                selectedOption ===
                                currentGame.cards[currentCard]?.answer
                                    ? 'correct'
                                    : 'wrong'
                            "
                        >
                            <CheckCircle2
                                v-if="
                                    selectedOption ===
                                    currentGame.cards[currentCard]?.answer
                                "
                                class="h-4 w-4"
                                :stroke-width="2.5"
                            />
                            <X v-else class="h-4 w-4" :stroke-width="2.5" />
                            {{
                                selectedOption ===
                                currentGame.cards[currentCard]?.answer
                                    ? 'Correct!'
                                    : "Time's up!"
                            }}
                        </span>
                        <p
                            v-if="selectedOption === currentGame.cards[currentCard]?.answer"
                            class="gl-answer-reveal select-none"
                        >
                            The correct answer is:
                            <strong style="color: var(--gl-success)">{{
                                currentGame.cards[currentCard]?.answer
                            }}</strong>
                        </p>
                        <p v-else class="gl-answer-reveal text-[var(--gl-text-muted)] italic select-none">
                            Answer hidden to prevent copying
                        </p>
                    </div>
                </div>

                <!-- Drag & Drop Matching -->
                <div
                    v-else-if="currentGame.type === 'dragdrop'"
                    class="p-6"
                    style="min-height: 280px"
                >
                    <div class="mb-4 text-center">
                        <span class="gl-eyebrow">Drag &amp; Drop Matching</span>
                        <p
                            class="mt-2 text-xs"
                            style="color: var(--gl-text-secondary)"
                        >
                            Drag each term onto its matching definition, or tap
                            a term then its match.
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-2">
                            <p
                                class="text-[10px] font-bold tracking-widest uppercase"
                                style="color: var(--gl-text-muted)"
                            >
                                Terms
                            </p>
                            <div
                                v-for="item in dropItems"
                                :key="item.cardId"
                                draggable="true"
                                @dragstart="
                                    $event.dataTransfer?.setData(
                                        'text/plain',
                                        String(item.cardId),
                                    )
                                "
                                @click="selectDropItem(item)"
                                class="cursor-grab rounded-xl border px-3 py-2 text-xs font-medium transition-all active:cursor-grabbing"
                                :style="
                                    dropSelected?.cardId === item.cardId
                                        ? 'border-color: var(--gl-primary); box-shadow: 0 0 0 2px var(--gl-primary-glow); background: var(--gl-surface-2); color: var(--gl-text-primary);'
                                        : 'background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);'
                                "
                            >
                                {{ item.text }}
                            </div>
                            <p
                                v-if="!dropItems.length"
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                            >
                                All matched!
                            </p>
                        </div>
                        <div class="space-y-2">
                            <p
                                class="text-[10px] font-bold tracking-widest uppercase"
                                style="color: var(--gl-text-muted)"
                            >
                                Definitions
                            </p>
                            <div
                                v-for="target in dropTargets"
                                :key="target.cardId"
                                @dragover.prevent
                                @drop="onDropItem($event, target)"
                                @click="dropOnTarget(target)"
                                class="rounded-xl border px-3 py-2 text-xs transition-all"
                                :style="
                                    target.matched
                                        ? 'border-color: rgba(16,185,129,0.4); background: var(--gl-success-bg); color: var(--gl-success);'
                                        : dropWrong?.cardId === target.cardId
                                          ? 'border-color: var(--gl-danger); background: var(--gl-danger-bg); color: var(--gl-danger); animation: gl-shake 0.3s ease;'
                                          : 'background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-secondary);'
                                "
                            >
                                <span
                                    v-if="target.matched"
                                    class="flex items-center gap-1.5"
                                >
                                    <CheckCircle2
                                        class="h-3.5 w-3.5"
                                        :stroke-width="2.5"
                                    />
                                    {{ target.text }}
                                </span>
                                <span v-else>{{ target.text }}</span>
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="dropMatches === currentGame.cards.length"
                        class="mt-4 text-center"
                    >
                        <span class="gl-feedback correct">All matched!</span>
                    </div>
                </div>

                <!-- Order the Steps -->
                <div
                    v-else-if="currentGame.type === 'ordering'"
                    class="p-6"
                    style="min-height: 280px"
                >
                    <div class="mb-4 text-center">
                        <span class="gl-eyebrow">Order the Steps</span>
                        <p
                            class="mt-2 text-xs"
                            style="color: var(--gl-text-secondary)"
                        >
                            Use the arrows to arrange the steps in the correct
                            order.
                        </p>
                    </div>
                    <div class="mx-auto max-w-md space-y-2">
                        <div
                            v-for="(step, si) in orderSteps"
                            :key="step.cardId"
                            class="flex items-center gap-2 rounded-xl border px-3 py-2.5 transition-all"
                            :style="
                                orderFinished
                                    ? orderGraded[si]
                                        ? 'border-color: rgba(16,185,129,0.4); background: var(--gl-success-bg);'
                                        : 'border-color: rgba(239,68,68,0.4); background: var(--gl-danger-bg);'
                                    : 'background: var(--gl-surface-2); border-color: var(--gl-border);'
                            "
                        >
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-[10px] font-bold"
                                :style="
                                    orderFinished
                                        ? orderGraded[si]
                                            ? 'background: rgba(16,185,129,0.15); color: var(--gl-success);'
                                            : 'background: rgba(239,68,68,0.15); color: var(--gl-danger);'
                                        : 'background: rgba(59,130,246,0.1); color: var(--gl-primary);'
                                "
                            >
                                {{
                                    orderFinished && !orderGraded[si]
                                        ? '✗'
                                        : si + 1
                                }}
                            </span>
                            <span
                                class="flex-1 text-xs font-medium"
                                style="color: var(--gl-text-primary)"
                                >{{ step.text }}</span
                            >
                            <div class="flex shrink-0 flex-col gap-0.5">
                                <button
                                    @click="moveOrder(si, -1)"
                                    :disabled="si === 0"
                                    class="rounded p-0.5 transition-colors hover:bg-[var(--gl-surface-2)] disabled:opacity-25"
                                    style="color: var(--gl-text-muted)"
                                >
                                    <ChevronUp
                                        class="h-3.5 w-3.5"
                                        :stroke-width="2.5"
                                    />
                                </button>
                                <button
                                    @click="moveOrder(si, 1)"
                                    :disabled="si === orderSteps.length - 1"
                                    class="rounded p-0.5 transition-colors hover:bg-[var(--gl-surface-2)] disabled:opacity-25"
                                    style="color: var(--gl-text-muted)"
                                >
                                    <ChevronDown
                                        class="h-3.5 w-3.5"
                                        :stroke-width="2.5"
                                    />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4 Pics 1 Word -->
                <div
                    v-else-if="currentGame.type === 'fourpics'"
                    class="p-6"
                    style="min-height: 280px"
                >
                    <div class="mb-4 text-center">
                        <span class="gl-eyebrow">4 Pics 1 Word</span>
                        <div class="gl-q-row">
                            <span class="gl-q-num">{{ currentCard + 1 }}</span>
                            <span class="gl-q-sep">/</span>
                            <span class="gl-q-total">{{ currentGame.cards.length }}</span>
                        </div>
                        <h3 class="gl-question">
                            {{ currentGame.cards[currentCard]?.question || 'What word connects all 4 pictures?' }}
                        </h3>
                    </div>

                    <!-- 2x2 Image Grid -->
                    <div class="mx-auto mb-4 grid max-w-[280px] sm:max-w-[320px] grid-cols-2 gap-2 sm:gap-2.5">
                        <div
                            v-for="(imgUrl, pIdx) in fourPicsImages"
                            :key="pIdx"
                            class="group relative aspect-square cursor-pointer overflow-hidden rounded-xl border transition-all hover:scale-[1.02] hover:shadow-md"
                            style="background: var(--gl-surface-2); border-color: var(--gl-border);"
                            @click="imgUrl ? (fourPicsZoomImage = imgUrl) : null"
                            :title="imgUrl ? 'Click to zoom' : ''"
                        >
                            <img
                                v-if="imgUrl"
                                :src="imgUrl"
                                :alt="'Picture ' + (pIdx + 1)"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                @error="(e: any) => (e.target.style.display = 'none')"
                            />
                            <div
                                v-else
                                class="flex h-full w-full flex-col items-center justify-center gap-1 p-2 text-center"
                                style="color: var(--gl-text-muted);"
                            >
                                <ImageIcon class="h-6 w-6 opacity-40" />
                                <span class="text-[10px] font-medium">Pic {{ pIdx + 1 }}</span>
                            </div>
                            <div
                                v-if="imgUrl"
                                class="absolute bottom-1.5 right-1.5 flex h-6 w-6 items-center justify-center rounded-full opacity-0 shadow transition-opacity group-hover:opacity-100"
                                style="background: rgba(0,0,0,0.65); color: white;"
                            >
                                <ZoomIn class="h-3 w-3" />
                            </div>
                        </div>
                    </div>

                    <template v-if="!answered">
                        <!-- Word Length Indicator -->
                        <div class="mb-2 text-center">
                            <span class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--gl-text-muted);">
                                {{ fourPicsSlots.length }} Letters
                            </span>
                        </div>

                        <!-- Answer Slots -->
                        <div
                            class="mb-4 flex flex-wrap items-center justify-center gap-1.5 sm:gap-2 transition-transform duration-200"
                            :class="{ 'gl-shake': fourPicsShake }"
                        >
                            <button
                                v-for="(slot, sIdx) in fourPicsSlots"
                                :key="sIdx"
                                type="button"
                                @click="tapSlot(sIdx)"
                                class="flex h-10 w-9 sm:h-12 sm:w-10 items-center justify-center rounded-xl border text-base font-black uppercase transition-all"
                                :style="
                                    slot
                                        ? fourPicsWrong
                                            ? 'background: var(--gl-danger-bg); border-color: var(--gl-danger); color: var(--gl-danger); box-shadow: 0 0 10px rgba(239,68,68,0.25);'
                                            : 'background: linear-gradient(135deg, rgba(124,58,237,0.18), rgba(59,130,246,0.18)); border-color: var(--gl-primary); color: var(--gl-primary); box-shadow: 0 0 8px var(--gl-primary-glow); cursor: pointer;'
                                        : 'background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-muted); border-style: dashed;'
                                "
                                :title="slot ? 'Click to remove letter' : ''"
                            >
                                {{ slot ? slot.letter : '' }}
                            </button>
                        </div>

                        <!-- Wrong guess notification -->
                        <div v-if="fourPicsWrong" class="mb-2 text-center">
                            <span class="text-xs font-semibold text-rose-500 animate-pulse">
                                Not quite! Tap letters to fix, or click Give Up.
                            </span>
                        </div>

                        <!-- Letter Bank Keyboard -->
                        <div class="mx-auto mb-3 flex max-w-sm flex-wrap justify-center gap-1.5 sm:gap-2">
                            <button
                                v-for="item in fourPicsBank"
                                :key="item.id"
                                type="button"
                                @click="tapBankLetter(item)"
                                :disabled="item.used"
                                class="flex h-10 w-9 sm:h-11 sm:w-10 items-center justify-center rounded-lg border text-sm font-black uppercase transition-all"
                                :style="
                                    item.used
                                        ? 'opacity: 0.18; cursor: default; border-color: transparent; background: var(--gl-surface-2);'
                                        : 'background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary); cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.06);'
                                "
                            >
                                {{ item.letter }}
                            </button>
                        </div>

                        <!-- Utility actions: Shuffle, Clear, Hint, Give Up -->
                        <div class="flex items-center justify-center gap-2">
                            <button
                                type="button"
                                @click="shuffleFourPicsBank"
                                class="flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-[11px] font-semibold transition-all hover:bg-[var(--gl-surface-2)]"
                                style="border-color: var(--gl-border); color: var(--gl-text-secondary);"
                                title="Shuffle letter bank"
                            >
                                <Shuffle class="h-3 w-3" />
                                Shuffle
                            </button>
                            <button
                                type="button"
                                @click="clearFourPics"
                                class="flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-[11px] font-semibold transition-all hover:bg-[var(--gl-surface-2)]"
                                style="border-color: var(--gl-border); color: var(--gl-text-secondary);"
                                title="Clear all placed letters"
                            >
                                <RotateCcw class="h-3 w-3" />
                                Clear
                            </button>
                            <button
                                type="button"
                                @click="requestHintFourPics"
                                :disabled="hintDeducting"
                                class="flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-[11px] font-semibold transition-all hover:bg-[var(--gl-surface-2)] active:scale-95 disabled:opacity-50"
                                style="border-color: rgba(234,179,8,0.3); background: rgba(234,179,8,0.08); color: #EAB308;"
                                :title="`Reveal a correct letter (-${hintCostAmount} XP)`"
                            >
                                <Lightbulb class="h-3 w-3" />
                                Hint (-{{ hintCostAmount }} XP)
                            </button>
                            <button
                                v-if="fourPicsWrong"
                                type="button"
                                @click="giveUpFourPics"
                                class="flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-[11px] font-semibold transition-all"
                                style="border-color: rgba(239,68,68,0.3); background: rgba(239,68,68,0.08); color: var(--gl-danger);"
                                title="Give up on this puzzle"
                            >
                                <Flag class="h-3 w-3" />
                                Give Up
                            </button>
                        </div>
                    </template>

                    <!-- Answer Reveal Feedback -->
                    <div v-else class="text-center">
                        <span
                            class="gl-feedback"
                            :class="answerResult ? 'correct' : 'wrong'"
                        >
                            <CheckCircle2
                                v-if="answerResult"
                                class="h-4 w-4"
                                :stroke-width="2.5"
                            />
                            <X v-else class="h-4 w-4" :stroke-width="2.5" />
                            {{ answerResult ? 'Puzzle Solved!' : 'Better luck next time!' }}
                        </span>
                        <p v-if="answerResult" class="gl-answer-reveal select-none">
                            Answer:
                            <strong style="color: var(--gl-success)">{{
                                currentGame.cards[currentCard]?.answer
                            }}</strong>
                        </p>
                        <p v-else class="gl-answer-reveal text-[var(--gl-text-muted)] italic select-none">
                            Answer hidden to prevent copying
                        </p>
                    </div>
                </div>

                <!-- Crossword Puzzle -->
                <div
                    v-else-if="currentGame.type === 'crossword'"
                    class="crossword-board flex h-full min-h-0 flex-col overflow-hidden p-2 sm:p-4"
                    style="min-height: 0"
                >
                    <div class="mb-2 shrink-0 text-center sm:mb-3">
                        <span class="gl-eyebrow">Crossword Puzzle</span>
                        <div class="gl-q-row">
                            <span class="gl-q-num">{{ crosswordSolvedCount }}</span>
                            <span class="gl-q-sep">/</span>
                            <span class="gl-q-total">{{ crosswordWords.length }} Solved</span>
                        </div>
                        <p class="text-xs mt-1" style="color: var(--gl-text-muted)">
                            Type answers into the grid. Click clues or cells to change focus and direction.
                        </p>
                    </div>

                    <!-- Active Clue Banner -->
                    <div
                        class="mb-2 flex shrink-0 items-center justify-between rounded-xl px-3 py-2 transition-all sm:mb-3 sm:px-4 sm:py-2.5"
                        style="background: rgba(139, 92, 246, 0.12); border: 1px solid rgba(139, 92, 246, 0.25);"
                    >
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span
                                class="flex h-6 px-2 shrink-0 items-center justify-center rounded-lg text-xs font-bold uppercase"
                                style="background: var(--gl-primary); color: #fff;"
                            >
                                {{ crosswordSelectedWord ? (crosswordSelectedWord.number + ' ' + (crosswordSelectedWord.direction === 'across' ? 'Across' : 'Down')) : 'Clue' }}
                            </span>
                            <p class="text-xs font-semibold break-words" style="color: var(--gl-text-primary)">
                                {{ crosswordSelectedWord ? crosswordSelectedWord.clue : 'Select a clue or cell to begin' }}
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="toggleCrosswordDirection"
                            class="ml-2 shrink-0 rounded-lg px-2 py-1 text-[11px] font-medium border transition-colors hover:bg-white/10"
                            style="border-color: var(--gl-border); color: var(--gl-text-secondary);"
                        >
                            Dir: {{ crosswordDirection.toUpperCase() }}
                        </button>
                    </div>

                    <!-- Grid and Clues layout -->
                    <div class="crossword-layout grid min-h-0 flex-1 grid-cols-2 items-stretch gap-3 lg:grid-cols-12 lg:gap-4">
                        <!-- Crossword Grid (7 cols on lg) -->
                        <div class="crossword-grid-panel flex min-h-0 flex-col items-center lg:col-span-7">
                            <div
                                class="crossword-grid-shell block w-full max-w-full rounded-2xl border p-2 shadow-inner sm:p-3"
                                style="background: var(--gl-surface-2); border-color: var(--gl-border);"
                            >
                                <div
                                    class="crossword-grid grid w-full gap-1"
                                    :style="{ gridTemplateColumns: `repeat(${crosswordGrid[0]?.length || crosswordGrid.length || 11}, minmax(0, 1fr))` }"
                                >
                                    <template v-for="(row, r) in crosswordGrid" :key="'r-' + r">
                                        <div
                                            v-for="(cell, c) in row"
                                            :key="'c-' + r + '-' + c"
                                            class="crossword-cell relative flex aspect-square select-none items-center justify-center rounded-md text-[10px] font-extrabold uppercase transition-all duration-150 sm:text-sm"
                                            :style="
                                                cell
                                                    ? isCrosswordSelectedCell(r, c)
                                                        ? 'background: var(--gl-primary); color: #fff; box-shadow: 0 0 10px var(--gl-primary-glow); transform: scale(1.05); z-index: 10;'
                                                        : isCrosswordWordHighlighted(cell)
                                                          ? 'background: rgba(139, 92, 246, 0.22); color: var(--gl-text-primary); border: 1.5px solid var(--gl-primary); cursor: pointer;'
                                                          : isCrosswordCellSolved(cell)
                                                            ? 'background: rgba(16, 185, 129, 0.18); color: var(--gl-success); border: 1px solid rgba(16, 185, 129, 0.4); cursor: pointer;'
                                                            : 'background: var(--gl-surface); color: var(--gl-text-primary); border: 1px solid var(--gl-border); cursor: pointer;'
                                                    : 'background: transparent; border: 1px dashed rgba(255,255,255,0.04);'
                                            "
                                            @click="cell ? selectCrosswordCell(r, c) : null"
                                        >
                                            <span
                                                v-if="cell && cell.number"
                                                class="absolute top-0.5 left-0.5 text-[8px] sm:text-[9px] font-bold leading-none pointer-events-none"
                                                :style="{ color: isCrosswordSelectedCell(r, c) ? 'rgba(255,255,255,0.85)' : 'var(--gl-text-muted)' }"
                                            >
                                                {{ cell.number }}
                                            </span>
                                            <span v-if="cell">{{ cell.value || '' }}</span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Quick On-Screen Keyboard (Universal for Desktop, Tablet & Mobile) -->
                            <div class="crossword-keyboard mt-2 flex w-full max-w-md flex-col items-center gap-1 mx-auto select-none sm:mt-3 sm:gap-1.5">
                                <p class="text-[10px] font-semibold text-center" style="color: var(--gl-text-muted)">
                                    Type on keyboard or tap letters below:
                                </p>
                                <div class="flex flex-wrap justify-center gap-1">
                                    <button
                                        v-for="letter in 'QWERTYUIOP'.split('')"
                                        :key="letter"
                                        type="button"
                                        @click="inputCrosswordLetter(letter)"
                                        class="h-7 w-7 sm:h-8 sm:w-8 rounded-lg bg-[var(--gl-surface-2)] text-xs font-bold border border-[var(--gl-border)] active:scale-95 text-[var(--gl-text-primary)] hover:border-[var(--gl-primary)] transition-all"
                                    >
                                        {{ letter }}
                                    </button>
                                </div>
                                <div class="flex flex-wrap justify-center gap-1">
                                    <button
                                        v-for="letter in 'ASDFGHJKL'.split('')"
                                        :key="letter"
                                        type="button"
                                        @click="inputCrosswordLetter(letter)"
                                        class="h-7 w-7 sm:h-8 sm:w-8 rounded-lg bg-[var(--gl-surface-2)] text-xs font-bold border border-[var(--gl-border)] active:scale-95 text-[var(--gl-text-primary)] hover:border-[var(--gl-primary)] transition-all"
                                    >
                                        {{ letter }}
                                    </button>
                                </div>
                                <div class="flex flex-wrap justify-center gap-1">
                                    <button
                                        type="button"
                                        @click="toggleCrosswordDirection"
                                        class="h-7 px-2 sm:h-8 rounded-lg bg-indigo-500/20 text-indigo-400 text-[10px] font-bold border border-indigo-500/30 active:scale-95"
                                        title="Toggle Across/Down Direction"
                                    >
                                        DIR: {{ crosswordDirection.toUpperCase() }}
                                    </button>
                                    <button
                                        v-for="letter in 'ZXCVBNM'.split('')"
                                        :key="letter"
                                        type="button"
                                        @click="inputCrosswordLetter(letter)"
                                        class="h-7 w-7 sm:h-8 sm:w-8 rounded-lg bg-[var(--gl-surface-2)] text-xs font-bold border border-[var(--gl-border)] active:scale-95 text-[var(--gl-text-primary)] hover:border-[var(--gl-primary)] transition-all"
                                    >
                                        {{ letter }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteCrosswordCell"
                                        class="h-7 px-2.5 sm:h-8 rounded-lg bg-rose-500/20 text-rose-400 text-[10px] font-bold border border-rose-500/30 active:scale-95"
                                        title="Backspace / Delete"
                                    >
                                        DEL
                                    </button>
                                </div>
                                <div v-if="crosswordHasNumbers" class="flex flex-wrap justify-center gap-1 mt-0.5">
                                    <button
                                        v-for="num in '1234567890'.split('')"
                                        :key="num"
                                        type="button"
                                        @click="inputCrosswordLetter(num)"
                                        class="h-7 w-7 sm:h-8 sm:w-8 rounded-lg bg-[var(--gl-surface-2)] text-xs font-bold border border-[var(--gl-border)] active:scale-95 text-[var(--gl-text-primary)] hover:border-[var(--gl-primary)] transition-all"
                                    >
                                        {{ num }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Word Details & Questions Card (Right - 5 cols on lg) -->
                        <div class="crossword-bank-panel flex min-h-0 min-w-0 flex-col lg:col-span-5">
                            <div
                                class="crossword-details-card flex h-full min-h-0 min-w-0 flex-1 flex-col overflow-hidden rounded-2xl border"
                                style="background: var(--gl-surface); border-color: var(--gl-border);"
                            >
                                <!-- Header of Details: 2 columns (Details | Questions) -->
                                <div
                                    class="crossword-details-header grid shrink-0 grid-cols-12 items-center gap-2 border-b px-3 py-2 text-[10px] font-extrabold uppercase tracking-wider text-[var(--gl-text-muted)] sm:gap-3 sm:px-4 sm:py-2.5"
                                    style="border-color: var(--gl-border);"
                                >
                                    <div class="col-span-5 sm:col-span-4 flex items-center gap-1.5">
                                        <span>Details</span>
                                        <span class="rounded-full bg-[var(--gl-surface-2)] px-1.5 py-0.5 text-[9px] font-semibold text-[var(--gl-text-secondary)]">
                                            {{ crosswordSolvedCount }}/{{ crosswordWords.length }}
                                        </span>
                                    </div>
                                    <div class="col-span-7 sm:col-span-8 flex items-center justify-between">
                                        <span>Questions</span>
                                        <span class="text-[9px] font-semibold text-[var(--gl-text-muted)]">
                                            {{ acrossWords.filter(w => w.solved).length }}/{{ acrossWords.length }} Across · {{ downWords.filter(w => w.solved).length }}/{{ downWords.length }} Down
                                        </span>
                                    </div>
                                </div>

                                <!-- Scrollable 2-Column Details List -->
                                <div class="crossword-bank flex-1 min-h-0 overflow-y-auto p-2 sm:p-2.5 space-y-1.5">
                                    <!-- Across Section Header -->
                                    <div class="flex items-center justify-between px-2 pt-1 pb-1">
                                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-[var(--gl-primary)] sm:text-xs">
                                            Across ({{ acrossWords.filter(w => w.solved).length }}/{{ acrossWords.length }})
                                        </span>
                                    </div>

                                    <button
                                        v-for="w in acrossWords"
                                        :key="'ac-' + w.number"
                                        :id="'cw-clue-across-' + w.number"
                                        type="button"
                                        @click="selectCrosswordWord(w)"
                                        class="crossword-entry w-full text-left grid grid-cols-12 items-center gap-2 py-2 px-2.5 rounded-xl border transition-all duration-200 sm:gap-3"
                                        :style="
                                            crosswordSelectedWord?.cardId === w.cardId && crosswordDirection === 'across'
                                                ? 'background: rgba(139, 92, 246, 0.16); border-color: var(--gl-primary); box-shadow: 0 0 0 1px var(--gl-primary);'
                                                : w.solved
                                                  ? 'background: rgba(16, 185, 129, 0.08); border-color: rgba(16, 185, 129, 0.25);'
                                                  : 'background: var(--gl-surface-2); border-color: var(--gl-border);'
                                        "
                                    >
                                        <!-- Column 1: Details -->
                                        <div class="col-span-5 sm:col-span-4 min-w-0 flex flex-col gap-0.5">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <span
                                                    class="inline-flex min-w-0 items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] font-bold border transition-all sm:text-xs"
                                                    :style="
                                                        w.solved
                                                            ? 'background: rgba(16, 185, 129, 0.2); border-color: rgba(16, 185, 129, 0.5); color: var(--gl-success);'
                                                            : 'background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-primary);'
                                                    "
                                                >
                                                    <CheckCircle2 v-if="w.solved" class="h-3 w-3 shrink-0 text-emerald-500" :stroke-width="2.5" />
                                                    <span class="truncate">{{ w.number }} Across</span>
                                                </span>
                                            </div>
                                            <div class="text-[10px] font-semibold text-[var(--gl-text-muted)] truncate px-0.5">
                                                <span v-if="w.solved" class="text-emerald-500 font-bold truncate">{{ w.word }}</span>
                                                <span v-else>{{ w.word.length }} letters</span>
                                            </div>
                                        </div>

                                        <!-- Column 2: Questions -->
                                        <div
                                            class="col-span-7 sm:col-span-8 min-w-0 text-[11px] leading-snug sm:text-xs"
                                            :class="w.solved ? 'line-through opacity-70 text-[var(--gl-text-muted)]' : 'text-[var(--gl-text-secondary)]'"
                                        >
                                            <p class="break-words">
                                                {{ w.clue }}
                                            </p>
                                        </div>
                                    </button>

                                    <!-- Down Section Header -->
                                    <div class="flex items-center justify-between px-2 pt-2.5 pb-1 border-t border-[var(--gl-border)]/50 mt-2">
                                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-[var(--gl-secondary)] sm:text-xs">
                                            Down ({{ downWords.filter(w => w.solved).length }}/{{ downWords.length }})
                                        </span>
                                    </div>

                                    <button
                                        v-for="w in downWords"
                                        :key="'dn-' + w.number"
                                        :id="'cw-clue-down-' + w.number"
                                        type="button"
                                        @click="selectCrosswordWord(w)"
                                        class="crossword-entry w-full text-left grid grid-cols-12 items-center gap-2 py-2 px-2.5 rounded-xl border transition-all duration-200 sm:gap-3"
                                        :style="
                                            crosswordSelectedWord?.cardId === w.cardId && crosswordDirection === 'down'
                                                ? 'background: rgba(139, 92, 246, 0.16); border-color: var(--gl-primary); box-shadow: 0 0 0 1px var(--gl-primary);'
                                                : w.solved
                                                  ? 'background: rgba(16, 185, 129, 0.08); border-color: rgba(16, 185, 129, 0.25);'
                                                  : 'background: var(--gl-surface-2); border-color: var(--gl-border);'
                                        "
                                    >
                                        <!-- Column 1: Details -->
                                        <div class="col-span-5 sm:col-span-4 min-w-0 flex flex-col gap-0.5">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <span
                                                    class="inline-flex min-w-0 items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] font-bold border transition-all sm:text-xs"
                                                    :style="
                                                        w.solved
                                                            ? 'background: rgba(16, 185, 129, 0.2); border-color: rgba(16, 185, 129, 0.5); color: var(--gl-success);'
                                                            : 'background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-secondary);'
                                                    "
                                                >
                                                    <CheckCircle2 v-if="w.solved" class="h-3 w-3 shrink-0 text-emerald-500" :stroke-width="2.5" />
                                                    <span class="truncate">{{ w.number }} Down</span>
                                                </span>
                                            </div>
                                            <div class="text-[10px] font-semibold text-[var(--gl-text-muted)] truncate px-0.5">
                                                <span v-if="w.solved" class="text-emerald-500 font-bold truncate">{{ w.word }}</span>
                                                <span v-else>{{ w.word.length }} letters</span>
                                            </div>
                                        </div>

                                        <!-- Column 2: Questions -->
                                        <div
                                            class="col-span-7 sm:col-span-8 min-w-0 text-[11px] leading-snug sm:text-xs"
                                            :class="w.solved ? 'line-through opacity-70 text-[var(--gl-text-muted)]' : 'text-[var(--gl-text-secondary)]'"
                                        >
                                            <p class="break-words">
                                                {{ w.clue }}
                                            </p>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Word Search -->
                <div
                    v-else-if="currentGame.type === 'wordsearch'"
                    class="word-search-board flex h-full min-h-0 flex-col overflow-hidden p-2 sm:p-4"
                    style="min-height: 0"
                >
                    <div class="word-search-top-header mb-2 shrink-0 text-center sm:mb-3">
                        <span class="gl-eyebrow !mb-1">Word Search</span>
                        <div class="gl-q-row !mb-1">
                            <span class="gl-q-num text-xl sm:text-2xl">{{ wordSearchFoundCount }}</span>
                            <span class="gl-q-sep">/</span>
                            <span class="gl-q-total text-xs sm:text-sm">{{ wordSearchTargets.length }} Words Found</span>
                        </div>
                        <p class="text-[11px] sm:text-xs" style="color: var(--gl-text-muted)">
                            Click start letter, then click end letter of any word (horizontal, vertical, or diagonal).
                        </p>
                    </div>

                    <div class="word-search-layout grid min-h-0 flex-1 grid-cols-1 items-stretch gap-3 lg:grid-cols-12 sm:gap-4">
                        <!-- Letter Matrix (Left - 7 cols on lg) -->
                        <div class="word-search-matrix flex min-h-0 min-w-0 items-center justify-center lg:col-span-7">
                            <div
                                class="word-search-matrix-shell flex items-center justify-center select-none rounded-2xl border p-2 sm:p-3"
                                style="background: var(--gl-surface-2); border-color: var(--gl-border);"
                            >
                                <div
                                    class="word-search-matrix-grid grid w-full h-full"
                                    :style="{ gridTemplateColumns: `repeat(${wordSearchMatrix[0]?.length || wordSearchMatrix.length || 10}, minmax(0, 1fr))` }"
                                >
                                    <template v-for="(row, r) in wordSearchMatrix" :key="'ws-r-' + r">
                                        <button
                                            v-for="(char, c) in row"
                                            :key="'ws-c-' + r + '-' + c"
                                            type="button"
                                            class="word-search-cell flex aspect-square select-none items-center justify-center rounded-md font-extrabold uppercase transition-all duration-100"
                                            :style="getWSCellStyle(r, c)"
                                            @click="handleWSCellClick(r, c)"
                                            @mouseenter="handleWSCellHover(r, c)"
                                        >
                                            {{ char }}
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Word Details Card (Right - 5 cols on lg) -->
                        <div class="word-search-bank-panel flex min-h-0 min-w-0 flex-col lg:col-span-5">
                            <div
                                class="word-search-details-card flex h-full min-h-0 min-w-0 flex-1 flex-col overflow-hidden rounded-2xl border"
                                style="background: var(--gl-surface); border-color: var(--gl-border);"
                            >
                                <!-- Header of Details: 2 columns (Word | Definition) -->
                                <div
                                    class="word-search-details-header grid shrink-0 grid-cols-12 items-center gap-2 border-b px-3 py-2 text-[10px] font-extrabold uppercase tracking-wider text-[var(--gl-text-muted)] sm:gap-3 sm:px-4 sm:py-2.5"
                                    style="border-color: var(--gl-border);"
                                >
                                    <div class="col-span-5 flex items-center gap-1.5">
                                        <span>Word</span>
                                        <span class="rounded-full bg-[var(--gl-surface-2)] px-1.5 py-0.5 text-[9px] font-semibold text-[var(--gl-text-secondary)]">
                                            {{ wordSearchFoundCount }}/{{ wordSearchTargets.length }}
                                        </span>
                                    </div>
                                    <div class="col-span-7">
                                        <span>Definition</span>
                                    </div>
                                </div>

                                <!-- Scrollable 2-Column Details List -->
                                <div class="word-search-bank flex-1 min-h-0 overflow-y-auto p-2 sm:p-2.5 space-y-1.5">
                                    <div
                                        v-for="t in wordSearchTargets"
                                        :key="t.cardId"
                                        class="word-search-entry grid grid-cols-12 items-center gap-2 py-2 px-2.5 rounded-xl border transition-all duration-200 sm:gap-3"
                                        :style="
                                            t.found
                                                ? {
                                                      background: `${t.color}15`,
                                                      borderColor: `${t.color}60`,
                                                  }
                                                : {
                                                      background: 'var(--gl-surface-2)',
                                                      borderColor: 'var(--gl-border)',
                                                  }
                                        "
                                    >
                                        <!-- Column 1: Word -->
                                        <div class="col-span-5 min-w-0 flex items-center gap-1.5">
                                            <span
                                                class="inline-flex min-w-0 items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-bold border transition-all sm:text-xs"
                                                :style="
                                                    t.found
                                                        ? {
                                                              background: `${t.color}25`,
                                                              borderColor: t.color,
                                                              color: t.color,
                                                              textDecoration: 'line-through',
                                                          }
                                                        : {
                                                              background: 'var(--gl-surface)',
                                                              borderColor: 'var(--gl-border)',
                                                              color: 'var(--gl-text-primary)',
                                                          }
                                                "
                                            >
                                                <CheckCircle2 v-if="t.found" class="h-3 w-3 shrink-0" :stroke-width="2.5" />
                                                <span class="truncate">{{ t.word }}</span>
                                            </span>
                                        </div>

                                        <!-- Column 2: Definition -->
                                        <div
                                            class="col-span-7 min-w-0 text-[11px] leading-snug sm:text-xs"
                                            :class="t.found ? 'line-through opacity-70' : 'text-[var(--gl-text-secondary)]'"
                                        >
                                            <p class="break-words">
                                                {{ t.clue && t.clue !== 'Word Search' ? t.clue : 'Find this word in the grid' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Code Puzzle -->
                <div
                    v-else-if="currentGame.type === 'codepuzzle'"
                    class="p-4 sm:p-6"
                    style="min-height: 400px"
                >
                    <!-- Header Bar -->
                    <div class="mb-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                            <div>
                                <span class="gl-eyebrow">Code &amp; Syntax Puzzle</span>
                                <h3 class="text-base sm:text-lg font-bold flex items-center gap-2" style="color: var(--gl-text-primary)">
                                    <Code class="h-5 w-5 text-emerald-400" />
                                    {{ currentGame.title || 'Code Sequence Challenge' }}
                                </h3>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <button
                                    type="button"
                                    @click="showCodeHelp = !showCodeHelp"
                                    class="flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-medium transition-colors"
                                    :style="
                                        showCodeHelp
                                            ? 'background: rgba(59,130,246,0.18); border-color: rgba(59,130,246,0.4); color: #93c5fd;'
                                            : 'background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-secondary);'
                                    "
                                >
                                    <Lightbulb class="h-3.5 w-3.5 text-amber-400" />
                                    <span>{{ showCodeHelp ? 'Hide Tips' : 'Rules &amp; Tips' }}</span>
                                </button>
                                <div class="gl-q-row ml-auto sm:ml-0">
                                    <span class="gl-q-num">{{ codePuzzleCorrectCount }}</span>
                                    <span class="gl-q-sep">/</span>
                                    <span class="gl-q-total">{{ codeLines.length }} Correct</span>
                                </div>
                            </div>
                        </div>

                        <!-- Mission Objective banner if description exists -->
                        <div
                            v-if="currentGame.description"
                            class="rounded-xl p-2.5 sm:p-3 text-xs mb-3 flex items-start gap-2.5 border"
                            style="background: rgba(16, 185, 129, 0.08); border-color: rgba(16, 185, 129, 0.25); color: #a7f3d0;"
                        >
                            <Sparkles class="h-4 w-4 shrink-0 text-emerald-400 mt-0.5" />
                            <div class="flex-1">
                                <span class="font-bold uppercase tracking-wider text-[10px] text-emerald-400 block mb-0.5">Objective</span>
                                <span class="text-slate-200">{{ currentGame.description }}</span>
                            </div>
                        </div>

                        <!-- Expandable Help & Logic Guide -->
                        <div
                            v-if="showCodeHelp"
                            class="rounded-xl p-3 text-xs mb-3 border space-y-2"
                            style="background: rgba(30, 41, 59, 0.8); border-color: rgba(148, 163, 184, 0.2); color: #cbd5e1;"
                        >
                            <div class="font-semibold text-sky-300 flex items-center gap-1.5 text-xs">
                                <Lightbulb class="h-4 w-4 text-amber-400" />
                                <span>How to Arrange &amp; Common Fixes</span>
                            </div>
                            <ul class="list-disc list-inside space-y-1 text-[11px] text-slate-300 leading-relaxed">
                                <li><strong class="text-white">Reorder lines:</strong> Drag any code card using its grip handle <span class="text-slate-400 font-bold">⠿</span> or click the <strong class="text-white">▲ / ▼</strong> arrow buttons.</li>
                                <li><strong class="text-white">Match Target Output:</strong> Arrange the statements so the execution order outputs the target result in the green box.</li>
                                <li><strong class="text-white">Execution Sequence:</strong> Variable &amp; constant declarations first &rarr; Calculations &amp; loops second &rarr; Output / print / return statements last.</li>
                                <li><strong class="text-white">Compiler Diagnostics:</strong> Click <strong class="text-white">"Run &amp; Verify"</strong>. If an error occurs, check the console output below for line-specific fix advice.</li>
                            </ul>
                        </div>

                        <!-- Target / Expected Output Box -->
                        <div
                            v-if="codeExpectedOutput"
                            class="rounded-xl border p-3 mb-3 font-mono text-xs shadow-inner"
                            style="background: #080d16; border-color: rgba(16, 185, 129, 0.35);"
                        >
                            <div class="flex items-center justify-between text-[11px] font-semibold mb-1.5">
                                <span class="flex items-center gap-1.5 text-emerald-400">
                                    <Terminal class="h-3.5 w-3.5 text-emerald-400" />
                                    Expected Program Output:
                                </span>
                                <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    Target Spec
                                </span>
                            </div>
                            <div class="rounded-lg bg-black/60 p-2.5 text-emerald-300 border border-emerald-950/80 font-mono text-[11px] sm:text-xs whitespace-pre-wrap select-all">
{{ codeExpectedOutput }}
                            </div>
                        </div>
                    </div>

                    <!-- IDE Code Window -->
                    <div
                        class="mx-auto w-full overflow-hidden rounded-2xl border shadow-xl"
                        style="background: #111827; border-color: var(--gl-border);"
                    >
                        <div class="flex items-center justify-between border-b px-4 py-2.5" style="border-color: rgba(255,255,255,0.08); background: #0b0f19;">
                            <div class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full bg-rose-500/80 inline-block"></span>
                                <span class="h-3 w-3 rounded-full bg-amber-500/80 inline-block"></span>
                                <span class="h-3 w-3 rounded-full bg-emerald-500/80 inline-block"></span>
                                <span class="ml-2 font-mono text-xs text-slate-400">solution.ts</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] text-slate-400 hidden sm:inline">Drag cards or use arrows to sort</span>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-bold font-mono"
                                    :style="
                                        codePuzzleSolved
                                            ? 'background: rgba(16,185,129,0.2); color: #34d399;'
                                            : 'background: rgba(59,130,246,0.15); color: #60a5fa;'
                                    "
                                >
                                    {{ codePuzzleSolved ? '● VERIFIED' : '● EDITING' }}
                                </span>
                            </div>
                        </div>

                        <!-- Draggable Code Lines -->
                        <div class="p-2 space-y-1.5" style="border-color: rgba(255,255,255,0.05);">
                            <div
                                v-for="(line, idx) in codeLines"
                                :key="line.cardId"
                                :draggable="!codePuzzleSolved"
                                @dragstart="onCodeDragStart($event, idx)"
                                @dragover.prevent="onCodeDragOver($event, idx)"
                                @dragleave="onCodeDragLeave($event, idx)"
                                @drop.prevent="onCodeDrop($event, idx)"
                                @dragend="onCodeDragEnd"
                                class="group flex items-center gap-2.5 rounded-xl border p-2.5 font-mono text-xs transition-all duration-150 select-none"
                                :class="{
                                    'opacity-30 border-dashed border-sky-400 bg-sky-950/30 scale-[0.99]': draggedCodeLineIdx === idx,
                                    'ring-2 ring-sky-400 border-sky-400 bg-sky-900/40 scale-[1.01]': dragOverCodeLineIdx === idx && draggedCodeLineIdx !== idx,
                                    'cursor-grab active:cursor-grabbing': !codePuzzleSolved
                                }"
                                :style="
                                    codeLinesGraded[idx] === true
                                        ? 'background: rgba(16, 185, 129, 0.12); border-color: rgba(16, 185, 129, 0.45); color: #6ee7b7;'
                                        : codeLinesGraded[idx] === false
                                          ? 'background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.45); color: #fca5a5;'
                                          : 'background: rgba(255, 255, 255, 0.03); border-color: rgba(255, 255, 255, 0.08); color: #e2e8f0;'
                                "
                            >
                                <!-- Drag Grip Handle -->
                                <div
                                    class="p-0.5 text-slate-500 hover:text-slate-200 transition-colors shrink-0"
                                    :class="{ 'opacity-25 cursor-not-allowed': codePuzzleSolved }"
                                    title="Drag to rearrange"
                                >
                                    <GripVertical class="h-4 w-4" />
                                </div>

                                <!-- Step Number -->
                                <span class="w-6 shrink-0 text-right select-none text-[11px] font-bold text-slate-500">
                                    {{ String(idx + 1).padStart(2, '0') }}
                                </span>

                                <!-- Statement Content -->
                                <div class="flex-1 min-w-0 flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-slate-200 text-xs sm:text-sm select-all">{{ line.code }}</span>
                                    <span
                                        v-if="line.comment"
                                        class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] text-slate-400 font-sans"
                                    >
                                        // {{ line.comment }}
                                    </span>
                                </div>

                                <!-- Graded indicator icon -->
                                <div v-if="codeLinesGraded[idx] !== null" class="shrink-0 mr-1">
                                    <Check v-if="codeLinesGraded[idx] === true" class="h-4 w-4 text-emerald-400" />
                                    <XCircle v-else class="h-4 w-4 text-rose-400" />
                                </div>

                                <!-- Move Up / Down Arrow Buttons -->
                                <div class="flex items-center gap-1 shrink-0">
                                    <button
                                        type="button"
                                        @click.stop="moveCodeLine(idx, -1)"
                                        :disabled="idx === 0 || codePuzzleSolved"
                                        class="rounded p-1 hover:bg-white/10 disabled:opacity-20 text-slate-400 hover:text-white transition-colors"
                                        title="Move Up"
                                    >
                                        <ChevronUp class="h-3.5 w-3.5" :stroke-width="2.5" />
                                    </button>
                                    <button
                                        type="button"
                                        @click.stop="moveCodeLine(idx, 1)"
                                        :disabled="idx === codeLines.length - 1 || codePuzzleSolved"
                                        class="rounded p-1 hover:bg-white/10 disabled:opacity-20 text-slate-400 hover:text-white transition-colors"
                                        title="Move Down"
                                    >
                                        <ChevronDown class="h-3.5 w-3.5" :stroke-width="2.5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Simulated Terminal Output Console -->
                        <div
                            v-if="codeTerminalLogs.length > 0"
                            class="border-t p-3 font-mono text-[11px] leading-relaxed select-text"
                            style="border-color: rgba(255,255,255,0.08); background: #070a12; color: #94a3b8;"
                        >
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                    <Terminal class="h-3 w-3 text-sky-400" />
                                    <span>Compiler &amp; Runtime Diagnostics</span>
                                </div>
                                <span
                                    v-if="codeLastVerifyStatus !== null"
                                    class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded"
                                    :class="codeLastVerifyStatus ? 'text-emerald-400 bg-emerald-500/10' : 'text-rose-400 bg-rose-500/10'"
                                >
                                    {{ codeLastVerifyStatus ? 'Build Passed' : 'Build Failed' }}
                                </span>
                            </div>
                            <div class="space-y-1 font-mono">
                                <div
                                    v-for="(log, lIdx) in codeTerminalLogs"
                                    :key="lIdx"
                                    :class="{
                                        'text-emerald-400 font-semibold': log.startsWith('✔'),
                                        'text-rose-400 font-semibold': log.startsWith('✖') || log.startsWith('Error:'),
                                        'text-amber-300': log.startsWith('💡') || log.startsWith('Fix Hint:') || log.startsWith('Logic Rule:'),
                                        'text-sky-300': log.startsWith('>') || log.startsWith('$'),
                                        'text-slate-400': !log.startsWith('✔') && !log.startsWith('✖') && !log.startsWith('💡') && !log.startsWith('Fix Hint:') && !log.startsWith('Logic Rule:') && !log.startsWith('>') && !log.startsWith('$')
                                    }"
                                >
                                    {{ log }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>

                <!-- Controls -->
                <div
                    class="flex shrink-0 items-center justify-between gap-3 border-t p-4 sm:px-6 sm:py-4"
                    style="
                        border-color: var(--gl-border);
                        background: var(--gl-surface);
                    "
                >
                    <template v-if="currentGame.type === 'memorymatch'">
                        <span
                            class="flex-1 text-center text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                            >{{ memoryMatches }} /
                            {{ currentGame.cards.length }} pairs</span
                        >
                        <button
                            @click="restartBoard"
                            class="rounded-xl px-4 py-2 text-xs font-medium transition-all"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            <RotateCcw
                                class="mr-1 inline h-3.5 w-3.5"
                                :stroke-width="2"
                            />
                            Restart
                        </button>
                        <button
                            @click="finishGame"
                            :disabled="memoryMatches < currentGame.cards.length"
                            class="rounded-xl px-4 py-2 text-xs font-semibold text-white transition-all disabled:opacity-40"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-success),
                                    #059669
                                );
                            "
                        >
                            Finish
                        </button>
                    </template>
                    <template v-else-if="currentGame.type === 'dragdrop'">
                        <span
                            class="flex-1 text-center text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                            >{{ dropMatches }} /
                            {{ currentGame.cards.length }} matched</span
                        >
                        <button
                            @click="restartBoard"
                            class="rounded-xl px-4 py-2 text-xs font-medium transition-all"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            <RotateCcw
                                class="mr-1 inline h-3.5 w-3.5"
                                :stroke-width="2"
                            />
                            Restart
                        </button>
                        <button
                            @click="finishGame"
                            :disabled="dropMatches < currentGame.cards.length"
                            class="rounded-xl px-4 py-2 text-xs font-semibold text-white transition-all disabled:opacity-40"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-success),
                                    #059669
                                );
                            "
                        >
                            Finish
                        </button>
                    </template>
                    <template v-else-if="currentGame.type === 'ordering'">
                        <span
                            class="flex-1 text-center text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                            >{{
                                orderFinished
                                    ? orderCorrect +
                                      ' / ' +
                                      currentGame.cards.length +
                                      ' correct'
                                    : 'Rearrange then check'
                            }}</span
                        >
                        <button
                            v-if="!orderFinished"
                            @click="checkOrder"
                            class="rounded-xl px-4 py-2 text-xs font-semibold text-white transition-all hover:scale-[1.02]"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                                box-shadow: 0 0 12px var(--gl-primary-glow);
                            "
                        >
                            Check
                        </button>
                        <div v-else class="flex items-center gap-2">
                            <button
                                v-if="orderCorrect < currentGame.cards.length"
                                type="button"
                                @click="orderFinished = false; orderGraded = []"
                                class="rounded-xl px-3 py-2 text-xs font-semibold transition-all hover:bg-white/10"
                                style="background: var(--gl-surface-2); color: var(--gl-text-primary); border: 1px solid var(--gl-border);"
                            >
                                Adjust &amp; Retry
                            </button>
                            <button
                                type="button"
                                @click="finishGame"
                                class="rounded-xl px-4 py-2 text-xs font-semibold text-white transition-all hover:scale-[1.02]"
                                style="
                                    background: linear-gradient(
                                        135deg,
                                        var(--gl-success),
                                        #059669
                                    );
                                "
                            >
                                Finish
                            </button>
                        </div>
                    </template>
                    <template v-else-if="currentGame.type === 'crossword'">
                        <span class="flex-1 text-center text-xs font-semibold" style="color: var(--gl-text-primary)">
                            {{ crosswordSolvedCount }} / {{ crosswordWords.length }} clues solved
                        </span>
                        <button
                            type="button"
                            @click="requestHintCrossword"
                            :disabled="hintDeducting"
                            class="flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold transition-all hover:bg-[var(--gl-surface-2)] active:scale-95 disabled:opacity-50"
                            style="border-color: rgba(234,179,8,0.3); background: rgba(234,179,8,0.08); color: #EAB308;"
                            :title="`Fill cell / letter (-${hintCostAmount} XP)`"
                        >
                            <Lightbulb class="h-3.5 w-3.5" />
                            Hint (-{{ hintCostAmount }} XP)
                        </button>
                        <button
                            type="button"
                            @click="restartBoard"
                            class="rounded-xl px-4 py-2 text-xs font-medium transition-all"
                            style="background: var(--gl-surface-2); color: var(--gl-text-primary); border: 1px solid var(--gl-border);"
                        >
                            <RotateCcw class="mr-1 inline h-3.5 w-3.5" :stroke-width="2" />
                            Reset
                        </button>
                        <button
                            type="button"
                            @click="finishGame"
                            :disabled="!crosswordWords.length || crosswordSolvedCount < crosswordWords.length"
                            class="rounded-xl px-4 py-2 text-xs font-semibold text-white transition-all disabled:opacity-40"
                            style="background: linear-gradient(135deg, var(--gl-success), #059669);"
                        >
                            Finish Crossword
                        </button>
                    </template>
                    <template v-else-if="currentGame.type === 'wordsearch'">
                        <span class="flex-1 text-center text-xs font-semibold" style="color: var(--gl-text-primary)">
                            {{ wordSearchFoundCount }} / {{ wordSearchTargets.length }} words found
                        </span>
                        <button
                            type="button"
                            @click="requestHintWordSearch"
                            :disabled="hintDeducting"
                            class="flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold transition-all hover:bg-[var(--gl-surface-2)] active:scale-95 disabled:opacity-50"
                            style="border-color: rgba(234,179,8,0.3); background: rgba(234,179,8,0.08); color: #EAB308;"
                            :title="`Highlight word start (-${hintCostAmount} XP)`"
                        >
                            <Lightbulb class="h-3.5 w-3.5" />
                            Hint (-{{ hintCostAmount }} XP)
                        </button>
                        <button
                            type="button"
                            @click="restartBoard"
                            class="rounded-xl px-4 py-2 text-xs font-medium transition-all"
                            style="background: var(--gl-surface-2); color: var(--gl-text-primary); border: 1px solid var(--gl-border);"
                        >
                            <RotateCcw class="mr-1 inline h-3.5 w-3.5" :stroke-width="2" />
                            Shuffle
                        </button>
                        <button
                            type="button"
                            @click="finishGame"
                            :disabled="wordSearchFoundCount < wordSearchTargets.length"
                            class="rounded-xl px-4 py-2 text-xs font-semibold text-white transition-all disabled:opacity-40"
                            style="background: linear-gradient(135deg, var(--gl-success), #059669);"
                        >
                            Finish
                        </button>
                    </template>
                    <template v-else-if="currentGame.type === 'codepuzzle'">
                        <span class="flex-1 text-center text-xs font-semibold" style="color: var(--gl-text-primary)">
                            {{ codePuzzleSolved ? 'Sequence Verified!' : codePuzzleCorrectCount + ' / ' + codeLines.length + ' placed' }}
                        </span>
                        <button
                            type="button"
                            @click="requestHintCodePuzzle"
                            :disabled="codePuzzleSolved || hintDeducting"
                            class="flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold transition-all hover:bg-[var(--gl-surface-2)] active:scale-95 disabled:opacity-50"
                            style="border-color: rgba(234,179,8,0.3); background: rgba(234,179,8,0.08); color: #EAB308;"
                            :title="`Move statement to correct step (-${hintCostAmount} XP)`"
                        >
                            <Lightbulb class="h-3.5 w-3.5" />
                            Hint (-{{ hintCostAmount }} XP)
                        </button>
                        <button
                            type="button"
                            @click="restartBoard"
                            class="rounded-xl px-4 py-2 text-xs font-medium transition-all"
                            style="background: var(--gl-surface-2); color: var(--gl-text-primary); border: 1px solid var(--gl-border);"
                        >
                            <RotateCcw class="mr-1 inline h-3.5 w-3.5" :stroke-width="2" />
                            Reset
                        </button>
                        <button
                            v-if="!codePuzzleSolved"
                            type="button"
                            @click="verifyCodePuzzle"
                            class="flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-semibold text-white transition-all hover:scale-[1.02]"
                            style="background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary)); box-shadow: 0 0 12px var(--gl-primary-glow);"
                        >
                            <Play class="h-3.5 w-3.5" :stroke-width="2.5" />
                            Run &amp; Verify
                        </button>
                        <button
                            v-else
                            type="button"
                            @click="finishGame"
                            class="rounded-xl px-4 py-2 text-xs font-semibold text-white transition-all hover:scale-[1.02]"
                            style="background: linear-gradient(135deg, var(--gl-success), #059669); box-shadow: 0 0 12px rgba(16, 185, 129, 0.3);"
                        >
                            Finish
                        </button>
                    </template>
                    <template v-else>
                        <span
                            v-if="currentGame.type === 'flashcard'"
                            class="flex-1 text-center text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                            >Card {{ currentCard + 1 }} of
                            {{ currentGame.cards.length }}</span
                        >
                        <span
                            v-else
                            class="flex-1 text-center text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                            >{{ correctCount }} /
                            {{ gradedCount }} correct</span
                        >
                        <button
                            v-if="currentCard < currentGame.cards.length - 1"
                            @click="
                                flipped = false;
                                nextCard();
                            "
                            class="rounded-xl px-4 py-2 text-xs font-semibold text-white transition-all hover:scale-[1.02]"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                                box-shadow: 0 0 12px var(--gl-primary-glow);
                            "
                        >
                            Next
                            <ArrowRight
                                class="ml-1 inline h-3.5 w-3.5"
                                :stroke-width="2"
                            />
                        </button>
                        <button
                            v-else
                            @click="finishGame"
                            class="rounded-xl px-4 py-2 text-xs font-semibold text-white transition-all hover:scale-[1.02]"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-success),
                                    #059669
                                );
                                box-shadow: 0 0 12px rgba(16, 185, 129, 0.3);
                            "
                        >
                            Finish
                            <Trophy
                                class="ml-1 inline h-3.5 w-3.5"
                                :stroke-width="2"
                            />
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

        <!-- Four Pics Lightbox Modal -->
        <Teleport to="body">
            <div
                v-if="fourPicsZoomImage"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
                @click.self="fourPicsZoomImage = null"
            >
                <div class="relative max-h-[85vh] max-w-[85vw] overflow-hidden rounded-2xl border border-white/20 bg-black shadow-2xl">
                    <button
                        type="button"
                        @click="fourPicsZoomImage = null"
                        class="absolute top-3 right-3 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-black/60 text-white backdrop-blur transition-all hover:bg-black/80 hover:scale-105"
                    >
                        <X class="h-4 w-4" />
                    </button>
                    <img
                        :src="fourPicsZoomImage"
                        alt="Zoomed picture"
                        class="max-h-[80vh] max-w-[80vw] object-contain"
                    />
                </div>
            </div>
        </Teleport>

        <!-- Join Live Match PIN Modal -->
        <Teleport to="body">
            <div
                v-if="showJoinModal"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
                @click.self="showJoinModal = false"
            >
                <div class="relative w-full max-w-sm rounded-3xl border border-[var(--gl-border)] bg-[var(--gl-surface)] p-6 shadow-2xl">
                    <button
                        type="button"
                        @click="showJoinModal = false"
                        class="absolute top-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-[var(--gl-surface-2)] text-[var(--gl-text-muted)] hover:text-[var(--gl-text-primary)]"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <div class="mb-5 text-center">
                        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600">
                            <Users class="h-6 w-6" />
                        </div>
                        <h3 class="text-lg font-black text-[var(--gl-text-primary)]">
                            Join Live Match
                        </h3>
                        <p class="mt-1 text-xs text-[var(--gl-text-secondary)]">
                            Select an active match below or enter the 6-digit Game PIN
                        </p>
                    </div>

                    <!-- Active Rooms List Inside Modal -->
                    <div v-if="activeRoomsList.length > 0" class="mb-4 space-y-2">
                        <p class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-600">
                            Live Match Rooms ({{ activeRoomsList.length }}):
                        </p>
                        <div
                            v-for="r in activeRoomsList"
                            :key="r.id"
                            class="flex items-center justify-between rounded-2xl border border-emerald-500/30 bg-emerald-500/5 p-3 text-left transition-colors hover:bg-emerald-500/10"
                        >
                            <div class="min-w-0 flex-1 pr-2">
                                <p class="truncate text-xs font-bold text-[var(--gl-text-primary)]">
                                    {{ r.game?.title || 'Live Match' }}
                                </p>
                                <p class="text-[10px] text-[var(--gl-text-secondary)]">
                                    {{ r.teacher?.name || 'Teacher' }} · PIN: <strong class="font-mono text-emerald-600 font-bold text-xs">{{ r.pin }}</strong>
                                </p>
                            </div>
                            <a
                                :href="`/student/games/rooms/${encodeURIComponent(r.pin)}`"
                                class="shrink-0 rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white transition-all hover:bg-emerald-500 active:scale-95"
                            >
                                Join
                            </a>
                        </div>
                        <div class="relative my-3 text-center">
                            <hr class="border-[var(--gl-border)]" />
                            <span class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-[var(--gl-surface)] px-2 text-[10px] font-bold text-[var(--gl-text-muted)]">
                                OR ENTER PIN MANUALLY
                            </span>
                        </div>
                    </div>

                    <form @submit.prevent="joinLiveRoom" class="space-y-4">
                        <div>
                            <input
                                v-model="livePin"
                                type="text"
                                maxlength="8"
                                placeholder="000000"
                                autofocus
                                class="w-full rounded-2xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] px-4 py-3 text-center font-mono text-2xl font-black tracking-widest text-[var(--gl-text-primary)] outline-none focus:border-emerald-500"
                            />
                            <p v-if="joinError" class="mt-2 text-center text-xs font-semibold text-red-500">
                                {{ joinError }}
                            </p>
                        </div>

                        <button
                            type="submit"
                            :disabled="joining || !livePin.trim()"
                            class="w-full rounded-2xl bg-emerald-600 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-600/30 transition-all hover:bg-emerald-500 active:scale-95 disabled:opacity-50"
                        >
                            {{ joining ? 'Joining Arena...' : 'Join Game' }}
                        </button>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Student Top 10 Standings Modal -->
        <Teleport to="body">
            <div
                v-if="showStudentStandingsModal && selectedFinishedRoom"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
                @click.self="closeStudentStandings"
            >
                <div
                    class="relative flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-3xl border shadow-2xl"
                    style="background: var(--gl-surface); border-color: var(--gl-border);"
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
                                <Trophy class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold tracking-tight text-[var(--gl-text-primary)]">
                                    Top 10 Match Standings
                                </h3>
                                <p class="text-xs text-[var(--gl-text-muted)]">
                                    {{ selectedFinishedRoom.title }} · PIN {{ selectedFinishedRoom.pin }}
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeStudentStandings"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-[var(--gl-surface)] text-[var(--gl-text-muted)] hover:text-[var(--gl-text-primary)] transition-colors"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Session Switcher (if multiple recent finished matches) -->
                    <div
                        v-if="recentFinishedRoomsList.length > 1"
                        class="flex items-center gap-2 overflow-x-auto border-b px-6 py-2.5 no-scrollbar"
                        style="border-color: var(--gl-border); background: var(--gl-surface-2);"
                    >
                        <span class="shrink-0 text-[11px] font-bold text-[var(--gl-text-muted)]">Match Sessions:</span>
                        <button
                            v-for="r in recentFinishedRoomsList"
                            :key="'tab-' + r.id"
                            type="button"
                            @click="selectedFinishedRoom = r"
                            class="shrink-0 rounded-xl px-3 py-1 text-xs font-bold transition-all"
                            :class="selectedFinishedRoom.id === r.id
                                ? 'bg-amber-500 text-white shadow-sm'
                                : 'border border-[var(--gl-border)] bg-[var(--gl-surface)] text-[var(--gl-text-secondary)] hover:text-[var(--gl-text-primary)]'"
                        >
                            PIN {{ r.pin }}
                            <span class="ml-1 text-[10px] opacity-75">({{ r.total_participants }}p)</span>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-6">
                        <!-- Match Info / Personal Placement Summary -->
                        <div
                            class="rounded-2xl border p-4 text-center transition-all"
                            :class="[
                                selectedFinishedRoom.has_participated && selectedFinishedRoom.my_rank === 1 ? 'border-amber-500/40 bg-amber-500/10' :
                                selectedFinishedRoom.has_participated && selectedFinishedRoom.my_rank === 2 ? 'border-slate-400/40 bg-slate-400/10' :
                                selectedFinishedRoom.has_participated && selectedFinishedRoom.my_rank === 3 ? 'border-amber-700/40 bg-amber-700/10' :
                                'border-[var(--gl-border)] bg-[var(--gl-surface-2)]'
                            ]"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-3 text-left">
                                <div>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-black text-amber-600 dark:text-amber-400">
                                        MATCH COMPLETED
                                    </span>
                                    <h4 class="mt-1 text-sm font-bold text-[var(--gl-text-primary)]">
                                        {{ selectedFinishedRoom.title }}
                                    </h4>
                                    <p class="text-xs text-[var(--gl-text-muted)]">
                                        Hosted by {{ selectedFinishedRoom.teacher_name }} · {{ selectedFinishedRoom.total_participants }} students joined
                                    </p>
                                </div>
                                <div v-if="selectedFinishedRoom.has_participated" class="text-right">
                                    <div class="text-lg font-black" :class="[
                                        selectedFinishedRoom.my_rank === 1 ? 'text-amber-500' :
                                        selectedFinishedRoom.my_rank === 2 ? 'text-slate-400' :
                                        selectedFinishedRoom.my_rank === 3 ? 'text-amber-700' : 'text-indigo-600 dark:text-indigo-400'
                                    ]">
                                        {{ selectedFinishedRoom.my_rank === 1 ? '🥇 1st Place' : selectedFinishedRoom.my_rank === 2 ? '🥈 2nd Place' : selectedFinishedRoom.my_rank === 3 ? '🥉 3rd Place' : `Rank #${selectedFinishedRoom.my_rank}` }}
                                    </div>
                                    <div class="font-mono text-xs font-extrabold text-emerald-600 dark:text-emerald-400">
                                        {{ selectedFinishedRoom.my_score.toLocaleString() }} pts
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Podium (Top 3) -->
                        <div v-if="selectedFinishedRoom.leaderboard?.length" class="flex flex-wrap items-end justify-center gap-3 pt-2">
                            <!-- 2nd -->
                            <div v-if="selectedFinishedRoom.leaderboard[1]" class="w-32 rounded-2xl border border-slate-400/30 bg-slate-400/10 p-3 text-center">
                                <div class="text-xl font-black text-slate-400">2nd</div>
                                <div class="mt-1 truncate text-xs font-bold text-[var(--gl-text-primary)]">
                                    {{ selectedFinishedRoom.leaderboard[1].name }}
                                </div>
                                <div class="font-mono text-[11px] font-semibold text-emerald-600">
                                    {{ selectedFinishedRoom.leaderboard[1].score.toLocaleString() }} pts
                                </div>
                            </div>

                            <!-- 1st -->
                            <div v-if="selectedFinishedRoom.leaderboard[0]" class="w-36 rounded-2xl border-2 border-amber-500/50 bg-amber-500/10 p-4 text-center shadow-md">
                                <Crown class="mx-auto h-5 w-5 text-amber-500" />
                                <div class="text-2xl font-black text-amber-500">1st</div>
                                <div class="mt-1 truncate text-xs font-extrabold text-[var(--gl-text-primary)]">
                                    {{ selectedFinishedRoom.leaderboard[0].name }}
                                </div>
                                <div class="font-mono text-xs font-bold text-emerald-600">
                                    {{ selectedFinishedRoom.leaderboard[0].score.toLocaleString() }} pts
                                </div>
                            </div>

                            <!-- 3rd -->
                            <div v-if="selectedFinishedRoom.leaderboard[2]" class="w-32 rounded-2xl border border-amber-700/30 bg-amber-700/10 p-3 text-center">
                                <div class="text-xl font-black text-amber-700">3rd</div>
                                <div class="mt-1 truncate text-xs font-bold text-[var(--gl-text-primary)]">
                                    {{ selectedFinishedRoom.leaderboard[2].name }}
                                </div>
                                <div class="font-mono text-[11px] font-semibold text-emerald-600">
                                    {{ selectedFinishedRoom.leaderboard[2].score.toLocaleString() }} pts
                                </div>
                            </div>
                        </div>

                        <!-- Full 1-10 List -->
                        <div class="space-y-2">
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-[var(--gl-text-muted)] flex items-center justify-between">
                                <span>Leaderboard Rankings</span>
                                <span class="text-[11px]">{{ Math.min(10, selectedFinishedRoom.leaderboard?.length || 0) }} of {{ selectedFinishedRoom.total_participants }}</span>
                            </h4>

                            <div
                                v-for="entry in selectedFinishedRoom.leaderboard"
                                :key="entry.user_id"
                                class="flex items-center justify-between rounded-2xl border p-3 transition-colors"
                                :class="[
                                    entry.is_me ? 'ring-2 ring-emerald-500 shadow-md' : '',
                                    entry.rank === 1 ? 'border-amber-500/40 bg-amber-500/10' :
                                    entry.rank === 2 ? 'border-slate-400/40 bg-slate-400/10' :
                                    entry.rank === 3 ? 'border-amber-700/40 bg-amber-700/10' :
                                    'border-[var(--gl-border)] bg-[var(--gl-surface-2)]'
                                ]"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl text-xs font-black"
                                        :class="[
                                            entry.rank === 1 ? 'bg-amber-500 text-white' :
                                            entry.rank === 2 ? 'bg-slate-400 text-white' :
                                            entry.rank === 3 ? 'bg-amber-700 text-white' :
                                            'bg-[var(--gl-surface)] text-[var(--gl-text-secondary)] border border-[var(--gl-border)]'
                                        ]"
                                    >
                                        {{ entry.rank }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="truncate text-xs font-bold text-[var(--gl-text-primary)]">
                                                {{ entry.name }}
                                            </span>
                                            <span v-if="entry.is_me" class="rounded-full bg-emerald-500 px-1.5 py-0.2 text-[9px] font-black text-white">
                                                YOU
                                            </span>
                                            <span v-if="entry.rank <= 3" class="text-xs shrink-0">
                                                {{ entry.rank === 1 ? '🥇' : entry.rank === 2 ? '🥈' : '🥉' }}
                                            </span>
                                        </div>
                                        <span v-if="entry.streak > 1" class="text-[10px] font-bold text-orange-500">
                                            🔥 {{ entry.streak }}x streak
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 ml-3">
                                    <div class="font-mono text-xs font-extrabold text-emerald-600 dark:text-emerald-400">
                                        {{ entry.score.toLocaleString() }} pts
                                    </div>
                                    <span class="text-[9px] text-[var(--gl-text-muted)]">
                                        {{ entry.rank === 1 ? 'Champion' : entry.rank <= 3 ? 'Podium' : 'Participant' }}
                                    </span>
                                </div>
                            </div>

                            <!-- If current student finished outside Top 10, pin their entry below -->
                            <div
                                v-if="selectedFinishedRoom.has_participated && selectedFinishedRoom.my_rank > 10"
                                class="mt-4 rounded-2xl border-2 border-dashed border-emerald-500/40 bg-emerald-500/5 p-3 flex items-center justify-between"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-emerald-500 text-white text-xs font-black">
                                        {{ selectedFinishedRoom.my_rank }}
                                    </span>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs font-bold text-[var(--gl-text-primary)]">You</span>
                                            <span class="rounded-full bg-emerald-500 px-1.5 py-0.2 text-[9px] font-black text-white">YOU</span>
                                        </div>
                                        <p class="text-[10px] text-[var(--gl-text-muted)]">Outside Top 10</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-mono text-xs font-bold text-emerald-600">
                                        {{ selectedFinishedRoom.my_score.toLocaleString() }} pts
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div
                        class="flex shrink-0 items-center justify-between border-t px-6 py-3.5"
                        style="border-color: var(--gl-border); background: var(--gl-surface-2);"
                    >
                        <Link
                            :href="`/student/games/rooms/${selectedFinishedRoom.pin}`"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3.5 py-2 text-xs font-bold text-amber-600 dark:text-amber-400 transition-colors hover:bg-amber-500/20"
                        >
                            <Crown class="h-3.5 w-3.5" />
                            <span>View Full Podium Screen</span>
                        </Link>
                        <button
                            type="button"
                            @click="closeStudentStandings"
                            class="rounded-xl bg-[var(--gl-surface)] border border-[var(--gl-border)] px-4 py-2 text-xs font-bold text-[var(--gl-text-primary)] hover:bg-[var(--gl-surface-2)] transition-colors"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Hint Deduction Confirmation Modal -->
        <Teleport to="body">
            <div
                v-if="showHintConfirmModal"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
                @click.self="showHintConfirmModal = false"
            >
                <div
                    class="w-full max-w-sm rounded-2xl p-6 shadow-2xl border"
                    style="background: var(--gl-surface); border-color: var(--gl-border);"
                >
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-500/15 text-amber-500 border border-amber-500/20">
                            <Lightbulb class="h-6 w-6" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold" style="color: var(--gl-text-primary)">
                                Use a Hint?
                            </h3>
                            <p class="text-xs font-semibold text-rose-400">
                                Costs {{ hintCostAmount }} XP
                            </p>
                        </div>
                    </div>

                    <p class="text-xs leading-relaxed mb-4" style="color: var(--gl-text-secondary)">
                        Using this hint will deduct <strong class="text-amber-500 font-bold">{{ hintCostAmount }} XP</strong> from your account points.
                    </p>

                    <label class="flex items-center gap-2.5 cursor-pointer mb-5 text-xs select-none p-3 rounded-xl border" style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);">
                        <input
                            type="checkbox"
                            v-model="dontAskHintAgain"
                            class="rounded border-zinc-500 text-amber-500 focus:ring-amber-500 h-4 w-4"
                        />
                        <span class="text-xs">Don't ask again for this game</span>
                    </label>

                    <div class="flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            @click="showHintConfirmModal = false"
                            class="rounded-xl px-4 py-2.5 text-xs font-semibold transition-colors"
                            style="background: var(--gl-surface-2); color: var(--gl-text-secondary); border: 1px solid var(--gl-border);"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="confirmHintModal"
                            class="flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-xs font-bold text-black transition-all hover:brightness-110 active:scale-95 shadow-md shadow-amber-500/20"
                            style="background: #EAB308;"
                        >
                            <Lightbulb class="h-3.5 w-3.5" />
                            Use Hint (-{{ hintCostAmount }} XP)
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Student Game Result Modal -->
        <Teleport to="body">
            <div
                v-if="resultGame"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
                @click.self="closeResult"
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
                        <div class="flex items-center gap-2">
                            <span
                                class="rounded-full px-2.5 py-0.5 text-[10px] font-extrabold tracking-wider uppercase"
                                style="
                                    background: rgba(34, 211, 238, 0.15);
                                    color: #22d3ee;
                                "
                            >
                                {{ TYPE_LABELS[resultGame.type] ?? resultGame.type }}
                            </span>
                            <span
                                class="text-xs font-semibold"
                                style="color: var(--gl-text-secondary)"
                            >
                                {{ resultGame.subject }}{{ resultGame.grade ? ' · ' + resultGame.grade : '' }}
                            </span>
                        </div>
                        <button
                            type="button"
                            @click="closeResult"
                            class="flex h-8 w-8 items-center justify-center rounded-full transition-all hover:scale-105"
                            style="
                                background: var(--gl-surface);
                                color: var(--gl-text-muted);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Scrollable Modal Content -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-6">
                        <!-- Hero Banner -->
                        <div
                            class="relative overflow-hidden rounded-2xl p-6 text-center"
                            :style="{ background: cover(resultGame) }"
                        >
                            <div class="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-white/10"></div>
                            <div class="absolute -bottom-10 -left-10 h-32 w-32 rounded-full bg-black/15"></div>
                            <div class="relative z-10 flex flex-col items-center">
                                <div
                                    class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 text-white shadow-lg backdrop-blur"
                                >
                                    <Trophy class="h-7 w-7 text-amber-300 drop-shadow" />
                                </div>
                                <h2 class="text-xl font-black text-white sm:text-2xl drop-shadow-sm">
                                    {{ resultGame.title }}
                                </h2>
                                <p class="mt-1 text-xs text-white/80 font-medium">
                                    Completed {{ formatCompletionDate(resultGame.progress?.updated_at) }}
                                </p>
                            </div>
                        </div>

                        <!-- Stats Strip -->
                        <div
                            class="gap-3"
                            :class="resultGame.progress?.hints_used ? 'grid grid-cols-2 sm:grid-cols-4' : 'grid grid-cols-3'"
                        >
                            <div
                                class="rounded-2xl p-4 text-center border"
                                style="
                                    background: rgba(34, 211, 238, 0.08);
                                    border-color: rgba(34, 211, 238, 0.2);
                                "
                            >
                                <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--gl-text-muted)">
                                    Score
                                </p>
                                <p class="mt-1 text-2xl font-black tabular-nums" style="color: var(--gl-text-primary)">
                                    {{ resultScore(resultGame) }}
                                    <span class="text-sm font-semibold text-[var(--gl-text-muted)]">/ {{ resultGame.cards?.length ?? 0 }}</span>
                                </p>
                            </div>

                            <div
                                class="rounded-2xl p-4 text-center border"
                                style="
                                    background: rgba(16, 185, 129, 0.08);
                                    border-color: rgba(16, 185, 129, 0.2);
                                "
                            >
                                <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--gl-text-muted)">
                                    Accuracy
                                </p>
                                <p class="mt-1 text-2xl font-black tabular-nums" style="color: var(--gl-success)">
                                    {{ resultPercentage(resultGame) }}%
                                </p>
                            </div>

                            <div
                                class="rounded-2xl p-4 text-center border"
                                style="
                                    background: rgba(251, 191, 36, 0.08);
                                    border-color: rgba(251, 191, 36, 0.2);
                                "
                            >
                                <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--gl-text-muted)">
                                    XP Earned
                                </p>
                                <p class="mt-1 flex items-center justify-center gap-1 text-2xl font-black tabular-nums text-amber-500">
                                    <Zap class="h-5 w-5 fill-current" />
                                    +{{ resultGame.progress?.earned_xp ?? Math.round(resultScore(resultGame) * (resultGame.xp ?? 0) / Math.max(1, resultGame.cards?.length ?? 1)) }}
                                </p>
                            </div>

                            <div
                                v-if="resultGame.progress?.hints_used"
                                class="rounded-2xl p-4 text-center border"
                                style="
                                    background: rgba(244, 63, 94, 0.08);
                                    border-color: rgba(244, 63, 94, 0.2);
                                "
                            >
                                <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--gl-text-muted)">
                                    Hints Used
                                </p>
                                <p class="mt-1 flex items-center justify-center gap-1 text-2xl font-black tabular-nums text-rose-500">
                                    <Lightbulb class="h-5 w-5" />
                                    {{ resultGame.progress.hints_used }}
                                    <span class="text-xs font-semibold text-rose-400">(-{{ resultGame.progress.hints_used * hintCostAmount }} XP)</span>
                                </p>
                            </div>
                        </div>

                        <!-- Item Breakdown List -->
                        <div class="select-none">
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-xs font-bold uppercase tracking-wider" style="color: var(--gl-text-muted)">
                                    Questions & Answers Breakdown
                                </h3>
                                <span class="text-xs font-semibold tabular-nums" style="color: var(--gl-text-secondary)">
                                    {{ resultScore(resultGame) }} of {{ resultGame.cards?.length ?? 0 }} Correct
                                </span>
                            </div>

                            <div class="space-y-2.5">
                                <div
                                    v-for="(card, idx) in (resultGame.cards || [])"
                                    :key="card.id"
                                    class="flex items-start justify-between gap-3 rounded-2xl border p-3.5 transition-all select-none"
                                    :style="
                                        isCardCorrect(resultGame, card, idx)
                                            ? {
                                                  background: 'rgba(16, 185, 129, 0.06)',
                                                  borderColor: 'rgba(16, 185, 129, 0.25)',
                                              }
                                            : {
                                                  background: 'rgba(239, 68, 68, 0.06)',
                                                  borderColor: 'rgba(239, 68, 68, 0.25)',
                                              }
                                    "
                                >
                                    <div class="flex items-start gap-3 min-w-0 flex-1">
                                        <span
                                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-black"
                                            :style="
                                                isCardCorrect(resultGame, card, idx)
                                                    ? { background: 'rgba(16, 185, 129, 0.2)', color: 'var(--gl-success)' }
                                                    : { background: 'rgba(239, 68, 68, 0.2)', color: 'var(--gl-danger)' }
                                            "
                                        >
                                            {{ idx + 1 }}
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold leading-snug break-words" style="color: var(--gl-text-primary)">
                                                {{ card.question || 'Item #' + (idx + 1) }}
                                            </p>
                                            <p class="mt-1 text-[11px] font-medium" style="color: var(--gl-text-secondary)">
                                                Answer:
                                                <span
                                                    v-if="isCardCorrect(resultGame, card, idx)"
                                                    class="font-bold text-[var(--gl-text-primary)]"
                                                >
                                                    {{ card.answer }}
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-flex items-center gap-1 font-semibold text-rose-500/90 dark:text-rose-400 italic"
                                                >
                                                    <Lock class="inline h-3 w-3" /> Hidden to prevent copying
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="shrink-0 flex items-center">
                                        <span
                                            v-if="isCardCorrect(resultGame, card, idx)"
                                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-bold"
                                            style="background: rgba(16, 185, 129, 0.15); color: var(--gl-success);"
                                        >
                                            <CheckCircle2 class="h-3 w-3" /> Correct
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-bold"
                                            style="background: rgba(239, 68, 68, 0.15); color: var(--gl-danger);"
                                        >
                                            <X class="h-3 w-3" /> Incorrect
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Teacher note & Reopen Request banner -->
                        <div
                            v-if="resultGame.reopen_pending || resultGame.progress?.reopen_pending"
                            class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-4 text-xs font-semibold text-amber-700 dark:text-amber-400 flex items-center gap-3"
                        >
                            <RotateCcw class="h-4 w-4 animate-spin shrink-0" />
                            <span>Reopen request submitted to your teacher. You can retake this game once approved.</span>
                        </div>
                        <div
                            v-else
                            class="rounded-2xl border p-4 text-xs flex flex-wrap items-center justify-between gap-3"
                            style="
                                background: var(--gl-surface-2);
                                border-color: var(--gl-border);
                                color: var(--gl-text-secondary);
                            "
                        >
                            <div>
                                <p class="font-bold text-[var(--gl-text-primary)]">Need to replay?</p>
                                <p class="mt-0.5 text-[11px] text-[var(--gl-text-muted)]">
                                    This game is completed. If you want to improve your score, request a retake from your teacher.
                                    <span v-if="(resultGame.progress?.earned_xp ?? resultGame.earned_xp ?? 0) > 0" class="block mt-0.5 text-amber-600 dark:text-amber-400 font-medium">
                                        Note: {{ resultGame.progress?.earned_xp ?? resultGame.earned_xp }} XP previously earned from this game will be deducted once approved so you can earn it afresh.
                                    </span>
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="requestGameReopen(resultGame)"
                                :disabled="requestingReopen"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-2 text-xs font-bold text-amber-600 dark:text-amber-400 transition-all hover:bg-amber-500/20 active:scale-95 disabled:opacity-50"
                            >
                                <RotateCcw class="h-3.5 w-3.5" :stroke-width="2" />
                                <span>{{ requestingReopen ? 'Sending Request...' : 'Request Reopen' }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div
                        class="flex shrink-0 items-center justify-end border-t px-6 py-4"
                        style="border-color: var(--gl-border); background: var(--gl-surface-2);"
                    >
                        <button
                            type="button"
                            @click="closeResult"
                            class="rounded-xl px-5 py-2.5 text-xs font-bold text-white transition-all hover:scale-105 active:scale-95"
                            style="
                                background: linear-gradient(135deg, #22d3ee, #3b82f6);
                                box-shadow: 0 2px 12px rgba(34, 211, 238, 0.35);
                            "
                        >
                            Close Results
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </Teleport>
</template>

<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Users,
    Gamepad2,
    HelpCircle,
    CheckCircle2,
    Eye,
    X,
    Zap,
    ArrowRight,
    Trophy,
    Code,
    Palette,
    ShieldCheck,
    Shuffle,
    SwatchBook,
    Lock,
    ShieldAlert,
    LayoutGrid,
    UserRound,
    Timer,
    ArrowDownUp,
    ListOrdered,
    ChevronUp,
    ChevronDown,
    RotateCcw,
    Flag,
    Search,
    Play,
    Image as ImageIcon,
    ZoomIn,
    Lightbulb,
    Terminal,
    Sparkles,
    Copy,
    Check,
    Crown,
    Flame,
    GripVertical,
    XCircle,
} from '@lucide/vue';
import { computed, ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { toast } from 'vue-sonner';
import { isNearMatch } from '@/lib/answerMatcher';

const props = defineProps<{
    games: any[];
    activeRooms?: any[];
    recentFinishedRooms?: any[];
    hintCost?: number;
}>();

const page = usePage();
const hintCostAmount = computed(() => Number(props.hintCost ?? 2));

// Hint Deduction and Confirmation State
const showHintConfirmModal = ref(false);
const dontAskHintAgain = ref(false);
const pendingHintAction = ref<(() => void) | null>(null);
const hintDeducting = ref(false);

function triggerHint(action: () => void) {
    if (hintDeducting.value) {
return;
}

    pendingHintAction.value = action;

    if (dontAskHintAgain.value) {
        executeHint();
    } else {
        showHintConfirmModal.value = true;
    }
}

function confirmHintModal() {
    showHintConfirmModal.value = false;
    executeHint();
}

async function executeHint() {
    const g = currentGame.value;

    if (!g || !g.id || !pendingHintAction.value) {
return;
}

    hintDeducting.value = true;
    const action = pendingHintAction.value;
    pendingHintAction.value = null;

    try {
        const token =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';

        const res = await fetch(`/student/games/${g.id}/hint`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({}),
        });

        if (res.ok) {
            const data = await res.json();

            if (data.ok) {
                if (page.props.auth?.user && typeof data.total_points === 'number') {
                    (page.props.auth.user as any).total_points = data.total_points;
                }

                if (g.progress) {
                    g.progress.hints_used = Number(data.hints_used ?? ((g.progress.hints_used || 0) + 1));
                }

                action();

                toast.warning(`💡 Hint used: -${data.cost} XP deducted!`);
            } else {
                toast.error(data.message || 'Could not use hint.');
            }
        } else {
            const err = await res.json().catch(() => null);
            toast.error(err?.message || 'Failed to use hint.');
        }
    } catch {
        toast.error('Network error when attempting to use hint.');
    } finally {
        hintDeducting.value = false;
    }
}

const activeRoomsList = computed(() => props.activeRooms || []);
const recentFinishedRoomsList = computed(() => props.recentFinishedRooms || []);

// Student Top 10 Standings Modal
const showStudentStandingsModal = ref(false);
const selectedFinishedRoom = ref<any>(null);

function openStudentStandings(room: any) {
    selectedFinishedRoom.value = room;
    showStudentStandingsModal.value = true;
}

function closeStudentStandings() {
    showStudentStandingsModal.value = false;
    selectedFinishedRoom.value = null;
}

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

function joinLiveRoomWithPin(pin: string) {
    openLiveRoom(pin);
}

const showJoinModal = ref(false);
const livePin = ref('');
const joinError = ref('');
const joining = ref(false);

function openLiveRoom(pin: string) {
    window.location.assign(`/student/games/rooms/${encodeURIComponent(pin.trim())}`);
}

function joinLiveRoom() {
    if (!livePin.value.trim()) {
return;
}

    joining.value = true;
    joinError.value = '';
    openLiveRoom(livePin.value);
}

const games = computed(() =>
    (props.games || []).map((g: any) => ({
        id: g.id,
        title: g.title,
        subject: g.subject,
        desc: g.description || 'Game for ' + g.subject,
        xp: g.xp_reward,
        type: g.type || 'flashcard',
        icon:
            g.type === 'quiz' || g.type === 'speedquiz'
                ? g.type === 'speedquiz'
                    ? Timer
                    : HelpCircle
                : g.type === 'fillblank'
                  ? Code
                  : g.type === 'truefalse'
                    ? ShieldCheck
                    : g.type === 'wordjumble'
                      ? Shuffle
                      : g.type === 'colorharmony'
                        ? SwatchBook
                        : g.type === 'memorymatch'
                          ? LayoutGrid
                          : g.type === 'hangman'
                            ? UserRound
                            : g.type === 'dragdrop'
                              ? ArrowDownUp
                              : g.type === 'ordering'
                                ? ListOrdered
                                : g.type === 'fourpics'
                                  ? ImageIcon
                                  : g.type === 'crossword'
                                    ? LayoutGrid
                                    : g.type === 'wordsearch'
                                      ? Search
                                      : g.type === 'codepuzzle'
                                        ? Code
                                        : Palette,
        bg: 'rgba(59,130,246,0.12)',
        color: '#3B82F6',
        badge: 'rgba(59,130,246,0.1)',
        progress: g.progress ?? null,
        cards: (g.cards || []).map((c: any) => ({
            id: c.id,
            question: c.question,
            answer: c.answer,
            color: c.color || '',
            options: c.options || [],
        })),
    })),
);

const currentGame = ref<any>(null);

const isBoardGame = computed(() =>
    ['crossword', 'wordsearch', 'memorymatch'].includes(currentGame.value?.type),
);

const gameModalMaxWidthClass = computed(() => {
    if (!currentGame.value) {
return 'max-w-xl';
}

    switch (currentGame.value.type) {
        case 'crossword':
        case 'wordsearch':
            return 'max-w-6xl';
        case 'memorymatch':
            return 'max-w-5xl';
        case 'codepuzzle':
            return 'max-w-4xl';
        case 'fourpics':
        case 'dragdrop':
            return 'max-w-2xl';
        default:
            return 'max-w-xl';
    }
});

// Marketplace: tabs, search, hub
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
const COVERS: Record<string, [string, string]> = {
    flashcard: ['#3B82F6', '#7C3AED'],
    quiz: ['#7C3AED', '#EC4899'],
    speedquiz: ['#F59E0B', '#EF4444'],
    fillblank: ['#10B981', '#06B6D4'],
    truefalse: ['#F97316', '#FBBF24'],
    wordjumble: ['#EC4899', '#7C3AED'],
    colorharmony: ['#D946EF', '#FB7185'],
    memorymatch: ['#06B6D4', '#3B82F6'],
    hangman: ['#64748B', '#7C3AED'],
    dragdrop: ['#6366F1', '#A855F7'],
    ordering: ['#14B8A6', '#22C55E'],
    fourpics: ['#E11D48', '#F97316'],
    crossword: ['#8B5CF6', '#EC4899'],
    wordsearch: ['#06B6D4', '#3B82F6'],
    codepuzzle: ['#10B981', '#06B6D4'],
};
function cover(g: any): string {
    const c = COVERS[g.type] ?? COVERS.flashcard;

    return `linear-gradient(135deg, ${c[0]}, ${c[1]})`;
}
function progressPct(g: any): number {
    if (!g.progress || !g.cards.length) {
return 0;
}

    return Math.min(
        100,
        Math.round((g.progress.done_count / g.cards.length) * 100),
    );
}
const query = ref('');
const activeTab = ref('all');
const tabs = computed(() => [
    'all',
    ...new Set(games.value.map((g: any) => g.type)),
]);
const filteredGames = computed(() => {
    const q = query.value.trim().toLowerCase();

    return games.value.filter((g: any) => {
        if (activeTab.value !== 'all' && g.type !== activeTab.value) {
return false;
}

        if (
            q &&
            !(g.title || '').toLowerCase().includes(q) &&
            !(g.subject || '').toLowerCase().includes(q)
        ) {
return false;
}

        return true;
    });
});
const completedCount = computed(
    () => games.value.filter((g: any) => g.progress?.completed).length,
);
const inProgressCount = computed(
    () =>
        games.value.filter(
            (g: any) =>
                g.progress &&
                !g.progress.completed &&
                g.progress.done_count > 0,
        ).length,
);
const grabXp = computed(() =>
    games.value
        .filter((g: any) => !g.progress?.completed)
        .reduce((a: number, g: any) => a + (g.xp ?? 0), 0),
);
const topRewards = computed(() =>
    games.value
        .filter((g: any) => !g.progress?.completed)
        .sort((a: any, b: any) => (b.xp ?? 0) - (a.xp ?? 0))
        .slice(0, 3),
);
const currentCard = ref(0);
const flipped = ref(false);
const correctCount = ref(0);
const gradedState = ref<(null | boolean)[]>([]);
const answered = ref(false);
const selectedOption = ref('');
const typedAnswer = ref('');
const fillCorrect = ref(false);
const answerResult = ref<boolean | null>(null);
const correctOnLast = ref(false);
const jumbleLetters = ref<string[]>([]);
const jumbleAnswer = ref<string[]>([]);

const memoryTiles = ref<any[]>([]);
const memoryFirst = ref<any>(null);
const memoryLastFlipped = ref<any>(null);
const memoryLocked = ref(false);
const memoryMatches = ref(0);

const activeMemoryTile = computed(() => {
    if (memoryFirst.value && memoryFirst.value.flipped) {
return memoryFirst.value;
}

    if (
        memoryLastFlipped.value &&
        (memoryLastFlipped.value.flipped || memoryLastFlipped.value.matched)
    ) {
        return memoryLastFlipped.value;
    }

    return null;
});

const memoryGridClass = computed(() => {
    const total = memoryTiles.value.length;

    if (total <= 8) {
return 'grid grid-cols-2 sm:grid-cols-4 gap-3';
}

    if (total <= 12) {
return 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3';
}

    if (total <= 16) {
return 'grid grid-cols-2 sm:grid-cols-4 md:grid-cols-4 gap-2.5';
}

    return 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2.5';
});

const HANGMAN_MAX = 6;
const HANGMAN_LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
const hangmanWord = ref('');
const hangmanGuessed = ref<string[]>([]);
const hangmanMistakes = ref(0);

const SPEED_SECONDS = 15;
let speedTimer: any = null;
const speedTimeLeft = ref(0);

const dropItems = ref<any[]>([]);
const dropTargets = ref<any[]>([]);
const dropSelected = ref<any>(null);
const dropMatches = ref(0);
const dropWrong = ref<any>(null);
let dropWrongTimer: any = null;

const orderSteps = ref<any[]>([]);
const orderFinished = ref(false);
const orderCorrect = ref(0);
const orderGraded = ref<(null | boolean)[]>([]);

const resultGame = ref<any>(null);
const requestingReopen = ref(false);

function openResult(game: any) {
    resultGame.value = game;
}

async function requestGameReopen(game: any) {
    if (!game || requestingReopen.value) {
return;
}

    requestingReopen.value = true;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    try {
        const res = await fetch(`/student/games/${game.id}/request-reopen`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ reason: 'Student requested retake from results view' }),
        });

        const data = await res.json();

        if (res.ok && data.ok) {
            if (data.auto_approved) {
                toast.success(data.message || 'Game reopened! You can replay now.');
                game.progress = null;
                game.reopen_pending = false;
                const propGame = (props.games || []).find((x: any) => x.id === game.id);

                if (propGame) {
                    propGame.progress = null;
                    propGame.reopen_pending = false;
                }

                if (resultGame.value && resultGame.value.id === game.id) {
                    resultGame.value = null;
                }

                openGame(game);
            } else {
                toast.info(data.message || 'Reopen request submitted to your teacher.');
                game.reopen_pending = true;

                if (game.progress) {
game.progress.reopen_pending = true;
}

                if (resultGame.value && resultGame.value.id === game.id) {
                    resultGame.value.reopen_pending = true;

                    if (resultGame.value.progress) {
resultGame.value.progress.reopen_pending = true;
}
                }
            }
        } else {
            toast.error(data?.message || 'Could not submit reopen request.');
        }
    } catch {
        toast.error('An error occurred while requesting reopen.');
    } finally {
        requestingReopen.value = false;
    }
}

function closeResult() {
    resultGame.value = null;
}

function resultScore(game: any): number {
    if (!game) {
return 0;
}

    const cards = game.cards || [];
    const correctCount = cards.filter((c: any, idx: number) => isCardCorrect(game, c, idx)).length;
    const rawScore = Number(game?.progress?.score ?? 0);

    return Math.max(rawScore, correctCount);
}

function resultPercentage(game: any): number {
    const total = game?.cards?.length || 1;
    const score = resultScore(game);

    return Math.min(100, Math.round((score / total) * 100));
}

function formatCompletionDate(dateStr: string | null | undefined): string {
    if (!dateStr) {
return 'Recently';
}

    try {
        const d = new Date(dateStr);

        return d.toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return 'Recently';
    }
}

function isCardCorrect(game: any, card: any, index: number): boolean {
    if (!game) {
return false;
}

    if (game.type === 'flashcard') {
return true;
}

    if (game.progress?.correct_card_ids && Array.isArray(game.progress.correct_card_ids)) {
        if (game.progress.correct_card_ids.includes(card.id)) {
return true;
}
    }

    if (game.progress?.graded) {
        let graded = game.progress.graded;

        if (typeof graded === 'string') {
            try {
 graded = JSON.parse(graded); 
} catch {
 graded = []; 
}
        }

        if (Array.isArray(graded)) {
            if (game.progress?.card_order) {
                const orderArr = String(game.progress.card_order).split(',').map(Number);
                const orderIndex = orderArr.indexOf(card.id);

                if (orderIndex !== -1 && graded[orderIndex] !== undefined) {
                    return Boolean(graded[orderIndex]);
                }
            }

            if (graded[index] !== undefined) {
                return Boolean(graded[index]);
            }
        }
    }

    return false;
}

interface FourPicsBankItem {
    id: number;
    letter: string;
    used: boolean;
}

const fourPicsBank = ref<FourPicsBankItem[]>([]);
const fourPicsSlots = ref<(FourPicsBankItem | null)[]>([]);
const fourPicsZoomImage = ref<string | null>(null);
const fourPicsShake = ref(false);
const fourPicsWrong = ref(false);

const fourPicsImages = computed(() => {
    const card = currentGame.value?.cards[currentCard.value];
    const opts = card?.options;
    const list = Array.isArray(opts) ? opts : [];

    return [
        list[0] || '',
        list[1] || '',
        list[2] || '',
        list[3] || '',
    ];
});

const boardTypes = ['memorymatch', 'dragdrop', 'ordering', 'crossword', 'wordsearch', 'codepuzzle'];

const gradedCount = computed(
    () => gradedState.value.filter((v) => v !== null).length,
);

const gameProgressLabel = computed(() => {
    const g = currentGame.value;

    if (!g) {
        return '';
    }

    if (g.type === 'memorymatch') {
        return `${memoryMatches.value} / ${g.cards.length} pairs`;
    }

    if (g.type === 'dragdrop') {
        return `${dropMatches.value} / ${g.cards.length} matched`;
    }

    if (g.type === 'ordering') {
        return orderFinished.value
            ? `${orderCorrect.value} / ${g.cards.length} correct`
            : 'Arrange the steps';
    }

    if (g.type === 'crossword') {
        return `${crosswordSolvedCount.value} / ${crosswordWords.value.length} clues`;
    }

    if (g.type === 'wordsearch') {
        return `${wordSearchFoundCount.value} / ${wordSearchTargets.value.length} words`;
    }

    if (g.type === 'codepuzzle') {
        return codePuzzleSolved.value
            ? 'Verified'
            : `${codePuzzleCorrectCount.value} / ${codeLines.value.length} placed`;
    }

    return `${currentCard.value + 1} / ${g.cards.length}`;
});

const leaveCount = ref(0);
const tabWarning = ref(false);
const forceSubmitted = ref(false);
const isFullscreen = ref(false);
const fullscreenDenied = ref(false);
let warningTimer: any = null;
let endingGame = false;

function blockGesture(e: Event) {
    if (!currentGame.value) {
        return;
    }

    if (
        e.type === 'dragstart' &&
        (currentGame.value.type === 'dragdrop' ||
            currentGame.value.type === 'codepuzzle' ||
            currentGame.value.type === 'ordering')
    ) {
        return;
    }

    e.preventDefault();
}

function stopSpeedTimer() {
    if (speedTimer) {
        clearInterval(speedTimer);
        speedTimer = null;
    }
}

async function enterGameFullscreen() {
    const element = document.documentElement;

    if (document.fullscreenElement || endingGame) {
        return;
    }

    if (!element.requestFullscreen || document.fullscreenEnabled === false) {
        fullscreenDenied.value = true;

        return;
    }

    try {
        await element.requestFullscreen();
        fullscreenDenied.value = false;
    } catch {
        fullscreenDenied.value = true;
    }
}

async function exitGameFullscreen() {
    if (!document.fullscreenElement || !document.exitFullscreen) {
        return;
    }

    try {
        await document.exitFullscreen();
    } catch {
        /* fullscreen may already have been exited by the browser */
    }
}

function onFullscreenChange() {
    isFullscreen.value = Boolean(document.fullscreenElement);

    if (!currentGame.value || endingGame || document.fullscreenElement) {
        return;
    }

    fullscreenDenied.value = true;
    tabSwitched();

    // Re-request immediately when the browser permits it. Pressing Escape may
    // still require another user gesture because fullscreen is browser-owned.
    window.setTimeout(() => {
        if (currentGame.value && !endingGame && !document.fullscreenElement) {
            void enterGameFullscreen();
        }
    }, 0);
}

function onBeforeUnload(event: BeforeUnloadEvent) {
    if (!currentGame.value || forceSubmitted.value || endingGame) {
        return;
    }

    event.preventDefault();
    event.returnValue = '';
}

function tabSwitched() {
    if (!currentGame.value || forceSubmitted.value) {
        return;
    }

    leaveCount.value++;
    tabWarning.value = true;

    if (leaveCount.value >= 3) {
        forceSubmitted.value = true;

        if (warningTimer) {
            clearTimeout(warningTimer);
            warningTimer = null;
        }

        void finishGame();
    } else {
        if (warningTimer) {
            clearTimeout(warningTimer);
        }

        warningTimer = setTimeout(() => {
            tabWarning.value = false;
            warningTimer = null;
        }, 3500);
    }
}

function onVisChange() {
    if (!currentGame.value) {
        return;
    }

    if (document.hidden) {
        tabSwitched();
    } else {
        if (warningTimer) {
            clearTimeout(warningTimer);
        }

        warningTimer = setTimeout(() => {
            tabWarning.value = false;
            warningTimer = null;
        }, 2500);
    }
}

function onWindowBlur() {
    if (!currentGame.value) {
        return;
    }

    if (!document.hasFocus() && !document.hidden) {
        tabSwitched();
    }
}

onMounted(() => {
    window.addEventListener('contextmenu', blockGesture);
    window.addEventListener('copy', blockGesture);
    window.addEventListener('cut', blockGesture);
    window.addEventListener('paste', blockGesture);
    document.addEventListener('dragstart', blockGesture);
    document.addEventListener('visibilitychange', onVisChange);
    document.addEventListener('fullscreenchange', onFullscreenChange);
    window.addEventListener('blur', onWindowBlur);
    window.addEventListener('pagehide', beaconSubmit);
    window.addEventListener('beforeunload', onBeforeUnload);
    window.addEventListener('keydown', handleGlobalKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('contextmenu', blockGesture);
    window.removeEventListener('copy', blockGesture);
    window.removeEventListener('cut', blockGesture);
    window.removeEventListener('paste', blockGesture);
    document.removeEventListener('dragstart', blockGesture);
    document.removeEventListener('visibilitychange', onVisChange);
    document.removeEventListener('fullscreenchange', onFullscreenChange);
    window.removeEventListener('blur', onWindowBlur);
    window.removeEventListener('pagehide', beaconSubmit);
    window.removeEventListener('beforeunload', onBeforeUnload);
    window.removeEventListener('keydown', handleGlobalKeydown);

    if (currentGame.value && !forceSubmitted.value) {
        forceSubmitted.value = true;
        void saveProgress(true, true);
    }

    if (warningTimer) {
        clearTimeout(warningTimer);
    }

    stopSpeedTimer();
    endingGame = true;
    void exitGameFullscreen();

    if (dropWrongTimer) {
        clearTimeout(dropWrongTimer);
    }
});

function shuffleArray(arr: any[]) {
    const a = [...arr];

    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }

    return a;
}

function shuffleCards(cards: any[]) {
    return shuffleArray(cards || []).map((c: any) => ({
        ...c,
        options: c.options?.length ? shuffleArray(c.options) : [],
    }));
}

function openGame(game: any) {
    if (game.progress?.completed) {
        return;
    }

    dontAskHintAgain.value = false;
    showHintConfirmModal.value = false;
    pendingHintAction.value = null;
    hintDeducting.value = false;

    leaveCount.value = 0;
    tabWarning.value = false;
    forceSubmitted.value = false;
    endingGame = false;
    fullscreenDenied.value = false;
    void enterGameFullscreen();
    stopSpeedTimer();
    const saved = game.progress;
    const cards = game.cards || [];
    const type = game.type;
    const isBoard = boardTypes.includes(type);

    let orderedCards: any[];
    let hasStoredOrder = false;

    if (isBoard) {
        orderedCards = cards;
    } else {
        const savedOrderArr = Array.isArray(saved?.card_order)
            ? saved.card_order
            : String(saved?.card_order || '')
                  .split(',')
                  .map(Number)
                  .filter((n) => n > 0);
        hasStoredOrder =
            savedOrderArr.length === cards.length &&
            savedOrderArr.every((id: number) =>
                cards.some((c: any) => c.id === id),
            );

        if (hasStoredOrder) {
            const byId = new Map(cards.map((c: any) => [c.id, c]));
            orderedCards = savedOrderArr
                .map((id: number) => byId.get(id))
                .filter(Boolean)
                .map((c: any) => ({
                    ...c,
                    options: c.options?.length ? c.options : [],
                }));
        } else {
            orderedCards = shuffleCards(cards);
        }
    }

    currentGame.value = { ...game, cards: orderedCards };

    if (isBoard) {
        gradedState.value = [];
        correctCount.value = 0;
    } else {
        let restoredGraded: (null | boolean)[] | null = null;

        if (saved && !saved.completed && saved.graded) {
            try {
                const parsed =
                    typeof saved.graded === 'string'
                        ? JSON.parse(saved.graded)
                        : saved.graded;

                if (Array.isArray(parsed)) {
                    restoredGraded = parsed.map((g: any) =>
                        g === true || g === false
                            ? !!g
                            : g === 1
                              ? true
                              : g === 0
                                ? false
                                : null,
                    );
                }
            } catch {
                /* malformed graded payload */
            }
        }

        gradedState.value = restoredGraded ?? [];
        correctCount.value = gradedState.value.filter((v) => v === true).length;

        currentCard.value =
            saved && !saved.completed && saved.current_card > 0
                ? Math.min(saved.current_card, cards.length - 1)
                : 0;

        if (!hasStoredOrder) {
            saveProgress(false);
        }
    }

    flipped.value = false;
    answered.value = false;
    selectedOption.value = '';
    typedAnswer.value = '';
    fillCorrect.value = false;
    answerResult.value = null;
    correctOnLast.value = false;
    jumbleAnswer.value = [];

    if (type === 'wordjumble') {
        scrambleFor(currentGame.value.cards[currentCard.value]);
    } else if (type === 'hangman') {
        startHangmanCard(currentCard.value);
    } else if (type === 'speedquiz') {
        startSpeedCard();
    } else if (type === 'fourpics') {
        initFourPicsCard(currentGame.value.cards[currentCard.value]);
    } else if (type === 'memorymatch') {
        initMemory();
    } else if (type === 'dragdrop') {
        initDrop();
    } else if (type === 'ordering') {
        initOrder();
    } else if (type === 'crossword') {
        initCrossword();
    } else if (type === 'wordsearch') {
        initWordSearch();
    } else if (type === 'codepuzzle') {
        initCodePuzzle();
    }
}


async function setGraded(correct: boolean) {
    const game = currentGame.value;
    const isLastCard = !!game && currentCard.value === game.cards.length - 1;
    const wasGraded =
        gradedState.value[currentCard.value] !== null &&
        gradedState.value[currentCard.value] !== undefined;

    if (!wasGraded) {
        gradedState.value[currentCard.value] = correct;

        if (correct) {
            correctCount.value++;

            if (isLastCard) {
                await awardCorrect();
            } else {
                awardCorrect();
            }
        }
    }

    answered.value = true;
    answerResult.value = correct;
    correctOnLast.value = correct;

    if (isLastCard) {
        checkAutoFinish();
    }
}

function checkAutoFinish() {
    const game = currentGame.value;

    if (!game || game.type === 'flashcard') {
        return;
    }

    const allGraded =
        gradedState.value.filter((v) => v !== null && v !== undefined)
            .length === game.cards.length;

    if (!allGraded) {
        return;
    }

    const finish = () => {
        if (currentGame.value === game) {
            finishGame();
        }
    };

    if (correctOnLast.value) {
        setTimeout(finish, 900);
    } else {
        setTimeout(finish, 1200);
    }
}

function scrambleFor(card: any) {
    const raw = (card?.answer || '').replace(/\s+/g, '').toUpperCase();
    const letters = raw.split('');

    for (let i = letters.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [letters[i], letters[j]] = [letters[j], letters[i]];
    }

    jumbleLetters.value = letters;
    jumbleAnswer.value = [];
}

function nextCard() {
    if (
        currentGame.value &&
        currentCard.value < currentGame.value.cards.length - 1
    ) {
        currentCard.value++;
        flipped.value = false;
        answered.value = false;
        selectedOption.value = '';
        typedAnswer.value = '';
        fillCorrect.value = false;
        answerResult.value = null;
        correctOnLast.value = false;
        jumbleAnswer.value = [];

        if (currentGame.value.type === 'wordjumble') {
            scrambleFor(currentGame.value.cards[currentCard.value]);
        } else if (currentGame.value.type === 'hangman') {
            startHangmanCard(currentCard.value);
        } else if (currentGame.value.type === 'speedquiz') {
            startSpeedCard();
        } else if (currentGame.value.type === 'fourpics') {
            initFourPicsCard(currentGame.value.cards[currentCard.value]);
        }

        saveProgress(false);
    }
}

function checkAnswer(opt: string) {
    selectedOption.value = opt;

    if (currentGame.value?.type === 'speedquiz') {
        stopSpeedTimer();
    }

    const correct = opt === currentGame.value?.cards[currentCard.value]?.answer;
    setGraded(correct);
}

function checkFillBlank() {
    const c = currentGame.value?.cards[currentCard.value];
    const expected = c?.answer || '';
    const student = typedAnswer.value || '';
    fillCorrect.value = isNearMatch(expected, student);
    setGraded(fillCorrect.value);
}

function checkTrueFalse(value: boolean) {
    const c = currentGame.value?.cards[currentCard.value];
    const ans = (c?.answer || '').trim().toLowerCase();
    const isTrue = ['true', 't', '1', 'yes'].includes(ans);
    setGraded(value === isTrue);
}

function addJumbleLetter(idx: number) {
    if (idx < 0 || idx >= jumbleLetters.value.length) {
        return;
    }

    const l = jumbleLetters.value.splice(idx, 1)[0];

    if (l !== undefined) {
        jumbleAnswer.value.push(l);
    }
}

function removeJumbleLetter(idx: number) {
    if (idx < 0 || idx >= jumbleAnswer.value.length) {
        return;
    }

    const l = jumbleAnswer.value.splice(idx, 1)[0];

    if (l !== undefined) {
        jumbleLetters.value.push(l);
    }
}

function checkWordJumble() {
    const c = currentGame.value?.cards[currentCard.value];
    const guess = jumbleAnswer.value.join('').toLowerCase();
    const correct =
        guess === (c?.answer || '').replace(/\s+/g, '').toLowerCase();
    setGraded(correct);
}

/* ── Memory Match ─────────────────────────────────────────── */

function initMemory() {
    const cards = currentGame.value?.cards || [];
    const tiles: any[] = [];
    cards.forEach((c: any) => {
        tiles.push({
            id: c.id + '-q',
            cardId: c.id,
            kind: 'q',
            text: c.question,
            flipped: false,
            matched: false,
        });
        tiles.push({
            id: c.id + '-a',
            cardId: c.id,
            kind: 'a',
            text: c.answer,
            flipped: false,
            matched: false,
        });
    });
    memoryTiles.value = shuffleArray(tiles);
    memoryFirst.value = null;
    memoryLastFlipped.value = null;
    memoryLocked.value = false;
    memoryMatches.value = 0;
}

async function memoryFlip(tile: any) {
    if (memoryLocked.value || tile.matched || tile.flipped) {
        return;
    }

    if (memoryFirst.value && memoryFirst.value.id === tile.id) {
        return;
    }

    tile.flipped = true;
    memoryLastFlipped.value = tile;

    if (!memoryFirst.value) {
        memoryFirst.value = tile;

        return;
    }

    const first = memoryFirst.value;
    memoryFirst.value = null;

    if (first.cardId === tile.cardId) {
        first.matched = true;
        tile.matched = true;
        memoryMatches.value++;
        await awardCard(tile.cardId);

        if (memoryMatches.value === currentGame.value.cards.length) {
            correctOnLast.value = true;
            finishGame();
        }
    } else {
        memoryLocked.value = true;
        setTimeout(() => {
            first.flipped = false;
            tile.flipped = false;
            memoryLocked.value = false;
        }, 800);
    }
}

function restartMemory() {
    initMemory();
    correctOnLast.value = false;
}

/* ── Hangman ───────────────────────────────────────────────── */

function startHangmanCard(idx: number) {
    const c = currentGame.value?.cards[idx];
    hangmanWord.value = (c?.answer || '')
        .toUpperCase()
        .replace(/[^A-Z0-9]/g, '');
    hangmanGuessed.value = [];
    hangmanMistakes.value = 0;
    answered.value = false;
    answerResult.value = null;
    correctOnLast.value = false;
}

function hangmanGuess(letter: string) {
    if (answered.value || !hangmanWord.value) {
        return;
    }

    if (hangmanGuessed.value.includes(letter)) {
        return;
    }

    hangmanGuessed.value.push(letter);

    if (!hangmanWord.value.includes(letter)) {
        hangmanMistakes.value++;

        if (hangmanMistakes.value >= HANGMAN_MAX) {
            setGraded(false);
        }
    } else if (
        [...hangmanWord.value].every((ch) => hangmanGuessed.value.includes(ch))
    ) {
        setGraded(true);
    }
}

/* ── Speed Quiz ────────────────────────────────────────────── */

function startSpeedCard() {
    speedTimeLeft.value = SPEED_SECONDS;
    stopSpeedTimer();
    speedTimer = setInterval(() => {
        speedTimeLeft.value--;

        if (speedTimeLeft.value <= 0) {
            stopSpeedTimer();

            if (!answered.value) {
                selectedOption.value = '';
                setGraded(false);
            }
        }
    }, 1000);
}

/* ── Drag & Drop Matching ──────────────────────────────────── */

function initDrop() {
    const cards = currentGame.value?.cards || [];
    dropItems.value = shuffleArray(
        cards.map((c: any) => ({ cardId: c.id, text: c.question })),
    );
    dropTargets.value = shuffleArray(
        cards.map((c: any) => ({
            cardId: c.id,
            text: c.answer,
            matched: false,
        })),
    );
    dropSelected.value = null;
    dropMatches.value = 0;
    dropWrong.value = null;

    if (dropWrongTimer) {
        clearTimeout(dropWrongTimer);
        dropWrongTimer = null;
    }
}

function selectDropItem(item: any) {
    if (dropItems.value.some((i) => i.cardId === item.cardId && i.matched)) {
        return;
    }

    dropSelected.value =
        dropSelected.value?.cardId === item.cardId ? null : item;
}

async function dropOnTarget(target: any, item: any = null) {
    const term = item ?? dropSelected.value;

    if (!term || target.matched) {
        return;
    }

    if (term.cardId === target.cardId) {
        target.matched = true;
        dropItems.value = dropItems.value.filter(
            (i) => i.cardId !== term.cardId,
        );
        dropMatches.value++;
        dropSelected.value = null;
        await awardCard(term.cardId);

        if (dropMatches.value === currentGame.value.cards.length) {
            correctOnLast.value = true;
            finishGame();
        }
    } else {
        dropSelected.value = null;
        dropWrong.value = target;
        clearTimeout(dropWrongTimer);
        dropWrongTimer = setTimeout(() => {
            dropWrong.value = null;
        }, 700);
    }
}

function onDropItem(event: DragEvent, target: any) {
    event.preventDefault();
    const cardId = Number(event.dataTransfer?.getData('text/plain'));

    if (!cardId) {
        return;
    }

    dropOnTarget(target, { cardId, text: '' });
}

function restartDrop() {
    initDrop();
    correctOnLast.value = false;
}

/* ── Order the Steps ───────────────────────────────────────── */

function initOrder() {
    const cards = currentGame.value?.cards || [];
    orderSteps.value = shuffleArray(
        cards.map((c: any, i: number) => ({ cardId: c.id, text: c.question || c.answer || `Step ${i + 1}` })),
    );
    orderFinished.value = false;
    orderCorrect.value = 0;
    orderGraded.value = [];
}

function moveOrder(idx: number, dir: -1 | 1) {
    const arr = [...orderSteps.value];
    const j = idx + dir;

    if (j < 0 || j >= arr.length) {
        return;
    }

    [arr[idx], arr[j]] = [arr[j], arr[idx]];
    orderSteps.value = arr;
    orderFinished.value = false;
    orderGraded.value = [];
}

async function checkOrder() {
    const cards = currentGame.value?.cards || [];
    let correct = 0;
    const graded = orderSteps.value.map((step: any, i: number) => {
        const ok = step.cardId === cards[i]?.id;

        if (ok) {
            correct++;
        }

        return ok;
    });
    orderCorrect.value = correct;
    orderGraded.value = graded;
    orderFinished.value = true;

    for (let i = 0; i < cards.length; i++) {
        if (graded[i]) {
            await awardCard(cards[i].id);
        }
    }

    if (orderFinished.value && orderCorrect.value === cards.length) {
        finishGame();
    }
}

function restartOrder() {
    initOrder();
}

function restartBoard() {
    const type = currentGame.value?.type;

    if (type === 'memorymatch') {
        restartMemory();
    } else if (type === 'dragdrop') {
        restartDrop();
    } else if (type === 'ordering') {
        restartOrder();
    } else if (type === 'crossword') {
        initCrossword();
    } else if (type === 'wordsearch') {
        initWordSearch();
    } else if (type === 'codepuzzle') {
        initCodePuzzle();
    }
}

/* ── 4 Pics 1 Word ────────────────────────────────────────── */

function initFourPicsCard(card: any) {
    fourPicsZoomImage.value = null;
    fourPicsShake.value = false;
    fourPicsWrong.value = false;

    const raw = (card?.answer || '').trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
    const word = raw || 'WORD';
    fourPicsSlots.value = Array(word.length).fill(null);

    const answerLetters = word.split('');
    const targetCount = Math.max(12, word.length + 4);
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const bankChars = [...answerLetters];

    while (bankChars.length < targetCount) {
        bankChars.push(alphabet[Math.floor(Math.random() * alphabet.length)]);
    }

    const shuffled = shuffleArray(bankChars);
    fourPicsBank.value = shuffled.map((letter, id) => ({
        id,
        letter,
        used: false,
    }));
}

function tapBankLetter(item: FourPicsBankItem) {
    if (item.used || answered.value) {
return;
}

    const emptyIdx = fourPicsSlots.value.findIndex((s) => s === null);

    if (emptyIdx === -1) {
return;
}

    fourPicsSlots.value[emptyIdx] = item;
    item.used = true;
    fourPicsWrong.value = false;

    if (fourPicsSlots.value.every((s) => s !== null)) {
        verifyFourPicsAnswer();
    }
}

function tapSlot(idx: number) {
    if (answered.value) {
return;
}

    const item = fourPicsSlots.value[idx];

    if (!item) {
return;
}

    item.used = false;
    fourPicsSlots.value[idx] = null;
    fourPicsWrong.value = false;
}

function clearFourPics() {
    if (answered.value) {
return;
}

    fourPicsSlots.value.forEach((item) => {
        if (item) {
item.used = false;
}
    });
    fourPicsSlots.value = Array(fourPicsSlots.value.length).fill(null);
    fourPicsWrong.value = false;
}

function shuffleFourPicsBank() {
    fourPicsBank.value = shuffleArray([...fourPicsBank.value]);
}

function canHintFourPics(): boolean {
    if (answered.value) {
return false;
}

    const card = currentGame.value?.cards[currentCard.value];
    const raw = (card?.answer || '').trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
    const targetWord = raw || 'WORD';

    for (let i = 0; i < targetWord.length; i++) {
        if (!fourPicsSlots.value[i] || fourPicsSlots.value[i]?.letter !== targetWord[i]) {
            return true;
        }
    }

    return false;
}

function requestHintFourPics() {
    if (!canHintFourPics()) {
        toast.info('All letters are already correctly placed!');

        return;
    }

    triggerHint(() => applyHintFourPics());
}

function applyHintFourPics() {
    if (answered.value) {
return;
}

    const card = currentGame.value?.cards[currentCard.value];
    const raw = (card?.answer || '').trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
    const targetWord = raw || 'WORD';

    let slotToFix = -1;

    for (let i = 0; i < targetWord.length; i++) {
        if (!fourPicsSlots.value[i] || fourPicsSlots.value[i]?.letter !== targetWord[i]) {
            slotToFix = i;
            break;
        }
    }

    if (slotToFix === -1) {
return;
}

    if (fourPicsSlots.value[slotToFix]) {
        fourPicsSlots.value[slotToFix]!.used = false;
        fourPicsSlots.value[slotToFix] = null;
    }

    const neededLetter = targetWord[slotToFix];
    let candidate = fourPicsBank.value.find((b) => !b.used && b.letter === neededLetter);

    if (!candidate) {
        for (let j = 0; j < fourPicsSlots.value.length; j++) {
            if (j !== slotToFix && fourPicsSlots.value[j]?.letter === neededLetter) {
                candidate = fourPicsSlots.value[j]!;
                fourPicsSlots.value[j] = null;
                break;
            }
        }
    }

    if (candidate) {
        candidate.used = true;
        fourPicsSlots.value[slotToFix] = candidate;
        fourPicsWrong.value = false;

        if (fourPicsSlots.value.every((s) => s !== null)) {
            verifyFourPicsAnswer();
        }
    }
}

function verifyFourPicsAnswer() {
    const card = currentGame.value?.cards[currentCard.value];
    const raw = (card?.answer || '').trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
    const targetWord = raw || 'WORD';
    const currentWord = fourPicsSlots.value.map((s) => s?.letter || '').join('');

    if (currentWord === targetWord) {
        fourPicsWrong.value = false;
        setGraded(true);
    } else {
        fourPicsWrong.value = true;
        fourPicsShake.value = true;
        setTimeout(() => {
            fourPicsShake.value = false;
        }, 500);
    }
}

function giveUpFourPics() {
    fourPicsWrong.value = false;
    setGraded(false);
}

/* ── Crossword Puzzle ──────────────────────────────────────── */

interface CrosswordCell {
    r: number;
    c: number;
    solution: string;
    value: string;
    number: number | null;
    isStart: boolean;
    acrossIndex: number | null;
    downIndex: number | null;
}

interface CrosswordPlacedWord {
    cardId: number;
    word: string;
    clue: string;
    number: number;
    direction: 'across' | 'down';
    startR: number;
    startC: number;
    cells: { r: number; c: number }[];
    solved: boolean;
}

const crosswordGrid = ref<(CrosswordCell | null)[][]>([]);
const crosswordWords = ref<CrosswordPlacedWord[]>([]);
const crosswordSelectedCell = ref<{ r: number; c: number } | null>(null);
const crosswordDirection = ref<'across' | 'down'>('across');
const crosswordSelectedWord = ref<CrosswordPlacedWord | null>(null);

const acrossWords = computed(() =>
    crosswordWords.value.filter((w) => w.direction === 'across')
);
const downWords = computed(() =>
    crosswordWords.value.filter((w) => w.direction === 'down')
);
const crosswordSolvedCount = computed(
    () => crosswordWords.value.filter((w) => w.solved).length
);
const crosswordHasNumbers = computed(() =>
    crosswordWords.value.some((w) => /[0-9]/.test(w.word))
);

function initCrossword() {
    const cards = currentGame.value?.cards || [];

    if (!cards.length) {
return;
}

    const rawWords = cards.map((c: any) => ({
        cardId: c.id,
        word: (c.answer || 'CODE').toUpperCase().replace(/[^A-Z0-9]/g, '') || 'WORD',
        clue: c.question && c.question !== 'Crossword Clue' ? c.question : (c.answer || 'Clue'),
    })).sort((a: any, b: any) => b.word.length - a.word.length);

    const maxWordLen = Math.max(...rawWords.map((w: any) => w.word.length), 4);
    const totalLetters = rawWords.reduce((sum: number, w: any) => sum + w.word.length, 0);

    const baseGridSize = Math.max(maxWordLen + 4, Math.ceil(Math.sqrt(totalLetters * 3.5)) + 4, 15);

    let placedResult: { grid: (CrosswordCell | null)[][]; words: CrosswordPlacedWord[] } | null = null;

    for (let attempt = 0; attempt < 5; attempt++) {
        const GRID_SIZE = baseGridSize + attempt * 3;
        const grid: (CrosswordCell | null)[][] = Array.from({ length: GRID_SIZE }, () =>
            Array.from({ length: GRID_SIZE }, () => null)
        );
        const placedWords: CrosswordPlacedWord[] = [];

        function canPlace(w: string, startR: number, startC: number, dir: 'across' | 'down'): { ok: boolean; intersections: number } {
            const dR = dir === 'down' ? 1 : 0;
            const dC = dir === 'across' ? 1 : 0;
            const endR = startR + (w.length - 1) * dR;
            const endC = startC + (w.length - 1) * dC;

            if (startR < 0 || startC < 0 || endR >= GRID_SIZE || endC >= GRID_SIZE) {
return { ok: false, intersections: 0 };
}

            const beforeR = startR - dR;
            const beforeC = startC - dC;

            if (beforeR >= 0 && beforeC >= 0 && beforeR < GRID_SIZE && beforeC < GRID_SIZE && grid[beforeR][beforeC] !== null) {
                return { ok: false, intersections: 0 };
            }

            const afterR = endR + dR;
            const afterC = endC + dC;

            if (afterR >= 0 && afterC >= 0 && afterR < GRID_SIZE && afterC < GRID_SIZE && grid[afterR][afterC] !== null) {
                return { ok: false, intersections: 0 };
            }

            let intersections = 0;

            for (let i = 0; i < w.length; i++) {
                const r = startR + i * dR;
                const c = startC + i * dC;
                const cell = grid[r][c];

                if (cell !== null) {
                    if (cell.solution !== w[i]) {
return { ok: false, intersections: 0 };
}

                    if (dir === 'across' && cell.acrossIndex !== null) {
return { ok: false, intersections: 0 };
}

                    if (dir === 'down' && cell.downIndex !== null) {
return { ok: false, intersections: 0 };
}

                    intersections++;
                } else {
                    const p1R = r + dC, p1C = c + dR;
                    const p2R = r - dC, p2C = c - dR;

                    if (p1R >= 0 && p1R < GRID_SIZE && p1C >= 0 && p1C < GRID_SIZE && grid[p1R][p1C] !== null) {
                        return { ok: false, intersections: 0 };
                    }

                    if (p2R >= 0 && p2R < GRID_SIZE && p2C >= 0 && p2C < GRID_SIZE && grid[p2R][p2C] !== null) {
                        return { ok: false, intersections: 0 };
                    }
                }
            }

            return { ok: true, intersections };
        }

        const first = rawWords[0];
        const firstStartC = Math.max(1, Math.floor((GRID_SIZE - first.word.length) / 2));
        const firstStartR = Math.floor(GRID_SIZE / 2);

        for (let i = 0; i < first.word.length; i++) {
            const c = firstStartC + i;
            grid[firstStartR][c] = {
                r: firstStartR,
                c,
                solution: first.word[i],
                value: '',
                number: null,
                isStart: i === 0,
                acrossIndex: 0,
                downIndex: null,
            };
        }

        placedWords.push({
            cardId: first.cardId,
            word: first.word,
            clue: first.clue,
            number: 1,
            direction: 'across',
            startR: firstStartR,
            startC: firstStartC,
            cells: Array.from({ length: first.word.length }, (_, i) => ({ r: firstStartR, c: firstStartC + i })),
            solved: false,
        });

        let allPlaced = true;

        for (let wi = 1; wi < rawWords.length; wi++) {
            const item = rawWords[wi];
            const candidates: { startR: number; startC: number; dir: 'across' | 'down'; score: number }[] = [];

            for (const pw of placedWords) {
                const targetDir: 'across' | 'down' = pw.direction === 'across' ? 'down' : 'across';

                for (let i = 0; i < item.word.length; i++) {
                    for (let j = 0; j < pw.word.length; j++) {
                        if (item.word[i] === pw.word[j]) {
                            const intersectR = pw.direction === 'across' ? pw.startR : pw.startR + j;
                            const intersectC = pw.direction === 'across' ? pw.startC + j : pw.startC;
                            const candR = targetDir === 'down' ? intersectR - i : intersectR;
                            const candC = targetDir === 'across' ? intersectC - i : intersectC;

                            const check = canPlace(item.word, candR, candC, targetDir);

                            if (check.ok && check.intersections > 0) {
                                const distFromCenter = Math.abs(candR - GRID_SIZE / 2) + Math.abs(candC - GRID_SIZE / 2);
                                candidates.push({
                                    startR: candR,
                                    startC: candC,
                                    dir: targetDir,
                                    score: check.intersections * 20 - distFromCenter,
                                });
                            }
                        }
                    }
                }
            }

            if (candidates.length === 0) {
                for (const testDir of ['across', 'down'] as const) {
                    const stepR = testDir === 'down' ? 1 : 0;
                    const stepC = testDir === 'across' ? 1 : 0;
                    const maxR = testDir === 'down' ? GRID_SIZE - item.word.length - 1 : GRID_SIZE - 2;
                    const maxC = testDir === 'across' ? GRID_SIZE - item.word.length - 1 : GRID_SIZE - 2;

                    for (let r = 1; r <= maxR; r++) {
                        for (let c = 1; c <= maxC; c++) {
                            const check = canPlace(item.word, r, c, testDir);

                            if (check.ok) {
                                let touches = false;

                                for (let k = 0; k < item.word.length; k++) {
                                    const cr = r + k * stepR;
                                    const cc = c + k * stepC;

                                    for (let dr = -1; dr <= 1; dr++) {
                                        for (let dc = -1; dc <= 1; dc++) {
                                            const nr = cr + dr;
                                            const nc = cc + dc;

                                            if (nr >= 0 && nr < GRID_SIZE && nc >= 0 && nc < GRID_SIZE) {
                                                if (grid[nr][nc] !== null) {
                                                    touches = true;
                                                    break;
                                                }
                                            }
                                        }

                                        if (touches) {
break;
}
                                    }

                                    if (touches) {
break;
}
                                }

                                if (!touches) {
                                    const dist = Math.abs(r - GRID_SIZE / 2) + Math.abs(c - GRID_SIZE / 2);
                                    candidates.push({
                                        startR: r,
                                        startC: c,
                                        dir: testDir,
                                        score: -dist,
                                    });
                                }
                            }
                        }
                    }
                }
            }

            if (candidates.length === 0) {
                allPlaced = false;
                break;
            }

            candidates.sort((a, b) => b.score - a.score);
            const best = candidates[0];
            const dR = best.dir === 'down' ? 1 : 0;
            const dC = best.dir === 'across' ? 1 : 0;
            const wordIdx = placedWords.length;
            const cells: { r: number; c: number }[] = [];

            for (let k = 0; k < item.word.length; k++) {
                const r = best.startR + k * dR;
                const c = best.startC + k * dC;
                cells.push({ r, c });

                if (!grid[r][c]) {
                    grid[r][c] = {
                        r,
                        c,
                        solution: item.word[k],
                        value: '',
                        number: null,
                        isStart: k === 0,
                        acrossIndex: best.dir === 'across' ? wordIdx : null,
                        downIndex: best.dir === 'down' ? wordIdx : null,
                    };
                } else {
                    if (best.dir === 'across') {
grid[r][c]!.acrossIndex = wordIdx;
} else {
grid[r][c]!.downIndex = wordIdx;
}
                }
            }

            placedWords.push({
                cardId: item.cardId,
                word: item.word,
                clue: item.clue,
                number: 0,
                direction: best.dir,
                startR: best.startR,
                startC: best.startC,
                cells,
                solved: false,
            });
        }

        if (allPlaced) {
            placedResult = { grid, words: placedWords };
            break;
        }
    }

    if (!placedResult) {
return;
}

    let minR = Infinity, maxR = -Infinity, minC = Infinity, maxC = -Infinity;

    for (const w of placedResult.words) {
        for (const cell of w.cells) {
            if (cell.r < minR) {
minR = cell.r;
}

            if (cell.r > maxR) {
maxR = cell.r;
}

            if (cell.c < minC) {
minC = cell.c;
}

            if (cell.c > maxC) {
maxC = cell.c;
}
        }
    }

    const padStartR = Math.max(0, minR - 1);
    const padEndR = Math.min(placedResult.grid.length - 1, maxR + 1);
    const padStartC = Math.max(0, minC - 1);
    const padEndC = Math.min(placedResult.grid[0].length - 1, maxC + 1);

    const croppedHeight = padEndR - padStartR + 1;
    const croppedWidth = padEndC - padStartC + 1;

    const croppedGrid: (CrosswordCell | null)[][] = Array.from({ length: croppedHeight }, (_, r) =>
        Array.from({ length: croppedWidth }, (_, c) => {
            const orig = placedResult!.grid[padStartR + r]?.[padStartC + c];

            if (!orig) {
return null;
}

            return {
                ...orig,
                r,
                c,
                number: null,
                isStart: false,
            };
        })
    );

    const adjustedWords: CrosswordPlacedWord[] = placedResult.words.map((w) => ({
        ...w,
        startR: w.startR - padStartR,
        startC: w.startC - padStartC,
        cells: w.cells.map((pos) => ({ r: pos.r - padStartR, c: pos.c - padStartC })),
    }));

    let currentNumber = 1;

    for (let r = 0; r < croppedHeight; r++) {
        for (let c = 0; c < croppedWidth; c++) {
            const wordsStartingHere = adjustedWords.filter((w) => w.startR === r && w.startC === c);

            if (wordsStartingHere.length > 0) {
                if (croppedGrid[r][c]) {
                    croppedGrid[r][c]!.number = currentNumber;
                    croppedGrid[r][c]!.isStart = true;
                }

                wordsStartingHere.forEach((w) => {
                    w.number = currentNumber;
                });
                currentNumber++;
            }
        }
    }

    crosswordGrid.value = croppedGrid;
    crosswordWords.value = adjustedWords;

    if (adjustedWords.length > 0) {
        selectCrosswordWord(adjustedWords[0]);
    }
}

function selectCrosswordWord(word: CrosswordPlacedWord) {
    crosswordSelectedWord.value = word;
    crosswordDirection.value = word.direction;
    const firstEmpty = word.cells.find((pos) => {
        const cell = crosswordGrid.value[pos.r]?.[pos.c];

        return cell && !cell.value;
    }) || word.cells[0];
    crosswordSelectedCell.value = firstEmpty ? { r: firstEmpty.r, c: firstEmpty.c } : null;

    nextTick(() => {
        const el = document.getElementById(`cw-clue-${word.direction}-${word.number}`);

        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });
}

function selectCrosswordCell(r: number, c: number) {
    const cell = crosswordGrid.value[r]?.[c];

    if (!cell) {
return;
}

    if (crosswordSelectedCell.value?.r === r && crosswordSelectedCell.value?.c === c) {
        if (cell.acrossIndex !== null && cell.downIndex !== null) {
            crosswordDirection.value = crosswordDirection.value === 'across' ? 'down' : 'across';
            const wordIdx = crosswordDirection.value === 'across' ? cell.acrossIndex : cell.downIndex;
            crosswordSelectedWord.value = crosswordWords.value[wordIdx] || null;

            if (crosswordSelectedWord.value) {
                const w = crosswordSelectedWord.value;
                nextTick(() => {
                    const el = document.getElementById(`cw-clue-${w.direction}-${w.number}`);

                    if (el) {
el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
                });
            }

            return;
        }
    }

    crosswordSelectedCell.value = { r, c };

    if (crosswordDirection.value === 'across' && cell.acrossIndex !== null) {
        crosswordSelectedWord.value = crosswordWords.value[cell.acrossIndex] || null;
    } else if (crosswordDirection.value === 'down' && cell.downIndex !== null) {
        crosswordSelectedWord.value = crosswordWords.value[cell.downIndex] || null;
    } else if (cell.acrossIndex !== null) {
        crosswordDirection.value = 'across';
        crosswordSelectedWord.value = crosswordWords.value[cell.acrossIndex] || null;
    } else if (cell.downIndex !== null) {
        crosswordDirection.value = 'down';
        crosswordSelectedWord.value = crosswordWords.value[cell.downIndex] || null;
    }

    if (crosswordSelectedWord.value) {
        const w = crosswordSelectedWord.value;
        nextTick(() => {
            const el = document.getElementById(`cw-clue-${w.direction}-${w.number}`);

            if (el) {
el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
        });
    }
}

function toggleCrosswordDirection() {
    crosswordDirection.value = crosswordDirection.value === 'across' ? 'down' : 'across';

    if (crosswordSelectedCell.value) {
        const cell = crosswordGrid.value[crosswordSelectedCell.value.r]?.[crosswordSelectedCell.value.c];

        if (cell) {
            const wordIdx = crosswordDirection.value === 'across' ? cell.acrossIndex : cell.downIndex;

            if (wordIdx !== null && crosswordWords.value[wordIdx]) {
                crosswordSelectedWord.value = crosswordWords.value[wordIdx];
                const w = crosswordSelectedWord.value;
                nextTick(() => {
                    const el = document.getElementById(`cw-clue-${w.direction}-${w.number}`);

                    if (el) {
el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
                });
            }
        }
    }
}

function isCrosswordSelectedCell(r: number, c: number): boolean {
    return crosswordSelectedCell.value?.r === r && crosswordSelectedCell.value?.c === c;
}

function isCrosswordWordHighlighted(cell: CrosswordCell): boolean {
    if (!crosswordSelectedWord.value) {
return false;
}

    return crosswordSelectedWord.value.cells.some((pos) => pos.r === cell.r && pos.c === cell.c);
}

function isCrosswordCellSolved(cell: CrosswordCell): boolean {
    const ac = cell.acrossIndex !== null ? crosswordWords.value[cell.acrossIndex]?.solved : false;
    const dn = cell.downIndex !== null ? crosswordWords.value[cell.downIndex]?.solved : false;

    return Boolean(ac || dn);
}

function inputCrosswordLetter(letter: string) {
    if (!crosswordSelectedCell.value) {
        const firstUnsolved = crosswordWords.value.find((w) => !w.solved);

        if (firstUnsolved) {
selectCrosswordWord(firstUnsolved);
}
    }

    if (!crosswordSelectedCell.value) {
return;
}

    const { r, c } = crosswordSelectedCell.value;
    const cell = crosswordGrid.value[r]?.[c];

    if (!cell) {
return;
}

    cell.value = letter.toUpperCase();

    if (cell.acrossIndex !== null && crosswordWords.value[cell.acrossIndex]) {
        checkCrosswordWord(crosswordWords.value[cell.acrossIndex]);
    }

    if (cell.downIndex !== null && crosswordWords.value[cell.downIndex]) {
        checkCrosswordWord(crosswordWords.value[cell.downIndex]);
    }

    if (crosswordSelectedWord.value) {
        const curIdx = crosswordSelectedWord.value.cells.findIndex((pos) => pos.r === r && pos.c === c);

        if (curIdx !== -1 && curIdx < crosswordSelectedWord.value.cells.length - 1) {
            const next = crosswordSelectedWord.value.cells[curIdx + 1];
            crosswordSelectedCell.value = { r: next.r, c: next.c };
        }
    }
}

function deleteCrosswordCell() {
    if (!crosswordSelectedCell.value) {
return;
}

    const { r, c } = crosswordSelectedCell.value;
    const cell = crosswordGrid.value[r]?.[c];

    if (!cell) {
return;
}

    if (cell.value) {
        cell.value = '';
    } else if (crosswordSelectedWord.value) {
        const curIdx = crosswordSelectedWord.value.cells.findIndex((pos) => pos.r === r && pos.c === c);

        if (curIdx > 0) {
            const prev = crosswordSelectedWord.value.cells[curIdx - 1];
            crosswordSelectedCell.value = { r: prev.r, c: prev.c };
            const prevCell = crosswordGrid.value[prev.r]?.[prev.c];

            if (prevCell) {
prevCell.value = '';
}
        }
    }
}

function moveCrosswordCursor(dr: number, dc: number) {
    if (!crosswordSelectedCell.value) {
return;
}

    const nr = crosswordSelectedCell.value.r + dr;
    const nc = crosswordSelectedCell.value.c + dc;

    if (crosswordGrid.value[nr]?.[nc]) {
        selectCrosswordCell(nr, nc);
    }
}

async function checkCrosswordWord(word: CrosswordPlacedWord | null) {
    if (!word || word.solved) {
return;
}

    const filled = word.cells.map((pos) => crosswordGrid.value[pos.r]?.[pos.c]?.value || '').join('');

    if (filled === word.word) {
        word.solved = true;
        toast.success(`Clue Solved: "${word.word}"!`);
        await awardCard(word.cardId);

        if (crosswordWords.value.every((w) => w.solved)) {
            toast.success('Crossword Complete! Brilliant job!');
            setTimeout(() => {
                finishGame();
            }, 1000);
        } else if (crosswordSelectedWord.value?.cardId === word.cardId) {
            const nextUnsolved = crosswordWords.value.find((w) => !w.solved);

            if (nextUnsolved) {
                selectCrosswordWord(nextUnsolved);
            }
        }
    }
}

function canHintCrossword(): boolean {
    return crosswordWords.value.some((w) => !w.solved);
}

function requestHintCrossword() {
    if (!canHintCrossword()) {
        toast.info('Crossword is already completely solved!');

        return;
    }

    triggerHint(() => applyHintCrossword());
}

function applyHintCrossword() {
    if (!crosswordSelectedCell.value) {
        const firstUnsolved = crosswordWords.value.find((w) => !w.solved);

        if (firstUnsolved) {
selectCrosswordWord(firstUnsolved);
}
    }

    if (!crosswordSelectedCell.value) {
return;
}

    const { r, c } = crosswordSelectedCell.value;
    const cell = crosswordGrid.value[r]?.[c];

    if (!cell) {
return;
}

    cell.value = cell.solution;

    if (cell.acrossIndex !== null && crosswordWords.value[cell.acrossIndex]) {
        checkCrosswordWord(crosswordWords.value[cell.acrossIndex]);
    }

    if (cell.downIndex !== null && crosswordWords.value[cell.downIndex]) {
        checkCrosswordWord(crosswordWords.value[cell.downIndex]);
    }

    if (crosswordSelectedWord.value) {
        const nextEmpty = crosswordSelectedWord.value.cells.find((pos) => {
            const item = crosswordGrid.value[pos.r]?.[pos.c];

            return item && !item.value;
        });

        if (nextEmpty) {
            crosswordSelectedCell.value = { r: nextEmpty.r, c: nextEmpty.c };
        }
    }
}

function handleGlobalKeydown(e: KeyboardEvent) {
    if (currentGame.value?.type !== 'crossword') {
return;
}

    const tag = (e.target as HTMLElement)?.tagName?.toLowerCase();

    if (tag === 'input' || tag === 'textarea') {
return;
}

    if (e.key === 'Backspace') {
        e.preventDefault();
        deleteCrosswordCell();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        moveCrosswordCursor(-1, 0);
    } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        moveCrosswordCursor(1, 0);
    } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        moveCrosswordCursor(0, -1);
    } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        moveCrosswordCursor(0, 1);
    } else if (e.key === ' ' || e.key === 'Tab') {
        e.preventDefault();
        toggleCrosswordDirection();
    } else if (e.key.length === 1 && /^[a-zA-Z0-9]$/.test(e.key)) {
        e.preventDefault();
        inputCrosswordLetter(e.key.toUpperCase());
    }
}

/* ── Word Search ───────────────────────────────────────────── */

interface WordSearchTarget {
    cardId: number;
    word: string;
    clue: string;
    found: boolean;
    color: string;
    cells: { r: number; c: number }[];
}

const WS_COLORS = [
    '#10B981',
    '#3B82F6',
    '#8B5CF6',
    '#F59E0B',
    '#EC4899',
    '#06B6D4',
];

const wordSearchMatrix = ref<string[][]>([]);
const wordSearchTargets = ref<WordSearchTarget[]>([]);
const wordSearchStartCell = ref<{ r: number; c: number } | null>(null);
const wordSearchSelectedCells = ref<{ r: number; c: number }[]>([]);

const wordSearchFoundCount = computed(
    () => wordSearchTargets.value.filter((t) => t.found).length
);

function initWordSearch() {
    const cards = currentGame.value?.cards || [];

    if (!cards.length) {
return;
}

    const rawWords = cards.map((c: any) => ({
        cardId: c.id,
        word: (c.answer || 'WORD').toUpperCase().replace(/[^A-Z0-9]/g, '') || 'FIND',
        clue: c.question && c.question !== 'Word Search' ? c.question : (c.answer || 'Keyword'),
    }));

    const maxLen = Math.max(...rawWords.map((w: any) => w.word.length), 4);
    const totalLetters = rawWords.reduce((s: number, w: any) => s + w.word.length, 0);

    let placedTargets: WordSearchTarget[] = [];
    let finalMatrix: string[][] = [];

    const dirs = [
        [0, 1],
        [1, 0],
        [1, 1],
        [-1, 1],
        [0, -1],
        [-1, 0],
    ];

    for (let attempt = 0; attempt < 5; attempt++) {
        const GRID_SIZE = Math.max(10, maxLen + 2, Math.ceil(Math.sqrt(totalLetters * 3.2))) + attempt * 2;
        const matrix: string[][] = Array.from({ length: GRID_SIZE }, () =>
            Array.from({ length: GRID_SIZE }, () => '')
        );
        const currentTargets: WordSearchTarget[] = [];
        let allPlaced = true;

        for (let idx = 0; idx < rawWords.length; idx++) {
            const item = rawWords[idx];
            const w = item.word;
            let placed = false;
            let tries = 0;

            while (!placed && tries < 300) {
                tries++;
                const dir = dirs[Math.floor(Math.random() * dirs.length)];
                const dR = dir[0];
                const dC = dir[1];

                const minR = dR < 0 ? w.length - 1 : 0;
                const maxR = dR > 0 ? GRID_SIZE - w.length : GRID_SIZE - 1;
                const minC = dC < 0 ? w.length - 1 : 0;
                const maxC = dC > 0 ? GRID_SIZE - w.length : GRID_SIZE - 1;

                if (maxR < minR || maxC < minC) {
continue;
}

                const startR = minR + Math.floor(Math.random() * (maxR - minR + 1));
                const startC = minC + Math.floor(Math.random() * (maxC - minC + 1));

                let can = true;

                for (let i = 0; i < w.length; i++) {
                    const r = startR + i * dR;
                    const c = startC + i * dC;

                    if (matrix[r][c] !== '' && matrix[r][c] !== w[i]) {
                        can = false;
                        break;
                    }
                }

                if (can) {
                    const cells: { r: number; c: number }[] = [];

                    for (let i = 0; i < w.length; i++) {
                        const r = startR + i * dR;
                        const c = startC + i * dC;
                        matrix[r][c] = w[i];
                        cells.push({ r, c });
                    }

                    currentTargets.push({
                        cardId: item.cardId,
                        word: w,
                        clue: item.clue,
                        found: false,
                        color: WS_COLORS[idx % WS_COLORS.length],
                        cells,
                    });
                    placed = true;
                }
            }

            if (!placed) {
                allPlaced = false;
                break;
            }
        }

        if (allPlaced) {
            placedTargets = currentTargets;
            finalMatrix = matrix;
            break;
        }
    }

    if (!finalMatrix.length) {
return;
}

    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    for (let r = 0; r < finalMatrix.length; r++) {
        for (let c = 0; c < finalMatrix[r].length; c++) {
            if (finalMatrix[r][c] === '') {
                finalMatrix[r][c] = alphabet[Math.floor(Math.random() * alphabet.length)];
            }
        }
    }

    wordSearchMatrix.value = finalMatrix;
    wordSearchTargets.value = placedTargets;
    wordSearchSelectedCells.value = [];
    wordSearchStartCell.value = null;
}

function handleWSCellHover(r: number, c: number) {
    if (!wordSearchStartCell.value) {
return;
}

    const start = wordSearchStartCell.value;
    const dR = r - start.r;
    const dC = c - start.c;
    const isLine = dR === 0 || dC === 0 || Math.abs(dR) === Math.abs(dC);

    if (!isLine) {
        wordSearchSelectedCells.value = [start];

        return;
    }

    const stepR = dR === 0 ? 0 : dR > 0 ? 1 : -1;
    const stepC = dC === 0 ? 0 : dC > 0 ? 1 : -1;
    const length = Math.max(Math.abs(dR), Math.abs(dC)) + 1;
    const sel: { r: number; c: number }[] = [];

    for (let i = 0; i < length; i++) {
        sel.push({ r: start.r + i * stepR, c: start.c + i * stepC });
    }

    wordSearchSelectedCells.value = sel;
}

function handleWSCellClick(r: number, c: number) {
    if (!wordSearchStartCell.value) {
        wordSearchStartCell.value = { r, c };
        wordSearchSelectedCells.value = [{ r, c }];

        return;
    }

    const start = wordSearchStartCell.value;
    const dR = r - start.r;
    const dC = c - start.c;

    const stepR = dR === 0 ? 0 : dR > 0 ? 1 : -1;
    const stepC = dC === 0 ? 0 : dC > 0 ? 1 : -1;

    const isLine = dR === 0 || dC === 0 || Math.abs(dR) === Math.abs(dC);

    if (!isLine) {
        wordSearchStartCell.value = { r, c };
        wordSearchSelectedCells.value = [{ r, c }];

        return;
    }

    const length = Math.max(Math.abs(dR), Math.abs(dC)) + 1;
    let formedWord = '';
    const selected: { r: number; c: number }[] = [];

    for (let i = 0; i < length; i++) {
        const curR = start.r + i * stepR;
        const curC = start.c + i * stepC;
        selected.push({ r: curR, c: curC });
        formedWord += wordSearchMatrix.value[curR]?.[curC] || '';
    }

    const reversedWord = formedWord.split('').reverse().join('');
    const target = wordSearchTargets.value.find(
        (t) => !t.found && (t.word === formedWord || t.word === reversedWord)
    );

    if (target) {
        target.found = true;
        toast.success(`Found "${target.word}"!`);
        awardCard(target.cardId);

        if (wordSearchTargets.value.every((t) => t.found)) {
            toast.success('All words found! Congratulations!');
            setTimeout(() => {
                finishGame();
            }, 1000);
        }
    }

    wordSearchStartCell.value = null;
    wordSearchSelectedCells.value = [];
}

function getWSCellStyle(r: number, c: number): any {
    const foundTarget = wordSearchTargets.value.find((t) =>
        t.found && t.cells.some((pos) => pos.r === r && pos.c === c)
    );

    if (foundTarget) {
        return {
            background: `${foundTarget.color}35`,
            borderColor: foundTarget.color,
            color: '#fff',
            boxShadow: `0 0 8px ${foundTarget.color}60`,
            border: `1.5px solid ${foundTarget.color}`,
        };
    }

    const isSelected = wordSearchSelectedCells.value.some((pos) => pos.r === r && pos.c === c);

    if (isSelected) {
        return {
            background: 'var(--gl-primary)',
            color: '#fff',
            boxShadow: '0 0 10px var(--gl-primary-glow)',
            border: '1.5px solid #fff',
        };
    }

    return {
        background: 'var(--gl-surface)',
        borderColor: 'var(--gl-border)',
        color: 'var(--gl-text-primary)',
        border: '1px solid var(--gl-border)',
    };
}

function canHintWordSearch(): boolean {
    const unfound = wordSearchTargets.value.find((t) => !t.found);

    return !!(unfound && unfound.cells.length);
}

function requestHintWordSearch() {
    if (!canHintWordSearch()) {
        toast.info('All words have already been found!');

        return;
    }

    triggerHint(() => applyHintWordSearch());
}

function applyHintWordSearch() {
    const unfound = wordSearchTargets.value.find((t) => !t.found);

    if (!unfound || !unfound.cells.length) {
return;
}

    const firstCell = unfound.cells[0];
    wordSearchStartCell.value = firstCell;
    wordSearchSelectedCells.value = [firstCell];
    toast.info(`Hint: "${unfound.word}" starts here!`);
}

/* ── Code Puzzle ───────────────────────────────────────────── */

interface CodeLineItem {
    cardId: number;
    code: string;
    comment: string;
    originalIndex: number;
}

const codeLines = ref<CodeLineItem[]>([]);
const codeLinesGraded = ref<(boolean | null)[]>([]);
const codeTerminalLogs = ref<string[]>([]);
const codePuzzleSolved = ref(false);
const codeExpectedOutput = ref<string>('');
const showCodeHelp = ref(false);
const codeLastVerifyStatus = ref<boolean | null>(null);
const draggedCodeLineIdx = ref<number | null>(null);
const dragOverCodeLineIdx = ref<number | null>(null);

const codePuzzleCorrectCount = computed(() => {
    return codeLines.value.filter((line, i) => line.originalIndex === i).length;
});

function deriveExpectedOutput(game: any, cards: any[]): string {
    if (!cards || !cards.length) {
return '';
}

    // 1. Check game.description for explicit target output patterns
    if (game?.description) {
        const desc = String(game.description);
        const match = desc.match(/(?:expected\s+output|target\s+output|target\s+result|output|prints?|result)[\s:=]+([^\n\r.]+)/i);

        if (match && match[1]?.trim()) {
            return match[1].trim().replace(/^['"`]|['"`]$/g, '');
        }
    }

    // 2. Scan cards in their correct (original) order for output statements
    const outputLines: string[] = [];

    for (const card of cards) {
        const code = String(card.answer || card.question || '').trim();
        const comment = String(card.question || '').trim();

        const printMatch =
            code.match(/(?:console\.log|System\.out\.println|print|println|printf)\s*\(\s*(.*?)\s*\)\s*;?$/i) ||
            code.match(/^echo\s+(.*?)\s*;?$/i);

        if (printMatch && printMatch[1]) {
            let val = printMatch[1].trim();

            if (
                (val.startsWith('"') && val.endsWith('"')) ||
                (val.startsWith("'") && val.endsWith("'")) ||
                (val.startsWith('`') && val.endsWith('`'))
            ) {
                val = val.slice(1, -1);
            }

            outputLines.push(val);
        } else if (comment && /print|output|display|show/i.test(comment)) {
            const commentMatch = comment.match(/(?:print|output|display|show)\s*[:"']?\s*(?:['"`](.*?)['"`]|([A-Za-z0-9_ -]+))/i);

            if (commentMatch && (commentMatch[1] || commentMatch[2])) {
                const target = (commentMatch[1] || commentMatch[2]).trim();

                if (target && !outputLines.includes(target) && !/^(the|a|an)\s/i.test(target)) {
                    outputLines.push(target);
                }
            }
        }
    }

    if (outputLines.length > 0) {
        return outputLines.join('\n');
    }

    // 3. Check for return statement in the cards
    for (let i = cards.length - 1; i >= 0; i--) {
        const cardCode = String(cards[i]?.answer || '').trim();
        const returnMatch = cardCode.match(/^return\s+(.*?);?$/i);

        if (returnMatch && returnMatch[1]) {
            return `Return: ${returnMatch[1].trim()}`;
        }
    }

    // 4. If game has a description, use it as the target specification
    if (game?.description && game.description.length < 120) {
        return game.description.trim();
    }

    // 5. Fallback
    return 'Program completes execution with exit code 0 (Success).';
}

function initCodePuzzle() {
    const cards = currentGame.value?.cards || [];
    const items: CodeLineItem[] = cards.map((c: any, i: number) => ({
        cardId: c.id,
        code: c.answer || c.question || '// code statement',
        comment: c.question && c.question !== 'Code Step' ? c.question : '',
        originalIndex: i,
    }));

    const shuffled = shuffleArray([...items]);

    if (items.length > 1 && shuffled.every((l, i) => l.originalIndex === i)) {
        [shuffled[0], shuffled[1]] = [shuffled[1], shuffled[0]];
    }

    codeLines.value = shuffled;
    codeLinesGraded.value = Array(items.length).fill(null);
    codeExpectedOutput.value = deriveExpectedOutput(currentGame.value, cards);
    codeLastVerifyStatus.value = null;
    draggedCodeLineIdx.value = null;
    dragOverCodeLineIdx.value = null;
    codeTerminalLogs.value = [
        '$ edupulse-exec --sandbox ./main.ts',
        '$ Ready. Drag statements or use arrows to sort into execution order.',
        '$ Click "Run & Verify" to test your solution.',
    ];
    codePuzzleSolved.value = false;
}

function onCodeDragStart(e: DragEvent, idx: number) {
    if (codePuzzleSolved.value) {
return;
}

    draggedCodeLineIdx.value = idx;

    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', String(idx));
    }
}

function onCodeDragOver(e: DragEvent, idx: number) {
    if (codePuzzleSolved.value) {
return;
}

    if (draggedCodeLineIdx.value === null) {
return;
}

    dragOverCodeLineIdx.value = idx;
}

function onCodeDragLeave(_e: DragEvent, idx: number) {
    if (dragOverCodeLineIdx.value === idx) {
        dragOverCodeLineIdx.value = null;
    }
}

function onCodeDrop(_e: DragEvent, targetIdx: number) {
    if (codePuzzleSolved.value) {
return;
}

    const fromIdx = draggedCodeLineIdx.value;

    if (
        fromIdx !== null &&
        fromIdx !== targetIdx &&
        fromIdx >= 0 &&
        fromIdx < codeLines.value.length &&
        targetIdx >= 0 &&
        targetIdx < codeLines.value.length
    ) {
        const arr = [...codeLines.value];
        const [moved] = arr.splice(fromIdx, 1);
        arr.splice(targetIdx, 0, moved);
        codeLines.value = arr;
        codeLinesGraded.value = Array(arr.length).fill(null);
        codeLastVerifyStatus.value = null;
    }

    draggedCodeLineIdx.value = null;
    dragOverCodeLineIdx.value = null;
}

function onCodeDragEnd() {
    draggedCodeLineIdx.value = null;
    dragOverCodeLineIdx.value = null;
}

function moveCodeLine(idx: number, dir: -1 | 1) {
    if (codePuzzleSolved.value) {
return;
}

    const j = idx + dir;

    if (j < 0 || j >= codeLines.value.length) {
return;
}

    const arr = [...codeLines.value];
    [arr[idx], arr[j]] = [arr[j], arr[idx]];
    codeLines.value = arr;
    codeLinesGraded.value = Array(arr.length).fill(null);
    codeLastVerifyStatus.value = null;
}

async function verifyCodePuzzle() {
    const cards = currentGame.value?.cards || [];
    const graded = codeLines.value.map((line, i) => line.originalIndex === i);
    codeLinesGraded.value = graded;

    const logs: string[] = [
        `$ edupulse-exec --verify-sequence ./main.ts`,
        `> Compiling and evaluating ${codeLines.value.length} code statements...`,
    ];

    let firstErrorIdx = -1;

    for (let i = 0; i < graded.length; i++) {
        if (!graded[i]) {
            firstErrorIdx = i;
            break;
        }
    }

    if (firstErrorIdx === -1) {
        codeLastVerifyStatus.value = true;
        logs.push(`✔ [PASS] All ${graded.length} statements executed in valid dependency order.`);
        logs.push(`✔ Verification 100% complete. Program exit status: 0.`);

        if (codeExpectedOutput.value) {
            logs.push(`----------------------------------------`);
            logs.push(`[Program Output]`);

            for (const outLine of codeExpectedOutput.value.split('\n')) {
                logs.push(outLine);
            }

            logs.push(`----------------------------------------`);
            logs.push(`✔ Output matches target specification!`);
        }

        codePuzzleSolved.value = true;
        codeTerminalLogs.value = logs;

        for (const card of cards) {
            await awardCard(card.id);
        }

        toast.success('Code executed successfully! All steps correct!');
        setTimeout(() => {
            finishGame();
        }, 1200);
    } else {
        codeLastVerifyStatus.value = false;
        const currentLine = codeLines.value[firstErrorIdx];
        const correctTargetLine = cards[firstErrorIdx];
        const actualStep = firstErrorIdx + 1;
        const properStep = currentLine.originalIndex + 1;

        if (firstErrorIdx > 0) {
            logs.push(`✔ Steps 1-${firstErrorIdx} verified.`);
        }

        logs.push(`✖ Line ${actualStep}: Sequence Error [${currentLine.code}]`);

        if (properStep > actualStep) {
            logs.push(`💡 Fix Hint: This statement belongs later in execution (expected Step ~${properStep}). Try dragging it downward.`);
        } else {
            logs.push(`💡 Fix Hint: This statement belongs earlier in execution (expected Step ~${properStep}). Try dragging it upward.`);
        }

        if (correctTargetLine?.question && correctTargetLine.question !== 'Code Step') {
            logs.push(`💡 Expected at Step ${actualStep}: ${correctTargetLine.question}`);
        } else if (/console\.log|print|return/i.test(currentLine.code) && actualStep < codeLines.value.length) {
            logs.push(`💡 Logic Rule: Output and return statements should come after variable operations and calculations.`);
        } else if (/\b(let|const|var)\b/i.test(currentLine.code)) {
            logs.push(`💡 Logic Rule: Variable declarations should be placed near the top before statements that consume them.`);
        }

        logs.push(`✖ Program terminated with exit status 1.`);
        codeTerminalLogs.value = logs;
        toast.error(`Line ${actualStep} is out of order. Check compiler diagnostics below!`);
    }
}

function canHintCodePuzzle(): boolean {
    if (codePuzzleSolved.value) {
return false;
}

    const misplacedIdx = codeLines.value.findIndex((line, i) => line.originalIndex !== i);

    return misplacedIdx !== -1;
}

function requestHintCodePuzzle() {
    if (!canHintCodePuzzle()) {
        toast.info('All statements are already in correct order!');

        return;
    }

    triggerHint(() => applyHintCodePuzzle());
}

function applyHintCodePuzzle() {
    if (codePuzzleSolved.value) {
return;
}

    const misplacedIdx = codeLines.value.findIndex((line, i) => line.originalIndex !== i);

    if (misplacedIdx === -1) {
return;
}

    const line = codeLines.value[misplacedIdx];
    const targetIdx = line.originalIndex;

    const arr = [...codeLines.value];
    [arr[misplacedIdx], arr[targetIdx]] = [arr[targetIdx], arr[misplacedIdx]];
    codeLines.value = arr;
    codeLinesGraded.value = Array(arr.length).fill(null);
    codeLastVerifyStatus.value = null;
    toast.info(`Moved statement "${line.code.slice(0, 24)}..." to Step ${targetIdx + 1}!`);
}

function canHintHangman(): boolean {
    if (answered.value) {
return false;
}

    const unguessed = hangmanWord.value.split('').filter((ch) => !hangmanGuessed.value.includes(ch));

    return unguessed.length > 0;
}

function requestHintHangman() {
    if (!canHintHangman()) {
        toast.info('All letters are already revealed!');

        return;
    }

    triggerHint(() => applyHintHangman());
}

function applyHintHangman() {
    const unguessed = hangmanWord.value.split('').filter((ch) => !hangmanGuessed.value.includes(ch));

    if (unguessed.length > 0) {
        const letter = unguessed[0];
        hangmanGuess(letter);
    }
}

function canHintWordJumble(): boolean {
    if (answered.value) {
return false;
}

    const card = currentGame.value?.cards[currentCard.value];
    const targetWord = (card?.answer || '').trim().toUpperCase();

    return jumbleAnswer.value.length < targetWord.length;
}

function requestHintWordJumble() {
    if (!canHintWordJumble()) {
        toast.info('All letters are already placed!');

        return;
    }

    triggerHint(() => applyHintWordJumble());
}

function applyHintWordJumble() {
    const card = currentGame.value?.cards[currentCard.value];
    const targetWord = (card?.answer || '').trim().toUpperCase();
    const nextIdx = jumbleAnswer.value.length;
    const nextLetter = targetWord[nextIdx];
    const bankIdx = jumbleLetters.value.findIndex((l: string, i: number) => l === nextLetter && !jumbleAnswerIndices.value.includes(i));

    if (bankIdx !== -1) {
        addJumbleLetter(bankIdx);
    }
}

async function awardCard(cardId: number) {
    const g = currentGame.value;

    if (!g || !g.id) {
        return;
    }

    try {
        const token =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';
        await fetch(`/student/games/${g.id}/answer`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ card_id: cardId }),
        });
    } catch {
        /* silent */
    }
}

async function awardCorrect() {
    const g = currentGame.value;

    if (!g || !g.id) {
        return;
    }

    try {
        const token =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';
        const card = currentGame.value?.cards[currentCard.value];

        if (!card?.id) {
            return;
        }

        const res = await fetch(`/student/games/${g.id}/answer`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ card_id: card.id }),
        });

        if (!res.ok) {
            void res;
        }
    } catch {
        /* silent */
    }
}

async function saveProgress(completed = false, keepalive = false) {
    const payload = progressPayload(completed);

    if (!payload) {
        return 0;
    }

    const { game, body } = payload;

    try {
        const token =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';
        const res = await fetch(`/student/games/${game.id}/progress`, {
            method: 'POST',
            keepalive,
            headers: {
                'X-CSRF-TOKEN': token,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(body),
        });

        if (res.ok) {
            const data = await res.json();

            if (data.progress) {
                const formatted = {
                    ...data.progress,
                    earned_xp: data.progress.earned_xp ?? data.xp ?? 0,
                    correct_card_ids: data.progress.correct_card_ids ?? [],
                };
                game.progress = formatted;
                const propGame = (props.games || []).find((x: any) => x.id === game.id);

                if (propGame) {
                    propGame.progress = formatted;
                }
            }

            return completed ? (data.xp ?? 0) : 0;
        }

        return 0;
    } catch {
        /* silent */
        return 0;
    }
}

function progressPayload(completed: boolean) {
    const g = currentGame.value;

    if (!g || !g.id) {
        return null;
    }

    const type = g.type;

    if (boardTypes.includes(type) && !completed) {
        return null;
    }

    let done: number;
    let score: number;
    let graded: (null | boolean)[] | null;

    if (type === 'memorymatch' || type === 'dragdrop') {
        done = g.cards.length;
        score =
            type === 'memorymatch' ? memoryMatches.value : dropMatches.value;
        graded = g.cards.map(() => true);
    } else if (type === 'ordering') {
        done = g.cards.length;
        score = orderCorrect.value;
        graded = orderFinished.value
            ? orderGraded.value
            : g.cards.map(() => true);
    } else if (type === 'crossword') {
        done = g.cards.length;
        score = crosswordSolvedCount.value;
        graded = g.cards.map((c: any) => {
            const w = crosswordWords.value.find((item: any) => item.cardId === c.id);

            return w ? Boolean(w.solved) : false;
        });
    } else if (type === 'wordsearch') {
        done = g.cards.length;
        score = wordSearchFoundCount.value;
        graded = g.cards.map((c: any) => {
            const t = wordSearchTargets.value.find((item: any) => item.cardId === c.id);

            return t ? Boolean(t.found) : false;
        });
    } else if (type === 'codepuzzle') {
        done = g.cards.length;
        score = codeLinesGraded.value.filter(Boolean).length;
        graded = codeLinesGraded.value;
    } else if (type === 'flashcard') {
        done = Math.max(0, currentCard.value);
        score = 0;
        graded = null;
    } else {
        done = gradedState.value.filter((v) => v !== null).length;
        score = correctCount.value;
        graded = gradedState.value;
    }

    const cardOrder = (g.cards || []).map((c: any) => c.id).join(',');

    return {
        game: g,
        body: {
            current_card: currentCard.value,
            done_count: done,
            score,
            completed,
            card_order: cardOrder,
            graded,
        },
    };
}

function beaconSubmit() {
    const g = currentGame.value;

    if (!g || !g.id || forceSubmitted.value) {
        return;
    }

    forceSubmitted.value = true;
    const payload = progressPayload(true);

    if (!payload) {
        return;
    }

    try {
        const token =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';
        navigator.sendBeacon(
            `/student/games/${g.id}/progress`,
            new Blob([JSON.stringify({ _token: token, ...payload.body })], {
                type: 'application/json',
            }),
        );
    } catch {
        /* silent */
    }
}

async function finishGame() {
    if (!currentGame.value || endingGame) {
        return;
    }

    endingGame = true;
    forceSubmitted.value = true;
    const current = currentGame.value;
    const xp = await saveProgress(true);
    currentGame.value = null;
    await exitGameFullscreen();

    if (current) {
        const found = games.value.find((item: any) => item.id === current.id);

        if (found) {
            openResult(found);
        } else {
            openResult(current);
        }
    }

    if (xp > 0) {
        toast.success(`Game completed! +${xp} XP`);
    }

    endingGame = false;
}
</script>

<style scoped>
.memory-match-board,
.crossword-board,
.word-search-board {
    min-height: 0;
}

.memory-match-board {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(108px, 35%);
    grid-template-rows: auto minmax(0, 1fr) auto;
    align-items: stretch;
}

.memory-match-board > :first-child {
    grid-column: 1 / -1;
}

.memory-description {
    grid-column: 2;
    grid-row: 2;
    align-self: center;
}

.memory-tiles {
    grid-column: 1;
    grid-row: 2;
    min-width: 0;
}

.memory-complete {
    grid-column: 1 / -1;
    grid-row: 3;
}

.memory-grid {
    grid-template-rows: repeat(auto-fit, minmax(0, 1fr));
    grid-auto-rows: minmax(0, 1fr);
}

.memory-tile {
    min-height: 0;
    overflow: hidden;
}

.memory-tile-copy {
    max-height: 100%;
    overflow: hidden;
}

.crossword-layout,
.crossword-grid-panel,
.crossword-bank-panel {
    min-height: 0;
}

.crossword-layout {
    min-height: 0;
    height: 100%;
    max-height: 100%;
    grid-template-rows: minmax(0, 1fr);
    align-items: stretch;
}

.crossword-grid-shell {
    width: min(100%, calc(100vh - 280px), 520px);
    max-width: 100%;
    max-height: 100%;
    overflow: hidden;
}

.crossword-grid {
    gap: clamp(2px, 0.35vw, 6px);
}

.crossword-cell {
    width: 100%;
    height: auto;
    min-width: 0;
    aspect-ratio: 1;
    font-size: clamp(0.45rem, 1.5vw, 0.875rem);
    line-height: 1;
}

.crossword-keyboard {
    flex-shrink: 1;
}

.crossword-keyboard .crossword-key {
    flex: 0 0 auto;
}

.crossword-bank-panel {
    min-height: 0;
    min-width: 0;
    height: 100%;
    max-height: 100%;
    overflow: hidden;
}

.crossword-details-card {
    min-height: 0;
    min-width: 0;
    height: 100%;
    max-height: 100%;
}

.crossword-details-header {
    min-width: 0;
}

.crossword-bank {
    min-width: 0;
    max-height: 100%;
    overflow-y: auto;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
}

.crossword-entry {
    min-width: 0;
}

.crossword-entry > div {
    min-width: 0;
}

.word-search-layout,
.word-search-matrix,
.word-search-bank-panel {
    min-height: 0;
}

.word-search-layout {
    gap: clamp(0.75rem, 2vw, 1.5rem);
    align-items: stretch;
}

.word-search-matrix {
    min-width: 0;
    min-height: 0;
    height: 100%;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.word-search-matrix-shell {
    width: min(100%, calc(100vh - 220px), 540px);
    max-width: 100%;
    max-height: 100%;
    aspect-ratio: 1 / 1;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex: 0 1 auto;
    box-sizing: border-box;
}

.word-search-matrix-grid {
    width: 100%;
    height: 100%;
    aspect-ratio: 1 / 1;
    gap: clamp(1px, 0.35vw, 4px);
    display: grid;
    place-content: stretch;
}

.word-search-cell {
    width: 100%;
    height: 100%;
    min-width: 0;
    min-height: 0;
    aspect-ratio: 1 / 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    font-size: clamp(0.55rem, 1.2vw, 0.95rem);
    line-height: 1;
}

.word-search-bank-panel {
    min-height: 0;
    min-width: 0;
    overflow: hidden;
}

.word-search-details-card {
    min-height: 0;
    min-width: 0;
}

.word-search-details-header {
    min-width: 0;
}

.word-search-bank {
    min-width: 0;
    max-height: 100%;
    overflow-y: auto;
    overscroll-behavior: contain;
}

.word-search-entry {
    min-width: 0;
}

.word-search-entry > div {
    min-width: 0;
}

@media (max-width: 1023px) {
    .crossword-layout {
        grid-template-columns: minmax(0, 1.15fr) minmax(180px, 0.85fr);
        grid-template-rows: minmax(0, 1fr);
        height: 100%;
        max-height: 100%;
    }

    .crossword-grid-panel {
        min-width: 0;
        grid-column: 1;
        grid-row: 1;
    }

    .crossword-bank-panel {
        grid-column: 2;
        grid-row: 1;
        min-height: 0;
        max-height: 100%;
        height: 100%;
    }
}

@media (max-width: 639px) {
    .memory-grid {
        gap: 0.35rem;
    }

    .memory-tile {
        border-radius: 0.75rem;
    }

    .memory-tile-copy p {
        font-size: 0.625rem;
        line-height: 1.15;
        display: -webkit-box;
        overflow: hidden;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 3;
    }

    .crossword-keyboard p {
        display: none;
    }

    .crossword-keyboard .crossword-key {
        width: clamp(1.25rem, 6vw, 2rem);
    }

    .crossword-clues {
        gap: 0.35rem;
    }

    .crossword-clue-card {
        padding: 0.35rem;
    }

    .crossword-clue-list {
        margin-top: 0.25rem;
    }

    .crossword-clue-list button {
        padding: 0.25rem;
        font-size: 0.625rem;
        line-height: 1.1;
    }

    .memory-match-board {
        gap: 0.35rem;
    }

    .memory-description {
        padding: 0.5rem;
    }

    .memory-complete {
        margin-top: 0.35rem;
    }

    .word-search-board {
        overflow-y: auto;
    }

    .word-search-layout {
        display: flex;
        flex-direction: column;
        flex: 0 0 auto;
        min-height: 0;
        gap: 0.75rem;
    }

    .word-search-matrix {
        min-height: 0;
        overflow: hidden;
    }

    .word-search-matrix-shell {
        width: min(100%, 46vh);
        max-width: 380px;
    }

    .word-search-bank-panel {
        min-height: 0;
        overflow: hidden;
    }

    .word-search-details-card {
        max-height: 38vh;
    }

    .word-search-bank {
        max-height: none;
        overflow-y: auto;
    }
}

.h-4\.5 {
    height: 1.125rem;
}
.w-4\.5 {
    width: 1.125rem;
}
</style>
