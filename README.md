# 🚀 Guías de Prácticas Laravel - TaskBoard: Pasarela de Pagos

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Git](https://img.shields.io/badge/Git-F05032?style=for-the-badge&logo=git&logoColor=white)
![GitHub](https://img.shields.io/badge/GitHub-181717?style=for-the-badge&logo=github&logoColor=white)

---

<p align="center">
  <img src="https://res.cloudinary.com/bcwlyire/image/upload/v1785118882/NUEVO-LOGO-UPED-A-COLOR-scaled_szoqws.jpg" alt="Logo UPED" width="300"/>
</p>

## 🏛️ Información Académica

- **Institución:** Universidad Pedagógica de El Salvador *Luis Alonso Aparicio*
- **Facultad:** Ingeniería
- **Asignatura:** Integración de Sistemas
- **Ciclo:** 02-2026
- **Docente:** Ing. Oscar Contreras
- **Proyecto Integrador:** TaskBoard - Pasarela de Pagos

---

## 📌 Descripción del Proyecto

Este repositorio contiene el trabajo de la **Semana 18** del proyecto **TaskBoard**, una plataforma de **Pasarela de Pagos** desarrollada con el framework **Laravel 12**.

Esta semana no agrega pantallas: **cambia la estructura del código sin cambiar su comportamiento**. Guía N.° 20 — Arquitectura y buenas prácticas:

1. **Red de seguridad primero:** pruebas de PHPUnit del endpoint de crear transacción y del cambio de estado, escritas **antes** de mover el código y que siguen en verde después.
2. **Capa de servicios:** la lógica del negocio sale de los controladores y pasa a `TransaccionService` (registrar, actualizar y cambiar estado). Los controladores solo coordinan: reciben, llaman al servicio y responden. La web y la API comparten el mismo servicio.
3. **Principio abierto/cerrado (SOLID) en las comisiones:** cada método de pago es una clase que cumple el contrato `ReglaComision`. La billetera digital se agregó sin modificar ninguna regla existente.
4. **Enum para los estados:** `EstadoTransaccion` reemplaza los textos escritos a mano y concentra la regla de qué transiciones valen.

Cada cambio queda registrado en [`CAMBIOS.md`](CAMBIOS.md).

---

## 🧱 Capas del proyecto

| Capa | Responsabilidad | Archivo |
|---|---|---|
| Ruta | Qué URL y verbo atiende quién | `routes/api.php`, `routes/web.php` |
| Form Request | ¿Los datos son válidos? | `GuardarTransaccionRequest`, `CambiarEstadoRequest` |
| Controlador | Coordina: recibe, llama al servicio, responde | `TransaccionApiController`, `TransaccionController` |
| Servicio | Reglas del negocio | `TransaccionService`, `CalculadoraComision` |
| Modelo | Datos, relaciones y casts | `Transaccion` (con el cast a `EstadoTransaccion`) |
| Resource | Cómo sale el JSON | `TransaccionResource` |

**Comisiones:** tarjeta 3.5 % · transferencia 1.50 fija · billetera 2 %.
Para agregar un método de pago nuevo: se crea una clase en `app/Services/Comisiones/` que implemente `ReglaComision` y se registra en `AppServiceProvider`.

**Pruebas:** `php artisan test` → 18 pruebas en verde.

---

## 🔌 API — Filtro de transacciones

`GET /api/transacciones/filtrar`

Devuelve las transacciones de los comercios **del usuario autenticado**, con filtros opcionales.

**Autenticación:** obligatoria. Encabezado `Authorization: Bearer <token>` (Sanctum).
Se recomienda enviar también `Accept: application/json`.

| Parámetro | Tipo | Obligatorio | Valores válidos |
|---|---|---|---|
| `estado` | texto | No | `Iniciada`, `Completada` o `Fallida` (distingue mayúsculas) |
| `desde` | fecha | No | `AAAA-MM-DD` |
| `hasta` | fecha | No | `AAAA-MM-DD`; debe ser igual o posterior a `desde`. Incluye las transacciones de ese mismo día |
| `monto_min` | número | No | Mayor o igual a 0 |
| `monto_max` | número | No | Mayor o igual a 0; si también viene `monto_min`, debe ser mayor o igual que él |

Sin ningún parámetro devuelve todas las transacciones del usuario.

| Código | Cuándo |
|---|---|
| `200` | Correcto. Devuelve `{"data": [...]}`, que puede venir vacío si nada coincide |
| `401` | Falta el token, o está vencido o revocado |
| `422` | Algún parámetro es inválido. El cuerpo trae `errors` con el campo y el motivo |

**Ejemplo**

```
GET /api/transacciones/filtrar?estado=Completada&monto_min=20
Accept: application/json
Authorization: Bearer 1|xxxxxxxx
```

```json
{
  "data": [
    {
      "id": 1,
      "comercio_id": 1,
      "cliente": "María López",
      "monto": 45,
      "estado": "Completada",
      "fecha": "2026-11-11T20:14:03+00:00"
    }
  ]
}
```

---

## 📁 Estructura del Proyecto

A continuación se detalla la estructura principal del repositorio y del proyecto Laravel `taskboard`:

```text
taskboard/
├── app/
│   ├── Enums/
│   │   └── EstadoTransaccion.php                    # Estados y transiciones permitidas
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthApiController.php            # Login, /yo, logout y tokens
│   │   │   │   └── TransaccionApiController.php     # Delgado: delega en TransaccionService
│   │   │   ├── AuthController.php                   # Registro, login y logout web
│   │   │   ├── ComercioController.php               # Web: index filtrado y show protegido
│   │   │   └── TransaccionController.php            # Web: CRUD y moverEstado()
│   │   ├── Requests/
│   │   │   ├── RegistroRequest.php                  # Validación del registro
│   │   │   ├── CambiarEstadoRequest.php             # Valida el estado (Rule::enum)
│   │   │   └── GuardarTransaccionRequest.php        # Incluye el método de pago
│   │   └── Resources/                               # ComercioResource y TransaccionResource
│   ├── Providers/
│   │   └── AppServiceProvider.php                   # Registra las reglas de comisión
│   ├── Services/
│   │   ├── TransaccionService.php                   # Registrar, actualizar y cambiar estado
│   │   ├── CalculadoraComision.php                  # Elige la regla según el método
│   │   └── Comisiones/                              # ReglaComision, Tarjeta, Transferencia, Billetera
│   └── Models/
│       ├── User.php                                 # HasApiTokens y hasMany(Comercio)
│       ├── Comercio.php                             # user_id y belongsTo(User)
│       ├── Transaccion.php                          # hasMany(EventoTransaccion)
│       └── EventoTransaccion.php                    # Bitácora de cada transacción
├── bootstrap/
│   └── app.php                                      # withExceptions: JSON en /api y 500 genérico
├── config/
│   └── sanctum.php                                  # Expiración del token a 24 horas
├── database/
│   ├── migrations/                                  # Incluye la tabla de tokens de Sanctum
│   └── seeders/
│       └── ComercioSeeder.php                       # Ana (2 comercios) y Carlos (1)
├── public/
│   ├── cliente.html                                 # Cliente web que consume la API con fetch()
│   └── index.php                                    # Punto de entrada de la aplicación
├── resources/
│   └── views/                                       # Vistas Blade de la aplicación web
├── routes/
│   ├── api.php                                      # Login público y el resto con auth:sanctum
│   └── web.php                                      # Grupos guest y auth de la aplicación web
├── tests/
│   └── Feature/
│       ├── CrearTransaccionTest.php                 # Regresión de E-01, E-02 y E-03
│       ├── RegistrarTransaccionTest.php             # Red de seguridad del store
│       └── CambiarEstadoTest.php                    # Red de seguridad del cambio de estado
├── storage/                                         # Registros (logs), archivos y caché
├── vendor/                                          # Dependencias administradas por Composer
├── CAMBIOS.md                                       # Registro de cambios del proyecto
├── pruebas-api.http                                 # Colección de pruebas de la API
├── .env.example                                     # Variables de entorno de ejemplo
├── composer.json                                    # Configuración de Composer y paquetes
└── README.md                                        # Documentación principal del repositorio
```
