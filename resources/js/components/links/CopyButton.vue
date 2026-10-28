<script setup lang="ts">
import { Check, Copy } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';

const props = withDefaults(
    defineProps<{
        text: string;
        label?: string;
        size?: 'default' | 'sm' | 'icon' | 'icon-sm';
        variant?: 'default' | 'outline' | 'secondary' | 'ghost';
        successMessage?: string;
    }>(),
    {
        label: 'Copiar',
        successMessage: 'Link copiado al portapapeles.',
        size: 'default',
        variant: 'default',
    },
);

const copied = ref(false);

async function copy() {
    try {
        await navigator.clipboard.writeText(props.text);
        copied.value = true;
        toast.success(props.successMessage);
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        toast.error('No se pudo copiar. Seleccionalo y copialo a mano.');
    }
}
</script>

<template>
    <Button
        type="button"
        :size="size"
        :variant="variant"
        :aria-label="label"
        :title="label"
        @click="copy"
    >
        <Check v-if="copied" class="size-4" />
        <Copy v-else class="size-4" />
        <span v-if="!size.startsWith('icon')">{{
            copied ? 'Copiado' : label
        }}</span>
    </Button>
</template>
