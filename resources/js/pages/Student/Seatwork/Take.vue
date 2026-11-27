<template>
    <Head :title="seatwork.title" />

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
            <div class="flex items-center gap-2.5">
                <Link
                    href="/student/seatworks"
                    class="mr-1 inline-flex items-center gap-1 text-xs font-semibold hover:underline"
                    style="color: var(--gl-text-muted)"
                >
                    <ArrowLeft class="h-3.5 w-3.5" :stroke-width="2" />
                    Seatworks
                </Link>
                <h2
                    class="text-lg font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    {{ seatwork.title }}
                </h2>
                <span
                    class="rounded bg-[var(--gl-border)] px-2 py-0.5 text-xs"
                    style="color: var(--gl-text-secondary)"
                    >Seatwork</span
                >
            </div>
            <p class="mt-1 text-sm" style="color: var(--gl-text-secondary)">
                {{ answeredCount }} of {{ seatwork.questions.length }} answered
            </p>
            <p
                v-if="seatwork.instructions"
                class="mt-2 text-sm"
                style="color: var(--gl-text-secondary)"
            >
                {{ seatwork.instructions }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <Link
                href="/student/seatworks"
                class="rounded-xl px-3.5 py-2 text-xs font-bold transition-all hover:scale-105"
                style="
                    background: var(--gl-surface);
                    color: var(--gl-text-secondary);
                    border: 1px solid var(--gl-border);
                "
            >
                Return to Seatworks
            </Link>
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

    <form @submit.prevent="submit(false)">
        <div
            v-for="(q, qi) in seatwork.questions"
            :key="q.id"
            class="card mb-4 p-5"
        >
            <p
                class="mb-3 text-sm font-medium"
                style="color: var(--gl-text-primary)"
            >
                {{ qi + 1 }}. {{ q.question_text }}
                <span
                    class="text-xs font-normal"
                    style="color: var(--gl-text-muted)"
                    >({{ q.points }} pt{{ q.points > 1 ? 's' : '' }})</span
                >
            </p>

            <template v-if="q.type === 'multiple_choice'">
                <div class="space-y-2">
                    <label
                        v-for="opt in q.options"
                        :key="opt.id"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 text-sm transition-colors"
                        :class="
                            answers[q.id] === opt.id
                                ? 'border-[var(--gl-primary)] bg-[var(--gl-primary-glow)] text-[var(--gl-text-primary)]'
                                : 'border-[var(--gl-border)] text-[var(--gl-text-secondary)] hover:bg-[var(--gl-surface-2)]'
                        "
                    >
                        <input
                            v-model="answers[q.id]"
                            type="radio"
                            :name="'q-' + q.id"
                            :value="opt.id"
                            class="h-4 w-4"
                        />
                        {{ opt.option_text }}
                    </label>
                </div>
            </template>

            <template v-if="q.type === 'true_false'">
                <div class="space-y-2">
                    <label
                        v-for="opt in q.options"
                        :key="opt.id"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 text-sm transition-colors"
                        :class="
                            answers[q.id] === opt.id
                                ? 'border-[var(--gl-primary)] bg-[var(--gl-primary-glow)] text-[var(--gl-text-primary)]'
                                : 'border-[var(--gl-border)] text-[var(--gl-text-secondary)] hover:bg-[var(--gl-surface-2)]'
                        "
                    >
                        <input
                            v-model="answers[q.id]"
                            type="radio"
                            :name="'q-' + q.id"
                            :value="opt.id"
                            class="h-4 w-4"
                        />
                        {{ opt.option_text }}
                    </label>
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

            <template v-if="q.type === 'essay'">
                <div class="space-y-3">
                    <div
                        v-if="q.criteria"
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
                            {{ q.criteria }}
                        </p>
                    </div>
                    <div>
                        <textarea
                            v-model="answers[q.id]"
                            rows="6"
                            class="input-field"
                            placeholder="Write your response here..."
                            required
                        ></textarea>
                    </div>
                </div>
            </template>

            <template v-if="q.type === 'enumeration'">
                <div
                    v-for="i in enumCount(q)"
                    :key="i"
                    class="mb-1.5 flex items-center gap-2"
                >
                    <span class="text-xs" style="color: var(--gl-text-muted)"
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
                    class="mt-1 text-xs font-medium hover:text-[var(--gl-text-primary)]"
                    style="color: var(--gl-text-secondary)"
                >
                    + Add item
                </button>
            </template>

            <template v-if="q.type === 'matching'">
                <div class="mt-2 grid grid-cols-2 gap-4">
                    <div>
                        <p
                            class="mb-1.5 text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                        >
                            Items
                        </p>
                        <div
                            v-for="pair in q.matching_pairs"
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
                            v-for="pair in q.matching_pairs"
                            :key="pair.id"
                            class="mb-1.5"
                        >
                            <select
                                v-model="matchingAnswers[q.id][pair.id]"
                                class="w-full rounded-lg border border-[var(--gl-border)] px-2.5 py-2 text-sm outline-none"
                            >
                                <option value="" disabled>Select match</option>
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
            </template>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <Link
                href="/student/seatworks"
                class="rounded-xl px-4 py-2.5 text-xs font-bold transition-all hover:scale-105"
                style="
                    background: var(--gl-surface);
                    color: var(--gl-text-secondary);
                    border: 1px solid var(--gl-border);
                "
            >
                Cancel & Return
            </Link>
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
                {{ submitting ? 'Submitting...' : 'Submit seatwork' }}
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
                    style="background: rgba(16, 185, 129, 0.15)"
                >
                    <LoaderCircle
                        class="h-6 w-6 animate-spin"
                        style="color: var(--gl-success)"
                        :stroke-width="2.5"
                    />
                </div>
                <h3
                    class="text-base font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    Submitting Seatwork...
                </h3>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    Grading your responses and computing XP. Redirecting automatically...
                </p>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Clock, LoaderCircle, Send } from '@lucide/vue';
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';
import { useAutoSave } from '@/composables/useAutoSave';

