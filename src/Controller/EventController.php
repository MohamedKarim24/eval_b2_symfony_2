<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

final class EventController extends AbstractController
{
    #[Route('/evenement/ajouter', name: 'app_event_new')]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $event = new Event();
        $event->setAuthor($this->getUser());

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->uploadImage($form, $event, $slugger);
            $event->setValidated(false);

            $entityManager->persist($event);
            $entityManager->flush();

            $this->addFlash('success', 'Votre événement a bien été ajouté. Il sera visible après validation.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('event/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/evenement/{id}/modifier', name: 'app_event_edit')]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->getUser() !== $event->getAuthor() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cet événement.');
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->uploadImage($form, $event, $slugger);
            $entityManager->flush();

            $this->addFlash('success', 'L\'événement a été modifié.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        return $this->render('event/edit.html.twig', [
            'form' => $form,
            'event' => $event,
        ]);
    }

    #[Route('/evenement/{id}', name: 'app_event_show', requirements: ['id' => '\d+'])]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/evenement/{id}/inscription', name: 'app_event_register', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function register(Event $event, EntityManagerInterface $entityManager): Response
    {
        if ($event->getRemainingPlaces() <= 0) {
            $this->addFlash('error', 'Désolé, cet événement est complet.');
        } else {
            $event->addParticipant($this->getUser());
            $entityManager->flush();

            $this->addFlash('success', 'Vous êtes inscrit à cet événement.');
        }

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    #[Route('/mes-evenements', name: 'app_my_events')]
#[IsGranted('ROLE_USER')]
public function myEvents(): Response
{
    return $this->render('event/my_events.html.twig', [
        'events' => $this->getUser()->getEvents(),
    ]);
}

    #[Route('/evenement/{id}/desinscription', name: 'app_event_unregister', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function unregister(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        $event->removeParticipant($this->getUser());
        $entityManager->flush();

        $this->addFlash('success', 'Vous êtes désinscrit de cet événement.');

        return $this->redirect($request->headers->get('referer'));
    }

    private function uploadImage($form, Event $event, SluggerInterface $slugger): void
    {
        $imageFile = $form->get('image')->getData();
        
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads',
                    $newFilename
                );
                $event->setImage($newFilename);
            } catch (FileException $e) {
            }
        }
    }
}