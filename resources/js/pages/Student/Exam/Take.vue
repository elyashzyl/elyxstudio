<template>
    <Head :title="exam.title" />

    <div
        v-if="autoSubmitReason"
        class="gl-fade-in mb-6 flex items-center gap-2 rounded-2xl px-5 py-3 text-sm font-medium"
        style="
            background: #fff3e0;
            border: 1px solid #ffb74d;
            color: #b76e00;
        "
    >
        <Clock class="h-4 w-4 animate-spin" />
        {{ autoSubmitReason }}
    </div>

    <div
        v-if="flash?.error"
        class="gl-fade-in mb-6 rounded-2xl px-5 py-3 text-sm font-medium"
        style="
            background: var(--gl-danger-bg);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: var(--gl-danger);
        "
    >
        {{ flash.error }}
    </div>

    <div
        v-if="errorMessage"
        class="gl-fade-in mb-6 rounded-2xl px-5 py-3 text-sm font-medium"
        style="
            background: var(--gl-danger-bg);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: var(--gl-danger);
        "
    >
        {{ errorMessage }}
    </div>

    <div
        v-if="flash?.info"
        class="mb-6 rounded-lg px-4 py-3 text-sm"
        style="background-color: #e5f0ff; color: #1a56db"
    >
        {{ flash.info }}
    </div>

    <div
        v-if="restored"
        class="mb-6 rounded-lg px-4 py-3 text-sm"
        style="background-color: #fff8e1; color: #8d6e00"
    >
        Progress restored from previous session.
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h2
                    class="text-lg font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    {{ exam.title }}
                </h2>
                <span
                    v-if="exam.time_limit_minutes && remainingSeconds === null"
                    class="flex items-center gap-1 rounded bg-[#FFF3E0] px-2 py-0.5 text-xs"
                    style="color: #b76e00"
                >
                    <Clock class="h-3.5 w-3.5" :stroke-width="2" />
                    {{ exam.time_limit_minutes }} min
                </span>
            </div>
            <p
                v-if="exam.instructions"
                class="mt-2 text-sm"
                style="color: var(--gl-text-secondary)"
            >
                {{ exam.instructions }}
            </p>
            <p
                v-if="totalQuestions > 0"
                class="mt-1 text-sm"
                style="color: var(--gl-text-secondary)"
            >
                {{ answeredCount }} of {{ totalQuestions }} answered
            </p>
        </div>
        <div
            v-if="remainingSeconds !== null"
            class="flex items-center gap-2 rounded-lg border px-3.5 py-2 text-sm font-medium"
            :class="
                remainingSeconds < 60
                    ? 'border-[var(--gl-danger-bg)] text-[var(--gl-danger)]'
                    : 'border-[var(--gl-border)] text-[var(--gl-text-secondary)]'
            "
        >
            <Clock class="h-4 w-4" :stroke-width="2" />
            {{ formatTime(remainingSeconds) }}
        </div>
    </div>

    <form @submit.prevent="submitExam(true)">
        <div v-for="(sec, si) in exam.sections" :key="sec.id" class="card mb-6">
            <div class="mb-3 flex items-center gap-2">
                <h3
                    class="text-sm font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    {{ sec.title }}
                </h3>
                <span
                    class="rounded bg-[var(--gl-border)] px-2 py-0.5 text-xs capitalize"
                    style="color: var(--gl-text-secondary)"
                    >{{ sec.section_type }}</span
                >
            </div>
            <p
                v-if="sec.instructions"
                class="mb-3 text-sm"
                style="color: var(--gl-text-secondary)"
            >
                {{ sec.instructions }}
            </p>

            <template v-if="sec.section_type === 'questions'">
                <div
                    v-for="(q, qi) in sec.questions"
                    :key="q.id"
                    class="mb-4 rounded-lg border border-[var(--gl-border)] p-4"
                >
                    <p
                        class="mb-2 text-sm font-medium"
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

                    <template v-if="q.type === 'multiple_choice'">
                        <div
                            v-for="opt in q.options"
                            :key="opt.id"
                            class="mb-1.5 flex items-center gap-2"
                        >
                            <input
                                type="radio"
                                :name="'q-' + q.id"
                                :value="opt.id"
                                v-model="answers[q.id]"
                                class="h-4 w-4"
                            />
                            <label
                                class="text-sm"
                                style="color: var(--gl-text-secondary)"
                                >{{ opt.option_text }}</label
                            >
                        </div>
                    </template>

                    <template v-if="q.type === 'true_false'">
                        <div
                            v-for="opt in q.options"
                            :key="opt.id"
                            class="mb-1.5 flex items-center gap-2"
                        >
                            <input
                                type="radio"
                                :name="'q-' + q.id"
                                :value="opt.id"
                                v-model="answers[q.id]"
                                class="h-4 w-4"
                            />
                            <label
                                class="text-sm"
                                style="color: var(--gl-text-secondary)"
                                >{{ opt.option_text }}</label
                            >
                        </div>
                    </template>

                    <template v-if="q.type === 'identification'">
                        <input
                            v-model="answers[q.id]"
                            type="text"
                            class="input-field"
                            placeholder="Type your answer..."
                        />
                    </template>

                    <template v-if="q.type === 'enumeration'">
                        <div
                            v-for="i in enumCount(q)"
                            :key="i"
                            class="mb-1.5 flex items-center gap-2"
                        >
                            <span
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                                >{{ i }}.</span
                            >
                            <input
                                v-model="enumAnswers[q.id][i - 1]"
                                type="text"
                                class="flex-1 rounded-lg border border-[var(--gl-border)] px-2.5 py-1.5 text-sm outline-none"
                                :placeholder="'Item ' + i"
                            />
                        </div>
                        <button
                            type="button"
                            @click="addEnumItem(q.id)"
                            class="text-xs font-medium hover:text-[var(--gl-text-primary)]"
                            style="color: var(--gl-text-secondary)"
                        >
                            + Add item
                        </button>
                    </template>

                    <template v-if="q.type === 'matching'">
                        <div class="mt-2 space-y-2">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p
                                        class="mb-1.5 text-xs font-medium"
                                        style="color: var(--gl-text-secondary)"
                                    >
                                        Items
                                    </p>
                                    <div
                                        v-for="(pair, pi) in q.matching_pairs"
                                        :key="pair.id"
                                        class="mb-1.5 rounded bg-[var(--gl-surface-2)] px-3 py-2 text-sm"
                                        style="color: var(--gl-text-primary)"
                                    >
                                        {{ pair.left_text }}
                                    </div>
                                </div>
                                <div>
                                    <p
                                        class="mb-1.5 text-xs font-medium"
                                        style="color: var(--gl-text-secondary)"
                                    >
                                        Matches
                                    </p>
                                    <div
                                        v-for="(pair, pi) in q.matching_pairs"
                                        :key="pair.id"
                                        class="mb-1.5"
                                    >
                                        <select
                                            v-model="
                                                matchingAnswers[q.id][pair.id]
                                            "
                                            class="w-full rounded-lg border border-[var(--gl-border)] px-2.5 py-2 text-sm outline-none"
                                        >
                                            <option value="" disabled>
                                                Select match
                                            </option>
                                            <option
                                                v-for="p in shuffledRight(q)"
                                                :key="p.id"
                                                :value="p.right_text"
                                            >
                                                {{ p.right_text }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <template v-if="sec.section_type === 'practical'">
                <div class="rounded-lg border border-[var(--gl-border)] p-4">
                    <p class="text-sm" style="color: var(--gl-text-secondary)">
                        This section will be graded by your teacher. Submit your
                        exam to proceed.
                    </p>
                </div>
            </template>
        </div>

        <div class="flex items-center justify-end gap-3">
            <button
                type="submit"
                class="btn-primary flex items-center gap-2"
                :disabled="submitting"
            >
                <LoaderCircle
                    v-if="submitting"
                    class="h-4 w-4 animate-spin"
                    :stroke-width="2"
                />
                <Send v-else class="h-4 w-4" :stroke-width="2" />
                {{ submitting ? 'Submitting...' : 'Submit Exam' }}
            </button>
        </div>
    </form>

    <!-- Submission Progress Overlay -->
    <Teleport to="body">
        <div
            v-if="submitting"
            class="fixed inset-0 z-50 flex flex-col items-center justify-center p-4 text-center"
            style="background: rgba(0, 0, 0, 0.65); backdrop-filter: blur(4px)"
        >
            <div
                class="w-full max-w-sm rounded-3xl p-6 flex flex-col items-center"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.5);
                "
            >
                <div
                    class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl"
                    style="background: rgba(239, 68, 68, 0.15)"
                >
                    <LoaderCircle
                        class="h-6 w-6 animate-spin"
                        style="color: var(--gl-danger)"
                        :stroke-width="2.5"
                    />
                </div>
                <h3
                    class="text-base font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    Submitting Exam...
                </h3>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    Grading exam sections and computing scores. Redirecting automatically...
                </p>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Clock, LoaderCircle, Send } from '@lucide/vue';
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue';
import { useAutoSave } from '@/composables/useAutoSave';
import { getEcho } from '@/lib/echo';

