<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/profile/password', name: 'app_profile_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em
    ): Response {
        if ($request->isMethod('POST')) {
            /** @var User $user */
            $user = $this->getUser();
            $currentPwd = $request->request->get('current_password');
            $newPwd = $request->request->get('new_password');

            // 1. Verify current password
            if (!$hasher->isPasswordValid($user, $currentPwd)) {
                $this->addFlash('danger', 'The current password you entered is incorrect.');
                return $this->redirectToRoute('app_profile_password');
            }

            // 2. Hash and Save new password
            $user->setPassword($hasher->hashPassword($user, $newPwd));
            $em->flush();

            $this->addFlash('success', 'Your password has been updated successfully.');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('profile/change_password.html.twig');
    }
}
