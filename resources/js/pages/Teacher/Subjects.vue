<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    BookOpen,
    Check,
    FileText,
    Gamepad2,
    Pencil,
    Plus,
    Power,
    Search,
    Trash2,
    Users,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';

type Teacher = {
    id: number;
    name: string;
    email: string;
};

type Subject = {
    id: number;
    name: string;
    is_active: boolean;
    teachers: Teacher[];
    games_count: number;
    materials_count: number;
};

const props = defineProps<{
    subjects: Subject[];
    teachers: Teacher[];
}>();

const page = usePage();
const flash = computed(() => (page.props as any).flash || {});
const localSubjects = ref<Subject[]>([...(props.subjects || [])]);

watch(
    () => props.subjects,
    (subjects) => {
        localSubjects.value = [...(subjects || [])];
    },
    { deep: true },
);

const search = ref('');
const statusFilter = ref<'all' | 'active' | 'inactive'>('all');
const showModal = ref(false);
const editingSubject = ref<Subject | null>(null);

const form = useForm({
    name: '',
    is_active: true,
    teacher_ids: [] as number[],
});

const filteredSubjects = computed(() => {
    const query = search.value.trim().toLowerCase();

    return localSubjects.value.filter((subject) => {
        if (statusFilter.value === 'active' && !subject.is_active) {
            return false;
        }

        if (statusFilter.value === 'inactive' && subject.is_active) {
            return false;
        }

        if (!query) {
            return true;
        }

        return (
            subject.name.toLowerCase().includes(query) ||
            subject.teachers.some((teacher) =>
                teacher.name.toLowerCase().includes(query),
            )
        );
    });
});

const activeCount = computed(
    () => localSubjects.value.filter((subject) => subject.is_active).length,
);
const inactiveCount = computed(
    () => localSubjects.value.filter((subject) => !subject.is_active).length,
);
const totalMaterials = computed(() =>
    localSubjects.value.reduce(
        (total, subject) => total + Number(subject.materials_count || 0),
        0,
    ),
);
const totalGames = computed(() =>
    localSubjects.value.reduce(
        (total, subject) => total + Number(subject.games_count || 0),
        0,
    ),
);

const modalTitle = computed(() =>
    editingSubject.value ? 'Edit subject' : 'Create subject',
);
const submitLabel = computed(() =>
    editingSubject.value ? 'Save changes' : 'Create subject',
);

function openCreate() {
    editingSubject.value = null;
    form.reset();
    form.clearErrors();
    form.name = '';
    form.is_active = true;
    form.teacher_ids = [];
    showModal.value = true;
}

function openEdit(subject: Subject) {
    editingSubject.value = subject;
    form.clearErrors();
    form.name = subject.name;
    form.is_active = Boolean(subject.is_active);
    form.teacher_ids = (subject.teachers || []).map((teacher) =>
        Number(teacher.id),
    );
    showModal.value = true;
}

function closeModal() {
    if (form.processing) {
        return;
    }

    showModal.value = false;
    editingSubject.value = null;
    form.clearErrors();
}

