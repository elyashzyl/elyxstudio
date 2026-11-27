<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    CheckCircle2,
    Clock,
    Eye,
    EyeOff,
    Plus,
    Trash2,
    Users,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

type PollOption = {
    text: string;
    votes: number;
    percentage: number;
};

type Poll = {
    id: number;
    question: string;
    options: PollOption[];
    is_active: boolean;
    total_votes: number;
    grade_level: { id: number; name: string } | null;
    section: { id: number; name: string } | null;
    teacher_name: string;
    created_at: string | null;
};

const props = defineProps<{
    polls: {
        data: Poll[];
        links?: any[];
        current_page?: number;
        last_page?: number;
    };
    gradeLevels: { id: number; name: string }[];
    sections: { id: number; name: string; grade_level_id: number }[];
}>();

const page = usePage();
const flash = computed(() => (page.props as any).flash);
const showCreateModal = ref(false);
const search = ref('');

const form = useForm({
    question: '',
    options: ['', ''],
    target_grade_level_id: '' as string | number,
    target_section_id: '' as string | number,
    is_active: true,
});

const filteredSections = computed(() => {
    if (!form.target_grade_level_id) {
return [];
}

    return props.sections.filter(
        (section) => section.grade_level_id === Number(form.target_grade_level_id),
    );
});

const filteredPolls = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (!query) {
return props.polls.data || [];
}

    return (props.polls.data || []).filter(
        (poll) =>
            poll.question.toLowerCase().includes(query) ||
            poll.teacher_name.toLowerCase().includes(query),
    );
});

function openCreateModal() {
    form.reset();
    form.question = '';
    form.options = ['', ''];
    form.target_grade_level_id = '';
    form.target_section_id = '';
    form.is_active = true;
    showCreateModal.value = true;
}

function addOption() {
    if (form.options.length < 10) {
form.options.push('');
}
}

function removeOption(index: number) {
    if (form.options.length <= 2) {
return;
}

    form.options.splice(index, 1);
}

