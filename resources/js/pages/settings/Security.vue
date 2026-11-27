<script setup lang="ts">
import { Head, usePage, router } from '@inertiajs/vue3';
import { onMounted } from 'vue';

defineProps<{
    canManageTwoFactor?: boolean;
    canManagePasskeys?: boolean;
    passkeys?: any[];
    passwordRules?: string;
    twoFactorEnabled?: boolean;
    requiresConfirmation?: boolean;
}>();

const page = usePage();

onMounted(() => {
    const user: any = page.props.auth?.user;

    if (user?.role === 'teacher' || user?.role === 'superadmin') {
        router.visit('/teacher/profile');
    } else {
        router.visit('/student/profile');
    }
});
</script>

<template>
    <Head title="Security Settings" />
    <div class="flex min-h-[50vh] items-center justify-center p-8 text-center text-sm" style="color: var(--gl-text-muted)">
        Redirecting to your security settings...
    </div>
</template>
