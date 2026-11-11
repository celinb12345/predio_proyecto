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
    inhabilitado: 'bg-fondo text-texto-suave ring-borde',
};

const campoBuscar =
    'block w-full rounded-lg border border-borde bg-superficie px-3.5 py-2.5 text-sm text-texto shadow-sm placeholder:text-texto-suave focus:border-[var(--primario)] focus:outline-none focus:ring-2 focus:ring-[var(--primario)]/20 sm:w-72';
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

        <div class="overflow-hidden rounded-2xl bg-superficie ring-1 ring-borde">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-borde text-sm">
                    <thead class="bg-[var(--primario)] text-left text-xs font-semibold uppercase tracking-wide text-white">
                        <tr>
                            <th class="px-5 py-3">Predio</th>
                            <th class="px-5 py-3">Tipo</th>
                            <th class="px-5 py-3">Responsable</th>
                            <th class="px-5 py-3">Capacidad</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-borde text-texto">
                        <tr v-for="fila in predios.data" :key="fila.id" class="hover:bg-fondo">
                            <td class="px-5 py-4 font-medium">{{ fila.nombre }}</td>
                            <td class="px-5 py-4 text-texto-suave">{{ fila.tipo }}</td>
                            <td class="px-5 py-4 text-texto-suave">{{ fila.responsable }}</td>
                            <td class="px-5 py-4 text-texto-suave">{{ fila.capacidad ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <span
                                    :class="[
                                        'rounded-full px-2.5 py-1 text-xs font-semibold ring-1',
                                        clasesEstado[fila.estado] ?? 'bg-fondo text-texto-suave ring-borde',
                                    ]"
                                >
                                    {{ etiquetasEstado[fila.estado] ?? fila.estado }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-5">
                                    <Link
                                        :href="`/panel/predios/${fila.id}`"
                                        class="font-semibold text-[var(--primario)] hover:underline"
                                    >
                                        Ficha
                                    </Link>
                                    <Link
                                        :href="`/panel/predios/${fila.id}/editar`"
                                        class="font-semibold text-texto-suave hover:underline"
                                    >
                                        Editar
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="predios.data.length === 0">
                            <td colspan="6" class="px-5 py-10 text-center text-texto-suave">
                                No hay predios registrados.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <nav class="mt-6 flex flex-wrap items-center justify-between gap-2 text-sm">
            <p class="text-texto-suave">
                Mostrando {{ predios.from ?? 0 }} a {{ predios.to ?? 0 }} de {{ predios.total }}
            </p>
            <div class="flex flex-wrap gap-1">
                <template v-for="enlace in predios.links" :key="enlace.label">
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