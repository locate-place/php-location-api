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
use App\Repository\AlternateNameRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class AlternateName
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-08-27)
 * @since 0.1.0 (2023-08-27) First version.
 */
#[ORM\Entity(repositoryClass: AlternateNameRepository::class)]
#[ORM\Index(columns: ['location_id'])]
#[ORM\Index(columns: ['alternate_name_id'])]
#[ORM\Index(columns: ['iso_language'])]
#[ORM\Index(columns: ['alternate_name'])]
#[ORM\Index(columns: ['type'])]
#[ORM\Index(columns: ['source'])]
#[ORM\HasLifecycleCallbacks]
class AlternateName
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'alternateNames')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Location $location = null;

    #[ORM\Column(length: 400)]
    private ?string $alternateName = null;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $isoLanguage = null;

    #[ORM\Column]
    private ?bool $preferredName = null;

    #[ORM\Column]
    private ?bool $shortName = null;

    #[ORM\Column]
    private ?bool $colloquial = null;

    #[ORM\Column]
    private ?bool $historic = null;

    #[ORM\Column]
    private ?int $alternateNameId = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $changed = null;

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
    public function getAlternateName(): ?string
    {
        return $this->alternateName;
    }

    /**
     * @param string $alternateName
     * @return $this
     */
    public function setAlternateName(string $alternateName): static
    {
        $this->alternateName = $alternateName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIsoLanguage(): ?string
    {
        return $this->isoLanguage;
    }

    /**
     * @param string|null $isoLanguage
     * @return $this
     */
    public function setIsoLanguage(?string $isoLanguage): static
    {
        $this->isoLanguage = $isoLanguage;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isPreferredName(): ?bool
    {
        return $this->preferredName;
    }

    /**
     * @param bool $preferredName
     * @return $this
     */
    public function setPreferredName(bool $preferredName): static
    {
        $this->preferredName = $preferredName;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isShortName(): ?bool
    {
        return $this->shortName;
    }

    /**
     * @param bool $shortName
     * @return $this
     */
    public function setShortName(bool $shortName): static
    {
        $this->shortName = $shortName;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isColloquial(): ?bool
    {
        return $this->colloquial;
    }

    /**
     * @param bool $colloquial
     * @return $this
     */
    public function setColloquial(bool $colloquial): static
    {
        $this->colloquial = $colloquial;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isHistoric(): ?bool
    {
        return $this->historic;
    }

    /**
     * @param bool $historic
     * @return $this
     */
    public function setHistoric(bool $historic): static
    {
        $this->historic = $historic;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getAlternateNameId(): ?int
    {
        return $this->alternateNameId;
    }

    /**
     * @param int $alternateNameId
     * @return $this
     */
    public function setAlternateNameId(int $alternateNameId): static
    {
        $this->alternateNameId = $alternateNameId;

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
     * @param string|null $type
     * @return $this
     */
    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * @param string|null $source
     * @return $this
     */
    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isChanged(): ?bool
    {
        return $this->changed;
    }

    /**
     * @param bool $changed
     * @return $this
     */
    public function setChanged(bool $changed): static
    {
        $this->changed = $changed;

        return $this;
    }
}
