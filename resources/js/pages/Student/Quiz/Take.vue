<template>
    <Head :title="quiz.title" />

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
        v-if="flash?.info"
        class="gl-fade-in mb-6 rounded-2xl px-5 py-3 text-sm font-medium"
        style="
            background: rgba(59, 130, 246, 0.12);
            color: var(--gl-primary);
            border: 1px solid rgba(59, 130, 246, 0.2);
        "
    >
        {{ flash.info }}
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
        v-if="restored"
        class="mb-6 rounded-lg px-4 py-3 text-sm"
        style="background-color: #fff8e1; color: #8d6e00"
    >
        Progress restored from previous session.
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2
                class="text-lg font-semibold"
                style="color: var(--gl-text-primary)"
            >
                {{ quiz.title }}
            </h2>
            <p class="text-sm" style="color: var(--gl-text-secondary)">
                {{ answeredCount }} of {{ quiz.questions.length }} answered
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button
                v-if="remainingSeconds !== null && timeWarpAvailable"
                type="button"
                @click="applyTimeWarpReward"
                :disabled="usingTimeWarp"
                class="flex items-center gap-1.5 rounded-lg border border-purple-400/40 bg-purple-500/10 px-3 py-1.5 text-xs font-semibold text-purple-700 transition-all hover:scale-105 hover:bg-purple-500/20 disabled:opacity-50 dark:text-purple-300"
                title="Time Warp: add extra seconds to the quiz countdown"
            >
                <span>⏳</span>
                <span>{{ usingTimeWarp ? 'Applying...' : `+${timeWarpSeconds}s Time Warp` }}</span>
                <span v-if="timeWarpCount > 1" class="opacity-70">×{{ timeWarpCount }}</span>
            </button>
            <div
                v-if="remainingSeconds !== null"
                class="flex items-center gap-2 rounded-lg border px-3.5 py-2 text-sm font-medium"
                :class="
                    remainingSeconds < 60
                        ? 'border-[var(--gl-danger-bg)] text-[var(--gl-danger)]'
                        : 'border-[var(--gl-border)] text-[var(--gl-text-secondary)]'
                "
                style="background-color: white"
            >
                <Clock class="h-4 w-4" :stroke-width="2" />
                {{ formatTime(remainingSeconds) }}
            </div>
        </div>
    </div>

    <div
        v-if="brainShieldActive"
        class="gl-fade-in mb-4 flex items-center gap-2 rounded-xl border border-amber-400/40 bg-amber-500/10 px-4 py-2.5 text-xs font-semibold text-amber-700 dark:text-amber-300"
    >
        <span class="text-base">🛡️</span>
        <span>Shield Armed: up to {{ shieldProtect }} wrong answer{{ shieldProtect === 1 ? '' : 's' }} on this quiz will be forgiven (full marks awarded)!</span>
    </div>

    <form class="space-y-4" @submit.prevent="submit(false)">
        <div
            v-for="(question, index) in quiz.questions"
            :key="question.id"
            class="card p-5"
        >
            <p
                class="mb-4 text-sm font-medium"
                style="color: var(--gl-text-primary)"
            >
                {{ index + 1 }}. {{ question.question_text }}
            </p>

            <div v-if="question.type === 'identification'" class="space-y-2">
                <input
                    v-model="answers[question.id]"
                    type="text"
                    placeholder="Type your answer here..."
                    class="w-full rounded-lg border border-[var(--gl-border)] px-4 py-2.5 text-sm outline-none transition-colors focus:border-[var(--gl-primary)] focus:ring-1 focus:ring-[var(--gl-primary)]"
                />
            </div>

            <div v-else-if="question.type === 'essay'" class="space-y-3">
                <div
                    v-if="question.criteria"
                    class="rounded-xl p-3 text-xs"
                    style="
                        background: var(--gl-surface-2);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <p class="font-semibold" style="color: var(--gl-primary)">
                        Grading Criteria:
                    </p>
                    <p class="mt-1 leading-relaxed whitespace-pre-line" style="color: var(--gl-text-secondary)">
                        {{ question.criteria }}
                    </p>
                </div>
                <div>
                    <textarea
                        v-model="answers[question.id]"
                        rows="5"
                        class="w-full rounded-lg border border-[var(--gl-border)] px-4 py-2.5 text-sm outline-none transition-colors focus:border-[var(--gl-primary)] focus:ring-1 focus:ring-[var(--gl-primary)]"
                        placeholder="Write your response here..."
                    ></textarea>
                </div>
            </div>

            <div v-else-if="question.type === 'enumeration'" class="space-y-2">
                <div
                    v-for="i in enumCount(question)"
                    :key="i"
                    class="flex items-center gap-2"
                >
                    <span class="text-xs font-bold" style="color: var(--gl-text-muted)">{{ i }}.</span>
                    <input
                        v-model="enumAnswers[question.id][i - 1]"
                        type="text"
                        class="flex-1 rounded-lg border border-[var(--gl-border)] px-3 py-2 text-sm outline-none"
                        :placeholder="'Item ' + i"
                    />
                </div>
                <button
                    type="button"
                    @click="addEnumItem(question.id)"
                    class="mt-1 text-xs font-medium hover:text-[var(--gl-text-primary)]"
                    style="color: var(--gl-text-secondary)"
                >
                    + Add item
                </button>
            </div>

            <div v-else-if="question.type === 'matching'" class="space-y-3">
                <p class="text-xs font-medium" style="color: var(--gl-text-secondary)">
                    Match each item on the left with the correct option on the right:
                </p>
                <div
                    v-for="pair in question.matching_pairs"
                    :key="pair.id"
                    class="flex flex-col gap-2 rounded-xl p-3 sm:flex-row sm:items-center sm:justify-between"
                    style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);"
                >
                    <span class="text-sm font-semibold" style="color: var(--gl-text-primary)">
                        {{ pair.left_text }}
                    </span>
                    <select
                        v-model="matchingAnswers[question.id][pair.id]"
                        class="rounded-lg border px-3 py-1.5 text-xs outline-none"
                        style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-primary);"
                    >
                        <option value="">-- Select matching option --</option>
                        <option
                            v-for="optPair in shuffledMatchingOptions(question)"
                            :key="optPair.id"
                            :value="optPair.right_text"
                        >
                            {{ optPair.right_text }}
                        </option>
                    </select>
                </div>
            </div>

            <div v-else class="space-y-2">
                <div
                    v-if="starCompassAvailable && question.options && question.options.length >= 3 && !eliminatedOptions[question.id]"
                    class="mb-2 flex justify-end"
                >
                    <button
                        type="button"
                        @click="applyStarCompassReward(question.id)"
                        :disabled="usingCompass"
                        class="flex items-center gap-1.5 rounded-lg border border-cyan-400/40 bg-cyan-500/10 px-2.5 py-1 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-500/20 disabled:opacity-50 dark:text-cyan-300"
                        title="Compass: eliminate wrong choices"
                    >
                        <span>🧭</span>
                        <span>{{ usingCompass ? 'Consulting...' : `Compass (remove ${compassEliminate})` }}</span>
                        <span v-if="compassCount > 1" class="opacity-70">×{{ compassCount }}</span>
                    </button>
                </div>

                <label
                    v-for="option in question.options"
                    :key="option.id"
                    class="flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 text-sm transition-colors"
                    :class="[
                        answers[question.id] === option.id
                            ? 'border-[var(--gl-primary)] bg-[var(--gl-primary-glow)] text-[var(--gl-text-primary)]'
                            : 'border-[var(--gl-border)] text-[var(--gl-text-secondary)] hover:bg-[var(--gl-surface-2)]',
                        eliminatedOptions[question.id]?.includes(option.id)
                            ? 'opacity-40 pointer-events-none line-through'
                            : ''
                    ]"
                >
                    <input
                        v-model="answers[question.id]"
                        type="radio"
                        :name="`question-${question.id}`"
                        :value="option.id"
                        :disabled="eliminatedOptions[question.id]?.includes(option.id)"
                        class="h-4 w-4"
                        style="color: var(--gl-primary)"
                    />
                    {{ option.option_text }}
                    <span
                        v-if="eliminatedOptions[question.id]?.includes(option.id)"
                        class="ml-auto text-xs text-red-500 font-normal no-underline"
                    >
                        Eliminated
                    </span>
                </label>
            </div>
        </div>

        <div class="flex justify-end">
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
                {{ submitting ? 'Submitting...' : 'Submit quiz' }}
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
                    style="background: rgba(59, 130, 246, 0.15)"
                >
                    <LoaderCircle
                        class="h-6 w-6 animate-spin"
                        style="color: var(--gl-primary)"
                        :stroke-width="2.5"
                    />
                </div>
                <h3
                    class="text-base font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    Submitting Quiz...
                </h3>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    Recording your answers and evaluating scores. Redirecting automatically...
                </p>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Clock, LoaderCircle, Send } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useAutoSave } from '@/composables/useAutoSave';

