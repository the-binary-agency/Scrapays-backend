<?php

namespace App\Http\Controllers;

use App\ContactMessage;
use App\Mail\replyContactMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Mail;

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

    public function get()
    {
        $contactmessage = DB::table('contact_messages')->latest()->get();
        return response()->json((object)["messages" => $contactmessage]);
    }

    public function delete($id)
    {
        $contactmessage = ContactMessage::find($id);
        $contactmessage->delete();
        return response()->json((object)["data" => 'Message deleted successfully']);
    }

    public function reply(Request $request)
    {
        $b = (object)$request->body;
        if($b->cc != '')
        {
            Mail::to($b->msgEmail)->cc($b->cc)
                              ->send(new replyContactMail($b));
        }else
        {
            Mail::to($b->msgEmail)->send(new replyContactMail($b));
        }

        return response()->json((object)['data' => 'Reply Successfull.'], Response::HTTP_OK);
    }
}
