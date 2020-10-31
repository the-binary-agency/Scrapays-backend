<?php

namespace App\Http\Controllers\Auth;

use App\Admin;
use App\Agent;
use App\Collector;
use App\Enterprise;
use App\Host;
use App\Household;
use App\Http\Controllers\ApiController;
use App\Http\Requests\SignUpRequest;
use App\User;
use Illuminate\Http\Request;

class RegisterController extends ApiController
{
    public function admin(SignUpRequest $request)
    {
        $superAdmin = User::find($request->input('super_admin'));
        if (!$superAdmin || $superAdmin->userable_type != 'Admin') {
            return $this->errorResponse('Only the super admin can ceate more admins.', 422);
        }

        $res = $this->saveUser($request);

        if ($res->error) {
            return $this->errorResponse($res->error, 422);
        } else {
            $admin              = new Admin();
            $admin->permissions = json_encode($request->input('permissions'));
            $admin->save();

            $admin->user()->save($res->user);

            return $this->successResponse('You have successfully created a new Admin.', 201, true);
        }
    }

    public function enterprise(SignUpRequest $request)
    {
        $res = $this->saveUser($request);

        if ($res->error) {
            return $this->errorResponse($res->error, 422);
        } else {
            $enterprise                     = new Enterprise();
            $enterprise->company_name       = $request->company_name;
            $enterprise->company_size       = $request->company_size;
            $enterprise->industry           = $request->industry;
            $enterprise->gender             = $request->gender;
            $enterprise->recovery_automated = false;
            $enterprise->save();

            $enterprise->user()->save($res->user);

            return $this->sendSignupMail($request, 'Enterprise');
        }
    }

    public function ussd(Request $request)
    {
        $this->validate($request, [
            'first_name' => 'required',
            'last_name'  => 'required',
            'phone'      => 'required|unique:users',
            'pin'        => 'required'
        ]);

        $request->password = '123456';
        $request->email    = null;

        $res = $this->saveUser($request);

        if ($res->error) {
            return $this->errorResponse($res->error, 422);
        } else {
            $household                  = new Household();
            $household->request_address = null;
            $household->save();

            $household->user()->save($res->user);

            return $this->successResponse('Household Account created successfully.', 201, true);
        }
    }

    public function household(SignupRequest $request)
    {
        $this->validate($request, [
            'request_address' => 'required'
        ]);

        $res = $this->saveUser($request);

        if ($res->error) {
            return $this->errorResponse($res->error, 422);
        } else {
            $household                  = new Household();
            $household->request_address = $request->request_address;
            $household->save();

            $household->user()->save($res->user);

            return $this->sendSignupMail($request, 'Household');
        }

    }

    public function host(SignUpRequest $request)
    {

        $res = $this->saveUser($request);

        if ($res->error) {
            return $this->errorResponse($res->error, 422);
        } else {
            $host                  = new Host();
            $host->host_address    = $request->host_address;
            $host->space_size      = $request->space_size;
            $host->host_duration   = $request->host_duration;
            $host->host_start_date = $request->host_start_date;
            $host->save();

            $host->user()->save($res->user);

            return $this->sendSignupMail($request, 'Host');
        }

    }

    public function collector(SignUpRequest $request)
    {

        $res = $this->saveUser($request);

        if ($res->error) {
            return $this->errorResponse($res->error, 422);
        } else {
            $collector                           = new Collector();
            $collector->collection_coverage_zone = $request->collection_coverage_zone;
            $collector->approvedAsCollector      = false;
            $collector->save();

            $collector->user()->save($res->user);

            return $this->sendSignupMail($request, 'Collector');
        }
    }

    public function agent(SignUpRequest $request)
    {

        $res = $this->saveUser($request);

        if ($res->error) {
            return $this->errorResponse($res->error, 422);
        } else {
            $agent                        = new Agent();
            $agent->collectorCoverageZone = $request->collectorCoverageZone;
            $agent->approvedAsCollector   = $request->approvedAsCollector;
            $agent->save();

            $agent->user()->save($res->user);

            return $this->sendSignupMail($request, 'Agent');
        }
    }
}
