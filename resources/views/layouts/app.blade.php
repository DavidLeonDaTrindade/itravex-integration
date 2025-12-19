{{-- Compat: permite usar este layout con @extends/@section o con <x-app-layout> --}}
@php
if (!isset($slot)) {
  $__env->startSection('content'); $__env->stopSection();
  $slot = $__env->yieldContent('content');
}
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ config('app.name', 'Laravel') }}</title>

  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased min-h-screen">
  <div class="min-h-screen flex flex-col bg-white">
    @include('layouts.navigation')

    @isset($header)
      <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
          {{ $header }}
        </div>
      </header>
    @endisset

    {{-- CLAVE: main debe ser flex y ocupar el alto restante --}}
    <main class="flex-1 flex">
      {{-- wrapper para que el slot pueda usar h-full/min-h-full --}}
      <div class="flex-1 flex">
        {{ $slot }}
      </div>
    </main>
  </div>
</body>
</html>
