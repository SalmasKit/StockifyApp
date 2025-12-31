<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Association;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user_index')]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $users = $entityManager
            ->getRepository(User::class)
            ->findAll();

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'roles' => $user->getRoles(),
                'association_id' => $user->getAssociation() ? $user->getAssociation()->getId() : null,
            ];
        }

        return new JsonResponse($data);
    }

    //////////////////////////Creation////////////////////////////////
    #[Route('/user/create', name: 'app_user_create')]
    public function createUser(EntityManagerInterface $entityManager): JsonResponse
    {
        $association = $entityManager->getRepository(Association::class)->find(1);
        if (!$association) {
            return new JsonResponse([
                'error' => 'Association not found'
            ], 404);
        }

        $user = new User();
        $user->setEmail('user@test.com');
        $user->setPassword('$2y$13$.mGqlpWzLyD949Xd6R34xepLHtmu7M/XBVYrCNxE9pD...'); // Hashed password
        $user->setName('Test User');
        $user->setRoles(['ROLE_USER']);
        $user->setAssociation($association);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Saved new User',
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'association_id' => $user->getAssociation()->getId(),
        ]);
    }

    ////////////////////////////Displaying/////////////////////////////////
    #[Route('/user/show/{id}', name: 'app_user_show')]
    public function show(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $user = $entityManager
            ->getRepository(User::class)
            ->find($id);

        if (!$user) {
            return new JsonResponse([
                'error' => 'User not found'
            ], 404);
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
            'association_id' => $user->getAssociation() ? $user->getAssociation()->getId() : null,
        ]);
    }

    ////////////////////////////////Editing//////////////////////////////////////
    #[Route('/user/edit/{id}', name: 'app_user_edit')]
    public function editUser(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse([
                'error' => 'User not found'
            ], 404);
        }

        $user->setName('Updated User Name');
        $user->setEmail('updated@test.com');
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'User updated',
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
        ]);
    }

    ///////////////////////////Deleting///////////////////////////////////
    #[Route('/user/delete/{id}', name: 'app_user_delete')]
    public function deleteUser(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse([
                'error' => 'User not found'
            ], 404);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'User deleted',
            'id' => $id,
        ]);
    }
}
