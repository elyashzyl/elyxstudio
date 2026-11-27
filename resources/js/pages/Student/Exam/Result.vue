<template>
    <Head :title="'Result - ' + attempt.exam.title" />

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
                Exam Result
            </p>
            <h1
                class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                {{ attempt.exam.title }}
            </h1>
            <p
                v-if="attempt.exam.instructions"
                class="mt-1 max-w-lg truncate text-xs"
                style="color: var(--gl-text-muted)"
            >
                {{ attempt.exam.instructions }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <Link
                href="/student/exams"
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
                Return to Exams
            </Link>
            <Link
                :href="'/student/results#result-Exam-' + attempt.id"
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
                    {{ score }}/{{ maxScore }} points
                </p>
                <p class="mt-0.5 text-xs" style="color: var(--gl-text-muted)">
                    {{ attempt.exam.sections?.length ?? 0 }} sections
                </p>
            </div>
        </div>
    </div>

    <div
        v-for="(sec, si) in attempt.exam.sections"
        :key="sec.id"
        class="mb-4 overflow-hidden rounded-2xl"
        style="
            background: var(--gl-surface);
            border: 1px solid var(--gl-border);
        "
    >
        <div
            class="px-5 py-3.5"
            style="border-bottom: 1px solid var(--gl-border)"
        >
            <div class="flex items-center gap-2">
                <h3
                    class="text-sm font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    {{ sec.title }}
                </h3>
                <span
                    class="rounded-full px-2.5 py-0.5 text-[10px] font-bold capitalize"
                    style="
                        background: rgba(124, 58, 237, 0.12);
                        color: var(--gl-secondary);
                    "
                    >{{ sec.section_type }}</span
                >
            </div>
        </div>

        <template
            v-if="sec.section_type === 'questions' && sec.questions.length"
        >
            <div
                v-for="(q, qi) in sec.questions"
                :key="q.id"
                class="border-b px-5 py-4"
                style="border-color: var(--gl-border)"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1">
                        <p
                            class="text-sm font-medium"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ qi + 1 }}. {{ q.question_text }}
                            <span
                                class="text-xs font-normal"
                                style="color: var(--gl-text-muted)"
                                >({{ q.points }} pt{{
                                    q.points > 1 ? 's' : ''
                                }})</span
                            >
                        </p>

                        <template
                            v-if="
                                q.type === 'multiple_choice' ||
                                q.type === 'true_false'
                            "
                        >
                            <div class="mt-2 space-y-1">
                                <div
                                    v-for="opt in q.options"
                                    :key="opt.id"
                                    class="flex items-center gap-1.5 text-sm"
                                    :style="{
                                        color: opt.is_correct
                                            ? 'var(--gl-success)'
                                            : isSelected(
                                                    attempt,
                                                    q.id,
                                                    opt.id,
                                                ) && !opt.is_correct
                                              ? 'var(--gl-danger)'
                                              : 'var(--gl-text-secondary)',
                                    }"
                                >
                                    <CheckCircle
                                        v-if="opt.is_correct"
                                        class="h-3.5 w-3.5 shrink-0"
                                        :stroke-width="2"
                                    />
                                    <XCircle
                                        v-else-if="
                                            isSelected(attempt, q.id, opt.id)
                                        "
                                        class="h-3.5 w-3.5 shrink-0"
                                        :stroke-width="2"
                                    />
                                    <span
                                        v-else
                                        class="inline-block h-3.5 w-3.5 shrink-0 rounded-full"
                                        style="
                                            border: 1px solid var(--gl-border);
                                        "
                                    ></span>
                                    {{ opt.option_text }}
                                </div>
                            </div>
                        </template>

                        <template v-if="q.type === 'identification'">
                            <div
                                class="mt-2 rounded-xl p-3 text-sm"
                                style="background: var(--gl-surface-2)"
                            >
                                <p style="color: var(--gl-text-secondary)">
                                    Your answer:
                                    <span
                                        class="font-medium"
                                        :style="{
                                            color: getAnswer(attempt, q.id)
                                                ?.is_correct
                                                ? 'var(--gl-success)'
                                                : 'var(--gl-danger)',
                                        }"
                                        >{{
                                            getAnswer(attempt, q.id)
                                                ?.answer_text ?? '—'
                                        }}</span
                                    >
                                </p>
                            </div>
                        </template>

                        <template v-if="q.type === 'enumeration'">
                            <div
                                class="mt-2 rounded-xl p-3 text-sm"
                                style="background: var(--gl-surface-2)"
                            >
                                <p style="color: var(--gl-text-secondary)">
                                    Your answer:
                                    <span
                                        class="font-medium"
                                        style="color: var(--gl-text-primary)"
                                        >{{
                                            getAnswer(attempt, q.id)
                                                ?.answer_text ?? '—'
                                        }}</span
                                    >
                                </p>
                                <p
                                    class="mt-1 text-xs italic"
                                    style="color: var(--gl-text-muted)"
                                >
                                    Enumeration is manually graded by the
                                    teacher.
                                </p>
                            </div>
                        </template>

                        <template v-if="q.type === 'matching'">
                            <div class="mt-2 grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p
                                        class="mb-1 text-xs font-medium"
                                        style="color: var(--gl-text-muted)"
                                    >
                                        Items
                                    </p>
                                    <div
                                        v-for="pair in q.matching_pairs"
                                        :key="pair.id"
                                        class="mb-1 rounded-lg px-3 py-2"
                                        style="
                                            background: var(--gl-surface-2);
                                            color: var(--gl-text-primary);
                                        "
                                    >
                                        {{ pair.left_text }}
                                    </div>
                                </div>
                                <div>
                                    <p
                                        class="mb-1 text-xs font-medium"
                                        style="color: var(--gl-text-muted)"
                                    >
                                        Your matches
                                    </p>
                                    <div
                                        v-for="pair in q.matching_pairs"
                                        :key="pair.id"
                                        class="mb-1 flex items-center gap-2 rounded-lg px-3 py-2"
                                        :style="{
                                            background:
                                                getAnswer(attempt, q.id)
                                                    ?.matching_answers?.[
                                                    pair.id
                                                ] === pair.right_text
                                                    ? 'var(--gl-success-bg)'
                                                    : 'var(--gl-danger-bg)',
                                        }"
                                    >
                                        <CheckCircle
                                            v-if="
                                                getAnswer(attempt, q.id)
                                                    ?.matching_answers?.[
                                                    pair.id
                                                ] === pair.right_text
                                            "
                                            class="h-3.5 w-3.5 shrink-0"
                                            style="color: var(--gl-success)"
                                            :stroke-width="2"
                                        />
                                        <XCircle
                                            v-else
                                            class="h-3.5 w-3.5 shrink-0"
                                            style="color: var(--gl-danger)"
                                            :stroke-width="2"
                                        />
                                        <span
                                            class="text-xs"
                                            style="
                                                color: var(--gl-text-primary);
                                            "
                                            >{{
                                                getAnswer(attempt, q.id)
                                                    ?.matching_answers?.[
                                                    pair.id
                                                ] ?? '—'
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="shrink-0 text-center">
                        <div
                            class="rounded-xl px-4 py-2"
                            :style="{
                                background: getAnswer(attempt, q.id)?.is_correct
                                    ? 'var(--gl-success-bg)'
                                    : 'var(--gl-danger-bg)',
                            }"
                        >
                            <p
                                class="text-xs font-semibold"
                                :style="{
                                    color: getAnswer(attempt, q.id)?.is_correct
                                        ? 'var(--gl-success)'
                                        : 'var(--gl-danger)',
                                }"
                            >
                                {{
                                    getAnswer(attempt, q.id)?.is_correct
                                        ? '+' + q.points
                                        : '0'
                                }}
                            </p>
                            <p
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                            >
                                / {{ q.points }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template
            v-if="sec.section_type === 'practical' && sec.criteria.length"
        >
            <div class="p-5">
                <div class="grid grid-cols-1 gap-3">
                    <div
                        v-for="c in sec.criteria"
                        :key="c.id"
                        class="rounded-xl p-4"
                        style="
                            background: var(--gl-surface-2);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p
                                    class="text-sm font-medium"
                                    style="color: var(--gl-text-primary)"
                                >
                                    {{ c.criterion_name }}
                                </p>
                                <p
                                    v-if="getSectionComment(attempt, c.id)"
                                    class="mt-0.5 text-xs italic"
                                    style="color: var(--gl-text-secondary)"
                                >
                                    {{ getSectionComment(attempt, c.id) }}
                                </p>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-3 py-1 text-sm font-semibold"
                                :style="{
                                    background:
                                        getSectionScore(attempt, c.id) > 0
                                            ? 'var(--gl-success-bg)'
                                            : 'var(--gl-danger-bg)',
                                    color:
                                        getSectionScore(attempt, c.id) > 0
                                            ? 'var(--gl-success)'
                                            : 'var(--gl-danger)',
                                }"
                            >
                                {{ getSectionScore(attempt, c.id) }} /
                                {{ c.max_points }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Bottom Return Actions -->
    <div class="mt-8 mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl p-4 sm:p-5" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);">
        <div>
            <h4 class="text-sm font-bold" style="color: var(--gl-text-primary)">Exam submission completed</h4>
            <p class="text-xs mt-0.5" style="color: var(--gl-text-muted)">Your exam responses have been evaluated and recorded.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <Link
                href="/student/exams"
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
                <span>Return to Exams</span>
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
import { ArrowLeft, CheckCircle, XCircle } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{ attempt: any }>();

