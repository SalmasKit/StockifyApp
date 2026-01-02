<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DonationController extends AbstractController
{
    #[Route('/donations', name: 'app_donation_page', methods: ['GET'])]
    public function index(ProductRepository $productRepo): Response
    {
        return $this->render('donation/donations.html.twig', [
            'products' => $productRepo->findAll(),
        ]);
    }

    #[Route('/donation/confirm', name: 'app_donation_confirm', methods: ['POST'])]
    public function confirm(Request $request, ProductRepository $repo, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);
        $items = $data['items'] ?? [];

        if (empty($items)) {
            return new Response("No items selected.", 400);
        }

        foreach ($items as $itemData) {
            $product = $repo->find($itemData['id']);
            if (!$product) continue;

            $askedQty = (float)$itemData['amount'];
            $currentQty = (float)$product->getQuantity();

            if ($askedQty > $currentQty) {
                return new Response("Insufficient stock for " . $product->getName(), 400);
            }

            if ($askedQty <= 0) {
                return new Response("Invalid quantity for " . $product->getName(), 400);
            }

            $product->setQuantity($currentQty - $askedQty);
            $product->setUpdatedAt(new \DateTime());
        }

        $em->flush();
        return new Response("Success", 200);
    }
}
