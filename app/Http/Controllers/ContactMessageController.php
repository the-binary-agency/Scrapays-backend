<?php

namespace App\Http\Controllers;

use App\ContactMessage;
use App\Http\Controllers\ApiController;
use App\Mail\replyContactMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactMessageController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $contact_messages = ContactMessage::all();
        return $this->showAll($contact_messages);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name'    => 'required|string',
            'email'   => 'required|string',
            'subject' => 'string',
            'message' => 'required|string'
        ]);

        $contactmessage = new ContactMessage();

        $contactmessage->name    = $request->input('name');
        $contactmessage->email   = $request->input('email');
        $contactmessage->phone   = $request->input('phone');
        $contactmessage->subject = $request->input('subject');
        $contactmessage->message = $request->input('message');
        $contactmessage->save();

        return $this->successResponse("Message Sent Succesfully.", 200, true);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(ContactMessage $contactmessage)
    {
        return $this->showOne($contactmessage);
    }

    /**
     * Reply the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function reply(Request $request, $id)
    {
        $this->validate($request, [
            'body' => 'required'
        ]);

        $body = (object) $request->body;
        if ($body->cc != '') {
            Mail::to($body->msg_email)->cc($body->cc)
                ->send(new replyContactMail($body));
        } else {
            Mail::to($body->msg_email)->send(new replyContactMail($body));
        }

        return $this->successResponse("Reply Succesful.", 200, true);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ContactMessage $contactmessage)
    {
        $contactmessage->delete();
        return $this->successResponse('Contact message deleted successfully.');
    }
}
