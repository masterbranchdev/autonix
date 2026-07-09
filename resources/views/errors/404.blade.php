<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada | Autonix</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 flex items-center justify-center h-screen selection:bg-blue-500 selection:text-white">
<div class="text-center px-6">
    <div class="mx-auto h-24 w-24 bg-blue-50 rounded-full flex items-center justify-center mb-6">
        <svg class="h-12 w-12 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
    </div>

    <h1 class="text-9xl font-black text-slate-200 tracking-tighter mb-2">404</h1>
    <h2 class="text-3xl font-extrabold text-slate-800 mb-4">¡Ups! Nos quedamos sin frenos</h2>

    <p class="text-slate-500 mb-8 max-w-md mx-auto text-sm md:text-base leading-relaxed">
        Parece que la página que buscas no existe, la URL es incorrecta o el vehículo se encuentra en revisión final y la página fue movida.
    </p>

    <a href="{{ url('/') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Volver a la plataforma
    </a>
</div>
</body>
</html>
