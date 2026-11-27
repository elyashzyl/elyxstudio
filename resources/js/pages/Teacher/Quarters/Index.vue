<template>
    <Head title="Quarters" />

    <div
        v-if="flash?.success"
        class="mb-6 rounded-xl px-4 py-3 text-sm font-medium"
        style="
            background: var(--gl-success-bg, #dceee3);
            color: var(--gl-success, #2f7a54);
            border: 1px solid rgba(16, 185, 129, 0.2);
        "
    >
        {{ flash.success }}
    </div>

    <!-- Header -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span
                    class="rounded-md px-2 py-0.5 text-[11px] font-bold tracking-wider uppercase"
                    style="
                        background: rgba(59, 130, 246, 0.12);
                        color: var(--gl-primary);
                    "
                >
                    Academic Periods
                </span>
            </div>
            <h1
                class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                Quarters
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                Configure grading quarters for materials, games, and assessments.
            </p>
        </div>
        <button
            @click="openAddModal"
            class="flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-xs font-bold text-white shadow-md transition-all hover:scale-[1.02] active:scale-[0.98]"
            style="background: linear-gradient(135deg, var(--gl-primary), #2563eb)"
        >
            <Plus class="h-4 w-4" :stroke-width="2.5" />
            Add Quarter
        </button>
    </div>

    <!-- Quarters List / Empty State -->
    <div
        v-if="!localQuarters.length"
        class="flex flex-col items-center justify-center rounded-2xl border p-12 text-center"
        style="
            background: var(--gl-surface);
            border-color: var(--gl-border);
        "
    >
        <div
            class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl"
            style="background: var(--gl-surface-2)"
        >
            <Calendar
                class="h-7 w-7"
                style="color: var(--gl-text-muted)"
                :stroke-width="1.75"
            />
        </div>
        <h3
            class="text-base font-semibold"
            style="color: var(--gl-text-primary)"
        >
            No quarters configured
        </h3>
        <p class="mt-1 max-w-sm text-xs" style="color: var(--gl-text-muted)">
            Add academic quarters to organize student assignments, materials, and learning games.
        </p>
        <button
            @click="openAddModal"
            class="mt-4 flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-bold text-white shadow-sm"
            style="background: var(--gl-primary)"
        >
            <Plus class="h-4 w-4" :stroke-width="2" />
            Add Quarter
        </button>
    </div>

    <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div
            v-for="q in localQuarters"
            :key="q.id"
            class="relative flex flex-col justify-between overflow-hidden rounded-2xl border p-5 transition-all hover:shadow-md"
            style="
                background: var(--gl-surface);
                border-color: var(--gl-border);
            "
        >
            <!-- Top bar: Code badge & Actions -->
            <div>
                <div class="flex items-center justify-between gap-2">
                    <span
                        class="rounded-xl px-3 py-1 text-xs font-black tracking-wide"
                        :style="{
                            background: q.is_active ? 'rgba(59, 130, 246, 0.14)' : 'var(--gl-surface-2)',
                            color: q.is_active ? 'var(--gl-primary)' : 'var(--gl-text-muted)',
                        }"
                    >
                        {{ q.code || ('Q' + (q.display_order || '')) }}
                    </span>
                    <div class="flex items-center gap-1">
                        <span
                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold"
                            :style="{
                                background: q.is_active ? 'rgba(16, 185, 129, 0.12)' : 'rgba(239, 68, 68, 0.12)',
                                color: q.is_active ? 'var(--gl-success, #10b981)' : 'var(--gl-danger, #ef4444)',
                            }"
                        >
                            <span class="h-1.5 w-1.5 rounded-full" :style="{ background: q.is_active ? '#10b981' : '#ef4444' }"></span>
                            {{ q.is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <button
                            @click="openEdit(q)"
                            class="rounded-lg p-1.5 transition-colors hover:bg-[var(--gl-surface-2)]"
                            style="color: var(--gl-text-secondary)"
                            title="Edit quarter"
                        >
                            <Pencil class="h-3.5 w-3.5" :stroke-width="2" />
                        </button>
                        <button
                            @click="confirmDelete(q)"
                            class="rounded-lg p-1.5 transition-colors hover:bg-[var(--gl-danger-bg,rgba(239,68,68,0.1))]"
                            style="color: var(--gl-danger, #ef4444)"
                            title="Delete quarter"
                        >
                            <Trash2 class="h-3.5 w-3.5" :stroke-width="2" />
                        </button>
                    </div>
                </div>

                <h3
                    class="mt-3 text-lg font-bold tracking-tight"
                    style="color: var(--gl-text-primary)"
                >
                    {{ q.name }}
                </h3>

                <p
                    v-if="q.description"
                    class="mt-1 line-clamp-2 text-xs"
                    style="color: var(--gl-text-secondary)"
                >
                    {{ q.description }}
                </p>

                <!-- Date range -->
                <div
                    class="mt-3 flex items-center gap-1.5 text-xs font-medium"
                    style="color: var(--gl-text-muted)"
                >
                    <Calendar class="h-3.5 w-3.5 shrink-0" :stroke-width="2" />
                    <span v-if="q.start_date || q.end_date">
                        {{ formatDate(q.start_date) }} – {{ formatDate(q.end_date) }}
                    </span>
                    <span v-else class="italic">Schedule open</span>
                </div>
            </div>

            <!-- Stats breakdown -->
            <div
                class="mt-5 grid grid-cols-3 gap-1 rounded-xl p-2.5 text-center"
                style="background: var(--gl-surface-2)"
            >
                <div>
                    <p class="text-xs font-extrabold tabular-nums" style="color: var(--gl-text-primary)">
                        {{ q.teaching_materials_count ?? 0 }}
                    </p>
                    <p class="text-[10px]" style="color: var(--gl-text-muted)">Materials</p>
                </div>
                <div>
                    <p class="text-xs font-extrabold tabular-nums" style="color: var(--gl-text-primary)">
                        {{ q.games_count ?? 0 }}
                    </p>
                    <p class="text-[10px]" style="color: var(--gl-text-muted)">Games</p>
                </div>
                <div>
                    <p class="text-xs font-extrabold tabular-nums" style="color: var(--gl-text-primary)">
                        {{ (q.quizzes_count ?? 0) + (q.seatworks_count ?? 0) + (q.practicals_count ?? 0) + (q.exams_count ?? 0) }}
                    </p>
                    <p class="text-[10px]" style="color: var(--gl-text-muted)">Activities</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form (Add / Edit) -->
    <Teleport to="body">
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="background: rgba(0, 0, 0, 0.45); backdrop-filter: blur(4px)"
            @click.self="showModal = false"
        >
            <div
                class="w-full max-w-md rounded-2xl border p-6 shadow-2xl transition-all"
                style="
                    background: var(--gl-surface);
                    border-color: var(--gl-border);
                "
            >
                <div class="mb-4 flex items-center justify-between">
                    <h3
                        class="text-base font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ editingQuarter ? 'Edit Quarter' : 'Create Quarter' }}
                    </h3>
                    <button
                        @click="showModal = false"
                        class="rounded-lg p-1.5 transition-colors hover:bg-[var(--gl-surface-2)]"
                        style="color: var(--gl-text-muted)"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form @submit.prevent="submitQuarter" class="space-y-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label
                                class="mb-1 block text-xs font-medium"
                                style="color: var(--gl-text-secondary)"
                            >
                                Quarter Name *
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="e.g. 1st Quarter"
                                required
                                class="w-full rounded-xl border px-3.5 py-2 text-xs outline-none transition-all focus:border-[var(--gl-primary)]"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            />
                            <p v-if="formErrors.name" class="mt-1 text-xs text-rose-500">
                                {{ formErrors.name }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium"
                                style="color: var(--gl-text-secondary)"
                            >
                                Code
                            </label>
                            <input
                                v-model="form.code"
                                type="text"
                                placeholder="e.g. Q1"
                                class="w-full rounded-xl border px-3.5 py-2 text-xs uppercase outline-none transition-all focus:border-[var(--gl-primary)]"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            />
                            <p v-if="formErrors.code" class="mt-1 text-xs text-rose-500">
                                {{ formErrors.code }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium"
                                style="color: var(--gl-text-secondary)"
                            >
                                Start Date
                            </label>
                            <input
                                v-model="form.start_date"
                                type="date"
                                class="w-full rounded-xl border px-3.5 py-2 text-xs outline-none transition-all focus:border-[var(--gl-primary)]"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium"
                                style="color: var(--gl-text-secondary)"
                            >
                                End Date
                            </label>
                            <input
                                v-model="form.end_date"
                                type="date"
                                class="w-full rounded-xl border px-3.5 py-2 text-xs outline-none transition-all focus:border-[var(--gl-primary)]"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                        >
                            Description
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Optional quarter notes or coverage summary..."
                            class="w-full rounded-xl border px-3.5 py-2 text-xs outline-none transition-all focus:border-[var(--gl-primary)]"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3 items-center">
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium"
                                style="color: var(--gl-text-secondary)"
                            >
                                Order
                            </label>
                            <input
                                v-model.number="form.display_order"
                                type="number"
                                min="0"
                                class="w-full rounded-xl border px-3.5 py-2 text-xs outline-none transition-all focus:border-[var(--gl-primary)]"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            />
                        </div>
                        <div class="pt-4 flex items-center gap-2">
                            <input
                                id="quarter_is_active"
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-4 w-4 rounded border-[var(--gl-border)]"
                                style="accent-color: var(--gl-primary)"
                            />
                            <label
                                for="quarter_is_active"
                                class="text-xs font-semibold cursor-pointer"
                                style="color: var(--gl-text-primary)"
                            >
                                Active quarter
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="rounded-xl border px-4 py-2 text-xs font-semibold transition-colors hover:bg-[var(--gl-surface-2)]"
                            style="
                                border-color: var(--gl-border);
                                color: var(--gl-text-secondary);
                            "
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-xl px-5 py-2 text-xs font-bold text-white shadow-sm transition-all hover:scale-[1.02] active:scale-[0.98]"
                            style="background: var(--gl-primary)"
                        >
                            {{ editingQuarter ? 'Save Changes' : 'Create Quarter' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Plus, Calendar, Pencil, Trash2, X } from '@lucide/vue';
import { ref, computed, watch } from 'vue';

const props = defineProps<{
    quarters: any[];
}>();

const localQuarters = ref<any[]>([...(props.quarters || [])]);

watch(
    () => props.quarters,
    (val) => {
        localQuarters.value = [...(val || [])];
    },
    { deep: true },
);

const page = usePage();
const flash = computed(() => (page.props as any).flash);

const showModal = ref(false);
const editingQuarter = ref<any>(null);
const form = ref({
    name: '',
    code: '',
    description: '',
    display_order: 1,
    is_active: true,
    start_date: '',
    end_date: '',
});
const formErrors = ref<any>({});

function formatDate(dateStr: string | null): string {
    if (!dateStr) {
return '—';
}

    try {
        const d = new Date(dateStr);

        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    } catch {
        return dateStr;
    }
}

function openAddModal() {
    editingQuarter.value = null;
    const nextOrder = (localQuarters.value.length || 0) + 1;
    form.value = {
        name: '',
        code: `Q${nextOrder}`,
        description: '',
        display_order: nextOrder,
        is_active: true,
        start_date: '',
        end_date: '',
    };
    formErrors.value = {};
    showModal.value = true;
}

function openEdit(q: any) {
    editingQuarter.value = q;
    form.value = {
        name: q.name,
        code: q.code || '',
        description: q.description || '',
        display_order: q.display_order ?? 1,
        is_active: Boolean(q.is_active),
        start_date: q.start_date ? q.start_date.substring(0, 10) : '',
        end_date: q.end_date ? q.end_date.substring(0, 10) : '',
    };
    formErrors.value = {};
    showModal.value = true;
}

function submitQuarter() {
    formErrors.value = {};

    if (editingQuarter.value) {
        router.put(`/teacher/quarters/${editingQuarter.value.id}`, form.value, {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
            onError: (errs) => {
                formErrors.value = errs;
            },
        });
    } else {
        router.post('/teacher/quarters', form.value, {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
            onError: (errs) => {
                formErrors.value = errs;
            },
        });
    }
}

function confirmDelete(q: any) {
    if (confirm(`Are you sure you want to delete "${q.name}"? Existing linked activities will remain safe.`)) {
        router.delete(`/teacher/quarters/${q.id}`, {
            preserveScroll: true,
        });
    }
}
</script>
