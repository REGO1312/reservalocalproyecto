# Reserva Local ✂️

Sistema web completo para descubrir y agendar citas en tiempo real en barberías y salones de belleza locales. Desarrollado con PHP nativo (sin frameworks ni Composer), MySQL InnoDB (con control de concurrencia y transacciones ACID) y Frontend en HTML5, CSS3 y JavaScript puro.

---

## 1. Requisitos del Sistema
- **PHP 8.0 o superior** con extensiones `pdo`, `pdo_mysql`, `openssl`, `mbstring`.
- **MySQL 5.7+ o MariaDB 10.3+** (motor InnoDB requerido para soporte transaccional y bloqueos de fila).
- Servidor web **Apache (XAMPP)** o el **servidor integrado de PHP**.

---

## 2. Instalación en 3 Pasos

### Opción A: Con Servidor Integrado de PHP (Recomendada para pruebas rápidas)
1. **Configurar base de datos (si difiere de XAMPP):**
   El archivo `app/config/config.php` se conecta por defecto a `127.0.0.1`, puerto `3307`, usuario `root` y sin contraseña. Puedes sobreescribirlos con variables de entorno (`DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME`).
2. **Ejecutar el instalador automático:**
   ```bash
   php install.php
   ```
   *(Crea la base `reserva_local`, importa el esquema `database/schema.sql` y los datos de prueba `database/datos_demo.sql`)*.
3. **Arrancar el servidor:**
   ```bash
   php -S localhost:8000 -t public
   ```
   Abre en tu navegador: [http://localhost:8000](http://localhost:8000)

### Opción B: Con XAMPP (Apache + MySQL)
1. Coloca la carpeta en `C:\xampp\htdocs\reserva-local`.
2. Inicia los servicios de Apache y MySQL en el Panel de Control de XAMPP.
3. Abre en tu navegador `http://localhost/reserva-local/install.php` (o ejecuta `php install.php` en consola) y luego ingresa a `http://localhost/reserva-local/public/`.

---

## 3. Cuentas de Demostración

> **Contraseña universal para todas las cuentas de prueba:** `Demo123!`

| Rol | Correo Electrónico | Acceso / Funcionalidad |
| :--- | :--- | :--- |
| **Administrador Global** | `admin@reservalocal.test` | Gestión de cuentas, establecimientos, aprobación y auditoría. |
| **Propietario 1** | `propietario1@reservalocal.test` | Dueño de *Barbería Clásica El Caballero* y *Vintage Blade Studio*. Calendario, servicios, staff, horarios y reportes. |
| **Propietario 2** | `propietario2@reservalocal.test` | Dueña de *Salón & Spa Bella Donna*. Agenda completa de estética y manicura. |
| **Profesional / Barbero** | `carlos@reservalocal.test` | Barbero especialista en El Caballero. Agenda personal y control de citas. |
| **Profesional / Estilista**| `sofia@reservalocal.test` | Estilista en Bella Donna. Agenda personal y atención. |
| **Cliente de Prueba 1** | `cliente1@reservalocal.test` | Alejandro Morales (cuenta cliente con citas previas). |
| **Cliente de Prueba 2** | `cliente2@reservalocal.test` | Camila Torres (cuenta cliente con citas previas). |

---

## 4. Programación de Recordatorios en Windows (Task Scheduler)

El script `scripts/recordatorios.php` despacha las notificaciones pendientes y recordatorios a 24 horas y 2 horas.

### Pasos para configurar en el Programador de tareas de Windows:
1. Abre **Inicio** y escribe **Programador de tareas** (`taskschd.msc`).
2. En el panel derecho haz clic en **Crear tarea básica...**.
3. **Nombre:** `ReservaLocal-Recordatorios`.
4. **Desencadenador:** Diariamente o Al iniciar (repetir cada 5 o 10 minutos).
5. **Acción:** Iniciar un programa.
   - **Programa o script:** `C:\xampp\php\php.exe`
   - **Argumentos:** `C:\xampp\htdocs\reserva-local\scripts\recordatorios.php`
   - **Iniciar en:** `C:\xampp\htdocs\reserva-local`
6. Guarda la tarea.

*(En Linux / Cron basta agregar: `*/5 * * * * /usr/bin/php /var/www/reserva-local/scripts/recordatorios.php > /dev/null 2>&1`)*.

---

## 5. Nota de Despliegue en Producción (HTTPS y GPS)
- **Geolocalización GPS (`navigator.geolocation`):** Los navegadores modernos (Chrome, Firefox, Safari, Edge) restringen el acceso al GPS exclusivamente a contextos seguros (`localhost` o dominios con certificado `HTTPS`). Para que la detección de distancia por GPS funcione en un servidor en línea, es indispensable contar con certificado SSL/TLS (ej. Let's Encrypt).
- **Alternativa manual:** Si el usuario no tiene GPS o deniega el permiso, el sistema incluye una alternativa manual por colonia, municipio o zona (ej. Roma, Condesa, Del Valle), ejecutando igualmente la búsqueda en MySQL.

---

## 6. Decisiones de Diseño y Arquitectura Documentadas
1. **Prevención de Solapamientos Estricta:** Implementada a través de `SELECT ... FOR UPDATE` sobre la fila del profesional dentro de una transacción InnoDB. Dos peticiones simultáneas para el mismo profesional son serializadas; la condición de conflicto es `inicio_existente < fin_solicitado AND fin_existente > inicio_solicitado`. Las citas en estado `cancelada` no bloquean el turno.
2. **Fórmula Haversine en MySQL:** Se calcula la distancia en kilómetros directamente en SQL usando el radio terrestre (6371 km) y funciones trigonométricas con `LEAST(1.0, GREATEST(-1.0, ...))` para prevenir desbordes de redondeo en `ACOS()`.
3. **Desacoplamiento de Notificaciones:** Por defecto, el adaptador activo es `LogNotificationSender`, el cual registra los mensajes en `storage/logs/recordatorios.log`. Para envío real de correos se activa configurando `MAIL_DRIVER=email` y las variables SMTP correspondientes.
4. **Seguridad y Sesiones:** Sesiones protegidas con directivas `HttpOnly` y `SameSite=Lax`. La bandera `Secure` se activa dinámicamente si la conexión entrante es HTTPS, garantizando compatibilidad con entornos locales HTTP sin comprometer producción.
5. **Precios y Duración Congelados:** Al momento de reservar, el precio y duración del servicio se guardan de forma inmutable en el registro de la cita, protegiendo al cliente ante posteriores aumentos de tarifas.
