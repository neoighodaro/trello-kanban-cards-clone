<?php

namespace App\Http\Controllers;

use App\Board;
use Illuminate\Http\Request;
use App\Events\BoardCardCreated;

class BoardCardController extends Controller
{
    public function create(Request $request, $id)
    {
        $data = $request->validate(['cards' => 'required']);

        $board = Board::findOrFail($id);

        $updated = $board->update($data);

        event(new BoardCardCreated($board));

        return response()->json(['status' => $updated ? 'success' : 'error']);
    }
}
