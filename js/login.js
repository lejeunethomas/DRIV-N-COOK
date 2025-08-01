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
        console.log('Réponse API complète:', data); // DEBUG

        if (data.success) {
            console.log('Role détecté:', data.role);
            console.log('Statut détecté:', data.statut);
            
            alert.style.display = 'block';
            alert.className = 'alert alert-success';
            alert.textContent = data.message;
            
            if (data.role === 'admin') {
                console.log('Redirection admin vers dashboard/index.php');
                setTimeout(() => window.location.href = 'dashboard/index.php', 1000);
            } else if (data.role === 'franchise') {
                if (data.statut === 'en_attente') {
                    console.log('Redirection franchisé en attente vers attente.html');
                    setTimeout(() => window.location.href = 'franchise/attente.html', 1500);
                } else {
                    console.log('Redirection franchisé validé vers index.php');
                    setTimeout(() => window.location.href = 'franchise/index.php', 1000);
                }
            } else if (data.role === 'client') {
                console.log('Redirection client vers index.html');
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