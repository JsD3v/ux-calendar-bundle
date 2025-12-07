# Utilisation Avancée - CalendarBundle

## Créer des événements programmatiquement

### Dans un contrôleur

```php
<?php

namespace App\Controller;

use JeanSebastienChristophe\CalendarBundle\Entity\Event;
use JeanSebastienChristophe\CalendarBundle\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MyController extends AbstractController
{
    #[Route('/demo/create-event')]
    public function createEvent(EventRepository $eventRepository): Response
    {
        $event = new Event();
        $event->setTitle('Réunion importante')
            ->setStartDate(new \DateTime('2024-11-20 14:00:00'))
            ->setEndDate(new \DateTime('2024-11-20 15:30:00'))
            ->setAllDay(false)
            ->setDescription('Discussion sur le projet Q4')
            ->setColor('#ff6b6b');
        
        $eventRepository->save($event);
        
        return $this->redirectToRoute('calendar_index');
    }
}
```

### Dans un service

```php
<?php

namespace App\Service;

use JeanSebastienChristophe\CalendarBundle\Entity\Event;
use JeanSebastienChristophe\CalendarBundle\Repository\EventRepository;

class EventManager
{
    public function __construct(
        private readonly EventRepository $eventRepository
    ) {
    }
    
    public function createWeeklyMeeting(): Event
    {
        $event = new Event();
        $event->setTitle('Stand-up hebdomadaire')
            ->setStartDate(new \DateTime('next monday 09:00'))
            ->setEndDate(new \DateTime('next monday 09:30'))
            ->setColor('#3788d8');
        
        $this->eventRepository->save($event);
        
        return $event;
    }
    
    public function createAllDayEvent(string $title, \DateTimeInterface $date): Event
    {
        $start = (clone $date)->setTime(0, 0, 0);
        $end = (clone $date)->setTime(23, 59, 59);
        
        $event = new Event();
        $event->setTitle($title)
            ->setStartDate($start)
            ->setEndDate($end)
            ->setAllDay(true);
        
        $this->eventRepository->save($event);
        
        return $event;
    }
}
```

## Requêtes personnalisées

### Récupérer les événements du mois

```php
use JeanSebastienChristophe\CalendarBundle\Repository\EventRepository;

class MyService
{
    public function __construct(
        private readonly EventRepository $eventRepository
    ) {
    }
    
    public function getEventsForCurrentMonth(): array
    {
        $now = new \DateTime();
        $year = (int) $now->format('Y');
        $month = (int) $now->format('m');
        
        return $this->eventRepository->findByMonth($year, $month);
    }
}
```

### Récupérer les événements d'un jour

```php
$today = new \DateTime();
$events = $eventRepository->findByDay($today);
```

### Requête personnalisée

```php
// Dans un repository custom qui étend EventRepository

public function findUpcomingEvents(int $limit = 10): array
{
    return $this->createQueryBuilder('e')
        ->where('e.startDate >= :now')
        ->setParameter('now', new \DateTime())
        ->orderBy('e.startDate', 'ASC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}

public function findEventsByColor(string $color): array
{
    return $this->createQueryBuilder('e')
        ->where('e.color = :color')
        ->setParameter('color', $color)
        ->orderBy('e.startDate', 'ASC')
        ->getQuery()
        ->getResult();
}
```

## Personnaliser l'entité Event

### Étendre l'entité

```php
<?php

namespace App\Entity;

use JeanSebastienChristophe\CalendarBundle\Entity\Event as BaseEvent;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CustomEvent extends BaseEvent
{
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $location = null;
    
    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $organizer = null;
    
    public function getLocation(): ?string
    {
        return $this->location;
    }
    
    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }
    
    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }
    
    public function setOrganizer(?User $organizer): self
    {
        $this->organizer = $organizer;
        return $this;
    }
}
```

### Utiliser votre entité custom

Vous devrez ensuite surcharger le contrôleur et les formulaires pour utiliser votre entité personnalisée.

## Événements Symfony (Event Dispatcher)

### Écouter la création d'événements

```php
<?php

namespace App\EventListener;

use JeanSebastienChristophe\CalendarBundle\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postPersist, entity: Event::class)]
class EventCreatedListener
{
    public function postPersist(Event $event): void
    {
        // Envoyer une notification, logger, etc.
        // $this->mailer->send(...);
    }
}
```

## Intégration avec d'autres bundles

### Avec EasyAdmin

```php
<?php

namespace App\Controller\Admin;

use JeanSebastienChristophe\CalendarBundle\Entity\Event;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class EventCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Event::class;
    }
}
```

### Avec API Platform

```php
<?php

namespace App\Entity;

use JeanSebastienChristophe\CalendarBundle\Entity\Event as BaseEvent;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource]
class Event extends BaseEvent
{
    // L'entité est maintenant exposée via API Platform
}
```

