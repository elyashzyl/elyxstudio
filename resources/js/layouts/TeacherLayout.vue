<template>
    <div
        class="flex min-h-screen"
        style="background: var(--gl-bg); color: var(--gl-text-primary)"
    >
        <!-- Mobile Sidebar Drawer Backdrop -->
        <div
            v-if="mobileNavOpen"
            class="fixed inset-0 z-40 bg-black/60 backdrop-blur-xs md:hidden"
            @click="mobileNavOpen = false"
        ></div>

        <!-- Mobile Sidebar Drawer -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] flex-col transition-transform duration-300 ease-in-out md:hidden"
            :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full'"
            style="
                background: var(--gl-sidebar-bg);
                border-right: 1px solid var(--gl-border);
                backdrop-filter: blur(16px);
            "
        >
            <!-- Logo & Close -->
            <div
                class="relative flex h-16 items-center justify-between gap-3 overflow-hidden border-b px-5"
                style="border-color: var(--gl-border)"
            >
                <div class="flex items-center gap-3">
                    <img
                        src="/logo.png"
                        alt="EduPulse logo"
                        class="relative z-10 h-9 w-9 rounded-full object-cover"
                        style="
                            box-shadow:
                                0 0 0 2px var(--gl-surface),
                                0 0 0 4px var(--gl-primary-glow);
                        "
                    />
                    <div class="relative z-10">
                        <p
                            class="text-sm font-extrabold tracking-tight"
                            style="color: var(--gl-text-primary)"
                        >
                            EduPulse
                        </p>
                        <p
                            class="text-[11px] font-medium"
                            style="color: var(--gl-text-muted)"
                        >
                            Level Up Learning
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="mobileNavOpen = false"
                    class="rounded-xl p-2 transition-colors"
                    style="
                        color: var(--gl-text-muted);
                        background: var(--gl-surface-2);
                    "
                    aria-label="Close menu"
                >
                    <X :size="18" :stroke-width="2.2" />
                </button>
            </div>

            <!-- Nav -->
            <nav
                class="no-scrollbar flex-1 space-y-1 overflow-y-auto px-3 py-3"
            >
                <template v-for="link in nav" :key="link.name">
                    <div
                        v-if="link.label"
                        class="flex items-center gap-2 px-3 pt-4 pb-1 text-[10px] font-extrabold uppercase"
                        style="
                            color: var(--gl-text-muted);
                            letter-spacing: 0.14em;
                        "
                    >
                        <span
                            class="h-1 w-1 shrink-0 rounded-full"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                            "
                        ></span>
                        {{ link.label }}
                    </div>
                    <Link
                        v-else
                        :href="link.href"
                        @click="mobileNavOpen = false"
                        class="group relative flex items-center gap-3 rounded-2xl px-2.5 py-2 text-sm font-semibold transition-all duration-200"
                        :style="
                            link.active
                                ? {
                                      background:
                                          'linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))',
                                      color: '#fff',
                                      boxShadow:
                                          '0 4px 14px var(--gl-primary-glow)',
                                  }
                                : {
                                      color: 'var(--gl-text-secondary)',
                                  }
                        "
                    >
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl transition-all"
                            :style="
                                link.active
                                    ? { background: 'rgba(255,255,255,0.2)' }
                                    : { background: 'var(--gl-surface-2)' }
                            "
                        >
                            <component
                                :is="link.icon"
                                :size="17"
                                :stroke-width="2.25"
                                :style="{
                                    color: link.active
                                        ? '#fff'
                                        : 'var(--gl-text-muted)',
                                }"
                            />
                        </span>
                        <span class="flex-1 truncate">{{ link.name }}</span>
                        <span
                            v-if="
                                link.name === 'Messenger' && messengerUnread > 0
                            "
                            class="flex h-5 min-w-[20px] items-center justify-center rounded-full px-1.5 text-[10px] leading-none font-extrabold"
                            :style="
                                link.active
                                    ? {
                                          background: '#fff',
                                          color: 'var(--gl-primary)',
                                      }
                                    : {
                                          background: 'var(--gl-danger)',
                                          color: '#fff',
                                      }
                            "
                            >{{ messengerUnread }}</span
                        >
                    </Link>
                </template>
            </nav>

            <!-- Profile Card -->
            <div class="p-3" style="border-top: 1px solid var(--gl-border)">
                <div
                    class="rounded-2xl p-3 transition-all"
                    style="
                        background: var(--gl-surface-2);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <Link
                        href="/teacher/profile"
                        @click="mobileNavOpen = false"
                        class="flex items-center gap-3"
                    >
                        <div
                            class="relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full text-sm font-extrabold text-white"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                                box-shadow:
                                    0 0 0 2px var(--gl-surface),
                                    0 0 0 4px var(--gl-primary-glow);
                            "
                        >
                            <img
                                v-if="user?.avatar"
                                :src="user.avatar"
                                :alt="user?.name"
                                class="h-full w-full object-cover"
                            />
                            <span v-else>{{ (user?.name ?? '?').charAt(0) }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-sm font-bold"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ user?.name ?? 'Account' }}
                            </p>
                            <span
                                class="mt-0.5 inline-block rounded-full px-2 py-px text-[10px] font-bold"
                                :style="
                                    user?.role === 'superadmin'
                                        ? 'background: rgba(251,191,36,0.14); color: var(--gl-accent);'
                                        : 'background: rgba(124,58,237,0.12); color: var(--gl-secondary);'
                                "
                            >
                                {{
                                    user?.role === 'superadmin'
                                        ? 'Superadmin'
                                        : 'Teacher'
                                }}
                            </span>
                        </div>
                        <button
                            type="button"
                            @click.stop.prevent="logout"
                            class="shrink-0 rounded-xl p-2 transition-all hover:bg-[rgba(239,68,68,0.12)] hover:text-[var(--gl-danger)]"
                            style="color: var(--gl-text-muted)"
                            title="Sign out"
                        >
                            <LogOut :size="16" :stroke-width="2.25" />
                        </button>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Desktop Sidebar -->
        <aside
            class="sticky top-0 hidden h-screen w-64 shrink-0 flex-col md:flex"
            style="
                background: var(--gl-sidebar-bg);
                border-right: 1px solid var(--gl-border);
                backdrop-filter: blur(12px);
            "
        >
            <!-- Logo -->
            <div
                class="relative flex h-16 items-center gap-3 overflow-hidden border-b px-5"
                style="border-color: var(--gl-border)"
            >
                <div
                    class="absolute inset-0 opacity-5"
                    style="
                        background:
                            radial-gradient(
                                circle at 30% 50%,
                                var(--gl-primary),
                                transparent 70%
                            ),
                            radial-gradient(
                                circle at 70% 50%,
                                var(--gl-secondary),
                                transparent 70%
                            );
                    "
                ></div>
                <img
                    src="/logo.png"
                    alt="EduPulse logo"
                    class="relative z-10 h-10 w-10 rounded-full object-cover"
                    style="
                        box-shadow:
                            0 0 0 2px var(--gl-surface),
                            0 0 0 4px var(--gl-primary-glow);
                    "
                />
                <div class="relative z-10">
                    <p
                        class="text-sm font-extrabold tracking-tight"
                        style="color: var(--gl-text-primary)"
                    >
                        EduPulse
                    </p>
                    <p
                        class="text-[11px] font-medium"
                        style="color: var(--gl-text-muted)"
                    >
                        Level Up Learning
                    </p>
                </div>
            </div>

            <!-- Nav -->
            <nav
                class="no-scrollbar flex-1 space-y-1 overflow-y-auto px-3 py-3"
            >
                <template v-for="link in nav" :key="link.name">
                    <div
                        v-if="link.label"
                        class="flex items-center gap-2 px-3 pt-4 pb-1 text-[10px] font-extrabold uppercase"
                        style="
                            color: var(--gl-text-muted);
                            letter-spacing: 0.14em;
                        "
                    >
                        <span
                            class="h-1 w-1 shrink-0 rounded-full"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                            "
                        ></span>
                        {{ link.label }}
                    </div>
                    <Link
                        v-else
                        :href="link.href"
                        class="group relative flex items-center gap-3 rounded-2xl px-2.5 py-2 text-sm font-semibold transition-all duration-200"
                        :style="
                            link.active
                                ? {
                                      background:
                                          'linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))',
                                      color: '#fff',
                                      boxShadow:
                                          '0 4px 14px var(--gl-primary-glow)',
                                  }
                                : {
                                      color: 'var(--gl-text-secondary)',
                                  }
                        "
                    >
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl transition-all"
                            :style="
                                link.active
                                    ? { background: 'rgba(255,255,255,0.2)' }
                                    : { background: 'var(--gl-surface-2)' }
                            "
                        >
                            <component
                                :is="link.icon"
                                :size="17"
                                :stroke-width="2.25"
                                :style="{
                                    color: link.active
                                        ? '#fff'
                                        : 'var(--gl-text-muted)',
                                }"
                            />
                        </span>
                        <span class="flex-1 truncate">{{ link.name }}</span>
                        <span
                            v-if="
                                link.name === 'Messenger' && messengerUnread > 0
                            "
                            class="flex h-5 min-w-[20px] items-center justify-center rounded-full px-1.5 text-[10px] leading-none font-extrabold"
                            :style="
                                link.active
                                    ? {
                                          background: '#fff',
                                          color: 'var(--gl-primary)',
                                      }
                                    : {
                                          background: 'var(--gl-danger)',
                                          color: '#fff',
                                      }
                            "
                            >{{ messengerUnread }}</span
                        >
                    </Link>
                </template>
            </nav>

            <!-- Profile Card -->
            <div class="p-3" style="border-top: 1px solid var(--gl-border)">
                <div
                    class="rounded-2xl p-3 transition-all"
                    style="
                        background: var(--gl-surface-2);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <Link
                        href="/teacher/profile"
                        class="flex items-center gap-3"
                    >
                        <div
                            class="relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full text-sm font-extrabold text-white"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                                box-shadow:
                                    0 0 0 2px var(--gl-surface),
                                    0 0 0 4px var(--gl-primary-glow);
                            "
                        >
                            <img
                                v-if="user?.avatar"
                                :src="user.avatar"
                                :alt="user?.name"
                                class="h-full w-full object-cover"
                            />
                            <span v-else>{{ (user?.name ?? '?').charAt(0) }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-sm font-bold"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ user?.name ?? 'Account' }}
                            </p>
                            <span
                                class="mt-0.5 inline-block rounded-full px-2 py-px text-[10px] font-bold"
                                :style="
                                    user?.role === 'superadmin'
                                        ? 'background: rgba(251,191,36,0.14); color: var(--gl-accent);'
                                        : 'background: rgba(124,58,237,0.12); color: var(--gl-secondary);'
                                "
                            >
                                {{
                                    user?.role === 'superadmin'
                                        ? 'Superadmin'
                                        : 'Teacher'
                                }}
                            </span>
                        </div>
                        <button
                            type="button"
                            @click.stop.prevent="logout"
                            class="shrink-0 rounded-xl p-2 transition-all hover:bg-[rgba(239,68,68,0.12)] hover:text-[var(--gl-danger)]"
                            style="color: var(--gl-text-muted)"
                            title="Sign out"
                        >
                            <LogOut :size="16" :stroke-width="2.25" />
                        </button>
                    </Link>
                </div>
            </div>
        </aside>

        <div class="flex h-screen flex-1 flex-col" style="min-width: 0">
            <header
                class="sticky top-0 z-20 flex h-16 items-center gap-3 px-4 sm:px-6"
                :style="{
                    background: 'var(--gl-header-bg)',
                    backdropFilter: 'blur(16px)',
                    borderBottom: '1px solid var(--gl-border)',
                }"
            >
                <button
                    type="button"
                    @click="mobileNavOpen = true"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition-colors md:hidden"
                    style="
                        background: var(--gl-surface-2);
                        border: 1px solid var(--gl-border);
                        color: var(--gl-text-primary);
                    "
                    aria-label="Open navigation menu"
                >
                    <Menu :size="18" :stroke-width="2.2" />
                </button>
                <div
                    v-if="isImpersonating"
                    class="flex w-full min-w-0 items-center gap-3 text-sm"
                    style="color: var(--gl-accent)"
                >
                    <UserCheck class="h-4 w-4 shrink-0" :stroke-width="2" />
                    <span class="truncate font-medium"
                        >Impersonating {{ user?.name ?? '' }}</span
                    >
                    <button
                        @click="stopImpersonating"
                        class="ml-auto shrink-0 rounded-lg px-3 py-1 text-xs font-semibold"
                        style="background: var(--gl-accent); color: #0f172a"
                    >
                        Stop
                    </button>
                </div>
                <div v-else class="flex w-full min-w-0 items-center gap-3">
                    <h1
                        class="min-w-0 flex-1 truncate text-sm font-semibold sm:text-base"
                        style="color: var(--gl-text-primary)"
                    >
                        <slot name="title" />
                    </h1>
                    <div class="flex shrink-0 items-center gap-2">
                        <div
                            v-if="schoolYearsList.length"
                            class="flex items-center gap-1.5 rounded-xl border px-2.5 py-1.5"
                            style="
                                background: var(--gl-surface-2);
                                border-color: var(--gl-border);
                            "
                            title="Switch active school year view"
                        >
                            <Calendar
                                :size="14"
                                :stroke-width="2.2"
                                style="color: var(--gl-primary)"
                            />
                            <select
                                :value="selectedSchoolYearValue"
                                @change="
                                    onSwitchSchoolYear(
                                        ($event.target as HTMLSelectElement)
                                            .value,
                                    )
                                "
                                class="cursor-pointer bg-transparent text-xs font-bold outline-none"
                                style="color: var(--gl-text-primary)"
                            >
                                <option
                                    v-for="sy in schoolYearsList"
                                    :key="sy.id"
                                    :value="String(sy.id)"
                                    style="
                                        background: var(--gl-surface);
                                        color: var(--gl-text-primary);
                                    "
                                >
                                    S.Y. {{ sy.name }}{{ sy.is_current ? ' (Current)' : '' }}
                                </option>
                                <option
                                    value="all"
                                    style="
                                        background: var(--gl-surface);
                                        color: var(--gl-text-primary);
                                    "
                                >
                                    All School Years
                                </option>
                            </select>
                        </div>
                        <ThemeToggle />
                        <NotificationBell role="teacher" />
                    </div>
                </div>
            </header>

            <main
                class="gl-main-scroll flex min-h-0 flex-1 flex-col overflow-y-auto px-3.5 py-4 sm:px-6 sm:py-6"
                style="background: var(--gl-bg)"
            >
                <div class="gl-slot flex min-h-0 w-full flex-1 flex-col">
                    <slot />
                </div>
            </main>
        </div>
        <CookieBanner />
    </div>
