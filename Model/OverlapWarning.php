<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\Model;

/**
 * Represents an overlap warning that can be ignored by the user.
 */
final class OverlapWarning
{
    public function __construct(
        private readonly string $type,
        private readonly \DateTimeInterface $date,
        private readonly string $messageKey,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function getMessageKey(): string
    {
        return $this->messageKey;
    }

    public function getDateFormatted(): string
    {
        return $this->date->format('Y-m-d');
    }
}
