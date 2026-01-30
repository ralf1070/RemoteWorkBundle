<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\EventSubscriber;

use App\Entity\User;
use App\Event\PageActionsEvent;
use App\EventSubscriber\Actions\AbstractActionsSubscriber;

final class RemoteWorkPageActionSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'remote_work_page';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        if (!\array_key_exists('year', $payload) || !($payload['year'] instanceof \DateTimeInterface)) {
            return;
        }

        if (!\array_key_exists('profile', $payload) || !($payload['profile'] instanceof User)) {
            return;
        }

        $year = $payload['year'];
        $user = $payload['profile'];

        $event->addAction('download', [
            'url' => $this->path('remote_work_export', ['year' => $year->format('Y-m-d'), 'profile' => $user->getId()]),
            'title' => 'export'
        ]);

        $event->addAction('calendar', [
            'url' => $this->path('remote_work_ical', ['year' => $year->format('Y-m-d'), 'profile' => $user->getId()]),
            'title' => 'ical'
        ]);
    }
}
