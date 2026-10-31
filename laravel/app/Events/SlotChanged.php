<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use App\Models\Slot;
use App\Models\User;

class SlotChanged extends Event implements ShouldBroadcast
{
    use SerializesModels;

    public $slot;
    public $change;

    // The volunteer this change is about. Protected (not public) on purpose:
    // public properties get broadcast to the websocket channel, and we don't
    // want volunteer emails going out to everyone viewing the event page.
    protected $volunteer;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Slot $slot, $change, User $volunteer = null)
    {
        $this->slot = $slot;
        $this->change = $change;
        $this->volunteer = $volunteer;
    }

    /**
     * The volunteer who took or released the slot.
     *
     * @return User|null
     */
    public function volunteer()
    {
        return $this->volunteer;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return ['event-' . $this->slot->event->id];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'slot-changed';
    }
}