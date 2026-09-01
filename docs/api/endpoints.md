# myTerrain — Documentation API REST
# Version 1.0 — Phase 2 (référence pour développement)

Base URL : `https://api.myterrain.com/api/v1`
Auth : Bearer Token (Laravel Sanctum)
Format : JSON
Pagination : `?page=1&per_page=15`

---

## 🔐 Authentification

### POST /auth/register
Inscription d'un nouvel utilisateur.

**Body :**
```json
{
  "name": "Mamadou Diallo",
  "email": "mamadou@example.com",
  "phone": "+221771234567",
  "password": "motdepasse123",
  "password_confirmation": "motdepasse123",
  "role": "player"  // "player" | "owner"
}
```
**Réponse 201 :**
```json
{
  "user": { "id": 1, "name": "Mamadou Diallo", "email": "...", "role": "player" },
  "token": "1|abcdef..."
}
```

---

### POST /auth/login
Connexion et obtention du token.

**Body :**
```json
{ "email": "mamadou@example.com", "password": "motdepasse123" }
```
**Réponse 200 :**
```json
{ "user": { ... }, "token": "2|xyz..." }
```

---

### POST /auth/logout
`🔒 Auth requise` — Révocation du token courant.

**Réponse 200 :**
```json
{ "message": "Déconnecté avec succès." }
```

---

### GET /auth/me
`🔒 Auth requise` — Informations de l'utilisateur connecté.

**Réponse 200 :**
```json
{
  "id": 1, "name": "Mamadou Diallo", "email": "...",
  "role": "player", "avatar": null, "phone": "+221..."
}
```

---

### POST /auth/forgot-password
Envoi d'un email de réinitialisation.

**Body :** `{ "email": "..." }`

---

### POST /auth/reset-password
Réinitialisation du mot de passe.

**Body :** `{ "token": "...", "email": "...", "password": "...", "password_confirmation": "..." }`

---

## 🏟️ Terrains (Fields)

### GET /fields
Liste des terrains (publique, avec filtres).

**Query params :**
| Param | Type | Description |
|---|---|---|
| `city` | string | Filtrer par ville |
| `lat` / `lng` | decimal | Centre géographique |
| `radius` | int | Rayon en km (défaut: 10) |
| `date` | date | Disponibilité à cette date (YYYY-MM-DD) |
| `type` | string | `natural_grass`, `artificial_grass`, `futsal`... |
| `min_price` | decimal | Prix min par heure |
| `max_price` | decimal | Prix max par heure |
| `capacity` | int | Nombre de joueurs minimum |
| `amenities[]` | array | `douche`, `vestiaire`, `éclairage`, `parking` |

**Réponse 200 :**
```json
{
  "data": [
    {
      "id": 1, "name": "Terrain Dakar Star", "city": "Dakar",
      "type": "artificial_grass", "price_per_hour": 15000,
      "currency": "XOF", "latitude": 14.6928, "longitude": -17.4467,
      "distance_km": 1.2,
      "primary_image": "https://...",
      "avg_rating": 4.5, "reviews_count": 23,
      "is_featured": true
    }
  ],
  "meta": { "total": 42, "page": 1, "per_page": 15 }
}
```

---

### GET /fields/{id}
Détail complet d'un terrain.

**Réponse 200 :**
```json
{
  "id": 1,
  "name": "Terrain Dakar Star",
  "description": "...",
  "address": "Rue 10, Medina",
  "city": "Dakar",
  "latitude": 14.6928, "longitude": -17.4467,
  "type": "artificial_grass",
  "capacity": 10, "size": "40x20m",
  "price_per_hour": 15000, "currency": "XOF",
  "amenities": ["douche","vestiaire","éclairage"],
  "images": [
    { "id": 1, "url": "https://...", "is_primary": true }
  ],
  "owner": { "id": 1, "business_name": "Sport Pro SARL" },
  "avg_rating": 4.5, "reviews_count": 23
}
```

---

### GET /fields/{id}/availability
Créneaux disponibles pour un terrain à une date donnée.

**Query :** `?date=2025-12-25`

