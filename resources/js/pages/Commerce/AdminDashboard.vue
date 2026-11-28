<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    Archive,
    CircleDollarSign,
    Package,
    Plus,
    Save,
    ShoppingBag,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import CommerceLayout from '@/layouts/CommerceLayout.vue';

type Product = {
    id: number;
    name: string;
    slug: string;
    type: 'product' | 'digital' | 'service';
    category: string;
    tagline: string | null;
    description: string;
    features: string[] | null;
    image_url: string | null;
    price: string | number;
    compare_at_price: string | number | null;
    billing_period: string | null;
    stock: number | null;
    reserved_stock: number;
    available_stock: number | null;
    is_featured: boolean;
    is_active: boolean;
    seller_id?: number | null;
    seller?: { id: number; name: string; email: string } | null;
    approval_status?: 'draft' | 'submitted' | 'approved' | 'rejected';
    rejection_reason?: string | null;
};

type PaymentStatus = 'unpaid' | 'paid' | 'failed' | 'refunded';

type Order = {
    id: number;
    order_number: string;
    status: string;
    payment_status: PaymentStatus;
    customer_name: string;
    customer_email: string;
    total: string | number;
    created_at: string;
    items: { name: string; quantity: number }[];
};

defineOptions({ layout: CommerceLayout });

const props = defineProps<{
    products: Product[];
    sellers: { id: number; name: string; email: string }[];
    orders: Order[];
    filters: { status: string };
    basePath?: string;
    stats: {
        products: number;
        pending_orders: number;
        reserved_stock: number;
        order_value: number;
        pending_products?: number;
    };
}>();

const page = usePage();
const flash = computed(() => (page.props as any).flash);
const editing = ref<Product | null>(null);
const showProductForm = ref(false);
const statusFilter = ref(props.filters.status || 'all');
const basePath = computed(() => props.basePath || '/admin/commerce');
const paymentStatuses: PaymentStatus[] = [
    'unpaid',
    'paid',
    'failed',
    'refunded',
];
const paymentTransitions: Record<PaymentStatus, PaymentStatus[]> = {
    unpaid: ['paid', 'failed'],
    paid: ['refunded'],
    failed: [],
    refunded: [],
};
const money = (value: string | number) =>
    `$${Number(value).toLocaleString('en-US', { minimumFractionDigits: 0 })}`;

const emptyForm = () => ({
    name: '',
    slug: '',
    type: 'digital',
    category: 'Digital assets',
    tagline: '',
    description: '',
    features: '',
    image_url: '',
    price: '',
    compare_at_price: '',
    billing_period: '',
    stock: '',
    is_featured: false,
    is_active: true,
    seller_id: '',
});

const form = useForm(emptyForm());

function startCreate() {
    editing.value = null;
    form.defaults(emptyForm());
    form.reset();
    showProductForm.value = true;
}

function startEdit(product: Product) {
    editing.value = product;
    form.name = product.name;
    form.slug = product.slug;
    form.type = product.type;
    form.category = product.category;
    form.tagline = product.tagline ?? '';
    form.description = product.description;
    form.features = product.features?.join('\n') ?? '';
    form.image_url = product.image_url ?? '';
    form.price = String(product.price);
    form.compare_at_price = product.compare_at_price
        ? String(product.compare_at_price)
        : '';
    form.billing_period = product.billing_period ?? '';
    form.stock = product.stock === null ? '' : String(product.stock);
    form.is_featured = product.is_featured;
    form.is_active = product.is_active;
    form.seller_id = product.seller_id ? String(product.seller_id) : '';
    showProductForm.value = true;
}

function submitProduct() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showProductForm.value = false;
            editing.value = null;
        },
    };

    if (editing.value) {
        form.patch(`${basePath.value}/products/${editing.value.slug}`, options);
    } else {
        form.post(`${basePath.value}/products`, options);
    }
}

function filterOrders() {
    router.get(
        basePath.value,
        { status: statusFilter.value },
        { preserveState: true, replace: true },
    );
}

function updateStatus(order: Order, status: string) {
    router.patch(
        `${basePath.value}/orders/${order.id}/status`,
        { status },
        { preserveScroll: true },
    );
}

function updatePaymentStatus(order: Order, paymentStatus: PaymentStatus) {
    router.patch(
        `${basePath.value}/orders/${order.id}/payment-status`,
        { payment_status: paymentStatus },
        { preserveScroll: true },
    );
}

function approveProduct(product: Product) {
    router.patch(
        `${basePath.value}/products/${product.slug}/approve`,
        {},
        { preserveScroll: true },
    );
}

