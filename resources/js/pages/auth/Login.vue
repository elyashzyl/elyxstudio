<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Trophy, Star, Zap, Sparkles } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Log in',
        description: 'Enter your email and password to log in',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister?: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Log in" />

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

        <!-- Left: Hero Section -->
        <div
            class="relative hidden flex-col items-center justify-center overflow-hidden lg:flex lg:w-[55%]"
        >
            <!-- Ambient background -->
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
                class="absolute -top-40 -left-40 h-96 w-96 rounded-full opacity-10"
                style="
                    background: radial-gradient(
                        circle,
                        var(--gl-primary),
                        transparent 70%
                    );
                "
            ></div>
            <div
                class="absolute -right-40 -bottom-40 h-96 w-96 rounded-full opacity-10"
                style="
                    background: radial-gradient(
                        circle,
                        var(--gl-secondary),
                        transparent 70%
                    );
                "
            ></div>
            <div
                class="absolute top-20 right-20 h-64 w-64 rounded-full opacity-5"
                style="
                    background: radial-gradient(
                        circle,
                        var(--gl-accent),
                        transparent 70%
                    );
                "
            ></div>

            <!-- Content -->
            <div class="relative z-10 max-w-md text-center">
                <div class="mb-8 flex justify-center">
                    <img
                        src="/logo.png"
                        alt="EduPulse logo"
                        class="h-20 w-20 rounded-full object-cover"
                        style="box-shadow: 0 0 40px var(--gl-primary-glow)"
                    />
                </div>
                <h1
                    class="mb-3 text-4xl font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    <span style="color: var(--gl-primary)">Edu</span>Pulse
                </h1>
                <p
                    class="mb-2 text-lg font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    Level Up Your Learning
                </p>
                <p
                    class="mb-10 text-sm"
                    style="color: var(--gl-text-secondary)"
                >
                    Complete challenges, earn XP, unlock achievements, and
                    become a top learner.
                </p>

                <!-- Feature Cards -->
                <div class="grid grid-cols-3 gap-3">
                    <div
                        class="rounded-xl p-4 text-center transition-all hover:scale-105"
                        style="
                            background: rgba(59, 130, 246, 0.08);
                            border: 1px solid rgba(59, 130, 246, 0.15);
                        "
                    >
                        <Trophy
                            class="mx-auto mb-1.5 h-6 w-6"
                            style="color: var(--gl-accent)"
                            :stroke-width="2"
                        />
                        <p
                            class="text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            Earn XP
                        </p>
                        <p
                            class="mt-0.5 text-[10px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Level up
                        </p>
                    </div>
                    <div
                        class="rounded-xl p-4 text-center transition-all hover:scale-105"
                        style="
                            background: rgba(124, 58, 237, 0.08);
                            border: 1px solid rgba(124, 58, 237, 0.15);
                        "
                    >
                        <Star
                            class="mx-auto mb-1.5 h-6 w-6"
                            style="color: var(--gl-secondary)"
                            :stroke-width="2"
                        />
                        <p
                            class="text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            Achievements
                        </p>
                        <p
                            class="mt-0.5 text-[10px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Collect badges
                        </p>
                    </div>
                    <div
                        class="rounded-xl p-4 text-center transition-all hover:scale-105"
                        style="
                            background: rgba(16, 185, 129, 0.08);
                            border: 1px solid rgba(16, 185, 129, 0.15);
                        "
                    >
                        <Zap
                            class="mx-auto mb-1.5 h-6 w-6"
                            style="color: var(--gl-success)"
                            :stroke-width="2"
                        />
                        <p
                            class="text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            Leaderboard
                        </p>
                        <p
                            class="mt-0.5 text-[10px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Compete
                        </p>
                    </div>
                </div>
            </div>

            <!-- Floating sparkles -->
            <div
                class="absolute top-1/4 left-1/4 text-xs opacity-20"
                style="color: var(--gl-accent)"
            >
                ✦
            </div>
            <div
                class="absolute top-1/3 right-1/3 text-xs opacity-30"
                style="color: var(--gl-primary)"
            >
                ✦
            </div>
            <div
                class="absolute bottom-1/4 left-1/2 text-xs opacity-15"
                style="color: var(--gl-secondary)"
            >
                ✦
            </div>
        </div>

        <!-- Right: Login Form -->
        <div class="flex flex-1 items-center justify-center p-8 lg:p-0">
            <div class="w-full max-w-sm">
                <div
                    v-if="status"
                    class="mb-6 rounded-xl px-4 py-3 text-center text-sm"
                    style="
                        background: var(--gl-success-bg);
                        color: var(--gl-success);
                        border: 1px solid rgba(16, 185, 129, 0.2);
                    "
                >
                    {{ status }}
                </div>

                <!-- Welcome -->
                <div class="mb-8 text-center">
                    <h2
                        class="mb-1 text-2xl font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        Welcome Back
                    </h2>
                    <p class="text-sm" style="color: var(--gl-text-secondary)">
                        Continue your learning journey
                    </p>
                </div>

                <form @submit.prevent="submit" class="flex flex-col gap-5">
                    <div class="grid gap-1.5">
                        <Label
                            for="email"
                            style="color: var(--gl-text-secondary)"
                            >Email</Label
                        >
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            name="email"
                            required
                            autofocus
                            autocomplete="email"
                            placeholder="email@example.com"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-1.5">
                        <div class="flex items-center justify-between">
                            <Label
                                for="password"
                                style="color: var(--gl-text-secondary)"
                                >Password</Label
                            >
                            <TextLink
                                v-if="canResetPassword"
                                :href="request()"
                                class="text-sm"
                                >Forgot password?</TextLink
                            >
                        </div>
                        <PasswordInput
                            id="password"
                            v-model="form.password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Password"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <Label for="remember" class="flex items-center gap-3">
                        <Checkbox
                            id="remember"
                            name="remember"
                            :model-value="form.remember"
                            @update:model-value="
                                (val: any) => (form.remember = Boolean(val))
                            "
                        />
                        <span
                            class="text-sm"
                            style="color: var(--gl-text-secondary)"
                            >Remember me</span
                        >
                    </Label>

                    <Button
                        type="submit"
                        class="mt-2 w-full"
                        :disabled="form.processing"
                    >
                        <Spinner v-if="form.processing" />
                        <Sparkles
                            v-else
                            class="mr-1.5 h-4 w-4"
                            :stroke-width="2"
                        />
                        Sign In
                    </Button>

                    <p
                        v-if="canRegister"
                        class="text-center text-sm"
                        style="color: var(--gl-text-secondary)"
                    >
                        Don't have an account?
                        <TextLink :href="register()">Sign up</TextLink>
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
                </form>

                <p
                    class="mt-8 text-center text-xs"
                    style="color: var(--gl-text-muted)"
                >
                    Learn. Play. Achieve.
                </p>
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
}
.login-dark [data-slot='button'] {
    background: linear-gradient(
        135deg,
        var(--gl-primary),
        var(--gl-secondary)
    ) !important;
    color: #fff !important;
    box-shadow: 0 0 12px var(--gl-primary-glow);
}
.login-dark [data-slot='label'],
.login-dark label {
    color: var(--gl-text-secondary) !important;
}
.login-dark a {
    color: var(--gl-primary);
}
.login-dark span {
    color: var(--gl-text-secondary);
}
</style>
