<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/png" href="/images/favicon.png">
        <title inertia>{{ config('app.name', 'PT INTEL CREATIVE ERP') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
        <style>
            html, body {
                font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
                font-feature-settings: 'tnum' 1;
                font-variant-numeric: tabular-nums;
            }
        </style>
    </head>
    <body class="h-full antialiased text-slate-800">
        @inertia
    </body>
</html>

