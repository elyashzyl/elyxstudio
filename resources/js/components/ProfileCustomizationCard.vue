<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Camera, Image, Save, Trash2, Upload } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';

type ProfileUser = {
    name?: string;
    avatar?: string | null;
    bio?: string | null;
};

const props = defineProps<{
    user: ProfileUser;
}>();

const { getInitials } = useInitials();
const fileInput = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(null);

const MAX_PHOTO_BYTES = 2 * 1024 * 1024;

const form = useForm<{ bio: string }>({
    bio: props.user.bio || '',
});

// Photo changes are saved immediately so a chosen photo never lives only as a local preview.
const photoForm = useForm<{
    profile_photo: File | null;
    remove_profile_photo: boolean;
}>({
    profile_photo: null,
    remove_profile_photo: false,
});

const imageUrl = computed(() => previewUrl.value || props.user.avatar || null);

function clearPreview() {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
}

function choosePhoto() {
    fileInput.value?.click();
}

function resetFileInput() {
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function submitPhoto() {
    photoForm.post('/settings/profile/customization', {
        forceFormData: true,
        preserveScroll: true,
        onError: () => clearPreview(),
        onFinish: () => {
            photoForm.reset();
            resetFileInput();
        },
    });
}

function handlePhotoChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] || null;

    if (!file) {
        return;
    }

    photoForm.clearErrors();

    if (file.size > MAX_PHOTO_BYTES) {
        photoForm.setError('profile_photo', 'The photo must be 2 MB or smaller.');
        resetFileInput();

        return;
    }

    // Keep the local preview after a successful upload to avoid flashing initials while the saved image loads.
    clearPreview();
    previewUrl.value = URL.createObjectURL(file);
    photoForm.profile_photo = file;
    photoForm.remove_profile_photo = false;
    submitPhoto();
}

function removePhoto() {
    clearPreview();
    photoForm.clearErrors();
    photoForm.profile_photo = null;
    photoForm.remove_profile_photo = true;
    submitPhoto();
}

function save() {
    form.post('/settings/profile/customization', {
        preserveScroll: true,
    });
}

watch(
    () => props.user.bio,
    (bio) => {
        if (!form.isDirty) {
            form.bio = bio || '';
        }
    },
);

onBeforeUnmount(clearPreview);
</script>

<template>
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
                    <Camera
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
                        Profile Customization
                    </h3>
                    <p class="text-xs" style="color: var(--gl-text-muted)">
                        Add a profile picture and a short introduction.
                    </p>
                </div>
            </div>
        </div>

        <form class="space-y-5 p-6" @submit.prevent="save">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <Avatar class="h-24 w-24 rounded-2xl">
                    <AvatarImage
                        v-if="imageUrl"
                        :src="imageUrl"
                        :alt="props.user.name || 'Profile photo'"
                        class="object-cover"
                    />
                    <AvatarFallback
                        class="rounded-2xl text-2xl font-bold text-white"
                        style="background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))"
                    >
                        {{ getInitials(props.user.name || '') || 'U' }}
                    </AvatarFallback>
                </Avatar>

                <div class="space-y-2">
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-white transition-opacity hover:opacity-90 disabled:opacity-50"
                            style="background: var(--gl-primary)"
                            :disabled="photoForm.processing"
                            @click="choosePhoto"
                        >
                            <Upload class="h-4 w-4" :stroke-width="2" />
                            {{ photoForm.processing ? 'Saving…' : 'Choose photo' }}
                        </button>
                        <button
                            v-if="props.user.avatar || previewUrl"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold transition-opacity hover:opacity-80 disabled:opacity-50"
                            style="color: var(--gl-danger); border-color: var(--gl-border)"
                            :disabled="photoForm.processing"
                            @click="removePhoto"
                        >
                            <Trash2 class="h-4 w-4" :stroke-width="2" />
                            Remove
                        </button>
                    </div>
                    <p class="flex items-center gap-1.5 text-xs" style="color: var(--gl-text-muted)">
                        <Image class="h-3.5 w-3.5" :stroke-width="2" />
                        JPG, PNG, or WebP up to 2 MB. Saved automatically.
                    </p>
                    <p v-if="photoForm.errors.profile_photo" class="text-xs" style="color: var(--gl-danger)">
                        {{ photoForm.errors.profile_photo }}
                    </p>
                </div>
                <input
                    ref="fileInput"
                    class="hidden"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    @change="handlePhotoChange"
                />
            </div>

            <div>
                <label class="field-label" style="color: var(--gl-text-secondary)">
                    About you
                </label>
                <textarea
                    v-model="form.bio"
                    rows="3"
                    maxlength="500"
                    placeholder="Tell your classmates a little about yourself"
                    class="w-full resize-y rounded-xl border px-4 py-2.5 text-sm transition-all outline-none focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                    style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border)"
                />
                <div class="mt-1 flex items-center justify-between gap-3">
                    <p v-if="form.errors.bio" class="text-xs" style="color: var(--gl-danger)">
                        {{ form.errors.bio }}
                    </p>
                    <span class="ml-auto text-xs" style="color: var(--gl-text-muted)">
                        {{ form.bio.length }}/500
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02] disabled:opacity-50"
                    style="background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary)); box-shadow: 0 0 12px var(--gl-primary-glow)"
                    :disabled="form.processing"
                >
                    <Save class="h-4 w-4" :stroke-width="2" />
                    Save bio
                </button>
                <span v-if="form.recentlySuccessful" class="text-sm" style="color: var(--gl-success)">
                    Saved.
                </span>
            </div>
        </form>
    </div>
</template>
