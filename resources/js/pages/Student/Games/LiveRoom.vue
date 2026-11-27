<template>
    <Head :title="`Live Arena: ${game.title}`" />

    <div class="mx-auto max-w-3xl space-y-6">
        <!-- First Solver Real-time Banner -->
        <div
            v-if="currentFirstSolver && roomStatus === 'active'"
            class="gl-fade-in flex items-center justify-center gap-2 rounded-2xl border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-xs font-extrabold text-amber-600 dark:text-amber-400 shadow-sm"
        >
            <Zap class="h-4 w-4 fill-current animate-bounce" />
            <span v-if="currentFirstSolver.user_id === (page.props as any).auth?.user?.id">
                ⚡ FASTEST FINGERS! You were the first to answer correctly! +500 Bonus Points!
            </span>
            <span v-else>
                ⚡ {{ currentFirstSolver.user_name }} was first to answer correctly! (+500 Bonus)
            </span>
        </div>

        <!-- Top Live Stats Header -->
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[var(--gl-border)] bg-[var(--gl-surface)] p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <Link
                    href="/student/games"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border border-[var(--gl-border)] transition-colors hover:bg-[var(--gl-surface-2)]"
                >
                    <ArrowLeft class="h-4 w-4" style="color: var(--gl-text-secondary)" />
                </Link>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-[11px] font-semibold text-emerald-600">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                            PIN: <strong class="font-mono font-bold">{{ room.pin }}</strong>
                            <button
                                type="button"
                                @click="copyPin"
                                class="ml-1 text-emerald-600 hover:text-emerald-700 transition-colors"
                                title="Copy Room PIN"
                            >
                                <Check v-if="copied" class="h-3 w-3" />
                                <Copy v-else class="h-3 w-3" />
                            </button>
                        </span>
                        <span class="text-xs font-semibold text-[var(--gl-text-primary)]">{{ game.title }}</span>
                    </div>
                </div>
            </div>

            <!-- Player Live Stats -->
            <div class="flex items-center gap-2 sm:gap-3">
                <button
                    type="button"
                    @click="showLiveLeaderboardModal = !showLiveLeaderboardModal"
                    class="flex items-center gap-1.5 rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 py-1.5 text-xs font-extrabold text-amber-600 dark:text-amber-400 transition-all hover:bg-amber-500/20 active:scale-95 shadow-sm"
                    title="View Top 10 Live Standings"
                >
                    <Trophy class="h-4 w-4 text-amber-500" />
                    <span v-if="studentRank" class="font-mono">#{{ studentRank }}</span>
                    <span>Top 10</span>
                </button>
                <div class="flex items-center gap-1.5 rounded-xl bg-orange-500/10 px-3 py-1.5 text-xs font-bold text-orange-600">
                    <Flame class="h-4 w-4 fill-current" />
                    <span>{{ streak }}x</span>
                </div>
                <div class="rounded-xl bg-emerald-500/10 px-3.5 py-1.5 font-mono text-sm font-black text-emerald-600">
                    {{ score.toLocaleString() }} pts
                </div>
            </div>
        </div>

        <!-- 1. WAITING LOBBY -->
        <div v-if="roomStatus === 'waiting'" class="rounded-3xl border border-[var(--gl-border)] bg-[var(--gl-surface)] p-8 text-center shadow-md sm:p-12">
            <div class="mx-auto max-w-md space-y-4">
                <div class="inline-flex h-16 w-16 animate-bounce items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600">
                    <Gamepad2 class="h-8 w-8" />
                </div>
                <h2 class="text-2xl font-black text-[var(--gl-text-primary)]">
                    You're in the Live Room!
                </h2>
                <p class="text-sm text-[var(--gl-text-secondary)]">
                    Waiting for your teacher to start the competition. Get ready!
                </p>

                <div class="flex items-center justify-center gap-2.5 rounded-2xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] p-4 text-xs font-semibold text-[var(--gl-text-muted)]">
                    <span>Room PIN:</span>
                    <span class="font-mono text-xl font-black text-[var(--gl-text-primary)] tracking-widest">{{ room.pin }}</span>
                    <button
                        type="button"
                        @click="copyPin"
                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-600/10 text-emerald-600 hover:bg-emerald-600/20 active:scale-95 transition-all"
                        title="Copy PIN"
                    >
                        <Check v-if="copied" class="h-3.5 w-3.5" />
                        <Copy v-else class="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. ACTIVE QUESTION INTERACTION -->
        <div v-else-if="roomStatus === 'active'" class="space-y-6">
            <div class="rounded-3xl border border-[var(--gl-border)] bg-[var(--gl-surface)] p-6 shadow-md sm:p-8">
                <!-- Question Number & Status Header -->
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <span class="rounded-full bg-[var(--gl-surface-2)] px-3 py-1 text-xs font-bold text-[var(--gl-text-secondary)]">
                        Question {{ currentCardIndex + 1 }} of {{ room.total_cards }}
                    </span>
                    <div class="flex items-center gap-2">
                        <span v-if="hasAnsweredCurrent" class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600">
                            <CheckCircle class="h-4 w-4" /> Locked In
                        </span>
                        <div
                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 font-mono text-xs font-bold border transition-all"
                            :class="timeLeft <= 5 ? 'border-red-500/40 bg-red-500/10 text-red-600 animate-pulse' : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-600'"
                        >
                            <Timer class="h-3 w-3" />
                            <span>{{ timeLeft }}s</span>
                        </div>
                    </div>
                </div>

                <!-- 15s Countdown Progress Bar -->
                <div class="mb-6 h-1.5 w-full overflow-hidden rounded-full bg-[var(--gl-surface-2)]">
                    <div
                        class="h-full transition-all duration-1000 ease-linear"
                        :class="timeLeft <= 5 ? 'bg-red-500' : 'bg-emerald-500'"
                        :style="{ width: `${(timeLeft / 15) * 100}%` }"
                    ></div>
                </div>

                <!-- Prompt Card -->
                <div class="mb-6 rounded-2xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] p-6 text-center">
                    <p class="text-lg font-bold leading-relaxed text-[var(--gl-text-primary)] sm:text-xl">
                        {{ currentCard?.question || 'Get ready for question...' }}
                    </p>
                </div>

                <!-- Feedback Banner -->
                <div
                    v-if="feedbackMessage"
                    class="gl-fade-in mb-6 rounded-2xl p-4 text-center font-bold"
                    :class="[
                        feedbackType === 'correct'
                            ? 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/30'
                            : 'bg-red-500/10 text-red-600 border border-red-500/30'
                    ]"
                >
                    <div class="text-base">{{ feedbackMessage }}</div>
                    <div v-if="feedbackPoints > 0" class="mt-1 text-xs font-mono font-extrabold">
                        +{{ feedbackPoints }} Points! 🔥
                    </div>
                </div>

                <!-- Multiple Choice Options (for Quiz, Speed Quiz, True False) -->
                <div v-if="currentCard?.options && currentCard.options.length" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <button
                        v-for="(opt, idx) in currentCard.options"
                        :key="idx"
                        type="button"
                        :disabled="hasAnsweredCurrent || submitting || timeLeft === 0"
                        @click="submitChoice(opt)"
                        class="group relative flex items-center gap-3.5 rounded-2xl p-4 sm:p-5 text-left font-bold text-white shadow-md transition-all hover:scale-[1.02] active:scale-95 disabled:pointer-events-none disabled:opacity-60"
                        :class="[
                            selectedAnswer === opt ? 'ring-4 ring-white/90 scale-[1.02]' : ''
                        ]"
                        :style="getOptionButtonStyle(idx)"
                    >
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/20 text-sm font-black shadow-inner backdrop-blur-sm group-hover:bg-white/30 transition-colors">
                            {{ ['A', 'B', 'C', 'D'][idx % 4] }}
                        </span>
                        <span class="flex-1 text-sm sm:text-base break-words leading-snug">{{ opt }}</span>
                        <Check v-if="selectedAnswer === opt" class="h-5 w-5 shrink-0 text-white" />
                    </button>
                </div>

                <!-- Text Input (for 4 Pics, Crossword, Fill Blank) -->
                <div v-else class="space-y-4">
                    <form @submit.prevent="submitTextInput" class="flex gap-2">
                        <input
                            v-model="textAnswer"
                            type="text"
                            :disabled="hasAnsweredCurrent || submitting || timeLeft === 0"
                            placeholder="Type your answer here..."
                            class="flex-1 rounded-2xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] px-4 py-3.5 text-sm font-bold text-[var(--gl-text-primary)] outline-none focus:border-emerald-500 disabled:opacity-50"
                        />
                        <button
                            type="submit"
                            :disabled="hasAnsweredCurrent || submitting || !textAnswer.trim() || timeLeft === 0"
                            class="rounded-2xl bg-emerald-600 px-6 py-3.5 text-sm font-bold text-white transition-all hover:bg-emerald-500 active:scale-95 disabled:opacity-50"
                        >
                            Submit
                        </button>
                    </form>
                </div>

                <!-- Waiting for Next Question Notice -->
                <div v-if="hasAnsweredCurrent || timeLeft === 0" class="mt-6 flex items-center justify-center gap-2 text-xs font-semibold text-[var(--gl-text-muted)]">
                    <LoaderCircle class="h-4 w-4 animate-spin" />
                    <span v-if="timeLeft === 0 && !hasAnsweredCurrent">Time's up! Advancing to the next question...</span>
                    <span v-else>Next question in {{ timeLeft }}s...</span>
                </div>
            </div>
        </div>

        <!-- 3. FINISHED PODIUM SCREEN -->
        <div v-else-if="roomStatus === 'finished'" class="rounded-3xl border border-[var(--gl-border)] bg-[var(--gl-surface)] p-6 text-center shadow-lg sm:p-10">
            <div class="mx-auto max-w-2xl space-y-6">
                <div>
                    <Trophy class="mx-auto h-14 w-14 text-amber-500 animate-bounce" />
                    <h2 class="text-3xl font-black text-[var(--gl-text-primary)]">
                        Game Over!
                    </h2>
                    <p class="mt-1 text-sm text-[var(--gl-text-secondary)]">
                        Match finished. Check out your final standing and the session's Top 10:
                    </p>
                </div>

                <!-- Personal Result Card -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-left">
                    <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-5">
                        <div class="text-xs font-bold uppercase tracking-wider text-emerald-600">Your Final Score</div>
                        <div class="mt-1 font-mono text-3xl font-black text-[var(--gl-text-primary)]">{{ score.toLocaleString() }} pts</div>
                        <div class="mt-1 text-xs font-semibold text-emerald-600">+XP Awarded to Profile!</div>
                    </div>

                    <div
                        class="rounded-2xl border p-5 flex flex-col justify-center"
                        :class="[
                            studentRank === 1 ? 'border-amber-500/40 bg-amber-500/10' :
                            studentRank && studentRank <= 3 ? 'border-amber-600/40 bg-amber-600/10' :
                            studentRank && studentRank <= 10 ? 'border-blue-500/30 bg-blue-500/10' :
                            'border-[var(--gl-border)] bg-[var(--gl-surface-2)]'
                        ]"
                    >
                        <div class="text-xs font-bold uppercase tracking-wider text-[var(--gl-text-muted)]">Your Rank</div>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="font-mono text-3xl font-black text-[var(--gl-text-primary)]">
                                {{ studentRank ? `#${studentRank}` : '—' }}
                            </span>
                            <span class="text-xs font-bold text-[var(--gl-text-secondary)]">
                                of {{ leaderboard.length }} players
                            </span>
                        </div>
                        <div class="mt-1 text-xs font-bold">
                            <span v-if="studentRank === 1" class="text-amber-600">🏆 1st Place Champion!</span>
                            <span v-else-if="studentRank && studentRank <= 3" class="text-amber-600">🎖️ Podium Winner!</span>
                            <span v-else-if="studentRank && studentRank <= 10" class="text-blue-600">✨ Top 10 Finish!</span>
                            <span v-else class="text-[var(--gl-text-muted)]">👏 Great Effort!</span>
                        </div>
                    </div>
                </div>

                <!-- Top 10 Session Leaderboard -->
                <div v-if="leaderboard.length > 0" class="text-left space-y-3 pt-2">
                    <div class="flex items-center justify-between">
                        <h3 class="flex items-center gap-2 text-sm font-extrabold uppercase tracking-wider text-[var(--gl-text-primary)]">
                            <Trophy class="h-4 w-4 text-amber-500" />
                            Top 10 Session Standings
                        </h3>
                        <span class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[11px] font-bold text-emerald-600">
                            {{ Math.min(10, leaderboard.length) }} of {{ leaderboard.length }} Players
                        </span>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] shadow-sm">
                        <div class="divide-y divide-[var(--gl-border)]">
                            <div
                                v-for="entry in top10Leaderboard"
                                :key="entry.user_id"
                                class="flex items-center justify-between p-3 transition-colors"
                                :class="[
                                    entry.user_id === currentUserId ? 'bg-emerald-500/15 border-l-4 border-l-emerald-500 font-bold' :
                                    entry.rank === 1 ? 'bg-amber-500/5' :
                                    entry.rank === 2 ? 'bg-slate-400/5' :
                                    entry.rank === 3 ? 'bg-amber-700/5' : ''
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
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="truncate text-xs font-bold text-[var(--gl-text-primary)]">
                                                {{ entry.name }}
                                            </span>
                                            <span v-if="entry.user_id === currentUserId" class="rounded-full bg-emerald-500/20 px-2 py-0.2 text-[10px] font-extrabold text-emerald-600">
                                                You
                                            </span>
                                            <span v-if="entry.rank <= 3" class="text-xs shrink-0">
                                                {{ entry.rank === 1 ? '🥇' : entry.rank === 2 ? '🥈' : '🥉' }}
                                            </span>
                                        </div>
                                        <span v-if="entry.streak > 1" class="inline-flex items-center gap-1 text-[10px] font-bold text-orange-500">
                                            <Flame class="h-2.5 w-2.5 fill-current" /> {{ entry.streak }}x streak
                                        </span>
                                    </div>
                                </div>
                                <span class="font-mono text-xs font-black text-emerald-600 dark:text-emerald-400 shrink-0 ml-2">
                                    {{ entry.score.toLocaleString() }} pts
                                </span>
                            </div>

                            <!-- If current student finished outside Top 10, pin their entry below -->
                            <div
                                v-if="currentStudentEntry && currentStudentEntry.rank > 10"
                                class="flex items-center justify-between p-3 bg-emerald-500/15 border-l-4 border-l-emerald-500"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl text-xs font-black bg-emerald-600 text-white shadow-sm">
                                        {{ currentStudentEntry.rank }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="truncate text-xs font-bold text-[var(--gl-text-primary)]">
                                                {{ currentStudentEntry.name }}
                                            </span>
                                            <span class="rounded-full bg-emerald-500/20 px-2 py-0.2 text-[10px] font-extrabold text-emerald-600">
                                                You
                                            </span>
                                        </div>
                                        <span v-if="currentStudentEntry.streak > 1" class="inline-flex items-center gap-1 text-[10px] font-bold text-orange-500">
                                            <Flame class="h-2.5 w-2.5 fill-current" /> {{ currentStudentEntry.streak }}x streak
                                        </span>
                                    </div>
                                </div>
                                <span class="font-mono text-xs font-black text-emerald-600 dark:text-emerald-400 shrink-0 ml-2">
                                    {{ currentStudentEntry.score.toLocaleString() }} pts
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <Link
                        href="/student/games"
                        class="inline-block rounded-xl bg-emerald-600 px-6 py-3 text-sm font-bold text-white shadow-md transition-transform hover:scale-105"
                    >
                        Return to Arcade
                    </Link>
                </div>
            </div>
        </div>

        <!-- Top 10 Modal for Student during Live Session -->
        <Teleport to="body">
            <div
                v-if="showLiveLeaderboardModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(2px);"
                @click.self="showLiveLeaderboardModal = false"
            >
                <div class="w-full max-w-md rounded-3xl border border-[var(--gl-border)] bg-[var(--gl-surface)] p-6 shadow-2xl">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="flex items-center gap-2 text-base font-extrabold text-[var(--gl-text-primary)]">
                            <Trophy class="h-5 w-5 text-amber-500" />
                            Top 10 Live Standings
                        </h3>
                        <button
                            type="button"
                            @click="showLiveLeaderboardModal = false"
                            class="rounded-xl p-1.5 text-[var(--gl-text-muted)] hover:bg-[var(--gl-surface-2)] hover:text-[var(--gl-text-primary)] transition-colors"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div v-if="!leaderboard.length" class="py-8 text-center text-xs text-[var(--gl-text-muted)]">
                        No scores recorded yet.
                    </div>

                    <div v-else class="space-y-2 max-h-[60vh] overflow-y-auto pr-1">
                        <div
                            v-for="entry in top10Leaderboard"
                            :key="entry.user_id"
                            class="flex items-center justify-between rounded-xl border border-[var(--gl-border)] p-2.5 transition-all"
                            :class="[
                                entry.user_id === currentUserId ? 'bg-emerald-500/15 border-emerald-500/40' : 'bg-[var(--gl-surface-2)]',
                                entry.rank === 1 ? 'border-amber-500/30' : ''
                            ]"
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span
                                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-xs font-black"
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
                                <span v-if="entry.user_id === currentUserId" class="rounded-full bg-emerald-500/20 px-1.5 py-0.2 text-[9px] font-extrabold text-emerald-600">
                                    You
                                </span>
                                <span v-if="entry.streak > 1" class="inline-flex shrink-0 items-center gap-0.5 text-[10px] font-bold text-orange-500">
                                    <Flame class="h-2.5 w-2.5 fill-current" /> {{ entry.streak }}x
                                </span>
                            </div>
                            <span class="font-mono text-xs font-bold text-emerald-600 dark:text-emerald-400 shrink-0 ml-2">
                                {{ entry.score.toLocaleString() }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-[var(--gl-border)] flex items-center justify-between text-xs text-[var(--gl-text-muted)]">
                        <span>Your Rank: <strong class="text-[var(--gl-text-primary)]">#{{ studentRank || '—' }}</strong></span>
                        <button
                            type="button"
                            @click="showLiveLeaderboardModal = false"
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
import { ArrowLeft, Flame, CheckCircle, Gamepad2, Trophy, LoaderCircle, Zap, Copy, Check, Timer, X } from '@lucide/vue';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { toast } from 'vue-sonner';
import { getEcho } from '@/lib/echo';

const props = defineProps<{
    room: {
        id: number;
        pin: string;
        status: string;
        current_card_index: number;
        total_cards: number;
        first_solver?: any;
    };
    game: {
        id: number;
        title: string;
        type: string;
        subject?: string;
        grade?: string;
    };
    card: any;
    participant: {
        id: number;
        score: number;
        streak: number;
        last_answered_card_index: number | null;
    };
    leaderboard: any[];
}>();

const page = usePage();
const copied = ref(false);

function copyPin() {
    navigator.clipboard.writeText(props.room.pin);
    copied.value = true;
    toast.success(`PIN ${props.room.pin} copied!`);
    setTimeout(() => {
        copied.value = false;
    }, 2000);
}

const roomStatus = ref(props.room.status);
const currentCardIndex = ref(props.room.current_card_index);
const currentCard = ref(props.card);
const currentFirstSolver = ref<any>(props.room.first_solver || null);
const leaderboard = ref<any[]>(props.leaderboard || []);
const score = ref(props.participant.score || 0);
const streak = ref(props.participant.streak || 0);
const lastAnsweredCardIndex = ref<number | null>(props.participant.last_answered_card_index);

const showLiveLeaderboardModal = ref(false);

const currentUserId = computed(() => {
    return (page.props as any).auth?.user?.id;
});

const top10Leaderboard = computed(() => {
    return leaderboard.value.slice(0, 10);
});

const currentStudentEntry = computed(() => {
    return leaderboard.value.find((e: any) => e.user_id === currentUserId.value) || null;
});

const studentRank = computed(() => {
    return currentStudentEntry.value?.rank || null;
});

const textAnswer = ref('');
const selectedAnswer = ref<string | null>(null);
const submitting = ref(false);
const feedbackMessage = ref('');
const feedbackType = ref<'correct' | 'wrong' | ''>('');
const feedbackPoints = ref(0);

const timeLeft = ref(15);
let studentTimerInterval: any = null;

function startStudentTimer() {
    stopStudentTimer();
    timeLeft.value = 15;

    if (roomStatus.value !== 'active') {
return;
}

    studentTimerInterval = setInterval(() => {
        if (roomStatus.value !== 'active') {
return;
}

        if (timeLeft.value > 0) {
            timeLeft.value -= 1;
        } else {
            stopStudentTimer();
        }
    }, 1000);
}

function stopStudentTimer() {
    if (studentTimerInterval) {
        clearInterval(studentTimerInterval);
        studentTimerInterval = null;
    }
}

watch(currentCardIndex, () => {
    selectedAnswer.value = null;
    startStudentTimer();
});

watch(roomStatus, (newStatus) => {
    if (newStatus === 'active') {
        startStudentTimer();
    } else {
        stopStudentTimer();
    }
});

let presenceChannel: any = null;
let pollInterval: any = null;

const hasAnsweredCurrent = computed(() => {
    return lastAnsweredCardIndex.value === currentCardIndex.value;
});

function getOptionButtonStyle(idx: number): { background: string } {
    const gradients = [
        'linear-gradient(135deg, #ef4444, #dc2626)',
        'linear-gradient(135deg, #3b82f6, #2563eb)',
        'linear-gradient(135deg, #f59e0b, #d97706)',
        'linear-gradient(135deg, #10b981, #059669)',
    ];

    return { background: gradients[idx % gradients.length] };
}

async function submitChoice(choice: string) {
    selectedAnswer.value = choice;
    await sendAnswer(choice);
}

async function submitTextInput() {
    if (!textAnswer.value.trim()) {
return;
}

    await sendAnswer(textAnswer.value.trim());
}

async function sendAnswer(answer: string) {
    if (submitting.value || hasAnsweredCurrent.value) {
return;
}

    submitting.value = true;

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const res = await fetch(`/student/games/rooms/${props.room.pin}/answer`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                card_index: currentCardIndex.value,
                answer: answer,
            }),
        });

        if (!res.ok) {
            submitting.value = false;

            return;
        }

        const data = await res.json();
        lastAnsweredCardIndex.value = currentCardIndex.value;
        score.value = data.total_score;
        streak.value = data.streak;

        if (data.is_first_solver) {
            currentFirstSolver.value = {
                user_id: (page.props as any).auth?.user?.id,
                user_name: (page.props as any).auth?.user?.name,
            };
        } else if (data.first_solver_name && !currentFirstSolver.value) {
            currentFirstSolver.value = {
                user_name: data.first_solver_name,
            };
        }

        if (data.correct) {
            feedbackType.value = 'correct';
            feedbackMessage.value = data.is_first_solver
                ? '⚡ FASTEST FINGERS! You answered first!'
                : 'Correct! Outstanding answer!';
            feedbackPoints.value = data.points_earned;
        } else {
            feedbackType.value = 'wrong';
            feedbackMessage.value = `Incorrect. Keep your head up!`;
            feedbackPoints.value = 0;
        }
    } catch {} finally {
        submitting.value = false;
    }
}

