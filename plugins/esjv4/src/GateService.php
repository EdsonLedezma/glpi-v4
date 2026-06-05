<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class GateService
{
    public static function evaluateConstructionRelease(array $context): array
    {
        $statuses = $context['stage_statuses'] ?? [];
        $missing_stages = [];

        foreach (Catalog::constructionReleaseRequiredStages() as $stage_key) {
            if (($statuses[$stage_key] ?? null) !== Catalog::STATUS_CLOSED) {
                $missing_stages[] = $stage_key;
            }
        }

        $blocking_rfi_ids = [];

        foreach (($context['blocking_rfis'] ?? []) as $rfi) {
            $id = (int) ($rfi['id'] ?? 0);
            if ($id > 0) {
                $blocking_rfi_ids[] = $id;
            }
        }

        return [
            'can_release' => $missing_stages === [] && $blocking_rfi_ids === [],
            'missing_stages' => $missing_stages,
            'blocking_rfi_ids' => $blocking_rfi_ids,
        ];
    }
}
