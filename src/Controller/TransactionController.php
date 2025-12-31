<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class TransactionController extends AbstractController
{
    #[Route('/transaction', name: 'app_transaction_index')]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $transactions = $entityManager
            ->getRepository(Transaction::class)
            ->findAll();

        $data = [];
        foreach ($transactions as $transaction) {
            $data[] = [
                'id' => $transaction->getId(),
                'type' => $transaction->getType(),
                'quantity' => $transaction->getQuantity(),
                'created_at' => $transaction->getCreatedAt()->format('Y-m-d H:i:s'),
                'product_id' => $transaction->getProduct() ? $transaction->getProduct()->getId() : null,
            ];
        }

        return new JsonResponse($data);
    }

    //////////////////////////Creation////////////////////////////////
    #[Route('/transaction/create', name: 'app_transaction_create')]
    public function createTransaction(EntityManagerInterface $entityManager): JsonResponse
    {
        $product = $entityManager->getRepository(Product::class)->find(2);
        if (!$product) {
            return new JsonResponse([
                'error' => 'Product not found'
            ], 404);
        }

        $transaction = new Transaction();
        $transaction->setType('in');
        $transaction->setQuantity(5);
        $transaction->setCreatedAt(new \DateTime());
        $transaction->setProduct($product);

        $entityManager->persist($transaction);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Saved new Transaction',
            'id' => $transaction->getId(),
            'type' => $transaction->getType(),
            'quantity' => $transaction->getQuantity(),
            'product_id' => $transaction->getProduct()->getId(),
        ]);
    }

    ////////////////////////////Displaying/////////////////////////////////
    #[Route('/transaction/show/{id}', name: 'app_transaction_show')]
    public function show(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $transaction = $entityManager
            ->getRepository(Transaction::class)
            ->find($id);

        if (!$transaction) {
            return new JsonResponse([
                'error' => 'Transaction not found'
            ], 404);
        }

        return new JsonResponse([
            'id' => $transaction->getId(),
            'type' => $transaction->getType(),
            'quantity' => $transaction->getQuantity(),
            'created_at' => $transaction->getCreatedAt()->format('Y-m-d H:i:s'),
            'product_id' => $transaction->getProduct() ? $transaction->getProduct()->getId() : null,
        ]);
    }

    ////////////////////////////////Editing//////////////////////////////////////
    #[Route('/transaction/edit/{id}', name: 'app_transaction_edit')]
    public function editTransaction(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $transaction = $entityManager->getRepository(Transaction::class)->find($id);
        if (!$transaction) {
            return new JsonResponse([
                'error' => 'Transaction not found'
            ], 404);
        }

        $transaction->setQuantity(10);
        $transaction->setType('out');
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Transaction updated',
            'id' => $transaction->getId(),
            'quantity' => $transaction->getQuantity(),
            'type' => $transaction->getType(),
        ]);
    }

    ///////////////////////////Deleting///////////////////////////////////
    #[Route('/transaction/delete/{id}', name: 'app_transaction_delete')]
    public function deleteTransaction(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $transaction = $entityManager->getRepository(Transaction::class)->find($id);
        if (!$transaction) {
            return new JsonResponse([
                'error' => 'Transaction not found'
            ], 404);
        }

        $entityManager->remove($transaction);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Transaction deleted',
            'id' => $id,
        ]);
    }
}
