<?php

namespace App\Livewire;

use Livewire\Component;

class TicTacToe extends Component
{
    public $board = [
        ['', '', ''],
        ['', '', ''],
        ['', '', ''],
    ];
    public $currentPlayer = 'X';
    public $isWinner = null;
    public $gameOver = false;
    public $winningLine = [];
    public $isDraw = false;
    public $difficulty = null;
    public $gameMode = null;
    protected $listeners = ['gameUpdated' => '$refresh'];


    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $currentMode = $this->gameMode;
        $currentDifficulty = $this->difficulty;
        $this->board = [
            ['', '', ''],
            ['', '', ''],
            ['', '', ''],
        ];

        $this->isWinner = null;
        $this->isDraw = false;
        $this->currentPlayer = 'X';
        $this->winningLine = [];
        $this->gameOver = false;

        $this->difficulty = $currentDifficulty;
        $this->gameMode = $currentMode;
    }

    // public function checkCpuTurn()
    // {
    //     if ($this->gameMode === 'cpu' && $this->currentPlayer === 'O' && !$this->gameOver) {
    //         $this->cpuMove();
    //     }
    // }
    public function setGameMode($mode)
    {
        $this->gameMode = $mode;
        $this->difficulty = null;
        $this->currentPlayer = 'X'; // Always start with player X
        $this->dispatch('gameUpdated');
        $this->resetGame();
    }
    public function setDifficulty($difficulty)
    {
        if ($this->gameMode === 'cpu') {
            $this->difficulty = $difficulty;
            $this->resetGame();
        }
    }
    public function makeMove($row, $col)
    {
        if ($this->gameOver || $this->board[$row][$col] !== '') {
            return;
        }
        $this->board[$row][$col] = $this->currentPlayer;

        $this->checkWinner();

        //if the game is not over,switch the player
        if (!$this->gameOver) {

            // If game is not over and in CPU mode
            if ($this->gameMode === 'cpu') {
                // Switch to CPU player if it's not already the CPU's turn

                $this->currentPlayer = 'O';
                if ($this->difficulty) {
                    $this->cpuMove();
                }
            } else {
                // For multiplayer, just switch players
                $this->currentPlayer = $this->currentPlayer === 'X' ? 'O' : 'X';
            }


            // check for draw
            //assume the game is already drawn and keep checking until false
            $isDraw = true;
            foreach ($this->board as $row) {
                foreach ($row as $cell) {
                    if ($cell == '') {
                        $isDraw = false;
                        break 2;
                    }
                }
            }
            if ($isDraw) {
                $this->gameOver = true;
                $this->isDraw = true;
                // $this->resetGame();
                $this->dispatch('gameUpdated');
            }
        }
    }

    public function cpuMove()
    {
        if (!$this->difficulty) {
            return;
        }
        switch ($this->difficulty) {
            case 'easy':
                $this->easy();
                break;
            case 'medium':
                $this->medium();
                break;
            case 'hard':
                $this->hard();
                break;
        }
        $this->checkWinner();
        $this->currentPlayer = 'X';
        $this->dispatch('gameUpdated');
    }



    public function easy()
    {
        $emptyCells = [];
        for ($row = 0; $row < 3; $row++) {
            for ($col = 0; $col < 3; $col++) {
                if ($this->board[$row][$col] === '') {
                    $emptyCells[] = ['row' => $row, 'col' => $col];
                }
            }
        }
        if (!empty($emptyCells)) {
            $randomMove = $emptyCells[array_rand($emptyCells)];
            $this->board[$randomMove['row']][$randomMove['col']] = 'O';
        }
    }

    public function medium()
    {
        //cpu try to win
        $winMove = $this->findWinningMove('O');
        if (is_array($winMove) && isset($winMove['row'], $winMove['col'])) {
            $this->board[$winMove['row']][$winMove['col']] = 'O';
            return;
        }

        //block user's move

        $blockMove = $this->findWinningMove('X');
        if (is_array($blockMove) && isset($blockMove['row'], $blockMove['col'])) {
            $this->board[$blockMove['row']][$blockMove['col']] = 'O';
            return;
        }

        //take center if possible

        if ($this->board[1][1] === '') {
            $this->board[1][1] = 'O';
            return;
        }

        //take corners 

        $corners = [[0, 0], [0, 2], [2, 0], [2, 2]];
        shuffle($corners);
        foreach ($corners as $corner) {
            if ($this->board[$corner[0]][$corner[1]] === '') {
                $this->board[$corner[0]][$corner[1]] = 'O';
                return;
            }
        }
        // fallback to easy
        $this->easy();
    }

    public function hard()
    {
        $bestScore = -INF;
        $bestMove = null;
        $secondBestMove = null;
    
        // Step 1: Check for immediate winning move (AI's turn)
        foreach ($this->getEmptyCells() as $cell) {
            $row = $cell['row'];
            $col = $cell['col'];
            $this->board[$row][$col] = 'O';
            if ($this->checkGameStatus() === 10) { // AI wins
                return $this->board[$row][$col] = 'O';
            }
            $this->board[$row][$col] = ''; // Undo
        }
    
        // Step 2: Check for immediate blocking move (Player's turn)
        foreach ($this->getEmptyCells() as $cell) {
            $row = $cell['row'];
            $col = $cell['col'];
            $this->board[$row][$col] = 'X';
            if ($this->checkGameStatus() === -10) { // Player would win
                return $this->board[$row][$col] = 'O'; // BLOCK immediately
            }
            $this->board[$row][$col] = ''; // Undo
        }
    
        // Step 3: Normal Minimax Evaluation
        foreach ($this->getEmptyCells() as $cell) {
            $row = $cell['row'];
            $col = $cell['col'];
            $this->board[$row][$col] = 'O';
            $score = $this->minimax(0, false, -INF, INF);
            $this->board[$row][$col] = '';
    
            if ($score > $bestScore) {
                $secondBestMove = $bestMove;
                $bestScore = $score;
                $bestMove = ['row' => $row, 'col' => $col];
            }
        }
    
        // Step 4: Randomness (20% chance to pick second-best move)
        if ($secondBestMove !== null && rand(0, 100) < 20) {
            $bestMove = $secondBestMove;
        }
    
        // Step 5: Execute the best move
        if ($bestMove) {
            $this->board[$bestMove['row']][$bestMove['col']] = 'O';
        }
    }
    

    public function getEmptyCells(){
        $emptyCells=[];
        for($row=0;$row<3;$row++){
            for($col=0;$col<3;$col++){
                if($this->board[$row][$col]===''){
                    $emptyCells[]=['row'=>$row,'col'=>$col];
                }
            }
        }
        shuffle($emptyCells);
        return $emptyCells;
    }

    private function minimax($depth, $isMaximizing, $alpha, $beta)
    {
        $max_depth = 3;

        if ($depth >= $max_depth) {
            return $this->evaluateBoard();
        }

        //To check winner
        $result = $this->checkGameStatus();

        if ($result !== null) {
            return $result;
        }
        // If it's the CPU's turn (maximizing player)
        if ($isMaximizing) {
            $bestScore = -INF; // Start with the worst possible score
            //try every cell
            for ($row = 0; $row < 3; $row++) {
                for ($col = 0; $col < 3; $col++) {
                    if ($this->board[$row][$col] === '') {
                        $this->board[$row][$col] = 'O';
                        // Ask: "If I make this move, what's the best the player can do?"
                        $score = $this->minimax($depth + 1, false, $alpha, $beta);
                        $this->board[$row][$col] = '';
                        // keep the highest score
                        $bestScore = max($score, $bestScore);
                        $alpha = max($alpha, $bestScore);
                        if ($beta <= $alpha) {
                            break 2; // Prune the remaining branches
                        }
                    }
                }
            }
            return $bestScore;
        }
        // If it's the player's turn (minimizing player)
        else {
            $bestScore = INF; // Start with the worst possible score

            //try every possible cell

            for ($row = 0; $row < 3; $row++) {
                for ($col = 0; $col < 3; $col++) {
                    if ($this->board[$row][$col] == '') {
                        $this->board[$row][$col] = 'X';
                        // Ask: "If the player makes this move, what's the best I can do?"
                        $score = $this->minimax($depth + 1, true, $alpha, $beta);

                        // Undo the move (reset the cell)
                        $this->board[$row][$col] = '';

                        // Keep the lowest score
                        $bestScore = min($score, $bestScore);
                        $beta = min($beta, $bestScore);
                        if ($beta <= $alpha) {
                            break 2;  // Prune the remaining branches
                        }
                    }
                }
            }
            return $bestScore;
        }
    }

    private function evaluateBoard(){
        if($this->checkGameStatus()===10){
            return 10;
        }
        if($this->checkGameStatus()===-10){
            return -10;
        }
        return 0;
    }

    private function checkGameStatus()
    {
        if ($this->checkWinner('O')) {
            return 10;
        }
        if ($this->checkWinner('X')) {
            return -10;
        }

        $isDraw = true;
        for ($row = 0; $row < 3; $row++) {
            for ($col = 0; $col < 3; $col++) {
                if ($this->board[$row][$col] === '') {
                    $isDraw = false;
                    break 2;
                }
            }
        }
        return $isDraw ? 0 : null;
    }


    public function findWinningMove($player)
    {
        $winPattern = [
            [[0, 0], [0, 1], [0, 2]],
            [[1, 0], [1, 1], [1, 2]],
            [[2, 0], [2, 1], [2, 2]],
            [[0, 0], [1, 0], [2, 0]],
            [[0, 1], [1, 1], [2, 1]],
            [[0, 2], [1, 2], [2, 2]],
            [[0, 0], [1, 1], [2, 2]],
            [[0, 2], [1, 1], [2, 0]],
        ];

        foreach ($winPattern as $pattern) {
            $playerCount = 0;
            $emptyCell = null;
            foreach ($pattern as [$r, $c]) {
                if ($this->board[$r][$c] === $player) {
                    $playerCount++;
                } elseif ($this->board[$r][$c] === '') {
                    $emptyCell = ['row' => $r, 'col' => $c];
                }
            }
            // If there are exactly 2 player marks and 1 empty cell, return the empty cell
            if ($playerCount === 2 && $emptyCell !== null) {
                return $emptyCell;
            }
        }
        return null;
    }



    public function checkWinner($specificPlayer = null)
    {
        $winPattern = [
            //check for rows
            [[0, 0], [0, 1], [0, 2]],
            [[1, 0], [1, 1], [1, 2]],
            [[2, 0], [2, 1], [2, 2]],

            //check for cols
            [[0, 0], [1, 0], [2, 0]],
            [[0, 1], [1, 1], [2, 1]],
            [[0, 2], [1, 2], [2, 2]],

            //for diagonal
            [[0, 0], [1, 1], [2, 2]],
            [[0, 2], [1, 1], [2, 0]],
        ];

        //loop for each patterns

        foreach ($winPattern as $pattern) {
            $values = [];
            foreach ($pattern as [$r, $c]) {
                $values[] = $this->board[$r][$c];
            }

            //final winner
            if ($values[0] !== '' && $values[0] === $values[1] && $values[1] === $values[2]) {
                //Check if any cell is empty and match each cell to get the correct pattern of the winning line
                $winner = $values[0];
                if ($specificPlayer !== null) {
                    return $winner ===  $specificPlayer;
                }
                $this->gameOver = true;
                $this->isWinner = $winner;
                $this->winningLine = $pattern;


                // sleep(2);
                // $this->resetGame();
                $this->dispatch('gameUpdated');
                return;
            }
        }
    }


    public function render()
    {
        return view('livewire.tic-tac-toe');
    }
}
