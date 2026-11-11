<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { Link } from '@inertiajs/vue3';

interface Deuda {
    id: number;
    reserva_id: number;
    persona_id: number;
    deudor: string;
    referencia: string;
    nro_cuota: number;
    monto: number;
    vence: string;
    dias: number;
    vencida: boolean;
}

const props = defineProps<{
    usuario: { name: string; rol: string };
    deudas: Deuda[];
    resumen: { personas: number; cuotas: number; monto: number; vencidas: number };
}>();

function formatearFecha(fecha: string): string {
    const [anio, mes, dia] = fecha.split('-');

    return `${dia}/${mes}/${anio}`;
}

function montoTexto(monto: number): string {
    return `Bs ${monto.toFixed(2)}`;
}

function textoDias(n: number): string {
    return n === 1 ? '1 día' : `${n} días`;
}

function situacion(d: Deuda): { texto: string; clases: string } {
    if (d.dias < 0) {
        return { texto: `Vencida hace ${textoDias(Math.abs(d.dias))}`, clases: 'bg-red-50 text-red-700 ring-red-200' };
    }

    if (d.dias === 0) {
        return { texto: 'Vence hoy', clases: 'bg-amber-50 text-amber-800 ring-amber-200' };
    }

    if (d.dias <= 2) {
        return { texto: `Vence en ${textoDias(d.dias)}`, clases: 'bg-amber-50 text-amber-800 ring-amber-200' };
    }

    return { texto: `Vence en ${textoDias(d.dias)}`, clases: 'bg-emerald-50 text-emerald-700 ring-emerald-200' };
}
</script>

<template>
    <PanelLayout titulo="Deudores" :usuario="usuario">
        <div class="mb-6">
            <Link href="/panel/pagos" class="text-sm font-medium text-texto-suave hover:text-[var(--primario)]">
                ← Volver a gestión de pagos
            </Link>
        </div>

        <p class="mb-6 text-sm text-texto-suave">
            Cuotas sin pagar de préstamos cobrados, ordenadas por fecha límite.
        </p>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl bg-fondo p-4 ring-1 ring-borde">
                <p class="text-xs text-texto-suave">Personas con deuda</p>
                <p class="mt-1 text-2xl font-bold">{{ resumen.personas }}</p>
            </div>
            <div class="rounded-xl bg-fondo p-4 ring-1 ring-borde">
                <p class="text-xs text-texto-suave">Cuotas pendientes</p>
                <p class="mt-1 text-2xl font-bold">{{ resumen.cuotas }}</p>
            </div>
            <div class="rounded-xl bg-fondo p-4 ring-1 ring-borde">
                <p class="text-xs text-texto-suave">Monto pendiente</p>
                <p class="mt-1 text-2xl font-bold">{{ montoTexto(resumen.monto) }}</p>
            </div>
            <div class="rounded-xl bg-fondo p-4 ring-1 ring-borde">
                <p class="text-xs text-texto-suave">Cuotas vencidas</p>
                <p class="mt-1 text-2xl font-bold text-red-700">{{ resumen.vencidas }}</p>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl ring-1 ring-borde">
            <table class="min-w-full divide-y divide-borde text-sm">
                <thead class="bg-[var(--primario)] text-left text-xs font-semibold uppercase tracking-wide text-white">
                    <tr>
                        <th class="px-4 py-3">Deudor</th>
                        <th class="px-4 py-3">Solicitud</th>
                        <th class="px-4 py-3">Cuota</th>
                        <th class="px-4 py-3">Monto</th>
                        <th class="px-4 py-3">Fecha límite</th>
                        <th class="px-4 py-3">Situación</th>
                        <th class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde bg-superficie text-texto">
                    <tr v-for="d in deudas" :key="d.id">
                        <td class="px-4 py-3 font-medium">{{ d.deudor }}</td>
                        <td class="px-4 py-3">{{ d.referencia }}</td>
                        <td class="px-4 py-3">Cuota {{ d.nro_cuota }}</td>
                        <td class="px-4 py-3">{{ montoTexto(d.monto) }}</td>
                        <td class="px-4 py-3 text-texto-suave">{{ formatearFecha(d.vence) }}</td>
                        <td class="px-4 py-3">
                            <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold ring-1', situacion(d).clases]">
                                {{ situacion(d).texto }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="`/panel/pagos/${d.reserva_id}`" class="font-semibold text-[var(--primario)] hover:underline">
                                Ver
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="deudas.length === 0">
                        <td colspan="7" class="px-4 py-10 text-center text-texto-suave">No hay pagos pendientes.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PanelLayout>
</template>