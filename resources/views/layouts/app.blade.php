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
  <div class="min-h-screen flex flex-col bg-slate-50">

    {{-- HEADER BAND --}}
    <div class="bg-slate-100 border-b border-slate-200">
      @if(!View::hasSection('hide_nav'))
      @if(view()->exists('navigation-menu'))
      @include('navigation-menu')
      @elseif(view()->exists('layouts.navigation'))
      @include('layouts.navigation')
      @endif
      @endif


    </div>

    {{-- CONTENIDO --}}
    <main class="flex-1">
      {{ $slot }}
    </main>

  </div>

</body>

</html>