<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Tic Tac Toe Game' }}</title>
    
    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    
    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
    
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto py-6 px-4">
        <header class="mb-6">
            <!-- Header content if needed -->
        </header>
        
        <main>
            {{ $slot }}
        </main>
        
        <footer class="mt-8 text-center text-gray-500 text-sm">
            <!-- Footer content if needed -->
        </footer>
    </div>
    
    @livewireScripts
</body>
</html>