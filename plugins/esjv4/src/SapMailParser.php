<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class SapMailParser
{
    private const LABELS = [
        'project_name' => ['nombre'],
        'sales_area' => ['area de ventas', 'área de ventas'],
        'project_number' => ['no.', 'no', 'numero', 'número'],
        'quotation_code' => ['cotizacion', 'cotización'],
        'ship_to' => ['destinatario de mercancias', 'destinatario de mercancías'],
        'customer_name' => ['cliente'],
        'address' => ['direccion', 'dirección'],
        'location' => ['ubicacion', 'ubicación'],
        'quote_contact' => ['contacto de la cotizacion', 'contacto de la cotización'],
    ];

    public function parse(string $content): ?array
    {
        $plain = $this->plainText($content);
        $fields = $this->fields($plain);
        $observations = $this->sectionAfterLabel($plain, 'observaciones');
        $project_code = $this->projectCode($observations, $fields);

        if ($project_code === '') {
            return null;
        }

        return [
            'project_code' => $project_code,
            'project_name' => $fields['project_name'] ?? $this->projectNameFromObservations($observations, $project_code),
            'quotation_code' => $fields['quotation_code'] ?? '',
            'customer_name' => $fields['customer_name'] ?? '',
            'location' => $fields['location'] ?? '',
            'ship_to' => $fields['ship_to'] ?? '',
            'supply_date' => $this->sectionAfterLabel($plain, 'fecha de suministro'),
            'contact' => $this->sectionAfterLabel($plain, 'contacto'),
            'sales_area' => $fields['sales_area'] ?? '',
            'project_number' => $fields['project_number'] ?? '',
            'address' => $fields['address'] ?? '',
            'quote_contact' => $fields['quote_contact'] ?? '',
            'raw' => $plain,
            'raw_hash' => hash('sha256', $plain),
        ];
    }

    private function plainText(string $content): string
    {
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = preg_replace('/<br\s*\/?>/i', "\n", $content) ?? $content;
        $content = strip_tags($content);
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = preg_replace("/[ \t]+/", ' ', $content) ?? $content;
        $content = preg_replace("/\n{3,}/", "\n\n", $content) ?? $content;

        return trim($content);
    }

    private function fields(string $plain): array
    {
        $fields = [];

        foreach (explode("\n", $plain) as $line) {
            if (!preg_match('/^\s*([^:]{2,90})\s*:\s*(.*?)\s*$/u', $line, $matches)) {
                continue;
            }

            $key = $this->canonicalLabel($matches[1]);
            $value = trim($matches[2]);

            if ($key !== null && $value !== '') {
                $fields[$key] = $value;
            }
        }

        return $fields;
    }

    private function canonicalLabel(string $label): ?string
    {
        $normalized = $this->normalize($label);

        foreach (self::LABELS as $key => $labels) {
            foreach ($labels as $candidate) {
                if ($normalized === $this->normalize($candidate)) {
                    return $key;
                }
            }
        }

        return null;
    }

    private function sectionAfterLabel(string $plain, string $label): string
    {
        $lines = explode("\n", $plain);
        $capture = false;
        $values = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                if ($capture && $values !== []) {
                    break;
                }
                continue;
            }

            if ($capture && preg_match('/^[A-ZÁÉÍÓÚÑ ]{3,}:$/u', $trimmed)) {
                break;
            }

            if ($this->normalize(rtrim($trimmed, ':')) === $this->normalize($label)) {
                $capture = true;
                continue;
            }

            if ($capture) {
                $values[] = $trimmed;
            }
        }

        return trim(implode("\n", $values));
    }

    private function projectCode(string $observations, array $fields): string
    {
        if (preg_match('/\b([A-Z]{2,5}\d{3,8})\b/u', $observations, $matches)) {
            return $matches[1];
        }

        foreach (['project_number', 'quotation_code'] as $field) {
            $value = (string) ($fields[$field] ?? '');
            if (preg_match('/\b([A-Z]{2,5}\d{3,8})\b/u', $value, $matches)) {
                return $matches[1];
            }
        }

        return '';
    }

    private function projectNameFromObservations(string $observations, string $project_code): string
    {
        $name = trim(str_replace($project_code, '', $observations));

        return $name !== '' ? $name : $project_code;
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = strtr($value, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
        ]);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
