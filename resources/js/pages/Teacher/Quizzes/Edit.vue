<template>
    <Head title="Edit Quiz" />

    <Link
        :href="`/teacher/quizzes/${quiz.id}`"
        class="mb-6 inline-flex items-center gap-1.5 text-sm font-medium hover:text-[var(--gl-text-primary)]"
        style="color: var(--gl-text-secondary)"
    >
        <ArrowLeft class="h-4 w-4" :stroke-width="2" />
        Back to quiz
    </Link>

    <form class="space-y-6" @submit.prevent="submit">
        <div class="card p-6">
            <h3
                class="mb-4 text-sm font-semibold"
                style="color: var(--gl-text-primary)"
            >
                Quiz details
            </h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="field-label">Title</label>
                    <input
                        v-model="form.title"
                        type="text"
                        class="input-field"
                        placeholder="e.g. Photosynthesis Chapter Review"
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
                    <label class="field-label">Description (optional)</label>
                    <textarea
                        v-model="form.description"
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
                <div v-if="form.grade_level_ids.length" class="sm:col-span-2">
                    <label class="field-label">Sections</label>
                    <div
                        class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4"
                    >
                        <button
                            v-for="sec in filteredSections"
                            :key="sec.id"
                            type="button"
                            class="flex items-center gap-2 rounded-lg border px-3.5 py-2.5 text-sm font-medium transition-colors"
                            :class="
                                form.section_ids.includes(sec.id)
                                    ? 'border-[var(--gl-primary)] bg-[var(--gl-primary-glow)] text-[var(--gl-primary)]'
                                    : 'border-[var(--gl-border)] text-[var(--gl-text-secondary)] hover:border-[var(--gl-text-muted)]'
                            "
                            @click="toggleSection(sec.id)"
                        >
                            <div
                                class="flex h-4.5 w-4.5 items-center justify-center rounded border"
                                :class="
                                    form.section_ids.includes(sec.id)
                                        ? 'border-[var(--gl-primary)] bg-[var(--gl-primary)]'
                                        : 'border-[var(--gl-border)]'
                                "
                            >
                                <Check
                                    v-if="form.section_ids.includes(sec.id)"
                                    class="h-3 w-3 text-white"
                                    :stroke-width="3"
                                />
                            </div>
                            {{ sec.name }}
                        </button>
                    </div>
                    <p
                        v-if="form.errors.section_ids"
                        class="mt-1 text-xs"
                        style="color: var(--gl-danger)"
                    >
                        {{ form.errors.section_ids }}
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
                            :selected="t.id === quiz.teacher_id"
                        >
                            {{ t.name }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <div
            v-for="(question, qIndex) in form.questions"
            :key="qIndex"
            class="card p-6"
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
                    Question {{ qIndex + 1 }}
                </div>
                <button
                    v-if="form.questions.length > 1"
                    type="button"
                    class="rounded-md p-1.5"
                    style="color: var(--gl-danger)"
                    @click="removeQuestion(qIndex)"
                >
                    <Trash2 class="h-4 w-4" :stroke-width="2" />
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="field-label">Question text</label>
                    <textarea
                        v-model="question.question_text"
                        rows="2"
                        class="input-field"
                        required
                    ></textarea>
                    <p
                        v-if="form.errors[`questions.${qIndex}.question_text`]"
                        class="mt-1 text-xs"
                        style="color: var(--gl-danger)"
                    >
                        {{ form.errors[`questions.${qIndex}.question_text`] }}
                    </p>
                </div>

                <div class="flex flex-wrap items-end gap-4">
                    <div>
                        <label class="field-label">Question type</label>
                        <div
                            class="flex rounded-lg border border-[var(--gl-border)] p-1"
                            style="background-color: var(--gl-surface-2)"
                        >
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-medium"
                                :class="
                                    question.type === 'multiple_choice'
                                        ? 'bg-surface text-[var(--gl-primary)] shadow-sm'
                                        : 'text-[var(--gl-text-secondary)]'
                                "
                                @click="setType(question, 'multiple_choice')"
                            >
                                Multiple choice
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-medium"
                                :class="
                                    question.type === 'true_false'
                                        ? 'bg-surface text-[var(--gl-primary)] shadow-sm'
                                        : 'text-[var(--gl-text-secondary)]'
                                "
                                @click="setType(question, 'true_false')"
                            >
                                True / False
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-medium"
                                :class="
                                    question.type === 'identification'
                                        ? 'bg-surface text-[var(--gl-primary)] shadow-sm'
                                        : 'text-[var(--gl-text-secondary)]'
                                "
                                @click="setType(question, 'identification')"
                            >
                                Identification
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-medium"
                                :class="
                                    question.type === 'enumeration'
                                        ? 'bg-surface text-[var(--gl-primary)] shadow-sm'
                                        : 'text-[var(--gl-text-secondary)]'
                                "
                                @click="setType(question, 'enumeration')"
                            >
                                Enumeration
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-medium"
                                :class="
                                    question.type === 'matching'
                                        ? 'bg-surface text-[var(--gl-primary)] shadow-sm'
                                        : 'text-[var(--gl-text-secondary)]'
                                "
                                @click="setType(question, 'matching')"
                            >
                                Matching
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-medium"
                                :class="
                                    question.type === 'essay'
                                        ? 'bg-surface text-[var(--gl-primary)] shadow-sm'
                                        : 'text-[var(--gl-text-secondary)]'
                                "
                                @click="setType(question, 'essay')"
                            >
                                Essay
                            </button>
                        </div>
                    </div>
                    <div class="w-28">
                        <label class="field-label">Points</label>
                        <input
                            v-model.number="question.points"
                            type="number"
                            min="1"
                            class="input-field"
                            required
                        />
                    </div>
                </div>

                <!-- Type: Identification -->
                <div v-if="question.type === 'identification'">
                    <label class="field-label flex items-center gap-1.5">
                        <ListChecks class="h-4 w-4" :stroke-width="2" />
                        Expected answer (near matches & conceptual idea accepted)
                    </label>
                    <input
                        v-if="question.options[0]"
                        v-model="question.options[0].option_text"
                        type="text"
                        class="input-field"
                        placeholder="e.g. Selection Tool"
                        required
                    />
                </div>

                <!-- Type: Enumeration -->
                <div v-else-if="question.type === 'enumeration'" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="field-label mb-0 flex items-center gap-1.5">
                            <ListChecks class="h-4 w-4" :stroke-width="2" />
                            Enumeration items (accepted correct answers)
                        </label>
                        <button
                            type="button"
                            class="text-xs font-medium hover:text-[var(--gl-text-primary)]"
                            style="color: var(--gl-primary)"
                            @click="question.enum_items.push('')"
                        >
                            + Add item
                        </button>
                    </div>
                    <div v-for="(item, ei) in question.enum_items" :key="ei" class="flex items-center gap-2">
                        <span class="text-xs font-bold" style="color: var(--gl-text-muted)">{{ ei + 1 }}.</span>
                        <input
                            v-model="question.enum_items[ei]"
                            type="text"
                            class="input-field"
                            :placeholder="`Accepted answer item ${ei + 1}`"
                            required
                        />
                        <button
                            v-if="question.enum_items.length > 1"
                            type="button"
                            class="shrink-0 rounded-md p-2 hover:bg-[var(--gl-border)]"
                            style="color: var(--gl-danger)"
                            @click="question.enum_items.splice(ei, 1)"
                        >
                            <Trash2 class="h-4 w-4" :stroke-width="2" />
                        </button>
                    </div>
                </div>

                <!-- Type: Matching Pairs -->
                <div v-else-if="question.type === 'matching'" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="field-label mb-0 flex items-center gap-1.5">
                            <ListChecks class="h-4 w-4" :stroke-width="2" />
                            Matching pairs (Left prompt and Right answer)
                        </label>
                        <button
                            type="button"
                            class="text-xs font-medium hover:text-[var(--gl-text-primary)]"
                            style="color: var(--gl-primary)"
                            @click="question.matching_pairs.push({ left_text: '', right_text: '' })"
                        >
                            + Add pair
                        </button>
                    </div>
                    <div v-for="(pair, pi) in question.matching_pairs" :key="pi" class="flex items-center gap-2">
                        <input
                            v-model="pair.left_text"
                            type="text"
                            class="input-field flex-1"
                            :placeholder="`Premise / Left ${pi + 1}`"
                            required
                        />
                        <span class="text-xs font-bold" style="color: var(--gl-text-muted)">→</span>
                        <input
                            v-model="pair.right_text"
                            type="text"
                            class="input-field flex-1"
                            :placeholder="`Match / Right ${pi + 1}`"
                            required
                        />
                        <button
                            v-if="question.matching_pairs.length > 1"
                            type="button"
                            class="shrink-0 rounded-md p-2 hover:bg-[var(--gl-border)]"
                            style="color: var(--gl-danger)"
                            @click="question.matching_pairs.splice(pi, 1)"
                        >
                            <Trash2 class="h-4 w-4" :stroke-width="2" />
                        </button>
                    </div>
                </div>

                <!-- Type: Essay -->
                <div v-else-if="question.type === 'essay'" class="space-y-2">
                    <label class="field-label flex items-center gap-1.5">
                        <ListChecks class="h-4 w-4" :stroke-width="2" />
                        Grading criteria / rubric (AI Graded)
                    </label>
                    <textarea
                        v-model="question.criteria"
                        rows="3"
                        class="input-field"
                        placeholder="Explain key concepts, terms, and quality factors AI should evaluate..."
                    ></textarea>
                    <p class="text-xs" style="color: var(--gl-text-muted)">
                        Student responses will be automatically evaluated against this criteria.
                    </p>
                </div>

                <!-- Type: Multiple Choice & True/False -->
                <div v-else>
                    <div class="mb-2 flex items-center justify-between">
                        <label
                            class="field-label mb-0 flex items-center gap-1.5"
                        >
                            <ListChecks class="h-4 w-4" :stroke-width="2" />
                            Answer options — select the correct one
                        </label>
                        <button
                            v-if="question.type === 'multiple_choice'"
                            type="button"
                            class="text-xs font-medium hover:text-[var(--gl-text-primary)]"
                            style="color: var(--gl-primary)"
                            @click="addOption(question)"
                        >
                            + Add option
                        </button>
                    </div>

                    <div class="space-y-2">
                        <div
                            v-for="(option, oIndex) in question.options"
                            :key="oIndex"
                            class="flex items-center gap-2"
                        >
                            <input
                                type="radio"
                                :name="`correct-${qIndex}`"
                                :checked="option.is_correct"
                                class="h-4 w-4 shrink-0"
                                style="color: var(--gl-primary)"
                                @change="markCorrect(question, oIndex)"
                            />
                            <input
                                v-model="option.option_text"
                                type="text"
                                class="input-field"
                                :disabled="question.type === 'true_false'"
                                :placeholder="`Option ${oIndex + 1}`"
                                required
                            />
                            <button
                                v-if="
                                    question.type === 'multiple_choice' &&
                                    question.options.length > 2
                                "
                                type="button"
                                class="shrink-0 rounded-md p-2 hover:bg-[var(--gl-border)]"
                                style="color: var(--gl-text-muted)"
                                @click="removeOption(question, oIndex)"
                            >
                                <Trash2 class="h-4 w-4" :stroke-width="2" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button
            type="button"
            class="btn-secondary w-full justify-center border-dashed"
            @click="addQuestion"
        >
            <Plus class="h-4 w-4" :stroke-width="2" />
            Add another question
        </button>

        <div
            class="flex items-center justify-end gap-3 border-t border-[var(--gl-border)] pt-6"
        >
            <Link :href="`/teacher/quizzes/${quiz.id}`" class="btn-secondary"
                >Cancel</Link
            >
            <button
                type="submit"
                class="btn-primary"
                :disabled="form.processing"
            >
                Update quiz
            </button>
        </div>
    </form>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Plus,
    Trash2,
    GripVertical,
    ArrowLeft,
    ListChecks,
    Check,
} from '@lucide/vue';
import { computed } from 'vue';

