<template>
    <Head :title="attempt.practical.title + ' — Results'" />

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

    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p
                class="text-xs font-semibold tracking-widest uppercase"
                style="color: var(--gl-text-muted)"
            >
                Practical Result
            </p>
            <h1
                class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                {{ attempt.practical.title }}
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                Submitted {{ formatDate(attempt.submitted_at) }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <Link
                href="/student/practicals"
                class="flex items-center gap-1.5 rounded-full px-4 py-2 text-xs font-bold text-white transition-all hover:scale-105"
                style="
                    background: linear-gradient(
                        135deg,
                        var(--gl-primary),
                        var(--gl-secondary)
                    );
                    box-shadow: 0 0 12px var(--gl-primary-glow);
                "
            >
                <ArrowLeft class="h-3.5 w-3.5" :stroke-width="2.5" />
                Return to Practicals
            </Link>
            <Link
                :href="'/student/results#result-Practical-' + attempt.id"
                class="flex items-center gap-1.5 rounded-full px-4 py-2 text-xs font-bold transition-all hover:scale-105"
                style="
                    background: var(--gl-surface);
                    color: var(--gl-text-secondary);
                    border: 1px solid var(--gl-border);
                "
            >
                All Results
            </Link>
        </div>
    </div>

    <!-- Submission -->
    <div
        v-if="attempt.submission_text || attempt.submission_file"
        class="gl-glow-card mb-6 overflow-hidden p-0"
    >
        <div class="border-b px-5 py-4" style="border-color: var(--gl-border)">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-lg"
                    style="
                        background: linear-gradient(
                            135deg,
                            var(--gl-primary),
                            #2563eb
                        );
                    "
                >
                    <Terminal
                        class="h-4.5 w-4.5 text-white"
                        :stroke-width="2"
                    />
                </div>
                <h3
                    class="text-sm font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    Your Submission
                </h3>
            </div>
        </div>
        <div v-if="attempt.submission_text" class="p-5">
            <div
                class="overflow-hidden rounded-xl"
                style="border: 1px solid var(--gl-border)"
            >
                <div
                    class="flex items-center gap-1.5 px-4 py-2.5"
                    style="
                        background: var(--gl-surface-2);
                        border-bottom: 1px solid var(--gl-border);
                    "
                >
                    <span
                        class="h-3 w-3 rounded-full"
                        style="background: #ef4444"
                    ></span>
                    <span
                        class="h-3 w-3 rounded-full"
                        style="background: #fbbf24"
                    ></span>
                    <span
                        class="h-3 w-3 rounded-full"
                        style="background: #10b981"
                    ></span>
                    <span
                        class="ml-3 text-xs"
                        style="color: var(--gl-text-muted)"
                        >submission.txt</span
                    >
                </div>
                <pre
                    class="p-4 font-mono text-sm whitespace-pre-wrap"
                    style="
                        background: #0d1117;
                        color: #e6edf3;
                        max-height: 400px;
                        overflow-y: auto;
                    "
                    >{{ attempt.submission_text }}</pre>
            </div>
        </div>
        <div v-if="attempt.submission_file" class="p-5">
            <img
                :src="attempt.submission_file_url"
                class="max-h-96 rounded-xl"
                style="border: 1px solid var(--gl-border)"
            />
        </div>
    </div>

    <!-- Score Hero -->
    <div
        class="mb-5 rounded-3xl p-6"
        :style="{ background: heroTint, border: '1px solid ' + heroBorder }"
    >
        <div class="flex flex-wrap items-center gap-6">
            <div class="relative h-28 w-28 shrink-0">
                <svg viewBox="0 0 36 36" class="h-full w-full -rotate-90">
                    <circle
                        cx="18"
                        cy="18"
                        r="15.915"
                        fill="none"
                        stroke-width="3.5"
                        style="stroke: var(--gl-surface-2)"
                    />
                    <circle
                        cx="18"
                        cy="18"
                        r="15.915"
                        fill="none"
                        stroke-width="3.5"
                        stroke-linecap="round"
                        pathLength="100"
                        :stroke-dasharray="`${percentage} 100`"
                        :style="{ stroke: ringColor }"
                    />
                </svg>
                <span
                    class="absolute inset-0 flex items-center justify-center text-xl font-extrabold tabular-nums"
                    style="color: var(--gl-text-primary)"
                    >{{ percentage }}%</span
                >
            </div>
            <div class="min-w-0 flex-1">
                <p
                    class="text-lg font-extrabold"
                    style="color: var(--gl-text-primary)"
                >
                    Your Score
                </p>
                <p
                    class="mt-0.5 text-sm tabular-nums"
                    style="color: var(--gl-text-secondary)"
                >
                    {{ attempt.total_score }}
                    <span style="color: var(--gl-text-muted)"
                        >/ {{ totalMax }} points</span
                    >
                </p>
                <div
                    class="mt-2.5 h-2 max-w-xs overflow-hidden rounded-full"
                    style="background: var(--gl-surface-2)"
                >
                    <div
                        class="h-full rounded-full transition-all"
                        :style="{
                            width: percentage + '%',
                            background:
                                'linear-gradient(90deg, var(--gl-success), #34D399)',
                        }"
                    ></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rubric Criteria -->
    <div
        class="rounded-2xl p-5"
        style="
            background: var(--gl-surface);
            border: 1px solid var(--gl-border);
        "
    >
        <div class="mb-4 flex items-center gap-3">
            <div
                class="flex h-9 w-9 items-center justify-center rounded-xl"
                style="background: rgba(251, 191, 36, 0.14)"
            >
                <Award
                    class="h-4 w-4"
                    style="color: var(--gl-accent)"
                    :stroke-width="2"
                />
            </div>
            <h3 class="text-sm font-bold" style="color: var(--gl-text-primary)">
                Scoring Criteria
            </h3>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div
                v-for="(c, ci) in attempt.practical.criteria"
                :key="c.id"
                class="rounded-xl p-4 transition-all"
                :style="{
                    background:
                        getScore(c) > 0
                            ? 'rgba(16,185,129,0.05)'
                            : 'rgba(239,68,68,0.05)',
                    border:
                        getScore(c) > 0
                            ? '1px solid rgba(16,185,129,0.18)'
                            : '1px solid rgba(239,68,68,0.18)',
                }"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
                            :style="{
                                background:
                                    getScore(c) > 0
                                        ? 'var(--gl-success-bg)'
                                        : 'var(--gl-danger-bg)',
                                color:
                                    getScore(c) > 0
                                        ? 'var(--gl-success)'
                                        : 'var(--gl-danger)',
                            }"
                        >
                            {{ ci + 1 }}
                        </div>
                        <div>
                            <p
                                class="text-sm font-medium"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ c.criterion_name }}
                            </p>
                            <p
                                v-if="c.description"
                                class="mt-0.5 text-xs"
                                style="color: var(--gl-text-muted)"
                            >
                                {{ c.description }}
                            </p>
                            <p
                                v-if="getComment(c)"
                                class="mt-1 text-xs italic"
                                style="color: var(--gl-text-secondary)"
                            >
                                {{ getComment(c) }}
                            </p>
                        </div>
                    </div>
                    <span
                        class="shrink-0 rounded-full px-3 py-1 text-sm font-semibold"
                        :style="{
                            background:
                                getScore(c) > 0
                                    ? 'var(--gl-success-bg)'
                                    : 'var(--gl-danger-bg)',
                            color:
                                getScore(c) > 0
                                    ? 'var(--gl-success)'
                                    : 'var(--gl-danger)',
                        }"
                    >
                        {{ getScore(c) }} / {{ c.max_points }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Return Actions -->
    <div class="mt-8 mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl p-4 sm:p-5" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);">
        <div>
            <h4 class="text-sm font-bold" style="color: var(--gl-text-primary)">Practical submission completed</h4>
            <p class="text-xs mt-0.5" style="color: var(--gl-text-muted)">Your teacher will review your submission and grade each criterion.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <Link
                href="/student/practicals"
                class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-xs font-bold text-white transition-all hover:scale-105"
                style="
                    background: linear-gradient(
                        135deg,
                        var(--gl-primary),
                        var(--gl-secondary)
                    );
                    box-shadow: 0 0 14px var(--gl-primary-glow);
                "
            >
                <ArrowLeft class="h-4 w-4" :stroke-width="2" />
                <span>Return to Practicals</span>
            </Link>
            <Link
                href="/student/dashboard"
                class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-xs font-semibold transition-all hover:scale-105"
                style="
                    background: var(--gl-surface);
                    color: var(--gl-text-primary);
                    border: 1px solid var(--gl-border);
                "
            >
                Dashboard
            </Link>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Terminal, Award } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{ attempt: any }>();

