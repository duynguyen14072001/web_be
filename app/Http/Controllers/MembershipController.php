<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MembershipController extends Controller
{
    public  $status_code = 200;
    public function listMemberships($team_id)
    {
        $memberships = Membership::where('team_id', $team_id);
        return response()->json(["status" => $this->status_code, "success" => true, "message" => "List memberships", "data" => $memberships]);
    }

    public function createMemberFunction(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "member_email"      =>          "required|email",
                "team_id"           =>          "required|exists:teams,team_id",
                "is_admin"          =>          "required",
            ],
            [
                "member_email.required"    =>     "Please enter email",
                "member_email.email"       =>     "Please enter valid e-mail address",
                "team_id.required"         =>     "Please choose team",
                "is_admin.required"        =>     "Please tick",
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

        $checkMemberExist = Membership::join("users", "users.user_id", "memberships.user_id")
            ->where(array("users.email" => $request->member_email, "memberships.team_id" => $request->team_id))->count();
        if (!empty($checkMemberExist)) {
            return response()->json(
                [
                    'status' => 'failed',
                    'message' => 'validation_error',
                    'errors' => ["member_email" => "E-mail address already exists"]
                ],
                400
            );
        }
        DB::beginTransaction();
        try {
            // $team = Team::where("team_id", $request->team_id)->first();
            $email = $request->member_email;
            $user = User::where('email', $email)->first();
            if ($user) {
                $user_id = $user->user_id;
            } else {
                $userDataArray = array(
                    "email"     =>  $email,
                    "password"  =>  bcrypt('Random@123'),
                );

                $user_id = User::create($userDataArray)->user_id;
                $link = '/register';
            }

            $data = [
                "user_id"            =>          $user_id,
                "team_id"            =>          $request->team_id,
                "function_id"        =>          $request->function_id,
                "is_admin"           =>          $request->is_admin,
            ];

            $member = Membership::create($data);
            DB::commit();
            return response()->json(["status" => $this->status_code, "success" => true, "message" => "Member function created successfully.", "data" => $member], 200);
        } catch (\Throwable $e) {
            DB::rollback();
            return response()->json(["status" => "failed", "success" => false, "message" => "Failed to create member function."], 400);
        }
    }
}
