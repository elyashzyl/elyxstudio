<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Search, ShieldCheck, ShoppingBag, Store, Trash2, UserCheck, Users, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import CommerceLayout from '@/layouts/CommerceLayout.vue';

interface Account {
    id: number;
    name: string;
    email: string;
    role: 'buyer' | 'seller' | 'superadmin';
    gender?: string | null;
    bio?: string | null;
    avatar?: string | null;
    created_at: string;
}

const props = defineProps<{ users: Account[]; basePath?: string }>();
defineOptions({ layout: CommerceLayout });

const page = usePage();
const search = ref('');
const roleFilter = ref('');
const selected = ref<number[]>([]);
const editing = ref<Account | null>(null);
const showForm = ref(false);
const impersonatingId = ref<number | null>(null);

const emptyForm = () => ({ name: '', email: '', role: 'buyer' as Account['role'], gender: '', password: '' });
const form = useForm(emptyForm());
const flash = computed(() => (page.props as any).flash ?? {});
const currentUser = computed(() => (page.props as any).auth?.user ?? null);

const filteredUsers = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.users.filter((user) => {
        const matchesRole = !roleFilter.value || user.role === roleFilter.value;
        const matchesSearch = !term || `${user.name} ${user.email}`.toLowerCase().includes(term);
        return matchesRole && matchesSearch;
    });
});

const counts = computed(() => ({
    buyers: props.users.filter((user) => user.role === 'buyer').length,
    sellers: props.users.filter((user) => user.role === 'seller').length,
    admins: props.users.filter((user) => user.role === 'superadmin').length,
}));

const allVisibleSelected = computed(() => filteredUsers.value.length > 0 && filteredUsers.value.every((user) => selected.value.includes(user.id)));

function openCreate() {
    editing.value = null;
    form.defaults(emptyForm());
    form.reset();
    form.clearErrors();
    showForm.value = true;
}

function openEdit(user: Account) {
    editing.value = user;
    form.defaults({ name: user.name, email: user.email, role: user.role, gender: user.gender ?? '', password: '' });
    form.reset();
    form.clearErrors();
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    editing.value = null;
}

function submitForm() {
    const options = { onSuccess: closeForm };
    if (editing.value) {
        form.put(`/admin/users/${editing.value.id}`, options);
    } else {
        form.post('/admin/users', options);
    }
}

function toggleAll() {
    if (allVisibleSelected.value) {
        selected.value = selected.value.filter((id) => !filteredUsers.value.some((user) => user.id === id));
    } else {
        selected.value = Array.from(new Set([...selected.value, ...filteredUsers.value.map((user) => user.id)]));
    }
}

function deleteUser(user: Account) {
    if (user.id === currentUser.value?.id || !window.confirm(`Delete ${user.name}'s account?`)) return;
    router.delete(`/admin/users/${user.id}`);
}

function bulkDelete() {
    const ids = selected.value.filter((id) => id !== currentUser.value?.id);
    if (!ids.length || !window.confirm(`Delete ${ids.length} selected account(s)?`)) return;
    router.delete('/admin/users/bulk', { data: { ids }, onSuccess: () => { selected.value = []; } });
}

function impersonate(user: Account) {
    if (user.role === 'superadmin') return;
    impersonatingId.value = user.id;
    router.post(`/admin/users/${user.id}/impersonate`, {}, { onFinish: () => { impersonatingId.value = null; } });
}

function leaveImpersonation() {
    router.post('/leave-impersonation');
}

function roleLabel(role: Account['role']) {
    return role === 'superadmin' ? 'Administrator' : role === 'seller' ? 'Seller' : 'Buyer';
}
</script>