const props = defineProps<{ exam: any; startedAt: string; attempt?: any }>();

const page = usePage();
const flash = computed(() => (page.props as any).flash);

const answers = reactive<Record<number, any>>({});
const enumAnswers = reactive<Record<number, string[]>>({});
const matchingAnswers = reactive<Record<number, Record<number, string>>>({});

const submitting = ref(false);
const autoSubmitReason = ref('');
const errorMessage = ref('');
const restored = ref(false);
const remainingSeconds = ref<number | null>(null);
let timer: ReturnType<typeof setInterval> | null = null;
let syncTimerInterval: ReturnType<typeof setInterval> | null = null;
let attemptChannel: any = null;
let examChannel: any = null;
let autoSave: ReturnType<typeof useAutoSave> | null = null;

function formatTime(totalSeconds: number): string {
    const m = Math.floor(totalSeconds / 60)
        .toString()
        .padStart(2, '0');
    const s = Math.floor(totalSeconds % 60)
        .toString()
        .padStart(2, '0');

    return `${m}:${s}`;
}

function tick() {
    if (remainingSeconds.value === null) {
return;
}

    remainingSeconds.value -= 1;

    if (remainingSeconds.value <= 0) {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }

        doSubmit(false);
    }
}

const totalQuestions = computed(() => {
    return (props.exam.sections || []).reduce((acc: number, sec: any) => {
        if (sec.section_type === 'questions') {
            return acc + (sec.questions?.length || 0);
        }

        return acc;
    }, 0);
});

