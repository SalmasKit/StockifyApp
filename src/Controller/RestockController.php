<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RestockController extends AbstractController
{
    #[Route('/restock', name: 'app_restock_page')]
    public function index(): Response
    {
        return $this->render('restock/restock.html.twig');
    }
}
