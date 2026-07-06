# 🏘️ Arryanaes — Control de Pagos del Fraccionamiento

Sistema web PHP + MySQL para gestionar pagos mensuales, estatus de tags y
control de morosos del fraccionamiento Arryanaes.

---

## Requisitos

- PHP 8.0+ con extensiones: `pdo_mysql`, `session`
- MySQL 5.7+ o MariaDB 10.4+
- Servidor web: Apache o Nginx (o `php -S` para pruebas)

---

## Instalación paso a paso

### 1. Crear la base de datos

```bash
mysql -u root -p < sql/schema.sql
```

Esto crea la BD `arryanaes` con todas las tablas, vistas e índices.

### 2. Configurar la conexión

Editar `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'arryanaes');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

### 3. Importar datos del Excel

```bash
pip install pandas openpyxl mysql-connector-python
python3 import/importar.py \
    --host localhost \
    --user root \
    --password TU_PASSWORD \
    --file "Validación_Tags.xlsx"
```

### 4. Configurar el servidor web

**Con Apache** — agrega en `.htaccess` o `VirtualHost`:

```apache
DocumentRoot /ruta/a/arryanaes
DirectoryIndex index.php

<Directory /ruta/a/arryanaes>
    AllowOverride All
    Require all granted
</Directory>
```

**Para pruebas rápidas:**

```bash
cd arryanaes
php -S localhost:8080
```

Luego abre: http://localhost:8080

### 5. Cambiar contraseñas

Los usuarios por defecto tienen contraseña `password`. Cámbiala en MySQL:

```sql
USE arryanaes;
UPDATE usuarios_sistema SET password_hash = '$2y$10$...' WHERE username = 'admin';
```

O desde PHP:
```php
echo password_hash('tu_nueva_contraseña', PASSWORD_DEFAULT);
```

---

## Usuarios del sistema

| Usuario   | Rol      | Puede hacer                             |
|-----------|----------|-----------------------------------------|
| `admin`   | Admin    | Registrar pagos, marcar morosos, todo   |
| `consulta`| Consulta | Solo ver, buscar y filtrar              |

---

## Estructura del proyecto

```
arryanaes/
├── index.php              ← Login
├── includes/
│   ├── config.php         ← BD y constantes
│   └── auth.php           ← Sesiones y roles
├── public/
│   ├── dashboard.php      ← Panel principal
│   └── api.php            ← API REST (AJAX)
├── sql/
│   ├── schema.sql         ← Estructura de la BD
│   └── import_data.sql    ← SQL generado del Excel (opcional)
└── import/
    └── importar.py        ← Script de importación recomendado
```

---

## Funcionalidades

### Dashboard
- Estadísticas en tiempo real: total residentes, morosos, activos, pagos del mes
- Tabla de residentes con paginación (25 por página)
- Búsqueda por nombre, calle, número de tag o estatus

### Gestión de pagos (admin)
- Registrar pago mensual de un residente
- Selección de mes, año, monto, método y referencia
- Al registrar pago → tags cambian automáticamente a ACTIVO
- Historial completo de pagos por residente

### Control de morosos
- **Proceso automático de morosos**: marca como MOROSO a todos los que no pagaron el mes anterior
- **Marcado manual**: admin puede marcar un residente específico como moroso
- Lógica de reactivación: si era moroso y paga → vuelve a ACTIVO automáticamente

### Estatus de tags
- ACTIVO: pagó el mes en curso
- MOROSO: no pagó el mes anterior
- Historial de cambios de estatus con fecha y usuario responsable

---

## Notas técnicas

- La API usa PDO con prepared statements (protección SQL injection)
- Sesiones con timeout de 1 hora
- Roles verificados en cada endpoint
- Vista MySQL `vista_residentes` para consultas optimizadas
