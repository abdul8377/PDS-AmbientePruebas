<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────────────────────────────────────
// CONTROLADORES
// ─────────────────────────────────────────────────────────────────────────────

// Auth & Users
use App\Http\Controllers\Api\Login\AuthController;
use App\Http\Controllers\Api\User\UserController;

// Lookups & Universidad
use App\Http\Controllers\Api\Lookup\LookupController;
use App\Http\Controllers\Api\Universidad\UniversidadController;

// Académico
use App\Http\Controllers\Api\Academico\SedeController;
use App\Http\Controllers\Api\Academico\FacultadController;
use App\Http\Controllers\Api\Academico\EscuelaProfesionalController;
use App\Http\Controllers\Api\Academico\EpSedeController;
use App\Http\Controllers\Api\Academico\ResponsableEpController;
use App\Http\Controllers\Api\Academico\ExpedienteController;

// VM (Virtual Manager)
use App\Http\Controllers\Api\Vm\ProyectoController;
use App\Http\Controllers\Api\Vm\ProyectoProcesoController;
use App\Http\Controllers\Api\Vm\ProcesoSesionController;
use App\Http\Controllers\Api\Vm\EditarProyectoController;
use App\Http\Controllers\Api\Vm\InscripcionProyectoController;
use App\Http\Controllers\Api\Vm\ProyectoImagenController;
use App\Http\Controllers\Api\Vm\EventoController;
use App\Http\Controllers\Api\Vm\AgendaController;
use App\Http\Controllers\Api\Vm\AsistenciasController;
use App\Http\Controllers\Api\Vm\EventoImagenController;

// ─────────────────────────────────────────────────────────────────────────────
// AUTENTICACIÓN Y USUARIOS
// ─────────────────────────────────────────────────────────────────────────────

Route::middleware('auth:sanctum')->get('/user', fn (Request $r) => $r->user());

Route::prefix('auth')->group(function () {
    Route::post('/lookup', [AuthController::class, 'lookup']);
    Route::post('/login',  [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum'])->prefix('users')->group(function () {
    Route::get('/me', [UserController::class, 'me']);
    Route::get('/by-username/{username}', [UserController::class, 'showByUsername']);
});

// ─────────────────────────────────────────────────────────────────────────────
// UNIVERSIDAD
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/universidad', [UniversidadController::class, 'show']);
    Route::put('/universidad', [UniversidadController::class, 'update']);
    Route::post('/universidad/logo', [UniversidadController::class, 'setLogo']);
    Route::post('/universidad/portada', [UniversidadController::class, 'setPortada']);
});

// ─────────────────────────────────────────────────────────────────────────────
// ACADÉMICO
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum'])->prefix('academico')->group(function () {
    Route::post('/sedes', [SedeController::class, 'store']);
    Route::post('/facultades', [FacultadController::class, 'store']);
    Route::post('/escuelas', [EscuelaProfesionalController::class, 'store']);
    Route::post('/ep-sede', [EpSedeController::class, 'store']);
    Route::delete('/ep-sede/{id}', [EpSedeController::class, 'destroy']);
    Route::post('/expedientes', [ExpedienteController::class, 'store']);
    Route::post('/ep-sede/{epSede}/coordinador', [ResponsableEpController::class, 'setCoordinador']);
    Route::post('/ep-sede/{epSede}/encargado',   [ResponsableEpController::class, 'setEncargado']);
});

// ─────────────────────────────────────────────────────────────────────────────
// LOOKUPS
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum'])->prefix('lookups')->group(function () {
    Route::get('/ep-sedes',  [LookupController::class, 'epSedes']);   // ?q=...&limit=...
    Route::get('/periodos',  [LookupController::class, 'periodos']);  // ?q=...&solo_activos=1
});

// ─────────────────────────────────────────────────────────────────────────────
// VM (Virtual Manager)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * 1️⃣ RUTAS PARA ALUMNO
 */
Route::middleware(['auth:sanctum'])->prefix('vm')->group(function () {
    // Listado de proyectos del alumno
    Route::get('/proyectos/alumno', [ProyectoController::class, 'indexAlumno'])
        ->name('vm.proyectos.index-alumno');

    // Inscripción
    Route::post('/proyectos/{proyecto}/inscribirse', [InscripcionProyectoController::class, 'inscribirProyecto'])
        ->whereNumber('proyecto')
        ->name('vm.proyectos.inscribirse');

    // Agenda del alumno
    Route::get('/alumno/agenda', [AgendaController::class, 'agendaAlumno'])
        ->name('vm.alumno.agenda');

    // Check-in por QR
    Route::post('/sesiones/{sesion}/check-in/qr', [AsistenciasController::class, 'checkInPorQr'])
        ->whereNumber('sesion')
        ->name('vm.sesiones.checkin-qr');

    // Detalle de proyecto alumno
    Route::get('/alumno/proyectos/{proyecto}', [ProyectoController::class, 'show'])
        ->whereNumber('proyecto')
        ->name('vm.alumno.proyectos.show');
});

/**
 * 2️⃣ RUTAS DE GESTIÓN (COORDINADOR / ENCARGADO)
 */
