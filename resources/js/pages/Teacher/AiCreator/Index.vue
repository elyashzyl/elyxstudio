<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Sparkles,
    Upload,
    FileText,
    FileQuestion,
    ClipboardList,
    FlaskConical,
    Check,
    Plus,
    Trash2,
    ArrowLeft,
    Clock,
    AlertCircle,
    CheckCircle2,
    RotateCcw,
    Layers,
    FileUp,
    Send,
    Edit3,
    BookOpen,
    Info,
    HelpCircle,
    X,
    Gamepad2,
    GraduationCap,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    gradeLevels: any[];
    allSections: any[];
    teachers?: any[];
    subjects?: any[];
    isSuperadmin?: boolean;
    initialActivity?: string;
    initialMaterial?: {
        id: number;
        title: string;
        text: string;
    } | null;
}>();

// ---- State ----
const activeActivity = ref<'quiz' | 'seatwork' | 'practical' | 'exam' | 'game'>(
    (props.initialActivity as any) || 'quiz'
);

const selectedGameType = ref('fourpics');
const availableGameTypes = [
    { id: 'fourpics', label: '4 Pics 1 Word' },
    { id: 'crossword', label: 'Crossword' },
    { id: 'wordsearch', label: 'Word Search' },
    { id: 'codepuzzle', label: 'Code Puzzle' },
    { id: 'flashcard', label: 'Flashcards' },
    { id: 'quiz', label: 'Quiz Game' },
    { id: 'truefalse', label: 'True / False' },
    { id: 'wordjumble', label: 'Word Jumble' },
    { id: 'memorymatch', label: 'Memory Match' },
    { id: 'hangman', label: 'Hangman' },
    { id: 'speedquiz', label: 'Speed Quiz' },
    { id: 'dragdrop', label: 'Drag & Drop' },
    { id: 'ordering', label: 'Ordering' },
];

const formSubject = ref('General');
const formSubjectId = ref<number | null>(props.initialMaterial?.subject_id ?? null);
const formXpReward = ref(25);
const gameCards = ref<any[]>([]);

function addGameCard() {
    gameCards.value.push({
        question: '',
        answer: '',
        options: ['', '', '', ''],
    });
}

function removeGameCard(idx: number) {
    gameCards.value.splice(idx, 1);
}

const mode = ref<'auto' | 'extract' | 'generate'>('auto');
const requestedCount = ref(10);
const inputMethod = ref<'file' | 'text'>(
    props.initialMaterial?.text ? 'text' : 'file'
);

const selectedFile = ref<File | null>(null);
const fileDragOver = ref(false);
const rawText = ref(props.initialMaterial?.text || '');

if (props.initialMaterial?.subject) {
formSubject.value = props.initialMaterial.subject;
}

const seatworkTypes = ref<string[]>([
    'multiple_choice',
    'identification',
    'true_false',
    'enumeration',
]);

const availableSeatworkTypes = [
    { id: 'multiple_choice', label: 'Multiple Choice' },
    { id: 'identification', label: 'Identification' },
    { id: 'true_false', label: 'True / False' },
    { id: 'enumeration', label: 'Enumeration' },
    { id: 'matching', label: 'Matching Type' },
    { id: 'essay', label: 'Essay' },
];

function toggleSeatworkType(typeId: string) {
    if (seatworkTypes.value.includes(typeId)) {
        if (seatworkTypes.value.length > 1) {
            seatworkTypes.value = seatworkTypes.value.filter((t) => t !== typeId);
        }
    } else {
        seatworkTypes.value.push(typeId);
    }
}

// ---- File Handling ----
function onFileSelect(e: Event) {
    const target = e.target as HTMLInputElement;

    if (target.files && target.files.length > 0) {
        selectedFile.value = target.files[0];
    }
}

function onDrop(e: DragEvent) {
    fileDragOver.value = false;

    if (e.dataTransfer?.files && e.dataTransfer.files.length > 0) {
        selectedFile.value = e.dataTransfer.files[0];
    }
}

function clearFile() {
    selectedFile.value = null;
}

function formatBytes(bytes: number): string {
    if (bytes === 0) {
return '0 B';
}

    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

// ---- AI Parsing Step ----
const isParsing = ref(false);
const parseError = ref('');
const parsedResult = ref<any | null>(null);

async function runParse() {
    parseError.value = '';

    if (inputMethod.value === 'file' && !selectedFile.value) {
        parseError.value = 'Please select a file to upload.';

        return;
    }

    if (inputMethod.value === 'text' && !rawText.value.trim()) {
        parseError.value = 'Please enter or paste questions or reference text.';

        return;
    }

    isParsing.value = true;

    try {
        const formData = new FormData();
        formData.append('activity', activeActivity.value);
        formData.append('mode', mode.value);
        formData.append('count', String(requestedCount.value));

        if (activeActivity.value === 'seatwork' || activeActivity.value === 'quiz' || activeActivity.value === 'exam') {
            seatworkTypes.value.forEach((t) => formData.append('types[]', t));
        }

        if (activeActivity.value === 'game') {
            formData.append('game_type', selectedGameType.value);
        }

        if (inputMethod.value === 'file' && selectedFile.value) {
            formData.append('file', selectedFile.value);
        } else {
            formData.append('raw_text', rawText.value);
        }

        const token =
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

        const res = await fetch('/teacher/ai-creator/parse', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                Accept: 'application/json',
            },
            body: formData,
        });

        const data = await res.json();

        if (!res.ok || !data.ok) {
            parseError.value = data.message || 'Parsing failed. Please check the file and try again.';

            return;
        }

        parsedResult.value = data;

        // Initialize review form from parsed result
        formTitle.value = data.title || '';
        formInstructions.value = data.instructions || '';
        formTimeLimit.value = data.time_limit_minutes || '';
        formMaxScore.value = data.max_score || 100;

        if (activeActivity.value === 'practical') {
            practicalCriteria.value = (data.items || []).map((c: any) => ({
                criterion_name: c.criterion_name || '',
                description: c.description || '',
                max_points: Number(c.max_points) || 20,
            }));

            if (practicalCriteria.value.length === 0) {
                addPracticalCriterion();
            }
        } else if (activeActivity.value === 'game') {
            gameCards.value = (data.items || []).map((card: any) => ({
                question: card.question || '',
                answer: card.answer || '',
                options: Array.isArray(card.options) ? [...card.options] : [],
            }));

            if (gameCards.value.length === 0) {
                addGameCard();
            }
        } else if (activeActivity.value === 'exam' || activeActivity.value === 'seatwork' || activeActivity.value === 'quiz') {
            seatworkQuestions.value = (data.items || []).map((q: any) => ({
                question_text: q.question_text || '',
                type: q.type || 'identification',
                points: Number(q.points) || 1,
                correct_answer: q.correct_answer || (Array.isArray(q.options) && q.options.find((o: any) => o.is_correct)?.option_text) || '',
                criteria: q.criteria || '',
                enum_items: Array.isArray(q.enum_items) ? [...q.enum_items] : [''],
                matching_pairs: Array.isArray(q.matching_pairs)
                    ? q.matching_pairs.map((p: any) => ({
                          left_text: p.left_text || '',
                          right_text: p.right_text || '',
                      }))
                    : [{ left_text: '', right_text: '' }],
                options: Array.isArray(q.options)
                    ? q.options.map((o: any) => ({
                          option_text: o.option_text || '',
                          is_correct: !!o.is_correct,
                      }))
                    : [
                          { option_text: '', is_correct: true },
                          { option_text: '', is_correct: false },
                      ],
            }));

            if (seatworkQuestions.value.length === 0) {
                addSeatworkQuestion();
            }
        }
    } catch (e: any) {
        parseError.value = e?.message || 'Network error occurred. Please try again.';
    } finally {
        isParsing.value = false;
    }
}

