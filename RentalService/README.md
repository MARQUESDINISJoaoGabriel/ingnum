# RentalService - Projet Microservices

## Description
Projet de microservices comprenant :
1. Un service Java Spring Boot (RentalService)
2. Un service PHP

Dépôt d'origine : https://github.com/charroux/ingnum

## Prérequis
- Java JDK 21
- Docker
- Git

## Installation et Tests

### 1. Cloner le projet et configurer le dépôt Git

```bash
# Cloner le projet
git clone https://github.com/MARQUESDINISJoaoGabriel/ingnum.git

# Changer l'origin
git remote remove origin
git remote add origin <adresseDeVotreDepotGit>
```

### 2. Tester le programme sans Docker

```bash
# Builder le projet (compiler)
./gradlew build

# Tester le projet
java -jar build/libs/RentalService-0.0.1-SNAPSHOT.jar
```

Vérifier dans votre navigateur à l'adresse : http://localhost:8080/bonjour

### 3. Déploiement avec Docker

#### Construire l'image Docker
```bash
docker build -t rentalservice .
```

#### Lancer le conteneur
```bash
# En mode détaché
docker run -d -p 8080:8080 --name rentalservice-test rentalservice

# Ou en mode interactif
docker run -p 8080:8080 rentalservice
```

#### Vérifier le fonctionnement
- Navigateur : http://localhost:8080/bonjour
- Ou avec curl : `curl http://localhost:8080/bonjour`

#### Commandes Docker utiles
```bash
# Voir les conteneurs en cours d'exécution
docker ps

# Voir les logs
docker logs rentalservice-test

# Arrêter le conteneur
docker stop rentalservice-test

# Démarrer le conteneur
docker start rentalservice-test

# Supprimer le conteneur
docker rm rentalservice-test
```

### 4. Publier l'image sur Docker Hub

```bash
# Se connecter à Docker Hub
docker login

# Tag l'image
docker tag rentalservice <votre-username-dockerhub>/rentalservice:latest

# Pousser l'image
docker push <votre-username-dockerhub>/rentalservice:latest
```

## Service PHP - RESTful Task API

Le service PHP est une API RESTful complète de gestion de tâches avec opérations CRUD, stockage JSON et validation.

### Architecture de l'API

L'API PHP microservice implémente:
- **Router**: Gestion du routage des URL et parsing des requêtes
- **ResponseHandler**: Réponses JSON cohérentes avec codes HTTP appropriés
- **Task**: Entité avec validation (titre requis, statut enum)
- **TaskRepository**: Opérations CRUD avec fichier JSON et verrouillage de fichier
- **TaskController**: Logique métier pour chaque endpoint

### Endpoints de l'API

| Méthode | Endpoint | Description | Code |
|---------|----------|-------------|------|
| GET | `/api/health` | Health check | 200 |
| GET | `/api/tasks` | Liste toutes les tâches | 200 |
| GET | `/api/tasks/{id}` | Récupère une tâche spécifique | 200/404 |
| POST | `/api/tasks` | Crée une nouvelle tâche | 201 |
| PUT | `/api/tasks/{id}` | Met à jour une tâche | 200/404 |
| DELETE | `/api/tasks/{id}` | Supprime une tâche | 204/404 |

### Structure de l'entité Task

```json
{
  "id": 1,
  "title": "Titre de la tâche",
  "description": "Description de la tâche",
  "status": "pending",
  "created_at": "2025-12-18T10:00:00+00:00",
  "updated_at": "2025-12-18T10:00:00+00:00"
}
```

**Statuts valides**: `pending`, `in_progress`, `completed`

### Format des réponses

**Succès:**
```json
{
  "success": true,
  "data": {...},
  "message": "Task retrieved successfully"
}
```

**Erreur:**
```json
{
  "success": false,
  "error": "Task not found",
  "code": 404
}
```

### Exemples d'utilisation avec cURL

#### Health Check
```bash
curl http://localhost:8081/api/health
```

#### Lister toutes les tâches
```bash
curl http://localhost:8081/api/tasks
```

#### Récupérer une tâche spécifique
```bash
curl http://localhost:8081/api/tasks/1
```

#### Créer une nouvelle tâche
```bash
curl -X POST http://localhost:8081/api/tasks \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Apprendre PHP",
    "description": "Maîtriser les APIs REST",
    "status": "pending"
  }'
```

#### Mettre à jour une tâche
```bash
curl -X PUT http://localhost:8081/api/tasks/1 \
  -H "Content-Type: application/json" \
  -d '{"status": "in_progress"}'
```

#### Supprimer une tâche
```bash
curl -X DELETE http://localhost:8081/api/tasks/1
```

### Build et test du service PHP

#### Option 1: Docker Compose (Recommandé)
```bash
# Construire et lancer tous les services
docker-compose up -d

# Voir les logs
docker-compose logs -f php-service

# Arrêter les services
docker-compose down
```

#### Option 2: Docker standalone
```bash
# Se placer dans le dossier php-service
cd php-service

# Construire l'image
docker build -t php-task-api .

# Lancer le conteneur avec volume pour persistance
docker run -d -p 8081:80 --name php-service \
  -v php-data:/var/www/html/data \
  php-task-api

# Tester l'API
curl http://localhost:8081/api/health
```

### Publier l'image PHP sur Docker Hub

```bash
# Tag l'image
docker tag php-task-api <votre-username-dockerhub>/php-task-api:latest

# Pousser l'image
docker push <votre-username-dockerhub>/php-task-api:latest
```

## Docker Compose - Orchestration des services

Le fichier `docker-compose.yml` permet de gérer les deux services ensemble:

```bash
# Lancer tous les services
docker-compose up -d

# Voir l'état des services
docker-compose ps

# Voir les logs
docker-compose logs -f

# Arrêter tous les services
docker-compose down

# Reconstruire et relancer
docker-compose up -d --build
```

### Services disponibles

- **rental-service**: http://localhost:8080
- **php-service**: http://localhost:8081

## Liens Docker Hub

- RentalService : `deariell/rentalservice` - https://hub.docker.com/r/deariell/rentalservice
- PHP Service : `deariell/php-name-service` - https://hub.docker.com/r/deariell/php-name-service
