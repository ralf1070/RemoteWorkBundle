<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\CalDav;

use App\Configuration\SystemConfiguration;

final class CalDavConfiguration
{
    public function __construct(private readonly SystemConfiguration $systemConfiguration)
    {
    }

    public function isEnabled(): bool
    {
        return (bool) $this->systemConfiguration->find('remote_work.caldav_enabled');
    }

    public function getUrl(): string
    {
        return (string) $this->systemConfiguration->find('remote_work.caldav_url');
    }

    public function getUsername(): string
    {
        return (string) $this->systemConfiguration->find('remote_work.caldav_username');
    }

    public function getPassword(): string
    {
        return (string) $this->systemConfiguration->find('remote_work.caldav_password');
    }

    public function getDomain(): string
    {
        $url = $this->getUrl();
        if ($url === '') {
            return 'kimai.local';
        }

        $parsed = parse_url($url);

        return $parsed['host'] ?? 'kimai.local';
    }
}
