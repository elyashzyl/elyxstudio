<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    CheckSquare,
    CheckCircle2,
    Circle,
    Clock,
    FileQuestion,
    FileText,
    ClipboardList,
    FlaskConical,
    BookOpen,
    ArrowRight,
    ArrowLeft,
    ExternalLink,
    Filter,
    Calendar,
    Sparkles,
    Trash2,
    RotateCcw,
    Archive,
} from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{
    pendingTodos: any[];
    completedTodos: any[];
    dismissedTodos?: any[];
    hasHomeworkPass?: boolean;
    homeworkPassCount?: number;
    stats: {
        total: number;
        pending: number;
        completed: number;
        dismissed?: number;
        due_soon: number;
        rate: number;
    };
}>();

const applyingPassId = ref<string | null>(null);
const PASSABLE_TYPES = ['Quiz', 'Seatwork', 'Task', 'Practical'];
const passCount = computed(() => props.homeworkPassCount ?? (props.hasHomeworkPass ? 1 : 0));

function canUsePass(item: any): boolean {
    return !item.completed && passCount.value > 0 && PASSABLE_TYPES.includes(item.type);
}

function useHomeworkPass(item: any) {
    if (applyingPassId.value) {
return;
}

    if (!confirm(`Use a Homework Pass on "${item.title}"? It will be completed with 100% full marks. (${passCount.value} pass${passCount.value === 1 ? '' : 'es'} available)`)) {
return;
}

    applyingPassId.value = item.id;
    router.post(
        '/student/rewards/use-homework-pass',
        {
            type: String(item.type).toLowerCase(),
            id: item.source_id,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                applyingPassId.value = null;
            },
        }
    );
}

const page = usePage();
const flash = computed(() => (page.props as any).flash);

const activeTab = ref<'pending' | 'completed' | 'dismissed'>('pending');
const selectedType = ref('all');

const typeFilters = [
    { value: 'all', label: 'All Types' },
    { value: 'activity', label: 'Activities' },
    { value: 'material', label: 'Reading Materials' },
    { value: 'task', label: 'Assigned Tasks' },
];

const currentList = computed(() => {
    let list = props.pendingTodos;

    if (activeTab.value === 'completed') {
list = props.completedTodos;
}

    if (activeTab.value === 'dismissed') {
list = props.dismissedTodos ?? [];
}

    if (selectedType.value === 'all') {
return list;
}

    if (selectedType.value === 'activity') {
        return list.filter((item) =>
            ['Quiz', 'Seatwork', 'Exam', 'Practical'].includes(item.type),
        );
    }

    if (selectedType.value === 'material') {
        return list.filter((item) => item.type === 'Material');
    }

    if (selectedType.value === 'task') {
        return list.filter((item) => item.type === 'Task');
    }

    return list;
});

function getTypeConfig(type: string) {
    switch (type) {
        case 'Quiz':
            return {
                icon: FileQuestion,
                color: '#3B82F6',
                bg: 'rgba(59, 130, 246, 0.12)',
            };
        case 'Exam':
            return {
                icon: FileText,
                color: '#8B5CF6',
                bg: 'rgba(139, 92, 246, 0.12)',
            };
        case 'Seatwork':
            return {
                icon: ClipboardList,
                color: '#10B981',
                bg: 'rgba(16, 185, 129, 0.12)',
            };
        case 'Practical':
            return {
                icon: FlaskConical,
                color: '#F59E0B',
                bg: 'rgba(245, 158, 11, 0.12)',
            };
        case 'Material':
            return {
                icon: BookOpen,
                color: '#EC4899',
                bg: 'rgba(236, 72, 153, 0.12)',
            };
        case 'Task':
            return {
                icon: CheckSquare,
                color: '#10B981',
                bg: 'rgba(16, 185, 129, 0.12)',
            };
        default:
            return {
                icon: CheckSquare,
                color: '#3B82F6',
                bg: 'rgba(59, 130, 246, 0.12)',
            };
    }
}

function toggleItem(item: any) {
    if (!item.toggle_url) {
return;
}

    router.post(
        item.toggle_url,
        {},
        {
            preserveScroll: true,
        },
    );
}

function dismissItem(item: any) {
    if (!confirm(`Dismiss "${item.title}" from your active to-do list? You can view or restore it anytime in the Dismissed tab.`)) {
return;
}

    router.post(
        '/student/todos/dismiss',
        { todo_key: item.dismiss_key || item.id },
        { preserveScroll: true }
    );
}

function restoreItem(item: any) {
    router.post(
        '/student/todos/restore',
        { todo_key: item.dismiss_key || item.id },
        { preserveScroll: true }
    );
}
</script>

