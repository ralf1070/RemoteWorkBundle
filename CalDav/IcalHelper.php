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
use Symfony\Contracts\Translation\TranslatorInterface;

final class IcalHelper
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function escapeText(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(';', '\\;', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace("\n", '\\n', $text);
        $text = str_replace("\r", '', $text);

        return $text;
    }

    public function generateUid(RemoteWork $entry, string $domain): string
    {
        $user = $entry->getUser();
        $date = $entry->getDate();

        if ($user === null || $date === null) {
            throw new \InvalidArgumentException('RemoteWork must have user and date');
        }

        return sprintf(
            'remote-work-%d-%s-%s@%s',
            $user->getId(),
            $date->format('Ymd'),
            $entry->getType(),
            $domain
        );
    }

    public function generateSummary(RemoteWork $entry, bool $includeComment = false): string
    {
        $type = $entry->isHomeoffice() ? 'homeoffice' : 'business_trip';
        $summary = $this->translator->trans($type);

        if ($entry->isHalfDay()) {
            $summary .= ' (' . $this->translator->trans('day_half') . ')';
        }

        if ($includeComment) {
            $comment = $entry->getComment();
            if ($comment !== '') {
                $summary .= ': ' . $comment;
            }
        }

        return $summary;
    }

    /**
     * Generates a single VEVENT for a RemoteWork entry.
     */
    public function generateEvent(RemoteWork $entry, string $domain, string $dtstamp, int $sequence): string
    {
        $date = $entry->getDate();
        if ($date === null) {
            throw new \InvalidArgumentException('RemoteWork must have a date');
        }

        $uid = $this->generateUid($entry, $domain);
        $summary = $this->generateSummary($entry, true);
        $dtstart = $date->format('Ymd');
        $dtend = $date->modify('+1 day')->format('Ymd');

        $lines = [
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . $dtstamp,
            'DTSTART;VALUE=DATE:' . $dtstart,
            'DTEND;VALUE=DATE:' . $dtend,
            'SUMMARY:' . $this->escapeText($summary),
            'SEQUENCE:' . $sequence,
            'TRANSP:OPAQUE',
        ];

        $comment = $entry->getComment();
        if ($comment !== '') {
            $lines[] = 'DESCRIPTION:' . $this->escapeText($comment);
        }

        $lines[] = 'END:VEVENT';

        return implode("\r\n", $lines);
    }

    /**
     * Generates a complete VCALENDAR with multiple events.
     *
     * @param array<RemoteWork> $entries
     */
    public function generateCalendar(array $entries, User $user, string $domain): string
    {
        $now = new \DateTimeImmutable();
        $sequence = (int) ($now->getTimestamp() / 10);
        $dtstamp = $now->format('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Kimai//RemoteWorkBundle//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:' . $this->escapeText($this->translator->trans('remote_work')) . ' - ' . $this->escapeText($user->getDisplayName()),
        ];

        foreach ($entries as $entry) {
            if ($entry->getDate() === null) {
                continue;
            }
            $lines[] = $this->generateEvent($entry, $domain, $dtstamp, $sequence);
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines);
    }

    /**
     * Generates a complete VCALENDAR with a single event (for CalDAV PUT).
     */
    public function generateSingleEventCalendar(RemoteWork $entry, string $domain): string
    {
        $now = new \DateTimeImmutable();
        $sequence = (int) ($now->getTimestamp() / 10);
        $dtstamp = $now->format('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Kimai//RemoteWorkBundle//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            $this->generateEvent($entry, $domain, $dtstamp, $sequence),
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines);
    }
}
