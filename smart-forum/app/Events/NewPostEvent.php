<?php

namespace App\Events;

use App\Models\Post;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewPostEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        $groupId = $this->post->topic->group_id ?? 1;
        return new Channel('group.' . $groupId);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs()
    {
        return 'post.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        return [
            'post' => [
                'id' => $this->post->id,
                'content' => $this->post->content,
                'parent_id' => $this->post->parent_id, // <-- ADDED: For nested replies
                'user' => [
                    'id' => $this->post->user->id,
                    'name' => $this->post->user->name,
                ],
                'created_at' => $this->post->created_at->toIso8601String(),
            ],
            'topic_id' => $this->post->topic_id,
        ];
    }
}