<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';

interface Fila {
    id: number;
    fecha: string;
    descripcion: string;
    costo: string;
    predio: string;
    responsable: string;
}

interface Enlace {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    usuario: { name: string; rol: string };
    mantenimientos: {
        data: Fila[];
        links: Enlace[];
        from: number | null;
        to: number | null;
        total: number;
    };
    predios: { id: number; nombre: string }[];
    responsables: { id: number; nombre: string; apellido: string }[];
}>();

const campo =
    'mt-1 w-full rounded-lg border border-borde px-3 py-2 text-sm focus:border-[var(--primario)] focus:outline-none';

function hoyLocal(): string {
    const ahora = new Date();
    ahora.setMinutes(ahora.getMinutes() - ahora.getTimezoneOffset());
    return ahora.toISOString().slice(0, 10);
}

const form = useForm({
    predio_id: '' as number | string,
    responsable_id: '' as number | string,
    fecha: hoyLocal(),
    descripcion: '',
    costo: '',
});

function registrar() {
    form.post('/panel/mantenimiento', {
        preserveScroll: true,
        onSuccess: () => form.reset('descripcion', 'costo'),
    });
}

function eliminar(id: number) {
    if (!window.confirm('¿Eliminar este mantenimiento? Esta acción no se puede deshacer.')) {
        return;
    }

    router.delete(`/panel/mantenimiento/${id}`, { preserveScroll: true });
}
</script>

<template>
    <PanelLayout titulo="Mantenimiento de predios" :usuario="usuario">
        <form
            class="mb-8 grid max-w-4xl gap-4 rounded-xl p-5 ring-1 ring-borde sm:grid-cols-2"
            @submit.prevent="registrar"
        >
            <label class="block text-sm font-medium">
                Predio
                <select v-model="form.predio_id" :class="campo">
                    <option value="" disabled>Selecciona un predio</option>
                    <option v-for="p in predios" :key="p.id" :value="p.id">{{ p.nombre }}</option>
                </select>
                <span v-if="form.errors.predio_id" class="text-sm text-red-600">{{ form.errors.predio_id }}</span>
            </label>

            <label class="block text-sm font-medium">
                Responsable
                <select v-model="form.responsable_id" :class="campo">
                    <option value="" disabled>Selecciona al responsable</option>
                    <option v-for="r in responsables" :key="r.id" :value="r.id">{{ r.apellido }}, {{ r.nombre }}</option>
                </select>
                <span v-if="form.errors.responsable_id" class="text-sm text-red-600">
                    {{ form.errors.responsable_id }}
                </span>
            </label>

            <label class="block text-sm font-medium">
                Fecha
                <input v-model="form.fecha" type="date" :class="campo" />
                <span v-if="form.errors.fecha" class="text-sm text-red-600">{{ form.errors.fecha }}</span>
            </label>

            <label class="block text-sm font-medium">
                Costo (Bs)
                <input v-model="form.costo" type="number" step="0.01" min="0" inputmode="decimal" :class="campo" />
                <span v-if="form.errors.costo" class="text-sm text-red-600">{{ form.errors.costo }}</span>
            </label>

            <label class="block text-sm font-medium sm:col-span-2">
                Descripción del trabajo
                <textarea v-model="form.descripcion" rows="3" :class="campo"></textarea>
                <span v-if="form.errors.descripcion" class="text-sm text-red-600">{{ form.errors.descripcion }}</span>
            </label>

            <div class="sm:col-span-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-lg bg-[var(--primario)] px-5 py-2.5 text-sm font-semibold text-white disabled:bg-gray-400 sm:w-auto"
                >
                    Registrar mantenimiento
                </button>
            </div>
        </form>

        <!-- Vista para celular y tablet -->
        <ul class="space-y-3 md:hidden">
            <li
                v-for="m in mantenimientos.data"
                :key="m.id"
                class="rounded-xl bg-superficie p-4 text-sm text-texto ring-1 ring-borde"
            >
                <div class="flex items-start justify-between gap-3">
                    <p class="font-semibold">{{ m.predio }}</p>
                    <p class="font-semibold text-[var(--primario)]">Bs {{ m.costo }}</p>
                </div>
                <p class="mt-1 text-texto-suave">{{ m.fecha }} · {{ m.responsable }}</p>
                <p class="mt-2">{{ m.descripcion }}</p>
                <button type="button" class="mt-3 font-semibold text-red-600" @click="eliminar(m.id)">
                    Eliminar
                </button>
            </li>
            <li v-if="mantenimientos.data.length === 0" class="text-sm text-texto-suave">
                Todavía no hay mantenimientos registrados.
            </li>
        </ul>

        <!-- Vista para PC -->
        <div class="hidden overflow-x-auto rounded-xl ring-1 ring-borde md:block">
            <table class="min-w-full divide-y divide-borde text-sm">
                <thead class="bg-[var(--primario)] text-left text-white">
                    <tr>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Predio</th>
                        <th class="px-4 py-3">Descripción</th>
                        <th class="px-4 py-3">Responsable</th>
                        <th class="px-4 py-3 text-right">Costo (Bs)</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde bg-superficie text-texto">
                    <tr v-for="m in mantenimientos.data" :key="m.id">
                        <td class="px-4 py-3">{{ m.fecha }}</td>
                        <td class="px-4 py-3 font-medium">{{ m.predio }}</td>
                        <td class="px-4 py-3">{{ m.descripcion }}</td>
                        <td class="px-4 py-3">{{ m.responsable }}</td>
                        <td class="px-4 py-3 text-right">{{ m.costo }}</td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" class="font-semibold text-red-600 hover:underline" @click="eliminar(m.id)">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                    <tr v-if="mantenimientos.data.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-texto-suave">
                            Todavía no hay mantenimientos registrados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav class="mt-6 flex flex-wrap items-center justify-between gap-2 text-sm">
            <p class="text-texto-suave">
                Mostrando {{ mantenimientos.from ?? 0 }} a {{ mantenimientos.to ?? 0 }} de {{ mantenimientos.total }}
            </p>
            <div class="flex flex-wrap gap-1">
                <template v-for="enlace in mantenimientos.links" :key="enlace.label">
                    <Link
                        v-if="enlace.url"
                        :href="enlace.url"
                        preserve-state
                        class="rounded-md px-3 py-1.5"
                        :class="enlace.active ? 'bg-[var(--primario)] text-white' : 'bg-superficie text-texto-suave ring-1 ring-borde hover:bg-fondo'"
                    >
                        <span v-html="enlace.label" />
                    </Link>
                    <span v-else class="rounded-md px-3 py-1.5 text-texto-suave">
                        <span v-html="enlace.label" />
                    </span>
                </template>
            </div>
        </nav>
    </PanelLayout>
</template>