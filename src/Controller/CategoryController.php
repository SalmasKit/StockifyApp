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
        $association = $user->getAssociation();

        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Logic: Tie category to the specific user AND their association
            $category->setCreatedBy($user);
            $category->setAssociation($association);

            $em->persist($category);
            $em->flush();

            $this->addFlash('success', 'Category created successfully!');
            return $this->redirectToRoute('app_category_index');
        }

        return $this->render('category/categories_list.html.twig', [
            // Only show categories belonging to the logged-in user's association
            'categories' => $repository->findBy(['association' => $association]),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/category/edit/{id}', name: 'app_category_edit', methods: ['POST'])]
    public function editCategory(Request $request, Category $category, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Security: Prevent editing categories from other associations
        if ($category->getAssociation() !== $user->getAssociation()) {
            throw $this->createAccessDeniedException('You cannot edit this category.');
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

        // Security: Prevent deleting categories from other associations
        if ($category->getAssociation() !== $user->getAssociation()) {
            throw $this->createAccessDeniedException('You cannot delete this category.');
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
