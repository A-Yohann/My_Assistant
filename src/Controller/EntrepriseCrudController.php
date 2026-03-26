<?php
namespace App\Controller;

use App\Entity\Entreprise;
use App\Entity\Siege;
use App\Form\EntrepriseType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/entreprise')]
class EntrepriseCrudController extends AbstractController
{
    #[Route('/', name: 'app_entreprise')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $entreprises = $em->getRepository(Entreprise::class)->findBy(['user' => $user]);
        return $this->render('entreprise/index.html.twig', [
            'entreprises' => $entreprises,
        ]);
    }

    #[Route('/new', name: 'entreprise_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $nbEntreprises = count($em->getRepository(Entreprise::class)->findBy(['user' => $user]));

        if ($user->getPlan() === 'free' && $nbEntreprises >= 1) {
            return $this->render('entreprise/limite.html.twig');
        }

        if ($user->getPlan() === 'pro' && $nbEntreprises >= 3) {
            return $this->render('entreprise/limite_pro.html.twig');
        }

        $entreprise = new Entreprise();
        $form = $this->createForm(EntrepriseType::class, $entreprise);
        $form->handleRequest($request);

        // ✅ Logo temporaire en session
        $session = $request->getSession();
        $logoTemp = $session->get('logo_temp');

        if ($form->isSubmitted()) {
            $logoFile = $form->get('logo')->getData();

            // ✅ Si nouveau logo uploadé → on le sauvegarde en session
            if ($logoFile) {
                $logoName = uniqid().'.'.$logoFile->guessExtension();
                $logoFile->move($this->getParameter('kernel.project_dir').'/public/uploads/logos', $logoName);
                $logoTemp = '/uploads/logos/'.$logoName;
                $session->set('logo_temp', $logoTemp);
            }

            if ($form->isValid()) {
                // ✅ On utilise le logo temp si pas de nouveau logo
                $entreprise->setLogo($logoTemp ?? '');

                if ($entreprise->getRoles() === null) {
                    $entreprise->setRoles(false);
                }
                if ($entreprise->getComplementAdresse() === null) {
                    $entreprise->setComplementAdresse('');
                }
                if ($entreprise->getType() === null) {
                    $entreprise->setType(false);
                }

                // ✅ Gestion du siège social optionnel
                $siege = $form->get('siege')->getData();
                if ($siege && ($siege->getNomSiege() || $siege->getAddresseSiege())) {
                    $siege->setDateCreation($siege->getDateCreation() ?? new \DateTime());
                    $siege->setStatuJuridique($siege->isStatuJuridique() ?? false);
                    $em->persist($siege);
                    $entreprise->setSiege($siege);
                } else {
                    $entreprise->setSiege(null);
                }

                $entreprise->setUser($this->getUser());
                $em->persist($entreprise);
                $em->flush();

                // ✅ Nettoyer la session
                $session->remove('logo_temp');

                $this->addFlash('success', 'Entreprise créée !');
                return $this->redirectToRoute('app_entreprise');
            }
        }

        return $this->render('entreprise/new.html.twig', [
            'form'      => $form->createView(),
            'logo_temp' => $logoTemp,
        ]);
    }

    #[Route('/new-multi', name: 'entreprise_new_multi')]
    public function newMulti(Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $nbEntreprises = count($em->getRepository(Entreprise::class)->findBy(['user' => $user]));

        if ($user->getPlan() === 'free' && $nbEntreprises >= 1) {
            return $this->render('entreprise/limite.html.twig');
        }

        if ($user->getPlan() === 'pro' && $nbEntreprises >= 3) {
            return $this->render('entreprise/limite_pro.html.twig');
        }

        $session = $request->getSession();
        $step = $request->query->get('step', 1);
        $formData = $session->get('entreprise_data', []);

        if ($request->isMethod('POST')) {
            $postData = $request->request->all();
            $formData = array_merge($formData, $postData);
            if ($request->files->get('logo')) {
                $logoFile = $request->files->get('logo');
                $logoName = uniqid().'.'.$logoFile->guessExtension();
                $logoFile->move($this->getParameter('kernel.project_dir').'/public/uploads/logos', $logoName);
                $formData['logo'] = '/uploads/logos/'.$logoName;
            }
            $session->set('entreprise_data', $formData);

            if ($step == 1) {
                if (
                    empty($postData['nomEntreprise']) ||
                    empty($postData['siret']) ||
                    empty($postData['formeJuridique']) ||
                    empty($postData['status']) ||
                    empty($postData['dateCreation']) ||
                    (!$request->files->get('logo') && empty($formData['logo']))
                ) {
                    $this->addFlash('error', 'Tous les champs sont requis');
                } else {
                    return $this->redirectToRoute('entreprise_new_multi', ['step' => 2]);
                }
            } elseif ($step == 2) {
                if (empty($postData['numeroRue']) || empty($postData['nomRue']) || empty($postData['codePostal']) || empty($postData['ville']) || empty($postData['pays'])) {
                    $this->addFlash('error', 'Tous les champs de localisation sont requis');
                } else {
                    $entreprise = new Entreprise();
                    $entreprise->setNomEntreprise($formData['nomEntreprise']);
                    $entreprise->setSiret($formData['siret']);
                    $entreprise->setEmail($formData['email'] ?? '');
                    $entreprise->setFormeJuridique($formData['formeJuridique'] ?? '');
                    $entreprise->setStatus($formData['status'] ?? '');
                    $entreprise->setTelephone($formData['telephone'] ?? '');
                    if (!empty($formData['dateCreation'])) {
                        $entreprise->setDateCreation(new \DateTime($formData['dateCreation']));
                    }
                    if (!empty($formData['logo'])) {
                        $entreprise->setLogo($formData['logo']);
                    } else {
                        $this->addFlash('error', 'Le logo est obligatoire.');
                        return $this->redirectToRoute('entreprise_new_multi', ['step' => 1]);
                    }
                    $entreprise->setNumeroRue($formData['numeroRue']);
                    $entreprise->setNomRue($formData['nomRue']);
                    $entreprise->setComplementAdresse($formData['complementAdresse'] ?? '');
                    $entreprise->setCodePostal($formData['codePostal']);
                    $entreprise->setVille($formData['ville']);
                    $entreprise->setPays($formData['pays']);
                    $entreprise->setRoles($formData['roles'] ?? false);
                    $entreprise->setType($formData['type'] ?? false);

                    // ✅ Gestion du siège social optionnel
                    if (!empty($formData['siege']['nomSiege']) || !empty($formData['siege']['addresseSiege'])) {
                        $siege = new Siege();
                        $siege->setNomSiege($formData['siege']['nomSiege'] ?? '');
                        $siege->setAddresseSiege($formData['siege']['addresseSiege'] ?? '');
                        $siege->setDateCreation(new \DateTime($formData['siege']['dateCreation'] ?? 'now'));
                        $siege->setStatuJuridique((bool)($formData['siege']['statuJuridique'] ?? false));
                        $em->persist($siege);
                        $entreprise->setSiege($siege);
                    }

                    $entreprise->setUser($this->getUser());
                    $em->persist($entreprise);
                    $em->flush();
                    $session->remove('entreprise_data');
                    $this->addFlash('success', 'Entreprise créée !');
                    return $this->redirectToRoute('app_entreprise');
                }
            }
        }

        $template = $step == 1 ? 'entreprise/new_step1.html.twig' : 'entreprise/new_step2.html.twig';
        return $this->render($template, [
            'step'     => $step,
            'formData' => $formData,
        ]);
    }

    #[Route('/{id}/edit', name: 'entreprise_edit')]
    #[IsGranted('EDIT', subject: 'entreprise')]
    public function edit(Entreprise $entreprise, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EntrepriseType::class, $entreprise);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // ✅ Gestion du logo à l'édition
            $logoFile = $form->get('logo')->getData();
            if ($logoFile) {
                $logoName = uniqid().'.'.$logoFile->guessExtension();
                $logoFile->move($this->getParameter('kernel.project_dir').'/public/uploads/logos', $logoName);
                $entreprise->setLogo('/uploads/logos/'.$logoName);
            }

            // ✅ Gestion du siège social optionnel à l'édition
            $siege = $form->get('siege')->getData();
            if ($siege && ($siege->getNomSiege() || $siege->getAddresseSiege())) {
                $siege->setDateCreation($siege->getDateCreation() ?? new \DateTime());
                $siege->setStatuJuridique($siege->isStatuJuridique() ?? false);
                $em->persist($siege);
                $entreprise->setSiege($siege);
            } else {
                $entreprise->setSiege(null);
            }

            $em->flush();
            $this->addFlash('success', 'Entreprise modifiée !');
            return $this->redirectToRoute('app_entreprise');
        }
        return $this->render('entreprise/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'entreprise_delete')]
    #[IsGranted('DELETE', subject: 'entreprise')]
    public function delete(Entreprise $entreprise, EntityManagerInterface $em): Response
    {
        $em->remove($entreprise);
        $em->flush();
        $this->addFlash('success', 'Entreprise supprimée !');
        return $this->redirectToRoute('app_entreprise');
    }

    #[Route('/{id}', name: 'entreprise_show')]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $entreprise = $em->getRepository(Entreprise::class)->find($id);
        if (!$entreprise) {
            throw $this->createNotFoundException('Entreprise non trouvée');
        }
        return $this->render('entreprise/show.html.twig', [
            'entreprise' => $entreprise,
        ]);
    }
}