const isSessionTerminated = ref(false);

function handleSessionTerminated(message = 'This live arena session has been closed or deleted by the host.') {
    if (isSessionTerminated.value) {
return;
}

    isSessionTerminated.value = true;

    if (pollInterval) {
clearInterval(pollInterval);
}

    stopStudentTimer();
    const echo = getEcho();

    if (echo) {
        echo.leave(`game.room.${props.room.pin}`);
    }

    toast.error(message);
    router.visit('/student/games');
}

async function fetchRoomStatus() {
    if (isSessionTerminated.value) {
return;
}

    try {
        const res = await fetch(`/student/games/rooms/${props.room.pin}/status`, {
            headers: { Accept: 'application/json' },
        });

        if (res.status === 404 || res.status === 410) {
            handleSessionTerminated();

            return;
        }

        if (!res.ok) {
return;
}

        const data = await res.json();

        if (data.deleted) {
            handleSessionTerminated();

            return;
        }

        if (!data.ok) {
return;
}

        if (data.status) {
roomStatus.value = data.status;
}

        const nextCardIndex = typeof data.current_card_index === 'number'
            ? data.current_card_index
            : currentCardIndex.value;
        const roundChanged = nextCardIndex !== currentCardIndex.value;

        if (roundChanged) {
            currentCardIndex.value = nextCardIndex;
            currentFirstSolver.value = data.first_solver || null;
            selectedAnswer.value = null;
            textAnswer.value = '';
            feedbackMessage.value = '';
            feedbackType.value = '';
            feedbackPoints.value = 0;

            if (data.card) {
                currentCard.value = data.card;
            }
        } else if (data.card && !currentCard.value) {
            currentCard.value = data.card;
        }

        if (data.first_solver && !currentFirstSolver.value) {
            currentFirstSolver.value = data.first_solver;
        }

        if (data.participant) {
            score.value = data.participant.score;
            streak.value = data.participant.streak;
            lastAnsweredCardIndex.value = data.participant.last_answered_card_index;
        }

        if (data.leaderboard) {
            leaderboard.value = data.leaderboard;
        }
    } catch {}
}

