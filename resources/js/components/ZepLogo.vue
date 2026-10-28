<script setup lang="ts">
/*
 * Logo completo de Zep (con el texto «Acorta tus URLs. Ve más lejos.»).
 * Va en todos lados; el isotipo cuadrado (AppLogoIcon) queda para el favicon,
 * los íconos de app y la barra lateral colapsada.
 */
withDefaults(defineProps<{ size?: 'sm' | 'lg' }>(), { size: 'sm' });
</script>

<template>
    <img
        :src="size === 'lg' ? '/img/zep-logo.png' : '/img/zep-logo-sm.png'"
        alt="Zep: acortá tus URLs"
        width="480"
        height="179"
        class="h-auto"
        decoding="async"
    />
</template>
