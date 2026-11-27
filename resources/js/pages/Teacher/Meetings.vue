<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Video,
    CalendarPlus,
    Copy,
    Check,
    Link2,
    Lock,
    Trash2,
    PhoneOff,
    X,
    Search,
} from '@lucide/vue';
import { nextTick, onUnmounted, ref, computed } from 'vue';
import { ensureJitsiScript, mountJitsi } from '@/composables/useMeet';

const props = defineProps<{ meetings: any[] }>();
const page = usePage();
const user = (page.props.auth as any)?.user ?? {};
const isStudent = user.role === 'student';
const prefix = isStudent ? '/student' : '/teacher';

const title = ref('');
const roomName = ref('');
const password = ref('');
const showCreate = ref(false);
const search = ref('');

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();

    if (!q) {
return props.meetings;
}

    return props.meetings.filter(
        (m: any) =>
            (m.title || '').toLowerCase().includes(q) ||
            (m.room || '').toLowerCase().includes(q) ||
            (m.owner_name || '').toLowerCase().includes(q),
    );
});
const creating = ref(false);
const copiedId = ref<number | null>(null);
const joinId = ref<number | null>(null);
const joinFrame = ref<HTMLElement | null>(null);
const promptMeeting = ref<any>(null);
const promptPassword = ref('');
const promptError = ref('');
const verifying = ref(false);
let activeJitsi: any = null;

function createMeeting() {
    creating.value = true;
    router.post(
        `${prefix}/meetings`,
        {
            title: title.value.trim() || null,
            room: roomName.value.trim() || null,
            password: password.value || null,
        },
        {
            onSuccess: () => {
                title.value = '';
                roomName.value = '';
                password.value = '';
                showCreate.value = false;
            },
            onFinish: () => {
                creating.value = false;
            },
        },
    );
}

function remove(m: any) {
    if (
        !window.confirm(`Delete this meeting${m.title ? ` "${m.title}"` : ''}?`)
    ) {
        return;
    }

    router.delete(`${prefix}/meetings/${m.id}`, { preserveScroll: true });
}

function canDelete(m: any) {
    return user?.role === 'superadmin' || m.owner_id === user?.id;
}

async function copyLink(m: any) {
    try {
        await navigator.clipboard.writeText(m.url);
    } catch {
        const el = document.createElement('textarea');
        el.value = m.url;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        el.remove();
    }

    copiedId.value = m.id;
    window.setTimeout(() => {
        copiedId.value = null;
    }, 2000);
}

function requestJoin(m: any) {
    if (m.has_password) {
        promptMeeting.value = m;
        promptPassword.value = '';
        promptError.value = '';

        return;
    }

    startJoin(m, '');
}

async function confirmPassword() {
    const m = promptMeeting.value;

    if (!m) {
        return;
    }

    verifying.value = true;
    promptError.value = '';

    try {
        const token =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';
        const res = await fetch(`${prefix}/meetings/${m.id}/verify`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                Accept: 'application/json',
            },
            body: JSON.stringify({ password: promptPassword.value }),
        });
        const data = await res.json();

        if (!res.ok || !data.ok) {
            promptError.value = data.message || 'Incorrect meeting password.';

            return;
        }

        promptMeeting.value = null;
        startJoin(m, promptPassword.value);
    } catch {
        promptError.value = 'Could not verify the password. Please try again.';
    } finally {
        verifying.value = false;
    }
}

async function startJoin(m: any, pwd: string) {
    joinId.value = m.id;
    await nextTick();
    const frame = joinFrame.value;

    if (!frame) {
        return;
    }

    try {
        await ensureJitsiScript();
        activeJitsi = mountJitsi(frame, m.room, user?.name ?? 'Guest');

        if (m.has_password && pwd) {
            activeJitsi.on('participantRoleChanged', (ev: any) => {
                if (ev.role === 'moderator') {
                    try {
                        activeJitsi.executeCommand('password', pwd);
                    } catch {}
                }
            });
            activeJitsi.on('passwordRequired', () => {
                try {
                    activeJitsi.executeCommand('password', pwd);
                } catch {}
            });
        }
    } catch (e) {
        console.error(e);
        joinId.value = null;
    }
}

