<?php

return [

    'title' => 'Projets',
    'subtitle' => 'Portefeuille des projets d’ingénierie et avancement documentaire.',
    'singular' => 'Projet',

    'create' => 'Nouveau projet',
    'edit' => 'Modifier le projet',
    'create_heading' => 'Créer un projet',
    'edit_heading' => 'Modifier :name',

    'fields' => [
        'project_code' => 'Code projet',
        'name' => 'Nom du projet',
        'client' => 'Client',
        'location' => 'Localisation',
        'description' => 'Description',
        'manager' => 'Chef de projet',
        'status' => 'Statut',
        'start_date' => 'Date de début',
        'end_date' => 'Date de fin',
    ],

    'hints' => [
        'project_code' => 'Identifiant court et unique, ex. OCP-GA-2026.',
        'manager' => 'Responsable du suivi documentaire du projet.',
        'dates' => 'La date de fin doit être postérieure à la date de début.',
    ],

    'stats' => [
        'documents' => 'Documents',
        'approved' => 'Approuvés',
        'in_progress' => 'En cours',
        'open_tasks' => 'Tâches ouvertes',
        'progress' => 'Avancement',
    ],

    'filters' => [
        'search' => 'Rechercher un code, un nom, un client…',
        'status' => 'Tous les statuts',
        'manager' => 'Tous les chefs de projet',
        'sort' => 'Trier par',
    ],

    'sort' => [
        'latest' => 'Plus récents',
        'oldest' => 'Plus anciens',
        'code' => 'Code projet',
        'name' => 'Nom',
        'end_date' => 'Échéance',
    ],

    'view' => [
        'table' => 'Vue tableau',
        'cards' => 'Vue cartes',
    ],

    'tabs' => [
        'overview' => 'Aperçu',
        'documents' => 'Documents',
        'reviews' => 'Revues',
        'approvals' => 'Approbations',
        'tasks' => 'Tâches',
        'activity' => 'Activité',
    ],

    'empty' => [
        'title' => 'Aucun projet',
        'description' => 'Créez un projet pour commencer à y rattacher des documents techniques.',
        'filtered_title' => 'Aucun projet ne correspond',
        'filtered_description' => 'Ajustez votre recherche ou réinitialisez les filtres.',
        'documents' => 'Aucun document dans ce projet',
        'documents_hint' => 'Les documents téléversés pour ce projet apparaîtront ici.',
        'tasks' => 'Aucune tâche',
        'activity' => 'Aucune activité enregistrée',
    ],

    'messages' => [
        'created' => 'Projet créé avec succès.',
        'updated' => 'Projet mis à jour.',
        'deleted' => 'Projet supprimé.',
        'delete_confirm' => 'Supprimer définitivement ce projet ?',
        'delete_blocked' => 'Impossible de supprimer un projet contenant des documents. Changez plutôt son statut.',
    ],

    'overdue' => 'Échéance dépassée',
    'no_manager' => 'Non assigné',
    'documents_count' => ':count document|:count documents',

];
