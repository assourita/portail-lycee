# Mon Portail Lycée

Portail scolaire en ligne pour un lycée : gestion pédagogique, vie scolaire et comptabilité, avec un espace dédié à chaque profil (administration, enseignants, élèves, parents, comptables).

Application **Symfony 7.4 LTS** (PHP 8.2+, Doctrine ORM 3, Twig, Bootstrap 5).

## Fonctionnalités

| Espace | Ce que l'on peut y faire |
| --- | --- |
| Administration | Utilisateurs, élèves, enseignants, comptables, parents ; filières, classes, matières ; périodes académiques et trimestres ; emplois du temps (par filière et par enseignant) ; évaluations, bulletins, absences (validation des justificatifs), parcours scolaires |
| Enseignant | Cours et chapitres avec pièces jointes ; devoirs avec documents, correction des copies ; évaluations ; saisie des notes (individuelle ou par groupe) ; absences ; emploi du temps |
| Élève | Tableau de bord ; cours, matières, devoirs (rendu de fichiers, brouillon) ; notes, évaluations, bulletins ; absences et justification avec pièce jointe ; parcours ; emploi du temps |
| Parent | Notes, bulletins et absences de chacun de ses enfants |
| Comptable | Factures (individuelles ou pour toute une filière), paiements (partiels, rattachés à une facture), comptes et soldes des élèves, rapports avec graphiques, exports CSV |
| Tous | Profil, informations personnelles, paramètres (thème, notifications), mot de passe oublié par email |

## Installation

Prérequis : PHP 8.2 ou plus récent (avec `pdo_mysql`), Composer, MariaDB ou MySQL.

```bash
git clone https://github.com/assourita/portail-lycee.git
cd portail-lycee
composer install
```

Créez un fichier `.env.local` (non versionné) avec vos paramètres :

```dotenv
DATABASE_URL="mysql://utilisateur:motdepasse@127.0.0.1:3306/mon_portail_lyceee?serverVersion=11.6.2-MariaDB&charset=utf8mb4"
APP_SECRET=une_chaine_aleatoire_de_32_caracteres
# Envoi réel des emails (sinon ils sont ignorés) :
# MAILER_DSN=smtp://utilisateur:motdepasse@smtp.exemple.com:587
```

Puis créez la base, son schéma et le premier administrateur :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console app:init-jours
php bin/console app:init-classes
php bin/console app:init-periodes-academiques          # année scolaire en cours
php bin/console app:create-admin admin admin@lycee.fr  # le mot de passe est demandé
```

Lancez le serveur de développement :

```bash
php -S 127.0.0.1:8000 -t public
# ou, avec la CLI Symfony : symfony serve
```

L'application est alors disponible sur http://127.0.0.1:8000.

### Données de démonstration (facultatif)

```bash
php bin/console app:create-filieres
php bin/console app:create-matieres
php bin/console app:create-additional-enseignants
php bin/console app:assign-matieres-to-enseignants
php bin/console app:create-students
php bin/console app:create-emploi-du-temps
php bin/console app:create-evaluations
php bin/console app:create-notes
php bin/console app:generate-test-bulletins
```

## Comptes utilisateurs

- Les comptes des élèves, enseignants, comptables et parents sont créés par l'administration. Un **mot de passe temporaire aléatoire** est affiché une seule fois à l'administrateur, qui le transmet à l'intéressé.
- Chacun peut ensuite changer son mot de passe depuis *Mon profil*, ou le réinitialiser depuis *Mot de passe oublié* (lien valable une heure, envoyé par email ; en environnement de développement, le lien est aussi affiché à l'écran).
- Après connexion, chaque utilisateur est redirigé vers le tableau de bord de son profil.

## Fichiers envoyés

- Les pièces jointes des chapitres de cours sont stockées dans `public/uploads/pieces_jointes/`.
- Les documents privés (justificatifs d'absence, documents de devoirs, copies rendues) sont stockés dans `var/uploads/`, hors du dossier public, et ne sont téléchargeables qu'après vérification des droits. **Pensez à sauvegarder ce dossier.**
- Formats acceptés : PDF, documents bureautiques, images, ZIP ; 10 Mo maximum par fichier.

## Structure du code

```
src/
  Command/      commandes console (initialisation, données de démonstration, création d'administrateur)
  Controller/   un contrôleur par espace et par domaine (Admin*, Enseignant*, Etudiant*, Parent, Comptable...)
  Entity/       modèle de données Doctrine
  Repository/   requêtes
  Service/      période académique active, envoi de fichiers
  Twig/         fonctions Twig (période active, années scolaires)
templates/      vues Twig, rangées par espace
migrations/     migrations Doctrine
```

## Vérifications utiles

```bash
php bin/console lint:twig templates
php bin/console lint:container
php bin/console doctrine:schema:validate
composer audit
```
