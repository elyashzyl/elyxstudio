<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{ mustVerifyEmail?: boolean; status?: string }>();
</script>

<template>
    <Head title="Profile settings" />
    <div class="mx-auto max-w-2xl px-5 py-10 sm:px-8">
        <div class="mb-8"><p class="text-xs font-bold tracking-[0.2em] uppercase" style="color: var(--gl-primary)">Account settings</p><h1 class="mt-2 text-3xl font-extrabold" style="color: var(--gl-text-primary)">Your profile</h1><p class="mt-2 text-sm" style="color: var(--gl-text-secondary)">Keep your ELYX account details current.</p></div>
        <div class="rounded-2xl border p-6" style="background: var(--gl-surface); border-color: var(--gl-border)">
            <Form action="/settings/profile" method="patch" v-slot="{ errors, processing }" class="space-y-5">
                <div class="grid gap-2"><Label for="name" style="color: var(--gl-text-secondary)">Name</Label><Input id="name" name="name" :default-value="$page.props.auth?.user?.name" required /><InputError :message="errors.name" /></div>
                <div class="grid gap-2"><Label for="email" style="color: var(--gl-text-secondary)">Email</Label><Input id="email" name="email" type="email" :default-value="$page.props.auth?.user?.email" required /><InputError :message="errors.email" /></div>
                <div class="grid gap-2"><Label for="gender" style="color: var(--gl-text-secondary)">Gender (optional)</Label><Input id="gender" name="gender" :default-value="String($page.props.auth?.user?.gender ?? '')" /><InputError :message="errors.gender" /></div>
                <div class="flex flex-wrap gap-3"><Button type="submit" :disabled="processing">Save profile</Button><Link href="/settings/security" class="inline-flex items-center rounded-md border px-4 py-2 text-sm font-semibold" style="border-color: var(--gl-border); color: var(--gl-text-secondary)">Security</Link></div>
            </Form>
        </div>
    </div>
</template>
