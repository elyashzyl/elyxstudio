<template>
    <Head :title="'Edit ' + seatwork.title" />

    <Link
        :href="`/teacher/seatworks/${seatwork.id}`"
        class="mb-6 inline-flex items-center gap-1.5 text-sm font-medium hover:text-[var(--gl-primary)]"
        style="color: var(--gl-text-secondary)"
    >
        <ArrowLeft class="h-4 w-4" :stroke-width="2" />
        Back to seatwork
    </Link>

    <form class="space-y-6" @submit.prevent="submit">
        <div
            class="rounded-2xl p-6"
            style="
                background: var(--gl-surface);
                border: 1px solid var(--gl-border);
            "
        >
            <h3
                class="mb-4 text-sm font-semibold"
                style="color: var(--gl-text-primary)"
            >
                Seatwork details
            </h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="field-label">Title</label>
                    <input
                        v-model="form.title"
                        type="text"
                        class="input-field"
                        placeholder="e.g. Fractions practice set"
                        required
                    />
                    <p
                        v-if="form.errors.title"
                        class="mt-1 text-xs"
                        style="color: var(--gl-danger)"
                    >
                        {{ form.errors.title }}
                    </p>
                </div>
                <div class="sm:col-span-2">
                    <label class="field-label">Instructions (optional)</label>
                    <textarea
                        v-model="form.instructions"
                        rows="2"
                        class="input-field"
                        placeholder="Brief instructions for students"
                    ></textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="field-label">Grade</label>
                    <select v-model="selectedGradeId" class="input-field" required>
                        <option :value="null">Select a grade</option>
                        <option v-for="gl in gradeLevels" :key="gl.id" :value="gl.id">
                            {{ gl.name }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.grade_level_ids"
                        class="mt-1 text-xs"
                        style="color: var(--gl-danger)"
                    >
                        {{ form.errors.grade_level_ids }}
                    </p>
                </div>
                <div>
                    <label class="field-label"
                        >Time limit in minutes (optional)</label
                    >
                    <input
                        v-model="form.time_limit_minutes"
                        type="number"
                        min="1"
                        class="input-field"
                        placeholder="e.g. 20"
                    />
                </div>
                <div>
                    <label class="field-label">Allowed Attempts</label>
                    <select v-model.number="form.max_attempts" class="input-field">
                        <option :value="1">1 Attempt (Default)</option>
                        <option :value="2">2 Attempts</option>
                        <option :value="3">3 Attempts</option>
                        <option :value="5">5 Attempts</option>
                        <option :value="10">10 Attempts</option>
                    </select>
                </div>
                <div>
                    <label class="field-label">Recorded Grade Policy</label>
                    <select v-model="form.grading_policy" class="input-field">
                        <option value="first">1st Attempt</option>
                        <option value="second">2nd Attempt</option>
                        <option value="third">3rd Attempt</option>
                        <option value="highest">Highest Score</option>
                        <option value="latest">Latest Attempt</option>
                        <option value="manual">Manual Selection</option>
                    </select>
                </div>
                <div v-if="schoolYears?.length">
                    <label class="field-label">School year</label>
                    <select v-model="form.school_year_id" class="input-field" required>
                        <option :value="null">Select a school year</option>
                        <option v-for="year in schoolYears" :key="year.id" :value="year.id">
                            {{ year.name }}
                        </option>
                    </select>
                </div>
                <div v-if="quarters?.length">
                    <label class="field-label">Quarter</label>
                    <select v-model="form.quarter_id" class="input-field">
                        <option :value="null">All Quarters / Unassigned</option>
                        <option
                            v-for="qtr in quarters"
                            :key="qtr.id"
                            :value="qtr.id"
                        >
                            {{ qtr.code }} · {{ qtr.name }}
                        </option>
                    </select>
                </div>
                <div v-if="teachers?.length">
                    <label class="field-label">Assign to teacher</label>
                    <select v-model="form.teacher_id" class="input-field">
                        <option :value="null">Select a teacher</option>
                        <option
                            v-for="t in teachers"
                            :key="t.id"
                            :value="t.id"
                            :selected="t.id === seatwork.teacher_id"
                        >
                            {{ t.name }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <div
            v-for="(q, qi) in form.questions"
            :key="qi"
            class="rounded-2xl p-6"
            style="
                background: var(--gl-surface);
                border: 1px solid var(--gl-border);
            "
        >
            <div class="mb-4 flex items-center justify-between">
                <div
                    class="flex items-center gap-2 text-sm font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    <GripVertical
                        class="h-4 w-4"
                        :stroke-width="2"
                        style="color: var(--gl-text-muted)"
                    />
                    Question {{ qi + 1 }}
                </div>
                <button
                    v-if="form.questions.length > 1"
                    type="button"
                    class="rounded-md p-1.5"
                    style="color: var(--gl-danger)"
                    @click="removeQ(qi)"
                >
                    <Trash2 class="h-4 w-4" :stroke-width="2" />
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="field-label">Question text</label>
                    <textarea
                        v-model="q.question_text"
                        rows="2"
                        class="input-field"
                        required
                    ></textarea>
                </div>

                <div class="flex flex-wrap items-end gap-4">
                    <div>
                        <label class="field-label">Type</label>
                        <div
                            class="flex rounded-lg border p-1"
                            style="
                                border-color: var(--gl-border);
                                background: var(--gl-surface-2);
                            "
                        >
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-medium"
                                :class="
                                    q.type === 'identification'
                                        ? 'shadow-sm'
                                        : ''
                                "
                                :style="
                                    q.type === 'identification'
                                        ? {
                                              background: 'var(--gl-surface)',
                                              color: 'var(--gl-primary)',
                                          }
                                        : { color: 'var(--gl-text-secondary)' }
                                "
                                @click="setType(q, 'identification')"
                            >
                                ID
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-medium"
                                :class="
                                    q.type === 'enumeration' ? 'shadow-sm' : ''
                                "
                                :style="
                                    q.type === 'enumeration'
                                        ? {
                                              background: 'var(--gl-surface)',
                                              color: 'var(--gl-primary)',
                                          }
                                        : { color: 'var(--gl-text-secondary)' }
                                "
                                @click="setType(q, 'enumeration')"
                            >
                                Enum
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-medium"
                                :class="
                                    q.type === 'true_false' ? 'shadow-sm' : ''
                                "
                                :style="
                                    q.type === 'true_false'
                                        ? {
                                              background: 'var(--gl-surface)',
                                              color: 'var(--gl-primary)',
                                          }
                                        : { color: 'var(--gl-text-secondary)' }
                                "
                                @click="setType(q, 'true_false')"
                            >
                                T/F
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-medium"
                                :class="
                                    q.type === 'multiple_choice'
                                        ? 'shadow-sm'
                                        : ''
                                "
                                :style="
                                    q.type === 'multiple_choice'
                                        ? {
                                              background: 'var(--gl-surface)',
                                              color: 'var(--gl-primary)',
                                          }
                                        : { color: 'var(--gl-text-secondary)' }
                                "
                                @click="setType(q, 'multiple_choice')"
                            >
                                MC
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-medium"
                                :class="
                                    q.type === 'matching' ? 'shadow-sm' : ''
                                "
                                :style="
                                    q.type === 'matching'
                                        ? {
                                              background: 'var(--gl-surface)',
                                              color: 'var(--gl-primary)',
                                          }
                                        : { color: 'var(--gl-text-secondary)' }
                                "
                                @click="setType(q, 'matching')"
                            >
                                Match
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-medium"
                                :class="q.type === 'essay' ? 'shadow-sm' : ''"
                                :style="
                                    q.type === 'essay'
                                        ? {
                                              background: 'var(--gl-surface)',
                                              color: 'var(--gl-primary)',
                                          }
                                        : { color: 'var(--gl-text-secondary)' }
                                "
                                @click="setType(q, 'essay')"
                            >
                                Essay
                            </button>
                        </div>
                    </div>
                    <div class="w-28">
                        <label class="field-label">Points</label>
                        <input
                            v-model.number="q.points"
                            type="number"
                            min="1"
                            class="input-field"
                            required
                        />
                    </div>
                </div>

                <template v-if="q.type === 'identification'">
                    <div>
                        <label class="field-label">Correct answer</label>
                        <input
                            v-model="q.correct_answer"
                            type="text"
                            class="input-field"
                        />
                    </div>
                </template>

                <template v-if="q.type === 'enumeration'">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="field-label mb-0"
                                >Expected answers</label
                            >
                            <button
                                type="button"
                                class="text-xs font-medium hover:opacity-80"
                                style="color: var(--gl-primary)"
                                @click="q.enum_items.push('')"
                            >
                                + Add item
                            </button>
                        </div>
                        <div
                            v-for="(item, ei) in q.enum_items"
                            :key="ei"
                            class="flex items-center gap-2"
                        >
                            <span
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                                >{{ ei + 1 }}.</span
                            >
                            <input
                                v-model="q.enum_items[ei]"
                                type="text"
                                class="flex-1 rounded-lg border border-[var(--gl-border)] px-3 py-1.5 text-sm outline-none focus:border-[var(--gl-primary)]"
                                style="
                                    background: var(--gl-surface);
                                    color: var(--gl-text-primary);
                                "
                            />
                            <button
                                v-if="q.enum_items.length > 1"
                                type="button"
                                class="rounded-md p-1 hover:bg-[rgba(239,68,68,0.1)]"
                                style="color: var(--gl-danger)"
                                @click="q.enum_items.splice(ei, 1)"
                            >
                                <X class="h-3 w-3" :stroke-width="2" />
                            </button>
                        </div>
                    </div>
                </template>

                <template
                    v-if="
                        q.type === 'true_false' || q.type === 'multiple_choice'
                    "
                >
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label class="field-label mb-0">Options</label>
                            <button
                                v-if="q.type === 'multiple_choice'"
                                type="button"
                                class="text-xs font-medium hover:opacity-80"
                                style="color: var(--gl-primary)"
                                @click="
                                    q.options.push({
                                        option_text: '',
                                        is_correct: false,
                                    })
                                "
                            >
                                + Add option
                            </button>
                        </div>
                        <div
                            v-for="(opt, oi) in q.options"
                            :key="oi"
                            class="mb-2 flex items-center gap-2"
                        >
                            <input
                                type="radio"
                                :name="'opt_' + qi"
                                :checked="opt.is_correct"
                                class="h-4 w-4 shrink-0"
                                style="accent-color: var(--gl-primary)"
                                @change="setCorrect(q, oi)"
                            />
                            <input
                                v-model="opt.option_text"
                                type="text"
                                class="input-field"
                                :readonly="q.type === 'true_false'"
                                :placeholder="
                                    q.type === 'true_false'
                                        ? oi === 0
                                            ? 'True'
                                            : 'False'
                                        : 'Option ' + (oi + 1)
                                "
                                required
                            />
                            <button
                                v-if="
                                    q.type === 'multiple_choice' &&
                                    q.options.length > 2
                                "
                                type="button"
                                class="shrink-0 rounded-md p-2 hover:bg-[var(--gl-surface-2)]"
                                style="color: var(--gl-text-muted)"
                                @click="q.options.splice(oi, 1)"
                            >
                                <Trash2 class="h-4 w-4" :stroke-width="2" />
                            </button>
                        </div>
                    </div>
                </template>

                <template v-if="q.type === 'matching'">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="field-label mb-0"
                                >Matching pairs</label
                            >
                            <button
                                type="button"
                                class="text-xs font-medium hover:opacity-80"
                                style="color: var(--gl-primary)"
                                @click="
                                    q.matching_pairs.push({
                                        left_text: '',
                                        right_text: '',
                                    })
                                "
                            >
                                + Add pair
                            </button>
                        </div>
                        <div
                            v-for="(pair, pi) in q.matching_pairs"
                            :key="pi"
                            class="flex items-center gap-2"
                        >
                            <input
                                v-model="pair.left_text"
                                type="text"
                                class="flex-1 rounded-lg border border-[var(--gl-border)] px-3 py-1.5 text-sm outline-none focus:border-[var(--gl-primary)]"
                                style="
                                    background: var(--gl-surface);
                                    color: var(--gl-text-primary);
                                "
                                placeholder="Left"
                            />
                            <span
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                                >→</span
                            >
                            <input
                                v-model="pair.right_text"
                                type="text"
                                class="flex-1 rounded-lg border border-[var(--gl-border)] px-3 py-1.5 text-sm outline-none focus:border-[var(--gl-primary)]"
                                style="
                                    background: var(--gl-surface);
                                    color: var(--gl-text-primary);
                                "
                                placeholder="Right"
                            />
                            <button
                                v-if="q.matching_pairs.length > 2"
                                type="button"
                                class="rounded-md p-1 hover:bg-[rgba(239,68,68,0.1)]"
                                style="color: var(--gl-danger)"
                                @click="q.matching_pairs.splice(pi, 1)"
                            >
                                <X class="h-3 w-3" :stroke-width="2" />
                            </button>
                        </div>
                    </div>
                </template>

                <template v-if="q.type === 'essay'">
                    <div class="space-y-1.5">
                        <label
                            class="field-label mb-0 flex items-center justify-between"
                        >
                            <span>Grading Criteria (AI Graded)</span>
                            <span
                                class="text-xs font-normal"
                                style="color: var(--gl-text-muted)"
                                >Teacher rubric guidelines</span
                            >
                        </label>
                        <textarea
                            v-model="q.criteria"
                            rows="3"
                            class="input-field"
                            placeholder="e.g. 1. Accurate explanation of concepts (4 pts). 2. Supporting examples (3 pts). 3. Clarity and structure (3 pts)."
                            required
                        ></textarea>
                        <p class="text-xs" style="color: var(--gl-text-muted)">
                            AI evaluates student essay answers automatically
                            against your criteria.
                        </p>
                    </div>
                </template>
            </div>
        </div>

        <button
            type="button"
            class="btn-secondary w-full justify-center border-dashed"
            @click="addQ"
        >
            <Plus class="h-4 w-4" :stroke-width="2" />
            Add another question
        </button>

        <div
            class="flex items-center justify-end gap-3 border-t pt-6"
            style="border-color: var(--gl-border)"
        >
            <Link
                :href="`/teacher/seatworks/${seatwork.id}`"
                class="btn-secondary"
                >Cancel</Link
            >
            <button
                type="submit"
                class="btn-primary"
                :disabled="form.processing"
            >
                Update seatwork
            </button>
        </div>
    </form>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, Trash2, ArrowLeft, X, Check, GripVertical } from '@lucide/vue';
import { computed } from 'vue';

interface Opt {
    option_text: string;
    is_correct: boolean;
}
interface Q {
    question_text: string;
    type: string;
    points: number;
    options: Opt[];
    matching_pairs: { left_text: string; right_text: string }[];
    correct_answer: string;
    enum_items: string[];
}

const props = defineProps<{
    seatwork: any;
    teachers?: any[];
    gradeLevels: any[];
    quarters?: any[];
    schoolYears?: any[];
    defaultSchoolYearId?: number | null;
}>();

function mapSeatworkToForm(sw: any) {
    return {
        title: sw.title,
        instructions: sw.instructions ?? '',
        time_limit_minutes: sw.time_limit_minutes ?? '',
        max_attempts: sw.max_attempts ?? 1,
        grading_policy: sw.grading_policy ?? 'first',
        teacher_id: sw.teacher_id ?? null,
        school_year_id: sw.school_year_id ?? props.defaultSchoolYearId ?? null,
        quarter_id: sw.quarter_id ?? null,
        grade_level_ids: sw.grade_levels
            ? sw.grade_levels.map((g: any) => Number(g.id))
            : [],
        questions: sw.questions.map((q: any) => {
            const base: Q = {
                question_text: q.question_text,
                type: q.type,
                points: q.points,
                options:
                    q.options?.map((o: any) => ({
                        option_text: o.option_text,
                        is_correct: o.is_correct,
                    })) ?? [],
                matching_pairs:
                    q.matching_pairs?.map((p: any) => ({
                        left_text: p.left_text,
                        right_text: p.right_text,
                    })) ?? [],
                correct_answer: '',
                enum_items: [''],
                criteria: q.criteria ?? '',
            };

            if (q.type === 'identification') {
                base.correct_answer = q.options?.[0]?.option_text ?? '';
            }

            if (q.type === 'enumeration') {
                base.enum_items = q.options?.map((o: any) => o.option_text) ?? [
                    '',
                ];
            }

            return base;
        }),
    };
}

const form = useForm(mapSeatworkToForm(props.seatwork));

interface Q {
    question_text: string;
    type: string;
    points: number;
    options: Opt[];
    matching_pairs: { left_text: string; right_text: string }[];
    correct_answer: string;
    enum_items: string[];
    criteria?: string;
}

function blankQ(): Q {
    return {
        question_text: '',
        type: 'identification',
        points: 1,
        options: [],
        matching_pairs: [],
        correct_answer: '',
        enum_items: [''],
        criteria: '',
    };
}

function addQ() {
    form.questions.push(blankQ());
}
function removeQ(i: number) {
    form.questions.splice(i, 1);
}

function setType(q: Q, type: string) {
    q.type = type;

    if (type === 'true_false') {
        q.options = [
            { option_text: 'True', is_correct: true },
            { option_text: 'False', is_correct: false },
        ];
        q.matching_pairs = [];
        q.enum_items = [];
    } else if (type === 'multiple_choice') {
        q.options = [
            { option_text: '', is_correct: true },
            { option_text: '', is_correct: false },
            { option_text: '', is_correct: false },
            { option_text: '', is_correct: false },
        ];
        q.matching_pairs = [];
        q.enum_items = [];
    } else if (type === 'matching') {
        q.matching_pairs = [
            { left_text: '', right_text: '' },
            { left_text: '', right_text: '' },
            { left_text: '', right_text: '' },
        ];
        q.options = [];
        q.enum_items = [];
    } else if (type === 'enumeration') {
        q.enum_items = [''];
        q.options = [];
        q.matching_pairs = [];
    } else if (type === 'identification') {
        q.options = [];
        q.matching_pairs = [];
        q.enum_items = [];
    } else if (type === 'essay') {
        q.options = [];
        q.matching_pairs = [];
        q.enum_items = [];

        if (!q.criteria) {
q.criteria = '';
}
    }
}

function setCorrect(q: Q, oi: number) {
    q.options.forEach((o, i) => {
        o.is_correct = i === oi;
    });
}

const selectedGradeId = computed<number | null>({
    get: () => form.grade_level_ids[0] ?? null,
    set: (value) => {
        form.grade_level_ids = value ? [Number(value)] : [];

        if (value) {
form.clearErrors('grade_level_ids');
}
    },
});

function submit() {
    if (!form.grade_level_ids.length) {
        form.setError('grade_level_ids', 'Please select at least one grade level.');

        return;
    }

    form.questions.forEach((q) => {
        if (q.type === 'true_false') {
            q.options = [
                {
                    option_text: 'True',
                    is_correct: q.options[0]?.is_correct ?? true,
                },
                {
                    option_text: 'False',
                    is_correct: q.options[1]?.is_correct ?? false,
                },
            ];
        }
    });
    form.put(`/teacher/seatworks/${props.seatwork.id}`);
}
</script>
