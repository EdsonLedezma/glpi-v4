<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class SapMailSample
{
    public static function mbaSanRafael(): string
    {
        return <<<MAIL
OBSERVACIONES:
EAA2506 MBA SAN RAFAEL

FECHA DE SUMINISTRO:
PENDIENTE POR CONFIRMAR.

CONTACTO:
+52 1 33 2254 6822
'Ing Antonio Zuloaga' <antonio.zuloaga1@gmail.com>

NOTIFICACIÓN DE PROYECTO:

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
    }
}
