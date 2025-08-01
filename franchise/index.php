<?php
require_once '../includes/auth.php';
require_franchise_validated(); 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord Franchisé</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            background: #ff5722;
            color: #fff;
            width: 220px;
            padding: 2rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
            min-height: 100vh;
        }
        .sidebar h2 {
            color: #fff;
            margin-bottom: 2rem;
            font-size: 1.3rem;
            text-align: center;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            margin-bottom: 1rem;
            display: block;
            padding: 0.7rem 1rem;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .sidebar a.active, .sidebar a:hover {
            background: #e64a19;
        }
        .main-content {
            flex: 1;
            padding: 2.5rem 3rem;
            background: #fff8f0;
        }
        .section-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px #0001;
            padding: 2rem 2.5rem;
            margin-bottom: 2.5rem;
        }
        .section-card h2 {
            color: #ff5722;
            margin-bottom: 1.2rem;
        }
        .topbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 2rem;
        }
        .logout-btn {
            background: #ff5722;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.7rem 1.5rem;
            font-weight: bold;
            cursor: pointer;
        }
        .logout-btn:hover {
            background: #e64a19;
        }
        @media (max-width: 900px) {
            .dashboard-layout { flex-direction: column; }
            .sidebar { flex-direction: row; width: 100%; min-height: unset; padding: 1rem; gap: 1rem;}
            .main-content { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar">
            <h2>Mon espace</h2>
            <a href="index.php" class="active">Tableau de bord</a>
            <a href="ventes.php">Mes ventes</a>
            <a href="commandes.php">Commandes de stock</a>
            <a href="compte.php">Mon compte</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>
        <main class="main-content">
            <div class="topbar">
                <h1 style="margin:0; color:#e64a19;">Bienvenue sur votre espace franchisé</h1>
            </div>

            <div class="section-card">
                <section id="camions">
                    <h2>Mon camion</h2>
                    <button onclick="afficherFormDemandeCamion()">Demander un camion</button>
                    <div id="form-demande-camion" style="display:none;">
                        <h3>Demande de camion</h3>
                        <form id="demandeCamionForm">
                            <label>Nom du camion :
                                <input type="text" name="nom_camion" required placeholder="Ex: Le Gourmand, Food Express...">
                            </label>
                            
                            <label>Numéro de permis de conduire :
                                <input type="text" name="numero_permis" required placeholder="Ex: 123456789012">
                            </label>
                            
                            <label>Emplacement souhaité :
                                <input type="text" name="emplacement" required>
                            </label>
                            <label>Menu souhaité :
                                <input type="text" name="menu" required>
                            </label>
                            <label>Jours d'ouverture :</label>
                            <div>
                                <label><input type="checkbox" name="jours[]" value="Lundi"> Lundi</label>
                                <label><input type="checkbox" name="jours[]" value="Mardi"> Mardi</label>
                                <label><input type="checkbox" name="jours[]" value="Mercredi"> Mercredi</label>
                                <label><input type="checkbox" name="jours[]" value="Jeudi"> Jeudi</label>
                                <label><input type="checkbox" name="jours[]" value="Vendredi"> Vendredi</label>
                                <label><input type="checkbox" name="jours[]" value="Samedi"> Samedi</label>
                                <label><input type="checkbox" name="jours[]" value="Dimanche"> Dimanche</label>
                            </div>
                            <button type="submit">Envoyer la demande</button>
                            <button type="button" onclick="fermerFormDemandeCamion()">Annuler</button>
                        </form>
                        <div id="resultat-demande"></div>
                    </div>
                </section>
            </div>

            <div class="section-card">
                <section id="ventes">
                    <h2>Mes ventes</h2>
                    <a href="ventes.php" class="btn">Voir l’historique des ventes</a>
                </section>
            </div>

            <div class="section-card">
                <section id="commandes">
                    <h2>Commandes de stock</h2>
                    <a href="commandes.php" class="btn">Gérer mes commandes</a>
                </section>
            </div>
        </main>
    </div>
    <script src="../public/js/franchise.js"></script>
    <script>
function afficherFormDemandeCamion() {
    document.getElementById('form-demande-camion').style.display = 'block';
}
function fermerFormDemandeCamion() {
    document.getElementById('form-demande-camion').style.display = 'none';
}

// Charger les données utilisateur pour pré-remplir le formulaire
document.addEventListener('DOMContentLoaded', async function() {
    try {
        const res = await fetch('../api/users/profile.php');
        const user = await res.json();
        if (user.numero_permis) {
            const permiInput = document.querySelector('input[name="numero_permis"]');
            if (permiInput) {
                permiInput.value = user.numero_permis;
            }
        }
    } catch (err) {
        console.log('Impossible de charger le profil utilisateur');
    }
    
    // Gestion de l'envoi du formulaire
    const form = document.getElementById('demandeCamionForm');
    if(form) {
        form.onsubmit = async function(e) {
            e.preventDefault();
            
            // Géocode l'emplacement
            const geoResult = await geocodeAddress(form.emplacement.value);
            
            const data = {
                nom_camion: form.nom_camion.value,
                numero_permis: form.numero_permis.value,
                emplacement: form.emplacement.value,
                latitude: geoResult.success ? geoResult.latitude : null,
                longitude: geoResult.success ? geoResult.longitude : null,
                menu: form.menu.value,
                jours: Array.from(form.querySelectorAll('input[name="jours[]"]:checked')).map(cb => cb.value)
            };
            
            const res = await fetch('../api/camions/demande.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            const json = await res.json();
            if(json.success) {
                // Masquer le formulaire
                document.getElementById('form-demande-camion').style.display = 'none';
                // Afficher le récapitulatif
                afficherDemandeEnAttente(data);
            } else {
                document.getElementById('resultat-demande').textContent = json.message || "Erreur lors de la demande.";
            }
        };
    }
});

// Fonction pour convertir une adresse en coordonnées
async function geocodeAddress(address) {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`);
        const data = await response.json();
        
        if (data.length > 0) {
            return {
                latitude: parseFloat(data[0].lat),
                longitude: parseFloat(data[0].lon),
                success: true
            };
        } else {
            return { success: false, error: 'Adresse non trouvée' };
        }
    } catch (error) {
        return { success: false, error: 'Erreur de géocodage' };
    }
}

// Affiche le bloc "demande en attente"
function afficherDemandeEnAttente(data) {
    const recap = `
        <div class="section-card" style="background:#fffbe7;">
            <h3 style="color:#e64a19;">Demande de camion en attente</h3>
            <ul>
                <li><strong>Nom du camion :</strong> ${data.nom_camion}</li>
                <li><strong>Numéro de permis :</strong> ${data.numero_permis}</li>
                <li><strong>Emplacement :</strong> ${data.emplacement}</li>
                <li><strong>Menu :</strong> ${data.menu}</li>
                <li><strong>Jours d'ouverture :</strong> ${data.jours.join(', ')}</li>
            </ul>
            <p style="color:#e64a19;">Votre demande est en attente de validation par l'administrateur.</p>
        </div>
    `;
    document.getElementById('camions').insertAdjacentHTML('beforeend', recap);
}
</script>
</body>
</html>