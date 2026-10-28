<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed } from 'vue';
import ZepLogo from '@/components/ZepLogo.vue';
import { dashboard, home, privacy, terms } from '@/routes';

/*
 * Términos y condiciones, y política de privacidad: la misma página con otro
 * texto. Los textos viven en `resources/legal/*.md` y llegan ya convertidos a
 * HTML desde LegalController; son del repo, no de un usuario.
 */

defineProps<{
    title: string;
    version: string;
    html: string;
}>();

const page = usePage();
const back = computed(() => (page.props.auth.user ? dashboard() : home()));
</script>

<template>
    <Head :title="title" />

    <div class="min-h-svh bg-background text-foreground">
        <header
            class="mx-auto flex w-full max-w-2xl items-center justify-between gap-4 px-4 pt-6 sm:px-6"
        >
            <Link
                :href="back"
                class="inline-flex min-h-11 items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="size-4 shrink-0" />
                Volver
            </Link>
            <Link :href="home()" aria-label="Zep">
                <ZepLogo class="w-24" />
            </Link>
        </header>

        <main
            class="mx-auto flex w-full max-w-2xl flex-col gap-2 p-4 pb-12 sm:p-6"
        >
            <h1 class="text-2xl font-semibold">{{ title }}</h1>
            <p class="text-sm text-muted-foreground">
                Versión del {{ version }}
            </p>

            <!-- eslint-disable-next-line vue/no-v-html -- texto propio del repo, ver LegalController -->
            <article
                class="mt-3 rounded-xl border bg-card p-5 text-pretty wrap-anywhere sm:p-6 [&_a]:underline [&_a]:underline-offset-4 [&_a:hover]:text-primary [&_blockquote]:mt-4 [&_blockquote]:border-l-4 [&_blockquote]:border-border [&_blockquote]:pl-4 [&_blockquote]:text-sm [&_blockquote]:text-muted-foreground [&_code]:rounded [&_code]:bg-muted [&_code]:px-1 [&_h2]:mt-8 [&_h2]:text-lg [&_h2]:font-semibold [&_li]:mt-1.5 [&_p]:mt-3 [&_p:first-child]:mt-0 [&_ul]:mt-2 [&_ul]:list-disc [&_ul]:pl-5"
                v-html="html"
            />

            <nav class="mt-4 flex gap-4 text-sm text-muted-foreground">
                <Link :href="terms()" class="hover:text-foreground"
                    >Términos y condiciones</Link
                >
                <Link :href="privacy()" class="hover:text-foreground"
                    >Política de privacidad</Link
                >
            </nav>
        </main>
    </div>
</template>
