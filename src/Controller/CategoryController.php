<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\User;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class CategoryController extends AbstractController
{
    #[Route('/category', name: 'app_category_index', methods: ['GET', 'POST'])]
    public function index(Request $request, CategoryRepository $repository, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // 1. Simple Security: If admin hasn't activated the user, block them
        if (!$user->isActive()) {
            $this->addFlash('warning', 'Your account is pending administrator approval.');
            return $this->redirectToRoute('app_login');
        }

        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Track who created it
            $category->setCreatedBy($user);

            $em->persist($category);
            $em->flush();

            $this->addFlash('success', 'Category created successfully!');
            return $this->redirectToRoute('app_category_index');
        }

        return $this->render('category/categories_list.html.twig', [
            // we show all categories to all active users/work for same association
            'categories' => $repository->findAll(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/category/edit/{id}', name: 'app_category_edit', methods: ['POST'])]
    public function editCategory(Request $request, Category $category, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Check activation
        if (!$user->isActive()) {
            throw $this->createAccessDeniedException('Account inactive.');
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Category updated successfully!');
        }

        return $this->redirectToRoute('app_category_index');
    }

    #[Route('/category/delete/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function deleteCategory(Request $request, Category $category, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isActive()) {
            throw $this->createAccessDeniedException();
        }

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