<template>
    <Head title="To-Do Center" />

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
        <div
            v-if="flash?.info"
            class="gl-fade-in rounded-2xl px-5 py-3 text-sm font-medium"
            style="
                background: rgba(59, 130, 246, 0.12);
                border: 1px solid rgba(59, 130, 246, 0.2);
                color: var(--gl-primary);
            "
        >
            {{ flash.info }}
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
                        My Academic To-Do Center
                    </h1>
                    <p class="text-xs" style="color: var(--gl-text-muted)">
                        All assigned quizzes, exams, reading materials, and class tasks in one place
                    </p>
                </div>
            </div>

            <Link
                href="/student/dashboard"
                class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold transition-all hover:bg-[var(--gl-surface-2)]"
                style="color: var(--gl-text-secondary); border: 1px solid var(--gl-border)"
            >
                <ArrowLeft class="h-3.5 w-3.5" />
                Back to Dashboard
            </Link>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="gl-glow-card p-4 rounded-2xl">
                <p class="text-xs font-semibold" style="color: var(--gl-text-muted)">Total Assigned</p>
                <p class="text-2xl font-bold mt-1" style="color: var(--gl-text-primary)">{{ stats.total }}</p>
                <p class="text-[11px] mt-0.5" style="color: var(--gl-text-secondary)">Across all classes</p>
            </div>
            <div class="gl-glow-card p-4 rounded-2xl">
                <p class="text-xs font-semibold" style="color: var(--gl-text-muted)">Pending Work</p>
                <p class="text-2xl font-bold mt-1" style="color: #3B82F6">{{ stats.pending }}</p>
                <p class="text-[11px] mt-0.5" style="color: var(--gl-text-secondary)">Items needing action</p>
            </div>
            <div class="gl-glow-card p-4 rounded-2xl">
                <p class="text-xs font-semibold" style="color: var(--gl-text-muted)">Completed</p>
                <p class="text-2xl font-bold mt-1" style="color: var(--gl-success)">{{ stats.completed }}</p>
                <p class="text-[11px] mt-0.5" style="color: var(--gl-text-secondary)">Finished activities & tasks</p>
            </div>
            <div class="gl-glow-card p-4 rounded-2xl">
                <div class="flex items-center justify-between text-xs font-semibold">
                    <span style="color: var(--gl-text-muted)">Completion Rate</span>
                    <span class="font-bold" style="color: var(--gl-primary)">{{ stats.rate }}%</span>
                </div>
                <div class="h-2 w-full rounded-full overflow-hidden mt-3" style="background: var(--gl-surface-2)">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :style="{
                            width: stats.rate + '%',
                            background: 'linear-gradient(90deg, var(--gl-primary), var(--gl-success))',
                        }"
                    ></div>
                </div>
                <p class="text-[10px] mt-2" style="color: var(--gl-text-muted)">
                    {{ stats.due_soon > 0 ? `${stats.due_soon} item(s) due soon` : 'No urgent deadlines' }}
                </p>
            </div>
        </div>

        <!-- Homework Pass Banner -->
        <div
            v-if="passCount > 0 && activeTab === 'pending'"
            class="gl-glow-card flex items-center justify-between gap-3 p-4 rounded-2xl border border-amber-400/30 bg-amber-500/10 text-xs text-amber-700 dark:text-amber-300 font-medium"
        >
            <div class="flex items-center gap-2.5">
                <span class="text-xl">🎟️</span>
                <span>You have <strong>{{ passCount }} Homework Pass{{ passCount === 1 ? '' : 'es' }}</strong>! Click <strong>"Use Pass"</strong> on any pending quiz, seatwork, performance task, or task to complete it instantly with 100% full score.</span>
            </div>
        </div>

        <!-- Tabs and Filter bar -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <!-- Tabs -->
            <div
                class="flex rounded-2xl p-1"
                style="background: var(--gl-surface-2); border: 1px solid var(--gl-border)"
            >
                <button
                    type="button"
                    @click="activeTab = 'pending'"
                    class="rounded-xl px-4 py-2 text-xs font-bold transition-all flex items-center gap-2"
                    :style="
                        activeTab === 'pending'
                            ? {
                                  background: 'var(--gl-surface)',
                                  color: 'var(--gl-primary)',
                                  boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
                              }
                            : { color: 'var(--gl-text-muted)' }
                    "
                >
                    <Clock class="h-3.5 w-3.5" />
                    Pending ({{ pendingTodos.length }})
                </button>
                <button
                    type="button"
                    @click="activeTab = 'completed'"
                    class="rounded-xl px-4 py-2 text-xs font-bold transition-all flex items-center gap-2"
                    :style="
                        activeTab === 'completed'
                            ? {
                                  background: 'var(--gl-surface)',
                                  color: 'var(--gl-success)',
                                  boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
                              }
                            : { color: 'var(--gl-text-muted)' }
                    "
                >
                    <CheckCircle2 class="h-3.5 w-3.5" />
                    Completed ({{ completedTodos.length }})
                </button>
                <button
                    type="button"
                    @click="activeTab = 'dismissed'"
                    class="rounded-xl px-4 py-2 text-xs font-bold transition-all flex items-center gap-2"
                    :style="
                        activeTab === 'dismissed'
                            ? {
                                  background: 'var(--gl-surface)',
                                  color: 'var(--gl-text-muted)',
                                  boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
                              }
                            : { color: 'var(--gl-text-muted)' }
                    "
                >
                    <Archive class="h-3.5 w-3.5" />
                    Dismissed ({{ dismissedTodos?.length || 0 }})
                </button>
            </div>

            <!-- Type Filter pills -->
            <div class="flex flex-wrap items-center gap-1.5">
                <button
                    v-for="t in typeFilters"
                    :key="t.value"
                    type="button"
                    @click="selectedType = t.value"
                    class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-all"
                    :style="
                        selectedType === t.value
                            ? {
                                  background: 'var(--gl-primary)',
                                  color: '#fff',
                                  boxShadow: '0 0 10px var(--gl-primary-glow)',
                              }
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

        <!-- Task List -->
        <div v-if="currentList.length > 0" class="space-y-3">
            <div
                v-for="item in currentList"
                :key="item.id"
                class="gl-glow-card p-4 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 transition-all duration-200 hover:-translate-y-0.5"
                :style="
                    item.is_urgent
                        ? {
                              border: '1px solid rgba(239, 68, 68, 0.3)',
                              background: 'rgba(239, 68, 68, 0.03)',
                          }
                        : {}
                "
            >
                <!-- Left: Icon & Info -->
                <div class="flex items-start gap-3 min-w-0">
                    <!-- Toggle Checkbox for interactive items (Tasks / Materials) -->
                    <button
                        v-if="item.toggle_url"
                        type="button"
                        @click="toggleItem(item)"
                        class="mt-1 shrink-0 transition-transform hover:scale-110"
                        :title="item.completed ? 'Mark as incomplete' : 'Mark as done'"
                    >
                        <CheckCircle2
                            v-if="item.completed"
                            class="h-5 w-5 fill-emerald-500/20 text-emerald-500"
                        />
                        <Circle
                            v-else
                            class="h-5 w-5 text-gray-400 hover:text-emerald-500"
                        />
                    </button>

                    <!-- Type Icon Badge -->
                    <div
                        class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                        :style="{ background: getTypeConfig(item.type).bg }"
                    >
                        <component
                            :is="getTypeConfig(item.type).icon"
                            class="h-4 w-4"
                            :style="{ color: getTypeConfig(item.type).color }"
                            :stroke-width="2"
                        />
                    </div>

                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span
                                class="rounded px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider"
                                :style="{
                                    background: getTypeConfig(item.type).bg,
                                    color: getTypeConfig(item.type).color,
                                }"
                            >
                                {{ item.type }}
                            </span>

                            <span
                                v-if="item.is_urgent"
                                class="inline-flex items-center gap-1 text-[10px] font-bold text-red-500"
                            >
                                <Clock class="h-2.5 w-2.5" />
                                Due Soon
                            </span>

                            <span
                                v-if="item.completed"
                                class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-500"
                            >
                                <CheckCircle2 class="h-2.5 w-2.5" />
                                Finished
                            </span>
                        </div>

                        <h2 class="text-sm font-bold mt-1 leading-snug" style="color: var(--gl-text-primary)">
                            {{ item.title }}
                        </h2>

                        <p
                            v-if="item.description"
                            class="text-xs mt-0.5 line-clamp-2"
                            style="color: var(--gl-text-secondary)"
                        >
                            {{ item.description }}
                        </p>

                        <div class="flex flex-wrap items-center gap-3 mt-1.5 text-[11px]" style="color: var(--gl-text-muted)">
                            <span class="inline-flex items-center gap-1">
                                <Clock class="h-3 w-3" />
                                {{ item.due_human }}
                                <template v-if="item.due_date"> ({{ item.due_date }})</template>
                            </span>
                            <span v-if="item.time_limit" class="inline-flex items-center gap-1">
                                &bull; {{ item.time_limit }} mins limit
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right Action Button -->
                <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                    <!-- Homework Pass Action button -->
                    <button
                        v-if="canUsePass(item)"
                        type="button"
                        @click="useHomeworkPass(item)"
                        :disabled="applyingPassId === item.id"
                        class="inline-flex items-center gap-1 rounded-xl border border-amber-400/50 bg-amber-500/15 px-3 py-1.5 text-xs font-bold text-amber-700 transition-all hover:scale-105 hover:bg-amber-500/25 disabled:opacity-50 dark:text-amber-300"
                        title="Homework Pass: complete this with 100% score"
                    >
                        <span>🎟️</span>
                        <span>{{ applyingPassId === item.id ? 'Applying...' : 'Use Pass' }}</span>
                    </button>

                    <!-- Toggle button for materials -->
                    <button
                        v-if="item.type === 'Material' && item.toggle_url"
                        type="button"
                        @click="toggleItem(item)"
                        class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-all"
                        :style="
                            item.completed
                                ? { background: 'var(--gl-surface-2)', color: 'var(--gl-text-muted)', border: '1px solid var(--gl-border)' }
                                : { background: 'rgba(16, 185, 129, 0.15)', color: 'var(--gl-success)', border: '1px solid rgba(16, 185, 129, 0.3)' }
                        "
                    >
                        {{ item.completed ? 'Mark Unread' : 'Mark as Read' }}
                    </button>

                    <!-- Toggle button for task -->
                    <button
                        v-if="item.type === 'Task' && item.toggle_url"
                        type="button"
                        @click="toggleItem(item)"
                        class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-all"
                        :style="
                            item.completed
                                ? { background: 'var(--gl-surface-2)', color: 'var(--gl-text-muted)', border: '1px solid var(--gl-border)' }
                                : { background: 'rgba(16, 185, 129, 0.15)', color: 'var(--gl-success)', border: '1px solid rgba(16, 185, 129, 0.3)' }
                        "
                    >
                        {{ item.completed ? 'Undo Done' : 'Complete Task' }}
                    </button>

                    <!-- Primary Action link (Take quiz, Read material, Open resource) -->
                    <a
                        v-if="item.href && item.href.startsWith('http')"
                        :href="item.href"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 rounded-xl px-3.5 py-1.5 text-xs font-bold text-white transition-all hover:scale-105"
                        style="background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))"
                    >
                        {{ item.action_label || 'Open' }}
                        <ExternalLink class="h-3 w-3" />
                    </a>
                    <a
                        v-else-if="item.href && item.type === 'Material'"
                        :href="item.href"
                        target="_blank"
                        class="inline-flex items-center gap-1 rounded-xl px-3.5 py-1.5 text-xs font-bold text-white transition-all hover:scale-105"
                        style="background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))"
                    >
                        Read
                        <BookOpen class="h-3 w-3" />
                    </a>
                    <Link
                        v-else-if="item.href"
                        :href="item.href"
                        class="inline-flex items-center gap-1 rounded-xl px-3.5 py-1.5 text-xs font-bold text-white transition-all hover:scale-105"
                        style="background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))"
                    >
                        {{ item.action_label || 'View' }}
                        <ArrowRight class="h-3 w-3" />
                    </Link>

                    <!-- Restore button if on dismissed tab -->
                    <button
                        v-if="activeTab === 'dismissed'"
                        type="button"
                        @click="restoreItem(item)"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] px-3 py-1.5 text-xs font-semibold text-[var(--gl-text-primary)] transition-all hover:scale-105 hover:bg-[var(--gl-surface)]"
                    >
                        <RotateCcw class="h-3 w-3" />
                        Restore
                    </button>

                    <!-- Dismiss button if on pending or completed -->
                    <button
                        v-else
                        type="button"
                        @click="dismissItem(item)"
                        title="Dismiss / hide from to-do list"
                        class="rounded-xl p-2 text-[var(--gl-text-muted)] transition-all hover:bg-[rgba(239,68,68,0.12)] hover:text-[var(--gl-danger)]"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-else
            class="gl-glow-card flex flex-col items-center justify-center p-12 text-center"
        >
            <CheckCircle2
                class="h-12 w-12 mb-3"
                style="color: var(--gl-success)"
                :stroke-width="1.75"
            />
            <h3 class="text-sm font-bold" style="color: var(--gl-text-primary)">
                {{ activeTab === 'pending' ? 'All caught up!' : activeTab === 'completed' ? 'No completed items yet' : 'No dismissed items' }}
            </h3>
            <p class="text-xs mt-1 max-w-sm" style="color: var(--gl-text-muted)">
                {{
                    activeTab === 'pending'
                        ? 'You have completed all pending activities and reading materials. Great work!'
                        : activeTab === 'completed'
                        ? 'Activities, materials, and tasks you finish will appear here.'
                        : 'Items you dismiss or hide from your to-do list will appear here, and can be restored anytime.'
                }}
            </p>
        </div>
    </div>
</template>
