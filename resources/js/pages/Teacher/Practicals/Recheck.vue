<template>
    <Head :title="`Recheck: ${attempt.student?.name || 'Student'}`" />

    <Link :href="returnUrl" class="mb-5 inline-flex items-center gap-1.5 text-xs font-semibold transition-colors hover:opacity-80" style="color: var(--gl-text-muted)">
        <ArrowLeft class="h-3.5 w-3.5" :stroke-width="2.5" />
        {{ returnUrl.includes('/results') ? 'Back to all results' : 'Back to practical' }}
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
                    #{{ i + 1 }} · {{ att.student?.name || 'Student' }} ({{ att.total_score ?? 0 }}/{{ totalMax }})
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
            <p class="text-xs font-semibold uppercase tracking-widest" style="color: var(--gl-text-muted)">Recheck · Practical</p>
            <h1 class="mt-1 truncate text-2xl sm:text-3xl font-extrabold tracking-tight" style="color: var(--gl-text-primary)">{{ practical.title }}</h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">Student: <span class="font-bold" style="color: var(--gl-text-primary)">{{ attempt.student?.name || 'Student' }}</span> &middot; {{ attempt.student?.grade || '—' }}</p>
        </div>
        <div class="flex items-center gap-3 rounded-2xl px-4 py-3" style="background: var(--gl-surface); border: 1px solid var(--gl-border);">
            <div class="relative h-12 w-12">
                <svg viewBox="0 0 36 36" class="h-full w-full -rotate-90">
                    <circle cx="18" cy="18" r="15.915" fill="none" stroke-width="4" style="stroke: var(--gl-surface-2);" />
                    <circle cx="18" cy="18" r="15.915" fill="none" stroke-width="4" stroke-linecap="round" pathLength="100"
                        :stroke-dasharray="`${Math.round(totalComputed / totalMax * 100) || 0} 100`" :style="{ stroke: scoreColor }" />
                </svg>
                <span class="absolute inset-0 flex items-center justify-center text-[10px] font-extrabold tabular-nums" style="color: var(--gl-text-primary);">{{ Math.round(totalComputed / totalMax * 100) || 0 }}</span>
            </div>
            <div>
                <p class="text-xs" style="color: var(--gl-text-muted)">Score</p>
                <p class="text-base font-extrabold tabular-nums" style="color: var(--gl-text-primary)"><span :style="{ color: scoreColor }">{{ totalComputed }}</span> / {{ totalMax }}</p>
            </div>
        </div>
    </div>

    <div v-if="attempt.submission_text || attempt.submission_file" class="mb-4 overflow-hidden rounded-2xl" style="background: var(--gl-surface); border: 1px solid var(--gl-border);">
        <div class="px-5 py-3" style="border-bottom: 1px solid var(--gl-border);">
            <h3 class="text-sm font-bold" style="color: var(--gl-text-primary)">Student submission</h3>
        </div>
        <div v-if="attempt.submission_text" class="p-5 pt-4">
            <p class="mb-1.5 text-[11px] font-bold uppercase" style="color: var(--gl-text-muted); letter-spacing: 0.08em;">Code / Text</p>
            <pre class="rounded-xl border p-4 text-xs font-mono whitespace-pre-wrap" style="border-color: var(--gl-border); background: var(--gl-surface-2); color: var(--gl-text-primary); max-height: 400px; overflow-y: auto;">{{ attempt.submission_text }}</pre>
        </div>
        <div v-if="attempt.submission_file" class="px-5 pb-5">
            <p class="mb-1.5 text-[11px] font-bold uppercase" style="color: var(--gl-text-muted); letter-spacing: 0.08em;">Uploaded image</p>
            <img :src="attempt.submission_file_url" class="max-h-96 cursor-pointer rounded-xl" style="border: 1px solid var(--gl-border);" @click="previewImg = attempt.submission_file_url" />
        </div>
    </div>
    <div v-else class="mb-4 rounded-2xl px-5 py-6 text-center text-sm" style="background: var(--gl-surface); border: 1px solid var(--gl-border); color: var(--gl-text-muted);">
        No submission provided.

    </div>

    <Teleport to="body">
        <div
            v-if="previewImg"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
            @click.self="previewImg = null"
        >
            <div class="relative max-h-[90vh] max-w-3xl p-4">
                <button
                    @click="previewImg = null"
                    class="absolute -top-2 -right-2 rounded-full bg-surface p-1.5 shadow-md hover:bg-[var(--gl-surface-2)]"
                    style="color: var(--gl-text-secondary)"
                >
                    <X class="h-4 w-4" :stroke-width="2" />
                </button>
                <img
                    :src="previewImg"
                    class="max-h-[85vh] rounded-lg shadow-2xl"
                />
            </div>
        </div>
        <div
            v-if="showSaved"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.25); backdrop-filter: blur(2px)"
            @click.self="showSaved = false"
        >
            <div
                class="mx-4 w-full max-w-sm rounded-xl bg-surface p-6 text-center shadow-2xl"
            >
                <div
                    class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full"
                    style="background-color: #dceee3"
                >
                    <Check
                        class="h-7 w-7"
                        style="color: #2f7a54"
                        :stroke-width="2.5"
                    />
                </div>
                <h3
                    class="mb-1 text-lg font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    Grades saved
                </h3>
                <p class="mb-6 text-sm" style="color: var(--gl-text-secondary)">
                    The scores have been updated successfully.
                </p>
                <button @click="showSaved = false" class="btn-primary w-full">
                    Done
                </button>
            </div>
        </div>
    </Teleport>

    <form :key="attempt.id" @submit.prevent="save">
        <div class="mb-4 overflow-hidden rounded-2xl" style="background: var(--gl-surface); border: 1px solid var(--gl-border);">
            <div class="px-5 py-3 flex items-center justify-between" style="border-bottom: 1px solid var(--gl-border);">
                <h3 class="text-sm font-bold" style="color: var(--gl-text-primary)">Rubric scoring</h3>
                <button
                    type="button"
                    @click="runAiGrade()"
                    :disabled="aiGrading"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                    style="background: rgba(124, 58, 237, 0.12); color: var(--gl-secondary); border: 1px solid rgba(124, 58, 237, 0.3);"
                >
                    <LoaderCircle v-if="aiGrading" :size="13" class="animate-spin" />
                    <Sparkles v-else :size="13" />
                    <span>{{ aiGrading ? 'AI Evaluating...' : 'AI Grade via Criteria' }}</span>
                </button>
            </div>
            <div v-if="aiBanner" class="m-5 rounded-xl p-3 text-xs flex items-center justify-between"
                style="background: rgba(124, 58, 237, 0.12); color: var(--gl-secondary); border: 1px solid rgba(124, 58, 237, 0.25);">
                <div class="flex items-center gap-2">
                    <Sparkles :size="14" class="shrink-0" />
                    <span>{{ aiBanner }}</span>
                </div>
                <button type="button" @click="aiBanner = null" class="font-bold opacity-60 hover:opacity-100 ml-2">Dismiss</button>
            </div>
            <div class="space-y-2.5 p-5">
                <div v-for="(c, ci) in practical.criteria" :key="c.id" class="rounded-xl p-3.5" style="background: var(--gl-surface-2);">
                    <div class="mb-2 flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold" style="color: var(--gl-text-primary)">{{ ci + 1 }}. {{ c.criterion_name }}</p>
                            <p v-if="c.description" class="truncate text-[11px]" style="color: var(--gl-text-muted)">{{ c.description }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold tabular-nums" style="background: rgba(251,191,36,0.14); color: var(--gl-accent);">max {{ c.max_points }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <div class="flex items-center gap-2">
                            <label class="text-[11px] font-semibold" style="color: var(--gl-text-secondary)">Score</label>
                            <input v-model.number="form.scores[c.id]" type="number" min="0" :max="c.max_points"
                                class="w-20 rounded-xl border px-2.5 py-2 text-center text-sm font-extrabold tabular-nums outline-none"
                                style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                        </div>
                        <input v-model="form.comments[c.id]" type="text" placeholder="Feedback..."
                            class="min-w-[180px] flex-1 rounded-xl border px-3 py-2 text-sm outline-none"
                            style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />

                    </div>
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

        <div class="flex flex-wrap items-center gap-2.5">

            <button
                type="submit"
                :disabled="saving"
                class="flex items-center gap-1.5 rounded-full px-5 py-2.5 text-xs font-bold transition-all hover:scale-[1.02] disabled:opacity-50"
                :style="nextAttempt
                    ? { background: 'var(--gl-surface)', color: 'var(--gl-text-primary)', border: '1px solid var(--gl-border)' }
                    : { background: 'linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))', color: '#fff', boxShadow: '0 0 14px var(--gl-primary-glow)' }"
            >
                <Spinner v-if="saving && saveTarget === 'current'" class="h-4 w-4 animate-spin" :stroke-width="2" />
                <Check v-else class="h-4 w-4" :stroke-width="2" />
                {{ nextAttempt ? 'Save' : 'Save & Done' }}
            </button>
            <Link :href="returnUrl" class="rounded-full px-5 py-2.5 text-xs font-bold transition-all hover:scale-[1.02]"
                style="background: var(--gl-surface-2); color: var(--gl-text-secondary);">Cancel</Link>
        </div>
    </form>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    AlertCircle,
    Check,
    ChevronLeft,
    ChevronRight,
    Users,
    X,
    Sparkles,
    LoaderCircle,
} from '@lucide/vue';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import Spinner from '@/components/ui/spinner/Spinner.vue';

