<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\TicketResource;

class TicketController extends Controller
{

public function index(Request $request)
{
    try {
        $query = Ticket::query();

        $query->orderBy('created_at', 'desc');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                  ->orWhere('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if (auth()->user()->role == 'user') {
            $query->where('user_id', auth()->user()->id);
        }

        $tickets = $query->get();

        return response()->json([
            'message' => 'Tickets retrieved successfully',
            'data' => TicketResource::collection($tickets)
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to retrieve tickets',
            'error' => $e->getMessage(), 
        ], 500);
    }
}


 public function store(Request $request)
{
    $data = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'priority' => 'required|in:low,medium,high',
    ]);

    DB::beginTransaction();

    try {
        $ticket = new Ticket;
        $ticket->user_id = auth()->user()->id;
        $ticket->code = 'TIC-' . rand(10000, 99999);
        $ticket->title = $data['title'];
        $ticket->description = $data['description'];
        $ticket->priority = $data['priority'];
        $ticket->save();

        DB::commit();

        return response()->json([
            'message' => 'Ticket created successfully',
            'data' => new TicketResource($ticket)
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Failed to create ticket',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
