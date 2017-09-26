<?php

namespace App\Http\Controllers;

use App\Board;
use Illuminate\Http\Request;
use App\Events\{BoardsUpdated, BoardCreated};

class BoardController extends Controller
{
    public function index()
    {
        return response()->json(Board::all()->toArray());
    }

    public function create(Request $request)
    {
        $data = $request->validate(['name' => 'required|between:1,50']);

        $board = Board::create($data + ['cards' => []]);

        event(new BoardCreated($board));

        return response()->json(['status' => $board ? 'success' : 'error', 'board' => $board]);
    }

    public function update(Request $request)
    {
        foreach ($request->get('boards') as $board) {
            Board::findOrFail($board['id'])->update($board);
        }

        event(new BoardsUpdated(Board::all()->toArray()));

        return response()->json(["status" => "success"]);
    }
}
