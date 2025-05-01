document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formPrim');
    let isValide = true;

    // Fonction pour afficher les messages d'erreur
    function showError(input, message) {
        const existingError = input.parentElement.querySelector('.error-message');
        if (existingError) existingError.remove();
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.style.color = 'red';
        errorDiv.style.fontSize = '12px';
        errorDiv.style.marginTop = '5px';
        errorDiv.textContent = message;
        input.parentElement.appendChild(errorDiv);
        input.style.borderColor = 'red';
    };

    function clearError(input) {
        const existingError = input.parentElement.querySelector('.error-message');
        if (existingError) existingError.remove();
        input.style.borderColor = '#ddd';
    }
    
    // Validation en temps réel pour tous les champs requis
    const requiredInputs = form.querySelectorAll('input[required], select[required]');
    requiredInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (!this.value.trim()) {
                showError(this, 'Ce champ est obligatoire');
            } else {
                clearError(this);
            }
        });
        input.addEventListener('input', function() {
            if (this.value.trim()) {
                clearError(this);
            }
        });
    });
    
    // Validation spécifique pour le téléphone
    const telephoneInput = document.getElementById('telephone');
    telephoneInput.addEventListener('input', function() {
        const regex = /^(\+213|0)(5|6|7)\d{8}$/;
        if (this.value && !regex.test(this.value)) {
            showError(this, 'Format: 05XXXXXXXX ou +2135XXXXXXXX');
        } else {
            clearError(this);
        }
    });
    
    telephoneInput.addEventListener('blur', function() {
        if (this.value.trim() === "" || !/^(\+213|0)(5|6|7)\d{8}$/.test(this.value)) {
            showError(this, 'Format: 05XXXXXXXX ou +2135XXXXXXXX');
        }
    });

    // Validation pour l'email
    const emailInput = document.getElementById('email');
    emailInput.addEventListener('blur', function() {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!this.value.trim()) {
            showError(this, 'Ce champ est obligatoire');
        } else if (!regex.test(this.value)) {
            showError(this, 'Veuillez entrer une adresse email valide');
        } else {
            clearError(this);
        }
    });
    
    // Validation pour l'âge (minimum 18 ans pour souscription)
    const dateNaissanceInput = document.getElementById('date_naissance');
    dateNaissanceInput.addEventListener('blur', function() {
        const naissance = new Date(this.value);
        const aujourdhui = new Date();
        let age = aujourdhui.getFullYear() - naissance.getFullYear();
        
        if (aujourdhui.getMonth() < naissance.getMonth() || 
            (aujourdhui.getMonth() === naissance.getMonth() && aujourdhui.getDate() < naissance.getDate())) {
            age--;
        }

        if (age < 18) {
            showError(this, 'L\'assuré doit avoir au moins 18 ans');
        } else {
            clearError(this);
        }
    });
    // Validation pour le poids (entre 30kg et 300kg)
    const poidsInput = document.getElementById('poids');
    poidsInput.addEventListener('blur', function() {
        if (!this.value) {
            showError(this, 'Ce champ est obligatoire');
            return;
        }
        const poids = parseFloat(this.value);
        if (poids < 30 || poids > 300) {
            showError(this, 'Le poids doit être entre 30kg et 300kg');
        } else {
            clearError(this);
        }
    });

    // Validation pour la taille (entre 100cm et 250cm)
    const tailleInput = document.getElementById('taille');
    tailleInput.addEventListener('blur', function() {
        if (!this.value) {
            showError(this, 'Ce champ est obligatoire');
            return;
        }
        const taille = parseInt(this.value);
        if (taille < 100 || taille > 250) {
            showError(this, 'La taille doit être entre 100cm et 250cm');
        } else {
            clearError(this);
        }
    });

    // Set les dates automatiques pour le contrat
    const dateSubscription = document.getElementById('date_souscription');
    dateSubscription.value = new Date().toISOString().split('T')[0];
    const dateExpiration = document.getElementById('date_expiration');
    dateExpiration.value = new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().split('T')[0];
    
    dateSubscription.addEventListener('change', function() {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const selectedDate = new Date(this.value);
        
        if (selectedDate < today) {
            showError(this, 'La date de souscription ne peut pas être dans le passé');
        } else {
            clearError(this);
            if (this.value) {
                const expirationDate = new Date(selectedDate);
                expirationDate.setFullYear(expirationDate.getFullYear() + 1);
                const formattedDate = expirationDate.toISOString().split('T')[0];
                dateExpiration.value = formattedDate;
            }
        }
    });
    
    dateExpiration.addEventListener('change', function() {
        if (dateSubscription.value) {
            const subscriptionDate = new Date(dateSubscription.value);
            const expirationDate = new Date(this.value);
            
            if (expirationDate <= subscriptionDate) {
                showError(this, 'La date d\'expiration doit être postérieure à la date de souscription');
            } else {
                clearError(this);
            }
        }
    });
    
    // Calcul de la prime
    document.getElementById('calculerPrimeBtn').addEventListener('click', async function() {
        isValide = true;        
        requiredInputs.forEach(input => {
            if (!input.value.trim()) {
                isValide = false;
                showError(input, 'Ce champ est obligatoire');
            }
        });
    
        if (!isValide) {
            alert('Veuillez corriger les erreurs avant de calculer');
            return;
        }
    
        const btnCalculer = this;
        btnCalculer.disabled = true;
        btnCalculer.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Calcul en cours...';
    
        try {
            const formData = new FormData(form);
            const response = await fetch('calcul_prime_sante.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Erreur lors du calcul');
            }
            afficherResultatPrime(data.prime, data.primeNet);
            document.getElementById('souscrireBtn').style.display = 'inline-block';
            document.getElementById('formPrim').dataset.prime = data.prime;
            document.getElementById('formPrim').dataset.franchise = data.franchise;
        } catch (error) {
            console.error("Erreur:", error);
            alert("Erreur lors du calcul: " + error.message);
        } finally {
            btnCalculer.disabled = false;
            btnCalculer.textContent = 'Calculer la prime';
        }
    });
    
    function afficherResultatPrime(prime, primeNet) {
        const resultatDiv = document.getElementById('resultatCalcul');
        const detailDiv = document.getElementById('detailPrime');
    
        const surcharge = parseFloat(document.getElementById('surcharge').value) / 100 || 0;
        const reduction = parseFloat(document.getElementById('reduction').value) / 100  || 0;
    
        const primeAvecSurcharge = primeNet * (1 + surcharge);
        const primeAvecReduction = primeAvecSurcharge * (1 - reduction);
    
        detailDiv.innerHTML = `
            <div class="prime-result">
                <p>Prime Net: ${primeNet.toLocaleString('fr-FR')} DZD</p>
                <p>Prime avec Surcharge: <strong>${primeAvecSurcharge.toLocaleString('fr-FR')} DZD</strong></p>
                <p>Prime avec Réduction: <strong>${primeAvecReduction.toLocaleString('fr-FR')} DZD</strong></p>
                <p>Prime annuelle: <strong>${prime.toLocaleString('fr-FR')} DZD</strong></p>
                <p>Date d'effet: ${document.getElementById('date_souscription').value}</p>
                <p>Date d'expiration: ${document.getElementById('date_expiration').value}</p>
            </div>
        `;
        resultatDiv.style.display = 'block';
    }

    // Empêcher la soumission par Entrée quand le formulaire est invalide
    document.getElementById('formPrim').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            const submitBtn = document.querySelector('#souscrireBtn');
            if (submitBtn.disabled || !this.dataset.prime) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // La soumission finale
    document.getElementById('formPrim').addEventListener('submit',  function(e) {
        if (!this.dataset.prime) {
            alert("Veuillez d'abord calculer la prime");
            e.preventDefault();
            return;
        }
        document.getElementById('prime').value = this.dataset.prime;
        document.getElementById('franchise').value = this.dataset.franchise;
        const submitBtn = document.getElementById('souscrireBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Souscription en cours...';
    });
    
    // Fonction de recherche client (identique à la version véhicule)
    const btnRecherche = document.getElementById('btnRechercheClient');
    btnRecherche.addEventListener('click', function() {
        const inputRecherche = document.getElementById('recherche_client');
        const resultatsDiv = document.getElementById('resultatsClient');
        const listeClients = document.getElementById('listeClients');
        const terme = inputRecherche.value.trim();
        
        if(terme.length < 2) {
            Swal.fire("Veuillez entrer au moins 2 caractères");
            return;
        }
        
        fetch('recherche_client.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'recherche=' + encodeURIComponent(terme)
        })
        .then(response => {
            if(!response.ok) throw new Error('Erreur réseau');
            return response.json();
        })
        .then(data => {
            listeClients.innerHTML = '';
            
            if(data.error) {
                listeClients.innerHTML = `<p class="error">${data.error}</p>`;
            } 
            else if(data.message) {
                listeClients.innerHTML = `<p>${data.message}</p>`;
            }
            else if(data.clients && data.clients.length > 0) {
                data.clients.forEach(client => {
                    const clientDiv = document.createElement('div');
                    clientDiv.className = 'client-item';
                    clientDiv.innerHTML = `
                        <div class="client-info">
                            <strong>${client.nom} ${client.prenom}</strong><br>
                            Né(e) le: ${formatDate(client.date_naissance)}<br>
                            Tél: ${client.telephone}<br>
                            Email: ${client.email}
                        </div>
                        <button class="select-btn" 
                                type="button"
                                data-nom="${client.nom}"
                                data-prenom="${client.prenom}"
                                data-telephone="${client.telephone}"
                                data-email="${client.email}"
                                data-date_naissance="${client.date_naissance}">
                            Sélectionner
                        </button>
                    `;
                    listeClients.appendChild(clientDiv);
                });
            }
            
            function formatDate(dateString) {
                if (!dateString) return 'Non renseignée';
                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                return new Date(dateString).toLocaleDateString('fr-FR', options);
            }
            
            resultatsDiv.style.display = 'block';
        })
        .catch(error => {
            console.error('Erreur:', error);
            listeClients.innerHTML = '<p class="error">Erreur lors de la recherche</p>';
            resultatsDiv.style.display = 'block';
        });
    });
    
    // Gestion de la sélection d'un client
    document.getElementById('listeClients').addEventListener('click', function(e) {
        if(e.target.classList.contains('select-btn')) {
            const btn = e.target;
            document.getElementById('nom_client').value = btn.dataset.nom;
            document.getElementById('prenom_client').value = btn.dataset.prenom;
            document.getElementById('telephone').value = btn.dataset.telephone;
            document.getElementById('email').value = btn.dataset.email;
            document.getElementById('date_naissance').value = btn.dataset.date_naissance;
            document.getElementById('resultatsClient').style.display = 'none';
            document.getElementById('recherche_client').value = '';
            document.getElementById('profession').focus();
        }
    });
    
    // Masquer les résultats quand on clique ailleurs
    document.addEventListener('click', function(e) {
        const resultatsDiv = document.getElementById('resultatsClient');
        if(!resultatsDiv.contains(e.target)) {
            resultatsDiv.style.display = 'none';
        }
    });
});