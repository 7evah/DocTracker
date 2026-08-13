<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Support\Facades\DB;

/**
 * Task lifecycle (§27).
 *
 * Thin by design — tasks carry far less workflow than documents — but the
 * transitions still live here so completion always stamps a timestamp and
 * reassignment always notifies (§5).
 */
class TaskService
{
    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes, User $author): Task
    {
        return DB::transaction(function () use ($attributes, $author) {
            $task = Task::create($attributes + ['created_by' => $author->id]);

            activity('task')
                ->performedOn($task)
                ->causedBy($author)
                ->event('created')
                ->withProperties(['title' => $task->title])
                ->log('task.created');

            $this->notifyAssignee($task, $author);

            return $task;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Task $task, array $attributes, User $actor): Task
    {
        return DB::transaction(function () use ($task, $attributes, $actor) {
            $previousAssignee = $task->assigned_to;

            $task->update($attributes);

            activity('task')
                ->performedOn($task)
                ->causedBy($actor)
                ->event('updated')
                ->log('task.updated');

            // Only ping someone when the task actually lands on them.
            if ($task->assigned_to !== $previousAssignee) {
                $this->notifyAssignee($task->fresh(), $actor);
            }

            return $task;
        });
    }

    public function complete(Task $task, User $actor): void
    {
        if ($task->status === TaskStatus::Completed) {
            return;
        }

        $task->complete();

        activity('task')
            ->performedOn($task)
            ->causedBy($actor)
            ->event('completed')
            ->log('task.completed');
    }

    /** Reopening clears the completion stamp so it never lies about history. */
    public function reopen(Task $task, User $actor): void
    {
        $task->forceFill([
            'status' => TaskStatus::Open,
            'completed_at' => null,
        ])->save();

        activity('task')
            ->performedOn($task)
            ->causedBy($actor)
            ->event('reopened')
            ->log('task.reopened');
    }

    public function cancel(Task $task, User $actor): void
    {
        $task->forceFill([
            'status' => TaskStatus::Cancelled,
            'completed_at' => null,
        ])->save();

        activity('task')
            ->performedOn($task)
            ->causedBy($actor)
            ->event('cancelled')
            ->log('task.cancelled');
    }

    private function notifyAssignee(Task $task, User $actor): void
    {
        $assignee = $task->assignee;

        // Assigning yourself something needs no notification.
        if ($assignee && $assignee->isNot($actor)) {
            $assignee->notify(new TaskAssigned($task));
        }
    }
}
