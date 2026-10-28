<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ZepLogo from '@/components/ZepLogo.vue';
</script>

<template>
    <!-- Con la barra lateral colapsada solo entra el isotipo. -->
    <AppLogoIcon class="hidden size-8 group-data-[collapsible=icon]:block" />
    <ZepLogo class="w-28 group-data-[collapsible=icon]:hidden" />
</template>
