<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TransactionController extends AbstractController
{
    #[Route('/transactions', name: 'app_transaction_index')]
    public function index(Request $request, TransactionRepository $trxRepo, CategoryRepository $catRepo): Response
    {
        $search = $request->query->get('search'); // <--- Capture the search input
        $categoryId = $request->query->get('category');
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        return $this->render('transaction/transactions.html.twig', [
            'transactions' => $trxRepo->findByFilters($categoryId, $startDate, $endDate, $search),
            'categories' => $catRepo->findAll(),
            'currentCat' => $categoryId,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
