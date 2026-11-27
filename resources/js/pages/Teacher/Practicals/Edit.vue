<template>
    <Head :title="'Edit ' + practical.title" />

    <Link :href="`/teacher/practicals/${practical.id}`" class="mb-5 inline-flex items-center gap-1.5 text-xs font-semibold transition-colors hover:opacity-80" style="color: var(--gl-text-muted);">
        <ArrowLeft class="h-3.5 w-3.5" :stroke-width="2.5" /> Back to practical
    </Link>

    <!-- Header -->
    <div class="mb-5">
        <p class="text-xs font-semibold uppercase tracking-widest" style="color: var(--gl-text-muted)">Edit · Practical</p>
        <h1 class="mt-1 truncate text-2xl sm:text-3xl font-extrabold tracking-tight" style="color: var(--gl-text-primary)">{{ practical.title }}</h1>
        <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">Adjust rubric criteria, scoring, and settings</p>

    </div>

    <form class="space-y-4" @submit.prevent="submit">
        <!-- Details -->
        <div class="rounded-2xl p-5" style="background: rgba(124,58,237,0.05); border: 1px solid rgba(124,58,237,0.16);">
            <div class="mb-3 flex items-center gap-2">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg" style="background: rgba(124,58,237,0.14);">
                    <Pencil class="h-3.5 w-3.5" style="color: var(--gl-secondary);" :stroke-width="2" />
                </div>
                <h3 class="text-sm font-bold" style="color: var(--gl-text-primary)">Mission Details</h3>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Title</label>
                    <input v-model="form.title" type="text" required
                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                        style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Instructions</label>
                    <textarea v-model="form.instructions" rows="2"
                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                        style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);"></textarea>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Grade</label>
                    <select v-model="selectedGradeId" class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none" style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" required>
                        <option :value="null">Select a grade</option>
                        <option v-for="gl in gradeLevels" :key="gl.id" :value="gl.id">{{ gl.name }}</option>
                    </select>
                    <p v-if="form.errors.grade_level_ids" class="mt-1 text-xs" style="color: var(--gl-danger)">{{ form.errors.grade_level_ids }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Time (min)</label>
                        <input v-model="form.time_limit_minutes" type="number" min="1"
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                            style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Max score</label>
                        <input v-model.number="form.max_score" type="number" min="1"
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-bold tabular-nums outline-none"
                            style="background: rgba(251,191,36,0.08); color: var(--gl-accent); border-color: rgba(251,191,36,0.25);" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">XP reward</label>
                        <input v-model.number="form.xp_reward" type="number" min="1" max="500"
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-bold tabular-nums outline-none"
                            style="background: rgba(124,58,237,0.08); color: var(--gl-secondary); border-color: rgba(124,58,237,0.25);" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Attempts</label>
                        <input v-model.number="form.max_attempts" type="number" min="1" max="10"
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                            style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                    </div>
                </div>
                <div v-if="schoolYears?.length">
                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">School year</label>
                    <select v-model="form.school_year_id" class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none" style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" required>
                        <option :value="null">Select a school year</option>
                        <option v-for="year in schoolYears" :key="year.id" :value="year.id">{{ year.name }}</option>
                    </select>
                </div>
                <div v-if="quarters?.length">
                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Quarter</label>
                    <select v-model="form.quarter_id"
                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                        style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);">
                        <option :value="null">All Quarters / Unassigned</option>
                        <option v-for="qtr in quarters" :key="qtr.id" :value="qtr.id">
                            {{ qtr.code }} · {{ qtr.name }}
                        </option>
                    </select>
                </div>
                <div v-if="teachers?.length">
                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Assign to teacher</label>
                    <select v-model="form.teacher_id"
                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                        style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);">

                        <option :value="null">Select a teacher</option>
                        <option v-for="t in teachers" :key="t.id" :value="t.id">
                            {{ t.name }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Rubric -->
        <div class="rounded-2xl p-5" style="background: rgba(251,191,36,0.05); border: 1px solid rgba(251,191,36,0.16);">
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg" style="background: rgba(251,191,36,0.14);">
                        <Award class="h-3.5 w-3.5" style="color: var(--gl-accent);" :stroke-width="2" />
                    </div>
                    <h3 class="text-sm font-bold" style="color: var(--gl-text-primary)">Rubric Criteria</h3>
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold tabular-nums" style="background: var(--gl-surface-2); color: var(--gl-text-muted);">{{ form.criteria.length }}</span>
                </div>
                <button type="button" @click="addCriterion" title="Add criterion"
                    class="flex h-8 w-8 items-center justify-center rounded-full text-white transition-all hover:scale-105"
                    style="background: linear-gradient(135deg, var(--gl-accent), #F59E0B); box-shadow: 0 0 10px var(--gl-accent-glow);">
                    <Plus class="h-4 w-4" :stroke-width="2.5" />
                </button>
            </div>

            <div v-for="(c, ci) in form.criteria" :key="ci" class="mb-2.5 rounded-2xl p-3.5" style="background: var(--gl-surface); border: 1px solid var(--gl-border);">
                <div class="mb-2 flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <label class="mb-1.5 block text-xs font-bold" style="color: var(--gl-accent);">Criterion {{ ci + 1 }}</label>
                        <input v-model="c.criterion_name" type="text" required
                            class="w-full rounded-xl border px-3 py-2 text-sm outline-none"
                            style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <div class="w-[70px]">
                            <label class="mb-1.5 block text-center text-[10px] font-bold" style="color: var(--gl-text-muted);">Max pts</label>
                            <input v-model.number="c.max_points" type="number" min="1"
                                class="w-full rounded-xl border px-2 py-2 text-center text-xs font-bold tabular-nums outline-none"
                                style="background: rgba(251,191,36,0.08); color: var(--gl-accent); border-color: rgba(251,191,36,0.25);" />
                        </div>
                        <button v-if="form.criteria.length > 1" type="button" title="Remove"
                            class="mt-5 flex h-8 w-8 items-center justify-center rounded-full transition-colors hover:bg-[var(--gl-danger-bg)]" style="color: var(--gl-danger);" @click="form.criteria.splice(ci, 1)">

                            <Trash2 class="h-3.5 w-3.5" :stroke-width="2" />
                        </button>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Description</label>
                    <input v-model="c.description" type="text"
                        class="w-full rounded-xl border px-3 py-2 text-sm outline-none"
                        style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);" />

                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5">
            <Link :href="`/teacher/practicals/${practical.id}`" class="rounded-full px-5 py-2.5 text-xs font-bold transition-all hover:scale-[1.02]" style="background: var(--gl-surface-2); color: var(--gl-text-primary); border: 1px solid var(--gl-border);">Cancel</Link>
            <button type="submit" :disabled="form.processing" class="rounded-full px-5 py-2.5 text-xs font-bold text-white transition-all hover:scale-[1.02] disabled:opacity-50"
                style="background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary)); box-shadow: 0 0 14px var(--gl-primary-glow);">Update Practical</button>

        </div>
    </form>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, Trash2, ArrowLeft, Check, Pencil, Award } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    practical: any;
    teachers?: any[];
    gradeLevels: any[];
    quarters?: any[];
    schoolYears?: any[];
    defaultSchoolYearId?: number | null;
}>();

