<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { formulario } from '@/lib/estilos';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface FilaPredio {
    id: number;
    nombre: string;
    tipo: string;
    responsable: string;
    capacidad: number | null;
    estado: string;
}

interface Enlace {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    usuario: { name: string; rol: string };
    predios: {
        data: FilaPredio[];
        links: Enlace[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filtros: { buscar: string };
}>();

const buscar = ref(props.filtros.buscar);

function buscarPredios() {
    router.get('/panel/predios', { buscar: buscar.value }, { preserveState: true, replace: true });
}

const etiquetasEstado: Record<string, string> = {
    disponible: 'Disponible',
    mantenimiento: 'En mantenimiento',
    inhabilitado: 'Inhabilitado',
};

const clasesEstado: Record<string, string> = {
    disponible: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    mantenimiento: 'bg-amber-50 text-amber-800 ring-amber-200',
    inhabilitado: 'bg-slate-100 text-slate-600 ring-slate-200',
};

const campoBuscar =
    'block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-[#1e3a8a] focus:outline-none focus:ring-2 focus:ring-[#1e3a8a]/20 sm:w-72';
</script>

<template>
    <PanelLayout titulo="Gestión de predios" :usuario="usuario">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <form class="flex gap-2" role="search" @submit.prevent="buscarPredios">
                <input v-model="buscar" type="search" placeholder="Buscar por nombre" :class="campoBuscar" />
                <button type="submit" :class="formulario.botonSecundario">Buscar</button>
            </form>

            <div class="flex gap-2">
                <Link href="/panel/responsables" :class="formulario.botonSecundario">Responsables</Link>
                <Link href="/panel/predios/crear" :class="formulario.botonPrimario">Nuevo predio</Link>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Predio</th>
                            <th class="px-5 py-3">Tipo</th>
                            <th class="px-5 py-3">Responsable</th>
                            <th class="px-5 py-3">Capacidad</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-900">
                        <tr v-for="fila in predios.data" :key="fila.id" class="hover:bg-slate-50">
                            <td class="px-5 py-4 font-medium">{{ fila.nombre }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ fila.tipo }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ fila.responsable }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ fila.capacidad ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <span
                                    :class="[
                                        'rounded-full px-2.5 py-1 text-xs font-semibold ring-1',
                                        clasesEstado[fila.estado] ?? 'bg-slate-100 text-slate-600 ring-slate-200',
                                    ]"
                                >
                                    {{ etiquetasEstado[fila.estado] ?? fila.estado }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-5">
                                    <Link
                                        :href="`/panel/predios/${fila.id}`"
                                        class="font-semibold text-[#1e3a8a] hover:underline"
                                    >
                                        Ficha
                                    </Link>
                                    <Link
                                        :href="`/panel/predios/${fila.id}/editar`"
                                        class="font-semibold text-slate-600 hover:underline"
                                    >
                                        Editar
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="predios.data.length === 0">
                            <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                                No hay predios registrados.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <nav class="mt-6 flex flex-wrap items-center justify-between gap-2 text-sm">
            <p class="text-slate-500">
                Mostrando {{ predios.from ?? 0 }} a {{ predios.to ?? 0 }} de {{ predios.total }}
            </p>
            <div class="flex flex-wrap gap-1">
                <template v-for="enlace in predios.links" :key="enlace.label">
                    <Link
                        v-if="enlace.url"
                        :href="enlace.url"
                        preserve-state
                        class="rounded-md px-3 py-1.5"
                        :class="enlace.active ? 'bg-[#1e3a8a] text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'"
                    >
                        <span v-html="enlace.label" />
                    </Link>
                    <span v-else class="rounded-md px-3 py-1.5 text-slate-400">
                        <span v-html="enlace.label" />
                    </span>
                </template>
            </div>
        </nav>
    </PanelLayout>
</template>