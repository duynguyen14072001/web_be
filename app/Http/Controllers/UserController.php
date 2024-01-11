<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public  $status_code = 200;
    public function getUserById($id)
    {
        $user = User::where('id', $id)->first();
        if ($user) {
            return response()->json(["status" => $this->status_code, "success" => true, "data" => $user]);
        }
        return response()->json(["status" => "failed", "success" => false, "errors" => "User not found"], 400);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make(
            $request->all(),
            [
                "new_password"          =>          "min:6",
            ]
        );
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'failed',
                    "success" => false,
                    'error' => [
                        "message" => $validator->errors()->first()
                    ],
                ],
                400
            );
        }
        if (User::where('username', $request->username)->where('id', '<>', $user->id)->first()) {
            return response()->json(
                [
                    'status' => 'failed',
                    'errors' => 'There are already people using this username',
                    'success' => false
                ],
                400
            );
        }
        $userDataArray = [];
        if ($request->username) {
            $userDataArray['username'] = $request->username;
        }
        if ($request->current_password) {
            if ($request->new_password) {
                if (Hash::check($request->current_password, auth()->user()->password)) {
                    $userDataArray['password'] = bcrypt($request->new_password);
                } else {
                    return response()->json(
                        [
                            "status" => "failed",
                            "success" => false,
                            "error" => [
                                "message" => "Current password is wrong"
                            ]
                        ],
                        400
                    );
                }
            } else {
                return response()->json(
                    [
                        "status" => "failed",
                        "success" => false,
                        "error" => [
                            "message" => "Enter a new password if you want to update"
                        ]
                    ],
                    400
                );
            }
        } else {
            if ($request->new_password) {
                return response()->json(
                    [
                        "status" => "failed",
                        "success" => false,
                        "error" => [
                            "message" => "Please enter a current password"
                        ]
                    ],
                    400
                );
            }
        }
        $user->update($userDataArray);
        $user->save();
        return response()->json(["status" => $this->status_code, "success" => true, "message" => "Update successfully", "data" => $user]);
    }
}
