<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    LayoutDashboard,
    LogOut,
    Menu,
    Package,
    ShoppingBag,
    Sparkles,
    Store,
    Users,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface PanelUser {
    name?: string;
    email?: string;
    role?: string;
    avatar?: string | null;
    is_commerce_admin?: boolean;
}

interface SharedProps {
    auth?: {
        user?: PanelUser | null;
    } | null;
}

const page = usePage();
const mobileOpen = ref(false);
const sharedProps = computed(() => page.props as unknown as SharedProps);
const user = computed(() => sharedProps.value.auth?.user ?? null);
const role = computed(() => user.value?.role ?? '');
const isAdmin = computed(
    () => role.value === 'superadmin' || user.value?.is_commerce_admin === true,
);
const isSeller = computed(() => role.value === 'seller');
const panelTitle = computed(() =>
    isAdmin.value ? 'Commerce admin' : isSeller.value ? 'Seller studio' : 'My account',
);
const panelDescription = computed(() =>
    isAdmin.value
        ? 'Run the catalog, orders, and seller operations.'
        : isSeller.value
          ? 'Publish and manage your ELYX offerings.'
          : 'Keep your ELYX purchases and requests in one place.',
);
const accountHref = computed(() =>
    isAdmin.value ? '/admin/commerce' : isSeller.value ? '/seller/dashboard' : '/account',
);

const navItems = computed(() => {
    if (isAdmin.value) {
        return [
            { label: 'Overview', href: '/admin/commerce', icon: LayoutDashboard },
            { label: 'Users & sellers', href: '/admin/users', icon: Users },
            { label: 'View storefront', href: '/shop', icon: Store },
        ];
    }

    if (isSeller.value) {
        return [
            { label: 'Seller studio', href: '/seller/dashboard', icon: LayoutDashboard },
            { label: 'Buyer account', href: '/account', icon: ShoppingBag },
            { label: 'View storefront', href: '/shop', icon: Store },
        ];
    }

    return [
        { label: 'My account', href: '/account', icon: LayoutDashboard },
        { label: 'Browse shop', href: '/shop', icon: Package },
    ];
});

const isActive = (href: string) => {
    if (href === '/admin/commerce') {
        return page.url === '/admin/commerce' || page.url.startsWith('/teacher/commerce');
    }

    if (href === '/account') {
        return page.url === '/account' || page.url.startsWith('/account/orders');
    }

    return page.url === href || page.url.startsWith(`${href}/`);
};

const closeMenu = () => {
    mobileOpen.value = false;
};

const logout = () => {
    router.post('/logout', {}, {
        onSuccess: () => {
            window.location.href = '/login';
        },
    });
};
</script>

<template>
    <div class="elyx-panel-shell">
        <div
            v-if="mobileOpen"
            class="elyx-panel-backdrop lg:hidden"
            aria-hidden="true"
            @click="closeMenu"
        ></div>

        <aside class="elyx-panel-sidebar elyx-panel-sidebar-mobile lg:hidden" :class="mobileOpen ? 'is-open' : ''">
            <div class="elyx-panel-brand">
                <Link href="/" class="elyx-panel-brand-link" @click="closeMenu">
                    <span class="elyx-panel-logo"><Sparkles class="h-5 w-5" /></span>
                    <span>
                        <strong>ELYX</strong>
                        <small>Studio commerce</small>
                    </span>
                </Link>
                <button type="button" class="elyx-panel-icon-button" aria-label="Close navigation" @click="closeMenu">
                    <X class="h-5 w-5" />
                </button>
            </div>
            <nav class="elyx-panel-nav" aria-label="Commerce navigation">
                <p class="elyx-panel-nav-label">Workspace</p>
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="elyx-panel-nav-link"
                    :class="{ 'is-active': isActive(item.href) }"
                    @click="closeMenu"
                >
                    <span class="elyx-panel-nav-icon"><component :is="item.icon" class="h-4 w-4" /></span>
                    <span>{{ item.label }}</span>
                </Link>
            </nav>
            <div class="elyx-panel-profile">
                <div class="elyx-panel-profile-row">
                    <span class="elyx-panel-avatar">{{ (user?.name ?? 'E').charAt(0).toUpperCase() }}</span>
                    <span class="min-w-0">
                        <strong class="block truncate">{{ user?.name ?? 'ELYX member' }}</strong>
                        <small class="block truncate">{{ user?.email ?? 'Account' }}</small>
                    </span>
                </div>
                <button type="button" class="elyx-panel-logout" @click="logout">
                    <LogOut class="h-4 w-4" /> Sign out
                </button>
            </div>
        </aside>

        <aside class="elyx-panel-sidebar elyx-panel-sidebar-desktop hidden lg:flex">
            <div class="elyx-panel-brand">
                <Link href="/" class="elyx-panel-brand-link">
                    <span class="elyx-panel-logo"><Sparkles class="h-5 w-5" /></span>
                    <span>
                        <strong>ELYX</strong>
                        <small>Studio commerce</small>
                    </span>
                </Link>
            </div>
            <div class="elyx-panel-context">
                <span class="elyx-panel-context-mark"><ShoppingBag class="h-4 w-4" /></span>
                <span>
                    <small>Workspace</small>
                    <strong>{{ panelTitle }}</strong>
                </span>
            </div>
            <nav class="elyx-panel-nav" aria-label="Commerce navigation">
                <p class="elyx-panel-nav-label">Navigate</p>
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="elyx-panel-nav-link"
                    :class="{ 'is-active': isActive(item.href) }"
                >
                    <span class="elyx-panel-nav-icon"><component :is="item.icon" class="h-4 w-4" /></span>
                    <span>{{ item.label }}</span>
                </Link>
            </nav>
            <div class="elyx-panel-profile">
                <Link :href="accountHref" class="elyx-panel-profile-row">
                    <span class="elyx-panel-avatar">{{ (user?.name ?? 'E').charAt(0).toUpperCase() }}</span>
                    <span class="min-w-0">
                        <strong class="block truncate">{{ user?.name ?? 'ELYX member' }}</strong>
                        <small class="block truncate">{{ user?.role ?? 'member' }}</small>
                    </span>
                </Link>
                <button type="button" class="elyx-panel-logout" @click="logout">
                    <LogOut class="h-4 w-4" /> Sign out
                </button>
            </div>
        </aside>

        <div class="elyx-panel-content">
            <header class="elyx-panel-header">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" class="elyx-panel-menu-button lg:hidden" aria-label="Open navigation" @click="mobileOpen = true">
                        <Menu class="h-5 w-5" />
                    </button>
                    <div class="min-w-0">
                        <p class="elyx-panel-kicker">{{ panelTitle }}</p>
                        <p class="hidden truncate text-sm lg:block">{{ panelDescription }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link href="/shop" class="elyx-panel-store-link">
                        <Store class="h-4 w-4" />
                        <span class="hidden sm:inline">Storefront</span>
                        <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                </div>
            </header>
            <main class="elyx-panel-main">
                <slot />
            </main>
        </div>
    </div>
</template>
