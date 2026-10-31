<?php

namespace App\Listeners;

use App\Helpers;
use App\Events\UserRegistered;

use App\Models\User;

class SendAdminWelcome
{
    /**
     * Send a user registered email to the admin alert address
     *
     * @param  UserRegistered  $event
     * @return void
     */
    public function handle(UserRegistered $event)
    {
        $user = $event->user;

        // Send to the same address as the shift alerts (ADMIN_ALERT_EMAIL).
        // Fall back to the first account only if that isn't configured.
        $admin_email = config('mail.admin_alert_email');

        if (!$admin_email)
        {
            $admin = User::find(1);
            $admin_email = $admin ? $admin->email : null;
        }

        if (!$admin_email)
        {
            return;
        }

        $subject = 'New user registered: ' . $user->name . ' <' . $user->email . '>';

        Helpers::sendMail('emails/admin-welcome', compact('user'), function ($message) use ($admin_email, $subject)
        {
            $message->to($admin_email)->subject($subject);
        });
    }
}