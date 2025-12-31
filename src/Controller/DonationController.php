<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DonationController extends AbstractController
{
    #[Route('/donations', name: 'app_donation_page')]
    public function index(): Response
    {
        return $this->render('donation/donations.html.twig');
    }
}
