<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\Validator\Constraints;

use App\WorkingTime\WorkingTimeService;
use KimaiPlugin\RemoteWorkBundle\Constants;
use KimaiPlugin\RemoteWorkBundle\Entity\RemoteWork as RemoteWorkEntity;
use KimaiPlugin\RemoteWorkBundle\RemoteWorkService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class RemoteWorkValidator extends ConstraintValidator
{
    public function __construct(
        private readonly WorkingTimeService $workingTimeService,
        private readonly RemoteWorkService $remoteWorkService,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof RemoteWork)) {
            throw new UnexpectedTypeException($constraint, RemoteWork::class);
        }

        if (!\is_object($value) || !($value instanceof RemoteWorkEntity)) {
            return;
        }

        $user = $value->getUser();
        if ($user === null) {
            $this->context->buildViolation(RemoteWork::getErrorName(RemoteWork::MISSING_USER))
                ->setTranslationDomain('validators')
                ->setCode(RemoteWork::MISSING_USER)
                ->addViolation();

            return;
        }

        $date = $value->getDate();
        if ($date === null) {
            $this->context->buildViolation(RemoteWork::getErrorName(RemoteWork::MISSING_DATE))
                ->setTranslationDomain('validators')
                ->atPath('date')
                ->setCode(RemoteWork::MISSING_DATE)
                ->addViolation();

            return;
        }

        // Check user's first and last working day
        $firstDay = $user->getWorkStartingDay();
        if ($firstDay !== null && $date < $firstDay) {
            $this->context->buildViolation(RemoteWork::getErrorName(RemoteWork::FIRST_DAY))
                ->setTranslationDomain('validators')
                ->atPath('date')
                ->setCode(RemoteWork::FIRST_DAY)
                ->addViolation();

            return;
        }

        $lastDay = $user->getLastWorkingDay();
        if ($lastDay !== null && $date > $lastDay) {
            $this->context->buildViolation(RemoteWork::getErrorName(RemoteWork::LAST_DAY))
                ->setTranslationDomain('validators')
                ->atPath('date')
                ->setCode(RemoteWork::LAST_DAY)
                ->addViolation();

            return;
        }

        // Check if date is locked
        $latestApproval = $this->workingTimeService->getLatestApprovalDate($user);
        if ($latestApproval !== null && $latestApproval > $date) {
            $this->context->buildViolation(RemoteWork::getErrorName(RemoteWork::DATE_LOCKED))
                ->setTranslationDomain('validators')
                ->atPath('date')
                ->setCode(RemoteWork::DATE_LOCKED)
                ->addViolation();

            return;
        }

        // Check that it's a working day
        $workingDays = $this->remoteWorkService->getWorkingDays($user, $date, null);
        if (\count($workingDays) === 0) {
            $this->context->buildViolation(RemoteWork::getErrorName(RemoteWork::NO_WORKING_DAY))
                ->setTranslationDomain('validators')
                ->atPath('date')
                ->setCode(RemoteWork::NO_WORKING_DAY)
                ->addViolation();
        }
    }
}
