# Wazuh GRC — Plateforme de supervision et de gouvernance de la cybersécurité

## Présentation du projet

Wazuh GRC est une application web développée avec Laravel qui intègre la solution de supervision de sécurité Wazuh à une plateforme de gouvernance, de gestion des risques et de conformité (GRC).

L'objectif est de relier la détection des événements de sécurité aux activités de gestion des incidents et des risques. Les alertes générées par Wazuh peuvent ainsi être importées dans l'application, analysées, évaluées et suivies depuis une interface centralisée.

Le projet couvre les principales étapes du traitement d'un événement de sécurité :

**Alerte de sécurité → Incident → Évaluation du risque → Conformité → Traitement et suivi**

## Architecture

```text
                  Kali Linux
              Tests de sécurité
                       |
                       v
                  Agent Wazuh
                     Ubuntu
                       |
                       v
                Gestionnaire Wazuh
                    + Indexer
                       |
                 Alertes de sécurité
                       |
                       v
                 Application Laravel
                       |
          +------------+------------+
          |            |            |
          v            v            v
       Gestion      Évaluation    Gestion de
      des incidents  des risques  la conformité
          |
          v
    Affectation et suivi
    des actions correctives
```

## Objectifs

Le projet vise à :

- Intégrer les alertes de sécurité Wazuh dans une application GRC.
- Centraliser la gestion des incidents de sécurité.
- Importer les alertes pertinentes depuis Wazuh.
- Évaluer les incidents selon leur gravité, leur probabilité et leur impact.
- Déterminer un niveau de risque afin de faciliter la priorisation.
- Associer les incidents à des contrôles de conformité.
- Affecter les incidents aux utilisateurs responsables.
- Assurer le suivi du traitement et des actions correctives.
- Envoyer des notifications par e-mail lors de l'affectation d'un incident.

## Fonctionnalités principales

### Intégration de Wazuh

L'application communique avec le Wazuh Indexer afin de récupérer les alertes de sécurité.

L'intégration permet de :

- Récupérer les alertes disponibles dans Wazuh.
- Filtrer les alertes selon leur niveau de gravité.
- Importer les alertes pertinentes dans l'application Laravel.
- Limiter les doublons lors de la création des incidents.
- Conserver l'identifiant de l'alerte Wazuh d'origine.
- Transformer les alertes importées en incidents pouvant être analysés et suivis.

### Gestion des incidents

L'application permet de consulter les incidents et de gérer les informations nécessaires à leur traitement.

Les fonctionnalités comprennent :

- La consultation des incidents et de leurs détails.
- L'analyse et la qualification des incidents.
- La définition de la gravité, de la probabilité et de l'impact.
- L'évaluation du niveau de risque.
- L'association aux contrôles de conformité.
- L'affectation d'un incident à un utilisateur responsable.
- Le suivi du statut et du traitement de l'incident.
- L'ajout de commentaires et d'informations relatives aux actions correctives.

### Évaluation des risques

L'évaluation des risques permet de prioriser les incidents en fonction de leur niveau de risque.

Elle prend notamment en compte :

- La gravité de l'incident.
- La probabilité.
- L'impact potentiel.

Les niveaux de risque utilisés dans l'application sont :

| Niveau | Classification |
|---|---|
| Faible | Low |
| Moyen | Medium |
| Élevé | High |
| Critique | Critical |

Cette évaluation aide à identifier les incidents nécessitant une attention prioritaire.

### Gestion de la conformité

L'application permet d'associer les incidents à des contrôles de conformité et d'en suivre l'état d'évaluation.

Le suivi peut inclure :

- Les contrôles de conformité.
- Le statut de l'évaluation.
- Les commentaires et les éléments de preuve.
- Les actions correctives.
- Le suivi du traitement.

### Affectation et notifications par e-mail

Les incidents peuvent être affectés à un utilisateur responsable depuis l'interface de gestion.

Une notification par e-mail est prévue lors de l'affectation afin d'informer le responsable concerné et de faciliter la prise en charge de l'incident.

### Flux de traitement des alertes

Le fonctionnement général de la plateforme suit les étapes suivantes :

1. Un événement de sécurité se produit.
2. L'agent Wazuh collecte les informations pertinentes.
3. Wazuh analyse l'événement et génère une alerte.
4. L'alerte est stockée dans le Wazuh Indexer.
5. L'application Laravel récupère les alertes pertinentes.
6. Les alertes importées sont enregistrées sous forme d'incidents.
7. Les incidents sont analysés et leur niveau de risque est évalué.
8. Les contrôles de conformité concernés sont associés.
9. Les incidents sont affectés et leur traitement est suivi.

## Technologies utilisées

