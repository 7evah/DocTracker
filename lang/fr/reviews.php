<?php

return [

    'title' => 'Revues',
    'subtitle' => 'Documents qui vous sont affectés pour vérification technique.',
    'singular' => 'Revue',
    'my_reviews' => 'Mes revues',
    'all_reviews' => 'Toutes les revues',

    'fields' => [
        'reviewer' => 'Vérificateur',
        'reviewers' => 'Vérificateurs',
        'status' => 'Statut',
        'priority' => 'Priorité',
        'assigned_at' => 'Affectée le',
        'assigned_by' => 'Affectée par',
        'deadline' => 'Échéance',
        'reviewed_at' => 'Terminée le',
        'summary' => 'Synthèse de la revue',
        'document' => 'Document',
        'revision' => 'Révision',
    ],

    'assign' => [
        'title' => 'Affecter pour revue',
        'heading' => 'Affecter des vérificateurs',
        'intro' => 'Les vérificateurs sélectionnés seront notifiés et le document passera en revue.',
        'reviewers' => 'Vérificateurs',
        'reviewers_hint' => 'Sélectionnez une ou plusieurs personnes.',
        'deadline_hint' => 'Pré-remplie selon la priorité choisie.',
        'submit' => 'Affecter et notifier',
        'no_candidates' => 'Aucun vérificateur disponible. Créez un utilisateur avec le rôle Vérificateur.',
    ],

    'actions' => [
        'open' => 'Ouvrir la revue',
        'start' => 'Commencer la revue',
        'approve' => 'Approuver',
        'request_revision' => 'Demander une révision',
        'reject' => 'Rejeter',
        'assign' => 'Affecter pour revue',
        'reassign' => 'Modifier les vérificateurs',
    ],

    'confirm' => [
        'approve' => 'Approuver cette révision ?',
        'request_revision' => 'Demander une révision de ce document ?',
        'reject' => 'Rejeter cette révision ?',
        'summary_required' => 'Une synthèse est obligatoire pour une demande de révision ou un rejet.',
    ],

    'filters' => [
        'scope' => 'Périmètre',
        'mine' => 'Qui me sont affectées',
        'all' => 'Toutes',
        'status' => 'Tous les statuts',
        'priority' => 'Toutes les priorités',
        'project' => 'Tous les projets',
        'pending' => 'En attente',
        'completed' => 'Terminées',
        'overdue' => 'En retard',
    ],

    'empty' => [
        'title' => 'Aucune revue',
        'description' => 'Les documents qui vous sont affectés pour vérification apparaîtront ici.',
        'filtered_title' => 'Aucune revue ne correspond',
        'filtered_description' => 'Ajustez votre recherche ou réinitialisez les filtres.',
        'none_on_document' => 'Aucune revue sur cette révision',
        'none_on_document_hint' => 'Affectez un vérificateur pour lancer le processus.',
    ],

    'messages' => [
        'assigned' => 'Revue affectée à :count vérificateur|Revue affectée à :count vérificateurs',
        'started' => 'Revue démarrée.',
        'approved' => 'Revue approuvée.',
        'revision_requested' => 'Révision demandée.',
        'rejected' => 'Document rejeté.',
        'already_completed' => 'Cette revue est déjà terminée.',
        'not_reviewable' => 'Ce document n’est pas en cours de revue.',
        'assign_blocked' => 'Seul un document en brouillon ou nécessitant une révision peut être affecté pour revue.',
    ],

    'overdue' => 'En retard',
    'due_in' => 'Échéance :time',
    'no_deadline' => 'Sans échéance',
    'count' => ':count revue|:count revues',
    'on_revision' => 'Sur la révision :revision',

    'comments' => [
        'title' => 'Commentaires',
        'placeholder' => 'Ajouter un commentaire…',
        'submit' => 'Publier',
        'reply' => 'Répondre',
        'resolve' => 'Marquer comme résolu',
        'resolved' => 'Résolu',
        'resolved_by' => 'Résolu par :name',
        'open' => 'Ouvert',
        'page' => 'Page :page',
        'empty' => 'Aucun commentaire',
        'empty_hint' => 'Les remarques de vérification apparaîtront ici.',
        'added' => 'Commentaire ajouté.',
        'resolved_message' => 'Commentaire résolu.',
        'open_count' => ':count ouvert|:count ouverts',
    ],

];