function resetForm() {
    parsedResult.value = null;
    selectedFile.value = null;
    rawText.value = '';
    parseError.value = '';
}

// ---- Review Form State ----
const formTitle = ref('');
const formInstructions = ref('');
const formGradeLevelIds = ref<number[]>([]);
const formSectionIds = ref<number[]>([]);
const formTimeLimit = ref<number | string>('');
const formMaxScore = ref<number | string>(100);
const formPracticalXpReward = ref<number | string>(10);
const formTeacherId = ref<number | null>(null);

const isSaving = ref(false);
const saveError = ref('');

// Computed filtered sections based on selected grade levels
const filteredSections = computed(() => {
    if (formGradeLevelIds.value.length === 0) {
return [];
}

    return props.allSections.filter((s) =>
        formGradeLevelIds.value.includes(s.grade_level_id)
    );
});

function toggleGradeLevel(id: number) {
    if (formGradeLevelIds.value.includes(id)) {
        formGradeLevelIds.value = formGradeLevelIds.value.filter((g) => g !== id);
        // Filter out section ids that no longer match selected grades
        const validSectionIds = new Set(
            props.allSections
                .filter((s) => formGradeLevelIds.value.includes(s.grade_level_id))
                .map((s) => s.id)
        );
        formSectionIds.value = formSectionIds.value.filter((sid) =>
            validSectionIds.has(sid)
        );
    } else {
        formGradeLevelIds.value.push(id);
    }
}

function toggleSection(id: number) {
    if (formSectionIds.value.includes(id)) {
        formSectionIds.value = formSectionIds.value.filter((s) => s !== id);
    } else {
        formSectionIds.value.push(id);
    }
}

function selectAllSections() {
    formSectionIds.value = filteredSections.value.map((s) => s.id);
}

// Auto-select all sections when a single grade level is selected if sections empty
watch(formGradeLevelIds, () => {
    if (formGradeLevelIds.value.length === 1 && formSectionIds.value.length === 0) {
        selectAllSections();
    }
});

function onSeatworkTypeChange(q: any) {
    if (q.type === 'true_false') {
        q.options = [
            { option_text: 'True', is_correct: true },
            { option_text: 'False', is_correct: false },
        ];
        q.matching_pairs = [];
        q.enum_items = [];
    } else if (q.type === 'multiple_choice') {
        if (!q.options || q.options.length < 2) {
            q.options = [
                { option_text: '', is_correct: true },
                { option_text: '', is_correct: false },
                { option_text: '', is_correct: false },
                { option_text: '', is_correct: false },
            ];
        }

        q.matching_pairs = [];
        q.enum_items = [];
    } else if (q.type === 'matching') {
        if (!q.matching_pairs || q.matching_pairs.length === 0) {
            q.matching_pairs = [
                { left_text: '', right_text: '' },
                { left_text: '', right_text: '' },
            ];
        }

        q.options = [];
        q.enum_items = [];
    } else if (q.type === 'enumeration') {
        if (!q.enum_items || q.enum_items.length === 0) {
            q.enum_items = [''];
        }

        q.options = [];
        q.matching_pairs = [];
    } else if (q.type === 'identification') {
        q.options = [];
        q.matching_pairs = [];
        q.enum_items = [];

        if (q.correct_answer === undefined) {
q.correct_answer = '';
}
    } else if (q.type === 'essay') {
        q.options = [];
        q.matching_pairs = [];
        q.enum_items = [];

        if (!q.criteria) {
q.criteria = '';
}
    }
}

// ---- Seatwork / Quiz / Exam Item Management ----
const seatworkQuestions = ref<any[]>([]);

function addSeatworkQuestion() {
    seatworkQuestions.value.push({
        question_text: '',
        type: 'identification',
        points: 1,
        correct_answer: '',
        criteria: '',
        enum_items: [''],
        matching_pairs: [{ left_text: '', right_text: '' }],
        options: [
            { option_text: '', is_correct: true },
            { option_text: '', is_correct: false },
        ],
    });
}

function removeSeatworkQuestion(index: number) {
    seatworkQuestions.value.splice(index, 1);
}

function setSeatworkCorrectOption(q: any, optIndex: number) {
    q.options.forEach((opt: any, idx: number) => {
        opt.is_correct = idx === optIndex;
    });
}

function addSeatworkOption(q: any) {
    if (q.options.length < 6) {
        q.options.push({ option_text: '', is_correct: false });
    }
}

function removeSeatworkOption(q: any, optIndex: number) {
    if (q.options.length > 2) {
        const wasCorrect = q.options[optIndex].is_correct;
        q.options.splice(optIndex, 1);

        if (wasCorrect && q.options.length > 0) {
            q.options[0].is_correct = true;
        }
    }
}

function addEnumItem(q: any) {
    q.enum_items.push('');
}

function removeEnumItem(q: any, idx: number) {
    if (q.enum_items.length > 1) {
        q.enum_items.splice(idx, 1);
    }
}

function addMatchingPair(q: any) {
    q.matching_pairs.push({ left_text: '', right_text: '' });
}

function removeMatchingPair(q: any, idx: number) {
    if (q.matching_pairs.length > 1) {
        q.matching_pairs.splice(idx, 1);
    }
}

// ---- Practical Item Management ----
const practicalCriteria = ref<any[]>([]);

function addPracticalCriterion() {
    practicalCriteria.value.push({
        criterion_name: '',
        description: '',
        max_points: 20,
    });
}

function removePracticalCriterion(index: number) {
    practicalCriteria.value.splice(index, 1);
}

const totalPracticalPoints = computed(() => {
    return practicalCriteria.value.reduce(
        (sum, c) => sum + (Number(c.max_points) || 0),
        0
    );
});

