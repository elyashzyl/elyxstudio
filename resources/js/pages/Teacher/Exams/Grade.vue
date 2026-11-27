<template>
    <Head :title="'Grade - ' + exam.title" />
    <Link :href="returnUrl" class="mb-5 inline-flex items-center gap-1.5 text-xs font-semibold transition-colors hover:opacity-80" style="color: var(--gl-text-muted)">
        <ArrowLeft class="h-3.5 w-3.5" :stroke-width="2.5" /> {{ returnUrl.includes('/results') ? 'Back to all results' : 'Back to exam' }}
    </Link>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest" style="color: var(--gl-text-muted)">Grade · Exam Practicals</p>
            <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold tracking-tight" style="color: var(--gl-text-primary)">{{ exam.title }}</h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">{{ attempts.length }} attempt{{ attempts.length === 1 ? '' : 's' }} to grade</p>
        </div>
        <button
            v-if="attempts.length"
            type="button"
            @click="aiGradeAll"
            :disabled="isGradingAll"
            class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-bold transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
            style="background: rgba(124, 58, 237, 0.12); color: var(--gl-secondary); border: 1px solid rgba(124, 58, 237, 0.3);"
        >
            <LoaderCircle v-if="isGradingAll" :size="14" class="animate-spin" />
            <Sparkles v-else :size="14" />
            <span>{{ isGradingAll ? 'Grading All Attempts...' : 'AI Grade All Attempts' }}</span>
        </button>
    </div>

    <div v-for="a in attempts" :key="a.id" :id="'attempt-' + a.id" class="mb-4 overflow-hidden rounded-2xl" style="background: var(--gl-surface); border: 1px solid var(--gl-border);">
        <div class="flex flex-wrap items-center gap-3 px-5 py-3.5" style="border-bottom: 1px solid var(--gl-border);">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-[11px] font-bold text-white"
                :style="{ background: avatarBg(a.student?.name) }">
                {{ (a.student?.name || '?').charAt(0).toUpperCase() }}
            </div>
            <div class="min-w-0 flex-1">
                <h3 class="truncate text-sm font-bold" style="color: var(--gl-text-primary)">{{ a.student?.name }}</h3>
                <p class="truncate text-[11px]" style="color: var(--gl-text-muted)">Grade {{ a.student?.grade ?? '—' }} · Total: {{ a.total_score ?? 0 }} / {{ exam.max_score }}</p>
            </div>
            <button
                type="button"
                @click="runAiGrade(a)"
                :disabled="aiGradingAttempt === a.id"
                class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                style="background: rgba(124, 58, 237, 0.12); color: var(--gl-secondary); border: 1px solid rgba(124, 58, 237, 0.3);"
            >
                <LoaderCircle v-if="aiGradingAttempt === a.id" :size="13" class="animate-spin" />
                <Sparkles v-else :size="13" />
                <span>{{ aiGradingAttempt === a.id ? 'AI Evaluating...' : 'AI Grade via Criteria' }}</span>
            </button>
        </div>

        <div v-if="aiBanners[a.id]" class="mx-5 my-3 rounded-xl p-3 text-xs flex items-center justify-between"
            style="background: rgba(124, 58, 237, 0.12); color: var(--gl-secondary); border: 1px solid rgba(124, 58, 237, 0.25);">
            <div class="flex items-center gap-2">
                <Sparkles :size="14" class="shrink-0" />
                <span>{{ aiBanners[a.id] }}</span>
            </div>
            <button type="button" @click="delete aiBanners[a.id]" class="font-bold opacity-60 hover:opacity-100 ml-2">Dismiss</button>
        </div>

        <template v-for="sec in practicalSections" :key="sec.id">
            <div class="px-5 py-3" style="border-bottom: 1px solid var(--gl-border);">
                <h4 class="mb-2 text-[11px] font-extrabold uppercase" style="color: var(--gl-text-muted); letter-spacing: 0.08em;">{{ sec.title }}</h4>
                <div v-for="c in sec.criteria" :key="c.id" class="mb-2 rounded-xl p-3" style="background: var(--gl-surface-2);">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-semibold" style="color: var(--gl-text-primary)">{{ c.criterion_name }}</p>
                            <p v-if="c.description" class="truncate text-[11px]" style="color: var(--gl-text-muted)">{{ c.description }}</p>
                        </div>
                        <span class="shrink-0 text-[10px] font-bold tabular-nums" style="color: var(--gl-text-muted);">max {{ c.max_points }}</span>
                    </div>
                    <div class="mt-2 flex items-center gap-2">
                        <input type="number" :min="0" :max="c.max_points"
                            :value="getScore(a, c.id)"
                            @input="setScore(a.id, c.id, ($event.target as HTMLInputElement).value)"
                            class="w-20 rounded-xl border px-2.5 py-2 text-center text-sm font-extrabold tabular-nums outline-none"
                            style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                        <input type="text"
                            :value="getComment(a, c.id)"
                            @input="setComment(a.id, c.id, ($event.target as HTMLInputElement).value)"
                            placeholder="Comment"
                            class="min-w-0 flex-1 rounded-xl border px-3 py-2 text-xs outline-none"
                            style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />

                    </div>
                </div>
            </div>
        </template>

        <div class="flex flex-wrap items-center justify-end gap-2 p-4">

            <button
                type="button"
                @click="saveGrades(a)"
                :disabled="savingAttempt === a.id"
                class="inline-flex items-center gap-1.5 rounded-full px-5 py-2.5 text-xs font-bold transition-all hover:scale-[1.02] disabled:opacity-50"
                :style="hasNextAttempt(a)
                    ? { background: 'var(--gl-surface-2)', color: 'var(--gl-text-primary)', border: '1px solid var(--gl-border)' }
                    : { background: 'linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))', color: '#fff', boxShadow: '0 0 14px var(--gl-primary-glow)' }"
            >
                <LoaderCircle v-if="savingAttempt === a.id && saveTarget === 'current'" class="h-4 w-4 animate-spin" :stroke-width="2" />
                <Check v-else class="h-4 w-4" :stroke-width="2" />
                {{ hasNextAttempt(a) ? 'Save Grades' : 'Save & Done' }}
            </button>
        </div>

    </div>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Check, Sparkles, LoaderCircle } from '@lucide/vue';
