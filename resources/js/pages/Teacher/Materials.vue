<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    Folder,
    FolderOpen,
    ArrowLeft,
    ChevronRight,
    FileText,
    FileQuestion,
    ClipboardList,
    FlaskConical,
    Gamepad2,
    Presentation,
    Download,
    Trash2,
    Pencil,
    Upload,
    Search,
    X,
    Sparkles,
    Check,
    Eye,
    Zap,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    materials: any[];
    gradeLevels: any[];
    allSections: any[];
    isSuperadmin?: boolean;
    currentUserId?: number;
    teachers?: any[];
    subjects?: any[];
    quarters?: any[];
    schoolYears?: any[];
}>();
const page = usePage();
const flash = computed(() => (page.props as any).flash);

const localMaterials = ref<any[]>([...(props.materials || [])]);

watch(
    () => props.materials,
    (val) => {
        localMaterials.value = [...(val || [])];
    },
    { deep: true },
);

const search = ref('');
const activeFolder = ref<number | 'unassigned' | null>(null);
const showUpload = ref(false);
const activeType = ref('all');
const typeTabs = [
    { value: 'all', label: 'All' },
    { value: 'pdf', label: 'PDF' },
    { value: 'ppt', label: 'PPT' },
];

const quarterFolders = computed(() => {
    const quarters = props.quarters || [];
    const folders: Array<{
        id: number | 'unassigned';
        name: string;
        code: string;
        total: number;
        pdfCount: number;
        pptCount: number;
        isUnassigned?: boolean;
    }> = quarters.map((q: any) => {
        const items = localMaterials.value.filter(
            (m) => Number(m.quarter_id) === Number(q.id),
        );
        const pdfCount = items.filter(
            (m) => (m.file_type || '').toLowerCase() === 'pdf',
        ).length;

        return {
            id: Number(q.id),
            name: q.name,
            code: q.code || q.name,
            total: items.length,
            pdfCount,
            pptCount: items.length - pdfCount,
        };
    });

    const unassignedItems = localMaterials.value.filter((m) => !m.quarter_id);

    if (unassignedItems.length > 0 || folders.length === 0) {
        const pdfCount = unassignedItems.filter(
            (m) => (m.file_type || '').toLowerCase() === 'pdf',
        ).length;
        folders.push({
            id: 'unassigned',
            name: 'General / Unassigned',
            code: 'GEN',
            total: unassignedItems.length,
            pdfCount,
            pptCount: unassignedItems.length - pdfCount,
            isUnassigned: true,
        });
    }

    return folders;
});

const currentFolder = computed(() => {
    if (activeFolder.value === null) {
return null;
}

    return (
        quarterFolders.value.find((f) => f.id === activeFolder.value) || null
    );
});

const showFolderGrid = computed(() => {
    return (
        activeFolder.value === null &&
        !search.value.trim() &&
        (props.quarters?.length || 0) > 0
    );
});

function openFolder(folderId: number | 'unassigned') {
    activeFolder.value = folderId;
}

function openUpload() {
    if (typeof activeFolder.value === 'number') {
        form.quarter_id = activeFolder.value;
    } else if (activeFolder.value === 'unassigned') {
        form.quarter_id = null;
    }

    showUpload.value = true;
}

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();

    return localMaterials.value.filter((m) => {
        const t = (m.file_type || '').toLowerCase();

        if (activeType.value === 'pdf' && t !== 'pdf') {
return false;
}

        if (activeType.value === 'ppt' && t === 'pdf') {
return false;
}

        if (activeFolder.value === 'unassigned') {
            if (m.quarter_id) {
return false;
}
        } else if (activeFolder.value !== null) {
            if (Number(m.quarter_id) !== Number(activeFolder.value)) {
return false;
}
        }

        if (!q) {
return true;
}

        return (
            m.title.toLowerCase().includes(q) ||
            m.subject.toLowerCase().includes(q)
        );
    });
});

function tint(type: string) {
    return (type || '').toLowerCase() === 'pdf'
        ? 'rgba(239,68,68,0.05)'
        : 'rgba(59,130,246,0.05)';
}

function tintBorder(type: string) {
    return (type || '').toLowerCase() === 'pdf'
        ? 'rgba(239,68,68,0.16)'
        : 'rgba(59,130,246,0.16)';
}

const form = useForm({
    title: '',
    subject: '',
    subject_id: null as number | null,
    quarter_id: null as number | null,
    school_year_id: null as number | null,
    description: '',
    file: null as File | null,
    grade_level_id: '' as string | number,
    teacher_id: null as number | null,
});

if ((props.subjects || []).length > 0) {
    form.subject_id = (props.subjects || [])[0]?.id ?? null;
}

const showEdit = ref(false);
const editingMaterial = ref<any>(null);
const editForm = useForm({
    title: '',
    subject: '',
    subject_id: null as number | null,
    quarter_id: null as number | null,
    school_year_id: null as number | null,
    description: '',
    file: null as File | null,
    grade_level_id: '' as string | number,
    teacher_id: null as number | null,
});

function openEdit(m: any) {
    editingMaterial.value = m;
    editForm.title = m.title || '';
    editForm.subject = m.subject || '';
    editForm.subject_id = m.subject_id || null;
    editForm.quarter_id = m.quarter_id || null;
    editForm.school_year_id = m.school_year_id || null;
    editForm.description = m.description || '';
    editForm.grade_level_id = m.grade_level_id || '';
    editForm.teacher_id = m.teacher_id || null;
    editForm.file = null;
    showEdit.value = true;
}

