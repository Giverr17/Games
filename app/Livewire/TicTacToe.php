<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Illuminate\Support\Str;
use PhpParser\Node\Expr\Cast;

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
    protected $listeners = ['gameUpdated' => '$refresh', 'gameCreated' => '$refresh'];
    public $playerWins, $aiWins, $draws, $playerStreak;

    public $gameId = null;
    public $joinGameId = null; 
    public $isWaiting = false;
    public $isHost = false;
    public $lastUpdated=null;
    public $lastPoll = null;
    public $opponentActive = true;
    protected $layout = 'components.layouts.app';

     // Add this for real-time validation
     protected $rules = [
        'joinGameId' => 'required|string|min:8|max:8',
    ];

    public function mount($gameId=null)
    {
        // Initialize session values if they don't exist
        if (!session()->has('playerWins')) {
            session()->put('playerWins', 0);
        }
        if (!session()->has('aiWins')) {
            session()->put('aiWins', 0);
        }
        if (!session()->has('draws')) {
            session()->put('draws', 0);
        }
        if (!session()->has('playerStreak')) {
            session()->put('playerStreak', 0);
        }

        // Get the values from session
        $this->playerWins = (int)session('playerWins', 0);
        $this->aiWins = (int)session('aiWins', 0);
        $this->draws = (int)session('draws', 0);
        $this->playerStreak = (int)session('playerStreak', 0);

        if ($gameId) {
            // $this->gameId = $gameId;
            $this->joinGame($gameId);
        }
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

         // If this is a multiplayer game, update the cache
    if ($this->gameId) {
        $this->updateGameCache();
    }   
    }

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

    public function createMultiplayerGame()
    {

        // Generate a unique game ID\
        $this->gameId = Str::random(8);
        $this->isHost = true;
        $this->isWaiting = true;
        $this->lastUpdated = now();

        // Initialize the game in cache
        $this->updateGameCache();

        $this->gameMode = 'multiplayer';
        $this->dispatch('gameCreated', $this->gameId);
    }

    public function updateGameCache()
    {
        if (!$this->gameId) return;
        
        Cache::put('tictactoe_' . $this->gameId, [
            'board' => $this->board,
            'currentPlayer' => $this->currentPlayer,
            'isWinner' => $this->isWinner,
            'gameOver' => $this->gameOver,
            'isDraw' => $this->isDraw,
            'isWaiting' => $this->isWaiting ?? false,
            'winningLine' => $this->winningLine,
            'lastUpdated' => now(),
            'hostLastActive' => $this->isHost ? now() : (Cache::get('tictactoe_' . $this->gameId)['hostLastActive'] ?? now()),
            'guestLastActive' => !$this->isHost ? now() : (Cache::get('tictactoe_' . $this->gameId)['guestLastActive'] ?? null),
        ], now()->addHour());
    }


    // Add this method to join an existing game
    public function joinGame($gameId)
    {
        $gameData = Cache::get('tictactoe_' . $gameId);
        if (!$gameData) {
            session()->flash('error', 'game not found or user failed to connecct');
            return redirect()->route('tictactoe');
        }

        $this->gameId = $gameId;
        $this->isHost = false;
        $this->board = $gameData['board'];
        $this->currentPlayer = $gameData['currentPlayer'];
        $this->isWinner = $gameData['isWinner'];
        $this->isDraw = $gameData['isDraw'];
        $this->winningLine = $gameData['winningLine'];
        $this->gameOver = $gameData['gameOver'];
        $this->gameMode = 'multiplayer';
        $this->lastUpdated = $gameData['lastUpdated'];

        // Mark game as joined by second player
        if ($gameData['isWaiting'] ?? true) {
            $gameData['isWaiting'] = false;
            Cache::put('tictactoe_' . $this->gameId, $gameData, now()->addHour());
        }
    }

  

    public function makeMove($row, $col)
    {
        // Don't allow moves if game is over or cell is already filled
        if ($this->gameOver || $this->board[$row][$col] !== '') {
            return;
        }

        //multiplayer mode

        if ($this->gameId) {
            $gameData = Cache::get('tictactoe_' . $this->gameId);

            //Refresh local state with the lastest from cache

            $this->board = $gameData['board'];
            $this->currentPlayer = $gameData['currentPlayer'];
            $this->gameOver = $gameData['gameOver'];

            // Don't allow moves if game is over or cell is already filled
            if ($this->gameOver || $this->board[$row][$col] !== '') {
                return;
            }

            // Check if it's this player's turn
            $playerSymbol=$this->isHost ?'X':'O';
            if($this->currentPlayer !== $playerSymbol){
                return;  //Not this player's turn
            }
        }

        // Player makes a move
        $this->board[$row][$col] = $this->currentPlayer;

        // Check if the game is over after player's move
        $this->checkGameStatus();

        // If game is not over, process next steps
        if (!$this->gameOver) {
            if ($this->gameMode === 'cpu' && $this->difficulty) {
                // In CPU mode, let AI make a move
                $this->currentPlayer = 'O';
                $this->cpuMove();

                // After AI moves, check game status again
                $this->checkGameStatus();

                // Switch back to player X for next turn if game is still ongoing
                if (!$this->gameOver) {
                    $this->currentPlayer = 'X';
                }
            } else {
                // For multiplayer, just switch players
                $this->currentPlayer = ($this->currentPlayer === 'X') ? 'O' : 'X';
            }
        }   

          if ($this->gameId) {
            $this->updateGameCache();
            $this->updatePlayerActivity();
        }


        $this->dispatch('gameUpdated');
    }

    public function updatePlayerActivity()
    {
        if (!$this->gameId) return;
        
        $gameData = Cache::get('tictactoe_' . $this->gameId);
        if (!$gameData) return;
        
        if ($this->isHost) {
            $gameData['hostLastActive'] = now();
        } else {
            $gameData['guestLastActive'] = now();
        }
        
        Cache::put('tictactoe_' . $this->gameId, $gameData, now()->addHour());
    }

    public function refreshGameState()
    {
        if (!$this->gameId) return;
        
        $gameData = Cache::get('tictactoe_' . $this->gameId);
        if (!$gameData) return;
        
        $this->board = $gameData['board'];
        $this->currentPlayer = $gameData['currentPlayer'];
        $this->isWinner = $gameData['isWinner'];
        $this->isDraw = $gameData['isDraw'];
        $this->winningLine = $gameData['winningLine'];
        $this->gameOver = $gameData['gameOver'];
        $this->isWaiting = $gameData['isWaiting'] ?? false;
        $this->lastUpdated = $gameData['lastUpdated'];
    }


    public function pollGameState()
    {
        if (!$this->gameId) return;
        
        // Update player activity status
        $this->updatePlayerActivity();
        
        // Get latest game data
        $gameData = Cache::get('tictactoe_' . $this->gameId);
        if (!$gameData) {
            session()->flash('error', 'Game has expired or was ended by the other player');
            return redirect()->route('tictactoe');
        }

        // Check opponent activity
        $opponentLastActive = $this->isHost ? 
            ($gameData['guestLastActive'] ?? null) : 
            ($gameData['hostLastActive'] ?? now());
            
        // Set opponent as inactive if they haven't polled in 10 seconds
        if ($opponentLastActive && now()->diffInSeconds($opponentLastActive) > 10) {
            $this->opponentActive = false;
        } else {
            $this->opponentActive = true;
        }

        // Only update local state if remote state is newer
        if ($gameData['lastUpdated'] > ($this->lastUpdated ?? now()->subMinutes(5))) {
            $this->refreshGameState();
        }
        
        $this->dispatch('gameUpdated');
    }


    public function cpuMove()
    {
        if (!$this->difficulty || $this->gameOver) {
            return;
        }

        switch ($this->difficulty) {
            case 'easy':
                $this->makeEasyMove();
                break;
            case 'medium':
                $this->makeMediumMove();
                break;
            case 'hard':
                $this->makeHardMove();
                break;
        }
    }

    // Easy AI - completely random moves
    private function makeEasyMove()
    {
        $emptyCells = $this->getEmptyCells();
        if (!empty($emptyCells)) {
            $randomMove = $emptyCells[array_rand($emptyCells)];
            $this->board[$randomMove['row']][$randomMove['col']] = 'O';
        }
    }

    // Medium AI - prioritizes winning, blocking, center, corners
    private function makeMediumMove()
    {
        // Try to win
        $winMove = $this->findWinningMove('O');
        if ($winMove) {
            $this->board[$winMove['row']][$winMove['col']] = 'O';
            return;
        }

        // Block player's winning move
        $blockMove = $this->findWinningMove('X');
        if ($blockMove) {
            $this->board[$blockMove['row']][$blockMove['col']] = 'O';
            return;
        }

        // Take center if available
        if ($this->board[1][1] === '') {
            $this->board[1][1] = 'O';
            return;
        }

        // Take corners if available
        $corners = [[0, 0], [0, 2], [2, 0], [2, 2]];
        shuffle($corners);
        foreach ($corners as $corner) {
            if ($this->board[$corner[0]][$corner[1]] === '') {
                $this->board[$corner[0]][$corner[1]] = 'O';
                return;
            }
        }

        // If no strategic move is available, make a random move
        $this->makeEasyMove();
    }

    // Hard AI - uses minimax with alpha-beta pruning
    private function makeHardMove()
    {
        // First check for winning move (priority 1)
        $winMove = $this->findWinningMove('O');
        if ($winMove) {
            $this->board[$winMove['row']][$winMove['col']] = 'O';
            return;
        }

        // Then check for blocking move (priority 2)
        $blockMove = $this->findWinningMove('X');
        if ($blockMove) {
            $this->board[$blockMove['row']][$blockMove['col']] = 'O';
            return;
        }

        // If no immediate winning or blocking moves, use minimax
        $bestScore = -INF;
        $bestMove = null;
        $secondBestMove = null;

        foreach ($this->getEmptyCells() as $cell) {
            $row = $cell['row'];
            $col = $cell['col'];

            // Try this move
            $this->board[$row][$col] = 'O';
            $score = $this->minimax(0, false, -INF, INF);
            $this->board[$row][$col] = ''; // Undo the move

            if ($score > $bestScore) {
                $secondBestMove = $bestMove;
                $bestScore = $score;
                $bestMove = ['row' => $row, 'col' => $col];
            }
        }

        // Add some randomness (20% chance to pick second-best move)
        if ($secondBestMove !== null && rand(0, 100) < 20) {
            $bestMove = $secondBestMove;
        }

        // Execute the best move found
        if ($bestMove !== null) {
            $this->board[$bestMove['row']][$bestMove['col']] = 'O';
        }
    }

    public function getEmptyCells()
    {
        $emptyCells = [];
        for ($row = 0; $row < 3; $row++) {
            for ($col = 0; $col < 3; $col++) {
                if ($this->board[$row][$col] === '') {
                    $emptyCells[] = ['row' => $row, 'col' => $col];
                }
            }
        }
        shuffle($emptyCells); // Randomize order for variety
        return $emptyCells;
    }

    private function minimax($depth, $isMaximizing, $alpha, $beta)
    {
        $max_depth = 6;

        if ($depth >= $max_depth) {
            return $this->evaluateBoard();
        }

        // Terminal states
        if ($this->checkWinner('O')) return 10 - $depth; // AI wins (prefer quicker wins)
        if ($this->checkWinner('X')) return -10 + $depth; // Player wins (prefer later losses)

        // Check for draw
        $isDraw = true;
        for ($row = 0; $row < 3; $row++) {
            for ($col = 0; $col < 3; $col++) {
                if ($this->board[$row][$col] === '') {
                    $isDraw = false;
                    break 2;
                }
            }
        }
        if ($isDraw) return 0;

        // Maximizing player (AI)
        if ($isMaximizing) {
            $bestScore = -INF;
            for ($row = 0; $row < 3; $row++) {
                for ($col = 0; $col < 3; $col++) {
                    if ($this->board[$row][$col] === '') {
                        $this->board[$row][$col] = 'O';
                        $score = $this->minimax($depth + 1, false, $alpha, $beta);
                        $this->board[$row][$col] = '';
                        $bestScore = max($score, $bestScore);
                        $alpha = max($alpha, $bestScore);
                        if ($beta <= $alpha) {
                            break 2; // Alpha-beta pruning
                        }
                    }
                }
            }
            return $bestScore;
        }
        // Minimizing player (human)
        else {
            $bestScore = INF;
            for ($row = 0; $row < 3; $row++) {
                for ($col = 0; $col < 3; $col++) {
                    if ($this->board[$row][$col] === '') {
                        $this->board[$row][$col] = 'X';
                        $score = $this->minimax($depth + 1, true, $alpha, $beta);
                        $this->board[$row][$col] = '';
                        $bestScore = min($score, $bestScore);
                        $beta = min($beta, $bestScore);
                        if ($beta <= $alpha) {
                            break 2; // Alpha-beta pruning
                        }
                    }
                }
            }
            return $bestScore;
        }
    }

    private function evaluateBoard()
    {
        if ($this->checkWinner('O')) return 10;
        if ($this->checkWinner('X')) return -10;

        // Check for draw
        $isDraw = true;
        for ($row = 0; $row < 3; $row++) {
            for ($col = 0; $col < 3; $col++) {
                if ($this->board[$row][$col] === '') {
                    $isDraw = false;
                    break 2;
                }
            }
        }

        return $isDraw ? 0 : 0; // Return 0 for non-terminal states with no advantage
    }

    private function checkGameStatus($evaluation = false)
    {
        // Skip if already evaluated
        if ($this->gameOver && !$evaluation) {
            return null;
        }

        // Only update session values when not in evaluation mode
        if (!$evaluation) {
            // Always refresh local properties from session first
            $this->aiWins = (int)session('aiWins', 0);
            $this->playerWins = (int)session('playerWins', 0);
            $this->draws = (int)session('draws', 0);
            $this->playerStreak = (int)session('playerStreak', 0);
        }

        // Check if O (AI) wins
        if ($this->checkWinner('O')) {
            if (!$evaluation) {
                $this->aiWins += 1;
                $this->playerStreak = 0;
                session()->put('aiWins', $this->aiWins);
                session()->put('playerStreak', 0);
                $this->isWinner = 'O';
                $this->gameOver = true;
                $this->dispatch('gameUpdated');
            }
            return 10;
        }

        // Check if X (Player) wins
        if ($this->checkWinner('X')) {
            if (!$evaluation) {
                $this->playerWins += 1;
                $this->playerStreak += 1;
                session()->put('playerWins', $this->playerWins);
                session()->put('playerStreak', $this->playerStreak);
                $this->isWinner = 'X';
                $this->gameOver = true;
                $this->dispatch('gameUpdated');
            }
            return -10;
        }

        // Check for draw
        $isDraw = true;
        for ($row = 0; $row < 3; $row++) {
            for ($col = 0; $col < 3; $col++) {
                if ($this->board[$row][$col] === '') {
                    $isDraw = false;
                    break 2;
                }
            }
        }

        if ($isDraw) {
            if (!$evaluation) {
                $this->draws += 1;
                session()->put('draws', $this->draws);
                $this->gameOver = true;
                $this->isDraw = true;
                $this->dispatch('gameUpdated');
            }
            return 0;
        }

        return null;
    }

    public function findWinningMove($player)
    {
        $winPatterns = [
            // Rows
            [[0, 0], [0, 1], [0, 2]],
            [[1, 0], [1, 1], [1, 2]],
            [[2, 0], [2, 1], [2, 2]],
            // Columns
            [[0, 0], [1, 0], [2, 0]],
            [[0, 1], [1, 1], [2, 1]],
            [[0, 2], [1, 2], [2, 2]],
            // Diagonals
            [[0, 0], [1, 1], [2, 2]],
            [[0, 2], [1, 1], [2, 0]],
        ];

        foreach ($winPatterns as $pattern) {
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
        $winPatterns = [
            // Rows
            [[0, 0], [0, 1], [0, 2]],
            [[1, 0], [1, 1], [1, 2]],
            [[2, 0], [2, 1], [2, 2]],
            // Columns
            [[0, 0], [1, 0], [2, 0]],
            [[0, 1], [1, 1], [2, 1]],
            [[0, 2], [1, 2], [2, 2]],
            // Diagonals
            [[0, 0], [1, 1], [2, 2]],
            [[0, 2], [1, 1], [2, 0]],
        ];

        foreach ($winPatterns as $pattern) {
            $values = [];
            foreach ($pattern as [$r, $c]) {
                $values[] = $this->board[$r][$c];
            }

            if ($values[0] !== '' && $values[0] === $values[1] && $values[1] === $values[2]) {
                $winner = $values[0];

                if ($specificPlayer !== null) {
                    return $winner === $specificPlayer;
                }

                $this->winningLine = $pattern;
                return $winner;
            }
        }

        return false;
    }

    public function resetScores()
    {
        // Reset session values
        session()->put('playerWins', 0);
        session()->put('aiWins', 0);
        session()->put('draws', 0);
        session()->put('playerStreak', 0);

        // Reset component properties
        $this->playerWins = 0;
        $this->aiWins = 0;
        $this->draws = 0;
        $this->playerStreak = 0;

        // Reset the game
        $this->resetGame();
    }

    public function render()
    {
        return view('livewire.tic-tac-toe');
    }
}
