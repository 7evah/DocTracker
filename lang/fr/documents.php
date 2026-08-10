<?php

return [

    'title' => 'Documents',
    'subtitle' => 'Documents techniques, révisions et état d’avancement.',
    'singular' => 'Document',

    'create' => 'Téléverser un document',
    'create_heading' => 'Nouveau document technique',
    'edit_heading' => 'Modifier :number',

    'fields' => [
        'document_number' => 'Numéro de document',
        'title' => 'Titre',
        'description' => 'Description',
        'project' => 'Projet',
        'discipline' => 'Discipline',
        'revision' => 'Révision',
        'version_notes' => 'Notes de version',
        'file' => 'Fichier',
        'status' => 'Statut',
        'creator' => 'Créé par',
        'uploaded_by' => 'Téléversé par',
        'uploaded_at' => 'Téléversé le',
        'size' => 'Taille',
        'filename' => 'Nom du fichier',
    ],

    'hints' => [
        'document_number' => 'Doit être unique dans le projet, ex. ME-1023.',
        'revision' => 'Première révision, généralement « A ».',
        'version_notes' => 'Décrivez ce qui change par rapport à la révision précédente.',
        'file' => 'Formats acceptés : :formats — taille maximale :size.',
        'auto_number' => 'Le préfixe correspond au code de la discipline sélectionnée.',
    ],

    'filters' => [
        'search' => 'Rechercher un numéro ou un titre…',
        'project' => 'Tous les projets',
        'discipline' => 'Toutes les disciplines',
        'status' => 'Tous les statuts',
        'creator' => 'Tous les auteurs',
        'from' => 'Du',
        'to' => 'Au',
    ],

    'sort' => [
        'latest' => 'Plus récents',
        'oldest' => 'Plus anciens',
        'number' => 'Numéro de document',
        'title' => 'Titre',
        'status' => 'Statut',
    ],

    'tabs' => [
        'overview' => 'Aperçu',
        'revisions' => 'Historique des révisions',
        'reviews' => 'Revues',
        'approvals' => 'Approbations',
        'comments' => 'Commentaires',
        'tasks' => 'Tâches',
        'activity' => 'Activité',
    ],

    'actions' => [
        'download' => 'Télécharger',
        'download_revision' => 'Télécharger la révision :revision',
        'upload_revision' => 'Nouvelle révision',
        'submit_review' => 'Soumettre pour revue',
        'archive' => 'Archiver',
        'unarchive' => 'Désarchiver',
        'preview' => 'Aperçu',
    ],

    'current_version' => 'Révision courante',
    'no_versions' => 'Aucune révision',
    'revision_label' => 'Révision :revision',

    'empty' => [
        'title' => 'Aucun document',
        'description' => 'Téléversez un premier document technique pour ce projet.',
        'filtered_title' => 'Aucun document ne correspond',
        'filtered_description' => 'Ajustez votre recherche ou réinitialisez les filtres.',
        'comments' => 'Aucun commentaire',
        'activity' => 'Aucune activité enregistrée',
    ],

    'messages' => [
        'created' => 'Document téléversé avec succès.',
        'updated' => 'Document mis à jour.',
        'revision_added' => 'Révision :revision ajoutée.',
        'submitted' => 'Document soumis pour revue.',
        'archived' => 'Document archivé.',
        'unarchived' => 'Document désarchivé.',
        'deleted' => 'Document supprimé.',
        'upload_failed' => 'Impossible de téléverser le document. Vérifiez le format et la taille du fichier.',
        'revision_blocked' => 'Une nouvelle révision ne peut pas être ajoutée tant que le document est en revue.',
        'submit_blocked' => 'Seul un document en brouillon ou nécessitant une révision peut être soumis.',
        'file_missing' => 'Le fichier de cette révision est introuvable sur le serveur.',
    ],

    'preview' => [
        'unavailable' => 'Aperçu indisponible pour ce format',
        'unavailable_hint' => 'Téléchargez le fichier pour le consulter. L’aperçu PDF est disponible dans le navigateur.',
    ],

    'count' => ':count document|:count documents',

];
