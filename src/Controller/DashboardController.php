<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        ProductRepository $productRepo,
        CategoryRepository $categoryRepo,
        TransactionRepository $transactionRepo
    ): Response {


        $allProducts = $productRepo->findAll();
        $totalProducts = count($allProducts);
        $totalCategories = $categoryRepo->count([]);
        $totalTransactions = $transactionRepo->count([]);

        // Calculate Total flouss
        $totalValue = 0;
        foreach ($allProducts as $product) {
            $totalValue += ($product->getPrice() * $product->getQuantity());
        }

        //CHART: INVENTORY BY CATEGORY
        $categories = $categoryRepo->findAll();
        $catLabels = [];
        $catValues = [];

        foreach ($categories as $cat) {
            $catLabels[] = $cat->getName();
            // Count products associated with this category
            $catValues[] = count($cat->getProducts());
        }

        // --- 3. LATEST TRANSACTIONS ---
        // Fetching the last 5 transactions with a Join to avoid N+1 query issues
        $latestTransactions = $transactionRepo->createQueryBuilder('t')
            ->leftJoin('t.product', 'p')
            ->addSelect('p')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // --- 4. RIGHT SIDE: STOCK STATUS COUNTS ---

        // Normal Stock: Quantity >= 5
        $normalCount = $productRepo->createQueryBuilder('p')
            ->select('count(p.id)')
            ->where('p.quantity >= 5')
            ->getQuery()
            ->getSingleScalarResult();

        // Low Stock: 1 <= Quantity < 5
        $lowCount = $productRepo->createQueryBuilder('p')
            ->select('count(p.id)')
            ->where('p.quantity < 5')
            ->andWhere('p.quantity > 0')
            ->getQuery()
            ->getSingleScalarResult();

        // Rupture (Out of Stock): Quantity = 0
        $ruptureCount = $productRepo->createQueryBuilder('p')
            ->select('count(p.id)')
            ->where('p.quantity <= 0')
            ->getQuery()
            ->getSingleScalarResult();

        // --- 5. URGENT REMINDERS (The "Most Rupture" products) ---
        $alertProducts = $productRepo->createQueryBuilder('p')
            ->where('p.quantity < 5')
            ->orderBy('p.quantity', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // --- 6. RENDER EVERYTHING TO TWIG ---
        return $this->render('dashboard/dashboard.html.twig', [
            // Top Cards
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'totalValue' => $totalValue,
            'totalTransactions' => $totalTransactions,

            // Chart Data
            'catLabels' => $catLabels,
            'catValues' => $catValues,

            // Transactions List
            'latestTransactions' => $latestTransactions,

            // Right Sidebar Stats
            'normalCount' => $normalCount,
            'lowCount' => $lowCount,
            'ruptureCount' => $ruptureCount,
            'alertProducts' => $alertProducts,
        ]);
    }
}