interface Option {
    id?: number;
    option_text: string;
    is_correct: boolean;
}

interface MatchingPair {
    id?: number;
    left_text: string;
    right_text: string;
}

interface Question {
    id?: number;
    question_text: string;
    type: string;
    points: number;
    options: Option[];
    matching_pairs: MatchingPair[];
    enum_items: string[];
    criteria: string;
}

const props = defineProps<{
    quiz: any;
    gradeLevels: any[];
    allSections: any[];
    quarters?: any[];
    schoolYears?: any[];
    defaultSchoolYearId?: number | null;
    teachers?: any[];
}>();

function mapQuizToForm(quiz: any) {
    return {
        title: quiz.title,
        description: quiz.description ?? '',
        grade_level_ids: quiz.grade_levels.map((g: any) => g.id),
        section_ids: quiz.sections.map((s: any) => s.id),
        time_limit_minutes: quiz.time_limit_minutes ?? '',
        school_year_id: quiz.school_year_id ?? props.defaultSchoolYearId ?? null,
        quarter_id: quiz.quarter_id ?? null,
        teacher_id: quiz.teacher_id ?? null,
        questions: quiz.questions.map((q: any) => ({
            id: q.id,
            question_text: q.question_text,
            type: q.type,
            points: q.points,
            criteria: q.criteria ?? '',
            options: (q.options || []).map((o: any) => ({
                id: o.id,
                option_text: o.option_text,
                is_correct: (bool) (o.is_correct ?? false),
            })),
            matching_pairs: (q.matching_pairs || []).map((mp: any) => ({
                id: mp.id,
                left_text: mp.left_text,
                right_text: mp.right_text,
            })),
            enum_items: q.type === 'enumeration'
                ? (q.options?.map((o: any) => o.option_text) || [''])
                : [''],
        })),
    };
}

