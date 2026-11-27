<template>
    <Head title="Leaderboard" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p
                class="text-xs font-semibold tracking-widest uppercase"
                style="color: var(--gl-text-muted)"
            >
                Leaderboard
            </p>
            <h1
                class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                Champions Board
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                {{ mode === 'week' ? 'Last 7 days' : 'All time' }} ·
                {{ ranked.length }} students
                <span v-if="sectionFilter"> · {{ sectionName }}</span>
            </p>
        </div>
        <div
            class="flex items-center gap-1 rounded-full p-1"
            style="
                background: var(--gl-surface-2);
                border: 1px solid var(--gl-border);
            "
        >
            <button
                @click="mode = 'week'"
                class="rounded-full px-4 py-1.5 text-xs font-bold transition-all"
                :style="
                    mode === 'week'
                        ? {
                              background:
                                  'linear-gradient(135deg, #FBBF24, #F59E0B)',
                              color: '#1F2937',
                              boxShadow: '0 2px 10px rgba(251,191,36,0.4)',
                          }
                        : { color: 'var(--gl-text-muted)' }
                "
            >
                Weekly
            </button>
            <button
                @click="mode = 'all'"
                class="rounded-full px-4 py-1.5 text-xs font-bold transition-all"
                :style="
                    mode === 'all'
                        ? {
                              background:
                                  'linear-gradient(135deg, #FBBF24, #F59E0B)',
                              color: '#1F2937',
                              boxShadow: '0 2px 10px rgba(251,191,36,0.4)',
                          }
                        : { color: 'var(--gl-text-muted)' }
                "
            >
                All time
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <select
            v-model="gradeFilter"
            class="rounded-xl px-4 py-2.5 text-sm outline-none"
            style="
                background: var(--gl-surface);
                border: 1px solid var(--gl-border);
                color: var(--gl-text-primary);
            "
        >
            <option value="">All grade levels</option>
            <option v-for="g in gradeLevels" :key="g.id" :value="g.id">
                {{ g.name }}
            </option>
        </select>
        <select
            v-model="sectionFilter"
            class="rounded-xl px-4 py-2.5 text-sm outline-none"
            style="
                background: var(--gl-surface);
                border: 1px solid var(--gl-border);
                color: var(--gl-text-primary);
            "
        >
            <option value="">All sections</option>
            <option v-for="s in filteredSections" :key="s.id" :value="s.id">
                {{ s.name }}
            </option>
        </select>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <!-- BOARD (2/3) -->
        <div class="space-y-5 xl:col-span-2">
            <!-- Podium -->
            <div
                v-if="podium.length === 3"
                class="overflow-hidden rounded-2xl p-6 sm:p-8"
                style="
                    background:
                        linear-gradient(
                            180deg,
                            rgba(251, 191, 36, 0.1),
                            rgba(251, 191, 36, 0.02)
                        ),
                        var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <div class="grid grid-cols-3 items-end gap-2 sm:gap-4">
                    <PodiumSpot
                        :entry="podium[0]"
                        :me="false"
                        metal="silver"
                        :points="pts(podium[0])"
                    />
                    <PodiumSpot
                        :entry="podium[1]"
                        :me="false"
                        metal="gold"
                        :points="pts(podium[1])"
                    />
                    <PodiumSpot
                        :entry="podium[2]"
                        :me="false"
                        metal="bronze"
                        :points="pts(podium[2])"
                    />
                </div>
            </div>

            <!-- Empty -->
            <div
                v-if="!ranked.length"
                class="gl-glow-card flex flex-col items-center justify-center px-8 py-16 text-center"
            >
                <div
                    class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl"
                    style="background: var(--gl-surface-2)"
                >
                    <Trophy
                        class="h-7 w-7"
                        style="color: var(--gl-text-muted)"
                        :stroke-width="1.5"
                    />
                </div>
                <p
                    class="text-sm font-medium"
                    style="color: var(--gl-text-secondary)"
                >
                    No students found
                </p>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    No students match your filter selection.
                </p>
            </div>

            <!-- Rows -->
            <div v-else class="space-y-2.5">
                <div
                    v-for="e in ranked"
                    :key="e.id"
                    class="flex items-center gap-3 rounded-2xl px-4 py-3 transition-all hover:-translate-y-px"
                    :style="
                        e.rank === 1
                            ? {
                                  background: 'rgba(251,191,36,0.1)',
                                  border: '1px solid rgba(251,191,36,0.35)',
                                  boxShadow: '0 2px 14px rgba(251,191,36,0.12)',
                              }
                            : {
                                  background: 'var(--gl-surface)',
                                  border: '1px solid var(--gl-border)',
                              }
                    "
                >
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-extrabold"
                        :style="
                            e.rank <= 3
                                ? { background: '#1F2937', color: '#FBBF24' }
                                : {
                                      background: 'var(--gl-surface-2)',
                                      color: 'var(--gl-text-muted)',
                                      border: '1px solid var(--gl-border)',
                                  }
                        "
                    >
                        {{ e.rank }}
                    </span>
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full text-sm font-bold text-white"
                        :style="{ background: avatarBg(e.id) }"
                    >
                        <img
                            v-if="e.avatar"
                            :src="e.avatar"
                            alt=""
                            class="h-full w-full object-cover"
                        />
                        <template v-else>{{ (e.name || '?').charAt(0).toUpperCase() }}</template>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-sm font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ e.name }}
                        </p>
                        <p
                            class="truncate text-[11px]"
                            style="color: var(--gl-text-muted)"
                        >
                            {{ e.section_name
                            }}{{
                                e.grade_level_name
                                    ? ' · ' + e.grade_level_name
                                    : ''
                            }}
                        </p>
                    </div>
                    <div
                        class="hidden shrink-0 items-center gap-1.5 sm:flex"
                        style="min-width: 150px"
                    >
                        <Medal
                            class="h-4 w-4 shrink-0"
                            :style="{
                                color:
                                    e.rank <= 3
                                        ? '#FBBF24'
                                        : 'var(--gl-text-muted)',
                            }"
                            :stroke-width="2"
                        />
                        <span
                            class="truncate text-xs font-medium"
                            :style="{
                                color:
                                    e.rank <= 3
                                        ? 'var(--gl-text-primary)'
                                        : 'var(--gl-text-muted)',
                            }"
                        >
                            {{ titleOf(e) }}
                        </span>
                    </div>
                    <span
                        class="shrink-0 text-sm font-extrabold tabular-nums"
                        style="color: var(--gl-text-primary)"
                        >XP {{ pts(e).toLocaleString() }}</span
                    >
                </div>
            </div>
        </div>

        <!-- SIDE (1/3) -->
        <div class="space-y-5">
            <!-- Top section -->
            <div
                class="overflow-hidden rounded-2xl p-5 text-center"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <div
                    class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-2xl"
                    style="
                        background: linear-gradient(135deg, #fbbf24, #f59e0b);
                        box-shadow: 0 0 18px rgba(251, 191, 36, 0.35);
                    "
                >
                    <Trophy class="h-6 w-6 text-white" :stroke-width="2" />
                </div>
                <p
                    class="text-[11px] font-bold tracking-widest uppercase"
                    style="color: var(--gl-text-muted)"
                >
                    Top Section
                </p>
                <p
                    class="mt-1 truncate text-sm font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    {{ topSection?.name ?? '–' }}
                </p>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <div
                        class="rounded-xl p-2"
                        style="background: var(--gl-surface-2)"
                    >
                        <p
                            class="text-sm font-extrabold tabular-nums"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ ranked.length }}
                        </p>
                        <p
                            class="text-[10px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Students
                        </p>
                    </div>
                    <div
                        class="rounded-xl p-2"
                        style="background: var(--gl-surface-2)"
                    >
                        <p
                            class="text-sm font-extrabold tabular-nums"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ totalXp.toLocaleString() }}
                        </p>
                        <p
                            class="text-[10px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Total XP
                        </p>
                    </div>
                </div>
            </div>

            <!-- How XP works -->
            <div
                class="rounded-2xl p-5"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <h3
                    class="mb-3 text-sm font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    How XP works
                </h3>
                <div class="space-y-3">
                    <div
                        v-for="(s, i) in steps"
                        :key="s.title"
                        class="relative flex items-start gap-3"
                    >
                        <div
                            v-if="i < steps.length - 1"
                            class="absolute top-10 left-[18px] h-[calc(100%-28px)] w-px"
                            style="background: var(--gl-border)"
                        ></div>
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                            :style="{ background: s.bg }"
                        >
                            <component
                                :is="s.icon"
                                class="h-4 w-4"
                                :style="{ color: s.color }"
                                :stroke-width="2"
                            />
                        </div>
                        <div class="min-w-0 pt-0.5">
                            <p
                                class="text-xs font-bold"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ s.title }}
                            </p>
                            <p
                                class="mt-0.5 text-[11px] leading-relaxed"
                                style="color: var(--gl-text-secondary)"
                            >
                                {{ s.text }}
                            </p>
                        </div>
                    </div>
                </div>
                <Link
                    href="/teacher/results"
                    class="mt-4 flex items-center justify-center gap-1.5 rounded-xl px-4 py-2.5 text-xs font-semibold transition-all hover:scale-[1.02]"
                    style="
                        background: linear-gradient(
                            135deg,
                            var(--gl-primary),
                            var(--gl-secondary)
                        );
                        color: #fff;
                        box-shadow: 0 0 12px var(--gl-primary-glow);
                    "
                >
                    Review results
                    <ArrowRight class="h-3.5 w-3.5" :stroke-width="2" />
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, usePoll } from '@inertiajs/vue3';
import { Trophy, Medal, Zap, Award, Gift, ArrowRight } from '@lucide/vue';
import { computed, ref } from 'vue';
import PodiumSpot from '@/components/PodiumSpot.vue';
import { levelForPoints, titleForLevel } from '@/lib/levels';

