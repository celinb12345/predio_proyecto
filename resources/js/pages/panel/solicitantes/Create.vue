<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type Fila = {
    predio_id: number | string;
    fecha: string;
    hora_inicio: string;
    hora_fin: string;
};

type FormularioSolicitud = {
    tipo_evento_id: number | string;
    persona_id: number | string;
    predios: Fila[];
};

const props = defineProps<{
    usuario: { name: string; rol: string };
    tiposEvento: { id: number; descripcion: string }[];
    personas: { id: number; nombre: string; apellido: string }[];
    puedeElegirPersona: boolean;
    personaPropia: number | null;
    predios: { id: number; nombre: string; capacidad: number | null; tipo: string }[];
}>();

const campo =
    'mt-1 w-full rounded-lg border border-borde px-3 py-2 text-sm focus:border-[var(--primario)] focus:outline-none';

const form = useForm<FormularioSolicitud>({
    tipo_evento_id: '',
    persona_id: props.puedeElegirPersona ? '' : (props.personaPropia ?? ''),
    predios: [{ predio_id: '', fecha: '', hora_inicio: '', hora_fin: '' }],
});

// Inertia solo tipa las claves fijas de errors; los errores de cada fila
// llegan con claves dinámicas como "predios.0.fecha", por eso se leen aquí.
const erroresDinamicos = form.errors as Record<string, string | undefined>;

function errorFila(indice: number, atributo: keyof Fila): string | undefined {
    return erroresDinamicos[`predios.${indice}.${atributo}`];
}

function filaCompleta(fila: Fila): boolean {
    return (
        fila.predio_id !== '' &&
        fila.fecha !== '' &&
        fila.hora_inicio !== '' &&
        fila.hora_fin !== '' &&
        fila.hora_fin > fila.hora_inicio
    );
}

// Choque entre filas de esta misma solicitud (se revisa sin ir al servidor)
function cruceConOtraFila(indice: number): boolean {
    const fila = form.predios[indice];
    if (!filaCompleta(fila)) {
        return false;
    }

    return form.predios.some(
        (otra, j) =>
            j !== indice &&
            filaCompleta(otra) &&
            String(otra.predio_id) === String(fila.predio_id) &&
            otra.fecha === fila.fecha &&
            otra.hora_inicio < fila.hora_fin &&
            otra.hora_fin > fila.hora_inicio,
    );
}

// Ocupación real en la base de datos (se revisa en el servidor)
const ocupados = ref<Record<number, string>>({});
let version = 0;
let temporizador: ReturnType<typeof setTimeout> | undefined;

async function consultarDisponibilidad(fila: Fila): Promise<string | null> {
    const parametros = new URLSearchParams({
        predio_id: String(fila.predio_id),
        fecha: fila.fecha,
        hora_inicio: fila.hora_inicio,
        hora_fin: fila.hora_fin,
    });

    try {
        const respuesta = await fetch(`/panel/solicitantes/disponibilidad?${parametros.toString()}`, {
            headers: { Accept: 'application/json' },
        });

        if (!respuesta.ok) {
            return null;
        }

        const datos: { ocupado: boolean; mensaje: string | null } = await respuesta.json();
        return datos.ocupado ? datos.mensaje : null;
    } catch {
        return null;
    }
}

async function verificarDisponibilidad(): Promise<void> {
    const actual = ++version;
    const resultados: Record<number, string> = {};

    await Promise.all(
        form.predios.map(async (fila, i) => {
            if (!filaCompleta(fila)) {
                return;
            }

            const mensaje = await consultarDisponibilidad(fila);
            if (mensaje) {
                resultados[i] = mensaje;
            }
        }),
    );

    // Si el usuario cambió algo mientras se consultaba, esta respuesta ya no sirve
    if (actual === version) {
        ocupados.value = resultados;
    }
}

watch(
    () => form.predios,
    () => {
        clearTimeout(temporizador);
        temporizador = setTimeout(verificarDisponibilidad, 400);
    },
    { deep: true },
);

function mensajeFila(indice: number): string | null {
    if (cruceConOtraFila(indice)) {
        return 'Se cruza con otra fila de esta misma solicitud.';
    }

    return ocupados.value[indice] ?? null;
}

const hayConflictos = computed(() => form.predios.some((_, i) => mensajeFila(i) !== null));

function agregarFila() {
    form.predios.push({ predio_id: '', fecha: '', hora_inicio: '', hora_fin: '' });
}

function quitarFila(indice: number) {
    if (form.predios.length > 1) {
        form.predios.splice(indice, 1);
    }
}

function guardar() {
    form.post('/panel/solicitantes');
}
</script>

