<template>
    <Head :title="`Recheck: ${attempt.student?.name || 'Student'}`" />

    <Link :href="returnUrl" class="mb-5 inline-flex items-center gap-1.5 text-xs font-semibold transition-colors hover:opacity-80" style="color: var(--gl-text-muted)">
        <ArrowLeft class="h-3.5 w-3.5" :stroke-width="2.5" />
        {{ returnUrl.includes('/results') ? 'Back to all results' : 'Back to seatwork' }}
    </Link>

    <!-- Student attempt switcher -->
    <div
        v-if="attemptsList.length > 1"
        class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl p-3 sm:px-4"
        style="background: var(--gl-surface); border: 1px solid var(--gl-border);"
    >
        <div class="flex items-center gap-2 text-xs font-semibold" style="color: var(--gl-text-secondary)">
            <Users class="h-4 w-4" style="color: var(--gl-primary)" />
            <span>Student <span class="font-bold" style="color: var(--gl-text-primary)">{{ currentIndex >= 0 ? currentIndex + 1 : 1 }}</span> of <span class="font-bold" style="color: var(--gl-text-primary)">{{ attemptsList.length }}</span></span>
        </div>

        <div class="flex items-center gap-2">
            <select
                :value="attempt.id"
                @change="goToAttempt(Number(($event.target as HTMLSelectElement).value))"
                class="rounded-xl border px-3 py-1.5 text-xs font-semibold outline-none"
                style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);"
            >
                <option v-for="(att, i) in attemptsList" :key="att.id" :value="att.id">
                    #{{ i + 1 }} · {{ att.student?.name || 'Student' }} ({{ att.score ?? 0 }}/{{ totalPoints }})
                </option>
            </select>

            <button
                type="button"
                :disabled="!prevAttempt"
                @click="prevAttempt && goToAttempt(prevAttempt.id)"
                title="Previous student"
                class="flex h-8 w-8 items-center justify-center rounded-xl border transition-all disabled:cursor-not-allowed disabled:opacity-30 hover:bg-[var(--gl-surface-2)]"
                style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-primary);"
            >
                <ChevronLeft class="h-4 w-4" />
            </button>

            <button
                type="button"
                :disabled="!nextAttempt"
                @click="nextAttempt && goToAttempt(nextAttempt.id)"
                title="Next student"
                class="flex h-8 w-8 items-center justify-center rounded-xl border transition-all disabled:cursor-not-allowed disabled:opacity-30 hover:bg-[var(--gl-surface-2)]"
                style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-primary);"
            >
                <ChevronRight class="h-4 w-4" />
            </button>
        </div>
    </div>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-widest" style="color: var(--gl-text-muted)">Recheck · Seatwork</p>
            <h1 class="mt-1 truncate text-2xl sm:text-3xl font-extrabold tracking-tight" style="color: var(--gl-text-primary)">{{ seatwork.title }}</h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">Student: <span class="font-bold" style="color: var(--gl-text-primary)">{{ attempt.student?.name || 'Student' }}</span> &middot; {{ attempt.student?.grade || '—' }}</p>
        </div>
        <div class="flex items-center gap-3">
            <button
                type="button"
                @click="runAiCheck()"
                :disabled="aiChecking"
                class="inline-flex items-center gap-1.5 rounded-2xl px-4 py-3 text-xs font-bold transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                style="background: rgba(124, 58, 237, 0.12); color: var(--gl-secondary); border: 1px solid rgba(124, 58, 237, 0.3);"
            >
                <LoaderCircle v-if="aiChecking" :size="14" class="animate-spin" />
                <Sparkles v-else :size="14" />
                <span>{{ aiChecking ? 'AI Checking...' : 'AI Check Answers' }}</span>
            </button>
            <div class="flex items-center gap-3 rounded-2xl px-4 py-3" style="background: var(--gl-surface); border: 1px solid var(--gl-border);">
                <div class="relative h-12 w-12">
                    <svg viewBox="0 0 36 36" class="h-full w-full -rotate-90">
                        <circle cx="18" cy="18" r="15.915" fill="none" stroke-width="4" style="stroke: var(--gl-surface-2);" />
                        <circle cx="18" cy="18" r="15.915" fill="none" stroke-width="4" stroke-linecap="round" pathLength="100"
                            :stroke-dasharray="`${Math.round(computedScore / totalPoints * 100) || 0} 100`" :style="{ stroke: scoreColor }" />
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center text-[10px] font-extrabold tabular-nums" style="color: var(--gl-text-primary);">{{ Math.round(computedScore / totalPoints * 100) || 0 }}</span>
                </div>
                <div>
                    <p class="text-xs" style="color: var(--gl-text-muted)">Score</p>
                    <p class="text-base font-extrabold tabular-nums" style="color: var(--gl-text-primary)"><span :style="{ color: scoreColor }">{{ computedScore }}</span> / {{ totalPoints }}</p>
                </div>
            </div>
        </div>
    </div>

    <div v-if="aiBanner" class="mb-4 rounded-xl p-3 text-xs flex items-center justify-between"
        style="background: rgba(124, 58, 237, 0.12); color: var(--gl-secondary); border: 1px solid rgba(124, 58, 237, 0.25);">
        <div class="flex items-center gap-2">
            <Sparkles :size="14" class="shrink-0" />
            <span>{{ aiBanner }}</span>
        </div>
        <button type="button" @click="aiBanner = null" class="font-bold opacity-60 hover:opacity-100 ml-2">Dismiss</button>
    </div>

    <form :key="attempt.id" @submit.prevent="save">
        <div v-for="(q, idx) in questions" :key="q.id" class="mb-3 overflow-hidden rounded-2xl" style="background: var(--gl-surface); border: 1px solid var(--gl-border);">
            <div class="flex items-center gap-2.5 px-5 py-3" style="border-bottom: 1px solid var(--gl-border);">
                <span class="text-[11px] font-extrabold uppercase" style="color: var(--gl-text-muted); letter-spacing: 0.08em;">{{ idx + 1 }}. {{ typeLabel(q.type) }}</span>
                <span class="ml-auto rounded-full px-2.5 py-0.5 text-[11px] font-bold tabular-nums" style="background: var(--gl-surface-2); color: var(--gl-text-muted);">{{ q.points }} pt{{ q.points !== 1 ? 's' : '' }}</span>
            </div>
            <div class="px-5 py-4">
                <p class="mb-3 text-sm font-medium" style="color: var(--gl-text-primary)">{{ q.question_text }}</p>


            <!-- Identification -->
            <div
                v-if="q.type === 'identification'"
                class="mb-3 space-y-1 text-sm"
            >
                <div
                    class="rounded-lg bg-[var(--gl-surface-2)] px-4 py-2"
                    style="color: var(--gl-text-secondary)"
                >
                    <span
                        class="text-xs font-medium"
                        style="color: var(--gl-text-muted)"
                        >Student's answer:</span
                    >
                    <p>{{ studentAnswerText(q) || '—' }}</p>
                </div>
                <div
                    class="rounded-lg bg-[#DCEEE3] px-4 py-2 text-sm"
                    style="color: #2f7a54"
                >
                    <span class="text-xs font-medium">Correct answer:</span>
                    <p>{{ q.options?.[0]?.option_text ?? '—' }}</p>
                </div>
                <div
                    v-if="getAnswerEntry(q.id)?.feedback || answerMap[q.id]?.feedback"
                    class="rounded-lg p-3 text-xs mt-2"
                    style="
                        background: linear-gradient(
                            135deg,
                            rgba(59, 130, 246, 0.08),
                            rgba(124, 58, 237, 0.08)
                        );
                        border: 1px solid rgba(59, 130, 246, 0.2);
                    "
                >
                    <span class="font-bold" style="color: var(--gl-primary)"
                        >AI Evaluation Feedback:</span
                    >
                    <p class="mt-1" style="color: var(--gl-text-primary)">
                        {{ getAnswerEntry(q.id)?.feedback || answerMap[q.id]?.feedback }}
                    </p>
                </div>
            </div>

            <!-- Enumeration -->
            <div v-if="q.type === 'enumeration'" class="mb-3 space-y-1 text-sm">
                <div
                    class="rounded-lg bg-[var(--gl-surface-2)] px-4 py-2"
                    style="color: var(--gl-text-secondary)"
                >
                    <span
                        class="text-xs font-medium"
                        style="color: var(--gl-text-muted)"
                        >Student's answers:</span
                    >
                    <div
                        v-for="(line, li) in (studentAnswerText(q) || '')
                            .split('\n')
                            .filter(Boolean)"
                        :key="li"
                        class="flex items-center gap-2"
                    >
                        <span
                            class="text-xs"
                            style="color: var(--gl-text-muted)"
                            >{{ li + 1 }}.</span
                        >
                        {{ line }}
                    </div>
                    <p
                        v-if="!studentAnswerText(q)"
                        class="text-xs"
                        style="color: var(--gl-text-muted)"
                    >
                        No answer
                    </p>
                </div>
                <div
                    class="rounded-lg bg-[#DCEEE3] px-4 py-2 text-sm"
                    style="color: #2f7a54"
                >
                    <span class="text-xs font-medium">Correct answers:</span>
                    <div
                        v-for="(opt, oi) in q.options"
                        :key="oi"
                        class="flex items-center gap-2"
                    >
                        <span class="text-xs" style="color: #2f7a54"
                            >{{ oi + 1 }}.</span
                        >
                        {{ opt.option_text }}
                    </div>
                </div>
                <div
                    v-if="getAnswerEntry(q.id)?.feedback || answerMap[q.id]?.feedback"
                    class="rounded-lg p-3 text-xs"
                    style="
                        background: linear-gradient(
                            135deg,
                            rgba(59, 130, 246, 0.08),
                            rgba(124, 58, 237, 0.08)
                        );
                        border: 1px solid rgba(59, 130, 246, 0.2);
                    "
                >
                    <span class="font-bold" style="color: var(--gl-primary)"
                        >AI Evaluation Feedback:</span
                    >
                    <p class="mt-1" style="color: var(--gl-text-primary)">
                        {{ getAnswerEntry(q.id)?.feedback || answerMap[q.id]?.feedback }}
                    </p>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <label class="text-xs font-semibold" style="color: var(--gl-text-muted)">Score (out of {{ q.points }}):</label>
                    <input
                        type="number"
                        min="0"
                        :max="q.points"
                        :value="getEssayPoints(q.id)"
                        @input="setEssayPoints(q.id, Number(($event.target as HTMLInputElement).value), q.points)"
                        class="w-20 rounded-xl border px-2.5 py-1.5 text-center text-sm font-extrabold tabular-nums outline-none"
                        style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);"
                    />
                </div>
            </div>

            <!-- True/False & Multiple Choice -->
            <div
                v-if="q.type === 'true_false' || q.type === 'multiple_choice'"
                class="mb-3 space-y-2"
            >
                <div
                    v-for="opt in q.options"
                    :key="opt.id"
                    class="flex items-center gap-3 rounded-lg border px-4 py-3 text-sm"
                    :class="optionClass(opt, q)"
                >
                    <Check
                        v-if="opt.is_correct"
                        class="h-4 w-4 shrink-0"
                        :stroke-width="2.5"
                        style="color: #2f7a54"
                    />
                    <X
                        v-else-if="isSelected(opt, q)"
                        class="h-4 w-4 shrink-0"
                        :stroke-width="2.5"
                        style="color: var(--gl-danger)"
                    />
                    <div
                        v-else
                        class="h-4 w-4 shrink-0 rounded-full border-2"
                        style="border-color: var(--gl-border)"
                    ></div>
                    <span :class="opt.is_correct ? 'font-medium' : ''">{{
                        opt.option_text
                    }}</span>
                    <span
                        v-if="opt.is_correct"
                        class="ml-auto text-xs font-medium"
                        style="color: #2f7a54"
                        >Correct answer</span
                    >
                    <span
                        v-else-if="isSelected(opt, q)"
                        class="ml-auto text-xs font-medium"
                        style="color: var(--gl-danger)"
                        >Student's answer</span
                    >
                </div>
            </div>

            <!-- Matching -->
            <div v-if="q.type === 'matching'" class="mb-3 space-y-1 text-sm">
                <div
                    v-for="pair in q.matching_pairs"
                    :key="pair.id"
                    class="flex items-center gap-2 rounded-lg bg-[var(--gl-surface-2)] px-4 py-2"
                    style="color: var(--gl-text-secondary)"
                >
                    <span
                        class="font-medium"
                        style="color: var(--gl-text-primary)"
                        >{{ pair.left_text }}</span
                    >
                    <ArrowRight
                        class="h-3.5 w-3.5 shrink-0"
                        :stroke-width="2"
                        style="color: var(--gl-text-muted)"
                    />
                    <span class="text-[#2F7A54]">{{ pair.right_text }}</span>
                    <span
                        class="ml-auto text-xs"
                        style="color: var(--gl-text-muted)"
                        >→ {{ studentMatchingAnswer(q, pair) }}</span
                    >
                </div>
            </div>

            <!-- Essay -->
            <div v-if="q.type === 'essay'" class="mb-3 space-y-3">
                <div
                    v-if="q.criteria"
                    class="rounded-lg p-3 text-xs"
                    style="
                        background: var(--gl-surface-2);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <span class="font-semibold" style="color: var(--gl-primary)"
                        >Rubric Criteria:</span
                    >
                    <p
                        class="mt-1 whitespace-pre-line"
                        style="color: var(--gl-text-secondary)"
                    >
                        {{ q.criteria }}
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[var(--gl-border)] p-3 text-sm"
                    style="background: var(--gl-surface-2)"
                >
                    <span
                        class="text-xs font-semibold"
                        style="color: var(--gl-text-muted)"
                        >Student's Essay Response:</span
                    >
                    <p
                        class="mt-1 text-sm whitespace-pre-line"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ studentAnswerText(q) || 'No response submitted' }}
                    </p>
                </div>
                <div
                    v-if="getAnswerEntry(q.id)?.feedback || answerMap[q.id]?.feedback"
                    class="rounded-lg p-3 text-xs"
                    style="
                        background: linear-gradient(
                            135deg,
                            rgba(59, 130, 246, 0.08),
                            rgba(124, 58, 237, 0.08)
                        );
                        border: 1px solid rgba(59, 130, 246, 0.2);
                    "
                >
                    <span class="font-bold" style="color: var(--gl-primary)"
                        >AI Evaluation Feedback:</span
                    >
                    <p class="mt-1" style="color: var(--gl-text-primary)">
                        {{ getAnswerEntry(q.id)?.feedback || answerMap[q.id]?.feedback }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold" style="color: var(--gl-text-muted)">Score (out of {{ q.points }}):</label>
                    <input
                        type="number"
                        min="0"
                        :max="q.points"
                        :value="getEssayPoints(q.id)"
                        @input="setEssayPoints(q.id, Number(($event.target as HTMLInputElement).value), q.points)"
                        class="w-20 rounded-xl border px-2.5 py-1.5 text-center text-sm font-extrabold tabular-nums outline-none"
                        style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);"
                    />
                </div>
            </div>

            <!-- Toggle correct/incorrect -->
            <div class="mt-3 flex items-center gap-2.5 rounded-xl px-3.5 py-2.5" style="background: var(--gl-surface-2);">
                <label class="flex cursor-pointer items-center gap-2.5 text-sm">
                    <input type="checkbox" :checked="isMarkedCorrect(q.id)" @change="toggleCorrect(q.id)" class="gl-check" />
                    <span :style="isMarkedCorrect(q.id) ? 'color: var(--gl-success); font-weight: 700;' : 'color: var(--gl-danger); font-weight: 600;'">
                        {{ isMarkedCorrect(q.id) ? 'Marked correct' : 'Marked incorrect' }}
                    </span>
                </label>
            </div>
        </div>
        </div>

        <div v-if="saveError" class="mb-4 rounded-xl p-3 text-xs flex items-center justify-between"
            style="background: var(--gl-danger-bg); color: var(--gl-danger); border: 1px solid var(--gl-danger);">
            <div class="flex items-center gap-2">
                <AlertCircle :size="14" class="shrink-0" />
                <span>{{ saveError }}</span>
            </div>
            <button type="button" @click="saveError = null" class="font-bold opacity-60 hover:opacity-100 ml-2">Dismiss</button>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 pt-1">

            <button
                type="submit"
                :disabled="saving"
                class="flex items-center gap-1.5 rounded-full px-5 py-2.5 text-xs font-bold transition-all hover:scale-[1.02] disabled:opacity-50"
                :style="nextAttempt
                    ? { background: 'var(--gl-surface)', color: 'var(--gl-text-primary)', border: '1px solid var(--gl-border)' }
                    : { background: 'linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))', color: '#fff', boxShadow: '0 0 14px var(--gl-primary-glow)' }"
            >
                <LoaderCircle v-if="saving && saveTarget === 'current'" class="h-4 w-4 animate-spin" :stroke-width="2" />
                <Check v-else class="h-4 w-4" :stroke-width="2" />
                {{ nextAttempt ? 'Save' : 'Save & Done' }}
            </button>

            <button type="button" @click="autoRecheck" :disabled="saving"
                class="flex items-center gap-1.5 rounded-full px-5 py-2.5 text-xs font-bold transition-all hover:scale-[1.02] disabled:opacity-50"
                style="background: var(--gl-surface); color: var(--gl-text-primary); border: 1px solid var(--gl-border);">
                <RotateCw class="h-4 w-4" :stroke-width="2" />
                Auto recheck
            </button>
            <Link :href="returnUrl" class="rounded-full px-5 py-2.5 text-xs font-bold transition-all hover:scale-[1.02]"
                style="background: var(--gl-surface-2); color: var(--gl-text-secondary);">Cancel</Link>
        </div>
    </form>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    AlertCircle,
    Check,
    RotateCw,
    X,
    ChevronLeft,
    ChevronRight,
    Users,
    LoaderCircle,
    Sparkles,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { isNearMatch } from '@/lib/answerMatcher';

