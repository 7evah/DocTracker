<?php

return [

    'title' => 'Notifications',
    'unread' => 'Non lues',
    'mark_read' => 'Marquer comme lue',
    'mark_all_read' => 'Tout marquer comme lu',
    'empty' => 'Aucune notification',
    'empty_hint' => 'Les affectations de revue et les décisions d’approbation apparaîtront ici.',

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

    'approval_requested' => 'Votre approbation est requise sur :number (révision :revision).',
    'document_approved' => 'La révision :revision de :number a été approuvée.',
    'document_rejected' => 'La révision :revision de :number a été rejetée.',

    'mail' => [
        'greeting' => 'Bonjour :name,',
        'salutation' => 'Cordialement, l’équipe DocFlow',
        'deadline' => 'Échéance : :date',
        'review_assigned_subject' => 'DocFlow — :number à vérifier',
        'review_completed_subject' => 'DocFlow — décision de revue sur :number',
        'approval_requested_subject' => 'DocFlow — :number à approuver',
        'document_decided_subject' => 'DocFlow — décision finale sur :number',
    ],

];
