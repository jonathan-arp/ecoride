<?php

namespace App\Controller;

use App\Form\CarshareSearchType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        $searchForm = $this->createForm(CarshareSearchType::class, null, [
            'action' => $this->generateUrl('app_carshare_search'),
            'method' => 'POST'
        ]);

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'searchForm' => $searchForm->createView(),
        ]);
    }
}
