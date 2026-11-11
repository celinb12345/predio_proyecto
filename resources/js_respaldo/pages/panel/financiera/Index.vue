<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { Link } from '@inertiajs/vue3';

interface Fila {
    id: number;
    predio: string;
    fecha: string;
    horario: string;
    referencia: string;
    solicitante: string;
    evento: string;
    estado_reserva: string;
}

interface Enlace {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    usuario: { name: string; rol: string };
    filas: {
        data: Fila[];
        links: Enlace[];
        from: number | null;
        to: number | null;
        total: number;
    };
}>();

const etiquetasReserva: Record<string, string> = {
    pendiente: 'Pendiente',
    confirmada: 'Confirmada',
};
</script>

<template>
    <PanelLayout titulo="Liberación de fechas" :usuario="usuario">
        <p class="mb-6 text-sm text-gray-600">
            Reservas activas que puedes liberar o reprogramar. Al liberar una fecha, el horario queda disponible para otras solicitudes.
        </p>

        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-[var(--primario)] text-left text-white">
                    <tr>
                        <th class="px-4 py-3">Predio</th>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Horario</th>
                        <th class="px-4 py-3">Solicitud</th>
                        <th class="px-4 py-3">Solicitante</th>
                        <th class="px-4 py-3">Reserva</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <tr v-for="fila in filas.data" :key="fila.id">
                        <td class="px-4 py-3 font-medium">{{ fila.predio }}</td>
                        <td class="px-4 py-3">{{ fila.fecha }}</td>
                        <td class="px-4 py-3">{{ fila.horario }}</td>
                        <td class="px-4 py-3">{{ fila.referencia }}</td>
                        <td class="px-4 py-3">{{ fila.solicitante }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                {{ etiquetasReserva[fila.estado_reserva] ?? fila.estado_reserva }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="`/panel/financiera/${fila.id}`" class="font-semibold text-[var(--primario)] hover:underline">
                                Gestionar
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="filas.data.length === 0">
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">No hay reservas activas.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav class="mt-6 flex flex-wrap items-center justify-between gap-2 text-sm">
            <p class="text-gray-500">
                Mostrando {{ filas.from ?? 0 }} a {{ filas.to ?? 0 }} de {{ filas.total }}
            </p>
            <div class="flex flex-wrap gap-1">
                <template v-for="enlace in filas.links" :key="enlace.label">
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