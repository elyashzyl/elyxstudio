<template>
    <div class="flex min-w-0 flex-col items-center">
        <div
            class="flex items-center justify-center overflow-hidden rounded-full font-bold text-white"
            :class="
                metal === 'gold' ? 'h-14 w-14 text-lg' : 'h-11 w-11 text-sm'
            "
            :style="{
                background: avatarBg,
                border: `3px solid ${cfg.ring}`,
                boxShadow: me
                    ? '0 0 0 3px var(--gl-primary-glow), 0 0 16px var(--gl-primary-glow)'
                    : 'none',
            }"
        >
            <img
                v-if="entry?.avatar"
                :src="entry.avatar"
                alt=""
                class="h-full w-full object-cover"
            />
            <template v-else>{{ (entry?.name || '?').charAt(0).toUpperCase() }}</template>
        </div>
        <p
            class="mt-1.5 w-full truncate text-center text-xs font-bold"
            :class="metal === 'gold' ? 'sm:text-sm' : ''"
            style="color: var(--gl-text-primary)"
        >
            {{ me ? 'You' : entry?.name }}
        </p>
        <span
            class="mt-1 rounded-full px-2.5 py-1 text-[10px] font-extrabold tabular-nums"
            style="background: #1f2937; color: #fff"
        >
            XP {{ (points ?? 0).toLocaleString() }}
        </span>
        <div
            class="z-10 mt-2 -mb-5 flex h-10 w-10 items-center justify-center rounded-full"
            :style="{
                background: cfg.medalBg,
                boxShadow: '0 3px 10px rgba(0,0,0,0.2)',
            }"
        >
            <Medal class="h-5 w-5 text-white" :stroke-width="2" />
        </div>
        <div
            class="w-20 sm:w-24"
            :class="
                metal === 'gold'
                    ? 'h-32 sm:h-36'
                    : metal === 'silver'
                      ? 'h-20 sm:h-24'
                      : 'h-16 sm:h-20'
            "
            :style="{
                background: cfg.body,
                borderRadius: '10px 10px 14px 14px',
                boxShadow:
                    'inset -8px 0 12px rgba(0,0,0,0.08), inset 8px 0 12px rgba(255,255,255,0.25)',
            }"
        >
            <div
                class="mx-auto h-3.5 w-full"
                :style="{ background: cfg.top, borderRadius: '50%' }"
            ></div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Medal } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    entry: any;
    me: boolean;
    metal: 'gold' | 'silver' | 'bronze';
    points: number;
}>();

const METALS = {
    gold: {
        ring: '#FBBF24',
        medalBg: 'linear-gradient(135deg, #FBBF24, #D97706)',
        body: 'linear-gradient(180deg, #FCD34D, #F59E0B)',
        top: '#FEF3C7',
    },
    silver: {
        ring: '#94A3B8',
        medalBg: 'linear-gradient(135deg, #CBD5E1, #64748B)',
        body: 'linear-gradient(180deg, #E2E8F0, #94A3B8)',
        top: '#F8FAFC',
    },
    bronze: {
        ring: '#F97316',
        medalBg: 'linear-gradient(135deg, #FDBA74, #C2410C)',
        body: 'linear-gradient(180deg, #FDBA74, #EA580C)',
        top: '#FFEDD5',
    },
};

const cfg = computed(() => METALS[props.metal]);

const AVATAR_BG = [
    'linear-gradient(135deg, #3B82F6, #7C3AED)',
    'linear-gradient(135deg, #10B981, #06B6D4)',
    'linear-gradient(135deg, #F59E0B, #EF4444)',
    'linear-gradient(135deg, #EC4899, #8B5CF6)',
    'linear-gradient(135deg, #14B8A6, #3B82F6)',
];

const avatarBg = computed(
    () => AVATAR_BG[Math.abs(props.entry?.id ?? 0) % AVATAR_BG.length],
);
</script>
