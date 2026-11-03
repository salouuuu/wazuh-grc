<?php

/*
|--------------------------------------------------------------------------
| ISO/IEC 27001 : correspondance version 2013 → version 2022
|--------------------------------------------------------------------------
|
| Wazuh étiquette ses règles avec la numérotation ISO 27001:2013
| (champ rule.iso_27001-2013), alors que l'application évalue la conformité
| avec la numérotation ISO 27001:2022.
|
| 'mapping'  : les 114 contrôles de l'annexe A 2013 et le ou les contrôles
|              2022 correspondants (d'après l'annexe B de l'ISO/IEC 27002:2022).
| 'controls' : les 93 contrôles de l'annexe A 2022 (intitulés en français).
|
| Une correspondance n'est qu'une suggestion : l'analyste choisit le contrôle.
*/

return [

    'mapping' => [
        // A.5 Politiques de sécurité
        'A.5.1.1' => ['A.5.1'],
        'A.5.1.2' => ['A.5.1'],

        // A.6 Organisation de la sécurité
        'A.6.1.1' => ['A.5.2'],
        'A.6.1.2' => ['A.5.3'],
        'A.6.1.3' => ['A.5.5'],
        'A.6.1.4' => ['A.5.6'],
        'A.6.1.5' => ['A.5.8'],
        'A.6.2.1' => ['A.8.1'],
        'A.6.2.2' => ['A.6.7'],

        // A.7 Sécurité des ressources humaines
        'A.7.1.1' => ['A.6.1'],
        'A.7.1.2' => ['A.6.2'],
        'A.7.2.1' => ['A.5.4'],
        'A.7.2.2' => ['A.6.3'],
        'A.7.2.3' => ['A.6.4'],
        'A.7.3.1' => ['A.6.5'],

        // A.8 Gestion des actifs
        'A.8.1.1' => ['A.5.9'],
        'A.8.1.2' => ['A.5.9'],
        'A.8.1.3' => ['A.5.10'],
        'A.8.1.4' => ['A.5.11'],
        'A.8.2.1' => ['A.5.12'],
        'A.8.2.2' => ['A.5.13'],
        'A.8.2.3' => ['A.5.10'],
        'A.8.3.1' => ['A.7.10'],
        'A.8.3.2' => ['A.7.10'],
        'A.8.3.3' => ['A.7.10'],

        // A.9 Contrôle d'accès
        'A.9.1.1' => ['A.5.15'],
        'A.9.1.2' => ['A.5.15'],
        'A.9.2.1' => ['A.5.16'],
        'A.9.2.2' => ['A.5.18'],
        'A.9.2.3' => ['A.8.2'],
        'A.9.2.4' => ['A.5.17'],
        'A.9.2.5' => ['A.5.18'],
        'A.9.2.6' => ['A.5.18'],
        'A.9.3.1' => ['A.5.17'],
        'A.9.4.1' => ['A.8.3'],
        'A.9.4.2' => ['A.8.5'],
        'A.9.4.3' => ['A.5.17'],
        'A.9.4.4' => ['A.8.18'],
        'A.9.4.5' => ['A.8.4'],

        // A.10 Cryptographie
        'A.10.1.1' => ['A.8.24'],
        'A.10.1.2' => ['A.8.24'],

        // A.11 Sécurité physique et environnementale
        'A.11.1.1' => ['A.7.1'],
        'A.11.1.2' => ['A.7.2'],
        'A.11.1.3' => ['A.7.3'],
        'A.11.1.4' => ['A.7.5'],
        'A.11.1.5' => ['A.7.6'],
        'A.11.1.6' => ['A.7.2'],
        'A.11.2.1' => ['A.7.8'],
        'A.11.2.2' => ['A.7.11'],
        'A.11.2.3' => ['A.7.12'],
        'A.11.2.4' => ['A.7.13'],
        'A.11.2.5' => ['A.7.10'],
        'A.11.2.6' => ['A.7.9'],
        'A.11.2.7' => ['A.7.14'],
        'A.11.2.8' => ['A.8.1'],
        'A.11.2.9' => ['A.7.7'],

        // A.12 Sécurité liée à l'exploitation
        'A.12.1.1' => ['A.5.37'],
        'A.12.1.2' => ['A.8.32'],
        'A.12.1.3' => ['A.8.6'],
        'A.12.1.4' => ['A.8.31'],
        'A.12.2.1' => ['A.8.7'],
        'A.12.3.1' => ['A.8.13'],
        'A.12.4.1' => ['A.8.15'],
        'A.12.4.2' => ['A.8.15'],
        'A.12.4.3' => ['A.8.15'],
        'A.12.4.4' => ['A.8.17'],
        'A.12.5.1' => ['A.8.19'],
        'A.12.6.1' => ['A.8.8'],
        'A.12.6.2' => ['A.8.19'],
        'A.12.7.1' => ['A.8.34'],

        // A.13 Sécurité des communications
        'A.13.1.1' => ['A.8.20'],
        'A.13.1.2' => ['A.8.21'],
        'A.13.1.3' => ['A.8.22'],
        'A.13.2.1' => ['A.5.14'],
        'A.13.2.2' => ['A.5.14'],
        'A.13.2.3' => ['A.5.14'],
        'A.13.2.4' => ['A.6.6'],

        // A.14 Acquisition, développement et maintenance des systèmes
        'A.14.1.1' => ['A.5.8'],
        'A.14.1.2' => ['A.8.26'],
        'A.14.1.3' => ['A.8.26'],
        'A.14.2.1' => ['A.8.25'],
        'A.14.2.2' => ['A.8.32'],
        'A.14.2.3' => ['A.8.32'],
        'A.14.2.4' => ['A.8.32'],
        'A.14.2.5' => ['A.8.27'],
        'A.14.2.6' => ['A.8.31'],
        'A.14.2.7' => ['A.8.30'],
        'A.14.2.8' => ['A.8.29'],
        'A.14.2.9' => ['A.8.29'],
        'A.14.3.1' => ['A.8.33'],

        // A.15 Relations avec les fournisseurs
        'A.15.1.1' => ['A.5.19'],
        'A.15.1.2' => ['A.5.20'],
        'A.15.1.3' => ['A.5.21'],
        'A.15.2.1' => ['A.5.22'],
        'A.15.2.2' => ['A.5.22'],

        // A.16 Gestion des incidents
        'A.16.1.1' => ['A.5.24'],
        'A.16.1.2' => ['A.6.8'],
        'A.16.1.3' => ['A.6.8'],
        'A.16.1.4' => ['A.5.25'],
        'A.16.1.5' => ['A.5.26'],
        'A.16.1.6' => ['A.5.27'],
        'A.16.1.7' => ['A.5.28'],

        // A.17 Continuité d'activité
        'A.17.1.1' => ['A.5.29'],
        'A.17.1.2' => ['A.5.29'],
        'A.17.1.3' => ['A.5.29'],
        'A.17.2.1' => ['A.8.14'],

        // A.18 Conformité
        'A.18.1.1' => ['A.5.31'],
        'A.18.1.2' => ['A.5.32'],
        'A.18.1.3' => ['A.5.33'],
        'A.18.1.4' => ['A.5.34'],
        'A.18.1.5' => ['A.5.31'],
        'A.18.2.1' => ['A.5.35'],
        'A.18.2.2' => ['A.5.36'],
        'A.18.2.3' => ['A.5.36', 'A.8.8'],
    ],

    'controls' => [
        // 5. Mesures organisationnelles
        'A.5.1' => 'Politiques de sécurité de l’information',
        'A.5.2' => 'Fonctions et responsabilités liées à la sécurité de l’information',
        'A.5.3' => 'Séparation des tâches',
        'A.5.4' => 'Responsabilités de la direction',
        'A.5.5' => 'Contacts avec les autorités',
        'A.5.6' => 'Contacts avec des groupes d’intérêt spécifiques',
        'A.5.7' => 'Renseignements sur les menaces',
        'A.5.8' => 'Sécurité de l’information dans la gestion de projet',
        'A.5.9' => 'Inventaire des informations et autres actifs associés',
        'A.5.10' => 'Utilisation correcte des informations et autres actifs associés',
        'A.5.11' => 'Restitution des actifs',
        'A.5.12' => 'Classification des informations',
        'A.5.13' => 'Marquage des informations',
        'A.5.14' => 'Transfert des informations',
        'A.5.15' => 'Contrôle d’accès',
        'A.5.16' => 'Gestion des identités',
        'A.5.17' => 'Informations d’authentification',
        'A.5.18' => 'Droits d’accès',
        'A.5.19' => 'Sécurité de l’information dans les relations avec les fournisseurs',
        'A.5.20' => 'Sécurité de l’information dans les accords avec les fournisseurs',
        'A.5.21' => 'Sécurité de l’information dans la chaîne d’approvisionnement TIC',
        'A.5.22' => 'Surveillance, revue et gestion des changements des services fournisseurs',
        'A.5.23' => 'Sécurité de l’information dans l’utilisation de services en nuage',
        'A.5.24' => 'Planification et préparation de la gestion des incidents',
        'A.5.25' => 'Évaluation des événements de sécurité et prise de décision',
        'A.5.26' => 'Réponse aux incidents de sécurité de l’information',
        'A.5.27' => 'Tirer des enseignements des incidents de sécurité',
        'A.5.28' => 'Collecte des preuves',
        'A.5.29' => 'Sécurité de l’information durant une perturbation',
        'A.5.30' => 'Préparation des TIC pour la continuité d’activité',
        'A.5.31' => 'Exigences légales, statutaires, réglementaires et contractuelles',
        'A.5.32' => 'Droits de propriété intellectuelle',
        'A.5.33' => 'Protection des enregistrements',
        'A.5.34' => 'Protection de la vie privée et des données personnelles',
        'A.5.35' => 'Revue indépendante de la sécurité de l’information',
        'A.5.36' => 'Conformité aux politiques, règles et normes de sécurité',
        'A.5.37' => 'Procédures d’exploitation documentées',

        // 6. Mesures liées aux personnes
        'A.6.1' => 'Sélection des candidats',
        'A.6.2' => 'Termes et conditions du contrat de travail',
        'A.6.3' => 'Sensibilisation, enseignement et formation à la sécurité',
        'A.6.4' => 'Processus disciplinaire',
        'A.6.5' => 'Responsabilités après la fin ou le changement d’un emploi',
        'A.6.6' => 'Accords de confidentialité ou de non-divulgation',
        'A.6.7' => 'Travail à distance',
        'A.6.8' => 'Déclaration des événements de sécurité de l’information',

        // 7. Mesures physiques
        'A.7.1' => 'Périmètres de sécurité physique',
        'A.7.2' => 'Entrées physiques',
        'A.7.3' => 'Sécurisation des bureaux, des salles et des installations',
        'A.7.4' => 'Surveillance de la sécurité physique',
        'A.7.5' => 'Protection contre les menaces physiques et environnementales',
        'A.7.6' => 'Travail dans les zones sécurisées',
        'A.7.7' => 'Bureau propre et écran vide',
        'A.7.8' => 'Emplacement et protection du matériel',
        'A.7.9' => 'Sécurité des actifs hors des locaux',
        'A.7.10' => 'Supports de stockage',
        'A.7.11' => 'Services généraux',
        'A.7.12' => 'Sécurité du câblage',
        'A.7.13' => 'Maintenance du matériel',
        'A.7.14' => 'Élimination ou recyclage sécurisé du matériel',

        // 8. Mesures technologiques
        'A.8.1' => 'Terminaux utilisateurs',
        'A.8.2' => 'Droits d’accès privilégiés',
        'A.8.3' => 'Restriction d’accès aux informations',
        'A.8.4' => 'Accès aux codes source',
        'A.8.5' => 'Authentification sécurisée',
        'A.8.6' => 'Dimensionnement',
        'A.8.7' => 'Protection contre les logiciels malveillants',
        'A.8.8' => 'Gestion des vulnérabilités techniques',
        'A.8.9' => 'Gestion de la configuration',
        'A.8.10' => 'Suppression des informations',
        'A.8.11' => 'Masquage des données',
        'A.8.12' => 'Prévention de la fuite de données',
        'A.8.13' => 'Sauvegarde des informations',
        'A.8.14' => 'Redondance des moyens de traitement de l’information',
        'A.8.15' => 'Journalisation',
        'A.8.16' => 'Activités de surveillance',
        'A.8.17' => 'Synchronisation des horloges',
        'A.8.18' => 'Utilisation de programmes utilitaires à privilèges',
        'A.8.19' => 'Installation de logiciels sur des systèmes en exploitation',
        'A.8.20' => 'Sécurité des réseaux',
        'A.8.21' => 'Sécurité des services réseau',
        'A.8.22' => 'Cloisonnement des réseaux',
        'A.8.23' => 'Filtrage web',
        'A.8.24' => 'Utilisation de la cryptographie',
        'A.8.25' => 'Cycle de vie de développement sécurisé',
        'A.8.26' => 'Exigences de sécurité des applications',
        'A.8.27' => 'Principes d’ingénierie et d’architecture des systèmes sécurisés',
        'A.8.28' => 'Codage sécurisé',
        'A.8.29' => 'Tests de sécurité dans le développement et l’acceptation',
        'A.8.30' => 'Développement externalisé',
        'A.8.31' => 'Séparation des environnements de développement, de test et de production',
        'A.8.32' => 'Gestion des changements',
        'A.8.33' => 'Informations de test',
        'A.8.34' => 'Protection des systèmes d’information pendant les audits et les tests',
    ],

];
