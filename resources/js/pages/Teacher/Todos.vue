<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    CheckSquare,
    Plus,
    Trash2,
    Calendar,
    Users,
    Search,
    X,
    ExternalLink,
    Clock,
    AlertCircle,
    CheckCircle2,
    BookOpen,
    Sparkles,
} from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import AiCommunicationPrompt from '@/components/AiCommunicationPrompt.vue';
import type { AiCommunicationDraft } from '@/types/ai-communication';

const props = defineProps<{
    todos: {
        data: any[];
        links?: any[];
        current_page?: number;
        last_page?: number;
    };
    gradingTasks?: any[];
    gradeLevels: { id: number; name: string }[];
    sections: { id: number; name: string; grade_level_id: number }[];
}>();

const page = usePage();
const flash = computed(() => (page.props as any).flash);

const search = ref('');
const selectedCategory = ref('all');
const showCreateModal = ref(false);
const showAiPrompt = ref(false);
const generatingDraft = ref(false);

const categories = [
    { value: 'all', label: 'All Tasks' },
    { value: 'general', label: 'General' },
    { value: 'homework', label: 'Homework' },
    { value: 'project', label: 'Project' },
    { value: 'reading', label: 'Reading' },
    { value: 'reminder', label: 'Reminder' },
];

const form = useForm({
    title: '',
    description: '',
    category: 'general',
    target_grade_level_id: '' as string | number,
    target_section_id: '' as string | number,
    due_date: '',
    link_url: '',
});

const filteredSections = computed(() => {
    if (!form.target_grade_level_id) {
        return [];
    }

    return props.sections.filter(
        (s) => s.grade_level_id === Number(form.target_grade_level_id),
    );
});

const items = computed(() => {
    let list = props.todos.data || [];

    if (selectedCategory.value !== 'all') {
        list = list.filter((t) => t.category === selectedCategory.value);
    }

    const q = search.value.trim().toLowerCase();

    if (q) {
        list = list.filter(
            (t) =>
                t.title.toLowerCase().includes(q) ||
                (t.description && t.description.toLowerCase().includes(q)),
        );
    }

    return list;
});

function openCreateModal(withAi = true) {
    if (form.processing || generatingDraft.value) {
        return;
    }

    showAiPrompt.value = withAi;
    form.clearErrors();
    form.reset();
    form.category = 'general';
    form.target_grade_level_id = '';
    form.target_section_id = '';
    showCreateModal.value = true;
}

function closeCreateModal() {
    if (form.processing) {
        return;
    }

    showCreateModal.value = false;
    generatingDraft.value = false;
}

function applyDraft(draft: AiCommunicationDraft) {
    if (!showCreateModal.value || form.processing || !('description' in draft)) {
        return;
    }

    form.clearErrors();
    form.title = draft.title;
    form.description = draft.description;
    form.category = draft.category;
    form.target_grade_level_id = draft.target_grade_level_id
        ?? props.sections.find((section) => section.id === draft.target_section_id)?.grade_level_id ?? '';
    form.target_section_id = draft.target_section_id ?? '';
    form.due_date = draft.due_date ?? '';
    form.link_url = draft.link_url ?? '';
}

function applyAndSubmitDraft(draft: AiCommunicationDraft) {
    applyDraft(draft);
    generatingDraft.value = false;
    submitCreate();
}

function openFromUrl() {
    if (!showCreateModal.value && new URL(page.url, window.location.origin).searchParams.get('create') === 'todo') {
        openCreateModal(true);
    }
}

onMounted(openFromUrl);
watch(() => page.url, openFromUrl);

