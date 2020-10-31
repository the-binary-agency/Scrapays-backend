<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Http\Requests\ChangePasswordRequest;
use App\User;
use Illuminate\Support\Facades\DB;

class ResetPasswordController extends ApiController
{
    public function process(ChangePasswordRequest $request)
    {
        return $this->getPasswordResetTableRow($request)->count() > 0 ? $this->changePassword($request) : $this->tokenNotFoundResponse();
    }

    private function getPasswordResetTableRow($request)
    {
        return DB::table('password_resets')->where([
            'email' => $request->email,
            'token' => $request->resetToken
        ]);
    }

    private function changePassword($request)
    {
        $user = User::whereEmail($request->email)->first();
        $user->update(['password' => $request->password]);
        $this->getPasswordResetTableRow($request)->delete();

        return $this->passwordChangedResponse();
    }

    private function passwordChangedResponse()
    {
        return $this->successResponse('Password Successfully Changed.', 201);
    }

    private function tokenNotFoundResponse()
    {
        return $this->errorResponse('Email or Token is invalid.', 422);
    }

}
