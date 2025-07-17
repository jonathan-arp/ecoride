<?php
// Test simple de sérialisation sans démarrer le kernel Symfony complet
require_once 'vendor/autoload.php';

// Créer une instance manuelle d'un User pour simuler
class MockUser {
    public $id = 1;
    public $email = "admin@test.com";
    public $roles = ["ROLE_ADMIN"];
    public $firstname = "John";
    public $lastname = "Doe";
    
    // Simuler des collections lourdes
    public $cars = [];
    public $carshares = [];
    public $credits = [];
    public $reservations = [];
    public $receivedReviews = [];
    public $givenReviews = [];
    public $parameters = [];
    
    public function __construct() {
        // Simuler beaucoup de données
        for ($i = 0; $i < 10; $i++) {
            $this->cars[] = (object)['id' => $i, 'brand' => 'Toyota', 'model' => 'Prius'];
            $this->carshares[] = (object)['id' => $i, 'departure' => 'Paris', 'arrival' => 'Lyon'];
            $this->credits[] = (object)['id' => $i, 'amount' => 10.0, 'type' => 'WELCOME'];
            $this->reservations[] = (object)['id' => $i, 'status' => 'CONFIRMED'];
            $this->receivedReviews[] = (object)['id' => $i, 'rating' => 5, 'comment' => 'Excellent'];
            $this->givenReviews[] = (object)['id' => $i, 'rating' => 4, 'comment' => 'Good'];
            $this->parameters[] = (object)['id' => $i, 'name' => 'param_' . $i];
        }
    }
    
    // Simuler __sleep actuel (toutes les propriétés sauf photoFile)
    public function __sleep(): array {
        return ['id', 'email', 'roles', 'firstname', 'lastname', 'cars', 'carshares', 'credits', 
                'reservations', 'receivedReviews', 'givenReviews', 'parameters'];
    }
    
    // Simuler __sleep optimisé (seulement les données essentielles)
    public function __sleepOptimized(): array {
        return ['id', 'email', 'roles', 'firstname', 'lastname'];
    }
}

echo "=== TEST DE SERIALISATION MOCK USER ===\n\n";

$user = new MockUser();

// Calculer la taille des données
$dataSize = count($user->cars) + count($user->carshares) + count($user->credits) + 
            count($user->reservations) + count($user->receivedReviews) + 
            count($user->givenReviews) + count($user->parameters);

echo "Données simulées:\n";
echo "- Total éléments dans collections: " . $dataSize . "\n";
echo "- Cars: " . count($user->cars) . "\n";
echo "- Carshares: " . count($user->carshares) . "\n";
echo "- Credits: " . count($user->credits) . "\n";
echo "- Reservations: " . count($user->reservations) . "\n";
echo "- Reviews reçues: " . count($user->receivedReviews) . "\n";
echo "- Reviews données: " . count($user->givenReviews) . "\n";
echo "- Parameters: " . count($user->parameters) . "\n\n";

// Test 1: Sérialisation complète (actuelle)
echo "=== TEST 1: SÉRIALISATION COMPLÈTE (ACTUELLE) ===\n";
try {
    $startTime = microtime(true);
    $serialized = serialize($user);
    $serializeTime = microtime(true) - $startTime;
    
    echo "✓ Sérialisation OK (" . round($serializeTime * 1000, 2) . "ms)\n";
    echo "Taille sérialisée: " . number_format(strlen($serialized)) . " octets\n";
    
    $startTime = microtime(true);
    $unserialized = unserialize($serialized);
    $deserializeTime = microtime(true) - $startTime;
    echo "✓ Désérialisation OK (" . round($deserializeTime * 1000, 2) . "ms)\n";
    
} catch (Exception $e) {
    echo "✗ ERREUR: " . $e->getMessage() . "\n";
}

// Test 2: Simulation session
echo "\n=== TEST 2: SIMULATION SESSION ===\n";
try {
    session_start();
    
    $startTime = microtime(true);
    $_SESSION['user_test'] = $user;
    $sessionTime = microtime(true) - $startTime;
    echo "✓ Stockage en session OK (" . round($sessionTime * 1000, 2) . "ms)\n";
    
    $startTime = microtime(true);
    $userFromSession = $_SESSION['user_test'];
    $retrieveTime = microtime(true) - $startTime;
    echo "✓ Récupération OK (" . round($retrieveTime * 1000, 2) . "ms)\n";
    
} catch (Exception $e) {
    echo "✗ ERREUR SESSION: " . $e->getMessage() . "\n";
}

// Test 3: Sérialisation optimisée
echo "\n=== TEST 3: SÉRIALISATION OPTIMISÉE ===\n";

// Simuler __sleep optimisé
class OptimizedUser extends MockUser {
    public function __sleep(): array {
        return ['id', 'email', 'roles', 'firstname', 'lastname'];
    }
}

$optimizedUser = new OptimizedUser();

try {
    $startTime = microtime(true);
    $serialized = serialize($optimizedUser);
    $serializeTime = microtime(true) - $startTime;
    
    echo "✓ Sérialisation optimisée OK (" . round($serializeTime * 1000, 2) . "ms)\n";
    echo "Taille sérialisée: " . number_format(strlen($serialized)) . " octets\n";
    
    $startTime = microtime(true);
    $unserialized = unserialize($serialized);
    $deserializeTime = microtime(true) - $startTime;
    echo "✓ Désérialisation optimisée OK (" . round($deserializeTime * 1000, 2) . "ms)\n";
    
} catch (Exception $e) {
    echo "✗ ERREUR: " . $e->getMessage() . "\n";
}

// Test 4: Comparaison des tailles
echo "\n=== COMPARAISON DES TAILLES ===\n";
$fullSize = strlen(serialize($user));
$optimizedSize = strlen(serialize($optimizedUser));
$reduction = (($fullSize - $optimizedSize) / $fullSize) * 100;

echo "Taille complète: " . number_format($fullSize) . " octets\n";
echo "Taille optimisée: " . number_format($optimizedSize) . " octets\n";
echo "Réduction: " . round($reduction, 1) . "%\n";

echo "\n=== CONCLUSIONS ===\n";
if ($fullSize > 10000) {
    echo "⚠️  La sérialisation complète est très lourde (" . number_format($fullSize) . " octets)\n";
}
if ($reduction > 80) {
    echo "✓ L'optimisation avec __sleep() apporte une réduction significative\n";
}
echo "💡 Recommandation: Utiliser __sleep() optimisé pour les sessions\n";
