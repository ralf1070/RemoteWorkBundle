<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\EventSubscriber;

use App\Event\WorkingTimeYearEvent;
use App\WorkingTime\Model\Day;
use App\WorkingTime\Model\DayAddon;
use KimaiPlugin\RemoteWorkBundle\Constants;
use KimaiPlugin\RemoteWorkBundle\Repository\RemoteWorkRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This subscriber adds remote work entries (homeoffice, business trips) to the working time year overview.
 * Remote work does not change actual working time (it's still work), so duration is 0.
 * It's purely informational to show where the work was done.
 */
final class WorkingTimeYearSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RemoteWorkRepository $repository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Priority 140 = after absences (150) but still early enough
            WorkingTimeYearEvent::class => ['onWorkingTimeYear', 140],
        ];
    }

    public function onWorkingTimeYear(WorkingTimeYearEvent $event): void
    {
        $user = $event->getYear()->getUser();
        $year = (int) $event->getYear()->getYear()->format('Y');

        // Only load approved entries
        $entries = $this->repository->findApprovedByUserAndYear($user, $year);

        $firstDay = $user->getWorkStartingDay();
        $lastDay = $user->getLastWorkingDay();

        foreach ($entries as $entry) {
            $date = $entry->getDate();
            if ($date === null) {
                continue;
            }

            // Skip entries after the "until" date
            if ($date > $event->getUntil()) {
                continue;
            }

            // Skip entries before user's first working day
            if ($firstDay !== null && $date < $firstDay) {
                continue;
            }

            // Skip entries after user's last working day
            if ($lastDay !== null && $date > $lastDay) {
                continue;
            }

            $day = $event->getYear()->getDay($date);

            if (!$day instanceof Day) {
                continue;
            }

            // For remote work, we don't add to actual time (it's still work time)
            // We just show it as an informational addon
            // Duration = 0 means it doesn't affect the time calculation

            $title = $entry->getType();
            if ($entry->isHalfDay()) {
                $title .= ' (½)';
            }

            // Add addon with duration 0 (doesn't affect calculation)
            $addon = new DayAddon($title, 0, 0);
            $addon->setBillable(true);

            $color = $entry->isHomeoffice() ? Constants::COLOR_HOMEOFFICE : Constants::COLOR_BUSINESS_TRIP;
            $addon->setAttribute('color', $color);

            $icon = $entry->isHomeoffice() ? Constants::ICON_HOMEOFFICE : Constants::ICON_BUSINESS_TRIP;
            $addon->setAttribute('icon', $icon);

            if ($entry->getComment() !== '') {
                $addon->setAttribute('comment', $entry->getComment());
            }

            $day->addAddon($addon);
        }
    }
}
