<?php

namespace App\Http\Controllers;

use App\Models\TeamPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TeamPositionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    private $status_code    =        200;

    public function createTeamPosition(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => [
                    'required',
                    Rule::unique('team_positions', 'name')
                        ->where('team_id', $request->team_id)
                ],
                "team_id"           =>          "required|exists:teams,team_id",
            ],
            [
                "name.required"     =>          "Please enter team position",
                "name.unique"       =>          "Team position name already been taken",
                "team_id.required"  =>          "Team id must be required.",
                "team_id.exists"    =>          "Team id not exists.",
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
            $data = [
                "name"               =>          $request->name,
                "team_id"            =>          $request->team_id,
            ];
            $team_position = TeamPosition::create($data)->id;
            DB::commit();
            return response()->json(["status" => $this->status_code, "success" => true, "message" => "Team function created successfully.", "data" => $team_position], 200);
        } catch (\Throwable $e) {
            DB::rollback();
            return response()->json(["status" => "failed", "success" => false, "message" => "Failed to create function "], 400);
        }
    }
}
