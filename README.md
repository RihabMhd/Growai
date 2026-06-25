# Backend API — Plateforme de gestion e-commerce

Backend Laravel de gestion e-commerce orienté **commandes**, **produits**, **clients**, **expéditions**, **intégration Shopify** et **gestion d’équipe**.

Ce projet fournit une API REST utilisée par un frontend d’administration (React + Vite) pour piloter une activité e-commerce : suivi des commandes, récupération des commandes abandonnées, gestion du catalogue, connexion à des sociétés de livraison, calcul de commissions, gestion des utilisateurs et synchronisation Shopify.

---

# Sommaire

* [1. Présentation du projet](#1-présentation-du-projet)
* [2. Stack technique](#2-stack-technique)
* [3. Principaux modules métier](#3-principaux-modules-métier)
* [4. Architecture backend](#4-architecture-backend)
* [5. Arborescence utile](#5-arborescence-utile)
* [6. Installation](#6-installation)
* [7. Configuration environnement](#7-configuration-environnement)
* [8. Base de données](#8-base-de-données)
* [9. Lancement du projet](#9-lancement-du-projet)
* [10. Commandes utiles](#10-commandes-utiles)
* [11. Modules fonctionnels détaillés](#11-modules-fonctionnels-détaillés)
* [12. Intégrations externes](#12-intégrations-externes)
* [13. Authentification et sécurité](#13-authentification-et-sécurité)
* [14. Endpoints principaux](#14-endpoints-principaux)
* [15. Flux métier importants](#15-flux-métier-importants)
* [16. Points sensibles / dette technique](#16-points-sensibles--dette-technique)
* [17. Ordre de lecture recommandé pour reprise](#17-ordre-de-lecture-recommandé-pour-reprise)

---

# 1. Présentation du projet

Ce backend expose une API Laravel pour une plateforme de gestion e-commerce multi-modules.

## Objectifs métier

L’application permet notamment de :

* gérer les **commandes** ;
* suivre les **commandes abandonnées** ;
* gérer les **clients** ;
* gérer les **produits** par boutique ;
* gérer les **statuts de commande** et les **sources de commande** ;
* créer et suivre les **expéditions** ;
* connecter des **sociétés de livraison / transporteurs** ;
* gérer une **équipe** d’agents / administrateurs ;
* configurer des **commissions** ;
* connecter et synchroniser des boutiques **Shopify** ;
* gérer les **uploads** de fichiers/images.

Le backend sert de socle API pour un frontend d’administration.

---

# 2. Stack technique

## Framework / langage

* **PHP 8+**
* **Laravel**

## Base de données

* **MySQL / MariaDB**

## Authentification

* Laravel API Auth (selon configuration du projet)
* gestion de session pour certains contextes métier (ex: boutique active)
* intégration d’authentification sociale / Shopify selon modules

## Intégrations observées

* **Shopify**
* **Transporteurs / delivery companies**
* Webhooks entrants
* synchronisation de commandes / produits

---

# 3. Principaux modules métier

## Module Commandes

* création, lecture, mise à jour de commandes ;
* assignation à des agents ;
* mise à jour massive de statut ;
* récupération de commandes abandonnées ;
* synchronisation de commandes abandonnées ;
* historisation métier.

## Module Produits

* CRUD produits ;
* filtrage / pagination ;
* actions massives ;
* rattachement à une boutique (`Shop`).

## Module Clients

* gestion du référentiel client ;
* rattachement probable aux commandes ;
* enrichissement des données de contact / adresse.

## Module Expéditions

* création d’expédition ;
* mise à jour / annulation ;
* tracking ;
* réception de webhooks transporteur ;
* liaison commande ↔ expédition.

## Module Delivery / Carriers

* gestion des sociétés de livraison ;
* connexion / déconnexion d’un transporteur ;
* configuration d’actions transporteur ;
* enregistrement de webhooks.

## Module Shopify

* OAuth Shopify ;
* connexion d’une boutique ;
* réception de webhooks Shopify ;
* synchronisation potentielle de commandes / produits.

## Module Team / Commission

* gestion des membres ;
* paramètres d’équipe ;
* commissions et règles associées.

---

# 4. Architecture backend

Le projet suit une organisation de type **Laravel modulaire** avec séparation entre :

* **HTTP** : contrôleurs, requests, resources
* **Application** : handlers, commands, queries, actions
* **Domain** : modèles métier, services, règles, value objects
* **Infrastructure** : persistance, intégrations externes, services techniques

## Schéma simplifié

```text
HTTP Request
   ↓
Controller
   ↓
Request validation / Authorization
   ↓
Command / Query / DTO
   ↓
Handler / Action / Repository
   ↓
Domain / Models / Services
   ↓
Database / External integrations
```

## Patterns observés

Selon les modules, on retrouve plusieurs patterns :

* **CQRS léger**

  * `*Command`
  * `*Query`
  * `*Handler`

* **Actions applicatives**

  * `CreateShipmentAction`
  * `ConnectCarrierAction`
  * etc.

* **DTO / Data objects**

  * transport de données entre couche HTTP et couche Application

* **Repositories**

  * surtout visibles côté produits

---

# 5. Arborescence utile

> L’arborescence exacte dépend du projet, mais la structure logique observée est la suivante :

```text
app/
├─ Http/
│  ├─ Controllers/
│  │  ├─ OrderController.php
│  │  ├─ AbandonedOrderController.php
│  │  ├─ ShipmentController.php
│  │  ├─ CarrierActionController.php
│  │  ├─ ProductController.php
│  │  ├─ ClientController.php
│  │  ├─ DeliveryCompanyController.php
│  │  ├─ OrderStatusController.php
│  │  ├─ RecoveryRuleController.php
│  │  ├─ UploadController.php
│  │  ├─ AuthController.php
│  │  ├─ PasswordController.php
│  │  ├─ SocialAuthController.php
│  │  ├─ ShopSessionController.php
│  │  ├─ ShopifyAuthController.php
│  │  └─ ShopifyWebhookController.php
│  │
│  ├─ Requests/
│  └─ Resources/
│
├─ Application/
│  ├─ Orders/
│  ├─ Delivery/
│  ├─ Products/
│  ├─ Shopify/
│  └─ ...
│
├─ Domain/
│  ├─ Orders/
│  ├─ Delivery/
│  ├─ Products/
│  ├─ Shopify/
│  ├─ Team/
│  └─ ...
│
├─ Models/ ou Domain/*/Models
└─ ...
```

---

# 6. Installation

## 1) Cloner le projet

```bash
git clone <repo-url>
cd <project-folder>
```

## 2) Installer les dépendances PHP

```bash
composer install
```

## 3) Copier le fichier d’environnement

```bash
cp .env.example .env
```

## 4) Générer la clé d’application

```bash
php artisan key:generate
```

## 5) Configurer la base de données dans `.env`

Exemple :

```env
APP_NAME=EcommerceBackend
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce_db
DB_USERNAME=root
DB_PASSWORD=
```

## 6) Lancer les migrations

```bash
php artisan migrate
```

## 7) Lancer les seeders si disponibles

```bash
php artisan db:seed
```

---

# 7. Configuration environnement

## Variables importantes à vérifier

### Application

```env
APP_NAME=
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

### Base de données

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

### Cache / Queue / Session

```env
CACHE_STORE=file
QUEUE_CONNECTION=database
SESSION_DRIVER=file
```

### Mail (si récupération mot de passe / notifications)

```env
MAIL_MAILER=
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=
```

### Shopify (si utilisé)

```env
SHOPIFY_CLIENT_ID=
SHOPIFY_CLIENT_SECRET=
SHOPIFY_REDIRECT_URI=
SHOPIFY_WEBHOOK_SECRET=
```

### Delivery / transporteurs

Selon le projet, certains transporteurs peuvent nécessiter :

```env
CARRIER_API_KEY=
CARRIER_API_SECRET=
CARRIER_WEBHOOK_SECRET=
```

---

# 8. Base de données

## Tables principales identifiées

### Domaine commandes

* `orders`
* `order_items`
* `order_histories`
* `order_statuses`
* `order_sources`

### Domaine logistique

* `shipments`
* `delivery_companies`

### Domaine catalogue / clients

* `products`
* `clients`
* `shops`

### Domaine équipe / sécurité

* `users`
* `teams`
* `personal_access_tokens`

### Domaine business complémentaire

* `commissions`
* `recovery_rules`
* `messages`

## Exemples de migrations repérées

* création des commandes ;
* création des statuts de commande ;
* création des items de commande ;
* création des expéditions ;
* ajout de champs Shopify sur les boutiques ;
* ajout d’un `shipment_id` sur les commandes ;
* ajout de champs de configuration / commissions / recovery rules.

---

# 9. Lancement du projet

## Démarrer le serveur Laravel

```bash
php artisan serve
```

Par défaut :

```text
http://127.0.0.1:8000
```

## Si le projet utilise des queues

Lancer aussi un worker :

```bash
php artisan queue:work
```

## Si le projet utilise des tâches planifiées

En local, lancer éventuellement :

```bash
php artisan schedule:work
```

---

# 10. Commandes utiles

## Migrations

```bash
php artisan migrate
php artisan migrate:fresh
php artisan migrate:fresh --seed
```

## Cache / config

```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

## Génération de docs/routes

```bash
php artisan route:list
```

## Queues

```bash
php artisan queue:work
php artisan queue:restart
```

## Tinker

```bash
php artisan tinker
```

---

# 11. Modules fonctionnels détaillés

# 11.1 Commandes

Le module commandes est le cœur du projet.

## Fonctionnalités observées

* lister les commandes ;
* créer une commande ;
* afficher une commande ;
* mettre à jour une commande ;
* assigner une commande à un agent ;
* assigner plusieurs commandes ;
* mettre à jour massivement le statut ;
* récupérer une commande abandonnée ;
* synchroniser les commandes abandonnées.

## Entités liées

* `orders`
* `order_items`
* `order_histories`
* `order_statuses`
* `order_sources`
* `clients`
* `shipments`
* `commissions`

## Points techniques

Le module semble utiliser une couche `Application\Orders\...` avec :

* `ListOrdersHandler`
* `CreateOrderHandler`
* `GetOrderHandler`
* `UpdateOrderHandler`
* `AssignOrderHandler`
* `BulkAssignOrdersHandler`
* `BulkUpdateOrderStatusHandler`
* `RecoverAbandonedOrderHandler`
* `SyncAbandonedOrdersHandler`

---

# 11.2 Commandes abandonnées

Module dédié à la consultation et à la récupération des commandes abandonnées.

## Fonctionnalités

* listing des commandes abandonnées ;
* filtrage ;
* récupération manuelle ;
* synchronisation depuis une source externe.

---

# 11.3 Produits

Le module produit est rattaché à une boutique (`Shop`).

## Fonctionnalités

* lister les produits d’une boutique ;
* créer un produit ;
* modifier un produit ;
* supprimer un produit ;
* suppression massive ;
* mise à jour massive de statut.

## Particularités

* utilisation d’un `ProductRepositoryInterface`
* usage de handlers pour création / mise à jour / suppression
* filtrage par boutique

---

# 11.4 Clients

Le module clients gère les informations client utilisées dans les commandes.

## Fonctionnalités probables

* listing des clients ;
* consultation d’un client ;
* mise à jour des informations ;
* rattachement / recherche dans le contexte commande.

---

# 11.5 Statuts de commande

Le module `OrderStatus` gère le référentiel des statuts.

## Fonctionnalités

* lister les statuts ;
* créer un statut ;
* modifier un statut ;
* supprimer un statut ;
* réordonner les statuts.

---

# 11.6 Règles de récupération

Le module `RecoveryRule` semble piloter les règles métier de récupération des commandes abandonnées.

## Fonctionnalités

* lister les règles ;
* remplacer ou mettre à jour la configuration ;
* modifier une règle ;
* supprimer une règle.

---

# 11.7 Expéditions

Le module expédition gère le cycle logistique de la commande.

## Fonctionnalités observées

* lister les expéditions ;
* afficher une expédition ;
* créer une expédition ;
* mettre à jour une expédition ;
* annuler une expédition ;
* récupérer le tracking ;
* traiter des webhooks transporteur.

## Entités liées

* `shipments`
* `orders`
* `delivery_companies`
* potentiellement `carrier_webhook_logs`

---

# 11.8 Sociétés de livraison / Carriers

Le module `DeliveryCompany` gère les transporteurs et leur configuration.

## Fonctionnalités observées

* lister les sociétés de livraison ;
* connecter un transporteur ;
* déconnecter un transporteur ;
* tester la connexion ;
* enregistrer / désenregistrer un webhook.

## Sous-module Carrier Actions

Un sous-module `CarrierActionController` semble gérer :

* les actions disponibles pour un transporteur ;
* la configuration de ces actions ;
* le test d’une action ;
* l’enregistrement de webhooks liés à une action.

---

# 11.9 Shopify

Le projet contient un sous-domaine d’intégration Shopify.

## Fonctionnalités observées

* OAuth Shopify ;
* connexion d’une boutique ;
* stockage d’informations Shopify dans `shops` ;
* webhooks Shopify ;
* synchronisation potentielle de produits / commandes.

## Contrôleurs repérés

* `ShopifyAuthController`
* `ShopifyWebhookController`
* `ShopSessionController`

---

# 11.10 Upload

Le backend expose un endpoint d’upload de fichiers/images.

## Fonctionnement observé

* validation d’un fichier image ;
* stockage sur le disque `public` ;
* retour d’une URL publique.

## Vigilance

* vérifier la protection de la route ;
* vérifier la gestion des fichiers orphelins ;
* vérifier la taille max et les types autorisés.

---

# 12. Intégrations externes

# Shopify

Le projet semble connecté à Shopify pour :

* connecter une boutique ;
* recevoir des webhooks ;
* synchroniser des données.

# Transporteurs

Le projet peut intégrer une ou plusieurs sociétés de livraison avec :

* credentials ;
* webhooks ;
* actions de création / annulation / suivi d’expédition.

---

# 13. Authentification et sécurité

## Authentification

Le projet contient des contrôleurs dédiés à :

* connexion ;
* réinitialisation / changement de mot de passe ;
* authentification sociale ;
* gestion de session boutique.

## Sécurité à surveiller

* policies sur les boutiques ;
* autorisations sur l’assignation de commandes ;
* endpoints d’upload ;
* endpoints de webhooks ;
* actions bulk ;
* cohérence entre routes protégées et contrôleurs.

---

# 14. Endpoints principaux

> Les routes exactes doivent être confirmées avec `routes/api.php`, mais les endpoints suivants sont fortement probables.

## Orders

* `GET /api/orders`
* `POST /api/orders`
* `GET /api/orders/{id}`
* `PUT|PATCH /api/orders/{id}`
* `POST|PATCH /api/orders/{id}/assign`
* `POST /api/orders/{id}/recover`
* `POST /api/orders/bulk-assign`
* `POST|PATCH /api/orders/bulk-status`
* `POST /api/orders/sync-abandoned`

## Abandoned Orders

* `GET /api/orders/abandoned` ou endpoint équivalent

## Products

* `GET /api/shops/{shop}/products`
* `POST /api/shops/{shop}/products`
* `GET /api/shops/{shop}/products/{product}`
* `PUT|PATCH /api/shops/{shop}/products/{product}`
* `DELETE /api/shops/{shop}/products/{product}`

## Shipments

* `GET /api/delivery/shipments`
* `GET /api/delivery/shipments/{id}`
* `POST /api/delivery/shipments`
* `PUT|PATCH /api/delivery/shipments/{id}`
* `DELETE /api/delivery/shipments/{id}`
* `GET /api/delivery/shipments/{id}/tracking`

## Order statuses

* `GET /api/order-statuses`
* `POST /api/order-statuses`
* `PUT|PATCH /api/order-statuses/{id}`
* `DELETE /api/order-statuses/{id}`

## Recovery rules

* `GET /api/recovery-rules`
* `POST /api/recovery-rules`
* `PUT|PATCH /api/recovery-rules/{id}`
* `DELETE /api/recovery-rules/{id}`

---

# 15. Flux métier importants

## 1) Création d’une commande

1. requête HTTP reçue par `OrderController` ;
2. validation ;
3. transformation en `CreateOrderCommand` ;
4. exécution par `CreateOrderHandler` ;
5. création de la commande + items + données associées.

## 2) Assignation d’une commande

1. appel de l’endpoint d’assignation ;
2. vérification d’autorisation ;
3. création d’un `AssignOrderCommand` ;
4. exécution du handler ;
5. mise à jour de l’agent assigné.

## 3) Mise à jour massive de statut

1. réception d’une liste d’IDs + nouveau statut ;
2. validation ;
3. passage au handler bulk ;
4. mise à jour des commandes ciblées.

## 4) Création d’une expédition

1. requête vers `ShipmentController` ;
2. validation ;
3. construction d’un `CreateShipmentCommand` / DTO ;
4. appel d’une action applicative ;
5. création de l’expédition et interaction transporteur si nécessaire.

## 5) OAuth Shopify

1. redirection vers Shopify ;
2. retour callback avec `code` ;
3. validation du `state` ;
4. appel au handler de connexion ;
5. enregistrement de la boutique / token.

---

# 16. Points sensibles / dette technique

## 1. Autorisations

Plusieurs points doivent être revérifiés :

* cohérence des `authorize()` ;
* protection des endpoints sensibles ;
* permissions sur les opérations bulk ;
* vérification d’accès à la boutique active.

## 2. Logique métier dispersée

La logique n’est pas toujours dans les contrôleurs. Une grande partie est déléguée à :

* handlers ;
* actions ;
* repositories ;
* services.

Une reprise du projet nécessite donc de lire au-delà de la couche HTTP.

## 3. Intégrations externes

Les modules Shopify et Delivery peuvent introduire :

* erreurs réseau ;
* problèmes de credentials ;
* webhooks dupliqués ;
* problèmes d’idempotence.

## 4. Bulk operations

Les opérations massives sur commandes / produits doivent être surveillées pour :

* performance ;
* cohérence transactionnelle ;
* impacts métier secondaires.

## 5. Uploads

Le module d’upload doit être revu sous l’angle :

* sécurité ;
* taille de fichier ;
* nettoyage des fichiers orphelins ;
* visibilité publique.

---

# 17. Ordre de lecture recommandé pour reprise

Pour une nouvelle développeuse, l’ordre conseillé est :

## Priorité 1 — Comprendre le cœur métier

1. `OrderController`
2. handlers du module commandes
3. modèle `Order` + `OrderItem` + `OrderHistory`
4. `ShipmentController`
5. `DeliveryCompanyController`

## Priorité 2 — Comprendre les modules transverses

6. `ProductController`
7. `ClientController`
8. `OrderStatusController`
9. `RecoveryRuleController`

## Priorité 3 — Comprendre les intégrations

10. `ShopifyAuthController`
11. `ShopifyWebhookController`
12. `CarrierActionController`

## Priorité 4 — Vérifier la couche infrastructure

13. `routes/api.php`
14. policies
15. requests / resources
16. jobs / listeners / events
17. services d’intégration externe

---

# Notes

Ce README donne une vue d’ensemble technique du backend à partir de la structure observée du projet.
Pour une compréhension exacte du comportement métier, il faut confirmer les éléments suivants dans le code source :

* `routes/api.php`
* les `Handlers`, `Actions`, `Commands`, `Queries`
* les `Form Requests`
* les `Policies`
* les `Models` et leurs relations
* les services Shopify / transporteurs
* les jobs / events / listeners éventuels
