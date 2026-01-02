<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RestockController extends AbstractController
{
    #[Route('/restock', name: 'app_restock_page', methods: ['GET'])]
    public function index(ProductRepository $productRepo): Response
    {
        // Sort products by quantity (low stock first) to help the manager
        return $this->render('restock/restock.html.twig', [
            'products' => $productRepo->findBy([], ['quantity' => 'ASC']),
        ]);
    }

    #[Route('/restock/confirm', name: 'app_restock_confirm', methods: ['POST'])]
    public function confirm(Request $request, ProductRepository $repo, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);
        $items = $data['items'] ?? [];

        foreach ($items as $itemData) {
            $product = $repo->find($itemData['id']);
            if (!$product) continue;

            $addedQty = (float)$itemData['amount'];
            if ($addedQty <= 0) continue;

            // Increment Stock Logic
            $product->setQuantity($product->getQuantity() + $addedQty);
            $product->setUpdatedAt(new \DateTime());
        }

        $em->flush();
        return new Response("Stock updated successfully", 200);
    }
}