const page = usePage();
const flash = computed(() => (page.props as any).flash);

const maxScore = computed(() => {
    let m = 0;

    for (const sec of props.attempt.exam.sections ?? []) {
        for (const q of sec.questions ?? []) {
m += q.points ?? 0;
}

        for (const c of sec.criteria ?? []) {
m += c.max_points ?? 0;
}
    }

    return m;
});

const derivedScore = computed(() => {
    let s = 0;

    for (const sec of props.attempt.exam.sections ?? []) {
        for (const q of sec.questions ?? []) {
            if (getAnswer(props.attempt, q.id)?.is_correct) {
s += q.points ?? 0;
}
        }

        for (const c of sec.criteria ?? []) {
            s += getSectionScore(props.attempt, c.id);
        }
    }

    return s;
});

const score = computed(() => props.attempt.total_score ?? derivedScore.value);

const percentage = computed(() => {
    if (!maxScore.value) {
return 0;
}

    return Math.round((score.value / maxScore.value) * 100);
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

function getAnswer(attempt: any, questionId: number): any {
    return attempt.answers?.find((a: any) => a.exam_question_id === questionId);
}

function isSelected(
    attempt: any,
    questionId: number,
    optionId: number,
): boolean {
    return getAnswer(attempt, questionId)?.selected_option_id === optionId;
}

function getSectionScore(attempt: any, criterionId: number): number {
    return (
        attempt.section_scores?.find(
            (s: any) => s.exam_criterion_id === criterionId,
        )?.score ?? 0
    );
}

function getSectionComment(attempt: any, criterionId: number): string | null {
    return (
        attempt.section_scores?.find(
            (s: any) => s.exam_criterion_id === criterionId,
        )?.comment ?? null
    );
}
</script>
