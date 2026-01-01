<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\User;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CategoryController extends AbstractController
{
    #[Route('/category', name: 'app_category_index', methods: ['GET', 'POST'])]
    public function index(Request $request, CategoryRepository $repository, EntityManagerInterface $em): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // set the loggedin user as the creator
            $category->setCreatedBy($this->getUser());

            $em->persist($category);
            $em->flush();

            $this->addFlash('success', 'Category created successfully!');
            return $this->redirectToRoute('app_category_index');
        }

        return $this->render('category/categories_list.html.twig', [
            'categories' => $repository->findAll(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/category/edit/{id}', name: 'app_category_edit', methods: ['POST'])]
    public function editCategory(Request $request, Category $category, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Category updated successfully!');
        }
        // Always return to the list
        return $this->redirectToRoute('app_category_index');
    }

    #[Route('/category/delete/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function deleteCategory(Request $request, Category $category, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            if ($category->getProducts()->count() > 0) {
                $this->addFlash('warning', 'Category cannot be deleted: it contains products.');
            } else {
                $em->remove($category);
                $em->flush();
                $this->addFlash('success', 'Category deleted.');
            }
        }
        return $this->redirectToRoute('app_category_index');
    }
    }
