<template>
    <Head title="Leaderboard" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p
                class="text-xs font-semibold tracking-widest uppercase"
                style="color: var(--gl-text-muted)"
            >
                Leaderboard
            </p>
            <h1
                class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                {{ section_name }}
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                {{ mode === 'week' ? 'Last 7 days' : 'All time' }} ·
                {{ ranked.length }} students
            </p>
        </div>
        <div
            class="flex items-center gap-1 rounded-full p-1"
            style="
                background: var(--gl-surface-2);
                border: 1px solid var(--gl-border);
            "
        >
            <button
                @click="mode = 'week'"
                class="rounded-full px-4 py-1.5 text-xs font-bold transition-all"
                :style="
                    mode === 'week'
                        ? {
                              background:
                                  'linear-gradient(135deg, #FBBF24, #F59E0B)',
                              color: '#1F2937',
                              boxShadow: '0 2px 10px rgba(251,191,36,0.4)',
                          }
                        : { color: 'var(--gl-text-muted)' }
                "
            >
                Weekly
            </button>
            <button
                @click="mode = 'all'"
                class="rounded-full px-4 py-1.5 text-xs font-bold transition-all"
                :style="
                    mode === 'all'
                        ? {
                              background:
                                  'linear-gradient(135deg, #FBBF24, #F59E0B)',
                              color: '#1F2937',
                              boxShadow: '0 2px 10px rgba(251,191,36,0.4)',
                          }
                        : { color: 'var(--gl-text-muted)' }
                "
            >
                All time
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <!-- BOARD (2/3) -->
        <div class="space-y-5 xl:col-span-2">
            <!-- Podium -->
            <div
                v-if="podium.length === 3"
                class="overflow-hidden rounded-2xl p-6 sm:p-8"
                style="
                    background:
                        linear-gradient(
                            180deg,
                            rgba(251, 191, 36, 0.1),
                            rgba(251, 191, 36, 0.02)
                        ),
                        var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <div class="grid grid-cols-3 items-end gap-2 sm:gap-4">
                    <PodiumSpot
                        :entry="podium[0]"
                        :me="podium[0].id === currentUserId"
                        metal="silver"
                        :points="pts(podium[0])"
                    />
                    <PodiumSpot
                        :entry="podium[1]"
                        :me="podium[1].id === currentUserId"
                        metal="gold"
                        :points="pts(podium[1])"
                    />
                    <PodiumSpot
                        :entry="podium[2]"
                        :me="podium[2].id === currentUserId"
                        metal="bronze"
                        :points="pts(podium[2])"
                    />
                </div>
            </div>

            <!-- Empty -->
            <div
                v-if="!ranked.length"
                class="gl-glow-card flex flex-col items-center justify-center px-8 py-16 text-center"
            >
                <div
                    class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl"
                    style="background: var(--gl-surface-2)"
                >
                    <Trophy
                        class="h-7 w-7"
                        style="color: var(--gl-text-muted)"
                        :stroke-width="1.5"
                    />
                </div>
                <p
                    class="text-sm font-medium"
                    style="color: var(--gl-text-secondary)"
                >
                    Leaderboard Coming Soon
                </p>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    Complete activities to become one of the first ranked
                    learners in your section.
                </p>
            </div>

            <!-- Rows -->
            <div v-else class="space-y-2.5">
                <div
                    v-for="e in ranked"
                    :key="e.id"
                    class="flex items-center gap-3 rounded-2xl px-4 py-3 transition-all hover:-translate-y-px"
                    :style="
                        e.rank === 1
                            ? {
                                  background: 'rgba(251,191,36,0.1)',
                                  border: '1px solid rgba(251,191,36,0.35)',
                                  boxShadow: '0 2px 14px rgba(251,191,36,0.12)',
                              }
                            : e.id === currentUserId
                              ? {
                                    background: 'rgba(59,130,246,0.06)',
                                    border: '1px solid rgba(59,130,246,0.3)',
                                }
                              : {
                                    background: 'var(--gl-surface)',
                                    border: '1px solid var(--gl-border)',
                                }
                    "
                >
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-extrabold"
                        :style="
                            e.rank <= 3
                                ? { background: '#1F2937', color: '#FBBF24' }
                                : {
                                      background: 'var(--gl-surface-2)',
                                      color: 'var(--gl-text-muted)',
                                      border: '1px solid var(--gl-border)',
                                  }
                        "
                    >
                        {{ e.rank }}
                    </span>
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full text-sm font-bold text-white"
                        :style="{ background: avatarBg(e.id) }"
                    >
                        <img
                            v-if="e.avatar"
                            :src="e.avatar"
                            alt=""
                            class="h-full w-full object-cover"
                        />
                        <template v-else>{{ (e.name || '?').charAt(0).toUpperCase() }}</template>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-sm font-semibold flex items-center gap-1.5"
                            style="color: var(--gl-text-primary)"
                        >
                            <span>{{ e.id === currentUserId ? 'You' : e.name }}</span>
                            <span
                                v-if="e.equipped_badge"
                                class="inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-[11px] font-bold bg-amber-500/10 text-amber-600 dark:text-amber-300 border border-amber-400/30"
                                :title="badgeLabel(e.equipped_badge)"
                            >
                                {{ badgeIcon(e.equipped_badge) }}
                            </span>
                            <span
                                v-if="e.id === currentUserId"
                                class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                style="
                                    background: rgba(59, 130, 246, 0.14);
                                    color: var(--gl-primary);
                                "
                                >YOU</span
                            >
                        </p>
                        <p
                            class="truncate text-[11px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Lv. {{ levelOf(e) }} · {{ titleOf(e) }}
                        </p>
                    </div>
                    <div
                        class="hidden shrink-0 items-center gap-1.5 sm:flex"
                        style="min-width: 150px"
                    >
                        <Medal
                            class="h-4 w-4 shrink-0"
                            :style="{
                                color:
                                    e.rank <= 3
                                        ? '#FBBF24'
                                        : 'var(--gl-text-muted)',
                            }"
                            :stroke-width="2"
                        />
                        <span
                            class="truncate text-xs font-medium"
                            :style="{
                                color:
                                    e.rank <= 3
                                        ? 'var(--gl-text-primary)'
                                        : 'var(--gl-text-muted)',
                            }"
                        >
                            {{ titleOf(e) }}
                        </span>
                    </div>
                    <span
                        class="shrink-0 text-sm font-extrabold tabular-nums"
                        style="color: var(--gl-text-primary)"
                        >XP {{ pts(e).toLocaleString() }}</span
                    >
                </div>
            </div>
        </div>

        <!-- SIDE (1/3) -->
        <div class="space-y-5">
            <!-- Profile card -->
            <div
                class="overflow-hidden rounded-2xl p-5 text-center"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-full text-xl font-bold text-white"
                    :style="{
                        background:
                            'linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))',
                        boxShadow: '0 0 18px var(--gl-primary-glow)',
                    }"
                >
                    {{ (student_name || '?').charAt(0).toUpperCase() }}
                </div>
                <p
                    class="mt-2 truncate text-sm font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    {{ student_name }}
                </p>
                <p class="text-[11px]" style="color: var(--gl-text-muted)">
                    {{ section_name }}
                </p>
                <div class="mt-3 grid grid-cols-3 gap-2">
                    <div
                        class="rounded-xl p-2"
                        style="background: var(--gl-surface-2)"
                    >
                        <p
                            class="text-sm font-extrabold tabular-nums"
                            style="color: var(--gl-text-primary)"
                        >
                            #{{ myRank }}
                        </p>
                        <p
                            class="text-[10px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Rank
                        </p>
                    </div>
                    <div
                        class="rounded-xl p-2"
                        style="background: var(--gl-surface-2)"
                    >
                        <p
                            class="text-sm font-extrabold tabular-nums"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ student_total_points.toLocaleString() }}
                        </p>
                        <p
                            class="text-[10px]"
                            style="color: var(--gl-text-muted)"
                        >
                            XP
                        </p>
                    </div>
                    <div
                        class="rounded-xl p-2"
                        style="background: var(--gl-surface-2)"
                    >
                        <p
                            class="text-sm font-extrabold tabular-nums"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ studentLevel }}
                        </p>
                        <p
                            class="text-[10px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Level
                        </p>
                    </div>
                </div>
                <div
                    class="mt-3 h-2 overflow-hidden rounded-full"
                    style="background: var(--gl-surface-2)"
                >
                    <div
                        class="h-full rounded-full"
                        :style="{
                            width: xpProgressPct + '%',
                            background:
                                'linear-gradient(90deg, var(--gl-primary), var(--gl-secondary))',
                        }"
                    ></div>
                </div>
                <p
                    class="mt-1.5 text-[11px]"
                    style="color: var(--gl-text-muted)"
                >
                    {{ xpProgress }} / {{ xpNextLevel }} XP to Level
                    {{ studentLevel + 1 }} · {{ studentLevelTitle }}
                </p>
            </div>

            <!-- How to climb -->
            <div
                class="rounded-2xl p-5"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <h3
                    class="mb-3 text-sm font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    How to climb
                </h3>
                <div class="space-y-3">
                    <div
                        v-for="(s, i) in steps"
                        :key="s.title"
                        class="relative flex items-start gap-3"
                    >
                        <div
                            v-if="i < steps.length - 1"
                            class="absolute top-10 left-[18px] h-[calc(100%-28px)] w-px"
                            style="background: var(--gl-border)"
                        ></div>
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                            :style="{ background: s.bg }"
                        >
                            <component
                                :is="s.icon"
                                class="h-4 w-4"
                                :style="{ color: s.color }"
                                :stroke-width="2"
                            />
                        </div>
                        <div class="min-w-0 pt-0.5">
                            <p
                                class="text-xs font-bold"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ s.title }}
                            </p>
                            <p
                                class="mt-0.5 text-[11px] leading-relaxed"
                                style="color: var(--gl-text-secondary)"
                            >
                                {{ s.text }}
                            </p>
                        </div>
                    </div>
                </div>
                <Link
                    href="/student/quizzes"
                    class="mt-4 flex items-center justify-center gap-1.5 rounded-xl px-4 py-2.5 text-xs font-semibold transition-all hover:scale-[1.02]"
                    style="
                        background: linear-gradient(
                            135deg,
                            var(--gl-primary),
                            var(--gl-secondary)
                        );
                        color: #fff;
                        box-shadow: 0 0 12px var(--gl-primary-glow);
                    "
                >
                    Earn XP now
                    <ArrowRight class="h-3.5 w-3.5" :stroke-width="2" />
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, usePage, usePoll } from '@inertiajs/vue3';
import { Trophy, Medal, Zap, Award, Gift, ArrowRight } from '@lucide/vue';
import { computed, ref } from 'vue';
import PodiumSpot from '@/components/PodiumSpot.vue';
import { badgeIcon as sharedBadgeIcon, badgeLabel as sharedBadgeLabel } from '@/lib/badges';
import {
    levelForPoints,
    titleForLevel,
    progressForLevel,
    nextLevelPoints,
    progressPct,
} from '@/lib/levels';

