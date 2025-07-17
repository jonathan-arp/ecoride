<?php

namespace App\Controller\Admin;

use App\Controller\Admin\EmployeeCrudController;
use App\Controller\Admin\UserCrudController;
use App\Entity\Review;
use App\Entity\User;
use App\Service\StatsService;
use App\Service\DirectStatsService;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private StatsService $statsService,
        private DirectStatsService $directStatsService
    ) {}

    public function index(): Response
    {
        // Vérification explicite de l'accès
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Afficher le dashboard personnalisé avec les statistiques
        $stats = $this->statsService->getGeneralStats();
        $carsharesChartData = $this->statsService->getCarsharesChartData();
        $creditsChartData = $this->statsService->getCreditsChartData();

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'carsharesChartData' => $carsharesChartData,
            'creditsChartData' => $creditsChartData,
        ]);
    }

    #[Route('/admin/update-stats', name: 'admin_update_stats')]
    #[IsGranted('ROLE_ADMIN')]
    public function updateStats(): Response
    {
        try {
            $result = $this->directStatsService->updateTodayStats();
            $this->addFlash('success', sprintf(
                'Statistiques mises à jour : %d covoiturages, %s crédits pour le %s.',
                $result['carshares'],
                $result['credits'],
                $result['date']
            ));
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la mise à jour des statistiques : ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<a href="' . $this->generateUrl('app_home') . '">EcoRide</a>')
            ->setFaviconPath('favicon.ico');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('📊 Dashboard', 'fa fa-home');
        
        yield MenuItem::section('👥 Gestion des Comptes');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', User::class)
            ->setController(UserCrudController::class);
        yield MenuItem::linkToCrud('Employés', 'fas fa-user-tie', User::class)
            ->setController(EmployeeCrudController::class);
        
        yield MenuItem::section('📝 Contenu');
        yield MenuItem::linkToCrud('Avis', 'fas fa-star', Review::class);
        
        yield MenuItem::section('🔧 Outils');
        yield MenuItem::linkToRoute('Mettre à jour les stats', 'fas fa-sync', 'admin_update_stats');
        yield MenuItem::linkToRoute('Retour au site', 'fas fa-arrow-left', 'app_account');
        yield MenuItem::linkToRoute('Déconnexion', 'fas fa-sign-out-alt', 'app_logout');
    }
}