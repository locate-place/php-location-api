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

use App\DBAL\GeoLocation\Types\PostgreSQL\PostGISType;
use App\DBAL\GeoLocation\ValueObject\Point;
use App\Entity\Trait\TimestampsTrait;
use App\Repository\ZipCodeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ZipCode
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-03-14)
 * @since 0.1.0 (2024-03-14) First version.
 */
#[ORM\Entity(repositoryClass: ZipCodeRepository::class)]
#[ORM\Index(columns: ['coordinate'], flags: ['gist'])]
#[ORM\Index(columns: ['place_name'])]
#[ORM\Index(columns: ['postal_code'])]
#[ORM\UniqueConstraint(columns: ['country_id', 'admin_code_id', 'place_name', 'postal_code'])]
#[ORM\HasLifecycleCallbacks]
class ZipCode
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'zipCodes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Country $country = null;

    #[ORM\ManyToOne(inversedBy: 'zipCodes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AdminCode $adminCode = null;

    #[ORM\Column(length: 180)]
    private ?string $placeName = null;

    #[ORM\Column(length: 20)]
    private ?string $postalCode = null;

    #[ORM\Column(type: PostGISType::GEOGRAPHY, nullable: false, options: ['geometry_type' => 'POINT', 'srid' => 4326])]
    private ?Point $coordinate = null;

    #[ORM\Column(nullable: true)]
    private ?int $accuracy = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Country|null
     */
    public function getCountry(): ?Country
    {
        return $this->country;
    }

    /**
     * @param Country|null $country
     * @return $this
     */
    public function setCountry(?Country $country): static
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return AdminCode|null
     */
    public function getAdminCode(): ?AdminCode
    {
        return $this->adminCode;
    }

    /**
     * @param AdminCode|null $adminCode
     * @return $this
     */
    public function setAdminCode(?AdminCode $adminCode): static
    {
        $this->adminCode = $adminCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPlaceName(): ?string
    {
        return $this->placeName;
    }

    /**
     * @param string $placeName
     * @return $this
     */
    public function setPlaceName(string $placeName): static
    {
        $this->placeName = $placeName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    /**
     * @param string $postalCode
     * @return $this
     */
    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * @return Point|null
     */
    public function getCoordinate(): ?Point
    {
        return $this->coordinate;
    }

    /**
     * @param Point $coordinate
     * @return $this
     */
    public function setCoordinate(Point $coordinate): static
    {
        $this->coordinate = $coordinate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getAccuracy(): ?int
    {
        return $this->accuracy;
    }

    /**
     * @param int|null $accuracy
     * @return $this
     */
    public function setAccuracy(?int $accuracy): static
    {
        $this->accuracy = $accuracy;

        return $this;
    }
}
