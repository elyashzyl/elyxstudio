<template>
    <Head title="My Results" />

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
                Progress
            </p>
            <h1
                class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                My Results
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                {{ submitted.length }} submitted · {{ avgScore }}% average
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
                    v-for="t in typeTabs"
                    :key="t"
                    @click="activeTab = t"
                    class="rounded-full px-3.5 py-2 text-xs font-bold transition-all"
                    :style="
                        activeTab === t
                            ? { background: '#1F2937', color: '#fff' }
                            : {
                                  background: 'var(--gl-surface)',
                                  border: '1px solid var(--gl-border)',
                                  color: 'var(--gl-text-secondary)',
                              }
                    "
                >
                    {{ t }}
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-3">
        <!-- LEFT (2/3) -->
        <div class="space-y-5 xl:col-span-2">
            <!-- Pastel stat cards -->
            <div class="grid grid-cols-3 gap-4">
                <div
                    class="rounded-2xl p-4 text-center"
                    style="
                        background: rgba(16, 185, 129, 0.1);
                        border: 1px solid rgba(16, 185, 129, 0.2);
                    "
                >
                    <div class="relative mx-auto h-12 w-12">
                        <svg
                            viewBox="0 0 36 36"
                            class="h-full w-full -rotate-90"
                        >
                            <circle
                                cx="18"
                                cy="18"
                                r="15.915"
                                fill="none"
                                stroke-width="4"
                                style="stroke: rgba(16, 185, 129, 0.2)"
                            />
                            <circle
                                cx="18"
                                cy="18"
                                r="15.915"
                                fill="none"
                                stroke-width="4"
                                stroke-linecap="round"
                                pathLength="100"
                                :stroke-dasharray="`${submitPct} 100`"
                                style="stroke: #10b981"
                            />
                        </svg>
                        <ClipboardCheck
                            class="absolute inset-0 m-auto h-4 w-4"
                            style="color: #10b981"
                            :stroke-width="2"
                        />
                    </div>
                    <p
                        class="mt-1.5 text-xl font-extrabold tabular-nums"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ submitted.length }}
                    </p>
                    <p
                        class="text-[11px]"
                        style="color: var(--gl-text-secondary)"
                    >
                        Submitted
                    </p>
                </div>
                <div
                    class="rounded-2xl p-4 text-center"
                    style="
                        background: rgba(251, 146, 60, 0.1);
                        border: 1px solid rgba(251, 146, 60, 0.2);
                    "
                >
                    <div class="relative mx-auto h-12 w-12">
                        <svg
                            viewBox="0 0 36 36"
                            class="h-full w-full -rotate-90"
                        >
                            <circle
                                cx="18"
                                cy="18"
                                r="15.915"
                                fill="none"
                                stroke-width="4"
                                style="stroke: rgba(251, 146, 60, 0.2)"
                            />
                            <circle
                                cx="18"
                                cy="18"
                                r="15.915"
                                fill="none"
                                stroke-width="4"
                                stroke-linecap="round"
                                pathLength="100"
                                :stroke-dasharray="`${avgScore} 100`"
                                style="stroke: #fb923c"
                            />
                        </svg>
                        <Target
                            class="absolute inset-0 m-auto h-4 w-4"
                            style="color: #fb923c"
                            :stroke-width="2"
                        />
                    </div>
                    <p
                        class="mt-1.5 text-xl font-extrabold tabular-nums"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ avgScore }}%
                    </p>
                    <p
                        class="text-[11px]"
                        style="color: var(--gl-text-secondary)"
                    >
                        Average
                    </p>
                </div>
                <div
                    class="rounded-2xl p-4 text-center"
                    style="
                        background: rgba(192, 132, 252, 0.1);
                        border: 1px solid rgba(192, 132, 252, 0.22);
                    "
                >
                    <div class="relative mx-auto h-12 w-12">
                        <svg
                            viewBox="0 0 36 36"
                            class="h-full w-full -rotate-90"
                        >
                            <circle
                                cx="18"
                                cy="18"
                                r="15.915"
                                fill="none"
                                stroke-width="4"
                                style="stroke: rgba(192, 132, 252, 0.2)"
                            />
                            <circle
                                cx="18"
                                cy="18"
                                r="15.915"
                                fill="none"
                                stroke-width="4"
                                stroke-linecap="round"
                                pathLength="100"
                                :stroke-dasharray="`${highestScore} 100`"
                                style="stroke: #c084fc"
                            />
                        </svg>
                        <Trophy
                            class="absolute inset-0 m-auto h-4 w-4"
                            style="color: #c084fc"
                            :stroke-width="2"
                        />
                    </div>
                    <p
                        class="mt-1.5 text-xl font-extrabold tabular-nums"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ highestScore }}%
                    </p>
                    <p
                        class="text-[11px]"
                        style="color: var(--gl-text-secondary)"
                    >
                        Best score
                    </p>
                </div>
            </div>

            <!-- Week banner -->
            <div
                class="flex flex-wrap items-center gap-4 rounded-2xl p-5"
                style="
                    background: rgba(192, 132, 252, 0.08);
                    border: 1px solid rgba(192, 132, 252, 0.2);
                "
            >
                <div class="min-w-0 flex-1">
                    <p
                        class="flex items-center gap-1.5 text-xs font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        <Flame
                            class="h-3.5 w-3.5"
                            style="color: var(--gl-accent)"
                            :stroke-width="2"
                        />
                        This week
                    </p>
                    <p
                        class="mt-1 text-sm font-extrabold"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ weekCount }} submitted
                    </p>
                    <div
                        class="mt-2 h-2 overflow-hidden rounded-full"
                        style="background: var(--gl-surface-2)"
                    >
                        <div
                            class="h-full rounded-full transition-all"
                            :style="{
                                width: Math.min(100, weekCount * 20) + '%',
                                background:
                                    'linear-gradient(90deg, #C084FC, #7C3AED)',
                            }"
                        ></div>
                    </div>
                </div>
                <Link
                    href="/student/leaderboard"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full transition-all hover:scale-105"
                    style="
                        background: var(--gl-surface);
                        border: 1px solid var(--gl-border);
                        color: var(--gl-text-primary);
                    "
                    title="Leaderboard"
                >
                    <ArrowRight class="h-4 w-4" :stroke-width="2" />
                </Link>
            </div>

            <!-- Level banner (kept) -->
            <div
                class="gl-glow-card p-5"
                style="border-color: rgba(251, 191, 36, 0.2)"
            >
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-accent),
                                    #f59e0b
                                );
                            "
                        >
                            <Award
                                class="h-6 w-6 text-white"
                                :stroke-width="2"
                            />
                        </div>
                        <div>
                            <p
                                class="text-base font-bold"
                                style="color: var(--gl-text-primary)"
                            >
                                Level {{ userLevel }}
                            </p>
                            <p
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                            >
                                {{ xpProgress }} / {{ xpNextLevel }} XP
                            </p>
                        </div>
                    </div>
                    <div class="min-w-[160px] flex-1">
                        <div
                            class="gl-xp-bar"
                            style="height: 10px; border-radius: 5px"
                        >
                            <div
                                class="gl-xp-bar-fill"
                                style="border-radius: 5px"
                                :style="{ width: xpProgressPct + '%' }"
                            ></div>
                        </div>
                    </div>
                    <div
                        class="flex items-center gap-2.5 rounded-xl px-3.5 py-2"
                        style="
                            background: var(--gl-surface-2);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        <Gift
                            class="h-4 w-4"
                            style="color: var(--gl-accent)"
                            :stroke-width="2"
                        />
                        <div>
                            <p
                                class="text-[11px] font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                Next Reward
                            </p>
                            <p
                                class="text-[11px]"
                                style="color: var(--gl-text-muted)"
                            >
                                Level {{ userLevel + 1 }} Chest
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Result cards -->
            <div
                v-if="!filtered.length"
                class="gl-glow-card flex flex-col items-center justify-center px-8 py-14 text-center"
            >
                <div
                    class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl"
                    style="
                        background: linear-gradient(
                            135deg,
                            rgba(59, 130, 246, 0.15),
                            rgba(124, 58, 237, 0.1)
                        );
                    "
                >
                    <Trophy
                        class="h-8 w-8"
                        style="color: var(--gl-primary)"
                        :stroke-width="1.5"
                    />
                </div>
                <h3
                    class="text-base font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    {{ activities.length ? 'No matches' : 'No Results Yet' }}
                </h3>
                <p
                    class="mt-1.5 max-w-md text-xs"
                    style="color: var(--gl-text-secondary)"
                >
                    {{
                        activities.length
                            ? 'Try a different search or filter.'
                            : 'Complete assignments and quizzes to build your learning profile.'
                    }}
                </p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="item in filtered"
                    :key="item.id + item.type"
                    :id="'result-' + item.type + '-' + item.id"
                    class="flex flex-wrap items-center gap-4 rounded-2xl p-4 transition-all hover:-translate-y-px"
                    :style="{
                        background: tint(item.type),
                        border: '1px solid ' + tintBorder(item.type),
                    }"
                >
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                        :style="{ background: typeColor(item.type).bg }"
                    >
                        <component
                            :is="typeIcon(item.type)"
                            class="h-5 w-5"
                            :stroke-width="2"
                            :style="{ color: typeColor(item.type).fg }"
                        />
                    </div>
                    <div class="min-w-0 flex-1" style="flex-basis: 180px">
                        <div class="flex flex-wrap items-center gap-2">
                            <p
                                class="truncate text-sm font-bold"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ item.title }}
                            </p>
                            <span
                                class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                :style="{
                                    background: typeColor(item.type).badge,
                                    color: typeColor(item.type).fg,
                                }"
                            >
                                {{ item.type }}
                            </span>
                            <span
                                v-if="item.is_live"
                                class="shrink-0 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-black bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30"
                            >
                                <Trophy class="h-3 w-3 text-amber-500" />
                                Live Arena · Rank #{{ item.rank }}
                            </span>
                            <span
                                v-if="item.attempt_number"
                                class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-secondary);
                                "
                            >
                                Attempt #{{ item.attempt_number }}
                            </span>
                            <span
                                v-if="item.is_recorded"
                                class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                style="
                                    background: rgba(16, 185, 129, 0.15);
                                    color: var(--gl-success);
                                    border: 1px solid rgba(16, 185, 129, 0.3);
                                "
                            >
                                Recorded Grade
                            </span>
                        </div>
                        <p
                            class="mt-0.5 truncate text-[11px]"
                            style="color: var(--gl-text-muted)"
                        >
                            <span v-if="item.teacher_name"
                                >{{ item.teacher_name }} · </span
                            >{{ formatDate(item.submitted_at) }}
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <template v-if="item.status === 'submitted'">
                            <span
                                class="hidden text-xs font-bold tabular-nums sm:block"
                                :style="{ color: scoreColor(item.percentage) }"
                            >
                                {{ item.score }}/{{ item.total }}
                            </span>
                            <div class="relative h-12 w-12 shrink-0">
                                <svg
                                    viewBox="0 0 36 36"
                                    class="h-full w-full -rotate-90"
                                >
                                    <circle
                                        cx="18"
                                        cy="18"
                                        r="15.915"
                                        fill="none"
                                        stroke-width="4"
                                        style="stroke: var(--gl-surface-2)"
                                    />
                                    <circle
                                        cx="18"
                                        cy="18"
                                        r="15.915"
                                        fill="none"
                                        stroke-width="4"
                                        stroke-linecap="round"
                                        pathLength="100"
                                        :stroke-dasharray="`${item.percentage} 100`"
                                        :style="{
                                            stroke: ringColor(item.percentage),
                                        }"
                                    />
                                </svg>
                                <span
                                    class="absolute inset-0 flex items-center justify-center text-[10px] font-extrabold tabular-nums"
                                    style="color: var(--gl-text-primary)"
                                    >{{ item.percentage }}</span
                                >
                            </div>
                            <Link
                                :href="item.resultUrl"
                                class="flex h-9 w-9 items-center justify-center rounded-xl transition-all hover:scale-105"
                                style="
                                    background: var(--gl-surface);
                                    color: var(--gl-text-secondary);
                                    border: 1px solid var(--gl-border);
                                "
                                :title="'View ' + item.type + ' details'"
                            >
                                <Eye class="h-4 w-4" :stroke-width="2" />
                            </Link>
                        </template>
                        <template v-else>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold"
                                :style="{
                                    background: rowStatusBg(item.status),
                                    color: rowStatusText(item.status),
                                }"
                            >
                                {{ rowStatusLabel(item.status) }}
                            </span>
                            <Link
                                v-if="item.takeUrl"
                                :href="item.takeUrl"
                                class="flex items-center gap-1 rounded-xl px-3.5 py-2 text-xs font-bold text-white transition-all hover:scale-105"
                                style="
                                    background: linear-gradient(
                                        135deg,
                                        var(--gl-primary),
                                        var(--gl-secondary)
                                    );
                                    box-shadow: 0 0 12px var(--gl-primary-glow);
                                "
                                :title="'Take ' + item.type"
                            >
                                Take
                                <ArrowRight
                                    class="h-3.5 w-3.5"
                                    :stroke-width="2.5"
                                />
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT (1/3) -->
        <div class="space-y-5">
            <!-- Week strip -->
            <div class="gl-glow-card p-5">
                <div class="mb-3 flex items-center justify-between">
                    <h3
                        class="text-sm font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        This Week
                    </h3>
                    <span
                        class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-muted);
                        "
                        >{{ weekCount }} done</span
                    >
                </div>
                <div class="grid grid-cols-5 gap-1.5">
                    <div
                        v-for="(d, i) in weekStrip"
                        :key="i"
                        class="flex flex-col items-center gap-1"
                    >
                        <span
                            class="flex h-9 w-full items-center justify-center rounded-xl text-xs font-extrabold tabular-nums"
                            :style="
                                d.today
                                    ? { background: '#1F2937', color: '#fff' }
                                    : {
                                          background: 'var(--gl-surface-2)',
                                          color: 'var(--gl-text-secondary)',
                                      }
                            "
                        >
                            {{ d.num }}
                        </span>
                        <span
                            class="text-[9px] font-semibold"
                            style="color: var(--gl-text-muted)"
                            >{{ d.dow }}</span
                        >
                        <span
                            class="h-1.5 w-1.5 rounded-full"
                            :style="{
                                background:
                                    d.count > 0
                                        ? 'var(--gl-success)'
                                        : 'transparent',
                            }"
                        ></span>
                    </div>
                </div>
            </div>

            <!-- Up next -->
            <div class="gl-glow-card p-5">
                <div class="mb-3 flex items-center justify-between">
                    <h3
                        class="text-sm font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        Up Next
                    </h3>
                    <span
                        class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-muted);
                        "
                        >{{ upNext.length }} pending</span
                    >
                </div>
                <div v-if="!upNext.length" class="py-3 text-center">
                    <p
                        class="text-xs font-medium"
                        style="color: var(--gl-text-secondary)"
                    >
                        All caught up!
                    </p>
                    <p
                        class="mt-0.5 text-[11px]"
                        style="color: var(--gl-text-muted)"
                    >
                        Nothing left to take.
                    </p>
                </div>
                <div v-else class="space-y-2">
                    <div
                        v-for="u in upNext.slice(0, 5)"
                        :key="u.id + u.type"
                        class="flex items-center gap-2.5 rounded-xl p-2.5"
                        style="background: var(--gl-surface-2)"
                    >
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                            :style="{ background: typeColor(u.type).bg }"
                        >
                            <component
                                :is="typeIcon(u.type)"
                                class="h-4 w-4"
                                :stroke-width="2"
                                :style="{ color: typeColor(u.type).fg }"
                            />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-xs font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ u.title }}
                            </p>
                            <p
                                class="text-[10px]"
                                style="color: var(--gl-text-muted)"
                            >
                                {{ u.type }} · {{ rowStatusLabel(u.status) }}
                            </p>
                        </div>
                        <Link
                            v-if="u.takeUrl"
                            :href="u.takeUrl"
                            title="Take now"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full transition-all hover:scale-105"
                            style="
                                background: var(--gl-surface);
                                border: 1px solid var(--gl-border);
                                color: var(--gl-text-secondary);
                            "
                        >
                            <ArrowRight class="h-3.5 w-3.5" :stroke-width="2" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Mastery nudge -->
            <div
                class="flex items-center gap-3 rounded-2xl p-4"
                style="
                    background: rgba(16, 185, 129, 0.07);
                    border: 1px solid rgba(16, 185, 129, 0.18);
                "
            >
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                    style="background: rgba(16, 185, 129, 0.14)"
                >
                    <Star
                        class="h-4 w-4"
                        style="color: var(--gl-success)"
                        :stroke-width="2"
                    />
                </div>
                <p
                    class="text-xs leading-relaxed"
                    style="color: var(--gl-text-secondary)"
                >
                    <span
                        class="font-bold"
                        style="color: var(--gl-text-primary)"
                        >{{ gradableCount }} above 60%.</span
                    >
                    Review past work to push the rest higher!
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Trophy,
    Target,
    Award,
    Gift,
    ClipboardCheck,
    Eye,
    FileQuestion,
    FileText,
    ClipboardList,
    FlaskConical,
    Gamepad2,
    Star,
    Search,
    ArrowRight,
    Flame,
} from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps<{ activities: any[] }>();

