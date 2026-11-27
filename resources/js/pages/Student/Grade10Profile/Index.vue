<template>
    <Head title="Grade 10 Profile Questionnaire" />

    <div class="mx-auto max-w-4xl space-y-6 pb-12">
        <!-- Top Flash Alert -->
        <div
            v-if="flash?.success"
            class="gl-fade-in flex items-center gap-3 rounded-2xl px-5 py-3.5 text-sm font-semibold"
            style="
                background: var(--gl-success-bg);
                color: var(--gl-success);
                border: 1px solid rgba(16, 185, 129, 0.25);
            "
        >
            <CheckCircle2 class="h-5 w-5 shrink-0" />
            <span>{{ flash.success }}</span>
        </div>

        <!-- Header Hero Banner -->
        <div
            class="relative overflow-hidden rounded-3xl p-6 sm:p-8"
            style="
                background: linear-gradient(
                    135deg,
                    var(--gl-surface),
                    var(--gl-surface-2)
                );
                border: 1px solid var(--gl-border);
                box-shadow: 0 4px 20px -2px var(--gl-primary-glow);
            "
        >
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold"
                            style="
                                background: rgba(59, 130, 246, 0.15);
                                color: var(--gl-primary);
                                border: 1px solid rgba(59, 130, 246, 0.25);
                            "
                        >
                            <GraduationCap class="h-3.5 w-3.5" />
                            Grade 10 Exclusive
                        </span>
                        <span
                            v-if="profile?.submitted_at"
                            class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold"
                            style="
                                background: var(--gl-success-bg);
                                color: var(--gl-success);
                                border: 1px solid rgba(16, 185, 129, 0.25);
                            "
                        >
                            <CheckCircle2 class="h-3.5 w-3.5" />
                            Submitted
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold"
                            style="
                                background: rgba(245, 158, 11, 0.12);
                                color: #f59e0b;
                                border: 1px solid rgba(245, 158, 11, 0.25);
                            "
                        >
                            <Clock class="h-3.5 w-3.5" />
                            Pending Submission
                        </span>
                    </div>

                    <h1
                        class="text-2xl font-black tracking-tight sm:text-3xl"
                        style="color: var(--gl-text-primary)"
                    >
                        Yearbook &amp; Profile Questionnaire
                    </h1>
                    <p class="text-sm leading-relaxed" style="color: var(--gl-text-muted)">
                        Please fill out your official yearbook record accurately. This questionnaire is strictly for Grade 10 graduating completers.
                    </p>
                </div>

                <div
                    v-if="profile?.submitted_at"
                    class="rounded-2xl p-4 sm:text-right"
                    style="
                        background: var(--gl-surface-2);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: var(--gl-text-muted)">
                        Last updated
                    </p>
                    <p class="text-xs font-bold sm:mt-1" style="color: var(--gl-text-primary)">
                        {{ profile.submitted_at }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Form Card -->
        <div
            class="gl-glow-card rounded-3xl p-6 sm:p-8"
            style="
                background: var(--gl-surface);
                border: 1px solid var(--gl-border);
            "
        >
            <form @submit.prevent="submit" class="space-y-6">
                <!-- 0. Graduation Portrait Photo Upload -->
                <div
                    class="rounded-3xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)]/60 p-5 sm:p-6"
                >
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                        <!-- Portrait Preview Thumbnail -->
                        <div class="relative shrink-0">
                            <div
                                class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-2xl border-2 border-[var(--gl-primary)]/40 bg-[var(--gl-surface)] shadow-md"
                            >
                                <img
                                    v-if="photoPreviewUrl"
                                    :src="photoPreviewUrl"
                                    alt="Graduation portrait preview"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex flex-col items-center justify-center text-center p-2 text-[var(--gl-text-muted)]"
                                >
                                    <Camera class="h-8 w-8 mb-1 opacity-60" />
                                    <span class="text-[10px] font-bold uppercase tracking-wider">No Photo</span>
                                </div>
                            </div>
                        </div>

                        <!-- Upload Actions & Guidelines -->
                        <div class="flex-1 space-y-2">
                            <div class="flex items-center gap-2">
                                <label
                                    class="text-xs font-bold uppercase tracking-wider text-[var(--gl-text-primary)]"
                                >
                                    Yearbook Portrait Photo
                                </label>
                                <span class="rounded-full bg-[rgba(59,130,246,0.12)] px-2 py-0.5 text-[10px] font-bold text-[var(--gl-primary)]">
                                    Recommended
                                </span>
                            </div>
                            <p class="text-xs text-[var(--gl-text-muted)] leading-relaxed">
                                Upload a front-facing formal or graduation portrait photo (JPEG, PNG, WebP up to 5 MB). This photo will be printed in the yearbook records and PDF exports.
                            </p>

                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <label
                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface)] px-3.5 py-2 text-xs font-bold text-[var(--gl-text-primary)] shadow-xs transition-all hover:border-[var(--gl-primary)] hover:text-[var(--gl-primary)]"
                                >
                                    <Upload class="h-3.5 w-3.5" />
                                    <span>{{ photoPreviewUrl ? 'Change Photo' : 'Upload Portrait Photo' }}</span>
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        class="hidden"
                                        @change="onPhotoSelected"
                                    />
                                </label>

                                <button
                                    v-if="photoPreviewUrl"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-red-500/20 bg-red-500/10 px-3 py-2 text-xs font-bold text-red-500 transition-all hover:bg-red-500/20"
                                    @click="removePhoto"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                    <span>Remove Photo</span>
                                </button>
                            </div>

                            <p v-if="form.errors.photo" class="text-xs text-red-500 font-semibold">
                                {{ form.errors.photo }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 1. Full Name -->
                <div>
                    <label
                        class="mb-1.5 flex items-center justify-between text-xs font-bold tracking-wide uppercase"
                        style="color: var(--gl-text-secondary)"
                    >
                        <span>
                            FULL NAME
                            <span class="text-[11px] font-normal lowercase" style="color: var(--gl-text-muted)">
                                (Last Name, First Name, Middle Name)
                            </span>
                            <span class="text-red-500">*</span>
                        </span>
                    </label>
                    <input
                        v-model="form.full_name"
                        type="text"
                        placeholder="e.g. Dela Cruz, Juan Protacio"
                        required
                        class="w-full rounded-2xl border px-4 py-3 text-sm font-medium transition-all outline-none"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-primary);
                            border-color: var(--gl-border);
                        "
                    />
                    <p v-if="form.errors.full_name" class="mt-1 text-xs text-red-500">
                        {{ form.errors.full_name }}
                    </p>
                </div>

                <!-- 2. Chinese Name -->
                <div>
                    <label
                        class="mb-1.5 flex items-center justify-between text-xs font-bold tracking-wide uppercase"
                        style="color: var(--gl-text-secondary)"
                    >
                        <span>
                            CHINESE NAME
                            <span class="text-[11px] font-normal lowercase" style="color: var(--gl-text-muted)">
                                (Chinese Class Students)
                            </span>
                        </span>
                        <span class="text-[11px] font-normal" style="color: var(--gl-text-muted)">Optional</span>
                    </label>
                    <input
                        v-model="form.chinese_name"
                        type="text"
                        placeholder="e.g. 陈大明 / Tan Tai Beng (leave blank if not applicable)"
                        class="w-full rounded-2xl border px-4 py-3 text-sm font-medium transition-all outline-none"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-primary);
                            border-color: var(--gl-border);
                        "
                    />
                    <p v-if="form.errors.chinese_name" class="mt-1 text-xs text-red-500">
                        {{ form.errors.chinese_name }}
                    </p>
                </div>

                <!-- Two-column grid for Birthday & Hometown -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <!-- 3. Birthday -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-bold tracking-wide uppercase"
                            style="color: var(--gl-text-secondary)"
                        >
                            BIRTHDAY
                            <span class="text-[11px] font-normal lowercase" style="color: var(--gl-text-muted)">
                                (Month, Day, Year)
                            </span>
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.birthday"
                            type="text"
                            placeholder="e.g. October 24, 2009"
                            required
                            class="w-full rounded-2xl border px-4 py-3 text-sm font-medium transition-all outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        />
                        <p v-if="form.errors.birthday" class="mt-1 text-xs text-red-500">
                            {{ form.errors.birthday }}
                        </p>
                    </div>

                    <!-- 4. Hometown -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-bold tracking-wide uppercase"
                            style="color: var(--gl-text-secondary)"
                        >
                            HOMETOWN
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.hometown"
                            type="text"
                            placeholder="e.g. Manila, Philippines"
                            required
                            class="w-full rounded-2xl border px-4 py-3 text-sm font-medium transition-all outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        />
                        <p v-if="form.errors.hometown" class="mt-1 text-xs text-red-500">
                            {{ form.errors.hometown }}
                        </p>
                    </div>
                </div>

                <!-- 5. Ambition -->
                <div>
                    <label
                        class="mb-1.5 block text-xs font-bold tracking-wide uppercase"
                        style="color: var(--gl-text-secondary)"
                    >
                        AMBITION
                        <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.ambition"
                        type="text"
                        placeholder="e.g. Aerospace Engineer / Neurosurgeon"
                        required
                        class="w-full rounded-2xl border px-4 py-3 text-sm font-medium transition-all outline-none"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-primary);
                            border-color: var(--gl-border);
                        "
                    />
                    <p v-if="form.errors.ambition" class="mt-1 text-xs text-red-500">
                        {{ form.errors.ambition }}
                    </p>
                </div>

                <!-- 6. Motto -->
                <div>
                    <label
                        class="mb-1.5 block text-xs font-bold tracking-wide uppercase"
                        style="color: var(--gl-text-secondary)"
                    >
                        MOTTO
                        <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        v-model="form.motto"
                        rows="2"
                        placeholder="e.g. Strive not to be a success, but rather to be of value."
                        required
                        class="w-full rounded-2xl border px-4 py-3 text-sm font-medium transition-all outline-none"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-primary);
                            border-color: var(--gl-border);
                        "
                    ></textarea>
                    <p v-if="form.errors.motto" class="mt-1 text-xs text-red-500">
                        {{ form.errors.motto }}
                    </p>
                </div>

                <!-- 7. Message -->
                <div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <label
                            class="text-xs font-bold tracking-wide uppercase"
                            style="color: var(--gl-text-secondary)"
                        >
                            MESSAGE
                            <span class="text-[11px] font-normal lowercase" style="color: var(--gl-text-muted)">
                                (3-5 sentences only)
                            </span>
                            <span class="text-red-500">*</span>
                        </label>
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                            :style="
                                sentenceCount >= 3 && sentenceCount <= 5
                                    ? 'background: var(--gl-success-bg); color: var(--gl-success);'
                                    : 'background: rgba(245, 158, 11, 0.12); color: #f59e0b;'
                            "
                        >
                            {{ sentenceCount }} {{ sentenceCount === 1 ? 'sentence' : 'sentences' }}
                            (Target: 3-5)
                        </span>
                    </div>
                    <textarea
                        v-model="form.message"
                        rows="4"
                        placeholder="Write your personal message or dedication to your batchmates, mentors, and parents (3 to 5 sentences)."
                        required
                        class="w-full rounded-2xl border px-4 py-3 text-sm font-medium transition-all outline-none"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-primary);
                            border-color: var(--gl-border);
                        "
                    ></textarea>
                    <p v-if="form.errors.message" class="mt-1 text-xs text-red-500">
                        {{ form.errors.message }}
                    </p>
                </div>

                <!-- 8. Social Media Account -->
                <div>
                    <label
                        class="mb-1.5 flex items-center justify-between text-xs font-bold tracking-wide uppercase"
                        style="color: var(--gl-text-secondary)"
                    >
                        <span>
                            SOCIAL MEDIA ACCOUNT
                            <span class="text-[11px] font-normal lowercase" style="color: var(--gl-text-muted)">
                                (Optional)
                            </span>
                        </span>
                        <span class="text-[11px] font-normal" style="color: var(--gl-text-muted)">Optional</span>
                    </label>
                    <input
                        v-model="form.social_media"
                        type="text"
                        placeholder="e.g. @username / instagram.com/username"
                        class="w-full rounded-2xl border px-4 py-3 text-sm font-medium transition-all outline-none"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-primary);
                            border-color: var(--gl-border);
                        "
                    />
                    <p v-if="form.errors.social_media" class="mt-1 text-xs text-red-500">
                        {{ form.errors.social_media }}
                    </p>
                </div>

                <!-- Submit Button Area -->
                <div class="flex items-center justify-between border-t pt-6" style="border-color: var(--gl-border)">
                    <p class="text-xs" style="color: var(--gl-text-muted)">
                        You can update your submission anytime before records are finalized.
                    </p>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-2xl px-6 py-3 text-sm font-bold text-white transition-all shadow-md disabled:opacity-50"
                        style="
                            background: linear-gradient(
                                135deg,
                                var(--gl-primary),
                                var(--gl-secondary)
                            );
                            box-shadow: 0 4px 14px var(--gl-primary-glow);
                        "
                    >
                        <Save class="h-4 w-4" />
                        <span>{{ form.processing ? 'Saving...' : (profile ? 'Update Profile' : 'Submit Profile') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    GraduationCap,
    CheckCircle2,
    Clock,
    Save,
    Camera,
    Upload,
    Trash2,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';

interface ProfileData {
    id: number;
    full_name: string;
    chinese_name: string | null;
    birthday: string;
    hometown: string;
    ambition: string;
    motto: string;
    message: string;
    social_media: string | null;
    photo_url?: string | null;
    submitted_at: string | null;
}

const props = defineProps<{
    profile: ProfileData | null;
    default_name: string;
}>();

const page = usePage();
const flash = computed(() => (page.props as any).flash);

const fileInput = ref<HTMLInputElement | null>(null);
const photoPreviewUrl = ref<string | null>(props.profile?.photo_url || null);

const form = useForm({
    full_name: props.profile?.full_name || props.default_name || '',
    chinese_name: props.profile?.chinese_name || '',
    birthday: props.profile?.birthday || '',
    hometown: props.profile?.hometown || '',
    ambition: props.profile?.ambition || '',
    motto: props.profile?.motto || '',
    message: props.profile?.message || '',
    social_media: props.profile?.social_media || '',
    photo: null as File | null,
    remove_photo: false,
});

watch(
    () => props.profile,
    (newProfile) => {
        if (newProfile) {
            form.full_name = newProfile.full_name || props.default_name || '';
            form.chinese_name = newProfile.chinese_name || '';
            form.birthday = newProfile.birthday || '';
            form.hometown = newProfile.hometown || '';
            form.ambition = newProfile.ambition || '';
            form.motto = newProfile.motto || '';
            form.message = newProfile.message || '';
            form.social_media = newProfile.social_media || '';
            form.photo = null;
            form.remove_photo = false;
            photoPreviewUrl.value = newProfile.photo_url || null;

            if (fileInput.value) {
                fileInput.value.value = '';
            }
        }
    },
    { deep: true },
);

function onPhotoSelected(e: Event) {
    const input = e.target as HTMLInputElement;

    if (input.files && input.files.length > 0) {
        const file = input.files[0];
        form.photo = file;
        form.remove_photo = false;

        if (photoPreviewUrl.value && photoPreviewUrl.value.startsWith('blob:')) {
            URL.revokeObjectURL(photoPreviewUrl.value);
        }

        photoPreviewUrl.value = URL.createObjectURL(file);
    }
}

function removePhoto() {
    if (photoPreviewUrl.value && photoPreviewUrl.value.startsWith('blob:')) {
        URL.revokeObjectURL(photoPreviewUrl.value);
    }

    form.photo = null;
    form.remove_photo = true;
    photoPreviewUrl.value = null;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

const sentenceCount = computed(() => {
    const text = form.message.trim();

    if (!text) {
return 0;
}

    // Split sentences by terminal punctuation (. ? !)
    const matches = text.match(/[^.!?]+(?:[.!?]+|$)/g);

    if (!matches) {
return 0;
}

    return matches.filter((s) => s.trim().length > 0).length;
});

function submit() {
    form.post('/student/grade10-profile', {
        preserveScroll: true,
        onSuccess: () => {
            form.photo = null;
            form.remove_photo = false;

            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
}
</script>
