# myTerrain ⚽

**myTerrain** est une plateforme complète de réservation de terrains de football (Five) au Sénégal. 
Elle permet aux gérants de terrains de digitaliser leurs plannings et leurs paiements, et aux joueurs de trouver et réserver facilement des créneaux horaires.

## 📱 Fonctionnalités

### 🧑‍💻 Application Mobile (Joueurs - Flutter)
* **Recherche et filtres** : Localisez les terrains à proximité ou selon vos préférences.
* **Réservation de créneaux** : Visualisez les disponibilités en temps réel et sélectionnez un créneau horaire.
* **Paiement mobile** : Intégration de PayDunya (Wave, Orange Money, Free Money, Carte Bancaire) pour payer l'acompte (50%) ou la totalité.
* **Gestion du profil** : Suivi des réservations (à venir, passées) et mise à jour des infos personnelles.

### 🏢 Tableau de bord Web (Gérants - Angular 17+)
* **Gestion des infrastructures** : Ajoutez et paramétrez vos terrains (tarifs, équipements, type de gazon).
* **Gestion des créneaux horaires** : Définissez vos plages d'ouverture manuellement ou générez-les automatiquement.
* **Suivi des réservations & Notifications** : Visualisez instantanément les nouvelles réservations payées, avec les acomptes versés et les coordonnées des joueurs.

### ⚙️ Backend API (Laravel 11)
* **API RESTful** sécurisée par Laravel Sanctum (Tokens).
* **Système de Webhook** pour la validation automatique des paiements asynchrones via PayDunya.
* **Gestion automatisée des statuts** (Créneau disponible -> En attente de paiement -> Réservé).
* **Système de notification (SMS / WhatsApp)** avec Twilio pour prévenir les gérants en temps réel d'une nouvelle réservation (mode développement intégré).

## 🛠️ Stack Technique

* **Frontend Mobile :** Flutter (Dart)
* **Frontend Web :** Angular 17 (TypeScript) + TailwindCSS
* **Backend :** Laravel 11 (PHP 8.2+)
* **Base de données :** MySQL
* **Paiement :** API PayDunya

## 🚀 Installation locale (Mode Développement)

### 1. Backend (Laravel)
\\\ash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
\\\

### 2. Frontend Web (Angular)
\\\ash
cd frontend
npm install
npm start
\\\
*(Le tableau de bord sera disponible sur http://localhost:4200)*

### 3. Application Mobile (Flutter)
\\\ash
cd mobile
flutter pub get
flutter run
\\\

## 🧪 Simulation de paiement (Sandbox)
Si vous ne possédez pas encore de clés d'API PayDunya en production, l'application fonctionnera en mode simulation. Lors d'un paiement, vous serez redirigé vers une page locale qui simulera le retour de PayDunya et validera la transaction.
Pour activer le mode de production, renseignez \PAYDUNYA_MASTER_KEY\, \PAYDUNYA_PRIVATE_KEY\ et \PAYDUNYA_TOKEN\ dans le fichier \.env\ du backend.

---
*Conçu avec ❤️ pour simplifier le football.*
