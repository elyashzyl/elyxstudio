<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ArrowLeft, ShoppingBag, Store, Sparkles } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

const props = defineProps<{ passwordRules: string }>();

const role = ref('buyer');

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Join the ELYX Studio community.',
    },
});
</script>

<template>
    <Head title="Create your ELYX account" />
    <div class="login-dark fixed inset-0 flex overflow-y-auto" style="background: var(--gl-bg)">
        <Link href="/" class="absolute top-5 left-5 z-30 inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-semibold" style="background: var(--gl-surface-2); color: var(--gl-text-secondary); border: 1px solid var(--gl-border)">
            <ArrowLeft class="h-3.5 w-3.5" /> Back to home
        </Link>

        <div class="mx-auto flex w-full max-w-md flex-col justify-center px-6 py-20">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl text-white" style="background: linear-gradient(135deg, #27a9e8, #0f6ea8); box-shadow: 0 10px 30px rgba(39,169,232,.25)">
                    <Sparkles class="h-6 w-6" />
                </div>
                <p class="text-xs font-bold tracking-[0.2em] uppercase" style="color: var(--gl-primary)">ELYX Studio</p>
                <h1 class="mt-2 text-2xl font-bold" style="color: var(--gl-text-primary)">Create your account</h1>
                <p class="mt-2 text-sm" style="color: var(--gl-text-secondary)">Shop digital products, commission a website, or share your own work.</p>
            </div>

            <Form action="/register" method="post" :reset-on-success="['password', 'password_confirmation']" v-slot="{ errors, processing }" class="space-y-4">
                <div class="grid gap-1.5">
                    <Label for="name" style="color: var(--gl-text-secondary)">Full name</Label>
                    <Input id="name" name="name" required autofocus autocomplete="name" placeholder="Your name" />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="email" style="color: var(--gl-text-secondary)">Email address</Label>
                    <Input id="email" name="email" type="email" required autocomplete="email" placeholder="you@example.com" />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <button type="button" class="rounded-2xl border p-4 text-left transition" :style="role === 'buyer' ? { borderColor: 'var(--gl-primary)', background: 'rgba(39,169,232,.12)' } : { borderColor: 'var(--gl-border)', background: 'var(--gl-surface)' }" @click="role = 'buyer'">
                        <ShoppingBag class="mb-2 h-5 w-5" style="color: var(--gl-primary)" />
                        <strong class="block text-sm" style="color: var(--gl-text-primary)">Shop as a buyer</strong>
                        <span class="mt-1 block text-xs" style="color: var(--gl-text-muted)">Track orders and discover ELYX products.</span>
                    </button>
                    <button type="button" class="rounded-2xl border p-4 text-left transition" :style="role === 'seller' ? { borderColor: 'var(--gl-primary)', background: 'rgba(39,169,232,.12)' } : { borderColor: 'var(--gl-border)', background: 'var(--gl-surface)' }" @click="role = 'seller'">
                        <Store class="mb-2 h-5 w-5" style="color: var(--gl-primary)" />
                        <strong class="block text-sm" style="color: var(--gl-text-primary)">Sell with ELYX</strong>
                        <span class="mt-1 block text-xs" style="color: var(--gl-text-muted)">Submit products for admin review and approval.</span>
                    </button>
                </div>
                <input type="hidden" name="role" :value="role" />

                <div class="grid gap-1.5">
                    <Label for="password" style="color: var(--gl-text-secondary)">Password</Label>
                    <PasswordInput id="password" name="password" required autocomplete="new-password" />
                    <p class="text-[11px]" style="color: var(--gl-text-muted)">{{ props.passwordRules }}</p>
                    <InputError :message="errors.password" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="password_confirmation" style="color: var(--gl-text-secondary)">Confirm password</Label>
                    <PasswordInput id="password_confirmation" name="password_confirmation" required autocomplete="new-password" />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button type="submit" class="w-full" :disabled="processing">
                    <Spinner v-if="processing" /> Create account
                </Button>
                <p class="text-center text-sm" style="color: var(--gl-text-muted)">Already have an account? <TextLink href="/login">Sign in</TextLink></p>
            </Form>
        </div>
    </div>
</template>
