<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\EventSubscriber;

use App\Event\SystemConfigurationEvent;
use App\Form\Model\Configuration;
use App\Form\Model\SystemConfiguration;
use App\Form\Type\YesNoType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class SystemConfigurationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SystemConfigurationEvent::class => ['onSystemConfiguration', 90],
        ];
    }

    public function onSystemConfiguration(SystemConfigurationEvent $event): void
    {
        $event->addConfiguration(
            (new SystemConfiguration('remote_work'))
                ->setTranslationDomain('messages')
                ->setConfiguration([
                    (new Configuration('remote_work.approval_required'))
                        ->setLabel('remote_work.approval_required')
                        ->setType(YesNoType::class)
                        ->setOptions([
                            'help' => 'remote_work.approval_required.help',
                        ]),
                    (new Configuration('remote_work.caldav_enabled'))
                        ->setLabel('remote_work.caldav_enabled')
                        ->setType(YesNoType::class)
                        ->setOptions([
                            'help' => 'remote_work.caldav_enabled.help',
                        ]),
                    (new Configuration('remote_work.caldav_url'))
                        ->setLabel('remote_work.caldav_url')
                        ->setType(TextType::class)
                        ->setOptions([
                            'help' => 'remote_work.caldav_url.help',
                            'required' => false,
                        ]),
                    (new Configuration('remote_work.caldav_username'))
                        ->setLabel('remote_work.caldav_username')
                        ->setType(TextType::class)
                        ->setOptions([
                            'required' => false,
                        ]),
                    (new Configuration('remote_work.caldav_password'))
                        ->setLabel('remote_work.caldav_password')
                        ->setType(PasswordType::class)
                        ->setOptions([
                            'required' => false,
                            'always_empty' => false,
                        ]),
                ])
        );
    }
}