function submitCreate() {
    form.post('/teacher/polls', {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
}

function toggleActive(poll: Poll) {
    router.patch(`/teacher/polls/${poll.id}/toggle-active`, {}, { preserveScroll: true });
}

function deletePoll(poll: Poll) {
    if (!confirm(`Delete this poll? All ${poll.total_votes} vote(s) will also be removed.`)) {
return;
}

    router.delete(`/teacher/polls/${poll.id}`, { preserveScroll: true });
}

function audienceLabel(poll: Poll) {
    if (poll.grade_level) {
        return poll.section ? `${poll.grade_level.name} - ${poll.section.name}` : poll.grade_level.name;
    }

    return 'All Students';
}
</script>

<template>
    <Head title="Classroom Polls" />

    <div class="space-y-6 px-3.5 py-4 sm:px-0 sm:py-0">
        <div
            v-if="flash?.success"
            class="rounded-2xl px-5 py-3 text-sm font-medium"
            style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.35); color: var(--gl-success)"
        >
            {{ flash.success }}
        </div>
        <div
            v-if="flash?.error"
            class="rounded-2xl px-5 py-3 text-sm font-medium"
            style="background: var(--gl-danger-bg); border: 1px solid rgba(239, 68, 68, 0.35); color: var(--gl-danger)"
        >
            {{ flash.error }}
        </div>

        <div class="gl-glow-card flex flex-col items-start justify-between gap-4 p-6 md:flex-row md:items-center">
            <div class="flex items-center gap-3.5">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl"
                    style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(139, 92, 246, 0.2)); border: 1px solid rgba(59, 130, 246, 0.35)"
                >
                    <BarChart3 class="h-6 w-6" style="color: var(--gl-primary)" />
                </div>
                <div>
                    <h1 class="text-xl font-bold" style="color: var(--gl-text-primary)">Classroom Polls</h1>
                    <p class="text-xs" style="color: var(--gl-text-muted)">
                        Ask students quick questions and review their responses in real time.
                    </p>
                </div>
            </div>
            <button
                type="button"
                @click="openCreateModal"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold text-white transition-all hover:scale-105"
                style="background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary)); box-shadow: 0 0 16px var(--gl-primary-glow)"
            >
                <Plus class="h-4 w-4" /> New Poll
            </button>
        </div>

        <div class="flex justify-end">
            <input
                v-model="search"
                type="search"
                placeholder="Search polls..."
                class="w-full rounded-xl px-3.5 py-2 text-xs outline-none sm:w-72"
                style="background: var(--gl-surface); border: 1px solid var(--gl-border); color: var(--gl-text-primary)"
            />
        </div>

        <div v-if="filteredPolls.length" class="grid gap-4 lg:grid-cols-2">
            <div
                v-for="poll in filteredPolls"
                :key="poll.id"
                class="gl-glow-card flex flex-col gap-4 p-5"
                :style="poll.is_active ? { border: '1px solid rgba(16, 185, 129, 0.35)' } : {}"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <span
                                class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                :style="poll.is_active ? 'background: rgba(16, 185, 129, 0.15); color: var(--gl-success)' : 'background: var(--gl-surface-2); color: var(--gl-text-muted)'"
                            >
                                {{ poll.is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <span class="text-[10px]" style="color: var(--gl-text-muted)">Poll #{{ poll.id }}</span>
                        </div>
                        <h2 class="text-sm font-bold leading-snug" style="color: var(--gl-text-primary)">
                            {{ poll.question }}
                        </h2>
                    </div>
                    <button
                        type="button"
                        @click="deletePoll(poll)"
                        title="Delete poll"
                        class="shrink-0 rounded-lg p-1.5 transition-colors hover:bg-red-500/10"
                        style="color: var(--gl-danger)"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>

                <div class="space-y-2">
                    <div v-for="option in poll.options" :key="option.text" class="space-y-1">
                        <div class="flex items-center justify-between gap-3 text-[11px]">
                            <span class="min-w-0 truncate" style="color: var(--gl-text-secondary)">{{ option.text }}</span>
                            <span class="shrink-0 font-semibold" style="color: var(--gl-text-muted)">
                                {{ option.votes }} · {{ option.percentage }}%
                            </span>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full" style="background: var(--gl-surface-2)">
                            <div
                                class="h-full rounded-full transition-all"
                                :style="{ width: `${option.percentage}%`, background: 'var(--gl-primary)' }"
                            ></div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 border-t pt-3 text-[10px]" style="border-color: var(--gl-border); color: var(--gl-text-muted)">
                    <span class="inline-flex items-center gap-1.5">
                        <Users class="h-3.5 w-3.5" /> {{ audienceLabel(poll) }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <BarChart3 class="h-3.5 w-3.5" /> {{ poll.total_votes }} vote{{ poll.total_votes === 1 ? '' : 's' }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <Clock class="h-3.5 w-3.5" /> {{ poll.created_at }}
                    </span>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="toggleActive(poll)"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors"
                        :style="poll.is_active ? 'background: rgba(245, 158, 11, 0.12); color: #d97706' : 'background: rgba(16, 185, 129, 0.12); color: var(--gl-success)'"
                    >
                        <EyeOff v-if="poll.is_active" class="h-3.5 w-3.5" />
                        <Eye v-else class="h-3.5 w-3.5" />
                        {{ poll.is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                </div>
            </div>
        </div>

        <div v-else class="gl-glow-card flex flex-col items-center justify-center p-12 text-center">
            <BarChart3 class="mb-3 h-10 w-10 opacity-40" style="color: var(--gl-text-muted)" />
            <h2 class="text-sm font-bold" style="color: var(--gl-text-primary)">No classroom polls found</h2>
            <p class="mt-1 max-w-sm text-xs" style="color: var(--gl-text-muted)">
                Create a poll to ask students a quick question and collect responses.
            </p>
        </div>

        <div v-if="polls.last_page && polls.last_page > 1" class="flex items-center justify-center gap-2">
            <a
                v-for="link in polls.links"
                :key="link.label"
                :href="link.url || undefined"
                class="rounded-lg px-3 py-1.5 text-xs"
                :class="{ 'pointer-events-none opacity-40': !link.url, 'font-bold': link.active }"
                :style="link.active ? 'background: var(--gl-primary); color: #fff' : 'background: var(--gl-surface); color: var(--gl-text-secondary); border: 1px solid var(--gl-border)'"
                v-html="link.label"
            ></a>
        </div>
    </div>

    <div
        v-if="showCreateModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        @click.self="showCreateModal = false"
    >
        <div class="gl-glow-card max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-3xl p-6" style="background: var(--gl-surface); border: 1px solid var(--gl-border)">
            <div class="mb-5 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl" style="background: rgba(59, 130, 246, 0.15); color: var(--gl-primary)">
                        <BarChart3 class="h-4 w-4" />
                    </div>
                    <h2 class="text-base font-bold" style="color: var(--gl-text-primary)">Create Classroom Poll</h2>
                </div>
                <button type="button" @click="showCreateModal = false" class="rounded-xl p-1.5" style="color: var(--gl-text-muted)">
                    <X class="h-4 w-4" />
                </button>
            </div>

            <form class="space-y-4" @submit.prevent="submitCreate">
                <div>
                    <label class="mb-1 block text-xs font-semibold" style="color: var(--gl-text-primary)">Question *</label>
                    <textarea
                        v-model="form.question"
                        rows="3"
                        maxlength="500"
                        required
                        placeholder="What should the class review next?"
                        class="w-full rounded-xl px-3.5 py-2 text-xs outline-none"
                        style="background: var(--gl-surface-2); border: 1px solid var(--gl-border); color: var(--gl-text-primary)"
                    ></textarea>
                    <p v-if="form.errors.question" class="mt-1 text-[11px] text-red-500">{{ form.errors.question }}</p>
                </div>

                <div>
                    <div class="mb-1 flex items-center justify-between">
                        <label class="text-xs font-semibold" style="color: var(--gl-text-primary)">Answer Options *</label>
                        <button type="button" @click="addOption" :disabled="form.options.length >= 10" class="inline-flex items-center gap-1 text-[11px] font-semibold disabled:opacity-40" style="color: var(--gl-primary)">
                            <Plus class="h-3.5 w-3.5" /> Add option
                        </button>
                    </div>
                    <div v-for="(_, index) in form.options" :key="index" class="mb-2 flex items-center gap-2">
                        <input
                            v-model="form.options[index]"
                            type="text"
                            :placeholder="`Option ${index + 1}`"
                            maxlength="255"
                            required
                            class="w-full rounded-xl px-3.5 py-2 text-xs outline-none"
                            style="background: var(--gl-surface-2); border: 1px solid var(--gl-border); color: var(--gl-text-primary)"
                        />
                        <button v-if="form.options.length > 2" type="button" @click="removeOption(index)" title="Remove option" class="rounded-lg p-1.5" style="color: var(--gl-danger)">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                    <p v-if="form.errors.options" class="mt-1 text-[11px] text-red-500">{{ form.errors.options }}</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold" style="color: var(--gl-text-primary)">Target Grade</label>
                        <select v-model="form.target_grade_level_id" @change="form.target_section_id = ''" class="w-full rounded-xl px-3 py-2 text-xs outline-none" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border); color: var(--gl-text-primary)">
                            <option value="">All Grade Levels</option>
                            <option v-for="grade in gradeLevels" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold" style="color: var(--gl-text-primary)">Target Section</label>
                        <select v-model="form.target_section_id" :disabled="!form.target_grade_level_id" class="w-full rounded-xl px-3 py-2 text-xs outline-none disabled:opacity-50" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border); color: var(--gl-text-primary)">
                            <option value="">All Sections</option>
                            <option v-for="section in filteredSections" :key="section.id" :value="section.id">{{ section.name }}</option>
                        </select>
                    </div>
                </div>

                <label class="flex cursor-pointer items-center gap-2 text-xs" style="color: var(--gl-text-primary)">
                    <input v-model="form.is_active" type="checkbox" class="h-4 w-4 accent-[var(--gl-primary)]" />
                    Make this poll visible to students immediately
                </label>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showCreateModal = false" class="rounded-xl px-4 py-2 text-xs font-semibold" style="background: var(--gl-surface-2); color: var(--gl-text-secondary)">Cancel</button>
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold text-white disabled:opacity-50" style="background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))">
                        <CheckCircle2 class="h-3.5 w-3.5" /> Create Poll
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
