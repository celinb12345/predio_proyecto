<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    usuario: { name: string; rol: string };
    filtros: { desde: string; hasta: string };
    resumen: { ingresos: number; prestamos: number; horas: number; costo_mantenimiento: number };
    usoPredios: { id: number; nombre: string; usos: number; horas: number }[];
    ingresosMensuales: { mes: string; total: number }[];
    prestamosPorTipo: { tipo: string; total: number }[];
    costosMantenimiento: { predio: string; registros: number; total: number }[];
}>();

const campo =
    'mt-1 w-full rounded-lg border border-borde px-3 py-2 text-sm focus:border-[var(--primario)] focus:outline-none';

const desde = ref(props.filtros.desde);
const hasta = ref(props.filtros.hasta);

function aplicarFiltro() {
    router.get(
        '/panel/reportes',
        { desde: desde.value, hasta: hasta.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function bs(valor: number): string {
    return `Bs ${valor.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function porcentaje(valor: number, maximo: number): number {
    return maximo > 0 ? Math.round((valor / maximo) * 100) : 0;
}

const maxUso = computed(() => Math.max(0, ...props.usoPredios.map((p) => p.usos)));
const maxIngresos = computed(() => Math.max(0, ...props.ingresosMensuales.map((i) => i.total)));
const maxPrestamos = computed(() => Math.max(0, ...props.prestamosPorTipo.map((p) => p.total)));
</script>

<template>
    <PanelLayout titulo="Reportes y estadísticas" :usuario="usuario">
        <form
            class="mb-8 grid gap-4 rounded-xl p-5 ring-1 ring-borde sm:grid-cols-[1fr_1fr_auto] sm:items-end"
            @submit.prevent="aplicarFiltro"
        >
            <label class="block text-sm font-medium">
                Desde
                <input v-model="desde" type="date" :class="campo" />
            </label>
            <label class="block text-sm font-medium">
                Hasta
                <input v-model="hasta" type="date" :class="campo" />
            </label>
            <button
                type="submit"
                class="rounded-lg bg-[var(--primario)] px-5 py-2.5 text-sm font-semibold text-white"
            >
                Aplicar
            </button>
        </form>

        <!-- Resumen -->
        <section class="mb-10 grid grid-cols-2 gap-4 xl:grid-cols-4">
            <div class="rounded-xl bg-superficie p-5 text-texto ring-1 ring-borde">
                <p class="text-sm text-texto-suave">Ingresos cobrados</p>
                <p class="mt-1 text-xl font-bold text-[var(--primario)]">{{ bs(resumen.ingresos) }}</p>
            </div>
            <div class="rounded-xl bg-superficie p-5 text-texto ring-1 ring-borde">
                <p class="text-sm text-texto-suave">Préstamos aprobados</p>
                <p class="mt-1 text-xl font-bold text-[var(--primario)]">{{ resumen.prestamos }}</p>
            </div>
            <div class="rounded-xl bg-superficie p-5 text-texto ring-1 ring-borde">
                <p class="text-sm text-texto-suave">Horas de uso</p>
                <p class="mt-1 text-xl font-bold text-[var(--primario)]">{{ resumen.horas.toFixed(1) }} h</p>
            </div>
            <div class="rounded-xl bg-superficie p-5 text-texto ring-1 ring-borde">
                <p class="text-sm text-texto-suave">Costo de mantenimiento</p>
                <p class="mt-1 text-xl font-bold text-[var(--primario)]">{{ bs(resumen.costo_mantenimiento) }}</p>
            </div>
        </section>

        <div class="grid gap-8 xl:grid-cols-2">
            <!-- Predio más usado -->
            <section class="rounded-xl bg-superficie p-5 text-texto ring-1 ring-borde">
                <h2 class="mb-4 font-semibold text-[var(--primario)]">Predio más usado</h2>

                <div v-if="usoPredios.length" class="space-y-4">
                    <div v-for="p in usoPredios" :key="p.id">
                        <div class="flex justify-between gap-2 text-sm">
                            <span class="font-medium">{{ p.nombre }}</span>
                            <span class="text-texto-suave">{{ p.usos }} usos · {{ p.horas.toFixed(1) }} h</span>
                        </div>
                        <div class="mt-1 h-3 w-full rounded-full bg-gray-200">
                            <div
                                class="h-3 rounded-full bg-[var(--primario)]"
                                :style="{ width: porcentaje(p.usos, maxUso) + '%' }"
                            />
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-texto-suave">No hay préstamos aprobados en este período.</p>
            </section>

            <!-- Ingresos por mes -->
            <section class="rounded-xl bg-superficie p-5 text-texto ring-1 ring-borde">
                <h2 class="mb-4 font-semibold text-[var(--primario)]">Ingresos por mes</h2>

                <div v-if="ingresosMensuales.length" class="space-y-4">
                    <div v-for="i in ingresosMensuales" :key="i.mes">
                        <div class="flex justify-between gap-2 text-sm">
                            <span class="font-medium">{{ i.mes }}</span>
                            <span class="text-texto-suave">{{ bs(i.total) }}</span>
                        </div>
                        <div class="mt-1 h-3 w-full rounded-full bg-gray-200">
                            <div
                                class="h-3 rounded-full bg-[var(--secundario)]"
                                :style="{ width: porcentaje(i.total, maxIngresos) + '%' }"
                            />
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-texto-suave">No hay cuotas pagadas en este período.</p>
            </section>

            <!-- Préstamos por tipo de evento -->
            <section class="rounded-xl bg-superficie p-5 text-texto ring-1 ring-borde">
                <h2 class="mb-4 font-semibold text-[var(--primario)]">Préstamos por tipo de evento</h2>

                <div v-if="prestamosPorTipo.length" class="space-y-4">
                    <div v-for="p in prestamosPorTipo" :key="p.tipo">
                        <div class="flex justify-between gap-2 text-sm">
                            <span class="font-medium">{{ p.tipo }}</span>
                            <span class="text-texto-suave">{{ p.total }}</span>
                        </div>
                        <div class="mt-1 h-3 w-full rounded-full bg-gray-200">
                            <div
                                class="h-3 rounded-full bg-[var(--primario)]"
                                :style="{ width: porcentaje(p.total, maxPrestamos) + '%' }"
                            />
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-texto-suave">No hay préstamos aprobados en este período.</p>
            </section>

            <!-- Costos de mantenimiento -->
            <section class="rounded-xl bg-superficie p-5 text-texto ring-1 ring-borde">
                <h2 class="mb-4 font-semibold text-[var(--primario)]">Costos de mantenimiento</h2>

                <div v-if="costosMantenimiento.length" class="overflow-x-auto rounded-lg ring-1 ring-borde">
                    <table class="min-w-full divide-y divide-borde text-sm">
                        <thead class="bg-[var(--primario)] text-left text-white">
                            <tr>
                                <th class="px-4 py-3">Predio</th>
                                <th class="px-4 py-3 text-right">Registros</th>
                                <th class="px-4 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-borde bg-superficie">
                            <tr v-for="c in costosMantenimiento" :key="c.predio">
                                <td class="px-4 py-3 font-medium">{{ c.predio }}</td>
                                <td class="px-4 py-3 text-right">{{ c.registros }}</td>
                                <td class="px-4 py-3 text-right">{{ bs(c.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-texto-suave">No hay mantenimientos registrados en este período.</p>
            </section>
        </div>
    </PanelLayout>
</template>