<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Sparkles } from '@lucide/vue';
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

defineOptions({
    layout: {
        title: 'ELYX Studio',
        description: '',
    },
});
</script>

<template>
    <Head title="ELYX Studio" />
    <div
        class="elyx-login-page min-h-screen px-5 py-6 text-[#102a43] sm:px-8 sm:py-8"
    >
        <header
            class="mx-auto flex w-full max-w-6xl items-center justify-between"
        >
            <Link href="/" class="flex items-center gap-3">
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#27a9e8] text-white shadow-[0_8px_18px_rgba(39,169,232,0.22)]"
                >
                    <Sparkles class="h-4 w-4" />
                </span>
                <span class="leading-none">
                    <strong
                        class="block text-sm font-extrabold tracking-[0.18em] uppercase"
                        >ELYX</strong
                    >
                    <small
                        class="mt-1 block text-[9px] font-semibold tracking-[0.2em] text-[#55718a] uppercase"
                        >Studio shop</small
                    >
                </span>
            </Link>
            <Link
                href="/"
                class="text-sm font-semibold text-[#55718a] transition hover:text-[#0f6ea8]"
                >Back to store</Link
            >
        </header>

        <main
            class="mx-auto flex w-full max-w-6xl justify-center py-16 sm:py-20 lg:py-24"
        >
            <section class="relative w-full max-w-[26rem]">
                <div
                    aria-hidden="true"
                    class="pointer-events-none absolute -inset-x-10 -top-10 -z-0 hidden h-40 rounded-[2.5rem] border border-[#c6ebfa] bg-[#e6f7ff]/80 shadow-[0_24px_60px_rgba(39,169,232,0.08)] sm:block"
                ></div>
                <div class="relative z-10 mb-7 text-center">
                    <p
                        class="text-xs font-bold tracking-[0.16em] text-[#0f6ea8] uppercase"
                    >
                        Welcome back
                    </p>
                    <h1
                        class="mt-3 text-3xl font-extrabold tracking-[-0.035em] text-[#102a43]"
                    >
                        Sign in to ELYX
                    </h1>
                    <p class="mt-3 text-sm leading-6 text-[#55718a]">
                        Manage your orders, products, and studio workspace.
                    </p>
                </div>

                <div
                    v-if="props.status"
                    class="mb-4 rounded-xl border border-[#b9e8d4] bg-[#f0fdf6] px-4 py-3 text-sm text-[#087443]"
                >
                    {{ props.status }}
                </div>

                <div
                    class="relative overflow-hidden rounded-2xl border border-[#d7ebf8] bg-white p-6 shadow-[0_18px_50px_rgba(16,42,67,0.08)] sm:p-8"
                >
                    <div
                        aria-hidden="true"
                        class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#8bd5f4] via-[#27a9e8] to-[#0f6ea8]"
                    ></div>
                    <Form
                        action="/login"
                        method="post"
                        v-slot="{ errors, processing }"
                        class="space-y-5"
                    >
                        <div class="grid gap-2">
                            <Label
                                for="email"
                                class="text-[13px] font-bold text-[#355d78]"
                                >Email address</Label
                            >
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                required
                                autofocus
                                autocomplete="email"
                                placeholder="you@example.com"
                                class="h-11 rounded-xl border-[#c7e3f3] bg-[#fbfeff] px-3.5 text-[#102a43] shadow-none placeholder:text-[#8aa4b8] focus-visible:border-[#27a9e8] focus-visible:ring-[#27a9e8]/20"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <div
                                class="flex items-center justify-between gap-3"
                            >
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
                                class="h-11 rounded-xl border-[#c7e3f3] bg-[#fbfeff] px-3.5 text-[#102a43] shadow-none placeholder:text-[#8aa4b8] focus-visible:border-[#27a9e8] focus-visible:ring-[#27a9e8]/20"
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
                            Remember me
                        </label>

                        <Button
                            type="submit"
                            class="h-11 w-full rounded-xl bg-[#0f6ea8] text-sm font-bold text-white shadow-[0_8px_18px_rgba(15,110,168,0.18)] transition hover:bg-[#0b527e]"
                            :disabled="processing"
                        >
                            <Spinner v-if="processing" />
                            <span v-else>Sign in</span>
                        </Button>
                    </Form>
                </div>

                <p
                    v-if="props.canRegister"
                    class="mt-6 text-center text-sm text-[#55718a]"
                >
                    New to ELYX?
                    <TextLink
                        href="/register"
                        class="font-bold text-[#0f6ea8] hover:text-[#0b527e]"
                        >Create an account</TextLink
                    >
                </p>
                <p class="mt-8 text-center text-xs text-[#8aa4b8]">
                    Secure access to your ELYX Studio account.
                </p>
            </section>
        </main>
    </div>
</template>

<style scoped>
.elyx-login-page {
    color-scheme: light;
    background:
        radial-gradient(
            circle at 8% 0%,
            rgba(139, 213, 244, 0.26),
            transparent 25rem
        ),
        radial-gradient(
            circle at 92% 100%,
            rgba(39, 169, 232, 0.1),
            transparent 24rem
        ),
        #f4fbff;
}

.elyx-login-page :deep(input) {
    color: #102a43;
}

.elyx-login-page :deep(input::placeholder) {
    color: #8aa4b8;
    opacity: 1;
}
</style>
