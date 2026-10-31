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

        $admin_email = config('mail.admin_alert_email');

        if (!$admin_email)
        {
            return;
        }

        $slot = $event->slot;
        $volunteer = Helpers::volunteerDetails($event->volunteer());

        $shift_name = $slot->schedule->shift->name;
        $event_name = $slot->event->name;
        $department_name = $slot->department->name;
        $start_date = date('l M d', strtotime($slot->start_date));
        $start_time = date('h:i a', strtotime($slot->start_time . $slot->start_date));
        $end_time = date('h:i a', strtotime($slot->end_time . $slot->start_date));
        $removed_by_admin = !empty($event->change['admin_released']);

        $event_data = array_merge($volunteer, compact('event_name', 'department_name', 'shift_name', 'start_date', 'start_time', 'end_time', 'removed_by_admin'));

        $subject = 'Shift released: ' . $volunteer['volunteer_display_name'] . ' <' . $volunteer['volunteer_email'] . '> - ' . $shift_name;

        Helpers::sendMail('emails/admin-shift-released', $event_data, function ($message) use ($admin_email, $subject)
        {
            $message->to($admin_email)->subject($subject);
        });
    }
}