<?php

namespace JeanSebastienChristophe\CalendarBundle\Trait;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait CalendarEventTrait
{
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: 'La date de début est obligatoire')]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: 'La date de fin est obligatoire')]
    #[Assert\Expression(
        "this.getEndDate() >= this.getStartDate()",
        message: 'La date de fin doit être après la date de début'
    )]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column]
    private bool $allDay = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 7, nullable: true)]
    #[Assert\Regex(
        pattern: '/^#[0-9A-Fa-f]{6}$/',
        message: 'La couleur doit être au format hexadécimal (#RRGGBB)'
    )]
    private ?string $color = '#3788d8';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $excludedDates = [];

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function isAllDay(): bool
    {
        return $this->allDay;
    }

    public function setAllDay(bool $allDay): static
    {
        $this->allDay = $allDay;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getExcludedDates(): array
    {
        return $this->excludedDates ?? [];
    }

    public function setExcludedDates(?array $excludedDates): static
    {
        $this->excludedDates = $excludedDates;
        return $this;
    }

    public function isExcludedDate(\DateTimeInterface $date): bool
    {
        $dateString = $date->format('Y-m-d');
        return in_array($dateString, $this->getExcludedDates(), true);
    }

    public function excludeDate(\DateTimeInterface $date): static
    {
        $dateString = $date->format('Y-m-d');
        $excluded = $this->getExcludedDates();

        if (!in_array($dateString, $excluded, true)) {
            $excluded[] = $dateString;
            $this->excludedDates = $excluded;
        }

        return $this;
    }

    public function includeDate(\DateTimeInterface $date): static
    {
        $dateString = $date->format('Y-m-d');
        $excluded = $this->getExcludedDates();

        $this->excludedDates = array_values(array_filter($excluded, function($d) use ($dateString) {
            return $d !== $dateString;
        }));

        return $this;
    }
}
