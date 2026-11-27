<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Mail, CheckCircle2 } from '@lucide/vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'Email verification',
        description:
            'Please verify your email address by clicking on the link we just emailed to you.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Email verification" />

    <div
        class="login-dark fixed inset-0 flex items-center justify-center"
        style="background: var(--gl-bg)"
    >
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
                        rgba(16, 185, 129, 0.06),
                        transparent 50%
                    );
            "
        ></div>

        <div class="relative z-10 w-full max-w-md px-6">
            <div
                class="rounded-3xl p-8 text-center"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                "
            >
                <div
                    class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl"
                    style="
                        background: linear-gradient(
                            135deg,
                            var(--gl-success),
                            var(--gl-primary)
                        );
                        box-shadow: 0 0 24px var(--gl-primary-glow);
                    "
                >
                    <Mail class="h-8 w-8 text-white" :stroke-width="1.5" />
                </div>
                <h2
                    class="text-xl font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    Verify Your Account
                </h2>
                <p class="mt-2 text-sm" style="color: var(--gl-text-secondary)">
                    Confirm your email to unlock your learning adventure. Check
                    your inbox for the verification link we just sent.
                </p>

                <div
                    v-if="status === 'verification-link-sent'"
                    class="mt-5 rounded-2xl px-4 py-3 text-sm"
                    style="
                        background: rgba(16, 185, 129, 0.1);
                        color: #10b981;
                        border: 1px solid rgba(16, 185, 129, 0.2);
                    "
                >
                    <CheckCircle2
                        class="mr-1 inline h-4 w-4"
                        :stroke-width="2"
                    />
                    A new verification link has been sent to your email.
                </div>

                <Form v-bind="send.form()" class="mt-6" v-slot="{ processing }">
                    <Button :disabled="processing" variant="secondary">
                        <Spinner v-if="processing" />
                        <Mail v-else class="mr-1.5 h-4 w-4" :stroke-width="2" />
                        Resend verification email
                    </Button>
                </Form>

                <div class="mt-5">
                    <TextLink :href="logout()" as="button" class="text-sm">
                        Log out
                    </TextLink>
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
</style>