import { ref, computed, onMounted } from 'vue';

const props = defineProps<{ exam: any; attempts: any[]; returnTo?: string }>();

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

    return `/teacher/exams/${props.exam.id}`;
});

function scrollAttemptIntoView(id: number | string) {
    let attempts = 0;
    const maxAttempts = 25;
    const interval = setInterval(() => {
        attempts++;
        const el = document.getElementById(`attempt-${id}`);

        if (el) {
            clearInterval(interval);
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else if (attempts >= maxAttempts) {
            clearInterval(interval);
        }
    }, 40);
}

onMounted(() => {
    if (typeof window !== 'undefined' && window.location.hash.startsWith('#attempt-')) {
        const id = window.location.hash.replace('#attempt-', '');

        if (id) {
            scrollAttemptIntoView(id);
        }
    }
});

const AVATAR_BG = [
    'linear-gradient(135deg, #3B82F6, #7C3AED)',
    'linear-gradient(135deg, #10B981, #06B6D4)',
    'linear-gradient(135deg, #F59E0B, #EF4444)',
    'linear-gradient(135deg, #EC4899, #8B5CF6)',
    'linear-gradient(135deg, #14B8A6, #3B82F6)',
];

function avatarBg(name: string): string {
    let h = 0;
    const n = name || '';

    for (let i = 0; i < n.length; i++) {
 h = n.charCodeAt(i) + ((h << 5) - h); 
}

    return AVATAR_BG[Math.abs(h) % AVATAR_BG.length];
}

const practicalSections = computed(() => props.exam.sections.filter((s: any) => s.section_type === 'practical'));


const grades = ref<
    Record<
        number,
        { scores: Record<number, number>; comments: Record<number, string> }
    >
>({});

const aiGradingAttempt = ref<number | null>(null);
const aiBanners = ref<Record<number, string>>({});

async function runAiGrade(attempt: any) {
    aiGradingAttempt.value = attempt.id;
    delete aiBanners.value[attempt.id];

    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        const res = await fetch(`/teacher/exams/attempts/${attempt.id}/ai-grade`, {
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
            if (!grades.value[attempt.id]) {
                grades.value[attempt.id] = { scores: {}, comments: {} };
            }

            for (const [k, v] of Object.entries(data.scores)) {
                grades.value[attempt.id].scores[Number(k)] = Number(v);
            }

            if (data.comments) {
                for (const [k, v] of Object.entries(data.comments)) {
                    grades.value[attempt.id].comments[Number(k)] = String(v);
                }
            }

            aiBanners.value[attempt.id] = 'AI evaluated practical criteria based on student performance. Review and save below.';
        } else {
            aiBanners.value[attempt.id] = data.message || 'AI evaluation returned an unexpected result.';
        }
    } catch (e: any) {
        aiBanners.value[attempt.id] = 'AI grading request failed: ' + (e.message || 'Network error');
    } finally {
        aiGradingAttempt.value = null;
    }
}

function getScore(attempt: any, criterionId: number): number {
    const g = grades.value[attempt.id];

    if (g?.scores[criterionId] !== undefined) {
return g.scores[criterionId];
}

    const existing = attempt.section_scores?.find(
        (s: any) => s.exam_criterion_id === criterionId,
    );

    return existing?.score ?? 0;
}

function setScore(attemptId: number, criterionId: number, value: string) {
    if (!grades.value[attemptId]) {
grades.value[attemptId] = { scores: {}, comments: {} };
}

    grades.value[attemptId].scores[criterionId] = parseInt(value) || 0;
}

function getComment(attempt: any, criterionId: number): string {
    const g = grades.value[attempt.id];

    if (g?.comments[criterionId] !== undefined) {
return g.comments[criterionId];
}

    const existing = attempt.section_scores?.find(
        (s: any) => s.exam_criterion_id === criterionId,
    );

    return existing?.comment ?? '';
}

function setComment(attemptId: number, criterionId: number, value: string) {
    if (!grades.value[attemptId]) {
grades.value[attemptId] = { scores: {}, comments: {} };
}

    grades.value[attemptId].comments[criterionId] = value;
}

const savingAttempt = ref<number | null>(null);
const saveTarget = ref<'current' | 'next'>('current');
const isGradingAll = ref(false);

function aiGradeAll() {
    if (isGradingAll.value) {
return;
}

    isGradingAll.value = true;
    router.post(
        `/teacher/exams/${props.exam.id}/ai-grade-all`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isGradingAll.value = false;
            },
        },
    );
}

