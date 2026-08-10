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

    'fallback' => 'a effectué une action',

];
