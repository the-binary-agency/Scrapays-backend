<?php

namespace App\Http\Controllers;

use App\ContactMessage;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function send(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string',
            'email' => 'required|string',
            'subject' => 'string',
            'message' => 'required|string',
        ]);

        $contactmessage = new ContactMessage;
            
            $contactmessage->name = $request->input('name');
            $contactmessage->email = $request->input('email');
            $contactmessage->subject = $request->input('subject');
            $contactmessage->message = $request->input('message');
            $contactmessage->save();

        return response()->json(["message" => "Message Sent Succesfully"]);
    }
}
