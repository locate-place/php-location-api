<?php

/*
 * This file is part of the locate-place/php-location-api project.
 *
 * (c) Björn Hempel <https://www.hempel.li/>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Entity;

use App\DBAL\GeoLocation\Types\PostgreSQL\Base\BasePostGISType;
use App\DBAL\GeoLocation\ValueObject\Polygon;
use App\Entity\Trait\TimestampsTrait;
use App\Repository\ZipCodeAreaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ZipCodeArea
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-03-14)
 * @since 0.1.0 (2024-03-14) First version.
 */
#[ORM\Entity(repositoryClass: ZipCodeAreaRepository::class)]
#[ORM\Index(columns: ['coordinates'], flags: ['gist'])]
#[ORM\Index(columns: ['zip_code'])]
#[ORM\Index(columns: ['place_name'])]
#[ORM\UniqueConstraint(columns: ['country_id', 'place_name', 'zip_code', 'number'])]
#[ORM\HasLifecycleCallbacks]
class ZipCodeArea
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $type = null;

    #[ORM\Column(length: 20)]
    private ?string $zipCode = null;

    #[ORM\Column(length: 1024)]
    private ?string $placeName = null;

    #[ORM\Column(nullable: true)]
    private ?int $population = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, nullable: true)]
    private ?string $area = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 1, nullable: true)]
    private ?string $populationDensity = null;

    #[ORM\Column(type: BasePostGISType::GEOGRAPHY, nullable: false, options: ['geometry_type' => 'Polygon', 'srid' => 4326, 'comment' => 'Polygon,4326'])]
    private ?Polygon $coordinates = null;

    #[ORM\ManyToOne(inversedBy: 'zipCodeAreas')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Country $country = null;

    #[ORM\Column(nullable: false)]
    private ?int $number = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    /**
     * @param string $zipCode
     * @return $this
     */
    public function setZipCode(string $zipCode): static
    {
        $this->zipCode = $zipCode;

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
     * @return int|null
     */
    public function getPopulation(): ?int
    {
        return $this->population;
    }

    /**
     * @param int|null $population
     * @return $this
     */
    public function setPopulation(?int $population): static
    {
        $this->population = $population;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getArea(): ?string
    {
        return $this->area;
    }

    /**
     * @param string|null $area
     * @return $this
     */
    public function setArea(?string $area): static
    {
        $this->area = $area;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPopulationDensity(): ?string
    {
        return $this->populationDensity;
    }

    /**
     * @param string|null $populationDensity
     * @return $this
     */
    public function setPopulationDensity(?string $populationDensity): static
    {
        $this->populationDensity = $populationDensity;

        return $this;
    }

    /**
     * @return Polygon|null
     */
    public function getCoordinates(): ?Polygon
    {
        return $this->coordinates;
    }

    /**
     * @param Polygon $coordinates
     * @return $this
     */
    public function setCoordinates(Polygon $coordinates): static
    {
        $this->coordinates = $coordinates;

        return $this;
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

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(?int $number): static
    {
        $this->number = $number;

        return $this;
    }
}