const form = useForm(mapQuizToForm(props.quiz));

const filteredSections = computed(() => {
    return props.allSections.filter((s: any) =>
        form.grade_level_ids.includes(s.grade_level_id),
    );
});

const selectedGradeId = computed<number | null>({
    get: () => form.grade_level_ids[0] ?? null,
    set: (value) => {
        form.grade_level_ids = value ? [Number(value)] : [];
        form.section_ids = form.section_ids.filter((sid: number) =>
            filteredSections.value.some((section: any) => section.id === sid),
        );

        if (value) {
form.clearErrors('grade_level_ids');
}
    },
});

function toggleSection(id: number) {
    const idx = form.section_ids.indexOf(id);

    if (idx >= 0) {
        form.section_ids.splice(idx, 1);
    } else {
        form.section_ids.push(id);
    }
}

function addQuestion() {
    form.questions.push({
        question_text: '',
        type: 'multiple_choice',
        points: 1,
        options: [
            { option_text: '', is_correct: true },
            { option_text: '', is_correct: false },
            { option_text: '', is_correct: false },
            { option_text: '', is_correct: false },
        ],
        matching_pairs: [
            { left_text: '', right_text: '' },
            { left_text: '', right_text: '' },
        ],
        enum_items: [''],
        criteria: '',
    });
}

