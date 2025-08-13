<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;

final class SettableGoalManager
{
    /**
     * @throws Exception
     */
    public function __construct(
        private User $user,
        private Event $event,
        private UserService $userService
    ) {
        $this->checkEventParticipation();
    }

    public function setUser(User $user): self
    {
        dd($user);
        $this->user = $user;

        return $this;
    }

    public function setEvent(Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get the user.
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Get the event.
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    public function calculateProgress()
    {
        $requiredDailyMileage = $this->getEventTotalGoal() / ($this->eventInLeapYear() ? 366 : 365);
    }

    /**
     * Check user is participated into event or not.
     *
     * @throws Exception
     */
    private function checkEventParticipation(): void
    {
        dd($this->userAttitude());
        if (! $this->user->participations()->where('event_id', $this->event->id)->count()) {
            throw new Exception('User is not participating in this event');
        }
    }

    /**
     * Get the event total goal.
     *
     * @return int
     */
    private function getEventTotalGoal(): int
    {
        $eventName = Str::slug($this->event->name, '-');
        $goals = json_decode($this->user->settings, true)['rty_goals'];

        return collect($goals)
            ->flatMap(fn ($item) => $item)
            ->get($eventName);
    }

    private function eventInLeapYear(): bool
    {
        $eventYear = (int) Carbon::parse($this->event->start_date)->format('Y');

        return ($eventYear % 4 === 0 && $eventYear % 100 !== 0) || $eventYear % 400 === 0;
    }

    private function getEventTotalMiles(): int
    {
        return $this->userService->total($this->event->id, $this->user);
    }

    private function userAttitude(): string
    {
        return json_decode($this->user->settings, true)['attitude'] ?? 'default';
    }
}
