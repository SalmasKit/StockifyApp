<?php

namespace App\Controller;

use App\Entity\Association;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AssociationController extends AbstractController
{
    #[Route('/association', name: 'app_association_index')]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $associations = $entityManager
            ->getRepository(Association::class)
            ->findAll();

        $data = [];
        foreach ($associations as $association) {
            $data[] = [
                'id' => $association->getId(),
                'name' => $association->getName(),
                'email' => $association->getEmail(),
            ];
        }

        return new JsonResponse($data);
    }

    //////////////////////////Creation////////////////////////////////
    #[Route('/association/create', name: 'app_association_create')]
    public function createAssociation(EntityManagerInterface $entityManager): JsonResponse
    {
        $association = new Association();
        $association->setName('AssociationA');
        $association->setEmail('association.a@gmail.com');

        $entityManager->persist($association);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Saved new Association',
            'id' => $association->getId(),
            'name' => $association->getName(),
            'email' => $association->getEmail(),
        ]);
    }

    ////////////////////////////Displaying/////////////////////////////////
    #[Route('/association/show/{id}', name: 'app_association_show')]
    public function show(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $association = $entityManager
            ->getRepository(Association::class)
            ->find($id);

        if (!$association) {
            return new JsonResponse([
                'error' => 'Association not found'
            ], 404);
        }

        return new JsonResponse([
            'id' => $association->getId(),
            'name' => $association->getName(),
            'email' => $association->getEmail(),
        ]);
    }

    ////////////////////////////////Editing//////////////////////////////////////
    #[Route('/association/edit/{id}', name: 'app_association_edit')]
    public function editAssociation(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $association = $entityManager->getRepository(Association::class)->find($id);
        if (!$association) {
            return new JsonResponse([
                'error' => 'Association not found'
            ], 404);
        }

        $association->setName('AssociationB');
        $association->setEmail('association.b@gmail.com');
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Association updated',
            'id' => $association->getId(),
            'name' => $association->getName(),
            'email' => $association->getEmail(),
        ]);
    }

    ///////////////////////////Deleting///////////////////////////////////
    #[Route('/association/delete/{id}', name: 'app_association_delete')]
    public function deleteAssociation(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $association = $entityManager->getRepository(Association::class)->find($id);
        if (!$association) {
            return new JsonResponse([
                'error' => 'Association not found'
            ], 404);
        }

        $entityManager->remove($association);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Association deleted',
            'id' => $id,
        ]);
    }
}
