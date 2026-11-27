<template>
    <Head title="Quizzes" />

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
                Practice
            </p>
            <h1
                class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                Quizzes
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                {{ completedCount }} of {{ quizzes.length }} completed ·
                {{ avgScore }}% average
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
            <div class="flex items-center gap-1.5">
                <button
                    v-for="t in statusTabs"
                    :key="t.value"
                    @click="statusTab = t.value"
                    class="rounded-full px-3.5 py-2 text-xs font-bold transition-all"
                    :style="
                        statusTab === t.value
                            ? { background: '#1F2937', color: '#fff' }
                            : {
                                  background: 'var(--gl-surface)',
                                  border: '1px solid var(--gl-border)',
                                  color: 'var(--gl-text-secondary)',
                              }
                    "
                >
                    {{ t.label }}
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-3">
        <!-- LIST (2/3) -->
        <div class="xl:col-span-2">
            <!-- Empty State -->
            <div
                v-if="!filtered.length"
                class="gl-glow-card flex flex-col items-center justify-center px-8 py-16 text-center"
            >
                <div
                    class="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl"
                    style="
                        background: linear-gradient(
                            135deg,
                            rgba(59, 130, 246, 0.15),
                            rgba(124, 58, 237, 0.1)
                        );
                    "
                >
                    <FileQuestion
                        class="h-10 w-10"
                        style="color: var(--gl-primary)"
                        :stroke-width="1.5"
                    />
                </div>
                <h3
                    class="text-lg font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    {{
                        quizzes.length
                            ? 'No matches'
                            : 'No Quizzes Available Yet'
                    }}
                </h3>
                <p
                    class="mt-2 max-w-md text-sm"
                    style="color: var(--gl-text-secondary)"
                >
                    {{
                        quizzes.length
                            ? 'Try a different search or filter.'
                            : "Your teacher hasn't published any quizzes yet. Check back later to continue earning XP."
                    }}
                </p>
                <div
                    v-if="!quizzes.length"
                    class="mt-6 flex max-w-sm items-center gap-3 rounded-xl p-4"
                    style="
                        background: var(--gl-surface-2);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <Gift
                        class="h-5 w-5 shrink-0"
                        style="color: var(--gl-accent)"
                        :stroke-width="2"
                    />
                    <p
                        class="text-left text-xs"
                        style="color: var(--gl-text-secondary)"
                    >
                        Complete quizzes to earn XP, unlock badges, increase
                        your level, and collect rewards.
                    </p>
                </div>
            </div>

            <!-- Quiz Cards -->
            <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div
                    v-for="quiz in filtered"
                    :key="quiz.id"
                    class="flex flex-col rounded-2xl p-5 transition-all hover:-translate-y-0.5"
                    :style="{
                        background: 'rgba(59,130,246,0.05)',
                        border: '1px solid rgba(59,130,246,0.16)',
                        opacity: quiz.status === 'submitted' ? 0.85 : 1,
                    }"
                >
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                :style="{
                                    background: statusGradient(quiz.status),
                                }"
                            >
                                <component
                                    :is="statusIcon(quiz.status)"
                                    class="h-5 w-5 text-white"
                                    :stroke-width="2"
                                />
                            </div>
                            <div class="min-w-0">
                                <h3
                                    class="truncate text-sm font-bold"
                                    style="color: var(--gl-text-primary)"
                                >
                                    {{ quiz.title }}
                                </h3>
                                <p
                                    v-if="quiz.description"
                                    class="mt-0.5 truncate text-xs"
                                    style="color: var(--gl-text-muted)"
                                >
                                    {{ quiz.description }}
                                </p>
                            </div>
                        </div>
                        <span
                            class="inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold"
                            :style="{
                                background: statusBadgeBg(quiz.status),
                                color: statusBadgeText(quiz.status),
                            }"
                        >
                            <component
                                :is="statusBadgeIcon(quiz.status)"
                                class="h-3 w-3"
                                :stroke-width="2"
                            />
                            {{ statusLabel(quiz.status) }}
                        </span>
                    </div>

                    <div
                        class="mb-3 flex flex-wrap gap-x-4 gap-y-1.5 text-xs"
                        style="color: var(--gl-text-muted)"
                    >
                        <span class="flex items-center gap-1.5">
                            <FileQuestion
                                class="h-3.5 w-3.5"
                                :stroke-width="2"
                                style="color: var(--gl-primary)"
                            />
                            {{ quiz.questions_count }} questions
                        </span>
                        <span
                            v-if="quiz.time_limit_minutes"
                            class="flex items-center gap-1.5"
                        >
                            <Clock
                                class="h-3.5 w-3.5"
                                :stroke-width="2"
                                style="color: var(--gl-secondary)"
                            />
                            {{ quiz.time_limit_minutes }} min
                        </span>
                        <span
                            class="flex items-center gap-1.5 font-semibold"
                            style="color: var(--gl-accent)"
                        >
                            <Zap class="h-3.5 w-3.5" :stroke-width="2" />
                            {{ xpForQuiz(quiz) }} XP
                        </span>
                    </div>

                    <div
                        v-if="quiz.status === 'submitted'"
                        class="mb-3 flex items-center gap-3"
                    >
                        <div
                            class="h-2 flex-1 overflow-hidden rounded-full"
                            style="background: var(--gl-surface-2)"
                        >
                            <div
                                class="h-full rounded-full"
                                :style="{
                                    width: scorePct(quiz) + '%',
                                    background:
                                        'linear-gradient(90deg, var(--gl-success), #34D399)',
                                }"
                            ></div>
                        </div>
                        <span
                            class="text-xs font-extrabold tabular-nums"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ quiz.score }}/{{ quiz.total_points }}
                        </span>
                    </div>

                    <div class="mt-auto flex justify-end">
                        <Link
                            v-if="quiz.status !== 'submitted'"
                            :href="`/student/quizzes/${quiz.id}/take`"
                            :title="
                                quiz.status === 'in_progress'
                                    ? 'Continue quiz'
                                    : 'Start quiz'
                            "
                            class="flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-xs font-bold text-white transition-all hover:scale-105"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                                box-shadow: 0 0 12px var(--gl-primary-glow);
                            "
                        >
                            <PlayCircle class="h-4 w-4" :stroke-width="2" />
                            {{
                                quiz.status === 'in_progress'
                                    ? 'Continue'
                                    : 'Start'
                            }}
                        </Link>
                        <Link
                            v-else
                            :href="`/student/quizzes/${quiz.attempt_id}/result`"
                            title="View result"
                            class="flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-xs font-bold transition-all hover:scale-105"
                            style="
                                background: var(--gl-surface);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            <Eye class="h-4 w-4" :stroke-width="2" /> Result
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- HUB (1/3) -->
        <div class="space-y-5">
            <div class="gl-glow-card p-5">
                <div class="mb-3 flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl"
                        style="
                            background: linear-gradient(
                                135deg,
                                var(--gl-primary),
                                var(--gl-secondary)
                            );
                        "
                    >
                        <Star class="h-5 w-5 text-white" :stroke-width="2" />
                    </div>
                    <div>
                        <p
                            class="text-sm font-bold"
                            style="color: var(--gl-text-primary)"
                        >
                            Quiz Mastery
                        </p>
                        <p
                            class="text-[11px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Your quiz stats
                        </p>
                    </div>
                </div>
                <div class="mb-1 flex justify-between text-xs">
                    <span style="color: var(--gl-text-secondary)"
                        >Level {{ userLevel }}</span
                    >
                    <span style="color: var(--gl-text-muted)"
                        >{{ xpProgress }} / {{ xpNextLevel }} XP</span
                    >
                </div>
                <div class="gl-xp-bar">
                    <div
                        class="gl-xp-bar-fill"
                        :style="{ width: xpProgressPct + '%' }"
                    ></div>
                </div>
                <div class="mt-3 space-y-1.5">
                    <div class="flex justify-between text-xs">
                        <span style="color: var(--gl-text-muted)"
                            >Quizzes completed</span
                        >
                        <span
                            class="font-bold tabular-nums"
                            style="color: var(--gl-text-primary)"
                            >{{ completedCount }}</span
                        >
                    </div>
                    <div class="flex justify-between text-xs">
                        <span style="color: var(--gl-text-muted)"
                            >Average score</span
                        >
                        <span
                            class="font-bold tabular-nums"
                            style="color: var(--gl-success)"
                            >{{ avgScore }}%</span
                        >
                    </div>
                </div>
            </div>

            <div
                class="gl-glow-card p-5 text-center"
                style="border-color: rgba(251, 191, 36, 0.2)"
            >
                <div
                    class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl"
                    style="
                        background: linear-gradient(
                            135deg,
                            rgba(251, 191, 36, 0.2),
                            rgba(251, 191, 36, 0.05)
                        );
                    "
                >
                    <Gift
                        class="h-7 w-7"
                        style="color: var(--gl-accent)"
                        :stroke-width="1.5"
                    />
                </div>
                <p
                    class="text-sm font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    Next Reward
                </p>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    Earn {{ xpNextLevel - xpProgress }} more XP to unlock a
                    badge
                </p>
            </div>

            <div class="gl-glow-card p-5">
                <p
                    class="mb-3 text-sm font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    Tips & Rewards
                </p>
                <div class="space-y-2.5">
                    <div class="flex items-center gap-2.5 text-xs">
                        <Trophy
                            class="h-4 w-4 shrink-0"
                            style="color: var(--gl-accent)"
                            :stroke-width="2"
                        />
                        <span style="color: var(--gl-text-secondary)"
                            >Complete quizzes to level up</span
                        >
                    </div>
                    <div class="flex items-center gap-2.5 text-xs">
                        <Award
                            class="h-4 w-4 shrink-0"
                            style="color: var(--gl-primary)"
                            :stroke-width="2"
                        />
                        <span style="color: var(--gl-text-secondary)"
                            >Earn badges for perfect scores</span
                        >
                    </div>
                    <div class="flex items-center gap-2.5 text-xs">
                        <Zap
                            class="h-4 w-4 shrink-0"
                            style="color: var(--gl-secondary)"
                            :stroke-width="2"
                        />
                        <span style="color: var(--gl-text-secondary)"
                            >Maintain your streak</span
                        >
                    </div>
                    <div class="flex items-center gap-2.5 text-xs">
                        <Shield
                            class="h-4 w-4 shrink-0"
                            style="color: var(--gl-success)"
                            :stroke-width="2"
                        />
                        <span style="color: var(--gl-text-secondary)"
                            >Unlock exclusive rewards</span
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    FileQuestion,
    Clock,
    PlayCircle,
    Eye,
    Trophy,
    Star,
    Gift,
    Zap,
    Award,
    Shield,
    CheckCircle2,
    Circle,
    Play,
    Search,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { xpForPercentage, maxPossibleXp } from '@/lib/xp';

