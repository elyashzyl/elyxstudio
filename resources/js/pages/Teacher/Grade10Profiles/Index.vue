<template>
    <Head title="Grade 10 Profiles &amp; Yearbook" />

    <div class="space-y-6 pb-12">
        <!-- Header & Action Bar -->
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <p
                    class="text-xs font-semibold tracking-widest uppercase"
                    style="color: var(--gl-text-muted)"
                >
                    Yearbook &amp; Records
                </p>
                <h1
                    class="mt-1 flex items-center gap-2.5 text-2xl font-black tracking-tight sm:text-3xl"
                    style="color: var(--gl-text-primary)"
                >
                    <GraduationCap class="h-8 w-8 text-blue-500" />
                    <span>Grade 10 Profiles</span>
                </h1>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    {{ profiles.length }} submitted &bull; {{ pendingStudents.length }} pending
                </p>
            </div>

            <!-- Export Buttons Group -->
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="/teacher/grade10-profiles/export/pdf"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 rounded-xl border px-3.5 py-2 text-xs font-bold transition-all hover:opacity-90"
                    style="
                        background: rgba(239, 68, 68, 0.1);
                        color: #ef4444;
                        border-color: rgba(239, 68, 68, 0.25);
                    "
                    title="Export as Landscape PDF Document"
                >
                    <FileText class="h-4 w-4" />
                    <span>Export PDF</span>
                </a>

                <a
                    href="/teacher/grade10-profiles/export/excel"
                    class="inline-flex items-center gap-1.5 rounded-xl border px-3.5 py-2 text-xs font-bold transition-all hover:opacity-90"
                    style="
                        background: rgba(16, 185, 129, 0.1);
                        color: #10b981;
                        border-color: rgba(16, 185, 129, 0.25);
                    "
                    title="Export as Microsoft Excel (.xls)"
                >
                    <Sheet class="h-4 w-4" />
                    <span>Export Excel</span>
                </a>

                <a
                    href="/teacher/grade10-profiles/export/word"
                    class="inline-flex items-center gap-1.5 rounded-xl border px-3.5 py-2 text-xs font-bold transition-all hover:opacity-90"
                    style="
                        background: rgba(59, 130, 246, 0.1);
                        color: #3b82f6;
                        border-color: rgba(59, 130, 246, 0.25);
                    "
                    title="Export as Microsoft Word (.doc)"
                >
                    <FileCode class="h-4 w-4" />
                    <span>Export Word</span>
                </a>

                <a
                    href="/teacher/grade10-profiles/export/csv"
                    class="inline-flex items-center gap-1.5 rounded-xl border px-3.5 py-2 text-xs font-bold transition-all hover:opacity-90"
                    style="
                        background: var(--gl-surface);
                        border-color: var(--gl-border);
                        color: var(--gl-text-primary);
                    "
                    title="Export as CSV with UTF-8 BOM"
                >
                    <Download class="h-4 w-4" />
                    <span>Export CSV</span>
                </a>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div
                class="rounded-2xl p-5"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--gl-text-muted)">
                        Total Submissions
                    </span>
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-xl"
                        style="background: rgba(59, 130, 246, 0.15); color: var(--gl-primary)"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                    </span>
                </div>
                <p class="mt-3 text-3xl font-black" style="color: var(--gl-text-primary)">
                    {{ profiles.length }}
                </p>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    Completed Grade 10 student profiles
                </p>
            </div>

            <div
                class="rounded-2xl p-5"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--gl-text-muted)">
                        Pending Students
                    </span>
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-xl"
                        style="background: rgba(245, 158, 11, 0.15); color: #f59e0b"
                    >
                        <Clock class="h-4 w-4" />
                    </span>
                </div>
                <p class="mt-3 text-3xl font-black" style="color: #f59e0b">
                    {{ pendingStudents.length }}
                </p>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    Grade 10 students not yet submitted
                </p>
            </div>

            <div
                class="rounded-2xl p-5"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--gl-text-muted)">
                        Completion Rate
                    </span>
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-xl"
                        style="background: rgba(16, 185, 129, 0.15); color: #10b981"
                    >
                        <GraduationCap class="h-4 w-4" />
                    </span>
                </div>
                <p class="mt-3 text-3xl font-black" style="color: var(--gl-text-primary)">
                    {{ completionRate }}%
                </p>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    {{ profiles.length }} of {{ totalGrade10 }} total students
                </p>
            </div>
        </div>

        <!-- Navigation Tabs & Filters -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2 border-b sm:border-0" style="border-color: var(--gl-border)">
                <button
                    @click="activeTab = 'submitted'"
                    class="flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition-all"
                    :style="
                        activeTab === 'submitted'
                            ? 'background: var(--gl-primary); color: #fff;'
                            : 'background: var(--gl-surface); color: var(--gl-text-muted); border: 1px solid var(--gl-border);'
                    "
                >
                    <CheckCircle2 class="h-4 w-4" />
                    <span>Submitted ({{ profiles.length }})</span>
                </button>
                <button
                    @click="activeTab = 'pending'"
                    class="flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition-all"
                    :style="
                        activeTab === 'pending'
                            ? 'background: #f59e0b; color: #fff;'
                            : 'background: var(--gl-surface); color: var(--gl-text-muted); border: 1px solid var(--gl-border);'
                    "
                >
                    <Clock class="h-4 w-4" />
                    <span>Pending ({{ pendingStudents.length }})</span>
                </button>
            </div>

            <!-- Search and Section Filter for Active Tab -->
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
                        :placeholder="activeTab === 'submitted' ? 'Search profiles...' : 'Search pending...'"
                        class="rounded-xl border py-2 pr-3 pl-9 text-xs transition-all outline-none"
                        style="
                            background: var(--gl-surface);
                            color: var(--gl-text-primary);
                            border-color: var(--gl-border);
                            width: 200px;
                        "
                    />
                </div>

                <select
                    v-model="sectionFilter"
                    class="rounded-xl border px-3 py-2 text-xs outline-none"
                    style="
                        background: var(--gl-surface);
                        color: var(--gl-text-primary);
                        border-color: var(--gl-border);
                    "
                >
                    <option value="">All sections</option>
                    <option v-for="sec in availableSections" :key="sec" :value="sec">
                        {{ sec }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Tab 1: Submitted Profiles Table -->
        <div
            v-if="activeTab === 'submitted'"
            class="overflow-hidden rounded-2xl border"
            style="
                background: var(--gl-surface);
                border-color: var(--gl-border);
            "
        >
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr
                            class="border-b uppercase tracking-wider"
                            style="
                                background: var(--gl-surface-2);
                                border-color: var(--gl-border);
                                color: var(--gl-text-muted);
                                font-size: 10px;
                            "
                        >
                            <th class="px-3.5 py-3 text-center" style="width: 45px;">#</th>
                            <th class="px-3.5 py-3 text-center" style="width: 50px;">Photo</th>
                            <th class="px-3.5 py-3">Full Name</th>
                            <th class="px-3.5 py-3">Chinese Name</th>
                            <th class="px-3.5 py-3">Birthday</th>
                            <th class="px-3.5 py-3">Hometown</th>
                            <th class="px-3.5 py-3">Ambition</th>
                            <th class="px-3.5 py-3">Section</th>
                            <th class="px-3.5 py-3">Submitted</th>
                            <th class="px-3.5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color: var(--gl-border)">
                        <tr
                            v-for="(item, idx) in filteredProfiles"
                            :key="item.id"
                            class="transition-colors hover:bg-black/5 dark:hover:bg-white/5"
                        >
                            <td class="px-3.5 py-3 text-center font-bold" style="color: var(--gl-text-muted)">
                                {{ idx + 1 }}
                            </td>
                            <td class="px-3.5 py-3 text-center">
                                <img
                                    v-if="item.photo_url"
                                    :src="item.photo_url"
                                    :alt="item.full_name"
                                    class="mx-auto h-9 w-9 rounded-xl object-cover border border-[var(--gl-border)] shadow-xs"
                                />
                                <div
                                    v-else
                                    class="mx-auto flex h-9 w-9 items-center justify-center rounded-xl bg-[var(--gl-surface-2)] text-[11px] font-bold text-[var(--gl-text-muted)]"
                                >
                                    {{ item.full_name.charAt(0) }}
                                </div>
                            </td>
                            <td class="px-3.5 py-3">
                                <p class="font-bold" style="color: var(--gl-text-primary)">
                                    {{ item.full_name }}
                                </p>
                                <p class="text-[10px]" style="color: var(--gl-text-muted)">
                                    Account: {{ item.student_name }}
                                </p>
                            </td>
                            <td class="px-3.5 py-3 font-medium" style="color: var(--gl-text-secondary)">
                                {{ item.chinese_name }}
                            </td>
                            <td class="px-3.5 py-3" style="color: var(--gl-text-secondary)">
                                {{ item.birthday }}
                            </td>
                            <td class="px-3.5 py-3" style="color: var(--gl-text-secondary)">
                                {{ item.hometown }}
                            </td>
                            <td class="px-3.5 py-3 font-semibold" style="color: var(--gl-text-primary)">
                                {{ item.ambition }}
                            </td>
                            <td class="px-3.5 py-3">
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    style="
                                        background: rgba(59, 130, 246, 0.1);
                                        color: var(--gl-primary);
                                    "
                                >
                                    {{ item.section }}
                                </span>
                            </td>
                            <td class="px-3.5 py-3 text-[11px]" style="color: var(--gl-text-muted)">
                                {{ item.submitted_at }}
                            </td>
                            <td class="px-3.5 py-3 text-right">
                                <button
                                    @click="openModal(item)"
                                    class="inline-flex items-center gap-1 rounded-xl border px-2.5 py-1 text-xs font-semibold transition-all hover:opacity-80"
                                    style="
                                        background: var(--gl-surface-2);
                                        border-color: var(--gl-border);
                                        color: var(--gl-text-primary);
                                    "
                                >
                                    <Eye class="h-3.5 w-3.5" />
                                    <span>Details</span>
                                </button>
                            </td>
                        </tr>

                        <tr v-if="filteredProfiles.length === 0">
                            <td colspan="9" class="py-12 text-center" style="color: var(--gl-text-muted)">
                                No submitted Grade 10 profiles found matching your search.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 2: Pending Students Table -->
        <div
            v-if="activeTab === 'pending'"
            class="overflow-hidden rounded-2xl border"
            style="
                background: var(--gl-surface);
                border-color: var(--gl-border);
            "
        >
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr
                            class="border-b uppercase tracking-wider"
                            style="
                                background: var(--gl-surface-2);
                                border-color: var(--gl-border);
                                color: var(--gl-text-muted);
                                font-size: 10px;
                            "
                        >
                            <th class="px-3.5 py-3 text-center" style="width: 45px;">#</th>
                            <th class="px-3.5 py-3">Student Name</th>
                            <th class="px-3.5 py-3">Section</th>
                            <th class="px-3.5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color: var(--gl-border)">
                        <tr
                            v-for="(pending, idx) in filteredPending"
                            :key="pending.id"
                            class="transition-colors hover:bg-black/5 dark:hover:bg-white/5"
                        >
                            <td class="px-3.5 py-3 text-center font-bold" style="color: var(--gl-text-muted)">
                                {{ idx + 1 }}
                            </td>
                            <td class="px-3.5 py-3 font-bold" style="color: var(--gl-text-primary)">
                                {{ pending.name }}
                            </td>
                            <td class="px-3.5 py-3">
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    style="
                                        background: rgba(59, 130, 246, 0.1);
                                        color: var(--gl-primary);
                                    "
                                >
                                    {{ pending.section }}
                                </span>
                            </td>
                            <td class="px-3.5 py-3">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    style="
                                        background: rgba(245, 158, 11, 0.12);
                                        color: #f59e0b;
                                    "
                                >
                                    <Clock class="h-3 w-3" />
                                    Not Submitted Yet
                                </span>
                            </td>
                        </tr>

                        <tr v-if="filteredPending.length === 0">
                            <td colspan="4" class="py-12 text-center" style="color: var(--gl-text-muted)">
                                No pending Grade 10 students found. All students have submitted their profiles!
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Detail Modal -->
        <Teleport to="body">
            <div
                v-if="selectedProfile"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background: rgba(0, 0, 0, 0.55); backdrop-filter: blur(4px)"
                @click.self="selectedProfile = null"
            >
                <div
                    class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl p-6 sm:p-8"
                    style="
                        background: var(--gl-surface);
                        border: 1px solid var(--gl-border);
                        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
                    "
                >
                    <div class="mb-5 flex items-center justify-between border-b pb-4" style="border-color: var(--gl-border)">
                        <div class="flex items-center gap-4">
                            <img
                                v-if="selectedProfile.photo_url"
                                :src="selectedProfile.photo_url"
                                :alt="selectedProfile.full_name"
                                class="h-16 w-16 rounded-2xl object-cover border-2 border-[var(--gl-primary)]/40 shadow-sm"
                            />
                            <div
                                v-else
                                class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[var(--gl-surface-2)] text-xl font-black text-[var(--gl-text-muted)] border border-[var(--gl-border)]"
                            >
                                {{ selectedProfile.full_name.charAt(0) }}
                            </div>

                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-blue-500">
                                    Grade 10 Completer Profile
                                </span>
                                <h2 class="text-xl font-black" style="color: var(--gl-text-primary)">
                                    {{ selectedProfile.full_name }}
                                </h2>
                            </div>
                        </div>
                        <button
                            @click="selectedProfile = null"
                            class="rounded-xl p-2 transition-colors hover:opacity-75"
                            style="background: var(--gl-surface-2); color: var(--gl-text-muted)"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl p-3.5" style="background: var(--gl-surface-2)">
                                <p class="text-[10px] font-bold uppercase" style="color: var(--gl-text-muted)">
                                    CHINESE NAME
                                </p>
                                <p class="mt-1 text-sm font-semibold" style="color: var(--gl-text-primary)">
                                    {{ selectedProfile.chinese_name || '—' }}
                                </p>
                            </div>
                            <div class="rounded-2xl p-3.5" style="background: var(--gl-surface-2)">
                                <p class="text-[10px] font-bold uppercase" style="color: var(--gl-text-muted)">
                                    BIRTHDAY
                                </p>
                                <p class="mt-1 text-sm font-semibold" style="color: var(--gl-text-primary)">
                                    {{ selectedProfile.birthday }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl p-3.5" style="background: var(--gl-surface-2)">
                                <p class="text-[10px] font-bold uppercase" style="color: var(--gl-text-muted)">
                                    HOMETOWN
                                </p>
                                <p class="mt-1 text-sm font-semibold" style="color: var(--gl-text-primary)">
                                    {{ selectedProfile.hometown }}
                                </p>
                            </div>
                            <div class="rounded-2xl p-3.5" style="background: var(--gl-surface-2)">
                                <p class="text-[10px] font-bold uppercase" style="color: var(--gl-text-muted)">
                                    AMBITION
                                </p>
                                <p class="mt-1 text-sm font-semibold" style="color: var(--gl-text-primary)">
                                    {{ selectedProfile.ambition }}
                                </p>
                            </div>
                        </div>

                        <div class="rounded-2xl p-3.5" style="background: var(--gl-surface-2)">
                            <p class="text-[10px] font-bold uppercase" style="color: var(--gl-text-muted)">
                                MOTTO
                            </p>
                            <p class="mt-1 text-sm font-medium italic" style="color: var(--gl-text-primary)">
                                "{{ selectedProfile.motto }}"
                            </p>
                        </div>

                        <div class="rounded-2xl p-3.5" style="background: var(--gl-surface-2)">
                            <p class="text-[10px] font-bold uppercase" style="color: var(--gl-text-muted)">
                                MESSAGE (3-5 Sentences)
                            </p>
                            <p class="mt-1 text-sm leading-relaxed whitespace-pre-wrap font-medium" style="color: var(--gl-text-primary)">
                                {{ selectedProfile.message }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl p-3.5" style="background: var(--gl-surface-2)">
                                <p class="text-[10px] font-bold uppercase" style="color: var(--gl-text-muted)">
                                    SOCIAL MEDIA ACCOUNT
                                </p>
                                <p class="mt-1 text-sm font-semibold" style="color: var(--gl-text-primary)">
                                    {{ selectedProfile.social_media || '—' }}
                                </p>
                            </div>
                            <div class="rounded-2xl p-3.5" style="background: var(--gl-surface-2)">
                                <p class="text-[10px] font-bold uppercase" style="color: var(--gl-text-muted)">
                                    SECTION &amp; SUBMISSION DATE
                                </p>
                                <p class="mt-1 text-sm font-semibold" style="color: var(--gl-text-primary)">
                                    {{ selectedProfile.section }} &bull; {{ selectedProfile.submitted_at }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button
                            @click="selectedProfile = null"
                            class="rounded-xl px-5 py-2.5 text-xs font-bold transition-all"
                            style="background: var(--gl-surface-2); color: var(--gl-text-primary)"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    GraduationCap,
    Download,
    FileText,
    FileCode,
    Sheet,
    Search,
    CheckCircle2,
    Clock,
    Eye,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface ProfileItem {
    no: number;
    id: number;
    student_name: string;
    section: string;
    full_name: string;
    chinese_name: string;
    birthday: string;
    hometown: string;
    ambition: string;
    motto: string;
    message: string;
    social_media: string;
    photo_url?: string | null;
    submitted_at: string;
}

interface PendingStudent {
    id: number;
    name: string;
    section: string;
}

const props = defineProps<{
    profiles: ProfileItem[];
    pendingStudents: PendingStudent[];
}>();

const activeTab = ref<'submitted' | 'pending'>('submitted');
const search = ref('');
const sectionFilter = ref('');
const selectedProfile = ref<ProfileItem | null>(null);

const totalGrade10 = computed(() => props.profiles.length + props.pendingStudents.length);
const completionRate = computed(() => {
    if (totalGrade10.value === 0) {
return 0;
}

    return Math.round((props.profiles.length / totalGrade10.value) * 100);
});

const availableSections = computed(() => {
    const set = new Set<string>();
    props.profiles.forEach((p) => {
        if (p.section && p.section !== 'Unassigned') {
set.add(p.section);
}
    });
    props.pendingStudents.forEach((p) => {
        if (p.section && p.section !== 'Unassigned') {
set.add(p.section);
}
    });

    return Array.from(set).sort();
});

const filteredProfiles = computed(() => {
    const q = search.value.trim().toLowerCase();

    return props.profiles.filter((p) => {
        const matchesSection = !sectionFilter.value || p.section === sectionFilter.value;

        if (!matchesSection) {
return false;
}

        if (!q) {
return true;
}

        return (
            p.full_name.toLowerCase().includes(q) ||
            p.student_name.toLowerCase().includes(q) ||
            p.chinese_name.toLowerCase().includes(q) ||
            p.hometown.toLowerCase().includes(q) ||
            p.ambition.toLowerCase().includes(q) ||
            p.motto.toLowerCase().includes(q) ||
            p.message.toLowerCase().includes(q) ||
            p.social_media.toLowerCase().includes(q)
        );
    });
});

const filteredPending = computed(() => {
    const q = search.value.trim().toLowerCase();

    return props.pendingStudents.filter((p) => {
        const matchesSection = !sectionFilter.value || p.section === sectionFilter.value;

        if (!matchesSection) {
return false;
}

        if (!q) {
return true;
}

        return p.name.toLowerCase().includes(q) || p.section.toLowerCase().includes(q);
    });
});

function openModal(item: ProfileItem) {
    selectedProfile.value = item;
}
</script>
