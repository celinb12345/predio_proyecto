<script setup lang="ts">
import { formulario } from '@/lib/estilos';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Tarifa {
    id: number;
    tipo_persona: string;
    monto: string;
    resolucion: string;
    vigente: boolean;
    fecha: string | null;
    vigente_hasta: string | null;
}

const props = defineProps<{
    predioId: number;
    tarifario: Tarifa[];
    tiposPersona: { id: number; descripcion: string }[];
}>();

// Fecha de hoy en formato AAAA-MM-DD, según el reloj del equipo
const ahora = new Date();
const fechaHoy = [
    ahora.getFullYear(),
    String(ahora.getMonth() + 1).padStart(2, '0'),
    String(ahora.getDate()).padStart(2, '0'),
].join('-');

const form = useForm({
    tipo_persona_id: '' as number | string,
    monto: '' as number | string,
    resolucion: '',
    vigente_hasta: '',
});

const formEdicion = useForm({
    monto: '' as number | string,
    resolucion: '',
    vigente_hasta: '',
});

const editandoId = ref<number | null>(null);

function guardar() {
    form.post(`/panel/predios/${props.predioId}/tarifas`, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function empezarEdicion(fila: Tarifa) {
    formEdicion.clearErrors();
    formEdicion.monto = fila.monto;
    formEdicion.resolucion = fila.resolucion;
    formEdicion.vigente_hasta = fila.vigente_hasta ? soloFecha(fila.vigente_hasta) : '';
    editandoId.value = fila.id;
}

function guardarEdicion(fila: Tarifa) {
    formEdicion.put(`/panel/predios/${props.predioId}/tarifas/${fila.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editandoId.value = null;
        },
    });
}

function cancelarEdicion() {
    editandoId.value = null;
    formEdicion.clearErrors();
}

function soloFecha(fecha: string): string {
    return fecha.slice(0, 10);
}

function formatearFecha(fecha: string | null): string {
    if (!fecha) return '—';

    const [anio, mes, dia] = soloFecha(fecha).split('-');

    return `${dia}/${mes}/${anio}`;
}

function diasHasta(fecha: string): number {
    const hasta = new Date(`${soloFecha(fecha)}T00:00:00`).getTime();
    const hoy = new Date(`${fechaHoy}T00:00:00`).getTime();

    return Math.round((hasta - hoy) / 86_400_000);
}

function montoTexto(monto: string): string {
    const valor = Number(monto);

    return valor === 0 ? 'Sin costo' : `Bs ${valor.toFixed(2)}`;
}

function estadoFila(fila: Tarifa): { texto: string; clases: string } {
    const hasta = fila.vigente_hasta ? soloFecha(fila.vigente_hasta) : null;

    if (!fila.vigente) {
        if (hasta && hasta < fechaHoy) {
            return { texto: 'Vencida', clases: 'bg-red-50 text-red-700 ring-red-200' };
        }

        return { texto: 'Historial', clases: 'bg-fondo text-texto-suave ring-borde' };
    }

    if (hasta && diasHasta(hasta) <= 4) {
        return { texto: 'Por vencer', clases: 'bg-amber-50 text-amber-800 ring-amber-200' };
    }

    return { texto: 'Vigente', clases: 'bg-emerald-50 text-emerald-700 ring-emerald-200' };
}
</script>

<template>
    <section :class="formulario.tarjeta">
        <h2 :class="formulario.titulo">Tarifario</h2>
        <p :class="formulario.subtitulo">
            Monto que paga cada tipo de persona por usar el predio. Cada tarifa vale hasta su fecha de vencimiento;
            cuando esa fecha pasa, deja de estar vigente.
        </p>

        <form class="mt-6 grid gap-5 rounded-xl bg-fondo p-5 sm:grid-cols-2 xl:grid-cols-5" @submit.prevent="guardar">
            <div>
                <label for="tipo-tarifa" :class="formulario.etiqueta">Tipo de persona</label>
                <select id="tipo-tarifa" v-model="form.tipo_persona_id" :class="formulario.campo">
                    <option value="" disabled>Selecciona un tipo</option>
                    <option v-for="tipo in tiposPersona" :key="tipo.id" :value="tipo.id">
                        {{ tipo.descripcion }}
                    </option>
                </select>
                <p v-if="form.errors.tipo_persona_id" :class="formulario.error">{{ form.errors.tipo_persona_id }}</p>
            </div>

            <div>
                <label for="monto" :class="formulario.etiqueta">Monto (Bs)</label>
                <input id="monto" v-model="form.monto" type="number" min="0" step="0.01" :class="formulario.campo" />
                <p v-if="form.errors.monto" :class="formulario.error">{{ form.errors.monto }}</p>
                <p v-else :class="formulario.ayuda">Usa 0 para los internos.</p>
            </div>

            <div class="xl:col-span-2">
                <label for="resolucion" :class="formulario.etiqueta">Resolución del Consejo Facultativo</label>
                <input
                    id="resolucion"
                    v-model="form.resolucion"
                    type="text"
                    placeholder="Ej. Resolución 012/2026"
                    :class="formulario.campo"
                />
                <p v-if="form.errors.resolucion" :class="formulario.error">{{ form.errors.resolucion }}</p>
            </div>

            <div>
                <label for="vigente-hasta" :class="formulario.etiqueta">Vigente hasta</label>
                <input
                    id="vigente-hasta"
                    v-model="form.vigente_hasta"
                    type="date"
                    :min="fechaHoy"
                    :class="formulario.campo"
                />
                <p v-if="form.errors.vigente_hasta" :class="formulario.error">{{ form.errors.vigente_hasta }}</p>
                <p v-else :class="formulario.ayuda">Déjalo vacío si no vence.</p>
            </div>

            <div class="sm:col-span-2 xl:col-span-5">
                <button type="submit" :disabled="form.processing" :class="formulario.botonPrimario">
                    {{ form.processing ? 'Registrando…' : 'Registrar tarifa' }}
                </button>
            </div>
        </form>

        <div class="mt-6 overflow-hidden rounded-xl ring-1 ring-borde">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-borde text-sm">
                    <thead class="bg-[var(--primario)] text-left text-xs font-semibold uppercase tracking-wide text-white">
                        <tr>
                            <th class="px-5 py-3">Tipo de persona</th>
                            <th class="px-5 py-3">Monto</th>
                            <th class="px-5 py-3">Resolución</th>
                            <th class="px-5 py-3">Registrada</th>
                            <th class="px-5 py-3">Vigente hasta</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-borde bg-superficie text-texto">
                        <template v-for="fila in tarifario" :key="fila.id">
                            <tr v-if="editandoId !== fila.id">
                                <td class="px-5 py-4 font-medium">{{ fila.tipo_persona }}</td>
                                <td class="px-5 py-4">{{ montoTexto(fila.monto) }}</td>
                                <td class="px-5 py-4 text-texto-suave">{{ fila.resolucion }}</td>
                                <td class="px-5 py-4 text-texto-suave">{{ formatearFecha(fila.fecha) }}</td>
                                <td class="px-5 py-4 text-texto-suave">{{ formatearFecha(fila.vigente_hasta) }}</td>
                                <td class="px-5 py-4">
                                    <span
                                        :class="['rounded-full px-2.5 py-1 text-xs font-semibold ring-1', estadoFila(fila).clases]"
                                    >
                                        {{ estadoFila(fila).texto }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end">
                                        <button
                                            type="button"
                                            class="font-semibold text-[var(--primario)] hover:underline"
                                            @click="empezarEdicion(fila)"
                                        >
                                            Editar
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-else class="bg-fondo">
                                <td colspan="7" class="px-5 py-5">
                                    <form
                                        class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
                                        @submit.prevent="guardarEdicion(fila)"
                                    >
                                        <p class="text-sm text-texto-suave sm:col-span-2 xl:col-span-4">
                                            Editando: <span class="font-semibold text-texto">{{ fila.tipo_persona }}</span>
                                        </p>

                                        <div>
                                            <label :for="`edit-monto-${fila.id}`" :class="formulario.etiqueta">Monto (Bs)</label>
                                            <input
                                                :id="`edit-monto-${fila.id}`"
                                                v-model="formEdicion.monto"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                :class="formulario.campo"
                                            />
                                            <p v-if="formEdicion.errors.monto" :class="formulario.error">
                                                {{ formEdicion.errors.monto }}
                                            </p>
                                        </div>

                                        <div class="xl:col-span-2">
                                            <label :for="`edit-resolucion-${fila.id}`" :class="formulario.etiqueta">
                                                Resolución
                                            </label>
                                            <input
                                                :id="`edit-resolucion-${fila.id}`"
                                                v-model="formEdicion.resolucion"
                                                type="text"
                                                :class="formulario.campo"
                                            />
                                            <p v-if="formEdicion.errors.resolucion" :class="formulario.error">
                                                {{ formEdicion.errors.resolucion }}
                                            </p>
                                        </div>

                                        <div>
                                            <label :for="`edit-hasta-${fila.id}`" :class="formulario.etiqueta">Vigente hasta</label>
                                            <input
                                                :id="`edit-hasta-${fila.id}`"
                                                v-model="formEdicion.vigente_hasta"
                                                type="date"
                                                :class="formulario.campo"
                                            />
                                            <p v-if="formEdicion.errors.vigente_hasta" :class="formulario.error">
                                                {{ formEdicion.errors.vigente_hasta }}
                                            </p>
                                            <p v-else :class="formulario.ayuda">Déjalo vacío si no vence.</p>
                                        </div>

                                        <div class="flex gap-2 sm:col-span-2 xl:col-span-4">
                                            <button type="submit" :disabled="formEdicion.processing" :class="formulario.botonPrimario">
                                                {{ formEdicion.processing ? 'Guardando…' : 'Guardar cambios' }}
                                            </button>
                                            <button type="button" :class="formulario.botonSecundario" @click="cancelarEdicion">
                                                Cancelar
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        </template>

                        <tr v-if="tarifario.length === 0">
                            <td colspan="7" class="px-5 py-10 text-center text-texto-suave">
                                Este predio todavía no tiene tarifas.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</template>