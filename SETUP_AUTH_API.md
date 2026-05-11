# Setup Authentification API Symfony pour Mobile

## Fichiers créés/modifiés

### 1. **Contrôleur d'authentification API**
   - Fichier: `src/Controller/ApiAuthenticationController.php`
   - Endpoints créés:
     - `POST /api/login` - Connexion avec email/password
     - `POST /api/register` - Inscription avec prénom/nom
     - `GET /api/me` - Récupérer les infos utilisateur (protégé)

### 2. **Entité User - Champs ajoutés**
   - Fichier: `src/Entity/User.php`
   - Champs: `firstName`, `lastName`
   - Méthodes: getters/setters

### 3. **Migration Doctrine**
   - Fichier: `migrations/Version20260428AddUserNames.php`
   - Ajoute les colonnes `first_name` et `last_name` à la table `user`

### 4. **Configuration Sécurité**
   - Fichier: `config/packages/security.yaml`
   - Ajout firewall JWT pour `/api`
   - Access control pour endpoints publics/protégés

### 5. **Documentation API**
   - Fichier: `API_AUTH_DOCUMENTATION.md`
   - Exemples de requêtes et réponses

---

## ✅ Étapes à suivre

### Étape 1: Arrêter les conteneurs Docker
```bash
cd c:\projets-ecole\galerie_symfony
docker compose down
```

### Étape 2: Reconstruire les images (optionnel mais recommandé)
```bash
docker compose up -d --build
```

### Étape 3: Exécuter les migrations
```bash
docker exec galerie_symfony-php-1 php bin/console doctrine:migrations:migrate
```

Ou si le conteneur a un autre nom:
```bash
docker compose exec php php bin/console doctrine:migrations:migrate
```

### Étape 4: Vérifier que les clés JWT existent
```bash
# Les clés doivent exister dans: config/jwt/
# - private.pem
# - public.pem

# Si elles n'existent pas, les générer:
docker compose exec php php bin/console lexik:jwt:generate-keypair
```

### Étape 5: Redémarrer le tunnel ngrok
```bash
cd c:\projets-ecole
./ngrok.exe http 80
```

### Étape 6: Tester les endpoints

**Test Login:**
```bash
curl -X POST https://bezanty-mica-arboricultural.ngrok-free.dev/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

**Test Register:**
```bash
curl -X POST https://bezanty-mica-arboricultural.ngrok-free.dev/api/register \
  -H "Content-Type: application/json" \
  -d '{"email":"newuser@example.com","password":"password","firstName":"Jean","lastName":"Dupont"}'
```

---

## 📱 Mise à jour Flutter (déjà partiellement fait)

Le fichier `login.dart` appelle déjà l'endpoint `/api/login`. Vérifiez que:

1. ✅ `apiBaseUrl` pointe vers ngrok
2. ✅ Le corps de la requête envoie email/password
3. ✅ Le token JWT est récupéré et sauvegardé

### À compléter dans Flutter:

**Installez shared_preferences:**
```bash
cd galerie_mobile
flutter pub add shared_preferences
```

**Mettez à jour login.dart pour stocker le token:**
```dart
import 'package:shared_preferences/shared_preferences.dart';

// Dans la réponse de connexion réussie:
final prefs = await SharedPreferences.getInstance();
await prefs.setString('jwt_token', token);
```

---

## 🔗 URLs de test

- **Login:** `https://bezanty-mica-arboricultural.ngrok-free.dev/api/login`
- **Register:** `https://bezanty-mica-arboricultural.ngrok-free.dev/api/register`
- **Get Photos:** `https://bezanty-mica-arboricultural.ngrok-free.dev/api/photos`
- **Get Me:** `https://bezanty-mica-arboricultural.ngrok-free.dev/api/me` (nécessite token)

---

## ⚠️ Troubleshooting

### Erreur: "CORS policy: Response to preflight request"
- ✅ CORS est déjà configuré dans `config/packages/nelmio_cors.yaml`
- Vérifiez que le firewall accepte les requêtes OPTIONS

### Erreur: "JWT Token not found"
- Vérifiez que le header `Authorization: Bearer <token>` est présent
- Vérifiez que les clés JWT existent dans `config/jwt/`

### Erreur: "Argument count wrong" dans ApiAuthenticationController
- Vérifiez que le UserPasswordHasher est bien injecté
- Vérifiez que la JWTTokenManager est bien injectée

### La base de données n'a pas les colonnes firstName/lastName
- Exécutez: `docker compose exec php php bin/console doctrine:migrations:migrate`

---

## ✨ Résumé

- ✅ Endpoint `/api/login` prêt pour mobile
- ✅ Endpoint `/api/register` prêt pour mobile
- ✅ JWT configuré et actif
- ✅ CORS configuré
- ✅ Documentation complète

**Prochaine étape:** Tester depuis le mobile Flutter avec ngrok !
