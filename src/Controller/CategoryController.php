<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class CategoryController extends AbstractController
{
    #[Route('/category', name: 'app_category_index')]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $categories = $entityManager
            ->getRepository(Category::class)
            ->findAll();

        $data = [];
        foreach ($categories as $category) {
            $data[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'description' => $category->getDescription(),
                'created_by_id' => $category->getCreatedBy() ? $category->getCreatedBy()->getId() : null,
                'created_by_name' => $category->getCreatedBy() ? $category->getCreatedBy()->getName() : null,
            ];
        }

        return new JsonResponse($data);
    }


    //////////////////////////Creation////////////////////////////////
    #[Route('/category/create', name: 'app_category_create')]
    public function createCategory(EntityManagerInterface $entityManager): JsonResponse
    {
        // Trouver l'utilisateur par son ID (ici on prend le premier)
        $user = $entityManager->getRepository(User::class)->find(1);
        if (!$user) {
            return new JsonResponse([
                'error' => 'User not found. Please create a user first.'
            ], 404);
        }

        $category = new Category();
        $category->setName('Electronics');
        $category->setDescription('Electronic devices and gadgets');
        $category->setCreatedBy($user); // Passez l'objet User, pas un ID

        $entityManager->persist($category);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Saved new Category',
            'id' => $category->getId(),
            'name' => $category->getName(),
            'description' => $category->getDescription(),
            'created_by_id' => $category->getCreatedBy()->getId(),
            'created_by_name' => $category->getCreatedBy()->getName(),
        ]);
    }

    ////////////////////////////Displaying/////////////////////////////////
    #[Route('/category/show/{id}', name: 'app_category_show')]
    public function show(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $category = $entityManager
            ->getRepository(Category::class)
            ->find($id);

        if (!$category) {
            return new JsonResponse([
                'error' => 'Category not found'
            ], 404);
        }

        // products if necessary
        $productsData = [];
        foreach ($category->getProducts() as $product) {
            $productsData[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
            ];
        }

        return new JsonResponse([
            'id' => $category->getId(),
            'name' => $category->getName(),
            'description' => $category->getDescription(),
            'created_by_id' => $category->getCreatedBy() ? $category->getCreatedBy()->getId() : null,
            'created_by_name' => $category->getCreatedBy() ? $category->getCreatedBy()->getName() : null,
            'products_count' => count($productsData),
            'products' => $productsData,
        ]);
    }

    ////////////////////////////////Editing//////////////////////////////////////
    #[Route('/category/edit/{id}', name: 'app_category_edit')]
    public function editCategory(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $category = $entityManager->getRepository(Category::class)->find($id);
        if (!$category) {
            return new JsonResponse([
                'error' => 'Category not found'
            ], 404);
        }


        $user = $entityManager->getRepository(User::class)->find(2);
        if (!$user) {
            $user = $category->getCreatedBy();
        }

        $category->setName('Updated Category Name');
        $category->setDescription('Updated description');
        $category->setCreatedBy($user);

        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Category updated',
            'id' => $category->getId(),
            'name' => $category->getName(),
            'description' => $category->getDescription(),
            'created_by_id' => $category->getCreatedBy()->getId(),
        ]);
    }

    ///////////////////////////Deleting///////////////////////////////////
    #[Route('/category/delete/{id}', name: 'app_category_delete')]
    public function deleteCategory(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $category = $entityManager->getRepository(Category::class)->find($id);
        if (!$category) {
            return new JsonResponse([
                'error' => 'Category not found'
            ], 404);
        }

        // Vérifier s'il y a des produits associés
        if ($category->getProducts()->count() > 0) {
            return new JsonResponse([
                'error' => 'Cannot delete category with associated products',
                'products_count' => $category->getProducts()->count(),
            ], 400);
        }

        $entityManager->remove($category);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Category deleted',
            'id' => $id,
        ]);
    }
}
