<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Category;
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

final class ProductController extends AbstractController
{
    #[Route('/product', name: 'app_product_index')]
    public function index(Request $request, ProductRepository $repository): Response
    {
        $sort = $request->query->get('sort', 'desc'); // Default newest first
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
        $product = new Product();

     //symfony maps form fields to $product
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

            $this->addFlash('success', 'Product created successfully!');
            //redirection
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

                // call flush. Doctrine will automatically detect if
                // Name, Price, Quantity, etc., actually changed.
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
        //Security check: Verify the CSRF token to prevent malicious deletions
        if($this->isCsrfTokenValid('delete'.$product->getId(), $request->get('_token'))){

            //can t delete product used in transactions
            if($product->getTransactions()->count() > 0){
                $this->addFlash('warning', 'Product cannot be deleted because it has transactions');
            }else{

                //1.delete img file
                if ($product->getImageUrl()) {
                    $this->deleteImage($product->getImageUrl());
                }

                //2.remove entity
                $em->remove($product);

                //3.flush db
                $em->flush();
                $this->addFlash('success', 'Product deleted successfully!');
            }
        }
        return $this->redirectToRoute('app_product_index');
    }



    /////////////////////Bulk delete : used when checkbox selectioon////////////
    #[Route('/product/bulk-delete', name: 'app_product_bulk_delete', methods: ['POST'])]
    public function bulkDelete(Request $request, EntityManagerInterface $em): Response
    {
        $idsString = $request->request->get('ids');
        if ($idsString) {
            $ids = explode(',', $idsString);
            $products = $em->getRepository(Product::class)->findBy(['id' => $ids]);

            foreach ($products as $product) {
                // 1.skip prods w transactions
                if ($product->getTransactions()->count() === 0) {
                    if ($product->getImageUrl()) {

                        //2.delete image
                        $this->deleteImage($product->getImageUrl()); // Using our cleanup method
                    }

                    //3.remove entity
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
        // Logic: if stock set to 0 |if 0 set to 1, used for switch buttons
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
        // 1. Get filters from the URL (Search, Sort, etc.)
        $sort = $request->query->get('sort', 'desc');
        // can add more filters
        $products = $repository->findBy([], ['createdAt' => $sort]);

        $response = new StreamedResponse(function() use ($products) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Product Inventory');

            // 2. Define Headers
            $headers = ['ID', 'Photo', 'Product Name', 'Description', 'Category', 'Price (MAD)', 'Quantity', 'Date Added'];
            $columnLetter = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($columnLetter . '1', $header);
                $columnLetter++;
            }

            // Style Headers (Bold and Gray Background)
            $sheet->getStyle('A1:H1')->getFont()->setBold(true);
            $sheet->getStyle('A1:H1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('F1F5F9');

            // 3. Set Column Widths
            $sheet->getColumnDimension('B')->setWidth(15); // Photo
            $sheet->getColumnDimension('C')->setWidth(25); // Name
            $sheet->getColumnDimension('D')->setWidth(40); // Description
            $sheet->getColumnDimension('E')->setWidth(25);
            $sheet->getColumnDimension('F')->setWidth(25);
            $sheet->getColumnDimension('G')->setWidth(25);
            $sheet->getColumnDimension('H')->setWidth(25);
            $sheet->getStyle('D')->getAlignment()->setWrapText(true); // Wrap long descriptions

            // 4. Fill Data
            $row = 2;
            $sequenceNumber = 1; // Initialize the counter at 1

            foreach ($products as $product) {
                // Text data
                $sheet->setCellValue('A' . $row, $sequenceNumber);
                $sheet->setCellValue('C' . $row, $product->getName());
                $sheet->setCellValue('D' . $row, $product->getDescription());
                $sheet->setCellValue('E' . $row, $product->getCategory() ? $product->getCategory()->getName() : 'N/A');
                $sheet->setCellValue('F' . $row, $product->getPrice());
                $sheet->setCellValue('G' . $row, $product->getQuantity());
                $sheet->setCellValue('H' . $row, $product->getCreatedAt() ? $product->getCreatedAt()->format('Y-m-d') : '');

                // 5. Handle Image Embedding
                if ($product->getImageUrl()) {
                    $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/products/' . $product->getImageUrl();

                    if (file_exists($imagePath)) {
                        $drawing = new Drawing();
                        $drawing->setName($product->getName());
                        $drawing->setPath($imagePath);
                        $drawing->setHeight(50); // Resize image to 50px height
                        $drawing->setCoordinates('B' . $row); // Column B is the Photo column
                        $drawing->setOffsetX(10); // Center the image slightly
                        $drawing->setOffsetY(5);
                        $drawing->setWorksheet($sheet);

                        // Increase row height to fit the image properly
                        $sheet->getRowDimension($row)->setRowHeight(45);
                    }
                } else {
                    $sheet->setCellValue('B' . $row, 'No Image');
                }

                // Align all text to the top so it looks good next to the image
                $sheet->getStyle('A' . $row . ':H' . $row)
                    ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

                $row++;
                $sequenceNumber++; // Increment the counter for the next product
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        // 6. Set Response Headers for Download
        $fileName = 'inventory_' . date('Y-m-d_Hi') . '.xlsx';
        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $fileName);

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    ////////////////////////Helper methods//////////////////////////////
    /// Methode to delete images when image product is deleted/updated
    private function deleteImage(string $fileName): void
    {
        $path = $this->getParameter('kernel.project_dir') . '/public/uploads/products/' . $fileName;
        if (file_exists($path)) {
            unlink($path);
        }
    }

}
