<?php

namespace App\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Psr\Log\LoggerInterface;

class SessionDebugListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        
        // Seulement pour les routes de réservation et account
        if (!$route || (!str_contains($route, 'reservation') && !str_contains($route, 'account'))) {
            return;
        }

        $user = $this->tokenStorage->getToken()?->getUser();
        $sessionId = $request->getSession()->getId();
        
        $this->logger->info('[SessionDebug] AVANT ' . $route, [
            'route' => $route,
            'user_logged' => $user ? 'OUI' : 'NON',
            'user_id' => ($user instanceof \App\Entity\User) ? $user->getId() : null,
            'session_id' => substr($sessionId, 0, 8),
            'transaction_active' => $this->entityManager->getConnection()->isTransactionActive() ? 'OUI' : 'NON'
        ]);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        
        // Seulement pour les routes de réservation et account
        if (!$route || (!str_contains($route, 'reservation') && !str_contains($route, 'account'))) {
            return;
        }

        $user = $this->tokenStorage->getToken()?->getUser();
        $sessionId = $request->getSession()->getId();
        
        $this->logger->info('[SessionDebug] APRÈS ' . $route, [
            'route' => $route,
            'user_logged' => $user ? 'OUI' : 'NON',
            'user_id' => ($user instanceof \App\Entity\User) ? $user->getId() : null,
            'session_id' => substr($sessionId, 0, 8),
            'transaction_active' => $this->entityManager->getConnection()->isTransactionActive() ? 'OUI' : 'NON',
            'response_status' => $event->getResponse()->getStatusCode()
        ]);
    }
}
