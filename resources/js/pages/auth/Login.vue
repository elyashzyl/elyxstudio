<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, LockKeyhole, Sparkles } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

const props = defineProps<{ status?: string; canResetPassword: boolean; canRegister?: boolean }>();

defineOptions({
    layout: {
        title: 'Sign in',
        description: 'Access your ELYX Studio account.',
    },
});
</script>

<template>
    <Head title="Sign in" />
    <div class="login-dark fixed inset-0 flex overflow-y-auto" style="background: var(--gl-bg)">
        <Link href="/" class="absolute top-5 left-5 z-30 inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-semibold" style="background: var(--gl-surface-2); color: var(--gl-text-secondary); border: 1px solid var(--gl-border)">
            <ArrowLeft class="h-3.5 w-3.5" /> Back to home
        </Link>
        <div class="mx-auto flex w-full max-w-md flex-col justify-center px-6 py-20">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl text-white" style="background: linear-gradient(135deg, #27a9e8, #0f6ea8); box-shadow: 0 10px 30px rgba(39,169,232,.25)"><Sparkles class="h-6 w-6" /></div>
                <p class="text-xs font-bold tracking-[0.2em] uppercase" style="color: var(--gl-primary)">ELYX Studio</p>
                <h1 class="mt-2 text-2xl font-bold" style="color: var(--gl-text-primary)">Welcome back</h1>
                <p class="mt-2 text-sm" style="color: var(--gl-text-secondary)">Sign in to manage your orders, products, or studio workspace.</p>
            </div>
            <div v-if="props.status" class="mb-4 rounded-xl border px-4 py-3 text-sm" style="border-color: rgba(16,185,129,.25); background: rgba(16,185,129,.08); color: var(--gl-success)">{{ props.status }}</div>
            <Form action="/login" method="post" v-slot="{ errors, processing }" class="space-y-4">
                <div class="grid gap-1.5">
                    <Label for="email" style="color: var(--gl-text-secondary)">Email address</Label>
                    <Input id="email" name="email" type="email" required autofocus autocomplete="email" placeholder="you@example.com" />
                    <InputError :message="errors.email" />
                </div>
                <div class="grid gap-1.5">
                    <div class="flex items-center justify-between"><Label for="password" style="color: var(--gl-text-secondary)">Password</Label><TextLink v-if="props.canResetPassword" href="/forgot-password">Forgot password?</TextLink></div>
                    <PasswordInput id="password" name="password" required autocomplete="current-password" />
                    <InputError :message="errors.password" />
                </div>
                <label class="flex items-center gap-2 text-sm" style="color: var(--gl-text-secondary)"><input type="checkbox" name="remember" value="1" class="rounded" /> Remember me</label>
                <Button type="submit" class="w-full" :disabled="processing"><Spinner v-if="processing" /><LockKeyhole v-else class="h-4 w-4" /> Sign in</Button>
                <p v-if="props.canRegister" class="text-center text-sm" style="color: var(--gl-text-muted)">New to ELYX? <TextLink href="/register">Create an account</TextLink></p>
            </Form>
        </div>
    </div>
</template>
