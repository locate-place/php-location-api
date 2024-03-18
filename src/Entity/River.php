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

use App\DBAL\GeoLocation\Types\PostgreSQL\PostGISLinestringType;
use App\DBAL\GeoLocation\ValueObject\Linestring;
use App\Entity\Trait\TimestampsTrait;
use App\Repository\RiverRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class River
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-03-16)
 * @since 0.1.0 (2024-03-16) First version.
 *
 * @see https://www.wasserblick.net/servlet/is/192028/rwseggeom_de.html?command=downloadContent&filename=rwseggeom_de.html
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
#[ORM\Entity(repositoryClass: RiverRepository::class)]
#[ORM\Index(columns: ['coordinates'], flags: ['gist'])]
#[ORM\Index(columns: ['name'])]
#[ORM\Index(columns: ['length'])]
#[ORM\HasLifecycleCallbacks]
class River
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 1024)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $length = null;

    #[ORM\Column(type: PostGISLinestringType::GEOGRAPHY, nullable: false, options: ['geometry_type' => 'LINESTRING', 'srid' => 4326])]
    private ?Linestring $coordinates = null;

    #[ORM\Column]
    private ?int $objectId = null;

    #[ORM\Column(length: 1)]
    private ?string $continua = null;

    #[ORM\Column(length: 1024)]
    private ?string $euSegCd = null;

    #[ORM\Column(length: 64)]
    private ?string $flowDirection = null;

    #[ORM\Column(length: 4)]
    private ?string $landCd = null;

    #[ORM\Column]
    private ?int $rbdCd = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?int $riverCd = null;

    #[ORM\Column(length: 1)]
    private ?string $scale = null;

    #[ORM\Column(length: 64)]
    private ?string $template = null;

    #[ORM\Column]
    private ?int $waCd = null;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $metadata = null;

    #[ORM\Column(nullable: true)]
    private ?int $number = null;

    #[ORM\ManyToOne(inversedBy: 'rivers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Country $country = null;

    #[ORM\Column(length: 10)]
    private ?string $type = null;

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
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLength(): ?string
    {
        return $this->length;
    }

    /**
     * @param string|null $length
     * @return $this
     */
    public function setLength(?string $length): static
    {
        $this->length = $length;

        return $this;
    }

    /**
     * @return Linestring|null
     */
    public function getCoordinates(): Linestring|null
    {
        return $this->coordinates;
    }

    /**
     * @param Linestring $coordinates
     * @return $this
     */
    public function setCoordinates(Linestring $coordinates): static
    {
        $this->coordinates = $coordinates;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getObjectId(): ?int
    {
        return $this->objectId;
    }

    /**
     * @param int $objectId
     * @return $this
     */
    public function setObjectId(int $objectId): static
    {
        $this->objectId = $objectId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getContinua(): ?string
    {
        return $this->continua;
    }

    /**
     * @param string $continua
     * @return $this
     */
    public function setContinua(string $continua): static
    {
        $this->continua = $continua;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEuSegCd(): ?string
    {
        return $this->euSegCd;
    }

    /**
     * @param string $euSegCd
     * @return $this
     */
    public function setEuSegCd(string $euSegCd): static
    {
        $this->euSegCd = $euSegCd;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFlowDirection(): ?string
    {
        return $this->flowDirection;
    }

    /**
     * @param string $flowDirection
     * @return $this
     */
    public function setFlowDirection(string $flowDirection): static
    {
        $this->flowDirection = $flowDirection;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLandCd(): ?string
    {
        return $this->landCd;
    }

    /**
     * @param string $landCd
     * @return $this
     */
    public function setLandCd(string $landCd): static
    {
        $this->landCd = $landCd;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getRbdCd(): ?int
    {
        return $this->rbdCd;
    }

    /**
     * @param int $rbdCd
     * @return $this
     */
    public function setRbdCd(int $rbdCd): static
    {
        $this->rbdCd = $rbdCd;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getRiverCd(): ?int
    {
        return $this->riverCd;
    }

    /**
     * @param int $riverCd
     * @return $this
     */
    public function setRiverCd(int $riverCd): static
    {
        $this->riverCd = $riverCd;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getScale(): ?string
    {
        return $this->scale;
    }

    /**
     * @param string $scale
     * @return $this
     */
    public function setScale(string $scale): static
    {
        $this->scale = $scale;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * @param string $template
     * @return $this
     */
    public function setTemplate(string $template): static
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getWaCd(): ?int
    {
        return $this->waCd;
    }

    /**
     * @param int $waCd
     * @return $this
     */
    public function setWaCd(int $waCd): static
    {
        $this->waCd = $waCd;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMetadata(): ?string
    {
        return $this->metadata;
    }

    /**
     * @param string|null $metadata
     * @return $this
     */
    public function setMetadata(?string $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getNumber(): ?int
    {
        return $this->number;
    }

    /**
     * @param int|null $number
     * @return $this
     */
    public function setNumber(?int $number): static
    {
        $this->number = $number;

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
}
