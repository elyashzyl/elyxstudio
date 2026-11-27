<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    Megaphone,
    Plus,
    Pin,
    Trash2,
    Calendar,
    Users,
    Search,
    X,
    AlertCircle,
    CheckCircle2,
    Clock,
    Sparkles,
} from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import AiCommunicationPrompt from '@/components/AiCommunicationPrompt.vue';
import type { AiCommunicationDraft } from '@/types/ai-communication';

const props = defineProps<{
    announcements: {
        data: any[];
        links?: any[];
        current_page?: number;
        last_page?: number;
    };
    gradeLevels: { id: number; name: string }[];
    sections: { id: number; name: string; grade_level_id: number }[];
}>();

const page = usePage();
const flash = computed(() => (page.props as any).flash);

const search = ref('');
const selectedCategory = ref('all');
const selectedAudience = ref('all');
const showCreateModal = ref(false);
const showAiPrompt = ref(false);
const notificationMode = ref(false);
const generatingDraft = ref(false);

const categories = [
    { value: 'all', label: 'All' },
    { value: 'general', label: 'General' },
    { value: 'academic', label: 'Academic' },
    { value: 'reminder', label: 'Reminder' },
    { value: 'urgent', label: 'Urgent' },
    { value: 'event', label: 'Event' },
];

const form = useForm({
    title: '',
    content: '',
    category: 'general',
    is_pinned: false,
    target_grade_level_id: '' as string | number,
    target_section_id: '' as string | number,
    expires_at: '',
});

const modalAvailableSections = computed(() => {
    if (!form.target_grade_level_id) {
        return props.sections;
    }

    return props.sections.filter(
        (s) => s.grade_level_id === Number(form.target_grade_level_id),
    );
});

function getGradeName(gradeLevelId?: number | null) {
    if (!gradeLevelId) {
return '';
}

    const g = props.gradeLevels.find((gl) => gl.id === gradeLevelId);

    return g ? g.name : '';
}

function onGradeLevelChange() {
    if (form.target_section_id) {
        const currentSec = props.sections.find((s) => s.id === Number(form.target_section_id));

        if (currentSec && form.target_grade_level_id && currentSec.grade_level_id !== Number(form.target_grade_level_id)) {
            form.target_section_id = '';
        }
    }
}

function onSectionChange() {
    if (form.target_section_id) {
        const sec = props.sections.find((s) => s.id === Number(form.target_section_id));

        if (sec) {
            form.target_grade_level_id = sec.grade_level_id;
        }
    }
}

const audienceHelpText = computed(() => {
    if (form.target_section_id) {
        const sec = props.sections.find((s) => s.id === Number(form.target_section_id));
        const gradeName = sec ? getGradeName(sec.grade_level_id) : '';

        return `Target: Section ${sec?.name || ''}${gradeName ? ' (' + gradeName + ')' : ''}`;
    }

    if (form.target_grade_level_id) {
        const grade = props.gradeLevels.find((g) => g.id === Number(form.target_grade_level_id));

        return `Target: All sections in ${grade?.name || 'Grade'}`;
    }

    return 'Target: Entire school (All students & grades)';
});

const items = computed(() => {
    let list = props.announcements.data || [];

    if (selectedCategory.value !== 'all') {
        list = list.filter((a) => a.category === selectedCategory.value);
    }

    if (selectedAudience.value !== 'all') {
        if (selectedAudience.value === 'school_wide') {
            list = list.filter((a) => !a.grade_level && !a.section);
        } else if (selectedAudience.value === 'any_grade') {
            list = list.filter((a) => a.grade_level && !a.section);
        } else if (selectedAudience.value === 'any_section') {
            list = list.filter((a) => !!a.section);
        } else if (selectedAudience.value.startsWith('grade:')) {
            const gid = Number(selectedAudience.value.split(':')[1]);
            list = list.filter((a) => a.grade_level?.id === gid && !a.section);
        } else if (selectedAudience.value.startsWith('section:')) {
            const sid = Number(selectedAudience.value.split(':')[1]);
            list = list.filter((a) => a.section?.id === sid);
        }
    }

    const q = search.value.trim().toLowerCase();

    if (q) {
        list = list.filter(
            (a) =>
                a.title.toLowerCase().includes(q) ||
                a.content.toLowerCase().includes(q) ||
                (a.author_name && a.author_name.toLowerCase().includes(q)) ||
                (a.section?.name && a.section.name.toLowerCase().includes(q)) ||
                (a.grade_level?.name && a.grade_level.name.toLowerCase().includes(q)),
        );
    }

    return list;
});

