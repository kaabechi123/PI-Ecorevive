<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Form\UpdateTicketFormType; // Assuming you have a form type for updating tickets
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\TicketRepository;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Snappy\Pdf;

final class TicketController extends AbstractController
{
    #[Route('/dashboard/tickets', name: 'dashboard_tickets')]
    public function displayTickets(Request $request, TicketRepository $ticketRepository, PaginatorInterface $paginator): Response
    {
        // Fetch all tickets from the database
        $ticketsQuery = $ticketRepository->findAll(); // This should be a query builder or a query
    
        // Paginate the results
        $pagination = $paginator->paginate(
            $ticketsQuery, // Query NOT result
            $request->query->getInt('page', 1), // Page number
            10 // Limit per page (you can adjust this)
        );
    
        return $this->render('ticket/displaydashboard.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/dashboard/tickets/delete/{id}', name: 'dashboard_tickets_delete', methods: ['GET'])]
    public function deleteTicket(int $id, TicketRepository $ticketRepository, EntityManagerInterface $entityManager): Response
    {
        $ticket = $ticketRepository->find($id);

        if ($ticket) {
            $entityManager->remove($ticket);
            $entityManager->flush();
            $this->addFlash('success', 'Ticket deleted successfully.');
        } else {
            $this->addFlash('error', 'Ticket not found.');
        }

        return $this->redirectToRoute('dashboard_tickets');
    }

    #[Route('/dashboard/tickets/update/{id}', name: 'update_ticket')]
    public function updateTicket(int $id, TicketRepository $ticketRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $ticket = $ticketRepository->find($id);

        if (!$ticket) {
            throw $this->createNotFoundException('Ticket not found');
        }

        // Create form for updating the ticket (assuming you have a form for this)
        $form = $this->createForm(UpdateTicketFormType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Ticket updated successfully.');

            return $this->redirectToRoute('dashboard_tickets');
        }

        return $this->render('ticket/updatedashboard.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/org/tickets', name: 'org_tickets')]
public function displayTickets2(EntityManagerInterface $entityManager): Response
{
    // Get the current logged-in user
    $user = $this->getUser();

    // Fetch tickets where the user is the organizer
    $tickets = $entityManager->getRepository(Ticket::class)
        ->createQueryBuilder('t')
        ->innerJoin('t.event', 'e')
        ->where('e.organizer = :user')
        ->setParameter('user', $user)
        ->getQuery()
        ->getResult();

    return $this->render('ticket/displayorg.html.twig', [
        'tickets' => $tickets,
    ]);
}
#[Route('/org/tickets/delete/{id}', name: 'org_tickets_delete', methods: ['GET'])]
    public function deleteTicket2(int $id, TicketRepository $ticketRepository, EntityManagerInterface $entityManager): Response
    {
        $ticket = $ticketRepository->find($id);

        if ($ticket) {
            $entityManager->remove($ticket);
            $entityManager->flush();
            $this->addFlash('success', 'Ticket deleted successfully.');
        } else {
            $this->addFlash('error', 'Ticket not found.');
        }

        return $this->redirectToRoute('org_tickets');
    }

    #[Route('/org/tickets/update/{id}', name: 'update_ticket2')]
    public function updateTicket2(int $id, TicketRepository $ticketRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $ticket = $ticketRepository->find($id);

        if (!$ticket) {
            throw $this->createNotFoundException('Ticket not found');
        }

        // Create form for updating the ticket (assuming you have a form for this)
        $form = $this->createForm(UpdateTicketFormType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Ticket updated successfully.');

            return $this->redirectToRoute('org_tickets');
        }

        return $this->render('ticket/updateorg.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/tickets/download/{id}', name: 'tickets_download', methods: ['GET'])]
    public function downloadTicketPdf(int $id, TicketRepository $ticketRepository, Pdf $knpSnappy): Response
    {
        $ticket = $ticketRepository->find($id);
    
        if (!$ticket) {
            throw $this->createNotFoundException('Ticket not found');
        }
    
        // Render the HTML view for the PDF
        $html = $this->renderView('ticket/ticket_pdf.html.twig', [
            'ticket' => $ticket,
        ]);
    
        // Generate the PDF with local file access enabled
        $pdfContent = $knpSnappy->getOutputFromHtml($html, [
            'enable-local-file-access' => true, // Enable local file access
        ]);
    
        // Return the PDF as a response
        return new Response(
            $pdfContent,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="ticket_' . $ticket->getId() . '.pdf"',
            ]
        );
    }

}
