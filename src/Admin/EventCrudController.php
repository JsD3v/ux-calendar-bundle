<?php

namespace JeanSebastienChristophe\CalendarBundle\Admin;

use JeanSebastienChristophe\CalendarBundle\Entity\Event;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

/**
 * EasyAdmin CRUD Controller for Calendar Events
 *
 * This controller provides a full admin interface for managing calendar events.
 * It includes:
 * - CRUD operations (Create, Read, Update, Delete)
 * - Advanced filtering by title, date, and all-day status
 * - Quick action to view events in the calendar UI
 * - Search functionality
 * - Pagination
 *
 * Usage in your DashboardController:
 * ```php
 * yield MenuItem::linkToCrud('Événements', 'fa fa-calendar', Event::class);
 * ```
 *
 * Or with custom entity:
 * ```php
 * yield MenuItem::linkToCrud('Événements', 'fa fa-calendar', MyCustomEvent::class);
 * ```
 */
class EventCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Event::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Événement')
            ->setEntityLabelInPlural('Événements')
            ->setPageTitle('index', 'Gestion des événements')
            ->setPageTitle('new', 'Créer un événement')
            ->setPageTitle('edit', 'Modifier l\'événement')
            ->setPageTitle('detail', 'Détails de l\'événement')
            ->setDefaultSort(['startDate' => 'DESC'])
            ->setSearchFields(['title', 'description'])
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined()
            ->setHelp('index', 'Gérez vos événements depuis cette interface ou <a href="' . $this->generateUrl('calendar_index') . '" target="_blank">visualisez-les dans le calendrier</a>');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm()
            ->setLabel('ID');

        yield TextField::new('title')
            ->setLabel('Titre')
            ->setRequired(true)
            ->setMaxLength(255)
            ->setHelp('Le titre de l\'événement (max 255 caractères)');

        yield DateTimeField::new('startDate')
            ->setLabel('Date de début')
            ->setRequired(true)
            ->setFormat('dd/MM/yyyy HH:mm')
            ->renderAsNativeWidget()
            ->setHelp('Date et heure de début de l\'événement');

        yield DateTimeField::new('endDate')
            ->setLabel('Date de fin')
            ->setRequired(true)
            ->setFormat('dd/MM/yyyy HH:mm')
            ->renderAsNativeWidget()
            ->setHelp('Date et heure de fin de l\'événement');

        yield BooleanField::new('allDay')
            ->setLabel('Journée entière')
            ->renderAsSwitch(true)
            ->setHelp('Cochez si l\'événement dure toute la journée');

        yield TextareaField::new('description')
            ->setLabel('Description')
            ->setRequired(false)
            ->hideOnIndex()
            ->setNumOfRows(5)
            ->setHelp('Description détaillée de l\'événement (optionnel)');

        yield ColorField::new('color')
            ->setLabel('Couleur')
            ->setRequired(false)
            ->hideOnIndex()
            ->setHelp('Couleur d\'affichage de l\'événement dans le calendrier');

        yield DateTimeField::new('createdAt')
            ->setLabel('Créé le')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');

        yield DateTimeField::new('updatedAt')
            ->setLabel('Modifié le')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');
    }

    public function configureActions(Actions $actions): Actions
    {
        // Action to view event in the calendar UI
        $viewInCalendar = Action::new('viewInCalendar', 'Voir dans le calendrier', 'fa fa-calendar')
            ->linkToRoute('calendar_month', function ($event): array {
                return [
                    'year' => $event->getStartDate()->format('Y'),
                    'month' => $event->getStartDate()->format('m'),
                ];
            })
            ->setHtmlAttributes(['target' => '_blank', 'title' => 'Ouvrir le calendrier à la date de cet événement']);

        // Action to edit in Turbo modal (optional, for advanced users)
        $editInModal = Action::new('editInModal', 'Modifier (modal)', 'fa fa-edit')
            ->linkToRoute('calendar_event_edit', function ($event): array {
                return ['id' => $event->getId()];
            })
            ->setHtmlAttributes([
                'data-turbo-frame' => 'event-modal',
                'title' => 'Modifier dans un modal Turbo'
            ])
            ->setCssClass('text-info');

        return $actions
            ->add(Crud::PAGE_INDEX, $viewInCalendar)
            ->add(Crud::PAGE_DETAIL, $viewInCalendar)
            ->add(Crud::PAGE_EDIT, $viewInCalendar)
            // Uncomment to enable Turbo modal editing
            // ->add(Crud::PAGE_INDEX, $editInModal)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, 'viewInCalendar', Action::DELETE])
            ->reorder(Crud::PAGE_DETAIL, [Action::EDIT, 'viewInCalendar', Action::DELETE, Action::INDEX]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('title')->setLabel('Titre'))
            ->add(DateTimeFilter::new('startDate')->setLabel('Date de début'))
            ->add(DateTimeFilter::new('endDate')->setLabel('Date de fin'))
            ->add(BooleanFilter::new('allDay')->setLabel('Journée entière'));
    }
}