function getNextAttempt(attempt: any) {
    const idx = props.attempts.findIndex((a: any) => Number(a.id) === Number(attempt.id));

    if (idx >= 0 && idx < props.attempts.length - 1) {
        return props.attempts[idx + 1];
    }

    return null;
}

function hasNextAttempt(attempt: any): boolean {
    return !!getNextAttempt(attempt);
}


function saveGrades(attempt: any, nextId: number | null = null) {
    if (!grades.value[attempt.id]) {
        grades.value[attempt.id] = { scores: {}, comments: {} };
    }

    const g = grades.value[attempt.id];

    for (const sec of practicalSections.value) {
        for (const c of sec.criteria) {
            if (g.scores[c.id] === undefined) {
                g.scores[c.id] = getScore(attempt, c.id);
            }

            if (g.comments[c.id] === undefined) {
                g.comments[c.id] = getComment(attempt, c.id);
            }
        }
    }

    savingAttempt.value = attempt.id;
    saveTarget.value = nextId ? 'next' : 'current';
    router.post(
        `/teacher/exams/attempts/${attempt.id}/grade`,
        {
            scores: g.scores,
            comments: g.comments,
            next_attempt_id: nextId,
            save_and_next: !!nextId,
            return_to: returnUrl.value,
        },
        {
            preserveScroll: !nextId,
            preserveState: !nextId,
            onFinish: () => {
                savingAttempt.value = null;
            },
            onSuccess: () => {
                if (!nextId) {
                    scrollAttemptIntoView(attempt.id);
                }
            },
        },
    );
}
</script>
