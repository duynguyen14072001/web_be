<?php

namespace App\Http\Controllers;

use App\Models\Major;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MajorController extends Controller
{
    public  $status_code = 200;
    public function listMajor()
    {
        $majors = Major::get();
        return response()->json(["status" => $this->status_code, "success" => true, "message" => "List majors", "data" => $majors]);
    }

    public function createMajor(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "name" => 'required|unique:majors,name',
            ],
            [
                "name.required"     =>          "Please enter major name",
                "name.unique"       =>          "Major already exists",
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
            $majorDataArray = array(
                "name" => $request->name,
            );
            Major::create($majorDataArray);
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }
}
