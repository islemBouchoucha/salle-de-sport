<?php
// compte.php
session_start();
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: inscrit.html');
    exit;
}

// Récupérer les données du membre
$stmt = $pdo->prepare("
    SELECT m.*, a.type as type_abonnement, a.date_debut, a.date_fin, a.statut as statut_abonnement
    FROM membres m 
    LEFT JOIN abonnements a ON m.id = a.membre_id 
    WHERE m.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: inscrit.html');
    exit;
}

// Récupérer l'historique IMC
$stmt = $pdo->prepare("SELECT * FROM historique_imc WHERE membre_id = ? ORDER BY date_calcul DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$historique_imc = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Google fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">

    <title>Mon Compte - GYM CENTER</title>

    <!-- Lien vers le fichier CSS -->
    <link rel="stylesheet" href="compte.css">
</head>

<body>

    <!-- HEADER -->
    <header class="header">
        <img src="imagee/logo.jpg" class="logo" />
        <nav class="navbar">
            <a href="proj.html">Accueil</a>
            <a href="deepseek_html_20251115_31774d.html">Activités</a>
            <a href="planning.html">Planning</a>
            <a href="compte.php" class="active">Mon Compte</a>
            <a href="deconnexion.php" id="btnDeconnexion">Déconnexion</a>
        </nav>
    </header>

    <!-- CONTENU PRINCIPAL DU COMPTE -->
    <div class="compte-container">
        <!-- BANNIÈRE PERSONNELLE -->
        <div class="banner-personnelle">
            <div class="banner-content">
                <div class="photo-profil">
                    <img id="userPhoto" src="imagee/user-default.jpg" alt="Photo de profil" class="photo">
                </div>
                <div class="info-principale">
                    <h1 id="userWelcome">Bonjour, <?php echo htmlspecialchars($user['prenom']); ?> !</h1>
                    <p id="userStatus">Membre GYM CENTER</p>
                    <div class="badges">
                        <span class="badge" id="badgeIMC">IMC: <?php echo $user['imc']; ?></span>
                        <span class="badge" id="badgeObjectif">Objectif: <?php echo ucfirst($user['objectif']); ?></span>
                        <span class="badge" id="badgeMembre">Membre depuis: <?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- GRID DES INFORMATIONS -->
        <div class="compte-grid">
            <!-- COLONNE GAUCHE - INFORMATIONS PERSONNELLES -->
            <div class="compte-colonne">
                <!-- CARTE PROFIL COMPLET -->
                <div class="carte-compte">
                    <h3>👤 MES INFORMATIONS PERSONNELLES</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Nom complet</label>
                            <span id="infoNomComplet"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <span id="infoEmail"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Âge</label>
                            <span id="infoAge"><?php echo $user['age']; ?> ans</span>
                        </div>
                        <div class="info-item">
                            <label>Sexe</label>
                            <span id="infoSexe"><?php echo $user['sexe'] === 'homme' ? 'Homme' : 'Femme'; ?></span>
                        </div>
                        <div class="info-item">
                            <label>Téléphone</label>
                            <span id="infoTelephone"><?php echo $user['telephone'] ?: 'Non renseigné'; ?></span>
                        </div>
                        <div class="info-item">
                            <label>Adresse</label>
                            <span id="infoAdresse"><?php echo $user['adresse'] ?: 'Non renseignée'; ?></span>
                        </div>
                    </div>
                    <button onclick="ouvrirModificationProfil()" class="btn-modifier">✏️ Modifier mes informations</button>
                </div>

                <!-- CARTE MESURES PHYSIQUES -->
                <div class="carte-compte">
                    <h3>📏 MES MESURES</h3>
                    <div class="mesures-grid">
                        <div class="mesure-item">
                            <label>Taille</label>
                            <span id="mesureTaille"><?php echo $user['taille']; ?> m</span>
                        </div>
                        <div class="mesure-item">
                            <label>Poids actuel</label>
                            <span id="mesurePoids"><?php echo $user['poids']; ?> kg</span>
                        </div>
                        <div class="mesure-item">
                            <label>IMC</label>
                            <span id="mesureIMC"><?php echo $user['imc']; ?></span>
                        </div>
                        <div class="mesure-item">
                            <label>Catégorie</label>
                            <span id="mesureCategorie"><?php echo $user['categorie_imc']; ?></span>
                        </div>
                    </div>
                    <button onclick="ouvrirModificationMesures()" class="btn-modifier">⚖️ Mettre à jour mes mesures</button>
                </div>
            </div>

            <!-- COLONNE CENTRALE - SANTÉ ET IMC -->
            <div class="compte-colonne">
                <!-- CARTE IMC DÉTAILLÉE -->
                <div class="carte-compte imc-carte">
                    <h3>📊 MON IMC ET SANTÉ</h3>
                    <div class="imc-display">
                        <div class="imc-valeur">
                            <span id="valeurIMC"><?php echo $user['imc']; ?></span>
                            <span class="imc-label">Indice de Masse Corporelle</span>
                        </div>
                        <div class="imc-categorie" id="categorieIMC">
                            <span id="texteCategorie"><?php echo $user['categorie_imc']; ?></span>
                        </div>
                    </div>
                    <div class="imc-interpretation">
                        <h4>Interprétation :</h4>
                        <p id="interpretationIMC">
                            <?php
                            $imc = floatval($user['imc']);
                            if ($imc < 18.5) {
                                echo "Votre IMC indique un poids insuffisant pour votre taille.";
                            } elseif ($imc >= 18.5 && $imc <= 24.9) {
                                echo "Votre IMC indique un poids santé pour votre taille.";
                            } elseif ($imc >= 25 && $imc <= 29.9) {
                                echo "Votre IMC indique un excès de poids pour votre taille.";
                            } else {
                                echo "Votre IMC indique une obésité. Une consultation médicale est recommandée.";
                            }
                            ?>
                        </p>
                    </div>
                </div>

                <!-- CARTE HISTORIQUE IMC -->
                <div class="carte-compte">
                    <h3>📈 HISTORIQUE IMC</h3>
                    <div class="historique-imc" id="historiqueIMC">
                        <?php if (empty($historique_imc)): ?>
                            <p class="aucune-donnee">Aucun historique disponible</p>
                        <?php else: ?>
                            <?php foreach ($historique_imc as $entree): ?>
                                <div class="entree-historique">
                                    <div class="date-imc"><?php echo date('d/m/Y', strtotime($entree['date_calcul'])); ?></div>
                                    <div class="details-imc">
                                        <span class="poids"><?php echo $entree['poids']; ?> kg</span>
                                        <span class="imc">IMC: <?php echo $entree['imc']; ?></span>
                                        <span class="categorie"><?php echo $entree['categorie']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- COLONNE DROITE - ABONNEMENT -->
            <div class="compte-colonne">
                <!-- CARTE ABONNEMENT -->
                <div class="carte-compte programme-carte">
                    <h3>💰 MON ABONNEMENT</h3>
                    <div class="programme-header">
                        <div class="calories-jour">
                            <span id="typeAbonnement"><?php echo strtoupper($user['type_abonnement'] ?: 'Aucun'); ?></span>
                            <span>Abonnement</span>
                        </div>
                    </div>
                    <div class="programme-content">
                        <div class="repas-section">
                            <h4>📅 Date de début</h4>
                            <p><?php echo $user['date_debut'] ? date('d/m/Y', strtotime($user['date_debut'])) : '-'; ?></p>
                        </div>
                        <div class="repas-section">
                            <h4>⏰ Date de fin</h4>
                            <p><?php echo $user['date_fin'] ? date('d/m/Y', strtotime($user['date_fin'])) : '-'; ?></p>
                        </div>
                        <div class="repas-section">
                            <h4>✅ Statut</h4>
                            <p><?php echo $user['statut_abonnement'] === 'actif' ? 'Actif' : 'Inactif'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fonctions JavaScript pour les modals (à compléter)
        function ouvrirModificationProfil() {
            alert('Fonction de modification à implémenter');
        }

        function ouvrirModificationMesures() {
            alert('Fonction de modification des mesures à implémenter');
        }
    </script>
</body>
</html>