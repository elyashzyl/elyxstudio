<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Check,
    LockKeyhole,
    ShieldCheck,
} from '@lucide/vue';
import type { CartItem } from '@/types';

const props = defineProps<{
    items: CartItem[];
    count: number;
    subtotal: number;
    tax: number;
    total: number;
    checkoutKey: string;
}>();
const form = useForm({
    checkout_key: props.checkoutKey,
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    customer_note: '',
});
const money = (value: string | number) =>
    `$${Number(value).toLocaleString('en-US', { minimumFractionDigits: 0 })}`;
const submit = () => form.post('/checkout');
</script>

<template>
    <Head title="Checkout" />
    <section class="mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-20">
        <Link
            href="/cart"
            class="mb-9 inline-flex items-center gap-2 text-xs font-bold text-[#55718a] transition hover:text-[#0f6ea8]"
            ><ArrowLeft class="h-3.5 w-3.5" /> Back to cart</Link
        >
        <div
            class="mb-10 flex flex-col justify-between gap-5 border-b border-[#d7ebf8] pb-8 lg:flex-row lg:items-end"
        >
            <div>
                <p
                    class="mb-3 text-[10px] font-bold tracking-[0.18em] text-[#0f6ea8] uppercase"
                >
                    Secure order request
                </p>
                <h1
                    class="text-5xl leading-[0.95] font-extrabold tracking-[-0.06em] text-[#102a43] sm:text-6xl"
                >
                    Let's make it<br /><span class="text-[#27a9e8]"
                        >official.</span
                    >
                </h1>
            </div>
            <div
                class="flex items-center gap-2 text-xs font-bold text-[#55718a]"
            >
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-[#0f6ea8] text-white"
                    >1</span
                >
                Details <span class="h-px w-8 bg-[#c7e3f3]"></span
                ><span
                    class="flex h-8 w-8 items-center justify-center rounded-full border border-[#c7e3f3] text-[#55718a]"
                    >2</span
                >
                Review
            </div>
        </div>
        <div class="grid gap-10 lg:grid-cols-[1fr_380px] lg:gap-16">
            <form class="max-w-2xl" @submit.prevent="submit">
                <div
                    class="rounded-3xl border border-[#c7e3f3] bg-white p-6 shadow-[0_8px_24px_rgba(16,42,67,0.04)] sm:p-8"
                >
                    <div class="mb-7 flex items-start gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#e8f7fe] text-[#0f6ea8]"
                        >
                            <LockKeyhole class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-extrabold text-[#102a43]">
                                Your details
                            </h2>
                            <p class="mt-1 text-xs text-[#55718a]">
                                We use this to confirm your order and follow up.
                            </p>
                        </div>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="block sm:col-span-2"
                            ><span
                                class="mb-2 block text-xs font-bold text-[#102a43]"
                                >Name <b class="text-[#0f6ea8]">*</b></span
                            ><input
                                v-model="form.customer_name"
                                type="text"
                                autocomplete="name"
                                class="elyx-input"
                                placeholder="Your full name"
                            /><span
                                v-if="form.errors.customer_name"
                                class="mt-1 block text-xs text-red-600"
                                >{{ form.errors.customer_name }}</span
                            ></label
                        ><label class="block sm:col-span-2"
                            ><span
                                class="mb-2 block text-xs font-bold text-[#102a43]"
                                >Email <b class="text-[#0f6ea8]">*</b></span
                            ><input
                                v-model="form.customer_email"
                                type="email"
                                autocomplete="email"
                                class="elyx-input"
                                placeholder="you@example.com"
                            /><span
                                v-if="form.errors.customer_email"
                                class="mt-1 block text-xs text-red-600"
                                >{{ form.errors.customer_email }}</span
                            ></label
                        ><label class="block"
                            ><span
                                class="mb-2 block text-xs font-bold text-[#102a43]"
                                >Phone
                                <span class="font-normal text-[#8aa4b8]"
                                    >(optional)</span
                                ></span
                            ><input
                                v-model="form.customer_phone"
                                type="tel"
                                autocomplete="tel"
                                class="elyx-input"
                                placeholder="+1 555 000 0000" /></label
                        ><label class="block sm:col-span-2"
                            ><span
                                class="mb-2 block text-xs font-bold text-[#102a43]"
                                >Anything we should know?
                                <span class="font-normal text-[#8aa4b8]"
                                    >(optional)</span
                                ></span
                            ><textarea
                                v-model="form.customer_note"
                                rows="4"
                                class="elyx-input resize-none"
                                placeholder="Tell us about your project, delivery needs, or questions..."
                            ></textarea>
                        </label>
                    </div>
                </div>
                <div
                    class="mt-7 rounded-2xl border border-[#c7e3f3] bg-[#f4fbff] p-4 text-xs leading-5 text-[#55718a]"
                >
                    <div class="flex items-start gap-3">
                        <Check
                            class="mt-0.5 h-4 w-4 shrink-0 text-[#27a9e8]"
                        />Your order request is reviewed by the studio before
                        payment or project kickoff is arranged.
                    </div>
                </div>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="group mt-6 inline-flex items-center gap-3 rounded-xl bg-[#0f6ea8] px-7 py-4 text-sm font-bold text-white shadow-[0_10px_22px_rgba(15,110,168,0.2)] transition hover:bg-[#0b527e] disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{
                        form.processing
                            ? 'Sending request...'
                            : 'Place order request'
                    }}<ArrowRight
                        class="h-4 w-4 transition-transform group-hover:translate-x-1"
                    />
                </button>
            </form>
            <aside
                class="h-fit rounded-3xl border border-[#b9e2f6] bg-gradient-to-br from-[#e8f7fe] to-white p-6 shadow-[0_12px_30px_rgba(39,169,232,0.1)] sm:p-8"
            >
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-lg font-extrabold text-[#102a43]">
                        Your order
                    </h2>
                    <span
                        class="rounded-full bg-white px-3 py-1 text-[10px] font-bold text-[#0f6ea8]"
                        >{{ props.count }} items</span
                    >
                </div>
                <div class="space-y-5 border-b border-[#c7e3f3] pb-6">
                    <div
                        v-for="item in props.items"
                        :key="item.product.id"
                        class="flex gap-3"
                    >
                        <div
                            class="h-12 w-12 shrink-0 overflow-hidden rounded-xl bg-white"
                        >
                            <img
                                v-if="item.product.image_url"
                                :src="item.product.image_url"
                                :alt="item.product.name"
                                class="h-full w-full object-cover"
                            />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-sm font-bold text-[#102a43]"
                            >
                                {{ item.product.name }}
                            </p>
                            <p class="mt-1 text-xs text-[#55718a]">
                                {{ item.quantity }} ×
                                {{ money(item.product.price) }}
                            </p>
                        </div>
                        <span class="text-sm font-extrabold text-[#102a43]">{{
                            money(item.line_total)
                        }}</span>
                    </div>
                </div>
                <div class="space-y-3 py-6 text-sm">
                    <div class="flex justify-between">
                        <span class="text-[#55718a]">Subtotal</span
                        ><span class="font-semibold text-[#102a43]">{{
                            money(props.subtotal)
                        }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#55718a]">Tax</span
                        ><span class="font-semibold text-[#102a43]">{{
                            props.tax ? money(props.tax) : 'Calculated later'
                        }}</span>
                    </div>
                    <div
                        class="flex justify-between pt-3 text-xl font-extrabold text-[#102a43]"
                    >
                        <span>Total</span><span>{{ money(props.total) }}</span>
                    </div>
                </div>
                <div
                    class="flex items-start gap-2 border-t border-[#c7e3f3] pt-5 text-[11px] leading-5 text-[#55718a]"
                >
                    <ShieldCheck
                        class="mt-0.5 h-4 w-4 shrink-0 text-[#27a9e8]"
                    />
                    Your information is sent securely. No card details are
                    stored in this order request.
                </div>
            </aside>
        </div>
    </section>
</template>
