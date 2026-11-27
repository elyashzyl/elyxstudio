<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Check, Mail, PackageCheck, Sparkles } from '@lucide/vue';
import type { OrderConfirmation } from '@/types';

defineProps<{ order: OrderConfirmation }>();
const money = (value: string | number) =>
    `$${Number(value).toLocaleString('en-US', { minimumFractionDigits: 0 })}`;
</script>

<template>
    <Head title="Order received" />
    <section class="mx-auto max-w-3xl px-5 py-16 text-center sm:px-8 sm:py-24">
        <div
            class="mx-auto mb-7 flex h-20 w-20 items-center justify-center rounded-3xl bg-[#27a9e8] text-white shadow-[0_14px_30px_rgba(39,169,232,0.28)]"
        >
            <Check class="h-9 w-9" />
        </div>
        <p
            class="mb-4 text-[10px] font-bold tracking-[0.18em] text-[#0f6ea8] uppercase"
        >
            Order received / {{ order.order_number }}
        </p>
        <h1
            class="text-5xl leading-[0.95] font-extrabold tracking-[-0.06em] text-[#102a43] sm:text-7xl"
        >
            Thanks,<br /><span class="text-[#27a9e8]"
                >{{ order.customer_name.split(' ')[0] }}.</span
            >
        </h1>
        <p class="mx-auto mt-7 max-w-md text-sm leading-7 text-[#55718a]">
            Your request is safely with the studio. We will review the details
            and follow up at
            <strong class="text-[#102a43]">{{ order.customer_email }}</strong>
            with next steps.
        </p>
        <div
            class="mx-auto mt-10 max-w-md rounded-3xl border border-[#c7e3f3] bg-white p-6 text-left shadow-[0_12px_32px_rgba(16,42,67,0.06)] sm:p-8"
        >
            <div
                class="mb-5 flex items-center gap-3 border-b border-[#d7ebf8] pb-5"
            >
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#e8f7fe] text-[#0f6ea8]"
                >
                    <Mail class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-sm font-extrabold text-[#102a43]">
                        A little note is on its way
                    </p>
                    <p class="mt-1 text-xs text-[#55718a]">
                        We usually reply within one business day.
                    </p>
                </div>
            </div>
            <div class="space-y-4">
                <div
                    v-for="item in order.items"
                    :key="item.id"
                    class="flex justify-between gap-4 text-sm"
                >
                    <span class="text-[#55718a]"
                        >{{ item.name }}
                        <span class="text-xs text-[#8aa4b8]"
                            >× {{ item.quantity }}</span
                        ></span
                    ><span class="font-bold text-[#102a43]">{{
                        money(item.line_total)
                    }}</span>
                </div>
                <div
                    class="flex justify-between border-t border-[#d7ebf8] pt-4 font-extrabold text-[#102a43]"
                >
                    <span>Total</span><span>{{ money(order.total) }}</span>
                </div>
            </div>
        </div>
        <div class="mt-9 flex flex-col justify-center gap-3 sm:flex-row">
            <Link
                href="/shop"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0f6ea8] px-6 py-3.5 text-sm font-bold text-white shadow-[0_10px_22px_rgba(15,110,168,0.2)] transition hover:bg-[#0b527e]"
                >Keep exploring <ArrowRight class="h-4 w-4" /></Link
            ><Link
                href="/"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#c7e3f3] bg-white px-6 py-3.5 text-sm font-bold text-[#102a43] transition hover:border-[#27a9e8] hover:bg-[#f4fbff]"
                >Back home <Sparkles class="h-4 w-4 text-[#27a9e8]"
            /></Link>
        </div>
        <div
            class="mt-12 flex items-center justify-center gap-2 text-xs font-semibold text-[#55718a]"
        >
            <PackageCheck class="h-4 w-4 text-[#27a9e8]" /> Your order details
            are safely recorded.
        </div>
    </section>
</template>
