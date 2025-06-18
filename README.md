# CRM Odontológico - TFG Judit Morel

Este repositorio contiene el código fuente del sistema CRM para la gestión clínica y comercial de clínicas dentales, desarrollado como Trabajo Fin de Grado (TFG) en Ingeniería Informática en la UNIR.

## Estructura del proyecto

- **assets/**: Recursos estáticos (CSS, JS, imágenes)
- **cita/**: Gestión de agendas y citas
- **dashboard/**: Panel administrativo, KPIs
- **documentos/**: Gestión documental y consentimientos
- **IA/**: Módulo de integración con IA (requiere variable de entorno)
- **paciente/**: Gestión de pacientes e historiales
- **presupuesto/**: Gestión de presupuestos y tratamientos
- **usuario/**: Gestión de usuarios y perfiles
- **estructura.sql**: Esquema de la base de datos (estructura sin datos reales)

## Instalación

1. Clonar el repositorio
2. Configurar entorno PHP/MySQL/Docker según necesidades
3. Importar la base de datos usando `estructura.sql`
4. Crear el archivo `.env` (no incluido) para las variables sensibles
5. Acceder mediante `index.php`
