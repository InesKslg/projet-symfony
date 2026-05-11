# Documentation API Authentification Mobile

## Base URL
```
https://bezanty-mica-arboricultural.ngrok-free.dev/api
```

## Endpoints

### 1. Connexion (Login)
**POST** `/api/login`

**Headers:**
```json
{
  "Content-Type": "application/json",
  "Accept": "application/json"
}
```

**Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Réponse réussie (200):**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "firstName": "Jean",
    "lastName": "Dupont"
  }
}
```

**Réponse erreur (401):**
```json
{
  "error": "Email ou mot de passe incorrect"
}
```

---

### 2. Inscription (Register)
**POST** `/api/register`

**Headers:**
```json
{
  "Content-Type": "application/json",
  "Accept": "application/json"
}
```

**Body:**
```json
{
  "email": "newuser@example.com",
  "password": "password123",
  "firstName": "Marie",
  "lastName": "Martin"
}
```

**Réponse réussie (201):**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 2,
    "email": "newuser@example.com",
    "firstName": "Marie",
    "lastName": "Martin"
  }
}
```

**Réponse erreur (409) - Email déjà utilisé:**
```json
{
  "error": "Cet email est déjà utilisé"
}
```

---

### 3. Récupérer les infos utilisateur (Me)
**GET** `/api/me`

**Headers:**
```json
{
  "Authorization": "Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "Accept": "application/json"
}
```

**Réponse réussie (200):**
```json
{
  "user": {
    "id": 1,
    "email": "user@example.com",
    "firstName": "Jean",
    "lastName": "Dupont"
  }
}
```

**Réponse erreur (401) - Non authentifié:**
```json
{
  "error": "Non authentifié"
}
```

---

## Utilisation du Token JWT

Après une connexion réussie, vous recevrez un `token` JWT. Pour accéder aux endpoints protégés, ajoutez ce token dans l'en-tête :

```
Authorization: Bearer <token>
```

### Exemple avec Flutter/Dart:
```dart
final response = await http.get(
  Uri.parse('$apiBaseUrl/api/me'),
  headers: {
    'Authorization': 'Bearer $token',
    'Accept': 'application/json',
  },
);
```

---

## Stockage du Token (Recommandé)

Utilisez `shared_preferences` pour stocker le token localement:

```dart
import 'package:shared_preferences/shared_preferences.dart';

// Après une connexion réussie
final prefs = await SharedPreferences.getInstance();
await prefs.setString('jwt_token', token);

// Récupérer le token
final token = prefs.getString('jwt_token');

// Supprimer le token lors de la déconnexion
await prefs.remove('jwt_token');
```

---

## Erreurs Courantes

| Code | Message | Cause |
|------|---------|-------|
| 400 | "Email et mot de passe requis" | Champs manquants |
| 401 | "Email ou mot de passe incorrect" | Identifiants invalides |
| 409 | "Cet email est déjà utilisé" | Email existe déjà |
| 500 | Erreur serveur | Problème backend |

---

## Migration Base de Données

Avant d'utiliser l'authentification, exécutez la migration:

```bash
docker exec galerie_symfony-php-1 php bin/console doctrine:migrations:migrate
```

Ou manuellement:
```bash
cd galerie_symfony
symfony console doctrine:migrations:migrate
```
