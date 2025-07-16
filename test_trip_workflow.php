<?php

// Quick test script for our new trip workflow functionality

require_once __DIR__ . '/vendor/autoload.php';

use App\Entity\PlatformTransaction;
use App\Entity\User;
use App\Entity\Carshare;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

$kernel = new \App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

/** @var EntityManagerInterface $entityManager */
$entityManager = $container->get('doctrine.orm.entity_manager');

echo "Testing Trip Workflow Entities...\n\n";

// Test PlatformTransaction entity
echo "1. Testing PlatformTransaction entity...\n";
try {
    $userRepo = $entityManager->getRepository(User::class);
    $users = $userRepo->findAll();
    
    if (count($users) >= 2) {
        $fromUser = $users[0];
        $toUser = $users[1];
        
        // Create a test transaction
        $transaction = new PlatformTransaction(
            $fromUser,
            $toUser,
            10.50,
            null, // No reservation for this test
            'Test transaction'
        );
        
        echo "✓ PlatformTransaction created successfully\n";
        echo "  - Status: " . $transaction->getStatus() . "\n";
        echo "  - Amount: " . $transaction->getAmount() . "\n";
        echo "  - From: " . $transaction->getFromUser()->getEmail() . "\n";
        echo "  - To: " . $transaction->getToUser()->getEmail() . "\n";
        
        // Test processing
        $initialFromBalance = $fromUser->getCreditBalance();
        $initialToBalance = $toUser->getCreditBalance();
        
        echo "  - Initial balances: From={$initialFromBalance}, To={$initialToBalance}\n";
        
        if ($fromUser->canAfford($transaction->getAmount())) {
            $transaction->process();
            echo "✓ Transaction processed successfully\n";
            echo "  - New status: " . $transaction->getStatus() . "\n";
            echo "  - New balances: From={$fromUser->getCreditBalance()}, To={$toUser->getCreditBalance()}\n";
        } else {
            echo "⚠ User cannot afford transaction, skipping process test\n";
        }
        
    } else {
        echo "⚠ Not enough users in database to test PlatformTransaction\n";
    }
} catch (\Exception $e) {
    echo "✗ Error testing PlatformTransaction: " . $e->getMessage() . "\n";
}

echo "\n2. Testing Carshare trip status methods...\n";
try {
    $carshareRepo = $entityManager->getRepository(Carshare::class);
    $carshares = $carshareRepo->findAll();
    
    if (count($carshares) > 0) {
        $carshare = $carshares[0];
        
        echo "✓ Found carshare: " . $carshare->getFormattedRoute() . "\n";
        echo "  - Current trip status: " . ($carshare->getTripStatus() ?? 'null') . "\n";
        echo "  - Can be started: " . ($carshare->canBeStarted() ? 'yes' : 'no') . "\n";
        echo "  - Is waiting for validation: " . ($carshare->isWaitingForValidation() ? 'yes' : 'no') . "\n";
        
    } else {
        echo "⚠ No carshares found in database\n";
    }
} catch (\Exception $e) {
    echo "✗ Error testing Carshare: " . $e->getMessage() . "\n";
}

echo "\n3. Testing Reservation validation methods...\n";
try {
    $reservationRepo = $entityManager->getRepository(Reservation::class);
    $reservations = $reservationRepo->findAll();
    
    if (count($reservations) > 0) {
        $reservation = $reservations[0];
        
        echo "✓ Found reservation for: " . $reservation->getCarshare()->getFormattedRoute() . "\n";
        echo "  - Passenger validated: " . ($reservation->isPassengerValidated() ? 'yes' : 'no') . "\n";
        echo "  - Can be validated: " . ($reservation->canBeValidated() ? 'yes' : 'no') . "\n";
        
    } else {
        echo "⚠ No reservations found in database\n";
    }
} catch (\Exception $e) {
    echo "✗ Error testing Reservation: " . $e->getMessage() . "\n";
}

echo "\n✓ All entity tests completed!\n";
