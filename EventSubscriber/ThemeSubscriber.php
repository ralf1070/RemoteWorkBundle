<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\EventSubscriber;

use App\Event\ThemeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Injects CSS for remote work types into the theme.
 */
final class ThemeSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ThemeEvent::STYLESHEET => ['onStylesheet', 100],
        ];
    }

    public function onStylesheet(ThemeEvent $event): void
    {
        $css = <<<'CSS'
<style>
:root {
    --kimai-homeoffice: var(--tblr-azure);
    --kimai-homeoffice-bg: var(--tblr-azure-lt);
    --kimai-business-trip: var(--tblr-orange);
    --kimai-business-trip-bg: var(--tblr-orange-lt);
}
.homeoffice { color: var(--kimai-homeoffice); }
.business-trip { color: var(--kimai-business-trip); }
.bg-homeoffice { background-color: var(--kimai-homeoffice-bg); --tblr-table-bg: var(--kimai-homeoffice-bg); }
.bg-homeoffice i.fas { color: var(--kimai-homeoffice); }
.bg-business-trip { background-color: var(--kimai-business-trip-bg); --tblr-table-bg: var(--kimai-business-trip-bg); }
.bg-business-trip i.fas { color: var(--kimai-business-trip); }
</style>
CSS;

        $event->addContent($css);
    }
}
