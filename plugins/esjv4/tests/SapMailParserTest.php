<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\SapMailParser;

esjv4_assert_true(class_exists(SapMailParser::class), 'SapMailParser class must exist');

$mail = <<<MAIL
OBSERVACIONES:
EAA2506 MBA SAN RAFAEL

FECHA DE SUMINISTRO:
PENDIENTE POR CONFIRMAR.

CONTACTO:
+52 1 33 2254 6822
'Ing Antonio Zuloaga' <antonio.zuloaga1@gmail.com>

NOTIFICACION DE PROYECTO:

Por: JESUS RENE CARRILLO BARRAZA

Nombre: MBA SAN RAFAEL
Área de Ventas: 400A/IT/ID - Construcción, INDUSTRIA TRANSFORMA, INDUSTRIAS DIVERSAS
No.: MBA SAN RAFAEL
Cotización: 0020033984
Destinatario de Mercancías: 0030013268 MBA SAN RAFAEL
Cliente: 5000004221 PUNTO ESTRUCTURAL
Dirección: AV. HISTORIADORES S/N, CALLE MANUEL MARIA PONCE
Ubicación: GUADALAJARA, Jalisco, México
Contacto de la Cotización:

8 JOISTS con 1124 kg
1 Flete Extra Extra Largo P.UNIT $24,200.00 Pesos - Centro: 4012
MAIL;

$parsed = (new SapMailParser())->parse($mail);

esjv4_assert_true($parsed !== null, 'SAP mail must parse');
esjv4_assert_same('EAA2506', $parsed['project_code'], 'Project code comes from observations token');
esjv4_assert_same('MBA SAN RAFAEL', $parsed['project_name'], 'Project name comes from Nombre');
esjv4_assert_same('0020033984', $parsed['quotation_code'], 'Quotation is parsed');
esjv4_assert_same('5000004221 PUNTO ESTRUCTURAL', $parsed['customer_name'], 'Customer is parsed');
esjv4_assert_same('GUADALAJARA, Jalisco, México', $parsed['location'], 'Location is parsed');
esjv4_assert_same('0030013268 MBA SAN RAFAEL', $parsed['ship_to'], 'Ship-to is parsed');
esjv4_assert_same('PENDIENTE POR CONFIRMAR.', $parsed['supply_date'], 'Supply date is parsed');
esjv4_assert_true(str_contains($parsed['raw'], '8 JOISTS con 1124 kg'), 'Raw mail is preserved');
esjv4_assert_true(strlen($parsed['raw_hash']) === 64, 'Raw hash is sha256');

$empty = (new SapMailParser())->parse('correo sin estructura');
esjv4_assert_same(null, $empty, 'Unstructured mail does not parse as SAP project');