const props = defineProps<{
    quiz: any;
    startedAt: string;
    availableRewards?: {
        time_warp?: boolean;
        time_warp_count?: number;
        time_warp_seconds?: number | null;
        brain_shield?: boolean;
        shield_protect?: number;
        star_compass?: boolean;
        compass_count?: number;
        compass_eliminate?: number | null;
    };
}>();

const timeWarpCount = ref(props.availableRewards?.time_warp_count ?? (props.availableRewards?.time_warp ? 1 : 0));
const timeWarpSeconds = ref(props.availableRewards?.time_warp_seconds ?? 30);
const timeWarpAvailable = computed(() => timeWarpCount.value > 0);
const shieldProtect = ref(props.availableRewards?.shield_protect ?? (props.availableRewards?.brain_shield ? 1 : 0));
const brainShieldActive = computed(() => shieldProtect.value > 0);
const compassCount = ref(props.availableRewards?.compass_count ?? (props.availableRewards?.star_compass ? 1 : 0));
const compassEliminate = ref(props.availableRewards?.compass_eliminate ?? 2);
const starCompassAvailable = computed(() => compassCount.value > 0);
const usingTimeWarp = ref(false);
const usingCompass = ref(false);
const eliminatedOptions = ref<Record<number, number[]>>({});

async function applyTimeWarpReward() {
    if (usingTimeWarp.value || !timeWarpAvailable.value) {
return;
}

    usingTimeWarp.value = true;

    try {
        const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;
        const res = await fetch('/student/rewards/use-time-warp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ quiz_id: props.quiz.id }),
        });
        const data = await res.json();

        if (data.ok && remainingSeconds.value !== null) {
            remainingSeconds.value += (data.added_seconds || 30);
            timeWarpCount.value = data.remaining ?? 0;

            if (data.next_seconds) {
                timeWarpSeconds.value = data.next_seconds;
            }
        } else if (!data.ok && data.message) {
            errorMessage.value = data.message;
        }
    } catch {
        // Fallback
    } finally {
        usingTimeWarp.value = false;
    }
}

