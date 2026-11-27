<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    GraduationCap,
    School,
    ShoppingBag,
    Store,
    Trophy,
    Star,
    Zap,
    ChevronDown,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});

const props = defineProps<{
    passwordRules: string;
    teachers: { id: number; name: string }[];
    gradeLevels: { id: number; name: string }[];
    sections: { id: number; name: string; grade_level_id: number }[];
}>();

const filteredSections = computed(() => {
    const gl = props.gradeLevels.find((g: any) => g.name === grade.value);

    return gl
        ? props.sections.filter((s: any) => s.grade_level_id === gl.id)
        : [];
});

const role = ref('buyer');
const teacherIds = ref<number[]>([]);
const grade = ref('');
const gradeLevelId = ref('');
const sectionId = ref('');

function toggleTeacher(id: number) {
    const idx = teacherIds.value.indexOf(id);

    if (idx === -1) {
        teacherIds.value.push(id);
    } else {
        teacherIds.value.splice(idx, 1);
    }
}

watch(grade, () => {
    sectionId.value = '';
    const gl = props.gradeLevels.find((g: any) => g.name === grade.value);
    gradeLevelId.value = gl ? String(gl.id) : '';
});
</script>

<template>
    <Head title="Sign up" />

    <div class="login-dark fixed inset-0 flex" style="background: var(--gl-bg)">
        <!-- Back to home -->
        <Link
            href="/"
            class="absolute top-5 left-5 z-30 inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-semibold backdrop-blur-md transition-all hover:scale-105 active:scale-95"
            style="
                background: var(--gl-surface-2);
                color: var(--gl-text-secondary);
                border: 1px solid var(--gl-border);
            "
        >
            <ArrowLeft class="h-3.5 w-3.5" :stroke-width="2" />
            <span>Back to home</span>
        </Link>

        <!-- Left: Form -->
        <div
            class="flex flex-1 flex-col items-center justify-center overflow-y-auto lg:w-[45%]"
            style="padding: 1.5rem 2rem"
        >
            <div class="w-full max-w-sm">
                <div class="mb-4 text-center">
                    <h2
                        class="mb-0.5 text-xl font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        Join EduPulse
                    </h2>
                    <p class="text-xs" style="color: var(--gl-text-secondary)">
                        Start your learning adventure
                    </p>
                </div>

                <Form
                    action="/register"
                    method="post"
                    :reset-on-success="['password', 'password_confirmation']"
                    v-slot="{ errors, processing }"
                    class="flex flex-col gap-3.5"
                >
                    <div class="grid gap-1.5">
                        <Label
                            for="name"
                            style="color: var(--gl-text-secondary)"
                            >Full name</Label
                        >
                        <Input
                            id="name"
                            type="text"
                            required
                            autofocus
                            autocomplete="name"
                            name="name"
                            placeholder="Full name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label
                            for="email"
                            style="color: var(--gl-text-secondary)"
                            >Email</Label
                        >
                        <Input
                            id="email"
                            type="email"
                            required
                            autocomplete="email"
                            name="email"
                            placeholder="email@example.com"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <!-- Role Toggle -->
                    <div
                        class="grid grid-cols-2 gap-1 rounded-xl p-1"
                        style="
                            background: var(--gl-surface-2);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        <button
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-lg py-2 text-sm font-medium transition-all"
                            :style="
                                role === 'teacher'
                                    ? {
                                          background:
                                              'linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))',
                                          color: '#FFF',
                                      }
                                    : { color: 'var(--gl-text-muted)' }
                            "
                            @click="role = 'teacher'"
                        >
                            <GraduationCap class="h-4 w-4" :stroke-width="2" />
                            Teacher
                        </button>
                        <button
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-lg py-2 text-sm font-medium transition-all"
                            :style="
                                role === 'student'
                                    ? {
                                          background:
                                              'linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))',
                                          color: '#FFF',
                                      }
                                    : { color: 'var(--gl-text-muted)' }
                            "
                            @click="role = 'student'"
                        >
                            <School class="h-4 w-4" :stroke-width="2" />
                            Student
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-xl border px-3 py-2.5 text-sm font-semibold transition-all"
                            :style="
                                role === 'buyer'
                                    ? {
                                          background: 'rgba(39,169,232,.14)',
                                          borderColor: 'var(--gl-primary)',
                                          color: 'var(--gl-primary)',
                                      }
                                    : {
                                          background: 'var(--gl-surface)',
                                          borderColor: 'var(--gl-border)',
                                          color: 'var(--gl-text-muted)',
                                      }
                            "
                            @click="role = 'buyer'"
                        >
                            <ShoppingBag class="h-4 w-4" />
                            Shop as buyer
                        </button>
                        <button
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-xl border px-3 py-2.5 text-sm font-semibold transition-all"
                            :style="
                                role === 'seller'
                                    ? {
                                          background: 'rgba(39,169,232,.14)',
                                          borderColor: 'var(--gl-primary)',
                                          color: 'var(--gl-primary)',
                                      }
                                    : {
                                          background: 'var(--gl-surface)',
                                          borderColor: 'var(--gl-border)',
                                          color: 'var(--gl-text-muted)',
                                      }
                            "
                            @click="role = 'seller'"
                        >
                            <Store class="h-4 w-4" />
                            Sell with ELYX
                        </button>
                    </div>

                    <input type="hidden" name="role" :value="role" />

                    <p
                        v-if="role === 'buyer'"
                        class="rounded-xl border px-3 py-2 text-xs leading-5"
                        style="
                            border-color: rgba(39, 169, 232, 0.25);
                            background: rgba(39, 169, 232, 0.07);
                            color: var(--gl-text-secondary);
                        "
                    >
                        Create a buyer account to track orders, save your
                        checkout details, and shop ELYX products.
                    </p>
                    <p
                        v-else-if="role === 'seller'"
                        class="rounded-xl border px-3 py-2 text-xs leading-5"
                        style="
                            border-color: rgba(39, 169, 232, 0.25);
                            background: rgba(39, 169, 232, 0.07);
                            color: var(--gl-text-secondary);
                        "
                    >
                        Seller products are reviewed by an ELYX admin before
                        they appear publicly.
                    </p>

                    <template v-if="role === 'student'">
                        <div class="grid gap-1.5">
                            <Label style="color: var(--gl-text-secondary)"
                                >Teachers</Label
                            >
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="teacher in teachers"
                                    :key="teacher.id"
                                    type="button"
                                    @click="toggleTeacher(teacher.id)"
                                    class="rounded-lg border px-3 py-2 text-sm font-medium transition-all"
                                    :style="
                                        teacherIds.includes(teacher.id)
                                            ? {
                                                  background:
                                                      'rgba(59,130,246,0.12)',
                                                  borderColor:
                                                      'var(--gl-primary)',
                                                  color: 'var(--gl-primary)',
                                              }
                                            : {
                                                  background:
                                                      'var(--gl-surface)',
                                                  borderColor:
                                                      'var(--gl-border)',
                                                  color: 'var(--gl-text-secondary)',
                                              }
                                    "
                                >
                                    {{ teacher.name }}
                                </button>
                            </div>
                            <input
                                v-for="id in teacherIds"
                                :key="id"
                                type="hidden"
                                name="teacher_ids[]"
                                :value="id"
                            />
                            <InputError :message="errors.teacher_ids" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-1.5">
                                <Label
                                    for="grade"
                                    style="color: var(--gl-text-secondary)"
                                    >Grade level</Label
                                >
                                <div class="relative">
                                    <select
                                        id="grade"
                                        v-model="grade"
                                        name="grade"
                                        class="flex h-10 w-full appearance-none rounded-lg border px-3 py-2 pr-10 text-sm outline-none"
                                        style="
                                            background: var(--gl-surface);
                                            border-color: var(--gl-border);
                                            color: var(--gl-text-primary);
                                        "
                                    >
                                        <option value="" disabled>
                                            Select grade
                                        </option>
                                        <option
                                            v-for="gl in gradeLevels"
                                            :key="gl.id"
                                            :value="gl.name"
                                        >
                                            {{ gl.name }}
                                        </option>
                                    </select>
                                    <ChevronDown
                                        class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2"
                                        style="color: var(--gl-text-muted)"
                                    />
                                </div>
                                <input
                                    type="hidden"
                                    name="grade_level_id"
                                    :value="gradeLevelId"
                                />
                                <InputError :message="errors.grade" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label
                                    for="section"
                                    style="color: var(--gl-text-secondary)"
                                    >Section</Label
                                >
                                <div class="relative">
                                    <select
                                        id="section_id"
                                        v-model="sectionId"
                                        name="section_id"
                                        class="flex h-10 w-full appearance-none rounded-lg border px-3 py-2 pr-10 text-sm outline-none"
                                        style="
                                            background: var(--gl-surface);
                                            border-color: var(--gl-border);
                                            color: var(--gl-text-primary);
                                        "
                                    >
                                        <option value="" disabled>
                                            Select section
                                        </option>
                                        <option
                                            v-for="sec in filteredSections"
                                            :key="sec.id"
                                            :value="sec.id"
                                        >
                                            {{ sec.name }}
                                        </option>
                                    </select>
                                    <ChevronDown
                                        class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2"
                                        style="color: var(--gl-text-muted)"
                                    />
                                </div>
                                <InputError :message="errors.section_id" />
                            </div>
                        </div>
                    </template>

                    <div class="grid gap-1.5">
                        <Label
                            for="password"
                            style="color: var(--gl-text-secondary)"
                            >Password</Label
                        >
                        <PasswordInput
                            id="password"
                            required
                            :tabindex="role === 'student' ? 6 : 3"
                            autocomplete="new-password"
                            name="password"
                            placeholder="Password"
                            :passwordrules="passwordRules"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label
                            for="password_confirmation"
                            style="color: var(--gl-text-secondary)"
                            >Confirm password</Label
                        >
                        <PasswordInput
                            id="password_confirmation"
                            required
                            :tabindex="role === 'student' ? 7 : 4"
                            autocomplete="new-password"
                            name="password_confirmation"
                            placeholder="Confirm password"
                            :passwordrules="passwordRules"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <Button
                        type="submit"
                        class="mt-2 w-full"
                        :disabled="processing"
                    >
                        <Spinner v-if="processing" />
                        <Zap v-else class="mr-1.5 h-4 w-4" :stroke-width="2" />
                        Create account
                    </Button>

                    <p
                        class="text-center text-sm"
                        style="color: var(--gl-text-secondary)"
                    >
                        Already have an account?
                        <TextLink :href="login()">Log in</TextLink>
                    </p>

                    <div class="mt-3 text-center">
                        <Link
                            href="/"
                            class="inline-flex items-center gap-1.5 text-xs font-medium transition-colors hover:underline"
                            style="color: var(--gl-text-muted)"
                        >
                            <ArrowLeft class="h-3 w-3" :stroke-width="2" />
                            Back to home
                        </Link>
                    </div>
                </Form>

                <p
                    class="mt-4 text-center text-xs"
                    style="color: var(--gl-text-muted)"
                >
                    Learn. Play. Achieve.
                </p>
            </div>
        </div>

        <!-- Right: Hero -->
        <div
            class="relative hidden flex-col items-center justify-center overflow-hidden lg:flex lg:w-[55%]"
        >
            <div
                class="absolute inset-0"
                style="
                    background: linear-gradient(
                        180deg,
                        var(--gl-bg),
                        var(--gl-surface)
                    );
                "
            ></div>
            <div
                class="absolute -top-40 -right-40 h-96 w-96 rounded-full opacity-10"
                style="
                    background: radial-gradient(
                        circle,
                        var(--gl-secondary),
                        transparent 70%
                    );
                "
            ></div>
            <div
                class="absolute -bottom-40 -left-40 h-96 w-96 rounded-full opacity-10"
                style="
                    background: radial-gradient(
                        circle,
                        var(--gl-primary),
                        transparent 70%
                    );
                "
            ></div>

            <div class="relative z-10 max-w-md text-center">
                <div class="mb-8 flex justify-center">
                    <img
                        src="/logo.png"
                        alt="EduPulse logo"
                        class="h-20 w-20 rounded-full object-cover"
                        style="box-shadow: 0 0 40px var(--gl-secondary-glow)"
                    />
                </div>
                <h2
                    class="mb-3 text-3xl font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    Begin Your Journey
                </h2>
                <p
                    class="mb-10 text-sm"
                    style="color: var(--gl-text-secondary)"
                >
                    Create your account and start earning XP, unlocking
                    achievements, and climbing the leaderboard.
                </p>

                <div class="grid grid-cols-3 gap-3">
                    <div
                        class="rounded-xl p-4 text-center transition-all hover:scale-105"
                        style="
                            background: rgba(16, 185, 129, 0.08);
                            border: 1px solid rgba(16, 185, 129, 0.15);
                        "
                    >
                        <Trophy
                            class="mx-auto mb-1.5 h-6 w-6"
                            style="color: var(--gl-success)"
                            :stroke-width="2"
                        />
                        <p
                            class="text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            Complete Tasks
                        </p>
                    </div>
                    <div
                        class="rounded-xl p-4 text-center transition-all hover:scale-105"
                        style="
                            background: rgba(251, 191, 36, 0.08);
                            border: 1px solid rgba(251, 191, 36, 0.15);
                        "
                    >
                        <Star
                            class="mx-auto mb-1.5 h-6 w-6"
                            style="color: var(--gl-accent)"
                            :stroke-width="2"
                        />
                        <p
                            class="text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            Earn Rewards
                        </p>
                    </div>
                    <div
                        class="rounded-xl p-4 text-center transition-all hover:scale-105"
                        style="
                            background: rgba(59, 130, 246, 0.08);
                            border: 1px solid rgba(59, 130, 246, 0.15);
                        "
                    >
                        <Zap
                            class="mx-auto mb-1.5 h-6 w-6"
                            style="color: var(--gl-primary)"
                            :stroke-width="2"
                        />
                        <p
                            class="text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            Level Up
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.login-dark {
    --background: var(--gl-bg);
    --foreground: var(--gl-text-primary);
    --card: var(--gl-surface);
    --card-foreground: var(--gl-text-primary);
    --border: var(--gl-border);
    --input: var(--gl-border);
    --muted-foreground: var(--gl-text-muted);
    --muted: var(--gl-surface-2);
    --primary: var(--gl-primary);
    --primary-foreground: #fff;
    --secondary: var(--gl-surface-2);
    --secondary-foreground: var(--gl-text-primary);
    --accent: var(--gl-surface-2);
    --accent-foreground: var(--gl-text-primary);
    --ring: var(--gl-primary);
    color-scheme: light dark;
}
.login-dark input::placeholder {
    color: var(--gl-text-muted) !important;
}
.login-dark [data-slot='input'] {
    background: var(--gl-surface) !important;
    border-color: var(--gl-border) !important;
    color: var(--gl-text-primary) !important;
    height: 2.5rem !important;
    font-size: 0.875rem !important;
}
.login-dark [data-slot='button'] {
    background: linear-gradient(
        135deg,
        var(--gl-primary),
        var(--gl-secondary)
    ) !important;
    color: #fff !important;
    box-shadow: 0 0 12px var(--gl-primary-glow);
    height: 2.5rem !important;
}
.login-dark [data-slot='label'],
.login-dark label {
    color: var(--gl-text-secondary) !important;
    font-size: 0.8rem !important;
}
.login-dark a {
    color: var(--gl-primary);
}
.login-dark span {
    color: var(--gl-text-secondary);
}
.login-dark select {
    background: var(--gl-surface) !important;
    border-color: var(--gl-border) !important;
    color: var(--gl-text-primary) !important;
}
.login-dark select option {
    background: var(--gl-surface) !important;
}
</style>
