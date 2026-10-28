<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { BarChart3, ChevronDown, Link2, MousePointerClick } from '@lucide/vue';
import { computed, ref } from 'vue';
import LinkController from '@/actions/App/Http/Controllers/LinkController';
import InputError from '@/components/InputError.vue';
import CopyButton from '@/components/links/CopyButton.vue';
import QrDialog from '@/components/links/QrDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { index as linksIndex, show as linksShow } from '@/routes/links';
import type { ShortLink } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Acortar', href: dashboard() }],
    },
});

defineProps<{
    recentLinks: ShortLink[];
    aliasRules: { min: number; max: number };
}>();

const page = usePage();
const shortDomain = computed(() => page.props.shortDomain);
const created = computed(() => page.flash.createdLink ?? null);
const showAdvanced = ref(false);

const minDate = computed(() => {
    const d = new Date(Date.now() + 60 * 60 * 1000);
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().slice(0, 16);
});

// datetime-local manda la hora local sin zona: se pasa a ISO (UTC) antes de enviar.
function withUtcExpiry(data: Record<string, unknown>) {
    const value = data.expires_at;

    return {
        ...data,
        expires_at:
            typeof value === 'string' && value !== ''
                ? new Date(value).toISOString()
                : null,
    };
}

function displayUrl(url: string) {
    return url.replace(/^https?:\/\//, '');
}
</script>

<template>
    <Head title="Acortar" />

    <div
        class="mx-auto flex w-full max-w-3xl flex-col gap-8 px-4 py-8 md:py-12"
    >
        <div class="text-center">
            <h1 class="text-3xl font-semibold tracking-tight md:text-4xl">
                Acortá tu link
            </h1>
            <p class="mt-2 text-muted-foreground">
                Pegá una URL larga y obtené un link corto de
                <span class="font-medium text-foreground">{{
                    shortDomain
                }}</span>
            </p>
        </div>

        <Form
            v-bind="LinkController.store.form()"
            :reset-on-success="['url', 'alias', 'expires_at']"
            :options="{ preserveScroll: true }"
            :transform="withUtcExpiry"
            class="rounded-2xl border bg-card p-4 shadow-sm md:p-6"
            v-slot="{ errors, processing }"
        >
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="min-w-0 flex-1">
                    <Label for="url" class="sr-only">URL larga</Label>
                    <Input
                        id="url"
                        name="url"
                        type="text"
                        inputmode="url"
                        autocomplete="off"
                        autofocus
                        required
                        placeholder="Pegá acá tu URL larga"
                        class="h-12 text-base"
                        data-test="url-input"
                    />
                </div>
                <Button
                    type="submit"
                    size="lg"
                    class="h-12 px-8 text-base"
                    :disabled="processing"
                    data-test="shorten-button"
                >
                    <Spinner v-if="processing" />
                    <Link2 v-else class="size-5" />
                    Acortar
                </Button>
            </div>
            <InputError class="mt-2" :message="errors.url" />

            <button
                type="button"
                class="mt-4 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                :aria-expanded="showAdvanced"
                @click="showAdvanced = !showAdvanced"
            >
                <ChevronDown
                    class="size-4 transition-transform"
                    :class="{ 'rotate-180': showAdvanced }"
                />
                Opciones avanzadas
            </button>

            <div
                v-show="showAdvanced || errors.alias || errors.expires_at"
                class="mt-4 grid gap-4 sm:grid-cols-2"
            >
                <div class="grid gap-2">
                    <Label for="alias">Alias personalizado (opcional)</Label>
                    <div
                        class="flex h-9 items-center overflow-hidden rounded-md border bg-transparent shadow-xs focus-within:ring-[3px] focus-within:ring-ring/50 dark:bg-input/30"
                    >
                        <span
                            class="shrink-0 border-r bg-muted px-2 text-sm text-muted-foreground"
                            >{{ shortDomain }}/</span
                        >
                        <input
                            id="alias"
                            name="alias"
                            type="text"
                            autocomplete="off"
                            :minlength="aliasRules.min"
                            :maxlength="aliasRules.max"
                            placeholder="mi-link"
                            class="h-full min-w-0 flex-1 bg-transparent px-2 text-sm outline-none"
                        />
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Letras, números, guiones y guiones bajos.
                    </p>
                    <InputError :message="errors.alias" />
                </div>
                <div class="grid gap-2">
                    <Label for="expires_at">Vence el (opcional)</Label>
                    <Input
                        id="expires_at"
                        name="expires_at"
                        type="datetime-local"
                        :min="minDate"
                    />
                    <p class="text-xs text-muted-foreground">
                        Después de esa fecha el link deja de funcionar.
                    </p>
                    <InputError :message="errors.expires_at" />
                </div>
            </div>
        </Form>

        <div
            v-if="created"
            class="rounded-2xl border border-primary/30 bg-primary/5 p-4 md:p-6"
            data-test="created-link"
        >
            <p class="text-sm text-muted-foreground">Tu link corto</p>
            <div
                class="mt-1 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <a
                    :href="created.short_url"
                    target="_blank"
                    rel="noopener"
                    class="min-w-0 truncate text-2xl font-semibold text-primary hover:underline"
                    >{{ displayUrl(created.short_url) }}</a
                >
                <div class="flex shrink-0 gap-2">
                    <CopyButton :text="created.short_url" />
                    <QrDialog :url="created.short_url" :code="created.code" />
                    <Button variant="outline" as-child>
                        <Link :href="linksShow(created.code)">
                            <BarChart3 class="size-4" /> Estadísticas
                        </Link>
                    </Button>
                </div>
            </div>
            <p
                class="mt-3 truncate text-sm text-muted-foreground"
                :title="created.original_url"
            >
                → {{ created.original_url }}
            </p>
        </div>

        <section v-if="recentLinks.length">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-medium">Tus últimos links</h2>
                <Link
                    :href="linksIndex()"
                    class="text-sm text-muted-foreground hover:text-foreground"
                    >Ver todos</Link
                >
            </div>
            <ul class="divide-y rounded-2xl border bg-card">
                <li
                    v-for="link in recentLinks"
                    :key="link.id"
                    class="flex items-center gap-3 p-3 md:px-4"
                >
                    <div class="min-w-0 flex-1">
                        <Link
                            :href="linksShow(link.code)"
                            class="block truncate font-medium hover:underline"
                            >{{ displayUrl(link.short_url) }}</Link
                        >
                        <p
                            class="truncate text-sm text-muted-foreground"
                            :title="link.original_url"
                        >
                            {{ link.original_url }}
                        </p>
                    </div>
                    <span
                        class="hidden shrink-0 items-center gap-1 text-sm text-muted-foreground sm:inline-flex"
                        :title="`${link.clicks_count} clics`"
                    >
                        <MousePointerClick class="size-4" />
                        {{ link.clicks_count }}
                    </span>
                    <CopyButton
                        :text="link.short_url"
                        size="icon-sm"
                        variant="ghost"
                    />
                </li>
            </ul>
        </section>
    </div>
</template>