async function applyStarCompassReward(questionId: number) {
    if (usingCompass.value || !starCompassAvailable.value) {
return;
}

    usingCompass.value = true;

    try {
        const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;
        const res = await fetch('/student/rewards/use-star-compass', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ question_id: questionId }),
        });
        const data = await res.json();

        if (data.ok && Array.isArray(data.eliminated_options)) {
            eliminatedOptions.value[questionId] = data.eliminated_options;

            if (data.eliminated_options.includes(answers.value[questionId])) {
                answers.value[questionId] = null;
            }

            compassCount.value = data.remaining ?? 0;

            if (data.next_eliminate) {
                compassEliminate.value = data.next_eliminate;
            }
        } else if (!data.ok && data.message) {
            errorMessage.value = data.message;
        }
    } catch {
        // Fallback
    } finally {
        usingCompass.value = false;
    }
}

const page = usePage();
const flash = computed(() => (page.props as any).flash);
const submitting = ref(false);
const errorMessage = ref('');
const answers = ref<Record<number, any>>({});
const enumAnswers = ref<Record<number, string[]>>({});
const matchingAnswers = ref<Record<number, Record<number, string>>>({});
const restored = ref(false);
let autoSave: ReturnType<typeof useAutoSave> | null = null;

props.quiz.questions.forEach((q: any) => {
    answers.value[q.id] = null;

    if (q.type === 'enumeration') {
        const defaultLen = Math.max(1, q.options?.length || 1);
        enumAnswers.value[q.id] = Array(defaultLen).fill('');
    } else if (q.type === 'matching') {
        matchingAnswers.value[q.id] = {};
    }
});

