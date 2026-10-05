<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

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

        // Publish a message for the notification service.
        // If Redis is down, the booking still succeeds.
        try {
            Redis::rpush('notifications', json_encode([
                'type' => 'booking.created',
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'email' => $request->attributes->get('email'),
                'slot_number' => $booking->slot_number,
                'vehicle_number' => $booking->vehicle_number,
            ]));
        } catch (Throwable $e) {
            Log::warning('Could not queue notification: '.$e->getMessage());
        }

        return response()->json($booking, 201);
    }
}
