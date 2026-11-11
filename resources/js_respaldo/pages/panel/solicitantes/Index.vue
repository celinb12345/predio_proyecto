<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { Link } from '@inertiajs/vue3';

interface FilaSolicitud {
    id: number;
    referencia: string;
    evento: string;
    solicitante: string;
    estado: string;
    fecha: string;
}

interface Enlace {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    usuario: { name: string; rol: string };
    solicitudes: {
        data: FilaSolicitud[];
        links: Enlace[];
        from: number | null;
        to: number | null;
        total: number;
    };
    puedeSolicitar: boolean;
}>();

const etiquetasEstado: Record<string, string> = {
    pendiente: 'Pendiente',
    aprobada: 'Aprobada',
    rechazada: 'Rechazada',
    cancelada: 'Cancelada',
};

const clasesEstado: Record<string, string> = {
    pendiente: 'bg-gray-200 text-gray-700',
    aprobada: 'bg-[var(--secundario)] text-[var(--primario)]',
    rechazada: 'bg-red-100 text-red-700',
    cancelada: 'bg-gray-300 text-gray-600',
};
</script>

<template>
    <PanelLayout titulo="Solicitudes de préstamo" :usuario="usuario">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500">Revisa el estado de tus solicitudes de préstamo.</p>
            <Link
                v-if="puedeSolicitar"
                href="/panel/solicitantes/crear"
                class="rounded-lg bg-[var(--secundario)] px-4 py-2 text-sm font-semibold text-[var(--primario)]"
            >
                Nueva solicitud
            </Link>
        </div>

        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-[var(--primario)] text-left text-white">
                    <tr>
                        <th class="px-4 py-3">Referencia</th>
                        <th class="px-4 py-3">Evento</th>
                        <th class="px-4 py-3">Solicitante</th>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <tr v-for="fila in solicitudes.data" :key="fila.id">
                        <td class="px-4 py-3 font-medium">{{ fila.referencia }}</td>
                        <td class="px-4 py-3">{{ fila.evento }}</td>
                        <td class="px-4 py-3">{{ fila.solicitante }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ fila.fecha }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="clasesEstado[fila.estado]">
                                {{ etiquetasEstado[fila.estado] ?? fila.estado }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="`/panel/solicitantes/${fila.id}`" class="font-semibold text-[var(--primario)] hover:underline">
                                Ver
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="solicitudes.data.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">No hay solicitudes registradas.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav class="mt-6 flex flex-wrap items-center justify-between gap-2 text-sm">
            <p class="text-gray-500">
                Mostrando {{ solicitudes.from ?? 0 }} a {{ solicitudes.to ?? 0 }} de {{ solicitudes.total }}
            </p>
            <div class="flex flex-wrap gap-1">
                <template v-for="enlace in solicitudes.links" :key="enlace.label">
                    <Link
                        v-if="enlace.url"
                        :href="enlace.url"
                        preserve-state
                        class="rounded-md px-3 py-1.5"
                        :class="enlace.active ? 'bg-[var(--primario)] text-white' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50'"
                    >
                        <span v-html="enlace.label" />
                    </Link>
                    <span v-else class="rounded-md px-3 py-1.5 text-gray-400">
                        <span v-html="enlace.label" />
                    </span>
                </template>
            </div>
        </nav>
    </PanelLayout>
</template>