const props = defineProps<{ quizzes: any[] }>();

const page = usePage();
const flash = computed(() => (page.props as any).flash);
const user = (page.props as any).auth?.user;
const totalPoints = computed(() => user?.total_points ?? 0);

const query = ref('');
const statusTab = ref('all');
const statusTabs = [
    { value: 'all', label: 'All' },
    { value: 'not_started', label: 'Ready' },
    { value: 'in_progress', label: 'Started' },
    { value: 'submitted', label: 'Done' },
];

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();

    return props.quizzes.filter((quiz) => {
        if (statusTab.value !== 'all' && quiz.status !== statusTab.value) {
return false;
}

        if (q && !(quiz.title || '').toLowerCase().includes(q)) {
return false;
}

        return true;
    });
});

const completedCount = computed(
    () => props.quizzes.filter((q) => q.status === 'submitted').length,
);

const avgScore = computed(() => {
    const submitted = props.quizzes.filter(
        (q) => q.status === 'submitted' && q.total_points > 0,
    );

    if (!submitted.length) {
return 0;
}

    const pcts = submitted.map((q) => (q.score / q.total_points) * 100);

    return Math.round(pcts.reduce((a, b) => a + b, 0) / pcts.length);
});

const userLevel = computed(() => Math.floor(totalPoints.value / 100) + 1);
const xpNextLevel = computed(() => userLevel.value * 100);
const xpProgress = computed(() => totalPoints.value % 100);
const xpProgressPct = computed(() =>
    Math.min(100, Math.round((xpProgress.value / xpNextLevel.value) * 100)),
);