const props = defineProps<{
    seatwork: any;
    attempt: any;
    attempts?: any[];
    returnTo?: string;
}>();

const attemptsList = computed(() => props.attempts || []);
const currentIndex = computed(() =>
    attemptsList.value.findIndex((a: any) => Number(a.id) === Number(props.attempt.id)),
);
const prevAttempt = computed(() =>
    currentIndex.value > 0 ? attemptsList.value[currentIndex.value - 1] : null,
);
const nextAttempt = computed(() =>
    currentIndex.value >= 0 && currentIndex.value < attemptsList.value.length - 1
        ? attemptsList.value[currentIndex.value + 1]
        : null,
);

const returnUrl = computed(() => {
    if (props.returnTo) {
return props.returnTo;
}

    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        const paramReturn = urlParams.get('return_to');

        if (paramReturn) {
return paramReturn;
}
    }

    return `/teacher/seatworks/${props.seatwork.id}#attempt-${props.attempt.id}`;
});

const saveTarget = ref<'current' | 'next'>('current');
const saveError = ref<string | null>(null);

const totalPoints = computed(() =>
    props.seatwork.questions.reduce((s: number, q: any) => s + q.points, 0),
);
const saving = ref(false);

interface AnswerDatum {
    id: number;
    is_correct: boolean;
    feedback?: string;
    points_awarded?: number | null;
}
const answerData = ref<AnswerDatum[]>([]);

