<script setup lang="ts">
import EditarPlanCuotas from '@/components/panel/EditarPlanCuotas.vue';
import PagoQr from '@/components/panel/PagoQr.vue';
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface CuotaVista {
    id: number;
    nro: number;
    monto: string;
    monto_numero: number;
    vencimiento: string;
    vencimiento_iso: string;
    fecha_pago: string | null;
    estado: string;
    nro_comprobante: string | null;
    url: string | null;
    medio_pago: string | null;
    puede_qr: boolean;
    url_generar_qr: string;
}

const props = defineProps<{
    usuario: { name: string; rol: string };
    reserva: {
        id: number;
        referencia: string;
        solicitante: string;
        estado: string;
        monto_total: string;
    };
    pago: { forma_pago: string; fecha: string } | null;
    cuotas: CuotaVista[];
    resumen: { pagado: string; saldo: string };
    puedeRegistrarPago: boolean;
    puedeCobrar: boolean;
    puedeEditarPlan: boolean;
}>();

const campo =
    'mt-1 w-full rounded-lg border border-borde px-3 py-2 text-sm focus:border-[var(--primario)] focus:outline-none';

const etiquetasEstado: Record<string, string> = {
    pendiente: 'Pendiente',
    pagada: 'Pagada',
    vencida: 'Vencida',
};

const clasesEstado: Record<string, string> = {
    pendiente: 'bg-gray-200 text-texto-suave',
    pagada: 'bg-[var(--secundario)] text-[var(--primario)]',
    vencida: 'bg-red-100 text-red-700',
};

const formPago = useForm({
    forma_pago: 'contado',
    medio_pago: 'efectivo',
});

function registrarPago() {
    const destino = formPago.medio_pago === 'qr' ? 'plan-qr' : 'pago';

    formPago.post(`/panel/pagos/${props.reserva.id}/${destino}`, {
        preserveScroll: true,
    });
}

const formCobro = useForm({
    cuota_id: null as number | null,
    nro_comprobante: '',
    comprobante: null as File | null,
});

function alElegirArchivo(evento: Event) {
    formCobro.comprobante = (evento.target as HTMLInputElement).files?.[0] ?? null;
}

function cobrar(cuotaId: number) {
    if (!window.confirm('¿Registrar el cobro de esta cuota en caja?')) {
        return;
    }

    formCobro.cuota_id = cuotaId;
    formCobro.post(`/panel/pagos/${props.reserva.id}/cobrar`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => formCobro.reset('nro_comprobante', 'comprobante'),
    });
}

const qrAbiertoId = ref<number | null>(null);

function alternarQr(cuotaId: number) {
    qrAbiertoId.value = qrAbiertoId.value === cuotaId ? null : cuotaId;
}

function alPagarQr() {
    qrAbiertoId.value = null;
    router.reload({ only: ['pago', 'cuotas', 'resumen'] });
}
</script>