## Templates personnalisés

### Surcharger le template principal

Créez `templates/bundles/CalendarBundle/calendar/index.html.twig` :

```twig
{% extends '@Calendar/calendar/base.html.twig' %}

{% block calendar_body %}
    <div class="my-custom-header">
        <h1>Mon Calendrier Personnalisé</h1>
    </div>
    
    {{ parent() }}
{% endblock %}
```

### Surcharger le template d'événement

Créez `templates/bundles/CalendarBundle/calendar/_event.html.twig` :

```twig
<div class="my-custom-event" 
     id="event-{{ event.id }}"
     style="border-left: 5px solid {{ event.color }};">
    <strong>{{ event.title }}</strong>
    <small>{{ event.startDate|date('H:i') }}</small>
</div>
```

## Utilisation avec Twig

### Afficher les événements dans une page custom

```twig
{# templates/my_page.html.twig #}

{% set events = event_repository.findByMonth(2024, 11) %}

<h2>Événements de novembre 2024</h2>
<ul>
    {% for event in events %}
        <li>
            <strong>{{ event.title }}</strong> - 
            {{ event.startDate|date('d/m/Y H:i') }}
        </li>
    {% endfor %}
</ul>
```

### Créer un widget "Prochains événements"

```twig
{# templates/widget/upcoming_events.html.twig #}

{% set upcomingEvents = event_repository
    .createQueryBuilder('e')
    .where('e.startDate >= :now')
    .setParameter('now', 'now'|date('Y-m-d H:i:s'))
    .orderBy('e.startDate', 'ASC')
    .setMaxResults(5)
    .getQuery()
    .getResult() 
%}

<div class="upcoming-events">
    <h3>Prochains événements</h3>
    {% for event in upcomingEvents %}
        <div class="event-item">
            <div style="background: {{ event.color }}; width: 4px; height: 100%;"></div>
            <div>
                <strong>{{ event.title }}</strong>
                <p>{{ event.startDate|date('d/m/Y à H:i') }}</p>
            </div>
        </div>
    {% endfor %}
</div>
```

## Export de données

### Export iCal (à venir en V2)

Pour l'instant, vous pouvez créer votre propre export :

```php
public function exportIcal(Event $event): string
{
    $ical = "BEGIN:VCALENDAR\r\n";
    $ical .= "VERSION:2.0\r\n";
    $ical .= "PRODID:-//CalendarBundle//NONSGML v1.0//EN\r\n";
    $ical .= "BEGIN:VEVENT\r\n";
    $ical .= "UID:" . $event->getId() . "@yourdomain.com\r\n";
    $ical .= "DTSTAMP:" . $event->getCreatedAt()->format('Ymd\THis\Z') . "\r\n";
    $ical .= "DTSTART:" . $event->getStartDate()->format('Ymd\THis\Z') . "\r\n";
    $ical .= "DTEND:" . $event->getEndDate()->format('Ymd\THis\Z') . "\r\n";
    $ical .= "SUMMARY:" . $event->getTitle() . "\r\n";
    
    if ($event->getDescription()) {
        $ical .= "DESCRIPTION:" . $event->getDescription() . "\r\n";
    }
    
    $ical .= "END:VEVENT\r\n";
    $ical .= "END:VCALENDAR\r\n";
    
    return $ical;
}
```

## Sécurité

### Restreindre l'accès au calendrier

```php
// Dans votre contrôleur custom

use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/events')]
#[IsGranted('ROLE_USER')]
class CalendarController extends AbstractController
{
    // Seuls les utilisateurs connectés peuvent accéder
}
```

### Filtrer les événements par utilisateur

```php
// Dans le repository

public function findUserEvents(User $user): array
{
    return $this->createQueryBuilder('e')
        ->where('e.user = :user')
        ->setParameter('user', $user)
        ->getQuery()
        ->getResult();
}
```

## Performance

### Cache des événements

```php
use Symfony\Contracts\Cache\CacheInterface;

public function getCachedEvents(int $year, int $month, CacheInterface $cache): array
{
    return $cache->get(
        "calendar_events_{$year}_{$month}",
        function () use ($year, $month) {
            return $this->eventRepository->findByMonth($year, $month);
        }
    );
}
```

## Tests

### Tester la création d'événements

```php
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use JeanSebastienChristophe\CalendarBundle\Entity\Event;

class EventTest extends KernelTestCase
{
    public function testCreateEvent(): void
    {
        $event = new Event();
        $event->setTitle('Test Event')
            ->setStartDate(new \DateTime())
            ->setEndDate(new \DateTime('+1 hour'));
        
        $this->assertNotNull($event->getTitle());
        $this->assertInstanceOf(\DateTimeInterface::class, $event->getStartDate());
    }
}
```

---

Pour plus d'exemples, consultez la [documentation complète](../README.md).
