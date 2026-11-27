<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Megaphone,
    Pin,
    Calendar,
    Search,
    Clock,
    User,
    Users,
    ArrowLeft,
    CheckCircle2,
} from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{
    announcements: {
        data: any[];
        links?: any[];
        current_page?: number;
        last_page?: number;
    };
}>();

const search = ref('');
const selectedCategory = ref('all');
const audienceFilter = ref('all');

const categories = [
    { value: 'all', label: 'All Notices' },
    { value: 'academic', label: 'Academic' },
    { value: 'reminder', label: 'Reminders' },
    { value: 'urgent', label: 'Urgent' },
    { value: 'event', label: 'Events' },
    { value: 'general', label: 'General' },
];

const items = computed(() => {
    let list = props.announcements.data || [];

    if (selectedCategory.value !== 'all') {
        list = list.filter((a) => a.category === selectedCategory.value);
    }

    if (audienceFilter.value === 'section') {
        list = list.filter((a) => !!a.section);
    } else if (audienceFilter.value === 'grade') {
        list = list.filter((a) => a.grade_level && !a.section);
    } else if (audienceFilter.value === 'school_wide') {
        list = list.filter((a) => !a.grade_level && !a.section);
    }

    const q = search.value.trim().toLowerCase();

    if (q) {
        list = list.filter(
            (a) =>
                a.title.toLowerCase().includes(q) ||
                a.content.toLowerCase().includes(q) ||
                (a.author_name && a.author_name.toLowerCase().includes(q)) ||
                (a.section?.name && a.section.name.toLowerCase().includes(q)),
        );
    }

    return list;
});

const pinnedItems = computed(() => items.value.filter((a) => a.is_pinned));
const regularItems = computed(() => items.value.filter((a) => !a.is_pinned));

function getCategoryColor(cat: string) {
    switch (cat) {
        case 'urgent':
            return {
                bg: 'rgba(239, 68, 68, 0.15)',
                text: '#EF4444',
                border: 'rgba(239, 68, 68, 0.3)',
            };
        case 'reminder':
            return {
                bg: 'rgba(245, 158, 11, 0.15)',
                text: '#F59E0B',
                border: 'rgba(245, 158, 11, 0.3)',
            };
        case 'academic':
            return {
                bg: 'rgba(59, 130, 246, 0.15)',
                text: '#3B82F6',
                border: 'rgba(59, 130, 246, 0.3)',
            };
        case 'event':
            return {
                bg: 'rgba(168, 85, 247, 0.15)',
                text: '#A855F7',
                border: 'rgba(168, 85, 247, 0.3)',
            };
        default:
            return {
                bg: 'rgba(16, 185, 129, 0.15)',
                text: '#10B981',
                border: 'rgba(16, 185, 129, 0.3)',
            };
    }
}
</script>