function scorePct(quiz: any): number {
    if (!quiz.total_points) {
return 0;
}

    return Math.round((quiz.score / quiz.total_points) * 100);
}

function xpForQuiz(quiz: any): number {
    const estimatedPct =
        quiz.status === 'submitted' && quiz.total_points
            ? (quiz.score / quiz.total_points) * 100
            : 0;

    if (quiz.status === 'submitted') {
return xpForPercentage(estimatedPct);
}

    return maxPossibleXp();
}

function statusLabel(status: string): string {
    const labels: Record<string, string> = {
        not_started: 'Ready',
        in_progress: 'In Progress',
        submitted: 'Completed',
    };

    return labels[status] ?? status;
}

function statusIcon(status: string) {
    const icons: Record<string, any> = {
        not_started: Circle,
        in_progress: Play,
        submitted: CheckCircle2,
    };

    return icons[status] ?? Circle;
}

function statusBadgeIcon(status: string) {
    const icons: Record<string, any> = {
        not_started: Circle,
        in_progress: Play,
        submitted: CheckCircle2,
    };

    return icons[status] ?? Circle;
}

function statusBadgeBg(status: string): string {
    const colors: Record<string, string> = {
        not_started: 'rgba(59,130,246,0.12)',
        in_progress: 'rgba(251,191,36,0.12)',
        submitted: 'rgba(16,185,129,0.12)',
    };

    return colors[status] ?? 'rgba(148,163,184,0.12)';
}

function statusBadgeText(status: string): string {
    const colors: Record<string, string> = {
        not_started: '#3B82F6',
        in_progress: '#FBBF24',
        submitted: '#10B981',
    };

    return colors[status] ?? '#94A3B8';
}

function statusGradient(status: string): string {
    const gradients: Record<string, string> = {
        not_started: 'linear-gradient(135deg, #3B82F6, #2563EB)',
        in_progress: 'linear-gradient(135deg, #F59E0B, #D97706)',
        submitted: 'linear-gradient(135deg, #10B981, #059669)',
    };

    return gradients[status] ?? 'linear-gradient(135deg, #64748B, #475569)';
}
</script>