</template>

<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    Users,
    ClipboardList,
    ClipboardCheck,

    LogOut,
    FileQuestion,
    Trophy,
    UserCheck,
    MessageCircle,
    Gamepad2,
    FolderOpen,
    Video,
    Menu,
    X,
    ScrollText,
    Database,
    Store,
    TriangleAlert,
    Settings,
    Sparkles,
    Bot,
    GraduationCap,
    BookOpen,
    Megaphone,
    CheckSquare,
    BarChart3,
    Layers,
    Calendar,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import CookieBanner from '@/components/CookieBanner.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { getEcho } from '@/lib/echo';

const page = usePage();
const mobileNavOpen = ref(false);
const user = computed(() => (page.props.auth as any)?.user);
const path = computed(() => usePage().url);
const isImpersonating = computed(() => !!(page.props as any).impersonated_by);
const messengerSystemEnabled = computed(() => {
    const val = (page.props as any).messenger_system_enabled;

    return val !== false && val !== 'false' && val !== 0;
});

const isSuperadmin = computed(() => user.value?.role === 'superadmin');

const schoolYearsList = computed<any[]>(
    () => ((page.props as any).school_years as any[]) || [],
);
const selectedSchoolYearValue = computed(() => {
    const val = (page.props as any).selected_school_year_id;

    if (val === 'all') {
return 'all';
}

    if (val) {
return String(val);
}

    const current = (page.props as any).current_school_year;

    return current?.id ? String(current.id) : '';
});