const previewImg = ref<string | null>(null);
const showSaved = ref(false);
const aiGrading = ref(false);
const aiBanner = ref<string | null>(null);

async function runAiGrade() {
    aiGrading.value = true;
    aiBanner.value = null;

    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        const res = await fetch(`/teacher/practicals/${props.practical.id}/ai-grade/${props.attempt.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await res.json();

        if (data.success && data.scores) {
            for (const [k, v] of Object.entries(data.scores)) {
                form.scores[Number(k)] = Number(v);
            }

            if (data.comments) {
                for (const [k, v] of Object.entries(data.comments)) {
                    form.comments[Number(k)] = String(v);
                }
            }

            aiBanner.value = 'AI evaluated all rubric criteria based on student submission. Review and save below.';
        } else {
            aiBanner.value = data.message || 'AI evaluation returned an unexpected result.';
        }
    } catch (e: any) {
        aiBanner.value = 'AI evaluation request failed: ' + (e.message || 'Network error');
    } finally {
        aiGrading.value = false;
    }
}

const props = defineProps<{
    practical: any;
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

    return `/teacher/practicals/${props.practical.id}#attempt-${props.attempt.id}`;
});

const saveTarget = ref<'current' | 'next'>('current');
const saveError = ref<string | null>(null);

const totalMax = computed(() =>
    props.practical.criteria.reduce((s: number, c: any) => s + c.max_points, 0),
);