const props = defineProps<{
    entries: any[];
    groups: any[];
    sections: any[];
    gradeLevels: any[];
}>();

// Keep names and profile photos fresh when students update their profile.
usePoll(15000, { only: ['entries', 'groups'] });

const gradeFilter = ref('');
const sectionFilter = ref('');
const mode = ref<'week' | 'all'>('all');

const filteredSections = computed(() => {
    const sections = props.sections ?? [];

    if (!gradeFilter.value) {
return sections;
}

    return sections.filter(
        (s: any) => s.grade_level_id === Number(gradeFilter.value),
    );
});

const scoped = computed(() => {
    let list = props.entries ?? [];

    if (gradeFilter.value) {
list = list.filter(
            (e: any) => e.grade_level_id === Number(gradeFilter.value),
        );
}

    if (sectionFilter.value) {
list = list.filter(
            (e: any) => e.section_id === Number(sectionFilter.value),
        );
}

    return list;
});

function pts(e: any): number {
    return mode.value === 'week'
        ? (e?.week_points ?? 0)
        : (e?.total_points ?? 0);
}

const ranked = computed(() => {
    return [...scoped.value]
        .sort((a, b) => pts(b) - pts(a))
        .map((e, i) => ({ ...e, rank: i + 1 }));
});

const podium = computed(() => {
    const top = ranked.value.slice(0, 3);

    if (top.length < 3) {
return top;
}

    return [top[1], top[0], top[2]];
});

