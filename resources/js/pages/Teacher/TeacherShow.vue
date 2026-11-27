<template>
    <Head :title="teacher.name" />

    <Link
        href="/teacher/teachers"
        class="mb-5 inline-flex items-center gap-1.5 text-xs font-semibold transition-colors hover:opacity-80"
        style="color: var(--gl-text-muted)"
    >
        <ArrowLeft class="h-3.5 w-3.5" :stroke-width="2" /> Back to teachers
    </Link>

    <!-- Header -->
    <div class="mb-5 flex flex-wrap items-center gap-4">
        <div
            class="relative flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full text-lg font-extrabold text-white"
            :style="{
                background: teacher.avatar
                    ? 'transparent'
                    : 'linear-gradient(135deg, var(--gl-secondary), var(--gl-primary))',
                boxShadow:
                    '0 0 0 3px var(--gl-surface), 0 0 0 5px var(--gl-secondary-glow)',
            }"
        >
            <img
                v-if="teacher.avatar"
                :src="teacher.avatar"
                :alt="teacher.name"
                class="h-full w-full object-cover"
            />
            <span v-else>{{ (teacher.name || '?').charAt(0) }}</span>
        </div>
        <div class="min-w-0 flex-1">
            <p
                class="text-xs font-semibold tracking-widest uppercase"
                style="color: var(--gl-text-muted)"
            >
                Teacher
            </p>
            <h1
                class="truncate text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                {{ teacher.name }}
            </h1>
            <p
                class="mt-0.5 truncate text-xs"
                style="color: var(--gl-text-muted)"
            >
                {{ teacher.email }} · {{ students.length }} student{{
                    students.length === 1 ? '' : 's'
                }}
            </p>
            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                <span
                    v-if="teacher.grade_level?.name || teacher.grade || teacher.section?.name"
                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-0.5 text-xs font-semibold"
                    style="
                        background: rgba(124, 58, 237, 0.12);
                        color: var(--gl-secondary);
                        border: 1px solid rgba(124, 58, 237, 0.2);
                    "
                >
                    <School class="h-3.5 w-3.5" :stroke-width="2" />
                    Advisory: {{ teacher.grade_level?.name || teacher.grade }} {{ teacher.section?.name ? '· ' + teacher.section.name : '' }}
                </span>
                <span
                    v-if="teacher.gender"
                    class="rounded-lg px-2.5 py-0.5 text-xs font-semibold"
                    style="
                        background: var(--gl-surface-2);
                        color: var(--gl-text-secondary);
                        border: 1px solid var(--gl-border);
                    "
                >
                    {{ teacher.gender }}
                </span>
                <span
                    v-for="sub in teacher.subjects"
                    :key="sub.id"
                    class="rounded-lg px-2.5 py-0.5 text-xs font-semibold"
                    style="
                        background: rgba(59, 130, 246, 0.12);
                        color: var(--gl-primary);
                        border: 1px solid rgba(59, 130, 246, 0.2);
                    "
                >
                    {{ sub.name }}
                </span>
            </div>
        </div>
    </div>

    <!-- Stat cards -->
    <div class="mb-5 grid grid-cols-3 gap-4">
        <div
            class="rounded-2xl p-4 text-center"
            style="
                background: rgba(59, 130, 246, 0.08);
                border: 1px solid rgba(59, 130, 246, 0.18);
            "
        >
            <div
                class="mx-auto mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                style="background: rgba(59, 130, 246, 0.16)"
            >
                <Users
                    class="h-[18px] w-[18px]"
                    style="color: var(--gl-primary)"
                    :stroke-width="2"
                />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ students.length }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Students
            </p>
        </div>
        <div
            class="rounded-2xl p-4 text-center"
            style="
                background: rgba(124, 58, 237, 0.08);
                border: 1px solid rgba(124, 58, 237, 0.18);
            "
        >
            <div
                class="mx-auto mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                style="background: rgba(124, 58, 237, 0.16)"
            >
                <School
                    class="h-[18px] w-[18px]"
                    style="color: var(--gl-secondary)"
                    :stroke-width="2"
                />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ sections.length }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Sections
            </p>
        </div>
        <div
            class="rounded-2xl p-4 text-center"
            style="
                background: rgba(16, 185, 129, 0.08);
                border: 1px solid rgba(16, 185, 129, 0.18);
            "
        >
            <div
                class="mx-auto mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                style="background: rgba(16, 185, 129, 0.16)"
            >
                <GraduationCap
                    class="h-[18px] w-[18px]"
                    style="color: var(--gl-success)"
                    :stroke-width="2"
                />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ grades.length }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Grades
            </p>
        </div>
    </div>

    <!-- Teacher Bio & Communication Overview -->
    <div
        v-if="teacher.bio || teacher.grade_level || teacher.section || teacher.messenger_enabled !== undefined"
        class="mb-5 rounded-2xl p-4 sm:p-5"
        style="background: var(--gl-surface); border: 1px solid var(--gl-border);"
    >
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
            <div :class="teacher.bio ? 'md:col-span-2' : 'md:col-span-3'">
                <p class="text-[11px] font-bold tracking-widest uppercase" style="color: var(--gl-text-muted)">
                    About / Bio
                </p>
                <p v-if="teacher.bio" class="mt-1 text-xs leading-relaxed italic" style="color: var(--gl-text-primary)">
                    "{{ teacher.bio }}"
                </p>
                <p v-else class="mt-1 text-xs italic" style="color: var(--gl-text-muted)">
                    No professional bio provided.
                </p>
            </div>
            <div class="space-y-2 border-t pt-3 md:border-t-0 md:border-l md:pt-0 md:pl-4" style="border-color: var(--gl-border)">
                <div>
                    <span class="text-[11px] font-bold tracking-widest uppercase" style="color: var(--gl-text-muted)">
                        Advisory Class
                    </span>
                    <p class="text-xs font-semibold" style="color: var(--gl-text-primary)">
                        {{ (teacher.grade_level?.name || teacher.grade) ? ((teacher.grade_level?.name || teacher.grade) + (teacher.section?.name ? ' · ' + teacher.section.name : '')) : 'No advisory class assigned' }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 pt-1 text-[11px]">
                    <span
                        class="rounded-md px-2 py-0.5 font-medium border"
                        :style="{
                            background: teacher.messenger_enabled ? 'rgba(16,185,129,0.08)' : 'var(--gl-surface-2)',
                            color: teacher.messenger_enabled ? 'var(--gl-success)' : 'var(--gl-text-muted)',
                            borderColor: teacher.messenger_enabled ? 'rgba(16,185,129,0.2)' : 'var(--gl-border)',
                        }"
                    >
                        Direct Chat: {{ teacher.messenger_enabled ? 'Active' : 'Disabled' }}
                    </span>
                    <span
                        class="rounded-md px-2 py-0.5 font-medium border"
                        :style="{
                            background: teacher.allow_classmate_messaging ? 'rgba(16,185,129,0.08)' : 'var(--gl-surface-2)',
                            color: teacher.allow_classmate_messaging ? 'var(--gl-success)' : 'var(--gl-text-muted)',
                            borderColor: teacher.allow_classmate_messaging ? 'rgba(16,185,129,0.2)' : 'var(--gl-border)',
                        }"
                    >
                        Classmate Chat: {{ teacher.allow_classmate_messaging ? 'Allowed' : 'Restricted' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div
        v-if="!students.length"
        class="gl-glow-card flex flex-col items-center justify-center px-8 py-16 text-center"
    >
        <div
            class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl"
            style="background: var(--gl-surface-2)"
        >
            <Users
                class="h-7 w-7"
                style="color: var(--gl-text-muted)"
                :stroke-width="1.5"
            />
        </div>
        <p class="text-sm font-medium" style="color: var(--gl-text-secondary)">
            No students yet
        </p>
        <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
            This teacher hasn't registered any students.
        </p>
    </div>

    <template v-else>
        <div class="mb-4 flex flex-wrap items-center gap-2.5">
            <div class="relative min-w-[200px] flex-1">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search students..."
                    class="w-full rounded-full border py-2.5 pr-4 pl-9 text-sm transition-all outline-none focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                    style="
                        background: var(--gl-surface);
                        color: var(--gl-text-primary);
                        border-color: var(--gl-border);
                    "
                />
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2"
                    :stroke-width="2"
                    style="color: var(--gl-text-muted)"
                />
            </div>
            <select
                v-model="gradeFilter"
                class="rounded-xl border px-3 py-2.5 text-sm outline-none"
                style="
                    background: var(--gl-surface);
                    color: var(--gl-text-primary);
                    border-color: var(--gl-border);
                "
            >
                <option value="">All grades</option>
                <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
            </select>
            <select
                v-model="sectionFilter"
                class="rounded-xl border px-3 py-2.5 text-sm outline-none"
                style="
                    background: var(--gl-surface);
                    color: var(--gl-text-primary);
                    border-color: var(--gl-border);
                "
            >
                <option value="">All sections</option>
                <option v-for="s in sections" :key="s" :value="s">
                    {{ s }}
                </option>
            </select>
        </div>

        <div
            v-if="!filteredStudents.length"
            class="gl-glow-card px-6 py-10 text-center text-sm"
            style="color: var(--gl-text-muted)"
        >
            No students match your filters.
        </div>

        <div v-else>
            <div
                class="mb-1 hidden grid-cols-[1fr_110px_110px_110px] items-center gap-3 px-4 pb-2 text-[11px] font-semibold tracking-wider uppercase sm:grid"
                style="color: var(--gl-text-muted)"
            >
                <span>Name</span><span>Grade</span><span>Section</span
                ><span class="text-right">Joined</span>
            </div>
            <div class="space-y-2">
                <div
                    v-for="s in filteredStudents"
                    :key="s.id"
                    class="grid grid-cols-[auto_1fr] items-center gap-3 rounded-2xl px-4 py-3 transition-all hover:-translate-y-px sm:grid-cols-[1fr_110px_110px_110px]"
                    style="
                        background: var(--gl-surface);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="relative flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full text-[11px] font-bold text-white"
                            :style="{
                                background: s.avatar
                                    ? 'transparent'
                                    : 'linear-gradient(135deg, ' +
                                      avatarColor(s.name) +
                                      ', ' +
                                      avatarColor2(s.name) +
                                      ')',
                            }"
                        >
                            <img
                                v-if="s.avatar"
                                :src="s.avatar"
                                :alt="s.name"
                                class="h-full w-full object-cover"
                            />
                            <span v-else>{{ initials(s.name) }}</span>
                        </div>
                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ s.name }}
                            </p>
                            <p
                                class="truncate text-[11px]"
                                style="color: var(--gl-text-muted)"
                            >
                                {{ s.email }}
                            </p>
                        </div>
                    </div>
                    <span
                        class="hidden truncate text-xs sm:block"
                        style="color: var(--gl-text-secondary)"
                        >{{ s.grade ?? '—' }}</span
                    >
                    <span
                        class="hidden truncate text-xs sm:block"
                        style="color: var(--gl-text-secondary)"
                        >{{ s.section?.name ?? '—' }}</span
                    >
                    <span
                        class="hidden truncate text-right text-[11px] sm:block"
                        style="color: var(--gl-text-muted)"
                        >{{ formatDate(s.created_at) }}</span
                    >
                </div>
            </div>
            <p class="mt-4 text-xs" style="color: var(--gl-text-muted)">
                {{ filteredStudents.length }} of {{ students.length }} students
            </p>
        </div>
    </template>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Users, Search, School, GraduationCap } from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{ teacher: any; students: any[] }>();

