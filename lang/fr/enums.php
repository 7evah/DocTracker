<?php

return [

    'document_status' => [
        'draft' => 'Brouillon',
        'under_review' => 'En revue',
        'needs_revision' => 'Révision requise',
        'approved' => 'Approuvé',
        'rejected' => 'Rejeté',
        'archived' => 'Archivé',
    ],

    'priority' => [
        'low' => 'Basse',
        'medium' => 'Moyenne',
        'high' => 'Haute',
        'critical' => 'Critique',
    ],

    'user_status' => [
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'suspended' => 'Suspendu',
    ],

    'role' => [
        'administrator' => 'Administrateur',
        'project_manager' => 'Chef de projet',
        'engineer' => 'Ingénieur',
        'reviewer' => 'Vérificateur',
        'approver' => 'Approbateur',
        'viewer' => 'Lecteur',
    ],

    'role_description' => [
        'administrator' => 'Accès complet : utilisateurs, rôles, projets, disciplines et paramètres.',
        'project_manager' => 'Pilote les projets, affecte les revues et suit l’avancement documentaire.',
        'engineer' => 'Crée les documents, dépose les révisions et répond aux commentaires.',
        'reviewer' => 'Vérifie les documents affectés, commente et demande des révisions.',
        'approver' => 'Approuve ou rejette les documents aux étapes d’approbation.',
        'viewer' => 'Consultation seule des projets et documents.',
    ],

];
