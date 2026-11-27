<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowRight,
    CheckCircle2,
    ChevronDown,
    Edit3,
    Image,
    Package,
    Plus,
    Send,
    Store,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import CommerceLayout from '@/layouts/CommerceLayout.vue';

type ProductType = 'product' | 'digital' | 'service';

interface SellerProduct {
    id: number;
    name: string;
    slug: string;
    type: ProductType;
    category: string;
    tagline: string | null;
    description: string;
    features: string[] | string | null;
    image_url: string | null;
    price: string | number;
    compare_at_price: string | number | null;
    billing_period: string | null;
    approval_status?: string;
    rejection_reason?: string | null;
}

interface SellerOrderItem {
    name: string;
    quantity: number;
    line_total: string | number;
}

interface SellerOrder {
    id: number;
    order_number: string;
    status: string;
    payment_status: string;
    customer_name: string;
    created_at: string | null;
    items: SellerOrderItem[];
}

interface SellerStats {
    products: number;
    pending: number;
    approved: number;
    sales: string | number;
}

interface ProductForm {
    name: string;
    slug: string;
    type: ProductType;
    category: string;
    tagline: string;
    description: string;
    features: string;
    image_url: string;
    price: string;
    compare_at_price: string;
    billing_period: string;
}

defineProps<{
    products: SellerProduct[];
    orders: SellerOrder[];
    stats: SellerStats;
}>();

defineOptions({ layout: CommerceLayout });

const editingProduct = ref<SellerProduct | null>(null);
const showForm = ref(false);

const emptyForm = (): ProductForm => ({
    name: '',
    slug: '',
    type: 'digital',
    category: '',
    tagline: '',
    description: '',
    features: '',
    image_url: '',
    price: '',
    compare_at_price: '',
    billing_period: '',
});

const form = useForm<ProductForm>(emptyForm());
const formHeading = computed(() =>
    editingProduct.value ? 'Refine your submission' : 'Submit a product',
);

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

    if (['approved', 'completed', 'paid'].includes(normalized)) {
        return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
    }

    if (['rejected', 'cancelled', 'failed'].includes(normalized)) {
        return 'bg-red-50 text-red-700 ring-red-200';
    }

    if (['confirmed', 'in_progress', 'processing'].includes(normalized)) {
        return 'bg-[#e8f7fe] text-[#0f6ea8] ring-[#b9e2f6]';
    }

    return 'bg-amber-50 text-amber-700 ring-amber-200';
};

const approvalLabel = (status?: string) => {
    if (status === 'submitted') {
        return 'Under review';
    }

    if (status === 'approved') {
        return 'Approved';
    }

    if (status === 'rejected') {
        return 'Needs changes';
    }

    return 'Draft';
};

const startNewProduct = () => {
    editingProduct.value = null;
    form.reset();
    form.clearErrors();
    showForm.value = true;
};

const editProduct = (product: SellerProduct) => {
    editingProduct.value = product;
    form.name = product.name;
    form.slug = product.slug;
    form.type = product.type;
    form.category = product.category;
    form.tagline = product.tagline ?? '';
    form.description = product.description;
    form.features = Array.isArray(product.features)
        ? product.features.join('\n')
        : (product.features ?? '');
    form.image_url = product.image_url ?? '';
    form.price = String(product.price ?? '');
    form.compare_at_price =
        product.compare_at_price === null
            ? ''
            : String(product.compare_at_price ?? '');
    form.billing_period = product.billing_period ?? '';
    form.clearErrors();
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    editingProduct.value = null;
    form.reset();
    form.clearErrors();
};

const submitProduct = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => closeForm(),
    };

    if (editingProduct.value) {
        form.patch(`/seller/products/${editingProduct.value.slug}`, options);

        return;
    }

    form.post('/seller/products', options);
};

const submitForReview = (product: SellerProduct) => {
    router.patch(
        `/seller/products/${product.slug}/submit`,
        {},
        { preserveScroll: true },
    );
};

const canEdit = (product: SellerProduct) =>
    ['draft', 'rejected', undefined].includes(product.approval_status);

const canSubmit = (product: SellerProduct) =>
    ['draft', 'rejected', undefined].includes(product.approval_status);
</script>

