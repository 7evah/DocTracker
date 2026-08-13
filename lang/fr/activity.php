<?php

/*
| Human-readable phrasing for audit-trail entries (§34).
|
| Keys match the log description passed to activity()->log(). Written to read
| naturally after a person's name:
|     "Youssef Amrani a téléversé la révision B"
*/

return [

    'document' => [
        'created' => 'a créé le document',
        'revision_uploaded' => 'a téléversé la révision :revision',
        'submitted' => 'a soumis la révision :revision pour revue',
        'archived' => 'a archivé le document',
        'unarchived' => 'a désarchivé le document',
        'downloaded' => 'a téléchargé la révision :revision',
        'updated' => 'a modifié le document',

        // Review outcomes, logged as document.review_<verdict>.
        'review_assigned' => 'a affecté la révision :revision pour revue',
        'review_approved' => 'a approuvé la revue de la révision :revision',
        'review_revision_requested' => 'a demandé une révision',
        'review_rejected' => 'a rejeté la révision :revision en revue',
        'commented' => 'a commenté la révision :revision',

        // Approval circuit (§8).
        'approval_started' => 'a lancé le circuit d’approbation',
        'approval_approved' => 'a approuvé une étape du circuit',
        'approval_rejected' => 'a rejeté le document au circuit d’approbation',
        'approved' => 'a approuvé le document',
    ],

    'project' => [
        'created' => 'a créé le projet',
        'updated' => 'a modifié le projet',
        'deleted' => 'a supprimé le projet',
    ],

    'review' => [
        'assigned' => 'a affecté une revue',
        'completed' => 'a terminé la revue',
        'revision_requested' => 'a demandé une révision',
        'commented' => 'a ajouté un commentaire',
    ],

    'approval' => [
        'approved' => 'a approuvé la révision :revision',
        'rejected' => 'a rejeté la révision :revision',
    ],

    'task' => [
        'created' => 'a créé la tâche',
        'updated' => 'a modifié la tâche',
        'completed' => 'a terminé la tâche',
        'reopened' => 'a rouvert la tâche',
        'cancelled' => 'a annulé la tâche',
    ],

    'fallback' => 'a effectué une action',

];
