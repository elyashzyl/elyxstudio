<template>
    <Head :title="`${attempt.quiz.title} — Results`" />

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
                Quiz Result
            </p>
            <h1
                class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                {{ attempt.quiz.title }}
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                Submitted {{ formatDate(attempt.submitted_at) }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <Link
                href="/student/quizzes"
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
                Return to Quizzes
            </Link>
            <Link
                :href="'/student/results#result-Quiz-' + attempt.id"
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
                        :style="{ stroke: scoreColor.fg }"
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
                    {{ attempt.score }}/{{ attempt.total_points }} points
                </p>
                <div class="mt-2.5 flex flex-wrap gap-2">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold"
                        style="
                            background: rgba(16, 185, 129, 0.12);
                            color: var(--gl-success);
                        "
                    >
                        <Check class="h-3.5 w-3.5" :stroke-width="2.5" />
                        {{ correctCount }} correct
                    </span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold"
                        style="
                            background: rgba(239, 68, 68, 0.12);
                            color: var(--gl-danger);
                        "
                    >
                        <X class="h-3.5 w-3.5" :stroke-width="2.5" />
                        {{ incorrectCount }} incorrect
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Question Cards -->
    <div
        v-for="(question, index) in questionsWithAnswers"
        :key="question.id"
        class="mb-3 overflow-hidden rounded-2xl"
        style="
            background: var(--gl-surface);
            border: 1px solid var(--gl-border);
        "
    >
        <div class="flex items-center gap-2.5 px-5 py-3">
            <div
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
                :style="
                    question.studentAnswer?.is_correct
                        ? 'background: rgba(16,185,129,0.14);'
                        : 'background: rgba(239,68,68,0.14);'
                "
            >
                <Check
                    v-if="question.studentAnswer?.is_correct"
                    class="h-4 w-4"
                    :stroke-width="2.5"
                    style="color: var(--gl-success)"
                />
                <X
                    v-else
                    class="h-4 w-4"
                    :stroke-width="2.5"
                    style="color: var(--gl-danger)"
                />
            </div>
            <span
                class="text-sm font-bold"
                style="color: var(--gl-text-primary)"
                >Question {{ index + 1 }}</span
            >
            <span
                class="ml-auto rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                style="
                    background: var(--gl-surface-2);
                    color: var(--gl-text-muted);
                "
                >{{ question.points }} pt{{
                    question.points !== 1 ? 's' : ''
                }}</span
            >
        </div>
        <div class="px-5 pb-5">
            <p
                class="mb-3 text-sm font-medium"
                style="color: var(--gl-text-primary)"
            >
                {{ question.question_text }}
            </p>

            <template v-if="question.type === 'identification'">
                <div
                    class="space-y-2 rounded-xl p-4 text-sm"
                    style="
                        background: var(--gl-surface-2);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <p style="color: var(--gl-text-secondary)">
                        Your answer:
                        <span
                            class="font-semibold"
                            :style="{
                                color: question.studentAnswer?.is_correct
                                    ? 'var(--gl-success)'
                                    : 'var(--gl-danger)',
                            }"
                            >{{ question.studentAnswer?.answer_text ?? '—' }}</span
                        >
                    </p>
                    <p
                        v-if="
                            !question.studentAnswer?.is_correct &&
                            question.options?.[0]?.option_text
                        "
                        style="color: var(--gl-text-secondary)"
                    >
                        Correct answer:
                        <span
                            class="font-semibold"
                            style="color: var(--gl-success)"
                        >
                            {{ question.options[0].option_text }}
                        </span>
                    </p>
                    <p
                        v-if="question.studentAnswer?.feedback"
                        class="text-xs font-semibold"
                        style="color: var(--gl-primary)"
                    >
                        Feedback: {{ question.studentAnswer.feedback }}
                    </p>
                </div>
            </template>

            <template v-else-if="question.type === 'enumeration'">
                <div
                    class="space-y-2 rounded-xl p-4 text-sm"
                    style="
                        background: var(--gl-surface-2);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <p style="color: var(--gl-text-secondary)">
                        Your answer:
                        <span
                            class="font-medium"
                            style="color: var(--gl-text-primary)"
                            >{{
                                question.studentAnswer?.answer_text?.replace(
                                    /\n/g,
                                    ', ',
                                ) ?? '—'
                            }}</span
                        >
                    </p>
                    <p
                        v-if="question.studentAnswer?.points_awarded !== null && question.studentAnswer?.points_awarded !== undefined"
                        class="text-xs font-semibold"
                        style="color: var(--gl-text-secondary)"
                    >
                        Score: <span class="font-bold" style="color: var(--gl-primary)">{{ question.studentAnswer.points_awarded }} / {{ question.points }} pts</span>
                    </p>
                    <p
                        v-if="question.studentAnswer?.feedback"
                        class="text-xs font-semibold"
                        style="color: var(--gl-primary)"
                    >
                        Feedback: {{ question.studentAnswer.feedback }}
                    </p>
                    <div v-if="question.options?.length" class="text-xs">
                        <span
                            class="font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Expected / Accepted answers:</span
                        >
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            <span
                                v-for="(opt, oi) in question.options"
                                :key="oi"
                                class="rounded-md px-2 py-0.5 text-xs font-medium"
                                style="
                                    background: var(--gl-success-bg);
                                    color: var(--gl-success);
                                    border: 1px solid rgba(16, 185, 129, 0.2);
                                "
                            >
                                {{ opt.option_text }}
                            </span>
                        </div>
                    </div>
                </div>
            </template>

            <template v-else-if="question.type === 'matching'">
                <div class="mt-2 space-y-2 text-sm">
                    <div
                        v-for="pair in question.matching_pairs"
                        :key="pair.id"
                        class="rounded-xl border p-3"
                        style="
                            background: var(--gl-surface-2);
                            border-color: var(--gl-border);
                        "
                    >
                        <div
                            class="flex items-center justify-between gap-3 text-xs"
                        >
                            <span
                                class="font-medium"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ pair.left_text }}
                            </span>
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="font-semibold"
                                    :style="{
                                        color: getMatchCorrect(question, pair)
                                            ? 'var(--gl-success)'
                                            : 'var(--gl-danger)',
                                    }"
                                >
                                    {{ getMatchAnswer(question, pair) || '—' }}
                                </span>
                                <Check
                                    v-if="getMatchCorrect(question, pair)"
                                    class="h-3.5 w-3.5"
                                    style="color: var(--gl-success)"
                                    :stroke-width="2"
                                />
                                <X
                                    v-else
                                    class="h-3.5 w-3.5"
                                    style="color: var(--gl-danger)"
                                    :stroke-width="2"
                                />
                            </div>
                        </div>
                        <div
                            v-if="!getMatchCorrect(question, pair)"
                            class="mt-1 text-xs"
                            style="color: var(--gl-text-muted)"
                        >
                            Correct match:
                            <span
                                class="font-semibold"
                                style="color: var(--gl-success)"
                            >
                                {{ pair.right_text }}
                            </span>
                        </div>
                    </div>
                    <p
                        v-if="question.studentAnswer?.feedback"
                        class="text-xs font-semibold"
                        style="color: var(--gl-primary)"
                    >
                        Feedback: {{ question.studentAnswer.feedback }}
                    </p>
                </div>
            </template>

            <template v-else-if="question.type === 'essay'">
                <div class="space-y-3">
                    <div
                        v-if="question.criteria"
                        class="rounded-xl p-3 text-xs"
                        style="
                            background: var(--gl-surface-2);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        <p
                            class="font-semibold"
                            style="color: var(--gl-primary)"
                        >
                            Grading Criteria:
                        </p>
                        <p
                            class="mt-1 leading-relaxed whitespace-pre-line"
                            style="color: var(--gl-text-secondary)"
                        >
                            {{ question.criteria }}
                        </p>
                    </div>

                    <div
                        class="rounded-xl p-4 text-sm"
                        style="
                            background: var(--gl-surface-2);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        <div class="mb-2 flex items-center justify-between">
                            <span
                                class="text-xs font-semibold"
                                style="color: var(--gl-text-secondary)"
                            >
                                Your Essay Response:
                            </span>
                            <span
                                class="text-xs font-bold"
                                :style="{
                                    color: (question.studentAnswer?.points_awarded ?? 0) > 0
                                        ? 'var(--gl-success)'
                                        : 'var(--gl-danger)',
                                }"
                            >
                                {{ question.studentAnswer?.points_awarded ?? 0 }} / {{ question.points }} pts
                            </span>
                        </div>
                        <p
                            class="text-xs leading-relaxed whitespace-pre-line"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ question.studentAnswer?.answer_text || '—' }}
                        </p>
                    </div>

                    <div
                        v-if="question.studentAnswer?.feedback"
                        class="rounded-xl p-3 text-xs"
                        style="
                            background: linear-gradient(
                                135deg,
                                rgba(59, 130, 246, 0.08),
                                rgba(124, 58, 237, 0.08)
                            );
                            border: 1px solid rgba(59, 130, 246, 0.2);
                        "
                    >
                        <span class="font-bold" style="color: var(--gl-primary)">
                            AI Evaluation & Feedback:
                        </span>
                        <p class="mt-1 leading-relaxed" style="color: var(--gl-text-primary)">
                            {{ question.studentAnswer.feedback }}
                        </p>
                    </div>
                </div>
            </template>

            <div v-else class="space-y-2">
                <div
                    v-for="option in question.options"
                    :key="option.id"
                    class="flex items-center gap-3 rounded-xl border px-4 py-3 text-sm transition-colors"
                    :class="optionStyle(option, question)"
                >
                    <div
                        v-if="option.is_correct"
                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full"
                        style="background: var(--gl-success-bg)"
                    >
                        <Check
                            class="h-3.5 w-3.5"
                            :stroke-width="2.5"
                            style="color: var(--gl-success)"
                        />
                    </div>
                    <div
                        v-else-if="isSelected(option, question)"
                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full"
                        style="background: var(--gl-danger-bg)"
                    >
                        <X
                            class="h-3.5 w-3.5"
                            :stroke-width="2.5"
                            style="color: var(--gl-danger)"
                        />
                    </div>
                    <div
                        v-else
                        class="h-5 w-5 shrink-0 rounded-full"
                        style="border: 2px solid var(--gl-border)"
                    ></div>
                    <span
                        :class="option.is_correct ? 'font-medium' : ''"
                        style="color: var(--gl-text-primary)"
                        >{{ option.option_text }}</span
                    >
                    <span
                        v-if="option.is_correct"
                        class="ml-auto shrink-0 text-xs font-bold"
                        style="color: var(--gl-success)"
                        >Correct answer</span
                    >
                    <span
                        v-else-if="isSelected(option, question)"
                        class="ml-auto shrink-0 text-xs font-bold"
                        style="color: var(--gl-danger)"
                        >Your answer</span
                    >
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Return Actions -->
    <div class="mt-8 mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl p-4 sm:p-5" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);">
        <div>
            <h4 class="text-sm font-bold" style="color: var(--gl-text-primary)">Ready to continue?</h4>
            <p class="text-xs mt-0.5" style="color: var(--gl-text-muted)">Your quiz attempt has been recorded and submitted.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <Link
                href="/student/quizzes"
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
                <span>Return to Quizzes</span>
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
import { ArrowLeft, Check, X } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{ attempt: any }>();

