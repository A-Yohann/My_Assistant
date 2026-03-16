<?php
namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Note;
use App\Form\NoteType;

class NotesController extends AbstractController
{
    #[Route('/notes', name: 'notes')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $notes = $em->getRepository(Note::class)->findBy(['user' => $user], ['dateCreation' => 'DESC']);
        return $this->render('notes/notes.html.twig', [
            'notes' => $notes
        ]);
    }

    #[Route('/notes/new', name: 'note_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $note = new Note();
        $form = $this->createForm(NoteType::class, $note);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $note->setDateCreation(new \DateTime());
            $note->setDateModification(new \DateTime());
            $note->setUser($user);
            $em->persist($note);
            $em->flush();
            return $this->redirectToRoute('notes');
        }
        return $this->render('notes/note_form.html.twig', [
            'form' => $form->createView(),
            'edit' => false
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
            'edit' => true
        ]);
    }

    #[Route('/notes/{idNote}/delete', name: 'note_delete', methods: ['POST'])]
    public function delete(int $idNote, EntityManagerInterface $em, Request $request): Response
    {
        $note = $em->getRepository(Note::class)->find($idNote);
        if ($note) {
            $em->remove($note);
            $em->flush();
        }
        return $this->redirectToRoute('notes');
    }
}
