<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Transaction;

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

        if (empty($items)) {
            return new Response("No items provided", 400);
        }

        foreach ($items as $itemData) {
            $product = $repo->find($itemData['id']);
            if (!$product) continue;

            $addedQty = (float)$itemData['amount'];
            if ($addedQty <= 0) continue;

            // 1. Update Product Stock
            $product->setQuantity($product->getQuantity() + $addedQty);
            $product->setUpdatedAt(new \DateTime());

            // 2. CREATE THE TRANSACTION LOG (This was missing)
            $transaction = new Transaction();
            $transaction->setProduct($product);
            $transaction->setQuantity($addedQty);
            $transaction->setType('RESTOCK'); // Matches your Twig logic
            $transaction->setCreatedAt(new \DateTime());

            // 3. Persist the transaction
            $em->persist($transaction);
        }

        // 4. Flush everything (Updates products AND inserts new transaction rows)
        $em->flush();

        return new Response("Stock and Ledger updated successfully", 200);
    }
}
