<template>
    <Head :title="'Edit ' + exam.title" />

    <Link
        :href="`/teacher/exams/${exam.id}`"
        class="mb-5 inline-flex items-center gap-1.5 text-xs font-semibold transition-colors hover:opacity-80"
        style="color: var(--gl-text-muted)"
    >
        <ArrowLeft class="h-3.5 w-3.5" :stroke-width="2.5" />
        Back to exam
    </Link>

    <div class="mb-5">
        <p class="text-xs font-semibold uppercase tracking-widest" style="color: var(--gl-text-muted)">Edit · Exam</p>
        <h1 class="mt-1 truncate text-2xl font-extrabold tracking-tight sm:text-3xl" style="color: var(--gl-text-primary)">{{ exam.title }}</h1>
        <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">Update exam details, sections, questions, and practical criteria.</p>
    </div>

    <form @submit.prevent="submit" class="space-y-5">
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(280px,0.8fr)_minmax(0,1.5fr)]">
            <section class="h-fit rounded-2xl p-5 lg:sticky lg:top-5" style="background: rgba(239,68,68,0.05); border: 1px solid rgba(239,68,68,0.16);">
                <div class="mb-4 flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl" style="background: rgba(239,68,68,0.14);">
                        <FileText class="h-4 w-4" style="color: #EF4444" :stroke-width="2" />
                    </div>
                    <div>
                        <h2 class="text-sm font-bold" style="color: var(--gl-text-primary)">Exam details</h2>
                        <p class="text-[11px]" style="color: var(--gl-text-muted)">Settings and assignment</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="field-label">Title</label>
                        <input v-model="form.title" type="text" class="input-field" required />
                        <p v-if="form.errors.title" class="mt-1 text-xs" style="color: var(--gl-danger)">{{ form.errors.title }}</p>
                    </div>
                    <div>
                        <label class="field-label">Grade</label>
                        <select v-model="selectedGradeId" class="input-field" required>
                            <option :value="null">Select a grade</option>
                            <option v-for="gl in gradeLevels" :key="gl.id" :value="gl.id">{{ gl.name }}</option>
                        </select>
                        <p v-if="form.errors.grade_level_ids" class="mt-1 text-xs" style="color: var(--gl-danger)">{{ form.errors.grade_level_ids }}</p>
                    </div>
                    <div>
                        <label class="field-label">Instructions (optional)</label>
                        <textarea v-model="form.instructions" rows="4" class="input-field" placeholder="General instructions for the exam"></textarea>
                    </div>
                    <div>
                        <label class="field-label">Time limit (minutes)</label>
                        <input v-model="form.time_limit_minutes" type="number" min="1" class="input-field" placeholder="Optional" />
                    </div>
                    <div v-if="schoolYears?.length">
                        <label class="field-label">School year</label>
                        <select v-model="form.school_year_id" class="input-field" required>
                            <option :value="null">Select a school year</option>
                            <option v-for="year in schoolYears" :key="year.id" :value="year.id">{{ year.name }}</option>
                        </select>
                    </div>
                    <div v-if="quarters?.length">
                        <label class="field-label">Quarter</label>
                        <select v-model="form.quarter_id" class="input-field">
                            <option :value="null">All Quarters / Unassigned</option>
                            <option v-for="qtr in quarters" :key="qtr.id" :value="qtr.id">{{ qtr.code }} · {{ qtr.name }}</option>
                        </select>
                    </div>
                    <div v-if="teachers?.length">
                        <label class="field-label">Assign to teacher</label>
                        <select v-model="form.teacher_id" class="input-field">
                            <option :value="null">Select a teacher</option>
                            <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl p-5" style="background: rgba(124,58,237,0.05); border: 1px solid rgba(124,58,237,0.16);">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl" style="background: rgba(124,58,237,0.14);">
                            <Layers class="h-4 w-4" style="color: var(--gl-secondary)" :stroke-width="2" />
                        </div>
                        <h2 class="text-sm font-bold" style="color: var(--gl-text-primary)">Exam sections</h2>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" style="background: var(--gl-surface-2); color: var(--gl-text-muted)">{{ form.sections.length }}</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" class="rounded-full px-3 py-1.5 text-[11px] font-bold" style="background: var(--gl-surface); border: 1px solid var(--gl-border); color: var(--gl-text-secondary)" @click="addSection('questions')">+ Questions</button>
                        <button type="button" class="rounded-full px-3 py-1.5 text-[11px] font-bold" style="background: var(--gl-surface); border: 1px solid var(--gl-border); color: var(--gl-text-secondary)" @click="addSection('practical')">+ Practical</button>
                    </div>
                </div>

                <div v-for="(section, si) in form.sections" :key="section.id || si" class="mb-4 rounded-2xl p-4" style="background: var(--gl-surface); border: 1px solid var(--gl-border);">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <div class="flex min-w-0 items-center gap-2">
                            <FileText v-if="section.section_type === 'questions'" class="h-4 w-4 shrink-0" style="color: var(--gl-secondary)" :stroke-width="2" />
                            <ClipboardCheck v-else class="h-4 w-4 shrink-0" style="color: var(--gl-accent)" :stroke-width="2" />
                            <span class="truncate text-xs font-extrabold uppercase tracking-wider" style="color: var(--gl-text-primary)">Section {{ si + 1 }} · {{ section.section_type === 'questions' ? 'Questions' : 'Practical' }}</span>
                        </div>
                        <button type="button" class="rounded-full p-1.5 hover:bg-[var(--gl-danger-bg)]" style="color: var(--gl-danger)" title="Remove section" @click="form.sections.splice(si, 1)"><X class="h-4 w-4" :stroke-width="2" /></button>
                    </div>
                    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="field-label">Section title</label>
                            <input v-model="section.title" type="text" class="input-field" required />
                        </div>
                        <div>
                            <label class="field-label">Instructions</label>
                            <input v-model="section.instructions" type="text" class="input-field" placeholder="Optional" />
                        </div>
                    </div>

                    <template v-if="section.section_type === 'questions'">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-xs font-bold" style="color: var(--gl-text-secondary)">Questions</span>
                            <button type="button" class="text-[11px] font-bold" style="color: var(--gl-primary)" @click="addQuestion(section)">+ Add question</button>
                        </div>
                        <div v-for="(question, qi) in section.questions" :key="question.id || qi" class="mb-3 rounded-xl p-3" style="background: var(--gl-surface-2);">
                            <div class="mb-2 flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <label class="field-label" style="color: var(--gl-primary)">Question {{ qi + 1 }}</label>
                                    <textarea v-model="question.question_text" rows="2" class="input-field" required></textarea>
                                </div>
                                <div class="flex shrink-0 items-center gap-1.5">
                                    <select v-model="question.type" class="rounded-xl border px-2 py-2 text-xs font-semibold outline-none" style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border)" @change="setType(question, question.type)">
                                        <option value="multiple_choice">Multiple Choice</option>
                                        <option value="true_false">True or False</option>
                                        <option value="identification">Identification</option>
                                        <option value="enumeration">Enumeration</option>
                                        <option value="matching">Matching</option>
                                        <option value="essay">Essay</option>
                                    </select>
                                    <input v-model.number="question.points" type="number" min="1" class="w-14 rounded-xl border px-2 py-2 text-center text-xs font-bold" style="background: rgba(251,191,36,0.08); color: var(--gl-accent); border-color: rgba(251,191,36,0.25)" title="Points" />
                                    <button type="button" class="rounded-md p-1 hover:bg-[var(--gl-danger-bg)]" style="color: var(--gl-danger)" title="Remove question" @click="section.questions.splice(qi, 1)"><X class="h-3.5 w-3.5" :stroke-width="2" /></button>
                                </div>
                            </div>

                            <template v-if="question.type === 'identification'">
                                <label class="field-label">Correct answer</label>
                                <input v-model="question.correct_answer" type="text" class="input-field" required />
                            </template>
                            <template v-else-if="question.type === 'enumeration'">
                                <div class="mb-1 flex items-center justify-between"><label class="field-label mb-0">Expected answers</label><button type="button" class="text-[11px] font-bold" style="color: var(--gl-primary)" @click="question.enum_items.push(''); question.option_ids.push(null)">+ Add item</button></div>
                                <div v-for="(item, ei) in question.enum_items" :key="ei" class="mb-1 flex items-center gap-2">
                                    <span class="text-[11px]" style="color: var(--gl-text-muted)">{{ ei + 1 }}.</span>
                                    <input v-model="question.enum_items[ei]" type="text" class="input-field" required />
                                    <button v-if="question.enum_items.length > 1" type="button" class="p-1" style="color: var(--gl-danger)" @click="question.enum_items.splice(ei, 1); question.option_ids.splice(ei, 1)"><X class="h-3 w-3" /></button>
                                </div>
                            </template>
                            <template v-else-if="question.type === 'true_false' || question.type === 'multiple_choice'">
                                <div class="mb-1 flex items-center justify-between"><label class="field-label mb-0">Answer options</label><button v-if="question.type === 'multiple_choice'" type="button" class="text-[11px] font-bold" style="color: var(--gl-primary)" @click="question.options.push({ id: null, option_text: '', is_correct: false })">+ Add option</button></div>
                                <div v-for="(option, oi) in question.options" :key="option.id || oi" class="mb-1 flex items-center gap-2">
                                    <input :checked="option.is_correct" type="radio" :name="`exam-${si}-${qi}`" class="h-3.5 w-3.5" style="accent-color: var(--gl-success)" @change="markCorrect(question, oi)" />
                                    <input v-model="option.option_text" type="text" class="input-field" :readonly="question.type === 'true_false'" :placeholder="question.type === 'true_false' ? (oi === 0 ? 'True' : 'False') : `Option ${oi + 1}`" required />
                                    <button v-if="question.type === 'multiple_choice' && question.options.length > 2" type="button" class="p-1" style="color: var(--gl-danger)" @click="question.options.splice(oi, 1)"><X class="h-3 w-3" /></button>
                                </div>
                            </template>
                            <template v-else-if="question.type === 'matching'">
                                <div class="mb-1 flex items-center justify-between"><label class="field-label mb-0">Matching pairs</label><button type="button" class="text-[11px] font-bold" style="color: var(--gl-primary)" @click="question.matching_pairs.push({ id: null, left_text: '', right_text: '' })">+ Add pair</button></div>
                                <div v-for="(pair, pi) in question.matching_pairs" :key="pair.id || pi" class="mb-1 flex items-center gap-2">
                                    <input v-model="pair.left_text" type="text" class="input-field" placeholder="Left item" required /><span class="text-xs font-bold" style="color: var(--gl-text-muted)">→</span><input v-model="pair.right_text" type="text" class="input-field" placeholder="Right item" required />
                                    <button v-if="question.matching_pairs.length > 1" type="button" class="p-1" style="color: var(--gl-danger)" @click="question.matching_pairs.splice(pi, 1)"><X class="h-3 w-3" /></button>
                                </div>
                            </template>
                            <template v-else>
                                <label class="field-label">Grading criteria (AI graded)</label>
                                <textarea v-model="question.criteria" rows="3" class="input-field" placeholder="Explain the scoring criteria" required></textarea>
                            </template>
                        </div>
                    </template>

                    <template v-else>
                        <div class="mb-2 flex items-center justify-between"><span class="text-xs font-bold" style="color: var(--gl-text-secondary)">Rubric criteria</span><button type="button" class="text-[11px] font-bold" style="color: var(--gl-primary)" @click="addCriterion(section)">+ Add criterion</button></div>
                        <div v-for="(criterion, ci) in section.criteria" :key="criterion.id || ci" class="mb-2 rounded-xl p-3" style="background: var(--gl-surface-2);">
                            <div class="grid grid-cols-[minmax(0,1fr)_80px_auto] items-end gap-2">
                                <div><label class="field-label" style="color: var(--gl-accent)">Criterion {{ ci + 1 }}</label><input v-model="criterion.criterion_name" type="text" class="input-field" required /></div>
                                <div><label class="field-label">Max pts</label><input v-model.number="criterion.max_points" type="number" min="1" class="input-field" required /></div>
                                <button v-if="section.criteria.length > 1" type="button" class="mb-1 rounded-md p-1" style="color: var(--gl-danger)" @click="section.criteria.splice(ci, 1)"><Trash2 class="h-4 w-4" /></button>
                            </div>
                            <input v-model="criterion.description" type="text" class="input-field mt-2" placeholder="Description (optional)" />
                        </div>
                    </template>
                </div>
                <p v-if="!form.sections.length" class="rounded-xl border border-dashed p-6 text-center text-xs" style="border-color: var(--gl-border); color: var(--gl-text-muted)">Add at least one exam section.</p>
            </section>
        </div>

        <div class="flex items-center justify-end gap-2.5 border-t pt-4" style="border-color: var(--gl-border)">
            <Link :href="`/teacher/exams/${exam.id}`" class="btn-secondary">Cancel</Link>
            <button type="submit" class="btn-primary" :disabled="form.processing">Update exam</button>
        </div>
    </form>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Check, ClipboardCheck, FileText, Layers, Trash2, X } from '@lucide/vue';
