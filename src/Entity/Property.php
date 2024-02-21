<?php

/*
 * This file is part of the twelvepics-com/php-location-api project.
 *
 * (c) Björn Hempel <https://www.hempel.li/>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\TimestampsTrait;
use App\Repository\PropertyRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Property
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-02-20)
 * @since 0.1.0 (2024-02-20) First version.
 */
#[ORM\Entity(repositoryClass: PropertyRepository::class)]
#[ORM\Index(columns: ['location_id'])]
#[ORM\Index(columns: ['property_name'])]
#[ORM\Index(columns: ['property_value'])]
#[ORM\Index(columns: ['property_language'])]
#[ORM\HasLifecycleCallbacks]
class Property
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'properties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Location $location = null;

    #[ORM\Column(length: 200)]
    private ?string $propertyName = null;

    #[ORM\Column(length: 1024)]
    private ?string $propertyValue = null;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $propertyLanguage = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Location|null
     */
    public function getLocation(): ?Location
    {
        return $this->location;
    }

    /**
     * @param Location|null $location
     * @return $this
     */
    public function setLocation(?Location $location): static
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPropertyName(): ?string
    {
        return $this->propertyName;
    }

    /**
     * @param string $propertyName
     * @return $this
     */
    public function setPropertyName(string $propertyName): static
    {
        $this->propertyName = $propertyName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPropertyValue(): ?string
    {
        return $this->propertyValue;
    }

    /**
     * @param string|null $propertyValue
     * @return $this
     */
    public function setPropertyValue(?string $propertyValue): static
    {
        $this->propertyValue = $propertyValue;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPropertyLanguage(): ?string
    {
        return $this->propertyLanguage;
    }

    /**
     * @param string|null $propertyLanguage
     * @return $this
     */
    public function setPropertyLanguage(?string $propertyLanguage): static
    {
        $this->propertyLanguage = $propertyLanguage;

        return $this;
    }
}
