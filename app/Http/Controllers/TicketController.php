<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordRecoveryRequested;
use App\Models\PasswordTicket;
use App\Models\User; 
use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 



class TicketController extends Controller
{
    public function storeRecoveryTicket(Request $request)
    {
        $validatedData = $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $user = User::where('email', $validatedData['email'])->first();

        $ticket = PasswordTicket::create([
            'user_id' => $user ? $user->id : null, 
            'username_provided' => $validatedData['username'],
            'email_provided' => $validatedData['email'],
            'status' => 'pending',
        ]);
        

        $adminEmail = env('ADMIN_EMAIL_FOR_TICKETS', 'default@support.com'); 
        Mail::to($adminEmail)->send(new PasswordRecoveryRequested($ticket));
        
        return response()->json([
            'message' => '¡Solicitud registrada! Un administrador procesará tu ticket.',
            'ticket_id' => $ticket->id,
        ], 201); 
    }
}