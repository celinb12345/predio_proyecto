<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { Link } from '@inertiajs/vue3';

interface Fila {
    id: number;
    referencia: string;
    solicitante: string;
    monto_total: string;
    forma: string;
    pagado: string;
    vencidas: number;
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
</script>

<template>
    <PanelLayout titulo="Gestión de pagos" :usuario="usuario">
        <p class="mb-6 text-sm text-texto-suave">
            Reservas con préstamo cobrado y el estado de sus pagos.
        </p>

        <div class="overflow-x-auto rounded-xl ring-1 ring-borde">
            <table class="min-w-full divide-y divide-borde text-sm">
                <thead class="bg-[var(--primario)] text-left text-white">
                    <tr>
                        <th class="px-4 py-3">Solicitud</th>
                        <th class="px-4 py-3">Solicitante</th>
                        <th class="px-4 py-3">Monto (Bs.)</th>
                        <th class="px-4 py-3">Forma</th>
                        <th class="px-4 py-3">Pagado (Bs.)</th>
                        <th class="px-4 py-3">Vencidas</th>
                        <th class="px-4 py-3">Reserva</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde bg-superficie">
                    <tr v-for="fila in filas.data" :key="fila.id">
                        <td class="px-4 py-3 font-medium">{{ fila.referencia }}</td>
                        <td class="px-4 py-3">{{ fila.solicitante }}</td>
                        <td class="px-4 py-3">{{ fila.monto_total }}</td>
                        <td class="px-4 py-3">{{ fila.forma }}</td>
                        <td class="px-4 py-3">{{ fila.pagado }}</td>
                        <td class="px-4 py-3">
                            <span
                                v-if="fila.vencidas > 0"
                                class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700"
                            >
                                {{ fila.vencidas }}
                            </span>
                            <span v-else class="text-texto-suave">0</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-texto-suave">
                                {{ fila.estado_reserva }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="`/panel/pagos/${fila.id}`" class="font-semibold text-[var(--primario)] hover:underline">
                                Ver
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="filas.data.length === 0">
                        <td colspan="8" class="px-4 py-8 text-center text-texto-suave">No hay reservas con cobro.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav class="mt-6 flex flex-wrap items-center justify-between gap-2 text-sm">
            <p class="text-texto-suave">
                Mostrando {{ filas.from ?? 0 }} a {{ filas.to ?? 0 }} de {{ filas.total }}
            </p>
            <div class="flex flex-wrap gap-1">
                <template v-for="enlace in filas.links" :key="enlace.label">
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