const answeredCount = computed(() => {
    let count = 0;
    (props.exam.sections || []).forEach((sec: any) => {
        if (sec.section_type === 'questions') {
            (sec.questions || []).forEach((q: any) => {
                if (q.type === 'enumeration') {
                    if ((enumAnswers[q.id] || []).some((i: string) => i.trim())) {
count++;
}
                } else if (q.type === 'matching') {
                    if (Object.keys(matchingAnswers[q.id] || {}).length > 0) {
count++;
}
                } else {
                    const val = answers[q.id];

                    if (val !== undefined && val !== null && val !== '') {
count++;
}
                }
            });
        }
    });

    return count;
});

// Initialize
props.exam.sections.forEach((sec: any) => {
    if (sec.section_type === 'questions') {
        sec.questions.forEach((q: any) => {
            if (q.type === 'enumeration') {
                enumAnswers[q.id] = [''];
            }

            if (q.type === 'matching') {
                matchingAnswers[q.id] = {};
            }
        });
    }
});

function enumCount(q: any): number {
    const arr = enumAnswers[q.id];

    return arr ? arr.length : 0;
}

function addEnumItem(qId: number) {
    if (!enumAnswers[qId]) {
enumAnswers[qId] = [''];
}

    enumAnswers[qId].push('');
}

const rightTexts = ref<Record<number, any[]>>({});

function shuffledRight(q: any): any[] {
    if (!rightTexts.value[q.id]) {
        const shuffled = [...q.matching_pairs].sort(() => Math.random() - 0.5);
        rightTexts.value[q.id] = shuffled;
    }

    return rightTexts.value[q.id];
}

