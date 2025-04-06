<?php

use App\Livewire\TicTacToe;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('game');
})->name('tictactoe');

Route::get('/{gameId}', function ($gameId) {
    return view('game', ['gameId' => $gameId]);
})->name('tictactoe.join');
