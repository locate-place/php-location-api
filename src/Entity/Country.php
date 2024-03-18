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
use App\Repository\CountryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class Country
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2023-06-27)
 * @since 0.1.0 (2023-06-27) First version.
 */
#[ORM\Entity(repositoryClass: CountryRepository::class)]
#[UniqueEntity('code')]
#[ORM\UniqueConstraint(columns: ['code'])]
#[ORM\Index(columns: ['code'])]
#[ORM\HasLifecycleCallbacks]
class Country
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 2, unique: true)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /** @var Collection<int, Location> $locations */
    #[ORM\OneToMany(mappedBy: 'country', targetEntity: Location::class, orphanRemoval: true)]
    private Collection $locations;

    /** @var Collection<int, AdminCode> $adminCodes */
    #[ORM\OneToMany(mappedBy: 'country', targetEntity: AdminCode::class, orphanRemoval: true)]
    private Collection $adminCodes;

    /** @var Collection<int, Timezone> $timezones */
    #[ORM\OneToMany(mappedBy: 'country', targetEntity: Timezone::class, orphanRemoval: true)]
    private Collection $timezones;

    /** @var Collection<int, Import> $imports */
    #[ORM\OneToMany(mappedBy: 'country', targetEntity: Import::class, orphanRemoval: true)]
    private Collection $imports;

    /** @var Collection<int, ZipCode> $zipCodes */
    #[ORM\OneToMany(mappedBy: 'country', targetEntity: ZipCode::class, orphanRemoval: true)]
    private Collection $zipCodes;

    /** @var Collection<int, ZipCodeArea> $zipCodeAreas */
    #[ORM\OneToMany(mappedBy: 'country', targetEntity: ZipCodeArea::class, orphanRemoval: true)]
    private Collection $zipCodeAreas;

    /** @var Collection<int, River> $rivers */
    #[ORM\OneToMany(mappedBy: 'country', targetEntity: River::class, orphanRemoval: true)]
    private Collection $rivers;

    /**
     *
     */
    public function __construct()
    {
        $this->locations = new ArrayCollection();
        $this->adminCodes = new ArrayCollection();
        $this->timezones = new ArrayCollection();
        $this->imports = new ArrayCollection();
        $this->zipCodes = new ArrayCollection();
        $this->zipCodeAreas = new ArrayCollection();
        $this->rivers = new ArrayCollection();
    }

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
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
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
     * @return Collection<int, Location>
     */
    public function getLocations(): Collection
    {
        return $this->locations;
    }

    /**
     * @param Location $location
     * @return $this
     */
    public function addLocation(Location $location): static
    {
        if (!$this->locations->contains($location)) {
            $this->locations->add($location);
            $location->setCountry($this);
        }

        return $this;
    }

    /**
     * @param Location $location
     * @return $this
     */
    public function removeLocation(Location $location): static
    {
        if ($this->locations->removeElement($location)) {
            /* Set the owning side to null (unless already changed). */
            if ($location->getCountry() === $this) {
                $location->setCountry(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AdminCode>
     */
    public function getAdminCodes(): Collection
    {
        return $this->adminCodes;
    }

    /**
     * @param AdminCode $adminCode
     * @return $this
     */
    public function addAdminCode(AdminCode $adminCode): static
    {
        if (!$this->adminCodes->contains($adminCode)) {
            $this->adminCodes->add($adminCode);
            $adminCode->setCountry($this);
        }

        return $this;
    }

    /**
     * @param AdminCode $adminCode
     * @return $this
     */
    public function removeAdminCode(AdminCode $adminCode): static
    {
        if ($this->adminCodes->removeElement($adminCode)) {
            // set the owning side to null (unless already changed)
            if ($adminCode->getCountry() === $this) {
                $adminCode->setCountry(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Timezone>
     */
    public function getTimezones(): Collection
    {
        return $this->timezones;
    }

    /**
     * @param Timezone $timezone
     * @return $this
     */
    public function addTimezone(Timezone $timezone): static
    {
        if (!$this->timezones->contains($timezone)) {
            $this->timezones->add($timezone);
            $timezone->setCountry($this);
        }

        return $this;
    }

    /**
     * @param Timezone $timezone
     * @return $this
     */
    public function removeTimezone(Timezone $timezone): static
    {
        if ($this->timezones->removeElement($timezone)) {
            // set the owning side to null (unless already changed)
            if ($timezone->getCountry() === $this) {
                $timezone->setCountry(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Import>
     */
    public function getImports(): Collection
    {
        return $this->imports;
    }

    public function addImport(Import $import): static
    {
        if (!$this->imports->contains($import)) {
            $this->imports->add($import);
            $import->setCountry($this);
        }

        return $this;
    }

    public function removeImport(Import $import): static
    {
        if ($this->imports->removeElement($import)) {
            // set the owning side to null (unless already changed)
            if ($import->getCountry() === $this) {
                $import->setCountry(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ZipCode>
     */
    public function getZipCodes(): Collection
    {
        return $this->zipCodes;
    }

    public function addZipCode(ZipCode $zipCode): static
    {
        if (!$this->zipCodes->contains($zipCode)) {
            $this->zipCodes->add($zipCode);
            $zipCode->setCountry($this);
        }

        return $this;
    }

    public function removeZipCode(ZipCode $zipCode): static
    {
        if ($this->zipCodes->removeElement($zipCode)) {
            // set the owning side to null (unless already changed)
            if ($zipCode->getCountry() === $this) {
                $zipCode->setCountry(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ZipCodeArea>
     */
    public function getZipCodeAreas(): Collection
    {
        return $this->zipCodeAreas;
    }

    public function addZipCodeArea(ZipCodeArea $zipCodeArea): static
    {
        if (!$this->zipCodeAreas->contains($zipCodeArea)) {
            $this->zipCodeAreas->add($zipCodeArea);
            $zipCodeArea->setCountry($this);
        }

        return $this;
    }

    public function removeZipCodeArea(ZipCodeArea $zipCodeArea): static
    {
        if ($this->zipCodeAreas->removeElement($zipCodeArea)) {
            // set the owning side to null (unless already changed)
            if ($zipCodeArea->getCountry() === $this) {
                $zipCodeArea->setCountry(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, River>
     */
    public function getRivers(): Collection
    {
        return $this->rivers;
    }

    public function addRiver(River $river): static
    {
        if (!$this->rivers->contains($river)) {
            $this->rivers->add($river);
            $river->setCountry($this);
        }

        return $this;
    }

    public function removeRiver(River $river): static
    {
        if ($this->rivers->removeElement($river)) {
            // set the owning side to null (unless already changed)
            if ($river->getCountry() === $this) {
                $river->setCountry(null);
            }
        }

        return $this;
    }
}
