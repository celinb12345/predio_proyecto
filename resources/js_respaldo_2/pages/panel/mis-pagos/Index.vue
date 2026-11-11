<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';

interface Cuota {
    nro_cuota: number;
    monto: number;
    vencimiento: string;
    pagada_el: string | null;
    estado: string;
    nro_comprobante: string | null;
}

interface Reserva {
    id: number;
    referencia: string;
    predios: string;
    estado: string;
    tipo_prestamo: string | null;
    monto_total: number;
    forma_pago: string | null;
    cuotas: Cuota[];
}

defineProps<{
    usuario: { name: string; rol: string };
    reservas: Reserva[];
    beneficios: { descripcion: string; fecha: string }[];
    sanciones: { motivo: string; fecha: string; estado: string }[];
}>();

const etiquetasReserva: Record<string, string> = {
    pendiente: 'Pendiente de pago',
    confirmada: 'Confirmada',
    finalizada: 'Finalizada',
    cancelada: 'Cancelada',
};

const etiquetasCuota: Record<string, string> = {
    pendiente: 'Pendiente',
    pagada: 'Pagada',
    vencida: 'Vencida',
};

const clasesCuota: Record<string, string> = {
    pendiente: 'bg-amber-100 text-amber-800',
    pagada: 'bg-green-100 text-green-800',
    vencida: 'bg-red-100 text-red-800',
};

const etiquetasSancion: Record<string, string> = {
    pendiente: 'Pendiente',
    cumplida: 'Cumplida',
    anulada: 'Anulada',
};

function bs(valor: number): string {
    return `Bs ${valor.toFixed(2)}`;
}

function formaPago(valor: string | null): string {
    if (valor === 'contado') return 'Pago al contado';
    if (valor === 'credito_2_cuotas') return 'Pago a crédito en 2 cuotas';
    return '';
}
</script>

<template>
    <PanelLayout titulo="Mis pagos" :usuario="usuario">
        <p class="mb-6 text-sm text-texto-suave">
            Los pagos se realizan en caja de Secretaría General. Aquí puedes revisar tus cuotas y sus vencimientos.
        </p>

        <section class="mb-10">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Mis reservas</h2>

            <div v-if="reservas.length" class="space-y-4">
                <article
                    v-for="r in reservas"
                    :key="r.id"
                    class="rounded-xl bg-superficie p-5 text-texto ring-1 ring-borde"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold">{{ r.referencia }}</p>
                            <p class="text-sm text-texto-suave">{{ r.predios || 'Sin predios asignados' }}</p>
                        </div>
                        <span class="rounded-full bg-fondo px-3 py-1 text-xs font-semibold text-texto-suave">
                            {{ etiquetasReserva[r.estado] ?? r.estado }}
                        </span>
                    </div>

                    <p class="mt-3 text-sm">
                        <span class="font-medium">{{ r.tipo_prestamo === 'gratuito' ? 'Préstamo gratuito' : 'Préstamo cobrado' }}</span>
                        <span v-if="r.tipo_prestamo !== 'gratuito'"> · Total: {{ bs(r.monto_total) }}</span>
                        <span v-if="formaPago(r.forma_pago)"> · {{ formaPago(r.forma_pago) }}</span>
                    </p>

                    <ul v-if="r.cuotas.length" class="mt-4 divide-y divide-borde rounded-lg ring-1 ring-borde">
                        <li
                            v-for="c in r.cuotas"
                            :key="c.nro_cuota"
                            class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm"
                        >
                            <div>
                                <p class="font-medium">Cuota {{ c.nro_cuota }} · {{ bs(c.monto) }}</p>
                                <p class="text-texto-suave">
                                    Vence: {{ c.vencimiento }}
                                    <span v-if="c.pagada_el"> · Pagada el {{ c.pagada_el }}</span>
                                </p>
                                <p v-if="c.nro_comprobante" class="text-texto-suave">Comprobante: {{ c.nro_comprobante }}</p>
                            </div>
                            <span
                                :class="['rounded-full px-2.5 py-1 text-xs font-semibold', clasesCuota[c.estado] ?? 'bg-fondo text-texto-suave']"
                            >
                                {{ etiquetasCuota[c.estado] ?? c.estado }}
                            </span>
                        </li>
                    </ul>

                    <p v-else-if="r.tipo_prestamo !== 'gratuito'" class="mt-4 text-sm text-texto-suave">
                        Todavía no hay pagos registrados. Puedes pagar en caja de Secretaría General.
                    </p>
                </article>
            </div>
            <p v-else class="text-sm text-texto-suave">Todavía no tienes reservas.</p>
        </section>

        <section class="mb-10">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Mis beneficios</h2>

            <ul v-if="beneficios.length" class="space-y-2">
                <li
                    v-for="(b, i) in beneficios"
                    :key="i"
                    class="rounded-lg bg-superficie px-4 py-3 text-sm text-texto ring-1 ring-borde"
                >
                    <span class="font-medium">{{ b.descripcion }}</span>
                    <span class="text-texto-suave"> · {{ b.fecha }}</span>
                </li>
            </ul>
            <p v-else class="text-sm text-texto-suave">Todavía no tienes beneficios.</p>
        </section>

        <section>
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Mis sanciones</h2>

            <ul v-if="sanciones.length" class="space-y-2">
                <li
                    v-for="(s, i) in sanciones"
                    :key="i"
                    class="rounded-lg bg-superficie px-4 py-3 text-sm text-texto ring-1 ring-borde"
                >
                    <p class="font-medium">{{ s.motivo }}</p>
                    <p class="text-texto-suave">{{ s.fecha }} · {{ etiquetasSancion[s.estado] ?? s.estado }}</p>
                </li>
            </ul>
            <p v-else class="text-sm text-texto-suave">No tienes sanciones registradas.</p>
        </section>
    </PanelLayout>
</template>