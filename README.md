# myTerrain 🏟️

> Plateforme numérique de réservation de terrains de football

## Architecture

| Couche | Technologie | Dossier |
|---|---|---|
| Mobile | Flutter 3 + Dart | `mobile/` |
| Web | Angular 17 + TailwindCSS | `frontend/` |
| Backend | Laravel 11 + PHP 8.2 | `backend/` |
| Base de données | MySQL 8.0 | — |
| Auth | Laravel Sanctum | — |
| Notifications | Firebase Cloud Messaging | — |
| Déploiement | Docker + Docker Compose | `docker/` |

## Structure du projet

```
myTerrain/
├── backend/          ← API REST Laravel
├── frontend/         ← Application web Angular
├── mobile/           ← Application Flutter Android/iOS
├── docker/           ← Configurations Docker & Docker Compose
└── docs/
    ├── uml/          ← Diagrammes UML (cas d'utilisation, séquence)
    ├── api/          ← Documentation des endpoints REST
    └── db/           ← Schéma MySQL et migrations
```

## Démarrage rapide

```bash
# Backend
cd backend && composer install && cp .env.example .env && php artisan migrate --seed

# Frontend
cd frontend && npm install && ng serve

# Mobile
cd mobile && flutter pub get && flutter run
```

## Documentation

- [Schéma base de données](docs/db/schema.sql)
- [Endpoints REST](docs/api/endpoints.md)
- [Diagrammes UML](docs/uml/)
