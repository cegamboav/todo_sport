# Todo Sport v2 — Aplicación Laravel

Implementación real del torneo local-first. **Slice S0 (fundación)** completado.

Documentación de diseño (congelada): carpeta raíz `../` — `CAPA1_USUARIOS_ROLES.md`, `PATRON_*`.

Patrón de código: `../PATRON_ARQUITECTURA_IMPLEMENTACION_V1.md`.

---

## Requisitos

- PHP 8.2+ (extensiones: `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, **`zip`** recomendado para Composer)
- Composer 2.x
- Node.js 20+ y npm
- SQLite (dev) o MySQL/MariaDB (producción LAN)

---

## Instalación (primera vez)

```powershell
cd D:\Software\todo_sport_new\todo_sport

# PHP (ejemplo XAMPP)
$php = "D:\xampp\php\php.exe"

# Dependencias PHP
& $php composer.phar install
# o: composer install

copy .env.example .env
& $php artisan key:generate
New-Item -ItemType File -Force database\database.sqlite

& $php artisan migrate --seed

npm install
npm run build
```

### Desarrollo

```powershell
# Terminal 1
& $php artisan serve

# Terminal 2
npm run dev
```

Abrir: http://127.0.0.1:8000/login

---

## Usuarios seed (auth alineado Capa 1)

| Usuario | Contraseña | Rol | Login | Acceso |
|---------|------------|-----|-------|--------|
| `admin` | `admin123` | admin | `/login` | Panel admin completo |
| `laura` | `laura2026` | staff | `/login` | Admin solo con `event_staff` en evento **open** |
| `mesa.demo` | `mesa2026` | mesa | `/login` | Rings solo con `event_staff` en evento **open** |
| `corner.demo` | `corner2026` | corner | `/judge/login` | Judge app solo con `event_staff` en evento **open** |
| `carlos` | `carlos2026` | professor | `/school/login` | Portal profesores (sin admin) |

Tras `migrate --seed`: evento demo **Torneo Demo S1** (open) con `laura`, `mesa.demo` y `corner.demo` asignados.

---

## Slice S0 — qué incluye

### Backend (`app/`)

| Carpeta | Contenido |
|---------|-----------|
| `Enums/` | `UserRole`, `UserStatus`, `LicenseStatus`, `AuditSeverity`, sesiones operativas |
| `Services/License/` | `LicenseService`, `FeatureGate` (dev_mode + import placeholder) |
| `Services/Audit/` | `AuditService` + tabla `audit_events` |
| `Services/OperationalSession/` | Sesiones, heartbeat, 409 conflict |
| `Policies/` | `EventPolicy`, `RingPolicy`, `CompetitorPolicy` (base) |
| `Actions/` | Patrón Actions (ejemplo) |
| `DTOs/` | `LicenseState` |

### Frontend (`resources/js/`)

Cuatro apps Inertia + Vite:

| Entry | Rutas | Layout |
|-------|-------|--------|
| `app.ts` | `/`, `/dashboard`, `/admin/*` | `AdministrativeLayout` |
| `app-rings.ts` | `/rings` | `RingOperationalLayout` |
| `app-judge.ts` | `/judge` | `JudgeLayout` |
| `app-professor.ts` | `/school` | `ProfessorLayout` |

Aliases: `@`, `@shared`, `@layouts`, `@domains`.

### Transversal

- Auth por **username** (Capa 1)
- Middleware `active`, `license` (placeholder S0)
- Licencia: `LICENSE_DEV_MODE=true` en `.env` → operación sin archivo firmado
- Panel: `/admin/license`

---

## Slice S2A — Event Core + Registration (implementado)

Rutas bajo `/events/*` (admin + staff asignado):

| Área | Ruta |
|------|------|
| Eventos | `/events`, `/events/{id}` |
| Catálogo modalidades (global) | `/config/modalities` |
| Event Workspace | `/events/{id}` (resumen), `/events/{id}/participants`, … |
| Event Operations (placeholder) | `/events/{id}/operations` |

**Filosofía:** catálogo global (`modalities`) sin precios → precios en `event_modalities` / `event_combos` → participación en `event_competitors` → cobros en `registration_items` (monto copiado manual, sin motor de precios).

**Lifecycle:** `draft` → `registration_open` → `registration_closed` → `operational` → `finished` → `archived`

**Auth:** sin cambios S1 — staff/mesa/corner requieren `event_staff`; mesa/corner solo en `operational`; staff también en fases de inscripción.

---

## Slice S1 — Maestros (implementado)

Rutas bajo `/masters/*` (admin + staff con `event_staff` en evento abierto):

| Módulo | Ruta |
|--------|------|
| Escuelas | `/masters/schools` |
| Profesores | `/masters/professors` |
| Competidores | `/masters/competitors` |
| Árbitros | `/masters/referees` |

**Flujo recomendado:** Profesores → Escuelas (director) → Competidores.

Datos demo tras `migrate --seed`: escuela DEMO, 2 competidores, 1 árbitro.

**Auth S1:** identidad (`users`) separada de operación (`event_staff`). Staff global permanente eliminado del panel; ver `CAPA1_USUARIOS_ROLES.md`.

---

## Qué NO está en S0

Brackets, propagación, WebSocket, categorías completas, caja, merges, scoring — ver `../PATRON_IMPLEMENTACION_SLICES.md` (S1+).

---

## Estructura de commits sugerida

Un slice = migraciones + servicios + páginas mínimas + README de slice.

---

## Licencia del producto

Ver `../PATRON_LICENCIAMIENTO.md`. En dev: `LICENSE_DEV_MODE=true`.

---

*Todo Sport v2 — base S0.*
