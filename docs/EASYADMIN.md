# EasyAdmin Integration - CalendarBundle

Ce guide explique comment intégrer CalendarBundle avec EasyAdmin pour gérer vos événements depuis l'interface d'administration.

## 📦 Prérequis

```bash
composer require easycorp/easyadmin-bundle
```

## 🚀 Installation Rapide

### 1. Ajouter le CRUD Controller à votre Dashboard

Dans votre `DashboardController.php` :

```php
<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use JeanSebastienChristophe\CalendarBundle\Admin\EventCrudController;
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

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        // Ajouter le calendrier au menu
        yield MenuItem::section('Calendrier');
        yield MenuItem::linkToCrud('Événements', 'fa fa-calendar', Event::class)
            ->setController(EventCrudController::class);
        yield MenuItem::linkToRoute('Voir le calendrier', 'fa fa-calendar-alt', 'calendar_index')
            ->setLinkTarget('_blank');
    }
}
```

### 2. Utiliser le Widget de Dashboard

Créez un dashboard personnalisé avec le widget calendrier :

```php
<?php

namespace App\Controller\Admin;

use JeanSebastienChristophe\CalendarBundle\Admin\CalendarDashboardWidget;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly CalendarDashboardWidget $calendarWidget
    ) {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $now = new \DateTime();

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $this->calendarWidget->getStatistics(),
            'upcoming_events' => $this->calendarWidget->getUpcomingEvents(5),
            'calendar_data' => $this->calendarWidget->getMonthlyCalendarData(),
            'current_date' => $now,
            'chart' => $this->calendarWidget->getEventsPerMonthChart(),
        ]);
    }
}
```

Template `templates/admin/dashboard.html.twig` :

```twig
{% extends '@EasyAdmin/page/content.html.twig' %}

{% block page_content %}
<div class="row">
    <div class="col-lg-8">
        {% include '@Calendar/admin/dashboard/calendar_widget.html.twig' %}
    </div>

    <div class="col-lg-4">
        {# Autres widgets #}
    </div>
</div>
{% endblock %}
```

## 🎯 Fonctionnalités du CRUD Controller

Le `EventCrudController` inclut :

### Champs automatiques :
- **ID** - Caché dans le formulaire
- **Titre** - TextField avec validation
- **Date de début** - DateTimeField avec format français
- **Date de fin** - DateTimeField avec format français
- **Journée entière** - BooleanField avec switch
- **Description** - TextareaField (caché dans la liste)
- **Couleur** - ColorField avec picker
- **Créé le / Modifié le** - Automatique

### Actions personnalisées :
- **Voir dans le calendrier** - Ouvre l'événement dans le calendrier
- Filtres par titre, dates, journée entière
- Tri par date de début (décroissant)
- Recherche par titre et description

## 🎨 Utiliser le CalendarField personnalisé

Ajoutez un mini-calendrier dans n'importe quel CRUD :

```php
use JeanSebastienChristophe\CalendarBundle\Admin\Field\CalendarField;

public function configureFields(string $pageName): iterable
{
    // ... autres champs ...

    yield CalendarField::new('calendar', 'Calendrier')
        ->showNavigation(true)
        ->showTodayButton(true)
        ->setMaxEventsPerDay(3)
        ->setCalendarHeight('400px')
        ->setClickableEvents(true);
}
```

### Options disponibles :

| Option | Type | Par défaut | Description |
|--------|------|------------|-------------|
| `showNavigation` | bool | true | Afficher les boutons de navigation |
| `showTodayButton` | bool | true | Afficher le bouton "Aujourd'hui" |
| `defaultView` | string | 'month' | Vue par défaut |
| `maxEventsPerDay` | int | 3 | Nombre max d'événements affichés par jour |
| `clickableEvents` | bool | true | Événements cliquables |
| `calendarHeight` | string | '500px' | Hauteur du calendrier |

## 📊 Widget Dashboard

Le `CalendarDashboardWidget` fournit :

### Statistiques :
```php
$stats = $widget->getStatistics();
// [
//     'total_events' => 42,
//     'events_this_month' => 12,
//     'upcoming_events' => 8,
//     'events_today' => 2,
// ]
```

### Événements à venir :
```php
$upcoming = $widget->getUpcomingEvents(5);
// Liste des 5 prochains événements
```

### Grille calendrier :
```php
$grid = $widget->getMonthlyCalendarData(2024, 11);
// Données pour afficher le mini-calendrier
```

### Graphique (si ChartJS installé) :
```php
$chart = $widget->getEventsPerMonthChart();
// Graphique des événements par mois
```

## 🔧 Configuration avancée

### Étendre le CRUD Controller

```php
<?php

namespace App\Controller\Admin;

use JeanSebastienChristophe\CalendarBundle\Admin\EventCrudController as BaseController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class EventCrudController extends BaseController
{
    public function configureFields(string $pageName): iterable
    {
        // Récupérer les champs de base
        yield from parent::configureFields($pageName);

        // Ajouter vos propres champs
        yield AssociationField::new('user')
            ->setLabel('Organisateur');
    }
}
```

### Personnaliser les actions

```php
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;

public function configureActions(Actions $actions): Actions
{
    $actions = parent::configureActions($actions);

    // Ajouter une action personnalisée
    $duplicateEvent = Action::new('duplicate', 'Dupliquer', 'fa fa-copy')
        ->linkToRoute('app_event_duplicate', function (Event $event): array {
            return ['id' => $event->getId()];
        });

    $actions->add(Crud::PAGE_INDEX, $duplicateEvent);

    return $actions;
}
```

## 🎯 Bonnes pratiques

1. **Utilisez le CRUD intégré** pour la gestion basique des événements
2. **Personnalisez via l'extension** au lieu de tout réécrire
3. **Ajoutez des filtres** pour faciliter la recherche
4. **Intégrez le widget** dans votre dashboard pour une vue d'ensemble
5. **Utilisez les permissions** EasyAdmin pour contrôler l'accès

## 🚨 Dépannage

### Le CRUD n'apparaît pas

Vérifiez que l'entité `Event` est bien importée :

```php
use JeanSebastienChristophe\CalendarBundle\Entity\Event;
```

### Les couleurs ne s'affichent pas

Installez le ColorField d'EasyAdmin :

```bash
composer require easycorp/easyadmin-bundle
```

### Le widget ne charge pas les événements

Vérifiez l'injection du service :

```yaml
# config/services.yaml
services:
    JeanSebastienChristophe\CalendarBundle\Admin\CalendarDashboardWidget:
        autowire: true
        autoconfigure: true
```

## 📝 Exemple complet

Voir le dossier `examples/easyadmin/` pour un exemple complet d'intégration.

---

Pour plus d'informations, consultez la [documentation EasyAdmin](https://symfony.com/bundles/EasyAdminBundle/current/index.html).
