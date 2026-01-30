<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\CalDav;

use App\Entity\User;
use KimaiPlugin\RemoteWorkBundle\Entity\RemoteWork;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CalDavService
{
    public function __construct(
        private readonly CalDavConfiguration $configuration,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly IcalHelper $icalHelper,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->configuration->isEnabled();
    }

    /**
     * Creates or updates a calendar event for the given remote work entry.
     */
    public function createOrUpdateEvent(RemoteWork $entry): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $user = $entry->getUser();
        if ($user === null) {
            return false;
        }

        $ical = $this->icalHelper->generateSingleEventCalendar($entry, $this->configuration->getDomain());
        $url = $this->getEventUrl($entry);

        return $this->putEvent($url, $ical);
    }

    /**
     * Deletes a calendar event for the given remote work entry.
     */
    public function deleteEvent(RemoteWork $entry): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $user = $entry->getUser();
        if ($user === null) {
            return false;
        }

        $url = $this->getEventUrl($entry);

        return $this->deleteRequest($url);
    }

    /**
     * Syncs all remote work entries for a user to their calendar.
     *
     * @param array<RemoteWork> $entries
     */
    public function syncAllForUser(User $user, array $entries): int
    {
        if (!$this->isEnabled()) {
            return 0;
        }

        $synced = 0;
        foreach ($entries as $entry) {
            if ($this->createOrUpdateEvent($entry)) {
                $synced++;
            }
        }

        return $synced;
    }

    private function getEventUrl(RemoteWork $entry): string
    {
        $user = $entry->getUser();
        if ($user === null) {
            throw new \InvalidArgumentException('RemoteWork must have a user');
        }

        $date = $entry->getDate();
        if ($date === null) {
            throw new \InvalidArgumentException('RemoteWork must have a date');
        }

        $baseUrl = $this->configuration->getUrl();
        $calendarUrl = str_replace('{username}', $user->getUserIdentifier(), $baseUrl);

        // Ensure URL ends with /
        if (!str_ends_with($calendarUrl, '/')) {
            $calendarUrl .= '/';
        }

        $filename = sprintf(
            'remote-work-%d-%s-%s.ics',
            $user->getId(),
            $date->format('Ymd'),
            $entry->getType()
        );

        return $calendarUrl . $filename;
    }

    private function putEvent(string $url, string $ical): bool
    {
        try {
            $response = $this->httpClient->request('PUT', $url, [
                'body' => $ical,
                'headers' => [
                    'Content-Type' => 'text/calendar; charset=utf-8',
                ],
                'auth_basic' => [
                    $this->configuration->getUsername(),
                    $this->configuration->getPassword(),
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();

            // 201 Created or 204 No Content are success
            if ($statusCode !== 201 && $statusCode !== 204) {
                $this->logger->error('CalDAV PUT failed with HTTP ' . $statusCode, [
                    'url' => $url,
                    'response' => $response->getContent(false),
                ]);

                return false;
            }

            $this->logger->debug('CalDAV PUT successful', ['url' => $url, 'httpCode' => $statusCode]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('CalDAV PUT exception: ' . $e->getMessage(), ['url' => $url]);

            return false;
        }
    }

    private function deleteRequest(string $url): bool
    {
        try {
            $response = $this->httpClient->request('DELETE', $url, [
                'auth_basic' => [
                    $this->configuration->getUsername(),
                    $this->configuration->getPassword(),
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();

            // 204 No Content, 200 OK, or 404 Not Found are all acceptable
            if ($statusCode !== 204 && $statusCode !== 200 && $statusCode !== 404) {
                $this->logger->error('CalDAV DELETE failed with HTTP ' . $statusCode, [
                    'url' => $url,
                    'response' => $response->getContent(false),
                ]);

                return false;
            }

            $this->logger->debug('CalDAV DELETE successful', ['url' => $url, 'httpCode' => $statusCode]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('CalDAV DELETE exception: ' . $e->getMessage(), ['url' => $url]);

            return false;
        }
    }
}
