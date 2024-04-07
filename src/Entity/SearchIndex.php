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

use App\DBAL\GeoLocation\Types\PostgreSQL\TsVectorType;
use App\DBAL\GeoLocation\ValueObject\TsVector;
use App\Entity\Trait\TimestampsTrait;
use App\Repository\SearchIndexRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class SearchIndex
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-04-07)
 * @since 0.1.0 (2024-04-07) First version.
 */
#[ORM\Entity(repositoryClass: SearchIndexRepository::class)]
#[ORM\Index(columns: ['search_text_simple'], flags: ['gin'])]
#[ORM\Index(columns: ['search_text_de'], flags: ['gin'])]
#[ORM\Index(columns: ['search_text_en'], flags: ['gin'])]
#[ORM\Index(columns: ['search_text_es'], flags: ['gin'])]
#[ORM\Index(columns: ['relevance_score'])]
#[ORM\HasLifecycleCallbacks]
class SearchIndex
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: TsVectorType::TS_VECTOR, nullable: true)]
    private ?TsVector $searchTextSimple = null;

    #[ORM\Column(type: TsVectorType::TS_VECTOR, nullable: true)]
    private ?TsVector $searchTextDe = null;

    #[ORM\Column(type: TsVectorType::TS_VECTOR, nullable: true)]
    private ?TsVector $searchTextEn = null;

    #[ORM\Column(type: TsVectorType::TS_VECTOR, nullable: true)]
    private ?string $searchTextEs = null;

    #[ORM\Column(nullable: false)]
    private ?int $relevanceScore = null;

    #[ORM\ManyToOne(inversedBy: 'searchIndices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Location $location = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return TsVector|null
     */
    public function getSearchTextSimple(): ?TsVector
    {
        return $this->searchTextSimple;
    }

    /**
     * @param TsVector|null $searchTextSimple
     * @return SearchIndex
     */
    public function setSearchTextSimple(?TsVector $searchTextSimple): SearchIndex
    {
        $this->searchTextSimple = $searchTextSimple;
        return $this;
    }

    /**
     * @return TsVector|null
     */
    public function getSearchTextDe(): ?TsVector
    {
        return $this->searchTextDe;
    }

    /**
     * @param TsVector|null $searchTextDe
     * @return SearchIndex
     */
    public function setSearchTextDe(?TsVector $searchTextDe): SearchIndex
    {
        $this->searchTextDe = $searchTextDe;
        return $this;
    }

    /**
     * @return TsVector|null
     */
    public function getSearchTextEn(): ?TsVector
    {
        return $this->searchTextEn;
    }

    /**
     * @param TsVector|null $searchTextEn
     * @return SearchIndex
     */
    public function setSearchTextEn(?TsVector $searchTextEn): SearchIndex
    {
        $this->searchTextEn = $searchTextEn;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSearchTextEs(): ?string
    {
        return $this->searchTextEs;
    }

    /**
     * @param string|null $searchTextEs
     * @return SearchIndex
     */
    public function setSearchTextEs(?string $searchTextEs): SearchIndex
    {
        $this->searchTextEs = $searchTextEs;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getRelevanceScore(): ?int
    {
        return $this->relevanceScore;
    }

    /**
     * @param int|null $relevanceScore
     * @return SearchIndex
     */
    public function setRelevanceScore(?int $relevanceScore): SearchIndex
    {
        $this->relevanceScore = $relevanceScore;
        return $this;
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
}