function rejectProduct(product: Product) {
    const reason = window.prompt(
        `Why should “${product.name}” be returned to the seller?`,
        product.rejection_reason ?? '',
    );

    if (!reason?.trim()) {
        return;
    }

    router.patch(
        `${basePath.value}/products/${product.slug}/reject`,
        { rejection_reason: reason.trim() },
        { preserveScroll: true },
    );
}

function canUpdatePaymentStatus(
    order: Order,
    paymentStatus: PaymentStatus,
): boolean {
    if (paymentStatus === order.payment_status) {
        return true;
    }

    if (
        order.status === 'cancelled' &&
        !['failed', 'refunded'].includes(paymentStatus)
    ) {
        return false;
    }

    return paymentTransitions[order.payment_status].includes(paymentStatus);
}
</script>

<template>
    <Head title="ELYX Commerce" />
    <div class="commerce-admin-workspace px-4 py-4 sm:px-6 sm:py-6">
        <div
            v-if="flash?.success"
            class="mb-5 rounded-2xl border border-emerald-400/30 bg-emerald-500/10 px-5 py-3 text-sm text-emerald-500"
        >
            {{ flash.success }}
        </div>
        <div
            v-if="flash?.error"
            class="mb-5 rounded-2xl border border-red-400/30 bg-red-500/10 px-5 py-3 text-sm text-red-500"
        >
            {{ flash.error }}
        </div>

        <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p
                    class="text-xs font-semibold tracking-[0.2em] uppercase"
                    style="color: var(--gl-primary)"
                >
                    ELYX Studio
                </p>
                <h1
                    class="mt-1 text-3xl font-extrabold tracking-tight"
                    style="color: var(--gl-text-primary)"
                >
                    Commerce workspace
                </h1>
                <p
                    class="mt-1 max-w-2xl text-sm"
                    style="color: var(--gl-text-muted)"
                >
                    Manage the public catalog, order requests, and reserved
                    studio goods from one operational view.
                </p>
            </div>
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold text-white"
                style="background: var(--gl-primary)"
                @click="startCreate"
            >
                <Plus class="h-4 w-4" /> Add product
            </button>
        </div>

        <div class="mb-7 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div
                v-for="stat in [
                    {
                        label: 'Active products',
                        value: stats.products,
                        icon: Package,
                    },
                    {
                        label: 'Open orders',
                        value: stats.pending_orders,
                        icon: ShoppingBag,
                    },
                    {
                        label: 'Reserved units',
                        value: stats.reserved_stock,
                        icon: Archive,
                    },
                    {
                        label: 'Order value',
                        value: money(stats.order_value),
                        icon: CircleDollarSign,
                    },
                ]"
                :key="stat.label"
                class="rounded-2xl border p-4"
                style="
                    background: var(--gl-surface);
                    border-color: var(--gl-border);
                "
            >
                <component
                    :is="stat.icon"
                    class="mb-3 h-5 w-5"
                    style="color: var(--gl-primary)"
                />
                <p
                    class="text-2xl font-extrabold"
                    style="color: var(--gl-text-primary)"
                >
                    {{ stat.value }}
                </p>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    {{ stat.label }}
                </p>
            </div>
        </div>

        <div
            v-if="showProductForm"
            class="mb-7 rounded-2xl border p-5 sm:p-6"
            style="
                background: var(--gl-surface);
                border-color: var(--gl-border);
            "
        >
            <div class="mb-5 flex items-center justify-between">
                <h2
                    class="text-lg font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    {{ editing ? 'Edit product' : 'Add product' }}
                </h2>
                <button
                    type="button"
                    class="text-xs font-semibold"
                    style="color: var(--gl-text-muted)"
                    @click="showProductForm = false"
                >
                    Close
                </button>
            </div>
            <form
                class="grid gap-4 md:grid-cols-2"
                @submit.prevent="submitProduct"
            >
                <label class="block"
                    ><span class="admin-label">Name</span
                    ><input v-model="form.name" class="admin-input" required
                /></label>
                <label class="block"
                    ><span class="admin-label">Slug</span
                    ><input v-model="form.slug" class="admin-input" required
                /></label>
                <label class="block"
                    ><span class="admin-label">Type</span
                    ><select v-model="form.type" class="admin-input">
                        <option value="digital">Digital</option>
                        <option value="product">Physical product</option>
                        <option value="service">Service</option>
                    </select></label
                >
                <label class="block"
                    ><span class="admin-label">Category</span
                    ><input
                        v-model="form.category"
                        class="admin-input"
                        required
                /></label>
                <label class="block md:col-span-2"
                    ><span class="admin-label">Tagline</span
                    ><input v-model="form.tagline" class="admin-input"
                /></label>
                <label class="block md:col-span-2"
                    ><span class="admin-label">Description</span
                    ><textarea
                        v-model="form.description"
                        class="admin-input min-h-24"
                        required
                    />
                </label>
                <label class="block"
                    ><span class="admin-label">Price</span
                    ><input
                        v-model="form.price"
                        type="number"
                        min="0"
                        step="0.01"
                        class="admin-input"
                        required
                /></label>
                <label class="block"
                    ><span class="admin-label">Compare-at price</span
                    ><input
                        v-model="form.compare_at_price"
                        type="number"
                        min="0"
                        step="0.01"
                        class="admin-input"
                /></label>
                <label class="block"
                    ><span class="admin-label">Stock (blank = unlimited)</span
                    ><input
                        v-model="form.stock"
                        type="number"
                        min="0"
                        class="admin-input"
                /></label>
                <label class="block"
                    ><span class="admin-label">Billing period</span
                    ><input
                        v-model="form.billing_period"
                        class="admin-input"
                        placeholder="project / sprint"
                /></label>
                <label class="block"
                    ><span class="admin-label">Seller / owner</span
                    ><select v-model="form.seller_id" class="admin-input">
                        <option value="">ELYX Studio</option>
                        <option
                            v-for="seller in sellers"
                            :key="seller.id"
                            :value="String(seller.id)"
                        >
                            {{ seller.name }} · {{ seller.email }}
                        </option>
                    </select></label
                >
                <label class="block md:col-span-2"
                    ><span class="admin-label">Features (one per line)</span
                    ><textarea
                        v-model="form.features"
                        class="admin-input min-h-24"
                    />
                </label>
                <label class="block md:col-span-2"
                    ><span class="admin-label">Image URL</span
                    ><input
                        v-model="form.image_url"
                        type="url"
                        class="admin-input"
                /></label>
                <div class="flex flex-wrap items-center gap-5 md:col-span-2">
                    <label
                        class="inline-flex items-center gap-2 text-sm"
                        style="color: var(--gl-text-secondary)"
                        ><input v-model="form.is_featured" type="checkbox" />
                        Featured</label
                    >
                    <label
                        class="inline-flex items-center gap-2 text-sm"
                        style="color: var(--gl-text-secondary)"
                        ><input v-model="form.is_active" type="checkbox" />
                        Active in storefront</label
                    >
                </div>
                <div class="flex justify-end md:col-span-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold text-white disabled:opacity-50"
                        style="background: var(--gl-primary)"
                    >
                        <Save class="h-4 w-4" /> Save product
                    </button>
                </div>
            </form>
        </div>

        <section
            class="mb-7 rounded-2xl border"
            style="
                background: var(--gl-surface);
                border-color: var(--gl-border);
            "
        >
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b p-5"
                style="border-color: var(--gl-border)"
            >
                <div>
                    <h2
                        class="text-lg font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        Catalog
                    </h2>
                    <p class="text-xs" style="color: var(--gl-text-muted)">
                        Products, digital goods, and service packages.
                    </p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead>
                        <tr
                            class="border-b text-xs"
                            style="
                                border-color: var(--gl-border);
                                color: var(--gl-text-muted);
                            "
                        >
                            <th class="px-5 py-3 font-semibold">Offering</th>
                            <th class="px-5 py-3 font-semibold">Type</th>
                            <th class="px-5 py-3 font-semibold">Price</th>
                            <th class="px-5 py-3 font-semibold">
                                Availability
                            </th>
                            <th class="px-5 py-3 font-semibold">State</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="product in products"
                            :key="product.id"
                            class="border-b last:border-0"
                            style="border-color: var(--gl-border)"
                        >
                            <td class="px-5 py-4">
                                <p
                                    class="font-semibold"
                                    style="color: var(--gl-text-primary)"
                                >
                                    {{ product.name }}
                                </p>
                                <p
                                    class="mt-1 text-xs"
                                    style="color: var(--gl-text-muted)"
                                >
                                    {{ product.category }}
                                    <span
                                        v-if="product.seller"
                                        class="text-[#0f6ea8]"
                                    >
                                        · {{ product.seller.name }}
                                    </span>
                                </p>
                            </td>
                            <td
                                class="px-5 py-4 text-xs capitalize"
                                style="color: var(--gl-text-secondary)"
                            >
                                {{ product.type }}
                            </td>
                            <td
                                class="px-5 py-4 font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ money(product.price) }}
                            </td>
                            <td
                                class="px-5 py-4 text-xs"
                                style="color: var(--gl-text-secondary)"
                            >
                                {{
                                    product.stock === null
                                        ? 'Unlimited'
                                        : `${product.available_stock} available / ${product.reserved_stock} reserved`
                                }}
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase"
                                    :style="
                                        product.approval_status ===
                                            'approved' && product.is_active
                                            ? 'background: rgba(16,185,129,.12); color: var(--gl-success)'
                                            : product.approval_status ===
                                                'rejected'
                                              ? 'background: rgba(239,68,68,.12); color: #dc2626'
                                              : 'background: rgba(245,158,11,.14); color: #b45309'
                                    "
                                    >{{
                                        product.approval_status === 'approved'
                                            ? product.is_active
                                                ? 'Live'
                                                : 'Archived'
                                            : product.approval_status ||
                                              'Pending'
                                    }}</span
                                >
                                <p
                                    v-if="product.rejection_reason"
                                    class="mt-2 max-w-44 text-[11px] text-red-500"
                                >
                                    {{ product.rejection_reason }}
                                </p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button
                                        v-if="
                                            product.approval_status ===
                                                'submitted' ||
                                            product.approval_status ===
                                                'rejected'
                                        "
                                        type="button"
                                        class="text-xs font-bold text-emerald-600"
                                        @click="approveProduct(product)"
                                    >
                                        Approve
                                    </button>
                                    <button
                                        v-if="
                                            product.approval_status ===
                                            'submitted'
                                        "
                                        type="button"
                                        class="text-xs font-bold text-amber-600"
                                        @click="rejectProduct(product)"
                                    >
                                        Return
                                    </button>
                                    <button
                                        type="button"
                                        class="text-xs font-bold"
                                        style="color: var(--gl-primary)"
                                        @click="startEdit(product)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        v-if="product.is_active"
                                        type="button"
                                        class="text-xs font-bold text-red-500"
                                        @click="
                                            router.patch(
                                                `${basePath}/products/${product.slug}/archive`,
                                                {},
                                                { preserveScroll: true },
                                            )
                                        "
                                    >
                                        Archive
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section
            class="rounded-2xl border"
            style="
                background: var(--gl-surface);
                border-color: var(--gl-border);
            "
        >
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b p-5"
                style="border-color: var(--gl-border)"
            >
                <div>
                    <h2
                        class="text-lg font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        Order requests
                    </h2>
                    <p class="text-xs" style="color: var(--gl-text-muted)">
                        Review, confirm, fulfill, and close customer work.
                    </p>
                </div>
                <select
                    v-model="statusFilter"
                    class="admin-input max-w-44"
                    @change="filterOrders"
                >
                    <option value="all">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="in_progress">In progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="divide-y" style="border-color: var(--gl-border)">
                <div
                    v-for="order in orders"
                    :key="order.id"
                    class="grid gap-4 p-5 lg:grid-cols-[1fr_auto_auto_auto] lg:items-center"
                >
                    <div>
                        <p
                            class="font-bold"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ order.order_number }}
                        </p>
                        <p
                            class="mt-1 text-xs"
                            style="color: var(--gl-text-muted)"
                        >
                            {{ order.customer_name }} ·
                            {{ order.customer_email }}
                        </p>
                        <p
                            class="mt-2 text-xs"
                            style="color: var(--gl-text-secondary)"
                        >
                            {{
                                order.items
                                    .map(
                                        (item) =>
                                            `${item.name} × ${item.quantity}`,
                                    )
                                    .join(', ')
                            }}
                        </p>
                    </div>
                    <p
                        class="font-semibold"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ money(order.total) }}
                    </p>
                    <select
                        class="admin-input lg:w-32"
                        :value="order.payment_status"
                        aria-label="Payment status"
                        @change="
                            updatePaymentStatus(
                                order,
                                ($event.target as HTMLSelectElement)
                                    .value as PaymentStatus,
                            )
                        "
                    >
                        <option
                            v-for="paymentStatus in paymentStatuses"
                            :key="paymentStatus"
                            :value="paymentStatus"
                            :disabled="
                                !canUpdatePaymentStatus(order, paymentStatus)
                            "
                        >
                            {{ paymentStatus }}
                        </option>
                    </select>
                    <select
                        class="admin-input lg:w-40"
                        :value="order.status"
                        aria-label="Fulfillment status"
                        @change="
                            updateStatus(
                                order,
                                ($event.target as HTMLSelectElement).value,
                            )
                        "
                    >
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="in_progress">In progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div
                    v-if="!orders.length"
                    class="p-10 text-center text-sm"
                    style="color: var(--gl-text-muted)"
                >
                    No order requests match this filter.
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
.admin-label {
    display: block;
    margin-bottom: 0.4rem;
    color: var(--gl-text-muted);
    font-size: 0.72rem;
    font-weight: 700;
}
.admin-input {
    width: 100%;
    border: 1px solid var(--gl-border);
    border-radius: 0.75rem;
    background: var(--gl-bg);
    color: var(--gl-text-primary);
    padding: 0.65rem 0.75rem;
    font-size: 0.82rem;
    outline: none;
}
.admin-input:focus {
    border-color: var(--gl-primary);
}
</style>
