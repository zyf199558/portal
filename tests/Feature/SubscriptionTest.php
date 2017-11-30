<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use App\Models\Thread;
use App\Jobs\CreateReply;
use App\Jobs\CreateThread;
use App\Models\Subscription;
use App\Notifications\NewReply;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class SubscriptionTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function users_receive_notifications_for_new_replies_to_threads_where_they_are_subscribed_to()
    {
        Notification::fake();

        $thread = factory(Thread::class)->create();
        [$author, $userOne, $userTwo] = factory(User::class)->times(3)->create();
        factory(Subscription::class)->create(['user_id' => $userOne->id(), 'subscriptionable_id' => $thread->id()]);
        factory(Subscription::class)->create(['user_id' => $userTwo->id(), 'subscriptionable_id' => $thread->id()]);

        $this->dispatch(new CreateReply($this->faker->text, $this->faker->ipv4, $author, $thread));

        Notification::assertSentTo([$userOne, $userTwo], NewReply::class);
    }

    /** @test */
    public function users_are_automatically_subscribed_to_a_thread_after_creating_it()
    {
        $user = $this->createUser();

        $thread = $this->dispatch(
            new CreateThread($this->faker->sentence, $this->faker->text, $this->faker->ipv4, $user)
        );

        $this->assertTrue($thread->hasSubscriber($user));
    }

    /** @test */
    public function users_are_automatically_subscribed_to_a_thread_after_replying_to_it()
    {
        $user = $this->createUser();
        $thread = factory(Thread::class)->create();

        $this->dispatch(new CreateReply($this->faker->text, $this->faker->ipv4, $user, $thread));

        $this->assertTrue($thread->hasSubscriber($user));
    }
}