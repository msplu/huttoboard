# 🗂️ Huttoboard

Un **Jira minimaliste** avec tableau **kanban**, construit avec **Symfony 8.1** et servi par
**FrankenPHP en mode worker**. Environnement de développement entièrement dockerisé.

## Fonctionnalités

- **Projets** : un projet possède un code (ex. `HUT`), une description et un tableau kanban.
- **Colonnes** : colonnes de tableau personnalisables (nom, couleur, limite WIP), réordonnables.
- **Tickets** : titre, description, priorité (Basse → Urgente), assignation, déplacement par
  **glisser-déposer** entre colonnes (Stimulus + SortableJS, persistance AJAX).
- **Utilisateurs & rôles** :
  - **Administrateur** (`ROLE_ADMIN`) : crée/modifie/supprime les projets, gère les colonnes et
    les comptes utilisateurs.
  - **Utilisateur classique** (`ROLE_USER`) : consulte les tableaux et gère les tickets.

## Stack technique

| Composant      | Choix                                                        |
|----------------|--------------------------------------------------------------|
| Framework      | Symfony 8.1 (PHP 8.4)                                         |
| Serveur        | FrankenPHP **mode worker** (runner natif `symfony/runtime`)  |
| Base de données| PostgreSQL 16                                                |
| ORM            | Doctrine ORM 3 + migrations                                  |
| Front          | Twig + AssetMapper + Stimulus + SortableJS (aucun build Node)|
| Conteneurs     | Docker Compose                                               |

## Démarrage rapide

Prérequis : **Docker** et **Docker Compose** (rien d'autre, pas besoin de PHP/Composer en local).

```bash
# 1. Construire et démarrer la stack (migrations appliquées automatiquement au démarrage)
docker compose up -d --build

# 2. Charger les données de démonstration (utilisateurs + projets + tickets)
docker compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

L'application est disponible sur **https://localhost:9443** (certificat auto-signé local — accepter
l'avertissement du navigateur). Le HTTP est sur http://localhost:9080.

> Les ports sont configurables dans `.env` (`HTTP_PORT` / `HTTPS_PORT`), car 80/443 sont
> souvent déjà occupés sur la machine hôte.

### Comptes de démonstration

| Rôle           | E-mail                  | Mot de passe |
|----------------|-------------------------|--------------|
| Administrateur | `admin@huttopia.com`    | `admin`      |
| Utilisateur    | `marie@huttopia.com`    | `marie`      |
| Utilisateur    | `paul@huttopia.com`     | `paul`       |

## Mode worker FrankenPHP

Le mode worker garde le noyau Symfony en mémoire entre les requêtes (pas de bootstrap à chaque
appel). Il est activé via `symfony/runtime` (classe `FrankenPhpWorkerRunner`), déclenché par la
variable `FRANKENPHP_WORKER=1` positionnée dans `frankenphp/worker*.Caddyfile`.

En **développement**, `frankenphp/worker.dev.Caddyfile` ajoute la directive `watch` : les workers
redémarrent automatiquement quand `src/`, `config/`, `templates/` ou `translations/` changent.
On garde donc la performance du mode worker **et** le rechargement à chaud.

> ⚠️ Le package `runtime/frankenphp-symfony` n'est **pas** utilisé : il ne supporte pas encore
> Symfony 8. Le support worker est désormais intégré nativement à `symfony/runtime`.

## Commandes utiles

```bash
# Console Symfony
docker compose exec php php bin/console <commande>

# Créer un utilisateur (ex. premier admin en production)
docker compose exec php php bin/console app:create-user --admin

# Migrations
docker compose exec php php bin/console make:migration
docker compose exec php php bin/console doctrine:migrations:migrate

# Logs du serveur
docker compose logs -f php
```

## Architecture

```
src/
├── Command/CreateUserCommand.php     # création d'utilisateur en CLI
├── Controller/                       # Project, BoardColumn, Ticket, User, Security
├── Entity/                           # User, Project, BoardColumn, Ticket
├── Enum/Priority.php                 # priorités des tickets
├── Form/                             # ProjectType, BoardColumnType, TicketType, UserType
├── Repository/                       # repositories Doctrine
└── DataFixtures/AppFixtures.php      # données de démonstration

assets/controllers/
├── board_controller.js              # glisser-déposer kanban (SortableJS)
└── flash_controller.js              # auto-masquage des messages flash

frankenphp/
├── Caddyfile                        # configuration du serveur
├── worker.Caddyfile                 # mode worker (prod)
└── worker.dev.Caddyfile             # mode worker + watch (dev)
```

### Sécurité & contrôle d'accès

- Authentification par formulaire (`form_login`) + « se souvenir de moi ».
- Hiérarchie de rôles : `ROLE_ADMIN` hérite de `ROLE_USER`.
- Actions d'administration protégées par l'attribut `#[IsGranted('ROLE_ADMIN')]` et les règles
  `access_control` (`^/admin`).
- Protection CSRF *stateful* (basée session) sur les formulaires, le login et l'API du tableau.

## Tests

Suite de tests **fonctionnels** (end-to-end au niveau HTTP, via le client Symfony) couvrant
l'authentification, le contrôle d'accès par rôle, le CRUD projets/colonnes/tickets, l'API de
déplacement kanban et la gestion des utilisateurs. Chaque test est isolé dans une transaction
annulée à la fin (DAMA DoctrineTestBundle).

```bash
# Préparer la base de test (une seule fois, ou après un changement de schéma/fixtures)
docker compose exec php php bin/console doctrine:database:create --env=test --if-not-exists
docker compose exec php php bin/console doctrine:migrations:migrate --env=test --no-interaction
docker compose exec php php bin/console doctrine:fixtures:load --env=test --no-interaction

# Lancer la suite
docker compose exec php php bin/phpunit
```

Les tests se trouvent dans `tests/Functional/` :

| Fichier                     | Couverture                                                       |
|-----------------------------|------------------------------------------------------------------|
| `SecurityControllerTest`    | login OK/KO, logout, redirection des anonymes                    |
| `ProjectControllerTest`     | CRUD projet, colonnes par défaut, unicité du code, accès admin   |
| `ColumnControllerTest`      | ajout / réordonnancement / suppression de colonnes (admin)       |
| `TicketControllerTest`      | création de ticket, **API `/board/move`** (ordre, CSRF, projet)  |
| `UserControllerTest`        | création de compte + login réel, anti-auto-suppression, accès    |

## Production

L'image de production (cible `frankenphp_prod` du `Dockerfile`) installe les dépendances sans
dev, précompile l'autoloader et l'OPcache preload, et active le mode worker sans `watch`.

```bash
docker build --target frankenphp_prod -t huttoboard-prod .
```

Pensez à définir un `APP_SECRET` et un mot de passe PostgreSQL robustes via les variables
d'environnement.
