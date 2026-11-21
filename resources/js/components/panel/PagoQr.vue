<script setup lang="ts">
import { CheckCircle2, Loader2, QrCode, RefreshCw, XCircle } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps<{
    urlGenerar: string;
    monto: number;
}>();

const emit = defineEmits<{ pagado: [] }>();

interface RespuestaGenerar {
    qr_imagen: string;
    expira_en: string;
    url_estado: string;
}

interface RespuestaEstado {
    estado: 'pendiente' | 'pagado' | 'vencido' | 'rechazado';
}

type Fase = 'inicial' | 'generando' | 'esperando' | 'verificando' | 'pagado' | 'error';

const fase = ref<Fase>('inicial');
const mensaje = ref('');
const qrImagen = ref('');
const urlEstado = ref('');
const expiraEn = ref<Date | null>(null);
const ahora = ref(Date.now());

let intervaloEstado: number | undefined;
let intervaloReloj: number | undefined;
// Evita que se acumulen consultas si una respuesta tarda más que el intervalo
let consultando = false;

// Cada 8 segundos: menos carga sobre el servidor y sobre PagoFácil
const INTERVALO_ESTADO_MS = 8000;

const srcQr = computed(() =>
    qrImagen.value.startsWith('data:') ? qrImagen.value : `data:image/png;base64,${qrImagen.value}`,
);