function enumCount(question: any): number {
    if (!enumAnswers.value[question.id]) {
        enumAnswers.value[question.id] = [''];
    }

    return enumAnswers.value[question.id].length;
}

function addEnumItem(questionId: number) {
    if (!enumAnswers.value[questionId]) {
        enumAnswers.value[questionId] = [''];
    }

    enumAnswers.value[questionId].push('');
}

const matchingOptionsCache = ref<Record<number, any[]>>({});

function shuffledMatchingOptions(question: any) {
    if (!matchingOptionsCache.value[question.id]) {
        const pairs = [...(question.matching_pairs || [])];

        for (let i = pairs.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [pairs[i], pairs[j]] = [pairs[j], pairs[i]];
        }

        matchingOptionsCache.value[question.id] = pairs;
    }

    return matchingOptionsCache.value[question.id];
}

const remainingSeconds = ref<number | null>(null);
let timer: ReturnType<typeof setInterval> | null = null;

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

        submit(true);
    }
}

onMounted(() => {
    const savedKey = 'autosave-quiz-' + props.quiz.id;
    autoSave = useAutoSave(savedKey, () => ({ answers: answers.value }));

    const saved = autoSave.load();

    if (saved?.answers) {
        Object.assign(answers.value, saved.answers);
        restored.value = true;
    }

    autoSave.start();

    if (props.quiz.time_limit_minutes && props.startedAt) {
        const start = new Date(props.startedAt).getTime();
        const deadline = start + props.quiz.time_limit_minutes * 60 * 1000;
        remainingSeconds.value = Math.max(
            0,
            Math.floor((deadline - Date.now()) / 1000),
        );
        timer = setInterval(tick, 1000);
    }

    window.addEventListener('pageshow', handlePageShow);
});

onUnmounted(() => {
    if (timer) {
clearInterval(timer);
}

    if (autoSave) {
autoSave.stop();
}

    window.removeEventListener('pageshow', handlePageShow);
});

function handlePageShow(event: PageTransitionEvent) {
    if (event.persisted) {
        window.location.reload();
    }
}

const answeredCount = computed(
    () => Object.values(answers.value).filter((v) => v !== null && v !== undefined && String(v).trim() !== '').length,
);

function submit(auto = false) {
    if (submitting.value) {
return;
}

    if (!auto && !confirm('Submit quiz? This cannot be undone.')) {
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

    const answersPayload: Record<number, any> = {};
    props.quiz.questions.forEach((q: any) => {
        if (q.type === 'enumeration') {
            const items = (enumAnswers.value[q.id] || []).filter((v) => String(v ?? '').trim() !== '');

            if (items.length) {
                answersPayload[q.id] = items;
            }
        } else if (q.type === 'matching') {
            const pairs = matchingAnswers.value[q.id] || {};

            if (Object.keys(pairs).length) {
                answersPayload[q.id] = pairs;
            }
        } else {
            const val = answers.value[q.id];

            if (val !== undefined && val !== null && String(val).trim() !== '') {
                answersPayload[q.id] = val;
            }
        }
    });

    router.post(
        `/student/quizzes/${props.quiz.id}/submit`,
        { answers: answersPayload },
        {
            preserveScroll: false,
            onError: (errs) => {
                submitting.value = false;
                const first = Object.values(errs)[0];
                errorMessage.value =
                    typeof first === 'string'
                        ? first
                        : 'Failed to submit quiz. Please try again.';
            },
            onFinish: () => {
                if (errorMessage.value) {
                    submitting.value = false;
                }
            },
        },
    );
}
</script>
