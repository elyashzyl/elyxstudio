<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Folder,
    FolderOpen,
    ArrowLeft,
    ChevronRight,
    FileText,
    Presentation,
    Download,
    Search,
    BookOpen,
    CheckCircle2,
    Circle,
} from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{
    materials: any[];
    quarters?: any[];
}>();
const page = usePage();
const flash = computed(() => (page.props as any).flash);

const search = ref('');
const activeFolder = ref<number | 'unassigned' | null>(null);
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
        readCount: number;
        isUnassigned?: boolean;
    }> = quarters.map((q: any) => {
        const items = props.materials.filter(
            (m) => Number(m.quarter_id) === Number(q.id),
        );
        const pdfCount = items.filter(
            (m) => (m.file_type || '').toLowerCase() === 'pdf',
        ).length;
        const readCount = items.filter((m) => Boolean(m.is_read)).length;

        return {
            id: Number(q.id),
            name: q.name,
            code: q.code || q.name,
            total: items.length,
            pdfCount,
            pptCount: items.length - pdfCount,
            readCount,
        };
    });

    const unassignedItems = props.materials.filter((m) => !m.quarter_id);

    if (unassignedItems.length > 0 || folders.length === 0) {
        const pdfCount = unassignedItems.filter(
            (m) => (m.file_type || '').toLowerCase() === 'pdf',
        ).length;
        const readCount = unassignedItems.filter((m) =>
            Boolean(m.is_read),
        ).length;
        folders.push({
            id: 'unassigned',
            name: 'General / Unassigned',
            code: 'GEN',
            total: unassignedItems.length,
            pdfCount,
            pptCount: unassignedItems.length - pdfCount,
            readCount,
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

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();

    return props.materials.filter((m) => {
        if (activeType.value !== 'all') {
            const t = (m.file_type || '').toLowerCase();

            if (activeType.value === 'pdf' && t !== 'pdf') {
return false;
}

            if (activeType.value === 'ppt' && t === 'pdf') {
return false;
}
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

const subjects = computed(() => [
    ...new Set(props.materials.map((m) => m.subject)),
]);

function isPdf(type: string) {
    return (type || '').toLowerCase() === 'pdf';
}

function fileIcon(type: string) {
    return isPdf(type) ? FileText : Presentation;
}

function fileColor(type: string) {
    return isPdf(type)
        ? { bg: 'rgba(239,68,68,0.12)', icon: '#F87171' }
        : { bg: 'rgba(59,130,246,0.12)', icon: '#60A5FA' };
}

function tint(type: string) {
    return isPdf(type) ? 'rgba(239,68,68,0.05)' : 'rgba(59,130,246,0.05)';
}

function tintBorder(type: string) {
    return isPdf(type) ? 'rgba(239,68,68,0.16)' : 'rgba(59,130,246,0.16)';
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

function formatDate(iso: string | null) {
    if (!iso) {
        return '';
    }

    return new Date(iso).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function toggleRead(m: any) {
    router.post(
        `/student/materials/${m.id}/toggle-read`,
        {},
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <Head title="Materials" />

    <!-- Flash notification -->
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
                Discussion Materials
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                <template v-if="showFolderGrid">
                    {{ quarterFolders.length }} quarter folder{{
                        quarterFolders.length === 1 ? '' : 's'
                    }}
                    · {{ subjects.length }} subject{{
                        subjects.length === 1 ? '' : 's'
                    }}
                    · {{ props.materials.length }} material{{
                        props.materials.length === 1 ? '' : 's'
                    }}
                </template>
                <template v-else-if="currentFolder">
                    {{ currentFolder.name }} · {{ filtered.length }} material{{
                        filtered.length === 1 ? '' : 's'
                    }}
                </template>
                <template v-else>
                    {{ subjects.length }} subject{{
                        subjects.length === 1 ? '' : 's'
                    }}
                    · {{ props.materials.length }} material{{
                        props.materials.length === 1 ? '' : 's'
                    }}
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
                <div class="mt-1 flex items-center gap-2 text-xs font-medium" style="color: var(--gl-text-secondary)">
                    <span>{{ folder.total }} material{{ folder.total === 1 ? '' : 's' }}</span>
                    <span
                        v-if="folder.total > 0"
                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                        style="
                            background: rgba(16, 185, 129, 0.12);
                            color: var(--gl-success);
                        "
                    >
                        <CheckCircle2 class="h-3 w-3" />
                        {{ folder.readCount }}/{{ folder.total }} read
                    </span>
                </div>
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
                <div class="flex items-center gap-1.5">
                    <span
                        v-if="m.is_read"
                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold text-emerald-500"
                        style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3)"
                    >
                        <CheckCircle2 class="h-3 w-3" />
                        Read
                    </span>
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
                    v-if="m.grade_level_name"
                    class="rounded-full px-2.5 py-0.5 text-[11px] font-medium"
                    style="
                        background: rgba(59, 130, 246, 0.1);
                        color: var(--gl-primary);
                    "
                >
                    {{ m.grade_level_name }}
                </span>
            </div>
            <div
                class="mt-3 flex items-center justify-between border-t pt-3"
                style="border-color: var(--gl-border)"
            >
                <span
                    class="flex min-w-0 items-center gap-1.5 truncate text-[11px]"
                    style="color: var(--gl-text-muted)"
                >
                    <Presentation class="h-3 w-3 shrink-0" :stroke-width="2" />
                    <span class="truncate"
                        >{{ m.teacher_name || 'Teacher' }} ·
                        {{ formatSize(m.file_size)
                        }}{{
                            formatSize(m.file_size) && formatDate(m.created_at)
                                ? ' · '
                                : ''
                        }}{{ formatDate(m.created_at) }}</span
                    >
                </span>
            </div>
            <div class="mt-3 flex gap-2">
                <a
                    :href="m.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex flex-1 items-center justify-center gap-1.5 rounded-xl px-3 py-2.5 text-xs font-bold text-white transition-all hover:scale-[1.02]"
                    style="
                        background: linear-gradient(
                            135deg,
                            var(--gl-primary),
                            var(--gl-secondary)
                        );
                        box-shadow: 0 0 12px var(--gl-primary-glow);
                    "
                >
                    <BookOpen class="h-3.5 w-3.5" :stroke-width="2" /> Open
                </a>
                <a
                    :href="`/materials/${m.id}/download`"
                    class="flex items-center justify-center gap-1.5 rounded-xl px-3 py-2.5 text-xs font-bold transition-all hover:scale-[1.02]"
                    style="
                        background: var(--gl-surface);
                        color: var(--gl-text-primary);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <Download class="h-3.5 w-3.5" :stroke-width="2" /> Save
                </a>
                <button
                    type="button"
                    @click="toggleRead(m)"
                    class="flex items-center justify-center gap-1 rounded-xl px-2.5 py-2.5 text-xs font-semibold transition-all hover:scale-[1.02]"
                    :style="
                        m.is_read
                            ? {
                                  background: 'var(--gl-surface)',
                                  color: 'var(--gl-text-muted)',
                                  border: '1px solid var(--gl-border)',
                              }
                            : {
                                  background: 'rgba(16, 185, 129, 0.12)',
                                  color: 'var(--gl-success)',
                                  border: '1px solid rgba(16, 185, 129, 0.3)',
                              }
                    "
                    :title="m.is_read ? 'Mark as unread' : 'Mark as read'"
                >
                    <CheckCircle2 v-if="m.is_read" class="h-3.5 w-3.5 text-emerald-500" />
                    <Circle v-else class="h-3.5 w-3.5" />
                    {{ m.is_read ? 'Read' : 'Mark Done' }}
                </button>
            </div>
        </div>
    </div>
    <div
        v-else
        class="gl-glow-card flex flex-col items-center justify-center px-8 py-16 text-center"
    >
        <div
            class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl"
            style="background: var(--gl-surface-2)"
        >
            <FolderOpen
                class="h-7 w-7"
                style="color: var(--gl-text-muted)"
                :stroke-width="1.5"
            />
        </div>
        <p class="text-sm font-medium" style="color: var(--gl-text-secondary)">
            {{
                search || activeType !== 'all'
                    ? 'No materials match your filters.'
                    : currentFolder
                      ? `No materials in ${currentFolder.name} yet.`
                      : 'No materials yet.'
            }}
        </p>
        <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
            {{
                search || activeType !== 'all'
                    ? 'Try different keywords or filters.'
                    : 'Your teachers have not shared any materials in this folder yet.'
            }}
        </p>
    </div>
    </template>
</template>