import { computed } from 'vue';

interface Option { id?: number | null; option_text: string; is_correct: boolean }
interface Pair { id?: number | null; left_text: string; right_text: string }
interface Question { id?: number; question_text: string; type: string; points: number; criteria: string; correct_answer: string; options: Option[]; option_ids: (number | null)[]; enum_items: string[]; matching_pairs: Pair[] }
interface Criterion { id?: number; criterion_name: string; description: string; max_points: number }
interface ExamSection { id?: number; title: string; section_type: 'questions' | 'practical'; instructions: string; questions: Question[]; criteria: Criterion[] }

const props = defineProps<{
    exam: any;
    gradeLevels: any[];
    quarters?: any[];
    schoolYears?: any[];
    defaultSchoolYearId?: number | null;
    teachers?: any[];
}>();

function mapQuestion(question: any): Question {
    const options = (question.options || []).map((option: any) => ({ id: option.id ?? null, option_text: option.option_text ?? '', is_correct: Boolean(option.is_correct) }));

    return {
        id: question.id,
        question_text: question.question_text ?? '',
        type: question.type,
        points: Number(question.points ?? 1),
        criteria: question.criteria ?? '',
        correct_answer: question.type === 'identification' ? (options[0]?.option_text ?? '') : '',
        options,
        option_ids: options.map((option) => option.id ?? null),
        enum_items: question.type === 'enumeration' ? options.map((option) => option.option_text) : [],
        matching_pairs: (question.matching_pairs || []).map((pair: any) => ({ id: pair.id ?? null, left_text: pair.left_text ?? '', right_text: pair.right_text ?? '' })),
    };
}

