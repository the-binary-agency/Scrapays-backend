<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ApiController;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class WalletController extends ApiController
{
    /**
     * Display the balance of the specified user.
     *
     * @return \Illuminate\Http\Response
     */
    public function balance(Request $request, User $user)
    {
        if ($request->user->id !== $user->id && $request->user->userable_type !== 'Admin') {
            return $this->errorResponse('Not authorised to view this user\'s balance.', 401);
        }

        $phone = '+234' . substr($user->phone, 1);
        $pin   = Crypt::decryptString($user->pin);

        // error_log('pin---->'.$pin);

        $res = $this->getWalletBalance($phone, $pin);

        if ($res->error) {
            return $this->errorResponse($res->error, 400);
        }

        $balance = (object) [
            'balance' => $res->balance
        ];

        return $this->successResponse($balance, 200, true);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function withdraw(Request $request)
    {
        $this->validate($request, [
            'phone'          => 'required|string',
            'amount'         => 'required',
            'account_number' => 'required',
            'account_name'   => 'required',
            'bank_code'      => 'required',
            'bank_name'      => 'required',
            'pin'            => 'required'
        ]);

        $response = $this->withdrawFromWallet($request);
    }

    public function transfer(Request $request)
    {
        $this->validate($request, [
            'phone'       => 'required|string',
            'pin'         => 'required',
            'beneficiary' => 'required',
            'full_name'   => 'required',
            'amount'      => 'required'
        ]);

        $response = $this->transferToBank($request);
    }

    public function airtime(Request $request)
    {
        $this->validate($request, [
            'phone'       => 'required|string',
            'pin'         => 'required',
            'beneficiary' => 'required',
            'full_name'   => 'required',
            'amount'      => 'required'
        ]);

        $response = $this->buyAirtime($request);
    }

    public function getCombinedWalletHistory(User $user)
    {
        $decoded = $this->getWalletHistory($user);

        if ($decoded) {
            if ($decoded->error->message) {
                return $this->errorResponse($decoded->error->message, 500);
            } else {
                if ($decoded->success->message) {
                    return $this->showAll(collect($decoded->content->data), 200, false);
                }
            }
        } else {
            return $this->errorResponse('Something went wrong, please try again.', 500);
        }
    }

    public function getWithdrawalHistory(User $user)
    {
        $phone = '+234' . substr($user->phone, 1);
        $pin   = Crypt::decryptString($user->pin);

        error_log('phone ' . $phone);

        $decoded = $this->getWalletWithdrawalHistory($phone, $pin);

        if ($decoded) {
            if ($decoded->error->message) {
                return $this->errorResponse($decoded->error->message, 500);
            } else {
                if ($decoded->success->message) {
                    return $this->showAll(collect($decoded->content->data), 200, false);
                }
            }
        } else {
            return $this->errorResponse('Something went wrong, please try again.', 500);
        }
    }

    /**
     * Update the specified user's wallet pin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function changeWalletPin(Request $request)
    {
        $user = User::where('phone', '+234' . substr($request->phone, 1))->first();

        if (!$user) {
            return $this->errorResponse('User with specified phone number not found.', 404);
        }

        if ($request->new_pin !== $request->new_pin_confirmation) {
            return $this->errorResponse('Pins do not match.', 401);
        }

        $credentials = [
            'phone'    => '+234' . substr($request->user->phone, 1),
            'password' => $request->adminPassword
        ];

        if (!Auth::guard()->attempt($credentials)) {
            return $this->errorResponse('Incorrect password.', 401);

        }

        $request->phone = '+234' . substr($request->phone, 1);

        $decoded = $this->setWalletPin($request);

        if ($decoded) {
            if ($decoded->error->message) {
                return $this->errorResponse($decoded->error->message, 500);
            } else {
                if ($decoded->success->message) {
                    $user->pin = $request->new_pin;
                    $user->save();
                    return $this->successResponse('Wallet pin changed successfully', 200, true);
                }
            }
        } else {
            return $this->errorResponse('Something went wrong, please try again.', 500);
        }
    }
}
