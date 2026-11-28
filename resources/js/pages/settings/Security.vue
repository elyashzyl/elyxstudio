<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';

defineProps<{
    canManageTwoFactor?: boolean;
    canManagePasskeys?: boolean;
    passkeys?: any[];
    passwordRules?: string;
    twoFactorEnabled?: boolean;
    requiresConfirmation?: boolean;
}>();
</script>

<template>
    <Head title="Security settings" />
    <div class="mx-auto max-w-2xl px-5 py-10 sm:px-8">
        <p class="text-xs font-bold tracking-[0.2em] text-sky-700 uppercase">Account settings</p>
        <h1 class="mt-2 text-3xl font-extrabold text-slate-900">Security</h1>
        <p class="mt-2 text-sm text-slate-600">Protect your ELYX account with a strong password, two-factor authentication, and passkeys.</p>
        <div class="mt-8 grid gap-6">
            <section class="rounded-2xl border border-sky-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Change password</h2>
                <Form action="/settings/password" method="put" v-slot="{ errors, processing }" class="mt-4 grid gap-4">
                    <label class="grid gap-1 text-sm font-semibold text-slate-700">Current password<input name="current_password" type="password" autocomplete="current-password" class="rounded-xl border border-sky-100 px-3 py-2" required /><span v-if="errors.current_password" class="text-xs font-normal text-red-600">{{ errors.current_password }}</span></label>
                    <label class="grid gap-1 text-sm font-semibold text-slate-700">New password<input name="password" type="password" autocomplete="new-password" class="rounded-xl border border-sky-100 px-3 py-2" required /><span v-if="errors.password" class="text-xs font-normal text-red-600">{{ errors.password }}</span></label>
                    <label class="grid gap-1 text-sm font-semibold text-slate-700">Confirm new password<input name="password_confirmation" type="password" autocomplete="new-password" class="rounded-xl border border-sky-100 px-3 py-2" required /></label>
                    <button type="submit" class="w-fit rounded-xl bg-sky-600 px-4 py-2 text-sm font-bold text-white" :disabled="processing">{{ processing ? 'Updating…' : 'Update password' }}</button>
                </Form>
            </section>
            <ManageTwoFactor
                :can-manage-two-factor="canManageTwoFactor"
                :two-factor-enabled="twoFactorEnabled"
                :requires-confirmation="requiresConfirmation"
            />
            <ManagePasskeys
                :can-manage-passkeys="canManagePasskeys"
                :passkeys="passkeys ?? []"
            />
        </div>
    </div>
</template>
