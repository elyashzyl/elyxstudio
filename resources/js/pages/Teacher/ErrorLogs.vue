<template>
    <Head title="Error Logs — Admin" />

    <div class="px-4 py-4 sm:px-6 sm:py-6">
        <!-- Flash Message -->
        <div
            v-if="flash?.success"
            class="gl-fade-in mb-6 rounded-2xl px-5 py-3.5 text-sm font-medium"
            style="
                background: rgba(16, 185, 129, 0.12);
                border: 1px solid rgba(16, 185, 129, 0.3);
                color: var(--gl-success);
            "
        >
            {{ flash.success }}
        </div>
        <div
            v-if="flash?.error"
            class="gl-fade-in mb-6 rounded-2xl px-5 py-3.5 text-sm font-medium"
            style="
                background: var(--gl-danger-bg);
                border: 1px solid rgba(239, 68, 68, 0.3);
                color: var(--gl-danger);
            "
        >
            {{ flash.error }}
        </div>

        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span
                        class="rounded-full px-2.5 py-0.5 text-[10px] font-bold tracking-wider uppercase"
                        style="
                            background: rgba(239, 68, 68, 0.12);
                            color: var(--gl-danger);
                        "
                    >
                        Diagnostics & Exceptions
                    </span>
                    <span class="text-[11px] font-medium" style="color: var(--gl-text-muted)">
                        {{ stats.file_name }} ({{ stats.file_size }})
                    </span>
                </div>
                <h1
                    class="mt-1.5 text-2xl font-extrabold tracking-tight sm:text-3xl"
                    style="color: var(--gl-text-primary)"
                >
                    System Error Logs
                </h1>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    Inspect application exceptions, runtime errors, and Monolog stack traces captured in real time.
                </p>
            </div>

            <!-- Header Quick Actions -->
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    @click="triggerTestLog"
                    :disabled="triggeringTest"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                    style="
                        background: var(--gl-surface);
                        color: var(--gl-text-secondary);
                        border: 1px solid var(--gl-border);
                    "
                    title="Write sample error and warning to log file"
                >
                    <Bug :size="14" :stroke-width="2.2" />
                    <span>{{ triggeringTest ? 'Generating...' : 'Trigger Test Log' }}</span>
                </button>

                <a
                    :href="`/teacher/error-logs/download?file=${encodeURIComponent(selectedFile)}`"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold transition-all hover:scale-105 active:scale-95"
                    style="
                        background: var(--gl-surface);
                        color: var(--gl-text-secondary);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <Download :size="14" :stroke-width="2.2" />
                    <span>Download Raw</span>
                </a>

                <button
                    type="button"
                    @click="refreshLogs"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold transition-all hover:scale-105 active:scale-95"
                    style="
                        background: var(--gl-surface);
                        color: var(--gl-text-secondary);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <RefreshCw :size="14" :stroke-width="2.2" :class="{ 'animate-spin': refreshing }" />
                    <span>Refresh</span>
                </button>

                <button
                    type="button"
                    @click="showClearModal = true"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold transition-all hover:scale-105 active:scale-95"
                    style="
                        background: var(--gl-danger-bg);
                        color: var(--gl-danger);
                        border: 1px solid rgba(239, 68, 68, 0.3);
                    "
                >
                    <Trash2 :size="14" :stroke-width="2.2" />
                    <span>Clear Log</span>
                </button>
            </div>
        </div>

        <!-- Stat Cards Grid -->
        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
            <div
                class="rounded-2xl p-4 transition-all"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider" style="color: var(--gl-text-muted)">
                        Total Entries
                    </p>
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl"
                        style="background: var(--gl-surface-2)"
                    >
                        <FileText :size="16" style="color: var(--gl-text-secondary)" />
                    </div>
                </div>
                <p class="mt-2 text-2xl font-black tabular-nums" style="color: var(--gl-text-primary)">
                    {{ stats.total }}
                </p>
                <p class="mt-0.5 text-[11px]" style="color: var(--gl-text-muted)">
                    Parsed from {{ stats.file_name }}
                </p>
            </div>

            <div
                class="rounded-2xl p-4 transition-all"
                style="
                    background: var(--gl-surface);
                    border: 1px solid rgba(239, 68, 68, 0.3);
                "
            >
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider" style="color: var(--gl-danger)">
                        Errors & Critical
                    </p>
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl"
                        style="background: var(--gl-danger-bg)"
                    >
                        <TriangleAlert :size="16" style="color: var(--gl-danger)" />
                    </div>
                </div>
                <p class="mt-2 text-2xl font-black tabular-nums" style="color: var(--gl-danger)">
                    {{ stats.errors }}
                </p>
                <p class="mt-0.5 text-[11px]" style="color: var(--gl-text-muted)">
                    Requires review
                </p>
            </div>

            <div
                class="rounded-2xl p-4 transition-all"
                style="
                    background: var(--gl-surface);
                    border: 1px solid rgba(245, 158, 11, 0.3);
                "
            >
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider" style="color: var(--gl-accent)">
                        Warnings
                    </p>
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl"
                        style="background: rgba(245, 158, 11, 0.12)"
                    >
                        <AlertCircle :size="16" style="color: var(--gl-accent)" />
                    </div>
                </div>
                <p class="mt-2 text-2xl font-black tabular-nums" style="color: var(--gl-accent)">
                    {{ stats.warnings }}
                </p>
                <p class="mt-0.5 text-[11px]" style="color: var(--gl-text-muted)">
                    Notices and warnings
                </p>
            </div>

            <div
                class="rounded-2xl p-4 transition-all"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider" style="color: var(--gl-text-muted)">
                        File Size & Age
                    </p>
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl"
                        style="background: var(--gl-surface-2)"
                    >
                        <HardDrive :size="16" style="color: var(--gl-text-secondary)" />
                    </div>
                </div>
                <p class="mt-2 text-2xl font-black tabular-nums" style="color: var(--gl-text-primary)">
                    {{ stats.file_size }}
                </p>
                <p class="mt-0.5 text-[11px]" style="color: var(--gl-text-muted)">
                    Modified {{ stats.last_modified }}
                </p>
            </div>
        </div>

        <!-- Filter Controls -->
        <div
            class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl p-4"
            style="
                background: var(--gl-surface);
                border: 1px solid var(--gl-border);
            "
        >
            <div class="flex flex-1 flex-wrap items-center gap-2.5">
                <!-- Search Input -->
                <div class="relative min-w-[220px] flex-1 max-w-md">
                    <Search
                        :size="15"
                        class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2"
                        style="color: var(--gl-text-muted)"
                    />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search error messages or trace..."
                        class="w-full rounded-xl border py-2 pr-3 pl-9 text-xs outline-none transition-all"
                        style="
                            background: var(--gl-surface-2);
                            border-color: var(--gl-border);
                            color: var(--gl-text-primary);
                        "
                        @keydown.enter="applyFilters"
                    />
                </div>

                <!-- Level Filter Pills -->
                <div class="flex flex-wrap items-center gap-1.5">
                    <button
                        v-for="lvl in levelTabs"
                        :key="lvl.id"
                        type="button"
                        @click="setLevel(lvl.id)"
                        class="rounded-xl px-3 py-1.5 text-xs font-bold transition-all"
                        :style="
                            currentLevel === lvl.id
                                ? {
                                      background: lvl.activeBg,
                                      color: lvl.activeColor,
                                      border: '1px solid ' + lvl.activeBorder,
                                  }
                                : {
                                      background: 'var(--gl-surface-2)',
                                      color: 'var(--gl-text-secondary)',
                                      border: '1px solid var(--gl-border)',
                                  }
                        "
                    >
                        {{ lvl.label }}
                    </button>
                </div>
            </div>

            <!-- File Selector -->
            <div v-if="files.length > 1" class="flex items-center gap-2">
                <span class="text-xs font-semibold" style="color: var(--gl-text-muted)">Log File:</span>
                <select
                    v-model="selectedFile"
                    @change="applyFilters"
                    class="rounded-xl border px-3 py-1.5 text-xs font-bold outline-none"
                    style="
                        background: var(--gl-surface-2);
                        border-color: var(--gl-border);
                        color: var(--gl-text-primary);
                    "
                >
                    <option v-for="f in files" :key="f.name" :value="f.name">
                        {{ f.name }} ({{ f.formatted_size }})
                    </option>
                </select>
            </div>
        </div>

        <!-- Log Entries List -->
        <div v-if="!entries.length" class="rounded-3xl p-12 text-center" style="background: var(--gl-surface); border: 1px solid var(--gl-border);">
            <div
                class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl"
                style="background: rgba(16, 185, 129, 0.12)"
            >
                <CheckCircle2 :size="28" style="color: var(--gl-success)" :stroke-width="2" />
            </div>
            <h3 class="text-base font-extrabold" style="color: var(--gl-text-primary)">
                No Log Entries Found
            </h3>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                {{ searchQuery || currentLevel !== 'all' ? 'No log records match your active search and filter criteria.' : 'The selected log file is clean and empty. No recorded errors at this time.' }}
            </p>
            <div v-if="searchQuery || currentLevel !== 'all'" class="mt-4">
                <button
                    type="button"
                    @click="resetFilters"
                    class="rounded-xl px-4 py-2 text-xs font-bold"
                    style="background: var(--gl-surface-2); color: var(--gl-text-primary); border: 1px solid var(--gl-border);"
                >
                    Reset Filters
                </button>
            </div>
        </div>

        <div v-else class="space-y-3">
            <div
                v-for="entry in entries"
                :key="entry.id"
                class="overflow-hidden rounded-2xl transition-all"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <!-- Entry Summary Row -->
                <div class="p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Level badge -->
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-black tracking-wider uppercase"
                                :style="levelBadgeStyle(entry.level)"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :style="{ background: levelDotColor(entry.level) }"></span>
                                {{ entry.level }}
                            </span>

                            <span
                                class="rounded-md px-2 py-0.5 text-[10px] font-mono font-bold uppercase"
                                style="background: var(--gl-surface-2); color: var(--gl-text-muted)"
                            >
                                {{ entry.environment }}
                            </span>

                            <span
                                v-if="entry.file"
                                class="rounded-md px-2 py-0.5 text-[10px] font-mono"
                                style="background: var(--gl-surface-2); color: var(--gl-text-secondary)"
                            >
                                {{ entry.file }}:{{ entry.line }}
                            </span>
                        </div>

                        <div class="flex items-center gap-3 text-xs" style="color: var(--gl-text-muted)">
                            <span class="font-medium">{{ entry.timestamp }}</span>
                            <span v-if="entry.time_ago" class="rounded-full px-2 py-0.5 text-[10px]" style="background: var(--gl-surface-2)">
                                {{ entry.time_ago }}
                            </span>
                        </div>
                    </div>

                    <!-- Message Body -->
                    <p class="mt-3 font-mono text-xs font-semibold leading-relaxed break-words" style="color: var(--gl-text-primary)">
                        {{ entry.message }}
                    </p>

                    <!-- Expand / Details Bar -->
                    <div v-if="entry.has_trace" class="mt-3 flex items-center justify-between gap-3 border-t pt-3" style="border-color: var(--gl-border)">
                        <button
                            type="button"
                            @click="toggleExpand(entry.id)"
                            class="inline-flex items-center gap-1.5 text-xs font-bold transition-colors hover:opacity-80"
                            style="color: var(--gl-primary)"
                        >
                            <ChevronDown v-if="expandedIds.has(entry.id)" :size="14" />
                            <ChevronRight v-else :size="14" />
                            <span>{{ expandedIds.has(entry.id) ? 'Hide Stack Trace' : 'View Stack Trace' }}</span>
                        </button>

                        <button
                            type="button"
                            @click="copyTrace(entry)"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[11px] font-semibold transition-colors"
                            style="background: var(--gl-surface-2); color: var(--gl-text-secondary)"
                        >
                            <Check v-if="copiedId === entry.id" :size="13" style="color: var(--gl-success)" />
                            <Copy v-else :size="13" />
                            <span>{{ copiedId === entry.id ? 'Copied' : 'Copy Trace' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Expanded Stack Trace -->
                <div
                    v-if="expandedIds.has(entry.id) && entry.has_trace"
                    class="border-t p-4"
                    style="
                        background: #0d1117;
                        border-color: var(--gl-border);
                    "
                >
                    <div class="mb-2 flex items-center justify-between text-[11px] font-bold" style="color: #8b949e">
                        <span>Stack Trace</span>
                        <span v-if="entry.full_file" class="truncate font-mono">{{ entry.full_file }}</span>
                    </div>
                    <pre
                        class="max-h-96 overflow-y-auto rounded-xl p-3 font-mono text-[11px] leading-relaxed whitespace-pre-wrap select-text"
                        style="color: #e6edf3; background: #161b22; border: 1px solid #30363d;"
                    >{{ entry.stack_trace }}</pre>
                </div>
            </div>
        </div>

        <!-- Clear Log Confirmation Modal -->
        <Teleport to="body">
            <div
                v-if="showClearModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
                @click.self="showClearModal = false"
            >
                <div
                    class="w-full max-w-md rounded-3xl p-6 shadow-2xl"
                    style="
                        background: var(--gl-surface);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <div
                        class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl"
                        style="background: var(--gl-danger-bg)"
                    >
                        <Trash2 :size="24" style="color: var(--gl-danger)" />
                    </div>

                    <h3 class="text-center text-lg font-extrabold" style="color: var(--gl-text-primary)">
                        Clear Error Log?
                    </h3>
                    <p class="mt-2 text-center text-xs leading-relaxed" style="color: var(--gl-text-muted)">
                        This will truncate all entries from <strong>{{ selectedFile }}</strong>. This action is recorded in the activity audit trail and cannot be undone.
                    </p>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="showClearModal = false"
                            class="rounded-xl px-4 py-2.5 text-xs font-bold"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="confirmClear"
                            :disabled="clearing"
                            class="rounded-xl px-5 py-2.5 text-xs font-bold text-white transition-all hover:scale-105 disabled:opacity-50"
                            style="background: var(--gl-danger)"
                        >
                            {{ clearing ? 'Clearing...' : 'Yes, Clear Log' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    Bug,
    Check,
    CheckCircle2,
    ChevronDown,
    ChevronRight,
    Copy,
    Download,
    FileText,
    HardDrive,
    RefreshCw,
    Search,
    Trash2,
    TriangleAlert,
} from '@lucide/vue';
import { computed, reactive, ref } from 'vue';

const props = defineProps<{
    entries: any[];
    files: any[];
    stats: any;
    filters: {
        file: string;
        level: string;
        search: string;
    };
}>();

const page = usePage();
const flash = computed(() => (page.props as any).flash);

const selectedFile = ref(props.filters.file || 'laravel.log');
const currentLevel = ref(props.filters.level || 'all');
const searchQuery = ref(props.filters.search || '');

const refreshing = ref(false);
const clearing = ref(false);
const triggeringTest = ref(false);
const showClearModal = ref(false);
const expandedIds = reactive(new Set<string>());
const copiedId = ref<string | null>(null);

const levelTabs = [
    {
        id: 'all',
        label: 'All Levels',
        activeBg: 'var(--gl-primary)',
        activeColor: '#fff',
        activeBorder: 'transparent',
    },
    {
        id: 'error',
        label: 'Errors & Critical',
        activeBg: 'rgba(239, 68, 68, 0.16)',
        activeColor: 'var(--gl-danger)',
        activeBorder: 'rgba(239, 68, 68, 0.3)',
    },
    {
        id: 'warning',
        label: 'Warnings',
        activeBg: 'rgba(245, 158, 11, 0.16)',
        activeColor: 'var(--gl-accent)',
        activeBorder: 'rgba(245, 158, 11, 0.3)',
    },
    {
        id: 'info',
        label: 'Info & Notice',
        activeBg: 'rgba(59, 130, 246, 0.16)',
        activeColor: '#3b82f6',
        activeBorder: 'rgba(59, 130, 246, 0.3)',
    },
    {
        id: 'debug',
        label: 'Debug',
        activeBg: 'var(--gl-surface-2)',
        activeColor: 'var(--gl-text-primary)',
        activeBorder: 'var(--gl-border)',
    },
];

function setLevel(lvl: string) {
    currentLevel.value = lvl;
    applyFilters();
}

function applyFilters() {
    router.get(
        '/teacher/error-logs',
        {
            file: selectedFile.value,
            level: currentLevel.value,
            search: searchQuery.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}

function resetFilters() {
    searchQuery.value = '';
    currentLevel.value = 'all';
    applyFilters();
}

function refreshLogs() {
    refreshing.value = true;
    router.reload({
        onFinish: () => {
            refreshing.value = false;
        },
    });
}

function toggleExpand(id: string) {
    if (expandedIds.has(id)) {
        expandedIds.delete(id);
    } else {
        expandedIds.add(id);
    }
}

async function copyTrace(entry: any) {
    const textToCopy = `${entry.level}: ${entry.message}\n${entry.stack_trace}`;

    try {
        await navigator.clipboard.writeText(textToCopy);
        copiedId.value = entry.id;
        setTimeout(() => {
            if (copiedId.value === entry.id) {
                copiedId.value = null;
            }
        }, 2000);
    } catch {
        // fallback
    }
}

function triggerTestLog() {
    triggeringTest.value = true;
    router.post(
        '/teacher/error-logs/test',
        {},
        {
            onFinish: () => {
                triggeringTest.value = false;
            },
        },
    );
}

function confirmClear() {
    clearing.value = true;
    router.post(
        '/teacher/error-logs/clear',
        { file: selectedFile.value },
        {
            onFinish: () => {
                clearing.value = false;
                showClearModal.value = false;
            },
        },
    );
}

function levelBadgeStyle(level: string): Record<string, string> {
    switch (level) {
        case 'EMERGENCY':
        case 'ALERT':
        case 'CRITICAL':
        case 'ERROR':
            return {
                background: 'rgba(239, 68, 68, 0.14)',
                color: 'var(--gl-danger)',
                border: '1px solid rgba(239, 68, 68, 0.3)',
            };
        case 'WARNING':
            return {
                background: 'rgba(245, 158, 11, 0.14)',
                color: 'var(--gl-accent)',
                border: '1px solid rgba(245, 158, 11, 0.3)',
            };
        case 'NOTICE':
        case 'INFO':
            return {
                background: 'rgba(59, 130, 246, 0.14)',
                color: '#3b82f6',
                border: '1px solid rgba(59, 130, 246, 0.3)',
            };
        case 'DEBUG':
        default:
            return {
                background: 'var(--gl-surface-2)',
                color: 'var(--gl-text-muted)',
                border: '1px solid var(--gl-border)',
            };
    }
}

function levelDotColor(level: string): string {
    switch (level) {
        case 'EMERGENCY':
        case 'ALERT':
        case 'CRITICAL':
        case 'ERROR':
            return 'var(--gl-danger)';
        case 'WARNING':
            return 'var(--gl-accent)';
        case 'NOTICE':
        case 'INFO':
            return '#3b82f6';
        default:
            return 'var(--gl-text-muted)';
    }
}
</script>