const page = usePage();
const flash = computed(() => (page.props as any).flash);
const user = (page.props as any).auth?.user;
const totalPoints = computed(() => user?.total_points ?? 0);

const rk = 'student_results_' + (typeof window !== 'undefined' ? window.location.pathname : '');
const query = ref(typeof window !== 'undefined' ? (sessionStorage.getItem(rk + '_query') ?? '') : '');
const activeTab = ref(typeof window !== 'undefined' ? (sessionStorage.getItem(rk + '_tab') ?? 'All') : 'All');
const typeTabs = ['All', 'Quiz', 'Exam', 'Seatwork', 'Practical', 'Game'];

watch([query, activeTab], () => {
    if (typeof window !== 'undefined') {
        sessionStorage.setItem(rk + '_query', query.value);
        sessionStorage.setItem(rk + '_tab', activeTab.value);
    }
});

onMounted(() => {
    if (typeof window !== 'undefined' && window.location.hash) {
        try {
            const el = document.querySelector(window.location.hash);

            if (el) {
                setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'center' }), 120);
            }
        } catch {}
    }
});

const submitted = computed(() =>
    props.activities.filter((i) => i.status === 'submitted'),
);

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();

    return props.activities.filter((i) => {
        if (activeTab.value !== 'All' && i.type !== activeTab.value) {
return false;
}

        if (q && !(i.title || '').toLowerCase().includes(q)) {
return false;
}

        return true;
    });
});