<template>
    <PanelLayout :titulo="`Pagos de ${reserva.referencia}`" :usuario="usuario">
        <Link href="/panel/pagos" class="mb-6 inline-block text-sm font-semibold text-[var(--primario)] hover:underline">
            ← Volver a gestión de pagos
        </Link>

        <section class="mb-10 grid gap-3 rounded-xl bg-fondo p-5 text-sm sm:grid-cols-2 xl:grid-cols-3">
            <p><span class="font-semibold">Solicitante:</span> {{ reserva.solicitante }}</p>
            <p><span class="font-semibold">Monto total:</span> Bs. {{ reserva.monto_total }}</p>
            <p><span class="font-semibold">Reserva:</span> {{ reserva.estado }}</p>
            <p v-if="pago"><span class="font-semibold">Forma de pago:</span> {{ pago.forma_pago }}</p>
            <p v-if="pago"><span class="font-semibold">Registrado:</span> {{ pago.fecha }}</p>
            <p><span class="font-semibold">Pagado:</span> Bs. {{ resumen.pagado }}</p>
            <p><span class="font-semibold">Saldo:</span> Bs. {{ resumen.saldo }}</p>
        </section>

        <section v-if="!pago" class="mb-10">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Pago</h2>

            <form
                v-if="puedeRegistrarPago"
                class="grid max-w-xl gap-4 rounded-xl p-5 ring-1 ring-borde"
                @submit.prevent="registrarPago"
            >
                <fieldset class="grid gap-3 text-sm">
                    <legend class="mb-1 font-medium">Forma de pago</legend>
                    <label class="flex items-center gap-2">
                        <input v-model="formPago.forma_pago" type="radio" value="contado" />
                        Contado (se paga completo)
                    </label>
                    <label class="flex items-center gap-2">
                        <input v-model="formPago.forma_pago" type="radio" value="credito_2_cuotas" />
                        Crédito en 2 cuotas
                    </label>
                    <span v-if="formPago.errors.forma_pago" class="text-sm text-red-600">{{ formPago.errors.forma_pago }}</span>
                </fieldset>

                <fieldset class="grid gap-3 text-sm">
                    <legend class="mb-1 font-medium">Medio de pago</legend>
                    <label class="flex items-center gap-2">
                        <input v-model="formPago.medio_pago" type="radio" value="efectivo" />
                        Efectivo (se cobra en caja)
                    </label>
                    <label class="flex items-center gap-2">
                        <input v-model="formPago.medio_pago" type="radio" value="qr" />
                        Código QR (el cliente paga con su app bancaria)
                    </label>
                </fieldset>

                <div>
                    <button
                        type="submit"
                        :disabled="formPago.processing"
                        class="rounded-lg bg-[var(--primario)] px-5 py-2.5 text-sm font-semibold text-white disabled:bg-gray-400"
                    >
                        {{ formPago.medio_pago === 'qr' ? 'Crear plan para pagar con QR' : 'Registrar pago' }}
                    </button>
                </div>
            </form>

            <p v-else class="text-sm text-texto-suave">Esta reserva todavía no tiene pago registrado.</p>
        </section>

        <section class="mb-10">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Cuotas</h2>

            <div v-if="puedeCobrar" class="mb-6 grid max-w-3xl gap-4 rounded-xl p-4 ring-1 ring-borde sm:grid-cols-2">
                <label class="block text-sm font-medium">
                    Número de comprobante (opcional)
                    <input v-model="formCobro.nro_comprobante" type="text" maxlength="50" :class="campo" />
                    <span v-if="formCobro.errors.nro_comprobante" class="text-sm text-red-600">{{ formCobro.errors.nro_comprobante }}</span>
                </label>

                <label class="block text-sm font-medium">
                    Comprobante (PDF, JPG o PNG, opcional)
                    <input type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm" @change="alElegirArchivo" />
                    <span v-if="formCobro.errors.comprobante" class="text-sm text-red-600">{{ formCobro.errors.comprobante }}</span>
                </label>

                <p class="text-sm text-texto-suave sm:col-span-2">
                    Llena estos datos si los tienes y luego presiona <span class="font-semibold">Cobrar</span> en la cuota.
                </p>
                <span v-if="formCobro.errors.cuota_id" class="text-sm text-red-600 sm:col-span-2">{{ formCobro.errors.cuota_id }}</span>
            </div>

            <div v-if="cuotas.length" class="overflow-x-auto rounded-xl ring-1 ring-borde">
                <table class="min-w-full divide-y divide-borde text-sm">
                    <thead class="bg-[var(--primario)] text-left text-white">
                        <tr>
                            <th class="px-4 py-3">Cuota</th>
                            <th class="px-4 py-3">Monto (Bs.)</th>
                            <th class="px-4 py-3">Vencimiento</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Pagada el</th>
                            <th class="px-4 py-3">Comprobante</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-borde bg-superficie">
                        <template v-for="c in cuotas" :key="c.id">
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ c.nro }}</td>
                                <td class="px-4 py-3">{{ c.monto }}</td>
                                <td class="px-4 py-3">{{ c.vencimiento }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="clasesEstado[c.estado]">
                                        {{ etiquetasEstado[c.estado] ?? c.estado }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ c.fecha_pago ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div v-if="c.nro_comprobante">{{ c.nro_comprobante }}</div>
                                    <a v-if="c.url" :href="c.url" target="_blank" class="font-semibold text-[var(--primario)] hover:underline">
                                        Ver archivo
                                    </a>
                                    <span v-if="!c.nro_comprobante && !c.url">—</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <button
                                            v-if="c.puede_qr"
                                            type="button"
                                            class="rounded-lg px-3 py-1.5 text-xs font-semibold text-[var(--primario)] ring-1 ring-[var(--primario)]"
                                            @click="alternarQr(c.id)"
                                        >
                                            {{ qrAbiertoId === c.id ? 'Ocultar QR' : 'Pagar con QR' }}
                                        </button>
                                        <button
                                            v-if="puedeCobrar && c.estado !== 'pagada'"
                                            type="button"
                                            :disabled="formCobro.processing"
                                            class="rounded-lg bg-[var(--primario)] px-3 py-1.5 text-xs font-semibold text-white disabled:bg-gray-400"
                                            @click="cobrar(c.id)"
                                        >
                                            Cobrar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="qrAbiertoId === c.id">
                                <td colspan="7" class="px-4 py-4">
                                    <div class="max-w-md">
                                        <PagoQr :url-generar="c.url_generar_qr" :monto="c.monto_numero" @pagado="alPagarQr" />
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <p v-else class="text-sm text-texto-suave">Todavía no hay cuotas.</p>

            <EditarPlanCuotas
                v-if="puedeEditarPlan && cuotas.length"
                :reserva-id="reserva.id"
                :monto-total="reserva.monto_total"
                :cuotas="cuotas"
            />
        </section>
    </PanelLayout>
</template>