const props = defineProps<{
    entries: any[];
    section_name: string;
    student_name: string;
    student_total_points: number;
    student_rank: number;
}>();

const page = usePage();

// Keep names and profile photos fresh when classmates update their profile.
usePoll(15000, { only: ['entries'] });
const currentUserId = computed(
    () => (page.props.auth as any)?.user?.id ?? null,
);
const mode = ref<'week' | 'all'>('all');

function pts(e: any): number {
    return mode.value === 'week'
        ? (e?.week_points ?? 0)
        : (e?.total_points ?? 0);
}

const ranked = computed(() => {
    return [...(props.entries ?? [])]
        .sort((a, b) => pts(b) - pts(a))
        .map((e, i) => ({ ...e, rank: i + 1 }));
});

const podium = computed(() => {
    const top = ranked.value.slice(0, 3);

    if (top.length < 3) {
return top;
}

    return [top[1], top[0], top[2]];
});

function badgeIcon(b?: string): string {
    return b ? sharedBadgeIcon(b) : '';
}

function badgeLabel(b?: string): string {
    return b ? sharedBadgeLabel(b) : '';
}

const myRank = computed(() => {
    if (mode.value === 'all') {
return props.student_rank;
}

    const i = ranked.value.findIndex((e) => e.id === currentUserId.value);

    return i >= 0 ? i + 1 : '–';
});