Route::middleware(['auth:sanctum','role:COORDINADOR|ENCARGADO'])->prefix('vm')->group(function () {

    // ─────────────────────────────────────────────────────────────
    // PROYECTOS
    // ─────────────────────────────────────────────────────────────
    Route::get   ('/proyectos/niveles-disponibles', [ProyectoController::class, 'nivelesDisponibles'])
        ->name('vm.proyectos.niveles-disponibles');

    Route::get   ('/proyectos',                 [ProyectoController::class, 'index']);
    Route::post  ('/proyectos',                 [ProyectoController::class, 'store']);
    Route::get   ('/proyectos/{proyecto}',      [EditarProyectoController::class, 'show'])->whereNumber('proyecto');
    Route::get   ('/proyectos/{proyecto}/edit', [EditarProyectoController::class, 'show'])->whereNumber('proyecto');
    Route::put   ('/proyectos/{proyecto}',      [EditarProyectoController::class, 'update'])->whereNumber('proyecto');
    Route::delete('/proyectos/{proyecto}',      [EditarProyectoController::class, 'destroy'])->whereNumber('proyecto');
    Route::put   ('/proyectos/{proyecto}/publicar', [ProyectoController::class, 'publicar'])->whereNumber('proyecto');

    // Inscripciones
    Route::get('/proyectos/{proyecto}/inscritos',  [InscripcionProyectoController::class, 'listarInscritos'])
        ->whereNumber('proyecto');
    Route::get('/proyectos/{proyecto}/candidatos', [InscripcionProyectoController::class, 'listarCandidatos'])
        ->whereNumber('proyecto');

    // Imágenes
    Route::get   ('/proyectos/{proyecto}/imagenes',          [ProyectoImagenController::class, 'index'])->whereNumber('proyecto');
    Route::post  ('/proyectos/{proyecto}/imagenes',          [ProyectoImagenController::class, 'store'])->whereNumber('proyecto');
    Route::delete('/proyectos/{proyecto}/imagenes/{imagen}', [ProyectoImagenController::class, 'destroy'])
        ->whereNumber('proyecto')->whereNumber('imagen');

    // Procesos y sesiones
    Route::post  ('/proyectos/{proyecto}/procesos', [ProyectoProcesoController::class, 'store'])->whereNumber('proyecto');
    Route::post  ('/procesos/{proceso}/sesiones/batch', [ProcesoSesionController::class, 'storeBatch'])->whereNumber('proceso');

    // ─────────────────────────────────────────────────────────────
    // EVENTOS (Gestión coordinador / encargado)
    // ─────────────────────────────────────────────────────────────
    Route::get   ('/eventos', [EventoController::class, 'index'])->name('vm.eventos.index');
    Route::get   ('/eventos/{evento}', [EventoController::class, 'show'])->whereNumber('evento')->name('vm.eventos.show');
    Route::post  ('/eventos', [EventoController::class, 'store'])->name('vm.eventos.store');
    Route::put   ('/eventos/{evento}', [EventoController::class, 'update'])->whereNumber('evento')->name('vm.eventos.update');
    // Imágenes de eventos
    Route::get   ('/eventos/{evento}/imagenes',          [EventoImagenController::class, 'index'])
        ->whereNumber('evento');
    Route::post  ('/eventos/{evento}/imagenes',          [EventoImagenController::class, 'store'])
        ->whereNumber('evento');
    Route::delete('/eventos/{evento}/imagenes/{imagen}', [EventoImagenController::class, 'destroy'])
        ->whereNumber('evento')->whereNumber('imagen');


    // ─────────────────────────────────────────────────────────────
    // AGENDA STAFF
    // ─────────────────────────────────────────────────────────────
    Route::get('/staff/agenda', [AgendaController::class, 'agendaStaff'])
        ->name('vm.staff.agenda');

    // ─────────────────────────────────────────────────────────────
    // ASISTENCIAS
    // ─────────────────────────────────────────────────────────────
    Route::post('/sesiones/{sesion}/qr', [AsistenciasController::class, 'generarQr'])
        ->whereNumber('sesion')->name('vm.sesiones.abrir-qr');

    Route::post('/sesiones/{sesion}/activar-manual', [AsistenciasController::class, 'activarManual'])
        ->whereNumber('sesion')->name('vm.sesiones.activar-manual');

    Route::post('/sesiones/{sesion}/check-in/manual', [AsistenciasController::class, 'checkInManual'])
        ->whereNumber('sesion')->name('vm.sesiones.checkin-manual');

    Route::get('/sesiones/{sesion}/participantes', [AsistenciasController::class, 'participantes'])
        ->whereNumber('sesion')->name('vm.sesiones.participantes');

    Route::post('/sesiones/{sesion}/asistencias/justificar', [AsistenciasController::class, 'checkInFueraDeHora'])
        ->whereNumber('sesion')->name('vm.sesiones.asistencias.justificar');

    Route::get('/sesiones/{sesion}/asistencias', [AsistenciasController::class, 'listarAsistencias'])
        ->whereNumber('sesion')->name('vm.sesiones.asistencias');

    Route::get('/sesiones/{sesion}/asistencias/reporte', [AsistenciasController::class, 'reporte'])
        ->whereNumber('sesion')->name('vm.sesiones.asistencias.reporte');

    Route::post('/sesiones/{sesion}/validar', [AsistenciasController::class, 'validarAsistencias'])
        ->whereNumber('sesion')->name('vm.sesiones.validar');
});
