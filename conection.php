<?php
// connexion.php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($email) || empty($password)) {
        echo "<script>alert('❌ Veuillez remplir tous les champs.'); window.history.back();</script>";
        exit;
    }
    
    try {
        // Vérifier si l'utilisateur existe
        $stmt = $pdo->prepare("SELECT * FROM membres WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Connexion réussie
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_imc'] = $user['imc'];
            
            echo "<script>
                alert('✅ Connexion réussie ! Redirection vers votre compte...');
                window.location.href = 'compte.php';
            </script>";
            exit;
            
        } else {
            echo "<script>alert('❌ Email ou mot de passe incorrect'); window.history.back();</script>";
            exit;
        }
        
    } catch (PDOException $e) {
        echo "<script>alert('❌ Erreur de connexion à la base de données'); window.history.back();</script>";
        exit;
    }
} else {
    // Redirection si accès direct
    header('Location: inscrit.html');
    exit;
}
?>