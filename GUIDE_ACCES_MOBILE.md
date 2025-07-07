# 📱 Guide d'accès EduConnect sur iPhone avec XAMPP

## 🔧 Configuration XAMPP pour accès mobile

### **1. 📍 Trouver l'adresse IP de votre ordinateur**

#### **Sur Windows :**
```cmd
ipconfig
```
Cherchez votre adresse IP locale (ex: `192.168.1.100`)

#### **Sur Mac :**
```bash
ifconfig | grep inet
```

#### **Sur Linux :**
```bash
hostname -I
```

### **2. ⚙️ Configuration XAMPP**

#### **A. Modifier httpd.conf**
1. Ouvrez **XAMPP Control Panel**
2. Cliquez sur **Config** à côté d'Apache
3. Sélectionnez **httpd.conf**
4. Trouvez la ligne :
   ```apache
   Listen 8080
   ```
5. Ajoutez en dessous :
   ```apache
   Listen 0.0.0.0:8080
   ```

#### **B. Autoriser les connexions externes**
Trouvez cette section et modifiez-la :
```apache
<Directory "C:/xampp/htdocs">
    Options Indexes FollowSymLinks Includes ExecCGI
    AllowOverride All
    Require all granted
</Directory>
```

#### **C. Redémarrer Apache**
- Dans XAMPP Control Panel, **Stop** puis **Start** Apache

### **3. 🔥 Configuration du pare-feu Windows**

#### **Autoriser le port 8080 :**
1. **Panneau de configuration** → **Système et sécurité** → **Pare-feu Windows Defender**
2. **Paramètres avancés**
3. **Règles de trafic entrant** → **Nouvelle règle**
4. **Port** → **TCP** → **8080**
5. **Autoriser la connexion**
6. Nommez la règle : "XAMPP Apache 8080"

### **4. 📱 Accès depuis votre iPhone**

#### **Adresse à utiliser :**
```
http://[VOTRE_IP_LOCALE]:8080/login.php
```

**Exemple :**
```
http://192.168.1.100:8080/login.php
```

#### **Étapes sur iPhone :**
1. **Connectez votre iPhone au même WiFi** que votre ordinateur
2. **Ouvrez Safari**
3. **Tapez l'adresse complète** avec votre IP et le port 8080
4. **Connectez-vous** avec vos identifiants AD

### **5. 🏠 Créer un raccourci sur l'écran d'accueil**

1. **Sur Safari**, allez sur votre site EduConnect
2. **Tapez le bouton Partager** 📤
3. **"Sur l'écran d'accueil"**
4. **Nommez-le** "EduConnect"
5. **Ajouter**

Maintenant vous avez une icône EduConnect sur votre iPhone ! 🎉

### **6. 🔍 Dépannage**

#### **Si ça ne marche pas :**

**A. Vérifiez la connectivité :**
```bash
# Sur votre ordinateur, testez :
ping [VOTRE_IP_LOCALE]
```

**B. Testez depuis un autre appareil :**
- Essayez d'abord depuis un autre ordinateur sur le même réseau
- Adresse : `http://[VOTRE_IP]:8080`

**C. Vérifiez XAMPP :**
- Apache doit être **vert** dans XAMPP Control Panel
- Port 8080 doit être **libre** (pas utilisé par autre chose)

**D. Vérifiez les logs :**
- Dans XAMPP : **Logs** → **Apache (error.log)**

### **7. 📋 Checklist rapide**

✅ **XAMPP Apache démarré** sur port 8080  
✅ **Pare-feu configuré** pour autoriser le port 8080  
✅ **iPhone connecté au même WiFi**  
✅ **IP locale trouvée** (ex: 192.168.1.100)  
✅ **Adresse testée** : `http://[IP]:8080/login.php`  

### **8. 🚀 Optimisations supplémentaires**

#### **A. Fichier .htaccess pour mobile :**
```apache
# Dans votre dossier racine
<IfModule mod_headers.c>
    Header set X-UA-Compatible "IE=edge"
    Header set Cache-Control "no-cache, must-revalidate"
</IfModule>

# Compression pour mobile
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/css text/javascript application/javascript
</IfModule>
```

#### **B. Configuration PHP pour mobile :**
```php
<?php
// À ajouter en haut de vos pages principales
header('X-UA-Compatible: IE=edge');
header('Cache-Control: no-cache, must-revalidate');

// Détection mobile
function isMobile() {
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}
?>
```

### **9. 🔐 Sécurité pour accès mobile**

#### **Recommandations :**
- **Changez le port par défaut** (ex: 8080 → 8443)
- **Utilisez HTTPS** si possible (certificat SSL)
- **Limitez l'accès** aux IP de votre réseau local
- **Surveillez les logs** d'accès

#### **Configuration sécurisée httpd.conf :**
```apache
# Limiter l'accès au réseau local uniquement
<Directory "C:/xampp/htdocs">
    Options Indexes FollowSymLinks Includes ExecCGI
    AllowOverride All
    Require ip 192.168.1
    Require ip 192.168.0
    Require ip 10.0.0
</Directory>
```

### **10. 📱 Test final**

**Adresse complète à taper sur iPhone :**
```
http://[VOTRE_IP_LOCALE]:8080/login.php
```

**Exemple concret :**
```
http://192.168.1.100:8080/login.php
```

Votre système EduConnect est maintenant accessible depuis votre iPhone ! 🎉📱

---

## 🆘 Support rapide

**Problème courant :** "Site inaccessible"
**Solution :** Vérifiez que votre ordinateur et iPhone sont sur le même WiFi

**Problème courant :** "Connexion refusée"
**Solution :** Vérifiez le pare-feu Windows et la configuration Apache

**Problème courant :** "Page non trouvée"
**Solution :** Vérifiez que vos fichiers PHP sont dans `C:/xampp/htdocs/`