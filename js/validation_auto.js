// document.addEventListener('DOMContentLoaded', function() {
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
    
    // Validation pour l'âge du conducteur (minimum 18 ans)
    const dateNaissanceInput = document.getElementById('date_naissance');
   
    dateNaissanceInput.addEventListener('blur', function() {
        const naissance = new Date(this.value);
        const aujourdhui = new Date();
        const age = aujourdhui.getFullYear() - naissance.getFullYear();
        
        // Vérifie si l'anniversaire est déjà passé cette année
        if (aujourdhui.getMonth() < naissance.getMonth() || 
            (aujourdhui.getMonth() === naissance.getMonth() && aujourdhui.getDate() < naissance.getDate())) {
            age--;
        }

        if (age < 18) {
            showError(this, 'Le conducteur doit avoir au moins 18 ans');
        } else {
            clearError(this);
            // Mettre à jour la validation de l'expérience si nécessaire
            if (experienceInput.value) {
                experienceInput.dispatchEvent(new Event('input'));
            }
        }
    });
        const anneeVehiculeInput = document.getElementById('annee_vehicule');
        anneeVehiculeInput.addEventListener('blur', function() {
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
    // Validation pour le bonus-malus (entre 0.5 et 3.5)
    const bonusMalusInput = document.getElementById('bonus_malus');
    bonusMalusInput.addEventListener('input', function() {
        const value = parseFloat(this.value);
        if (this.value && (value < 0.5 || value > 3.5)) {
            showError(this, 'Le coefficient doit être entre 0.5 et 3.5');
        } else {
            clearError(this);
        }
    });
    // Validation numéro de série 
    document.getElementById('numero_serie').addEventListener('blur', function() {
            if (this.value.length < 3) {
                showError(this, 'Trop court (min 3 caractères)');
            } else {
                clearError(this);
            }
        });
    // });
  
    // Validation pour l'immatriculation algérienne
    const immatInput = document.getElementById('immatriculation');
    immatInput.addEventListener('blur', function() {
        const value = this.value.trim();
        
        // Expression régulière pour le format algérien : 12345-321-16 ou 123456-321-16
        // - 5 ou 6 chiffres (numéro de dossier)
        // - 3 chiffres (type + année)
        // - 2 chiffres (wilaya)
        const algerianPlateRegex = /^(\d{5,6})-(\d{3})-(\d{2})$/;
        
        if (!value) {
            showError(this, 'Ce champ est obligatoire');
        } else if (!algerianPlateRegex.test(value) || value.length !== 13) {
            showError(this, 'Format invalide. Format attendu : 12345-321-16' );
        } else {
            // Validation supplémentaire des composants
            const matches = value.match(algerianPlateRegex);
            const typeVehicule = matches[2][0]; // Premier chiffre du deuxième groupe
            const anneeCirculation = matches[2].substring(1); // Deux derniers chiffres du deuxième groupe
            const wilaya = matches[3]; // Troisième groupe
            
            const currentYearLastTwoDigits = new Date().getFullYear() % 100;
            const errors = [];
            
            // Validation du type de véhicule (1-9)
            if (typeVehicule < '1' || typeVehicule > '9') {
                errors.push('Le type de véhicule doit être entre 1 et 9');
            }
            
            // Validation de l'année (00 à année en cours)
            if (parseInt(anneeCirculation) > currentYearLastTwoDigits) {
                errors.push(`L'année de circulation ne peut pas dépasser ${currentYearLastTwoDigits}`);
            }
            
            // Validation de la wilaya (01-58)
            if (parseInt(wilaya) < 1 || parseInt(wilaya) > 58) {
                errors.push('La wilaya doit être entre 01 et 58');
            }
            
            if (errors.length > 0) {
                showError(this, errors.join('<br>'));
            } else {
                clearError(this);
            }
        }
    });

    // Permettre aussi la saisie sans tirets mais ajouter automatiquement les tirets
    immatInput.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, ''); // Supprimer tout ce qui n'est pas un chiffre
        
        if (value.length > 6) {
            // Après 6 chiffres, ajouter le premier tiret
            value = value.substring(0, 6) + '-' + value.substring(6);
        }
        if (value.length > 10) {
            // Après 3 chiffres suivants, ajouter le deuxième tiret
            value = value.substring(0, 10) + '-' + value.substring(10);
        }
        if (value.length > 13) {
            // Limiter à 13 caractères au total (6-3-2)
            value = value.substring(0, 13);
        }
        
        this.value = value;
        });
    
    //Set les dates automatique et des Validation
    const dateSubscription = document.getElementById('date_souscription');
    dateSubscription.value = new Date().toISOString().split('T')[0]; // Date par défaut à aujourd'hui
    const dateExpiration = document.getElementById('date_expiration');
    dateExpiration.value = new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().split('T')[0]; // Date d'expiration par défaut à +1 an
    
    dateSubscription.addEventListener('change', function() {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const selectedDate = new Date(this.value);
        
        if (selectedDate < today) {
            showError(this, 'La date de souscription ne peut pas être dans le passé');
        } else {
            clearError(this);
            // Définir automatiquement la date d'expiration à +1 an
            if (this.value) {
                const expirationDate = new Date(selectedDate);
                expirationDate.setFullYear(expirationDate.getFullYear() + 1);
                
                // Format YYYY-MM-DD pour input date
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
    
    // Validation de l'expérience du conducteur par rapport à son âge
    const experienceInput = document.getElementById('experience_conducteur');
    experienceInput.addEventListener('input', function() {
        if (dateNaissanceInput.value && this.value) {
            const naissance = new Date(dateNaissanceInput.value);
            const aujourdhui = new Date();
            let age = aujourdhui.getFullYear() - naissance.getFullYear();
            
            // Ajustement si anniversaire pas encore passé
            if (aujourdhui.getMonth() < naissance.getMonth() || 
                (aujourdhui.getMonth() === naissance.getMonth() && aujourdhui.getDate() < naissance.getDate())) {
                age--;
            }

            const experience = parseInt(this.value);
            const experienceMax = age - 18;
            
            if (experience > experienceMax) {
                showError(this, `L'expérience ne peut dépasser ${experienceMax} ans (âge: ${age} ans)`);
            } else {
                clearError(this);
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
            alert('Veuillez corriger les erreurs avant de calculer');
            return;
        }
    
        // Calcul AJAX 
        const btnCalculer = this;
        btnCalculer.disabled = true;
        btnCalculer.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Calcul en cours...';
    
        try {
            const formData = new FormData(document.getElementById('formPrim'));
            const response = await fetch('calcul_prime_auto.php', {
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
                document.getElementById('marque_vehicule').focus();
            }
        });
        
        // Masquer les résultats quand on clique ailleurs
        document.addEventListener('click', function(e) {
            if(!resultatsDiv.contains(e.target) && e.target !== inputRecherche && e.target !== btnRecherche) {
                resultatsDiv.style.display = 'none';
            }
        });
        });
        
     
        //Gestion les Types des Marque
        const marqueSelect = document.getElementById('marque_vehicule');
        const typeSelect = document.getElementById('type_vehicule');
        
        const typesParMarque = {
            'renault': ['Clio', 'Megane', 'Kadjar'],
            'peugeot': ['208', '308', '3008'],
            'citroen': ['C3', 'C4', 'C5 Aircross'],
            'volkswagen': ['Golf', 'Passat', 'Tiguan'],
            'bmw': ['Série 1', 'Série 3', 'X3']
        };
        
        marqueSelect.addEventListener('change', function() {
            const marque = this.value;
            typeSelect.innerHTML = '<option value="">-- Sélectionnez --</option>';
            
            if(marque && typesParMarque[marque]) {
                typesParMarque[marque].forEach(type => {
                    const option = document.createElement('option');
                    option.value = type;
                    option.textContent = type;
                    typeSelect.appendChild(option);
                });
            }
        });

