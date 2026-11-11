<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface FilaUsuario {
    id: number;
    name: string;
    email: string;
    foto: string | null;
    rol: string | null;
    activo: boolean;
    ultimo_acceso: string | null;
}

interface Enlace {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    usuario: { name: string; rol: string };
    usuarios: {
        data: FilaUsuario[];
        links: Enlace[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filtros: { buscar: string };
}>();

const buscar = ref(props.filtros.buscar);

function buscarUsuarios() {
    router.get('/panel/usuarios', { buscar: buscar.value }, { preserveState: true, replace: true });
}
</script>

<template>
    <PanelLayout titulo="Gestión de usuarios" :usuario="usuario">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <form class="flex gap-2" @submit.prevent="buscarUsuarios">
                <input
                    v-model="buscar"
                    type="search"
                    placeholder="Buscar por nombre o correo"
                    class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[var(--primario)] focus:outline-none"
                />
                <button type="submit" class="rounded-lg bg-[var(--primario)] px-4 py-2 text-sm font-medium text-white">
                    Buscar
                </button>
            </form>

            <Link
                href="/panel/usuarios/crear"
                class="rounded-lg bg-[var(--secundario)] px-4 py-2 text-sm font-semibold text-[var(--primario)]"
            >
                Nuevo usuario
            </Link>
        </div>

        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-[var(--primario)] text-left text-white">
                    <tr>
                        <th class="px-4 py-3">Foto</th>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Correo</th>
                        <th class="px-4 py-3">Rol</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Último acceso</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <tr v-for="fila in usuarios.data" :key="fila.id">
                        <td class="px-4 py-3">
                            <img v-if="fila.foto" :src="fila.foto" alt="" class="h-9 w-9 rounded-full object-cover" />
                            <div
                                v-else
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-[var(--secundario)] text-xs font-bold text-[var(--primario)]"
                            >
                                {{ fila.name.charAt(0) }}
                            </div>
                        </td>
                        <td class="px-4 py-3 font-medium">{{ fila.name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ fila.email }}</td>
                        <td class="px-4 py-3">{{ fila.rol }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="fila.activo ? 'bg-[var(--secundario)] text-[var(--primario)]' : 'bg-gray-300 text-gray-700'"
                            >
                                {{ fila.activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ fila.ultimo_acceso ?? 'Nunca' }}</td>
                        <td class="px-4 py-3 text-right">
                            <Link
                                :href="`/panel/usuarios/${fila.id}/editar`"
                                class="font-semibold text-[var(--primario)] hover:underline"
                            >
                                Editar
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="usuarios.data.length === 0">
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">No se encontraron usuarios.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav class="mt-6 flex flex-wrap items-center justify-between gap-2 text-sm">
            <p class="text-gray-500">
                Mostrando {{ usuarios.from ?? 0 }} a {{ usuarios.to ?? 0 }} de {{ usuarios.total }}
            </p>
            <div class="flex flex-wrap gap-1">
                <template v-for="enlace in usuarios.links" :key="enlace.label">
                    <Link
                        v-if="enlace.url"
                        :href="enlace.url"
                        preserve-state
                        class="rounded-md px-3 py-1.5"
                        :class="enlace.active ? 'bg-[var(--primario)] text-white' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50'"
                    >
                        <span v-html="enlace.label" />
                    </Link>
                    <span v-else class="rounded-md px-3 py-1.5 text-gray-400">
                        <span v-html="enlace.label" />
                    </span>
                </template>
            </div>
        </nav>
    </PanelLayout>
</template>