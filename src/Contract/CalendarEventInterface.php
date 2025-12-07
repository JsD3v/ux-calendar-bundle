<?php

namespace JeanSebastienChristophe\CalendarBundle\Contract;

interface CalendarEventInterface
{
    public function getId(): ?int;

    public function getTitle(): ?string;
    public function setTitle(string $title): static;

    public function getStartDate(): ?\DateTimeInterface;
    public function setStartDate(\DateTimeInterface $startDate): static;

    public function getEndDate(): ?\DateTimeInterface;
    public function setEndDate(\DateTimeInterface $endDate): static;

    public function isAllDay(): bool;
    public function setAllDay(bool $allDay): static;

    public function getDescription(): ?string;
    public function setDescription(?string $description): static;

    public function getColor(): ?string;
    public function setColor(?string $color): static;

    public function getCreatedAt(): \DateTimeInterface;
    public function getUpdatedAt(): \DateTimeInterface;

    public function getExcludedDates(): array;
    public function setExcludedDates(?array $excludedDates): static;
    public function isExcludedDate(\DateTimeInterface $date): bool;
    public function excludeDate(\DateTimeInterface $date): static;
    public function includeDate(\DateTimeInterface $date): static;
}