<template>
    <Head title="Seller studio" />

    <section class="border-b border-[#d7ebf8] bg-white">
        <div class="mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-16">
            <div
                class="flex flex-col justify-between gap-7 sm:flex-row sm:items-end"
            >
                <div>
                    <p
                        class="mb-3 text-[10px] font-bold tracking-[0.18em] text-[#0f6ea8] uppercase"
                    >
                        Seller studio
                    </p>
                    <h1
                        class="text-5xl leading-[0.95] font-extrabold tracking-[-0.06em] text-[#102a43] sm:text-6xl"
                    >
                        Make something<br /><span class="text-[#27a9e8]"
                            >worth finding.</span
                        >
                    </h1>
                    <p class="mt-5 max-w-xl text-sm leading-6 text-[#55718a]">
                        Submit useful digital products to the ELYX catalog.
                        Every submission is reviewed before it becomes available
                        to customers.
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex w-fit items-center gap-2 rounded-xl bg-[#0f6ea8] px-5 py-3.5 text-sm font-bold text-white shadow-[0_10px_22px_rgba(15,110,168,0.2)] transition hover:bg-[#0b527e]"
                    @click="startNewProduct"
                >
                    <Plus class="h-4 w-4" /> New product
                </button>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-5 py-10 sm:px-8 sm:py-14">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div
                class="rounded-3xl border border-[#c7e3f3] bg-white p-5 shadow-[0_8px_24px_rgba(16,42,67,0.04)]"
            >
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#e8f7fe] text-[#0f6ea8]"
                    ><Package class="h-5 w-5"
                /></span>
                <p
                    class="mt-5 text-3xl font-extrabold tracking-tight text-[#102a43]"
                >
                    {{ stats.products }}
                </p>
                <p class="mt-1 text-sm font-semibold text-[#55718a]">
                    Products submitted
                </p>
            </div>
            <div
                class="rounded-3xl border border-[#c7e3f3] bg-white p-5 shadow-[0_8px_24px_rgba(16,42,67,0.04)]"
            >
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600"
                    ><Send class="h-5 w-5"
                /></span>
                <p
                    class="mt-5 text-3xl font-extrabold tracking-tight text-[#102a43]"
                >
                    {{ stats.pending }}
                </p>
                <p class="mt-1 text-sm font-semibold text-[#55718a]">
                    Awaiting review
                </p>
            </div>
            <div
                class="rounded-3xl border border-[#c7e3f3] bg-white p-5 shadow-[0_8px_24px_rgba(16,42,67,0.04)]"
            >
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                    ><CheckCircle2 class="h-5 w-5"
                /></span>
                <p
                    class="mt-5 text-3xl font-extrabold tracking-tight text-[#102a43]"
                >
                    {{ stats.approved }}
                </p>
                <p class="mt-1 text-sm font-semibold text-[#55718a]">
                    Approved products
                </p>
            </div>
            <div
                class="rounded-3xl border border-[#c7e3f3] bg-white p-5 shadow-[0_8px_24px_rgba(16,42,67,0.04)]"
            >
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#e8f7fe] text-[#0f6ea8]"
                    ><Store class="h-5 w-5"
                /></span>
                <p
                    class="mt-5 text-3xl font-extrabold tracking-tight text-[#102a43]"
                >
                    {{ money(stats.sales) }}
                </p>
                <p class="mt-1 text-sm font-semibold text-[#55718a]">
                    Paid sales
                </p>
            </div>
        </div>

        <div
            v-if="showForm"
            class="mt-10 rounded-3xl border border-[#b9e2f6] bg-gradient-to-br from-[#e8f7fe] to-white p-5 shadow-[0_12px_30px_rgba(39,169,232,0.1)] sm:p-8"
        >
            <div
                class="flex flex-col justify-between gap-4 border-b border-[#c7e3f3] pb-6 sm:flex-row sm:items-start"
            >
                <div>
                    <p
                        class="mb-2 text-[10px] font-bold tracking-[0.18em] text-[#0f6ea8] uppercase"
                    >
                        Catalog submission
                    </p>
                    <h2
                        class="text-2xl font-extrabold tracking-tight text-[#102a43]"
                    >
                        {{ formHeading }}
                    </h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-[#55718a]">
                        Use the fields below to describe the product. ELYX
                        manages approval, availability, and stock after review.
                    </p>
                </div>
                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border border-[#c7e3f3] bg-white text-[#55718a] transition hover:text-[#0f6ea8]"
                    aria-label="Close product form"
                    @click="closeForm"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
            <form
                class="mt-7 grid gap-5 md:grid-cols-2"
                @submit.prevent="submitProduct"
            >
                <label class="block md:col-span-2"
                    ><span class="mb-2 block text-xs font-bold text-[#102a43]"
                        >Product name <b class="text-[#0f6ea8]">*</b></span
                    ><input
                        v-model="form.name"
                        type="text"
                        class="elyx-input"
                        placeholder="A clear, memorable name"
                    /><span
                        v-if="form.errors.name"
                        class="mt-1 block text-xs text-red-600"
                        >{{ form.errors.name }}</span
                    ></label
                >
                <label class="block"
                    ><span class="mb-2 block text-xs font-bold text-[#102a43]"
                        >Slug <b class="text-[#0f6ea8]">*</b></span
                    ><input
                        v-model="form.slug"
                        type="text"
                        class="elyx-input"
                        placeholder="clear-product-slug"
                    /><span class="mt-1 block text-[11px] text-[#55718a]"
                        >Use lowercase letters, numbers, and dashes.</span
                    ><span
                        v-if="form.errors.slug"
                        class="mt-1 block text-xs text-red-600"
                        >{{ form.errors.slug }}</span
                    ></label
                >
                <label class="block"
                    ><span class="mb-2 block text-xs font-bold text-[#102a43]"
                        >Product type <b class="text-[#0f6ea8]">*</b></span
                    ><span class="relative block"
                        ><select
                            v-model="form.type"
                            class="elyx-input appearance-none"
                        >
                            <option value="digital">Digital</option>
                            <option value="product">Product</option>
                            <option value="service">Service</option></select
                        ><ChevronDown
                            class="pointer-events-none absolute top-1/2 right-4 h-4 w-4 -translate-y-1/2 text-[#55718a]" /></span
                    ><span
                        v-if="form.errors.type"
                        class="mt-1 block text-xs text-red-600"
                        >{{ form.errors.type }}</span
                    ></label
                >
                <label class="block"
                    ><span class="mb-2 block text-xs font-bold text-[#102a43]"
                        >Category <b class="text-[#0f6ea8]">*</b></span
                    ><input
                        v-model="form.category"
                        type="text"
                        class="elyx-input"
                        placeholder="Templates, strategy, tools..."
                    /><span
                        v-if="form.errors.category"
                        class="mt-1 block text-xs text-red-600"
                        >{{ form.errors.category }}</span
                    ></label
                >
                <label class="block"
                    ><span class="mb-2 block text-xs font-bold text-[#102a43]"
                        >Tagline</span
                    ><input
                        v-model="form.tagline"
                        type="text"
                        class="elyx-input"
                        placeholder="One line that makes it click"
                    /><span
                        v-if="form.errors.tagline"
                        class="mt-1 block text-xs text-red-600"
                        >{{ form.errors.tagline }}</span
                    ></label
                >
                <label class="block md:col-span-2"
                    ><span class="mb-2 block text-xs font-bold text-[#102a43]"
                        >Description <b class="text-[#0f6ea8]">*</b></span
                    ><textarea
                        v-model="form.description"
                        rows="5"
                        class="elyx-input resize-none"
                        placeholder="Explain what it is, who it helps, and what customers can expect."
                    ></textarea
                    ><span
                        v-if="form.errors.description"
                        class="mt-1 block text-xs text-red-600"
                        >{{ form.errors.description }}</span
                    ></label
                >
                <label class="block"
                    ><span class="mb-2 block text-xs font-bold text-[#102a43]"
                        >Features</span
                    ><textarea
                        v-model="form.features"
                        rows="5"
                        class="elyx-input resize-none"
                        placeholder="One feature per line"
                    ></textarea
                    ><span class="mt-1 block text-[11px] text-[#55718a]"
                        >Add up to 30 customer-facing features, one per
                        line.</span
                    ><span
                        v-if="form.errors.features"
                        class="mt-1 block text-xs text-red-600"
                        >{{ form.errors.features }}</span
                    ></label
                >
                <label class="block"
                    ><span class="mb-2 block text-xs font-bold text-[#102a43]"
                        >Image URL</span
                    ><input
                        v-model="form.image_url"
                        type="url"
                        class="elyx-input"
                        placeholder="https://..."
                    /><span
                        v-if="form.errors.image_url"
                        class="mt-1 block text-xs text-red-600"
                        >{{ form.errors.image_url }}</span
                    ></label
                >
                <label class="block"
                    ><span class="mb-2 block text-xs font-bold text-[#102a43]"
                        >Price <b class="text-[#0f6ea8]">*</b></span
                    ><input
                        v-model="form.price"
                        type="number"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        class="elyx-input"
                        placeholder="0.00"
                    /><span
                        v-if="form.errors.price"
                        class="mt-1 block text-xs text-red-600"
                        >{{ form.errors.price }}</span
                    ></label
                >
                <label class="block"
                    ><span class="mb-2 block text-xs font-bold text-[#102a43]"
                        >Compare-at price</span
                    ><input
                        v-model="form.compare_at_price"
                        type="number"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        class="elyx-input"
                        placeholder="Optional reference price"
                    /><span
                        v-if="form.errors.compare_at_price"
                        class="mt-1 block text-xs text-red-600"
                        >{{ form.errors.compare_at_price }}</span
                    ></label
                >
                <label class="block"
                    ><span class="mb-2 block text-xs font-bold text-[#102a43]"
                        >Billing period</span
                    ><input
                        v-model="form.billing_period"
                        type="text"
                        class="elyx-input"
                        placeholder="month, year, or one-time"
                    /><span
                        v-if="form.errors.billing_period"
                        class="mt-1 block text-xs text-red-600"
                        >{{ form.errors.billing_period }}</span
                    ></label
                >
                <div
                    class="flex flex-wrap items-center gap-3 border-t border-[#c7e3f3] pt-6 md:col-span-2"
                >
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-[#0f6ea8] px-5 py-3 text-sm font-bold text-white shadow-[0_10px_22px_rgba(15,110,168,0.16)] transition hover:bg-[#0b527e] disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <Send class="h-4 w-4" />{{
                            form.processing
                                ? 'Sending...'
                                : editingProduct
                                  ? 'Resubmit for review'
                                  : 'Submit for review'
                        }}</button
                    ><button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-[#c7e3f3] bg-white px-5 py-3 text-sm font-bold text-[#102a43] transition hover:border-[#27a9e8]"
                        @click="closeForm"
                    >
                        Cancel</button
                    ><span class="text-xs text-[#55718a]"
                        >Your submission will be reviewed by the ELYX
                        team.</span
                    >
                </div>
            </form>
        </div>

        <div
            class="mt-12 flex items-end justify-between gap-4 border-b border-[#d7ebf8] pb-5"
        >
            <div>
                <p
                    class="mb-2 text-[10px] font-bold tracking-[0.18em] text-[#0f6ea8] uppercase"
                >
                    Your catalog
                </p>
                <h2
                    class="text-3xl font-extrabold tracking-[-0.04em] text-[#102a43]"
                >
                    Product submissions.
                </h2>
            </div>
            <span
                class="hidden items-center gap-2 text-xs font-semibold text-[#55718a] sm:flex"
                ><Package class="h-4 w-4 text-[#27a9e8]" />
                {{ products.length }}
                {{ products.length === 1 ? 'product' : 'products' }}</span
            >
        </div>

        <div v-if="products.length" class="mt-6 grid gap-4 lg:grid-cols-2">
            <article
                v-for="product in products"
                :key="product.id"
                class="overflow-hidden rounded-3xl border border-[#c7e3f3] bg-white shadow-[0_8px_24px_rgba(16,42,67,0.04)]"
            >
                <div class="flex gap-4 p-5 sm:p-6">
                    <div
                        class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-[#e8f7fe] text-[#27a9e8] sm:h-24 sm:w-24"
                    >
                        <img
                            v-if="product.image_url"
                            :src="product.image_url"
                            :alt="product.name"
                            class="h-full w-full object-cover"
                        /><Image v-else class="h-6 w-6" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div
                            class="flex flex-wrap items-start justify-between gap-3"
                        >
                            <div>
                                <p
                                    class="text-[10px] font-bold tracking-[0.14em] text-[#0f6ea8] uppercase"
                                >
                                    {{ product.category }} · {{ product.type }}
                                </p>
                                <h3
                                    class="mt-1 truncate text-lg font-extrabold text-[#102a43]"
                                >
                                    {{ product.name }}
                                </h3>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 ring-inset"
                                :class="
                                    statusClasses(
                                        product.approval_status ?? 'draft',
                                    )
                                "
                                >{{
                                    approvalLabel(product.approval_status)
                                }}</span
                            >
                        </div>
                        <p class="mt-2 text-sm font-extrabold text-[#102a43]">
                            {{ money(product.price)
                            }}<span
                                v-if="product.billing_period"
                                class="ml-1 text-[11px] font-medium text-[#55718a]"
                                >/ {{ product.billing_period }}</span
                            >
                        </p>
                    </div>
                </div>
                <div
                    v-if="product.rejection_reason"
                    class="mx-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-xs leading-5 text-red-700 sm:mx-6"
                >
                    <strong>Feedback from review:</strong>
                    {{ product.rejection_reason }}
                </div>
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-[#d7ebf8] px-5 py-4 sm:px-6"
                >
                    <p class="text-xs text-[#55718a]">
                        {{
                            product.approval_status === 'approved'
                                ? 'Visible in the ELYX shop'
                                : 'Submitted for catalog review'
                        }}
                    </p>
                    <div class="flex gap-3">
                        <button
                            v-if="canEdit(product)"
                            type="button"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-[#55718a] transition hover:text-[#0f6ea8]"
                            @click="editProduct(product)"
                        >
                            <Edit3 class="h-3.5 w-3.5" /> Edit</button
                        ><button
                            v-if="canSubmit(product)"
                            type="button"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-[#0f6ea8] transition hover:text-[#0b527e]"
                            @click="submitForReview(product)"
                        >
                            <Send class="h-3.5 w-3.5" /> Submit
                        </button>
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
                <Package class="h-6 w-6" />
            </div>
            <h2
                class="mt-5 text-2xl font-extrabold tracking-tight text-[#102a43]"
            >
                Your catalog is ready for its first idea.
            </h2>
            <p class="mt-2 text-sm text-[#55718a]">
                Submit a product and the ELYX team will review it for the shop.
            </p>
            <button
                type="button"
                class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#0f6ea8] px-5 py-3 text-sm font-bold text-white"
                @click="startNewProduct"
            >
                <Plus class="h-4 w-4" /> Create your first product
            </button>
        </div>

        <div
            class="mt-14 flex items-end justify-between gap-4 border-b border-[#d7ebf8] pb-5"
        >
            <div>
                <p
                    class="mb-2 text-[10px] font-bold tracking-[0.18em] text-[#0f6ea8] uppercase"
                >
                    Seller-scoped sales
                </p>
                <h2
                    class="text-3xl font-extrabold tracking-[-0.04em] text-[#102a43]"
                >
                    Recent orders.
                </h2>
            </div>
            <Link
                href="/shop"
                class="hidden items-center gap-2 text-xs font-bold text-[#0f6ea8] sm:flex"
                >View shop <ArrowRight class="h-3.5 w-3.5"
            /></Link>
        </div>
        <div v-if="orders.length" class="mt-6 grid gap-4">
            <article
                v-for="order in orders"
                :key="order.id"
                class="rounded-3xl border border-[#c7e3f3] bg-white p-5 shadow-[0_8px_24px_rgba(16,42,67,0.04)] sm:p-6"
            >
                <div
                    class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start"
                >
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="text-lg font-extrabold text-[#102a43]"
                                >{{ order.order_number }}</span
                            ><span
                                class="rounded-full px-2.5 py-1 text-[10px] font-bold capitalize ring-1 ring-inset"
                                :class="statusClasses(order.status)"
                                >{{ order.status.replace('_', ' ') }}</span
                            >
                        </div>
                        <p class="mt-2 text-xs font-medium text-[#55718a]">
                            {{ order.customer_name }} ·
                            {{ formatDate(order.created_at) }}
                        </p>
                    </div>
                    <span
                        class="rounded-full px-2.5 py-1 text-[10px] font-bold capitalize ring-1 ring-inset"
                        :class="statusClasses(order.payment_status)"
                        >Payment
                        {{ order.payment_status.replace('_', ' ') }}</span
                    >
                </div>
                <div
                    class="mt-4 flex flex-wrap gap-x-4 gap-y-2 border-t border-[#d7ebf8] pt-4 text-xs text-[#55718a]"
                >
                    <span
                        v-for="item in order.items"
                        :key="`${order.id}-${item.name}`"
                        >{{ item.name }} × {{ item.quantity }}
                        <strong class="text-[#102a43]">{{
                            money(item.line_total)
                        }}</strong></span
                    >
                </div>
            </article>
        </div>
        <div
            v-else
            class="mt-6 rounded-3xl border border-dashed border-[#9edaf3] bg-white px-6 py-16 text-center"
        >
            <p class="text-lg font-extrabold text-[#102a43]">
                No seller-scoped sales yet.
            </p>
            <p class="mt-2 text-sm text-[#55718a]">
                Approved products will appear here when customers place orders.
            </p>
        </div>
    </section>
</template>