<template>
    <Head title="Announcements & Notices" />

    <div class="px-6 py-6 space-y-6">
        <!-- Top Banner -->
        <div class="gl-glow-card p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl"
                    style="
                        background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(139, 92, 246, 0.2));
                        border: 1px solid rgba(59, 130, 246, 0.35);
                    "
                >
                    <Megaphone class="h-6 w-6" style="color: var(--gl-primary)" />
                </div>
                <div>
                    <h1 class="text-xl font-bold" style="color: var(--gl-text-primary)">
                        School & Class Announcements
                    </h1>
                    <p class="text-xs" style="color: var(--gl-text-muted)">
                        Stay up to date with official school notices, academic reminders, and events
                    </p>
                </div>
            </div>

            <Link
                href="/student/dashboard"
                class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold transition-all hover:bg-[var(--gl-surface-2)]"
                style="color: var(--gl-text-secondary); border: 1px solid var(--gl-border)"
            >
                <ArrowLeft class="h-3.5 w-3.5" />
                Back to Dashboard
            </Link>
        </div>

        <!-- Filters & Search -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-1.5">
                <button
                    v-for="cat in categories"
                    :key="cat.value"
                    type="button"
                    @click="selectedCategory = cat.value"
                    class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-all capitalize"
                    :style="
                        selectedCategory === cat.value
                            ? {
                                  background: 'var(--gl-primary)',
                                  color: '#fff',
                                  boxShadow: '0 0 12px var(--gl-primary-glow)',
                              }
                            : {
                                  background: 'var(--gl-surface)',
                                  border: '1px solid var(--gl-border)',
                                  color: 'var(--gl-text-secondary)',
                              }
                    "
                >
                    {{ cat.label }}
                </button>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                <select
                    v-model="audienceFilter"
                    class="rounded-xl px-3 py-2 text-xs font-medium outline-none transition-all cursor-pointer"
                    style="
                        background: var(--gl-surface);
                        border: 1px solid var(--gl-border);
                        color: var(--gl-text-primary);
                    "
                    title="Filter by audience scope"
                >
                    <option value="all">All Audiences</option>
                    <option value="section">Section Notices Only</option>
                    <option value="grade">Grade-wide Notices</option>
                    <option value="school_wide">School-wide Notices</option>
                </select>

                <div class="relative w-full sm:w-64">
                    <Search
                        class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4"
                        style="color: var(--gl-text-muted)"
                    />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search announcements..."
                        class="w-full rounded-xl pl-9 pr-4 py-2 text-xs outline-none transition-all focus:border-[var(--gl-primary)]"
                        style="
                            background: var(--gl-surface);
                            border: 1px solid var(--gl-border);
                            color: var(--gl-text-primary);
                        "
                    />
                </div>
            </div>
        </div>

        <!-- Pinned Section -->
        <div v-if="pinnedItems.length > 0" class="space-y-3">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider" style="color: #F59E0B">
                <Pin class="h-3.5 w-3.5 fill-current" />
                Pinned Notices
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div
                    v-for="a in pinnedItems"
                    :key="a.id"
                    class="gl-glow-card relative p-5 space-y-3 rounded-2xl transition-all duration-200 hover:-translate-y-0.5"
                    style="
                        border: 1px solid rgba(245, 158, 11, 0.4);
                        background: linear-gradient(135deg, rgba(245, 158, 11, 0.05), var(--gl-surface));
                    "
                >
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span
                                class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                :style="{
                                    background: getCategoryColor(a.category).bg,
                                    color: getCategoryColor(a.category).text,
                                    border: '1px solid ' + getCategoryColor(a.category).border,
                                }"
                            >
                                {{ a.category }}
                            </span>

                            <span
                                v-if="a.section"
                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                style="
                                    background: rgba(139, 92, 246, 0.15);
                                    color: #8B5CF6;
                                    border: 1px solid rgba(139, 92, 246, 0.35);
                                "
                                title="Targeted to your section"
                            >
                                <Users class="h-2.5 w-2.5" />
                                Section {{ a.section.name }}
                            </span>
                            <span
                                v-else-if="a.grade_level"
                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                style="
                                    background: rgba(59, 130, 246, 0.15);
                                    color: #3B82F6;
                                    border: 1px solid rgba(59, 130, 246, 0.35);
                                "
                            >
                                <Users class="h-2.5 w-2.5" />
                                {{ a.grade_level.name }}
                            </span>
                        </div>

                        <span class="inline-flex items-center gap-1 text-[11px]" style="color: var(--gl-text-muted)">
                            <Clock class="h-3 w-3" />
                            {{ a.time_ago || a.formatted_date }}
                        </span>
                    </div>

                    <h2 class="text-base font-bold leading-snug" style="color: var(--gl-text-primary)">
                        {{ a.title }}
                    </h2>

                    <p class="text-xs leading-relaxed whitespace-pre-line" style="color: var(--gl-text-secondary)">
                        {{ a.content }}
                    </p>

                    <div class="pt-3 border-t flex items-center justify-between text-[11px]" style="border-color: var(--gl-border)">
                        <span class="flex items-center gap-1.5 font-medium" style="color: var(--gl-text-muted)">
                            <User class="h-3.5 w-3.5" />
                            {{ a.author_name }} · {{ a.author_role }}
                        </span>
                        <span style="color: var(--gl-text-muted)">{{ a.formatted_date }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Regular Section -->
        <div v-if="regularItems.length > 0" class="space-y-3">
            <div
                v-if="pinnedItems.length > 0"
                class="text-xs font-bold uppercase tracking-wider pt-2"
                style="color: var(--gl-text-muted)"
            >
                Recent Announcements
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="a in regularItems"
                    :key="a.id"
                    class="gl-glow-card relative p-5 flex flex-col justify-between space-y-3 rounded-2xl transition-all duration-200 hover:-translate-y-0.5"
                >
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                    :style="{
                                        background: getCategoryColor(a.category).bg,
                                        color: getCategoryColor(a.category).text,
                                        border: '1px solid ' + getCategoryColor(a.category).border,
                                    }"
                                >
                                    {{ a.category }}
                                </span>

                                <span
                                    v-if="a.section"
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    style="
                                        background: rgba(139, 92, 246, 0.15);
                                        color: #8B5CF6;
                                        border: 1px solid rgba(139, 92, 246, 0.35);
                                    "
                                    title="Targeted to your section"
                                >
                                    <Users class="h-2.5 w-2.5" />
                                    Section {{ a.section.name }}
                                </span>
                                <span
                                    v-else-if="a.grade_level"
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    style="
                                        background: rgba(59, 130, 246, 0.15);
                                        color: #3B82F6;
                                        border: 1px solid rgba(59, 130, 246, 0.35);
                                    "
                                >
                                    <Users class="h-2.5 w-2.5" />
                                    {{ a.grade_level.name }}
                                </span>
                            </div>

                            <span class="inline-flex items-center gap-1 text-[11px]" style="color: var(--gl-text-muted)">
                                <Clock class="h-3 w-3" />
                                {{ a.time_ago || a.formatted_date }}
                            </span>
                        </div>

                        <h2 class="text-sm font-bold leading-snug" style="color: var(--gl-text-primary)">
                            {{ a.title }}
                        </h2>

                        <p class="text-xs leading-relaxed whitespace-pre-line" style="color: var(--gl-text-secondary)">
                            {{ a.content }}
                        </p>
                    </div>

                    <div class="pt-3 border-t flex items-center justify-between text-[11px]" style="border-color: var(--gl-border)">
                        <span class="flex items-center gap-1.5 font-medium truncate" style="color: var(--gl-text-muted)">
                            <User class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">{{ a.author_name }}</span>
                        </span>
                        <span class="shrink-0" style="color: var(--gl-text-muted)">{{ a.formatted_date }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-if="items.length === 0"
            class="gl-glow-card flex flex-col items-center justify-center p-12 text-center"
        >
            <div
                class="flex h-14 w-14 items-center justify-center rounded-2xl mb-3"
                style="background: var(--gl-surface-2)"
            >
                <Megaphone class="h-7 w-7 opacity-40" style="color: var(--gl-text-muted)" />
            </div>
            <h3 class="text-sm font-bold" style="color: var(--gl-text-primary)">
                No announcements found
            </h3>
            <p class="text-xs mt-1 max-w-sm" style="color: var(--gl-text-muted)">
                There are no active announcements matching your filter right now. Check back soon!
            </p>
        </div>
    </div>
</template>
