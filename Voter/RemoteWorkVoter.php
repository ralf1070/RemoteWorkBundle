<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\Voter;

use App\Entity\User;
use App\Security\RolePermissionManager;
use KimaiPlugin\RemoteWorkBundle\Entity\RemoteWork;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, RemoteWork|User>
 */
final class RemoteWorkVoter extends Voter
{
    public const VIEW = 'view_remote_work';
    public const CREATE = 'create_remote_work';
    public const EDIT = 'edit_remote_work';
    public const DELETE = 'delete_remote_work';
    public const APPROVE = 'approve_remote_work';

    private const ALLOWED_ATTRIBUTES = [
        self::VIEW,
        self::CREATE,
        self::EDIT,
        self::DELETE,
        self::APPROVE,
        // Legacy attributes used in templates/controller
        'view_own_remote_work',
        'view_other_remote_work',
        'create_own_remote_work',
        'edit_own_remote_work',
        'edit_other_remote_work',
        'delete_own_remote_work',
        'delete_other_remote_work',
    ];

    public function __construct(
        private readonly RolePermissionManager $permissionManager,
    ) {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return \in_array($attribute, self::ALLOWED_ATTRIBUTES, true);
    }

    public function supportsType(string $subjectType): bool
    {
        return str_contains($subjectType, RemoteWork::class)
            || str_contains($subjectType, User::class)
            || $subjectType === 'null';
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!\in_array($attribute, self::ALLOWED_ATTRIBUTES, true)) {
            return false;
        }

        // Direct permission checks (without subject)
        if ($subject === null) {
            return true;
        }

        return $subject instanceof RemoteWork || $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!($user instanceof User)) {
            return false;
        }

        // Handle direct permission checks (e.g., is_granted('view_own_remote_work'))
        if ($this->isDirectPermission($attribute)) {
            return $this->permissionManager->hasRolePermission($user, $attribute);
        }

        // Get the user whose remote work we're checking
        $remoteWorkUser = $this->getRemoteWorkUser($subject);

        return match ($attribute) {
            self::VIEW => $this->canView($user, $remoteWorkUser),
            self::CREATE => $this->canCreate($user, $remoteWorkUser),
            self::EDIT => $this->canEdit($user, $subject, $remoteWorkUser),
            self::DELETE => $this->canDelete($user, $subject, $remoteWorkUser),
            self::APPROVE => $this->canApprove($user, $subject),
            default => false,
        };
    }

    private function isDirectPermission(string $attribute): bool
    {
        return \in_array($attribute, [
            'view_own_remote_work',
            'view_other_remote_work',
            'create_own_remote_work',
            'edit_own_remote_work',
            'edit_other_remote_work',
            'delete_own_remote_work',
            'delete_other_remote_work',
        ], true);
    }

    private function getRemoteWorkUser(mixed $subject): ?User
    {
        if ($subject instanceof RemoteWork) {
            return $subject->getUser();
        }

        if ($subject instanceof User) {
            return $subject;
        }

        return null;
    }

    private function canView(User $user, ?User $remoteWorkUser): bool
    {
        if ($remoteWorkUser === null) {
            return $this->permissionManager->hasRolePermission($user, 'view_own_remote_work');
        }

        if ($user->getId() === $remoteWorkUser->getId()) {
            return $this->permissionManager->hasRolePermission($user, 'view_own_remote_work');
        }

        return $this->permissionManager->hasRolePermission($user, 'view_other_remote_work');
    }

    private function canCreate(User $user, ?User $remoteWorkUser): bool
    {
        if ($remoteWorkUser === null || $user->getId() === $remoteWorkUser->getId()) {
            return $this->permissionManager->hasRolePermission($user, 'create_own_remote_work');
        }

        // Creating for other users requires edit_other permission
        return $this->permissionManager->hasRolePermission($user, 'edit_other_remote_work');
    }

    private function canEdit(User $user, mixed $subject, ?User $remoteWorkUser): bool
    {
        if ($subject instanceof RemoteWork) {
            // Approved entries cannot be edited (except by admins via delete_other)
            if ($subject->isApproved() || $subject->isRejected()) {
                return false;
            }
        }

        if ($remoteWorkUser === null || $user->getId() === $remoteWorkUser->getId()) {
            return $this->permissionManager->hasRolePermission($user, 'edit_own_remote_work');
        }

        return $this->permissionManager->hasRolePermission($user, 'edit_other_remote_work');
    }

    private function canDelete(User $user, mixed $subject, ?User $remoteWorkUser): bool
    {
        if ($subject instanceof RemoteWork) {
            // Approved entries can only be deleted by users with delete_other permission
            if ($subject->isApproved() && $user->getId() === $remoteWorkUser?->getId()) {
                return $this->permissionManager->hasRolePermission($user, 'delete_other_remote_work');
            }
        }

        if ($remoteWorkUser === null || $user->getId() === $remoteWorkUser->getId()) {
            return $this->permissionManager->hasRolePermission($user, 'delete_own_remote_work');
        }

        return $this->permissionManager->hasRolePermission($user, 'delete_other_remote_work');
    }

    private function canApprove(User $user, mixed $subject): bool
    {
        if ($subject instanceof RemoteWork) {
            // Can only approve new entries
            if (!$subject->isNew()) {
                return false;
            }
        }

        return $this->permissionManager->hasRolePermission($user, 'approve_remote_work');
    }
}
