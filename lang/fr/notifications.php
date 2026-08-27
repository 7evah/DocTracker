<?php

return [

    'title' => 'Notifications',
    'subtitle' => 'Affectations, décisions et échéances qui vous concernent.',
    'unread' => 'Non lues',
    'read' => 'Lues',
    'all' => 'Toutes',
    'mark_read' => 'Marquer comme lue',
    'mark_unread' => 'Marquer comme non lue',
    'mark_all_read' => 'Tout marquer comme lu',
    'delete' => 'Supprimer',
    'delete_read' => 'Supprimer les notifications lues',
    'view_all' => 'Voir toutes les notifications',
    'open' => 'Ouvrir',
    'empty' => 'Aucune notification',
    'empty_hint' => 'Les affectations de revue et les décisions d’approbation apparaîtront ici.',
    'empty_unread' => 'Aucune notification non lue',
    'empty_unread_hint' => 'Vous êtes à jour.',
    'bell_label' => 'Notifications — :count non lue|Notifications — :count non lues',
    'bell_label_none' => 'Notifications — aucune non lue',
    'count' => ':count notification|:count notifications',
    'recent' => 'Récentes',

    'messages' => [
        'marked_read' => 'Notification marquée comme lue.',
        'all_marked_read' => 'Toutes les notifications ont été marquées comme lues.',
        'deleted' => 'Notification supprimée.',
        'read_deleted' => 'Notifications lues supprimées.',
    ],

    /*
    | Message bodies, written as complete sentences so they read the same in
    | the notification centre, the bell dropdown and an e-mail (§26).
    */
    'review_assigned' => 'Le document :number (révision :revision) vous a été affecté pour revue.',
    'review_approved' => 'La révision :revision de :number a été approuvée par :reviewer.',
    'review_revision_requested' => 'La révision :revision de :number nécessite une révision.',
    'review_rejected' => 'La révision :revision de :number a été rejetée par :reviewer.',
    'review_updated' => 'La revue de :number a été mise à jour.',
    'deadline_soon' => 'L’échéance de revue pour :number approche.',

    'task_assigned' => 'Une tâche vous a été affectée : :title',

    'document_submitted_needs_reviewer' => 'La révision :revision de :number a été soumise pour revue et attend l’affectation d’un vérificateur.',
    'document_submitted_reviewers_kept' => 'La révision :revision de :number a été soumise pour revue ; les vérificateurs habituels ont été affectés automatiquement.',

    'approval_requested' => 'Votre approbation est requise sur :number (révision :revision).',
    'document_approved' => 'La révision :revision de :number a été approuvée.',
    'document_rejected' => 'La révision :revision de :number a été rejetée.',

    'mail' => [
        'greeting' => 'Bonjour :name,',
        'salutation' => 'Cordialement, l’équipe DocFlow',
        'deadline' => 'Échéance : :date',
        'review_assigned_subject' => 'DocFlow — :number à vérifier',
        'review_completed_subject' => 'DocFlow — décision de revue sur :number',
        'task_assigned_subject' => 'DocFlow — une tâche vous a été affectée',
        'approval_requested_subject' => 'DocFlow — :number à approuver',
        'document_decided_subject' => 'DocFlow — décision finale sur :number',
        'document_submitted_subject' => 'DocFlow — :number (révision :revision) soumis pour revue',
    ],

];
