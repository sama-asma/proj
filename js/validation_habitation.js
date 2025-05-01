document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formPrim');
    let isValide = true;

    // Fonction pour afficher les messages d'erreur
    function showError(input, message) {
        // Supprime les messages d'erreur existants
        const existingError = input.parentElement.querySelector('.error-message');
        if (existingError) existingError.remove();
        
        // Crée un nouvel élément pour le message d'erreur
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.style.color = 'red';
        errorDiv.style.fontSize = '12px';
        errorDiv.style.marginTop = '5px';
        errorDiv.textContent = message;
        // Ajoute le message après l'input
        input.parentElement.appendChild(errorDiv);
        input.style.borderColor = 'red';
    };
    // Fonction pour nettoyer les erreurs
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
        // Revalidation quand l'utilisateur quitte le champ (blur)
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
    // Variables globales
    let wilayasData = [];
    let communesData = [];
    let wilayaCode;
    // Chargement des données
    async function loadGeoData() {
        try {
            // Chargez les fichiers JSON 
            const [wilayasResponse, communesResponse] = await Promise.all([
                fetch('js/wilayas.json'),
                fetch('js/communes.json')
            ]);
            
            wilayasData = await wilayasResponse.json();
            communesData = await communesResponse.json();
            
            remplirWilayas();
        } catch (error) {
            console.error("Erreur de chargement des données:", error);
        }
    }
    
   // Remplir la liste des wilayas
    function remplirWilayas() {
        const wilayaSelect = document.getElementById('wilaya');
        wilayaSelect.innerHTML = '<option value="">-- Sélectionnez --</option>';
        
        wilayasData.forEach(wilaya => {
            const option = document.createElement('option');
            option.value = wilaya.nom; // Stocke le NOM dans value (pour la BDD)
            option.textContent = wilaya.nom;
            option.dataset.code = wilaya.code; // Stocke le CODE en data attribute
            wilayaSelect.appendChild(option);
        });
    }
    loadGeoData();
    // Gestion du changement de wilaya
    document.getElementById('wilaya').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const wilayaCode = selectedOption.dataset.code; // Récupère le CODE depuis data attribute
        const wilayaNom = selectedOption.value; // Récupère le NOM
        
        const communeSelect = document.getElementById('commune');
        communeSelect.innerHTML = '<option value="">-- Sélectionnez --</option>';
        communeSelect.disabled = !wilayaCode;
        
        if (wilayaCode) {
            // Filtrer les communes par code de wilaya
            const communes = communesData.filter(commune => commune.wilaya_id == wilayaCode);
            
            communes.forEach(commune => {
                const option = document.createElement('option');
                option.value = commune.nom; // Stocke le NOM dans value (pour la BDD)
                option.textContent = commune.nom;
                option.dataset.code = commune.code; // Stocke le CODE en data attribute
                communeSelect.appendChild(option);
            });
        }
        
        console.log("Wilaya sélectionnée:", {
            nom: wilayaNom, 
            code: wilayaCode
        });
    });
    // la validation de  l'année de construction
    const anneeConstructionInput = document.getElementById('annee_construction');
    anneeConstructionInput.addEventListener('blur', function() {
        if (!this.value) {
            showError(this, 'Ce champ est obligatoire');
            return;
        }
        const annee = parseInt(this.value);
        const anneeActuelle = new Date().getFullYear();
        
        if (annee < 1900 || annee > anneeActuelle) {
            showError(this, `L'année doit être entre 1900 et ${anneeActuelle}`);
        } else {
            clearError(this);
        }
    });

    // Validation de la superficie
    const superficieInput = document.getElementById('superficie');
    superficieInput.addEventListener('blur', function() {
        if (!this.value) {
            showError(this, 'Ce champ est obligatoire');
            return;
        }
       superficie = parseInt(this.value);
        if (superficie < 10 || superficie > 1000) {
            showError(this, 'La superficie doit être entre 10 et 1000 m²');
        } else {
            clearError(this);
        }
    });

    // Validation du capital mobilier
    const capitalInput = document.getElementById('capital_mobilier');
    capitalInput.addEventListener('blur', function() {
        if (!this.value) {
            showError(this, 'Ce champ est obligatoire');
            return;
        }
        const capital = parseFloat(this.value);
        
        if (capital < 0 || capital > 5000000) {
            showError(this, 'Le capital doit être entre 0 et 5 000 000 DZD');
        } else {
            clearError(this);
        }
    });
    // Validation du nombre de sinistres
    const sinistresInput = document.getElementById('antecedents');
    sinistresInput.addEventListener('blur', function() {
        if (!this.value) {
            showError(this, 'Ce champ est obligatoire');
            return;
        }
        const nbSinistres = parseInt(this.value);
        if (nbSinistres < 0 || nbSinistres > 20) {
            showError(this, 'Nombre de sinistres invalide (0-20)');
        } else {
            clearError(this);
        }
    });
    
    //Set les dates automatique et des Validation
    const dateSubscription = document.getElementById('date_souscription');
    dateSubscription.value = new Date().toISOString().split('T')[0]; // Date par défaut à aujourd'hui
    const dateExpiration = document.getElementById('date_expiration');
    dateExpiration.value = new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().split('T')[0]; // Date d'expiration par défaut à +1 an
    
    dateSubscription.addEventListener('change', function() {
        today = new Date();
        today.setHours(0, 0, 0, 0);
        selectedDate = new Date(this.value);
        
        if (selectedDate < today) {
            showError(this, 'La date de souscription ne peut pas être dans le passé');
        } else {
            clearError(this);
            // Définir automatiquement la date d'expiration à +1 an
            if (this.value) {
                 expirationDate = new Date(selectedDate);
                expirationDate.setFullYear(expirationDate.getFullYear() + 1);
                
                // Format YYYY-MM-DD pour input date
                 formattedDate = expirationDate.toISOString().split('T')[0];
                dateExpiration.value = formattedDate;
            }
        }
    });
    document.getElementById('calculerPrimeBtn').addEventListener('click', async function() {
        isValide = true;        
        
        requiredInputs.forEach(input => {
            if (!input.value.trim()) {
                isValide = false;
                showError(input, 'Ce champ est obligatoire');
            }
        });
    
        // Vérifiez s'il y a des erreurs
        if (!isValide) {
            swal.fire('Veuillez corriger les erreurs avant de calculer');
            return;
        }
    
        // Calcul AJAX 
        const btnCalculer = this;
        btnCalculer.disabled = true;
        btnCalculer.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Calcul en cours...';
    
        try {
            const formData = new FormData(document.getElementById('formPrim'));
            const response = await fetch('calcul_prime_habitation.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Erreur lors du calcul');
            }
            afficherResultatPrime(data.prime, data.primeNet);
            document.getElementById('souscrireBtn').style.display = 'inline-block';
            document.getElementById('formPrim').dataset.prime = data.prime; // ajouter l'attribut data-prime en html
            document.getElementById('formPrim').dataset.franchise = data.franchise;
        } catch (error) {
            console.error("Erreur:", error);
            alert("Erreur lors du calcul: " + error.message);
        } finally {
            btnCalculer.disabled = false;
            btnCalculer.textContent = 'Calculer la prime';
        }
    });
    // Fonction pour afficher le résultat de la prime
    function afficherResultatPrime(prime, primeNet) {
        const resultatDiv = document.getElementById('resultatCalcul');
        const detailDiv = document.getElementById('detailPrime');
    
        // Récupération des valeurs de surcharge et réduction 
        const surcharge = parseFloat(document.getElementById('surcharge').value) / 100 || 0;
        const reduction = parseFloat(document.getElementById('reduction').value) / 100  || 0;
    
        const primeAvecSurcharge = primeNet * (1 + surcharge);
        const primeAvecReduction = primeAvecSurcharge * (1 - reduction);
    
        // Affichage des résultats
        detailDiv.innerHTML = `
        <div class="prime-result">
            <h3>Détails de la prime</h3>
            <p>Prime Net: ${primeNet.toLocaleString('fr-FR')} DZD</p>
            <p>Prime ajustée: ${prime.toLocaleString('fr-FR')} DZD</p>
            <p>Validité: Du ${document.getElementById('date_souscription').value} au ${document.getElementById('date_expiration').value}</p>
            <p>Superficie assurée: ${document.getElementById('superficie').value} m²</p>
            <p>Capital mobilier: ${document.getElementById('capital_mobilier').value} DZD</p>
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
        
        // Vérifiez si la prime a été calculée
        if (!this.dataset.prime) {
            alert("Veuillez d'abord calculer la prime");
            return;
        }
        document.getElementById('prime').value = this.dataset.prime;
        document.getElementById('franchise').value = this.dataset.franchise;
        const submitBtn = document.getElementById('souscrireBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Souscription en cours...';

    });
        const btnRecherche = document.getElementById('btnRechercheClient');
        
        // Fonction de recherche
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
                // Fonction helper pour formater la date
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
               // Gestion de la sélection d'un client
            listeClients.addEventListener('click', function(e) {
            if(e.target.classList.contains('select-btn')) {
                const btn = e.target;
                
                // Remplissage automatique des champs
                document.getElementById('nom_client').value = btn.dataset.nom;
                document.getElementById('prenom_client').value = btn.dataset.prenom;
                document.getElementById('telephone').value = btn.dataset.telephone;
                document.getElementById('email').value = btn.dataset.email;
                document.getElementById('date_naissance').value = btn.dataset.date_naissance;
                // Masquer les résultats
                resultatsDiv.style.display = 'none';
                
                // Vider le champ de recherche
                inputRecherche.value = '';
                
                // Focus sur le champ suivant
                document.getElementById('statut_logement').focus();
            }
        });
        
        // Masquer les résultats quand on clique ailleurs
        document.addEventListener('click', function(e) {
            if(!resultatsDiv.contains(e.target) && e.target !== inputRecherche && e.target !== btnRecherche) {
                resultatsDiv.style.display = 'none';
            }
        });
        });
        
});

