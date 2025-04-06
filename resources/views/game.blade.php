<!DOCTYPE html>
<html>
<head>
    <title>Tic Tac Toe</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    @livewireStyles
</head>
<body class="bg-gray-100 py-10">
    <div class="container mx-auto">
        @livewire('tic-tac-toe',['gameId' => $gameId ?? null])
    </div>
    
    @livewireScripts
</body>
</html>