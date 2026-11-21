# Registro de cambios — TaskBoard API

Formato: se separa lo **Agregado** (evolutivo), lo **Cambiado** (refactorización
o ajuste de algo existente) y lo **Corregido** (correctivo).
Se marca con ⚠️ todo cambio que pueda afectar a un cliente que ya usa la API.

---

## 2026-11-20 — Semana 18: capa de servicios

### Cambiado

- **`store` de la API y de la web pasan por `TransaccionService::registrar()`.**
  El controlador de la API bajó de 31 a 6 líneas: ya no calcula la comisión ni
  toca la base de datos. La web y la API comparten ahora la misma lógica, así que
  el formulario web también guarda la comisión y el evento de creación (antes no
  registraba el evento).
- **`update` de la API y de la web pasan por `TransaccionService::actualizar()`,**
  que vuelve a calcular la comisión cuando cambian el monto o el método.
- **Comisiones con el principio abierto/cerrado.** El `if` por método de pago se
  reemplazó por reglas (`ReglaComision`) que `CalculadoraComision` recorre. Las
  reglas se registran en `AppServiceProvider`.
- **Estados con un Enum (`EstadoTransaccion`).** `'Iniciada'`, `'Completada'` y
  `'Fallida'` ya no están escritos a mano fuera del Enum. La regla de qué
  transiciones valen vive en `EstadoTransaccion::puedePasarA()` y la usan la web
  y la API.
- La prueba de fábrica `ExampleTest` esperaba 200 en `/`; desde la Semana 12 la
  web exige sesión, así que ahora verifica la redirección al login.

**Sin cambios para el cliente:** las 18 pruebas automáticas pasan antes y después
de cada paso del refactor, y los casos de las Semanas 15 y 16 responden igual.

### Agregado

- ⚠️ **Método de pago y comisión.** Las transacciones guardan `metodo` (`tarjeta`,
  `transferencia` o `billetera`) y `comision`, calculada por el servidor:
  tarjeta 3.5 %, transferencia 1.50 fija, billetera 2 %.
  **Impacto:** `metodo` es **obligatorio** al crear y al editar (`POST` y `PUT`).
  Un cliente que no lo envíe recibirá 422. Por el semáforo de la Semana 16, volver
  obligatorio un parámetro es un cambio que **rompe**: hay que avisar a la tienda
  en línea y actualizar las colecciones de prueba.
  La respuesta JSON incluye los campos nuevos `metodo` y `comision` (cambio seguro).
- **`PATCH /api/transacciones/{id}/estado`** para cambiar el estado desde la API:
  422 si la transición no está permitida, 404 si la transacción es de otro usuario.
- **Billetera digital (2 %)**, agregada sin modificar `ComisionTarjeta`,
  `ComisionTransferencia` ni `CalculadoraComision`.

---

## 2026-11-11 — Semana 16: mantenimiento

### Corregido

- ⚠️ **MT-013**: `GET /api/transacciones` responde **422** si `?estado` no es
  `Iniciada`, `Completada` o `Fallida` (antes: 200 con la lista vacía, que hacía
  creer que no había cobros).

### Agregado

- **MT-014**: `GET /api/transacciones/filtrar` acepta `monto_min` y `monto_max`,
  ambos opcionales. Cambio seguro: quien no los mande recibe lo mismo de siempre.
- **Filtro de transacciones**: `GET /api/transacciones/filtrar` con `estado`,
  `desde` y `hasta`, solo con las transacciones del usuario del token.
