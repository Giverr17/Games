<div>
    <h1 class="text-center text-2xl font-bold mb-2">Tic Tac Toe</h1>
    <div class="p-2 bg-gray-800 text-white rounded-lg text-sm w-full max-w-xs mx-auto">
        <h2 class="text-lg font-bold text-center">Scoreboard</h2>

        <div class="grid grid-cols-2 gap-2 text-center mt-2">
            <p>🏆 Player: <span class="font-semibold">{{ $playerWins }}</span></p>
            <p>🤖 AI: <span class="font-semibold">{{ $aiWins }}</span></p>
            <p>🎯 Draws: <span class="font-semibold">{{ $draws }}</span></p>
            <p>🔥 Streak: <span class="font-semibold">{{ $playerStreak }}</span></p>
        </div>

        @if ($playerStreak >= 3)
            <p class="text-yellow-400 text-center mt-1 animate-pulse">🔥 Winning Streak!</p>
        @endif

        <button wire:click="resetScores()" class="mt-3 bg-red-500 px-2 py-1 text-xs rounded w-full">Reset</button>
    </div>

    <div class="mode flex justify-center space-x-4 mb-4">
        @if ($difficulty)
            <div class="mode grid place-items-center mb-2">
                <button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                    wire:click="setGameMode('cpu')">
                    Change Difficulty
                </button>
            </div>
            <div class="mode grid place-items-center mb-2">
                <button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                    wire:click="setGameMode('multiplayer')">
                    Multiplayer Mode
                </button>
            </div>
        @elseif($gameMode === 'multiplayer')
            <div class="mode grid place-items-center mb-2">
                <button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                    wire:click="setGameMode('cpu')">
                    Cpu Mode
                </button>
            </div>
        @endif
    </div>
    {{-- Game Mode Selection --}}
    @if (!$gameMode)
        <div class="mode flex justify-center space-x-4 mb-1">
            <button class="mt-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                wire:click="setGameMode('multiplayer')">
                Two Players
            </button>
            <button class="mt-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                wire:click="setGameMode('cpu')">
                Play vs CPU
            </button>
        </div>
    @endif

    {{-- Difficulty Selection (Only for CPU mode) --}}
    @if ($gameMode === 'cpu' && !$difficulty)
        <div class="difficulty flex justify-center space-x-4 mb-2">
            <button class="mt-2 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
                wire:click="setDifficulty('easy')">
                Easy
            </button>
            <button class="mt-2 px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600"
                wire:click="setDifficulty('medium')">
                Medium
            </button>
            <button class="mt-2 px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
                wire:click="setDifficulty('hard')">
                Hard
            </button>
        </div>
    @endif


    @if ($difficulty)
        <div>
            <div class="text-center text-xl "> Difficulty:{{ strtoupper($difficulty) }}</div>
        </div>
    @endif

    {{-- Game Status --}}
    <div class="status mb-3 text-center">
        @if ($gameOver)
            @if ($isWinner)
                <div class="text-xl font-bold text-green-600">
                    Player {{ $isWinner }} wins!
                </div>
            @elseif($isDraw)
                <div class="text-xl font-bold text-blue-600">
                    It's a draw!
                </div>
            @endif
            <button wire:click="resetGame" class="mt-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                Play Again
            </button>
        @else
            <div class="text-lg">
                @if ($gameMode === 'cpu')
                    <span
                        class="font-bold text-red-600">{{ $currentPlayer === 'O' ? 'CPU\'s Turn' : 'Your Turn' }}</span>
                @elseif($gameMode === 'multiplayer')
                    Current Player: <span class="font-bold">{{ $currentPlayer }}</span>
                @endif
            </div>
        @endif
    </div>

    {{-- Game Board --}}
    @if (($gameMode === 'cpu' && $difficulty) || $gameMode === 'multiplayer')
        <div class="game-board mx-auto" style="width: 300px">
            @foreach ($board as $rowIndex => $row)
                <div class="flex">
                    @foreach ($row as $colIndex => $cell)
                        <div wire:click="makeMove({{ $rowIndex }}, {{ $colIndex }})"
                            class="cell flex items-center justify-center border border-gray-400 h-24 w-24 text-4xl font-bold cursor-pointer 
                {{ in_array([$rowIndex, $colIndex], $winningLine) ? 'bg-green-200' : '' }}
                {{ $cell == '' ? 'hover:bg-gray-100' : '' }}">
                            {{ $cell }}
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
        <div class="mode grid place-items-center mt-3">
            <button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600" wire:click="resetGame">
                Reset Game
            </button>
        </div>
</div>
@endif
