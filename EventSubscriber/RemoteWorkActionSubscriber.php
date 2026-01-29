<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\EventSubscriber;

use App\Event\PageActionsEvent;
use App\EventSubscriber\Actions\AbstractActionsSubscriber;
use KimaiPlugin\RemoteWorkBundle\Entity\RemoteWork;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class RemoteWorkActionSubscriber extends AbstractActionsSubscriber
{
    public function __construct(AuthorizationCheckerInterface $auth, UrlGeneratorInterface $urlGenerator)
    {
        parent::__construct($auth, $urlGenerator);
    }

    public static function getActionName(): string
    {
        return 'remote_work';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        if (!\array_key_exists('remote_work', $payload)) {
            return;
        }

        $remoteWork = $payload['remote_work'];

        if (!($remoteWork instanceof RemoteWork)) {
            return;
        }

        $user = $remoteWork->getUser();
        if ($user === null) {
            return;
        }

        $currentUser = $event->getUser();
        $isOwn = $currentUser === $user;

        // Edit action
        $canEdit = ($isOwn && $this->isGranted('edit_own_remote_work'))
            || (!$isOwn && $this->isGranted('edit_other_remote_work'));

        if ($canEdit) {
            $event->addEdit($this->path('remote_work_edit', ['id' => $remoteWork->getId()]), true);
        }

        // Delete action
        $canDelete = ($isOwn && $this->isGranted('delete_own_remote_work'))
            || (!$isOwn && $this->isGranted('delete_other_remote_work'));

        if ($canDelete) {
            $event->addDelete(
                $this->path('remote_work_delete', ['id' => $remoteWork->getId()]),
                false
            );
        }
    }
}