const page = usePage();
const flash = computed(() => (page.props as any).flash);

const questionsWithAnswers = computed(() => {
    return props.attempt.quiz.questions.map((q: any) => {
        const answer = props.attempt.answers.find(
            (a: any) => a.question_id === q.id,
        );

        return { ...q, studentAnswer: answer || null };
    });
});

const correctCount = computed(
    () =>
        questionsWithAnswers.value.filter(
            (q: any) => q.studentAnswer?.is_correct,
        ).length,
);
const incorrectCount = computed(
    () =>
        questionsWithAnswers.value.filter(
            (q: any) => q.studentAnswer && !q.studentAnswer.is_correct,
        ).length,
);

const percentage = computed(() => {
    if (!props.attempt.total_points) {
return 0;
}

    return Math.round((props.attempt.score / props.attempt.total_points) * 100);
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

function isSelected(option: any, question: any): boolean {
    return question.studentAnswer?.quiz_option_id === option.id;
}

function optionStyle(option: any, question: any): Record<string, boolean> {
    const selected = isSelected(option, question);

    return {
        'border-[var(--gl-success)] bg-[var(--gl-success-bg)]':
            option.is_correct,
        'border-[var(--gl-danger)] bg-[var(--gl-danger-bg)]':
            !option.is_correct && selected,
        'border-[var(--gl-border)]': !option.is_correct && !selected,
    };
}

function getMatchAnswer(question: any, pair: any): string {
    const ma = question.studentAnswer?.matching_answers;

    if (!ma || typeof ma !== 'object') {
return '';
}

    return ma[pair.id] ?? '';
}

function getMatchCorrect(question: any, pair: any): boolean {
    const answered = getMatchAnswer(question, pair);

    if (!answered) {
return false;
}

    return answered.trim().toLowerCase() === (pair.right_text || '').trim().toLowerCase();
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