const aiChecking = ref(false);
const aiBanner = ref<string | null>(null);

function getAnswerEntry(questionId: number) {
    const ans = props.attempt.answers?.find((a: any) => a.question_id === questionId);

    if (!ans) {
return null;
}

    return answerData.value.find((d: AnswerDatum) => d.id === ans.id) ?? null;
}

function isMarkedCorrect(questionId: number): boolean {
    const entry = getAnswerEntry(questionId);

    if (entry) {
return entry.is_correct;
}

    const ans = props.attempt.answers?.find(
        (a: any) => a.question_id === questionId,
    );

    return ans ? !!ans.is_correct : false;
}

function getEssayPoints(questionId: number): number {
    const entry = getAnswerEntry(questionId);

    if (entry && entry.points_awarded !== undefined && entry.points_awarded !== null) {
        return Number(entry.points_awarded);
    }

    const ans = props.attempt.answers?.find((a: any) => a.question_id === questionId);

    if (ans && ans.points_awarded !== undefined && ans.points_awarded !== null) {
        return Number(ans.points_awarded);
    }

    const q = props.seatwork.questions.find((item: any) => item.id === questionId);

    return entry?.is_correct ? (q?.points ?? 0) : 0;
}

function setEssayPoints(questionId: number, val: number, maxPoints: number) {
    const clamped = Math.max(0, Math.min(maxPoints, isNaN(val) ? 0 : val));
    let entry = getAnswerEntry(questionId);

    if (!entry) {
        const ans = props.attempt.answers?.find((a: any) => a.question_id === questionId);

        if (ans) {
            entry = {
                id: ans.id,
                is_correct: clamped >= Math.ceil(maxPoints / 2),
                feedback: ans.feedback ?? '',
                points_awarded: clamped,
            };
            answerData.value.push(entry);

            return;
        }
    }

    if (entry) {
        entry.points_awarded = clamped;
        entry.is_correct = clamped >= Math.ceil(maxPoints / 2);
    }
}

