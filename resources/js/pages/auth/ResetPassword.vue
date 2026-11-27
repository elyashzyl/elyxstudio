<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Shield, Lock } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Reset password',
        description: 'Please enter your new password below',
    },
});

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const inputEmail = ref(props.email);
</script>

<template>
    <Head title="Reset password" />

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
                        rgba(124, 58, 237, 0.06),
                        transparent 50%
                    );
            "
        ></div>

        <div class="relative z-10 w-full max-w-md px-6">
            <div
                class="rounded-3xl p-8"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                "
            >
                <div class="mb-6 text-center">
                    <div
                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl"
                        style="
                            background: linear-gradient(
                                135deg,
                                var(--gl-secondary),
                                var(--gl-primary)
                            );
                            box-shadow: 0 0 24px var(--gl-secondary-glow);
                        "
                    >
                        <Shield
                            class="h-8 w-8 text-white"
                            :stroke-width="1.5"
                        />
                    </div>
                    <h2
                        class="text-xl font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        Create a Strong Password
                    </h2>
                    <p
                        class="mt-2 text-sm"
                        style="color: var(--gl-text-secondary)"
                    >
                        Secure your account and continue your learning
                        adventure.
                    </p>
                </div>

                <Form
                    v-bind="update.form()"
                    :transform="(data) => ({ ...data, token, email })"
                    :reset-on-success="['password', 'password_confirmation']"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-5">
                        <div class="grid gap-2">
                            <Label
                                for="email"
                                style="color: var(--gl-text-secondary)"
                                >Email</Label
                            >
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                autocomplete="email"
                                v-model="inputEmail"
                                readonly
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label
                                for="password"
                                style="color: var(--gl-text-secondary)"
                                >New password</Label
                            >
                            <PasswordInput
                                id="password"
                                name="password"
                                autocomplete="new-password"
                                autofocus
                                placeholder="Password"
                                :passwordrules="passwordRules"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="grid gap-2">
                            <Label
                                for="password_confirmation"
                                style="color: var(--gl-text-secondary)"
                                >Confirm password</Label
                            >
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                autocomplete="new-password"
                                placeholder="Confirm password"
                                :passwordrules="passwordRules"
                            />
                            <InputError
                                :message="errors.password_confirmation"
                            />
                        </div>

                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="processing"
                            data-test="reset-password-button"
                        >
                            <Spinner v-if="processing" />
                            <Lock
                                v-else
                                class="mr-1.5 h-4 w-4"
                                :stroke-width="2"
                            />
                            Reset password
                        </Button>
                    </div>
                </Form>
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
