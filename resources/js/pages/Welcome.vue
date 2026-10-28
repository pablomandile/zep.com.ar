<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { BarChart3, CalendarClock, PenLine, QrCode } from '@lucide/vue';
import { computed } from 'vue';
import ZepLogo from '@/components/ZepLogo.vue';
import { Button } from '@/components/ui/button';
import { login, privacy, register, terms } from '@/routes';

const page = usePage();
const shortDomain = computed(() => page.props.shortDomain);

const features = [
    {
        icon: PenLine,
        title: 'Alias a medida',
        text: 'Elegí cómo termina tu link: /promo, /cv, /evento.',
    },
    {
        icon: BarChart3,
        title: 'Estadísticas',
        text: 'Clics por día, de dónde vienen, dispositivos y países.',
    },
    {
        icon: QrCode,
        title: 'Código QR',
        text: 'Descargalo en PNG o SVG para imprimir o compartir.',
    },
    {
        icon: CalendarClock,
        title: 'Vencimiento',
        text: 'Elegí hasta cuándo anda cada link, o pausalo cuando quieras.',
    },
];
</script>

<template>
    <Head title="Acortá tus URLs" />

    <div class="flex min-h-svh flex-col bg-background text-foreground">
        <header
            class="mx-auto flex w-full max-w-5xl items-center justify-end gap-2 px-4 py-4"
        >
            <Button variant="ghost" as-child>
                <Link :href="login()">Iniciar sesión</Link>
            </Button>
            <Button as-child>
                <Link :href="register()">Registrarse</Link>
            </Button>
        </header>

        <main
            class="mx-auto flex w-full max-w-5xl flex-1 flex-col items-center px-4 py-10 text-center md:py-16"
        >
            <h1 class="sr-only">Zep: acortá tus URLs</h1>
            <ZepLogo size="lg" class="w-full max-w-md" />

            <p class="mt-8 max-w-xl text-lg text-pretty text-muted-foreground">
                Convertí cualquier URL larga en un link corto de
                <span class="font-medium text-foreground">{{
                    shortDomain
                }}</span
                >, fácil de compartir y de recordar, y mirá cuántas personas lo
                abren.
            </p>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <Button size="lg" as-child>
                    <Link :href="register()">Crear cuenta gratis</Link>
                </Button>
                <Button size="lg" variant="outline" as-child>
                    <Link :href="login()">Ya tengo cuenta</Link>
                </Button>
            </div>
            <p class="mt-3 text-sm text-muted-foreground">
                Las cuentas nuevas se habilitan después de una aprobación.
            </p>

            <ul
                class="mt-16 grid w-full gap-4 text-left sm:grid-cols-2 lg:grid-cols-4"
            >
                <li
                    v-for="feature in features"
                    :key="feature.title"
                    class="rounded-2xl border bg-card p-5"
                >
                    <component
                        :is="feature.icon"
                        class="size-6 text-primary"
                        aria-hidden="true"
                    />
                    <h2 class="mt-3 font-medium">{{ feature.title }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ feature.text }}
                    </p>
                </li>
            </ul>
        </main>

        <footer
            class="mx-auto flex w-full max-w-5xl flex-wrap justify-center gap-x-6 gap-y-2 px-4 py-6 text-sm text-muted-foreground"
        >
            <Link :href="terms()" class="hover:text-foreground"
                >Términos y condiciones</Link
            >
            <Link :href="privacy()" class="hover:text-foreground"
                >Política de privacidad</Link
            >
        </footer>
    </div>
</template>