function openCreateModal(mode?: 'announcement' | 'notification') {
    if (form.processing || generatingDraft.value) {
        return;
    }

    showAiPrompt.value = true;
    notificationMode.value = mode === 'notification';
    form.clearErrors();
    form.reset();
    form.category = mode === 'notification' ? 'reminder' : 'general';
    form.is_pinned = false;
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
    if (!showCreateModal.value || form.processing || !('content' in draft)) {
        return;
    }

    form.clearErrors();
    form.title = draft.title;
    form.content = draft.content;
    form.category = draft.category;
    form.is_pinned = draft.is_pinned;
    form.target_grade_level_id = draft.target_grade_level_id
        ?? props.sections.find((section) => section.id === draft.target_section_id)?.grade_level_id ?? '';
    form.target_section_id = draft.target_section_id ?? '';
    form.expires_at = draft.expires_at ?? '';
}

function applyAndSubmitDraft(draft: AiCommunicationDraft) {
    applyDraft(draft);
    generatingDraft.value = false;
    submitCreate();
}

function openFromUrl() {
    const mode = new URL(page.url, window.location.origin).searchParams.get('create');

    if (!showCreateModal.value && (mode === 'announcement' || mode === 'notification')) {
        openCreateModal(mode);
    }
}

onMounted(openFromUrl);
watch(() => page.url, openFromUrl);

