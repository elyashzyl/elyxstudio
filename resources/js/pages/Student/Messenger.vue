<template>
    <Messenger v-bind="$props" />
</template>

<script setup lang="ts">
import Messenger from '@/pages/Teacher/Messenger.vue';

defineProps<{
    conversations: any[];
    contacts: any[];
    conversation?: any;
    can_message_classmates?: boolean;
    allow_classmate_messaging?: boolean;
    is_teacher?: boolean;
    is_superadmin?: boolean;
}>();
</script>
