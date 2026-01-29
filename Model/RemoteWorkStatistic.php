<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\Model;

final class RemoteWorkStatistic
{
    private float $homeOfficeDays = 0;
    private float $businessTripDays = 0;

    public function getHomeOfficeDays(): float
    {
        return $this->homeOfficeDays;
    }

    public function addHomeOfficeDays(float $days): void
    {
        $this->homeOfficeDays += $days;
    }

    public function getBusinessTripDays(): float
    {
        return $this->businessTripDays;
    }

    public function addBusinessTripDays(float $days): void
    {
        $this->businessTripDays += $days;
    }

    public function getTotalDays(): float
    {
        return $this->homeOfficeDays + $this->businessTripDays;
    }
}
