<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, Search, SlidersHorizontal, X } from '@lucide/vue';
import { ref } from 'vue';
import type { StorefrontProduct } from '@/types';

const props = defineProps<{
    products: StorefrontProduct[];
    categories: string[];
    filters: { category?: string; type?: string; search?: string };
}>();
const search = ref(props.filters.search ?? '');
const category = ref(props.filters.category ?? 'all');
const type = ref(props.filters.type ?? 'all');
const filter = () =>
    router.get(
        '/shop',
        {
            search: search.value || undefined,
            category: category.value !== 'all' ? category.value : undefined,
            type: type.value !== 'all' ? type.value : undefined,
        },
        { preserveState: true, replace: true },
    );
const clear = () => {
    search.value = '';
    category.value = 'all';
    type.value = 'all';
    filter();
};
const money = (value: string | number) =>
    `$${Number(value).toLocaleString('en-US', { minimumFractionDigits: 0 })}`;
</script>

<template>
    <Head title="Shop" />
    <section class="border-b border-[#d7ebf8] bg-white">
        <div class="mx-auto max-w-7xl px-5 py-14 sm:px-8 sm:py-20">
            <div
                class="flex flex-col justify-between gap-7 md:flex-row md:items-end"
            >
                <div>
                    <p
                        class="mb-3 text-[10px] font-bold tracking-[0.18em] text-[#0f6ea8] uppercase"
                    >
                        The ELYX collection
                    </p>
                    <h1
                        class="max-w-3xl text-5xl leading-[0.95] font-extrabold tracking-[-0.06em] text-[#102a43] sm:text-7xl"
                    >
                        Tools for your<br /><span class="text-[#27a9e8]"
                            >next good idea.</span
                        >
                    </h1>
                </div>
                <p class="max-w-sm text-sm leading-6 text-[#55718a]">
                    Ready-to-use digital products, studio goods, and services
                    made with a little more intention.
                </p>
            </div>
        </div>
    </section>
    <section class="border-b border-[#d7ebf8] bg-[#f4fbff] px-5 py-5 sm:px-8">
        <div
            class="mx-auto flex max-w-7xl flex-col gap-3 lg:flex-row lg:items-center"
        >
            <div class="relative flex-1">
                <Search
                    class="absolute top-1/2 left-4 h-4 w-4 -translate-y-1/2 text-[#55718a]"
                /><input
                    v-model="search"
                    @keyup.enter="filter"
                    type="search"
                    placeholder="Search products, services, and resources"
                    class="elyx-input !rounded-xl !py-3 !pl-11"
                />
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="option in ['all', ...categories]"
                    :key="option"
                    type="button"
                    class="rounded-xl border px-4 py-2.5 text-xs font-bold capitalize transition"
                    :class="
                        category === option
                            ? 'border-[#0f6ea8] bg-[#0f6ea8] text-white'
                            : 'border-[#c7e3f3] bg-white text-[#55718a] hover:border-[#27a9e8] hover:text-[#0f6ea8]'
                    "
                    @click="
                        category = option;
                        filter();
                    "
                >
                    {{ option }}
                </button>
                <button
                    v-for="option in ['all', 'product', 'digital', 'service']"
                    :key="option"
                    type="button"
                    class="hidden rounded-xl border px-4 py-2.5 text-xs font-bold capitalize transition xl:block"
                    :class="
                        type === option
                            ? 'border-[#27a9e8] bg-[#e8f7fe] text-[#0f6ea8]'
                            : 'border-[#c7e3f3] bg-white text-[#55718a] hover:border-[#27a9e8]'
                    "
                    @click="
                        type = option;
                        filter();
                    "
                >
                    {{ option }}
                </button>
                <button
                    v-if="search || category !== 'all' || type !== 'all'"
                    type="button"
                    class="flex items-center gap-1 rounded-xl px-3 text-xs font-bold text-[#0f6ea8]"
                    @click="clear"
                >
                    <X class="h-3.5 w-3.5" /> Clear
                </button>
            </div>
        </div>
    </section>
    <section class="mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-16">
        <div class="mb-7 flex items-center justify-between">
            <p class="text-sm font-semibold text-[#55718a]">
                {{ products.length }}
                {{ products.length === 1 ? 'result' : 'results' }}
            </p>
            <span
                class="hidden items-center gap-2 text-xs font-semibold text-[#55718a] sm:flex"
                ><SlidersHorizontal class="h-3.5 w-3.5 text-[#27a9e8]" />
                Curated for you</span
            >
        </div>
        <div
            v-if="products.length"
            class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
        >
            <div
                v-for="product in products"
                :key="product.id"
                class="group overflow-hidden rounded-3xl border border-[#c7e3f3] bg-white shadow-[0_8px_24px_rgba(16,42,67,0.04)] transition duration-300 hover:-translate-y-1 hover:border-[#72c9ef] hover:shadow-[0_18px_40px_rgba(39,169,232,0.13)]"
            >
                <Link
                    :href="`/shop/${product.slug}`"
                    class="relative block aspect-[4/4.2] overflow-hidden bg-[#e8f7fe]"
                    ><img
                        v-if="product.image_url"
                        :src="product.image_url"
                        :alt="product.name"
                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-[#102a43]/45 via-transparent to-transparent opacity-60"
                    ></div>
                    <div class="absolute inset-x-4 top-4 flex justify-between">
                        <span
                            class="rounded-full bg-white/95 px-3 py-1.5 text-[10px] font-bold tracking-[0.12em] text-[#0f6ea8] uppercase"
                            >{{ product.type }}</span
                        ><span
                            v-if="product.is_featured"
                            class="rounded-full bg-[#27a9e8] px-3 py-1.5 text-[10px] font-bold text-white"
                            >Featured</span
                        >
                    </div>
                    <span
                        class="absolute right-4 bottom-4 flex h-10 w-10 items-center justify-center rounded-xl bg-white text-[#0f6ea8] opacity-0 shadow-lg transition group-hover:opacity-100"
                        ><ArrowRight class="h-4 w-4" /></span
                ></Link>
                <div class="p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p
                                class="mb-1 text-[10px] font-bold tracking-[0.14em] text-[#0f6ea8] uppercase"
                            >
                                {{ product.category }}
                            </p>
                            <h2 class="font-extrabold text-[#102a43]">
                                {{ product.name }}
                            </h2>
                            <p
                                class="mt-1.5 line-clamp-2 text-xs leading-5 text-[#55718a]"
                            >
                                {{ product.tagline }}
                            </p>
                        </div>
                        <p
                            class="shrink-0 text-sm font-extrabold text-[#102a43]"
                        >
                            {{ money(product.price)
                            }}<span
                                v-if="product.billing_period"
                                class="block text-right text-[10px] font-medium text-[#55718a]"
                                >/ {{ product.billing_period }}</span
                            >
                        </p>
                    </div>
                    <Link
                        :href="`/shop/${product.slug}`"
                        class="mt-5 inline-flex items-center gap-2 text-xs font-bold text-[#0f6ea8]"
                        >View details <ArrowRight class="h-3.5 w-3.5"
                    /></Link>
                </div>
            </div>
        </div>
        <div
            v-else
            class="rounded-3xl border border-dashed border-[#9edaf3] bg-[#f4fbff] px-6 py-24 text-center"
        >
            <p class="text-3xl font-extrabold tracking-tight text-[#102a43]">
                Nothing here yet.
            </p>
            <p class="mt-2 text-sm text-[#55718a]">
                Try a different search or browse the full collection.
            </p>
            <button
                type="button"
                class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#0f6ea8] px-4 py-3 text-sm font-bold text-white"
                @click="clear"
            >
                Reset filters <ArrowRight class="h-4 w-4" />
            </button>
        </div>
    </section>
</template>
