<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\Entity;

use App\Entity\User;
use App\Export\Annotation as Exporter;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use KimaiPlugin\RemoteWorkBundle\Constants;
use KimaiPlugin\RemoteWorkBundle\Repository\RemoteWorkRepository;
use KimaiPlugin\RemoteWorkBundle\Validator\Constraints as Constraints;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_remote_work')]
#[ORM\Index(columns: ['user_id', 'date'], name: 'IDX_REMOTE_WORK_USER_DATE')]
#[ORM\Entity(repositoryClass: RemoteWorkRepository::class)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[Constraints\RemoteWork]
#[Exporter\Order(['user', 'type', 'date', 'halfDay', 'status', 'comment'])]
#[Exporter\Expose(name: 'user', label: 'username', type: 'string', exp: 'object.getUser() === null ? null : object.getUser().getDisplayName()')]
class RemoteWork
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?User $user = null;

    #[ORM\Column(name: 'type', type: 'string', length: 30, nullable: false)]
    #[Assert\NotNull]
    #[Assert\Choice(choices: [Constants::TYPE_HOMEOFFICE, Constants::TYPE_BUSINESS_TRIP])]
    #[Exporter\Expose(name: 'type', label: 'type')]
    private string $type = Constants::TYPE_HOMEOFFICE;

    #[ORM\Column(name: 'date', type: Types::DATE_IMMUTABLE, nullable: false)]
    #[Assert\NotNull]
    #[Exporter\Expose(label: 'date', type: 'date')]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(name: 'half_day', type: 'boolean', nullable: false, options: ['default' => false])]
    #[Assert\NotNull]
    #[Exporter\Expose(label: 'day_half', type: 'boolean')]
    private bool $halfDay = false;

    #[ORM\Column(name: 'comment', type: 'string', length: 250, nullable: false)]
    #[Assert\Length(max: 250)]
    #[Exporter\Expose(label: 'comment')]
    private string $comment = '';

    #[ORM\Column(name: 'status', type: 'string', length: 20, nullable: false)]
    #[Assert\NotNull]
    #[Exporter\Expose(name: 'status', label: 'status')]
    private string $status = Constants::STATUS_NEW;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\Column(name: 'created_date', type: Types::DATETIME_IMMUTABLE, nullable: false)]
    #[Assert\NotNull]
    private \DateTimeImmutable $createdDate;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'approved_by', nullable: true, onDelete: 'SET NULL')]
    private ?User $approvedBy = null;

    #[ORM\Column(name: 'approved_date', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $approvedDate = null;

    /**
     * Only used for Symfony form - end date for date range selection.
     */
    private ?\DateTimeInterface $end = null;

    public function __construct(User $createdBy, \DateTimeInterface $createdDate)
    {
        $this->createdBy = $createdBy;
        $this->createdDate = \DateTimeImmutable::createFromInterface($createdDate);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function isHomeoffice(): bool
    {
        return $this->type === Constants::TYPE_HOMEOFFICE;
    }

    public function isBusinessTrip(): bool
    {
        return $this->type === Constants::TYPE_BUSINESS_TRIP;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): void
    {
        $this->date = $date === null ? null : \DateTimeImmutable::createFromInterface($date);
    }

    public function isHalfDay(): bool
    {
        return $this->halfDay;
    }

    public function setHalfDay(bool $halfDay): void
    {
        $this->halfDay = $halfDay;
    }

    /**
     * Returns the day value: 1.0 for full day, 0.5 for half day.
     */
    public function getDayValue(): float
    {
        return $this->halfDay ? 0.5 : 1.0;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment ?? '';
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isNew(): bool
    {
        return $this->status === Constants::STATUS_NEW;
    }

    public function isApproved(): bool
    {
        return $this->status === Constants::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === Constants::STATUS_REJECTED;
    }

    public function approve(User $user, \DateTimeInterface $dateTime): void
    {
        $this->approvedBy = $user;
        $this->approvedDate = \DateTimeImmutable::createFromInterface($dateTime);
        $this->status = Constants::STATUS_APPROVED;
    }

    public function reject(): void
    {
        $this->approvedBy = null;
        $this->approvedDate = null;
        $this->status = Constants::STATUS_REJECTED;
    }

    public function setApproved(): void
    {
        $this->status = Constants::STATUS_APPROVED;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function getCreatedDate(): \DateTimeImmutable
    {
        return $this->createdDate;
    }

    public function getApprovedBy(): ?User
    {
        return $this->approvedBy;
    }

    public function getApprovedDate(): ?\DateTimeImmutable
    {
        return $this->approvedDate;
    }

    /**
     * Only used for Symfony form - end date for date range selection.
     */
    public function getEnd(): ?\DateTimeInterface
    {
        return $this->end;
    }

    /**
     * Only used for Symfony form - end date for date range selection.
     */
    public function setEnd(?\DateTimeInterface $end): void
    {
        $this->end = $end;
    }

    public function __clone()
    {
        if ($this->id !== null) {
            $this->id = null;
        }
    }
}