<template>
    <PanelLayout titulo="Nueva solicitud de préstamo" :usuario="usuario">
        <Link href="/panel/solicitantes" class="mb-6 inline-block text-sm font-semibold text-[var(--primario)] hover:underline">
            ← Volver a solicitudes
        </Link>

        <p
            v-if="!puedeElegirPersona && !personaPropia"
            class="mb-6 max-w-3xl rounded-lg bg-red-50 p-4 text-sm text-red-700"
        >
            Tu usuario todavía no tiene una persona asociada. Pide a Secretaría General que la registre.
        </p>

        <div
            v-if="hayConflictos"
            class="mb-6 max-w-4xl rounded-lg border-l-4 border-red-600 bg-red-50 p-4 text-sm font-semibold text-red-700"
        >
            Hay predios ocupados en esa fecha y horario. Cambia las filas marcadas en rojo para poder enviar la solicitud.
        </div>

        <div
            v-else-if="form.hasErrors"
            class="mb-6 max-w-4xl rounded-lg border-l-4 border-red-600 bg-red-50 p-4 text-sm font-semibold text-red-700"
        >
            No se pudo enviar la solicitud. Revisa los campos marcados en rojo.
        </div>

        <form class="grid max-w-4xl gap-6" @submit.prevent="guardar">
            <div class="grid gap-5 sm:grid-cols-2">
                <label v-if="puedeElegirPersona" class="block text-sm font-medium">
                    Solicitante
                    <select v-model="form.persona_id" :class="campo">
                        <option value="" disabled>Selecciona una persona</option>
                        <option v-for="p in personas" :key="p.id" :value="p.id">{{ p.apellido }}, {{ p.nombre }}</option>
                    </select>
                    <span v-if="form.errors.persona_id" class="text-sm text-red-600">{{ form.errors.persona_id }}</span>
                </label>

                <label class="block text-sm font-medium">
                    Tipo de evento
                    <select v-model="form.tipo_evento_id" :class="campo">
                        <option value="" disabled>Selecciona un tipo</option>
                        <option v-for="t in tiposEvento" :key="t.id" :value="t.id">{{ t.descripcion }}</option>
                    </select>
                    <span v-if="form.errors.tipo_evento_id" class="text-sm text-red-600">{{ form.errors.tipo_evento_id }}</span>
                </label>
            </div>

            <div>
                <h2 class="mb-3 text-lg font-semibold text-[var(--primario)]">Predios, fechas y horarios</h2>

                <div
                    v-for="(fila, i) in form.predios"
                    :key="i"
                    class="mb-4 grid gap-3 rounded-xl p-4 ring-1 sm:grid-cols-6"
                    :class="mensajeFila(i) ? 'bg-red-50 ring-2 ring-red-500' : 'ring-borde'"
                >
                    <label class="block text-sm font-medium sm:col-span-2">
                        Predio
                        <select v-model="fila.predio_id" :class="campo">
                            <option value="" disabled>Selecciona un predio</option>
                            <option v-for="p in predios" :key="p.id" :value="p.id">
                                {{ p.nombre }} ({{ p.tipo }}{{ p.capacidad ? ', ' + p.capacidad + ' personas' : '' }})
                            </option>
                        </select>
                        <span v-if="errorFila(i, 'predio_id')" class="text-sm text-red-600">
                            {{ errorFila(i, 'predio_id') }}
                        </span>
                    </label>

                    <label class="block text-sm font-medium">
                        Fecha
                        <input v-model="fila.fecha" type="date" :class="campo" />
                        <span v-if="errorFila(i, 'fecha')" class="text-sm text-red-600">
                            {{ errorFila(i, 'fecha') }}
                        </span>
                    </label>

                    <label class="block text-sm font-medium">
                        Hora inicio
                        <input v-model="fila.hora_inicio" type="time" :class="campo" />
                        <span v-if="errorFila(i, 'hora_inicio')" class="text-sm text-red-600">
                            {{ errorFila(i, 'hora_inicio') }}
                        </span>
                    </label>

                    <label class="block text-sm font-medium">
                        Hora fin
                        <input v-model="fila.hora_fin" type="time" :class="campo" />
                        <span v-if="errorFila(i, 'hora_fin')" class="text-sm text-red-600">
                            {{ errorFila(i, 'hora_fin') }}
                        </span>
                    </label>

                    <p
                        v-if="mensajeFila(i)"
                        class="rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white sm:col-span-6"
                    >
                        {{ mensajeFila(i) }}
                    </p>

                    <div class="flex items-end sm:col-span-6">
                        <button
                            type="button"
                            :disabled="form.predios.length === 1"
                            class="text-sm font-semibold text-red-600 hover:underline disabled:text-texto-suave"
                            @click="quitarFila(i)"
                        >
                            Quitar esta fila
                        </button>
                    </div>
                </div>

                <span v-if="form.errors.predios" class="mb-3 block text-sm text-red-600">{{ form.errors.predios }}</span>

                <button
                    type="button"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-[var(--primario)] ring-1 ring-[var(--primario)] hover:bg-fondo"
                    @click="agregarFila"
                >
                    Agregar otro predio
                </button>
            </div>

            <div class="flex gap-3">
                <button
                    type="submit"
                    :disabled="form.processing || hayConflictos || (!puedeElegirPersona && !personaPropia)"
                    class="rounded-lg bg-[var(--primario)] px-5 py-2.5 text-sm font-semibold text-white disabled:bg-gray-400"
                >
                    Enviar solicitud
                </button>
                <Link href="/panel/solicitantes" class="rounded-lg px-5 py-2.5 text-sm font-semibold text-texto-suave ring-1 ring-borde hover:bg-fondo">
                    Cancelar
                </Link>
            </div>
        </form>
    </PanelLayout>
</template>