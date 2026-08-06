<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a Autonix</title>
    <!-- Incluimos Tailwind CSS vía CDN para que funcione de inmediato -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Fondo con formas sutiles estilo SaaS */
        body {
            background-color: #f8fafc;
            background-image: radial-gradient(circle at 15% 50%, rgba(226, 232, 240, 0.4) 0%, transparent 50%),
            radial-gradient(circle at 85% 30%, rgba(226, 232, 240, 0.5) 0%, transparent 50%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center font-sans text-gray-800 antialiased relative overflow-hidden">

<!-- Contenedor Principal Centrado -->
<div class="relative z-10 flex flex-col items-center justify-center px-4 text-center">

    <!-- Logo de Autonix (Asegúrate de que la ruta sea correcta) -->
    <img src="{{ asset('img/autonix_logo.png') }}" alt="Autonix Logo" class="h-40 md:h-60 mb-6 object-contain">

    <h2 class="text-lg md:text-xl font-bold text-gray-500 tracking-widest uppercase mb-10">
        Bienvenido a tu sistema automotriz
    </h2>

    <!-- Botón de Iniciar Sesión (Apunta a la nueva ruta del login de Filament) -->
    <a href="/login"
       class="inline-flex items-center justify-center px-8 py-3 text-sm md:text-base font-semibold text-white transition-all duration-300 transform rounded-full shadow-lg hover:scale-105 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-blue-300"
       style="background-color: #d97706;">
        Iniciar sesión
    </a>

    <footer class="w-full py-4 text-sm mt-8 flex justify-center">
        <small class="text-gray-600 dark:text-gray-700 inline-flex items-center gap-1.5">
            Copyright © 2026
            <a href="https://syntaro.mx" target="_blank" style="color: #00a3e4; font-weight: 300;" class="hover:opacity-80 transition-opacity flex items-center">
                <!-- Cambiamos h-5 por h-3 (aprox 12px) para igualar el texto, y quitamos el mb-1 -->
                <img src="{{ asset('img/logo_2.png') }}" alt="SYNTARO" class="h-3">
            </a>
            Todos los derechos reservados.
        </small>
    </footer>

</div>

</body>
</html>
