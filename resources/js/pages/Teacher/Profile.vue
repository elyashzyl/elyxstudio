<template>
    <Head title="Profile" />

    <div
        v-if="flash?.success"
        class="gl-fade-in mb-6 rounded-xl px-4 py-3 text-sm"
        style="
            background: var(--gl-success-bg);
            color: var(--gl-success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        "
    >
        {{ flash.success }}
    </div>

    <!-- Header -->
    <div class="mb-6">
        <p
            class="text-xs font-semibold tracking-widest uppercase"
            style="color: var(--gl-text-muted)"
        >
            Account
        </p>
        <h1
            class="mt-1 flex flex-wrap items-center gap-2 text-2xl font-extrabold tracking-tight sm:text-3xl"
            style="color: var(--gl-text-primary)"
        >
            {{ user.name }}
            <span
                class="rounded-full px-2.5 py-1 text-[11px] font-bold"
                :style="
                    user.role === 'superadmin'
                        ? 'background: rgba(251,191,36,0.14); color: var(--gl-accent); border: 1px solid rgba(251,191,36,0.3);'
                        : 'background: rgba(124,58,237,0.1); color: var(--gl-secondary); border: 1px solid rgba(124,58,237,0.25);'
                "
            >
                {{ user.role === 'superadmin' ? 'Superadmin' : 'Teacher' }}
            </span>
        </h1>
        <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
            {{ user.email }}
        </p>
    </div>

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">
            <!-- Messenger Toggle -->
            <div class="gl-glow-card overflow-hidden p-0">
                <div
                    class="border-b px-6 py-4"
                    style="border-color: var(--gl-border)"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg"
                            style="background: rgba(59, 130, 246, 0.12)"
                        >
                            <MessageCircle
                                class="h-4 w-4"
                                style="color: var(--gl-primary)"
                                :stroke-width="2"
                            />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                Messenger
                            </h3>
                            <p
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                            >
                                Enable or disable the messaging feature.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between p-4">
                    <span
                        class="text-sm"
                        style="color: var(--gl-text-secondary)"
                        >Allow students to message you</span
                    >
                    <button
                        @click="toggleMessenger"
                        class="relative h-6 w-12 rounded-full transition-colors"
                        :style="{
                            background: messengerEnabled
                                ? 'var(--gl-success)'
                                : '#475569',
                        }"
                    >
                        <span
                            class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-surface transition-transform"
                            :style="{
                                transform: messengerEnabled
                                    ? 'translateX(24px)'
                                    : 'translateX(0)',
                            }"
                        ></span>
                    </button>
                </div>
                <div class="flex items-center justify-between border-t p-4" style="border-color: var(--gl-border)">
                    <div>
                        <span
                            class="block text-sm"
                            style="color: var(--gl-text-secondary)"
                            >Allow classmates to message each other</span
                        >
                        <span class="text-xs" style="color: var(--gl-text-muted)">
                            {{ allowClassmateMessaging ? 'Students can message each other' : 'Students can only message teachers' }}
                        </span>
                    </div>
                    <button
                        @click="toggleClassmateMessaging"
                        class="relative h-6 w-12 shrink-0 rounded-full transition-colors"
                        :style="{
                            background: allowClassmateMessaging
                                ? 'var(--gl-success)'
                                : '#475569',
                        }"
                    >
                        <span
                            class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-surface transition-transform"
                            :style="{
                                transform: allowClassmateMessaging
                                    ? 'translateX(24px)'
                                    : 'translateX(0)',
                            }"
                        ></span>
                    </button>
                </div>
            </div>

            <!-- System Messenger Toggle -->
            <div
                v-if="user?.role === 'superadmin'"
                class="gl-glow-card overflow-hidden p-0"
            >
                <div
                    class="border-b px-6 py-4"
                    style="border-color: var(--gl-border)"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg"
                            style="background: rgba(16, 185, 129, 0.12)"
                        >
                            <MessageCircle
                                class="h-4 w-4"
                                style="color: var(--gl-success)"
                                :stroke-width="2"
                            />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                System Messenger
                            </h3>
                            <p
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                            >
                                Enable or disable messaging for all users.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between p-4">
                    <span
                        class="text-sm"
                        style="color: var(--gl-text-secondary)"
                        >{{
                            systemMessengerEnabled
                                ? 'Enabled for all users'
                                : 'Disabled for all users'
                        }}</span
                    >
                    <button
                        @click="toggleSystemMessenger"
                        class="relative h-6 w-12 rounded-full transition-colors"
                        :style="{
                            background: systemMessengerEnabled
                                ? 'var(--gl-success)'
                                : '#475569',
                        }"
                    >
                        <span
                            class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-surface transition-transform"
                            :style="{
                                transform: systemMessengerEnabled
                                    ? 'translateX(24px)'
                                    : 'translateX(0)',
                            }"
                        ></span>
                    </button>
                </div>
                <div class="flex items-center justify-between border-t p-4" style="border-color: var(--gl-border)">
                    <div>
                        <span
                            class="block text-sm"
                            style="color: var(--gl-text-secondary)"
                            >System Classmate Messaging</span
                        >
                        <span class="text-xs" style="color: var(--gl-text-muted)">
                            {{ systemClassmateMessagingEnabled ? 'Enabled system-wide' : 'Disabled system-wide (students only message teachers)' }}
                        </span>
                    </div>
                    <button
                        @click="toggleSystemClassmateMessaging"
                        class="relative h-6 w-12 shrink-0 rounded-full transition-colors"
                        :style="{
                            background: systemClassmateMessagingEnabled
                                ? 'var(--gl-success)'
                                : '#475569',
                        }"
                    >
                        <span
                            class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-surface transition-transform"
                            :style="{
                                transform: systemClassmateMessagingEnabled
                                    ? 'translateX(24px)'
                                    : 'translateX(0)',
                            }"
                        ></span>
                    </button>
                </div>
            </div>

            <ProfileCustomizationCard :user="user" />

            <!-- Appearance -->
            <div class="gl-glow-card overflow-hidden p-0">
                <div
                    class="border-b px-6 py-4"
                    style="border-color: var(--gl-border)"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg"
                            style="background: rgba(124, 58, 237, 0.12)"
                        >
                            <Palette
                                class="h-4 w-4"
                                style="color: var(--gl-secondary)"
                                :stroke-width="2"
                            />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                Appearance
                            </h3>
                            <p
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                            >
                                Pick a theme — it applies across the whole
                                system.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="p-4 sm:p-6">
                    <ThemePicker />
                </div>
            </div>

            <!-- Password Form -->
            <div class="gl-glow-card overflow-hidden p-0">
                <div
                    class="border-b px-6 py-4"
                    style="border-color: var(--gl-border)"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg"
                            style="background: rgba(59, 130, 246, 0.12)"
                        >
                            <Lock
                                class="h-4 w-4"
                                style="color: var(--gl-primary)"
                                :stroke-width="2"
                            />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                Update Password
                            </h3>
                            <p
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                            >
                                Ensure your account is using a strong password.
                            </p>
                        </div>
                    </div>
                </div>
                <form @submit.prevent="updatePassword" class="space-y-4 p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="field-label"
                                style="color: var(--gl-text-secondary)"
                                >Current password</label
                            >
                            <input
                                v-model="passwordForm.current_password"
                                type="password"
                                required
                                autocomplete="current-password"
                                class="w-full rounded-xl border px-4 py-2.5 text-sm outline-none"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            />
                            <p
                                v-if="passwordForm.errors.current_password"
                                class="mt-1 text-xs"
                                style="color: var(--gl-danger)"
                            >
                                {{ passwordForm.errors.current_password }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="field-label"
                                style="color: var(--gl-text-secondary)"
                                >New password</label
                            >
                            <input
                                v-model="passwordForm.password"
                                type="password"
                                required
                                autocomplete="new-password"
                                class="w-full rounded-xl border px-4 py-2.5 text-sm outline-none"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            />
                            <p
                                v-if="passwordForm.errors.password"
                                class="mt-1 text-xs"
                                style="color: var(--gl-danger)"
                            >
                                {{ passwordForm.errors.password }}
                            </p>
                        </div>
                    </div>
                    <div>
                        <label
                            class="field-label"
                            style="color: var(--gl-text-secondary)"
                            >Confirm new password</label
                        >
                        <input
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="w-full rounded-xl border px-4 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        />
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="rounded-xl px-5 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02] disabled:opacity-50"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-secondary),
                                    var(--gl-primary)
                                );
                                box-shadow: 0 0 12px var(--gl-secondary-glow);
                            "
                        >
                            Save
                        </button>
                        <span
                            v-if="passwordForm.recentlySuccessful"
                            class="text-sm"
                            style="color: var(--gl-success)"
                            >Saved.</span
                        >
                    </div>
                </form>
            </div>

            <!-- Delete Account -->
            <div
                class="gl-glow-card p-6"
                style="border-color: rgba(239, 68, 68, 0.2)"
            >
                <div class="mb-4 flex items-center gap-3">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg"
                        style="background: var(--gl-danger-bg)"
                    >
                        <Trash2
                            class="h-4 w-4"
                            style="color: var(--gl-danger)"
                            :stroke-width="2"
                        />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-semibold"
                            style="color: var(--gl-danger)"
                        >
                            Delete Account
                        </h3>
                        <p class="text-xs" style="color: var(--gl-text-muted)">
                            Permanently delete your account. This cannot be
                            undone.
                        </p>
                    </div>
                </div>
                <button
                    @click="showDelete = true"
                    class="rounded-xl px-4 py-2.5 text-sm font-medium transition-all"
                    style="
                        background: var(--gl-danger-bg);
                        color: var(--gl-danger);
                        border: 1px solid rgba(239, 68, 68, 0.2);
                    "
                >
                    <Trash2 class="mr-1.5 inline h-4 w-4" :stroke-width="2" />
                    Delete account
                </button>
            </div>
        </div>
        <!-- /left column -->

        <!-- Identity card -->
        <aside class="xl:sticky xl:top-6">
            <div
                class="overflow-hidden rounded-2xl p-5 text-center"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center overflow-hidden rounded-full text-xl font-bold text-white"
                    style="
                        background: linear-gradient(
                            135deg,
                            var(--gl-secondary),
                            var(--gl-primary)
                        );
                        box-shadow: 0 0 18px var(--gl-secondary-glow);
                    "
                >
                    <img
                        v-if="user.avatar"
                        :src="user.avatar"
                        :alt="user.name"
                        class="h-full w-full object-cover"
                    />
                    <span v-else>{{ (user.name || '?').charAt(0) }}</span>
                </div>
                <p
                    class="mt-2 truncate text-sm font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    {{ user.name }}
                </p>
                <p
                    class="truncate text-[11px]"
                    style="color: var(--gl-text-muted)"
                >
                    {{ user.email }}
                </p>
                <div
                    class="mt-3 space-y-2 border-t pt-3 text-left"
                    style="border-color: var(--gl-border)"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span
                            class="text-[11px]"
                            style="color: var(--gl-text-muted)"
                            >Role</span
                        >
                        <span
                            class="text-xs font-semibold capitalize"
                            style="color: var(--gl-text-primary)"
                            >{{ user.role }}</span
                        >
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span
                            class="text-[11px]"
                            style="color: var(--gl-text-muted)"
                            >Messenger</span
                        >
                        <span
                            class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                            :style="
                                messengerEnabled
                                    ? 'background: rgba(16,185,129,0.12); color: var(--gl-success);'
                                    : 'background: var(--gl-surface-2); color: var(--gl-text-muted);'
                            "
                        >
                            {{ messengerEnabled ? 'On' : 'Off' }}
                        </span>
                    </div>
                </div>
            </div>
        </aside>
    </div>
    <!-- /grid -->

    <!-- Delete Modal -->
    <Teleport to="body">
        <div
            v-if="showDelete"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.25); backdrop-filter: blur(2px)"
            @click.self="showDelete = false"
        >
            <div
                class="mx-4 w-full max-w-md rounded-2xl p-6"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
                "
            >
                <div
                    class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl"
                    style="background: var(--gl-danger-bg)"
                >
                    <Trash2
                        class="h-7 w-7"
                        style="color: var(--gl-danger)"
                        :stroke-width="2"
                    />
                </div>
                <h3
                    class="mb-2 text-center text-base font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    Delete Account
                </h3>
                <p
                    class="text-center text-sm"
                    style="color: var(--gl-text-secondary)"
                >
                    Enter your password to confirm. This action cannot be
                    undone.
                </p>
                <form @submit.prevent="deleteAccount">
                    <div class="mt-4">
                        <input
                            v-model="deleteForm.password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="Password"
                            class="w-full rounded-xl border px-4 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        />
                        <p
                            v-if="deleteForm.errors.password"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ deleteForm.errors.password }}
                        </p>
                    </div>
                    <div class="mt-5 flex items-center justify-center gap-3">
                        <button
                            type="button"
                            @click="showDelete = false"
                            class="rounded-xl px-4 py-2.5 text-sm font-medium"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="deleteForm.processing"
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02]"
                            style="background: var(--gl-danger)"
                        >
                            {{
                                deleteForm.processing ? 'Deleting...' : 'Delete'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Trash2, User, Lock, MessageCircle, Palette } from '@lucide/vue';
import { computed, ref } from 'vue';
import ProfileCustomizationCard from '@/components/ProfileCustomizationCard.vue';
import ThemePicker from '@/components/ThemePicker.vue';
import { send } from '@/routes/verification';

const props = defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
    gradeLevels?: { id: number; name: string }[];
    sections?: { id: number; name: string; grade_level_id: number }[];
}>();