function leave() {
    try {
        activeJitsi?.dispose();
    } catch {}

    activeJitsi = null;
    joinId.value = null;
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

onUnmounted(leave);
</script>

<template>
    <Head title="Video Meetings" />

    <div class="px-6 py-6">
        <!-- Header -->
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
            <div>
                <p
                    class="text-xs font-semibold tracking-widest uppercase"
                    style="color: var(--gl-text-muted)"
                >
                    Connect
                </p>
                <h1
                    class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                    style="color: var(--gl-text-primary)"
                >
                    Video Meetings
                </h1>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    {{
                        isStudent
                            ? 'Join your teacher\u2019s meeting from the list below.'
                            : 'Create a meeting link and share it with anyone.'
                    }}
                    <span
                        class="ml-1 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-muted);
                        "
                    >
                        <Link2 class="h-3 w-3" :stroke-width="2" /> Jitsi Meet
                    </span>
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
                        placeholder="Search"
                        class="rounded-full border py-2.5 pr-4 pl-9 text-sm transition-all outline-none focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                        style="
                            background: var(--gl-surface);
                            color: var(--gl-text-primary);
                            border-color: var(--gl-border);
                            width: 180px;
                        "
                    />
                </div>
                <button
                    v-if="!isStudent"
                    @click="showCreate = true"
                    class="flex h-10 w-10 items-center justify-center rounded-xl text-white transition-all hover:scale-105"
                    style="
                        background: linear-gradient(135deg, #ff6a45, #f94f2b);
                        box-shadow: 0 4px 14px rgba(249, 79, 43, 0.4);
                    "
                    title="Create meeting"
                >
                    <CalendarPlus class="h-5 w-5" :stroke-width="2" />
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
                style="
                    background: rgba(124, 58, 237, 0.05);
                    border: 1px solid rgba(124, 58, 237, 0.16);
                "
            >
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl"
                        style="
                            background: linear-gradient(
                                135deg,
                                #7c3aed,
                                #4f46e5
                            );
                            box-shadow: 0 0 12px rgba(124, 58, 237, 0.3);
                        "
                    >
                        <Video class="h-5 w-5 text-white" :stroke-width="2" />
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            @click="copyLink(m)"
                            title="Copy link"
                            class="flex h-8 w-8 items-center justify-center rounded-lg transition-colors hover:bg-[var(--gl-surface-2)]"
                            :style="
                                copiedId === m.id
                                    ? { color: 'var(--gl-success)' }
                                    : { color: 'var(--gl-text-muted)' }
                            "
                        >
                            <Copy
                                v-if="copiedId !== m.id"
                                class="h-4 w-4"
                                :stroke-width="2"
                            />
                            <Check v-else class="h-4 w-4" :stroke-width="2" />
                        </button>
                        <button
                            v-if="canDelete(m)"
                            @click="remove(m)"
                            title="Delete meeting"
                            class="flex h-8 w-8 items-center justify-center rounded-lg transition-colors hover:bg-[var(--gl-danger-bg)]"
                            style="color: var(--gl-text-muted)"
                        >
                            <Trash2 class="h-4 w-4" :stroke-width="2" />
                        </button>
                    </div>
                </div>
                <p
                    class="text-sm leading-snug font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    {{ m.title || 'Untitled meeting' }}
                </p>
                <p
                    class="mt-1 flex items-center gap-1.5 truncate font-mono text-xs"
                    style="color: var(--gl-text-muted)"
                >
                    <Lock
                        v-if="m.has_password"
                        class="h-3.5 w-3.5 shrink-0"
                        style="color: var(--gl-accent)"
                        :stroke-width="2"
                    />
                    {{ m.room }}
                </p>
                <p class="mt-2 text-[11px]" style="color: var(--gl-text-muted)">
                    by {{ m.owner_name || 'Unknown' }} ·
                    {{ formatDate(m.created_at) }}
                </p>
                <button
                    @click="requestJoin(m)"
                    class="mt-3 flex w-full items-center justify-center gap-1.5 rounded-xl px-4 py-2.5 text-xs font-bold text-white transition-all hover:scale-[1.02]"
                    style="
                        background: linear-gradient(
                            135deg,
                            var(--gl-primary),
                            var(--gl-secondary)
                        );
                        box-shadow: 0 0 12px var(--gl-primary-glow);
                    "
                >
                    <Video class="h-3.5 w-3.5" :stroke-width="2" /> Join now
                </button>
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
                <Video
                    class="h-7 w-7"
                    style="color: var(--gl-text-muted)"
                    :stroke-width="1.5"
                />
            </div>
            <p
                class="text-sm font-medium"
                style="color: var(--gl-text-secondary)"
            >
                {{
                    search
                        ? 'No meetings match your search.'
                        : 'No meetings yet.'
                }}
            </p>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                {{
                    search
                        ? 'Try a different keyword.'
                        : isStudent
                          ? 'Your teacher will post a meeting link here when class starts.'
                          : 'Click + above to generate a link anyone can join.'
                }}
            </p>
        </div>
    </div>
    <!-- Create Modal -->
    <Teleport to="body">
        <div
            v-if="showCreate"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0,0,0,0.25); backdrop-filter: blur(2px);"
            @click.self="showCreate = false"
        >
            <div class="no-scrollbar mx-4 max-h-[92vh] w-full max-w-md overflow-y-auto rounded-3xl p-6" style="background: var(--gl-surface); border: 1px solid var(--gl-border); box-shadow: 0 8px 32px rgba(0,0,0,0.4);">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl"
                            style="background: linear-gradient(135deg, var(--gl-secondary), var(--gl-primary)); box-shadow: 0 0 14px var(--gl-secondary-glow);">
                            <Video class="h-5 w-5 text-white" :stroke-width="2" />
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold tracking-tight" style="color: var(--gl-text-primary)">New meeting</h3>
                            <p class="text-[11px]" style="color: var(--gl-text-muted)">Share the link — anyone can join</p>
                        </div>
                    </div>
                    <button @click="showCreate = false" class="flex h-8 w-8 items-center justify-center rounded-full transition-colors hover:bg-[var(--gl-surface-2)]" style="color: var(--gl-text-muted);"><X class="h-4 w-4" :stroke-width="2.5" /></button>
                </div>
                <form @submit.prevent="createMeeting" class="space-y-3.5">
                    <div class="rounded-2xl p-4 space-y-3" style="background: rgba(124,58,237,0.05); border: 1px solid rgba(124,58,237,0.16);">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">
                                Meeting title
                                <span class="font-normal" style="color: var(--gl-text-muted)">(optional)</span>
                            </label>
                            <input v-model="title" type="text" placeholder="e.g. Algebra review"
                                class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">
                                Room name
                                <span class="font-normal" style="color: var(--gl-text-muted)">(optional — fixed link)</span>
                            </label>
                            <input v-model="roomName" type="text" placeholder="e.g. algebra-review"
                                class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                            <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">
                                Becomes meet.jit.si/algebra-review. Leave empty for a random link.
                            </p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">
                                Meeting password
                                <span class="font-normal" style="color: var(--gl-text-muted)">(optional)</span>
                            </label>
                            <input v-model="password" type="password" placeholder="Only people with this password can join"
                                class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2.5 pt-1">
                        <button type="button" @click="showCreate = false" class="rounded-full px-5 py-2.5 text-xs font-bold transition-all hover:scale-[1.02]" style="background: var(--gl-surface-2); color: var(--gl-text-primary); border: 1px solid var(--gl-border);">Cancel</button>
                        <button type="submit" :disabled="creating" class="rounded-full px-5 py-2.5 text-xs font-bold text-white transition-all hover:scale-[1.02] disabled:opacity-50" style="background: linear-gradient(135deg, var(--gl-secondary), var(--gl-primary)); box-shadow: 0 0 14px var(--gl-secondary-glow);">

                            {{ creating ? 'Creating…' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Password Prompt -->
    <Teleport to="body">
        <div
            v-if="promptMeeting"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.25); backdrop-filter: blur(2px)"
            @click.self="promptMeeting = null"
        >
            <div class="w-full max-w-md rounded-xl bg-surface p-6 shadow-lg">
                <h3
                    class="mb-1 text-base font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    Password required
                </h3>
                <p class="mb-4 text-sm" style="color: var(--gl-text-secondary)">
                    {{
                        promptMeeting.title
                            ? `"${promptMeeting.title}" is password protected.`
                            : 'This meeting is password protected.'
                    }}
                    Enter the password to join.
                </p>
                <form @submit.prevent="confirmPassword">
                    <label class="field-label">Meeting password</label>
                    <input
                        v-model="promptPassword"
                        type="password"
                        class="input-field"
                        placeholder="Meeting password"
                        autofocus
                    />
                    <p
                        v-if="promptError"
                        class="mt-1 text-xs"
                        style="color: var(--gl-danger)"
                    >
                        {{ promptError }}
                    </p>
                    <div class="flex items-center justify-end gap-3 pt-4">
                        <button
                            type="button"
                            class="btn-secondary"
                            @click="promptMeeting = null"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="btn-primary"
                            :disabled="verifying || !promptPassword"
                        >
                            {{ verifying ? 'Checking…' : 'Join meeting' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Join Overlay -->
    <div
        v-if="joinId !== null"
        class="fixed inset-0 z-50 flex flex-col"
        style="background: rgba(8, 11, 18, 0.92)"
    >
        <div
            class="flex shrink-0 items-center justify-between gap-3 px-5 py-3"
            style="
                background: var(--gl-surface);
                border-bottom: 1px solid var(--gl-border);
            "
        >
            <div class="flex items-center gap-3">
                <Video
                    class="h-5 w-5"
                    style="color: var(--gl-success)"
                    :stroke-width="2"
                />
                <p
                    class="truncate text-sm font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    {{
                        props.meetings.find((m) => m.id === joinId)?.title ||
                        'Meeting'
                    }}
                </p>
                <button
                    @click="joinId = null"
                    class="rounded-lg p-1.5 transition-colors hover:bg-[var(--gl-surface-2)]"
                    style="color: var(--gl-text-muted)"
                    title="Close"
                >
                    <X class="h-4 w-4" :stroke-width="2" />
                </button>
            </div>
            <button
                @click="leave"
                class="flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold text-white transition-colors hover:opacity-90"
                style="background: var(--gl-danger)"
            >
                <PhoneOff class="h-4 w-4" :stroke-width="2" />
                Leave call
            </button>
        </div>
        <div ref="joinFrame" class="min-h-0 flex-1"></div>
    </div>
</template>