const montoTexto = computed(
    () =>
        `Bs ${props.monto.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
);

const restante = computed(() => {
    if (!expiraEn.value) return '--:--';
    const segundos = Math.max(0, Math.floor((expiraEn.value.getTime() - ahora.value) / 1000));
    const minutos = String(Math.floor(segundos / 60)).padStart(2, '0');
    const resto = String(segundos % 60).padStart(2, '0');
    return `${minutos}:${resto}`;
});

function cookieXsrf(): string {
    const fila = document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='));
    return fila ? decodeURIComponent(fila.split('=')[1] ?? '') : '';
}

async function pedir<T>(url: string, metodo: 'GET' | 'POST'): Promise<T> {
    const respuesta = await fetch(url, {
        method: metodo,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-XSRF-TOKEN': cookieXsrf(),
        },
    });

    const datos = await respuesta.json().catch(() => ({}));

    if (!respuesta.ok) {
        throw new Error((datos as { mensaje?: string }).mensaje ?? 'Ocurrió un error. Intenta nuevamente.');
    }

    return datos as T;
}

function detenerVigilancia() {
    if (intervaloEstado) clearInterval(intervaloEstado);
    if (intervaloReloj) clearInterval(intervaloReloj);
    intervaloEstado = undefined;
    intervaloReloj = undefined;
}

function iniciarVigilancia() {
    detenerVigilancia();
    intervaloEstado = window.setInterval(consultarEstado, INTERVALO_ESTADO_MS);
    intervaloReloj = window.setInterval(() => (ahora.value = Date.now()), 1000);
}

async function generarQr() {
    fase.value = 'generando';
    mensaje.value = '';

    try {
        const datos = await pedir<RespuestaGenerar>(props.urlGenerar, 'POST');
        qrImagen.value = datos.qr_imagen;
        urlEstado.value = datos.url_estado;
        expiraEn.value = new Date(datos.expira_en.replace(' ', 'T'));
        fase.value = 'esperando';
        iniciarVigilancia();
    } catch (error) {
        fase.value = 'error';
        mensaje.value = error instanceof Error ? error.message : 'No se pudo generar el QR.';
    }
}

async function consultarEstado() {
    if (consultando) return;
    consultando = true;

    try {
        const { estado } = await pedir<RespuestaEstado>(urlEstado.value, 'GET');

        if (estado === 'pagado') {
            detenerVigilancia();
            fase.value = 'verificando';
            await new Promise((resolver) => setTimeout(resolver, 1500));
            fase.value = 'pagado';
        } else if (estado === 'vencido' || estado === 'rechazado') {
            detenerVigilancia();
            fase.value = 'error';
            mensaje.value = estado === 'vencido' ? 'El QR venció. Genera uno nuevo.' : 'El pago fue rechazado.';
        }
    } catch {
        // Un fallo puntual de red no detiene la espera: se reintenta en el siguiente ciclo
    } finally {
        consultando = false;
    }
}

function reintentar() {
    detenerVigilancia();
    qrImagen.value = '';
    fase.value = 'inicial';
}

function cerrarExito() {
    emit('pagado');
}

onBeforeUnmount(detenerVigilancia);
</script>

<template>
    <section class="rounded-xl bg-superficie p-5 text-texto ring-1 ring-borde">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 class="font-semibold text-[var(--primario)]">Pagar con QR</h3>
                <p class="text-sm text-texto-suave">
                    Escanea el código con tu app bancaria. La confirmación es automática.
                </p>
            </div>
            <QrCode class="h-6 w-6 shrink-0 text-[var(--primario)]" />
        </div>

        <div v-if="fase === 'inicial'" class="mt-5">
            <button
                type="button"
                class="rounded-lg bg-[var(--primario)] px-5 py-2.5 text-sm font-semibold text-white"
                @click="generarQr"
            >
                Generar QR de {{ montoTexto }}
            </button>
        </div>

        <div v-else-if="fase === 'generando'" class="mt-5 flex items-center gap-2 text-sm">
            <Loader2 class="h-5 w-5 animate-spin text-[var(--primario)]" />
            Generando QR…
        </div>

        <div v-else-if="fase === 'esperando'" class="mt-5 flex flex-col items-center gap-3">
            <img :src="srcQr" alt="Código QR para pagar" class="h-60 w-60 rounded-lg bg-white p-3" />
            <p class="text-sm">Monto: <span class="font-semibold">{{ montoTexto }}</span></p>
            <p class="text-sm text-texto-suave">
                Esperando el pago · vence en <span class="font-semibold text-texto">{{ restante }}</span>
            </p>
        </div>

        <div v-else-if="fase === 'error'" class="mt-5 flex flex-col items-start gap-3">
            <p class="flex items-center gap-2 text-sm text-red-600">
                <XCircle class="h-5 w-5" />
                {{ mensaje }}
            </p>
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-[var(--primario)] ring-1 ring-[var(--primario)]"
                @click="reintentar"
            >
                <RefreshCw class="h-4 w-4" />
                Intentar de nuevo
            </button>
        </div>

        <!-- Pantalla de verificación y éxito: el fondo se difumina, la tarjeta queda nítida -->
        <Teleport to="body">
            <div
                v-if="fase === 'verificando'"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-md"
                role="status"
                aria-live="polite"
            >
                <div class="flex flex-col items-center gap-4 rounded-2xl bg-superficie p-8 text-texto shadow-xl">
                    <Loader2 class="h-12 w-12 animate-spin text-[var(--primario)]" />
                    <p class="text-lg font-semibold">Verificando el pago…</p>
                </div>
            </div>

            <div
                v-else-if="fase === 'pagado'"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-md"
                role="alertdialog"
                aria-live="assertive"
            >
                <div
                    class="flex w-full max-w-sm flex-col items-center gap-5 rounded-3xl bg-superficie p-10 text-center text-texto shadow-2xl"
                >
                    <div class="flex h-28 w-28 items-center justify-center rounded-full bg-[var(--primario)] text-white">
                        <CheckCircle2 class="h-16 w-16" />
                    </div>
                    <p class="text-3xl font-bold text-[var(--primario)]">¡Pagado exitosamente!</p>
                    <p class="text-texto-suave">{{ montoTexto }} recibidos.</p>
                    <button
                        type="button"
                        class="rounded-lg bg-[var(--primario)] px-6 py-2.5 text-sm font-semibold text-white"
                        @click="cerrarExito"
                    >
                        Continuar
                    </button>
                </div>
            </div>
        </Teleport>
    </section>
</template>