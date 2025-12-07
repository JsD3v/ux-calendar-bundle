<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use JeanSebastienChristophe\CalendarBundle\Admin\CalendarDashboardWidget;
use JeanSebastienChristophe\CalendarBundle\Admin\EventCrudController;
use JeanSebastienChristophe\CalendarBundle\Entity\Event;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Example Dashboard Controller for EasyAdmin with Calendar Integration
 *
 * This example shows how to:
 * - Add calendar menu items
 * - Display calendar statistics
 * - Show upcoming and recent events
 * - Link to the calendar UI
 *
 * Copy this file to src/Controller/Admin/DashboardController.php
 * and customize it to your needs.
 */
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly CalendarDashboardWidget $calendarWidget
    ) {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // Get calendar statistics
        $statistics = $this->calendarWidget->getStatistics();
        $upcomingEvents = $this->calendarWidget->getUpcomingEvents(5);
        $recentEvents = $this->calendarWidget->getRecentEvents(5);

        return $this->render('admin/dashboard.html.twig', [
            'statistics' => $statistics,
            'upcoming_events' => $upcomingEvents,
            'recent_events' => $recentEvents,
            'calendar_widget' => $this->calendarWidget,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Mon Application')
            ->setFaviconPath('favicon.ico')
            ->setLocales(['fr', 'en'])
            ->setDefaultColorScheme('auto');
    }

    public function configureMenuItems(): iterable
    {
        // Dashboard
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        // Calendar Section
        yield MenuItem::section('Calendrier', 'fa fa-calendar');

        // Link to Calendar CRUD
        yield MenuItem::linkToCrud('Gestion des événements', 'fa fa-list', Event::class)
            ->setController(EventCrudController::class);

        // Link to Calendar UI (opens in new tab)
        yield MenuItem::linkToRoute('Vue Calendrier', 'fa fa-calendar-alt', 'calendar_index')
            ->setLinkTarget('_blank');

        // Quick add event
        yield MenuItem::linkToRoute('Nouvel événement', 'fa fa-plus', 'calendar_event_new');

        // Link to current month
        $now = new \DateTime();
        yield MenuItem::linkToRoute('Ce mois', 'fa fa-calendar-days', 'calendar_month', [
            'year' => $now->format('Y'),
            'month' => $now->format('m'),
        ])->setLinkTarget('_blank');

        // Other sections (example)
        yield MenuItem::section('Administration');
        // yield MenuItem::linkToCrud('Users', 'fa fa-user', User::class);
        // yield MenuItem::linkToCrud('Settings', 'fa fa-cog', Setting::class);
    }
}
