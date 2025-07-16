<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\DBAL\Connection;

class SessionTestController extends AbstractController
{
    #[Route('/test-session-db', name: 'app_test_session_db')]
    public function testSessionDatabase(Request $request, Connection $connection): Response
    {
        // Start a session
        $session = $request->getSession();
        $session->set('test_key', 'Session stockée en BDD - ' . date('Y-m-d H:i:s'));
        
        // Check if session is stored in database
        try {
            $sessionId = $session->getId();
            $result = $connection->fetchAssociative(
                'SELECT sess_id, sess_time, sess_lifetime FROM sessions WHERE sess_id = ?',
                [$sessionId]
            );
            
            if ($result) {
                $message = sprintf(
                    'Session active trouvée en BDD ! ID: %s, Créée: %s, Expire dans: %d secondes',
                    $result['sess_id'],
                    date('Y-m-d H:i:s', $result['sess_time']),
                    $result['sess_lifetime']
                );
                $status = 'success';
            } else {
                $message = 'Session non trouvée en BDD. Vérifiez la configuration.';
                $status = 'error';
            }
            
            // Count total sessions
            $totalSessions = $connection->fetchOne('SELECT COUNT(*) FROM sessions');
            $message .= sprintf(' | Total sessions actives: %d', $totalSessions);
            
        } catch (\Exception $e) {
            $message = 'Erreur lors de la vérification: ' . $e->getMessage();
            $status = 'error';
        }
        
        return $this->render('base.html.twig', [
            'body' => sprintf('<div class="alert alert-%s">%s</div>', $status, $message)
        ]);
    }
}
