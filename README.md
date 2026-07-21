# CalendarBundle for Symfony 8

[![Tests](https://github.com/JsD3v/ux-calendar-bundle/actions/workflows/test.yml/badge.svg)](https://github.com/JsD3v/ux-calendar-bundle/actions/workflows/test.yml)
[![PHPStan](https://github.com/JsD3v/ux-calendar-bundle/actions/workflows/phpstan.yml/badge.svg)](https://github.com/JsD3v/ux-calendar-bundle/actions/workflows/phpstan.yml)

A lightweight calendar bundle for Symfony 8, built on Turbo, Stimulus and AssetMapper. It provides month, week and day views, event management forms and EasyAdmin helpers, without a heavy JavaScript dependency such as FullCalendar. No third-party CDNs are loaded by default.

## Compatibility

- PHP >= 8.4
- Symfony FrameworkBundle, Form, Validator, TwigBundle, Console, Translation and AssetMapper `^8.0`
- Symfony UX Turbo and Stimulus Bundle `^2.0|^3.0`
- Doctrine ORM `^2.0|^3.0` and DoctrineBundle `^2.0|^3.0`
- EasyAdmin `^4.0|^5.0`, optional, for the admin panel
- Symfony UX ChartJS `^2.0|^3.0`, optional, for the dashboard charts

## Features

- Month, week and day views with a built-in switcher and Turbo Streams updates
- Week and day views rendered as an hourly grid (0:00–23:00 slots), plus an "all-day" row
- Create, edit, delete and one-off date exclusion
- Ready-to-use `Event` entity
- `CalendarEventInterface` and `CalendarEventRepositoryInterface` contracts for custom entities
- `CalendarEventTrait` to reuse the common Doctrine mapping
- Bootstrap theme by default, with `default` and `tailwind` variants and optional automatic detection
- Optional EasyAdmin CRUD, calendar field and dashboard widget

## Installation

```bash
composer require jean-sebastien-christophe/ux-calendar-bundle
```

Register the bundle in `config/bundles.php`:

```php
JeanSebastienChristophe\CalendarBundle\CalendarBundle::class => ['all' => true],
```

Declare the routes in `config/routes/calendar.yaml`:

```yaml
calendar_bundle:
    resource: '@CalendarBundle/src/Controller/'
    type: attribute
```

The default route is `/events`. To use `/calendar` instead, create `config/packages/calendar.yaml`:

```yaml
calendar:
    theme: bootstrap
    assets:
        include_cdn: false
    route_prefix: /calendar
    views:
        enabled: [month, week, day]
        default: month
    features:
        all_day_events: true
        colors: true
```

The bundle registers the Doctrine mapping for its own `Event` entity (table `calendar_events`), so it is picked up by the schema tools out of the box. Create and apply the migration:

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
php bin/console cache:clear
```

The CSS assets are exposed through AssetMapper. No `assets:install` command is required.

The default theme is `bootstrap`, to stay consistent with EasyAdmin and the classes used by the templates. The `bootstrap.css` theme only maps the `--bs-*` variables: **Bootstrap itself must therefore be loaded**, otherwise the classes (`btn`, `container`, `alert`, …) used in the templates are left unstyled. There are two ways to provide it:

1. **Through your application's AssetMapper (recommended).** The calendar's standalone pages automatically render `importmap('app')` (see the Stimulus section). If your `importmap.php` imports Bootstrap (for example `import 'bootstrap/dist/css/bootstrap.min.css'` in `assets/app.js`), it is loaded on `/events` with nothing else to do.

2. **Through the Bootstrap CDN**, useful for a standalone rendering when the application does not embed Bootstrap:

```yaml
calendar:
    theme: bootstrap
    assets:
        include_cdn: true
```

The `tailwind`, `default` and `auto` themes remain available through `calendar.theme`.

## Stimulus

The Stimulus controller is exposed as a Symfony UX controller. Enable it in `assets/controllers.json`:

```json
{
    "controllers": {
        "@jean-sebastien-christophe/ux-calendar-bundle": {
            "calendar": {
                "enabled": true,
                "fetch": "eager"
            }
        }
    }
}
```

Your application must start StimulusBundle, for example in `assets/bootstrap.js`:

```javascript
import { startStimulusApp } from '@symfony/stimulus-bundle';

startStimulusApp();
```

The calendar's standalone pages (the `@Calendar/calendar/base.html.twig` layout) **automatically** render the `importmap('app')` entrypoint. This is what loads, on `/events`, both the `calendar` Stimulus controller and your application's assets (including Bootstrap if it is in your `importmap.php`). Your application must therefore expose an entrypoint named `app` (the Symfony default).

If your entrypoint has a different name, override the `importmap` block by creating `templates/bundles/CalendarBundle/calendar/base.html.twig`:

```twig
{% extends '@Calendar/calendar/base.html.twig' %}

{% block importmap %}
    {{ importmap('my_entrypoint') }}
{% endblock %}
```

To embed the calendar in your own layout (instead of the standalone page), override the same template so that it extends your application's layout:

```twig
{# templates/bundles/CalendarBundle/calendar/base.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    {{ calendar_theme_css()|raw }}
    {% block calendar_body %}{% endblock %}
{% endblock %}
```

Then open `/events`, or `/calendar` if you configured `route_prefix: /calendar`.

## Exposed routes

`{prefix}` defaults to `/events`.

| Method | Route | Name | Description |
|--------|-------|------|-------------|
| GET | `{prefix}` | `calendar_index` | Redirects to the default view (`views.default`) |
| GET | `{prefix}/{year}/{month}` | `calendar_month` | Renders the monthly calendar |
| GET | `{prefix}/week/{date}` | `calendar_week` | Renders the week containing `{date}` (`Y-m-d`) |
| GET | `{prefix}/day/{date}` | `calendar_day` | Renders the `{date}` day (`Y-m-d`) |
| GET, POST | `{prefix}/new` | `calendar_event_new` | Renders the form and creates the event |
| GET, POST | `{prefix}/{id}/edit` | `calendar_event_edit` | Renders the form and updates the event |
| POST | `{prefix}/{id}/exclude/{date}` | `calendar_event_exclude_date` | Excludes a date for an event |
| POST, DELETE | `{prefix}/{id}` | `calendar_event_delete` | Deletes the event |

## Custom entity

The default entity is `JeanSebastienChristophe\CalendarBundle\Entity\Event`. To use your own entity, it must implement `CalendarEventInterface`. The `CalendarEventTrait` provides the common Doctrine mapping.

You can configure the entity with the install command:

```bash
php bin/console ux-calendar:install --event-class='App\Entity\MyEvent'
```

```php
<?php

namespace App\Entity;

use App\Repository\MyEventRepository;
use Doctrine\ORM\Mapping as ORM;
use JeanSebastienChristophe\CalendarBundle\Contract\CalendarEventInterface;
use JeanSebastienChristophe\CalendarBundle\Trait\CalendarEventTrait;

#[ORM\Entity(repositoryClass: MyEventRepository::class)]
#[ORM\HasLifecycleCallbacks]
class MyEvent implements CalendarEventInterface
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
```

The associated repository must implement `CalendarEventRepositoryInterface`, because the bundle's controller loads the monthly events through `findByMonth()`.

```php
<?php

namespace App\Repository;

use App\Entity\MyEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use JeanSebastienChristophe\CalendarBundle\Contract\CalendarEventRepositoryInterface;

/**
 * @extends ServiceEntityRepository<MyEvent>
 */
final class MyEventRepository extends ServiceEntityRepository implements CalendarEventRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MyEvent::class);
    }

    public function findByMonth(int $year, int $month): array
    {
        $start = new \DateTime(sprintf('%d-%02d-01 00:00:00', $year, $month));
        $end = (clone $start)->modify('last day of this month')->setTime(23, 59, 59);

        return $this->createQueryBuilder('e')
            ->where('e.startDate BETWEEN :start AND :end')
            ->orWhere('e.endDate BETWEEN :start AND :end')
            ->orWhere('e.startDate <= :start AND e.endDate >= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

### Week and day views (optional interface)

The week and day views work out of the box: the controller falls back to `findByMonth()` for the months covered. For a single query optimized over an arbitrary range, also implement `CalendarEventRangeRepositoryInterface`:

```php
use JeanSebastienChristophe\CalendarBundle\Contract\CalendarEventRangeRepositoryInterface;
use JeanSebastienChristophe\CalendarBundle\Contract\CalendarEventRepositoryInterface;

final class MyEventRepository extends ServiceEntityRepository implements
    CalendarEventRepositoryInterface,
    CalendarEventRangeRepositoryInterface
{
    // ... findByMonth() ...

    public function findByDateRange(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.startDate BETWEEN :start AND :end')
            ->orWhere('e.endDate BETWEEN :start AND :end')
            ->orWhere('e.startDate <= :start AND e.endDate >= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

Then configure the bundle:

```yaml
calendar:
    event_class: App\Entity\MyEvent
```

This value is used by the controllers, the argument resolver, `EventType` and `EventCrudController`. A `createForm(EventType::class, $event)` call therefore expects the configured entity, not the bundle's default entity.

As soon as `event_class` points somewhere else, the bundle stops registering the Doctrine mapping for its own `Event` entity: your schema only contains your table, with no leftover `calendar_events`. Mapping your entity is then up to your application, as usual.

## EasyAdmin

The EasyAdmin helpers are optional. Install EasyAdmin if needed:

```bash
composer require easycorp/easyadmin-bundle
```

Then reference the provided CRUD in your dashboard:

```php
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use JeanSebastienChristophe\CalendarBundle\Admin\EventCrudController;
use JeanSebastienChristophe\CalendarBundle\Entity\Event;

yield MenuItem::linkToCrud('Events', 'fa fa-calendar', Event::class)
    ->setController(EventCrudController::class);
```

`EventCrudController::getEntityFqcn()` follows `calendar.event_class`, so with a custom entity link the menu item to that class instead:

```php
yield MenuItem::linkToCrud('Events', 'fa fa-calendar', MyEvent::class)
    ->setController(EventCrudController::class);
```

See also:

- [EasyAdmin guide](docs/EASYADMIN.md)
- [Advanced EasyAdmin guide](docs/EASYADMIN_INTEGRATION.md)
- [Advanced usage guide](docs/USAGE.md)
- [SQL migration](docs/MIGRATION.md)

## Quality

The repository does not version `vendor/`. Install the dependencies with Composer:

```bash
composer install
```

Useful commands before a PR or a tag:

```bash
composer validate --strict
composer analyse
composer test
```

`composer analyse` runs PHPStan at level 5 with the Symfony and Doctrine extensions.

## Roadmap

- Drag and drop to move events
- Full recurring events
- iCal export
- Event categories
- REST API

## Contributing

1. Fork the project
2. Create a branch (`git checkout -b feature/amazing-feature`)
3. Install the dependencies (`composer install`)
4. Run `composer analyse` and `composer test`
5. Push the branch and open a Pull Request

## License

MIT

## Support

For any question or issue, open an issue on GitHub: <https://github.com/JsD3v/ux-calendar-bundle/issues>
