<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Mail, ArrowLeft } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Forgot password',
        description: 'Enter your email to receive a password reset link',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Forgot password" />

    <div
        class="login-dark fixed inset-0 flex items-center justify-center"
        style="background: var(--gl-bg)"
    >
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

        <!-- Ambient bg -->
        <div
            class="absolute inset-0"
            style="
                background:
                    radial-gradient(
                        circle at 30% 50%,
                        rgba(59, 130, 246, 0.06),
                        transparent 50%
                    ),
                    radial-gradient(
                        circle at 70% 50%,
                        rgba(124, 58, 237, 0.06),
                        transparent 50%
                    );
            "
        ></div>

        <div class="relative z-10 w-full max-w-md px-6">
            <!-- Card -->
            <div
                class="rounded-3xl p-8"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                "
            >
                <div class="mb-6 text-center">
                    <img
                        src="/logo.png"
                        alt="ELYX Studio logo"
                        class="mx-auto mb-4 h-16 w-16 rounded-full object-cover"
                        style="box-shadow: 0 0 24px var(--gl-primary-glow)"
                    />
                    <h2
                        class="text-xl font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        Recover Your Adventure
                    </h2>
                    <p
                        class="mt-2 text-sm"
                        style="color: var(--gl-text-secondary)"
                    >
                        Don't worry — your progress is safe. Enter your email
                        and we'll send you a recovery link.
                    </p>
                </div>

                <div
                    v-if="status"
                    class="mb-6 rounded-2xl px-4 py-3 text-center text-sm"
                    style="
                        background: rgba(16, 185, 129, 0.1);
                        color: #10b981;
                        border: 1px solid rgba(16, 185, 129, 0.2);
                    "
                >
                    {{ status }}
                </div>

                <Form v-bind="email.form()" v-slot="{ errors, processing }">
                    <div class="mb-6 grid gap-2">
                        <Label
                            for="email"
                            style="color: var(--gl-text-secondary)"
                            >Email address</Label
                        >
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            autocomplete="off"
                            autofocus
                            placeholder="email@example.com"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <Button class="mb-4 w-full" :disabled="processing">
                        <Spinner v-if="processing" />
                        <Mail v-else class="mr-1.5 h-4 w-4" :stroke-width="2" />
                        Send Recovery Link
                    </Button>
                </Form>

                <div class="text-center">
                    <TextLink
                        :href="login()"
                        class="inline-flex items-center gap-1 text-sm"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" :stroke-width="2" />
                        Back to log in
                    </TextLink>
                    <div class="mt-2.5">
                        <Link
                            href="/"
                            class="inline-flex items-center gap-1.5 text-xs font-medium transition-colors hover:underline"
                            style="color: var(--gl-text-muted)"
                        >
                            <ArrowLeft class="h-3 w-3" :stroke-width="2" />
                            Back to home
                        </Link>
                    </div>
                </div>
            </div>

            <p
                class="mt-6 text-center text-xs"
                style="color: var(--gl-text-muted)"
            >
                Learn. Play. Achieve.
            </p>
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