function submitCreate() {
    if (generatingDraft.value || form.processing) {
        return;
    }

    form.post('/teacher/todos', {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
}

function deleteTodo(item: any) {
    if (!confirm(`Delete to-do task "${item.title}"?`)) {
        return;
    }

    router.delete(`/teacher/todos/${item.id}`, {
        preserveScroll: true,
    });
}

function dismissGradingTask(item: any) {
    router.post(
        '/teacher/todos/dismiss-grading-task',
        { task_key: item.id },
        { preserveScroll: true }
    );
}

function getCategoryColor(cat: string) {
    switch (cat) {
        case 'homework':
            return { bg: 'rgba(59, 130, 246, 0.15)', text: '#3B82F6' };
        case 'project':
            return { bg: 'rgba(168, 85, 247, 0.15)', text: '#A855F7' };
        case 'reading':
            return { bg: 'rgba(236, 72, 153, 0.15)', text: '#EC4899' };
        case 'reminder':
            return { bg: 'rgba(245, 158, 11, 0.15)', text: '#F59E0B' };
        default:
            return { bg: 'rgba(16, 185, 129, 0.15)', text: '#10B981' };
    }
}
</script>

<template>
    <Head title="Class To-Dos & Tasks" />

    <div class="px-6 py-6 space-y-6">
        <!-- Flash messages -->
        <div
            v-if="flash?.success"
            class="gl-fade-in rounded-2xl px-5 py-3 text-sm font-medium"
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
            class="gl-fade-in rounded-2xl px-5 py-3 text-sm font-medium"
            style="
                background: var(--gl-danger-bg);
                border: 1px solid rgba(239, 68, 68, 0.35);
                color: var(--gl-danger);
            "
        >
            {{ flash.error }}
        </div>

        <!-- Header Banner -->
        <div class="gl-glow-card p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl"
                    style="
                        background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(59, 130, 246, 0.2));
                        border: 1px solid rgba(16, 185, 129, 0.35);
                    "
                >
                    <CheckSquare class="h-6 w-6" style="color: var(--gl-success)" />
                </div>
                <div>
                    <h1 class="text-xl font-bold" style="color: var(--gl-text-primary)">
                        Class To-Dos & Assigned Tasks
                    </h1>
                    <p class="text-xs" style="color: var(--gl-text-muted)">
                        Assign tasks, reading materials, and homework to students with live completion tracking
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="openCreateModal(true)" class="inline-flex items-center gap-2 rounded-xl border border-[var(--gl-border)] px-3 py-2.5 text-xs font-bold text-[var(--gl-primary)]">
                    <Sparkles class="h-4 w-4" /> Create with AI
                </button>
            <button
                type="button"
                @click="openCreateModal()"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold text-white transition-all hover:scale-105"
                style="
                    background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary));
                    box-shadow: 0 0 16px var(--gl-primary-glow);
                "
            >
                <Plus class="h-4 w-4" />
                Assign New Task
            </button>
            </div>
        </div>

        <!-- Action Items / Pending Grading Tasks -->
        <div v-if="gradingTasks && gradingTasks.length > 0" class="space-y-3">
            <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-amber-500">
                <Clock class="h-4 w-4" /> Action Items: Submissions Needing Review ({{ gradingTasks.length }})
            </h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="gt in gradingTasks"
                    :key="gt.id"
                    class="gl-glow-card flex flex-col justify-between p-4 border border-amber-500/30 bg-amber-500/5 transition-transform hover:scale-[1.01]"
                >
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <span class="rounded-full bg-amber-500/20 px-2 py-0.5 text-[10px] font-bold text-amber-600">
                                {{ gt.type }}
                            </span>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] font-medium text-[var(--gl-text-muted)]">{{ gt.created_at }}</span>
                                <button
                                    type="button"
                                    @click="dismissGradingTask(gt)"
                                    title="Dismiss action item"
                                    class="rounded-md p-1 text-[var(--gl-text-muted)] hover:bg-amber-500/20 hover:text-amber-700 transition-colors"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                        <h4 class="text-xs font-bold text-[var(--gl-text-primary)] mb-1">{{ gt.title }}</h4>
                        <p class="text-[11px] text-[var(--gl-text-secondary)] line-clamp-2">{{ gt.description }}</p>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-[var(--gl-border)] flex justify-end">
                        <a
                            :href="gt.link"
                            class="inline-flex items-center gap-1 rounded-lg bg-amber-500/20 px-3 py-1.5 text-xs font-bold text-amber-600 transition-colors hover:bg-amber-500 hover:text-white"
                        >
                            {{ gt.action_label }} <ExternalLink class="h-3 w-3" />
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls / Search & Filters -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-1.5">
                <button
                    v-for="cat in categories"
                    :key="cat.value"
                    type="button"
                    @click="selectedCategory = cat.value"
                    class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-all capitalize"
                    :style="
                        selectedCategory === cat.value
                            ? {
                                  background: 'var(--gl-primary)',
                                  color: '#fff',
                                  boxShadow: '0 0 12px var(--gl-primary-glow)',
                              }
                            : {
                                  background: 'var(--gl-surface)',
                                  border: '1px solid var(--gl-border)',
                                  color: 'var(--gl-text-secondary)',
                              }
                    "
                >
                    {{ cat.label }}
                </button>
            </div>

            <div class="relative w-full sm:w-64">
                <Search
                    class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4"
                    style="color: var(--gl-text-muted)"
                />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search assigned tasks..."
                    class="w-full rounded-xl pl-9 pr-4 py-2 text-xs outline-none transition-all focus:border-[var(--gl-primary)]"
                    style="
                        background: var(--gl-surface);
                        border: 1px solid var(--gl-border);
                        color: var(--gl-text-primary);
                    "
                />
            </div>
        </div>

        <!-- Task Cards Grid -->
        <div v-if="items.length > 0" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="t in items"
                :key="t.id"
                class="gl-glow-card relative flex flex-col justify-between p-5 transition-all duration-200 hover:-translate-y-0.5"
            >
                <div>
                    <!-- Header pills & actions -->
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span
                            class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                            :style="{
                                background: getCategoryColor(t.category).bg,
                                color: getCategoryColor(t.category).text,
                            }"
                        >
                            {{ t.category }}
                        </span>

                        <div v-if="t.can_manage" class="flex items-center gap-1">
                            <button
                                type="button"
                                @click="deleteTodo(t)"
                                title="Delete task"
                                class="rounded-lg p-1.5 transition-all hover:bg-[rgba(239,68,68,0.12)] hover:text-[var(--gl-danger)]"
                                style="color: var(--gl-text-muted)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Title & description -->
                    <h3 class="text-sm font-bold mb-1 leading-snug" style="color: var(--gl-text-primary)">
                        {{ t.title }}
                    </h3>
                    <p
                        v-if="t.description"
                        class="text-xs leading-relaxed line-clamp-3 mb-3"
                        style="color: var(--gl-text-secondary)"
                    >
                        {{ t.description }}
                    </p>

                    <!-- Link resource if present -->
                    <a
                        v-if="t.link_url"
                        :href="t.link_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 text-[11px] font-semibold mb-3 hover:underline"
                        style="color: var(--gl-primary)"
                    >
                        <ExternalLink class="h-3 w-3" />
                        Attached Resource Link
                    </a>
                </div>

                <!-- Bottom stats & completion bar -->
                <div class="mt-4 pt-3 border-t space-y-2.5 text-[11px]" style="border-color: var(--gl-border)">
                    <!-- Target & Due Date -->
                    <div class="flex items-center justify-between gap-2" style="color: var(--gl-text-muted)">
                        <div class="flex items-center gap-1.5 truncate">
                            <Users class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">
                                <template v-if="t.grade_level">
                                    {{ t.grade_level.name }}
                                    <template v-if="t.section"> - {{ t.section.name }}</template>
                                </template>
                                <template v-else>All Students</template>
                            </span>
                        </div>

                        <div class="flex items-center gap-1 shrink-0" :class="{ 'text-red-500 font-bold': t.is_overdue }">
                            <Clock class="h-3.5 w-3.5" />
                            <span>{{ t.formatted_due }}</span>
                        </div>
                    </div>

                    <!-- Completion progress -->
                    <div>
                        <div class="flex items-center justify-between text-[10px] mb-1">
                            <span style="color: var(--gl-text-muted)">Student Completion</span>
                            <span class="font-bold" style="color: var(--gl-text-primary)">
                                {{ t.completions_count }} / {{ t.total_eligible }}
                                ({{ t.total_eligible > 0 ? Math.round((t.completions_count / t.total_eligible) * 100) : 0 }}%)
                            </span>
                        </div>
                        <div class="h-1.5 w-full rounded-full overflow-hidden" style="background: var(--gl-surface-2)">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :style="{
                                    width: (t.total_eligible > 0 ? Math.min(100, Math.round((t.completions_count / t.total_eligible) * 100)) : 0) + '%',
                                    background: 'linear-gradient(90deg, var(--gl-success), var(--gl-primary))',
                                }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="gl-glow-card flex flex-col items-center justify-center p-12 text-center"
        >
            <div
                class="flex h-14 w-14 items-center justify-center rounded-2xl mb-3"
                style="background: var(--gl-surface-2)"
            >
                <CheckSquare class="h-7 w-7 opacity-40" style="color: var(--gl-text-muted)" />
            </div>
            <h3 class="text-sm font-bold" style="color: var(--gl-text-primary)">
                No tasks assigned yet
            </h3>
            <p class="text-xs mt-1 max-w-sm" style="color: var(--gl-text-muted)">
                Click "Assign New Task" to create homework, projects, or reading assignments for your classes.
            </p>
        </div>
    </div>

    <!-- Create Task Modal -->
    <div
        v-if="showCreateModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        @click.self="closeCreateModal"
    >
        <div
            class="gl-glow-card w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 space-y-4 rounded-3xl"
            style="background: var(--gl-surface); border: 1px solid var(--gl-border)"
        >
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl"
                        style="background: rgba(16, 185, 129, 0.15); color: var(--gl-success)"
                    >
                        <CheckSquare class="h-4 w-4" />
                    </div>
                    <h2 class="text-base font-bold" style="color: var(--gl-text-primary)">
                        Assign Class To-Do
                    </h2>
                </div>
                <div class="flex items-center gap-1.5">
                    <button
                        type="button"
                        @click="showAiPrompt = !showAiPrompt"
                        class="inline-flex items-center gap-1.5 rounded-xl border px-2.5 py-1.5 text-xs font-bold transition-colors"
                        :style="{
                            borderColor: 'var(--gl-border)',
                            color: showAiPrompt ? 'var(--gl-primary)' : 'var(--gl-text-secondary)',
                            background: showAiPrompt ? 'var(--gl-surface-2)' : 'transparent',
                        }"
                    >
                        <Sparkles class="h-3.5 w-3.5" />
                        AI Prompt
                    </button>
                    <button
                        type="button"
                        @click="closeCreateModal"
                        class="rounded-xl p-1.5 transition-colors hover:bg-[var(--gl-surface-2)]"
                        style="color: var(--gl-text-muted)"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <AiCommunicationPrompt
                v-if="showAiPrompt"
                type="todo"
                :disabled="form.processing"
                :grade-levels="gradeLevels"
                :sections="sections"
                :categories="['general', 'homework', 'project', 'reading', 'reminder']"
                @draft="applyDraft"
                @submit-draft="applyAndSubmitDraft"
                @generating="generatingDraft = $event"
            />

            <form @submit.prevent="submitCreate" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--gl-text-primary)">
                        Task Title *
                    </label>
                    <input
                        v-model="form.title"
                        type="text"
                        placeholder="e.g. Read Chapter 4 & Submit Laboratory Report"
                        required
                        class="w-full rounded-xl px-3.5 py-2 text-xs outline-none transition-all focus:border-[var(--gl-primary)]"
                        style="background: var(--gl-surface-2); border: 1px solid var(--gl-border); color: var(--gl-text-primary)"
                    />
                    <p v-if="form.errors.title" class="text-[11px] text-red-500 mt-1">{{ form.errors.title }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color: var(--gl-text-primary)">
                            Category
                        </label>
                        <select
                            v-model="form.category"
                            class="w-full rounded-xl px-3 py-2 text-xs outline-none capitalize"
                            style="background: var(--gl-surface-2); border: 1px solid var(--gl-border); color: var(--gl-text-primary)"
                        >
                            <option value="general">General</option>
                            <option value="homework">Homework</option>
                            <option value="project">Project</option>
                            <option value="reading">Reading</option>
                            <option value="reminder">Reminder</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color: var(--gl-text-primary)">
                            Due Date & Time
                        </label>
                        <input
                            v-model="form.due_date"
                            type="datetime-local"
                            class="w-full rounded-xl px-3 py-2 text-xs outline-none"
                            style="background: var(--gl-surface-2); border: 1px solid var(--gl-border); color: var(--gl-text-primary)"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color: var(--gl-text-primary)">
                            Target Grade Level
                        </label>
                        <select
                            v-model="form.target_grade_level_id"
                            @change="form.target_section_id = ''"
                            class="w-full rounded-xl px-3 py-2 text-xs outline-none"
                            style="background: var(--gl-surface-2); border: 1px solid var(--gl-border); color: var(--gl-text-primary)"
                        >
                            <option value="">All Grade Levels</option>
                            <option v-for="g in gradeLevels" :key="g.id" :value="g.id">
                                {{ g.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color: var(--gl-text-primary)">
                            Target Section
                        </label>
                        <select
                            v-model="form.target_section_id"
                            :disabled="!form.target_grade_level_id"
                            class="w-full rounded-xl px-3 py-2 text-xs outline-none disabled:opacity-50"
                            style="background: var(--gl-surface-2); border: 1px solid var(--gl-border); color: var(--gl-text-primary)"
                        >
                            <option value="">All Sections</option>
                            <option v-for="s in filteredSections" :key="s.id" :value="s.id">
                                {{ s.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--gl-text-primary)">
                        Resource URL (Optional)
                    </label>
                    <input
                        v-model="form.link_url"
                        type="url"
                        placeholder="https://example.com/slides-or-resource"
                        class="w-full rounded-xl px-3.5 py-2 text-xs outline-none transition-all focus:border-[var(--gl-primary)]"
                        style="background: var(--gl-surface-2); border: 1px solid var(--gl-border); color: var(--gl-text-primary)"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--gl-text-primary)">
                        Task Description & Instructions
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        placeholder="Provide details or guidelines for students..."
                        class="w-full rounded-xl px-3.5 py-2 text-xs outline-none transition-all focus:border-[var(--gl-primary)]"
                        style="background: var(--gl-surface-2); border: 1px solid var(--gl-border); color: var(--gl-text-primary)"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="closeCreateModal"
                        :disabled="form.processing"
                        class="rounded-xl px-4 py-2 text-xs font-semibold transition-all disabled:opacity-50"
                        style="background: var(--gl-surface-2); color: var(--gl-text-secondary)"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing || generatingDraft"
                        class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold text-white transition-all hover:scale-105 disabled:opacity-50"
                        style="background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))"
                    >
                        Assign Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