function removeQuestion(index: number) {
    if (form.questions.length > 1) {
        form.questions.splice(index, 1);
    }
}

function setType(question: Question, type: string) {
    question.type = type;

    if (type === 'true_false') {
        question.options = [
            { option_text: 'True', is_correct: true },
            { option_text: 'False', is_correct: false },
        ];
    } else if (type === 'identification') {
        const firstText = question.options[0]?.option_text || '';
        question.options = [{ option_text: firstText, is_correct: true }];
    } else if (type === 'enumeration') {
        if (!question.enum_items || !question.enum_items.length) {
            question.enum_items = [''];
        }
    } else if (type === 'matching') {
        if (!question.matching_pairs || !question.matching_pairs.length) {
            question.matching_pairs = [
                { left_text: '', right_text: '' },
                { left_text: '', right_text: '' },
            ];
        }
    } else if (type === 'multiple_choice') {
        if (question.options.length < 2) {
            question.options = [
                { option_text: '', is_correct: true },
                { option_text: '', is_correct: false },
                { option_text: '', is_correct: false },
                { option_text: '', is_correct: false },
            ];
        }
    }
}

function addOption(question: Question) {
    question.options.push({ option_text: '', is_correct: false });
}

function removeOption(question: Question, index: number) {
    if (question.options.length > 2) {
        question.options.splice(index, 1);
    }
}

function markCorrect(question: Question, index: number) {
    question.options.forEach((opt, i) => (opt.is_correct = i === index));
}

function submit() {
    form.questions.forEach((q) => {
        if (q.type === 'identification') {
            if (q.options[0]) {
                q.options[0].is_correct = true;
            }
        } else if (q.type === 'true_false') {
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
        } else if (q.type === 'enumeration') {
            q.options = (q.enum_items || [])
                .filter((item) => String(item ?? '').trim() !== '')
                .map((item) => ({ option_text: item, is_correct: true }));
        }
    });
    form.put(`/teacher/quizzes/${props.quiz.id}`);
}
</script>
