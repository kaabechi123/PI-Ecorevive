<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\AvatarType;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class AvatarController extends AbstractController
{
    private $entityManager;

    // Inject the EntityManagerInterface
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/profile/avatar', name: 'avatar_update')]
    public function updateAvatar(Request $request): Response
    {
        // Get the current user
        $user = $this->getUser();

        // Create the form
        $form = $this->createForm(AvatarType::class, $user);

        // Handle form submission
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Save the updated user (including the avatar)
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Add a success message
            $this->addFlash('success', 'Avatar updated successfully!');

            // Redirect to the profile page or another route
            return $this->redirectToRoute('app_profile');
        }

        // Render the form and pass the user variable
        return $this->render('profile/avatar.html.twig', [
            'form' => $form->createView(),
            'user' => $user, // Pass the user variable to the template
        ]);
    }
}
