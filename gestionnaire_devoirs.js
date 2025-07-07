// Variables globales pour le gestionnaire de devoirs
let currentDevoirId = null;

// Fonction pour vérifier si la date limite est dépassée
function isDeadlinePassed(dateLimite) {
  if (!dateLimite) return false;
  
  const deadline = new Date(dateLimite);
  const now = new Date();
  
  return now > deadline;
}

// Fonction pour gérer la sélection de fichier
function handleFileSelection(file) {
  const dropZone = document.getElementById('file-drop-zone');
  const uploadContent = document.getElementById('file-upload-content');
  const selectedContent = document.getElementById('file-selected-content');
  const selectedFileName = document.getElementById('selected-file-name');
  const fileInput = document.getElementById('homework-file');

  // Vérifier la taille du fichier (10MB max)
  if (file.size > 10 * 1024 * 1024) {
    dropZone.style.borderColor = '#ef4444';
    dropZone.style.background = '#fef2f2';
    alert('Fichier trop volumineux (maximum 10MB)');
    setTimeout(() => {
      dropZone.style.borderColor = '#d1d5db';
      dropZone.style.background = '#fafafa';
    }, 2000);
    return;
  }

  // Vérifier le type de fichier
  const allowedTypes = ['.pdf', '.doc', '.docx', '.txt', '.jpg', '.jpeg', '.png', '.zip'];
  const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
  
  if (!allowedTypes.includes(fileExtension)) {
    dropZone.style.borderColor = '#ef4444';
    dropZone.style.background = '#fef2f2';
    alert('Type de fichier non autorisé');
    setTimeout(() => {
      dropZone.style.borderColor = '#d1d5db';
      dropZone.style.background = '#fafafa';
    }, 2000);
    return;
  }

  // Créer un nouveau DataTransfer pour assigner le fichier à l'input
  const dataTransfer = new DataTransfer();
  dataTransfer.items.add(file);
  fileInput.files = dataTransfer.files;

  // Mettre à jour l'affichage
  uploadContent.style.display = 'none';
  selectedContent.style.display = 'block';
  selectedFileName.textContent = file.name;
  
  // Changer l'apparence de la zone
  dropZone.style.borderColor = '#10b981';
  dropZone.style.background = '#f0fdf4';
  
  console.log('Fichier sélectionné:', file.name);
}

