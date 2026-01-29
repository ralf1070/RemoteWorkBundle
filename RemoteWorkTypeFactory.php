<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle;

use KimaiPlugin\RemoteWorkBundle\Entity\RemoteWork;
use KimaiPlugin\RemoteWorkBundle\Model\RemoteWorkType;
use KimaiPlugin\RemoteWorkBundle\Model\Type\BusinessTripType;
use KimaiPlugin\RemoteWorkBundle\Model\Type\HomeofficeType;

final class RemoteWorkTypeFactory
{
    /**
     * @var array<string, RemoteWorkType>
     */
    private array $cache = [];

    public function fromRemoteWork(RemoteWork $remoteWork): RemoteWorkType
    {
        return $this->create($remoteWork->getType());
    }

    public function create(string $name): RemoteWorkType
    {
        if (\count($this->cache) === 0) {
            foreach ($this->all() as $type) {
                $this->cache[$type->getType()] = $type;
            }
        }

        if (\array_key_exists($name, $this->cache)) {
            return $this->cache[$name];
        }

        throw new \InvalidArgumentException('Unknown remote work type: ' . $name);
    }

    /**
     * @return array<RemoteWorkType>
     */
    public function all(): array
    {
        return [
            $this->createHomeoffice(),
            $this->createBusinessTrip(),
        ];
    }

    public function createHomeoffice(): RemoteWorkType
    {
        return new HomeofficeType();
    }

    public function createBusinessTrip(): RemoteWorkType
    {
        return new BusinessTripType();
    }
}
