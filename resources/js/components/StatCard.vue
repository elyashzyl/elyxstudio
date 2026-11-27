<template>
    <div class="card flex items-center gap-4 p-5">
        <div
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg"
            style="background-color: var(--gl-primary-glow)"
        >
            <component
                :is="icon"
                class="h-5 w-5"
                :stroke-width="2"
                style="color: var(--gl-primary)"
            />
        </div>
        <div>
            <p
                class="text-2xl font-semibold"
                style="color: var(--gl-text-primary)"
            >
                {{ value }}
            </p>
            <p class="text-sm" style="color: var(--gl-text-secondary)">
                {{ label }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    label: string;
    value: string | number;
    icon: any;
}>();
</script>
