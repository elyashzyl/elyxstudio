<template>
    <button
        type="button"
        @click="toggleThemeMode"
        class="relative inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition-all duration-200 hover:scale-105 focus:outline-none active:scale-95"
        style="
            background: var(--gl-surface-2);
            border: 1px solid var(--gl-border);
            color: var(--gl-text-secondary);
        "
        :title="
            isDark
                ? 'Dark mode active (click to switch to Light mode)'
                : 'Light mode active (click to switch to Dark mode)'
        "
        :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
    >
        <Moon
            v-if="isDark"
            class="h-4.5 w-4.5 transition-transform duration-200"
            style="color: var(--gl-primary)"
            :stroke-width="2.2"
        />
        <Sun
            v-else
            class="h-4.5 w-4.5 text-amber-500 transition-transform duration-200"
            :stroke-width="2.2"
        />
    </button>
</template>

<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue';
import { isDark, toggleThemeMode } from '@/composables/useTheme';
</script>