function submit() {
    if (form.processing) {
        return;
    }

    if (editingSubject.value) {
        form.put(`/teacher/subjects/${editingSubject.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                editingSubject.value = null;
                form.reset();
            },
        });

        return;
    }

    form.post('/teacher/subjects', {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    });
}

function remove(subject: Subject) {
    if (
        !window.confirm(
            `Delete "${subject.name}"? Existing materials and games will be kept.`,
        )
    ) {
        return;
    }

    router.delete(`/teacher/subjects/${subject.id}`, {
        preserveScroll: true,
    });
}

function teacherNames(subject: Subject) {
    return (
        subject.teachers?.map((teacher) => teacher.name).join(', ') ||
        'No teachers assigned'
    );
}

function teacherInitials(name: string) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}
</script>

<template>
    <Head title="Subjects" />

    <div
        v-if="flash?.success"
        class="gl-fade-in mb-6 rounded-xl px-4 py-3 text-sm"
        style="
            background: var(--gl-success-bg);
            color: var(--gl-success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        "
    >
        {{ flash.success }}
    </div>

    <div
        v-if="flash?.error"
        class="gl-fade-in mb-6 flex items-start gap-2 rounded-xl px-4 py-3 text-sm"
        style="
            background: rgba(239, 68, 68, 0.08);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        "
    >
        <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
        <span>{{ flash.error }}</span>
    </div>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p
                class="text-xs font-semibold tracking-widest uppercase"
                style="color: var(--gl-text-muted)"
            >
                Manage
            </p>
            <h1
                class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                Subjects
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                Organize teacher assignments and learning content
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
                    type="search"
                    placeholder="Search subjects"
                    aria-label="Search subjects"
                    class="w-48 rounded-full border py-2.5 pr-4 pl-9 text-sm transition-all outline-none focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                    style="
                        background: var(--gl-surface);
                        color: var(--gl-text-primary);
                        border-color: var(--gl-border);
                    "
                />
            </div>
            <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2.5 text-xs font-bold text-white shadow-md transition-all hover:scale-105 disabled:opacity-50"
                style="
                    background: linear-gradient(
                        135deg,
                        var(--gl-primary),
                        var(--gl-secondary)
                    );
                    box-shadow: 0 4px 14px rgba(59, 130, 246, 0.3);
                "
                @click="openCreate"
            >
                <Plus class="h-4 w-4" :stroke-width="2.5" />
                <span>New subject</span>
            </button>
        </div>
    </div>

    <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div
            class="rounded-2xl p-4"
            style="
                background: rgba(59, 130, 246, 0.08);
                border: 1px solid rgba(59, 130, 246, 0.18);
            "
        >
            <div
                class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                style="
                    background: rgba(59, 130, 246, 0.15);
                    color: var(--gl-primary);
                "
            >
                <BookOpen class="h-[18px] w-[18px]" />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ localSubjects.length }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Total subjects
            </p>
        </div>
        <div
            class="rounded-2xl p-4"
            style="
                background: rgba(16, 185, 129, 0.08);
                border: 1px solid rgba(16, 185, 129, 0.18);
            "
        >
            <div
                class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                style="
                    background: rgba(16, 185, 129, 0.15);
                    color: var(--gl-success);
                "
            >
                <Power class="h-[18px] w-[18px]" />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ activeCount }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Active
            </p>
        </div>
        <div
            class="rounded-2xl p-4"
            style="
                background: rgba(245, 158, 11, 0.08);
                border: 1px solid rgba(245, 158, 11, 0.18);
            "
        >
            <div
                class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                style="background: rgba(245, 158, 11, 0.15); color: #d97706"
            >
                <Users class="h-[18px] w-[18px]" />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ inactiveCount }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Inactive
            </p>
        </div>
        <div
            class="rounded-2xl p-4"
            style="
                background: rgba(124, 58, 237, 0.08);
                border: 1px solid rgba(124, 58, 237, 0.18);
            "
        >
            <div
                class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                style="
                    background: rgba(124, 58, 237, 0.15);
                    color: var(--gl-secondary);
                "
            >
                <FileText class="h-[18px] w-[18px]" />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ totalMaterials + totalGames }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Content items
            </p>
        </div>
    </div>

    <div class="mb-4 flex items-center gap-1.5 overflow-x-auto pb-1">
        <button
            v-for="filter in [
                { value: 'all', label: 'All', count: localSubjects.length },
                { value: 'active', label: 'Active', count: activeCount },
                { value: 'inactive', label: 'Inactive', count: inactiveCount },
            ]"
            :key="filter.value"
            type="button"
            class="shrink-0 rounded-full px-3.5 py-2 text-xs font-bold transition-all"
            :style="
                statusFilter === filter.value
                    ? { background: '#1F2937', color: '#fff' }
                    : {
                          background: 'var(--gl-surface)',
                          border: '1px solid var(--gl-border)',
                          color: 'var(--gl-text-secondary)',
                      }
            "
            @click="
                statusFilter = filter.value as 'all' | 'active' | 'inactive'
            "
        >
            {{ filter.label }}
            <span
                class="ml-1 rounded-full px-1.5 py-0.5 text-[10px]"
                :style="
                    statusFilter === filter.value
                        ? { background: 'rgba(255,255,255,0.2)' }
                        : { background: 'var(--gl-surface-2)' }
                "
                >{{ filter.count }}</span
            >
        </button>
    </div>

    <div
        v-if="!filteredSubjects.length"
        class="gl-glow-card flex flex-col items-center justify-center px-8 py-16 text-center"
    >
        <div
            class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl"
            style="background: var(--gl-surface-2)"
        >
            <BookOpen
                class="h-6 w-6"
                style="color: var(--gl-text-muted)"
                :stroke-width="1.75"
            />
        </div>
        <p class="text-sm font-medium" style="color: var(--gl-text-secondary)">
            {{ localSubjects.length ? 'No subjects found' : 'No subjects yet' }}
        </p>
        <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
            {{
                localSubjects.length
                    ? 'Try a different search or filter.'
                    : 'Create a subject to start organizing content.'
            }}
        </p>
        <button
            v-if="!localSubjects.length"
            type="button"
            class="mt-4 inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02]"
            style="
                background: linear-gradient(
                    135deg,
                    var(--gl-primary),
                    var(--gl-secondary)
                );
            "
            @click="openCreate"
        >
            <Plus class="h-4 w-4" /> New subject
        </button>
    </div>

    <div v-else class="gl-glow-card overflow-hidden">
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full text-left">
                <thead>
                    <tr
                        class="border-b text-[10px] font-bold tracking-wider uppercase"
                        style="
                            border-color: var(--gl-border);
                            color: var(--gl-text-muted);
                        "
                    >
                        <th class="px-5 py-4">Subject</th>
                        <th class="px-5 py-4">Assigned teachers</th>
                        <th class="px-5 py-4">Content</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="subject in filteredSubjects"
                        :key="subject.id"
                        class="border-b transition-colors hover:bg-[var(--gl-surface-2)]"
                        style="border-color: var(--gl-border)"
                    >
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                    style="
                                        background: rgba(59, 130, 246, 0.12);
                                        color: var(--gl-primary);
                                    "
                                >
                                    <BookOpen class="h-5 w-5" />
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-bold"
                                        style="color: var(--gl-text-primary)"
                                    >
                                        {{ subject.name }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-[11px]"
                                        style="color: var(--gl-text-muted)"
                                    >
                                        Subject #{{ subject.id }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="max-w-xs px-5 py-4">
                            <div
                                v-if="subject.teachers?.length"
                                class="flex items-center gap-2"
                            >
                                <div class="flex -space-x-2">
                                    <span
                                        v-for="teacher in subject.teachers.slice(
                                            0,
                                            3,
                                        )"
                                        :key="teacher.id"
                                        class="flex h-7 w-7 items-center justify-center rounded-full border-2 text-[9px] font-extrabold"
                                        style="
                                            background: var(--gl-surface-2);
                                            border-color: var(--gl-surface);
                                            color: var(--gl-primary);
                                        "
                                        :title="teacher.name"
                                        >{{
                                            teacherInitials(teacher.name)
                                        }}</span
                                    >
                                </div>
                                <span
                                    class="truncate text-xs"
                                    style="color: var(--gl-text-secondary)"
                                >
                                    {{ teacherNames(subject) }}
                                </span>
                            </div>
                            <span
                                v-else
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                                >No teachers assigned</span
                            >
                        </td>
                        <td class="px-5 py-4">
                            <div
                                class="flex items-center gap-3 text-xs"
                                style="color: var(--gl-text-secondary)"
                            >
                                <span
                                    class="inline-flex items-center gap-1.5"
                                    title="Materials"
                                >
                                    <FileText
                                        class="h-3.5 w-3.5"
                                        style="color: var(--gl-primary)"
                                    />
                                    {{ subject.materials_count || 0 }}
                                </span>
                                <span
                                    class="inline-flex items-center gap-1.5"
                                    title="Games"
                                >
                                    <Gamepad2
                                        class="h-3.5 w-3.5"
                                        style="color: var(--gl-secondary)"
                                    />
                                    {{ subject.games_count || 0 }}
                                </span>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold"
                                :style="
                                    subject.is_active
                                        ? {
                                              background:
                                                  'var(--gl-success-bg)',
                                              color: 'var(--gl-success)',
                                          }
                                        : {
                                              background:
                                                  'rgba(107,114,128,0.12)',
                                              color: 'var(--gl-text-muted)',
                                          }
                                "
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full"
                                    :class="
                                        subject.is_active
                                            ? 'bg-emerald-500'
                                            : 'bg-gray-400'
                                    "
                                />
                                {{ subject.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-1.5">
                                <button
                                    type="button"
                                    class="rounded-lg p-2 transition-colors hover:bg-[var(--gl-surface-2)]"
                                    style="color: var(--gl-primary)"
                                    :aria-label="`Edit ${subject.name}`"
                                    title="Edit subject"
                                    @click="openEdit(subject)"
                                >
                                    <Pencil class="h-4 w-4" />
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-red-500 transition-colors hover:bg-red-500/10"
                                    :aria-label="`Delete ${subject.name}`"
                                    title="Delete subject"
                                    @click="remove(subject)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="divide-y md:hidden" style="divide-color: var(--gl-border)">
            <div
                v-for="subject in filteredSubjects"
                :key="subject.id"
                class="p-4"
                style="border-color: var(--gl-border)"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                            style="
                                background: rgba(59, 130, 246, 0.12);
                                color: var(--gl-primary);
                            "
                        >
                            <BookOpen class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-bold"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ subject.name }}
                            </p>
                            <span
                                class="mt-1 inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                :style="
                                    subject.is_active
                                        ? {
                                              background:
                                                  'var(--gl-success-bg)',
                                              color: 'var(--gl-success)',
                                          }
                                        : {
                                              background:
                                                  'rgba(107,114,128,0.12)',
                                              color: 'var(--gl-text-muted)',
                                          }
                                "
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full"
                                    :class="
                                        subject.is_active
                                            ? 'bg-emerald-500'
                                            : 'bg-gray-400'
                                    "
                                />
                                {{ subject.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <button
                            type="button"
                            class="rounded-lg p-2"
                            style="color: var(--gl-primary)"
                            @click="openEdit(subject)"
                        >
                            <Pencil class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            class="rounded-lg p-2 text-red-500"
                            @click="remove(subject)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>
                <div
                    class="mt-4 grid grid-cols-2 gap-3 border-t pt-3"
                    style="border-color: var(--gl-border)"
                >
                    <div>
                        <p
                            class="text-[10px] font-bold tracking-wider uppercase"
                            style="color: var(--gl-text-muted)"
                        >
                            Teachers
                        </p>
                        <p
                            class="mt-1 truncate text-xs"
                            style="color: var(--gl-text-secondary)"
                        >
                            {{ teacherNames(subject) }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-[10px] font-bold tracking-wider uppercase"
                            style="color: var(--gl-text-muted)"
                        >
                            Content
                        </p>
                        <div
                            class="mt-1 flex items-center gap-3 text-xs"
                            style="color: var(--gl-text-secondary)"
                        >
                            <span class="inline-flex items-center gap-1"
                                ><FileText
                                    class="h-3.5 w-3.5"
                                    style="color: var(--gl-primary)"
                                />{{ subject.materials_count || 0 }}</span
                            >
                            <span class="inline-flex items-center gap-1"
                                ><Gamepad2
                                    class="h-3.5 w-3.5"
                                    style="color: var(--gl-secondary)"
                                />{{ subject.games_count || 0 }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        @click.self="closeModal"
    >
        <div
            class="gl-glow-card max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-3xl p-6"
            style="
                background: var(--gl-surface);
                border: 1px solid var(--gl-border);
            "
            role="dialog"
            aria-modal="true"
            :aria-label="modalTitle"
        >
            <div class="mb-5 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl"
                        style="
                            background: rgba(59, 130, 246, 0.15);
                            color: var(--gl-primary);
                        "
                    >
                        <BookOpen class="h-4 w-4" />
                    </div>
                    <div>
                        <h2
                            class="text-base font-bold"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ modalTitle }}
                        </h2>
                        <p
                            class="text-[11px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Set the subject details and teacher access
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="rounded-xl p-1.5 transition-colors hover:bg-[var(--gl-surface-2)] disabled:opacity-50"
                    style="color: var(--gl-text-muted)"
                    :disabled="form.processing"
                    aria-label="Close modal"
                    @click="closeModal"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <form class="space-y-5" @submit.prevent="submit">
                <div>
                    <label
                        for="subject-name"
                        class="mb-1 block text-xs font-semibold"
                        style="color: var(--gl-text-primary)"
                        >Subject name *</label
                    >
                    <input
                        id="subject-name"
                        v-model="form.name"
                        type="text"
                        maxlength="100"
                        required
                        autofocus
                        placeholder="e.g. Mathematics"
                        class="w-full rounded-xl px-3.5 py-2.5 text-sm transition-all outline-none focus:border-[var(--gl-primary)]"
                        style="
                            background: var(--gl-surface-2);
                            border: 1px solid var(--gl-border);
                            color: var(--gl-text-primary);
                        "
                    />
                    <p
                        v-if="form.errors.name"
                        class="mt-1 text-[11px] text-red-500"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <div>
                            <p
                                class="text-xs font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                Assigned teachers
                            </p>
                            <p
                                class="mt-0.5 text-[11px]"
                                style="color: var(--gl-text-muted)"
                            >
                                Select every teacher who can use this subject.
                            </p>
                        </div>
                        <span
                            class="rounded-full px-2 py-1 text-[10px] font-bold"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-muted);
                            "
                        >
                            {{ form.teacher_ids.length }} selected
                        </span>
                    </div>
                    <div
                        v-if="teachers.length"
                        class="max-h-48 space-y-1 overflow-y-auto rounded-2xl border p-2"
                        style="
                            background: var(--gl-surface-2);
                            border-color: var(--gl-border);
                        "
                    >
                        <label
                            v-for="teacher in teachers"
                            :key="teacher.id"
                            class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 transition-colors hover:bg-[var(--gl-surface)]"
                        >
                            <input
                                v-model="form.teacher_ids"
                                type="checkbox"
                                :value="teacher.id"
                                class="h-4 w-4 rounded accent-[var(--gl-primary)]"
                            />
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[10px] font-extrabold"
                                style="
                                    background: rgba(59, 130, 246, 0.12);
                                    color: var(--gl-primary);
                                "
                                >{{ teacherInitials(teacher.name) }}</span
                            >
                            <span class="min-w-0">
                                <span
                                    class="block truncate text-xs font-semibold"
                                    style="color: var(--gl-text-primary)"
                                    >{{ teacher.name }}</span
                                >
                                <span
                                    class="block truncate text-[10px]"
                                    style="color: var(--gl-text-muted)"
                                    >{{ teacher.email }}</span
                                >
                            </span>
                            <Check
                                v-if="form.teacher_ids.includes(teacher.id)"
                                class="ml-auto h-4 w-4 shrink-0"
                                style="color: var(--gl-success)"
                            />
                        </label>
                    </div>
                    <p
                        v-else
                        class="rounded-xl px-3 py-3 text-xs"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-muted);
                        "
                    >
                        No teachers available.
                    </p>
                    <p
                        v-if="form.errors.teacher_ids"
                        class="mt-1 text-[11px] text-red-500"
                    >
                        {{ form.errors.teacher_ids }}
                    </p>
                </div>

                <div
                    class="flex items-center justify-between rounded-2xl border p-3.5"
                    style="
                        background: var(--gl-surface-2);
                        border-color: var(--gl-border);
                    "
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg"
                            style="
                                background: var(--gl-success-bg);
                                color: var(--gl-success);
                            "
                        >
                            <Power class="h-4 w-4" />
                        </div>
                        <div>
                            <p
                                class="text-xs font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                Active subject
                            </p>
                            <p
                                class="mt-0.5 text-[11px]"
                                style="color: var(--gl-text-muted)"
                            >
                                {{
                                    editingSubject
                                        ? 'Inactive subjects can be reactivated later.'
                                        : 'New subjects are active by default.'
                                }}
                            </p>
                        </div>
                    </div>
                    <label
                        class="relative inline-flex cursor-pointer items-center"
                        :class="
                            !editingSubject
                                ? 'cursor-not-allowed opacity-60'
                                : ''
                        "
                    >
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="peer sr-only"
                            :disabled="!editingSubject"
                        />
                        <span
                            class="h-6 w-11 rounded-full bg-gray-300 transition-colors peer-checked:bg-emerald-500 peer-focus:ring-2 peer-focus:ring-emerald-500/30 peer-focus:outline-none dark:bg-gray-600"
                        ></span>
                        <span
                            class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"
                        ></span>
                    </label>
                </div>
                <p
                    v-if="form.errors.is_active"
                    class="-mt-3 text-[11px] text-red-500"
                >
                    {{ form.errors.is_active }}
                </p>

                <div
                    v-if="Object.keys(form.errors).length"
                    class="flex items-start gap-2 rounded-xl px-3 py-2.5 text-xs"
                    style="background: rgba(239, 68, 68, 0.08); color: #ef4444"
                >
                    <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                    <span
                        >Please review the highlighted fields and try
                        again.</span
                    >
                </div>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2.5 text-xs font-semibold transition-all disabled:opacity-50"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-secondary);
                        "
                        :disabled="form.processing"
                        @click="closeModal"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold text-white transition-all hover:scale-105 disabled:cursor-not-allowed disabled:opacity-50"
                        style="
                            background: linear-gradient(
                                135deg,
                                var(--gl-primary),
                                var(--gl-secondary)
                            );
                        "
                        :disabled="form.processing"
                    >
                        <span
                            v-if="form.processing"
                            class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"
                        />
                        <Check v-else class="h-3.5 w-3.5" />
                        {{ form.processing ? 'Saving...' : submitLabel }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
