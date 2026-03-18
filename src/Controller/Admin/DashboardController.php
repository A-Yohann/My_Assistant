<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(private AdminUrlGenerator $adminUrlGenerator)
    {
    }

    public function index(): Response
    {
        return $this->redirectToRoute('admin_user_index');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('My Assistant');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToRoute('Utilisateurs', 'fas fa-user', 'admin_user_index');
        yield MenuItem::linkToRoute('Entreprises', 'fas fa-building', 'admin_entreprise_index');
        yield MenuItem::linkToRoute('Clients', 'fas fa-users', 'admin_client_index');
        yield MenuItem::linkToRoute('Devis', 'fas fa-file-contract', 'admin_devis_index');
        yield MenuItem::linkToRoute('Bons de commande', 'fas fa-file', 'admin_bon_de_commande_index');
        yield MenuItem::linkToRoute('Factures', 'fas fa-file-invoice', 'admin_facture_index');
        yield MenuItem::linkToRoute('Notes', 'fas fa-sticky-note', 'admin_note_index');
    }
}