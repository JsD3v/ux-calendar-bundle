# CalendarBundle pour Symfony 7.4 / 8

[![Tests](https://github.com/JsD3v/ux-calendar-bundle/actions/workflows/test.yml/badge.svg)](https://github.com/JsD3v/ux-calendar-bundle/actions/workflows/test.yml)
[![PHPStan](https://github.com/JsD3v/ux-calendar-bundle/actions/workflows/phpstan.yml/badge.svg)](https://github.com/JsD3v/ux-calendar-bundle/actions/workflows/phpstan.yml)

Bundle calendrier léger pour Symfony 7.4 et 8, basé sur Turbo, Stimulus et AssetMapper. Il fournit une vue mensuelle, des formulaires de gestion d'événements et des helpers EasyAdmin, sans dépendance JavaScript lourde de type FullCalendar. Les CDN tiers ne sont pas chargés par défaut.

## Compatibilité

- PHP >= 8.2
- Symfony FrameworkBundle, Form, Validator, TwigBundle, Console, Translation et AssetMapper `^7.4|^8.0`
- Symfony UX Turbo et Stimulus Bundle `^2.0|^3.0`
- Doctrine ORM `^2.0|^3.0` et DoctrineBundle `^2.0|^3.0`
- EasyAdmin `^4.0|^5.0` optionnel pour l'admin
- Symfony UX ChartJS `^2.0|^3.0` optionnel pour les graphiques du dashboard

## Fonctionnalités

- Vue mensuelle responsive avec mises à jour Turbo Streams
- Création, édition, suppression et exclusion ponctuelle de dates
- Entité `Event` prête à l'emploi
- Contrats `CalendarEventInterface` et `CalendarEventRepositoryInterface` pour les entités personnalisées
- Trait `CalendarEventTrait` pour réutiliser le mapping Doctrine commun
- Thème Bootstrap par défaut, variantes `default`, `tailwind` et détection automatique optionnelle
- CRUD EasyAdmin, champ calendrier et widget de dashboard optionnels

## Installation

```bash
composer require jean-sebastien-christophe/ux-calendar-bundle
```

Ajoutez le bundle dans `config/bundles.php` :

```php
JeanSebastienChristophe\CalendarBundle\CalendarBundle::class => ['all' => true],
```

Déclarez les routes dans `config/routes/calendar.yaml` :

```yaml
calendar_bundle:
    resource: '@CalendarBundle/src/Controller/'
    type: attribute
```

La route par défaut est `/events`. Pour utiliser `/calendar`, créez `config/packages/calendar.yaml` :

```yaml
calendar:
    theme: bootstrap
    assets:
        include_cdn: false
    route_prefix: /calendar
    views:
        enabled: [month]
        default: month
    features:
        all_day_events: true
        colors: true
```

Créez puis appliquez la migration Doctrine :

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
php bin/console cache:clear
```

Les assets CSS sont exposés via AssetMapper. Aucune commande `assets:install` n'est nécessaire.

Le thème par défaut est `bootstrap`, pour rester cohérent avec EasyAdmin et les classes utilisées par les templates. Le thème `bootstrap.css` se contente de mapper les variables `--bs-*` : **Bootstrap lui-même doit donc être chargé**, sinon les classes (`btn`, `container`, `alert`…) des templates ne sont pas stylées. Deux façons de le fournir :

1. **Via l'AssetMapper de votre application (recommandé).** Les pages standalone du calendrier rendent automatiquement `importmap('app')` (voir la section Stimulus). Si votre `importmap.php` importe Bootstrap (par ex. `import 'bootstrap/dist/css/bootstrap.min.css'` dans `assets/app.js`), il est chargé sur `/events` sans rien d'autre à faire.

2. **Via le CDN Bootstrap**, utile pour un rendu standalone quand l'application n'embarque pas Bootstrap :

```yaml
calendar:
    theme: bootstrap
    assets:
        include_cdn: true
```

Les thèmes `tailwind`, `default` et `auto` restent disponibles via `calendar.theme`.

## Stimulus

Le contrôleur Stimulus est exposé comme un contrôleur Symfony UX. Activez-le dans `assets/controllers.json` :

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

Votre application doit démarrer StimulusBundle, par exemple dans `assets/bootstrap.js` :

```javascript
import { startStimulusApp } from '@symfony/stimulus-bundle';

startStimulusApp();
```

Les pages standalone du calendrier (layout `@Calendar/calendar/base.html.twig`) rendent **automatiquement** l'entrypoint `importmap('app')`. C'est ce qui charge, sur `/events`, à la fois le contrôleur Stimulus `calendar` et les assets de votre application (dont Bootstrap s'il est dans votre `importmap.php`). Votre application doit donc disposer d'un entrypoint nommé `app` (le défaut Symfony).

Si votre entrypoint porte un autre nom, surchargez le bloc `importmap` en créant `templates/bundles/CalendarBundle/calendar/base.html.twig` :

```twig
{% extends '@Calendar/calendar/base.html.twig' %}

{% block importmap %}
    {{ importmap('mon_entrypoint') }}
{% endblock %}
```

Pour intégrer le calendrier dans votre propre layout (au lieu de la page standalone), surchargez ce même template afin qu'il étende le layout de votre application :

```twig
{# templates/bundles/CalendarBundle/calendar/base.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    {{ calendar_theme_css()|raw }}
    {% block calendar_body %}{% endblock %}
{% endblock %}
```

Ouvrez ensuite `/events`, ou `/calendar` si vous avez configuré `route_prefix: /calendar`.

## Routes exposées

`{prefix}` vaut `/events` par défaut.

| Méthode | Route | Nom | Description |
|---------|-------|-----|-------------|
| GET | `{prefix}` | `calendar_index` | Redirige vers le mois courant |
| GET | `{prefix}/{year}/{month}` | `calendar_month` | Affiche le calendrier mensuel |
| GET, POST | `{prefix}/new` | `calendar_event_new` | Affiche le formulaire et crée l'événement |
| GET, POST | `{prefix}/{id}/edit` | `calendar_event_edit` | Affiche le formulaire et met à jour l'événement |
| POST | `{prefix}/{id}/exclude/{date}` | `calendar_event_exclude_date` | Exclut une date pour un événement |
| POST, DELETE | `{prefix}/{id}` | `calendar_event_delete` | Supprime l'événement |

## Entité personnalisée

L'entité par défaut est `JeanSebastienChristophe\CalendarBundle\Entity\Event`. Pour utiliser votre propre entité, elle doit implémenter `CalendarEventInterface`. Le trait `CalendarEventTrait` fournit le mapping Doctrine commun.

Vous pouvez configurer l'entité avec la commande d'installation :

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

Le repository associé doit implémenter `CalendarEventRepositoryInterface`, car le contrôleur du bundle charge les événements mensuels via `findByMonth()`.

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

Configurez ensuite le bundle :

```yaml
calendar:
    event_class: App\Entity\MyEvent
```

Cette valeur est utilisée par les contrôleurs, le resolver d'arguments et `EventType`. Un `createForm(EventType::class, $event)` attend donc l'entité configurée, pas l'entité par défaut du bundle.

## EasyAdmin

Les helpers EasyAdmin sont optionnels. Installez EasyAdmin si nécessaire :

```bash
composer require easycorp/easyadmin-bundle
```

Puis référencez le CRUD fourni dans votre dashboard :

```php
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use JeanSebastienChristophe\CalendarBundle\Admin\EventCrudController;
use JeanSebastienChristophe\CalendarBundle\Entity\Event;

yield MenuItem::linkToCrud('Événements', 'fa fa-calendar', Event::class)
    ->setController(EventCrudController::class);
```

Voir aussi :

- [Guide EasyAdmin](docs/EASYADMIN.md)
- [Guide EasyAdmin avancé](docs/EASYADMIN_INTEGRATION.md)
- [Guide d'utilisation avancée](docs/USAGE.md)
- [Migration SQL](docs/MIGRATION.md)

## Qualité

Le dépôt ne versionne pas `vendor/`. Installez les dépendances avec Composer :

```bash
composer install
```

Commandes utiles avant une PR ou un tag :

```bash
composer validate --strict
composer analyse
composer test
```

`composer analyse` exécute PHPStan niveau 5 avec les extensions Symfony et Doctrine.

## Roadmap

- Vue semaine
- Vue jour
- Drag and drop pour déplacer les événements
- Événements récurrents complets
- Export iCal
- Catégories d'événements
- API REST

## Contribution

1. Forker le projet
2. Créer une branche (`git checkout -b feature/amazing-feature`)
3. Installer les dépendances (`composer install`)
4. Exécuter `composer analyse` et `composer test`
5. Pousser la branche et ouvrir une Pull Request

## Licence

MIT

## Support

Pour toute question ou problème, ouvrez une issue sur GitHub : <https://github.com/JsD3v/ux-calendar-bundle/issues>