// ---- Submission ----
function submitActivity(action: 'save' | 'publish' | 'save_and_edit') {
    saveError.value = '';

    if (!formTitle.value.trim()) {
        saveError.value = 'Please provide an activity title.';

        return;
    }

    if (formGradeLevelIds.value.length === 0) {
        saveError.value = 'Please select at least one grade level.';

        return;
    }

    if (activeActivity.value === 'quiz' && formSectionIds.value.length === 0) {
        saveError.value = 'Please select at least one section for the quiz.';

        return;
    }

    if ((activeActivity.value === 'quiz' || activeActivity.value === 'seatwork') && seatworkQuestions.value.length === 0) {
        saveError.value = `Please have at least one question in your ${activeActivity.value}.`;

        return;
    }

    if (activeActivity.value === 'practical' && practicalCriteria.value.length === 0) {
        saveError.value = 'Please have at least one rubric criterion.';

        return;
    }

    if (activeActivity.value === 'game' && gameCards.value.length === 0) {
        saveError.value = 'Please have at least one card in your game.';

        return;
    }

    if (activeActivity.value === 'exam' && seatworkQuestions.value.length === 0 && practicalCriteria.value.length === 0) {
        saveError.value = 'Please have at least one question or criterion in your exam.';

        return;
    }

    isSaving.value = true;

    const payload: any = {
        activity: activeActivity.value,
        action,
        title: formTitle.value.trim(),
        grade_level_ids: formGradeLevelIds.value,
        time_limit_minutes: formTimeLimit.value ? Number(formTimeLimit.value) : null,
        teacher_id: formTeacherId.value,
    };

    if (activeActivity.value === 'quiz') {
        payload.description = formInstructions.value.trim() || null;
        payload.section_ids = formSectionIds.value;
        payload.questions = seatworkQuestions.value.map((q) => {
            const item: any = {
                question_text: q.question_text.trim(),
                type: q.type,
                points: Number(q.points) || 1,
            };

            if (q.type === 'identification') {
                item.correct_answer = q.correct_answer?.trim() || '';
            } else if (q.type === 'enumeration') {
                item.enum_items = (q.enum_items || []).filter((e: string) => e.trim() !== '');
            } else if (q.type === 'essay') {
                item.criteria = q.criteria?.trim() || null;
            } else if (q.type === 'matching') {
                item.matching_pairs = (q.matching_pairs || []).filter(
                    (p: any) => p.left_text.trim() !== '' && p.right_text.trim() !== ''
                );
            } else {
                item.options = (q.options || []).map((o: any) => ({
                    option_text: o.option_text.trim(),
                    is_correct: !!o.is_correct,
                }));
            }

            return item;
        });
    } else if (activeActivity.value === 'seatwork') {
        payload.instructions = formInstructions.value.trim() || null;
        payload.questions = seatworkQuestions.value.map((q) => {
            const item: any = {
                question_text: q.question_text.trim(),
                type: q.type,
                points: Number(q.points) || 1,
            };

            if (q.type === 'identification') {
                item.correct_answer = q.correct_answer?.trim() || '';
            } else if (q.type === 'enumeration') {
                item.enum_items = (q.enum_items || []).filter((e: string) => e.trim() !== '');
            } else if (q.type === 'essay') {
                item.criteria = q.criteria?.trim() || null;
            } else if (q.type === 'matching') {
                item.matching_pairs = (q.matching_pairs || []).filter(
                    (p: any) => p.left_text.trim() !== '' && p.right_text.trim() !== ''
                );
            } else {
                item.options = q.options.map((o: any) => ({
                    option_text: o.option_text.trim(),
                    is_correct: !!o.is_correct,
                }));
            }

            return item;
        });
    } else if (activeActivity.value === 'practical') {
        payload.instructions = formInstructions.value.trim() || null;
        payload.max_score = formMaxScore.value ? Number(formMaxScore.value) : 100;
        payload.xp_reward = formPracticalXpReward.value ? Number(formPracticalXpReward.value) : 10;
        payload.criteria = practicalCriteria.value.map((c) => ({
            criterion_name: c.criterion_name.trim(),
            description: c.description.trim() || null,
            max_points: Number(c.max_points) || 10,
        }));
    } else if (activeActivity.value === 'exam') {
        payload.instructions = formInstructions.value.trim() || null;
        payload.questions = seatworkQuestions.value.map((q) => {
            const item: any = {
                question_text: q.question_text.trim(),
                type: q.type,
                points: Number(q.points) || 1,
            };

            if (q.type === 'identification') {
                item.correct_answer = q.correct_answer?.trim() || '';
            } else if (q.type === 'enumeration') {
                item.enum_items = (q.enum_items || []).filter((e: string) => e.trim() !== '');
            } else if (q.type === 'matching') {
                item.matching_pairs = (q.matching_pairs || []).filter(
                    (p: any) => p.left_text.trim() !== '' && p.right_text.trim() !== ''
                );
            } else {
                item.options = q.options.map((o: any) => ({
                    option_text: o.option_text.trim(),
                    is_correct: !!o.is_correct,
                }));
            }

            return item;
        });
        payload.criteria = practicalCriteria.value.map((c) => ({
            criterion_name: c.criterion_name.trim(),
            description: c.description.trim() || null,
            max_points: Number(c.max_points) || 20,
        }));
    } else if (activeActivity.value === 'game') {
        payload.game_type = selectedGameType.value;
        payload.subject = formSubject.value.trim() || 'General';
        payload.subject_id = formSubjectId.value;
        payload.xp_reward = Number(formXpReward.value) || 25;
        payload.description = formInstructions.value.trim() || null;
        payload.cards = gameCards.value.map((card) => ({
            question: card.question.trim(),
            answer: card.answer.trim(),
            options: (card.options || []).filter((opt: string) => opt.trim() !== ''),
        }));
    }

    router.post('/teacher/ai-creator', payload, {
        onError: (errors) => {
            isSaving.value = false;
            const firstError = Object.values(errors)[0];
            saveError.value = Array.isArray(firstError)
                ? String(firstError[0] || 'Please correct the highlighted fields.')
                : String(firstError || 'Unable to save this activity. Please try again.');
        },
        onFinish: () => {
            isSaving.value = false;
        },
    });
}
</script>

