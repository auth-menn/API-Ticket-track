<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketReplyStoreRequest;
use App\Http\Resources\TicketReplyResource;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\TicketResource;
use App\Models\TicketReply;

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

public function show($code)
{
    try {
        $ticket = Ticket::where('code', $code)->first();

        if (!$ticket) {
            return response()->json([
                'message' => 'Ticket not found'
            ], 404);
        }

        if (auth()->user()->role == 'user' && $ticket->user_id != auth()->user()->id) {
            return response()->json([
                'message' => 'Unauthorized access to this ticket'
            ], 403);
        }

        return response()->json([
            'message' => 'Ticket retrieved successfully',
            'data' => new TicketResource($ticket)
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to retrieve ticket',
            'error' => $e->getMessage()
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

public function storeReply(TicketReplyStoreRequest $request, $code)
{
    $data = $request->validated();


    DB::beginTransaction();

    try {
       $ticket = Ticket::where('code', $code)->first();

        if (!$ticket) {
            return response()->json([
                'message' => 'Ticket not found'
            ], 404);
        }

        if (auth()->user()->role == 'user' && $ticket->user_id != auth()->user()->id) {
            return response()->json([
                'message' => 'Unauthorized access to this ticket'
            ], 403);
        }

       $ticketReply = new TicketReply();
        $ticketReply->ticket_id = $ticket->id; 
        $ticketReply->user_id = auth()->user()->id;
        $ticketReply->content = $data['content'];   
        $ticketReply->save();

        if (auth()->user()->role == 'admin') {
            $ticket->status = $data['status'];
            if ($data['status'] == 'resolved') {
                $ticket->completed_at = now();
            } else {
                $ticket->completed_at = null;
            }   
            $ticket->save();
        }

        DB::commit();

        return response()->json([
            'message' => 'Reply added successfully',
            'data' => new TicketReplyResource($ticketReply)
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Failed to add reply',
            'error' => $e->getMessage()
        ], 500);
    }

}

}