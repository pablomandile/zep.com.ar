<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';
import { computed, ref } from 'vue';
import LinkController from '@/actions/App/Http/Controllers/LinkController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { ShortLink } from '@/types';

const props = defineProps<{ link: ShortLink }>();

const open = ref(false);

/** ISO (UTC) → valor para un input datetime-local en la hora del navegador. */
function toLocalInput(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const d = new Date(iso);
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());

    return d.toISOString().slice(0, 16);
}

const expiresLocal = computed(() => toLocalInput(props.link.expires_at));

function transform(data: Record<string, unknown>) {
    const value = data.expires_at;

    return {
        ...data,
        is_active: data.is_active === 'on' || data.is_active === true,
        expires_at:
            typeof value === 'string' && value !== ''
                ? new Date(value).toISOString()
                : null,
    };
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button
                type="button"
                size="icon-sm"
                variant="ghost"
                aria-label="Editar"
                title="Editar"
            >
                <Pencil class="size-4" />
            </Button>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Editar /{{ link.code }}</DialogTitle>
                <DialogDescription>
                    El link corto no cambia: podés cambiar a dónde lleva, su
                    vencimiento o desactivarlo.
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="LinkController.update.form(link.code)"
                :transform="transform"
                :options="{ preserveScroll: true }"
                class="grid gap-4"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <div class="grid gap-2">
                    <Label :for="`url-${link.id}`">Destino</Label>
                    <Input
                        :id="`url-${link.id}`"
                        name="url"
                        type="text"
                        inputmode="url"
                        required
                        :default-value="link.original_url"
                    />
                    <InputError :message="errors.url" />
                </div>

                <div class="grid gap-2">
                    <Label :for="`expires-${link.id}`">Vence el</Label>
                    <Input
                        :id="`expires-${link.id}`"
                        name="expires_at"
                        type="datetime-local"
                        :default-value="expiresLocal"
                    />
                    <p class="text-xs text-muted-foreground">
                        Dejalo vacío para que no venza nunca.
                    </p>
                    <InputError :message="errors.expires_at" />
                </div>

                <Label class="flex items-center gap-2 font-normal">
                    <Checkbox
                        name="is_active"
                        :default-value="link.is_active"
                    />
                    Activo (si lo desactivás, el link deja de redirigir)
                </Label>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancelar</Button
                    >
                    <Button type="submit" :disabled="processing">
                        <Spinner v-if="processing" />
                        Guardar
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