onMounted(() => {
    if (roomStatus.value === 'active') {
        startStudentTimer();
    }

    // Start periodic polling fallback
    pollInterval = setInterval(fetchRoomStatus, 2500);

    const echo = getEcho((page.props as any).broadcasting?.reverb);

    if (echo) {
        presenceChannel = echo.join(`game.room.${props.room.pin}`)
            .listen('.game.room.updated', (e: any) => {
                if (e.deleted) {
                    handleSessionTerminated();

                    return;
                }

                if (e.status) {
roomStatus.value = e.status;
}

                const cardIndexChanged = typeof e.current_card_index === 'number'
                    && e.current_card_index !== currentCardIndex.value;

                if (cardIndexChanged) {
                    currentCardIndex.value = e.current_card_index;
                    // Reset interaction state for new card
                    selectedAnswer.value = null;
                    textAnswer.value = '';
                    feedbackMessage.value = '';
                    feedbackType.value = '';
                    feedbackPoints.value = 0;
                    // The broadcast card follows the host's order. Fetch status
                    // so this player receives the card from their own order.
                    void fetchRoomStatus();
                }

                currentFirstSolver.value = e.first_solver || null;

                if (e.leaderboard) {
                    leaderboard.value = e.leaderboard;
                }
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
            });
    }
});

onUnmounted(() => {
    stopStudentTimer();

    if (pollInterval) {
        clearInterval(pollInterval);
    }

    const echo = getEcho();

    if (echo) {
        echo.leave(`game.room.${props.room.pin}`);
    }
});
</script>
