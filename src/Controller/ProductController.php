<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Category;
use App\Form\ProductType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;

final class ProductController extends AbstractController
{
    #[Route('/product', name: 'app_product_index')]
    public function index(ProductRepository $repository): Response
    {
        return $this->render('product/products_list.html.twig', [
            'products' => $repository->findAll(),
        ]);
    }

    //////////////////////////Creation////////////////////////////////
    #[Route('/product/create', name: 'app_product_create')]
    public function createProduct(Request $request, EntityManagerInterface $em): Response
    {
        $product = new Product();

        //connect to form class(creating form)
        $form = $this->createForm(ProductType::class, $product);

        //inspect $_POST data(handling req)
        $form->handleRequest($request);


        //validation check
        if($form->isSubmitted() && $form->isValid()) {
            //handle image upload
            /** @var Symfony\Component\HttpFoundation\File\UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                // Generate a unique name for the file
                $newFilename = uniqid().'.'.$imageFile->guessExtension();

                // Move the file to the directory
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/products',
                    $newFilename
                );

                // 2. Save the filename string to the database
                $product->setImageUrl($newFilename);
            }

            //manual data entry (cuz it isn t a field in the form / it will be autom. set)
            $product->setCreatedAt(new \DateTime());
            $product->setUpdatedAt(new \DateTime());

            //to db manager: hey keep eye on this obj i want to save it
            $em->persist($product);

            //Save button (runs Insert)
            $em->flush();

            //redirection
            return $this->redirectToRoute('app_product_index');
        }
        return $this->render('product/add_product.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);

    }

    ////////////////////////////Displaying/////////////////////////////////
    #[Route('/product/show/{id}', name: 'app_product_show')]
    public function show(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $product = $entityManager
            ->getRepository(Product::class)
            ->find($id);

        if (!$product) {
            return new JsonResponse([
                'error' => 'Product not found'
            ], 404);
        }

        return new JsonResponse([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'quantity' => $product->getQuantity(),
            'unit' => $product->getUnit(),
            'image_url' => $product->getImageUrl(),
            'created_at' => $product->getCreatedAt() ? $product->getCreatedAt()->format('Y-m-d H:i:s') : null,
            'updated_at' => $product->getUpdatedAt() ? $product->getUpdatedAt()->format('Y-m-d H:i:s') : null,
            'category_id' => $product->getCategory() ? $product->getCategory()->getId() : null,
            'category_name' => $product->getCategory() ? $product->getCategory()->getName() : null,
            'transactions_count' => $product->getTransactions()->count(),
        ]);
    }

    ////////////////////////////////Editing//////////////////////////////////////
    #[Route('/product/edit/{id}', name: 'app_product_edit')]
    public function editProduct(Request $request, Product $product, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $imageFile = $form->get('image')->getData();

                // Handle Image Upload
                if ($imageFile) {
                    if ($product->getImageUrl()) {
                        $this->deleteImage($product->getImageUrl());
                    }

                    $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/products',
                        $newFilename
                    );
                    $product->setImageUrl($newFilename);
                }

                // Always update the timestamp
                $product->setUpdatedAt(new \DateTime());

                // Just call flush. Doctrine will automatically detect if
                // Name, Price, Quantity, etc., actually changed.
                $em->flush();

                $this->addFlash('success', 'Product updated successfully!');
                return $this->redirectToRoute('app_product_index');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Error: ' . $e->getMessage());
            }
        }

        return $this->render('product/edit_product.html.twig', [
            'form' => $form->createView(),
            'product' => $product
        ]);
    }


    ///////////////////////////Deleting///////////////////////////////////
    #[Route('/product/delete/{id}', name: 'app_product_delete')]
    public function deleteProduct(Request $request, Product $product, EntityManagerInterface $em): Response
    {
        //Security check: Verify the CSRF token to prevent malicious deletions
        if($this->isCsrfTokenValid('delete'.$product->getId(), $request->get('_token'))){

            if($product->getTransactions()->count() > 0){
                $this->addFlash('warning', 'Product cannot be deleted because it has transactions');
            }else{

                if ($product->getImageUrl()) {
                    $this->deleteImage($product->getImageUrl());
                }

                $em->remove($product);
                $em->flush();
                $this->addFlash('success', 'Product deleted successfully!');
            }
        }
        return $this->redirectToRoute('app_product_index');
    }



    #[Route('/product/bulk-delete', name: 'app_product_bulk_delete', methods: ['POST'])]
    public function bulkDelete(Request $request, EntityManagerInterface $em): Response
    {
        $idsString = $request->request->get('ids');
        if ($idsString) {
            $ids = explode(',', $idsString);
            $products = $em->getRepository(Product::class)->findBy(['id' => $ids]);

            foreach ($products as $product) {
                // Only delete if no transactions exist (safety first)
                if ($product->getTransactions()->count() === 0) {
                    if ($product->getImageUrl()) {
                        $this->deleteImage($product->getImageUrl()); // Using our cleanup method
                    }
                    $em->remove($product);
                }
            }
            $em->flush();
            $this->addFlash('success', 'Selected products deleted successfully.');
        }

        return $this->redirectToRoute('app_product_index');
    }


    #[Route('/product/toggle-stock/{id}', name: 'app_product_toggle_stock', methods: ['POST'])]
    public function toggleStock(Product $product, EntityManagerInterface $em): JsonResponse
    {
        // Logic: If there is stock, set to 0. If it's 0, set to 1 (or previous)
        $currentQty = (float)$product->getQuantity();
        $newQty = $currentQty > 0 ? 0 : 1;

        $product->setQuantity($newQty);
        $product->setUpdatedAt(new \DateTime());

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'newQty' => $newQty,
            'statusText' => $newQty > 0 ? 'In Stock' : 'Out of Stock'
        ]);
    }



    ////////////////Helper methods//////////////////////////////
    private function deleteImage(string $fileName): void
    {
        $path = $this->getParameter('kernel.project_dir') . '/public/uploads/products/' . $fileName;
        if (file_exists($path)) {
            unlink($path);
        }
    }

}
