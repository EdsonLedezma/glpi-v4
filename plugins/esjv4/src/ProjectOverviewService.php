<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class ProjectOverviewService
{
    public function __construct(private object $repository)
    {
    }

    public function overview(int $project_id): array
    {
        $phases = $this->repository->phases($project_id);
        $activities = $this->repository->activities($project_id);

        return [
            'project' => $this->repository->project($project_id),
            'buildings' => method_exists($this->repository, 'buildings') ? $this->repository->buildings($project_id) : [],
            'stages' => $this->repository->stages($project_id),
            'phases' => $phases,
            'activities' => $activities,
            'phase_counts' => $this->statusCounts($phases),
            'activity_counts' => $this->statusCounts($activities),
            'phase_progress_percent' => $this->progressPercent($phases),
            'activity_progress_percent' => $this->progressPercent($activities),
            'gate' => GateService::evaluateConstructionRelease(
                $this->repository->constructionReleaseContext($project_id)
            ),
        ];
    }

    private function statusCounts(array $rows): array
    {
        $counts = [
            'active' => 0,
            'blocked' => 0,
            'closed' => 0,
            'paused' => 0,
            'other' => 0,
        ];

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');

            if ($status === Catalog::STATUS_ACTIVE || $status === Catalog::STATUS_IN_PROGRESS) {
                $counts['active']++;
            } elseif ($status === Catalog::STATUS_BLOCKED) {
                $counts['blocked']++;
            } elseif ($status === Catalog::STATUS_CLOSED || $status === 'done') {
                $counts['closed']++;
            } elseif ($status === Catalog::STATUS_PAUSED_RFI || $status === Catalog::STATUS_PAUSED_RESTRICTION) {
                $counts['paused']++;
            } else {
                $counts['other']++;
            }
        }

        return $counts;
    }

    private function progressPercent(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        $closed = 0;

        foreach ($rows as $row) {
            if (in_array((string) ($row['status'] ?? ''), [Catalog::STATUS_CLOSED, 'done'], true)) {
                $closed++;
            }
        }

        return (int) floor(($closed / count($rows)) * 100);
    }
}
