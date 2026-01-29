<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\Validator;

use App\Entity\User;
use KimaiPlugin\RemoteWorkBundle\Entity\RemoteWork;
use KimaiPlugin\RemoteWorkBundle\Model\OverlapWarning;
use KimaiPlugin\RemoteWorkBundle\Repository\RemoteWorkRepository;
use KimaiPlugin\WorkContractBundle\Entity\Absence;
use KimaiPlugin\WorkContractBundle\Repository\AbsenceRepository;
use KimaiPlugin\WorkContractBundle\Repository\PublicHolidayRepository;

final class OverlapValidator
{
    public function __construct(
        private readonly RemoteWorkRepository $remoteWorkRepository,
        private readonly ?AbsenceRepository $absenceRepository = null,
        private readonly ?PublicHolidayRepository $publicHolidayRepository = null,
    ) {
    }

    /**
     * Checks for overlapping entries and returns warnings.
     * These are warnings, not errors - user can choose to ignore them.
     *
     * @return array<OverlapWarning>
     */
    public function checkOverlaps(RemoteWork $remoteWork, ?\DateTimeInterface $endDate = null): array
    {
        $warnings = [];
        $user = $remoteWork->getUser();

        if ($user === null || $remoteWork->getDate() === null) {
            return $warnings;
        }

        $startDate = $remoteWork->getDate();

        // For date range, check all days
        if ($endDate !== null) {
            $currentDate = \DateTimeImmutable::createFromInterface($startDate);
            $end = \DateTimeImmutable::createFromInterface($endDate);

            while ($currentDate <= $end) {
                $warnings = array_merge($warnings, $this->checkDateForOverlaps($user, $currentDate, $remoteWork));
                $currentDate = $currentDate->modify('+1 day');
            }
        } else {
            $warnings = $this->checkDateForOverlaps($user, $startDate, $remoteWork);
        }

        return $warnings;
    }

    /**
     * @return array<OverlapWarning>
     */
    private function checkDateForOverlaps(User $user, \DateTimeInterface $date, RemoteWork $remoteWork): array
    {
        $warnings = [];

        // Check for existing remote work entries
        $existingRemoteWork = $this->remoteWorkRepository->findByUserAndDate($user, $date);
        foreach ($existingRemoteWork as $existing) {
            if ($existing->getId() === $remoteWork->getId()) {
                continue;
            }
            if ($existing->isRejected()) {
                continue;
            }

            $warnings[] = new OverlapWarning(
                $existing->getType(),
                $date,
                'remote_work.overlap_warning'
            );
        }

        // Check for absences (vacation, sickness) if WorkContractBundle is available
        if ($this->absenceRepository !== null) {
            $absences = $this->absenceRepository->findBy(['user' => $user, 'date' => $date]);
            foreach ($absences as $absence) {
                if ($absence->isRejected()) {
                    continue;
                }

                $warnings[] = new OverlapWarning(
                    $this->getAbsenceTypeName($absence),
                    $date,
                    'remote_work.overlap_with_absence'
                );
            }
        }

        // Check for public holidays if available
        if ($this->publicHolidayRepository !== null) {
            $holidayGroup = $user->getPublicHolidayGroup();
            if (\is_string($holidayGroup)) {
                $holidayGroup = (int) $holidayGroup;
            }

            $publicHolidays = $this->publicHolidayRepository->findForDay($date, $holidayGroup);
            foreach ($publicHolidays as $holiday) {
                if (!$holiday->isHalfDay()) {
                    $warnings[] = new OverlapWarning(
                        'public_holiday',
                        $date,
                        'remote_work.overlap_with_holiday'
                    );
                }
            }
        }

        return $warnings;
    }

    private function getAbsenceTypeName(Absence $absence): string
    {
        return match ($absence->getType()) {
            Absence::HOLIDAY => 'holiday',
            Absence::SICKNESS => 'sickness',
            Absence::TIME_OFF => 'time_off',
            default => 'absence',
        };
    }
}