**Réponse 200 :**
```json
{
  "date": "2025-12-25",
  "slots": [
    { "id": 10, "start_time": "08:00", "end_time": "09:00", "status": "available", "price": 15000 },
    { "id": 11, "start_time": "09:00", "end_time": "10:00", "status": "reserved", "price": 15000 },
    { "id": 12, "start_time": "10:00", "end_time": "11:00", "status": "available", "price": 20000 }
  ]
}
```

---

### GET /fields/{id}/reviews
Avis sur un terrain.

**Réponse 200 :**
```json
{
  "data": [
    {
      "id": 1, "rating": 5, "comment": "Excellent terrain !",
      "user": { "id": 2, "name": "Ibrahima", "avatar": "..." },
      "created_at": "2025-11-01T14:30:00Z"
    }
  ],
  "avg_rating": 4.5, "total": 23
}
```

---

### POST /fields `🔒 Owner`
Créer un nouveau terrain.

**Body (multipart/form-data ou JSON) :**
```json
{
  "name": "Terrain Elite FC",
  "description": "Terrain synthétique homologué...",
  "address": "Avenue Bourguiba, Plateau",
  "city": "Abidjan",
  "latitude": 5.3544, "longitude": -4.0089,
  "type": "artificial_grass",
  "capacity": 10, "size": "40x20m",
  "price_per_hour": 20000,
  "amenities": ["vestiaire","éclairage","parking"]
}
```
**Réponse 201 :** Terrain créé.

---

### PUT /fields/{id} `🔒 Owner (propriétaire)`
Modifier un terrain.

---

### DELETE /fields/{id} `🔒 Owner (propriétaire)`
Supprimer un terrain (soft delete).

---

### POST /fields/{id}/images `🔒 Owner`
Ajouter une image à un terrain.

**Body (multipart/form-data) :** `image` (fichier), `is_primary` (bool)

---

### DELETE /fields/{id}/images/{imageId} `🔒 Owner`
Supprimer une image.

---

## 📅 Créneaux Horaires (Time Slots)

### POST /fields/{id}/slots `🔒 Owner`
Créer des créneaux pour un terrain.

**Body :**
```json
{
  "date": "2025-12-25",
  "slots": [
    { "start_time": "08:00", "end_time": "09:00" },
    { "start_time": "09:00", "end_time": "10:00" },
    { "start_time": "10:00", "end_time": "11:00" }
  ]
}
```

---

### PUT /fields/{id}/slots/{slotId} `🔒 Owner`
Modifier un créneau (statut, prix).

---

### POST /fields/{id}/slots/bulk `🔒 Owner`
Générer automatiquement les créneaux sur une plage de dates.

**Body :**
```json
{
  "start_date": "2026-01-01",
  "end_date": "2026-01-31",
  "daily_start": "07:00",
  "daily_end": "22:00",
  "slot_duration": 60  // en minutes
}
```

---

## 📋 Réservations (Reservations)

### POST /reservations `🔒 Player`
Créer une réservation. ⚠️ Transaction MySQL avec SELECT FOR UPDATE.

**Body :**
```json
{
  "field_id": 1,
  "time_slot_id": 10,
  "notes": "Nous serons 8 joueurs."
}
```
**Réponse 201 :**
```json
{
  "id": 1,
  "status": "pending",
  "field": { "id": 1, "name": "..." },
  "slot": { "date": "2025-12-25", "start_time": "08:00", "end_time": "09:00" },
  "total_price": 15000,
  "payment_url": "https://..."  // si paiement en ligne activé
}
```
**Réponse 409 :** Créneau déjà réservé.

---

### GET /reservations `🔒 Auth`
Historique des réservations de l'utilisateur connecté.

**Query :** `?status=confirmed&page=1`

---

### GET /reservations/{id} `🔒 Auth`
Détail d'une réservation.

---

### PATCH /reservations/{id}/cancel `🔒 Player (propriétaire)`
Annuler une réservation.

**Body :** `{ "reason": "Empêchement de dernière minute" }`

---

### PATCH /reservations/{id}/confirm `🔒 Owner`
Le loueur confirme manuellement une réservation en attente.

---

## 💳 Paiements (Payments)

### POST /payments `🔒 Auth`
Initier un paiement pour une réservation.

