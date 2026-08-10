<?php

namespace App\Support;

use App\Enums\UserRole;

/**
 * Single source of truth for permission names and the role → permission map.
 *
 * Policies reference these constants rather than string literals so a typo is
 * a fatal error instead of a silently-denied (or silently-granted) check.
 */
final class Permissions
{
    // Projects
    public const PROJECTS_VIEW = 'projects.view';

    public const PROJECTS_CREATE = 'projects.create';

    public const PROJECTS_UPDATE = 'projects.update';

    public const PROJECTS_DELETE = 'projects.delete';

    // Documents
    public const DOCUMENTS_VIEW = 'documents.view';

    public const DOCUMENTS_CREATE = 'documents.create';

    public const DOCUMENTS_UPDATE = 'documents.update';

    public const DOCUMENTS_DELETE = 'documents.delete';

    public const DOCUMENTS_DOWNLOAD = 'documents.download';

    public const DOCUMENTS_UPLOAD_REVISION = 'documents.upload_revision';

    public const DOCUMENTS_SUBMIT_REVIEW = 'documents.submit_review';

    public const DOCUMENTS_ARCHIVE = 'documents.archive';

    // Reviews
    public const REVIEWS_VIEW = 'reviews.view';

    public const REVIEWS_ASSIGN = 'reviews.assign';

    public const DOCUMENTS_REVIEW = 'documents.review';

    // Approvals
    public const APPROVALS_VIEW = 'approvals.view';

    public const DOCUMENTS_APPROVE = 'documents.approve';

    public const DOCUMENTS_REJECT = 'documents.reject';

    // Tasks
    public const TASKS_VIEW = 'tasks.view';

    public const TASKS_CREATE = 'tasks.create';

    public const TASKS_UPDATE = 'tasks.update';

    public const TASKS_COMPLETE = 'tasks.complete';

    // Reporting & audit
    public const REPORTS_VIEW = 'reports.view';

    public const REPORTS_EXPORT = 'reports.export';

    public const ACTIVITY_VIEW = 'activity.view';

    // Administration
    public const USERS_MANAGE = 'users.manage';

    public const SETTINGS_MANAGE = 'settings.manage';

    public const DISCIPLINES_MANAGE = 'disciplines.manage';

    public const WORKFLOWS_MANAGE = 'workflows.manage';

    /**
     * Every permission the application knows about, grouped for the role
     * editor UI in the admin area (§29).
     *
     * @return array<string, list<string>>
     */
    public static function grouped(): array
    {
        return [
            'projects' => [
                self::PROJECTS_VIEW,
                self::PROJECTS_CREATE,
                self::PROJECTS_UPDATE,
                self::PROJECTS_DELETE,
            ],
            'documents' => [
                self::DOCUMENTS_VIEW,
                self::DOCUMENTS_CREATE,
                self::DOCUMENTS_UPDATE,
                self::DOCUMENTS_DELETE,
                self::DOCUMENTS_DOWNLOAD,
                self::DOCUMENTS_UPLOAD_REVISION,
                self::DOCUMENTS_SUBMIT_REVIEW,
                self::DOCUMENTS_ARCHIVE,
            ],
            'reviews' => [
                self::REVIEWS_VIEW,
                self::REVIEWS_ASSIGN,
                self::DOCUMENTS_REVIEW,
            ],
            'approvals' => [
                self::APPROVALS_VIEW,
                self::DOCUMENTS_APPROVE,
                self::DOCUMENTS_REJECT,
            ],
            'tasks' => [
                self::TASKS_VIEW,
                self::TASKS_CREATE,
                self::TASKS_UPDATE,
                self::TASKS_COMPLETE,
            ],
            'reports' => [
                self::REPORTS_VIEW,
                self::REPORTS_EXPORT,
                self::ACTIVITY_VIEW,
            ],
            'administration' => [
                self::USERS_MANAGE,
                self::SETTINGS_MANAGE,
                self::DISCIPLINES_MANAGE,
                self::WORKFLOWS_MANAGE,
            ],
        ];
    }

    /** @return list<string> */
    public static function all(): array
    {
        return array_merge(...array_values(self::grouped()));
    }

    /**
     * Default permission set per role. Administrators receive everything via
     * the Gate::before hook in AuthServiceProvider, so they are listed here
     * only for completeness of the admin UI.
     *
     * @return array<string, list<string>>
     */
    public static function forRoles(): array
    {
        $viewer = [
            self::PROJECTS_VIEW,
            self::DOCUMENTS_VIEW,
            self::DOCUMENTS_DOWNLOAD,
            self::REVIEWS_VIEW,
            self::APPROVALS_VIEW,
            self::TASKS_VIEW,
        ];

        $engineer = array_merge($viewer, [
            self::DOCUMENTS_CREATE,
            self::DOCUMENTS_UPDATE,
            self::DOCUMENTS_UPLOAD_REVISION,
            self::DOCUMENTS_SUBMIT_REVIEW,
            self::TASKS_CREATE,
            self::TASKS_UPDATE,
            self::TASKS_COMPLETE,
            self::ACTIVITY_VIEW,
        ]);

        $reviewer = array_merge($viewer, [
            self::DOCUMENTS_REVIEW,
            self::TASKS_CREATE,
            self::TASKS_UPDATE,
            self::TASKS_COMPLETE,
            self::ACTIVITY_VIEW,
        ]);

        $approver = array_merge($viewer, [
            self::DOCUMENTS_APPROVE,
            self::DOCUMENTS_REJECT,
            self::TASKS_COMPLETE,
            self::REPORTS_VIEW,
            self::ACTIVITY_VIEW,
        ]);

        $projectManager = array_merge($viewer, [
            self::PROJECTS_CREATE,
            self::PROJECTS_UPDATE,
            self::DOCUMENTS_CREATE,
            self::DOCUMENTS_UPDATE,
            self::DOCUMENTS_DELETE,
            self::DOCUMENTS_SUBMIT_REVIEW,
            self::DOCUMENTS_ARCHIVE,
            self::REVIEWS_ASSIGN,
            self::DOCUMENTS_APPROVE,
            self::DOCUMENTS_REJECT,
            self::TASKS_CREATE,
            self::TASKS_UPDATE,
            self::TASKS_COMPLETE,
            self::REPORTS_VIEW,
            self::REPORTS_EXPORT,
            self::ACTIVITY_VIEW,
            self::WORKFLOWS_MANAGE,
        ]);

        return [
            UserRole::Administrator->value => self::all(),
            UserRole::ProjectManager->value => array_values(array_unique($projectManager)),
            UserRole::Engineer->value => array_values(array_unique($engineer)),
            UserRole::Reviewer->value => array_values(array_unique($reviewer)),
            UserRole::Approver->value => array_values(array_unique($approver)),
            UserRole::Viewer->value => $viewer,
        ];
    }
}
