<template>
    <Head title="Activity Logs — Admin" />

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
                        Security & Audit
                    </span>
                    <div class="flex items-center gap-1.5">
                        <span
                            class="inline-block h-2 w-2 rounded-full"
                            :class="
                                livePolling
                                    ? 'animate-pulse bg-emerald-500'
                                    : 'bg-zinc-400'
                            "
                        ></span>
                        <span
                            class="text-[11px] font-medium"
                            style="color: var(--gl-text-muted)"
                        >
                            {{
                                livePolling ? 'Live feed active' : 'Live paused'
                            }}
                        </span>
                    </div>
                </div>
                <h1
                    class="mt-1.5 text-2xl font-extrabold tracking-tight sm:text-3xl"
                    style="color: var(--gl-text-primary)"
                >
                    Activity Logs
                </h1>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    Real-time monitoring of user logins, submissions, and
                    administrative events across the platform.
                </p>
            </div>

            <!-- Header Quick Actions -->
            <div class="flex flex-wrap items-center gap-2">
                <button
                    @click="toggleLivePolling"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold transition-all hover:scale-105 active:scale-95"
                    :style="
                        livePolling
                            ? {
                                  background: 'rgba(16, 185, 129, 0.12)',
                                  color: 'var(--gl-success)',
                                  border: '1px solid rgba(16, 185, 129, 0.3)',
                              }
                            : {
                                  background: 'var(--gl-surface)',
                                  color: 'var(--gl-text-secondary)',
                                  border: '1px solid var(--gl-border)',
                              }
                    "
                >
                    <Activity class="h-3.5 w-3.5" :stroke-width="2" />
                    <span>{{
                        livePolling ? 'Pause Live' : 'Resume Live'
                    }}</span>
                </button>

                <button
                    @click="manualRefresh"
                    :disabled="isRefreshing"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                    style="
                        background: var(--gl-surface);
                        color: var(--gl-text-primary);
                        border: 1px solid var(--gl-border);
                    "
                    title="Refresh logs now"
                >
                    <RefreshCw
                        class="h-3.5 w-3.5"
                        :class="{ 'animate-spin': isRefreshing }"
                        :stroke-width="2"
                    />
                    <span>Refresh</span>
                </button>

                <button
                    @click="showClearModal = true"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold transition-all hover:scale-105 active:scale-95"
                    style="
                        background: var(--gl-danger-bg);
                        color: var(--gl-danger);
                        border: 1px solid rgba(239, 68, 68, 0.25);
                    "
                >
                    <Trash2 class="h-3.5 w-3.5" :stroke-width="2" />
                    <span>Prune Logs</span>
                </button>
            </div>
        </div>

        <!-- 4 Stat Cards Row -->
        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <!-- Events Today -->
            <div
                class="rounded-2xl p-4 transition-all"
                style="
                    background: rgba(99, 102, 241, 0.07);
                    border: 1px solid rgba(99, 102, 241, 0.18);
                "
            >
                <div class="mb-2 flex items-center justify-between">
                    <span
                        class="text-xs font-medium"
                        style="color: var(--gl-text-secondary)"
                        >Events Today</span
                    >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-lg"
                        style="background: rgba(99, 102, 241, 0.15)"
                    >
                        <Activity
                            class="h-4 w-4"
                            style="color: var(--gl-secondary)"
                            :stroke-width="2"
                        />
                    </div>
                </div>
                <p
                    class="text-2xl font-extrabold tabular-nums"
                    style="color: var(--gl-text-primary)"
                >
                    {{ currentStats.total_today }}
                </p>
                <p
                    class="mt-0.5 text-[11px]"
                    style="color: var(--gl-text-muted)"
                >
                    Total activities recorded
                </p>
            </div>

            <!-- Logins Today -->
            <div
                class="rounded-2xl p-4 transition-all"
                style="
                    background: rgba(16, 185, 129, 0.07);
                    border: 1px solid rgba(16, 185, 129, 0.18);
                "
            >
                <div class="mb-2 flex items-center justify-between">
                    <span
                        class="text-xs font-medium"
                        style="color: var(--gl-text-secondary)"
                        >Logins Today</span
                    >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-lg"
                        style="background: rgba(16, 185, 129, 0.15)"
                    >
                        <LogIn
                            class="h-4 w-4"
                            style="color: var(--gl-success)"
                            :stroke-width="2"
                        />
                    </div>
                </div>
                <p
                    class="text-2xl font-extrabold tabular-nums"
                    style="color: var(--gl-text-primary)"
                >
                    {{ currentStats.logins_today }}
                </p>
                <p
                    class="mt-0.5 text-[11px]"
                    style="color: var(--gl-text-muted)"
                >
                    Successful user sessions
                </p>
            </div>

            <!-- Submissions Today -->
            <div
                class="rounded-2xl p-4 transition-all"
                style="
                    background: rgba(59, 130, 246, 0.07);
                    border: 1px solid rgba(59, 130, 246, 0.18);
                "
            >
                <div class="mb-2 flex items-center justify-between">
                    <span
                        class="text-xs font-medium"
                        style="color: var(--gl-text-secondary)"
                        >Submissions</span
                    >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-lg"
                        style="background: rgba(59, 130, 246, 0.15)"
                    >
                        <ClipboardCheck
                            class="h-4 w-4"
                            style="color: var(--gl-primary)"
                            :stroke-width="2"
                        />
                    </div>
                </div>
                <p
                    class="text-2xl font-extrabold tabular-nums"
                    style="color: var(--gl-text-primary)"
                >
                    {{ currentStats.submissions_today }}
                </p>
                <p
                    class="mt-0.5 text-[11px]"
                    style="color: var(--gl-text-muted)"
                >
                    Quizzes, seatworks & exams
                </p>
            </div>

            <!-- Active Accounts Today -->
            <div
                class="rounded-2xl p-4 transition-all"
                style="
                    background: rgba(251, 191, 36, 0.08);
                    border: 1px solid rgba(251, 191, 36, 0.2);
                "
            >
                <div class="mb-2 flex items-center justify-between">
                    <span
                        class="text-xs font-medium"
                        style="color: var(--gl-text-secondary)"
                        >Active Users</span
                    >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-lg"
                        style="background: rgba(251, 191, 36, 0.18)"
                    >
                        <Users
                            class="h-4 w-4"
                            style="color: var(--gl-accent)"
                            :stroke-width="2"
                        />
                    </div>
                </div>
                <p
                    class="text-2xl font-extrabold tabular-nums"
                    style="color: var(--gl-text-primary)"
                >
                    {{ currentStats.unique_users_today }}
                </p>
                <p
                    class="mt-0.5 text-[11px]"
                    style="color: var(--gl-text-muted)"
                >
                    {{ currentStats.total_count }} total entries in log
                </p>
            </div>
        </div>

        <!-- Filter & Search Controls Panel -->
        <div
            class="mb-6 rounded-3xl p-4 transition-all"
            style="
                background: var(--gl-surface);
                border: 1px solid var(--gl-border);
            "
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <!-- Search bar -->
                <div class="relative min-w-[240px] flex-1">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2"
                        style="color: var(--gl-text-muted)"
                        :stroke-width="2"
                    />
                    <input
                        v-model="searchInput"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Search by user, email, action, description, or IP..."
                        class="w-full rounded-2xl border py-2 pr-4 pl-10 text-xs transition-all outline-none"
                        style="
                            background: var(--gl-surface-2);
                            border-color: var(--gl-border);
                            color: var(--gl-text-primary);
                        "
                    />
                </div>

                <!-- Category Tabs -->
                <div
                    class="flex flex-wrap items-center gap-1 rounded-2xl p-1"
                    style="
                        background: var(--gl-surface-2);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <button
                        v-for="tab in categoryTabs"
                        :key="tab.id"
                        @click="setCategory(tab.id)"
                        class="rounded-xl px-3 py-1.5 text-xs font-bold transition-all"
                        :style="
                            currentCategory === tab.id
                                ? {
                                      background: 'var(--gl-primary)',
                                      color: '#fff',
                                      boxShadow:
                                          '0 0 10px var(--gl-primary-glow)',
                                  }
                                : { color: 'var(--gl-text-secondary)' }
                        "
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Select Filters -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Role select -->
                    <select
                        v-model="selectedRole"
                        @change="applyFilters"
                        class="rounded-xl border px-3 py-2 text-xs transition-colors outline-none"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-primary);
                            border-color: var(--gl-border);
                        "
                    >
                        <option value="all">All Roles</option>
                        <option value="student">Students</option>
                        <option value="teacher">Teachers</option>
                        <option value="superadmin">Superadmins</option>
                        <option value="guest">Guests / Unauth</option>
                    </select>

                    <!-- Date range select -->
                    <select
                        v-model="selectedDate"
                        @change="applyFilters"
                        class="rounded-xl border px-3 py-2 text-xs transition-colors outline-none"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-primary);
                            border-color: var(--gl-border);
                        "
                    >
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="7d">Last 7 Days</option>
                        <option value="30d">Last 30 Days</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Logs Feed Card -->
        <div
            class="overflow-hidden rounded-3xl"
            style="
                background: var(--gl-surface);
                border: 1px solid var(--gl-border);
            "
        >
            <!-- Table Header -->
            <div
                class="hidden grid-cols-12 gap-3 border-b px-6 py-3.5 text-[11px] font-bold tracking-wider uppercase md:grid"
                style="
                    border-color: var(--gl-border);
                    background: var(--gl-surface-2);
                    color: var(--gl-text-muted);
                "
            >
                <div class="col-span-3">User & Role</div>
                <div class="col-span-2">Action</div>
                <div class="col-span-4">Activity Description</div>
                <div class="col-span-2">Origin & Device</div>
                <div class="col-span-1 text-right">Time</div>
            </div>

            <!-- Empty State -->
            <div v-if="!logList.length" class="py-16 text-center">
                <div
                    class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl"
                    style="
                        background: var(--gl-surface-2);
                        color: var(--gl-text-muted);
                    "
                >
                    <ScrollText class="h-6 w-6" :stroke-width="1.5" />
                </div>
                <h4
                    class="text-sm font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    No activity logs found
                </h4>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    {{
                        searchInput
                            ? 'No activity matches your search query.'
                            : 'Activity events will appear here in real-time as users interact with the app.'
                    }}
                </p>
            </div>

            <!-- Logs List Rows -->
            <div v-else class="divide-y divide-[var(--gl-border)]">
                <div
                    v-for="log in logList"
                    :key="log.id"
                    class="gl-fade-in flex flex-col gap-2 p-4 transition-colors hover:bg-[var(--gl-surface-2)] md:grid md:grid-cols-12 md:items-center md:gap-3 md:px-6 md:py-3.5"
                >
                    <!-- User & Role (Col 3) -->
                    <div class="col-span-3 flex items-center gap-3">
                        <div
                            class="relative flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full text-xs font-bold text-white shadow-xs"
                            :style="{
                                background: log.avatar
                                    ? 'transparent'
                                    : avatarBg(log.user_name || 'Guest'),
                            }"
                        >
                            <img
                                v-if="log.avatar"
                                :src="log.avatar"
                                :alt="log.user_name || 'User'"
                                class="h-full w-full object-cover"
                            />
                            <span v-else>{{ (log.user_name || 'G').charAt(0).toUpperCase() }}</span>
                        </div>
                        <div class="min-w-0">
                            <p
                                class="truncate text-xs font-bold"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ log.user_name || 'Anonymous Guest' }}
                            </p>
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="rounded-md px-1.5 py-0.5 text-[9px] font-bold uppercase"
                                    :style="roleBadgeStyle(log.user_role)"
                                >
                                    {{ log.user_role || 'guest' }}
                                </span>
                                <span
                                    v-if="log.user_email"
                                    class="truncate text-[10px]"
                                    style="color: var(--gl-text-muted)"
                                >
                                    {{ log.user_email }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Badge (Col 2) -->
                    <div class="col-span-2 flex items-center gap-1.5">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-xl px-2.5 py-1 text-[11px] font-bold"
                            :style="actionBadgeStyle(log.action)"
                        >
                            <component
                                :is="actionIcon(log.action)"
                                class="h-3.5 w-3.5 shrink-0"
                                :stroke-width="2"
                            />
                            <span>{{ formatActionLabel(log.action) }}</span>
                        </span>
                    </div>

                    <!-- Description & Details (Col 4) -->
                    <div class="col-span-4 min-w-0">
                        <p
                            class="text-xs leading-relaxed"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ log.description }}
                        </p>
                        <div
                            v-if="log.entity_type"
                            class="mt-1 flex items-center gap-2 text-[10px]"
                            style="color: var(--gl-text-muted)"
                        >
                            <span
                                class="rounded px-1.5 py-0.5 font-semibold"
                                style="background: var(--gl-surface-2)"
                            >
                                {{ log.entity_type }}
                                <template v-if="log.entity_id">
                                    #{{ log.entity_id }}
                                </template>
                            </span>
                            <span v-if="log.properties?.score !== undefined">
                                Score: {{ log.properties.score }}/{{
                                    log.properties.total || 0
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Origin & Device (Col 2) -->
                    <div
                        class="col-span-2 min-w-0 text-[11px]"
                        style="color: var(--gl-text-muted)"
                    >
                        <div class="flex items-center gap-1">
                            <Globe class="h-3 w-3 shrink-0" :stroke-width="2" />
                            <span class="truncate font-mono">{{
                                log.ip_address || 'Local'
                            }}</span>
                        </div>
                        <div
                            v-if="log.user_agent"
                            class="mt-0.5 truncate text-[10px]"
                            :title="log.user_agent"
                        >
                            {{ parseBrowser(log.user_agent) }}
                        </div>
                    </div>

                    <!-- Time (Col 1) -->
                    <div
                        class="col-span-1 text-left text-[11px] tabular-nums md:text-right"
                        style="color: var(--gl-text-muted)"
                    >
                        <span :title="formatFullDate(log.created_at)">
                            {{ formatRelativeTime(log.created_at) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Pagination Row -->
            <div
                v-if="logs.links?.length > 3"
                class="flex flex-wrap items-center justify-between gap-3 border-t p-4"
                style="
                    border-color: var(--gl-border);
                    background: var(--gl-surface-2);
                "
            >
                <p
                    class="text-xs font-medium"
                    style="color: var(--gl-text-muted)"
                >
                    Showing {{ logs.from || 0 }} to {{ logs.to || 0 }} of
                    {{ logs.total || 0 }} events
                </p>

                <div class="flex items-center gap-1">
                    <Link
                        v-for="(link, i) in logs.links"
                        :key="i"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="flex h-8 items-center justify-center rounded-xl px-3 text-xs font-semibold transition-all"
                        :class="[
                            link.active
                                ? 'text-white'
                                : 'text-[var(--gl-text-secondary)] hover:bg-[var(--gl-surface)]',
                            !link.url ? 'pointer-events-none opacity-40' : '',
                        ]"
                        :style="
                            link.active
                                ? {
                                      background: 'var(--gl-primary)',
                                      boxShadow:
                                          '0 0 10px var(--gl-primary-glow)',
                                  }
                                : { border: '1px solid var(--gl-border)' }
                        "
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- Prune Logs Modal -->
    <Teleport to="body">
        <div
            v-if="showClearModal"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(2px)"
            @click.self="showClearModal = false"
        >
            <div
                class="mx-4 w-full max-w-sm rounded-3xl p-6"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 12px 36px rgba(0, 0, 0, 0.45);
                "
            >
                <div class="mb-4 flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-2xl"
                        style="background: var(--gl-danger-bg)"
                    >
                        <Trash2
                            class="h-5 w-5"
                            style="color: var(--gl-danger)"
                            :stroke-width="2"
                        />
                    </div>
                    <div>
                        <h3
                            class="text-base font-bold"
                            style="color: var(--gl-text-primary)"
                        >
                            Prune Activity Logs
                        </h3>
                        <p class="text-xs" style="color: var(--gl-text-muted)">
                            Clean up older audit records
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    <label
                        class="block text-xs font-semibold"
                        style="color: var(--gl-text-secondary)"
                    >
                        Select log retention threshold:
                    </label>
                    <select
                        v-model="pruneRange"
                        class="w-full rounded-xl border px-3.5 py-2.5 text-xs outline-none"
                        style="
                            background: var(--gl-surface-2);
                            border-color: var(--gl-border);
                            color: var(--gl-text-primary);
                        "
                    >
                        <option value="7_days">
                            Delete logs older than 7 days
                        </option>
                        <option value="30_days">
                            Delete logs older than 30 days
                        </option>
                        <option value="90_days">
                            Delete logs older than 90 days
                        </option>
                        <option value="all">Delete all activity logs</option>
                    </select>
                </div>

                <div class="mt-6 flex justify-end gap-2.5">
                    <button
                        @click="showClearModal = false"
                        class="rounded-xl px-4 py-2 text-xs font-semibold transition-colors"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-secondary);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        Cancel
                    </button>
                    <button
                        @click="submitPrune"
                        class="rounded-xl px-4 py-2 text-xs font-bold text-white transition-all hover:scale-105 active:scale-95"
                        style="background: var(--gl-danger)"
                    >
                        Confirm Prune
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Activity,
    AlertTriangle,
    CheckCircle2,
    ClipboardCheck,
    FolderOpen,
    Globe,
    LogIn,
    LogOut,
    RefreshCw,
    ScrollText,
    Search,
    Shield,
    Trash2,
    UserCheck,
    Users,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps<{
    logs: any;
    filters: {
        search: string;
        category: string;
        role: string;
        date: string;
    };
    stats: {
        total_today: number;
        logins_today: number;
        submissions_today: number;
        unique_users_today: number;
        total_count: number;
    };
}>();

const page = usePage();
const flash = computed(() => (page.props as any).flash || {});

const searchInput = ref(props.filters.search || '');
const currentCategory = ref(props.filters.category || 'all');
const selectedRole = ref(props.filters.role || 'all');
const selectedDate = ref(props.filters.date || 'all');

const currentStats = ref({ ...props.stats });
const logList = ref<any[]>([...(props.logs.data || [])]);

const isRefreshing = ref(false);
const livePolling = ref(true);
let pollTimer: number | null = null;

const showClearModal = ref(false);
const pruneRange = ref('30_days');

const categoryTabs = [
    { id: 'all', label: 'All Activities' },
    { id: 'auth', label: 'Logins & Auth' },
    { id: 'submissions', label: 'Submissions' },
    { id: 'materials', label: 'Materials' },
    { id: 'admin', label: 'Administrative' },
];

const AVATAR_BG = [
    'linear-gradient(135deg, #3B82F6, #7C3AED)',
    'linear-gradient(135deg, #10B981, #06B6D4)',
    'linear-gradient(135deg, #F59E0B, #EF4444)',
    'linear-gradient(135deg, #EC4899, #8B5CF6)',
    'linear-gradient(135deg, #14B8A6, #3B82F6)',
    'linear-gradient(135deg, #6366F1, #A855F7)',
];

function avatarBg(name: string): string {
    let hash = 0;

    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }

    return AVATAR_BG[Math.abs(hash) % AVATAR_BG.length];
}

function roleBadgeStyle(role?: string) {
    switch (role) {
        case 'superadmin':
            return {
                background: 'rgba(251, 191, 36, 0.14)',
                color: 'var(--gl-accent)',
            };
        case 'teacher':
            return {
                background: 'rgba(124, 58, 237, 0.12)',
                color: 'var(--gl-secondary)',
            };
        case 'student':
            return {
                background: 'rgba(59, 130, 246, 0.12)',
                color: 'var(--gl-primary)',
            };
        default:
            return {
                background: 'var(--gl-surface-2)',
                color: 'var(--gl-text-muted)',
            };
    }
}

function actionBadgeStyle(action: string) {
    if (action === 'login') {
        return {
            background: 'rgba(16, 185, 129, 0.14)',
            color: 'var(--gl-success)',
        };
    }

    if (action === 'logout') {
        return {
            background: 'var(--gl-surface-2)',
            color: 'var(--gl-text-muted)',
        };
    }

    if (action === 'failed_login') {
        return { background: 'var(--gl-danger-bg)', color: 'var(--gl-danger)' };
    }

    if (action.endsWith('_submitted')) {
        return {
            background: 'rgba(59, 130, 246, 0.14)',
            color: 'var(--gl-primary)',
        };
    }

    if (action.startsWith('material_')) {
        return { background: 'rgba(249, 115, 22, 0.14)', color: '#f97316' };
    }

    if (action.startsWith('user_') || action.startsWith('impersonate_')) {
        return {
            background: 'rgba(124, 58, 237, 0.14)',
            color: 'var(--gl-secondary)',
        };
    }

    return {
        background: 'var(--gl-surface-2)',
        color: 'var(--gl-text-secondary)',
    };
}

function actionIcon(action: string) {
    if (action === 'login') {
return LogIn;
}

    if (action === 'logout') {
return LogOut;
}

    if (action === 'failed_login') {
return AlertTriangle;
}

    if (action.endsWith('_submitted')) {
return CheckCircle2;
}

    if (action.startsWith('material_')) {
return FolderOpen;
}

    if (action.startsWith('user_')) {
return Shield;
}

    if (action.startsWith('impersonate_')) {
return UserCheck;
}

    return Activity;
}

function formatActionLabel(action: string): string {
    switch (action) {
        case 'login':
            return 'Sign In';
        case 'logout':
            return 'Sign Out';
        case 'failed_login':
            return 'Failed Login';
        case 'quiz_submitted':
            return 'Quiz Submitted';
        case 'seatwork_submitted':
            return 'Seatwork Submitted';
        case 'practical_submitted':
            return 'Practical Submitted';
        case 'exam_submitted':
            return 'Exam Submitted';
        case 'material_uploaded':
            return 'Material Upload';
        case 'material_downloaded':
            return 'Material Download';
        case 'user_created':
            return 'User Created';
        case 'user_updated':
            return 'User Updated';
        case 'user_deleted':
            return 'User Deleted';
        case 'impersonate_start':
            return 'Impersonation';
        case 'impersonate_end':
            return 'End Impersonation';
        default:
            return action.replace(/_/g, ' ');
    }
}

function parseBrowser(agent?: string): string {
    if (!agent) {
return 'Web Browser';
}

    if (agent.includes('Edg/')) {
return 'Edge Browser';
}

    if (agent.includes('Chrome/')) {
return 'Chrome Browser';
}

    if (agent.includes('Firefox/')) {
return 'Firefox';
}

    if (agent.includes('Safari/')) {
return 'Safari';
}

    return 'Web Client';
}

function formatRelativeTime(dateStr: string): string {
    if (!dateStr) {
return '—';
}

    const diffSec = Math.floor(
        (Date.now() - new Date(dateStr).getTime()) / 1000,
    );

    if (diffSec < 15) {
return 'Just now';
}

    if (diffSec < 60) {
return `${diffSec}s ago`;
}

    if (diffSec < 3600) {
return `${Math.floor(diffSec / 60)}m ago`;
}

    if (diffSec < 86400) {
return `${Math.floor(diffSec / 3600)}h ago`;
}

    return new Date(dateStr).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}

function formatFullDate(dateStr: string): string {
    if (!dateStr) {
return '';
}

    return new Date(dateStr).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'medium',
    });
}

