<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use Illuminate\Support\Str;

#[ORM\Entity]
#[ORM\Table(name: 'partners')]
class Partner
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    public function __construct(
        #[ORM\Column]
        private string $name,
        #[ORM\Column(length: 64, unique: true)]
        private string $apiTokenHash,
    ) {
        $this->id = (string) Str::uuid();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