function mapPracticalToForm(p: any) {
    return {
        title: p.title,
        instructions: p.instructions ?? '',
        time_limit_minutes: p.time_limit_minutes ?? '',
        max_score: p.max_score ?? 100,
        xp_reward: p.xp_reward ?? 10,
        max_attempts: p.max_attempts ?? 3,
        teacher_id: p.teacher_id ?? null,
        school_year_id: p.school_year_id ?? props.defaultSchoolYearId ?? null,
        quarter_id: p.quarter_id ?? null,
        grade_level_ids: p.grade_levels
            ? p.grade_levels.map((g: any) => Number(g.id))
            : [],
        criteria: p.criteria.map((c: any) => ({
            criterion_name: c.criterion_name,
            description: c.description ?? '',
            max_points: c.max_points,
        })),
    };
}

const form = useForm(mapPracticalToForm(props.practical));

const selectedGradeId = computed<number | null>({
    get: () => form.grade_level_ids[0] ?? null,
    set: (value) => {
        form.grade_level_ids = value ? [Number(value)] : [];

        if (value) {
form.clearErrors('grade_level_ids');
}
    },
});
function addCriterion() {
    form.criteria.push({ criterion_name: '', description: '', max_points: 10 });
}
function submit() {
    if (!form.grade_level_ids.length) {
        form.setError('grade_level_ids', 'Please select at least one grade level.');

        return;
    }

    form.put(`/teacher/practicals/${props.practical.id}`);
}
</script>
