<script setup lang="ts">
import { formulario } from '@/lib/estilos';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface CuotaPlan {
    id: number;
    nro: number;
    monto_numero: number;
    vencimiento_iso: string;
    estado: string;
}

const props = defineProps<{
    reservaId: number;
    montoTotal: string;
    cuotas: CuotaPlan[];
}>();

function pad(n: number): string {
    return String(n).padStart(2, '0');
}

function fechaDeHoy(): string {
    const hoy = new Date();

    return `${hoy.getFullYear()}-${pad(hoy.getMonth() + 1)}-${pad(hoy.getDate())}`;
}

function fechaSiguiente(fecha: string): string {
    const d = new Date(`${fecha}T00:00:00`);
    d.setDate(d.getDate() + 1);

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

const abierto = ref(false);

const total = computed(() => Number(props.montoTotal.replace(/,/g, '')));

const pagadas = computed(() => props.cuotas.filter((c) => c.estado === 'pagada'));

const pagadoBs = computed(() =>
    pagadas.value.reduce((suma, c) => suma + Number(c.monto_numero), 0),
);

const form = useForm({
    cuotas: [] as { id: number; monto: number | string; fecha_vencimiento: string }[],
});

// useForm solo tipa la clave fija "cuotas"; las claves anidadas (cuotas.0.monto) se leen como texto
function errorDe(clave: string): string | undefined {
    return (form.errors as unknown as Record<string, string | undefined>)[clave];
}

function nroDe(id: number): number {
    return props.cuotas.find((c) => c.id === id)?.nro ?? 0;
}

// Fecha límite de una cuota por su número, sea editada en el formulario o ya guardada
function fechaDe(nro: number): string | null {
    const enFormulario = form.cuotas.find((c) => nroDe(c.id) === nro);

    if (enFormulario) {
        return enFormulario.fecha_vencimiento;
    }

    return props.cuotas.find((c) => c.nro === nro)?.vencimiento_iso ?? null;
}

// La cuota 2 debe vencer después de la cuota 1; ninguna puede ser anterior a hoy
function minimoFecha(id: number): string {
    const hoy = fechaDeHoy();

    if (nroDe(id) === 2) {
        const primera = fechaDe(1);

        if (primera) {
            const siguiente = fechaSiguiente(primera);

            return siguiente > hoy ? siguiente : hoy;
        }
    }

    return hoy;
}

const sumaFormulario = computed(() => {
    const editadas = form.cuotas.reduce((suma, c) => suma + Number(c.monto || 0), 0);

    return Math.round((editadas + pagadoBs.value) * 100) / 100;
});

const cuadra = computed(() => Math.abs(sumaFormulario.value - total.value) < 0.005);

function abrir(): void {
    form.clearErrors();
    form.cuotas = props.cuotas
        .filter((c) => c.estado !== 'pagada')
        .map((c) => ({
            id: c.id,
            monto: c.monto_numero,
            fecha_vencimiento: c.vencimiento_iso,
        }));
    abierto.value = true;
}

function cancelar(): void {
    form.clearErrors();
    abierto.value = false;
}

function guardar(): void {
    form.put(`/panel/pagos/${props.reservaId}/plan`, {
        preserveScroll: true,
        onSuccess: () => {
            abierto.value = false;
        },
    });
}
</script>

<template>
    <section v-if="!abierto" class="mt-6">
        <button type="button" :class="formulario.botonSecundario" @click="abrir">
            Editar plan de pagos
        </button>
    </section>

    <form v-else :class="[formulario.tarjeta, 'mt-6']" @submit.prevent="guardar">
        <h3 :class="formulario.titulo">Editar plan de pagos</h3>
        <p :class="formulario.subtitulo">
            Cambia el monto y la fecha límite de las cuotas pendientes. La suma de todas las cuotas debe ser
            Bs {{ total.toFixed(2) }}.
        </p>

        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            <div v-for="(cuota, indice) in form.cuotas" :key="cuota.id" class="rounded-xl bg-fondo p-4 ring-1 ring-borde">
                <p class="mb-3 font-semibold">Cuota {{ nroDe(cuota.id) }}</p>

                <label :for="`monto-${cuota.id}`" :class="formulario.etiqueta">Monto (Bs)</label>
                <input
                    :id="`monto-${cuota.id}`"
                    v-model="cuota.monto"
                    type="number"
                    min="0.01"
                    step="0.01"
                    :class="formulario.campo"
                />
                <p v-if="errorDe(`cuotas.${indice}.monto`)" :class="formulario.error">
                    {{ errorDe(`cuotas.${indice}.monto`) }}
                </p>

                <label :for="`fecha-${cuota.id}`" :class="[formulario.etiqueta, 'mt-4']">Fecha límite para pagar</label>
                <input
                    :id="`fecha-${cuota.id}`"
                    v-model="cuota.fecha_vencimiento"
                    type="date"
                    :min="minimoFecha(cuota.id)"
                    :class="formulario.campo"
                />
                <p v-if="errorDe(`cuotas.${indice}.fecha_vencimiento`)" :class="formulario.error">
                    {{ errorDe(`cuotas.${indice}.fecha_vencimiento`) }}
                </p>
            </div>
        </div>

        <div v-if="pagadas.length" class="mt-4 space-y-1 text-sm text-texto-suave">
            <p v-for="c in pagadas" :key="c.id">
                Cuota {{ c.nro }} ya pagada por Bs {{ c.monto_numero.toFixed(2) }} (no se puede cambiar).
            </p>
        </div>

        <p class="mt-5 text-sm font-semibold" :class="cuadra ? 'text-emerald-700' : 'text-red-700'">
            Suma de las cuotas: Bs {{ sumaFormulario.toFixed(2) }} de Bs {{ total.toFixed(2) }}
        </p>
        <p v-if="form.errors.cuotas" :class="formulario.error">{{ form.errors.cuotas }}</p>

        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" :disabled="form.processing || !cuadra" :class="formulario.botonPrimario">
                {{ form.processing ? 'Guardando…' : 'Guardar plan' }}
            </button>
            <button type="button" :class="formulario.botonSecundario" @click="cancelar">Cancelar</button>
        </div>
    </form>
</template>