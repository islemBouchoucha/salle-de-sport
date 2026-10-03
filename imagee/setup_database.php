-- Créer la base de données
CREATE DATABASE gym_center;
USE gym_center;

-- Table des membres
CREATE TABLE membres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    sexe ENUM('homme', 'femme') NOT NULL,
    taille DECIMAL(3,2) NOT NULL,
    poids DECIMAL(5,2) NOT NULL,
    imc DECIMAL(4,2) NOT NULL,
    categorie_imc VARCHAR(50) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    adresse TEXT,
    objectif VARCHAR(100) DEFAULT 'forme',
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des abonnements
CREATE TABLE abonnements (
    id INT AUTO_INCREMENT PRIMA<?php
// setup_database.php
// Ce fichier crée la base de données et toutes les tables automatiquement

// Configuration de connexion MySQL
$host = 'localhost';
$username = 'root';  // Utilisateur par défaut XAMPP/WAMP
$password = '';      // Mot de passe par défaut (vide pour XAMPP/WAMP)

try {
    // Connexion sans base de données spécifique
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Configuration de la base de données GYM CENTER</h2>";
    echo "<div style='font-family: Arial, sans-serif; padding: 20px;'>";
    
    // Créer la base de données
    $pdo->exec("CREATE DATABASE IF NOT EXISTS gym_center CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p style='color: green;'>✅ Base de données 'gym_center' créée avec succès</p>";
    
    // Utiliser la base de données
    $pdo->exec("USE gym_center");
    
    // Table des membres
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS membres (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            age INT NOT NULL,
            sexe ENUM('homme', 'femme') NOT NULL,
            taille DECIMAL(3,2) NOT NULL,
            poids DECIMAL(5,2) NOT NULL,
            imc DECIMAL(4,2) NOT NULL,
            categorie_imc VARCHAR(50) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            telephone VARCHAR(20),
            adresse TEXT,
            objectif VARCHAR(100) DEFAULT 'forme',
            date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p style='color: green;'>✅ Table 'membres' créée avec succès</p>";
    
    // Table des abonnements
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS abonnements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            membre_id INT,
            type VARCHAR(50) NOT NULL,
            prix DECIMAL(10,2) NOT NULL,
            duree INT NOT NULL,
            date_debut TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            date_fin TIMESTAMP NULL,
            statut ENUM('actif', 'inactif') DEFAULT 'actif',
            FOREIGN KEY (membre_id) REFERENCES membres(id) ON DELETE CASCADE
        )
    ");
    echo "<p style='color: green;'>✅ Table 'abonnements' créée avec succès</p>";
    
    // Table des paiements
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS paiements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            membre_id INT,
            abonnement_id INT,
            montant DECIMAL(10,2) NOT NULL,
            methode VARCHAR(50) NOT NULL,
            statut VARCHAR(50) NOT NULL,
            date_paiement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (membre_id) REFERENCES membres(id) ON DELETE CASCADE,
            FOREIGN KEY (abonnement_id) REFERENCES abonnements(id) ON DELETE CASCADE
        )
    ");
    echo "<p style='color: green;'>✅ Table 'paiements' créée avec succès</p>";
    
    // Table historique IMC
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS historique_imc (
            id INT AUTO_INCREMENT PRIMARY KEY,
            membre_id INT,
            imc DECIMAL(4,2) NOT NULL,
            poids DECIMAL(5,2) NOT NULL,
            taille DECIMAL(3,2) NOT NULL,
            categorie VARCHAR(50) NOT NULL,
            date_calcul TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (membre_id) REFERENCES membres(id) ON DELETE CASCADE
        )
    ");
    echo "<p style='color: green;'>✅ Table 'historique_imc' créée avec succès</p>";
    
    // Ajouter des données de démonstration
    echo "<h3>Ajout des données de démonstration...</h3>";
    
    // Hasher un mot de passe de test
    $password_hash = password_hash('123456', PASSWORD_DEFAULT);
    
    // Insérer des membres de démonstration
    $membres_demo = [
        ['Dupont', 'Jean', 28, 'homme', 1.75, 70.5, 23.02, 'Poids normal', 'jean.dupont@email.com'],
        ['Lambert', 'Marie', 32, 'femme', 1.65, 58.2, 21.38, 'Poids normal', 'marie.lambert@email.com'],
        ['Martin', 'Pierre', 45, 'homme', 1.80, 85.0, 26.23, 'Surpoids', 'pierre.martin@email.com'],
        ['Bernard', 'Sophie', 26, 'femme', 1.70, 62.0, 21.45, 'Poids normal', 'sophie.bernard@email.com']
    ];
    
    $membre_ids = [];
    
    foreach ($membres_demo as $membre) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO membres (nom, prenom, age, sexe, taille, poids, imc, categorie_imc, email, password) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        try {
            $stmt->execute([
                $membre[0], $membre[1], $membre[2], $membre[3], 
                $membre[4], $membre[5], $membre[6], $membre[7],
                $membre[8], $password_hash
            ]);
            
            // Récupérer l'ID du membre inséré
            $membre_id = $pdo->lastInsertId();
            if ($membre_id > 0) {
                $membre_ids[] = $membre_id;
            } else {
                // Si l'insertion a échoué (doublon), récupérer l'ID existant
                $stmt = $pdo->prepare("SELECT id FROM membres WHERE email = ?");
                $stmt->execute([$membre[8]]);
                $existing_id = $stmt->fetchColumn();
                if ($existing_id) {
                    $membre_ids[] = $existing_id;
                }
            }
            
        } catch (PDOException $e) {
            // Ignorer les erreurs de doublons
            if ($e->getCode() != 23000) {
                throw $e;
            }
        }
    }
    
    echo "<p style='color: green;'>✅ Membres de démonstration ajoutés</p>";
    
    // Créer des abonnements pour chaque membre
    foreach ($membre_ids as $membre_id) {
        $date_fin = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO abonnements (membre_id, type, prix, duree, date_fin) 
            VALUES (?, 'fitness', 49.00, 30, ?)
        ");
        $stmt->execute([$membre_id, $date_fin]);
    }
    echo "<p style='color: green;'>✅ Abonnements de démonstration créés</p>";
    
    // Créer l'historique IMC
    foreach ($membre_ids as $membre_id) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO historique_imc (membre_id, imc, poids, taille, categorie) 
            VALUES (?, 23.02, 70.5, 1.75, 'Poids normal')
        ");
        $stmt->execute([$membre_id]);
    }
    echo "<p style='color: green;'>✅ Historique IMC de démonstration créé</p>";
    
    // Afficher le résumé
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #2e7d32;'>🎉 Base de données configurée avec succès !</h3>";
    
    // Compter les membres
    $stmt = $pdo->query("SELECT COUNT(*) FROM membres");
    $nb_membres = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM abonnements");
    $nb_abonnements = $stmt->fetchColumn();
    
    echo "<p><strong>Résumé :</strong></p>";
    echo "<ul>";
    echo "<li>Membres créés : $nb_membres</li>";
    echo "<li>Abonnements créés : $nb_abonnements</li>";
    echo "<li>Tables configurées : 4</li>";
    echo "</ul>";
    echo "</div>";
    
    // Liens utiles
    echo "<div style='margin-top: 20px;'>";
    echo "<p><strong>Liens utiles :</strong></p>";
    echo "<ul>";
    echo "<li><a href='proj.html' style='color: #2196F3;'>📱 Accéder à l'accueil</a></li>";
    echo "<li><a href='se connecter.html' style='color: #2196F3;'>📝 Tester l'inscription</a></li>";
    echo "<li><a href='inscrit.html' style='color: #2196F3;'>🔐 Tester la connexion</a></li>";
    echo "<li><a href='admin.html' style='color: #2196F3;'>⚙️ Accéder à l'administration</a></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<p style='color: #666; font-size: 12px; margin-top: 30px;'>";
    echo "<strong>Note :</strong> Vous pouvez supprimer ce fichier après utilisation pour des raisons de sécurité.";
    echo "</p>";
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='font-family: Arial, sans-serif; padding: 20px; color: red;'>";
    echo "<h2>❌ Erreur lors de la configuration</h2>";
    echo "<p><strong>Message d'erreur :</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Vérifiez que :</strong></p>";
    echo "<ul>";
    echo "<li>MySQL est démarré dans XAMPP/WAMP/MAMP</li>";
    echo "<li>Les identifiants de connexion sont corrects</li>";
    echo "<li>Le service MySQL fonctionne</li>";
    echo "</ul>";
    echo "</div>";
}
?>