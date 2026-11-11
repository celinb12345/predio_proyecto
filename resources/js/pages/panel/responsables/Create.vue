<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

defineProps<{
    usuario: { name: string; rol: string };
}>();

const campo =
    'mt-1 w-full rounded-lg border border-borde px-3 py-2 text-sm focus:border-[var(--primario)] focus:outline-none';

const form = useForm({
    nombre: '',
    apellido: '',
    cargo: '',
    celular: '',
});

function guardar() {
    form.post('/panel/responsables');
}
</script>

<template>
    <PanelLayout titulo="Nuevo responsable" :usuario="usuario">
        <Link href="/panel/responsables" class="mb-6 inline-block text-sm font-semibold text-[var(--primario)] hover:underline">
            ← Volver a responsables
        </Link>

        <form class="grid max-w-3xl gap-5 sm:grid-cols-2" @submit.prevent="guardar">
            <label class="block text-sm font-medium">
                Nombre
                <input v-model="form.nombre" :class="campo" />
                <span v-if="form.errors.nombre" class="text-sm text-red-600">{{ form.errors.nombre }}</span>
            </label>

            <label class="block text-sm font-medium">
                Apellido
                <input v-model="form.apellido" :class="campo" />
                <span v-if="form.errors.apellido" class="text-sm text-red-600">{{ form.errors.apellido }}</span>
            </label>

            <label class="block text-sm font-medium">
                Cargo (opcional)
                <input v-model="form.cargo" :class="campo" placeholder="Ej. Encargado de apertura" />
                <span v-if="form.errors.cargo" class="text-sm text-red-600">{{ form.errors.cargo }}</span>
            </label>

            <label class="block text-sm font-medium">
                Celular (opcional)
                <input v-model="form.celular" :class="campo" />
                <span v-if="form.errors.celular" class="text-sm text-red-600">{{ form.errors.celular }}</span>
            </label>

            <div class="flex gap-3 sm:col-span-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-[var(--primario)] px-5 py-2.5 text-sm font-semibold text-white disabled:bg-gray-400"
                >
                    Guardar responsable
                </button>
                <Link href="/panel/responsables" class="rounded-lg px-5 py-2.5 text-sm font-semibold text-texto-suave ring-1 ring-borde hover:bg-fondo">
                    Cancelar
                </Link>
            </div>
        </form>
    </PanelLayout>
</template>