// Fonction pour afficher le modal de soumission de devoir
function showSubmitModal(devoirId, titre, dateLimite = null) {
  console.log("showSubmitModal appelé avec devoirId:", devoirId, "et titre:", titre);
  currentDevoirId = devoirId;

  // Vérifie si la date limite est dépassée
  const deadlinePassed = isDeadlinePassed(dateLimite);
  console.log("Deadline passée ?", deadlinePassed);
  
  if (deadlinePassed) {
    alert('La date limite pour rendre ce devoir est dépassée.');
    return;
  }

  // Vérifie si le modal existe déjà
  let modal = document.getElementById('submitModal');
  if (!modal) {
    console.log("Modal non trouvé, création du modal...");

    // Crée le modal avec un design moderne amélioré
    modal = document.createElement('div');
    modal.id = 'submitModal';
    modal.className = 'modal';
    modal.style.cssText = `
      display: flex;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0, 0, 0, 0.75);
      backdrop-filter: blur(12px);
      justify-content: center;
      align-items: center;
      z-index: 9999;
      animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      padding: 20px;
    `;

    modal.innerHTML = `
      <div class="modal-content" style="background: linear-gradient(145deg, #ffffff 0%, #f8fafc 50%, #ffffff 100%); padding: 0; border-radius: 24px; max-width: 650px; width: 92%; max-height: 90vh; position: relative; box-shadow: 0 32px 64px -12px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); overflow: hidden; animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1); transform-origin: center; display: flex; flex-direction: column;">
        
        <!-- Header du modal avec design premium -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 2.5rem 2rem; color: white; position: relative; overflow: hidden; flex-shrink: 0;">
          <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><defs><pattern id=\"grain\" width=\"100\" height=\"100\" patternUnits=\"userSpaceOnUse\"><circle cx=\"50\" cy=\"50\" r=\"1\" fill=\"%23ffffff\" opacity=\"0.1\"/></pattern></defs><rect width=\"100\" height=\"100\" fill=\"url(%23grain)\"/></svg>'); opacity: 0.3;"></div>
          
          <button onclick="closeSubmitModal()" style="position: absolute; top: 1.25rem; right: 1.25rem; background: rgba(255, 255, 255, 0.15); border: none; color: white; width: 44px; height: 44px; border-radius: 50%; cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); backdrop-filter: blur(10px);" onmouseover="this.style.background='rgba(255, 255, 255, 0.25)'; this.style.transform='scale(1.05)'" onmouseout="this.style.background='rgba(255, 255, 255, 0.15)'; this.style.transform='scale(1)'">
            <i class="fas fa-times"></i>
          </button>
          
          <div style="display: flex; align-items: center; gap: 1.5rem; position: relative; z-index: 1;">
            <div style="background: rgba(255, 255, 255, 0.2); padding: 1.25rem; border-radius: 16px; backdrop-filter: blur(20px); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);">
              <i class="fas fa-cloud-upload-alt" style="font-size: 1.75rem;"></i>
            </div>
            <div>
              <h3 id="modal-title" style="margin: 0; font-size: 1.75rem; font-weight: 700; letter-spacing: -0.025em;">Rendre le devoir</h3>
              <p style="margin: 0.75rem 0 0 0; opacity: 0.9; font-size: 1rem; font-weight: 400;">Soumettez votre travail en toute sécurité</p>
            </div>
          </div>
        </div>

        <!-- Corps du modal avec espacement amélioré et scrollbar -->
        <div style="padding: 2.5rem; overflow-y: auto; flex: 1; scrollbar-width: thin; scrollbar-color: #cbd5e1 #f1f5f9;">
          <style>
            .modal-content div::-webkit-scrollbar {
              width: 8px;
            }
            .modal-content div::-webkit-scrollbar-track {
              background: #f1f5f9;
              border-radius: 4px;
            }
            .modal-content div::-webkit-scrollbar-thumb {
              background: #cbd5e1;
              border-radius: 4px;
            }
            .modal-content div::-webkit-scrollbar-thumb:hover {
              background: #94a3b8;
            }
          </style>
          
          <form id="submitForm" enctype="multipart/form-data">
            <input type="hidden" id="submit-devoir-id" name="devoir_id">
            
            <!-- Titre du devoir avec design premium -->
            <div style="margin-bottom: 2rem;">
              <label for="homework-title" style="display: block; font-weight: 700; color: #1f2937; margin-bottom: 0.75rem; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Titre du devoir</label>
              <input type="text" id="homework-title" name="homework_title" readonly style="width: 100%; padding: 1rem 1.25rem; border: 2px solid #e5e7eb; border-radius: 16px; font-size: 1.1rem; background: linear-gradient(145deg, #f9fafb, #f3f4f6); color: #4b5563; font-weight: 600; box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06);">
            </div>
            
            <!-- Zone de téléchargement de fichier améliorée -->
            <div style="margin-bottom: 2rem;">
              <label for="homework-file" style="display: block; font-weight: 700; color: #1f2937; margin-bottom: 0.75rem; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Fichier à joindre *</label>
              
              <div id="file-drop-zone" style="border: 3px dashed #d1d5db; border-radius: 20px; padding: 3rem 2rem; text-align: center; background: linear-gradient(145deg, #fafafa, #f5f5f5); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; position: relative; overflow: hidden;" onclick="document.getElementById('homework-file').click()">
                
                <!-- Effet de brillance au survol -->
                <div style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent); transform: translateX(-100%); transition: transform 0.6s;"></div>
                
                <div id="file-upload-content">
                  <div style="margin-bottom: 1.5rem;">
                    <div style="display: inline-block; background: linear-gradient(135deg, #3b82f6, #1d4ed8); padding: 1.5rem; border-radius: 20px; box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);">
                      <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: white;"></i>
                    </div>
                  </div>
                  <p style="margin: 0; color: #374151; font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem;">
                    Cliquez pour sélectionner un fichier
                  </p>
                  <p style="margin: 0; font-size: 0.9rem; color: #6b7280; margin-bottom: 1rem;">
                    ou glissez-déposez votre fichier ici
                  </p>
                  <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 0.5rem; margin-top: 1rem;">
                    <span style="background: #eff6ff; color: #1d4ed8; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">PDF</span>
                    <span style="background: #f0f9ff; color: #0284c7; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">DOC</span>
                    <span style="background: #ecfdf5; color: #059669; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">IMG</span>
                    <span style="background: #fef3c7; color: #d97706; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">ZIP</span>
                  </div>
                  <p style="margin: 1rem 0 0 0; font-size: 0.8rem; color: #9ca3af;">Maximum 10MB</p>
                </div>
                
                <div id="file-selected-content" style="display: none;">
                  <div style="margin-bottom: 1rem;">
                    <div style="display: inline-block; background: linear-gradient(135deg, #10b981, #059669); padding: 1.25rem; border-radius: 16px; box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);">
                      <i class="fas fa-file-check" style="font-size: 2rem; color: white;"></i>
                    </div>
                  </div>
                  <p id="selected-file-name" style="margin: 0; color: #1f2937; font-weight: 700; font-size: 1.1rem; margin-bottom: 0.5rem;"></p>
                  <p style="margin: 0; font-size: 0.9rem; color: #10b981; font-weight: 600;">✓ Fichier prêt à être envoyé</p>
                  <button type="button" onclick="resetFileSelection()" style="margin-top: 1rem; background: none; border: 2px solid #e5e7eb; color: #6b7280; padding: 0.5rem 1rem; border-radius: 12px; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; font-weight: 600;" onmouseover="this.style.borderColor='#3b82f6'; this.style.color='#3b82f6'" onmouseout="this.style.borderColor='#e5e7eb'; this.style.color='#6b7280'">
                    Changer de fichier
                  </button>
                </div>
              </div>
              
              <input type="file" id="homework-file" name="fichier_rendu" required accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.zip" style="display: none;">
            </div>
            
            <!-- Zone de commentaire avec design amélioré -->
            <div style="margin-bottom: 2.5rem;">
              <label for="homework-comment" style="display: block; font-weight: 700; color: #1f2937; margin-bottom: 0.75rem; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Commentaire (optionnel)</label>
              <textarea id="homework-comment" name="commentaire" rows="4" placeholder="Ajoutez un commentaire sur votre travail, des explications ou des notes..." style="width: 100%; padding: 1rem 1.25rem; border: 2px solid #e5e7eb; border-radius: 16px; font-size: 1rem; resize: vertical; min-height: 120px; font-family: inherit; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: white; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='0 1px 3px rgba(0, 0, 0, 0.1)'"></textarea>
            </div>
            
            <!-- Boutons d'action avec design premium -->
            <div style="display: flex; justify-content: flex-end; gap: 1rem; padding-top: 1.5rem; border-top: 2px solid #f3f4f6;">
              <button type="button" onclick="closeSubmitModal()" style="padding: 1rem 2rem; border: 2px solid #e5e7eb; background: white; color: #6b7280; border-radius: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); font-size: 0.95rem;" onmouseover="this.style.borderColor='#d1d5db'; this.style.background='#f9fafb'; this.style.transform='translateY(-1px)'" onmouseout="this.style.borderColor='#e5e7eb'; this.style.background='white'; this.style.transform='translateY(0)'">
                <i class="fas fa-times" style="margin-right: 0.5rem;"></i>
                Annuler
              </button>
              <button type="button" id="submit-btn" onclick="submitHomework()" style="padding: 1rem 2.5rem; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; border: none; border-radius: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); font-size: 0.95rem; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(59, 130, 246, 0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 14px rgba(59, 130, 246, 0.4)'">
                <i class="fas fa-paper-plane" style="margin-right: 0.5rem;"></i>
                Rendre le devoir
              </button>
            </div>
          </form>
        </div>
      </div>
    `;

    // Ajouter les styles d'animation améliorés
    const style = document.createElement('style');
    style.textContent = `
      @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
      }
      @keyframes slideUp {
        from { transform: translateY(60px) scale(0.95); opacity: 0; }
        to { transform: translateY(0) scale(1); opacity: 1; }
      }
      @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
      }
      #file-drop-zone:hover .shine {
        transform: translateX(100%);
      }
    `;
    document.head.appendChild(style);
    document.body.appendChild(modal);

    // Ajouter les événements de drag & drop
    const dropZone = document.getElementById('file-drop-zone');
    const fileInput = document.getElementById('homework-file');

    // Événements de drag & drop
    dropZone.addEventListener('dragover', function(e) {
      e.preventDefault();
      this.style.borderColor = '#3b82f6';
      this.style.background = 'linear-gradient(145deg, #eff6ff, #dbeafe)';
      this.style.transform = 'scale(1.02)';
    });

    dropZone.addEventListener('dragleave', function(e) {
      e.preventDefault();
      this.style.borderColor = '#d1d5db';
      this.style.background = 'linear-gradient(145deg, #fafafa, #f5f5f5)';
      this.style.transform = 'scale(1)';
    });

    dropZone.addEventListener('drop', function(e) {
      e.preventDefault();
      this.style.borderColor = '#d1d5db';
      this.style.background = 'linear-gradient(145deg, #fafafa, #f5f5f5)';
      this.style.transform = 'scale(1)';
      
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        handleFileSelection(files[0]);
      }
    });

    // Événement de sélection de fichier
    fileInput.addEventListener('change', function(e) {
      if (e.target.files.length > 0) {
        handleFileSelection(e.target.files[0]);
      }
    });
  }

  // Met à jour les valeurs du modal
  console.log("Mettre à jour les valeurs du modal...");
  document.getElementById('modal-title').textContent = `Rendre le devoir : ${titre}`;
  document.getElementById('submit-devoir-id').value = devoirId;
  document.getElementById('homework-title').value = titre;
  
  // Réinitialiser l'état du fichier
  resetFileSelection();
  document.getElementById('homework-comment').value = '';

  // Affiche le modal
  modal.style.display = 'flex';
  console.log("Modal affiché !");
  
  // Focus sur le modal pour l'accessibilité
  modal.focus();
}

