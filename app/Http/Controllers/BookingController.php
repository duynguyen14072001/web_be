<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    public  $status_code = 200;
    public function listBooking($user_id)
    {
        $bookings = Booking::where('user_id', $user_id)->get();
        if ($bookings) {
            return response()->json(["status" => $this->status_code, "success" => true, "message" => "List bookings", "data" => $bookings]);
        }
        return response()->json(["status" => $this->status_code, "success" => true, "message" => "No data"]);
    }

    public function createBooking(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "user_id" => 'required',
                'doctor_id' => 'required',
                'appointment_date' => 'required',
                'time_from' => 'required',
                'time_to' => 'required',
            ],
            [
                "user_id.required" => "Please enter major name",
                "doctor_id.unique" => "Major already exists",
                "appointment_date.unique" => "Major already exists",
                "time_from.unique" => "Major already exists",
                "time_to.unique" => "Major already exists",
            ]
        );

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'failed',
                    'message' => 'validation_error',
                    'errors' => $validator->errors()
                ],
                400
            );
        }
        DB::beginTransaction();
        try {
            DB::commit();
            $bookingDataArray = array(
                "user_id" => $request->user_id,
                "doctor_id" => $request->doctor_id,
                "appointment_date" => $request->appointment_date,
                "time_from" => $request->time_from,
                "time_to" => $request->time_to,
            );
            Booking::create($bookingDataArray);
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }
}
