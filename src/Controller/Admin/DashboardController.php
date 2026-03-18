<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use App\Entity\Client;
use App\Entity\Facture;
use App\Entity\BonDeCommande;
use App\Entity\Devis;
use App\Entity\Entreprise;
use App\Entity\Note;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
        private EntityManagerInterface $em
    ) {}

    public function index(): Response
    {
        // ✅ Page d'accueil personnalisée avec stats
        $totalUsers      = $this->em->getRepository(User::class)->count([]);
        $totalEntreprises = $this->em->getRepository(Entreprise::class)->count([]);
        $totalDevis      = $this->em->getRepository(Devis::class)->count([]);
        $totalFactures   = $this->em->getRepository(Facture::class)->count([]);
        $totalClients    = $this->em->getRepository(Client::class)->count([]);
        $totalBons       = $this->em->getRepository(BonDeCommande::class)->count([]);
        $totalNotes      = $this->em->getRepository(Note::class)->count([]);

        $devisEnAttente  = $this->em->getRepository(Devis::class)->count(['etat' => 'en_attente']);
        $facturesImpayees = $this->em->getRepository(Facture::class)->count(['etat' => 'impayee']);

        return $this->render('admin/dashboard.html.twig', [
            'totalUsers'       => $totalUsers,
            'totalEntreprises' => $totalEntreprises,
            'totalDevis'       => $totalDevis,
            'totalFactures'    => $totalFactures,
            'totalClients'     => $totalClients,
            'totalBons'        => $totalBons,
            'totalNotes'       => $totalNotes,
            'devisEnAttente'   => $devisEnAttente,
            'facturesImpayees' => $facturesImpayees,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<img src="/assets/img/logo.png" alt="Logo" style="height:40px;"> My Assistant')
            ->setFaviconPath('/assets/img/logo.png')
            ->renderContentMaximized();
    }

    public function configureAssets(): Assets
    {
        return Assets::new()->addCssFile('css/admin.css');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Gestion');
        yield MenuItem::linkToRoute('Utilisateurs', 'fas fa-user', 'admin_user_index');
        yield MenuItem::linkToRoute('Entreprises', 'fas fa-building', 'admin_entreprise_index');
        yield MenuItem::linkToRoute('Clients', 'fas fa-users', 'admin_client_index');
        yield MenuItem::section('Documents');
        yield MenuItem::linkToRoute('Devis', 'fas fa-file-contract', 'admin_devis_index');
        yield MenuItem::linkToRoute('Bons de commande', 'fas fa-file', 'admin_bon_de_commande_index');
        yield MenuItem::linkToRoute('Factures', 'fas fa-file-invoice', 'admin_facture_index');
        yield MenuItem::section('Autres');
        yield MenuItem::linkToRoute('Notes', 'fas fa-sticky-note', 'admin_note_index');
        yield MenuItem::linkToUrl('Retour au site', 'fas fa-arrow-left', '/');
    }
}