function submitCreate() {
    if (generatingDraft.value || form.processing) {
        return;
    }

    form.post('/teacher/announcements', {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
}

function togglePin(announcement: any) {
    router.patch(
        `/teacher/announcements/${announcement.id}/toggle-pin`,
        {},
        {
            preserveScroll: true,
        },
    );
}

function deleteAnnouncement(announcement: any) {
    if (!confirm(`Delete announcement "${announcement.title}"?`)) {
        return;
    }

    router.delete(`/teacher/announcements/${announcement.id}`, {
        preserveScroll: true,
    });
}

function getCategoryColor(cat: string) {
    switch (cat) {
        case 'urgent':
            return {
                bg: 'rgba(239, 68, 68, 0.15)',
                text: '#EF4444',
                border: 'rgba(239, 68, 68, 0.3)',
            };
        case 'reminder':
            return {
                bg: 'rgba(245, 158, 11, 0.15)',
                text: '#F59E0B',
                border: 'rgba(245, 158, 11, 0.3)',
            };
        case 'academic':
            return {
                bg: 'rgba(59, 130, 246, 0.15)',
                text: '#3B82F6',
                border: 'rgba(59, 130, 246, 0.3)',
            };
        case 'event':
            return {
                bg: 'rgba(168, 85, 247, 0.15)',
                text: '#A855F7',
                border: 'rgba(168, 85, 247, 0.3)',
            };
        default:
            return {
                bg: 'rgba(16, 185, 129, 0.15)',
                text: '#10B981',
                border: 'rgba(16, 185, 129, 0.3)',
            };
    }
}
</script>

<template>
    <Head title="Announcements Management" />

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

        <!-- Top Header Banner -->
        <div class="gl-glow-card p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl"
                    style="
                        background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(139, 92, 246, 0.2));
                        border: 1px solid rgba(59, 130, 246, 0.35);
                    "
                >
                    <Megaphone class="h-6 w-6" style="color: var(--gl-primary)" />
                </div>
                <div>
                    <h1 class="text-xl font-bold" style="color: var(--gl-text-primary)">
                        School & Class Announcements
                    </h1>
                    <p class="text-xs" style="color: var(--gl-text-muted)">
                        Publish notices, exam schedules, and urgent alerts for all students or targeted sections
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="openCreateModal('announcement')" class="inline-flex items-center gap-2 rounded-xl border border-[var(--gl-border)] px-3 py-2.5 text-xs font-bold text-[var(--gl-primary)]">
                    <Sparkles class="h-4 w-4" /> Create with AI
                </button>
                <button type="button" @click="openCreateModal('notification')" class="rounded-xl border border-[var(--gl-border)] px-3 py-2.5 text-xs font-bold text-[var(--gl-primary)]">
                    AI notification
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
                New Announcement
            </button>
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

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                <div class="relative w-full sm:w-56">
                    <select
                        v-model="selectedAudience"
                        class="w-full rounded-xl px-3 py-2 text-xs font-medium outline-none transition-all cursor-pointer"
                        style="
                            background: var(--gl-surface);
                            border: 1px solid var(--gl-border);
                            color: var(--gl-text-primary);
                        "
                        title="Filter by target audience or section"
                    >
                        <option value="all">All Audiences</option>
                        <option value="school_wide">School-wide (All Students)</option>
                        <option value="any_section">All Section Announcements</option>
                        <option value="any_grade">All Grade Announcements</option>
                        <optgroup v-if="gradeLevels.length" label="Filter By Grade">
                            <option
                                v-for="g in gradeLevels"
                                :key="'g-' + g.id"
                                :value="'grade:' + g.id"
                            >
                                {{ g.name }} (All Sections)
                            </option>
                        </optgroup>
                        <optgroup v-if="sections.length" label="Filter By Section">
                            <option
                                v-for="s in sections"
                                :key="'s-' + s.id"
                                :value="'section:' + s.id"
                            >
                                Section {{ s.name }} ({{ getGradeName(s.grade_level_id) }})
                            </option>
                        </optgroup>
                    </select>
                </div>

                <div class="relative w-full sm:w-64">
                    <Search
                        class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4"
                        style="color: var(--gl-text-muted)"
                    />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search announcements..."
                        class="w-full rounded-xl pl-9 pr-4 py-2 text-xs outline-none transition-all focus:border-[var(--gl-primary)]"
                        style="
                            background: var(--gl-surface);
                            border: 1px solid var(--gl-border);
                            color: var(--gl-text-primary);
                        "
                    />
                </div>
            </div>
        </div>

        <!-- Announcements Grid -->
        <div v-if="items.length > 0" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="a in items"
                :key="a.id"
                class="gl-glow-card relative flex flex-col justify-between p-5 transition-all duration-200 hover:-translate-y-0.5"
                :style="
                    a.is_pinned
                        ? {
                              border: '1px solid rgba(59, 130, 246, 0.4)',
                              background: 'rgba(59, 130, 246, 0.03)',
                          }
                        : {}
                "
            >
                <div>
                    <!-- Top badges -->
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span
                                class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                :style="{
                                    background: getCategoryColor(a.category).bg,
                                    color: getCategoryColor(a.category).text,
                                    border: '1px solid ' + getCategoryColor(a.category).border,
                                }"
                            >
                                {{ a.category }}
                            </span>
                            <span
                                v-if="a.section"
                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                                style="
                                    background: rgba(139, 92, 246, 0.15);
                                    color: #8B5CF6;
                                    border: 1px solid rgba(139, 92, 246, 0.35);
                                "
                                title="Targeted to specific section"
                            >
                                <Users class="h-2.5 w-2.5" />
                                Section {{ a.section.name }}
                            </span>
                            <span
                                v-else-if="a.grade_level"
                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                                style="
                                    background: rgba(59, 130, 246, 0.15);
                                    color: #3B82F6;
                                    border: 1px solid rgba(59, 130, 246, 0.35);
                                "
                                title="Targeted to grade level"
                            >
                                <Users class="h-2.5 w-2.5" />
                                {{ a.grade_level.name }}
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                style="
                                    background: rgba(100, 116, 139, 0.12);
                                    color: var(--gl-text-secondary);
                                    border: 1px solid rgba(100, 116, 139, 0.25);
                                "
                            >
                                School-wide
                            </span>
                            <span
                                v-if="a.is_pinned"
                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                style="
                                    background: rgba(245, 158, 11, 0.15);
                                    color: #F59E0B;
                                    border: 1px solid rgba(245, 158, 11, 0.3);
                                "
                            >
                                <Pin class="h-2.5 w-2.5 fill-current" />
                                Pinned
                            </span>
                        </div>

                        <!-- Action buttons -->
                        <div v-if="a.can_manage" class="flex items-center gap-1">
                            <button
                                type="button"
                                @click="togglePin(a)"
                                :title="a.is_pinned ? 'Unpin announcement' : 'Pin to top'"
                                class="rounded-lg p-1.5 transition-all hover:scale-110"
                                :style="{
                                    color: a.is_pinned ? '#F59E0B' : 'var(--gl-text-muted)',
                                }"
                            >
                                <Pin class="h-3.5 w-3.5" :class="{ 'fill-current': a.is_pinned }" />
                            </button>
                            <button
                                type="button"
                                @click="deleteAnnouncement(a)"
                                title="Delete announcement"
                                class="rounded-lg p-1.5 transition-all hover:bg-[rgba(239,68,68,0.12)] hover:text-[var(--gl-danger)]"
                                style="color: var(--gl-text-muted)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Title & Content -->
                    <h3 class="text-sm font-bold mb-2 leading-snug" style="color: var(--gl-text-primary)">
                        {{ a.title }}
                    </h3>
                    <p
                        class="text-xs leading-relaxed whitespace-pre-line"
                        style="color: var(--gl-text-secondary)"
                    >
                        {{ a.content }}
                    </p>
                </div>

                <!-- Footer target & date -->
                <div class="mt-4 pt-3 border-t flex flex-wrap items-center justify-between gap-2 text-[11px]" style="border-color: var(--gl-border)">
                    <div class="flex items-center gap-1.5" style="color: var(--gl-text-muted)">
                        <Users class="h-3.5 w-3.5" />
                        <span>
                            <template v-if="a.section">
                                <template v-if="a.grade_level">{{ a.grade_level.name }} &middot; </template>
                                Section {{ a.section.name }}
                            </template>
                            <template v-else-if="a.grade_level">
                                {{ a.grade_level.name }} (All Sections)
                            </template>
                            <template v-else>All Students (School-wide)</template>
                        </span>
                    </div>

                    <div class="flex items-center gap-1" style="color: var(--gl-text-muted)">
                        <Clock class="h-3.5 w-3.5" />
                        <span>{{ a.formatted_date }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-else
            class="gl-glow-card flex flex-col items-center justify-center p-12 text-center"
        >
            <div
                class="flex h-14 w-14 items-center justify-center rounded-2xl mb-3"
                style="background: var(--gl-surface-2)"
            >
                <Megaphone class="h-7 w-7 opacity-40" style="color: var(--gl-text-muted)" />
            </div>
            <h3 class="text-sm font-bold" style="color: var(--gl-text-primary)">
                No announcements found
            </h3>
            <p class="text-xs mt-1 max-w-sm" style="color: var(--gl-text-muted)">
                Click "New Announcement" above to publish your first notice to students.
            </p>
        </div>
    </div>

    <!-- Create Announcement Modal -->
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
                        style="background: rgba(59, 130, 246, 0.15); color: var(--gl-primary)"
                    >
                        <Megaphone class="h-4 w-4" />
                    </div>
                    <h2 class="text-base font-bold" style="color: var(--gl-text-primary)">
                        {{ notificationMode ? 'AI Notification Announcement' : 'Publish Announcement' }}
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
                :type="notificationMode ? 'notification' : 'announcement'"
                :disabled="form.processing"
                :grade-levels="gradeLevels"
                :sections="sections"
                :categories="['general', 'academic', 'reminder', 'urgent', 'event']"
                @draft="applyDraft"
                @submit-draft="applyAndSubmitDraft"
                @generating="generatingDraft = $event"
            />

            <form @submit.prevent="submitCreate" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--gl-text-primary)">
                        Title *
                    </label>
                    <input
                        v-model="form.title"
                        type="text"
                        placeholder="e.g. Schedule of 1st Quarter Examinations"
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
                            <option value="academic">Academic</option>
                            <option value="reminder">Reminder</option>
                            <option value="urgent">Urgent</option>
                            <option value="event">Event</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color: var(--gl-text-primary)">
                            Expiry Date (Optional)
                        </label>
                        <input
                            v-model="form.expires_at"
                            type="datetime-local"
                            class="w-full rounded-xl px-3 py-2 text-xs outline-none"
                            style="background: var(--gl-surface-2); border: 1px solid var(--gl-border); color: var(--gl-text-primary)"
                        />
                    </div>
                </div>

                <div class="rounded-2xl p-3.5 border space-y-2.5" style="background: var(--gl-surface-2); border-color: var(--gl-border)">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold" style="color: var(--gl-text-primary)">
                            Target Audience
                        </label>
                        <span class="text-[11px] font-medium" style="color: var(--gl-primary)">
                            {{ audienceHelpText }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold mb-1" style="color: var(--gl-text-secondary)">
                                Grade Level
                            </label>
                            <select
                                v-model="form.target_grade_level_id"
                                @change="onGradeLevelChange"
                                class="w-full rounded-xl px-3 py-2 text-xs outline-none"
                                style="background: var(--gl-surface); border: 1px solid var(--gl-border); color: var(--gl-text-primary)"
                            >
                                <option value="">All Grade Levels</option>
                                <option v-for="g in gradeLevels" :key="g.id" :value="g.id">
                                    {{ g.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold mb-1" style="color: var(--gl-text-secondary)">
                                Section
                            </label>
                            <select
                                v-model="form.target_section_id"
                                @change="onSectionChange"
                                class="w-full rounded-xl px-3 py-2 text-xs outline-none"
                                style="background: var(--gl-surface); border: 1px solid var(--gl-border); color: var(--gl-text-primary)"
                            >
                                <option value="">All Sections {{ form.target_grade_level_id ? 'in Grade' : '(School-wide)' }}</option>
                                <option v-for="s in modalAvailableSections" :key="s.id" :value="s.id">
                                    {{ s.name }} {{ !form.target_grade_level_id ? `(${getGradeName(s.grade_level_id)})` : '' }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--gl-text-primary)">
                        Announcement Content *
                    </label>
                    <textarea
                        v-model="form.content"
                        rows="4"
                        placeholder="Write the full announcement details here..."
                        required
                        class="w-full rounded-xl px-3.5 py-2 text-xs outline-none transition-all focus:border-[var(--gl-primary)]"
                        style="background: var(--gl-surface-2); border: 1px solid var(--gl-border); color: var(--gl-text-primary)"
                    ></textarea>
                    <p v-if="form.errors.content" class="text-[11px] text-red-500 mt-1">{{ form.errors.content }}</p>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input
                        id="is_pinned"
                        v-model="form.is_pinned"
                        type="checkbox"
                        class="h-4 w-4 rounded accent-[var(--gl-primary)]"
                    />
                    <label for="is_pinned" class="text-xs font-medium cursor-pointer" style="color: var(--gl-text-primary)">
                        Pin this announcement to the top of students' boards
                    </label>
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
                        Publish Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
