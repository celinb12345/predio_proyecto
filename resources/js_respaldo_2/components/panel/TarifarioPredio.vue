<script setup lang="ts">
import { formulario } from '@/lib/estilos';
import { useForm } from '@inertiajs/vue3';

interface Tarifa {
    id: number;
    tipo_persona: string;
    monto: string;
    resolucion: string;
    vigente: boolean;
    fecha: string | null;
}

const props = defineProps<{
    predioId: number;
    tarifario: Tarifa[];
    tiposPersona: { id: number; descripcion: string }[];
}>();

const form = useForm({
    tipo_persona_id: '' as number | string,
    monto: '' as number | string,
    resolucion: '',
});

function guardar() {
    form.post(`/panel/predios/${props.predioId}/tarifas`, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function montoTexto(monto: string): string {
    const valor = Number(monto);

    return valor === 0 ? 'Sin costo' : `Bs ${valor.toFixed(2)}`;
}
</script>

<template>
    <section :class="formulario.tarjeta">
        <h2 :class="formulario.titulo">Tarifario</h2>
        <p :class="formulario.subtitulo">
            Monto que paga cada tipo de persona por usar el predio. Al registrar una tarifa nueva, la vigente anterior
            del mismo tipo pasa al historial.
        </p>

        <form class="mt-6 grid gap-5 rounded-xl bg-fondo p-5 sm:grid-cols-2 xl:grid-cols-4" @submit.prevent="guardar">
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

            <div class="sm:col-span-2">
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

            <div class="sm:col-span-2 xl:col-span-4">
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
                            <th class="px-5 py-3">Fecha</th>
                            <th class="px-5 py-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-borde bg-superficie text-texto">
                        <tr v-for="fila in tarifario" :key="fila.id">
                            <td class="px-5 py-4 font-medium">{{ fila.tipo_persona }}</td>
                            <td class="px-5 py-4">{{ montoTexto(fila.monto) }}</td>
                            <td class="px-5 py-4 text-texto-suave">{{ fila.resolucion }}</td>
                            <td class="px-5 py-4 text-texto-suave">{{ fila.fecha ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <span
                                    :class="[
                                        'rounded-full px-2.5 py-1 text-xs font-semibold ring-1',
                                        fila.vigente
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                            : 'bg-fondo text-texto-suave ring-borde',
                                    ]"
                                >
                                    {{ fila.vigente ? 'Vigente' : 'Historial' }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="tarifario.length === 0">
                            <td colspan="5" class="px-5 py-10 text-center text-texto-suave">
                                Este predio todavía no tiene tarifas.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</template>