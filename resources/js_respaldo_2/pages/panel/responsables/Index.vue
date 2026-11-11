<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { Link } from '@inertiajs/vue3';

interface Responsable {
    id: number;
    nombre: string;
    apellido: string;
    cargo: string | null;
    celular: string | null;
    activo: boolean;
}

defineProps<{
    usuario: { name: string; rol: string };
    responsables: Responsable[];
}>();
</script>

<template>
    <PanelLayout titulo="Responsables de predios" :usuario="usuario">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <Link href="/panel/predios" class="text-sm font-semibold text-[var(--primario)] hover:underline">
                ← Volver a predios
            </Link>
            <Link
                href="/panel/responsables/crear"
                class="rounded-lg bg-[var(--secundario)] px-4 py-2 text-sm font-semibold text-[var(--primario)]"
            >
                Nuevo responsable
            </Link>
        </div>

        <div class="overflow-x-auto rounded-xl ring-1 ring-borde">
            <table class="min-w-full divide-y divide-borde text-sm">
                <thead class="bg-[var(--primario)] text-left text-white">
                    <tr>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Cargo</th>
                        <th class="px-4 py-3">Celular</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde bg-superficie">
                    <tr v-for="fila in responsables" :key="fila.id">
                        <td class="px-4 py-3 font-medium">{{ fila.apellido }}, {{ fila.nombre }}</td>
                        <td class="px-4 py-3">{{ fila.cargo ?? '—' }}</td>
                        <td class="px-4 py-3">{{ fila.celular ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="fila.activo ? 'bg-[var(--secundario)] text-[var(--primario)]' : 'bg-gray-300 text-texto-suave'"
                            >
                                {{ fila.activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link
                                :href="`/panel/responsables/${fila.id}/editar`"
                                class="font-semibold text-[var(--primario)] hover:underline"
                            >
                                Editar
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="responsables.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-texto-suave">Aún no hay responsables.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PanelLayout>
</template>