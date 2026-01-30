<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle;

use App\Entity\User;
use App\Timesheet\DateTimeFactory;
use App\WorkingTime\WorkingTimeService;
use KimaiPlugin\RemoteWorkBundle\CalDav\CalDavService;
use KimaiPlugin\RemoteWorkBundle\Entity\RemoteWork;
use KimaiPlugin\RemoteWorkBundle\Model\RemoteWorkStatistic;
use KimaiPlugin\RemoteWorkBundle\Repository\RemoteWorkRepository;

final class RemoteWorkService
{
    public function __construct(
        private readonly RemoteWorkRepository $repository,
        private readonly RemoteWorkConfiguration $configuration,
        private readonly WorkingTimeService $workingTimeService,
        private readonly CalDavService $calDavService,
    ) {
    }

    public function getRepository(): RemoteWorkRepository
    {
        return $this->repository;
    }

    public function save(RemoteWork $remoteWork): void
    {
        $this->repository->save($remoteWork);
        $this->syncToCalendar($remoteWork);
    }

    public function delete(RemoteWork $remoteWork): void
    {
        $this->calDavService->deleteEvent($remoteWork);
        $this->repository->remove($remoteWork);
    }

    /**
     * Creates new remote work entries. For date range,
     * creates one entry per working day (similar to Absence behavior).
     *
     * @return array<RemoteWork>
     */
    public function createNewEntries(User $currentUser, RemoteWork $remoteWork, ?\DateTimeInterface $endDate = null): array
    {
        $user = $remoteWork->getUser();
        if ($user === null) {
            throw new \InvalidArgumentException('RemoteWork must have a user');
        }

        $entries = [];
        $now = DateTimeFactory::createByUser($currentUser)->createDateTime();

        $startDate = $remoteWork->getDate();
        if ($startDate === null) {
            throw new \InvalidArgumentException('RemoteWork must have a date');
        }

        $workingDays = $this->getWorkingDays($user, $startDate, $endDate);

        if (\count($workingDays) === 0) {
            throw new \InvalidArgumentException('No working days found in the selected date range');
        }

        foreach ($workingDays as $day) {
            $entry = clone $remoteWork;
            $entry->setDate($day->setTime(0, 0, 0));
            $this->applyStatus($entry, $currentUser, $now);
            $this->repository->save($entry);
            $this->syncToCalendar($entry);
            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * Syncs a remote work entry to the calendar if it's approved (or approval not required).
     */
    private function syncToCalendar(RemoteWork $remoteWork): void
    {
        // Only sync approved entries, or all entries if approval is not required
        if ($remoteWork->isApproved()) {
            $this->calDavService->createOrUpdateEvent($remoteWork);
        }
    }

    /**
     * Applies the correct status based on configuration.
     */
    private function applyStatus(RemoteWork $remoteWork, User $currentUser, \DateTimeInterface $now): void
    {
        if ($this->configuration->isApprovalRequired()) {
            // Status stays 'new', needs approval
            return;
        }

        // Auto-approve
        $remoteWork->approve($currentUser, $now);
    }

    /**
     * Returns working days between start and end date.
     *
     * @return array<\DateTimeImmutable>
     */
    public function getWorkingDays(User $user, \DateTimeInterface $start, ?\DateTimeInterface $end = null): array
    {
        $days = [];

        $factory = DateTimeFactory::createByUser($user);
        $startDate = $factory->create($start->format('Y-m-d 00:00:00'));

        $calculator = $this->workingTimeService->getContractMode($user)->getCalculator($user);

        if ($calculator->isWorkDay($startDate)) {
            $days[] = \DateTimeImmutable::createFromInterface($startDate);
        }

        if ($end !== null && $end > $start) {
            $currentDate = \DateTimeImmutable::createFromInterface($startDate);
            $endDate = \DateTimeImmutable::createFromInterface($end);

            while ($currentDate < $endDate) {
                $currentDate = $currentDate->modify('+1 day');
                if ($calculator->isWorkDay($currentDate)) {
                    $days[] = $currentDate;
                }
            }
        }

        return $days;
    }

    /**
     * Calculates statistics for a user in a given year.
     */
    public function calculateStatistic(User $user, int $year): RemoteWorkStatistic
    {
        $stats = new RemoteWorkStatistic();

        $entries = $this->repository->findApprovedByUserAndYear($user, $year);

        foreach ($entries as $entry) {
            $dayValue = $entry->getDayValue();

            if ($entry->isHomeoffice()) {
                $stats->addHomeOfficeDays($dayValue);
            } elseif ($entry->isBusinessTrip()) {
                $stats->addBusinessTripDays($dayValue);
            }
        }

        return $stats;
    }

    /**
     * Approves multiple remote work entries.
     *
     * @param iterable<RemoteWork> $entries
     */
    public function approve(iterable $entries, User $approver): void
    {
        $now = DateTimeFactory::createByUser($approver)->createDateTime();
        $toSave = [];

        foreach ($entries as $entry) {
            $entry->approve($approver, $now);
            $toSave[] = $entry;
        }

        if (\count($toSave) > 0) {
            $this->repository->batchSave($toSave);

            // Sync approved entries to calendar
            foreach ($toSave as $entry) {
                $this->calDavService->createOrUpdateEvent($entry);
            }
        }
    }

    /**
     * Rejects multiple remote work entries.
     *
     * @param iterable<RemoteWork> $entries
     */
    public function reject(iterable $entries): void
    {
        $toSave = [];

        foreach ($entries as $entry) {
            // Delete from calendar if it was previously approved
            if ($entry->isApproved()) {
                $this->calDavService->deleteEvent($entry);
            }
            $entry->reject();
            $toSave[] = $entry;
        }

        if (\count($toSave) > 0) {
            $this->repository->batchSave($toSave);
        }
    }

    /**
     * Deletes multiple remote work entries.
     *
     * @param iterable<RemoteWork> $entries
     */
    public function batchDelete(iterable $entries): void
    {
        foreach ($entries as $entry) {
            $this->calDavService->deleteEvent($entry);
        }
        $this->repository->batchDelete($entries);
    }

    /**
     * Returns entries for a specific user and year.
     *
     * @return array<RemoteWork>
     */
    public function findByUserAndYear(User $user, int $year): array
    {
        return $this->repository->findByUserAndYear($user, $year);
    }

    /**
     * Returns entries for a specific user and date.
     *
     * @return array<RemoteWork>
     */
    public function findByUserAndDate(User $user, \DateTimeInterface $date): array
    {
        return $this->repository->findByUserAndDate($user, $date);
    }

    /**
     * Returns entries for a specific user, year and month.
     *
     * @return array<RemoteWork>
     */
    public function findByUserAndMonth(User $user, int $year, int $month): array
    {
        return $this->repository->findByUserAndMonth($user, $year, $month);
    }

    /**
     * Syncs multiple entries to calendar (only approved ones).
     *
     * @param array<RemoteWork> $entries
     */
    public function syncToCalendarBatch(array $entries): int
    {
        $synced = 0;
        foreach ($entries as $entry) {
            if ($entry->isApproved() && $this->calDavService->createOrUpdateEvent($entry)) {
                $synced++;
            }
        }

        return $synced;
    }
}