| Catégorie | Technologies |
|---|---|
| Supervision de sécurité | Wazuh Manager, Wazuh Indexer, Wazuh Agent |
| Backend | Laravel, PHP, Composer |
| Communication | API REST de Wazuh |
| Frontend | Blade, Tailwind CSS, JavaScript, Vite |
| Base de données | MariaDB / MySQL |
| Serveur web | Apache |
| Systèmes et environnement | Linux, Ubuntu, Kali Linux, VMware |

## Structure du projet

```text
wazuh-grc/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── ImportWazuhAlerts.php
│   ├── Http/
│   │   └── Controllers/
│   │       └── IncidentController.php
│   ├── Mail/
│   │   └── IncidentAssigned.php
│   ├── Models/
│   │   ├── Incident.php
│   │   ├── ComplianceControl.php
│   │   └── User.php
│   └── Services/
│       └── WazuhAlertService.php
├── database/
│   └── migrations/
├── resources/
│   └── views/
│       ├── incidents/
│       └── emails/
│           └── incident-assigned.blade.php
├── routes/
│   └── web.php
├── .env.example
├── artisan
├── composer.json
├── package.json
└── README.md
```

## Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/salouuuu/wazuh-grc.git
cd wazuh-grc
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Installer les dépendances frontend

```bash
npm install
```

### 4. Configurer l'environnement

Créer le fichier `.env` à partir du modèle fourni :

```bash
cp .env.example .env
```

Générer la clé de l'application Laravel :

```bash
php artisan key:generate
```

Configurer ensuite les paramètres de la base de données, la connexion au Wazuh Indexer et les paramètres d'envoi des e-mails dans le fichier `.env`.

### 5. Configurer la base de données

Créer la base de données de l'application et renseigner les paramètres correspondants :

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=grc
DB_USERNAME=
DB_PASSWORD=
```

### 6. Configurer la connexion à Wazuh

Renseigner les paramètres de connexion au Wazuh Indexer :

```dotenv
WAZUH_INDEXER_URL=https://<WAZUH_INDEXER_HOST>:9200
WAZUH_INDEXER_USERNAME=
WAZUH_INDEXER_PASSWORD=
```

Remplacer les valeurs d'exemple par les paramètres de l'environnement utilisé.

### 7. Exécuter les migrations

```bash
php artisan migrate
```

### 8. Configurer l'envoi des e-mails

Configurer les paramètres SMTP dans le fichier `.env` selon le service de messagerie utilisé.

Les identifiants et mots de passe doivent rester confidentiels et ne doivent pas être ajoutés au dépôt Git.

### 9. Démarrer l'application

```bash
php artisan serve --host=0.0.0.0
```

L'application sera accessible à l'adresse indiquée par Laravel dans le terminal. Si nécessaire, compiler les ressources frontend avec :

```bash
npm run build
```

## Importation des alertes Wazuh

Le projet fournit une commande Artisan pour importer les alertes de sécurité :

```bash
php artisan wazuh:import
```

Pour limiter le nombre d'alertes importées :

```bash
php artisan wazuh:import --limit=10
```

La commande récupère les alertes pertinentes depuis le Wazuh Indexer et crée les incidents correspondants dans l'application, en tenant compte du mécanisme de prévention des doublons.

## Tests de sécurité

L'intégration peut être vérifiée à l'aide d'événements de sécurité générés dans un environnement de laboratoire contrôlé, par exemple :

- Échecs d'authentification SSH.
- Erreurs de serveur web.
- Événements de surveillance de l'intégrité des fichiers (FIM).
- Événements liés à l'utilisation de privilèges.
- Alertes déclenchées par des règles Wazuh personnalisées.

Ces événements permettent de vérifier la génération des alertes dans Wazuh, leur importation dans Laravel et leur prise en charge dans le processus de gestion des incidents.

## Sécurité et bonnes pratiques

- Ne jamais publier le fichier `.env`.
- Ne pas exposer les identifiants Wazuh ou les mots de passe de la base de données.
- Ne pas publier de jetons d'API ni d'identifiants SMTP.
- Éviter de versionner des journaux de sécurité réels ou des alertes contenant des informations sensibles.
- Utiliser `.env.example` comme modèle de configuration sans y inclure de secrets.
- Contrôler les accès à l'application et aux informations relatives aux incidents.

## Améliorations possibles

Les évolutions envisagées comprennent :

- La synchronisation périodique des alertes Wazuh.
- L'ajout de tableaux de bord et d'indicateurs de sécurité.
- L'amélioration de la matrice d'évaluation des risques.
- L'enrichissement de la correspondance entre incidents et référentiels de conformité.
- Le renforcement de la gestion des rôles et des permissions.
- L'amélioration du suivi des actions correctives et de la traçabilité.
- L'intégration d'autres outils de cybersécurité.

## Projet académique

**Wazuh GRC — Plateforme de supervision de sécurité et de gestion des risques et de la conformité**

Projet réalisé dans le cadre d'un projet d'ingénierie en cybersécurité à l'ENSA Agadir.

## Licence

Ce projet est destiné à des fins pédagogiques et de recherche.
