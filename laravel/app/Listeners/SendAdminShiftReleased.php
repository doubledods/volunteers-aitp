<?php

namespace App\Listeners;

use App\Events\SlotChanged;
use App\Helpers;

class SendAdminShiftReleased
{
    /**
     * Email the admin alert address any time a slot is released,
     * whether the volunteer dropped it themselves or an admin removed them.
     *
     * @param  SlotChanged  $event
     * @return void
     */
    public function handle(SlotChanged $event)
    {
        if ($event->change['status'] !== 'released')
        {
            return;
        }

        $slot = $event->slot;
        $volunteer_name = Helpers::displayName($slot->user);
        $shift_name = $slot->schedule->shift->name;
        $event_name = $slot->event->name;
        $department_name = $slot->department->name;
        $start_date = date('l M d', strtotime($slot->start_date));
        $start_time = date('h:i a', strtotime($slot->start_time . $slot->start_date));
        $end_time = date('h:i a', strtotime($slot->end_time . $slot->start_date));
        $removed_by_admin = isset($event->change['admin_released']) && $event->change['admin_released'];

        $admin_email = config('mail.admin_alert_email');

        $event_data = compact('volunteer_name', 'event_name', 'department_name', 'shift_name', 'start_date', 'start_time', 'end_time', 'removed_by_admin');

        Helpers::sendMail('emails/admin-shift-released', $event_data, function ($message) use ($admin_email, $shift_name, $volunteer_name)
        {
            $message->to($admin_email)->subject('Shift released: ' . $volunteer_name . ' - ' . $shift_name);
        });
    }
}