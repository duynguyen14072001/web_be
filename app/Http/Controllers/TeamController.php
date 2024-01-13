<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public  $status_code = 200;
    public function getListTeamByUserId($id)
    {
        $teams = Membership::join('teams', 'teams.id', '=', 'memberships.team_id')
            ->where('memberships.user_id', $id)
            ->select('teams.*')
            ->get();
        return response()->json(["status" => $this->status_code, "success" => true, "message" => "List teams", "data" => $teams]);
    }

    public function createTeam(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "team_name"         =>          [
                    'required',
                    Rule::unique('teams', 'team_name')
                        ->where('is_created', 'Yes')
                        ->where('is_active', 'Yes')
                        ->where('owner_user_id', Auth::user()->user_id)
                ],
                "team_type_id"      =>          "required|exists:team_types,team_type_id",
                'icon'              =>          'nullable|mimes:jpg,jpeg,png|max:2048',
            ],
            [
                "icon.mimes"             =>          "(Only png, jpg, jpeg format allowed)",
                "icon.max"               =>          "Team logo must not be greater than 2MB",
                "team_name.required"     =>          "Please enter team",
                "team_name.unique"       =>          "Team name already exists",
                "team_type_id.required"  =>          "Please select team type",
                "team_type_id.exists"    =>          "Team type not exists",
                "team_owner.required"    =>          "Please enter team owner",
                "team_owner.exists"      =>          "Team owner not exists",
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

            $filename = '';
            if ($request->icon) {
                $filename = time() . '.' . $request->icon->getClientOriginalExtension();
                $request->icon->move('team_logo/', $filename);
            }
            $owner_user_id = Auth::user()->id;
            $teamDataArray          =       array(
                "team_name"          =>          $request->team_name,
                "icon"               =>          $filename,
                "team_type_id"       =>          $request->team_type_id,
                "owner_user_id"      =>          $owner_user_id,
            );

            Team::create($teamDataArray);
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }
}
