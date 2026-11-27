<template>
    <Meetings :meetings="meetings" />
</template>

<script setup lang="ts">
import Meetings from '@/pages/Teacher/Meetings.vue';

defineProps<{
    meetings: any[];
}>();
</script>
