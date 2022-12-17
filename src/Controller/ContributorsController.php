<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContributorsController extends AbstractController
{
    #[Route('/contributors', name: 'app_contributors')]
    public function index(): Response
    {
        return $this->render('contributors/index.html.twig', [
            'controller_name' => 'ContributorsController',
        ]);
    }
}
