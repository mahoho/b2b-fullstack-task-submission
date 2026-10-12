<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use Illuminate\Support\Str;

#[ORM\Entity]
#[ORM\Table(name: 'campaigns')]
class Campaign
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Partner::class)]
        #[ORM\JoinColumn(nullable: false)]
        private Partner $partner,
        #[ORM\Column]
        private string $name,
        #[ORM\Column(type: 'smallint', options: ['default' => 70])]
        private int $eligibilityThreshold = 70,
        ?string $id = null,
    ) {
        $this->id = $id ?? (string) Str::uuid();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPartner(): Partner
    {
        return $this->partner;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEligibilityThreshold(): int
    {
        return $this->eligibilityThreshold;
    }
}
