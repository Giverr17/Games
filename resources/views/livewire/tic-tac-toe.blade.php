<div>
    <h1 class="text-center text-2xl font-bold mb-4">Tic Tac Toe</h1>

    <div class="status mb-4 text-center">
        @if ($gameOver)
            @if ($isWinner)
                <div class="text-xl font-bold">Player {{ $isWinner }} wins!</div>
            @elseif($isDraw)
                <div class="text-xl font-bold">It's a draw!</div>
            @endif
            <button wire:click="resetGame" class="mt-2 px-4 py-2 bg-blue-500 text-white rounded">Play Again</button>
        @else
            <div class="text-lg">Current Player: <span class="font-bold">{{ $currentPlayer }}</span></div>
        @endif
    </div>

    <div class="game-board mx-auto" style="width: 300px">
        @foreach ($board as $rowIndex =>$row)
            <div class="flex">
                @foreach ($row as $colIndex =>$cell)
                    <div wire:click="makeMove({{ $rowIndex }},{{ $colIndex }})"
                     class="cell flex items-center justify-center border border-gray-400 h-24 w-24 text-4xl font-bold cursor-pointer
                        {{ in_array([$rowIndex, $colIndex], $winningLine) ? 'bg-green-200' : '' }}">
                         {{ $cell }}
                    </div>  
                @endforeach
            </div>
        @endforeach
    </div>


</div>
