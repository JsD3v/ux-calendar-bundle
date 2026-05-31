<?php

namespace JeanSebastienChristophe\CalendarBundle\Tests\Fixtures;

use JeanSebastienChristophe\CalendarBundle\Contract\CalendarEventInterface;
use JeanSebastienChristophe\CalendarBundle\Trait\CalendarEventTrait;

class CustomEvent implements CalendarEventInterface
{
    use CalendarEventTrait;

    private ?int $id = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