<template>
    <Head title="Accounts" />
    <div class="px-5 py-8 sm:px-8 lg:px-10">
        <div v-if="flash.success" class="mb-5 rounded-2xl border px-4 py-3 text-sm" style="border-color: rgba(16,185,129,.25); background: rgba(16,185,129,.08); color: var(--gl-success)">{{ flash.success }}</div>
        <div v-if="page.props.impersonated_by" class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border px-4 py-3 text-sm" style="border-color: rgba(245,158,11,.3); background: rgba(245,158,11,.08); color: var(--gl-accent)">
            <span>You are viewing ELYX as {{ currentUser?.name }}.</span>
            <button type="button" class="rounded-xl px-3 py-2 text-xs font-bold" style="background: var(--gl-accent); color: #102a43" @click="leaveImpersonation">Return to admin</button>
        </div>

        <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
            <div><p class="text-xs font-bold tracking-[0.2em] uppercase" style="color: var(--gl-primary)">Platform access</p><h1 class="mt-2 text-3xl font-extrabold tracking-tight" style="color: var(--gl-text-primary)">Accounts & sellers</h1><p class="mt-2 text-sm" style="color: var(--gl-text-secondary)">Manage buyers, sellers, and administrators across ELYX Studio.</p></div>
            <button type="button" class="inline-flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-bold text-white" style="background: linear-gradient(135deg, #27a9e8, #0f6ea8); box-shadow: 0 8px 20px rgba(15,110,168,.22)" @click="openCreate"><Plus class="h-4 w-4" /> Add account</button>
        </div>

        <div class="mb-6 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border p-4" style="background: var(--gl-surface); border-color: var(--gl-border)"><ShoppingBag class="h-5 w-5" style="color: var(--gl-primary)" /><strong class="mt-3 block text-2xl" style="color: var(--gl-text-primary)">{{ counts.buyers }}</strong><span class="text-xs" style="color: var(--gl-text-muted)">Buyers</span></div>
            <div class="rounded-2xl border p-4" style="background: var(--gl-surface); border-color: var(--gl-border)"><Store class="h-5 w-5" style="color: var(--gl-primary)" /><strong class="mt-3 block text-2xl" style="color: var(--gl-text-primary)">{{ counts.sellers }}</strong><span class="text-xs" style="color: var(--gl-text-muted)">Sellers</span></div>
            <div class="rounded-2xl border p-4" style="background: var(--gl-surface); border-color: var(--gl-border)"><ShieldCheck class="h-5 w-5" style="color: var(--gl-primary)" /><strong class="mt-3 block text-2xl" style="color: var(--gl-text-primary)">{{ counts.admins }}</strong><span class="text-xs" style="color: var(--gl-text-muted)">Administrators</span></div>
        </div>

        <div class="mb-4 flex flex-wrap gap-3">
            <div class="relative min-w-[240px] flex-1"><Search class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" style="color: var(--gl-text-muted)" /><input v-model="search" type="search" placeholder="Search accounts..." class="w-full rounded-xl border py-3 pr-3 pl-10 text-sm outline-none" style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border)" /></div>
            <select v-model="roleFilter" class="rounded-xl border px-3 py-3 text-sm outline-none" style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border)"><option value="">All roles</option><option value="buyer">Buyers</option><option value="seller">Sellers</option><option value="superadmin">Administrators</option></select>
        </div>

        <div v-if="selected.length" class="mb-4 flex items-center gap-3 rounded-xl border px-4 py-3 text-sm" style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border)"><span>{{ selected.length }} selected</span><button type="button" class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-bold" style="background: var(--gl-danger-bg); color: var(--gl-danger)" @click="bulkDelete"><Trash2 class="h-3.5 w-3.5" /> Delete</button><button type="button" class="ml-auto text-xs" style="color: var(--gl-text-muted)" @click="selected = []">Clear</button></div>

        <div class="overflow-hidden rounded-2xl border" style="background: var(--gl-surface); border-color: var(--gl-border)">
            <div class="flex items-center gap-3 border-b px-4 py-3 text-xs font-bold uppercase" style="border-color: var(--gl-border); color: var(--gl-text-muted)"><input type="checkbox" :checked="allVisibleSelected" @change="toggleAll" /><span>Account</span><span class="ml-auto">Role</span><span class="w-28 text-right">Actions</span></div>
            <div v-for="user in filteredUsers" :key="user.id" class="flex items-center gap-3 border-b px-4 py-4 last:border-b-0" style="border-color: var(--gl-border)">
                <input v-model="selected" type="checkbox" :value="user.id" :disabled="user.id === currentUser?.id" />
                <div class="flex min-w-0 flex-1 items-center gap-3"><div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full text-sm font-bold text-white" style="background: linear-gradient(135deg, #27a9e8, #0f6ea8)"><img v-if="user.avatar" :src="user.avatar" :alt="user.name" class="h-full w-full object-cover" /><span v-else>{{ user.name.charAt(0).toUpperCase() }}</span></div><div class="min-w-0"><p class="truncate text-sm font-bold" style="color: var(--gl-text-primary)">{{ user.name }}</p><p class="truncate text-xs" style="color: var(--gl-text-muted)">{{ user.email }}</p></div></div>
                <span class="hidden rounded-full px-3 py-1 text-xs font-bold sm:inline-flex" :style="user.role === 'superadmin' ? { background: 'rgba(124,58,237,.12)', color: '#7c3aed' } : user.role === 'seller' ? { background: 'rgba(39,169,232,.12)', color: 'var(--gl-primary)' } : { background: 'rgba(16,185,129,.12)', color: 'var(--gl-success)' }">{{ roleLabel(user.role) }}</span>
                <div class="flex w-28 justify-end gap-1"><button type="button" class="rounded-lg p-2" title="Edit account" style="color: var(--gl-text-secondary)" @click="openEdit(user)"><Pencil class="h-4 w-4" /></button><button v-if="user.role !== 'superadmin'" type="button" class="rounded-lg p-2" title="Impersonate account" style="color: var(--gl-primary)" :disabled="impersonatingId === user.id" @click="impersonate(user)"><UserCheck class="h-4 w-4" /></button><button v-if="user.id !== currentUser?.id" type="button" class="rounded-lg p-2" title="Delete account" style="color: var(--gl-danger)" @click="deleteUser(user)"><Trash2 class="h-4 w-4" /></button></div>
            </div>
            <div v-if="!filteredUsers.length" class="px-6 py-16 text-center"><Users class="mx-auto h-8 w-8" style="color: var(--gl-text-muted)" /><p class="mt-3 text-sm" style="color: var(--gl-text-secondary)">No accounts match your search.</p></div>
        </div>
    </div>

    <Teleport to="body">
        <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" @click.self="closeForm">
            <div class="w-full max-w-md rounded-2xl border p-6 shadow-2xl" style="background: var(--gl-surface); border-color: var(--gl-border)">
                <div class="mb-5 flex items-center justify-between"><div><p class="text-xs font-bold tracking-[0.18em] uppercase" style="color: var(--gl-primary)">{{ editing ? 'Edit account' : 'New account' }}</p><h2 class="mt-1 text-xl font-extrabold" style="color: var(--gl-text-primary)">{{ editing ? editing.name : 'Add an ELYX account' }}</h2></div><button type="button" class="rounded-lg p-2" style="color: var(--gl-text-muted)" @click="closeForm"><X class="h-5 w-5" /></button></div>
                <form class="space-y-4" @submit.prevent="submitForm"><div><label class="mb-1.5 block text-xs font-bold" style="color: var(--gl-text-secondary)">Name</label><input v-model="form.name" required class="w-full rounded-xl border px-3 py-2.5 text-sm outline-none" style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border)" /><p v-if="form.errors.name" class="mt-1 text-xs" style="color: var(--gl-danger)">{{ form.errors.name }}</p></div><div><label class="mb-1.5 block text-xs font-bold" style="color: var(--gl-text-secondary)">Email</label><input v-model="form.email" required type="email" class="w-full rounded-xl border px-3 py-2.5 text-sm outline-none" style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border)" /><p v-if="form.errors.email" class="mt-1 text-xs" style="color: var(--gl-danger)">{{ form.errors.email }}</p></div><div><label class="mb-1.5 block text-xs font-bold" style="color: var(--gl-text-secondary)">Role</label><select v-model="form.role" class="w-full rounded-xl border px-3 py-2.5 text-sm outline-none" style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border)"><option value="buyer">Buyer</option><option value="seller">Seller</option><option value="superadmin">Administrator</option></select></div><div><label class="mb-1.5 block text-xs font-bold" style="color: var(--gl-text-secondary)">Password {{ editing ? '(leave blank to keep current)' : '' }}</label><input v-model="form.password" :required="!editing" type="password" minlength="8" class="w-full rounded-xl border px-3 py-2.5 text-sm outline-none" style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border)" /><p v-if="form.errors.password" class="mt-1 text-xs" style="color: var(--gl-danger)">{{ form.errors.password }}</p></div><div class="flex justify-end gap-2 pt-2"><button type="button" class="rounded-xl border px-4 py-2.5 text-sm font-bold" style="border-color: var(--gl-border); color: var(--gl-text-secondary)" @click="closeForm">Cancel</button><button type="submit" class="rounded-xl px-4 py-2.5 text-sm font-bold text-white disabled:opacity-50" style="background: linear-gradient(135deg, #27a9e8, #0f6ea8)" :disabled="form.processing">{{ form.processing ? 'Saving...' : editing ? 'Save changes' : 'Create account' }}</button></div></form>
            </div>
        </div>
    </Teleport>
</template>
