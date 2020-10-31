<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ApiController;
use App\User;
use Illuminate\Http\Request;
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

        $pin = Crypt::decryptString($user->pin);

        $balance = $this->getWalletBalance($user->phone, $pin);

        $balance = (object) [
            'balance' => $balance
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
}
