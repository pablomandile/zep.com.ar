<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { KeyRound, Plus, TriangleAlert } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import CopyButton from '@/components/links/CopyButton.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { destroy, index, store } from '@/routes/api-tokens';

type ApiToken = {
    id: number;
    name: string;
    abilities: string[];
    last_used_at: string | null;
    created_at: string;
};

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tokens de API', href: index() }],
    },
});

const props = defineProps<{
    tokens: ApiToken[];
    abilities: string[];
    apiBase: string;
}>();

const page = usePage();
const plainToken = computed(() => page.flash.plainToken ?? null);

const abilityLabels: Record<string, string> = {
    'links:read': 'Leer links y estadísticas',
    'links:write': 'Crear, editar y borrar links',
};

const form = useForm({
    name: '',
    abilities: [...props.abilities],
});

function setAbility(ability: string, checked: boolean | 'indeterminate') {
    form.abilities =
        checked === true
            ? [...new Set([...form.abilities, ability])]
            : form.abilities.filter((a) => a !== ability);
}

function createToken() {
    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

const toRevoke = ref<ApiToken | null>(null);
const revokeOpen = ref(false);
const revoking = ref(false);

function askRevoke(token: ApiToken) {
    toRevoke.value = token;
    revokeOpen.value = true;
}

function confirmRevoke() {
    const token = toRevoke.value;

    if (!token) {
        return;
    }

    router.delete(destroy.url(token.id), {
        preserveScroll: true,
        onStart: () => (revoking.value = true),
        onFinish: () => {
            revoking.value = false;
            revokeOpen.value = false;
        },
    });
}

function formatDateTime(value: string) {
    return new Date(value).toLocaleString('es-AR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

const headersSample = `Authorization: Bearer TU_TOKEN
Accept: application/json`;

const endpoints = computed(() => [
    {
        method: 'POST',
        path: '/links',
        body: '{ url, alias?, expires_at? }',
        note: '201 al crear; 200 si ya tenías un link para la misma URL.',
    },
    {
        method: 'GET',
        path: '/links',
        body: '?search=&per_page=',
        note: 'Tus links, paginados.',
    },
    { method: 'GET', path: '/links/{code}', body: '', note: 'Un link.' },
    {
        method: 'PATCH',
        path: '/links/{code}',
        body: '{ url?, is_active?, expires_at? }',
        note: 'Modifica un link.',
    },
    {
        method: 'DELETE',
        path: '/links/{code}',
        body: '',
        note: 'Borra el link. Responde 204.',
    },
    {
        method: 'GET',
        path: '/links/{code}/stats',
        body: '?from=&to= (YYYY-MM-DD)',
        note: 'Estadísticas de clics.',
    },
]);

const curlSample = computed(
    () => `curl -X POST ${props.apiBase}/links \\
  -H "Authorization: Bearer TU_TOKEN" \\
  -H "Accept: application/json" \\
  -H "Content-Type: application/json" \\
  -d '{"url": "https://ejemplo.com/una/url/muy/larga"}'`,
);

const phpSample = computed(
    () => `use Illuminate\\Support\\Facades\\Http;

$shortUrl = Http::withToken(env('ZEP_TOKEN'))
    ->acceptJson()
    ->post('${props.apiBase}/links', ['url' => 'https://ejemplo.com/una/url/muy/larga'])
    ->json('data.short_url');`,
);

const responseSample = `{
  "data": {
    "code": "aB3",
    "short_url": "https://zep.com.ar/aB3",
    "original_url": "https://ejemplo.com/una/url/muy/larga",
    "is_custom": false,
    "is_active": true,
    "is_expired": false,
    "expires_at": null,
    "clicks": 0,
    "last_clicked_at": null,
    "created_at": "2026-10-27T12:00:00.000000Z",
    "updated_at": "2026-10-27T12:00:00.000000Z"
  }
}`;
</script>

<template>
    <Head title="Tokens de API" />

    <h1 class="sr-only">Tokens de API</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Tokens de API"
            description="Creá tokens para acortar links y consultar estadísticas desde tus propias apps o scripts"
        />

        <form class="space-y-5" @submit.prevent="createToken">
            <div class="grid gap-2">
                <Label for="token-name">Nombre</Label>
                <Input
                    id="token-name"
                    v-model="form.name"
                    required
                    maxlength="100"
                    autocomplete="off"
                    placeholder="Por ejemplo: Mi sitio web"
                />
                <InputError :message="form.errors.name" />
            </div>

            <fieldset class="grid gap-3">
                <legend class="mb-2 text-sm font-medium">Permisos</legend>
                <Label
                    v-for="ability in abilities"
                    :key="ability"
                    class="flex items-start gap-3 font-normal"
                >
                    <Checkbox
                        :model-value="form.abilities.includes(ability)"
                        @update:model-value="
                            (checked) => setAbility(ability, checked)
                        "
                    />
                    <span class="grid gap-0.5">
                        <span>{{ abilityLabels[ability] ?? ability }}</span>
                        <code class="text-xs text-muted-foreground">{{
                            ability
                        }}</code>
                    </span>
                </Label>
                <InputError
                    :message="
                        form.errors.abilities ??
                        (form.errors as Record<string, string>)['abilities.0']
                    "
                />
            </fieldset>

            <Button
                type="submit"
                :disabled="form.processing || form.abilities.length === 0"
                data-test="create-token-button"
            >
                <Spinner v-if="form.processing" />
                <Plus v-else class="size-4" />
                Crear token
            </Button>
        </form>

        <div
            v-if="plainToken"
            class="space-y-3 rounded-xl border border-primary/30 bg-primary/5 p-4"
            data-test="plain-token"
        >
            <p class="flex items-center gap-2 font-medium">
                <KeyRound class="size-4" /> Tu nuevo token
            </p>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <code
                    class="min-w-0 flex-1 rounded-md border bg-background px-3 py-2 font-mono text-sm break-all select-all"
                    >{{ plainToken }}</code
                >
                <CopyButton
                    :text="plainToken"
                    label="Copiar token"
                    success-message="Token copiado al portapapeles."
                />
            </div>
            <p
                class="flex items-start gap-2 text-sm text-amber-700 dark:text-amber-300"
            >
                <TriangleAlert class="mt-0.5 size-4 shrink-0" />
                Copialo y guardalo en un lugar seguro: por seguridad no lo vamos
                a volver a mostrar.
            </p>
        </div>
    </div>

    <div class="flex flex-col space-y-4">
        <Heading
            variant="small"
            title="Tus tokens"
            description="Revocá los que ya no uses: dejan de funcionar al instante"
        />

        <p
            v-if="tokens.length === 0"
            class="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground"
        >
            Todavía no creaste ningún token.
        </p>

        <ul v-else class="divide-y rounded-xl border">
            <li
                v-for="token in tokens"
                :key="token.id"
                class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center"
            >
                <div class="min-w-0 flex-1 space-y-1.5">
                    <p class="truncate font-medium">{{ token.name }}</p>
                    <div class="flex flex-wrap gap-1">
                        <Badge
                            v-for="ability in token.abilities"
                            :key="ability"
                            variant="secondary"
                            :title="abilityLabels[ability]"
                            >{{ ability }}</Badge
                        >
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Creado el {{ formatDateTime(token.created_at) }} ·
                        Último uso:
                        {{
                            token.last_used_at
                                ? formatDateTime(token.last_used_at)
                                : 'Nunca'
                        }}
                    </p>
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    class="shrink-0 self-start text-destructive hover:text-destructive sm:self-center"
                    @click="askRevoke(token)"
                >
                    Revocar
                </Button>
            </li>
        </ul>
    </div>

    <div class="flex flex-col space-y-5">
        <Heading
            variant="small"
            title="Cómo usar la API"
            description="Una API REST simple que responde JSON"
        />

        <div class="space-y-2 text-sm">
            <p>
                Mandá el token en cada pedido, junto con el header
                <code class="font-mono">Accept</code>:
            </p>
            <div class="relative">
                <pre
                    class="overflow-x-auto rounded-lg border bg-muted p-3 pr-12 font-mono text-xs"
                    >{{ headersSample }}</pre>
                <CopyButton
                    success-message="Copiado al portapapeles."
                    :text="headersSample"
                    size="icon-sm"
                    variant="ghost"
                    class="absolute top-1.5 right-1.5"
                />
            </div>
            <p class="text-muted-foreground">
                Base: <code class="font-mono break-all">{{ apiBase }}</code> ·
                Límite: 60 pedidos por minuto por token.
            </p>
        </div>

        <div class="space-y-2 text-sm">
            <p class="font-medium">Endpoints</p>
            <ul class="divide-y rounded-lg border">
                <li
                    v-for="e in endpoints"
                    :key="e.method + e.path"
                    class="space-y-0.5 p-3"
                >
                    <p class="flex flex-wrap items-baseline gap-x-2 font-mono">
                        <span class="w-14 shrink-0 text-xs font-semibold">{{
                            e.method
                        }}</span>
                        <span class="break-all">{{ e.path }}</span>
                        <span
                            v-if="e.body"
                            class="text-xs break-all text-muted-foreground"
                            >{{ e.body }}</span
                        >
                    </p>
                    <p class="text-muted-foreground">{{ e.note }}</p>
                </li>
            </ul>
            <p class="text-muted-foreground">
                <code class="font-mono">GET</code> requiere el permiso
                <code class="font-mono">links:read</code>; crear, modificar y
                borrar requieren <code class="font-mono">links:write</code>.
            </p>
        </div>

        <div class="space-y-2 text-sm">
            <p class="font-medium">Ejemplo con curl</p>
            <div class="relative">
                <pre
                    class="overflow-x-auto rounded-lg border bg-muted p-3 pr-12 font-mono text-xs"
                    >{{ curlSample }}</pre>
                <CopyButton
                    success-message="Copiado al portapapeles."
                    :text="curlSample"
                    size="icon-sm"
                    variant="ghost"
                    class="absolute top-1.5 right-1.5"
                />
            </div>
        </div>

        <div class="space-y-2 text-sm">
            <p class="font-medium">Ejemplo en PHP (Laravel)</p>
            <div class="relative">
                <pre
                    class="overflow-x-auto rounded-lg border bg-muted p-3 pr-12 font-mono text-xs"
                    >{{ phpSample }}</pre>
                <CopyButton
                    success-message="Copiado al portapapeles."
                    :text="phpSample"
                    size="icon-sm"
                    variant="ghost"
                    class="absolute top-1.5 right-1.5"
                />
            </div>
        </div>

        <div class="space-y-2 text-sm">
            <p class="font-medium">Respuesta</p>
            <div class="relative">
                <pre
                    class="overflow-x-auto rounded-lg border bg-muted p-3 pr-12 font-mono text-xs"
                    >{{ responseSample }}</pre>
                <CopyButton
                    success-message="Copiado al portapapeles."
                    :text="responseSample"
                    size="icon-sm"
                    variant="ghost"
                    class="absolute top-1.5 right-1.5"
                />
            </div>
            <p class="text-muted-foreground">
                Los errores de validación vuelven con código 422 y el detalle en
                <code class="font-mono">errors</code>.
            </p>
        </div>
    </div>

    <Dialog v-model:open="revokeOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle
                    >¿Revocar el token «{{ toRevoke?.name }}»?</DialogTitle
                >
                <DialogDescription>
                    Las apps que lo usen van a dejar de poder conectarse a Zep
                    al instante. Esta acción no se puede deshacer.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancelar</Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    :disabled="revoking"
                    data-test="confirm-revoke-token"
                    @click="confirmRevoke"
                >
                    <Spinner v-if="revoking" />
                    Revocar token
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
