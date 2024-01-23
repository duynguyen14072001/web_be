<?php

namespace App\Http\Controllers;

use App\Enums\RoleUser;
use App\Enums\StatusBooking;
use App\Mail\SendMailCreateBookingForDoctor;
use App\Mail\SendMailCreateBookingForUser;
use App\Mail\SendMailFailBookingForUser;
use App\Mail\SendMailSuccessBookingForDoctor;
use App\Mail\SendMailSuccessBookingForUser;
use App\Models\Booking;
use App\Models\User;
use BenSampo\Enum\Rules\Enum;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
                "doctor_id.required" => "Please choose doctor",
                "appointment_date.required" => "Please enter appointment date",
                "time_from.required" => "Please choose time start",
                "time_to.required" => "Please choose time end",
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

        $user = User::where('id', $request->user_id)->first();
        $doctor = User::where('id', $request->doctor_id)->first();
        if ($doctor->role != RoleUser::DOCTOR) {
            return response()->json(
                [
                    'status' => 'failed',
                    'errors' => 'Need to make an appointment with your doctor',
                ],
                400
            );
        }

        if ($request->time_from->gt($request->time_to)) {
            return response()->json(
                [
                    'status' => 'failed',
                    'message' => 'validation_error',
                    'errors' => ["time_to" => "The end time is earlier than the start time"]
                ],
                400
            );
        }

        $bookings = Booking::where('doctor_id', $request->doctor_id)->get();
        foreach ($bookings as $booking) {
            if ($booking->appointment_date == $request->appointment_date) {
                if ($booking->time_from == $request->time_from) {
                    return response()->json(
                        [
                            'status' => 'failed',
                            'message' => 'validation_error',
                            'errors' => ["time_from" => "Someone has already booked an appointment"]
                        ],
                        400
                    );
                } else if ($booking->time_to >= $request->time_from) {
                    return response()->json(
                        [
                            'status' => 'failed',
                            'message' => 'validation_error',
                            'errors' => ["time_from" => "The doctor is busy"]
                        ],
                        400
                    );
                }
            }
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
                "status" => StatusBooking::WAITING,
            );
            $booking = Booking::create($bookingDataArray);
            Mail::send(new SendMailCreateBookingForUser($user->email, $user->username));
            Mail::send(new SendMailCreateBookingForDoctor($doctor->email, $doctor->username));
            return response()->json(["status" => $this->status_code, "success" => true, "message" => "Create successfully", "data" => $booking]);
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }

    public function updateStatusBooking($id, Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "status" => [
                    'required',
                    new Enum(StatusBooking::class),
                ],
            ],
            [
                "status.required" => "Please choose status",
                "status.enum" => "Value is incorrect",
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
        $booking = Booking::where('id', $id)->first();
        $user = Auth::user();
        if ($booking->doctor_id == $user->id) {
            $booking->status = $request->status;
            $booking->save();
            if ($request->status == StatusBooking::FAIL) {
                Mail::send(new SendMailFailBookingForUser($user->email, $user->username));
            } else if ($request->status == StatusBooking::SUCCESS) {
                Mail::send(new SendMailSuccessBookingForUser($user->email, $user->username));
                Mail::send(new SendMailSuccessBookingForDoctor($user->email, $user->username));
            }
            return response()->json(["status" => $this->status_code, "success" => true, "message" => "Update successfully", "data" => $booking]);
        }
        return response()->json(["status" => "failed", "success" => false, "errors" => "you don't have access"], 400);
    }
}