const page = usePage();
const flash = computed(() => (page.props as any).flash);
const user = computed(() => (page.props.auth as any).user);
const messengerEnabled = ref(user.value?.messenger_enabled != false);
const allowClassmateMessaging = computed(
    () => user.value?.allow_classmate_messaging !== false,
);
const showDelete = ref(false);

function toggleMessenger() {
    router.patch(
        '/teacher/profile/toggle-messenger',
        {},
        { preserveScroll: true },
    );
}

function toggleClassmateMessaging() {
    router.patch(
        '/teacher/profile/toggle-classmate-messaging',
        {},
        { preserveScroll: true },
    );
}

const systemMessengerEnabled = computed(
    () => (page.props as any).messenger_system_enabled !== false,
);

const systemClassmateMessagingEnabled = computed(
    () => (page.props as any).classmate_messaging_system_enabled !== false,
);

function toggleSystemMessenger() {
    router.patch(
        '/admin/users/toggle-system-messenger',
        {},
        { preserveScroll: true },
    );
}

function toggleSystemClassmateMessaging() {
    router.patch(
        '/admin/users/toggle-classmate-messaging',
        {},
        { preserveScroll: true },
    );
}

const profileForm = useForm({
    name: user.value.name,
    email: user.value.email,
    gender: user.value.gender || '',
    grade_level_id: user.value.grade_level_id ?? '',
    section_id: user.value.section_id ?? '',
});

const filteredSections = computed(() =>
    profileForm.grade_level_id
        ? (props.sections || []).filter(
              (s) =>
                  String(s.grade_level_id) ===
                  String(profileForm.grade_level_id),
          )
        : props.sections || [],
);

function updateProfile() {
    profileForm.patch('/settings/profile', { preserveScroll: true });
}

function resendVerification() {
    router.post(send.url());
}

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function updatePassword() {
    passwordForm.put('/settings/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

const deleteForm = useForm({ password: '' });

function deleteAccount() {
    deleteForm.delete('/settings/profile', {
        preserveScroll: true,
        onSuccess: () => {
            showDelete.value = false;
        },
    });
}
</script>
