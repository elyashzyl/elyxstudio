<script setup lang="ts">
import { LoaderCircle, Sparkles } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, useId, watch } from 'vue';
import type { AiCommunicationDraft, AiCommunicationType } from '@/types/ai-communication';

const props = defineProps<{
    type: AiCommunicationType;
    disabled?: boolean;
    gradeLevels: { id: number }[];
    sections: { id: number; grade_level_id: number }[];
    categories: string[];
}>();

const emit = defineEmits<{
    draft: [draft: AiCommunicationDraft];
    submitDraft: [draft: AiCommunicationDraft];
    generating: [value: boolean];
}>();

const promptTemplates = computed(() => {
    if (props.type === 'todo') {
        return [
            {
                label: 'Reading & guide',
                text: 'Assign a reading task to read Module 2 and answer the review questions before Friday at 5:00 PM.',
            },
            {
                label: 'Homework worksheet',
                text: 'Homework task: complete the practice worksheet and bring your solutions to our next class session.',
            },
            {
                label: 'Project milestone',
                text: 'Project task: submit your group project outline and member roles for review.',
            },
        ];
    }

    if (props.type === 'notification') {
        return [
            {
                label: 'Assessment reminder',
                text: 'Urgent reminder: our scheduled quiz opens today. Please review your notes and log in on time.',
            },
            {
                label: 'Room / schedule update',
                text: 'Reminder: today’s class session will meet in the computer laboratory. Please bring your materials.',
            },
            {
                label: 'Submission deadline',
                text: 'Reminder to check your pending class activities and submit any ungraded work before the deadline.',
            },
        ];
    }

    return [
        {
            label: 'Exam schedule',
            text: 'Pin an academic announcement about the upcoming quarterly examination schedule and coverage review.',
        },
        {
            label: 'Class suspension / event',
            text: 'Event announcement: school assembly and club orientation will take place in the main auditorium.',
        },
        {
            label: 'General reminder',
            text: 'Reminder to all students to check their learning modules and complete pending tasks for this week.',
        },
    ];
});

const placeholderText = computed(() => {
    if (props.type === 'todo') {
        return 'e.g. Assign Grade 10 Emerald a homework task to complete Lab Worksheet 3 due 2026-11-15 17:00...';
    }

    if (props.type === 'notification') {
        return 'e.g. Send an urgent reminder to Grade 10 about tomorrow’s practical assessment...';
    }

    return 'e.g. Pin an academic announcement for Grade 10 Emerald about Monday’s review session...';
});

const promptId = useId();
const prompt = ref('');
const generating = ref(false);
const error = ref('');
const generated = ref(false);
let controller: AbortController | null = null;
const canGenerate = computed(
    () => !props.disabled && !generating.value && prompt.value.trim().length > 0 && prompt.value.length <= 4000,
);

function cancel() {
    controller?.abort();
    controller = null;
    generating.value = false;
    emit('generating', false);
}

function isDraft(value: unknown): value is AiCommunicationDraft {
    if (!value || typeof value !== 'object') {
        return false;
    }

    const draft = value as Record<string, unknown>;
    const isId = (id: unknown) => id === null || (typeof id === 'number' && Number.isInteger(id) && id > 0);
    const isDate = (date: unknown) => date === null || (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(date));

    if (typeof draft.title !== 'string' || !draft.title.trim() || typeof draft.category !== 'string'
        || !isId(draft.target_grade_level_id) || !isId(draft.target_section_id)) {
        return false;
    }

    const section = props.sections.find((item) => item.id === draft.target_section_id);

    // Reject unresolved recipients rather than silently broadening the audience.
    if (!props.categories.includes(draft.category)
        || (draft.target_grade_level_id !== null && !props.gradeLevels.some((grade) => grade.id === draft.target_grade_level_id))
        || (draft.target_section_id !== null && (!section
            || !props.gradeLevels.some((grade) => grade.id === section.grade_level_id)
            || (draft.target_grade_level_id !== null && section.grade_level_id !== draft.target_grade_level_id)))) {
        return false;
    }

    return props.type === 'todo'
        ? typeof draft.description === 'string' && isDate(draft.due_date)
            && (draft.link_url === null || typeof draft.link_url === 'string')
        : typeof draft.content === 'string' && !!draft.content.trim()
            && isDate(draft.expires_at) && typeof draft.is_pinned === 'boolean';
}

