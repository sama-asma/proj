document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formVie');
    let isValide = true;

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
    }

    function clearError(input) {
        const existingError = input.parentElement.querySelector('.error-message');
        if (existingError) existingError.remove();
        input.style.borderColor = '#ddd';
    }
    
    // Validation générale des champs requis
    const requiredInputs = form.querySelectorAll('input[required], select[required]');
    requiredInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (!this.value.trim()) {
                showError(this, 'Ce champ est obligatoire');
            } else {
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

    // Validation pour l'âge (minimum 18 ans, maximum 70 ans)
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
        } else if (age > 70) {
            showError(this, 'L\'assuré ne peut pas avoir plus de 70 ans');
        } else {
            clearError(this);
        }
    });

    // Validation pour le capital (minimum 100 000 DZD)
    const capitalInput = document.getElementById('capital');
    capitalInput.addEventListener('blur', function() {
        if (this.value < 100000) {
            showError(this, 'Le capital minimum est de 100 000 DZD');
        } else {
            clearError(this);
        }
    });

    // Validation pour la durée du contrat (5 à 30 ans)
    const dureeInput = document.getElementById('duree');
    dureeInput.addEventListener('blur', function() {
        if (this.value < 5 || this.value > 30) {
            showError(this, 'La durée doit être entre 5 et 30 ans');
        } else {
            clearError(this);
        }
    });

    // Set la date de souscription par défaut à aujourd'hui
    const dateSubscription = document.getElementById('date_souscription');
    dateSubscription.value = new Date().toISOString().split('T')[0];

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
            Swal.fire('Erreur', 'Veuillez corriger les erreurs avant de calculer', 'error');
            return;
        }
        verifierTotalParts();
        const btnCalculer = this;
        btnCalculer.disabled = true;
        btnCalculer.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Calcul en cours...';
    
        try {
            const formData = new FormData(form);
            const response = await fetch('calcul_prime_vie.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Erreur lors du calcul');
            }
            
            afficherResultatPrime(data.prime);
            document.getElementById('souscrireBtn').style.display = 'inline-block';
            form.dataset.prime = data.prime;
            
        } catch (error) {
            console.error("Erreur:", error);
            Swal.fire('Erreur', "Erreur lors du calcul: " + error.message, 'error');
        } finally {
            btnCalculer.disabled = false;
            btnCalculer.textContent = 'Calculer la prime';
        }
    });
    
    function afficherResultatPrime(prime) {
        const detailDiv = document.getElementById('detailPrime');
        detailDiv.innerHTML = `
            <div class="prime-result">
                <p>Prime annuelle: <strong>${prime.toLocaleString('fr-FR')} DZD</strong></p>
                <p>Capital assuré: ${document.getElementById('capital').value.toLocaleString('fr-FR')} DZD</p>
                <p>Durée: ${document.getElementById('duree').value} ans</p>
                <p>Date d'effet: ${document.getElementById('date_souscription').value}</p>
            </div>
        `;
    }

    // Gestion de la soumission finale
    form.addEventListener('submit', function(e) {
        if (!this.dataset.prime) {
            e.preventDefault();
            Swal.fire('Information', 'Veuillez d\'abord calculer la prime', 'info');
            return;
        }
        
        document.getElementById('prime').value = this.dataset.prime;
        const submitBtn = document.getElementById('souscrireBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Souscription en cours...';
    });

    // Fonction de recherche client (identique aux autres formulaires)
    document.getElementById('btnRechercheClient').addEventListener('click', function() {
        const inputRecherche = document.getElementById('recherche_client');
        const resultatsDiv = document.getElementById('resultatsClient');
        const listeClients = document.getElementById('listeClients');
        const terme = inputRecherche.value.trim();
        
        if(terme.length < 2) {
            Swal.fire("Information", "Veuillez entrer au moins 2 caractères", "info");
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
            } else {
                listeClients.innerHTML = '<p>Aucun client trouvé</p>';
            }
            
            resultatsDiv.style.display = 'block';
        })
        .catch(error => {
            console.error('Erreur:', error);
            listeClients.innerHTML = '<p class="error">Erreur lors de la recherche</p>';
            resultatsDiv.style.display = 'block';
        });
    });
    
    function formatDate(dateString) {
        if (!dateString) return 'Non renseignée';
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('fr-FR', options);
    }
    
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
        // Gestion des bénéficiaires
    document.getElementById('ajouter_beneficiaire').addEventListener('click', function() {
        const nom = document.getElementById('nom_beneficiaire').value.trim();
        const lien = document.getElementById('lien_beneficiaire').value.trim();
        const part = parseInt(document.getElementById('part_beneficiaire').value);

        // Validation
        if (!nom || !lien || isNaN(part)) {
            Swal.fire('Erreur', 'Veuillez remplir tous les champs du bénéficiaire', 'error');
            return;
        }
         
        // Ajout à la liste visuelle
        const beneficiaireHTML = `
            <div class="beneficiaire-item">
                <span>${nom} (${lien}) - ${part}%</span>
                <button type="button" class="supprimer-beneficiaire">×</button>
            </div>
        `;
        document.getElementById('liste_beneficiaires').insertAdjacentHTML('beforeend', beneficiaireHTML);
        verifierTotalParts();
        // Mise à jour du champ caché (format JSON)
        majChampCacheBeneficiaires();

        // Réinitialisation des champs
        document.getElementById('nom_beneficiaire').value = '';
        document.getElementById('lien_beneficiaire').value = '';
        document.getElementById('part_beneficiaire').value = '';
    });

    // Suppression d'un bénéficiaire
    document.getElementById('liste_beneficiaires').addEventListener('click', function(e) {
        if (e.target.classList.contains('supprimer-beneficiaire')) {
            e.target.parentElement.remove();
            majChampCacheBeneficiaires();
            verifierTotalParts();
        }
    });

    // Mise à jour du champ caché
    function majChampCacheBeneficiaires() {
        const beneficiaires = [];
        document.querySelectorAll('.beneficiaire-item').forEach(item => {
            const texte = item.textContent.replace('×', '').trim();
            const [nomLien, part] = texte.split('-');
            beneficiaires.push({
                nom: nomLien.split('(')[0].trim(),
                lien: nomLien.split('(')[1].replace(')', '').trim(),
                part: parseInt(part.replace('%', '').trim())
            });
        });

        document.getElementById('beneficiaires').value = JSON.stringify(beneficiaires);
    }
    function verifierTotalParts() {
        const total = Array.from(document.querySelectorAll('.beneficiaire-item'))
            .reduce((sum, item) => {
                const part = parseInt(item.textContent.split('-')[1].replace('%', '').trim());
                return sum + part;
            }, 0);
        
        if (total !== 100) {
            Swal.fire('Attention', `Le total des parts doit être 100% (actuellement ${total}%)`, 'warning');
        }
    }
});