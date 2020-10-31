<?php

namespace App\Http\Controllers;

use App\Household;
use App\Mail\registerHouseholdMail;
use App\Mail\registerMail;
use App\Traits\ApiResponse;
use App\Traits\HttpRequests;
use App\User;
use Illuminate\Support\Facades\Mail;

class ApiController extends Controller
{
    use ApiResponse, HttpRequests;

    public function saveUser($request)
    {
        $user              = new User();
        $user->first_name  = $request->first_name;
        $user->last_name   = $request->last_name;
        $user->phone       = $request->phone;
        $user->email       = $request->email;
        $user->pin         = $request->pin;
        $user->password    = $request->password;
        $user->invite_code = $request->invite_code;

        $decoded = $this->createWallet($request);
        $res     = (object) [
            'user'  => $user,
            'error' => ''
        ];
        if ($decoded) {
            if ($decoded->error->message) {
                $res->error = $decoded->error->message;
            } else {
                if ($decoded->success->message) {
                    $user->save();
                }
            }
        } else {
            $res->error = 'Something went wrong, please try again.';
        }

        return $res;
    }

    public function sendSignupMail($user, $type)
    {
        if ($type == 'Enterprise') {
            Mail::to($user->email)->send(new registerMail($user));
        } else if ($type == 'Household') {
            Mail::to($user->email)->send(new registerHouseholdMail($user));
        }
        // else if($type == 'Household')
        // {
        //     Mail::to($user->email)->send(new registerHouseholdMail($user));
        // }else if($type == 'Household')
        // {
        //     Mail::to($user->email)->send(new registerHouseholdMail($user));
        // }else if($type == 'Household')
        // {
        //     Mail::to($user->email)->send(new registerHouseholdMail($user));
        // }

        return $this->successResponse('Your account has been created successfully. Please check your email for your details', 200, true);
    }

    public function registerProducerDuringCollection($producer)
    {
        $request           = (object) $producer;
        $user              = new User();
        $user->first_name  = $request->first_name;
        $user->last_name   = $request->last_name;
        $user->phone       = $request->phone;
        $user->email       = null;
        $user->pin         = '1234';
        $user->password    = '123456';
        $user->invite_code = null;

        $household                  = new Household();
        $household->request_address = $request->request_address;

        $decoded = $this->createWallet($request);

        $res = '';
        if ($decoded) {
            if ($decoded->error->message) {
                $res = $decoded->error->message;
            } else {
                if ($decoded->success->message) {
                    $user->save();
                    $household->save();
                    $household->user()->save($user);
                    $res = 'success';
                }
            }
        } else {
            $res = 'Something went wrong, please try again.';
        }
        return $res;
    }
}
