<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class SapIntakeService
{
    public function __construct(private object $repository, private ?SapMailParser $parser = null)
    {
        $this->parser ??= new SapMailParser();
    }

    public function receiveMail(string $content): array
    {
        $parsed = $this->parser->parse($content);

        if ($parsed === null) {
            return [
                'ok' => false,
                'created' => false,
                'message' => 'El correo no tiene estructura SAP reconocible.',
            ];
        }

        $existing = $this->repository->findProjectByRawHash((string) $parsed['raw_hash']);
        if ($existing === []) {
            $existing = $this->repository->findProjectByCode((string) $parsed['project_code']);
        }

        if ($existing !== []) {
            return [
                'ok' => true,
                'created' => false,
                'project_id' => (int) $existing['id'],
                'message' => 'Proyecto SAP ya estaba en bandeja.',
            ];
        }

        $project_id = $this->repository->createProjectFromSap([
            'project_code' => $parsed['project_code'],
            'project_name' => $parsed['project_name'],
            'customer_name' => $parsed['customer_name'],
            'quotation_code' => $parsed['quotation_code'],
            'location' => $parsed['location'],
            'raw_hash' => $parsed['raw_hash'],
            'raw' => $parsed['raw'],
            'status' => Catalog::STATUS_PENDING_PLANNING,
        ]);

        $this->repository->recordEvent([
            'project_id' => $project_id,
            'event_type' => EventLog::SAP_MAIL_RECEIVED,
            'event_at' => date('Y-m-d H:i:s'),
            'payload' => [
                'project_code' => $parsed['project_code'],
                'quotation_code' => $parsed['quotation_code'],
            ],
        ]);

        return [
            'ok' => true,
            'created' => true,
            'project_id' => $project_id,
            'message' => 'Proyecto SAP agregado a Planeacion.',
        ];
    }
}
