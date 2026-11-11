<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    Building2,
    ChevronLeft,
    ChevronRight,
    ClipboardCheck,
    CreditCard,
    FileText,
    LogOut,
    Users,
    Wallet,
    Wrench,
} from 'lucide-vue-next';
import type { Component } from 'vue';
import { computed, ref } from 'vue';

interface Modulo {
    codigo: string;
    nombre: string;
    href: string;
}

const props = defineProps<{
    usuario: { name: string; rol: string };
    modulos: Modulo[];
}>();

const colapsado = ref(false);
const pagina = usePage();

const iconos: Record<string, Component> = {
    CU1: Users,
    CU2: Building2,
    CU3: FileText,
    CU4: Wallet,
    CU5: ClipboardCheck,
    CU6: Wrench,
    CU7: CreditCard,
    CU8: BarChart3,
};

const iniciales = computed(() =>
    props.usuario.name
        .split(' ')
        .slice(0, 2)
        .map((parte) => parte.charAt(0))
        .join('')
        .toUpperCase(),
);

const esActivo = (href: string) => pagina.url.startsWith(href);
</script>

<template>
    <aside
        :class="[
            'flex h-full flex-col rounded-2xl bg-[var(--primario)] text-white shadow-sm transition-all duration-300',
            colapsado ? 'w-20' : 'w-72',
        ]"
    >
        <div class="flex items-center justify-between p-4">
            <span v-if="!colapsado" class="text-lg font-bold tracking-widest text-[var(--secundario)]">PREDIOS FICH</span>
            <span v-else class="text-sm font-bold text-[var(--secundario)]">FICH</span>
            <button
                type="button"
                class="rounded-lg p-1.5 text-white hover:bg-[var(--secundario)] hover:text-[var(--primario)]"
                @click="colapsado = !colapsado"
            >
                <ChevronLeft v-if="!colapsado" class="h-4 w-4" />
                <ChevronRight v-else class="h-4 w-4" />
            </button>
        </div>

        <div class="mx-3 mb-4 flex items-center gap-3 rounded-xl bg-[var(--secundario)] p-3 text-[var(--primario)]">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[var(--primario)] text-sm font-semibold text-white"
            >
                {{ iniciales }}
            </div>
            <div v-if="!colapsado" class="min-w-0">
                <p class="truncate text-sm font-semibold">{{ usuario.name }}</p>
                <p class="truncate text-xs">{{ usuario.rol }}</p>
            </div>
        </div>

        <p v-if="!colapsado" class="px-6 pb-2 text-xs font-semibold uppercase tracking-wider text-[var(--secundario)]">
            Menú
        </p>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3">
            <Link
                v-for="modulo in modulos"
                :key="modulo.codigo"
                :href="modulo.href"
                :title="modulo.nombre"
                :class="[
                    'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition',
                    esActivo(modulo.href)
                        ? 'bg-[var(--secundario)] font-semibold text-[var(--primario)]'
                        : 'text-white hover:bg-[var(--secundario)] hover:text-[var(--primario)]',
                    colapsado ? 'justify-center' : '',
                ]"
            >
                <component :is="iconos[modulo.codigo]" class="h-4 w-4 shrink-0" />
                <span v-if="!colapsado" class="truncate">{{ modulo.nombre }}</span>
            </Link>
        </nav>

        <div class="p-3">
            <Link
                href="/logout"
                method="post"
                as="button"
                :class="[
                    'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-white hover:bg-[var(--secundario)] hover:text-[var(--primario)]',
                    colapsado ? 'justify-center' : '',
                ]"
            >
                <LogOut class="h-4 w-4 shrink-0" />
                <span v-if="!colapsado">Cerrar sesión</span>
            </Link>
        </div>
    </aside>
</template>