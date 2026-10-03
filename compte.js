// ===================== GESTION DE LA PAGE COMPTE =====================

class CompteManager {
    constructor() {
        this.utilisateurActuel = null;
        this.init();
    }

    init() {
        this.verifierConnexion();
        this.initialiserEvenements();
        this.chargerDonneesUtilisateur();
    }

    // Vérifier si l'utilisateur est connecté et récupérer ses données
    verifierConnexion() {
        const utilisateurConnecte = localStorage.getItem('userLoggedIn');
        const donneesUtilisateur = localStorage.getItem('currentUser');

        if (!utilisateurConnecte || !donneesUtilisateur) {
            alert('❌ Vous devez être connecté pour accéder à cette page');
            window.location.href = 'inscrit.html';
            return;
        }

        this.utilisateurActuel = JSON.parse(donneesUtilisateur);
        console.log('Utilisateur connecté:', this.utilisateurActuel);
        
        // Vérifier dans la base de données pour les données à jour
        const membreDB = GYMDB.getMembreParId(this.utilisateurActuel.id);
        if (membreDB) {
            this.utilisateurActuel = { ...this.utilisateurActuel, ...membreDB };
            localStorage.setItem('currentUser', JSON.stringify(this.utilisateurActuel));
        }
    }

    // Charger TOUTES les données de l'utilisateur dans la page
    chargerDonneesUtilisateur() {
        if (!this.utilisateurActuel) return;

        // 1. INFORMATIONS PERSONNELLES
        this.afficherInformationsPersonnelles();
        
        // 2. MESURES ET IMC
        this.afficherMesuresEtIMC();
        
        // 3. PROGRAMMES PERSONNALISÉS
        this.genererProgrammesPersonnalises();
        
        // 4. ABONNEMENT
        this.afficherInformationsAbonnement();
        
        // 5. HISTORIQUE IMC
        this.afficherHistoriqueIMC();
        
        // 6. PHOTO DE PROFIL
        this.chargerPhotoProfil();
    }

    // Afficher les informations personnelles
    afficherInformationsPersonnelles() {
        const user = this.utilisateurActuel;

        // En-tête de la page
        document.getElementById('userWelcome').textContent = `Bonjour, ${user.prenom} !`;
        
        // Badges d'information
        document.getElementById('badgeIMC').textContent = `IMC: ${user.imc || 'Non calculé'}`;
        document.getElementById('badgeObjectif').textContent = `Objectif: ${this.getObjectifTexte(user.objectif)}`;
        
        const dateInscription = user.date_inscription ? new Date(user.date_inscription) : new Date();
        document.getElementById('badgeMembre').textContent = `Membre depuis: ${dateInscription.toLocaleDateString('fr-FR')}`;

        // Section informations personnelles
        document.getElementById('infoNomComplet').textContent = `${user.prenom} ${user.nom}`;
        document.getElementById('infoEmail').textContent = user.email;
        document.getElementById('infoAge').textContent = `${user.age} ans`;
        document.getElementById('infoSexe').textContent = user.sexe === 'homme' ? 'Homme' : 'Femme';
        document.getElementById('infoTelephone').textContent = user.telephone || 'Non renseigné';
        document.getElementById('infoAdresse').textContent = user.adresse || 'Non renseignée';
    }

    // Afficher les mesures physiques et IMC
    afficherMesuresEtIMC() {
        const user = this.utilisateurActuel;

        // Mesures physiques
        document.getElementById('mesureTaille').textContent = `${user.taille} m`;
        document.getElementById('mesurePoids').textContent = `${user.poids} kg`;
        document.getElementById('mesureObjectifPoids').textContent = user.poids_objectif ? `${user.poids_objectif} kg` : 'Non défini';

        // Calcul de l'évolution du poids
        if (user.poids_initial && user.poids) {
            const evolution = user.poids - user.poids_initial;
            const elementEvolution = document.getElementById('mesureEvolution');
            elementEvolution.textContent = `${evolution > 0 ? '+' : ''}${evolution.toFixed(1)} kg`;
            elementEvolution.className = evolution > 0 ? 'evolution positive' : 'evolution negative';
        }

        // Calcul et affichage de l'IMC
        const imc = user.imc ? parseFloat(user.imc) : this.calculerIMC(user.poids, user.taille);
        const categorie = this.determinerCategorieIMC(imc);

        // Affichage IMC
        document.getElementById('valeurIMC').textContent = imc.toFixed(2);
        document.getElementById('texteCategorie').textContent = categorie.texte;
        document.getElementById('categorieIMC').style.backgroundColor = categorie.couleur;
        
        // Interprétation et conseils
        document.getElementById('interpretationIMC').textContent = categorie.interpretation;
        document.getElementById('conseilsIMC').textContent = categorie.conseils;
    }