function onSwitchSchoolYear(val: string) {
    router.post(
        '/teacher/school-years/switch',
        { school_year_id: val === 'all' ? 'all' : Number(val) },
        { preserveScroll: true },
    );
}

const messengerUnread = ref(0);
let userEchoChannel: any = null;

async function fetchMessengerUnread() {
    if (!messengerSystemEnabled.value || user.value?.messenger_enabled == false) {
        messengerUnread.value = 0;

        return;
    }

    try {
        const res = await fetch('/teacher/messenger/unread-count');

        if (res.ok) {
            const data = await res.json();
            messengerUnread.value = data.count;
        }
    } catch {
        /* silent */
    }
}

function setupMessengerEcho() {
    if (!user.value?.id) {
return;
}

    const echo = getEcho((page.props as any).broadcasting?.reverb);

    if (!echo) {
return;
}

    try {
        userEchoChannel = echo.private(`users.${user.value.id}`);
        userEchoChannel
            .listen('.message.sent', () => {
                fetchMessengerUnread();
            })
            .listen('.conversation.read', () => {
                fetchMessengerUnread();
            })
            .listen('.message.deleted', () => {
                fetchMessengerUnread();
            });
    } catch {}
}

function teardownMessengerEcho() {
    if (userEchoChannel && user.value?.id) {
        const echo = getEcho();

        if (echo) {
            try {
                echo.leave(`users.${user.value.id}`);
            } catch {}
        }

        userEchoChannel = null;
    }
}

