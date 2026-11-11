// Clases compartidas para formularios y tarjetas. Usan las variables del tema activo.
export const formulario = {
    tarjeta: 'rounded-2xl bg-superficie p-6 sm:p-8 ring-1 ring-borde shadow-sm',
    titulo: 'text-lg font-semibold text-texto',
    subtitulo: 'mt-1 text-sm text-texto-suave',
    etiqueta: 'block text-sm font-medium text-texto',
    campo:
        'mt-1.5 block w-full rounded-lg border border-borde bg-superficie px-3.5 py-2.5 text-sm text-texto shadow-sm placeholder:text-texto-suave focus:border-[var(--primario)] focus:outline-none focus:ring-2 focus:ring-[var(--primario)]/30',
    error: 'mt-1.5 text-sm font-medium text-red-600',
    ayuda: 'mt-1.5 text-xs text-texto-suave',
    botonPrimario:
        'inline-flex items-center justify-center rounded-lg bg-[var(--primario)] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[var(--primario-oscuro)] disabled:cursor-not-allowed disabled:opacity-60',
    botonSecundario:
        'inline-flex items-center justify-center rounded-lg bg-superficie px-5 py-2.5 text-sm font-semibold text-texto ring-1 ring-borde hover:bg-fondo',
};