const upNext = computed(() =>
    props.activities.filter((i) => i.status !== 'submitted' && i.takeUrl),
);

const avgScore = computed(() => {
    if (!submitted.value.length) {
return 0;
}

    return Math.round(
        submitted.value.reduce((a, i) => a + i.percentage, 0) /
            submitted.value.length,
    );
});

const highestScore = computed(() => {
    if (!submitted.value.length) {
return 0;
}

    return Math.max(...submitted.value.map((i) => i.percentage));
});

const gradableCount = computed(
    () => submitted.value.filter((i) => i.percentage >= 60).length,
);

const submitPct = computed(() => {
    const total = props.activities.length;

    if (!total) {
return 0;
}

    return Math.round((submitted.value.length / total) * 100);
});

const weekCount = computed(() => {
    const now = new Date();
    const start = new Date(
        now.getFullYear(),
        now.getMonth(),
        now.getDate() - now.getDay(),
    );

    return submitted.value.filter(
        (i) => i.submitted_at && new Date(i.submitted_at) >= start,
    ).length;
});

const weekStrip = computed(() => {
    const now = new Date();
    const dows = ['S', 'M', 'T', 'W', 'T'];
    const days: { num: number; dow: string; today: boolean; count: number }[] =
        [];
    const monday = new Date(
        now.getFullYear(),
        now.getMonth(),
        now.getDate() - ((now.getDay() + 6) % 7),
    );

    for (let i = 0; i < 5; i++) {
        const d = new Date(
            monday.getFullYear(),
            monday.getMonth(),
            monday.getDate() + i,
        );
        const key = `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`;
        const count = submitted.value.filter((a) => {
            if (!a.submitted_at) {
return false;
}

            const s = new Date(a.submitted_at);

            return `${s.getFullYear()}-${s.getMonth()}-${s.getDate()}` === key;
        }).length;
        days.push({
            num: d.getDate(),
            dow: dows[i],
            today: d.toDateString() === now.toDateString(),
            count,
        });
    }

    return days;
});