**Body :**
```json
{ "reservation_id": 1, "method": "wave" }
```
**Réponse 200 :**
```json
{ "payment_url": "https://wave.com/pay/abc123", "reference": "TXN_001" }
```

---

### POST /payments/webhook
Webhook appelé par le prestataire de paiement (non authentifié, signature vérifiée).

---

### GET /payments/{id} `🔒 Auth`
Détail d'un paiement.

---

## ⭐ Avis (Reviews)

### POST /reviews `🔒 Player`
Soumettre un avis (nécessite une réservation complétée).

**Body :**
```json
{ "field_id": 1, "rating": 5, "comment": "Excellent terrain !" }
```

---

### PUT /reviews/{id} `🔒 Player (auteur)`
Modifier un avis.

---

### DELETE /reviews/{id} `🔒 Player/Admin`
Supprimer un avis.

---

## ❤️ Favoris (Favorites)

### GET /favorites `🔒 Player`
Liste des terrains favoris.

### POST /favorites `🔒 Player`
Ajouter un favori. **Body :** `{ "field_id": 1 }`

### DELETE /favorites/{fieldId} `🔒 Player`
Retirer un favori.

---

## ⚽ Matchs (Matches)

### GET /matches
Liste des matchs ouverts (publics).

**Query :** `?city=Dakar&date=2025-12-25&level=intermediate`

---

### POST /matches `🔒 Player`
Créer un match.

**Body :**
```json
{
  "field_id": 1,
  "reservation_id": 5,
  "title": "Match amical du samedi",
  "date": "2025-12-25",
  "start_time": "08:00",
  "max_players": 10,
  "level": "intermediate",
  "is_public": true
}
```

---

### POST /matches/{id}/join `🔒 Player`
Rejoindre un match.

### DELETE /matches/{id}/leave `🔒 Player`
Quitter un match.

---

## 🔔 Notifications

### GET /notifications `🔒 Auth`
Liste des notifications (non lues en premier).

### PATCH /notifications/{id}/read `🔒 Auth`
Marquer comme lue.

### PATCH /notifications/read-all `🔒 Auth`
Tout marquer comme lu.

---

## 👤 Profil Utilisateur

### PUT /profile `🔒 Auth`
Mettre à jour son profil.

**Body :** `name`, `phone`, `avatar` (fichier)

### PUT /profile/password `🔒 Auth`
Changer son mot de passe.

---

## 🏢 Dashboard Loueur

### GET /owner/dashboard `🔒 Owner`
Statistiques globales.

**Réponse 200 :**
```json
{
  "total_fields": 3,
  "reservations_today": 5,
  "revenue_this_month": 450000,
  "pending_reservations": 2,
  "occupancy_rate": 72.5
}
```

---

### GET /owner/reservations `🔒 Owner`
Réservations de tous ses terrains.

**Query :** `?field_id=1&status=pending&date=2025-12-25`

---

### GET /owner/revenue `🔒 Owner`
Revenus par période.

**Query :** `?period=month&year=2025&month=12`

---

## 🛡️ Administration

### GET /admin/users `🔒 Admin`
Liste des utilisateurs.

### PATCH /admin/users/{id}/activate `🔒 Admin`
Activer/désactiver un compte.

### POST /admin/owners/{id}/verify `🔒 Admin`
Valider un loueur.

### GET /admin/fields `🔒 Admin`
Tous les terrains (avec filtre).

### PATCH /admin/fields/{id}/feature `🔒 Admin`
Mettre un terrain en avant.

### GET /admin/reservations `🔒 Admin`
Toutes les réservations.

### GET /admin/payments `🔒 Admin`
Tous les paiements.

### GET /admin/stats `🔒 Admin`
Statistiques globales de la plateforme.

---

## Codes d'erreur courants

| Code | Signification |
|---|---|
| 400 | Données invalides (validation) |
| 401 | Non authentifié |
| 403 | Accès interdit (mauvais rôle) |
| 404 | Ressource introuvable |
| 409 | Conflit (ex: créneau déjà réservé) |
| 422 | Entité non traitable |
| 429 | Trop de requêtes (rate limiting) |
| 500 | Erreur serveur |

---

*Documentation générée — Phase 1 myTerrain*
