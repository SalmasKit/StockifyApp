<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    /**
     * List all users for the Admin
     */
    #[Route('/', name: 'app_admin_users', methods: ['GET'])]
    public function index(UserRepository $userRepo): Response
    {
        return $this->render('admin/users/index.html.twig', [
            'users' => $userRepo->findAll(),
        ]);
    }

    /**
     * Activate/Deactivate a user (Approval logic)
     */
    #[Route('/{id}/toggle', name: 'app_admin_user_toggle', methods: ['GET'])]
    public function toggleStatus(User $user, EntityManagerInterface $em): Response
    {
        // Prevent an admin from desactivating himself
        if ($user === $this->getUser()) {
            $this->addFlash('warning', 'Security Protection: You cannot deactivate your own account.');
            return $this->redirectToRoute('app_admin_users');
        }

        // Toggle the active status
        $newState = !$user->isActive();
        $user->setIsActive($newState);

        // Ensure ROLE_USER is persisted in the database when activating
        // getRoles() adds ROLE_USER virtually if missing, so checking in_array() checks the virtual list.
        // We simply set back the full list (virtual + real) to make it real.
        if ($newState) {
            $user->setRoles($user->getRoles());
        }

        $em->flush();

        $statusLabel = $newState ? 'activated' : 'deactivated';
        $this->addFlash('success', "User {$user->getName()} has been successfully {$statusLabel}.");

        return $this->redirectToRoute('app_admin_users');
    }

    /**
     * Delete a user account
     */
    #[Route('/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('danger', 'You cannot delete the account you are currently using.');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'User account permanently deleted.');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/new', name: 'app_admin_user_new', methods: ['POST'])]
    public function newUser(Request $request, EntityManagerInterface $em, \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $hasher): Response
    {
        $user = new User();
        $user->setName($request->request->get('name'));
        $user->setEmail($request->request->get('email'));
        $user->setIsActive(true); // Admin-created users are active by default
        $user->setRoles(['ROLE_USER']);

        // Set a temporary password
        $hashedPassword = $hasher->hashPassword($user, 'Welcome123!');
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'User ' . $user->getName() . ' created successfully.');
        return $this->redirectToRoute('app_admin_users');
    }
}
