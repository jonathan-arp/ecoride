<?php
require 'vendor/autoload.php';

use App\Kernel;
use App\Entity\User;

// Créer le kernel en mode dev
$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

$entityManager = $container->get('doctrine.orm.entity_manager');
$userRepository = $entityManager->getRepository(User::class);

echo "=== TEST DE SERIALISATION USER ===\n\n";

// Récupérer un utilisateur (change l'ID selon tes données)
$users = $userRepository->findAll();
if (empty($users)) {
    echo "Aucun utilisateur trouvé dans la base de données.\n";
    exit;
}

$user = $users[0]; // Prendre le premier utilisateur
echo "Utilisateur testé: " . $user->getEmail() . " (ID: " . $user->getId() . ")\n";

// Informations sur les collections
echo "Collections chargées:\n";
echo "- Cars: " . $user->getCars()->count() . "\n";
echo "- Carshares: " . $user->getCarshares()->count() . "\n";
echo "- Credits: " . $user->getCredits()->count() . "\n";
echo "- Reservations: " . $user->getReservations()->count() . "\n";
echo "- Parameters: " . $user->getParameters()->count() . "\n";
echo "- Received Reviews: " . $user->getReceivedReviews()->count() . "\n";
echo "- Given Reviews: " . $user->getGivenReviews()->count() . "\n";

echo "\n=== TEST DE SERIALISATION ===\n";

// Test 1: Sérialisation complète
try {
    echo "1. Test sérialisation complète...\n";
    $startTime = microtime(true);
    $serialized = serialize($user);
    $serializeTime = microtime(true) - $startTime;
    echo "   ✓ Sérialisation OK (" . round($serializeTime * 1000, 2) . "ms)\n";
    echo "   Taille sérialisée: " . strlen($serialized) . " octets\n";
    
    // Test désérialisation
    $startTime = microtime(true);
    $unserialized = unserialize($serialized);
    $deserializeTime = microtime(true) - $startTime;
    echo "   ✓ Désérialisation OK (" . round($deserializeTime * 1000, 2) . "ms)\n";
    
} catch (Exception $e) {
    echo "   ✗ ERREUR: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

// Test 2: Simulation session Symfony
echo "\n2. Test simulation session Symfony...\n";
try {
    // Simuler ce que fait Symfony avec les sessions
    session_start();
    $_SESSION['user_test'] = $user;
    echo "   ✓ Stockage en session OK\n";
    
    $userFromSession = $_SESSION['user_test'];
    echo "   ✓ Récupération depuis session OK\n";
    echo "   Email récupéré: " . $userFromSession->getEmail() . "\n";
    
} catch (Exception $e) {
    echo "   ✗ ERREUR SESSION: " . $e->getMessage() . "\n";
}

// Test 3: Analyser les propriétés __sleep
echo "\n3. Test des propriétés __sleep...\n";
$sleepProperties = $user->__sleep();
echo "   Propriétés sérialisées (" . count($sleepProperties) . "):\n";
foreach ($sleepProperties as $prop) {
    echo "   - " . $prop . "\n";
}

echo "\n=== RECOMMANDATIONS ===\n";
if (count($sleepProperties) > 10) {
    echo "⚠️  Beaucoup de propriétés sérialisées (" . count($sleepProperties) . ")\n";
    echo "   Recommandation: Réduire avec __sleep() optimisé\n";
}

$totalCollections = $user->getCars()->count() + $user->getCarshares()->count() + 
                   $user->getCredits()->count() + $user->getReservations()->count() + 
                   $user->getReceivedReviews()->count() + $user->getGivenReviews()->count();

if ($totalCollections > 50) {
    echo "⚠️  Beaucoup d'éléments dans les collections (" . $totalCollections . ")\n";
    echo "   Recommandation: Éviter de sérialiser les collections\n";
}

echo "\n=== FIN DU TEST ===\n";