const page = usePage();
const flash = computed(() => (page.props as any).flash);

const totalMax = computed(() => {
    return props.attempt.practical.criteria.reduce(
        (s: number, c: any) => s + c.max_points,
        0,
    );
});

const percentage = computed(() => {
    if (!totalMax.value) {
return 0;
}

    return Math.round((props.attempt.total_score / totalMax.value) * 100);
});

const scoreColor = computed(() => {
    if (percentage.value >= 80) {
return { bg: 'var(--gl-success-bg)', fg: 'var(--gl-success)' };
}

    if (percentage.value >= 60) {
return { bg: 'var(--gl-warning-bg)', fg: 'var(--gl-accent)' };
}

    return { bg: 'var(--gl-danger-bg)', fg: 'var(--gl-danger)' };
});

const ringColor = computed(() => {
    if (percentage.value >= 80) {
return 'var(--gl-success)';
}

    if (percentage.value >= 60) {
return 'var(--gl-accent)';
}

    return 'var(--gl-danger)';
});

const heroTint = computed(() => {
    if (percentage.value >= 80) {
return 'rgba(16,185,129,0.07)';
}

    if (percentage.value >= 60) {
return 'rgba(251,191,36,0.07)';
}

    return 'rgba(239,68,68,0.07)';
});

const heroBorder = computed(() => {
    if (percentage.value >= 80) {
return 'rgba(16,185,129,0.2)';
}

    if (percentage.value >= 60) {
return 'rgba(251,191,36,0.22)';
}

    return 'rgba(239,68,68,0.2)';
});

function getScore(criterion: any): number {
    return (
        props.attempt.scores?.find((s: any) => s.criterion_id === criterion.id)
            ?.score ?? 0
    );
}

function getComment(criterion: any): string | null {
    return (
        props.attempt.scores?.find((s: any) => s.criterion_id === criterion.id)
            ?.comment ?? null
    );
}

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<style scoped>
.h-4\.5 {
    height: 1.125rem;
}
.w-4\.5 {
    width: 1.125rem;
}
</style>