    // Générer les programmes basés sur les données utilisateur
    genererProgrammesPersonnalises() {
        const user = this.utilisateurActuel;
        const imc = parseFloat(user.imc) || this.calculerIMC(user.poids, user.taille);
        const programme = this.genererProgrammeComplet(imc, user.sexe, user.age, user.objectif);

        // PROGRAMME ALIMENTAIRE
        document.getElementById('caloriesRecommandees').textContent = programme.calories;
        this.afficherPlanAlimentaire(programme.alimentation);

        // PROGRAMME SPORTIF
        document.getElementById('dureeProgramme').textContent = programme.duree;
        document.getElementById('frequenceProgramme').textContent = programme.frequence;
        this.afficherPlanSportif(programme.entrainement);
    }

    // Afficher le plan alimentaire détaillé
    afficherPlanAlimentaire(plan) {
        document.getElementById('planPetitDejeuner').textContent = plan.petit_dejeuner;
        document.getElementById('planDejeuner').textContent = plan.dejeuner;
        document.getElementById('planDiner').textContent = plan.diner;
        document.getElementById('planCollations').textContent = plan.collations;
    }

    // Afficher le plan sportif détaillé
    afficherPlanSportif(plan) {
        document.getElementById('exercicesProgramme').textContent = plan.exercices;
        document.getElementById('frequenceDetail').textContent = plan.frequence_detail;
        document.getElementById('conseilsEntrainement').textContent = plan.conseils;
    }

    // Afficher les informations d'abonnement
    afficherInformationsAbonnement() {
        const abonnement = GYMDB.getAbonnementParMembre(this.utilisateurActuel.id);
        
        if (abonnement) {
            document.getElementById('typeAbonnement').textContent = this.getTypeAbonnementTexte(abonnement.type);
            document.getElementById('dateDebutAbonnement').textContent = new Date(abonnement.date_debut).toLocaleDateString('fr-FR');
            document.getElementById('dateFinAbonnement').textContent = new Date(abonnement.date_fin).toLocaleDateString('fr-FR');
            document.getElementById('statutAbonnement').textContent = abonnement.statut === 'actif' ? 'Actif' : 'Inactif';
        } else {
            document.getElementById('typeAbonnement').textContent = 'Aucun abonnement';
            document.getElementById('dateDebutAbonnement').textContent = '-';
            document.getElementById('dateFinAbonnement').textContent = '-';
            document.getElementById('statutAbonnement').textContent = 'Inactif';
        }
    }

    // Afficher l'historique IMC
    afficherHistoriqueIMC() {
        const historique = GYMDB.getHistoriqueIMCParMembre(this.utilisateurActuel.id);
        const container = document.getElementById('historiqueIMC');

        if (historique.length === 0) {
            container.innerHTML = '<p class="aucune-donnee">Aucun historique disponible</p>';
            return;
        }

        container.innerHTML = historique.slice(0, 5).map(entree => `
            <div class="entree-historique">
                <div class="date-imc">${new Date(entree.date_calcul).toLocaleDateString('fr-FR')}</div>
                <div class="details-imc">
                    <span class="poids">${entree.poids} kg</span>
                    <span class="imc">IMC: ${entree.imc}</span>
                    <span class="categorie ${entree.categorie.toLowerCase()}">${entree.categorie}</span>
                </div>
            </div>
        `).join('');
    }

    // ===================== FONCTIONS IMC =====================
    calculerIMC(poids, taille) {
        return poids / (taille * taille);
    }

    determinerCategorieIMC(imc) {
        if (imc < 18.5) {
            return {
                texte: 'Sous-poids',
                couleur: '#ffcc00',
                interpretation: 'Votre IMC indique un poids insuffisant pour votre taille.',
                conseils: 'Nous recommandons une alimentation riche en nutriments et un programme de renforcement musculaire.'
            };
        } else if (imc >= 18.5 && imc <= 24.9) {
            return {
                texte: 'Poids normal',
                couleur: '#4CAF50',
                interpretation: 'Votre IMC indique un poids santé pour votre taille.',
                conseils: 'Continuez vos bonnes habitudes ! Maintenez une alimentation équilibrée et une activité physique régulière.'
            };
        } else if (imc >= 25 && imc <= 29.9) {
            return {
                texte: 'Surpoids',
                couleur: '#FF9800',
                interpretation: 'Votre IMC indique un excès de poids pour votre taille.',
                conseils: 'Un programme combinant alimentation équilibrée et activité physique vous aidera à atteindre un poids santé.'
            };
        } else {
            return {
                texte: 'Obésité',
                couleur: '#F44336',
                interpretation: 'Votre IMC indique une obésité. Une consultation médicale est recommandée.',
                conseils: 'Consultez un professionnel de santé pour établir un programme adapté à votre situation.'
            };
        }
    }

