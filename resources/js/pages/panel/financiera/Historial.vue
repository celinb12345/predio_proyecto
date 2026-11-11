<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { formulario } from '@/lib/estilos';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Movimiento {
    id: string;
    tipo: 'liberacion' | 'reprogramacion';
    momento: string;
    predio: string;
    referencia: string;
    solicitante: string;
    categoria: string;
    horario: string;
    horario_nuevo: string | null;
    motivo: string;
    usuario: string;
    rol: string | null;
}

const props = defineProps<{
    usuario: { name: string; rol: string };
    movimientos: Movimiento[];
    resumen: { liberaciones: number; reprogramaciones: number; hoy: number };
    categorias: { etiqueta: string; orden: number }[];
}>();

const tipo = ref<'todos' | 'liberacion' | 'reprogramacion'>('todos');
const categoria = ref<string>('todas');
const buscar = ref('');

const filtrados = computed(() => {
    const termino = buscar.value.trim().toLowerCase();

    return props.movimientos.filter((m) => {
        if (tipo.value !== 'todos' && m.tipo !== tipo.value) {
            return false;
        }

        if (categoria.value !== 'todas' && m.categoria !== categoria.value) {
            return false;
        }

        if (!termino) {
            return true;
        }

        return [m.predio, m.referencia, m.solicitante, m.usuario, m.motivo]
            .some((texto) => texto.toLowerCase().includes(termino));
    });
});

const etiquetasTipo: Record<string, string> = {
    liberacion: 'Liberación',
    reprogramacion: 'Reprogramación',
};

const clasesTipo: Record<string, string> = {
    liberacion: 'bg-superficie text-red-600 ring-1 ring-red-600',
    reprogramacion: 'bg-[var(--secundario)] text-[var(--primario)]',
};
</script>

<template>
    <PanelLayout titulo="Historial de movimientos" :usuario="usuario">
        <Link href="/panel/financiera" class="mb-6 inline-block text-sm font-semibold text-[var(--primario)] hover:underline">
            ← Volver a liberación de fechas
        </Link>

        <section class="mb-8 grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl bg-superficie p-5 ring-1 ring-borde">
                <p class="text-xs font-semibold uppercase tracking-wide text-texto-suave">Liberaciones</p>
                <p class="mt-2 text-3xl font-bold text-red-700">{{ resumen.liberaciones }}</p>
            </div>
            <div class="rounded-xl bg-superficie p-5 ring-1 ring-borde">
                <p class="text-xs font-semibold uppercase tracking-wide text-texto-suave">Reprogramaciones</p>
                <p class="mt-2 text-3xl font-bold text-blue-800">{{ resumen.reprogramaciones }}</p>
            </div>
            <div class="rounded-xl bg-superficie p-5 ring-1 ring-borde">
                <p class="text-xs font-semibold uppercase tracking-wide text-texto-suave">Movimientos de hoy</p>
                <p class="mt-2 text-3xl font-bold text-[var(--primario)]">{{ resumen.hoy }}</p>
            </div>
        </section>

        <div class="mb-6 grid gap-4 rounded-xl bg-fondo p-4 ring-1 ring-borde md:grid-cols-[1fr_auto_auto] md:items-end">
            <label class="block text-sm font-medium">
                Buscar
                <input
                    v-model="buscar"
                    type="search"
                    placeholder="Predio, solicitud, solicitante, usuario o motivo"
                    :class="formulario.campo"
                />
            </label>
            <label class="block text-sm font-medium">
                Tipo
                <select v-model="tipo" :class="formulario.campo">
                    <option value="todos">Todos</option>
                    <option value="liberacion">Liberaciones</option>
                    <option value="reprogramacion">Reprogramaciones</option>
                </select>
            </label>
            <label class="block text-sm font-medium">
                Categoría
                <select v-model="categoria" :class="formulario.campo">
                    <option value="todas">Todas</option>
                    <option v-for="c in categorias" :key="c.etiqueta" :value="c.etiqueta">
                        {{ c.etiqueta }}
                    </option>
                </select>
            </label>
        </div>

        <p class="mb-3 text-sm text-texto-suave">
            Mostrando {{ filtrados.length }} de {{ movimientos.length }} movimientos
        </p>

        <div class="overflow-x-auto rounded-xl ring-1 ring-borde">
            <table class="min-w-full divide-y divide-borde text-sm">
                <thead class="bg-[var(--primario)] text-left text-white">
                    <tr>
                        <th class="px-4 py-3">Movimiento</th>
                        <th class="px-4 py-3">Fecha y hora</th>
                        <th class="px-4 py-3">Predio</th>
                        <th class="px-4 py-3">Solicitud</th>
                        <th class="px-4 py-3">Solicitante</th>
                        <th class="px-4 py-3">Categoría</th>
                        <th class="px-4 py-3">Horario</th>
                        <th class="px-4 py-3">Motivo</th>
                        <th class="px-4 py-3">Realizado por</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde bg-superficie">
                    <tr v-for="m in filtrados" :key="m.id" class="align-top">
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="clasesTipo[m.tipo]">
                                {{ etiquetasTipo[m.tipo] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">{{ m.momento }}</td>
                        <td class="px-4 py-3 font-medium">{{ m.predio }}</td>
                        <td class="px-4 py-3">{{ m.referencia }}</td>
                        <td class="px-4 py-3">{{ m.solicitante }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-md bg-fondo px-2 py-1 text-xs font-semibold text-[var(--primario)] ring-1 ring-borde">
                                {{ m.categoria }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <div>{{ m.horario }}</div>
                            <div v-if="m.horario_nuevo" class="mt-1 text-xs text-texto-suave">
                                Nuevo: {{ m.horario_nuevo }}
                            </div>
                        </td>
                        <td class="max-w-xs px-4 py-3">{{ m.motivo }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ m.usuario }}</div>
                            <div class="text-xs text-texto-suave">{{ m.rol ?? 'Sin rol' }}</div>
                        </td>
                    </tr>
                    <tr v-if="filtrados.length === 0">
                        <td colspan="9" class="px-4 py-8 text-center text-texto-suave">
                            No hay movimientos con esos filtros.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PanelLayout>
</template>