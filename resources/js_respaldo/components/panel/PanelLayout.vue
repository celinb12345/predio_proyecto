<script setup lang="ts">
import Notificacion from '@/components/Notificacion.vue';
import PanelSidebar from '@/components/panel/PanelSidebar.vue';
import { modulosPara } from '@/lib/panel-menu';
import { tema } from '@/lib/tema';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    titulo: string;
    usuario: { name: string; rol: string };
}>();

const modulos = computed(() => modulosPara(props.usuario.rol));

// Tema claro fijo para el panel: aunque el equipo esté en modo oscuro,
// los campos y el texto se ven siempre con colores legibles.
const estilosTema = {
    '--primario': tema.primario,
    '--secundario': tema.secundario,
    colorScheme: 'light',
};
</script>

<template>
    <Head :title="titulo" />

    <div class="flex min-h-screen bg-gray-100" :style="estilosTema">
        
        <Notificacion />

        <div class="hidden md:flex">
            <PanelSidebar :usuario="usuario" :modulos="modulos" />
        </div>

        <main class="flex-1 p-4 md:p-8">
            <div class="rounded-2xl bg-white p-6 text-gray-900 shadow-sm ring-1 ring-gray-200">
                <header class="mb-6 border-b-4 border-[var(--secundario)] pb-3">
                    <h1 class="text-2xl font-bold text-[var(--primario)]">{{ titulo }}</h1>
                </header>

                <slot />
            </div>
        </main>
    </div>
</template>