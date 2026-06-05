# ESJ V4 Plugin Design

## Objetivo

Crear un plugin nuevo para GLPI 11 que convierta GLPI en un sistema operativo de proyectos de construccion para ESJ. El sistema debe ser facil de usar para ingenieria, automatizar la creacion de proyectos desde correos SAP, medir tiempos 24/7 por eventos, controlar fases y RFIs, y mostrar vistas diferenciadas para Planeacion, PM, lideres e ingenieros.

El plugin se construira desde cero en `plugins/esjv4`. No se reutilizara el plugin anterior.

## Principios

- La experiencia diaria debe hablar en terminos ESJ, no en terminos genericos de GLPI.
- El trabajo tecnico comun se gestiona con tareas de proyecto.
- RFIs, restricciones, submittals y requisiciones se gestionan como tickets.
- Los usuarios no deben iniciar o detener timers manualmente.
- Todo tiempo se calcula por eventos automaticos en tiempo calendario 24/7.
- La fase es el eje principal de medicion operativa.
- Nave/edificio se conserva como dimension de trazabilidad para cliente y reportes.
- El sistema debe permitir plantillas, pero tambien cambios durante la marcha.
- Un RFI abierto bloqueante debe impedir el cierre del alcance afectado.

## Modulos GLPI Usados

### Proyectos

Cada proyecto SAP se crea como un `Project` GLPI. El proyecto se agrupa dentro de un portafolio ESJ y contiene la estructura de fases y tareas.

### Tareas De Proyecto

Las actividades tecnicas comunes se crean como `ProjectTask`. Estas tareas representan trabajo operativo de ingenieria: planeacion, diseno estructural, asignacion de equipo, modelo de conexiones, planos, cierre y actividades agregadas en marcha.

### Tickets

Los tickets se usan solo para elementos que requieren trazabilidad tipo solicitud, bloqueo o comunicacion:

- RFI
- Restriccion
- Submittal
- Requisicion
- Solicitud inicial SAP
- Incidencia o aclaracion relevante

### Grupos Y Usuarios

Los grupos y perfiles GLPI se ajustan a roles ESJ:

- Planeacion
- PM
- Cliente / Proyectos
- Coordinador de Ingenieria
- Lider de Ingenieria
- Modelador
- Detallista
- Capturista
- Admin ESJ

### Documentos

Los documentos GLPI se usan para adjuntar o ligar entregables: planos, PDFs firmados, requisiciones, archivos de respuesta RFI y evidencia de cierre.

## Jerarquia ESJ

La jerarquia funcional del plugin sera:

```text
Portafolio
  Proyecto
    Fase
      Nave / Edificio
        Etapa
          Actividad / Tarea
            Producto / Entregable
```

La fase es obligatoria y prioritaria. Nave/edificio es una dimension asociada a la fase, no sustituye la medicion por fase.

Ejemplo:

```text
Proyecto EAA260102
  Fase 01
    Edificio A1
      Modelo de conexiones
        Actividad: Modelar columnas
          Producto: Columnas
        Actividad: Modelar vigas
          Producto: Vigas
```

## Plantillas

El sistema tendra plantillas configurables para evitar captura repetitiva.

### Plantilla De Proyecto

Define el maximo de fases sugeridas para el proyecto. Por ejemplo, 10, 15 o 20 fases disponibles.

Planeacion decide cuales fases aplican. Las fases no aplicables quedan inactivas.

### Plantillas De Actividades

Cada fase puede recibir actividades preestablecidas. Planeacion decide si una fase hereda una plantilla o si arranca vacia.

Tareas core disponibles:

- Planeacion
- Diseno estructural
- Asignacion de equipo
- Modelo de conexiones
- Planos de fabricacion
- Planos de montaje
- Submittals
- Requisiciones
- Cierre de ingenieria

### Actividades En Marcha

Lideres, PM o usuarios con permiso pueden agregar actividades despues de que la fase ya este activa. Las actividades agregadas tambien generan eventos y entran a medicion.

## Flujo SAP

El correo SAP dispara la creacion del proyecto. El parser debe reconocer el formato real del correo observado:

- Observaciones
- Fecha de suministro
- Contacto
- Nombre
- Area de ventas
- Numero o codigo de proyecto
- Cotizacion
- Destinatario de mercancias
- Cliente
- Direccion
- Ubicacion
- Contacto de cotizacion
- Partidas, cantidades, descripcion, peso, centro y precio cuando existan

