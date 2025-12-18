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

## Service PHP

Le service PHP se trouve dans le dossier `php-service` et retourne un prénom via une requête HTTP GET.

### Build et test du service PHP

```bash
# Se placer dans le dossier php-service
cd php-service

# Construire l'image
docker build -t php-name-service .

# Lancer le conteneur
docker run -d -p 8081:80 --name php-service php-name-service

# Tester
curl http://localhost:8081
```

### Publier l'image PHP sur Docker Hub

```bash
# Tag l'image
docker tag php-name-service <votre-username-dockerhub>/php-name-service:latest

# Pousser l'image
docker push <votre-username-dockerhub>/php-name-service:latest
```

## Liens Docker Hub

- RentalService : `deariell/rentalservice` - https://hub.docker.com/r/deariell/rentalservice
- PHP Service : `deariell/php-name-service` - https://hub.docker.com/r/deariell/php-name-service