function mapSection(section: any): ExamSection {
    return {
        id: section.id,
        title: section.title ?? '',
        section_type: section.section_type,
        instructions: section.instructions ?? '',
        questions: (section.questions || []).map(mapQuestion),
        criteria: (section.criteria || []).map((criterion: any) => ({ id: criterion.id, criterion_name: criterion.criterion_name ?? '', description: criterion.description ?? '', max_points: Number(criterion.max_points ?? 1) })),
    };
}

const form = useForm({
    title: props.exam.title ?? '',
    instructions: props.exam.instructions ?? '',
    time_limit_minutes: props.exam.time_limit_minutes ?? '',
    school_year_id: props.exam.school_year_id ?? props.defaultSchoolYearId ?? null,
    quarter_id: props.exam.quarter_id ?? null,
    teacher_id: props.exam.teacher_id ?? null,
    grade_level_ids: (props.exam.grade_levels || []).map((grade: any) => Number(grade.id)),
    sections: (props.exam.sections || []).map(mapSection) as ExamSection[],
});

const selectedGradeId = computed<number | null>({
    get: () => form.grade_level_ids[0] ?? null,
    set: (value) => {
        form.grade_level_ids = value ? [Number(value)] : [];

        if (value) {
form.clearErrors('grade_level_ids');
}
    },
});
function blankQuestion(): Question {
    return { question_text: '', type: 'multiple_choice', points: 1, criteria: '', correct_answer: '', options: [{ id: null, option_text: '', is_correct: true }, { id: null, option_text: '', is_correct: false }], option_ids: [null, null], enum_items: [], matching_pairs: [] };
}
function addSection(type: 'questions' | 'practical') {
    form.sections.push(type === 'questions'
        ? { title: `Section ${form.sections.length + 1}`, section_type: type, instructions: '', questions: [blankQuestion()], criteria: [] }
        : { title: `Practical ${form.sections.length + 1}`, section_type: type, instructions: '', questions: [], criteria: [{ criterion_name: '', description: '', max_points: 10 }] });
}
function addQuestion(section: ExamSection) {
 section.questions.push(blankQuestion()); 
}
function addCriterion(section: ExamSection) {
 section.criteria.push({ criterion_name: '', description: '', max_points: 10 }); 
}
function markCorrect(question: Question, index: number) {
 question.options.forEach((option, optionIndex) => option.is_correct = optionIndex === index); 
}
function setType(question: Question, type: string) {
    question.type = type;

    if (type === 'true_false') {
        question.options = [{ id: question.options[0]?.id ?? null, option_text: 'True', is_correct: question.options[0]?.is_correct ?? true }, { id: question.options[1]?.id ?? null, option_text: 'False', is_correct: question.options[1]?.is_correct ?? false }];
        question.option_ids = question.options.map((option) => option.id ?? null);
        question.enum_items = [];
        question.matching_pairs = [];
    } else if (type === 'multiple_choice') {
        if (question.options.length < 2) {
question.options = [{ id: null, option_text: '', is_correct: true }, { id: null, option_text: '', is_correct: false }];
}

        question.option_ids = question.options.map((option) => option.id ?? null);
        question.enum_items = [];
        question.matching_pairs = [];
    } else if (type === 'identification') {
        question.correct_answer = question.options[0]?.option_text ?? question.correct_answer;
        question.options = [{ id: question.options[0]?.id ?? null, option_text: question.correct_answer, is_correct: true }];
        question.option_ids = [question.options[0].id ?? null];
        question.enum_items = [];
        question.matching_pairs = [];
    } else if (type === 'enumeration') {
        question.enum_items = question.options.length ? question.options.map((option) => option.option_text) : [''];
        question.option_ids = question.options.length ? question.options.map((option) => option.id ?? null) : [null];
        question.options = [];
        question.matching_pairs = [];
    } else if (type === 'matching') {
        question.matching_pairs = question.matching_pairs.length ? question.matching_pairs : [{ id: null, left_text: '', right_text: '' }, { id: null, left_text: '', right_text: '' }];
        question.options = [];
        question.option_ids = [];
        question.enum_items = [];
    } else {
        question.options = [];
        question.option_ids = [];
        question.enum_items = [];
        question.matching_pairs = [];
    }
}
function submit() {
    if (!form.grade_level_ids.length) {
        form.setError('grade_level_ids', 'Please select at least one grade level.');

        return;
    }

    form.sections.forEach((section) => section.questions.forEach((question) => {
        if (question.type === 'identification') {
            question.options = [{ id: question.options[0]?.id ?? null, option_text: question.correct_answer, is_correct: true }];
        } else if (question.type === 'enumeration') {
            question.option_ids = question.option_ids.slice(0, question.enum_items.length);
            question.options = [];
        } else if (question.type === 'true_false') {
            question.options = [{ id: question.options[0]?.id ?? null, option_text: 'True', is_correct: question.options[0]?.is_correct ?? true }, { id: question.options[1]?.id ?? null, option_text: 'False', is_correct: question.options[1]?.is_correct ?? false }];
        }
    }));
    form.put(`/teacher/exams/${props.exam.id}`);
}
</script>
