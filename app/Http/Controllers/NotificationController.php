<?php

namespace App\Http\Controllers;

use App\Notification;
use Illuminate\Http\Request;
use App\Traits\Notifications;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    use Notifications;

    public function makeNotification(Request $request){
        $this->createNotification($request->phone, $request->notification_body);
    }

    public function toggleNotifications(Request $request){
        $notifications = $request->notifications;

        foreach($notifications as $notification){
            $noty = Notification::find($notification['id']);
            $noty->read = !$notification['read'];
            $noty->save();
        }

    }

    public function deleteNotifications(Request $request){
        $notifications = $request->notifications;

        foreach($notifications as $notification){
            $noty = Notification::find($notification['id']);
            $noty->delete();
        }
        return response()->json(['data' => 'The selected notifications have been been deleted successfully'], Response::HTTP_OK);

    }

}