Si el correo no trae fases suficientes, el proyecto entra a Planeacion para que el equipo defina fases, naves, fechas y plantillas.

La deduplicacion se hara con una combinacion configurable, inicialmente:

- numero de proyecto SAP si existe
- cotizacion
- destinatario de mercancias
- hash del correo normalizado

## Gate De Liberacion

Las fases de construccion o ejecucion paralela no se activan al terminar solo Planeacion.

El gate principal se llama `Liberacion de construccion` y requiere cerrar estas tres fases principales:

1. Planeacion
2. Diseno estructural
3. Modelado de conexiones

La liberacion ocurre cuando se entregan planos finales y completos de modelado de conexiones y se cierran las tres fases principales.

Al cumplirse el gate:

- Se activan las fases definidas por Planeacion.
- Se crean las actividades precargadas elegidas para cada fase.
- Las fases quedan disponibles para trabajo paralelo.
- Se registra un evento de liberacion con fecha/hora 24/7.

Si el gate no esta cumplido:

- Las fases posteriores son visibles como planeadas o bloqueadas.
- No pueden cerrarse.
- No deben aparecer como trabajo activo para ingenieros.

## Estados

### Estado De Fase

- Planeada
- Bloqueada
- Activa
- En proceso
- Pausada por RFI
- Pausada por restriccion
- Lista para cierre
- Cerrada

### Estado De Tarea

- Planeada
- Bloqueada
- Sin asignar
- Asignada
- En proceso
- Pausada por RFI
- Pausada por restriccion
- Terminada
- Cerrada

### Estado De Ticket ESJ

- Abierto
- En revision
- Esperando respuesta
- Respondido
- Resuelto
- Cerrado

## RFI

Un RFI es un ticket con metadatos ESJ. Al crearlo, el usuario define alcance:

- Proyecto completo
- Fase
- Nave / Edificio
- Actividad / Tarea
- Producto / Entregable

Tambien define impacto:

- Informativo
- Bloqueante

El valor por defecto es `Bloqueante`.

### Reglas De Bloqueo

Un RFI bloqueante abierto impide cerrar el alcance afectado.

Si afecta una tarea, impide cerrar esa tarea.

Si afecta una fase, impide cerrar esa fase.

Si afecta todo el proyecto, impide cerrar el proyecto y puede bloquear gates configurados.

Un RFI informativo no bloquea cierre, pero cuenta para estadisticas y trazabilidad.

### Pausa Por RFI

Cuando una tarea entra en pausa por RFI:

- Se registra evento de pausa.
- El timer operativo de la tarea se pausa logicamente.
- El timer del RFI corre 24/7 hasta respuesta o cierre.
- Al resolver el RFI, la tarea puede reanudarse y se registra evento.

## Medicion De Tiempos

No habra botones manuales de iniciar o finalizar timer.

Todos los tiempos se calculan con eventos automaticos derivados de acciones reales:

- proyecto creado
- fase definida
- fase activada
- tarea creada
- tarea asignada
- tarea puesta en proceso
- tarea pausada por RFI
- tarea pausada por restriccion
- RFI abierto
- RFI respondido
- RFI cerrado
- tarea reanudada
- tarea terminada
- tarea cerrada
- fase cerrada
- proyecto cerrado
- gate liberado

Los calculos usan tiempo calendario 24/7.

### Tiempos Calculados

- duracion total de Planeacion
- duracion de Diseno estructural
- duracion de Modelado de conexiones
- tiempo hasta liberacion de construccion
- tiempo de fase
- tiempo de tarea
- tiempo de espera por asignacion
- tiempo operativo asignado
- tiempo pausado por RFI
- tiempo pausado por restriccion
- tiempo de respuesta RFI
- tiempo total de proyecto
- tiempo por fase, nave, etapa, actividad y producto

## Carga De Trabajo

La regla operativa es que un ingeniero trabaja activamente en una sola tarea la mayor parte del tiempo.

El sistema no bloqueara estrictamente una segunda asignacion, porque existen excepciones.

Si un usuario tiene mas de una tarea activa, el tablero debe mostrarlo como advertencia.

La vista de lider debe mostrar:

- ingenieros disponibles
- ingenieros ocupados
- tarea activa actual
- tareas pausadas
- tareas sin asignar
- sobreasignaciones
- excepciones justificadas

## Vistas

### Vista PM

Vista macro sin tickets detallados por defecto.

Debe mostrar:

