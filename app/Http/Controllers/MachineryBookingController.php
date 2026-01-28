<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MachineryBookingController extends Controller
{
    public function book(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'machine_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'price_per_day' => 'required|numeric',
        ]);

        $start = $request->start_date;
        $end = $request->end_date;

        // 🔴 Check availability
        $exists = DB::table('machinery_bookings')
            ->where('machine_id', $request->machine_id)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end]);
            })
            ->where('booking_status', '!=', 'cancelled')
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Machine already booked for selected dates'
            ], 409);
        }

        // 📆 Calculate days
        $days = (strtotime($end) - strtotime($start)) / 86400 + 1;
        $total = $days * $request->price_per_day;

        // ✅ Create booking
        $bookingId = DB::table('machinery_bookings')->insertGetId([
            'user_id' => $request->user_id,
            'machine_id' => $request->machine_id,
            'start_date' => $start,
            'end_date' => $end,
            'price_per_day' => $request->price_per_day,
            'total_amount' => $total,
            'booking_status' => 'pending',
            'payment_status' => 'pending',
            'created_at' => now(),
        ]);

        return response()->json([
            'booking_id' => $bookingId,
            'total_amount' => $total
        ]);
    }
}