onMounted(() => {
    const savedKey = 'autosave-exam-' + props.exam.id;
    autoSave = useAutoSave(savedKey, () => ({
        answers: { ...answers },
        enumAnswers: Object.fromEntries(
            Object.entries(enumAnswers).map(([k, v]) => [k, [...v]]),
        ),
        matchingAnswers: Object.fromEntries(
            Object.entries(matchingAnswers).map(([k, v]) => [k, { ...v }]),
        ),
    }));

    const saved = autoSave.load();

    if (saved) {
        if (saved.answers) {
Object.assign(answers, saved.answers);
}

        if (saved.enumAnswers) {
Object.assign(enumAnswers, saved.enumAnswers);
}

        if (saved.matchingAnswers) {
Object.assign(matchingAnswers, saved.matchingAnswers);
}

        restored.value = true;
    }

    autoSave.start();

    if (props.exam.time_limit_minutes && props.startedAt) {
        const start = new Date(props.startedAt).getTime();
        const deadline = start + props.exam.time_limit_minutes * 60 * 1000;
        remainingSeconds.value = Math.max(
            0,
            Math.floor((deadline - Date.now()) / 1000),
        );
        timer = setInterval(tick, 1000);
    }

    const echo = getEcho((page.props as any).broadcasting?.reverb);

    if (echo) {
        if (props.attempt?.id) {
            attemptChannel = echo.private(`exam.attempt.${props.attempt.id}`);
            attemptChannel.listen('.exam.expired', (e: any) => {
                forceAutoSubmit(e.message || 'Exam time has expired.');
            });
        }

        if (props.exam?.id) {
            examChannel = echo.private(`exam.${props.exam.id}`);
            examChannel.listen('.exam.closed', (e: any) => {
                forceAutoSubmit(e.message || 'Exam session has closed.');
            });
            examChannel.listen('.exam.expired', (e: any) => {
                forceAutoSubmit(e.message || 'Exam time has expired.');
            });
        }
    }

    if (props.attempt?.id) {
        syncTimerInterval = setInterval(syncServerTimer, 20000);
    }

    window.addEventListener('pageshow', handlePageShow);
});

onUnmounted(() => {
    if (timer) {
clearInterval(timer);
}

    if (syncTimerInterval) {
clearInterval(syncTimerInterval);
}

    if (autoSave) {
autoSave.stop();
}

    const echo = getEcho();

    if (echo) {
        if (props.attempt?.id) {
echo.leave(`exam.attempt.${props.attempt.id}`);
}

        if (props.exam?.id) {
echo.leave(`exam.${props.exam.id}`);
}
    }

    window.removeEventListener('pageshow', handlePageShow);
});

function forceAutoSubmit(reason: string) {
    if (submitting.value) {
return;
}

    autoSubmitReason.value = reason;
    doSubmit(false);
}

async function syncServerTimer() {
    if (!props.attempt?.id || submitting.value) {
return;
}

    try {
        const token =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';
        const res = await fetch(
            `/student/exams/attempts/${props.attempt.id}/sync-timer`,
            {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
            },
        );

        if (!res.ok) {
return;
}

        const data = await res.json();

        if (data.is_expired) {
            forceAutoSubmit('Exam time has expired.');

            return;
        }

        if (typeof data.remaining_seconds === 'number') {
            if (
                remainingSeconds.value === null ||
                Math.abs(remainingSeconds.value - data.remaining_seconds) > 3
            ) {
                remainingSeconds.value = data.remaining_seconds;
            }
        }
    } catch {}
}

function handlePageShow(event: PageTransitionEvent) {
    if (event.persisted) {
        window.location.reload();
    }
}

function submitExam(needConfirm = true) {
    doSubmit(needConfirm);
}

function doSubmit(needConfirm = true) {
    if (submitting.value) {
return;
}

    if (
        needConfirm &&
        !confirm('Submit exam? This action cannot be undone.')
    ) {
return;
}

    submitting.value = true;
    errorMessage.value = '';

    if (timer) {
        clearInterval(timer);
        timer = null;
    }

    if (autoSave) {
        autoSave.stop();
        autoSave.clear();
    }

    const answersPayload: Record<string, any> = {};

    props.exam.sections.forEach((sec: any) => {
        if (sec.section_type === 'questions') {
            sec.questions.forEach((q: any) => {
                if (q.type === 'enumeration') {
                    const items = (enumAnswers[q.id] || []).filter(
                        (i: string) => i.trim(),
                    );

                    if (items.length) {
answersPayload[q.id] = items;
}
                } else if (q.type === 'matching') {
                    const m = matchingAnswers[q.id] || {};

                    if (Object.keys(m).length) {
answersPayload[q.id] = m;
}
                } else {
                    const val = answers[q.id];

                    if (val !== undefined && val !== null && val !== '') {
                        answersPayload[q.id] = val;
                    }
                }
            });
        }
    });

    router.post(
        `/student/exams/${props.exam.id}/submit`,
        { answers: answersPayload, attempt_id: props.attempt?.id },
        {
            preserveScroll: false,
            onSuccess: () => {
                submitting.value = false;
                window.location.assign(`/student/exams/${props.attempt?.id}/result`);
            },
            onError: (errs) => {
                submitting.value = false;
                const first = Object.values(errs)[0];
                errorMessage.value =
                    typeof first === 'string'
                        ? first
                        : 'Failed to submit exam. Please try again.';
            },
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
}
</script>
