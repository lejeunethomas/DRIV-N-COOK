// Configuration globale
const GEOLOCATION_CONFIG = {
    nominatimUrl: 'https://nominatim.openstreetmap.org/search',
    timeout: 5000,
    defaultCountry: 'France'
};

/**
 * Géocoder une adresse
 */
async function geocodeAddress(address, includeCountry = true) {
    try {
        const fullAddress = includeCountry ? `${address}, ${GEOLOCATION_CONFIG.defaultCountry}` : address;
        
        const response = await fetch(
            `${GEOLOCATION_CONFIG.nominatimUrl}?format=json&q=${encodeURIComponent(fullAddress)}&limit=1`,
            { timeout: GEOLOCATION_CONFIG.timeout }
        );
        
        if (!response.ok) {
            throw new Error('Erreur réseau lors de la géolocalisation');
        }
        
        const data = await response.json();
        
        if (data.length > 0) {
            return {
                latitude: parseFloat(data[0].lat),
                longitude: parseFloat(data[0].lon),
                display_name: data[0].display_name,
                success: true
            };
        } else {
            return { 
                success: false, 
                error: 'Adresse non trouvée',
                suggestion: 'Vérifiez l\'orthographe ou essayez une adresse plus précise'
            };
        }
    } catch (error) {
        console.error('Erreur de géocodage:', error);
        return { 
            success: false, 
            error: 'Erreur de géocodage',
            details: error.message 
        };
    }
}

/**
 * Obtenir la position actuelle de l'utilisateur
 */
function getCurrentPosition() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('Géolocalisation non supportée par ce navigateur'));
            return;
        }
        
        navigator.geolocation.getCurrentPosition(
            position => {
                resolve({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    success: true
                });
            },
            error => {
                let message;
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        message = "Géolocalisation refusée par l'utilisateur";
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message = "Position non disponible";
                        break;
                    case error.TIMEOUT:
                        message = "Délai de géolocalisation dépassé";
                        break;
                    default:
                        message = "Erreur de géolocalisation inconnue";
                        break;
                }
                reject(new Error(message));
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000 // 5 minutes
            }
        );
    });
}

/**
 * Calculer la distance entre deux points
 */
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Rayon de la Terre en km
    const dLat = deg2rad(lat2 - lat1);
    const dLon = deg2rad(lon2 - lon1);
    const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
        Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

function deg2rad(deg) {
    return deg * (Math.PI/180);
}

/**
 * Formater l'affichage d'une distance
 */
function formatDistance(km) {
    if (km < 1) {
        return Math.round(km * 1000) + ' m';
    } else if (km < 10) {
        return km.toFixed(1) + ' km';
    } else {
        return Math.round(km) + ' km';
    }
}

/**
 * Obtenir une estimation du temps de livraison
 */
function getDeliveryTimeEstimate(km) {
    if (km <= 10) return { text: "Même jour", class: "delivery-same-day", hours: 4 };
    if (km <= 50) return { text: "24h", class: "delivery-next-day", hours: 24 };
    if (km <= 150) return { text: "48h", class: "delivery-two-days", hours: 48 };
    return { text: "3-5 jours", class: "delivery-long", hours: 120 };
}