onMounted(() => {
    const page = usePage();

    if ((page.props.flash as any)?.saved) {
        showSaved.value = true;
    }
});

watch(
    () => (usePage().props.flash as any)?.saved,
    (val) => {
        if (val) {
showSaved.value = true;
}
    },
);
const saving = ref(false);

const form = reactive<{
    scores: Record<number, number>;
    comments: Record<number, string>;
}>({
    scores: {},
    comments: {},
});

function syncFormWithAttempt(att: any) {
    if (!att || !props.practical?.criteria) {
return;
}

    const scores: Record<number, number> = {};
    const comments: Record<number, string> = {};

    for (const c of props.practical.criteria) {
        const existing = att.scores?.find(
            (s: any) => s.criterion_id === c.id,
        );
        scores[c.id] = existing?.score ?? 0;
        comments[c.id] = existing?.comment ?? '';
    }

    form.scores = scores;
    form.comments = comments;
}

function cleanFormData() {
    const scores: Record<number, number> = {};
    const comments: Record<number, string> = {};

    for (const c of props.practical?.criteria || []) {
        const raw = form.scores[c.id];
        scores[c.id] = typeof raw === 'number' && !isNaN(raw)
            ? Math.max(0, Math.min(c.max_points, Math.round(raw)))
            : 0;
        comments[c.id] = form.comments[c.id] || '';
    }

    return { scores, comments };
}

watch(
    () => props.attempt,
    (att) => {
        aiBanner.value = null;
        syncFormWithAttempt(att);
    },
    { immediate: true, deep: true },
);

const totalComputed = computed(() => {
    let total = 0;

    for (const c of props.practical.criteria) {
        total += Math.min(form.scores[c.id] ?? 0, c.max_points);
    }

    return total;
});

const scoreColor = computed(() => {
    const pct = totalMax.value
        ? (totalComputed.value / totalMax.value) * 100
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
    const pct = totalMax.value
        ? (totalComputed.value / totalMax.value) * 100
        : 0;

    if (pct >= 80) {
return '#DCEEE3';
}

    if (pct >= 60) {
return '#FFF3D6';
}

    return 'var(--gl-danger-bg)';
});

function computeReturnUrl(targetAttemptId: number): string {
    const base = returnUrl.value;

    if (base.includes('#result-attempt-')) {
        return base.replace(/#(result-attempt-[a-zA-Z]+-|result-attempt-)\d+.*$/, `#$1${targetAttemptId}`);
    }

    if (base.includes('#attempt-')) {
        return base.replace(/#attempt-\d+.*$/, `#attempt-${targetAttemptId}`);
    }

    if (base.includes('/results')) {
        return `${base}#result-attempt-practical-${targetAttemptId}`;
    }

    return `${base}#attempt-${targetAttemptId}`;
}

function save() {
    saving.value = true;
    saveTarget.value = 'current';
    saveError.value = null;
    router.put(
        `/teacher/practicals/${props.practical.id}/recheck/${props.attempt.id}`,
        {
            ...cleanFormData(),
            return_to: returnUrl.value,
        },
        {
            preserveScroll: false,
            preserveState: false,
            onError: (errs) => {
                saveError.value = Object.values(errs).flat().join(', ') || 'Failed to save practical grades.';
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
        `/teacher/practicals/${props.practical.id}/recheck/${targetId}?return_to=${encodeURIComponent(destReturn)}`,
    );
}
</script>