<template>
    <Head title="AI Activity Creator" />

    <div class="mx-auto max-w-5xl space-y-6 pb-12">
        <!-- Material Link Banner -->
        <div
            v-if="props.initialMaterial"
            class="flex items-center justify-between gap-3 rounded-2xl border border-[var(--gl-primary)] bg-[rgba(99,102,241,0.08)] p-4 shadow-sm"
        >
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[var(--gl-primary)] text-white">
                    <BookOpen class="h-5 w-5" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-[var(--gl-primary)]">
                            Source Material Loaded
                        </span>
                        <span class="rounded-md bg-[rgba(99,102,241,0.18)] px-2 py-0.5 text-[10px] font-bold text-[var(--gl-primary)]">
                            Ready to parse
                        </span>
                    </div>
                    <p class="text-sm font-semibold text-[var(--gl-text-primary)]">
                        {{ props.initialMaterial.title }}
                    </p>
                </div>
            </div>
            <Link
                href="/teacher/materials"
                class="rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface)] px-3 py-1.5 text-xs font-medium text-[var(--gl-text-muted)] hover:text-[var(--gl-text-primary)] transition-all"
            >
                Change Material
            </Link>
        </div>

        <!-- Breadcrumb & Header -->
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="mb-1 flex items-center gap-2">
                    <Link
                        href="/teacher/dashboard"
                        class="text-xs font-semibold text-[var(--gl-text-muted)] hover:text-[var(--gl-text-primary)]"
                    >
                        Teacher
                    </Link>
                    <span class="text-xs text-[var(--gl-text-muted)]">/</span>
                    <span class="text-xs font-semibold text-[var(--gl-primary)]">
                        AI Creator
                    </span>
                </div>
                <h1
                    class="flex items-center gap-2.5 text-2xl font-black tracking-tight"
                    style="color: var(--gl-text-primary)"
                >
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-[var(--gl-primary)] to-[var(--gl-secondary)] text-white shadow-md shadow-[var(--gl-primary-glow)]"
                    >
                        <Sparkles class="h-5 w-5" :stroke-width="2.2" />
                    </span>
                    AI Activity Creator
                </h1>
                <p class="mt-1 text-sm text-[var(--gl-text-secondary)]">
                    Upload test sheets, lesson files, or paste questions. AI parses and formats quizzes, seatworks, and practicals instantly.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <Link
                    href="/teacher/quizzes"
                    class="rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface)] px-3.5 py-2 text-xs font-semibold text-[var(--gl-text-secondary)] shadow-sm hover:border-[var(--gl-text-muted)] hover:text-[var(--gl-text-primary)]"
                >
                    Quizzes
                </Link>
                <Link
                    href="/teacher/seatworks"
                    class="rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface)] px-3.5 py-2 text-xs font-semibold text-[var(--gl-text-secondary)] shadow-sm hover:border-[var(--gl-text-muted)] hover:text-[var(--gl-text-primary)]"
                >
                    Seatworks
                </Link>
                <Link
                    href="/teacher/practicals"
                    class="rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface)] px-3.5 py-2 text-xs font-semibold text-[var(--gl-text-secondary)] shadow-sm hover:border-[var(--gl-text-muted)] hover:text-[var(--gl-text-primary)]"
                >
                    Practicals
                </Link>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-[var(--gl-border)] bg-[var(--gl-surface)] px-4 py-3 text-xs">
            <span class="font-semibold text-[var(--gl-text-secondary)]">Create with AI:</span>
            <Link href="/teacher/announcements?create=announcement" class="font-bold text-[var(--gl-primary)] hover:underline">
                Draft an announcement
            </Link>
            <Link href="/teacher/todos?create=todo" class="font-bold text-[var(--gl-primary)] hover:underline">
                Draft a class task
            </Link>
            <span class="text-[var(--gl-text-muted)]">Review before publishing.</span>
        </div>

        <!-- Step 1: Upload & Generation Configuration (Shown before parse) -->
        <div v-if="!parsedResult" class="space-y-6">
            <!-- 1. Choose Activity Type -->
            <div class="card p-6">
                <label class="field-label mb-3 text-xs font-bold uppercase tracking-wider text-[var(--gl-text-muted)]">
                    1. Select Target Activity
                </label>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <button
                        type="button"
                        class="group relative flex flex-col items-start gap-2 rounded-2xl border p-4 text-left transition-all"
                        :class="
                            activeActivity === 'quiz'
                                ? 'border-[var(--gl-primary)] bg-[var(--gl-primary-glow)] ring-2 ring-[var(--gl-primary)]/20'
                                : 'border-[var(--gl-border)] bg-[var(--gl-surface-2)] hover:border-[var(--gl-text-muted)]'
                        "
                        @click="activeActivity = 'quiz'"
                    >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl"
                            :class="
                                activeActivity === 'quiz'
                                    ? 'bg-[var(--gl-primary)] text-white shadow-sm'
                                    : 'bg-[var(--gl-surface)] text-[var(--gl-text-secondary)]'
                            "
                        >
                            <FileQuestion class="h-5 w-5" :stroke-width="2.2" />
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 font-bold text-sm" style="color: var(--gl-text-primary)">
                                Quiz
                                <Check v-if="activeActivity === 'quiz'" class="h-4 w-4 text-[var(--gl-primary)]" :stroke-width="2.5" />
                            </div>
                            <p class="mt-0.5 text-xs text-[var(--gl-text-muted)] leading-relaxed">
                                Multiple Choice & True/False with automatic scoring.
                            </p>
                        </div>
                    </button>

                    <button
                        type="button"
                        class="group relative flex flex-col items-start gap-2 rounded-2xl border p-4 text-left transition-all"
                        :class="
                            activeActivity === 'seatwork'
                                ? 'border-[var(--gl-primary)] bg-[var(--gl-primary-glow)] ring-2 ring-[var(--gl-primary)]/20'
                                : 'border-[var(--gl-border)] bg-[var(--gl-surface-2)] hover:border-[var(--gl-text-muted)]'
                        "
                        @click="activeActivity = 'seatwork'"
                    >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl"
                            :class="
                                activeActivity === 'seatwork'
                                    ? 'bg-[var(--gl-primary)] text-white shadow-sm'
                                    : 'bg-[var(--gl-surface)] text-[var(--gl-text-secondary)]'
                            "
                        >
                            <ClipboardList class="h-5 w-5" :stroke-width="2.2" />
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 font-bold text-sm" style="color: var(--gl-text-primary)">
                                Seatwork
                                <Check v-if="activeActivity === 'seatwork'" class="h-4 w-4 text-[var(--gl-primary)]" :stroke-width="2.5" />
                            </div>
                            <p class="mt-0.5 text-xs text-[var(--gl-text-muted)] leading-relaxed">
                                Identification, Enumeration, True/False, Matching & Essays.
                            </p>
                        </div>
                    </button>

                    <button
                        type="button"
                        class="group relative flex flex-col items-start gap-2 rounded-2xl border p-4 text-left transition-all"
                        :class="
                            activeActivity === 'exam'
                                ? 'border-[var(--gl-primary)] bg-[var(--gl-primary-glow)] ring-2 ring-[var(--gl-primary)]/20'
                                : 'border-[var(--gl-border)] bg-[var(--gl-surface-2)] hover:border-[var(--gl-text-muted)]'
                        "
                        @click="activeActivity = 'exam'"
                    >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl"
                            :class="
                                activeActivity === 'exam'
                                    ? 'bg-[var(--gl-primary)] text-white shadow-sm'
                                    : 'bg-[var(--gl-surface)] text-[var(--gl-text-secondary)]'
                            "
                        >
                            <GraduationCap class="h-5 w-5" :stroke-width="2.2" />
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 font-bold text-sm" style="color: var(--gl-text-primary)">
                                Exam
                                <Check v-if="activeActivity === 'exam'" class="h-4 w-4 text-[var(--gl-primary)]" :stroke-width="2.5" />
                            </div>
                            <p class="mt-0.5 text-xs text-[var(--gl-text-muted)] leading-relaxed">
                                Major periodic exam with question sections & practical rubric.
                            </p>
                        </div>
                    </button>

                    <button
                        type="button"
                        class="group relative flex flex-col items-start gap-2 rounded-2xl border p-4 text-left transition-all"
                        :class="
                            activeActivity === 'practical'
                                ? 'border-[var(--gl-primary)] bg-[var(--gl-primary-glow)] ring-2 ring-[var(--gl-primary)]/20'
                                : 'border-[var(--gl-border)] bg-[var(--gl-surface-2)] hover:border-[var(--gl-text-muted)]'
                        "
                        @click="activeActivity = 'practical'"
                    >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl"
                            :class="
                                activeActivity === 'practical'
                                    ? 'bg-[var(--gl-primary)] text-white shadow-sm'
                                    : 'bg-[var(--gl-surface)] text-[var(--gl-text-secondary)]'
                            "
                        >
                            <FlaskConical class="h-5 w-5" :stroke-width="2.2" />
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 font-bold text-sm" style="color: var(--gl-text-primary)">
                                Practical
                                <Check v-if="activeActivity === 'practical'" class="h-4 w-4 text-[var(--gl-primary)]" :stroke-width="2.5" />
                            </div>
                            <p class="mt-0.5 text-xs text-[var(--gl-text-muted)] leading-relaxed">
                                Hands-on task with file uploads & rubric criteria.
                            </p>
                        </div>
                    </button>

                    <button
                        type="button"
                        class="group relative flex flex-col items-start gap-2 rounded-2xl border p-4 text-left transition-all"
                        :class="
                            activeActivity === 'game'
                                ? 'border-[var(--gl-primary)] bg-[var(--gl-primary-glow)] ring-2 ring-[var(--gl-primary)]/20'
                                : 'border-[var(--gl-border)] bg-[var(--gl-surface-2)] hover:border-[var(--gl-text-muted)]'
                        "
                        @click="activeActivity = 'game'"
                    >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl"
                            :class="
                                activeActivity === 'game'
                                    ? 'bg-[var(--gl-primary)] text-white shadow-sm'
                                    : 'bg-[var(--gl-surface)] text-[var(--gl-text-secondary)]'
                            "
                        >
                            <Gamepad2 class="h-5 w-5" :stroke-width="2.2" />
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 font-bold text-sm" style="color: var(--gl-text-primary)">
                                Game
                                <Check v-if="activeActivity === 'game'" class="h-4 w-4 text-[var(--gl-primary)]" :stroke-width="2.5" />
                            </div>
                            <p class="mt-0.5 text-xs text-[var(--gl-text-muted)] leading-relaxed">
                                4 Pics 1 Word, Crossword, Word Search, Code Puzzle, etc.
                            </p>
                        </div>
                    </button>
                </div>

                <!-- Sub-selector for Game Type -->
                <div v-if="activeActivity === 'game'" class="mt-4 pt-4 border-t border-[var(--gl-border)] space-y-2">
                    <label class="field-label text-xs font-bold text-[var(--gl-text-secondary)]">Select Game Format</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="g in availableGameTypes"
                            :key="g.id"
                            type="button"
                            class="rounded-xl border px-3 py-1.5 text-xs font-semibold transition-all"
                            :class="
                                selectedGameType === g.id
                                    ? 'border-[var(--gl-primary)] bg-[var(--gl-primary-glow)] text-[var(--gl-primary)] ring-1 ring-[var(--gl-primary)]'
                                    : 'border-[var(--gl-border)] text-[var(--gl-text-muted)] hover:border-[var(--gl-text-secondary)]'
                            "
                            @click="selectedGameType = g.id"
                        >
                            {{ g.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Source Input (Upload or Paste) -->
            <div class="card p-6">
                <div class="mb-3 flex items-center justify-between">
                    <label class="field-label mb-0 text-xs font-bold uppercase tracking-wider text-[var(--gl-text-muted)]">
                        2. Provide File or Text
                    </label>

                    <div class="flex rounded-lg border border-[var(--gl-border)] bg-[var(--gl-surface-2)] p-0.5 text-xs">
                        <button
                            type="button"
                            class="rounded-md px-2.5 py-1 font-medium transition-all"
                            :class="
                                inputMethod === 'file'
                                    ? 'bg-[var(--gl-surface)] text-[var(--gl-primary)] shadow-xs'
                                    : 'text-[var(--gl-text-muted)]'
                            "
                            @click="inputMethod = 'file'"
                        >
                            File Upload
                        </button>
                        <button
                            type="button"
                            class="rounded-md px-2.5 py-1 font-medium transition-all"
                            :class="
                                inputMethod === 'text'
                                    ? 'bg-[var(--gl-surface)] text-[var(--gl-primary)] shadow-xs'
                                    : 'text-[var(--gl-text-muted)]'
                            "
                            @click="inputMethod = 'text'"
                        >
                            Paste Text
                        </button>
                    </div>
                </div>

                <!-- File Drag and Drop Zone -->
                <div v-if="inputMethod === 'file'" class="space-y-3">
                    <div
                        v-if="!selectedFile"
                        class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed p-8 text-center transition-all"
                        :class="
                            fileDragOver
                                ? 'border-[var(--gl-primary)] bg-[var(--gl-primary-glow)]'
                                : 'border-[var(--gl-border)] bg-[var(--gl-surface-2)]/50 hover:border-[var(--gl-text-muted)]'
                        "
                        @dragover.prevent="fileDragOver = true"
                        @dragleave.prevent="fileDragOver = false"
                        @drop.prevent="onDrop"
                    >
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[var(--gl-surface)] text-[var(--gl-primary)] shadow-xs">
                            <Upload class="h-6 w-6" :stroke-width="2" />
                        </div>
                        <p class="mt-3 text-sm font-bold" style="color: var(--gl-text-primary)">
                            Drag and drop your document here
                        </p>
                        <p class="mt-1 text-xs text-[var(--gl-text-muted)]">
                            Supports PDF, Word (.docx, .doc), PowerPoint (.pptx, .ppt), TXT, Markdown, CSV (up to 25 MB)
                        </p>

                        <label
                            class="mt-4 inline-flex cursor-pointer items-center gap-2 rounded-xl bg-[var(--gl-surface)] border border-[var(--gl-border)] px-4 py-2 text-xs font-semibold text-[var(--gl-text-primary)] shadow-xs hover:border-[var(--gl-primary)] hover:text-[var(--gl-primary)] transition-all"
                        >
                            <FileUp class="h-4 w-4" />
                            Browse files
                            <input
                                type="file"
                                class="hidden"
                                accept=".pdf,.docx,.doc,.pptx,.ppt,.txt,.md,.csv,.json"
                                @change="onFileSelect"
                            />
                        </label>
                    </div>

                    <!-- Selected File Preview -->
                    <div
                        v-else
                        class="flex items-center justify-between rounded-2xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] p-4"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[var(--gl-primary-glow)] text-[var(--gl-primary)]">
                                <FileText class="h-5 w-5" :stroke-width="2.2" />
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold" style="color: var(--gl-text-primary)">
                                    {{ selectedFile.name }}
                                </p>
                                <p class="text-xs text-[var(--gl-text-muted)]">
                                    {{ formatBytes(selectedFile.size) }}
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="rounded-xl p-2 text-[var(--gl-text-muted)] hover:bg-[rgba(239,68,68,0.12)] hover:text-[var(--gl-danger)] transition-all"
                            title="Remove file"
                            @click="clearFile"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- Raw Text Area -->
                <div v-else class="space-y-2">
                    <textarea
                        v-model="rawText"
                        rows="8"
                        class="input-field font-mono text-xs leading-relaxed"
                        placeholder="Paste questions with answer key, assessment syllabus, lesson notes, or performance criteria here...&#10;&#10;Example:&#10;Shapes and Graphic Elements in InDesign&#10;1. Which tool moves a rectangle?&#10;A. Rectangle Tool  B. Selection Tool&#10;Answer Key: 1. B"
                    ></textarea>
                    <p class="text-[11px] text-[var(--gl-text-muted)]">
                        Tip: You can paste complete test sheets with choices and answer keys; AI will extract questions, options, and match the correct answers directly.
                    </p>
                </div>
            </div>

            <!-- 3. AI Parsing Configuration -->
            <div class="card p-6">
                <label class="field-label mb-3 text-xs font-bold uppercase tracking-wider text-[var(--gl-text-muted)]">
                    3. Parsing & Generation Options
                </label>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="field-label">AI Processing Mode</label>
                        <select v-model="mode" class="input-field">
                            <option value="auto">
                                Smart Auto-Detect (Extract if test sheet, generate if notes)
                            </option>
                            <option value="extract">
                                Extract Existing Assessment (Preserve questions & answer keys)
                            </option>
                            <option value="generate">
                                Generate New Assessment from Material Content
                            </option>
                        </select>
                        <p class="mt-1 text-[11px] text-[var(--gl-text-muted)]">
                            Auto-Detect inspects the file for numbered questions and answer keys.
                        </p>
                    </div>

                    <div>
                        <label class="field-label">
                            Target Question Count (for generation)
                        </label>
                        <input
                            v-model.number="requestedCount"
                            type="number"
                            min="1"
                            max="50"
                            class="input-field"
                            placeholder="e.g. 10 or 15"
                        />
                        <p class="mt-1 text-[11px] text-[var(--gl-text-muted)]">
                            If extracting an existing test paper, all detected questions are preserved.
                        </p>
                    </div>

                    <!-- Question Types Filter -->
                    <div v-if="activeActivity === 'seatwork' || activeActivity === 'quiz' || activeActivity === 'exam'" class="sm:col-span-2">
                        <label class="field-label">Included Question Types</label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="t in availableSeatworkTypes"
                                :key="t.id"
                                type="button"
                                class="rounded-xl border px-3 py-1.5 text-xs font-semibold transition-all"
                                :class="
                                    seatworkTypes.includes(t.id)
                                        ? 'border-[var(--gl-primary)] bg-[var(--gl-primary-glow)] text-[var(--gl-primary)]'
                                        : 'border-[var(--gl-border)] text-[var(--gl-text-muted)] hover:border-[var(--gl-text-secondary)]'
                                "
                                @click="toggleSeatworkType(t.id)"
                            >
                                <Check v-if="seatworkTypes.includes(t.id)" class="mr-1 inline h-3.5 w-3.5" :stroke-width="2.5" />
                                {{ t.label }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Error Banner -->
                <div
                    v-if="parseError"
                    class="mt-4 flex items-center gap-2.5 rounded-xl border border-[var(--gl-danger)]/30 bg-[rgba(239,68,68,0.08)] p-3.5 text-xs text-[var(--gl-danger)] font-medium"
                >
                    <AlertCircle class="h-4 w-4 shrink-0" />
                    <span>{{ parseError }}</span>
                </div>

                <!-- Parse Action Button -->
                <div class="mt-6 flex items-center justify-end">
                    <button
                        type="button"
                        class="btn-primary flex items-center gap-2 px-6 py-2.5 text-sm font-bold shadow-md shadow-[var(--gl-primary-glow)] disabled:opacity-50"
                        :disabled="isParsing"
                        @click="runParse"
                    >
                        <RotateCcw v-if="isParsing" class="h-4 w-4 animate-spin" />
                        <Sparkles v-else class="h-4 w-4" />
                        <span>{{ isParsing ? 'Analyzing & Populating with AI...' : 'Parse & Format with AI' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 2: Interactive Review & Activity Details (Shown after parsing) -->
        <div v-else class="space-y-6">
            <!-- Summary Banner -->
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[var(--gl-primary)]/30 bg-[var(--gl-primary-glow)] p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[var(--gl-primary)] text-white shadow-xs">
                        <CheckCircle2 class="h-5 w-5" :stroke-width="2.2" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-bold" style="color: var(--gl-text-primary)">
                                AI successfully populated {{ activeActivity }} format
                            </span>
                            <span class="rounded-full bg-[var(--gl-surface)] px-2.5 py-0.5 text-[10px] font-bold text-[var(--gl-primary)] border border-[var(--gl-border)]">
                                {{ parsedResult.detected_mode === 'extracted_test' ? '📄 Extracted Test Paper' : '🧠 Generated from Content' }}
                            </span>
                            <span class="rounded-full bg-[var(--gl-surface)] px-2.5 py-0.5 text-[10px] font-bold text-[var(--gl-text-secondary)] border border-[var(--gl-border)]">
                                Provider: {{ parsedResult.provider }}
                            </span>
                        </div>
                        <p class="text-xs text-[var(--gl-text-secondary)] mt-0.5">
                            Review and adjust questions or options below. Click "Save & Create" when ready.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    class="rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface)] px-3 py-1.5 text-xs font-semibold text-[var(--gl-text-secondary)] hover:text-[var(--gl-text-primary)] transition-all flex items-center gap-1.5"
                    @click="resetForm"
                >
                    <RotateCcw class="h-3.5 w-3.5" />
                    Upload another file
                </button>
            </div>

            <!-- Activity Details Form Card -->
            <div class="card p-6">
                <h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-[var(--gl-text-muted)]">
                    Activity Details & Assignments
                </h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="field-label">Title</label>
                        <input
                            v-model="formTitle"
                            type="text"
                            class="input-field"
                            placeholder="e.g. Graphic Elements in InDesign"
                            required
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="field-label">Instructions / Description (optional)</label>
                        <textarea
                            v-model="formInstructions"
                            rows="2"
                            class="input-field"
                            placeholder="Instructions for students"
                        ></textarea>
                    </div>

                    <!-- Grade Levels -->
                    <div class="sm:col-span-2">
                        <label class="field-label">
                            Grade Levels <span class="text-[var(--gl-danger)]">*</span>
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="gl in gradeLevels"
                                :key="gl.id"
                                type="button"
                                class="rounded-xl border px-3.5 py-2 text-xs font-semibold transition-all"
                                :class="
                                    formGradeLevelIds.includes(gl.id)
                                        ? 'border-[var(--gl-primary)] bg-[var(--gl-primary-glow)] text-[var(--gl-primary)]'
                                        : 'border-[var(--gl-border)] text-[var(--gl-text-secondary)] hover:border-[var(--gl-text-muted)]'
                                "
                                @click="toggleGradeLevel(gl.id)"
                            >
                                <Check v-if="formGradeLevelIds.includes(gl.id)" class="mr-1 inline h-3.5 w-3.5" :stroke-width="2.5" />
                                {{ gl.name }}
                            </button>
                        </div>
                    </div>

                    <!-- Sections (Required for Quiz) -->
                    <div v-if="activeActivity === 'quiz' && formGradeLevelIds.length > 0" class="sm:col-span-2">
                        <div class="mb-2 flex items-center justify-between">
                            <label class="field-label mb-0">
                                Assigned Sections <span class="text-[var(--gl-danger)]">*</span>
                            </label>
                            <button
                                type="button"
                                class="text-xs font-semibold text-[var(--gl-primary)] hover:underline"
                                @click="selectAllSections"
                            >
                                Select all sections
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                            <button
                                v-for="sec in filteredSections"
                                :key="sec.id"
                                type="button"
                                class="flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-medium transition-all"
                                :class="
                                    formSectionIds.includes(sec.id)
                                        ? 'border-[var(--gl-primary)] bg-[var(--gl-primary-glow)] text-[var(--gl-primary)]'
                                        : 'border-[var(--gl-border)] text-[var(--gl-text-secondary)] hover:border-[var(--gl-text-muted)]'
                                "
                                @click="toggleSection(sec.id)"
                            >
                                <div
                                    class="flex h-4 w-4 items-center justify-center rounded border"
                                    :class="
                                        formSectionIds.includes(sec.id)
                                            ? 'border-[var(--gl-primary)] bg-[var(--gl-primary)] text-white'
                                            : 'border-[var(--gl-border)]'
                                    "
                                >
                                    <Check v-if="formSectionIds.includes(sec.id)" class="h-3 w-3" :stroke-width="3" />
                                </div>
                                <span class="truncate">{{ sec.name }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Time Limit -->
                    <div v-if="activeActivity !== 'game'">
                        <label class="field-label">Time limit in minutes (optional)</label>
                        <input
                            v-model="formTimeLimit"
                            type="number"
                            min="1"
                            class="input-field"
                            placeholder="e.g. 20 (leave blank for unlimited)"
                        />
                    </div>

                    <!-- Game Subject & XP Reward -->
                    <div v-if="activeActivity === 'game'">
                        <label class="field-label">Subject</label>
                        <select v-model="formSubjectId" class="input-field">
                            <option :value="null">Select subject</option>
                            <option v-for="s in props.subjects || []" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                    <div v-if="activeActivity === 'game'">
                        <label class="field-label">XP Reward</label>
                        <input
                            v-model.number="formXpReward"
                            type="number"
                            min="5"
                            max="500"
                            class="input-field"
                            placeholder="25"
                        />
                    </div>

                    <!-- Max Score and XP Reward (For Practical) -->
                    <div v-if="activeActivity === 'practical'">
                        <label class="field-label">Maximum Score</label>
                        <input
                            v-model.number="formMaxScore"
                            type="number"
                            min="1"
                            class="input-field"
                            placeholder="100"
                        />
                    </div>
                    <div v-if="activeActivity === 'practical'">
                        <label class="field-label">XP Reward</label>
                        <input
                            v-model.number="formPracticalXpReward"
                            type="number"
                            min="1"
                            max="500"
                            class="input-field"
                            placeholder="10"
                        />
                    </div>

                    <!-- Teacher Reassign (Superadmin) -->
                    <div v-if="teachers?.length && isSuperadmin">
                        <label class="field-label">Assign to Teacher</label>
                        <select v-model="formTeacherId" class="input-field">
                            <option :value="null">Current Superadmin (Self)</option>
                            <option v-for="t in teachers" :key="t.id" :value="t.id">
                                {{ t.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Items Editor: QUIZ / SEATWORK / EXAM QUESTIONS -->
            <div v-if="activeActivity === 'quiz' || activeActivity === 'seatwork' || activeActivity === 'exam'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-[var(--gl-text-muted)]">
                        {{ activeActivity === 'exam' ? 'Exam Questions (Part I)' : activeActivity === 'quiz' ? 'Quiz Questions' : 'Seatwork Questions' }} ({{ seatworkQuestions.length }} items)
                    </h3>
                    <button
                        type="button"
                        class="flex items-center gap-1.5 rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface)] px-3 py-1.5 text-xs font-semibold text-[var(--gl-primary)] hover:border-[var(--gl-primary)] transition-all"
                        @click="addSeatworkQuestion"
                    >
                        <Plus class="h-4 w-4" />
                        Add Question
                    </button>
                </div>

                <div
                    v-for="(q, qIndex) in seatworkQuestions"
                    :key="qIndex"
                    class="card p-5 space-y-4"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-[var(--gl-surface-2)] text-xs font-black text-[var(--gl-text-primary)]">
                                {{ qIndex + 1 }}
                            </span>
                            <select
                                v-model="q.type"
                                @change="onSeatworkTypeChange(q)"
                                class="rounded-lg border border-[var(--gl-border)] bg-[var(--gl-surface-2)] px-2.5 py-1 text-xs font-bold"
                            >
                                <option value="identification">Identification</option>
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="true_false">True / False</option>
                                <option value="enumeration">Enumeration</option>
                                <option value="matching">Matching Type</option>
                                <option value="essay">Essay</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1.5 text-xs">
                                <span class="text-[var(--gl-text-muted)]">Points:</span>
                                <input
                                    v-model.number="q.points"
                                    type="number"
                                    min="1"
                                    class="w-16 rounded-lg border border-[var(--gl-border)] bg-[var(--gl-surface-2)] px-2 py-1 text-xs text-center font-bold"
                                />
                            </div>

                            <button
                                v-if="seatworkQuestions.length > 1"
                                type="button"
                                class="rounded-lg p-1.5 text-[var(--gl-text-muted)] hover:bg-[rgba(239,68,68,0.1)] hover:text-[var(--gl-danger)] transition-all"
                                title="Remove question"
                                @click="removeSeatworkQuestion(qIndex)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div>
                        <textarea
                            v-model="q.question_text"
                            rows="2"
                            class="input-field text-sm"
                            placeholder="Enter question text..."
                            required
                        ></textarea>
                    </div>

                    <!-- Identification Type Answer Input -->
                    <div v-if="q.type === 'identification'" class="space-y-1">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-[var(--gl-text-muted)]">
                            Correct Answer
                        </label>
                        <input
                            v-model="q.correct_answer"
                            type="text"
                            class="input-field text-xs font-semibold"
                            placeholder="Exact correct answer string..."
                            required
                        />
                    </div>

                    <!-- Multiple Choice & True/False Options -->
                    <div v-else-if="q.type === 'multiple_choice' || q.type === 'true_false'" class="space-y-2">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-[var(--gl-text-muted)]">
                            Options (click circle to set correct answer)
                        </label>
                        <div
                            v-for="(opt, optIndex) in q.options"
                            :key="optIndex"
                            class="flex items-center gap-2.5 rounded-xl border p-2 transition-all"
                            :class="
                                opt.is_correct
                                    ? 'border-[var(--gl-success)] bg-[rgba(16,185,129,0.06)]'
                                    : 'border-[var(--gl-border)] bg-[var(--gl-surface-2)]'
                            "
                        >
                            <button
                                type="button"
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border transition-all"
                                :class="
                                    opt.is_correct
                                        ? 'border-[var(--gl-success)] bg-[var(--gl-success)] text-white'
                                        : 'border-[var(--gl-border)] hover:border-[var(--gl-primary)]'
                                "
                                @click="setSeatworkCorrectOption(q, optIndex)"
                            >
                                <Check v-if="opt.is_correct" class="h-3 w-3" :stroke-width="3" />
                            </button>

                            <input
                                v-model="opt.option_text"
                                type="text"
                                class="flex-1 bg-transparent text-xs font-medium focus:outline-hidden"
                                placeholder="Option text..."
                                required
                            />

                            <button
                                v-if="q.options.length > 2"
                                type="button"
                                class="p-1 text-[var(--gl-text-muted)] hover:text-[var(--gl-danger)]"
                                @click="removeSeatworkOption(q, optIndex)"
                            >
                                <X class="h-3.5 w-3.5" />
                            </button>
                        </div>

                        <button
                            v-if="q.type === 'multiple_choice' && (!q.options || q.options.length < 6)"
                            type="button"
                            class="text-xs font-semibold text-[var(--gl-primary)] hover:underline inline-flex items-center gap-1 mt-1"
                            @click="addSeatworkOption(q)"
                        >
                            <Plus class="h-3.5 w-3.5" />
                            Add Option
                        </button>
                    </div>

                    <!-- Enumeration Items -->
                    <div v-else-if="q.type === 'enumeration'" class="space-y-2">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-[var(--gl-text-muted)]">
                            Accepted Enumeration Items
                        </label>
                        <div
                            v-for="(item, itemIdx) in q.enum_items"
                            :key="itemIdx"
                            class="flex items-center gap-2"
                        >
                            <span class="text-xs font-bold text-[var(--gl-text-muted)] w-4 text-right">
                                {{ itemIdx + 1 }}.
                            </span>
                            <input
                                v-model="q.enum_items[itemIdx]"
                                type="text"
                                class="input-field text-xs flex-1"
                                placeholder="Accepted answer item..."
                            />
                            <button
                                v-if="q.enum_items.length > 1"
                                type="button"
                                class="p-1.5 text-[var(--gl-text-muted)] hover:text-[var(--gl-danger)]"
                                @click="removeEnumItem(q, itemIdx)"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                        <button
                            type="button"
                            class="text-xs font-semibold text-[var(--gl-primary)] hover:underline inline-flex items-center gap-1"
                            @click="addEnumItem(q)"
                        >
                            <Plus class="h-3.5 w-3.5" />
                            Add Item
                        </button>
                    </div>

                    <!-- Matching Pairs -->
                    <div v-else-if="q.type === 'matching'" class="space-y-2">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-[var(--gl-text-muted)]">
                            Matching Pairs (Column A ↔ Column B)
                        </label>
                        <div
                            v-for="(pair, pairIdx) in q.matching_pairs"
                            :key="pairIdx"
                            class="flex items-center gap-2"
                        >
                            <input
                                v-model="pair.left_text"
                                type="text"
                                class="input-field text-xs flex-1"
                                placeholder="Left prompt / item..."
                            />
                            <span class="text-xs font-bold text-[var(--gl-text-muted)]">↔</span>
                            <input
                                v-model="pair.right_text"
                                type="text"
                                class="input-field text-xs flex-1"
                                placeholder="Right matching answer..."
                            />
                            <button
                                v-if="q.matching_pairs.length > 1"
                                type="button"
                                class="p-1.5 text-[var(--gl-text-muted)] hover:text-[var(--gl-danger)]"
                                @click="removeMatchingPair(q, pairIdx)"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                        <button
                            type="button"
                            class="text-xs font-semibold text-[var(--gl-primary)] hover:underline inline-flex items-center gap-1"
                            @click="addMatchingPair(q)"
                        >
                            <Plus class="h-3.5 w-3.5" />
                            Add Pair
                        </button>
                    </div>

                    <!-- Essay Rubric Criteria -->
                    <div v-else-if="q.type === 'essay'" class="space-y-1">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-[var(--gl-text-muted)]">
                            Essay Grading Criteria / Model Answer
                        </label>
                        <textarea
                            v-model="q.criteria"
                            rows="2"
                            class="input-field text-xs"
                            placeholder="Key concepts or scoring rubric for evaluating student responses..."
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Items Editor: PRACTICAL / EXAM RUBRIC -->
            <div v-if="activeActivity === 'practical' || activeActivity === 'exam'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-[var(--gl-text-muted)]">
                            {{ activeActivity === 'exam' ? 'Exam Practical Rubric (Part II)' : 'Practical Rubric Criteria' }} ({{ practicalCriteria.length }} criteria)
                        </h3>
                        <p class="text-xs text-[var(--gl-text-secondary)]">
                            Total points: <strong class="text-[var(--gl-primary)]">{{ totalPracticalPoints }} pts</strong>
                        </p>
                    </div>
                    <button
                        type="button"
                        class="flex items-center gap-1.5 rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface)] px-3 py-1.5 text-xs font-semibold text-[var(--gl-primary)] hover:border-[var(--gl-primary)] transition-all"
                        @click="addPracticalCriterion"
                    >
                        <Plus class="h-4 w-4" />
                        Add Criterion
                    </button>
                </div>

                <div
                    v-for="(c, cIndex) in practicalCriteria"
                    :key="cIndex"
                    class="card p-5 space-y-3"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-[var(--gl-surface-2)] text-xs font-black text-[var(--gl-text-primary)]">
                                {{ cIndex + 1 }}
                            </span>
                            <span class="text-xs font-semibold text-[var(--gl-text-muted)]">
                                Criterion {{ cIndex + 1 }}
                            </span>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1.5 text-xs">
                                <span class="text-[var(--gl-text-muted)]">Max Points:</span>
                                <input
                                    v-model.number="c.max_points"
                                    type="number"
                                    min="1"
                                    class="w-16 rounded-lg border border-[var(--gl-border)] bg-[var(--gl-surface-2)] px-2 py-1 text-xs text-center font-bold"
                                />
                            </div>

                            <button
                                v-if="practicalCriteria.length > 1"
                                type="button"
                                class="rounded-lg p-1.5 text-[var(--gl-text-muted)] hover:bg-[rgba(239,68,68,0.1)] hover:text-[var(--gl-danger)] transition-all"
                                title="Remove criterion"
                                @click="removePracticalCriterion(cIndex)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="sm:col-span-1">
                            <label class="field-label text-xs">Criterion Name</label>
                            <input
                                v-model="c.criterion_name"
                                type="text"
                                class="input-field text-xs font-semibold"
                                placeholder="e.g. Design & Composition"
                                required
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="field-label text-xs">Description / Expectations</label>
                            <input
                                v-model="c.description"
                                type="text"
                                class="input-field text-xs"
                                placeholder="Demonstrates correct use of grids, alignment, and visual hierarchy..."
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Editor: GAME CARDS -->
            <div v-else-if="activeActivity === 'game'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-[var(--gl-text-muted)]">
                            Game Cards ({{ gameCards.length }} cards &bull; Format: {{ selectedGameType }})
                        </h3>
                        <p class="text-xs text-[var(--gl-text-secondary)]">
                            Review and adjust the questions, answers, and hints below.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="flex items-center gap-1.5 rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface)] px-3 py-1.5 text-xs font-semibold text-[var(--gl-primary)] hover:border-[var(--gl-primary)] transition-all"
                        @click="addGameCard"
                    >
                        <Plus class="h-4 w-4" />
                        Add Card
                    </button>
                </div>

                <div
                    v-for="(card, cardIdx) in gameCards"
                    :key="cardIdx"
                    class="card p-5 space-y-3"
                >
                    <div class="flex items-center justify-between">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-[var(--gl-surface-2)] text-xs font-black text-[var(--gl-text-primary)]">
                            {{ cardIdx + 1 }}
                        </span>
                        <button
                            v-if="gameCards.length > 1"
                            type="button"
                            class="rounded-lg p-1.5 text-[var(--gl-text-muted)] hover:bg-[rgba(239,68,68,0.1)] hover:text-[var(--gl-danger)] transition-all"
                            title="Remove card"
                            @click="removeGameCard(cardIdx)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="field-label text-xs">Clue / Question</label>
                            <input
                                v-model="card.question"
                                type="text"
                                class="input-field text-xs font-semibold"
                                placeholder="Clue, question, or hint..."
                                required
                            />
                        </div>
                        <div>
                            <label class="field-label text-xs">Target Answer</label>
                            <input
                                v-model="card.answer"
                                type="text"
                                class="input-field text-xs font-bold uppercase"
                                placeholder="TARGET WORD"
                                required
                            />
                        </div>
                    </div>

                    <!-- Options / Hints for FourPics and Multiple-choice games -->
                    <div v-if="card.options && card.options.length" class="space-y-1.5 pt-2">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-[var(--gl-text-muted)]">
                            Visual Clues / Choices ({{ card.options.length }} items)
                        </label>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <input
                                v-for="(opt, optIdx) in card.options"
                                :key="optIdx"
                                v-model="card.options[optIdx]"
                                type="text"
                                class="input-field text-xs"
                                :placeholder="`Clue ${optIdx + 1}...`"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Error Banner -->
            <div
                v-if="saveError"
                class="flex items-center gap-2.5 rounded-xl border border-[var(--gl-danger)]/30 bg-[rgba(239,68,68,0.08)] p-3.5 text-xs text-[var(--gl-danger)] font-medium"
            >
                <AlertCircle class="h-4 w-4 shrink-0" />
                <span>{{ saveError }}</span>
            </div>

            <!-- Actions Bar -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-[var(--gl-border)]">
                <button
                    type="button"
                    class="rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface)] px-4 py-2.5 text-xs font-semibold text-[var(--gl-text-secondary)] hover:text-[var(--gl-text-primary)] transition-all"
                    @click="resetForm"
                >
                    Cancel / Start Over
                </button>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface)] px-4 py-2.5 text-xs font-semibold text-[var(--gl-text-primary)] hover:border-[var(--gl-primary)] hover:text-[var(--gl-primary)] transition-all flex items-center gap-1.5 disabled:opacity-50"
                        :disabled="isSaving"
                        @click="submitActivity('save_and_edit')"
                    >
                        <Edit3 class="h-3.5 w-3.5" />
                        <span>Save & Create New</span>
                    </button>

                    <button
                        type="button"
                        class="rounded-xl border border-[var(--gl-primary)]/40 bg-[var(--gl-primary-glow)] px-4 py-2.5 text-xs font-bold text-[var(--gl-primary)] hover:bg-[var(--gl-primary)] hover:text-white transition-all flex items-center gap-1.5 disabled:opacity-50"
                        :disabled="isSaving"
                        @click="submitActivity('save')"
                    >
                        <Check class="h-3.5 w-3.5" />
                        <span>Save as Draft</span>
                    </button>

                    <button
                        type="button"
                        class="btn-primary flex items-center gap-2 px-5 py-2.5 text-xs font-bold shadow-md shadow-[var(--gl-primary-glow)] disabled:opacity-50"
                        :disabled="isSaving"
                        @click="submitActivity('publish')"
                    >
                        <Send class="h-3.5 w-3.5" />
                        <span>Save & Publish Now</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
