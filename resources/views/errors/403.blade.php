<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Acceso Denegado | Autonix</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 flex items-center justify-center h-screen selection:bg-red-500 selection:text-white">
<div class="text-center px-6">
    <div class="mx-auto h-24 w-24 bg-red-50 rounded-full flex items-center justify-center mb-6">
        <svg class="h-12 w-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
        </svg>
    </div>

    <h1 class="text-9xl font-black text-slate-200 tracking-tighter mb-2">403</h1>
    <h2 class="text-3xl font-extrabold text-slate-800 mb-4">Acceso Restringido</h2>

    <p class="text-slate-500 mb-8 max-w-md mx-auto text-sm md:text-base leading-relaxed">
        No tienes las llaves para acceder a esta área. Tu perfil actual no cuenta con la autorización necesaria para ver o modificar este módulo.
    </p>

    <a href="{{ url('/') }}" class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-900 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        Volver a mi área de trabajo
    </a>
</div>
</body>
</html>
