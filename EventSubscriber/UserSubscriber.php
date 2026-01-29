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

final class UserSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'user';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        if (!\array_key_exists('user', $payload)) {
            return;
        }

        $user = $payload['user'];

        if (!$user instanceof User || $user->getId() === null) {
            return;
        }

        $currentUser = $event->getUser();
        $isOwn = $currentUser === $user;

        if ($isOwn && $this->isGranted('view_own_remote_work')) {
            $event->addActionToSubmenu(
                'report',
                'remote_work',
                [
                    'url' => $this->path('remote_work', ['user' => $user->getId()]),
                    'title' => 'remote_work'
                ]
            );
        } elseif (!$isOwn && $this->isGranted('view_other_remote_work')) {
            $event->addActionToSubmenu(
                'report',
                'remote_work',
                [
                    'url' => $this->path('remote_work', ['user' => $user->getId()]),
                    'title' => 'remote_work'
                ]
            );
        }
    }
}