async function generate(autoSubmit = false) {
    if (!canGenerate.value) {
        return;
    }

    const request = new AbortController();
    controller = request;
    generating.value = true;
    error.value = '';
    generated.value = false;
    emit('generating', true);

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const response = await fetch('/teacher/ai-communications/draft', {
            method: 'POST',
            credentials: 'same-origin',
            signal: request.signal,
            headers: {
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: props.type === 'notification' ? 'announcement' : props.type,
                prompt: prompt.value.trim(),
            }),
        });
        const data = await response.json().catch(() => null);

        if (controller !== request || request.signal.aborted) {
            return;
        }

        if (!response.ok || data?.ok !== true) {
            const validationErrors = data?.errors && typeof data.errors === 'object'
                ? Object.values(data.errors).flat().filter((message): message is string => typeof message === 'string').join(' ')
                : '';

            throw new Error(validationErrors || (typeof data?.message === 'string' && data.message)
                || (response.status === 419 ? 'Your session expired. Refresh the page before trying again.' : 'Could not generate a draft. Please try again.'));
        }

        if (!isDraft(data.draft)) {
            throw new Error('The AI returned an invalid draft. Your form has not changed. Please try again.');
        }

        if (autoSubmit) {
            emit('submitDraft', data.draft);
        } else {
            emit('draft', data.draft);
        }

        generated.value = true;
    } catch (cause) {
        if (controller === request && !request.signal.aborted) {
            error.value = cause instanceof Error ? cause.message : 'Could not generate a draft. Please try again.';
        }
    } finally {
        if (controller === request) {
            controller = null;
            generating.value = false;
            emit('generating', false);
        }
    }
}

watch(() => props.type, () => {
    cancel();
    error.value = '';
    generated.value = false;
});
watch(() => props.disabled, (disabled) => {
    if (disabled) {
        cancel();
    }
});
onBeforeUnmount(cancel);
</script>

<template>
    <section
        class="space-y-3 rounded-2xl border p-4"
        style="background: var(--gl-surface-2); border-color: var(--gl-border)"
        :aria-busy="generating"
    >
        <h3 class="flex items-center gap-2 text-sm font-bold" style="color: var(--gl-primary)">
            <Sparkles class="h-4 w-4" />
            {{ type === 'notification' ? 'AI notification' : 'Create with AI' }}
        </h3>
        <p class="text-xs" style="color: var(--gl-text-secondary)">
            Your prompt is sent to the configured AI provider. Avoid sensitive student information.
            Generation replaces the form fields below but does not publish anything.
        </p>
        <p v-if="type === 'notification'" class="text-xs" style="color: var(--gl-text-secondary)">
            Notifications are published as announcements using Publish Now below.
        </p>
        <div class="flex flex-wrap gap-1.5">
            <button
                v-for="item in promptTemplates"
                :key="item.label"
                type="button"
                :disabled="generating || disabled"
                class="rounded-lg border px-2.5 py-1 text-[11px] font-medium transition-colors hover:border-[var(--gl-primary)] disabled:opacity-50"
                style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-secondary)"
                @click="prompt = item.text"
            >
                + {{ item.label }}
            </button>
        </div>
        <div>
            <label :for="promptId" class="mb-1 block text-xs font-semibold" style="color: var(--gl-text-primary)">
                Describe your {{ type === 'todo' ? 'class task' : type }}, recipients, and dates
            </label>
            <textarea
                :id="promptId"
                v-model="prompt"
                :disabled="generating || disabled"
                maxlength="4000"
                rows="3"
                :placeholder="placeholderText"
                class="w-full rounded-xl border px-3 py-2 text-xs outline-none focus:border-[var(--gl-primary)] disabled:opacity-50"
                style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-primary)"
            ></textarea>
            <p class="mt-1 text-right text-[11px]" style="color: var(--gl-text-muted)">{{ prompt.length }}/4000</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                :disabled="!canGenerate"
                class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-white disabled:opacity-50"
                style="background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))"
                @click="generate(false)"
            >
                <LoaderCircle v-if="generating" class="h-4 w-4 animate-spin" />
                <Sparkles v-else class="h-4 w-4" />
                {{ generating ? 'Generating…' : 'Generate draft' }}
            </button>
            <button
                type="button"
                :disabled="!canGenerate"
                class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-bold disabled:opacity-50"
                style="border-color: var(--gl-primary); color: var(--gl-primary); background: var(--gl-surface)"
                @click="generate(true)"
            >
                <Sparkles class="h-3.5 w-3.5" />
                {{ type === 'todo' ? 'Prompt & Assign Now' : 'Prompt & Publish Now' }}
            </button>
            <button v-if="generating" type="button" class="text-xs font-semibold" style="color: var(--gl-text-secondary)" @click="cancel">
                Cancel generation
            </button>
        </div>
        <p v-if="error" role="alert" class="text-xs" style="color: var(--gl-danger)">{{ error }}</p>
        <p v-if="generated" role="status" class="text-xs" style="color: var(--gl-success)">
            Draft added to the form. Review and edit it before publishing.
        </p>
        <p class="text-xs" style="color: var(--gl-text-secondary)">
            Review the content, recipients, and dates before publishing. All Grade Levels / All Sections means a broad audience.
        </p>
    </section>
</template>
