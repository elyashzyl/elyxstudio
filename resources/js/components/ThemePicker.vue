<template>
    <div>
        <!-- Filter + search -->
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div
                class="flex rounded-xl p-1"
                style="
                    background: var(--gl-surface-2);
                    border: 1px solid var(--gl-border);
                "
            >
                <button
                    v-for="f in filters"
                    :key="f.id"
                    type="button"
                    @click="filter = f.id"
                    class="rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all"
                    :style="
                        filter === f.id
                            ? {
                                  background:
                                      'linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))',
                                  color: '#fff',
                                  boxShadow: '0 0 12px var(--gl-primary-glow)',
                              }
                            : { color: 'var(--gl-text-muted)' }
                    "
                >
                    {{ f.label }}
                </button>
            </div>

            <div class="relative min-w-[200px] flex-1 sm:max-w-xs">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2"
                    style="color: var(--gl-text-muted)"
                    :stroke-width="2"
                />
                <input
                    v-model="query"
                    type="text"
                    placeholder="Search themes..."
                    class="w-full rounded-xl py-2 pr-3 pl-9 text-xs transition-all outline-none"
                    style="
                        background: var(--gl-surface-2);
                        border: 1px solid var(--gl-border);
                        color: var(--gl-text-primary);
                    "
                />
            </div>

            <span
                class="text-xs font-medium"
                style="color: var(--gl-text-muted)"
            >
                {{ shown.length }} themes
            </span>
        </div>

        <!-- Compact Theme Pills -->
        <div
            class="flex flex-wrap items-center justify-center gap-2 sm:gap-2.5"
        >
            <button
                v-for="t in shown"
                :key="t.id"
                type="button"
                @click="applyTheme(t.id)"
                class="group relative inline-flex items-center gap-2.5 rounded-xl px-3.5 py-2 text-xs font-semibold transition-all duration-200 hover:scale-[1.03] active:scale-[0.98]"
                :style="{
                    background:
                        activeTheme === t.id
                            ? 'var(--gl-surface-3)'
                            : 'var(--gl-surface)',
                    border:
                        activeTheme === t.id
                            ? '1.5px solid var(--gl-primary)'
                            : '1px solid var(--gl-border)',
                    boxShadow:
                        activeTheme === t.id
                            ? '0 0 16px -2px var(--gl-primary-glow), 0 0 0 1px var(--gl-primary)'
                            : 'none',
                }"
                :title="`${t.label} (${t.dark ? 'Dark' : 'Light'})`"
            >
                <!-- 3 Color Swatches -->
                <span class="flex shrink-0 items-center gap-1">
                    <span
                        v-for="(color, ci) in t.swatches"
                        :key="ci"
                        class="h-2.5 w-2.5 rounded-[3px] shadow-xs transition-transform duration-200 group-hover:scale-110"
                        :style="{ background: color }"
                    ></span>
                </span>

                <!-- Theme Label -->
                <span
                    class="font-medium tracking-tight select-none"
                    :style="{
                        color:
                            activeTheme === t.id
                                ? 'var(--gl-primary)'
                                : 'var(--gl-text-primary)',
                    }"
                >
                    {{ t.label }}
                </span>

                <!-- Active Indicator -->
                <Check
                    v-if="activeTheme === t.id"
                    class="h-3 w-3 shrink-0"
                    :style="{ color: 'var(--gl-primary)' }"
                    :stroke-width="3"
                />
            </button>
        </div>

        <p
            v-if="!shown.length"
            class="py-8 text-center text-sm"
            style="color: var(--gl-text-muted)"
        >
            No themes match your search.
        </p>
    </div>
</template>

<script setup lang="ts">
import { Check, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import { THEMES, activeTheme, applyTheme } from '@/composables/useTheme';

const filters = [
    { id: 'all', label: 'All' },
    { id: 'dark', label: 'Dark' },
    { id: 'light', label: 'Light' },
] as const;

const filter = ref<'all' | 'dark' | 'light'>('all');
const query = ref('');

const shown = computed(() => {
    const q = query.value.trim().toLowerCase();

    return THEMES.filter((t) => {
        if (filter.value === 'dark' && !t.dark) {
return false;
}

        if (filter.value === 'light' && t.dark) {
return false;
}

        if (q && !t.label.toLowerCase().includes(q)) {
return false;
}

        return true;
    });
});
</script>
