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
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar franchise">
            <h2>Mon espace</h2>
            <a href="index.php" class="active">Tableau de bord</a>
            <a href="ventes.php">Mes ventes</a>
            <a href="menu.php">Mon menu</a>
            <a href="commandes.php">Commandes de stock</a>
            <a href="compte.php">Mon compte</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn franchise" style="width:100%;">Déconnexion</button>
            </form>
        </nav>
        <main class="main-content franchise">
            <div class="topbar">
                <h1 style="margin:0; color:#e64a19;">Bienvenue sur votre espace franchisé</h1>
            </div>

            <div class="section-card">
                <section id="camions">
                    <h2>Mon camion</h2>
                    <div id="camion-status">
                    </div>
                    <button id="btn-demande-camion" onclick="afficherFormDemandeCamion()" style="display:none;">
                        Demander mon camion
                    </button>
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

document.addEventListener('DOMContentLoaded', async function() {
    await loadCamionStatus();
    
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
    
    const form = document.getElementById('demandeCamionForm');
    if(form) {
        form.onsubmit = async function(e) {
            e.preventDefault();
            
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
                document.getElementById('form-demande-camion').style.display = 'none';
                afficherDemandeEnAttente(data);
            } else {
                document.getElementById('resultat-demande').textContent = json.message || "Erreur lors de la demande.";
            }
        };
    }

    await loadCommandesEnCours();
});

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

async function loadCommandesEnCours() {
    try {
        const response = await fetch('../api/commandes/list_by_user.php');
        const commandes = await response.json();
        
        const commandesEnCours = commandes.filter(c => c.statut === 'en_attente' || c.statut === 'validee');
        
        if (commandesEnCours.length > 0) {
            const commandesSection = document.getElementById('commandes');
            commandesSection.innerHTML = `
                <h2>Commandes en cours</h2>
                ${commandesEnCours.map(commande => `
                    <div style="background: #fff8f0; border: 1px solid #ff5722; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                        <strong>Commande du ${new Date(commande.date_commande).toLocaleDateString('fr-FR')}</strong><br>
                        <small>Entrepôt: ${commande.entrepot_nom} | Total: ${parseFloat(commande.total).toFixed(2)}€ | Statut: ${commande.statut}</small>
                    </div>
                `).join('')}
                <a href="commandes.php" class="btn">Gérer mes commandes</a>
            `;
        }
    } catch (error) {
        console.error('Erreur lors du chargement des commandes:', error);
    }
}

async function loadCamionStatus() {
    try {
        const camionsRes = await fetch('../api/camions/get_by_user.php');
        const camions = await camionsRes.json();
        const statusDiv = document.getElementById('camion-status');
        const btnDemande = document.getElementById('btn-demande-camion');
        
        if (Array.isArray(camions) && camions.length > 0) {
            // Le franchisé a un camion
            const camion = camions[0];
            statusDiv.innerHTML = `
                <div class="camion-info" style="background: #e8f5e8; padding: 1rem; border-radius: 6px; margin: 1rem 0;">
                    <h3 style="color: #4caf50; margin: 0 0 0.5rem 0;">Votre camion "${camion.nom_camion}"</h3>
                    <p><strong>Immatriculation :</strong> ${camion.immatriculation}</p>
                    <p><strong>État :</strong> ${camion.etat}</p>
                    <p><strong>Emplacement :</strong> ${camion.emplacement || 'Non défini'}</p>
                    ${camion.date_livraison ? `<p><strong>Date de livraison :</strong> ${new Date(camion.date_livraison).toLocaleDateString('fr-FR')}</p>` : ''}
                </div>
            `;
            btnDemande.style.display = 'none';
        } else {
            // Vérifier s'il y a une demande en attente
            const demandesRes = await fetch('../api/camions/demande_status.php');
            const demandes = await demandesRes.json();
            
            if (demandes.has_pending) {
                statusDiv.innerHTML = `
                    <div class="demande-attente" style="background: #fff3cd; padding: 1rem; border-radius: 6px; margin: 1rem 0;">
                        <h3 style="color: #856404; margin: 0 0 0.5rem 0;">Demande en cours de traitement</h3>
                        <p>Votre demande de camion est en cours d'analyse par notre équipe.</p>
                        <p><strong>Camion demandé :</strong> ${demandes.nom_camion}</p>
                        <p><strong>Date de demande :</strong> ${new Date(demandes.date_demande).toLocaleDateString('fr-FR')}</p>
                    </div>
                `;
                btnDemande.style.display = 'none';
            } else {
                // Aucun camion, aucune demande
                statusDiv.innerHTML = `
                    <div class="no-camion" style="background: #f8d7da; padding: 1rem; border-radius: 6px; margin: 1rem 0;">
                        <h3 style="color: #721c24; margin: 0 0 0.5rem 0;">Aucun camion attribué</h3>
                        <p>Vous n'avez pas encore de camion. Faites une demande pour commencer votre activité !</p>
                    </div>
                `;
                btnDemande.style.display = 'block';
            }
        }
    } catch (error) {
        console.error('Erreur lors du chargement du statut camion:', error);
    }
}
</script>
</body>
</html>