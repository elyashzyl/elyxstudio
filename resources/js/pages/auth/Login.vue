<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Mail, Sparkles } from '@lucide/vue';
import ElyxAuthShell from '@/components/ElyxAuthShell.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister?: boolean;
}>();
</script>

<template>
    <Head title="ELYX Studio" />

    <ElyxAuthShell
        eyebrow="Welcome back"
        title="Sign in to ELYX"
        description="Pick up where you left off. Your orders, products, and studio workspace are waiting."
    >
        <template #status>
            <div
                v-if="props.status"
                class="mb-5 rounded-xl border border-[#b9e8d4] bg-[#f0fdf6] px-4 py-3 text-sm font-medium text-[#087443]"
            >
                {{ props.status }}
            </div>
        </template>

        <Form
            action="/login"
            method="post"
            v-slot="{ errors, processing }"
            class="space-y-5"
        >
            <div class="grid gap-2">
                <Label for="email" class="text-[13px] font-bold text-[#355d78]"
                    >Email address</Label
                >
                <div class="relative">
                    <Mail
                        aria-hidden="true"
                        class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-[#8aa4b8]"
                    />
                    <Input
                        id="email"
                        name="email"
                        type="email"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="you@example.com"
                        class="h-12 rounded-xl border-[#c7e3f3] bg-[#fbfeff] pl-10 text-[#102a43] shadow-none placeholder:text-[#8aa4b8] focus-visible:border-[#27a9e8] focus-visible:ring-[#27a9e8]/20"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between gap-3">
                    <Label
                        for="password"
                        class="text-[13px] font-bold text-[#355d78]"
                        >Password</Label
                    >
                    <TextLink
                        v-if="props.canResetPassword"
                        href="/forgot-password"
                        class="text-xs font-bold text-[#0f6ea8] hover:text-[#0b527e]"
                        >Forgot password?</TextLink
                    >
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="h-12 rounded-xl border-[#c7e3f3] bg-[#fbfeff] text-[#102a43] shadow-none placeholder:text-[#8aa4b8] focus-visible:border-[#27a9e8] focus-visible:ring-[#27a9e8]/20"
                />
                <InputError :message="errors.password" />
            </div>

            <label
                class="flex cursor-pointer items-center gap-2 text-xs font-semibold text-[#55718a]"
            >
                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                    class="h-4 w-4 rounded border-[#b9dced] accent-[#0f6ea8]"
                />
                Remember me on this device
            </label>

            <Button
                type="submit"
                class="h-12 w-full rounded-xl bg-[#0f6ea8] text-sm font-bold text-white shadow-[0_10px_22px_rgba(15,110,168,0.2)] transition hover:bg-[#0b527e]"
                :disabled="processing"
            >
                <Spinner v-if="processing" />
                <template v-else>
                    <Sparkles class="h-4 w-4" />
                    Sign in to ELYX
                </template>
            </Button>
        </Form>

        <template #footer>
            New to ELYX?
            <TextLink
                v-if="props.canRegister"
                href="/register"
                class="ml-1 font-bold text-[#0f6ea8] hover:text-[#0b527e]"
                >Create an account</TextLink
            >
            <Link
                v-else
                href="/register"
                class="ml-1 font-bold text-[#0f6ea8] hover:text-[#0b527e]"
                >Create an account</Link
            >
        </template>
    </ElyxAuthShell>
</template>