const search = ref('');
const gradeFilter = ref('');
const sectionFilter = ref('');
const grades = computed(() => {
    const set = new Set<string>();

    for (const s of props.students) {
if (s.grade) {
set.add(s.grade);
}
}

    return [...set].sort();
});
const sections = computed(() => {
    const set = new Set<string>();

    for (const s of props.students) {
if (s.section?.name) {
set.add(s.section.name);
}
}

    return [...set].sort();
});
const filteredStudents = computed(() =>
    props.students.filter((s) => {
        if (search.value) {
            const q = search.value.toLowerCase();

            if (
                !s.name.toLowerCase().includes(q) &&
                !s.email.toLowerCase().includes(q)
            ) {
return false;
}
        }

        if (gradeFilter.value && s.grade !== gradeFilter.value) {
return false;
}

        if (sectionFilter.value && s.section?.name !== sectionFilter.value) {
return false;
}

        return true;
    }),
);

function initials(name: string) {
    return (name || '')
        .split(' ')
        .map((w: string) => w[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}
function avatarColor(name: string) {
    const c = [
        '#3B82F6',
        '#10B981',
        '#F59E0B',
        '#EF4444',
        '#7C3AED',
        '#EC4899',
        '#06B6D4',
    ];
    let h = 0;
    const n = name || '';

    for (let i = 0; i < n.length; i++) {
        h = n.charCodeAt(i) + ((h << 5) - h);
    }

    return c[Math.abs(h) % c.length];
}
function avatarColor2(name: string) {
    const c = [
        '#2563EB',
        '#059669',
        '#D97706',
        '#DC2626',
        '#6D28D9',
        '#DB2777',
        '#0891B2',
    ];
    let h = 0;
    const n = name || '';

    for (let i = 0; i < n.length; i++) {
        h = n.charCodeAt(i) + ((h << 7) - h);
    }

    return c[Math.abs(h) % c.length];
}

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>
