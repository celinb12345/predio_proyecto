<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { formulario } from '@/lib/estilos';
import { Link } from '@inertiajs/vue3';
import { ChevronDown, History } from 'lucide-vue-next';
import { ref } from 'vue';

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

interface Grupo {
    clave: string;
    categoria: string;
    orden: number;
    total: number;
    filas: Fila[];
}

defineProps<{
    usuario: { name: string; rol: string };
    grupos: Grupo[];
    total: number;
    filtro: { activo: boolean; predio: string | null; desde: string | null; hasta: string | null };
}>();

const etiquetasReserva: Record<string, string> = {
    pendiente: 'Pendiente',
    confirmada: 'Confirmada',
};

// Mismo relleno que los estados "Aprobada" y "Rechazada", que se ven bien en modo día y noche
const clasesReserva: Record<string, string> = {
    confirmada: 'bg-[var(--secundario)] text-[var(--primario)]',
    pendiente: 'bg-superficie text-[var(--primario)] ring-1 ring-[var(--secundario)]',
};

// Por defecto todos los grupos están recogidos. Solo se abren los que el usuario pulsa.
const abiertos = ref<Record<string, boolean>>({});

function alternar(clave: string): void {
    abiertos.value[clave] = !abiertos.value[clave];
}
</script>

<template>
    <PanelLayout titulo="Liberación de fechas" :usuario="usuario">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <p class="max-w-2xl text-sm text-texto-suave">
                Reservas activas agrupadas por tipo de persona, en orden de jerarquía. Al liberar una fecha, el horario queda disponible para otras solicitudes.
            </p>
            <Link href="/panel/financiera/historial" :class="formulario.botonSecundario">
                <History class="h-4 w-4" />
                Historial de movimientos
            </Link>
        </div>

        <div
            v-if="filtro.activo"
            class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-fondo p-4 text-sm ring-1 ring-borde"
        >
            <p>
                Filtro activo:
                <span class="font-semibold">{{ filtro.predio ?? 'Todos los predios' }}</span>
                <span v-if="filtro.desde"> · del {{ filtro.desde }} al {{ filtro.hasta }}</span>
            </p>
            <Link href="/panel/financiera" class="font-semibold text-[var(--primario)] hover:underline">
                Quitar filtro
            </Link>
        </div>

        <div v-if="grupos.length" class="space-y-6">
            <section
                v-for="grupo in grupos"
                :key="grupo.clave"
                class="overflow-hidden rounded-xl ring-1 ring-borde"
            >
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 bg-superficie px-5 py-4 text-left"
                    :aria-expanded="abiertos[grupo.clave] === true"
                    @click="alternar(grupo.clave)"
                >
                    <span class="flex flex-wrap items-center gap-3">
                        <span class="text-base font-semibold text-[var(--primario)]">{{ grupo.categoria }}</span>
                        <span class="rounded-full bg-fondo px-2.5 py-0.5 text-xs font-semibold text-texto-suave">
                            {{ grupo.total }} {{ grupo.total === 1 ? 'reserva' : 'reservas' }}
                        </span>
                    </span>
                    <ChevronDown
                        class="h-5 w-5 shrink-0 text-texto-suave transition-transform"
                        :class="abiertos[grupo.clave] ? '' : '-rotate-90'"
                    />
                </button>

                <div v-show="abiertos[grupo.clave]" class="overflow-x-auto border-t border-borde">
                    <table class="min-w-full divide-y divide-borde text-sm">
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
                        <tbody class="divide-y divide-borde bg-superficie">
                            <tr v-for="fila in grupo.filas" :key="fila.id">
                                <td class="px-4 py-3 font-medium">{{ fila.predio }}</td>
                                <td class="px-4 py-3">{{ fila.fecha }}</td>
                                <td class="px-4 py-3">{{ fila.horario }}</td>
                                <td class="px-4 py-3">{{ fila.referencia }}</td>
                                <td class="px-4 py-3">{{ fila.solicitante }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="clasesReserva[fila.estado_reserva] ?? 'bg-superficie text-texto-suave ring-1 ring-borde'"
                                    >
                                        {{ etiquetasReserva[fila.estado_reserva] ?? fila.estado_reserva }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="`/panel/financiera/${fila.id}`" class="font-semibold text-[var(--primario)] hover:underline">
                                        Gestionar
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="grupo.total === 0">
                                <td colspan="7" class="px-4 py-6 text-center text-texto-suave">
                                    No hay reservas activas en esta categoría.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <p v-else class="rounded-xl bg-fondo p-8 text-center text-sm text-texto-suave ring-1 ring-borde">
            No hay reservas activas.
        </p>
    </PanelLayout>
</template>