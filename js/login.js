document.querySelector('#login-form form').onsubmit = async function(e) {
    e.preventDefault();
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;
    const alert = document.getElementById('login-alert');
    alert.style.display = 'none';

    try {
        const res = await fetch('api/users/login.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ email, password })
        });
        const data = await res.json();
        console.log('Réponse API:', data); // DEBUG

        if (data.success) {
            // Connexion réussie - redirection selon le rôle et le statut
            alert.style.display = 'block';
            alert.className = 'alert alert-success';
            alert.textContent = data.message;
            
            if (data.role === 'admin') {
                setTimeout(() => window.location.href = 'dashboard/index.php', 1000);
            } else if (data.role === 'franchise') {
                // VOICI LE PROBLÈME : vous vérifiez le statut mais dans success au lieu d'échec
                if (data.statut === 'en_attente') {
                    setTimeout(() => window.location.href = 'franchise/attente.html', 1500);
                } else {
                    setTimeout(() => window.location.href = 'franchise/index.php', 1000);
                }
            } else if (data.role === 'client') {
                setTimeout(() => window.location.href = 'index.html', 1000);
            }
        } else {
            // Pour les autres erreurs (mot de passe incorrect, etc.)
            alert.style.display = 'block';
            alert.className = 'alert alert-error';
            alert.textContent = data.message;
        }
    } catch (err) {
        alert.style.display = 'block';
        alert.className = 'alert alert-error';
        alert.textContent = "Erreur réseau ou serveur. Veuillez réessayer.";
        console.error(err);
    }
};