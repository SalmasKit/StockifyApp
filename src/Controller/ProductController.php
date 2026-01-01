<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Category;
use App\Entity\User;
use App\Form\ProductType;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ProductController extends AbstractController
{
    #[Route('/product', name: 'app_product_index')]
    public function index(Request $request, ProductRepository $repository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Security Check: Only approved users can see products
        if (!$user->isActive()) {
            $this->addFlash('warning', 'Your account is pending administrator approval.');
            return $this->redirectToRoute('app_login');
        }

        $sort = $request->query->get('sort', 'desc'); // Default newest first

        //  Show all products to all authorized users
        $products = $repository->findBy([], ['createdAt' => $sort]);

        return $this->render('product/products_list.html.twig', [
            'products' => $products,
            'currentSort' => $sort
        ]);
    }

    //////////////////////////Creation////////////////////////////////
    #[Route('/product/create', name: 'app_product_create')]
    public function createProduct(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isActive()) {
            throw $this->createAccessDeniedException('Account inactive.');
        }

        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            /** @var Symfony\Component\HttpFoundation\File\UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/products',
                    $newFilename
                );
                $product->setImageUrl($newFilename);
            }

            $product->setCreatedAt(new \DateTime());
            $product->setUpdatedAt(new \DateTime());
            $product->setCreatedBy($user);

            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Product created successfully!');
            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('product/add_product.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }


    ////////////////////////////////Editing//////////////////////////////////////
    #[Route('/product/edit/{id}', name: 'app_product_edit')]
    public function editProduct(Request $request, Product $product, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isActive()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $imageFile = $form->get('image')->getData();

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

                $product->setUpdatedAt(new \DateTime());
                $em->flush();

                $this->addFlash('success', 'Product updated successfully!');
                return $this->redirectToRoute('app_product_index');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Error: ' . $e->getMessage());
            }
        }

        return $this->render('product/add_product.html.twig', [
            'form' => $form->createView(),
            'product' => $product
        ]);
    }


    ///////////////////////////Deleting///////////////////////////////////
    #[Route('/product/delete/{id}', name: 'app_product_delete')]
    public function deleteProduct(Request $request, Product $product, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isActive()) {
            throw $this->createAccessDeniedException();
        }

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

    /////////////////////Bulk delete///////////////////////////////////
    #[Route('/product/bulk-delete', name: 'app_product_bulk_delete', methods: ['POST'])]
    public function bulkDelete(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isActive()) {
            throw $this->createAccessDeniedException();
        }

        $idsString = $request->request->get('ids');
        if ($idsString) {
            $ids = explode(',', $idsString);
            $products = $em->getRepository(Product::class)->findBy(['id' => $ids]);

            foreach ($products as $product) {
                if ($product->getTransactions()->count() === 0) {
                    if ($product->getImageUrl()) {
                        $this->deleteImage($product->getImageUrl());
                    }
                    $em->remove($product);
                }
            }
            $em->flush();
            $this->addFlash('success', 'Selected products deleted successfully.');
        }

        return $this->redirectToRoute('app_product_index');
    }

    /////////////////////////////toggle stock status(AJAX)///////////////
    #[Route('/product/toggle-stock/{id}', name: 'app_product_toggle_stock', methods: ['POST'])]
    public function toggleStock(Product $product, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isActive()) {
            return new JsonResponse(['success' => false, 'message' => 'Account inactive'], 403);
        }

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

    #[Route('/product/export/excel', name: 'app_product_export_excel')]
    public function exportExcel(Request $request, ProductRepository $repository): StreamedResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isActive()) {
            die("Unauthorized"); // Simple block for export
        }

        $sort = $request->query->get('sort', 'desc');
        $products = $repository->findBy([], ['createdAt' => $sort]);

        $response = new StreamedResponse(function() use ($products) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Inventory');

            $headers = ['ID', 'Photo', 'Product Name', 'Description', 'Category', 'Price (MAD)', 'Quantity', 'Date Added'];
            $columnLetter = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($columnLetter . '1', $header);
                $columnLetter++;
            }

            $sheet->getStyle('A1:H1')->getFont()->setBold(true);

            $row = 2;
            $sequenceNumber = 1;

            foreach ($products as $product) {
                $sheet->setCellValue('A' . $row, $sequenceNumber);
                $sheet->setCellValue('C' . $row, $product->getName());
                $sheet->setCellValue('D' . $row, $product->getDescription());
                $sheet->setCellValue('E' . $row, $product->getCategory() ? $product->getCategory()->getName() : 'N/A');
                $sheet->setCellValue('F' . $row, $product->getPrice());
                $sheet->setCellValue('G' . $row, $product->getQuantity());
                $sheet->setCellValue('H' . $row, $product->getCreatedAt() ? $product->getCreatedAt()->format('Y-m-d') : '');

                if ($product->getImageUrl()) {
                    $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/products/' . $product->getImageUrl();
                    if (file_exists($imagePath)) {
                        $drawing = new Drawing();
                        $drawing->setPath($imagePath);
                        $drawing->setHeight(50);
                        $drawing->setCoordinates('B' . $row);
                        $drawing->setWorksheet($sheet);
                        $sheet->getRowDimension($row)->setRowHeight(45);
                    }
                }
                $row++;
                $sequenceNumber++;
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $fileName = 'inventory_' . date('Y-m-d') . '.xlsx';
        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $fileName);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    private function deleteImage(string $fileName): void
    {
        $path = $this->getParameter('kernel.project_dir') . '/public/uploads/products/' . $fileName;
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
