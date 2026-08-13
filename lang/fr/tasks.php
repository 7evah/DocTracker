<?php

return [

    'title' => 'Tâches',
    'subtitle' => 'Actions de suivi issues des revues et des réunions.',
    'singular' => 'Tâche',

    'create' => 'Nouvelle tâche',
    'create_heading' => 'Créer une tâche',
    'edit_heading' => 'Modifier la tâche',

    'fields' => [
        'title' => 'Intitulé',
        'description' => 'Description',
        'project' => 'Projet',
        'document' => 'Document',
        'assigned_to' => 'Affectée à',
        'created_by' => 'Créée par',
        'priority' => 'Priorité',
        'status' => 'Statut',
        'due_date' => 'Échéance',
        'completed_at' => 'Terminée le',
    ],

    'hints' => [
        'document' => 'Optionnel — rattachez la tâche à un document précis.',
        'due_date' => 'Une tâche sans échéance ne peut pas être signalée en retard.',
    ],

    'actions' => [
        'complete' => 'Marquer comme terminée',
        'reopen' => 'Rouvrir',
        'cancel_task' => 'Annuler la tâche',
        'edit' => 'Modifier',
        'open' => 'Ouvrir',
    ],

    'filters' => [
        'scope' => 'Périmètre',
        'mine' => 'Qui me sont affectées',
        'all' => 'Toutes',
        'created_by_me' => 'Que j’ai créées',
        'status' => 'Tous les statuts',
        'priority' => 'Toutes les priorités',
        'project' => 'Tous les projets',
        'open' => 'Ouvertes',
        'overdue' => 'En retard',
        'completed' => 'Terminées',
        'search' => 'Rechercher une tâche…',
    ],

    'empty' => [
        'title' => 'Aucune tâche',
        'description' => 'Créez une tâche pour suivre une action à réaliser.',
        'filtered_title' => 'Aucune tâche ne correspond',
        'filtered_description' => 'Ajustez votre recherche ou vos filtres.',
        'none_on_document' => 'Aucune tâche sur ce document',
        'none_on_project' => 'Aucune tâche sur ce projet',
    ],

    'messages' => [
        'created' => 'Tâche créée.',
        'updated' => 'Tâche mise à jour.',
        'completed' => 'Tâche marquée comme terminée.',
        'reopened' => 'Tâche rouverte.',
        'cancelled' => 'Tâche annulée.',
        'deleted' => 'Tâche supprimée.',
        'already_completed' => 'Cette tâche est déjà terminée.',
        'document_project_mismatch' => 'Le document sélectionné n’appartient pas à ce projet.',
    ],

    'overdue' => 'En retard',
    'due_today' => 'Échéance aujourd’hui',
    'no_due_date' => 'Sans échéance',
    'count' => ':count tâche|:count tâches',
    'unassigned' => 'Non affectée',

];
