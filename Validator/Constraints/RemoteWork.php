<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class RemoteWork extends Constraint
{
    public const MISSING_USER = 'remote-work-01';
    public const MISSING_DATE = 'remote-work-02';
    public const DATE_LOCKED = 'remote-work-05';
    public const FIRST_DAY = 'remote-work-06';
    public const LAST_DAY = 'remote-work-07';
    public const NO_WORKING_DAY = 'remote-work-09';

    protected const ERROR_NAMES = [
        self::MISSING_USER => 'The user is required.',
        self::MISSING_DATE => 'The date is required.',
        self::DATE_LOCKED => 'The date is locked and cannot be changed.',
        self::FIRST_DAY => 'The date is before the user\'s first working day.',
        self::LAST_DAY => 'The date is after the user\'s last working day.',
        self::NO_WORKING_DAY => 'The selected date is not a working day.',
    ];

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    public static function getErrorName(string $errorCode): string
    {
        return self::ERROR_NAMES[$errorCode] ?? 'Unknown error';
    }
}
