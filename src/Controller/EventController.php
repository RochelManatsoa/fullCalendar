<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Event;
use App\Entity\Place;
use App\Service\Cart;
use App\Form\EventType;
use App\Security\Voter\EventVoter;
use App\Repository\EventRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/event')]
class EventController extends AbstractController
{
    #[Route('/', name: 'app_event_index', methods: ['GET'])]
    public function index(
        EventRepository $eventRepository,
        Request $request,
        Security $security
    ): Response
    {
        $page = $request->query->getInt('page', 1);
        /** @var User $user */
        $user = $security->getUser();
        $userId = $user->getId();
        $canListAll = $security->isGranted(EventVoter::LIST_ALL);
        $events = $eventRepository->paginateRecipes($page, $canListAll ? null : $userId);

        return $this->render('event/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        Cart $cartService,
        Security $security,
        ProductRepository $productRepository
    ): Response
    {
        $date = $request->query->get('date', 'today');
        $placeId = $request->query->get('placeId', null);
        $journee = $productRepository->findOneBy(['slug' => 'place-journee']);
        $demiJournee = $productRepository->findOneBy(['slug' => 'place-demi-journee']);
        /** @var User $user */
        $user = $security->getUser();
        $place = $entityManager->getRepository(Place::class)->findOneBy(['id' => $placeId]);
        $event = new Event();
        $event->setTitle($user->getNom() . ' ' . $user->getPrenom());
        if ($date === 'dayAfterTomorrow') {
            $event->setStartEvent((new \DateTime('tomorrow'))->modify('+1 day')->setTime(8, 0));
        }else{
            $event->setStartEvent((new \DateTime($date))->setTime(8, 0));
        }
        if ($place) {
            $event->addPlace($place);
        }   
        $event->setBackgroundColor('#ff0000'); 
        $event->setTextColor('#000000'); 
        $event->setBorderColor('#00ff00');
        $event->setAllDay(false);
        $form = $this->createForm(EventType::class, $event, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = $form->getData();
            $event->setUser($this->getUser());
            $entityManager->persist($event);
            $entityManager->flush();
            foreach ($event->getPlaces() as $place) {
                if($event->getDuration() == "demi_journee"){
                    $cartService->addProduct($demiJournee->getId());
                }else{
                    $cartService->addProduct($journee->getId());
                }
            }
            $this->addFlash('success', 'Place bien réservée');

            return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    #[IsGranted('EVENT_EDIT', subject: 'event')]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            $entityManager->remove($event);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
    }
}
