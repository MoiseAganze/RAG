# RAG Mini-Site

Une interface web moderne, minimaliste et sécurisée permettant d'interagir avec un pipeline RAG (Retrieval-Augmented Generation) propulsé par n8n. Le projet intègre une gestion d'authentification par rôles, un historique des conversations en base de données, et une interface d'indexation de documents.

## 🛠️ Stack technique

Le projet est conçu de manière légère et performante, sans framework lourd :
- **Backend :** PHP 8+ (Vanilla)
- **Base de données :** MySQL / MariaDB via PDO
- **Frontend :** HTML5, JavaScript (Vanilla, Fetch API, DOM API)
- **Style :** CSS natif (variables CSS, Flexbox/Grid, Dark/Light mode)
- **Communication IA :** Appels Webhooks vers n8n via cURL (backend) et Fetch (frontend)

## 🗄️ Structure de la base de données

La base de données repose sur 3 tables principales :
1. `users` : Stocke les utilisateurs (matricule, nom, prénom, mot de passe haché avec BCRYPT, et rôle : `admin_full` ou `admin_chat`).
2. `conversations` : Historique des sessions de chat (liées à un utilisateur). Le titre est mis à jour dynamiquement au premier message.
3. `messages` : Contenu des échanges (lié à une conversation). Distingue le rôle `user` (la question posée) du rôle `assistant` (la réponse de l'IA).

## 🔐 Système d'authentification et Rôles

L'accès à l'application est entièrement protégé.
- **Authentification :** Connexion via un `matricule` unique et un mot de passe.
- **Sessions :** Gérées de manière sécurisée en PHP (régénération d'ID, cookies HTTP Only).
- **Rôles :**
  - `admin_chat` : Accès limité à l'interface de chat et aux paramètres du compte personnel.
  - `admin_full` : Accès complet. Peut utiliser le chat, indexer de nouveaux documents, et gérer les autres administrateurs (ajout, modification de rôle, suppression).

## 🚀 Scénarios d'utilisation

### Scénario 1 : Initialisation du projet (Setup)
1. L'administrateur système importe le fichier `database.sql` dans MySQL.
2. Il configure les accès DB et les URL des webhooks n8n dans `includes/config.php`.
3. Il se rend sur `/setup.php` pour créer le tout premier compte `admin_full` (ex: `ADMIN001`).
4. **Action critique :** Il supprime le fichier `setup.php` du serveur pour des raisons de sécurité.

### Scénario 2 : Utilisation quotidienne (Chat)
1. L'utilisateur (ex: un employé avec le rôle `admin_chat`) se connecte sur `/login.php`.
2. Il arrive sur `/chat.php`. L'interface lui permet de :
   - Créer une nouvelle conversation (bouton "+").
   - Poser une question à l'IA. Le message est sauvegardé en base, puis envoyé au webhook QA de n8n via l'API PHP (`api/message_send.php`).
   - Recevoir et lire la réponse de l'IA, formatée en HTML (Markdown supporté côté n8n).
   - Retrouver son historique de conversations dans la barre latérale, les renommer ou les supprimer.

### Scénario 3 : Entraînement de l'IA (Indexation)
1. Un utilisateur avec le rôle `admin_full` se connecte.
2. Il clique sur "Indexation" dans la barre latérale.
3. Sur la page `/indexation.php`, il glisse-dépose des documents (PDF, Word, TXT, etc.).
4. Il clique sur "Indexer". Le frontend envoie chaque fichier de manière asynchrone au webhook d'indexation n8n. Une barre de progression indique l'avancement.

### Scénario 4 : Gestion des accès (Admins)
1. Le responsable du projet (`admin_full`) se rend sur `/admins.php`.
2. Il peut créer de nouveaux accès pour ses collaborateurs en définissant un matricule, un mot de passe temporaire, et un niveau de privilège (`admin_chat` ou `admin_full`).
3. Il peut également rétrograder, promouvoir ou supprimer des comptes existants (hors de son propre compte).

### Scénario 5 : Personnalisation (Paramètres)
1. N'importe quel utilisateur connecté peut se rendre sur `/settings.php`.
2. Il peut mettre à jour son nom/prénom d'affichage.
3. Il peut changer son mot de passe pour des raisons de sécurité.
4. Il peut basculer l'interface entre le thème sombre (par défaut) et le thème clair via l'icône dans la barre de navigation. Le choix est sauvegardé localement.

## 📂 Architecture des dossiers

- `/assets/` : Contient `app.css` (design system global).
- `/includes/` : 
  - `config.php` : Variables d'environnement.
  - `db.php` : Connexion PDO.
  - `auth.php` : Logique de session et middlewares de protection.
  - `sidebar.php` : Composant de navigation partagé.
- `/api/` : Endpoints JSON appelés par le frontend (`conv_new.php`, `message_send.php`, etc.).
- `/*.php` : Pages principales de l'application (`chat.php`, `indexation.php`, `login.php`, etc.).
