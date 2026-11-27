<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CalendarDays,
    CheckCircle2,
    Clock3,
    Package,
    ShoppingBag,
} from '@lucide/vue';
import CommerceLayout from '@/layouts/CommerceLayout.vue';

interface BuyerOrderItem {
    id: number;
    name: string;
    quantity: number;
    line_total: string | number;
}

interface BuyerOrder {
    id: number;
    order_number: string;
    status: string;
    payment_status: string;
    total: string | number;
    created_at: string | null;
    confirmation_url: string | null;
    items: BuyerOrderItem[];
}

interface BuyerStats {
    orders: number;
    active: number;
    spent: string | number;
}

defineProps<{
    orders: BuyerOrder[];
    stats: BuyerStats;
}>();

defineOptions({ layout: CommerceLayout });

const money = (value: string | number) => {
    const amount = Number(value);

    return Number.isFinite(amount)
        ? amount.toLocaleString('en-US', {
              style: 'currency',
              currency: 'USD',
              minimumFractionDigits: 0,
              maximumFractionDigits: 2,
          })
        : '$0';
};

const formatDate = (value: string | null) => {
    if (!value) {
        return 'Date unavailable';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? 'Date unavailable'
        : date.toLocaleDateString('en-US', {
              month: 'short',
              day: 'numeric',
              year: 'numeric',
          });
};

const statusClasses = (status: string) => {
    const normalized = status.toLowerCase();

    if (['completed', 'paid', 'approved'].includes(normalized)) {
        return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
    }

    if (['cancelled', 'failed', 'refunded'].includes(normalized)) {
        return 'bg-red-50 text-red-700 ring-red-200';
    }

    if (['confirmed', 'in_progress', 'processing'].includes(normalized)) {
        return 'bg-[#e8f7fe] text-[#0f6ea8] ring-[#b9e2f6]';
    }

    return 'bg-amber-50 text-amber-700 ring-amber-200';
};
</script>

<template>
    <Head title="My account" />

    <section class="border-b border-[#d7ebf8] bg-white">
        <div class="mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-16">
            <div
                class="flex flex-col justify-between gap-7 sm:flex-row sm:items-end"
            >
                <div>
                    <p
                        class="mb-3 text-[10px] font-bold tracking-[0.18em] text-[#0f6ea8] uppercase"
                    >
                        Your ELYX account
                    </p>
                    <h1
                        class="text-5xl leading-[0.95] font-extrabold tracking-[-0.06em] text-[#102a43] sm:text-6xl"
                    >
                        Keep building.<br /><span class="text-[#27a9e8]"
                            >We’ll keep track.</span
                        >
                    </h1>
                </div>
                <Link
                    href="/shop"
                    class="inline-flex w-fit items-center gap-2 rounded-xl bg-[#0f6ea8] px-5 py-3.5 text-sm font-bold text-white shadow-[0_10px_22px_rgba(15,110,168,0.2)] transition hover:bg-[#0b527e]"
                    >Browse the shop <ArrowRight class="h-4 w-4"
                /></Link>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-5 py-10 sm:px-8 sm:py-14">
        <div class="grid gap-4 sm:grid-cols-3">
            <div
                class="rounded-3xl border border-[#c7e3f3] bg-white p-5 shadow-[0_8px_24px_rgba(16,42,67,0.04)]"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#e8f7fe] text-[#0f6ea8]"
                        ><ShoppingBag class="h-5 w-5"
                    /></span>
                    <span
                        class="text-[10px] font-bold tracking-[0.14em] text-[#8aa4b8] uppercase"
                        >All time</span
                    >
                </div>
                <p
                    class="mt-5 text-3xl font-extrabold tracking-tight text-[#102a43]"
                >
                    {{ stats.orders }}
                </p>
                <p class="mt-1 text-sm font-semibold text-[#55718a]">
                    Orders placed
                </p>
            </div>
            <div
                class="rounded-3xl border border-[#c7e3f3] bg-white p-5 shadow-[0_8px_24px_rgba(16,42,67,0.04)]"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#e8f7fe] text-[#0f6ea8]"
                        ><Clock3 class="h-5 w-5"
                    /></span>
                    <span
                        class="text-[10px] font-bold tracking-[0.14em] text-[#8aa4b8] uppercase"
                        >In motion</span
                    >
                </div>
                <p
                    class="mt-5 text-3xl font-extrabold tracking-tight text-[#102a43]"
                >
                    {{ stats.active }}
                </p>
                <p class="mt-1 text-sm font-semibold text-[#55718a]">
                    Active orders
                </p>
            </div>
            <div
                class="rounded-3xl border border-[#c7e3f3] bg-white p-5 shadow-[0_8px_24px_rgba(16,42,67,0.04)]"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#e8f7fe] text-[#0f6ea8]"
                        ><CheckCircle2 class="h-5 w-5"
                    /></span>
                    <span
                        class="text-[10px] font-bold tracking-[0.14em] text-[#8aa4b8] uppercase"
                        >Paid orders</span
                    >
                </div>
                <p
                    class="mt-5 text-3xl font-extrabold tracking-tight text-[#102a43]"
                >
                    {{ money(stats.spent) }}
                </p>
                <p class="mt-1 text-sm font-semibold text-[#55718a]">
                    Total spent
                </p>
            </div>
        </div>

        <div
            class="mt-12 flex items-end justify-between gap-4 border-b border-[#d7ebf8] pb-5"
        >
            <div>
                <p
                    class="mb-2 text-[10px] font-bold tracking-[0.18em] text-[#0f6ea8] uppercase"
                >
                    Order history
                </p>
                <h2
                    class="text-3xl font-extrabold tracking-[-0.04em] text-[#102a43]"
                >
                    Your orders.
                </h2>
            </div>
            <span
                class="hidden items-center gap-2 text-xs font-semibold text-[#55718a] sm:flex"
                ><Package class="h-4 w-4 text-[#27a9e8]" />
                {{ orders.length }} recent
                {{ orders.length === 1 ? 'order' : 'orders' }}</span
            >
        </div>

        <div v-if="orders.length" class="mt-6 grid gap-4">
            <article
                v-for="order in orders"
                :key="order.id"
                class="rounded-3xl border border-[#c7e3f3] bg-white p-5 shadow-[0_8px_24px_rgba(16,42,67,0.04)] transition hover:border-[#72c9ef] sm:p-6"
            >
                <div
                    class="flex flex-col justify-between gap-5 sm:flex-row sm:items-start"
                >
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <Link
                                :href="`/account/orders/${order.id}`"
                                class="text-lg font-extrabold text-[#102a43] transition hover:text-[#0f6ea8]"
                                >{{ order.order_number }}</Link
                            >
                            <span
                                class="rounded-full px-2.5 py-1 text-[10px] font-bold capitalize ring-1 ring-inset"
                                :class="statusClasses(order.status)"
                                >{{ order.status.replace('_', ' ') }}</span
                            >
                        </div>
                        <p
                            class="mt-2 flex items-center gap-1.5 text-xs font-medium text-[#55718a]"
                        >
                            <CalendarDays class="h-3.5 w-3.5 text-[#27a9e8]" />
                            {{ formatDate(order.created_at) }}
                        </p>
                    </div>
                    <div class="text-left sm:text-right">
                        <p class="text-xl font-extrabold text-[#102a43]">
                            {{ money(order.total) }}
                        </p>
                        <p
                            class="mt-1 text-[11px] font-semibold text-[#55718a] capitalize"
                        >
                            Payment {{ order.payment_status.replace('_', ' ') }}
                        </p>
                    </div>
                </div>
                <div
                    class="mt-5 flex flex-col justify-between gap-4 border-t border-[#d7ebf8] pt-4 sm:flex-row sm:items-center"
                >
                    <p class="text-xs text-[#55718a]">
                        <span
                            v-for="(item, index) in order.items.slice(0, 3)"
                            :key="item.id"
                            >{{ index ? ' · ' : '' }}{{ item.name }} ×
                            {{ item.quantity }}</span
                        ><span v-if="order.items.length > 3">
                            · +{{ order.items.length - 3 }} more</span
                        >
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <a
                            v-if="order.confirmation_url"
                            :href="order.confirmation_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-[#55718a] transition hover:text-[#0f6ea8]"
                            >Confirmation <ArrowRight class="h-3.5 w-3.5"
                        /></a>
                        <Link
                            :href="`/account/orders/${order.id}`"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-[#0f6ea8]"
                            >View details <ArrowRight class="h-3.5 w-3.5"
                        /></Link>
                    </div>
                </div>
            </article>
        </div>

        <div
            v-else
            class="mt-6 rounded-3xl border border-dashed border-[#9edaf3] bg-white px-6 py-20 text-center"
        >
            <div
                class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#e8f7fe] text-[#27a9e8]"
            >
                <ShoppingBag class="h-6 w-6" />
            </div>
            <h2
                class="mt-5 text-2xl font-extrabold tracking-tight text-[#102a43]"
            >
                Your order history starts here.
            </h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-[#55718a]">
                Find something useful for your next idea, then come back to see
                the details and progress in one place.
            </p>
            <Link
                href="/shop"
                class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#0f6ea8] px-5 py-3 text-sm font-bold text-white"
                >Explore the shop <ArrowRight class="h-4 w-4"
            /></Link>
        </div>
    </section>
</template>
