<template>
    <Teleport to="body">
        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="background: rgba(0, 0, 0, 0.55); backdrop-filter: blur(4px)"
            @click.self="closeModal"
        >
            <div
                class="no-scrollbar max-h-[92vh] w-full max-w-xl overflow-y-auto rounded-3xl p-6 sm:p-7"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
                "
            >
                <!-- Modal Header -->
                <div class="mb-5 flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-white"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    #f59e0b,
                                    #d97706
                                );
                                box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
                            "
                        >
                            <GitMerge class="h-6 w-6" :stroke-width="2" />
                        </div>
                        <div>
                            <h3
                                class="text-lg font-bold tracking-tight"
                                style="color: var(--gl-text-primary)"
                            >
                                Merge Duplicate Accounts
                            </h3>
                            <p
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                            >
                                Consolidate attempts, scores, XP, and messages
                                into one primary account.
                            </p>
                        </div>
                    </div>
                    <button
                        @click="closeModal"
                        class="rounded-xl p-2 transition-colors hover:bg-[var(--gl-surface-2)]"
                        style="color: var(--gl-text-muted)"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Error flash -->
                <div
                    v-if="errorMessage"
                    class="mb-4 flex items-start gap-2.5 rounded-xl px-4 py-3 text-xs"
                    style="
                        background: var(--gl-danger-bg);
                        color: var(--gl-danger);
                        border: 1px solid rgba(239, 68, 68, 0.25);
                    "
                >
                    <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
                    <span>{{ errorMessage }}</span>
                </div>

                <!-- Account Selection Form -->
                <div class="space-y-4">
                    <!-- Source Account (To be absorbed & deleted) -->
                    <div>
                        <label
                            class="mb-1.5 flex items-center justify-between text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            <span class="flex items-center gap-1.5">
                                <span
                                    class="h-2 w-2 rounded-full bg-amber-500"
                                ></span>
                                Duplicate Account (Source to remove)
                            </span>
                            <span
                                class="text-[11px] font-normal"
                                style="color: var(--gl-text-muted)"
                            >
                                Will be deleted after merge
                            </span>
                        </label>
                        <div class="relative">
                            <select
                                v-model.number="sourceId"
                                class="w-full appearance-none rounded-xl border px-3.5 py-2.5 pr-9 text-xs transition-all outline-none focus:border-amber-500 sm:text-sm"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            >
                                <option :value="null">
                                    -- Select duplicate account to absorb --
                                </option>
                                <option
                                    v-for="u in selectableSources"
                                    :key="u.id"
                                    :value="u.id"
                                >
                                    {{ u.name }} ({{ u.email }}) — {{ u.role }}
                                    {{
                                        u.gradeLevel?.name || u.grade
                                            ? `[${u.gradeLevel?.name || u.grade}]`
                                            : ''
                                    }}
                                </option>
                            </select>
                            <ChevronDown
                                class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2"
                                style="color: var(--gl-text-muted)"
                            />
                        </div>
                    </div>

                    <!-- Target Account (Primary to keep) -->
                    <div>
                        <label
                            class="mb-1.5 flex items-center justify-between text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            <span class="flex items-center gap-1.5">
                                <span
                                    class="h-2 w-2 rounded-full bg-emerald-500"
                                ></span>
                                Primary Account (Target to keep)
                            </span>
                            <span
                                class="text-[11px] font-normal"
                                style="color: var(--gl-text-muted)"
                            >
                                Will receive all records
                            </span>
                        </label>
                        <div class="relative">
                            <select
                                v-model.number="targetId"
                                :disabled="!sourceId"
                                class="w-full appearance-none rounded-xl border px-3.5 py-2.5 pr-9 text-xs transition-all outline-none focus:border-emerald-500 disabled:opacity-50 sm:text-sm"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            >
                                <option :value="null">
                                    {{
                                        sourceId
                                            ? '-- Select primary account to preserve --'
                                            : '-- Choose duplicate account first --'
                                    }}
                                </option>
                                <option
                                    v-for="u in selectableTargets"
                                    :key="u.id"
                                    :value="u.id"
                                >
                                    {{ u.name }} ({{ u.email }}) — {{ u.role }}
                                    {{
                                        u.gradeLevel?.name || u.grade
                                            ? `[${u.gradeLevel?.name || u.grade}]`
                                            : ''
                                    }}
                                </option>
                            </select>
                            <ChevronDown
                                class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2"
                                style="color: var(--gl-text-muted)"
                            />
                        </div>
                    </div>

                    <!-- Transfer Overview Card -->
                    <div
                        v-if="selectedSource && selectedTarget"
                        class="rounded-2xl p-4 transition-all"
                        style="
                            background: var(--gl-surface-2);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        <p
                            class="mb-3 text-[11px] font-semibold tracking-wider uppercase"
                            style="color: var(--gl-text-muted)"
                        >
                            Consolidation Preview
                        </p>
                        <div
                            class="grid grid-cols-1 items-center gap-3 text-center sm:grid-cols-[1fr_auto_1fr] sm:text-left"
                        >
                            <div
                                class="rounded-xl p-3"
                                style="
                                    background: rgba(245, 158, 11, 0.08);
                                    border: 1px solid rgba(245, 158, 11, 0.2);
                                "
                            >
                                <p
                                    class="text-[10px] font-semibold tracking-wider text-amber-500 uppercase"
                                >
                                    Duplicate (Absorbed)
                                </p>
                                <p
                                    class="truncate text-xs font-bold"
                                    style="color: var(--gl-text-primary)"
                                >
                                    {{ selectedSource.name }}
                                </p>
                                <p
                                    class="truncate text-[11px]"
                                    style="color: var(--gl-text-muted)"
                                >
                                    {{ selectedSource.email }}
                                </p>
                            </div>

                            <div class="flex justify-center">
                                <div
                                    class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-500/20 text-amber-500"
                                >
                                    <ArrowRight class="h-4 w-4" />
                                </div>
                            </div>

                            <div
                                class="rounded-xl p-3"
                                style="
                                    background: rgba(16, 185, 129, 0.08);
                                    border: 1px solid rgba(16, 185, 129, 0.2);
                                "
                            >
                                <p
                                    class="text-[10px] font-semibold tracking-wider text-emerald-500 uppercase"
                                >
                                    Primary (Preserved)
                                </p>
                                <p
                                    class="truncate text-xs font-bold"
                                    style="color: var(--gl-text-primary)"
                                >
                                    {{ selectedTarget.name }}
                                </p>
                                <p
                                    class="truncate text-[11px]"
                                    style="color: var(--gl-text-muted)"
                                >
                                    {{ selectedTarget.email }}
                                </p>
                            </div>
                        </div>

                        <!-- Records transferred list -->
                        <div
                            class="mt-3.5 space-y-1.5 border-t pt-3 text-xs"
                            style="
                                border-color: var(--gl-border);
                                color: var(--gl-text-secondary);
                            "
                        >
                            <div class="flex items-center gap-2">
                                <Check class="h-3.5 w-3.5 text-emerald-500" />
                                <span
                                    >Assessment attempts & answers (quizzes,
                                    practicals, seatworks, exams)</span
                                >
                            </div>
                            <div class="flex items-center gap-2">
                                <Check class="h-3.5 w-3.5 text-emerald-500" />
                                <span
                                    >Student points & gamification XP
                                    consolidated without duplication</span
                                >
                            </div>
                            <div class="flex items-center gap-2">
                                <Check class="h-3.5 w-3.5 text-emerald-500" />
                                <span
                                    >Messenger chat threads & sent messages
                                    re-attributed</span
                                >
                            </div>
                            <div class="flex items-center gap-2">
                                <Check class="h-3.5 w-3.5 text-emerald-500" />
                                <span
                                    >Game completion progress, rewards & Grade
                                    10 yearbook profile</span
                                >
                            </div>
                            <div
                                v-if="selectedSource.role === 'teacher'"
                                class="flex items-center gap-2"
                            >
                                <Check class="h-3.5 w-3.5 text-emerald-500" />
                                <span
                                    >Teaching materials, schedules & created
                                    activities moved</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Permanent Action Warning Box -->
                    <div
                        class="rounded-xl p-3.5 text-xs"
                        style="
                            background: rgba(239, 68, 68, 0.06);
                            border: 1px solid rgba(239, 68, 68, 0.2);
                            color: var(--gl-text-secondary);
                        "
                    >
                        <p
                            class="mb-1 flex items-center gap-1.5 font-bold text-red-500"
                        >
                            <AlertTriangle class="h-3.5 w-3.5" />
                            Warning: Irreversible Administrative Action
                        </p>
                        <p class="leading-relaxed">
                            Account merging cannot be undone. All activity
                            records, scores, messages, and XP from the duplicate
                            account will be permanently transferred to the
                            primary account, and the duplicate user account will
                            be permanently deleted.
                        </p>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div
                    class="mt-6 flex flex-wrap items-center justify-end gap-3 pt-2"
                >
                    <button
                        type="button"
                        @click="closeModal"
                        :disabled="isMerging"
                        class="rounded-xl px-4 py-2.5 text-xs font-medium transition-colors hover:bg-[var(--gl-surface-2)] sm:text-sm"
                        style="
                            color: var(--gl-text-primary);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="submitMerge"
                        :disabled="!canSubmit || isMerging"
                        class="flex items-center gap-2 rounded-xl px-5 py-2.5 text-xs font-semibold text-white shadow-lg transition-all hover:scale-[1.02] disabled:pointer-events-none disabled:opacity-50 sm:text-sm"
                        style="
                            background: linear-gradient(
                                135deg,
                                #f59e0b,
                                #d97706
                            );
                            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
                        "
                    >
                        <Loader2
                            v-if="isMerging"
                            class="h-4 w-4 animate-spin"
                        />
                        <GitMerge v-else class="h-4 w-4" />
                        <span>{{
                            isMerging
                                ? 'Merging Accounts...'
                                : 'Confirm & Merge Accounts'
                        }}</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import {
    GitMerge,
    X,
    AlertTriangle,
    ArrowRight,
    Check,
    ChevronDown,
    Loader2,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';

const isOpen = defineModel<boolean>('isOpen', { default: false });

const props = withDefaults(
    defineProps<{
        users: any[];
        initialSourceId?: number | null;
        initialTargetId?: number | null;
        lockRole?: string | null;
        basePath?: string;
    }>(),
    {
        users: () => [],
        initialSourceId: null,
        initialTargetId: null,
        lockRole: null,
        basePath: '/admin/users',
    },
);

const emit = defineEmits<{
    (e: 'merged', stats: any): void;
}>();

const page = usePage();
const currentAuthId = computed(() => (page.props.auth as any)?.user?.id);

const sourceId = ref<number | null>(props.initialSourceId ?? null);
const targetId = ref<number | null>(props.initialTargetId ?? null);
const isMerging = ref(false);
const errorMessage = ref<string>('');

watch(
    () => props.initialSourceId,
    (val) => {
        if (val !== undefined) {
            sourceId.value = val;
        }
    },
);

watch(
    () => props.initialTargetId,
    (val) => {
        if (val !== undefined) {
            targetId.value = val;
        }
    },
);

watch(sourceId, (newSource) => {
    if (newSource && targetId.value === newSource) {
        targetId.value = null;
    }
});

const selectableSources = computed(() => {
    return (props.users || []).filter((u) => {
        // Cannot merge the currently logged in superadmin as source
        if (u.id === currentAuthId.value) {
            return false;
        }

        if (props.lockRole && u.role !== props.lockRole) {
            return false;
        }

        return true;
    });
});

const selectedSource = computed(() => {
    if (!sourceId.value) {
        return null;
    }

    return (props.users || []).find((u) => u.id === sourceId.value) || null;
});

const selectableTargets = computed(() => {
    if (!selectedSource.value) {
        return [];
    }

    const sourceRole = selectedSource.value.role;

    return (props.users || []).filter((u) => {
        if (u.id === sourceId.value) {
            return false;
        }

        return u.role === sourceRole;
    });
});

const selectedTarget = computed(() => {
    if (!targetId.value) {
        return null;
    }

    return (props.users || []).find((u) => u.id === targetId.value) || null;
});

const canSubmit = computed(() => {
    return (
        sourceId.value !== null &&
        targetId.value !== null &&
        sourceId.value !== targetId.value &&
        selectedSource.value !== null &&
        selectedTarget.value !== null &&
        selectedSource.value.role === selectedTarget.value.role
    );
});

function closeModal() {
    if (isMerging.value) {
        return;
    }

    isOpen.value = false;
    errorMessage.value = '';
}

function submitMerge() {
    if (!canSubmit.value || isMerging.value) {
        return;
    }

    if (
        !confirm(
            `Are you sure you want to merge "${selectedSource.value.name}" (${selectedSource.value.email}) into "${selectedTarget.value.name}" (${selectedTarget.value.email})?\n\nThis will transfer all assessments, messages, scores, and XP, then permanently delete "${selectedSource.value.name}".`,
        )
    ) {
        return;
    }

    isMerging.value = true;
    errorMessage.value = '';

    router.post(
        `${props.basePath}/merge`,
        {
            source_id: sourceId.value,
            target_id: targetId.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isMerging.value = false;
                isOpen.value = false;
                sourceId.value = null;
                targetId.value = null;
                emit('merged', {
                    source: selectedSource.value,
                    target: selectedTarget.value,
                });
            },
            onError: (err: any) => {
                isMerging.value = false;
                errorMessage.value =
                    err.error ||
                    err.source_id ||
                    err.target_id ||
                    'Failed to merge user accounts. Please check logs and try again.';
            },
        },
    );
}
</script>
