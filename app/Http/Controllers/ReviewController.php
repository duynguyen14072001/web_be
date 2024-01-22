<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public  $status_code = 200;
    public function listReview($doctor_id)
    {
        $reviews = Review::where('doctor_id', $doctor_id)->get();
        if ($reviews) {
            return response()->json(["status" => $this->status_code, "success" => true, "message" => "List reviews", "data" => $reviews]);
        }
        return response()->json(["status" => $this->status_code, "success" => true, "message" => "No data"]);
    }

    public function createReview(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "user_id" => 'required',
                'doctor_id' => 'required',
                'stars' => 'required',
            ],
            [
                "user_id.required" => "Please enter major name",
                "doctor_id.required" => "Please choose doctor",
                "stars.required" => "Please stars",
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
            $reviewDataArray = array(
                "user_id" => $request->user_id,
                "doctor_id" => $request->doctor_id,
                "description" => $request->description,
                "stars" => $request->stars,
            );
            $review = Review::create($reviewDataArray);
            return response()->json(["status" => $this->status_code, "success" => true, "message" => "Create successfully", "data" => $review]);
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }
}