- avance global de ingenieria
- avance por fase
- estado del gate de liberacion
- RFIs abiertos
- restricciones abiertas
- fechas comprometidas
- semaforo de riesgo
- porcentaje general
- fases activas, bloqueadas y cerradas

### Vista Lider De Ingenieria

Vista de control operativo.

Debe mostrar:

- cola de tareas sin asignar
- carga de trabajo por ingeniero
- tareas en proceso
- tareas pausadas por RFI
- tareas listas para cierre
- fases activas
- advertencias por sobreasignacion

### Vista Ingeniero

Vista simple y enfocada.

Debe mostrar:

- mi tarea activa
- mis tareas pausadas
- RFIs relacionados
- entregables requeridos
- boton para crear RFI
- boton para adjuntar entrega
- accion para marcar tarea terminada

No debe mostrar menus GLPI innecesarios.

### Vista Planeacion

Debe mostrar:

- proyectos nuevos desde SAP
- datos capturados del correo
- definicion de fases
- asignacion de naves/edificios
- seleccion de plantillas por fase
- captura de fechas de inicio y termino limite
- estado del gate principal

## Fechas Desde Gantt O PDF

Como las fechas por fase llegan por Gantt o PDF por correo, la primera version debe permitir captura manual asistida:

- fase
- nave/edificio
- fecha inicio programada
- fecha termino limite
- observaciones
- archivo fuente asociado

La version futura puede incluir parser de PDF o importador de tabla si el formato se estabiliza.

## Apagado De Funcionalidades GLPI

El plugin debe reducir la exposicion de GLPI usando perfiles, derechos y menu.

Usuarios operativos no deben ver:

- inventario
- activos
- problemas
- cambios
- contratos
- compras
- modulos de administracion
- configuraciones genericas

Admin ESJ mantiene acceso a:

- usuarios
- grupos
- perfiles
- plugin
- correo SAP
- categorias
- documentos

## Datos Propios Del Plugin

El plugin necesitara tablas propias para:

- proyectos SAP normalizados
- fases ESJ
- naves/edificios
- etapas
- actividades ESJ
- productos/entregables
- plantillas de fases
- plantillas de actividades
- asignacion de plantillas a fases
- gates
- eventos de tiempo
- vinculos de tickets ESJ
- estados calculados
- captura de fechas programadas

Los IDs GLPI se guardan como referencias, no como sustituto del modelo ESJ.

## Primer Alcance Implementable

La primera entrega util debe construir:

1. Plugin base `esjv4`.
2. Tablas de modelo ESJ.
3. Menu ESJ minimo.
4. Creacion manual de proyecto ESJ desde pantalla del plugin.
5. Definicion de fases.
6. Seleccion de plantillas de actividades por fase.
7. Gate de liberacion basado en Planeacion, Diseno estructural y Modelado de conexiones.
8. Registro de eventos 24/7.
9. RFI bloqueante ligado a fase o tarea.
10. Vista simple de PM, lider e ingeniero.

La ingesta SAP automatica puede entrar despues de validar que la estructura operativa funciona bien.

## Riesgos Y Decisiones

### Riesgo: GLPI Puede Ser Demasiado Generico

Mitigacion: ocultar GLPI operativo y presentar vistas ESJ propias.

### Riesgo: Demasiada Captura Para Ingenieria

Mitigacion: plantillas, eventos automaticos y pantallas enfocadas por rol.

### Riesgo: Fase Y Nave Compiten Como Eje

Decision: fase es el eje principal; nave/edificio es dimension de reporte.

### Riesgo: Timers Manuales Fallan

Decision: no usar timers manuales; todo se deriva de eventos.

### Riesgo: RFI Informativo Vs Bloqueante

Decision: todo RFI nace bloqueante por defecto, pero puede marcarse informativo si no detiene cierre.

## Criterios De Exito

- Planeacion puede crear un proyecto y configurar fases sin captura repetitiva.
- Las fases posteriores permanecen bloqueadas hasta cerrar las tres fases principales.
- Al liberar el gate, las fases se activan en paralelo con actividades precargadas.
- Un ingeniero puede ver su trabajo sin navegar GLPI generico.
- Un lider puede ver disponibilidad y carga de trabajo sin abrir cada ticket.
- Un PM puede ver avance macro sin tickets detallados.
- Un RFI bloqueante abierto impide cerrar el alcance afectado.
- El dashboard puede calcular tiempos 24/7 desde eventos, sin botones de timer.
