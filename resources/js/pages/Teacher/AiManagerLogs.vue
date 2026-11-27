<template>
    <Head title="AI Supervisor Logs — Admin" />

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
                            background: rgba(124, 58, 237, 0.12);
                            color: var(--gl-secondary);
                        "
                    >
                        Autonomous Guardrails
                    </span>
                    <span
                        class="rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase"
                        :style="
                            status.enabled
                                ? { background: 'rgba(16, 185, 129, 0.12)', color: 'var(--gl-success)' }
                                : { background: 'rgba(239, 68, 68, 0.12)', color: 'var(--gl-danger)' }
                        "
                    >
                        {{ status.enabled ? `ONLINE (${status.mode})` : 'DISABLED' }}
                    </span>
                </div>
                <h1
                    class="mt-1.5 text-2xl font-extrabold tracking-tight sm:text-3xl"
                    style="color: var(--gl-text-primary)"
                >
                    AI System Supervisor
                </h1>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    Immutable audit log of autonomous inspections, background auto-grading, self-healing storage, and anomaly watchdog.
                </p>
            </div>

            <!-- Header Quick Actions -->
            <div class="flex flex-wrap items-center gap-2">
                <Link
                    href="/teacher/system-settings"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold transition-all hover:scale-105 active:scale-95"
                    style="
                        background: var(--gl-surface);
                        color: var(--gl-text-secondary);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <Settings :size="14" :stroke-width="2.2" />
                    <span>Manager Settings</span>
                </Link>

                <button
                    type="button"
                    @click="triggerInspection"
                    :disabled="runningInspection"
                    class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-bold text-white transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                    style="
                        background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary));
                        box-shadow: 0 0 16px var(--gl-primary-glow);
                    "
                >
                    <LoaderCircle v-if="runningInspection" :size="14" class="animate-spin" />
                    <Play v-else :size="14" :stroke-width="2.2" />
                    <span>{{ runningInspection ? 'Inspecting...' : 'Run Inspection Now' }}</span>
                </button>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
            <div
                class="rounded-2xl p-4 transition-all"
                style="background: var(--gl-surface); border: 1px solid var(--gl-border)"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold" style="color: var(--gl-text-muted)">Actions Today</span>
                    <Activity :size="16" style="color: var(--gl-primary)" />
                </div>
                <p class="mt-2 text-2xl font-black" style="color: var(--gl-text-primary)">
                    {{ status.stats_today?.total_actions ?? 0 }}
                </p>
                <p class="mt-0.5 text-[10px]" style="color: var(--gl-text-muted)">
                    Total inspections & events
                </p>
            </div>

            <div
                class="rounded-2xl p-4 transition-all"
                style="background: var(--gl-surface); border: 1px solid var(--gl-border)"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold" style="color: var(--gl-text-muted)">Submissions Graded</span>
                    <CheckCircle2 :size="16" style="color: var(--gl-success)" />
                </div>
                <p class="mt-2 text-2xl font-black" style="color: var(--gl-success)">
                    {{ status.stats_today?.auto_graded ?? 0 }}
                </p>
                <p class="mt-0.5 text-[10px]" style="color: var(--gl-text-muted)">
                    Confidence &ge; {{ Math.round((status.confidence_threshold || 0.85) * 100) }}%
                </p>
            </div>

            <div
                class="rounded-2xl p-4 transition-all"
                style="background: var(--gl-surface); border: 1px solid var(--gl-border)"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold" style="color: var(--gl-text-muted)">Files Self-Healed</span>
                    <FileCheck :size="16" style="color: var(--gl-secondary)" />
                </div>
                <p class="mt-2 text-2xl font-black" style="color: var(--gl-secondary)">
                    {{ status.stats_today?.files_healed ?? 0 }}
                </p>
                <p class="mt-0.5 text-[10px]" style="color: var(--gl-text-muted)">
                    Restored from DB binary
                </p>
            </div>

            <div
                class="rounded-2xl p-4 transition-all"
                style="background: var(--gl-surface); border: 1px solid var(--gl-border)"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold" style="color: var(--gl-text-muted)">Flagged For Review</span>
                    <AlertTriangle :size="16" style="color: #f59e0b" />
                </div>
                <p class="mt-2 text-2xl font-black" style="color: #f59e0b">
                    {{ status.stats_today?.flagged_reviews ?? 0 }}
                </p>
                <p class="mt-0.5 text-[10px]" style="color: var(--gl-text-muted)">
                    Needs teacher review
                </p>
            </div>
        </div>

        <!-- Filter Bar -->
        <div
            class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl p-4"
            style="background: var(--gl-surface); border: 1px solid var(--gl-border)"
        >
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold" style="color: var(--gl-text-muted)">Filter Event:</span>
                <button
                    v-for="ev in eventTypes"
                    :key="ev.value"
                    type="button"
                    @click="applyFilter('event_type', ev.value)"
                    class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-all"
                    :style="
                        selectedEventType === ev.value
                            ? { background: 'var(--gl-primary)', color: '#fff' }
                            : { background: 'var(--gl-surface-2)', color: 'var(--gl-text-secondary)' }
                    "
                >
                    {{ ev.label }}
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold" style="color: var(--gl-text-muted)">Status:</span>
                <button
                    v-for="st in statusTypes"
                    :key="st.value"
                    type="button"
                    @click="applyFilter('status', st.value)"
                    class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-all"
                    :style="
                        selectedStatus === st.value
                            ? { background: 'var(--gl-secondary)', color: '#fff' }
                            : { background: 'var(--gl-surface-2)', color: 'var(--gl-text-secondary)' }
                    "
                >
                    {{ st.label }}
                </button>
            </div>
        </div>

        <!-- Audit Table -->
        <div
            class="overflow-hidden rounded-3xl"
            style="background: var(--gl-surface); border: 1px solid var(--gl-border)"
        >
            <div class="hidden lg:block">
                <table class="w-full table-fixed border-collapse text-left text-xs">
                    <colgroup>
                        <col class="w-[13%]" />
                        <col class="w-[14%]" />
                        <col class="w-[15%]" />
                        <col class="w-[30%]" />
                        <col class="w-[9%]" />
                        <col class="w-[9%]" />
                        <col class="w-[10%]" />
                    </colgroup>
                    <thead>
                        <tr style="background: var(--gl-surface-2); border-bottom: 1px solid var(--gl-border)">
                            <th class="px-4 py-3.5 font-bold uppercase tracking-wider" style="color: var(--gl-text-muted)">ID / Time</th>
                            <th class="px-4 py-3.5 font-bold uppercase tracking-wider" style="color: var(--gl-text-muted)">Event Type</th>
                            <th class="px-4 py-3.5 font-bold uppercase tracking-wider" style="color: var(--gl-text-muted)">Target</th>
                            <th class="px-4 py-3.5 font-bold uppercase tracking-wider" style="color: var(--gl-text-muted)">Summary</th>
                            <th class="px-4 py-3.5 font-bold uppercase tracking-wider text-center" style="color: var(--gl-text-muted)">Confidence</th>
                            <th class="px-4 py-3.5 font-bold uppercase tracking-wider text-center" style="color: var(--gl-text-muted)">Status</th>
                            <th class="px-4 py-3.5 font-bold uppercase tracking-wider text-right" style="color: var(--gl-text-muted)">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color: var(--gl-border)">
                        <tr
                            v-for="log in logs.data"
                            :key="log.id"
                            class="transition-colors hover:bg-[rgba(255,255,255,0.02)]"
                        >
                            <td class="break-words px-3 py-3">
                                <div class="font-bold" style="color: var(--gl-text-primary)">#{{ log.id }}</div>
                                <div class="text-[10px]" style="color: var(--gl-text-muted)">{{ formatDate(log.created_at) }}</div>
                            </td>

                            <td class="break-words px-3 py-3">
                                <span
                                    class="inline-block max-w-full rounded-lg px-2 py-1 text-[11px] font-bold"
                                    :style="getEventBadgeStyle(log.event_type)"
                                >
                                    {{ formatEventType(log.event_type) }}
                                </span>
                            </td>

                            <td class="break-words px-3 py-3">
                                <span v-if="log.target_type" class="font-semibold" style="color: var(--gl-text-secondary)">
                                    {{ log.target_type }} #{{ log.target_id }}
                                </span>
                                <span v-else style="color: var(--gl-text-muted)">System daemon</span>
                            </td>

                            <td class="break-words px-3 py-3">
                                <p class="font-medium" style="color: var(--gl-text-primary); overflow-wrap: anywhere">
                                    {{ log.summary }}
                                </p>
                                <div v-if="log.reverted_at" class="mt-1 flex items-center gap-1 text-[10px] text-amber-500 font-bold">
                                    <RotateCcw :size="10" />
                                    <span>Reverted at {{ formatDate(log.reverted_at) }}</span>
                                </div>
                            </td>

                            <td class="px-3 py-3 text-center">
                                <span
                                    v-if="log.confidence !== null"
                                    class="font-black text-xs"
                                    :style="
                                        log.confidence >= 0.85
                                            ? { color: 'var(--gl-success)' }
                                            : { color: '#f59e0b' }
                                    "
                                >
                                    {{ Math.round(log.confidence * 100) }}%
                                </span>
                                <span v-else style="color: var(--gl-text-muted)">—</span>
                            </td>

                            <td class="px-3 py-3 text-center">
                                <span
                                    class="inline-block max-w-full rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase"
                                    :style="getStatusBadgeStyle(log.status)"
                                >
                                    {{ log.status }}
                                </span>
                            </td>

                            <td class="px-3 py-3 text-right">
                                <button
                                    v-if="log.can_revert"
                                    type="button"
                                    @click="revertAction(log.id)"
                                    class="inline-flex max-w-full items-center justify-end gap-1 rounded-lg px-2 py-1 text-[10px] font-bold transition-all hover:scale-105 active:scale-95"
                                    style="
                                        background: rgba(245, 158, 11, 0.12);
                                        color: #f59e0b;
                                        border: 1px solid rgba(245, 158, 11, 0.3);
                                    "
                                >
                                    <RotateCcw :size="12" />
                                    <span>1-Click Revert</span>
                                </button>
                                <span v-else-if="log.reverted_at" class="text-[10px] text-zinc-500 font-semibold">
                                    Rolled back
                                </span>
                                <span v-else style="color: var(--gl-text-muted)">—</span>
                            </td>
                        </tr>

                        <tr v-if="logs.data.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center" style="color: var(--gl-text-muted)">
                                <Bot :size="32" class="mx-auto mb-2 opacity-40" />
                                <p class="text-sm font-semibold">No AI Manager log entries found</p>
                                <p class="text-xs mt-0.5">Click "Run Inspection Now" or wait for scheduled background runs.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile audit rows: stacked to avoid horizontal scrolling -->
            <div class="space-y-2 p-3 lg:hidden">
                <div
                    v-for="log in logs.data"
                    :key="`mobile-${log.id}`"
                    class="rounded-2xl p-3"
                    style="background: var(--gl-surface-2); border: 1px solid var(--gl-border)"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-bold" style="color: var(--gl-text-primary)">#{{ log.id }}</span>
                                <span class="text-[10px]" style="color: var(--gl-text-muted)">{{ formatDate(log.created_at) }}</span>
                            </div>
                            <span
                                class="mt-2 inline-block max-w-full rounded-lg px-2 py-1 text-[10px] font-bold"
                                :style="getEventBadgeStyle(log.event_type)"
                            >
                                {{ formatEventType(log.event_type) }}
                            </span>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-black uppercase"
                            :style="getStatusBadgeStyle(log.status)"
                        >
                            {{ log.status }}
                        </span>
                    </div>

                    <div class="mt-3 space-y-2 text-[11px]">
                        <div>
                            <span class="font-bold uppercase tracking-wide" style="color: var(--gl-text-muted)">Target</span>
                            <p class="break-words font-semibold" style="color: var(--gl-text-secondary)">
                                {{ log.target_type ? `${log.target_type} #${log.target_id}` : 'System daemon' }}
                            </p>
                        </div>
                        <div>
                            <span class="font-bold uppercase tracking-wide" style="color: var(--gl-text-muted)">Summary</span>
                            <p class="break-words" style="color: var(--gl-text-primary); overflow-wrap: anywhere">
                                {{ log.summary }}
                            </p>
                            <div v-if="log.reverted_at" class="mt-1 flex items-center gap-1 text-[10px] font-bold text-amber-500">
                                <RotateCcw :size="10" />
                                <span>Reverted at {{ formatDate(log.reverted_at) }}</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2 border-t pt-2" style="border-color: var(--gl-border)">
                            <span style="color: var(--gl-text-muted)">
                                Confidence:
                                <strong v-if="log.confidence !== null" :style="log.confidence >= 0.85 ? { color: 'var(--gl-success)' } : { color: '#f59e0b' }">
                                    {{ Math.round(log.confidence * 100) }}%
                                </strong>
                                <strong v-else style="color: var(--gl-text-muted)">—</strong>
                            </span>
                            <button
                                v-if="log.can_revert"
                                type="button"
                                @click="revertAction(log.id)"
                                class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-[10px] font-bold"
                                style="background: rgba(245, 158, 11, 0.12); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3)"
                            >
                                <RotateCcw :size="11" /> Revert
                            </button>
                            <span v-else-if="log.reverted_at" class="text-[10px] font-semibold text-zinc-500">Rolled back</span>
                        </div>
                    </div>
                </div>

                <div v-if="logs.data.length === 0" class="px-4 py-12 text-center" style="color: var(--gl-text-muted)">
                    <Bot :size="32" class="mx-auto mb-2 opacity-40" />
                    <p class="text-sm font-semibold">No AI Manager log entries found</p>
                    <p class="mt-0.5 text-xs">Click "Run Inspection Now" or wait for scheduled background runs.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="logs.links && logs.links.length > 3"
                class="flex items-center justify-between p-4 border-t"
                style="border-color: var(--gl-border)"
            >
                <div class="text-xs" style="color: var(--gl-text-muted)">
                    Showing {{ logs.from ?? 0 }} to {{ logs.to ?? 0 }} of {{ logs.total }} records
                </div>
                <div class="flex items-center gap-1">
                    <Link
                        v-for="(link, idx) in logs.links"
                        :key="idx"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-all"
                        :class="!link.url ? 'opacity-40 pointer-events-none' : ''"
                        :style="
                            link.active
                                ? { background: 'var(--gl-primary)', color: '#fff' }
                                : { background: 'var(--gl-surface-2)', color: 'var(--gl-text-secondary)' }
                        "
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Activity,
    AlertTriangle,
    Bot,
    CheckCircle2,
    FileCheck,
    LoaderCircle,
    Play,
    RotateCcw,
    Settings,
} from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{
    logs: {
        data: Array<{
            id: number;
            event_type: string;
            status: string;
            target_type: string | null;
            target_id: number | null;
            confidence: number | null;
            summary: string;
            payload: any;
            can_revert: boolean;
            reverted_at: string | null;
            created_at: string;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number;
        to: number;
        total: number;
    };
    filters: {
        status: string;
        event_type: string;
    };
    status: {
        enabled: boolean;
        mode: string;
        interval_minutes: number;
        confidence_threshold: number;
        auto_grade_enabled: boolean;
        self_heal_enabled: boolean;
        anomaly_detection_enabled: boolean;
        last_inspection?: {
            created_at: string;
            summary: string;
            status: string;
        };
        stats_today?: {
            total_actions: number;
            auto_graded: number;
            files_healed: number;
            flagged_reviews: number;
            reverted: number;
        };
    };
}>();

const page = usePage();
const flash = computed(() => (page.props as any).flash);
const runningInspection = ref(false);

const selectedEventType = ref(props.filters.event_type || 'all');
const selectedStatus = ref(props.filters.status || 'all');

const eventTypes = [
    { value: 'all', label: 'All Events' },
    { value: 'inspection', label: 'Inspections' },
    { value: 'auto_grade', label: 'Auto-Grading' },
    { value: 'storage_heal', label: 'Storage Heal' },
    { value: 'anomaly_flag', label: 'Anomalies' },
    { value: 'game_reopen', label: 'Game Reopens' },
];

const statusTypes = [
    { value: 'all', label: 'All Statuses' },
    { value: 'success', label: 'Success' },
    { value: 'flagged_for_review', label: 'Flagged Review' },
    { value: 'failed', label: 'Failed' },
];

function applyFilter(key: 'event_type' | 'status', value: string) {
    if (key === 'event_type') {
selectedEventType.value = value;
}

    if (key === 'status') {
selectedStatus.value = value;
}

    router.get(
        '/teacher/ai-manager',
        {
            event_type: selectedEventType.value,
            status: selectedStatus.value,
        },
        { preserveState: true, preserveScroll: true }
    );
}

function triggerInspection() {
    runningInspection.value = true;
    router.post('/teacher/ai-manager/run', {}, {
        onFinish: () => {
            runningInspection.value = false;
        },
    });
}

function revertAction(id: number) {
    if (!confirm(`Are you sure you want to revert AI action #${id} to its previous score state?`)) {
        return;
    }

    router.post(`/teacher/ai-manager/logs/${id}/revert`, {}, {
        preserveScroll: true,
    });
}

function formatDate(dateStr?: string | null): string {
    if (!dateStr) {
return '—';
}

    const d = new Date(dateStr);

    return d.toLocaleString([], {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function formatEventType(type: string): string {
    switch (type) {
        case 'inspection': return 'Inspection';
        case 'auto_grade': return 'Auto Grade';
        case 'storage_heal': return 'Storage Heal';
        case 'anomaly_flag': return 'Anomaly Flag';
        case 'game_reopen': return 'Game Reopen';
        default: return type;
    }
}

function getEventBadgeStyle(type: string) {
    switch (type) {
        case 'inspection':
            return { background: 'rgba(59, 130, 246, 0.12)', color: 'var(--gl-primary)' };
        case 'auto_grade':
            return { background: 'rgba(16, 185, 129, 0.12)', color: 'var(--gl-success)' };
        case 'storage_heal':
            return { background: 'rgba(124, 58, 237, 0.12)', color: 'var(--gl-secondary)' };
        case 'anomaly_flag':
            return { background: 'rgba(245, 158, 11, 0.12)', color: '#f59e0b' };
        case 'game_reopen':
            return { background: 'rgba(236, 72, 153, 0.12)', color: '#ec4899' };
        default:
            return { background: 'var(--gl-surface-2)', color: 'var(--gl-text-secondary)' };
    }
}

function getStatusBadgeStyle(status: string) {
    switch (status) {
        case 'success':
            return { background: 'rgba(16, 185, 129, 0.12)', color: 'var(--gl-success)' };
        case 'flagged_for_review':
            return { background: 'rgba(245, 158, 11, 0.12)', color: '#f59e0b' };
        case 'failed':
            return { background: 'rgba(239, 68, 68, 0.12)', color: 'var(--gl-danger)' };
        default:
            return { background: 'var(--gl-surface-2)', color: 'var(--gl-text-muted)' };
    }
}
</script>