onMounted(() => {
    fetchMessengerUnread();
    setupMessengerEcho();
    router.on('navigate', () => {
        mobileNavOpen.value = false;
        fetchMessengerUnread();
    });
});

onUnmounted(() => {
    teardownMessengerEcho();
});

const nav = computed(() => [
    {
        name: 'Dashboard',
        href: '/teacher/dashboard',
        icon: LayoutDashboard,
        active: path.value === '/teacher/dashboard',
    },
    { label: 'MANAGE' },
    {
        name: 'Students',
        href: '/teacher/students',
        icon: Users,
        active: path.value.startsWith('/teacher/students'),
    },
    {
        name: 'Grade 10 Profiles',
        href: '/teacher/grade10-profiles',
        icon: GraduationCap,
        active: path.value.startsWith('/teacher/grade10-profiles'),
    },
    {
        name: 'Grade Levels',
        href: '/teacher/grade-levels',
        icon: Layers,
        active: path.value.startsWith('/teacher/grade-levels'),
    },
    {
        name: 'Quarters',
        href: '/teacher/quarters',
        icon: Calendar,
        active: path.value.startsWith('/teacher/quarters'),
    },
    {
        name: 'School Years',
        href: '/teacher/school-years',
        icon: Calendar,
        active: path.value.startsWith('/teacher/school-years'),
    },
    ...(isSuperadmin.value
        ? [
              {
                  name: 'Teachers',
                  href: '/teacher/teachers',
                  icon: Users,
                  active: path.value.startsWith('/teacher/teachers'),
              },
              {
                  name: 'Subjects',
                  href: '/teacher/subjects',
                  icon: BookOpen,
                  active: path.value.startsWith('/teacher/subjects'),
              },

              {
                  name: 'Activity Logs',
                  href: '/teacher/activity-logs',
                  icon: ScrollText,
                  active: path.value.startsWith('/teacher/activity-logs'),
              },
              {
                  name: 'Error Logs',
                  href: '/teacher/error-logs',
                  icon: TriangleAlert,
                  active: path.value.startsWith('/teacher/error-logs'),
              },
              {
                  name: 'AI Supervisor',
                  href: '/teacher/ai-manager',
                  icon: Bot,
                  active: path.value.startsWith('/teacher/ai-manager'),
              },
              {
                  name: 'Backups',
                  href: '/teacher/backups',
                  icon: Database,
                  active: path.value.startsWith('/teacher/backups'),
              },
              {
                  name: 'Commerce',
                  href: '/teacher/commerce',
                  icon: Store,
                  active: path.value.startsWith('/teacher/commerce'),
              },
              {
                  name: 'Settings',
                  href: '/teacher/system-settings',
                  icon: Settings,
                  active: path.value.startsWith('/teacher/system-settings'),
              },
          ]
        : []),
    { label: 'ACTIVITIES' },
    {
        name: 'AI Creator',
        href: '/teacher/ai-creator',
        icon: Sparkles,
        active: path.value.startsWith('/teacher/ai-creator'),
    },
    {
        name: 'Quizzes',
        href: '/teacher/quizzes',
        icon: ClipboardList,
        active: path.value.startsWith('/teacher/quizzes'),
    },
    {
        name: 'Seatworks',
        href: '/teacher/seatworks',
        icon: ClipboardCheck,
        active: path.value.startsWith('/teacher/seatworks'),
    },
    {
        name: 'Practicals',
        href: '/teacher/practicals',
        icon: ClipboardCheck,
        active: path.value.startsWith('/teacher/practicals'),
    },
    {
        name: 'Exams',
        href: '/teacher/exams',
        icon: FileQuestion,
        active: path.value.startsWith('/teacher/exams'),
    },
    {
        name: 'Materials',
        href: '/teacher/materials',
        icon: FolderOpen,
        active: path.value.startsWith('/teacher/materials'),
    },
    { label: 'COMMUNITY' },
    {
        name: 'Announcements',
        href: '/teacher/announcements',
        icon: Megaphone,
        active: path.value.startsWith('/teacher/announcements'),
    },
    {
        name: 'Classroom Polls',
        href: '/teacher/polls',
        icon: BarChart3,
        active: path.value.startsWith('/teacher/polls'),
    },
    {
        name: 'To-Dos & Tasks',
        href: '/teacher/todos',
        icon: CheckSquare,
        active: path.value.startsWith('/teacher/todos'),
    },
    {
        name: 'Results',
        href: '/teacher/results',
        icon: ClipboardCheck,
        active: path.value.startsWith('/teacher/results'),
    },
    {
        name: 'Meetings',
        href: '/teacher/meetings',
        icon: Video,
        active: path.value.startsWith('/teacher/meetings'),
    },
    ...(user.value?.messenger_enabled != false && messengerSystemEnabled.value
        ? [
              {
                  name: 'Messenger',
                  href: '/teacher/messenger',
                  icon: MessageCircle,
                  active: path.value.startsWith('/teacher/messenger'),
              },
          ]
        : []),
    {
        name: 'Leaderboard',
        href: '/teacher/leaderboard',
        icon: Trophy,
        active: path.value.startsWith('/teacher/leaderboard'),
    },
    {
        name: 'Games',
        href: '/teacher/games',
        icon: Gamepad2,
        active: path.value.startsWith('/teacher/games'),
    },
]);

function logout() {
    router.post(
        '/logout',
        {},
        {
            onSuccess: () => {
                window.location.href = '/login';
            },
            onError: () => {
                window.location.href = '/login';
            },
            onFinish: () => {
                window.location.href = '/login';
            },
        },
    );
}

function stopImpersonating() {
    router.post(
        '/leave-impersonation',
        {},
        {
            onError: () => {
                window.location.href = '/leave-impersonation';
            },
        },
    );
}
</script>