const userLevel = computed(() => Math.floor(totalPoints.value / 100) + 1);
const xpNextLevel = computed(() => userLevel.value * 100);
const xpProgress = computed(() => totalPoints.value % 100);
const xpProgressPct = computed(() =>
    Math.min(100, Math.round((xpProgress.value / xpNextLevel.value) * 100)),
);

function typeIcon(type: string) {
    const icons: Record<string, any> = {
        Quiz: FileQuestion,
        Exam: FileText,
        Seatwork: ClipboardList,
        Practical: FlaskConical,
        Game: Gamepad2,
    };

    return icons[type] ?? ClipboardCheck;
}

function typeColor(type: string) {
    const colors: Record<string, { bg: string; fg: string; badge: string }> = {
        Quiz: {
            bg: 'rgba(59,130,246,0.12)',
            fg: '#3B82F6',
            badge: 'rgba(59,130,246,0.12)',
        },
        Exam: {
            bg: 'rgba(239,68,68,0.12)',
            fg: '#EF4444',
            badge: 'rgba(239,68,68,0.12)',
        },
        Seatwork: {
            bg: 'rgba(16,185,129,0.12)',
            fg: '#10B981',
            badge: 'rgba(16,185,129,0.12)',
        },
        Practical: {
            bg: 'rgba(124,58,237,0.12)',
            fg: '#7C3AED',
            badge: 'rgba(124,58,237,0.12)',
        },
        Game: {
            bg: 'rgba(34,211,238,0.12)',
            fg: '#22D3EE',
            badge: 'rgba(34,211,238,0.12)',
        },
    };

    return (
        colors[type] ?? {
            bg: 'rgba(148,163,184,0.12)',
            fg: '#94A3B8',
            badge: 'rgba(148,163,184,0.12)',
        }
    );
}