function setCategory(id: string) {
    currentCategory.value = id;
    applyFilters();
}

function applyFilters() {
    router.get(
        '/teacher/activity-logs',
        {
            search: searchInput.value.trim(),
            category: currentCategory.value,
            role: selectedRole.value,
            date: selectedDate.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                logList.value = [...(props.logs.data || [])];
                currentStats.value = { ...props.stats };
            },
        },
    );
}

function toggleLivePolling() {
    livePolling.value = !livePolling.value;
}

async function fetchLiveUpdates() {
    if (!livePolling.value || isRefreshing.value) {
return;
}

    const highestId = logList.value.length
        ? Math.max(...logList.value.map((l: any) => Number(l.id) || 0))
        : 0;

    try {
        const params = new URLSearchParams({
            since_id: String(highestId),
            search: searchInput.value.trim(),
            category: currentCategory.value,
            role: selectedRole.value,
            date: selectedDate.value,
        });

        const res = await fetch(
            `/teacher/activity-logs/poll?${params.toString()}`,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        );

        if (res.ok) {
            const data = await res.json();

            if (data.logs?.length) {
                const existingIds = new Set(
                    logList.value.map((l: any) => l.id),
                );
                const newItems = data.logs.filter(
                    (l: any) => !existingIds.has(l.id),
                );

                if (newItems.length) {
                    logList.value = [...newItems, ...logList.value];
                }
            }

            if (data.stats) {
                currentStats.value = data.stats;
            }
        }
    } catch {
        /* silent network error */
    }
}

function manualRefresh() {
    isRefreshing.value = true;
    router.reload({
        onFinish: () => {
            isRefreshing.value = false;
            logList.value = [...(props.logs.data || [])];
            currentStats.value = { ...props.stats };
        },
    });
}

function submitPrune() {
    router.post(
        '/teacher/activity-logs/clear',
        { range: pruneRange.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                showClearModal.value = false;
                logList.value = [...(props.logs.data || [])];
                currentStats.value = { ...props.stats };
            },
        },
    );
}

onMounted(() => {
    pollTimer = window.setInterval(fetchLiveUpdates, 3000);
});

onUnmounted(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
    }
});

watch(
    () => props.logs,
    (newVal) => {
        logList.value = [...(newVal.data || [])];
    },
);

watch(
    () => props.stats,
    (newVal) => {
        currentStats.value = { ...newVal };
    },
);
</script>
