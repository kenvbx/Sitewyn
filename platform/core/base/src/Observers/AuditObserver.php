<?php

namespace Sitewyn\Core\Base\Observers;

use Illuminate\Database\Eloquent\Model;
use Sitewyn\Core\Base\Support\AuditLogger;

class AuditObserver
{
    public function __construct(
        private readonly AuditLogger $logger,
    ) {}

    public function created(Model $model): void
    {
        $this->logger->record('created', $model->getMorphClass(), $this->subjectId($model), [
            'attributes' => $model->withoutRelations()->getAttributes(),
        ]);
    }

    public function updated(Model $model): void
    {
        $this->logger->record('updated', $model->getMorphClass(), $this->subjectId($model), [
            'changes' => [
                'id' => $model->getKey(),
                ...$this->changedAttributes($model),
            ],
        ]);
    }

    public function deleted(Model $model): void
    {
        $this->logger->record('deleted', $model->getMorphClass(), $this->subjectId($model), [
            'attributes' => $model->withoutRelations()->getAttributes(),
        ]);
    }

    private function subjectId(Model $model): ?int
    {
        return $model->getKey() === null ? null : (int) $model->getKey();
    }

    /**
     * Only the fields that actually changed, without timestamp churn that
     * every save produces anyway.
     *
     * @return array<string, mixed>
     */
    private function changedAttributes(Model $model): array
    {
        return collect($model->withoutRelations()->getChanges())
            ->except(['created_at', 'updated_at'])
            ->all();
    }
}