// Fonction pour réinitialiser la sélection de fichier
function resetFileSelection() {
  const dropZone = document.getElementById('file-drop-zone');
  const uploadContent = document.getElementById('file-upload-content');
  const selectedContent = document.getElementById('file-selected-content');
  const fileInput = document.getElementById('homework-file');

  if (uploadContent && selectedContent && dropZone && fileInput) {
    uploadContent.style.display = 'block';
    selectedContent.style.display = 'none';
    dropZone.style.borderColor = '#d1d5db';
    dropZone.style.background = 'linear-gradient(145deg, #fafafa, #f5f5f5)';
    fileInput.value = '';
  }
}

// Fonction pour fermer le modal
function closeSubmitModal() {
  const modal = document.getElementById('submitModal');
  if (modal) {
    modal.style.animation = 'fadeOut 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
    setTimeout(() => {
      modal.style.display = 'none';
      modal.style.animation = '';
    }, 300);
  }
}

// Fonction pour soumettre le devoir
function submitHomework() {
  const form = document.getElementById('submitForm');
  const fileInput = document.getElementById('homework-file');
  const commentInput = document.getElementById('homework-comment');
  const submitBtn = document.getElementById('submit-btn');

  console.log("Début de la soumission du devoir...");

  if (!fileInput.files[0]) {
    console.log("Aucun fichier sélectionné.");
    
    // Animation d'erreur sur la zone de fichier
    const dropZone = document.getElementById('file-drop-zone');
    dropZone.style.borderColor = '#ef4444';
    dropZone.style.background = 'linear-gradient(145deg, #fef2f2, #fee2e2)';
    dropZone.style.transform = 'scale(1.02)';
    setTimeout(() => {
      dropZone.style.borderColor = '#d1d5db';
      dropZone.style.background = 'linear-gradient(145deg, #fafafa, #f5f5f5)';
      dropZone.style.transform = 'scale(1)';
    }, 2000);
    
    alert('Veuillez sélectionner un fichier.');
    return;
  }

  if (fileInput.files[0].size > 10 * 1024 * 1024) {
    console.log("Fichier trop volumineux.");
    alert('Fichier trop volumineux (max 10MB).');
    return;
  }

  const formData = new FormData(form);
  formData.append('ajax', '1'); // Ajouter un paramètre pour indiquer que c'est une requête AJAX

  console.log("Préparation à l'envoi des données...");
  console.log("Commentaire:", commentInput.value);

  const originalHTML = submitBtn.innerHTML;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 0.5rem;"></i>Envoi en cours...';
  submitBtn.disabled = true;
  submitBtn.style.background = 'linear-gradient(135deg, #9ca3af, #6b7280)';
  submitBtn.style.cursor = 'not-allowed';
  submitBtn.style.transform = 'none';

  // Utilisation de fetch au lieu de XMLHttpRequest pour une meilleure gestion
  fetch('rendre_devoir.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    console.log("Réponse reçue du serveur, status:", response.status);
    
    // Vérifier si la réponse est OK (status 200-299)
    if (response.ok) {
      return response.text();
    } else {
      throw new Error(`Erreur HTTP: ${response.status}`);
    }
  })
  .then(data => {
    console.log("Réponse du serveur:", data);
    
    // Vérifier si la réponse contient un indicateur de succès
    // On cherche plusieurs indicateurs possibles de succès
    if (data.includes('✅') || data.includes('succès') || data.includes('success') || data.includes('rendu')) {
      // Animation de succès
      submitBtn.innerHTML = '<i class="fas fa-check" style="margin-right: 0.5rem;"></i>Devoir rendu !';
      submitBtn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
      submitBtn.style.boxShadow = '0 4px 14px rgba(16, 185, 129, 0.4)';
      
      setTimeout(() => {
        alert('Devoir rendu avec succès.');
        closeSubmitModal();
        location.reload();
      }, 1000);
    } else {
      console.log("Réponse ne contient pas d'indicateur de succès");
      throw new Error('Réponse inattendue du serveur');
    }
  })
  .catch(error => {
    console.error('Erreur lors de la soumission:', error);
    
    // Restaurer le bouton en cas d'erreur
    submitBtn.innerHTML = originalHTML;
    submitBtn.disabled = false;
    submitBtn.style.background = 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)';
    submitBtn.style.cursor = 'pointer';
    submitBtn.style.boxShadow = '0 4px 14px rgba(59, 130, 246, 0.4)';
    
    alert('Erreur lors de l\'envoi du devoir. Veuillez réessayer.');
  });
}

// Fermer le modal en cliquant à l'extérieur
document.addEventListener('click', function(e) {
  const modal = document.getElementById('submitModal');
  if (modal && e.target === modal) {
    closeSubmitModal();
  }
});

// Fermer le modal avec la touche Échap
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeSubmitModal();
  }
});