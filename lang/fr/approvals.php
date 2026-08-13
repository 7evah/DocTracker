<?php

return [

    'title' => 'Approbations',
    'subtitle' => 'Documents en attente de votre signature.',
    'singular' => 'Approbation',
    'workflow' => 'Circuit d’approbation',

    'fields' => [
        'approver' => 'Approbateur',
        'step' => 'Étape',
        'role' => 'Rôle',
        'status' => 'Statut',
        'assigned_at' => 'Affectée le',
        'deadline' => 'Échéance',
        'approved_at' => 'Approuvée le',
        'rejected_at' => 'Rejetée le',
        'comment' => 'Commentaire',
        'document' => 'Document',
    ],

    'actions' => [
        'approve' => 'Approuver',
        'reject' => 'Rejeter',
        'open' => 'Ouvrir',
        'comment' => 'Ajouter un commentaire',
    ],

    'confirm' => [
        'approve' => 'Approuver cette étape ?',
        'approve_final' => 'Ceci est la dernière étape : le document passera en « Approuvé ».',
        'reject' => 'Rejeter ce document ?',
        'reject_hint' => 'Le circuit sera interrompu et les étapes suivantes ignorées.',
        'comment_required' => 'Un commentaire est obligatoire pour un rejet.',
    ],

    'stepper' => [
        'title' => 'Progression de l’approbation',
        'waiting' => 'En attente',
        'current' => 'Étape en cours',
        'completed' => 'Terminée',
        'step_of' => 'Étape :current sur :total',
        'not_started' => 'Circuit non démarré',
        'not_started_hint' => 'Le circuit d’approbation démarre une fois toutes les revues favorables.',
    ],

    'filters' => [
        'scope' => 'Périmètre',
        'mine' => 'Qui me sont affectées',
        'all' => 'Toutes',
        'pending' => 'En attente',
        'completed' => 'Terminées',
        'overdue' => 'En retard',
        'project' => 'Tous les projets',
    ],

    'empty' => [
        'title' => 'Aucune approbation',
        'description' => 'Les documents en attente de votre signature apparaîtront ici.',
        'filtered_title' => 'Aucune approbation ne correspond',
        'filtered_description' => 'Ajustez vos filtres.',
        'none_on_document' => 'Aucune approbation sur cette révision',
    ],

    'messages' => [
        'approved' => 'Étape approuvée.',
        'approved_final' => 'Document approuvé.',
        'rejected' => 'Document rejeté.',
        'started' => 'Circuit d’approbation démarré.',
        'not_your_turn' => 'Cette étape n’est pas encore active ou ne vous est pas affectée.',
        'no_workflow' => 'Aucun circuit d’approbation actif n’est défini.',
        'no_approver' => 'Aucun utilisateur ne correspond au rôle « :role » pour l’étape :step.',
    ],

    'overdue' => 'En retard',
    'count' => ':count approbation|:count approbations',

];
