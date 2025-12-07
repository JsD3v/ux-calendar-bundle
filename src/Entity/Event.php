<?php

namespace JeanSebastienChristophe\CalendarBundle\Entity;

use JeanSebastienChristophe\CalendarBundle\Contract\CalendarEventInterface;
use JeanSebastienChristophe\CalendarBundle\Repository\EventRepository;
use JeanSebastienChristophe\CalendarBundle\Trait\CalendarEventTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'calendar_events')]
#[ORM\HasLifecycleCallbacks]
class Event implements CalendarEventInterface
{
    use CalendarEventTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
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
