<script setup lang="ts">
import PagoQr from '@/components/panel/PagoQr.vue';
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Cuota {
    id: number;
    nro_cuota: number;
    monto: number;
    vencimiento: string;
    pagada_el: string | null;
    estado: string;
    nro_comprobante: string | null;
    puede_qr: boolean;
    url_generar_qr: string;
}

interface Reserva {
    id: number;
    referencia: string;
    predios: string;
    estado: string;
    tipo_prestamo: string | null;
    monto_total: number;
    forma_pago: string | null;
    puede_plan_qr: boolean;
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

const qrAbiertoId = ref<number | null>(null);

const formPlan = useForm({
    forma_pago: 'contado',
});

function bs(valor: number): string {
    return `Bs ${valor.toFixed(2)}`;
}

function formaPago(valor: string | null): string {
    if (valor === 'contado') return 'Pago al contado';
    if (valor === 'credito_2_cuotas') return 'Pago a crédito en 2 cuotas';
    return '';
}

function crearPlan(reservaId: number, forma: string) {
    formPlan.forma_pago = forma;
    formPlan.post(`/panel/mis-pagos/${reservaId}/plan-qr`, {
        preserveScroll: true,
    });
}

function alternarQr(cuotaId: number) {
    qrAbiertoId.value = qrAbiertoId.value === cuotaId ? null : cuotaId;
}

function alPagarQr() {
    qrAbiertoId.value = null;
    router.reload({ only: ['reservas'] });
}
</script>

<template>
    <PanelLayout titulo="Mis pagos" :usuario="usuario">
        <p class="mb-6 text-sm text-texto-suave">
            Puedes pagar tus cuotas con código QR desde esta pantalla o en caja de Secretaría General.
        </p>

        <section class="mb-10">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Mis reservas</h2>

            <p v-if="formPlan.errors.forma_pago" class="mb-4 text-sm text-red-600">
                {{ formPlan.errors.forma_pago }}
            </p>

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

                    <div v-if="r.puede_plan_qr" class="mt-4 rounded-lg bg-fondo p-4 text-sm">
                        <p class="mb-3 font-medium">Elige cómo pagar con QR:</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                :disabled="formPlan.processing"
                                class="rounded-lg bg-[var(--primario)] px-4 py-2 text-sm font-semibold text-white disabled:bg-gray-400"
                                @click="crearPlan(r.id, 'contado')"
                            >
                                Pagar al contado · {{ bs(r.monto_total) }}
                            </button>
                            <button
                                type="button"
                                :disabled="formPlan.processing"
                                class="rounded-lg px-4 py-2 text-sm font-semibold text-[var(--primario)] ring-1 ring-[var(--primario)] disabled:text-gray-400"
                                @click="crearPlan(r.id, 'credito_2_cuotas')"
                            >
                                Pagar en 2 cuotas
                            </button>
                        </div>
                    </div>

                    <ul v-if="r.cuotas.length" class="mt-4 divide-y divide-borde rounded-lg ring-1 ring-borde">
                        <template v-for="c in r.cuotas" :key="c.id">
                            <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm">
                                <div>
                                    <p class="font-medium">Cuota {{ c.nro_cuota }} · {{ bs(c.monto) }}</p>
                                    <p class="text-texto-suave">
                                        Vence: {{ c.vencimiento }}
                                        <span v-if="c.pagada_el"> · Pagada el {{ c.pagada_el }}</span>
                                    </p>
                                    <p v-if="c.nro_comprobante" class="text-texto-suave">Comprobante: {{ c.nro_comprobante }}</p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        v-if="c.puede_qr"
                                        type="button"
                                        class="rounded-lg px-3 py-1.5 text-xs font-semibold text-[var(--primario)] ring-1 ring-[var(--primario)]"
                                        @click="alternarQr(c.id)"
                                    >
                                        {{ qrAbiertoId === c.id ? 'Ocultar QR' : 'Pagar con QR' }}
                                    </button>
                                    <span
                                        :class="['rounded-full px-2.5 py-1 text-xs font-semibold', clasesCuota[c.estado] ?? 'bg-fondo text-texto-suave']"
                                    >
                                        {{ etiquetasCuota[c.estado] ?? c.estado }}
                                    </span>
                                </div>
                            </li>

                            <li v-if="qrAbiertoId === c.id" class="px-4 py-4">
                                <div class="max-w-md">
                                    <PagoQr :url-generar="c.url_generar_qr" :monto="c.monto" @pagado="alPagarQr" />
                                </div>
                            </li>
                        </template>
                    </ul>

                    <p v-else-if="r.tipo_prestamo !== 'gratuito'" class="mt-4 text-sm text-texto-suave">
                        Todavía no hay un plan de pago para esta reserva. Elige una opción arriba o acude a caja.
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