const studentLevel = computed(() => levelForPoints(props.student_total_points));
const studentLevelTitle = computed(() => titleForLevel(studentLevel.value));
const xpNextLevel = computed(() => nextLevelPoints(props.student_total_points));
const xpProgress = computed(() => progressForLevel(props.student_total_points));
const xpProgressPct = computed(() => progressPct(props.student_total_points));

function levelOf(entry: any): number {
    return levelForPoints(entry?.total_points ?? 0);
}

function titleOf(entry: any): string {
    return titleForLevel(levelOf(entry));
}

const AVATAR_BG = [
    'linear-gradient(135deg, #3B82F6, #7C3AED)',
    'linear-gradient(135deg, #10B981, #06B6D4)',
    'linear-gradient(135deg, #F59E0B, #EF4444)',
    'linear-gradient(135deg, #EC4899, #8B5CF6)',
    'linear-gradient(135deg, #14B8A6, #3B82F6)',
];

function avatarBg(id: number): string {
    return AVATAR_BG[Math.abs(id ?? 0) % AVATAR_BG.length];
}

const steps = [
    {
        title: 'Earn XP',
        text: 'Every graded activity earns XP based on your score.',
        icon: Zap,
        bg: 'rgba(251,191,36,0.14)',
        color: '#FBBF24',
    },
    {
        title: 'Level up',
        text: 'Each 100 XP takes you one level higher with a new title.',
        icon: Award,
        bg: 'rgba(59,130,246,0.12)',
        color: '#3B82F6',
    },
    {
        title: 'Climb the ranks',
        text: 'Pass your classmates to earn the gold medal spotlight.',
        icon: Trophy,
        bg: 'rgba(16,185,129,0.12)',
        color: '#10B981',
    },
    {
        title: 'Redeem rewards',
        text: 'Each new level unlocks a one-time reward on your dashboard.',
        icon: Gift,
        bg: 'rgba(124,58,237,0.12)',
        color: '#7C3AED',
    },
];
</script>