const sectionName = computed(() => {
    if (!sectionFilter.value) {
return '';
}

    return (
        props.sections?.find((s: any) => s.id === Number(sectionFilter.value))
            ?.name ?? ''
    );
});

const topSection = computed(() => {
    const sums = new Map<number, { name: string; xp: number }>();

    for (const e of scoped.value) {
        if (e.section_id == null) {
continue;
}

        const cur = sums.get(e.section_id) ?? {
            name: e.section_name ?? 'Unknown',
            xp: 0,
        };
        cur.xp += e.total_points ?? 0;
        sums.set(e.section_id, cur);
    }

    let best: { name: string; xp: number } | null = null;

    for (const v of sums.values()) {
        if (!best || v.xp > best.xp) {
best = v;
}
    }

    return best;
});

const totalXp = computed(() =>
    scoped.value.reduce((a: number, e: any) => a + (e.total_points ?? 0), 0),
);

const AVATAR_BG = [
    'linear-gradient(135deg, #3B82F6, #7C3AED)',
    'linear-gradient(135deg, #10B981, #06B6D4)',
    'linear-gradient(135deg, #F59E0B, #EF4444)',
    'linear-gradient(135deg, #EC4899, #8B5CF6)',
    'linear-gradient(135deg, #14B8A6, #3B82F6)',
];

function avatarBg(id: number): string {
    return AVATAR_BG[Math.abs(id ?? 0) % AVATAR_BG.length];
}

function titleOf(entry: any): string {
    return titleForLevel(levelForPoints(entry?.total_points ?? 0));
}

const steps = [
    {
        title: 'Earn XP',
        text: 'Graded work earns XP based on score — higher scores, more XP.',
        icon: Zap,
        bg: 'rgba(251,191,36,0.14)',
        color: '#FBBF24',
    },
    {
        title: 'Level up',
        text: 'Each 100 XP moves a student one level higher with a new title.',
        icon: Award,
        bg: 'rgba(59,130,246,0.12)',
        color: '#3B82F6',
    },
    {
        title: 'Top 3 spotlight',
        text: 'The top 3 of each board get the gold medal spotlight.',
        icon: Trophy,
        bg: 'rgba(16,185,129,0.12)',
        color: '#10B981',
    },
    {
        title: 'Weekly board',
        text: 'Weekly mode counts XP earned in the last 7 days only.',
        icon: Gift,
        bg: 'rgba(124,58,237,0.12)',
        color: '#7C3AED',
    },
];
</script>
