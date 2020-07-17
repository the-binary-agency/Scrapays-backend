<?php

namespace App\Traits;
use App\Notification;

trait Notifications {

    public function createNotification ($user_id, $notification_body) {

        $notification = new Notification;
        $notification->user_id = $user_id;
        $notification->notification_body = $notification_body;
        $notification->save();
    }

    public function markAsRead ($user_id, $id) {

        $notification = Notification::find($id);
        $notification->read = true;
        $notification->save();
    }

    public function markAsUnRead ($user_id, $id) {

        $notification = Notification::find($id);
        $notification->read = false;
        $notification->save();
    }
}