<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout, privacy, terms } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import { store } from '@/routes/terms/accept';

/*
 * «Aceptar y continuar». La muestra el middleware EnsureTermsAccepted a quien
 * no aceptó la versión vigente: las altas con Google y todos cuando se sube la
 * versión. Sin aceptar igual se puede salir o eliminar la cuenta.
 */

defineProps<{
    firstTime: boolean;
    version: string;
}>();

defineOptions({
    layout: {
        title: 'Antes de seguir',
        description: 'Leé y aceptá los términos y la política de privacidad',
    },
});
</script>

<template>
    <Head title="Términos y privacidad" />

    <Form
        v-bind="store.form()"
        class="flex flex-col gap-6"
        v-slot="{ errors, processing }"
    >
        <p class="text-sm text-pretty">
            <template v-if="firstTime"
                >Para usar Zep tenés que aceptar</template
            >
            <template v-else>Actualizamos</template>
            los
            <a
                :href="terms().url"
                target="_blank"
                class="underline underline-offset-4 hover:text-primary"
                >términos y condiciones</a
            >
            y la
            <a
                :href="privacy().url"
                target="_blank"
                class="underline underline-offset-4 hover:text-primary"
                >política de privacidad</a
            >
            (versión del {{ version }}). Explican qué datos guardamos, para qué
            y qué podés hacer con ellos.
        </p>

        <div class="grid gap-1">
            <label
                class="flex min-h-11 cursor-pointer items-start gap-3 text-sm text-pretty"
            >
                <input
                    type="checkbox"
                    name="terms"
                    value="1"
                    required
                    class="mt-0.5 size-5 shrink-0 accent-primary"
                />
                <span
                    >Leí y acepto los términos y la política de
                    privacidad.</span
                >
            </label>
            <InputError :message="errors.terms" />
        </div>

        <Button type="submit" class="w-full" :disabled="processing">
            <Spinner v-if="processing" />
            Aceptar y continuar
        </Button>

        <p class="text-center text-sm text-pretty text-muted-foreground">
            Si no estás de acuerdo podés
            <Link
                :href="logout()"
                as="button"
                class="underline underline-offset-4 hover:text-foreground"
                >salir</Link
            >
            o
            <Link
                :href="editProfile()"
                class="underline underline-offset-4 hover:text-foreground"
                >eliminar tu cuenta</Link
            >.
        </p>
    </Form>
</template>
