<?php

namespace App\Listeners;

use App\Events\SlotChanged;
use App\Helpers;

class SendAdminNewShiftSignup
{
    /**
     * Email the admin alert address any time a slot is taken,
     * whether the volunteer signed up themselves or an admin assigned them.
     *
     * @param  SlotChanged  $event
     * @return void
     */
    public function handle(SlotChanged $event)
    {
        if ($event->change['status'] !== 'taken')
        {
            return;
        }

        $slot = $event->slot;
        $volunteer_name = $event->change['name'];
        $shift_name = $slot->schedule->shift->name;
        $event_name = $slot->event->name;
        $department_name = $slot->department->name;
        $start_date = date('l M d', strtotime($slot->start_date));
        $start_time = date('h:i a', strtotime($slot->start_time . $slot->start_date));
        $end_time = date('h:i a', strtotime($slot->end_time . $slot->start_date));
        $admin_assigned = isset($event->change['admin_assigned']) && $event->change['admin_assigned'];

        $admin_email = config('mail.admin_alert_email');

        $event_data = compact('volunteer_name', 'event_name', 'department_name', 'shift_name', 'start_date', 'start_time', 'end_time', 'admin_assigned');

        Helpers::sendMail('emails/admin-new-signup', $event_data, function ($message) use ($admin_email, $shift_name, $volunteer_name)
        {
            $message->to($admin_email)->subject('Shift signup: ' . $volunteer_name . ' - ' . $shift_name);
        });
    }
}