async function runAiCheck() {
    aiChecking.value = true;
    aiBanner.value = null;

    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        const res = await fetch(`/teacher/seatworks/${props.seatwork.id}/ai-check/${props.attempt.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await res.json();

        if (data.success && Array.isArray(data.answers)) {
            for (const item of data.answers) {
                const target = answerData.value.find(d => d.id === item.id);

                if (target) {
                    target.is_correct = !!item.is_correct;

                    if (item.feedback !== undefined) {
target.feedback = item.feedback;
}

                    if (item.points_awarded !== undefined) {
target.points_awarded = item.points_awarded;
}
                }

                const existingAns = props.attempt.answers.find((a: any) => a.id === item.id);

                if (existingAns) {
                    if (item.feedback !== undefined) {
existingAns.feedback = item.feedback;
}

                    if (item.points_awarded !== undefined) {
existingAns.points_awarded = item.points_awarded;
}

                    existingAns.is_correct = !!item.is_correct;
                }
            }

            aiBanner.value = 'AI evaluated all questions based on criteria and answers. Review and save below.';
        } else {
            aiBanner.value = data.message || 'AI check returned an unexpected response.';
        }
    } catch (e: any) {
        aiBanner.value = 'AI checking failed: ' + (e.message || 'Network error');
    } finally {
        aiChecking.value = false;
    }
}

watch(
    () => props.attempt,
    (att) => {
        aiBanner.value = null;

        if (!att?.answers) {
            answerData.value = [];

            return;
        }

        answerData.value = att.answers.map((a: any) => ({
            id: a.id,
            is_correct: !!a.is_correct,
            feedback: a.feedback ?? '',
            points_awarded: a.points_awarded !== null && a.points_awarded !== undefined ? Math.round(Number(a.points_awarded)) : null,
        }));
    },
    { immediate: true, deep: true },
);

const answerMap = computed(() => {
    const map: Record<number, any> = {};
    (props.attempt.answers || []).forEach((a: any) => {
        map[a.question_id] = a;
    });

    return map;
});

const questions = computed(() => {
    return props.seatwork.questions.map((q: any) => {
        const answer = answerMap.value[q.id];

        return { ...q, studentAnswer: answer || null };
    });
});

const computedScore = computed(() => {
    let s = 0;
    props.seatwork.questions.forEach((q: any) => {
        const entry = answerData.value.find((d: AnswerDatum) => {
            const ans = props.attempt.answers.find(
                (a: any) => a.question_id === q.id,
            );

            return ans && d.id === ans.id;
        });

        if (q.type === 'essay' && entry?.points_awarded !== undefined && entry?.points_awarded !== null) {
            s += Number(entry.points_awarded);
        } else if (entry?.is_correct) {
            s += q.points;
        }
    });

    return s;
});

const scoreColor = computed(() => {
    const pct = totalPoints.value
        ? (computedScore.value / totalPoints.value) * 100
        : 0;

    if (pct >= 80) {
return '#2F7A54';
}

    if (pct >= 60) {
return '#A5701A';
}

    return 'var(--gl-danger)';
});

const scoreBg = computed(() => {
    const pct = totalPoints.value
        ? (computedScore.value / totalPoints.value) * 100
        : 0;

    if (pct >= 80) {
return '#DCEEE3';
}

    if (pct >= 60) {
return '#FFF3D6';
}

    return 'var(--gl-danger-bg)';
});

function typeLabel(t: string): string {
    const labels: Record<string, string> = {
        identification: 'Identification',
        enumeration: 'Enumeration',
        true_false: 'True/False',
        multiple_choice: 'Multiple Choice',
        matching: 'Matching Type',
    };

    return labels[t] ?? t;
}

function studentAnswerText(q: any): string {
    return q.studentAnswer?.answer_text ?? '';
}

function studentMatchingAnswer(q: any, pair: any): string {
    const ma = q.studentAnswer?.matching_answers;

    if (!ma) {
return '—';
}

    return ma[pair.id] ?? '—';
}

function isSelected(opt: any, question: any): boolean {
    return question.studentAnswer?.selected_option_id === opt.id;
}

function optionClass(opt: any, question: any): Record<string, boolean> {
    const selected = isSelected(opt, question);

    return {
        'border-[#2F7A54] bg-[#DCEEE3]': opt.is_correct,
        'border-[var(--gl-danger)] bg-[var(--gl-danger-bg)]':
            !opt.is_correct && selected,
        'border-[var(--gl-border)]': !opt.is_correct && !selected,
    };
}

function toggleCorrect(questionId: number) {
    const ans = props.attempt.answers?.find(
        (a: any) => a.question_id === questionId,
    );

    if (!ans) {
return;
}

    let entry = answerData.value.find((d: AnswerDatum) => d.id === ans.id);
    const q = props.seatwork.questions.find((item: any) => item.id === questionId);

    if (!entry) {
        entry = {
            id: ans.id,
            is_correct: !ans.is_correct,
            feedback: ans.feedback ?? '',
            points_awarded: ans.points_awarded ?? null,
        };
        answerData.value.push(entry);
    } else {
        entry.is_correct = !entry.is_correct;
    }

    if (q?.type === 'essay') {
        entry.points_awarded = entry.is_correct ? q.points : 0;
    }
}

function autoRecheck() {
    answerData.value.forEach((d: AnswerDatum) => {
        const ans = props.attempt.answers.find((a: any) => a.id === d.id);

        if (!ans) {
return;
}

        const question = props.seatwork.questions.find(
            (q: any) => q.id === ans.question_id,
        );

        if (!question) {
return;
}

        switch (question.type) {
            case 'identification': {
                d.is_correct = (question.options || []).some(
                    (o: any) => isNearMatch(o.option_text, ans.answer_text)
                );
                break;
            }
            case 'enumeration': {
                const expected = (question.options || []).map((o: any) => o.option_text).filter(Boolean);
                const studentLines = (ans.answer_text || '').split(/[\r\n,;]+/).map((s: string) => s.trim()).filter(Boolean);
                let matches = 0;
                const matchedExpected = new Set<number>();

                for (const line of studentLines) {
                    for (let i = 0; i < expected.length; i++) {
                        if (!matchedExpected.has(i) && isNearMatch(expected[i], line)) {
                            matchedExpected.add(i);
                            matches++;
                            break;
                        }
                    }
                }

                const total = Math.max(1, expected.length);
                d.is_correct = matches === total || (matches > 0 && matches >= Math.ceil(total / 2));
                d.points_awarded = Math.round((matches / total) * question.points);
                d.feedback = `${matches}/${total} items identified (near match accepted).`;
                break;
            }
            case 'true_false':
            case 'multiple_choice': {
                const correctOption = question.options?.find(
                    (o: any) => o.is_correct,
                );
                d.is_correct = correctOption
                    ? ans.selected_option_id === correctOption.id
                    : false;
                break;
            }
            case 'matching': {
                const ma = ans.matching_answers ?? {};
                const pairs = question.matching_pairs ?? [];
                d.is_correct =
                    pairs.length > 0 &&
                    pairs.every((p: any) => ma[p.id] === p.right_text);
                break;
            }
            default:
                d.is_correct = false;
        }
    });
}

function computeReturnUrl(targetAttemptId: number): string {
    const base = returnUrl.value;

    if (base.includes('#result-attempt-')) {
        return base.replace(/#(result-attempt-[a-zA-Z]+-|result-attempt-)\d+.*$/, `#$1${targetAttemptId}`);
    }

    if (base.includes('#attempt-')) {
        return base.replace(/#attempt-\d+.*$/, `#attempt-${targetAttemptId}`);
    }

    if (base.includes('/results')) {
        return `${base}#result-attempt-seatwork-${targetAttemptId}`;
    }

    return `${base}#attempt-${targetAttemptId}`;
}

function save() {
    saving.value = true;
    saveTarget.value = 'current';
    saveError.value = null;
    router.put(
        `/teacher/seatworks/${props.seatwork.id}/recheck/${props.attempt.id}`,
        {
            answers: answerData.value,
            return_to: returnUrl.value,
        },
        {
            preserveScroll: false,
            preserveState: false,
            onError: (errs) => {
                saveError.value = Object.values(errs).flat().join(', ') || 'Failed to save recheck data.';
            },
            onSuccess: () => {
                saveError.value = null;
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}


function goToAttempt(targetId: number) {
    if (targetId === props.attempt.id) {
return;
}

    const destReturn = computeReturnUrl(targetId);
    router.visit(
        `/teacher/seatworks/${props.seatwork.id}/recheck/${targetId}?return_to=${encodeURIComponent(destReturn)}`,
    );
}
</script>
