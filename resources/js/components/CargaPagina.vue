<script setup lang="ts">
import { cargando } from '@/lib/cargando';

// Ventanas: 2 filas por 4 columnas, a los costados de la puerta
const ventanas = [40, 58, 100, 118].flatMap((x) => [76, 96].map((y) => ({ x, y })));
</script>

<template>
    <Transition name="desvanecer">
        <div
            v-if="cargando"
            class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-superficie/95 px-6 backdrop-blur-sm"
            role="status"
            aria-live="polite"
        >
            <svg viewBox="0 0 160 150" class="h-44 w-44" aria-hidden="true">
                <!-- Suelo -->
                <line class="trazo" x1="8" y1="134" x2="152" y2="134" style="--d: 0s" />
                <!-- Cuerpo del edificio -->
                <path class="trazo" d="M30 134 V66 H130 V134" style="--d: 0.3s" />
                <!-- Frontón -->
                <path class="trazo" d="M22 66 L80 26 L138 66" style="--d: 0.7s" />
                <!-- Puerta -->
                <rect class="trazo" x="72" y="104" width="16" height="30" style="--d: 1s" />
                <!-- Mástil -->
                <path class="trazo" d="M80 26 V6" style="--d: 1.2s" />
                <!-- Bandera -->
                <path class="bandera" d="M80 7 H100 L94 12 L100 17 H80 Z" />
                <!-- Ventanas que se encienden una por una -->
                <rect
                    v-for="(v, i) in ventanas"
                    :key="i"
                    class="ventana"
                    :x="v.x"
                    :y="v.y"
                    width="12"
                    height="12"
                    :style="{ '--i': i }"
                />
            </svg>

            <p class="mt-6 text-sm font-semibold tracking-wide text-[var(--primario)]">
                Preparando el sistema
                <span class="puntos"><i /><i /><i /></span>
            </p>

            <div class="mt-4 h-1.5 w-44 overflow-hidden rounded-full bg-gray-200">
                <div class="barra h-full rounded-full bg-[var(--secundario)]" />
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.trazo {
    fill: none;
    stroke: var(--primario);
    stroke-width: 3;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-dasharray: 260;
    stroke-dashoffset: 260;
    animation: dibujar 1.1s ease forwards;
    animation-delay: var(--d, 0s);
}

.ventana {
    fill: #e5e7eb;
    animation: encender 0.5s ease forwards;
    animation-delay: calc(1.5s + var(--i) * 0.12s);
}

.bandera {
    fill: var(--secundario);
    opacity: 0;
    transform-origin: 80px 7px;
    animation:
        aparecer 0.4s ease 1.4s forwards,
        ondear 1.4s ease-in-out 1.8s infinite;
}

.puntos i {
    display: inline-block;
    width: 4px;
    height: 4px;
    margin-left: 3px;
    border-radius: 9999px;
    background: var(--primario);
    animation: saltar 1s ease-in-out infinite;
}

.puntos i:nth-child(2) {
    animation-delay: 0.15s;
}

.puntos i:nth-child(3) {
    animation-delay: 0.3s;
}

.barra {
    width: 0;
    animation: llenar 1.4s ease-in-out infinite;
}

.desvanecer-enter-active,
.desvanecer-leave-active {
    transition: opacity 0.3s ease;
}

.desvanecer-enter-from,
.desvanecer-leave-to {
    opacity: 0;
}

@keyframes dibujar {
    to {
        stroke-dashoffset: 0;
    }
}

@keyframes encender {
    to {
        fill: var(--secundario);
    }
}

@keyframes aparecer {
    to {
        opacity: 1;
    }
}

@keyframes ondear {
    0%,
    100% {
        transform: skewY(0deg);
    }
    50% {
        transform: skewY(-8deg);
    }
}

@keyframes saltar {
    0%,
    100% {
        transform: translateY(0);
        opacity: 0.4;
    }
    50% {
        transform: translateY(-4px);
        opacity: 1;
    }
}

@keyframes llenar {
    0% {
        width: 0;
    }
    100% {
        width: 100%;
    }
}
</style>