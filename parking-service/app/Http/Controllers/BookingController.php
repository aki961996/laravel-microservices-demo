<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        return Booking::where('user_id', $request->attributes->get('user_id'))
            ->latest()
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'slot_number' => 'required|string|max:20',
            'vehicle_number' => 'required|string|max:20',
        ]);

        $booking = Booking::create($data + [
            'user_id' => $request->attributes->get('user_id'),
        ]);

        return response()->json($booking, 201);
    }
}
