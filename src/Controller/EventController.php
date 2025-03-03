<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Ticket;
use App\Form\AddEventFormType;
use App\Form\UpdateEventFormType;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\EventRepository;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

final class EventController extends AbstractController
{
    #[Route('/create_event', name: 'create_event', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Create a new Event object
        $event = new Event();
    
        // Get the current logged-in user (the organizer)
        $user = $this->getUser();
        $event->setOrganizer($user);
    
        // Create the form
        $form = $this->createForm(AddEventFormType::class, $event);
    
        // Handle form submission
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Save the event to the database
            $entityManager->persist($event);
            $entityManager->flush();
    
            // Add a success flash message
            $this->addFlash('success', 'Event created successfully!');
    
            // Redirect to the events page
            return $this->redirectToRoute('create_event');
        }
    
        // Render the form template
        return $this->render('event/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/create_event2', name: 'create_event2', methods: ['GET', 'POST'])]
    public function index2(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Create a new Event object
        $event = new Event();
    
        // Get the current logged-in user (the organizer)
        $user = $this->getUser();
        $event->setOrganizer($user);
    
        // Create the form
        $form = $this->createForm(AddEventFormType::class, $event);
    
        // Handle form submission
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Save the event to the database
            $entityManager->persist($event);
            $entityManager->flush();
    
            // Add a success flash message
            $this->addFlash('success', 'Event created successfully!');
    
            // Redirect to the events page
            return $this->redirectToRoute('create_event2');
        }
    
        // Render the form template
        return $this->render('event/createorg.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/event_display', name: 'event_display', methods: ['GET'])]
    public function displayEvents(EntityManagerInterface $entityManager): Response
    {
        // Fetch all events from the database
        $events = $entityManager->getRepository(Event::class)->findAll();
    
        return $this->render('event/display.html.twig', [
            'events' => $events,
        ]);
    }
   
    #[Route('/participate/{eventId}', name: 'participate_event', methods: ['POST'])]
    public function participateEvent(Request $request, EntityManagerInterface $entityManager, int $eventId): Response
    {
        // Fetch the event by ID
        $event = $entityManager->getRepository(Event::class)->find($eventId);
    
        if (!$event) {
            throw $this->createNotFoundException('Event not found.');
        }
    
        // Get the current logged-in user (the owner)
        $user = $this->getUser();
    
        if (!$user) {
            throw $this->createNotFoundException('Owner not found.');
        }
    
        // Create a new Ticket
        $ticket = new Ticket();
        $ticket->setEvent($event);
        $ticket->setOwner($user);
    
        // Add the owner to the event's participants list
        $event->addParticipant($user);
    
        // **Persist and flush the ticket first to get the ID**
        $entityManager->persist($ticket);
        $entityManager->flush(); // Now the ticket has an ID
    
        // **Generate QR Code AFTER persisting**
        $qrCodeData = json_encode([
            'ticket_id' => $ticket->getId(), // Now it has a valid ID
            'owner' => $user->getUsername(),
            'event' => $event->getName(),
        ]);
    
        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $qrCodeData,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 40,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            logoPath: '',
            logoResizeToWidth: 50,
            logoPunchoutBackground: false,
            labelText: '',
            labelFont: new OpenSans(20),
            labelAlignment: LabelAlignment::Center
        );
    
        $qrCodeResult = $builder->build();
    
        // **Save the QR code image to a file**
        $qrCodeImagePath = $this->getParameter('kernel.project_dir') . '/public/qrcodes/ticket_' . $ticket->getId() . '.png';
        $qrCodeResult->saveToFile($qrCodeImagePath);
    
        // **Set the correct QR code URL and update the ticket**
        $ticket->setQrcode('/qrcodes/ticket_' . $ticket->getId() . '.png');
        
        // Flush again to save the QR code path
        $entityManager->flush();
    
        // Redirect with success message
        $this->addFlash('success', 'You have successfully participated in the event!');
        return $this->redirectToRoute('event_display');
    }
    #[Route('/dashboard/events', name: 'dashboard_events')]
public function events(EventRepository $eventRepository): Response
{
    $events = $eventRepository->findAll();

    return $this->render('event/displaydashboard.html.twig', [
        'events' => $events,
    ]);
}
#[Route('/dashboard/events/delete/{id}', name: 'dashboard_events_delete', methods: ['GET'])]
public function deleteEvent(int $id, EventRepository $eventRepository, EntityManagerInterface $entityManager): Response
{
    $event = $eventRepository->find($id);

    if ($event) {
        $entityManager->remove($event);
        $entityManager->flush();
        $this->addFlash('success', 'Event deleted successfully.');
    } else {
        $this->addFlash('error', 'Event not found.');
    }

    return $this->redirectToRoute('dashboard_events');
}
#[Route('/dashboard/events/update/{id}', name: 'update_event')]
public function updateEvent(int $id, EventRepository $eventRepository, Request $request, EntityManagerInterface $entityManager): Response
{
    $event = $eventRepository->find($id);

    if (!$event) {
        throw $this->createNotFoundException('Event not found');
    }

    $form = $this->createForm(UpdateEventFormType::class, $event);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();
        $this->addFlash('success', 'Event updated successfully.');

        return $this->redirectToRoute('dashboard_events');
    }

    return $this->render('event/updatedashboard.html.twig', [
        'form' => $form->createView(),
    ]);
}
#[Route('/org/events', name: 'org_events', methods: ['GET'])]
public function userEvents(EventRepository $eventRepository): Response
{
    // Get the current logged-in user (the organizer)
    $user = $this->getUser();

    // Fetch the events created by the logged-in user
    $events = $eventRepository->findBy(['organizer' => $user]);

    return $this->render('event/displayorg.html.twig', [
        'events' => $events,
    ]);
}
#[Route('/org/events/update/{id}', name: 'update_event2')]
public function updateEvent2(int $id, EventRepository $eventRepository, Request $request, EntityManagerInterface $entityManager): Response
{
    $event = $eventRepository->find($id);

    if (!$event) {
        throw $this->createNotFoundException('Event not found');
    }

    $form = $this->createForm(UpdateEventFormType::class, $event);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();
        $this->addFlash('success', 'Event updated successfully.');

        return $this->redirectToRoute('org_events');
    }

    return $this->render('event/updateorg.html.twig', [
        'form' => $form->createView(),
    ]);
}
#[Route('/org/events/delete/{id}', name: 'org_events_delete', methods: ['GET'])]
public function deleteEvent2(int $id, EventRepository $eventRepository, EntityManagerInterface $entityManager): Response
{
    $event = $eventRepository->find($id);

    if ($event) {
        $entityManager->remove($event);
        $entityManager->flush();
        $this->addFlash('success', 'Event deleted successfully.');
    } else {
        $this->addFlash('error', 'Event not found.');
    }

    return $this->redirectToRoute('org_events');
}
}