function submitEdit() {
    if (!editingMaterial.value) {
return;
}

    editForm.post(`/teacher/materials/${editingMaterial.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showEdit.value = false;
            editingMaterial.value = null;
            editForm.reset();
        },
    });
}

function submitUpload() {
    form.post('/teacher/materials', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showUpload.value = false;
        },
    });
}

function remove(m: any) {
    if (!window.confirm(`Delete "${m.title}"? The file will be removed.`)) {
        return;
    }

    const backup = [...localMaterials.value];
    localMaterials.value = localMaterials.value.filter((item: any) => item.id !== m.id);
    router.delete('/teacher/materials/' + m.id, {
        preserveScroll: true,
        onError: () => {
            localMaterials.value = backup;
        },
    });
}

function reassign(m: any, teacherId: string | number) {
    if (!teacherId || Number(teacherId) === m.teacher_id) {
return;
}

    router.patch(
        `/teacher/materials/${m.id}/reassign`,
        { teacher_id: Number(teacherId) },
        { preserveScroll: true },
    );
}

// ---- AI quiz generation ----
const showGenerate = ref(false);
const genMaterial = ref<any>(null);
const genStep = ref<'options' | 'preview'>('options');
const genActivity = ref('quiz');
const genCount = ref(5);
const genTypes = ref<string[]>([
    'multiple_choice',
    'true_false',
    'identification',
]);
const genGameType = ref('flashcard');
const genSubject = ref('');
const genGradeText = ref('');
const genGradeId = ref<number | null>(null);
const genSectionIds = ref<number[]>([]);
const genQuestions = ref<any[]>([]);
const genItems = ref<any[]>([]);
const genTitle = ref('');
const genInstructions = ref('');
const genProvider = ref('');
const genLoading = ref(false);
const genSaving = ref(false);
const genError = ref('');

const genActivities = [
    { id: 'quiz', label: 'Quiz', icon: FileQuestion },
    { id: 'seatwork', label: 'Seatwork', icon: ClipboardList },
    { id: 'practical', label: 'Practical', icon: FlaskConical },
    { id: 'exam', label: 'Exam', icon: FileText },
    { id: 'game', label: 'Game', icon: Gamepad2 },
    { id: 'live_arena', label: 'Live Arena', icon: Zap },
];

const seatworkTypes = [
    { id: 'identification', label: 'ID' },
    { id: 'enumeration', label: 'Enum' },
    { id: 'true_false', label: 'T/F' },
    { id: 'multiple_choice', label: 'MC' },
    { id: 'matching', label: 'Match' },
    { id: 'essay', label: 'Essay' },
];

const genActivityLabel = computed(() => {
    return (
        {
            quiz: 'quiz',
            seatwork: 'seatwork',
            practical: 'practical',
            exam: 'exam',
            game: 'game',
        }[genActivity.value] ?? 'quiz'
    );
});

const previewCountLabel = computed(() => {
    if (genActivity.value === 'practical') {
return `${genItems.value.length} criteria`;
}

    if (genActivity.value === 'game') {
return `${genItems.value.length} cards`;
}

    return `${genQuestions.value.length} questions`;
});

const canGenerate = computed(() => {
    if (genActivity.value === 'live_arena') {
        return true;
    }

    if (genActivity.value === 'game') {
        return genSubject.value.trim() !== '' && genTypesOk.value;
    }

    return (
        genGradeId.value !== null &&
        (genActivity.value !== 'quiz' || genSectionIds.value.length > 0) &&
        genTypesOk.value
    );
});

const genTypesOk = computed(() => {
    if (genActivity.value === 'practical' || genActivity.value === 'game') {
return true;
}

    return genTypes.value.length > 0;
});

const genSections = computed(() => {
    if (!genGradeId.value) {
return [];
}

    return (props.allSections || []).filter(
        (s: any) => s.grade_level_id === Number(genGradeId.value),
    );
});

function openGenerate(m: any) {
    genMaterial.value = m;
    genStep.value = 'options';
    genActivity.value = 'quiz';
    genCount.value = 5;
    genTypes.value = ['multiple_choice', 'true_false', 'identification'];
    genGameType.value = 'flashcard';
    genSubject.value = m.subject || '';
    genGradeText.value = '';
    genGradeId.value = null;
    genSectionIds.value = [];
    genQuestions.value = [];
    genItems.value = [];
    genTitle.value = '';
    genInstructions.value = '';
    genProvider.value = '';
    genError.value = '';
    genLoading.value = false;
    genSaving.value = false;
    showGenerate.value = true;
}

function closeGenerate() {
    showGenerate.value = false;
    genMaterial.value = null;
    genStep.value = 'options';
    genQuestions.value = [];
    genItems.value = [];
    genError.value = '';
}

function toggleGenType(t: string) {
    if (genTypes.value.includes(t)) {
        if (genTypes.value.length > 1) {
            genTypes.value = genTypes.value.filter((x) => x !== t);
        }
    } else {
        genTypes.value.push(t);
    }
}

function toggleGenSection(id: number) {
    if (genSectionIds.value.includes(id)) {
        genSectionIds.value = genSectionIds.value.filter((x) => x !== id);
    } else {
        genSectionIds.value.push(id);
    }
}

async function runGenerate() {
    if (!genMaterial.value || genLoading.value) {
return;
}

    if (genActivity.value === 'live_arena') {
        const matId = genMaterial.value.id;
        closeGenerate();
        router.visit(`/teacher/games/live/create?material_id=${matId}`);

        return;
    }

    genLoading.value = true;
    genError.value = '';

    try {
        const token =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';
        const res = await fetch(
            `/teacher/materials/${genMaterial.value.id}/generate`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    activity: genActivity.value,
                    count: Math.max(
                        1,
                        Math.min(50, Number(genCount.value) || 5),
                    ),
                    types:
                        genActivity.value === 'seatwork'
                            ? genTypes.value
                            : genTypes.value.filter(
                                  (t) =>
                                      t === 'multiple_choice' ||
                                      t === 'true_false' ||
                                      t === 'identification',
                              ),
                    game_type: genGameType.value,
                }),
            },
        );
        const data = await res.json();

        if (!res.ok || !data.ok) {
            genError.value =
                data.message || 'Generation failed. Please try again.';

            return;
        }

        const items = data.items || data.questions || [];
        genProvider.value = data.provider || '';
        genTitle.value = data.title || '';
        genInstructions.value = data.instructions || '';

        if (!items.length) {
            genError.value = 'No content was generated. Try again.';

            return;
        }

        if (genActivity.value === 'practical' || genActivity.value === 'game') {
            genItems.value = items;
            genQuestions.value = [];
        } else if (genActivity.value === 'exam') {
            genQuestions.value = (items[0]?.questions || items) as any[];
            genItems.value = items;
        } else {
            genQuestions.value = items;
            genItems.value = [];
        }

        genStep.value = 'preview';
    } catch {
        genError.value = 'Could not reach the server. Please try again.';
    } finally {
        genLoading.value = false;
    }
}

function saveDraft() {
    if (!genMaterial.value || genSaving.value) {
return;
}

    const targetTeacherId = genMaterial.value?.teacher_id ?? null;

    const title =
        genTitle.value.trim() ||
        (genMaterial.value.title +
            ' - ' +
            genActivityLabel.value[0].toUpperCase() +
            genActivityLabel.value.slice(1));
    const description =
        genInstructions.value.trim() ||
        ('AI-generated draft from material: ' +
            genMaterial.value.title +
            '. Please review before publishing.');

    if (genActivity.value === 'seatwork') {
        if (!genGradeId.value || !genQuestions.value.length) {
            genError.value = 'Pick a grade level first.';
            genStep.value = 'options';

            return;
        }

        genSaving.value = true;
        router.post(
            '/teacher/seatworks',
            {
                title,
                instructions: genInstructions.value.trim() || '',
                time_limit_minutes: null,
                teacher_id: targetTeacherId,
                grade_level_ids: [Number(genGradeId.value)],
                questions: genQuestions.value,
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    genSaving.value = false;
                },
                onSuccess: () => {
                    closeGenerate();
                },
            },
        );

        return;
    }

    if (genActivity.value === 'practical') {
        if (!genGradeId.value || !genItems.value.length) {
            genError.value = 'Pick a grade level first.';
            genStep.value = 'options';

            return;
        }

        genSaving.value = true;
        router.post(
            '/teacher/practicals',
            {
                title,
                instructions: '',
                time_limit_minutes: null,
                max_attempts: 3,
                teacher_id: targetTeacherId,
                grade_level_ids: [Number(genGradeId.value)],
                criteria: genItems.value,
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    genSaving.value = false;
                },
                onSuccess: () => {
                    closeGenerate();
                },
            },
        );

        return;
    }

    if (genActivity.value === 'exam') {
        if (!genGradeId.value || !genQuestions.value.length) {
            genError.value = 'Pick a grade level first.';
            genStep.value = 'options';

            return;
        }

        genSaving.value = true;
        router.post(
            '/teacher/exams',
            {
                title,
                instructions: '',
                time_limit_minutes: null,
                teacher_id: targetTeacherId,
                grade_level_ids: [Number(genGradeId.value)],
                sections: [
                    {
                        title: 'Part 1 - Questions',
                        section_type: 'questions',
                        instructions: '',
                        questions: genQuestions.value,
                    },
                ],
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    genSaving.value = false;
                },
                onSuccess: () => {
                    closeGenerate();
                },
            },
        );

        return;
    }

    if (genActivity.value === 'game') {
        if (!genSubject.value.trim() || !genItems.value.length) {
            genError.value = 'Enter a subject first.';
            genStep.value = 'options';

            return;
        }

        genSaving.value = true;
        router.post(
            '/teacher/games',
            {
                title,
                subject: genSubject.value.trim(),
                subject_id: genMaterial.value.subject_id || null,
                grade: genGradeText.value.trim() || null,
                description:
                    'AI-generated draft from material: ' +
                    genMaterial.value.title,
                teacher_id: targetTeacherId,
                xp_reward: 10,
                type: genGameType.value,
                hidden: true,
                cards: genItems.value,
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    genSaving.value = false;
                },
                onSuccess: () => {
                    closeGenerate();
                },
            },
        );

        return;
    }

    if (
        !genGradeId.value ||
        !genSectionIds.value.length ||
        !genQuestions.value.length
    ) {
        genError.value = 'Pick a grade level and at least one section first.';
        genStep.value = 'options';

        return;
    }

    genSaving.value = true;
    router.post(
        '/teacher/quizzes',
        {
            title,
            description,
            grade_level_ids: [Number(genGradeId.value)],
            section_ids: genSectionIds.value,
            time_limit_minutes: null,
            teacher_id: targetTeacherId,
            questions: genQuestions.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                genSaving.value = false;
            },
            onSuccess: () => {
                closeGenerate();
            },
        },
    );
}

function fileIcon(type: string) {
    return type === 'pdf' ? FileText : Presentation;
}

function fileColor(type: string) {
    return type === 'pdf'
        ? { bg: 'rgba(239,68,68,0.12)', fg: '#EF4444', icon: '#F87171' }
        : { bg: 'rgba(59,130,246,0.12)', fg: '#3B82F6', icon: '#60A5FA' };
}

function formatSize(bytes: number | null) {
    if (!bytes) {
        return '';
    }

    const kb = bytes / 1024;

    if (kb < 1024) {
        return kb.toFixed(0) + ' KB';
    }

    return (kb / 1024).toFixed(1) + ' MB';
}
</script>

<template>
    <Head title="Teaching Materials" />

    <div class="px-6 py-6">
        <div
            v-if="flash?.success"
            class="gl-fade-in mb-6 rounded-2xl px-5 py-3 text-sm font-medium"
            style="
                background: rgba(16, 185, 129, 0.12);
                border: 1px solid rgba(16, 185, 129, 0.35);
                color: var(--gl-success);
            "
        >
            {{ flash.success }}
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

        <!-- Header -->
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
            <div>
                <p
                    class="text-xs font-semibold tracking-widest uppercase"
                    style="color: var(--gl-text-muted)"
                >
                    Library
                </p>
                <h1
                    class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                    style="color: var(--gl-text-primary)"
                >
                    Teaching Materials
                </h1>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    <template v-if="showFolderGrid">
                        {{ quarterFolders.length }} quarter folder{{ quarterFolders.length === 1 ? '' : 's' }} · {{ localMaterials.length }} total material{{ localMaterials.length === 1 ? '' : 's' }}
                    </template>
                    <template v-else-if="currentFolder">
                        {{ currentFolder.name }} · {{ filtered.length }} material{{ filtered.length === 1 ? '' : 's' }}
                    </template>
                    <template v-else>
                        {{ filtered.length }} of {{ localMaterials.length }} materials
                    </template>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <div class="relative">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2"
                        :stroke-width="2"
                        style="color: var(--gl-text-muted)"
                    />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search materials..."
                        class="rounded-full border py-2.5 pr-4 pl-9 text-sm transition-all outline-none focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                        style="
                            background: var(--gl-surface);
                            color: var(--gl-text-primary);
                            border-color: var(--gl-border);
                            width: 190px;
                        "
                    />
                </div>
                <div v-if="!showFolderGrid" class="flex items-center gap-1.5">
                    <button
                        v-for="t in typeTabs"
                        :key="t.value"
                        @click="activeType = t.value"
                        class="rounded-full px-3.5 py-2 text-xs font-bold transition-all"
                        :style="
                            activeType === t.value
                                ? { background: '#1F2937', color: '#fff' }
                                : {
                                      background: 'var(--gl-surface)',
                                      border: '1px solid var(--gl-border)',
                                      color: 'var(--gl-text-secondary)',
                                  }
                        "
                    >
                        {{ t.label }}
                    </button>
                </div>
                <Link
                    href="/teacher/ai-creator"
                    class="flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold text-white transition-all hover:scale-105 shadow-sm"
                    style="
                        background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary));
                        box-shadow: 0 4px 14px var(--gl-primary-glow);
                    "
                    title="Create activities from documents with AI"
                >
                    <Sparkles class="h-4 w-4" :stroke-width="2.2" />
                    <span>AI Activity Creator</span>
                </Link>
                <button
                    @click="openUpload"
                    class="flex h-10 w-10 items-center justify-center rounded-xl text-white transition-all hover:scale-105"
                    style="
                        background: linear-gradient(135deg, #ff6a45, #f94f2b);
                        box-shadow: 0 4px 14px rgba(249, 79, 43, 0.4);
                    "
                    title="Upload material"
                >
                    <Upload class="h-5 w-5" :stroke-width="2.5" />
                </button>
            </div>
        </div>

        <!-- Quarter Folders Grid View -->
        <div
            v-if="showFolderGrid"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
        >
            <button
                v-for="folder in quarterFolders"
                :key="String(folder.id)"
                type="button"
                @click="openFolder(folder.id)"
                class="group flex flex-col justify-between rounded-2xl p-5 text-left transition-all hover:-translate-y-1 hover:shadow-lg"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <div>
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl transition-transform group-hover:scale-105"
                            :style="
                                folder.isUnassigned
                                    ? {
                                          background: 'rgba(107, 114, 128, 0.12)',
                                          color: 'var(--gl-text-secondary)',
                                      }
                                    : {
                                          background: 'rgba(245, 158, 11, 0.14)',
                                          color: '#f59e0b',
                                      }
                            "
                        >
                            <Folder class="h-6 w-6" :stroke-width="2" />
                        </div>
                        <span
                            class="rounded-full px-2.5 py-1 text-[11px] font-extrabold tracking-wider uppercase"
                            :style="
                                folder.isUnassigned
                                    ? {
                                          background: 'var(--gl-surface-2)',
                                          color: 'var(--gl-text-muted)',
                                      }
                                    : {
                                          background: 'rgba(14, 165, 233, 0.12)',
                                          color: '#0284c7',
                                      }
                            "
                        >
                            {{ folder.code }}
                        </span>
                    </div>
                    <h2
                        class="text-base font-extrabold tracking-tight"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ folder.name }}
                    </h2>
                    <p
                        class="mt-1 text-xs font-medium"
                        style="color: var(--gl-text-secondary)"
                    >
                        {{ folder.total }} material{{ folder.total === 1 ? '' : 's' }}
                    </p>
                </div>

                <div
                    class="mt-4 flex items-center justify-between border-t pt-3 text-[11px]"
                    style="border-color: var(--gl-border); color: var(--gl-text-muted)"
                >
                    <span>
                        {{ folder.pdfCount }} PDF · {{ folder.pptCount }} PPT
                    </span>
                    <span
                        class="inline-flex items-center gap-0.5 font-bold transition-transform group-hover:translate-x-0.5"
                        style="color: var(--gl-primary)"
                    >
                        Open
                        <ChevronRight class="h-3.5 w-3.5" :stroke-width="2.5" />
                    </span>
                </div>
            </button>
        </div>

        <!-- Inside a Quarter Folder (or Search Results) -->
        <template v-else>
            <!-- Folder Breadcrumb Bar -->
            <div
                v-if="props.quarters?.length"
                class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl px-4 py-3"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <div class="flex flex-wrap items-center gap-2.5">
                    <button
                        type="button"
                        @click="
                            activeFolder = null;
                            search = '';
                        "
                        class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition-all hover:scale-[1.02]"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-primary);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        <ArrowLeft class="h-3.5 w-3.5" :stroke-width="2.2" />
                        Quarter Folders
                    </button>
                    <ChevronRight
                        class="h-4 w-4"
                        style="color: var(--gl-text-muted)"
                    />
                    <div class="flex items-center gap-2">
                        <FolderOpen
                            class="h-4 w-4"
                            style="color: #f59e0b"
                            :stroke-width="2"
                        />
                        <span
                            class="text-sm font-extrabold"
                            style="color: var(--gl-text-primary)"
                        >
                            {{
                                currentFolder
                                    ? currentFolder.name
                                    : 'All Quarters (Search Results)'
                            }}
                        </span>
                        <span
                            v-if="currentFolder"
                            class="rounded-full px-2 py-0.5 text-[10px] font-extrabold uppercase"
                            style="
                                background: rgba(14, 165, 233, 0.12);
                                color: #0284c7;
                            "
                        >
                            {{ currentFolder.code }}
                        </span>
                    </div>
                </div>

                <!-- Quick Folder Switcher Pills -->
                <div class="flex flex-wrap items-center gap-1.5">
                    <button
                        v-for="folder in quarterFolders"
                        :key="String(folder.id)"
                        type="button"
                        @click="openFolder(folder.id)"
                        class="rounded-full px-3 py-1 text-xs font-bold transition-all"
                        :style="
                            activeFolder === folder.id
                                ? {
                                      background: 'var(--gl-primary)',
                                      color: '#fff',
                                  }
                                : {
                                      background: 'var(--gl-surface-2)',
                                      color: 'var(--gl-text-secondary)',
                                      border: '1px solid var(--gl-border)',
                                  }
                        "
                    >
                        {{ folder.code }} ({{ folder.total }})
                    </button>
                </div>
            </div>

        <!-- List -->
        <div
            v-if="filtered.length"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            <div
                v-for="m in filtered"
                :key="m.id"
                class="group flex flex-col rounded-2xl p-5 transition-all hover:-translate-y-0.5"
                :style="{
                    background: tint(m.file_type),
                    border: '1px solid ' + tintBorder(m.file_type),
                }"
            >
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl"
                        :style="{ background: fileColor(m.file_type).bg }"
                    >
                        <component
                            :is="fileIcon(m.file_type)"
                            class="h-5 w-5"
                            :style="{ color: fileColor(m.file_type).icon }"
                            :stroke-width="2"
                        />
                    </div>
                    <span
                        class="rounded-full px-2.5 py-1 text-[10px] font-extrabold tracking-wider"
                        :style="{
                            background: fileColor(m.file_type).bg,
                            color: fileColor(m.file_type).icon,
                        }"
                    >
                        {{ m.file_type.toUpperCase() }}
                    </span>
                </div>
                <p
                    class="text-sm leading-snug font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    {{ m.title }}
                </p>
                <p
                    v-if="m.description"
                    class="mt-1 line-clamp-2 text-xs"
                    style="color: var(--gl-text-secondary)"
                >
                    {{ m.description }}
                </p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span
                        class="rounded-full px-2.5 py-0.5 text-[11px] font-medium"
                        style="
                            background: rgba(16, 185, 129, 0.1);
                            color: var(--gl-success);
                        "
                    >
                        {{ m.subject }}
                    </span>
                    <span
                        v-if="m.quarter_name"
                        class="rounded-full px-2.5 py-0.5 text-[11px] font-medium"
                        style="
                            background: rgba(14, 165, 233, 0.1);
                            color: #0284c7;
                        "
                    >
                        {{ m.quarter_name }}
                    </span>
                    <span
                        v-if="m.school_year_name"
                        class="rounded-full px-2.5 py-0.5 text-[11px] font-medium"
                        style="
                            background: rgba(139, 92, 246, 0.1);
                            color: #8b5cf6;
                        "
                    >
                        S.Y. {{ m.school_year_name }}
                    </span>
                    <span
                        v-if="m.grade_level_name"
                        class="rounded-full px-2.5 py-0.5 text-[11px] font-medium"
                        style="
                            background: rgba(59, 130, 246, 0.1);
                            color: var(--gl-primary);
                        "
                    >
                        {{ m.grade_level_name }}
                    </span>
                    <span
                        v-else
                        class="rounded-full px-2.5 py-0.5 text-[11px] font-medium"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-muted);
                        "
                    >
                        All grades
                    </span>
                    <span
                        v-if="isSuperadmin && m.teacher_name"
                        class="rounded-full px-2.5 py-0.5 text-[11px] font-medium"
                        style="
                            background: rgba(249, 115, 22, 0.1);
                            color: #f97316;
                        "
                    >
                        {{ m.teacher_name }}
                    </span>
                </div>
                <div
                    class="mt-3 flex items-center justify-between border-t pt-3"
                    style="border-color: var(--gl-border)"
                >
                    <span
                        class="truncate text-[11px]"
                        style="color: var(--gl-text-muted)"
                        >{{ m.file_name }}</span
                    >
                    <span
                        class="shrink-0 text-[11px] tabular-nums"
                        style="color: var(--gl-text-muted)"
                    >
                        {{ formatSize(m.file_size) }}
                    </span>
                </div>
                <div class="mt-3 flex items-center gap-1.5">
                    <a
                        :href="m.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        title="Open"
                        class="flex h-9 flex-1 items-center justify-center gap-1.5 rounded-xl text-xs font-bold transition-all hover:scale-[1.02]"
                        style="
                            background: var(--gl-surface);
                            color: var(--gl-text-secondary);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        <FileText class="h-3.5 w-3.5" :stroke-width="2" /> Open
                    </a>
                    <a
                        :href="`/materials/${m.id}/download`"
                        title="Download"
                        class="flex h-9 flex-1 items-center justify-center gap-1.5 rounded-xl text-xs font-bold transition-all hover:scale-[1.02]"
                        style="
                            background: var(--gl-surface);
                            color: var(--gl-text-secondary);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        <Download class="h-3.5 w-3.5" :stroke-width="2" /> Save
                    </a>
                    <Link
                        :href="`/teacher/games/live/create?material_id=${m.id}`"
                        title="Host Live Match Arena based on this material"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-white transition-all hover:scale-105"
                        style="
                            background: linear-gradient(
                                135deg,
                                #059669,
                                #10b981
                            );
                            box-shadow: 0 0 10px rgba(16, 185, 129, 0.35);
                        "
                    >
                        <Zap class="h-4 w-4" :stroke-width="2" />
                    </Link>
                    <button
                        @click="openGenerate(m)"
                        title="Generate quiz with AI"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-white transition-all hover:scale-105"
                        style="
                            background: linear-gradient(
                                135deg,
                                #7c3aed,
                                #4f46e5
                            );
                            box-shadow: 0 0 10px rgba(124, 58, 237, 0.35);
                        "
                    >
                        <Sparkles class="h-4 w-4" :stroke-width="2" />
                    </button>
                    <button
                        v-if="isSuperadmin || m.teacher_id === currentUserId"
                        @click="openEdit(m)"
                        title="Edit material"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition-colors hover:bg-[var(--gl-surface-hover)]"
                        style="
                            color: var(--gl-text-secondary);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        <Pencil class="h-3.5 w-3.5" :stroke-width="2" />
                    </button>
                    <button
                        v-if="isSuperadmin || m.teacher_id === currentUserId"
                        @click="remove(m)"
                        title="Delete"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition-colors hover:bg-[var(--gl-danger-bg)]"
                        style="
                            color: var(--gl-danger);
                            border: 1px solid rgba(239, 68, 68, 0.2);
                        "
                    >
                        <Trash2 class="h-4 w-4" :stroke-width="2" />
                    </button>
                </div>

                <!-- Reassign Row for superadmin -->
                <div
                    v-if="teachers?.length"
                    class="mt-3 flex items-center justify-between gap-2 border-t pt-2.5 text-xs"
                    style="border-color: var(--gl-border)"
                >
                    <span
                        class="shrink-0 text-[11px] font-medium"
                        style="color: var(--gl-text-muted)"
                    >
                        Teacher:
                    </span>
                    <select
                        :value="m.teacher_id"
                        @change="reassign(m, ($event.target as HTMLSelectElement).value)"
                        class="max-w-[170px] truncate rounded-xl border px-2.5 py-1 text-xs outline-none transition-colors"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-primary);
                            border-color: var(--gl-border);
                        "
                    >
                        <option
                            v-if="!teachers.some((t: any) => t.id === m.teacher_id)"
                            :value="m.teacher_id"
                            disabled
                        >
                            {{ m.teacher_name || 'Admin / Unassigned' }}
                        </option>
                        <option
                            v-for="t in teachers"
                            :key="t.id"
                            :value="t.id"
                        >
                            {{ t.name }}
                        </option>
                    </select>
                </div>
            </div>
        </div>
        <div
            v-else
            class="rounded-2xl py-16 text-center"
            style="
                background: var(--gl-surface);
                border: 1px solid var(--gl-border);
            "
        >
            <FolderOpen
                class="mx-auto mb-3 h-10 w-10"
                style="color: var(--gl-text-muted)"
                :stroke-width="1.5"
            />
            <p
                class="text-sm font-medium"
                style="color: var(--gl-text-secondary)"
            >
                {{
                    search
                        ? 'No materials match your search.'
                        : currentFolder
                          ? `No materials in ${currentFolder.name} yet.`
                          : 'No materials yet.'
                }}
            </p>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                {{
                    search
                        ? 'Try a different keyword.'
                        : 'Upload your first PPT or PDF to share with students.'
                }}
            </p>
            <button
                v-if="!search && currentFolder"
                type="button"
                @click="openUpload"
                class="mt-4 inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-bold text-white transition-all hover:scale-105"
                style="
                    background: linear-gradient(135deg, #ff6a45, #f94f2b);
                    box-shadow: 0 4px 14px rgba(249, 79, 43, 0.35);
                "
            >
                <Upload class="h-3.5 w-3.5" :stroke-width="2.5" />
                Upload to {{ currentFolder.name }}
            </button>
        </div>
        </template>
    </div>

    <!-- Upload Modal -->
    <Teleport to="body">
        <div
            v-if="showUpload"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.25); backdrop-filter: blur(2px)"
            @click.self="showUpload = false"
        >
            <div
                class="w-full max-w-md rounded-2xl p-6"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
                "
            >
                <div class="mb-4 flex items-center justify-between">
                    <h3
                        class="text-base font-semibold"
                        style="color: var(--gl-text-primary)"
                    >
                        Upload Material
                    </h3>
                    <button
                        @click="showUpload = false"
                        class="rounded-lg p-1.5 transition-colors hover:bg-[var(--gl-surface-2)]"
                        style="color: var(--gl-text-muted)"
                    >
                        <X class="h-5 w-5" :stroke-width="2" />
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Title</label
                        >
                        <input
                            v-model="form.title"
                            type="text"
                            placeholder="e.g. Photosynthesis Discussion"
                            class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        />
                        <p
                            v-if="form.errors.title"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ form.errors.title }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Subject</label
                        >
                        <select
                            v-model="form.subject_id"
                            class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                            style="background: var(--gl-surface-2); color: var(--gl-text-primary); border: 1px solid var(--gl-border);"
                        >
                            <option :value="null">Select subject</option>
                            <option v-for="s in subjects || []" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                        <p
                            v-if="form.errors.subject"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ form.errors.subject }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Description (optional)</label
                        >
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="What does this material cover?"
                            class="w-full resize-none rounded-xl px-3 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        ></textarea>
                        <p
                            v-if="form.errors.description"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ form.errors.description }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Visible to grade</label
                        >
                        <select
                            v-model="form.grade_level_id"
                            class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            <option value="">All grades</option>
                            <option
                                v-for="g in gradeLevels"
                                :key="g.id"
                                :value="g.id"
                            >
                                {{ g.name }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.grade_level_id"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ form.errors.grade_level_id }}
                        </p>
                    </div>
                    <div v-if="props.quarters?.length">
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Quarter (optional)</label
                        >
                        <select
                            v-model="form.quarter_id"
                            class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            <option :value="null">All / General (No quarter)</option>
                            <option
                                v-for="q in props.quarters"
                                :key="q.id"
                                :value="q.id"
                            >
                                {{ q.name }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.quarter_id"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ form.errors.quarter_id }}
                        </p>
                    </div>
                    <div v-if="props.schoolYears?.length">
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >School Year</label
                        >
                        <select
                            v-model="form.school_year_id"
                            class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            <option :value="null">Active / Current School Year</option>
                            <option
                                v-for="sy in props.schoolYears"
                                :key="sy.id"
                                :value="sy.id"
                            >
                                S.Y. {{ sy.name }}{{ sy.is_current ? ' (Current)' : '' }}
                            </option>
                        </select>
                    </div>
                    <div v-if="teachers?.length">
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Assign to teacher</label
                        >
                        <select
                            v-model="form.teacher_id"
                            class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            <option :value="null">Select a teacher (optional)</option>
                            <option
                                v-for="t in teachers"
                                :key="t.id"
                                :value="t.id"
                            >
                                {{ t.name }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.teacher_id"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ form.errors.teacher_id }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >File (PPT / PDF, max 20 MB)</label
                        >
                        <input
                            type="file"
                            accept=".pdf,.ppt,.pptx"
                            @change="
                                (e: any) =>
                                    (form.file = e.target.files?.[0] ?? null)
                            "
                            class="w-full cursor-pointer text-sm"
                            style="color: var(--gl-text-secondary)"
                        />
                        <p
                            v-if="form.errors.file"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ form.errors.file }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <button
                            @click="showUpload = false"
                            class="rounded-xl px-4 py-2.5 text-sm font-medium"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            Cancel
                        </button>
                        <button
                            @click="submitUpload"
                            :disabled="
                                form.processing ||
                                !form.title ||
                                !form.subject_id ||
                                !form.file
                            "
                            class="flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all disabled:cursor-not-allowed disabled:opacity-50"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                                box-shadow: 0 4px 16px rgba(59, 130, 246, 0.35);
                            "
                        >
                            <Upload class="h-4 w-4" :stroke-width="2" />
                            {{ form.processing ? 'Uploading…' : 'Upload' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Edit Material Modal -->
    <Teleport to="body">
        <div
            v-if="showEdit"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(4px)"
            @click.self="showEdit = false"
        >
            <div
                class="w-full max-w-lg rounded-2xl p-6 shadow-2xl"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <h3
                            class="text-base font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            Edit Teaching Material
                        </h3>
                        <p class="text-xs" style="color: var(--gl-text-muted)">
                            Update title, grade level, or replace the file.
                        </p>
                    </div>
                    <button
                        @click="showEdit = false"
                        class="rounded-lg p-1.5 transition-colors hover:bg-[var(--gl-surface-2)]"
                        style="color: var(--gl-text-muted)"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Title</label
                        >
                        <input
                            v-model="editForm.title"
                            type="text"
                            placeholder="e.g. Introduction to Physics"
                            class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        />
                        <p
                            v-if="editForm.errors.title"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ editForm.errors.title }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Subject</label
                        >
                        <select
                            v-model="editForm.subject_id"
                            class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                            style="background: var(--gl-surface-2); color: var(--gl-text-primary); border: 1px solid var(--gl-border);"
                        >
                            <option :value="null">Select subject</option>
                            <option v-if="editForm.subject_id === null && editForm.subject" :value="null">{{ editForm.subject }} (legacy)</option>
                            <option v-for="s in subjects || []" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                        <p
                            v-if="editForm.errors.subject"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ editForm.errors.subject }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Grade Level</label
                        >
                        <select
                            v-model="editForm.grade_level_id"
                            class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            <option value="">All Grade Levels</option>
                            <option
                                v-for="g in gradeLevels"
                                :key="g.id"
                                :value="g.id"
                            >
                                {{ g.name }}
                            </option>
                        </select>
                        <p
                            v-if="editForm.errors.grade_level_id"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ editForm.errors.grade_level_id }}
                        </p>
                    </div>

                    <div v-if="props.quarters?.length">
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Quarter</label
                        >
                        <select
                            v-model="editForm.quarter_id"
                            class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            <option :value="null">All / General (No quarter)</option>
                            <option
                                v-for="q in props.quarters"
                                :key="q.id"
                                :value="q.id"
                            >
                                {{ q.name }}
                            </option>
                        </select>
                        <p
                            v-if="editForm.errors.quarter_id"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ editForm.errors.quarter_id }}
                        </p>
                    </div>

                    <div v-if="props.schoolYears?.length">
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >School Year</label
                        >
                        <select
                            v-model="editForm.school_year_id"
                            class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            <option
                                v-for="sy in props.schoolYears"
                                :key="sy.id"
                                :value="sy.id"
                            >
                                S.Y. {{ sy.name }}{{ sy.is_current ? ' (Current)' : '' }}
                            </option>
                        </select>
                    </div>

                    <!-- Assign Teacher (superadmin only) -->
                    <div v-if="isSuperadmin && teachers?.length">
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Assign Teacher</label
                        >
                        <select
                            v-model="editForm.teacher_id"
                            class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            <option :value="null">Admin / Current</option>
                            <option
                                v-for="t in teachers"
                                :key="t.id"
                                :value="t.id"
                            >
                                {{ t.name }}
                            </option>
                        </select>
                        <p
                            v-if="editForm.errors.teacher_id"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ editForm.errors.teacher_id }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Description (optional)</label
                        >
                        <textarea
                            v-model="editForm.description"
                            rows="2"
                            placeholder="Brief description of the material..."
                            class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        ></textarea>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                        >
                            Replace File (optional - leave empty to keep current file)
                        </label>
                        <input
                            type="file"
                            accept=".pdf,.ppt,.pptx"
                            @change="
                                (e: any) =>
                                    (editForm.file = e.target.files?.[0] ?? null)
                            "
                            class="w-full cursor-pointer text-sm"
                            style="color: var(--gl-text-secondary)"
                        />
                        <p
                            v-if="editingMaterial?.file_name"
                            class="mt-1 text-xs"
                            style="color: var(--gl-text-muted)"
                        >
                            Current: {{ editingMaterial.file_name }}
                        </p>
                        <p
                            v-if="editForm.errors.file"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ editForm.errors.file }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <button
                            @click="showEdit = false"
                            type="button"
                            class="rounded-xl px-4 py-2.5 text-sm font-medium"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            Cancel
                        </button>
                        <button
                            @click="submitEdit"
                            :disabled="
                                editForm.processing ||
                                !editForm.title.trim() ||
                                !editForm.subject.trim()
                            "
                            class="flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all disabled:cursor-not-allowed disabled:opacity-50"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                                box-shadow: 0 4px 16px rgba(59, 130, 246, 0.35);
                            "
                        >
                            {{ editForm.processing ? 'Saving…' : 'Save Changes' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Generate Quiz Modal -->
    <Teleport to="body">
        <div
            v-if="showGenerate"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-8"
            style="background: rgba(0, 0, 0, 0.25); backdrop-filter: blur(2px)"
            @click.self="closeGenerate"
        >
            <div
                class="w-full max-w-2xl rounded-2xl p-6"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
                "
            >
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3
                            class="text-base font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            Generate Quiz with AI
                        </h3>
                        <p
                            class="mt-0.5 text-xs"
                            style="color: var(--gl-text-muted)"
                        >
                            {{ genMaterial?.title }}
                        </p>
                    </div>
                    <button
                        @click="closeGenerate"
                        class="rounded-lg p-1.5 transition-colors hover:bg-[var(--gl-surface-2)]"
                        style="color: var(--gl-text-muted)"
                    >
                        <X class="h-5 w-5" :stroke-width="2" />
                    </button>
                </div>

                <div v-if="genStep === 'options'" class="space-y-4">
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Create</label
                        >
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                            <button
                                v-for="a in genActivities"
                                :key="a.id"
                                type="button"
                                @click="genActivity = a.id"
                                class="flex flex-col items-center gap-1 rounded-xl border px-2 py-2.5 transition-colors"
                                :style="
                                    genActivity === a.id
                                        ? {
                                              borderColor: 'var(--gl-primary)',
                                              background:
                                                  'var(--gl-primary-glow)',
                                              color: 'var(--gl-primary)',
                                          }
                                        : {
                                              borderColor: 'var(--gl-border)',
                                              color: 'var(--gl-text-secondary)',
                                          }
                                "
                            >
                                <component
                                    :is="a.icon"
                                    class="h-4 w-4"
                                    :stroke-width="2"
                                />
                                <span class="text-[10px] font-semibold">{{
                                    a.label
                                }}</span>
                            </button>
                        </div>
                    </div>
                    <div v-if="genActivity !== 'game' && genActivity !== 'live_arena'" class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-medium"
                                style="color: var(--gl-text-secondary)"
                                >{{
                                    genActivity === 'practical'
                                        ? 'Criteria (1–25)'
                                        : 'Items (1–50)'
                                }}</label
                            >
                            <input
                                v-model.number="genCount"
                                type="number"
                                min="1"
                                max="50"
                                class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border: 1px solid var(--gl-border);
                                "
                            />
                        </div>
                        <div v-if="genActivity !== 'game'">
                            <label
                                class="mb-1.5 block text-xs font-medium"
                                style="color: var(--gl-text-secondary)"
                                >Grade level</label
                            >
                            <select
                                v-model="genGradeId"
                                class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border: 1px solid var(--gl-border);
                                "
                            >
                                <option :value="null" disabled>
                                    Select grade
                                </option>
                                <option
                                    v-for="g in gradeLevels"
                                    :key="g.id"
                                    :value="g.id"
                                >
                                    {{ g.name }}
                                </option>
                            </select>
                        </div>
                        <div v-else>
                            <label
                                class="mb-1.5 block text-xs font-medium"
                                style="color: var(--gl-text-secondary)"
                                >Subject</label
                            >
                            <input
                                v-model="genSubject"
                                type="text"
                                placeholder="e.g. Science"
                                class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border: 1px solid var(--gl-border);
                                "
                            />
                        </div>
                    </div>
                    <div
                        v-if="genActivity === 'live_arena'"
                        class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-xs space-y-2"
                    >
                        <div class="flex items-center gap-2 font-bold text-emerald-600 dark:text-emerald-400">
                            <Zap class="h-4 w-4" />
                            <span>Live Multiplayer Match Arena</span>
                        </div>
                        <p class="text-[var(--gl-text-secondary)]">
                            Generate live speed quiz cards directly from <strong>{{ genMaterial?.title }}</strong>. Students compete in real-time with fastest-fingers bonus points.
                        </p>
                    </div>

                    <div
                        v-else-if="genActivity === 'game'"
                        class="grid grid-cols-2 gap-4"
                    >
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-medium"
                                style="color: var(--gl-text-secondary)"
                                >Game type</label
                            >
                            <select
                                v-model="genGameType"
                                class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border: 1px solid var(--gl-border);
                                "
                            >
                                <option value="flashcard">Flashcards</option>
                                <option value="quiz">Quiz game</option>
                                <option value="truefalse">True / False</option>
                                <option value="fillblank">
                                    Fill in the blank
                                </option>
                                <option value="fourpics">4 Pics 1 Word</option>
                                <option value="crossword">Crossword</option>
                                <option value="wordsearch">Word Search</option>
                                <option value="codepuzzle">Code Puzzle</option>
                                <option value="wordjumble">Word Jumble</option>
                                <option value="memorymatch">Memory Match</option>
                                <option value="hangman">Hangman</option>
                                <option value="speedquiz">Speed Quiz</option>
                                <option value="dragdrop">Drag & Drop</option>
                                <option value="ordering">Ordering / Sequence</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-medium"
                                style="color: var(--gl-text-secondary)"
                                >Grade (optional)</label
                            >
                            <select
                                v-model="genGradeText"
                                class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border: 1px solid var(--gl-border);
                                "
                            >
                                <option value="">All grades</option>
                                <option
                                    v-for="g in gradeLevels"
                                    :key="g.id"
                                    :value="g.name"
                                >
                                    {{ g.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div v-if="genActivity === 'quiz'">
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Sections</label
                        >
                        <div
                            v-if="genSections.length"
                            class="flex flex-wrap gap-2"
                        >
                            <button
                                v-for="s in genSections"
                                :key="s.id"
                                type="button"
                                @click="toggleGenSection(s.id)"
                                class="rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors"
                                :style="
                                    genSectionIds.includes(s.id)
                                        ? {
                                              borderColor: 'var(--gl-primary)',
                                              background:
                                                  'var(--gl-primary-glow)',
                                              color: 'var(--gl-primary)',
                                          }
                                        : {
                                              borderColor: 'var(--gl-border)',
                                              color: 'var(--gl-text-secondary)',
                                          }
                                "
                            >
                                {{ s.name }}
                            </button>
                        </div>
                        <p
                            v-else
                            class="text-xs"
                            style="color: var(--gl-text-muted)"
                        >
                            Select a grade level first.
                        </p>
                    </div>
                    <div
                        v-if="genActivity === 'quiz' || genActivity === 'exam'"
                    >
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Question types</label
                        >
                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="toggleGenType('multiple_choice')"
                                class="flex-1 rounded-lg border px-3 py-2 text-xs font-medium transition-colors"
                                :style="
                                    genTypes.includes('multiple_choice')
                                        ? {
                                              borderColor: 'var(--gl-primary)',
                                              background:
                                                  'var(--gl-primary-glow)',
                                              color: 'var(--gl-primary)',
                                          }
                                        : {
                                              borderColor: 'var(--gl-border)',
                                              color: 'var(--gl-text-secondary)',
                                          }
                                "
                            >
                                Multiple choice
                            </button>
                            <button
                                type="button"
                                @click="toggleGenType('true_false')"
                                class="flex-1 rounded-lg border px-3 py-2 text-xs font-medium transition-colors"
                                :style="
                                    genTypes.includes('true_false')
                                        ? {
                                              borderColor: 'var(--gl-primary)',
                                              background:
                                                  'var(--gl-primary-glow)',
                                              color: 'var(--gl-primary)',
                                          }
                                        : {
                                              borderColor: 'var(--gl-border)',
                                              color: 'var(--gl-text-secondary)',
                                          }
                                "
                            >
                                True / False
                            </button>
                            <button
                                type="button"
                                @click="toggleGenType('identification')"
                                class="flex-1 rounded-lg border px-3 py-2 text-xs font-medium transition-colors"
                                :style="
                                    genTypes.includes('identification')
                                        ? {
                                              borderColor: 'var(--gl-primary)',
                                              background:
                                                  'var(--gl-primary-glow)',
                                              color: 'var(--gl-primary)',
                                          }
                                        : {
                                              borderColor: 'var(--gl-border)',
                                              color: 'var(--gl-text-secondary)',
                                          }
                                "
                            >
                                Identification
                            </button>
                        </div>
                    </div>
                    <div v-if="genActivity === 'seatwork'">
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Question types</label
                        >
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="t in seatworkTypes"
                                :key="t.id"
                                type="button"
                                @click="toggleGenType(t.id)"
                                class="rounded-lg border px-3 py-2 text-xs font-medium transition-colors"
                                :style="
                                    genTypes.includes(t.id)
                                        ? {
                                              borderColor: 'var(--gl-primary)',
                                              background:
                                                  'var(--gl-primary-glow)',
                                              color: 'var(--gl-primary)',
                                          }
                                        : {
                                              borderColor: 'var(--gl-border)',
                                              color: 'var(--gl-text-secondary)',
                                          }
                                "
                            >
                                {{ t.label }}
                            </button>
                        </div>
                    </div>
                    <p
                        v-if="genError"
                        class="rounded-xl px-4 py-2.5 text-xs"
                        style="
                            background: var(--gl-danger-bg);
                            color: var(--gl-danger);
                        "
                    >
                        {{ genError }}
                    </p>
                    <button
                        @click="runGenerate"
                        :disabled="genLoading || !canGenerate"
                        class="flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all disabled:cursor-not-allowed disabled:opacity-50"
                        :style="
                            genActivity === 'live_arena'
                                ? 'background: linear-gradient(135deg, #059669, #10b981); box-shadow: 0 4px 16px rgba(16, 185, 129, 0.4);'
                                : {
                                      background:
                                          'linear-gradient(135deg, var(--gl-secondary), var(--gl-primary))',
                                      boxShadow: '0 4px 16px var(--gl-primary-glow)',
                                  }
                        "
                    >
                        <Zap v-if="genActivity === 'live_arena'" class="h-4 w-4" :stroke-width="2" />
                        <Sparkles v-else class="h-4 w-4" :stroke-width="2" />
                        {{
                            genActivity === 'live_arena'
                                ? 'Open in Live Match Arena'
                                : genLoading
                                ? 'Reading material & generating…'
                                : 'Generate preview'
                        }}
                    </button>
                    <p
                        class="text-center text-[11px]"
                        style="color: var(--gl-text-muted)"
                    >
                        AI draft — always review before saving.
                    </p>

                    <div class="relative flex py-1 items-center">
                        <div class="flex-grow border-t border-[var(--gl-border)]"></div>
                        <span class="flex-shrink mx-2 text-[10px] text-[var(--gl-text-muted)] uppercase tracking-wider font-semibold">or studio</span>
                        <div class="flex-grow border-t border-[var(--gl-border)]"></div>
                    </div>
                    <a
                        :href="`/teacher/ai-creator?material_id=${genMaterial?.id}&activity=${genActivity}`"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] px-4 py-2.5 text-xs font-semibold text-[var(--gl-text-primary)] hover:border-[var(--gl-primary)] hover:text-[var(--gl-primary)] transition-all"
                    >
                        <Sparkles class="h-3.5 w-3.5 text-[var(--gl-primary)]" />
                        Open in AI Activity Creator Studio
                    </a>
                </div>

                <div v-else class="space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-xs" style="color: var(--gl-text-muted)">
                            {{ previewCountLabel }}
                            <span
                                v-if="
                                    genProvider && genProvider !== 'heuristic'
                                "
                                >· via {{ genProvider }}</span
                            >
                            <span v-else>· offline draft</span>
                        </p>
                        <button
                            @click="genStep = 'options'"
                            class="text-xs font-medium transition-colors hover:opacity-80"
                            style="color: var(--gl-primary)"
                        >
                            Regenerate
                        </button>
                    </div>
                    <div class="max-h-80 space-y-3 overflow-y-auto pr-1">
                        <template v-if="genActivity === 'practical'">
                            <div
                                v-for="(c, ci) in genItems"
                                :key="ci"
                                class="rounded-xl p-4"
                                style="
                                    background: var(--gl-surface-2);
                                    border: 1px solid var(--gl-border);
                                "
                            >
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <p
                                        class="text-sm font-medium"
                                        style="color: var(--gl-text-primary)"
                                    >
                                        {{ ci + 1 }}. {{ c.criterion_name }}
                                    </p>
                                    <span
                                        class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                        style="
                                            background: rgba(
                                                251,
                                                191,
                                                36,
                                                0.12
                                            );
                                            color: var(--gl-accent);
                                        "
                                        >{{ c.max_points }} pts</span
                                    >
                                </div>
                                <p
                                    v-if="c.description"
                                    class="mt-1 text-xs"
                                    style="color: var(--gl-text-secondary)"
                                >
                                    {{ c.description }}
                                </p>
                            </div>
                        </template>
                        <template v-else-if="genActivity === 'game'">
                            <div
                                v-for="(c, ci) in genItems"
                                :key="ci"
                                class="rounded-xl p-4"
                                style="
                                    background: var(--gl-surface-2);
                                    border: 1px solid var(--gl-border);
                                "
                            >
                                <p
                                    class="text-sm font-medium"
                                    style="color: var(--gl-text-primary)"
                                >
                                    {{ ci + 1 }}. {{ c.question }}
                                </p>
                                <p
                                    class="mt-1 text-xs"
                                    style="color: var(--gl-success)"
                                >
                                    Answer: {{ c.answer }}
                                </p>
                                <div
                                    v-if="c.options"
                                    class="mt-2 flex flex-wrap gap-1.5"
                                >
                                    <span
                                        v-for="(o, oi) in c.options"
                                        :key="oi"
                                        class="rounded-md px-2 py-0.5 text-[11px]"
                                        style="
                                            background: var(--gl-surface);
                                            border: 1px solid var(--gl-border);
                                            color: var(--gl-text-secondary);
                                        "
                                        >{{ o }}</span
                                    >
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <div
                                v-for="(q, qi) in genQuestions"
                                :key="qi"
                                class="rounded-xl p-4"
                                style="
                                    background: var(--gl-surface-2);
                                    border: 1px solid var(--gl-border);
                                "
                            >
                                <div
                                    class="mb-1 flex items-center justify-between gap-2"
                                >
                                    <p
                                        class="text-sm font-medium"
                                        style="color: var(--gl-text-primary)"
                                    >
                                        {{ qi + 1 }}. {{ q.question_text }}
                                    </p>
                                    <span
                                        v-if="
                                            genActivity === 'seatwork' ||
                                            genActivity === 'exam' ||
                                            genActivity === 'quiz'
                                        "
                                        class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium"
                                        style="
                                            background: var(--gl-surface);
                                            border: 1px solid var(--gl-border);
                                            color: var(--gl-text-muted);
                                        "
                                        >{{ q.type }}</span
                                    >
                                </div>
                                <div v-if="q.options" class="mt-2 space-y-1.5">
                                    <div
                                        v-for="(o, oi) in q.options"
                                        :key="oi"
                                        class="flex items-center gap-2 text-xs"
                                        :style="{
                                            color: o.is_correct
                                                ? 'var(--gl-success)'
                                                : 'var(--gl-text-secondary)',
                                        }"
                                    >
                                        <Check
                                            v-if="o.is_correct"
                                            class="h-3.5 w-3.5 shrink-0"
                                            :stroke-width="2.5"
                                        />
                                        <span v-else class="ml-5"></span>
                                        {{ o.option_text }}
                                    </div>
                                </div>
                                <div
                                    v-if="q.enum_items?.length"
                                    class="mt-2 space-y-1"
                                >
                                    <div
                                        v-for="(e, ei) in q.enum_items"
                                        :key="ei"
                                        class="text-xs"
                                        style="color: var(--gl-success)"
                                    >
                                        {{ ei + 1 }}. {{ e }}
                                    </div>
                                </div>
                                <div
                                    v-if="q.matching_pairs?.length"
                                    class="mt-2 space-y-1"
                                >
                                    <div
                                        v-for="(p, pi) in q.matching_pairs"
                                        :key="pi"
                                        class="text-xs"
                                        style="color: var(--gl-text-secondary)"
                                    >
                                        {{ p.left_text }} → {{ p.right_text }}
                                    </div>
                                </div>
                                <p
                                    v-if="q.correct_answer"
                                    class="mt-2 text-xs"
                                    style="color: var(--gl-success)"
                                >
                                    Answer: {{ q.correct_answer }}
                                </p>
                            </div>
                        </template>
                    </div>
                    <p
                        v-if="genError"
                        class="rounded-xl px-4 py-2.5 text-xs"
                        style="
                            background: var(--gl-danger-bg);
                            color: var(--gl-danger);
                        "
                    >
                        {{ genError }}
                    </p>
                    <div class="flex items-center gap-2 pt-1">
                        <button
                            @click="closeGenerate"
                            class="rounded-xl px-4 py-2.5 text-sm font-medium"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            Cancel
                        </button>
                        <button
                            @click="saveDraft"
                            :disabled="genSaving"
                            class="flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all disabled:opacity-60"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                                box-shadow: 0 4px 16px var(--gl-primary-glow);
                            "
                        >
                            <Eye class="h-4 w-4" :stroke-width="2" />
                            {{
                                genSaving
                                    ? 'Saving…'
                                    : 'Save as draft ' + genActivityLabel
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
