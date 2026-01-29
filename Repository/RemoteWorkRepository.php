<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use KimaiPlugin\RemoteWorkBundle\Constants;
use KimaiPlugin\RemoteWorkBundle\Entity\RemoteWork;

/**
 * @extends EntityRepository<RemoteWork>
 */
class RemoteWorkRepository extends EntityRepository
{
    public function save(RemoteWork $remoteWork): void
    {
        $em = $this->getEntityManager();
        $em->persist($remoteWork);
        $em->flush();
    }

    public function remove(RemoteWork $remoteWork): void
    {
        $em = $this->getEntityManager();
        $em->remove($remoteWork);
        $em->flush();
    }

    /**
     * @param iterable<RemoteWork> $entries
     */
    public function batchSave(iterable $entries): void
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            foreach ($entries as $entry) {
                $em->persist($entry);
            }
            $em->flush();
            $em->commit();
        } catch (\Exception $ex) {
            $em->rollback();
            throw $ex;
        }
    }

    /**
     * @param iterable<RemoteWork> $entries
     */
    public function batchDelete(iterable $entries): void
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            foreach ($entries as $entry) {
                $em->remove($entry);
            }
            $em->flush();
            $em->commit();
        } catch (\Exception $ex) {
            $em->rollback();
            throw $ex;
        }
    }

    /**
     * @return array<RemoteWork>
     */
    public function findByUserAndYear(User $user, int $year): array
    {
        $qb = $this->createQueryBuilder('r');
        $qb->select('r')
            ->where($qb->expr()->eq('r.user', ':user'))
            ->andWhere($qb->expr()->eq('YEAR(r.date)', ':year'))
            ->setParameter('user', $user->getId())
            ->setParameter('year', $year)
            ->orderBy('r.date', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<RemoteWork>
     */
    public function findApprovedByUserAndYear(User $user, int $year): array
    {
        $qb = $this->createQueryBuilder('r');
        $qb->select('r')
            ->where($qb->expr()->eq('r.user', ':user'))
            ->andWhere($qb->expr()->eq('YEAR(r.date)', ':year'))
            ->andWhere($qb->expr()->eq('r.status', ':status'))
            ->setParameter('user', $user->getId())
            ->setParameter('year', $year)
            ->setParameter('status', Constants::STATUS_APPROVED)
            ->orderBy('r.date', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<RemoteWork>
     */
    public function findByUserAndPeriod(User $user, \DateTimeInterface $begin, \DateTimeInterface $end): array
    {
        $qb = $this->createQueryBuilder('r');
        $qb->select('r')
            ->where($qb->expr()->eq('r.user', ':user'))
            ->andWhere($qb->expr()->gte('r.date', ':begin'))
            ->andWhere($qb->expr()->lte('r.date', ':end'))
            ->setParameter('user', $user->getId())
            ->setParameter('begin', $begin->format('Y-m-d 00:00:00'))
            ->setParameter('end', $end->format('Y-m-d 23:59:59'))
            ->orderBy('r.date', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<RemoteWork>
     */
    public function findByUserAndDate(User $user, \DateTimeInterface $date): array
    {
        $qb = $this->createQueryBuilder('r');
        $qb->select('r')
            ->where($qb->expr()->eq('r.user', ':user'))
            ->andWhere($qb->expr()->eq('DATE(r.date)', ':date'))
            ->setParameter('user', $user->getId())
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('r.date', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<RemoteWork>
     */
    public function findByUserYearAndType(User $user, int $year, string $type): array
    {
        $qb = $this->createQueryBuilder('r');
        $qb->select('r')
            ->where($qb->expr()->eq('r.user', ':user'))
            ->andWhere($qb->expr()->eq('YEAR(r.date)', ':year'))
            ->andWhere($qb->expr()->eq('r.type', ':type'))
            ->setParameter('user', $user->getId())
            ->setParameter('year', $year)
            ->setParameter('type', $type)
            ->orderBy('r.date', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<RemoteWork>
     */
    public function findPendingForApproval(User $user): array
    {
        $qb = $this->createQueryBuilder('r');
        $qb->select('r')
            ->where($qb->expr()->eq('r.user', ':user'))
            ->andWhere($qb->expr()->eq('r.status', ':status'))
            ->setParameter('user', $user->getId())
            ->setParameter('status', Constants::STATUS_NEW)
            ->orderBy('r.date', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
