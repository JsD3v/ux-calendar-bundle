# 🔗 EasyAdmin Integration

This guide shows how to integrate the CalendarBundle with EasyAdmin for a complete admin interface.

## ⚠️ Important Note

**The CalendarBundle provides helper classes for EasyAdmin integration**, but they are **NOT automatically activated**. You need to manually wire them in your own application by:
1. Creating your own `DashboardController` (in your app)
2. Referencing the provided `EventCrudController` from the bundle
3. Optionally using the `CalendarDashboardWidget` for statistics

The bundle **provides** these classes, but **you control** when and how to use them.

## 📋 Table of Contents

- [Quick Setup](#quick-setup)
- [Dashboard Configuration](#dashboard-configuration)
- [Custom Actions](#custom-actions)
- [Widget Integration](#widget-integration)
- [Turbo Modal Integration](#turbo-modal-integration)
- [Custom Entity](#custom-entity)

---

## 🚀 Quick Setup

**The bundle is NOT wired to EasyAdmin by default.** You need to set it up manually:

### 1. Install EasyAdmin (if not already installed)

```bash
composer require easycorp/easyadmin-bundle
```

### 2. Create Your Dashboard Controller (in YOUR app)

Create this file in **your application** (not in the bundle):

```php
<?php
// src/Controller/Admin/DashboardController.php
// ⚠️ This file goes in YOUR application, not in the bundle

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use JeanSebastienChristophe\CalendarBundle\Entity\Event;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Mon Application');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        // Add Calendar section
        yield MenuItem::section('Calendrier');
        yield MenuItem::linkToCrud('Événements', 'fa fa-calendar', Event::class);
        yield MenuItem::linkToRoute('Vue Calendrier', 'fa fa-calendar-alt', 'calendar_index')
            ->setLinkTarget('_blank');
    }
}
```

### 3. Reference the Provided CRUD Controller

The bundle **provides** a ready-to-use `EventCrudController`. Reference it in your Dashboard:

```php
use JeanSebastienChristophe\CalendarBundle\Admin\EventCrudController;
use JeanSebastienChristophe\CalendarBundle\Entity\Event;

// In configureMenuItems()
yield MenuItem::linkToCrud('Événements', 'fa fa-calendar', Event::class)
    ->setController(EventCrudController::class); // ← References the bundle's controller
```

**What the bundle provides:**
- ✅ `EventCrudController` - Complete CRUD with actions ("View in calendar", optional Turbo modal)
- ✅ `CalendarDashboardWidget` - Statistics and upcoming/recent events
- ✅ Helper fields configuration (title, dates, color, etc.)

**What you need to create:**
- ❌ Your own `DashboardController` (in `src/Controller/Admin/`)
- ❌ Your own dashboard template (optional, for statistics)
- ❌ Menu configuration pointing to the bundle's classes

---

## 📊 Dashboard Configuration

### Add Calendar Widget to Dashboard

Create a custom dashboard template with calendar statistics:

```twig
{# templates/admin/dashboard.html.twig #}
{% extends '@EasyAdmin/page/content.html.twig' %}

{% block content_title %}
    <h1>Tableau de bord</h1>
{% endblock %}

{% block main %}
    <div class="row">
        {# Calendar Statistics #}
        <div class="col-md-3">
            <div class="card bg-primary text-white mb-3">
                <div class="card-body">
                    <h5 class="card-title">Événements aujourd'hui</h5>
                    <p class="card-text display-4">{{ calendar_widget.statistics.events_today }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-success text-white mb-3">
                <div class="card-body">
                    <h5 class="card-title">Événements ce mois</h5>
                    <p class="card-text display-4">{{ calendar_widget.statistics.events_this_month }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-info text-white mb-3">
                <div class="card-body">
                    <h5 class="card-title">À venir</h5>
                    <p class="card-text display-4">{{ calendar_widget.statistics.upcoming_events }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-secondary text-white mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total</h5>
                    <p class="card-text display-4">{{ calendar_widget.statistics.total_events }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {# Upcoming Events #}
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Événements à venir</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        {% for event in calendar_widget.upcomingEvents(5) %}
                            <a href="{{ path('calendar_event_edit', {id: event.id}) }}"
                               class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">{{ event.title }}</h6>
                                    <small>{{ event.startDate|date('d/m/Y H:i') }}</small>
                                </div>
                                {% if event.description %}
                                    <p class="mb-1 text-muted">{{ event.description|slice(0, 100) }}...</p>
                                {% endif %}
                            </a>
                        {% else %}
                            <div class="list-group-item">Aucun événement à venir</div>
                        {% endfor %}
                    </div>
                </div>
            </div>
        </div>

        {# Recent Events #}
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Événements récents</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        {% for event in calendar_widget.recentEvents(5) %}
                            <a href="{{ path('calendar_event_edit', {id: event.id}) }}"
                               class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">{{ event.title }}</h6>
                                    <small>{{ event.createdAt|date('d/m/Y') }}</small>
                                </div>
                            </a>
                        {% else %}
                            <div class="list-group-item">Aucun événement</div>
                        {% endfor %}
                    </div>
                </div>
            </div>
        </div>
    </div>
{% endblock %}
```

Update your DashboardController to inject the widget:

```php
use JeanSebastienChristophe\CalendarBundle\Admin\CalendarDashboardWidget;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly CalendarDashboardWidget $calendarWidget
    ) {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'calendar_widget' => $this->calendarWidget,
        ]);
    }
}
```

---

## 🎯 Custom Actions

The provided `EventCrudController` already includes a **"Voir dans le calendrier"** action that:
- Opens the calendar view at the event's month
- Opens in a new tab
- Available on Index, Detail, and Edit pages

### Enable Turbo Modal Editing (Advanced)

Uncomment this line in `EventCrudController`:

```php
// ->add(Crud::PAGE_INDEX, $editInModal)
```

This adds a button to edit events in a Turbo modal without leaving the admin page.

---

## 🎨 Turbo Modal Integration

To use the calendar's Turbo modal inside EasyAdmin:

### 1. Add Turbo Frame to Your Admin Template

Create a custom base template:

```twig
{# templates/bundles/EasyAdminBundle/layout.html.twig #}
{% extends '@!EasyAdmin/layout.html.twig' %}

{% block body_javascript %}
    {{ parent() }}

    {# Include calendar Stimulus controller #}
    {{ importmap('app') }}

    {# Add Turbo Frame for calendar modal #}
    <turbo-frame id="event-modal"></turbo-frame>
{% endblock %}
```

### 2. Add CSS for Calendar in Admin

```twig
{% block configured_stylesheets %}
    {{ parent() }}
    {{ calendar_theme_css()|raw }}
{% endblock %}
```

### 3. Use the Modal Action

```php
// In EventCrudController
$editInModal = Action::new('editInModal', 'Modifier (modal)', 'fa fa-edit')
    ->linkToRoute('calendar_event_edit', function ($event): array {
        return ['id' => $event->getId()];
    })
    ->setHtmlAttributes([
        'data-turbo-frame' => 'event-modal',
        'title' => 'Modifier dans un modal Turbo'
    ]);
```

---

## 🔧 Custom Entity

To use your own custom entity with EasyAdmin:

### 1. Create Your Entity

```php
<?php
// src/Entity/MyCustomEvent.php

namespace App\Entity;

use JeanSebastienChristophe\CalendarBundle\Contract\CalendarEventInterface;
use JeanSebastienChristophe\CalendarBundle\Trait\CalendarEventTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class MyCustomEvent implements CalendarEventInterface
{
    use CalendarEventTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Your custom fields
    #[ORM\Column(nullable: true)]
    private ?string $location = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $organizer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    // Getters and setters for custom fields...
}
```

### 2. Create Custom CRUD Controller

```php
<?php
// src/Controller/Admin/MyCustomEventCrudController.php

namespace App\Controller\Admin;

use App\Entity\MyCustomEvent;
use JeanSebastienChristophe\CalendarBundle\Admin\EventCrudController as BaseEventCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class MyCustomEventCrudController extends BaseEventCrudController
{
    public static function getEntityFqcn(): string
    {
        return MyCustomEvent::class;
    }

    public function configureFields(string $pageName): iterable
    {
        // Get base fields
        yield from parent::configureFields($pageName);

        // Add your custom fields
        yield TextField::new('location')
            ->setLabel('Lieu')
            ->setRequired(false);

        yield AssociationField::new('organizer')
            ->setLabel('Organisateur')
            ->setRequired(false);
    }
}
```

### 3. Update Dashboard

```php
yield MenuItem::linkToCrud('Mes Événements', 'fa fa-calendar', MyCustomEvent::class)
    ->setController(MyCustomEventCrudController::class);
```

### 4. Configure the Bundle

```yaml
# config/packages/calendar.yaml
calendar:
    event_class: App\Entity\MyCustomEvent
```

---

## 📈 Advanced Features

### 1. Add Charts (requires symfony/ux-chartjs)

```bash
composer require symfony/ux-chartjs
```

In your dashboard template:

```twig
{% set chart = calendar_widget.eventsPerMonthChart() %}
{% if chart %}
    <div class="card mb-3">
        <div class="card-header">
            <h5>Événements par mois</h5>
        </div>
        <div class="card-body">
            {{ render_chart(chart) }}
        </div>
    </div>
{% endif %}
```

### 2. Custom Filters

Add more filters in your CRUD controller:

```php
public function configureFilters(Filters $filters): Filters
{
    return parent::configureFilters($filters)
        ->add(EntityFilter::new('organizer', 'Organisateur'))
        ->add(TextFilter::new('location', 'Lieu'));
}
```

### 3. Batch Actions

```php
public function configureBatchActions(BatchActions $batchActions): BatchActions
{
    return $batchActions
        ->add(BatchAction::new('exportICS', 'Exporter en ICS')
            ->linkToRoute('calendar_export_ics')
            ->addCssClass('btn btn-primary'));
}
```

---

## 🔍 Troubleshooting

### Calendar Modal Not Working in EasyAdmin

Make sure:
1. Turbo is loaded: `{{ importmap('app') }}`
2. Turbo frame exists: `<turbo-frame id="event-modal"></turbo-frame>`
3. Calendar CSS is loaded: `{{ calendar_theme_css()|raw }}`

### Custom Entity Not Showing in Calendar

Verify:
1. Entity implements `CalendarEventInterface`
2. Configuration updated: `calendar.event_class`
3. Cache cleared: `php bin/console cache:clear`

---

## 🎉 Complete Example

See the full working examples in `docs/examples/`:
- **DashboardController.php** - Example dashboard with calendar widget
- **dashboard.html.twig** - Beautiful dashboard template with statistics

Copy these files to your application and customize them!

---

## 📝 Summary

**To integrate CalendarBundle with EasyAdmin:**

1. ✅ **Install EasyAdmin**: `composer require easycorp/easyadmin-bundle`

2. ✅ **Create YOUR DashboardController** (in `src/Controller/Admin/`)
   ```php
   namespace App\Controller\Admin;
   // Your own dashboard controller
   ```

3. ✅ **Reference the bundle's EventCrudController**
   ```php
   use JeanSebastienChristophe\CalendarBundle\Admin\EventCrudController;

   yield MenuItem::linkToCrud('Événements', 'fa fa-calendar', Event::class)
       ->setController(EventCrudController::class);
   ```

4. ✅ **Optionally use CalendarDashboardWidget** for statistics
   ```php
   public function __construct(
       private readonly CalendarDashboardWidget $widget
   ) {}
   ```

**Remember**: The bundle provides helper classes, but YOU wire them in YOUR application. It's not automatic!

---

## 📚 Resources

- [EasyAdmin Documentation](https://symfony.com/bundles/EasyAdminBundle/current/index.html)
- [CalendarBundle Documentation](../README.md)
- [Turbo Documentation](https://turbo.hotwired.dev/)

---

**Need help?** Open an issue on [GitHub](https://github.com/JsD3v/calendar-bundle/issues).
