<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    FileText,
    Mail,
    PackageCheck,
    Phone,
    ShieldCheck,
} from '@lucide/vue';
import CommerceLayout from '@/layouts/CommerceLayout.vue';

interface OrderItem {
    id: number;
    name: string;
    type?: string | null;
    unit_price: string | number;
    quantity: number;
    line_total: string | number;
}

interface OrderDetails {
    order_number: string;
    customer_name: string;
    customer_email: string;
    customer_phone: string | null;
    customer_note: string | null;
    status: string;
    payment_status: string;
    subtotal: string | number;
    tax: string | number;
    total: string | number;
    created_at: string | null;
    confirmation_url: string | null;
    items: OrderItem[];
}

defineProps<{ order: OrderDetails }>();
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
              month: 'long',
              day: 'numeric',
              year: 'numeric',
          });
};

const statusClasses = (status: string) => {
    const normalized = status.toLowerCase();

    if (['completed', 'paid'].includes(normalized)) {
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
    <Head :title="`Order ${order.order_number}`" />

    <section class="mx-auto max-w-5xl px-5 py-10 sm:px-8 sm:py-16">
        <Link
            href="/account"
            class="mb-9 inline-flex items-center gap-2 text-xs font-bold text-[#55718a] transition hover:text-[#0f6ea8]"
            ><ArrowLeft class="h-3.5 w-3.5" /> Back to account</Link
        >

        <div
            class="flex flex-col justify-between gap-6 border-b border-[#d7ebf8] pb-8 sm:flex-row sm:items-end"
        >
            <div>
                <p
                    class="mb-3 text-[10px] font-bold tracking-[0.18em] text-[#0f6ea8] uppercase"
                >
                    Order details
                </p>
                <h1
                    class="text-4xl leading-none font-extrabold tracking-[-0.05em] text-[#102a43] sm:text-6xl"
                >
                    {{ order.order_number }}
                </h1>
                <p
                    class="mt-4 flex items-center gap-2 text-sm font-medium text-[#55718a]"
                >
                    <CalendarDays class="h-4 w-4 text-[#27a9e8]" /> Placed
                    {{ formatDate(order.created_at) }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2 sm:justify-end">
                <span
                    class="rounded-full px-3 py-1.5 text-[11px] font-bold capitalize ring-1 ring-inset"
                    :class="statusClasses(order.status)"
                    >{{ order.status.replace('_', ' ') }}</span
                >
                <span
                    class="rounded-full px-3 py-1.5 text-[11px] font-bold capitalize ring-1 ring-inset"
                    :class="statusClasses(order.payment_status)"
                    >Payment {{ order.payment_status.replace('_', ' ') }}</span
                >
            </div>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_320px]">
            <div class="space-y-6">
                <div
                    class="rounded-3xl border border-[#c7e3f3] bg-white p-5 shadow-[0_8px_24px_rgba(16,42,67,0.04)] sm:p-7"
                >
                    <div
                        class="mb-6 flex items-center gap-3 border-b border-[#d7ebf8] pb-5"
                    >
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#e8f7fe] text-[#0f6ea8]"
                            ><PackageCheck class="h-5 w-5"
                        /></span>
                        <div>
                            <h2 class="text-lg font-extrabold text-[#102a43]">
                                What’s in your order
                            </h2>
                            <p class="mt-1 text-xs text-[#55718a]">
                                {{ order.items.length }}
                                {{
                                    order.items.length === 1 ? 'item' : 'items'
                                }}
                                recorded
                            </p>
                        </div>
                    </div>
                    <div class="divide-y divide-[#d7ebf8]">
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex items-center justify-between gap-4 py-4 first:pt-0 last:pb-0"
                        >
                            <div class="flex min-w-0 items-start gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#f4fbff] text-[#27a9e8]"
                                >
                                    <FileText class="h-4 w-4" />
                                </div>
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-bold text-[#102a43]"
                                    >
                                        {{ item.name }}
                                    </p>
                                    <p class="mt-1 text-xs text-[#55718a]">
                                        {{ item.quantity }} ×
                                        {{ money(item.unit_price)
                                        }}<span v-if="item.type">
                                            · {{ item.type }}</span
                                        >
                                    </p>
                                </div>
                            </div>
                            <p
                                class="shrink-0 text-sm font-extrabold text-[#102a43]"
                            >
                                {{ money(item.line_total) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-3xl border border-[#c7e3f3] bg-white p-5 shadow-[0_8px_24px_rgba(16,42,67,0.04)] sm:p-7"
                >
                    <div class="mb-5 flex items-center gap-3">
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#e8f7fe] text-[#0f6ea8]"
                            ><Mail class="h-5 w-5"
                        /></span>
                        <div>
                            <h2 class="text-lg font-extrabold text-[#102a43]">
                                Customer details
                            </h2>
                            <p class="mt-1 text-xs text-[#55718a]">
                                The details attached to this order request.
                            </p>
                        </div>
                    </div>
                    <div
                        class="grid gap-4 border-t border-[#d7ebf8] pt-5 sm:grid-cols-2"
                    >
                        <div>
                            <p
                                class="text-[10px] font-bold tracking-[0.14em] text-[#8aa4b8] uppercase"
                            >
                                Name
                            </p>
                            <p
                                class="mt-1 text-sm font-semibold text-[#102a43]"
                            >
                                {{ order.customer_name }}
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-[10px] font-bold tracking-[0.14em] text-[#8aa4b8] uppercase"
                            >
                                Email
                            </p>
                            <a
                                :href="`mailto:${order.customer_email}`"
                                class="mt-1 block truncate text-sm font-semibold text-[#0f6ea8] hover:underline"
                                >{{ order.customer_email }}</a
                            >
                        </div>
                        <div v-if="order.customer_phone">
                            <p
                                class="text-[10px] font-bold tracking-[0.14em] text-[#8aa4b8] uppercase"
                            >
                                Phone
                            </p>
                            <a
                                :href="`tel:${order.customer_phone}`"
                                class="mt-1 inline-flex items-center gap-1.5 text-sm font-semibold text-[#102a43] hover:text-[#0f6ea8]"
                                ><Phone class="h-3.5 w-3.5 text-[#27a9e8]" />{{
                                    order.customer_phone
                                }}</a
                            >
                        </div>
                        <div v-if="order.customer_note" class="sm:col-span-2">
                            <p
                                class="text-[10px] font-bold tracking-[0.14em] text-[#8aa4b8] uppercase"
                            >
                                Note
                            </p>
                            <p class="mt-1 text-sm leading-6 text-[#55718a]">
                                {{ order.customer_note }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <aside
                class="h-fit rounded-3xl border border-[#b9e2f6] bg-gradient-to-br from-[#e8f7fe] to-white p-6 shadow-[0_12px_30px_rgba(39,169,232,0.1)] sm:p-7"
            >
                <h2 class="text-lg font-extrabold text-[#102a43]">
                    Order summary
                </h2>
                <div
                    class="mt-6 space-y-3 border-b border-[#c7e3f3] pb-6 text-sm"
                >
                    <div class="flex justify-between">
                        <span class="text-[#55718a]">Subtotal</span
                        ><span class="font-semibold text-[#102a43]">{{
                            money(order.subtotal)
                        }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#55718a]">Tax</span
                        ><span class="font-semibold text-[#102a43]">{{
                            money(order.tax)
                        }}</span>
                    </div>
                </div>
                <div
                    class="flex justify-between py-6 text-2xl font-extrabold text-[#102a43]"
                >
                    <span>Total</span><span>{{ money(order.total) }}</span>
                </div>
                <a
                    v-if="order.confirmation_url"
                    :href="order.confirmation_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#0f6ea8] px-5 py-3.5 text-sm font-bold text-white shadow-[0_10px_22px_rgba(15,110,168,0.2)] transition hover:bg-[#0b527e]"
                    ><CheckCircle2 class="h-4 w-4" /> Open confirmation</a
                >
                <div
                    class="mt-5 flex items-start gap-2 border-t border-[#c7e3f3] pt-5 text-[11px] leading-5 text-[#55718a]"
                >
                    <ShieldCheck
                        class="mt-0.5 h-4 w-4 shrink-0 text-[#27a9e8]"
                    />
                    Keep this order number handy when you contact ELYX about
                    your request.
                </div>
            </aside>
        </div>
    </section>
</template>
