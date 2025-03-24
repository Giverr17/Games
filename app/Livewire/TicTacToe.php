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
    protected $listeners = ['gameUpdated' => '$refresh'];


    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {

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
    }

    public function makeMove($rol, $col)
    {
        if ($this->gameOver || $this->board[$rol][$col] !== '') {
            return;
        }
        $this->board[$rol][$col] = $this->currentPlayer;

        $this->checkWinner();

        if (!$this->gameOver) {
            $this->currentPlayer = $this->currentPlayer === 'X' ? 'O' : 'X';

            // check for draw
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
            }
        }
        $this->dispatch('gameUpdated');
    }

    public function checkWinner()
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

            if ($values[0] !== '' && $values[0] === $values[1] && $values[1] === $values[2]) {
                $this->gameOver = true;
                $this->isWinner = $values[0];
                $this->winningLine = $pattern;
                return;
            }
        }
    }


    public function render()
    {
        return view('livewire.tic-tac-toe');
    }
}
