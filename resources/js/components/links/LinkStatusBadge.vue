<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { ShortLink } from '@/types';

const props = defineProps<{
    link: Pick<ShortLink, 'is_active' | 'expires_at' | 'is_custom'>;
}>();

const expired = computed(
    () =>
        props.link.expires_at !== null &&
        new Date(props.link.expires_at) <= new Date(),
);
</script>

<template>
    <Badge v-if="!link.is_active" variant="secondary">Desactivado</Badge>
    <Badge v-else-if="expired" variant="destructive">Vencido</Badge>
    <Badge v-if="link.is_custom" variant="outline">Alias</Badge>
</template>