    // ===================== GÉNÉRATION DES PROGRAMMES =====================
    genererProgrammeComplet(imc, sexe, age, objectif) {
        let programme = {};

        if (imc < 18.5) {
            programme = {
                calories: sexe === 'homme' ? 3200 : 2800,
                duree: 45,
                frequence: 5,
                alimentation: {
                    petit_dejeuner: '• 100g flocons d\'avoine\n• 1 banane + 30g noix\n• 250ml lait entier\n• 1 cuillère de miel',
                    dejeuner: '• 200g poulet/poisson\n• 150g riz/pâtes complètes\n• Légumes à volonté\n• 1 avocat',
                    diner: '• 150g viande rouge/poisson\n• 100g quinoa\n• Salade variée\n• 30g amandes',
                    collations: '• Fruits secs\n• Yaourt grec\n• Smoothie protéiné'
                },
                entrainement: {
                    exercices: '• Développé couché: 4x12\n• Squats: 4x15\n• Rowing: 4x12\n• Cardio léger: 30min',
                    frequence_detail: 'Lundi, Mercredi, Vendredi: Musculation\nMardi, Jeudi: Cardio\nWeek-end: Récupération',
                    conseils: 'Augmentez progressivement les charges et les calories.'
                }
            };
        } else if (imc >= 18.5 && imc <= 24.9) {
            programme = {
                calories: sexe === 'homme' ? 2500 : 2000,
                duree: 60,
                frequence: 4,
                alimentation: {
                    petit_dejeuner: '• 80g flocons d\'avoine\n• Fruits frais\n• 200ml lait demi-écrémé\n• 1 œuf',
                    dejeuner: '• 150g protéines\n• 100g glucides complexes\n• Légumes variés\n• 1 fruit',
                    diner: '• 120g poisson/poulet\n• Salade complète\n• 1 yaourt nature',
                    collations: '• Fruit\n• Poignée d\'amandes\n• Fromage blanc'
                },
                entrainement: {
                    exercices: '• Pompes: 4x15\n• Squats: 4x20\n• Tractions: 4x8\n• Cardio: 20min',
                    frequence_detail: 'Lundi, Mercredi: Haut du corps\nMardi, Jeudi: Bas du corps\nVendredi: Full body',
                    conseils: 'Variez les exercices pour un développement harmonieux.'
                }
            };
        } else {
            programme = {
                calories: sexe === 'homme' ? 1800 : 1500,
                duree: 90,
                frequence: 5,
                alimentation: {
                    petit_dejeuner: '• 50g flocons d\'avoine\n• 1 fruit\n• Thé vert\n• 1 œuf à la coque',
                    dejeuner: '• 120g poulet/poisson\n• Légumes à volonté\n• 50g quinoa\n• Salade verte',
                    diner: '• 100g poisson blanc\n• Légumes vapeur\n• 1 yaourt 0%',
                    collations: '• Légumes crus\n• 1 pomme\n• Eau citronnée'
                },
                entrainement: {
                    exercices: '• Cardio modéré: 45min\n• Musculation légère\n• Marche rapide\n• Étirements',
                    frequence_detail: 'Lundi à Vendredi: Cardio + Musculation\nSamedi: Activité plaisir\nDimanche: Repos',
                    conseils: 'Commencez progressivement et augmentez l\'intensité.'
                }
            };
        }

        return programme;
    }

    // ===================== FONCTIONS UTILITAIRES =====================
    getObjectifTexte(objectif) {
        const objectifs = {
            'forme': 'Mise en forme',
            'perte': 'Perte de poids',
            'musculation': 'Prise de masse',
            'performance': 'Performance',
            'sante': 'Santé'
        };
        return objectifs[objectif] || 'Mise en forme';
    }

    getTypeAbonnementTexte(type) {
        const types = {
            'decouverte': 'Découverte',
            'fitness': 'Fitness',
            'premium': 'Premium'
        };
        return types[type] || type;
    }

    chargerPhotoProfil() {
        const photoSauvegardee = localStorage.getItem(`userPhoto_${this.utilisateurActuel.id}`);
        if (photoSauvegardee) {
            document.getElementById('userPhoto').src = photoSauvegardee;
        }
    }
}

// ===================== INITIALISATION =====================
let gestionnaireCompte;

document.addEventListener('DOMContentLoaded', function() {
    gestionnaireCompte = new CompteManager();
});