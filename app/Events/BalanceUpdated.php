<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BalanceUpdated
{
    use SerializesModels;

    public $balance;
    public $user;

    public function __construct($user, $balance)
    {
        $this->user = $user;
        $this->balance = $balance;
    }

    public function broadcastOn()
    {
        return new Channel('balance.' . $this->user->id);
    }
}