const props = defineProps<{ seatwork: any; startedAt: string }>();

const page = usePage();
const flash = computed(() => (page.props as any).flash);

const answers = reactive<Record<number, any>>({});
const enumAnswers = reactive<Record<number, string[]>>({});
const matchingAnswers = reactive<Record<number, Record<number, string>>>({});
const submitting = ref(false);
const errorMessage = ref('');
const restored = ref(false);
let autoSave: ReturnType<typeof useAutoSave> | null = null;

props.seatwork.questions.forEach((q: any) => {
    if (q.type === 'enumeration') {
        enumAnswers[q.id] = [''];
    } else if (q.type === 'matching') {
        matchingAnswers[q.id] = {};
    } else {
        answers[q.id] =
            q.type === 'identification' || q.type === 'essay' ? '' : null;
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
        rightTexts.value[q.id] = [...q.matching_pairs].sort(
            () => Math.random() - 0.5,
        );
    }

    return rightTexts.value[q.id];
}

const answeredCount = computed(() => {
    const answered = props.seatwork.questions.filter((q: any) => {
        if (q.type === 'enumeration') {
return (enumAnswers[q.id] || []).some((i: string) => i.trim());
}

        if (q.type === 'matching') {
return Object.values(matchingAnswers[q.id] || {}).some((v) => v);
}

        return (
            answers[q.id] !== null &&
            answers[q.id] !== undefined &&
            answers[q.id] !== ''
        );
    });

    return answered.length;
});

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
    const savedKey = 'autosave-seatwork-' + props.seatwork.id;
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

    if (props.seatwork.time_limit_minutes && props.startedAt) {
        const start = new Date(props.startedAt).getTime();
        const deadline = start + props.seatwork.time_limit_minutes * 60 * 1000;
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

function submit(auto = false) {
    if (submitting.value) {
return;
}

    if (!auto && !confirm('Submit seatwork? This cannot be undone.')) {
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
    props.seatwork.questions.forEach((q: any) => {
        if (q.type === 'enumeration') {
            const items = (enumAnswers[q.id] || []).filter((i: string) =>
                i.trim(),
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
    router.post(
        '/student/seatworks/' + props.seatwork.id + '/submit',
        { answers: answersPayload },
        {
            preserveScroll: false,
            onError: (errs) => {
                submitting.value = false;
                const first = Object.values(errs)[0];
                errorMessage.value =
                    typeof first === 'string'
                        ? first
                        : 'Failed to submit seatwork. Please try again.';
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
