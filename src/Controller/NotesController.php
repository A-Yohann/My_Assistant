<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Note;
use App\Form\NoteType;
use App\Service\EntrepriseActiveService;

class NotesController extends AbstractController
{
    #[Route('/notes', name: 'notes')]
    public function index(EntityManagerInterface $em, Request $request, EntrepriseActiveService $entrepriseService): Response
    {
        $user = $this->getUser();
        $entrepriseActive = $entrepriseService->getEntrepriseActive();

        $notes = $em->getRepository(Note::class)->createQueryBuilder('n')
            ->where('n.user = :user')
            ->andWhere('n.entreprise = :entreprise')
            ->setParameter('user', $user)
            ->setParameter('entreprise', $entrepriseActive)
            ->orderBy('n.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();

        $month = (int) $request->query->get('month', date('n'));
        $year  = (int) $request->query->get('year', date('Y'));

        if ($month < 1) { $month = 12; $year--; }
        if ($month > 12) { $month = 1; $year++; }

        $notesByDay = [];
        foreach ($notes as $note) {
            if ($note->getDateCreation()) {
                $noteMonth = (int) $note->getDateCreation()->format('n');
                $noteYear  = (int) $note->getDateCreation()->format('Y');
                if ($noteMonth === $month && $noteYear === $year) {
                    $day = (int) $note->getDateCreation()->format('j');
                    $notesByDay[$day][] = $note;
                }
            }
        }

        $firstDayOfMonth = (int) (new \DateTime("$year-$month-01"))->format('N');
        $daysInMonth     = (int) (new \DateTime("$year-$month-01"))->format('t');

        $prevMonth = $month - 1;
        $prevYear  = $year;
        if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

        $nextMonth = $month + 1;
        $nextYear  = $year;
        if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

        return $this->render('notes/notes.html.twig', [
            'notes'        => $notes,
            'notesByDay'   => $notesByDay,
            'month'        => $month,
            'year'         => $year,
            'firstDay'     => $firstDayOfMonth,
            'daysInMonth'  => $daysInMonth,
            'prevMonth'    => $prevMonth,
            'prevYear'     => $prevYear,
            'nextMonth'    => $nextMonth,
            'nextYear'     => $nextYear,
            'monthName'    => (new \DateTime("$year-$month-01"))->format('F'),
            'today'        => (int) date('j'),
            'currentMonth' => (int) date('n'),
            'currentYear'  => (int) date('Y'),
        ]);
    }

    #[Route('/notes/new', name: 'note_new')]
    public function new(Request $request, EntityManagerInterface $em, EntrepriseActiveService $entrepriseService): Response
    {
        $user = $this->getUser();
        $entrepriseActive = $entrepriseService->getEntrepriseActive();

        $note = new Note();
        $form = $this->createForm(NoteType::class, $note);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $note->setDateModification(new \DateTime());
            $note->setUser($user);
            // ✅ Lier la note à l'entreprise active
            $note->setEntreprise($entrepriseActive);
            $em->persist($note);
            $em->flush();
            return $this->redirectToRoute('notes');
        }
        return $this->render('notes/note_form.html.twig', [
            'form' => $form->createView(),
            'edit' => false,
        ]);
    }

    #[Route('/notes/{idNote}/edit', name: 'note_edit')]
    public function edit(int $idNote, Request $request, EntityManagerInterface $em): Response
    {
        $note = $em->getRepository(Note::class)->find($idNote);
        if (!$note) {
            throw $this->createNotFoundException('Note non trouvée');
        }
        $form = $this->createForm(NoteType::class, $note);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $note->setDateModification(new \DateTime());
            $em->flush();
            return $this->redirectToRoute('notes');
        }
        return $this->render('notes/note_form.html.twig', [
            'form' => $form->createView(),
            'edit' => true,
        ]);
    }

    #[Route('/notes/{idNote}/delete', name: 'note_delete', methods: ['POST'])]
    public function delete(int $idNote, EntityManagerInterface $em): Response
    {
        $note = $em->getRepository(Note::class)->find($idNote);
        if ($note) {
            $em->remove($note);
            $em->flush();
        }
        return $this->redirectToRoute('notes');
    }

    #[Route('/notes/{idNote}/deplacer', name: 'note_deplacer', methods: ['POST'])]
    public function deplacer(int $idNote, Request $request, EntityManagerInterface $em): Response
    {
        $note = $em->getRepository(Note::class)->find($idNote);
        if (!$note || $note->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Note non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $newDate = $data['date'] ?? null;

        if (!$newDate) {
            return $this->json(['error' => 'Date invalide'], 400);
        }

        try {
            $date = new \DateTime($newDate);
            $originalDate = $note->getDateCreation();
            $date->setTime(
                (int) $originalDate->format('H'),
                (int) $originalDate->format('i')
            );
            $note->setDateCreation($date);
            $note->setDateModification(new \DateTime());
            $em->flush();
            return $this->json(['success' => true, 'newDate' => $date->format('d/m/Y')]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/notes/{idNote}', name: 'note_show')]
    public function show(int $idNote, EntityManagerInterface $em): Response
    {
        $note = $em->getRepository(Note::class)->find($idNote);
        if (!$note) {
            throw $this->createNotFoundException('Note non trouvée');
        }
        return $this->render('notes/note_show.html.twig', [
            'note' => $note,
        ]);
    }
}