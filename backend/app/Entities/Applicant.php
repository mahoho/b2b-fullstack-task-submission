<?php

namespace App\Entities;

use App\Enums\ApplicantStatus;
use App\Repositories\ApplicantRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Illuminate\Support\Str;

#[ORM\Entity(repositoryClass: ApplicantRepository::class)]
#[ORM\Table(name: 'applicants')]
#[ORM\Index(name: 'applicants_campaign_score_idx', columns: ['campaign_id', 'ai_score'])]
class Applicant
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(length: 20, enumType: ApplicantStatus::class)]
    private ApplicantStatus $status = ApplicantStatus::Pending;

    /**
     * @param  list<string>  $highlights
     */
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Campaign::class)]
        #[ORM\JoinColumn(nullable: false)]
        private Campaign $campaign,
        #[ORM\Column]
        private string $fullName,
        #[ORM\Column]
        private string $email,
        #[ORM\Column(type: 'smallint', nullable: true)]
        private ?int $aiScore,
        #[ORM\Column(type: 'json')]
        private array $highlights,
        #[ORM\Column(type: 'datetime_immutable')]
        private DateTimeImmutable $appliedAt,
    ) {
        $this->id = (string) Str::uuid();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCampaign(): Campaign
    {
        return $this->campaign;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getAiScore(): ?int
    {
        return $this->aiScore;
    }

    /**
     * @return list<string>
     */
    public function getHighlights(): array
    {
        return $this->highlights;
    }

    public function getStatus(): ApplicantStatus
    {
        return $this->status;
    }

    public function setStatus(ApplicantStatus $status): void
    {
        $this->status = $status;
    }

    public function getAppliedAt(): DateTimeImmutable
    {
        return $this->appliedAt;
    }
}