function tint(type: string): string {
    const map: Record<string, string> = {
        Quiz: 'rgba(59,130,246,0.05)',
        Exam: 'rgba(239,68,68,0.05)',
        Seatwork: 'rgba(16,185,129,0.05)',
        Practical: 'rgba(124,58,237,0.05)',
        Game: 'rgba(34,211,238,0.05)',
    };

    return map[type] ?? 'var(--gl-surface)';
}

function tintBorder(type: string): string {
    const map: Record<string, string> = {
        Quiz: 'rgba(59,130,246,0.16)',
        Exam: 'rgba(239,68,68,0.16)',
        Seatwork: 'rgba(16,185,129,0.16)',
        Practical: 'rgba(124,58,237,0.16)',
        Game: 'rgba(34,211,238,0.16)',
    };

    return map[type] ?? 'var(--gl-border)';
}

function scoreColor(pct: number): string {
    if (pct >= 80) {
return 'var(--gl-success)';
}

    if (pct >= 60) {
return 'var(--gl-accent)';
}

    return 'var(--gl-danger)';
}

function ringColor(pct: number): string {
    if (pct >= 80) {
return 'var(--gl-success)';
}

    if (pct >= 60) {
return 'var(--gl-accent)';
}

    return 'var(--gl-danger)';
}

function formatDate(value: string): string {
    if (!value) {
return '—';
}

    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function rowStatusLabel(status: string): string {
    const labels: Record<string, string> = {
        not_started: 'Not started',
        in_progress: 'In progress',
        closed: 'Closed',
    };

    return labels[status] ?? status;
}

function rowStatusBg(status: string): string {
    const colors: Record<string, string> = {
        not_started: 'rgba(59,130,246,0.12)',
        in_progress: 'rgba(251,191,36,0.12)',
        closed: 'rgba(148,163,184,0.12)',
    };

    return colors[status] ?? 'rgba(148,163,184,0.12)';
}

function rowStatusText(status: string): string {
    const colors: Record<string, string> = {
        not_started: '#3B82F6',
        in_progress: '#FBBF24',
        closed: '#94A3B8',
    };

    return colors[status] ?? '#94A3B8';
}
</script>
