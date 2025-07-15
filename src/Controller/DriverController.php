<?php

namespace App\Controller;

use App\Entity\Parameter;
use App\Form\ParameterType;
use App\Repository\ParameterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/account/driver')]
class DriverController extends AbstractController
{
    #[Route('/parameters', name: 'app_driver_parameters')]
    public function parameters(ParameterRepository $parameterRepository): Response
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof \App\Entity\User) {
            $this->addFlash('danger', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }
        $userParameters = $user->getParameters();
        $allParameters = $parameterRepository->findAll();

        return $this->render('account/driver/parameters.html.twig', [
            'userParameters' => $userParameters,
            'allParameters' => $allParameters,
        ]);
    }

    #[Route('/parameters/{id}/toggle', name: 'app_driver_parameter_toggle', methods: ['POST'])]
    public function toggleParameter(Parameter $parameter, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof \App\Entity\User) {
            $this->addFlash('danger', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->getParameters()->contains($parameter)) {
            $user->removeParameter($parameter);
            $this->addFlash('success', 'Paramètre "' . $parameter->getName() . '" retiré de vos préférences.');
        } else {
            $user->addParameter($parameter);
            $this->addFlash('success', 'Paramètre "' . $parameter->getName() . '" ajouté à vos préférences.');
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_driver_parameters');
    }

    #[Route('/parameters/new', name: 'app_driver_parameter_new')]
    public function newParameter(Request $request, EntityManagerInterface $entityManager): Response
    {
        $parameter = new Parameter();
        $form = $this->createForm(ParameterType::class, $parameter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($parameter);
            
            // Automatically add the new parameter to the current user
            $user = $this->getUser();
            if (!$user || !$user instanceof \App\Entity\User) {
                $this->addFlash('danger', 'Vous devez être connecté.');
                return $this->redirectToRoute('app_login');
            }
            
            $user->addParameter($parameter);
            
            $entityManager->flush();

            $this->addFlash('success', 'Nouveau paramètre "' . $parameter->getName() . '" créé et ajouté à vos préférences.');

            return $this->redirectToRoute('app_driver_parameters');
        }

        return $this->render('account/driver/parameter_new.html.twig', [
            'parameter' => $parameter,
            'form' => $form,
        ]);
    }
}
