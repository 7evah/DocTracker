<?php

return [

    'title' => 'Rapports',
    'subtitle' => 'Indicateurs de suivi documentaire et de performance des circuits.',

    'types' => [
        'document_status_summary' => [
            'label' => 'Synthèse par statut',
            'description' => 'Répartition des documents selon leur statut.',
        ],
        'documents_by_project' => [
            'label' => 'Documents par projet',
            'description' => 'Volume documentaire et avancement par projet.',
        ],
        'documents_by_discipline' => [
            'label' => 'Documents par discipline',
            'description' => 'Répartition des documents entre les disciplines.',
        ],
        'project_progress' => [
            'label' => 'Avancement des projets',
            'description' => 'Part de documents approuvés et respect des échéances.',
        ],
        'review_delays' => [
            'label' => 'Délais de revue',
            'description' => 'Charge et délai moyen de traitement par vérificateur.',
        ],
        'approval_performance' => [
            'label' => 'Performance des approbations',
            'description' => 'Volume signé et délai moyen par approbateur.',
        ],
        'overdue_reviews' => [
            'label' => 'Revues en retard',
            'description' => 'Revues dont l’échéance est dépassée.',
        ],
        'overdue_approvals' => [
            'label' => 'Approbations en retard',
            'description' => 'Étapes d’approbation dont l’échéance est dépassée.',
        ],
        'user_workload' => [
            'label' => 'Charge par utilisateur',
            'description' => 'Revues, approbations et tâches ouvertes par personne.',
        ],
    ],

    'headings' => [
        'count' => 'Nombre',
        'share' => 'Part',
        'total' => 'Total',
        'documents' => 'Documents',
        'approved' => 'Approuvés',
        'rejected' => 'Rejetés',
        'pending' => 'En cours',
        'open' => 'Ouvertes',
        'overdue' => 'En retard',
        'assigned' => 'Affectées',
        'completed' => 'Terminées',
        'late' => 'Terminées en retard',
        'avg_days' => 'Délai moyen (j)',
        'days_late' => 'Jours de retard',
        'code' => 'Code',
        'open_reviews' => 'Revues ouvertes',
        'open_approvals' => 'Approbations ouvertes',
        'open_tasks' => 'Tâches ouvertes',
        'documents_created' => 'Documents créés',
        'total_open' => 'Total en cours',
    ],

    'filters' => [
        'title' => 'Filtres',
        'project' => 'Tous les projets',
        'discipline' => 'Toutes les disciplines',
        'from' => 'Du',
        'to' => 'Au',
        'not_applicable' => 'Non applicable à ce rapport',
    ],

    'export' => [
        'excel' => 'Exporter en Excel',
        'pdf' => 'Exporter en PDF',
        'generated_at' => 'Généré le :date',
        'generated_by' => 'Généré par :name',
        'filters_applied' => 'Filtres appliqués',
        'no_filters' => 'Aucun filtre',
    ],

    'empty' => [
        'title' => 'Aucune donnée',
        'description' => 'Aucun résultat pour les filtres sélectionnés.',
    ],

    'select_report' => 'Choisir un rapport',
    'rows' => ':count ligne|:count lignes',

];
