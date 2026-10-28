<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { LinkIcon } from '@lucide/vue';
import ZepLogo from '@/components/ZepLogo.vue';
import { home } from '@/routes';

defineProps<{
    reason: 'expired' | 'inactive';
}>();
</script>

<template>
    <Head title="Link no disponible" />

    <main
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 text-center"
    >
        <div
            class="flex size-14 items-center justify-center rounded-2xl bg-muted"
        >
            <LinkIcon class="size-7 text-muted-foreground" />
        </div>
        <div class="max-w-md">
            <h1 class="text-2xl font-semibold">Este link no está disponible</h1>
            <p class="mt-2 text-muted-foreground">
                <template v-if="reason === 'expired'">
                    Venció y ya no lleva a ningún lado.
                </template>
                <template v-else> Quien lo creó lo desactivó. </template>
                Si te lo pasaron, pedile a esa persona uno nuevo.
            </p>
        </div>
        <Link
            :href="home()"
            class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
        >
            <ZepLogo class="w-24" />
        </Link>
    </main>
</template>
