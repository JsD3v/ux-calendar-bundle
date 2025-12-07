# CalendarBundle pour Symfony 7.4 / 8

Bundle calendrier léger pour Symfony 7.4+ et 8 basé sur **Turbo** et **Stimulus**, sans dépendances externes de type FullCalendar. Idéal pour une intégration rapide avec un minimum de configuration.

## Fonctionnalités clés

- Vue mensuelle réactive avec Turbo Streams
- Création, édition et suppression d'événements (journée ou horaires)
- Couleurs personnalisables et interface Bootstrap 5 responsive
- Aucun JS lourd : uniquement Stimulus

## Installation rapide

```bash
composer require jean-sebastien-christophe/calendar-bundle
composer require symfony/asset-mapper symfony/ux-turbo symfony/stimulus-bundle
```

Activez le bundle via Symfony Flex ou ajoutez-le dans `config/bundles.php` si nécessaire :

```php
JeanSebastienChristophe\CalendarBundle\CalendarBundle::class => ['all' => true],
```

Routes obligatoires (`config/routes/calendar.yaml`) :

```yaml
calendar_bundle:
    resource: '@CalendarBundle/src/Controller/'
    type: attribute
```

Base de données :

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

Stimulus (copie du contrôleur) :

```bash
mkdir -p assets/controllers
cp vendor/jean-sebastien-christophe/calendar-bundle/assets/dist/controllers/calendar_controller.js assets/controllers/
```

Enregistrez-le dans `assets/app.js` :

```javascript
import '@hotwired/turbo';
import './bootstrap.js';
import { startStimulusApp } from '@hotwired/stimulus-bundle';
import CalendarController from './controllers/calendar_controller.js';

const app = startStimulusApp();
app.register('calendar', CalendarController);
```

Lancez votre serveur et ouvrez `/calendar`.

## Configuration (optionnelle)

Créer `config/packages/calendar.yaml` si vous voulez changer l'URL ou les options :

```yaml
calendar:
    route_prefix: /calendar
    features:
        all_day_events: true
        colors: true
```

## Personnalisation

- Surchargez les templates dans `templates/bundles/CalendarBundle/calendar/`.
- Étendez l'entité `Event` pour définir une couleur par défaut ou ajouter des champs métier.

## État du bundle

Version initiale en cours de préparation pour la distribution. Tests et vues supplémentaires (semaine/jour) arriveront dans les prochaines itérations.

### Routes exposées

| Méthode | Route | Nom | Description |
|---------|-------|-----|-------------|
| GET | `/events` | `calendar_index` | Redirige vers mois courant |
| GET | `/events/{year}/{month}` | `calendar_month` | Affiche le calendrier |
| GET | `/events/new` | `calendar_event_new` | Formulaire création |
| POST | `/events` | `calendar_event_new` | Crée l'événement |
| GET | `/events/{id}/edit` | `calendar_event_edit` | Formulaire édition |
| POST | `/events/{id}` | `calendar_event_edit` | Met à jour l'événement |
| DELETE | `/events/{id}` | `calendar_event_delete` | Supprime l'événement |

## 🚀 Utilisation

### Créer un événement

Cliquez sur une date dans le calendrier → Un modal s'ouvre avec le formulaire pré-rempli.

### Modifier un événement

Cliquez sur un événement → Le modal d'édition s'ouvre.

### Navigation

Utilisez les boutons **← Mois précédent** | **Aujourd'hui** | **Mois suivant →**

### Turbo Streams en action

Toutes les actions (création, modification, suppression) mettent à jour le calendrier **sans rechargement de page** grâce à Turbo Streams !

## 🔧 Dépannage

### Erreur : "CalendarBundle requires Turbo Bundle"

```bash
composer require symfony/ux-turbo
php bin/console cache:clear
```

### Erreur : "CalendarBundle requires Stimulus Bundle"

```bash
composer require symfony/stimulus-bundle
php bin/console importmap:install
```

### Erreur : "CalendarBundle requires AssetMapper"

```bash
composer require symfony/asset-mapper
php bin/console importmap:install
```

### Le contrôleur Stimulus ne fonctionne pas

Vérifiez que vous avez bien :
1. Copié `calendar_controller.js` dans `assets/controllers/`
2. Enregistré le contrôleur dans votre `app.js`
3. Lancé `php bin/console asset-map:compile`

## 🗺️ Roadmap (V2)

- [ ] Vue semaine
- [ ] Vue jour
- [ ] Drag & drop pour déplacer les événements
- [ ] Événements récurrents
- [ ] Export iCal
- [ ] Catégories d'événements
- [ ] Multi-utilisateurs (événements privés/publics)
- [ ] API REST
- [ ] Interface `CalendarEventInterface` pour plus de flexibilité
- [ ] Trait `CalendarEventTrait`
- [ ] Tests PHPUnit

## 📄 Licence

MIT

## 🤝 Contribution

Les contributions sont les bienvenues ! 

1. Fork le projet
2. Créez votre branche (`git checkout -b feature/amazing-feature`)
3. Commit vos changements (`git commit -m 'Add amazing feature'`)
4. Push vers la branche (`git push origin feature/amazing-feature`)
5. Ouvrez une Pull Request

## 📞 Support

Pour toute question ou problème, ouvrez une issue sur GitHub.

---

Fait avec ❤️ pour la communauté Symfony
