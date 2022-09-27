<?php

use App\Http\Controllers\ProcesoController;
use App\Http\Controllers\QuantumController;
use App\Models\Proceso;
use App\Models\Quantum;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('proceso')->with('quantum', Quantum::find(1))->with('procesos', Proceso::all());
});

Route::get('/fcfs', function () {
    $procesos = Proceso::orderBy('id')->get();

    $tiempoEspera = collect([]);
    $tiempoRetorno = collect([]);
    $duracionTE = 0;
    $duracionTR = 0;

    // Recorre cada proceso y extrae la duración de la BD
    foreach ($procesos as $key => $proceso) {
        // Si es el primer proceso se añade un nuevo proceso con tiempo de duración = 0
        if ($key == 0) {
            $tiempoEspera->push([
                'duracion' => $duracionTE,
            ]);
        }

        // Si no es el primer proceso se añade la suma de la duración del proceso anterior con la actual
        $tiempoEspera->push([
            'duracion' => $duracionTE += $proceso->duracion,
        ]);

        $tiempoRetorno->push([
            'duracion' => $duracionTR += $proceso->duracion,
        ]);
    }

    // Se calcula el promedio del TR
    $promedioRetorno = $tiempoRetorno->sum('duracion') / count($tiempoRetorno);

    // Como se añade un proceso con tiempo de duración 0 se debe eliminar el último proceso para que quede la misma cantidad de la BD
    $tiempoEspera = $tiempoEspera->slice(0, -1);

    // Se calcula el promedio de TE
    $promedioEspera = $tiempoEspera->sum('duracion') / count($tiempoEspera);

    return view('fcfs')->with('procesos', $procesos)->with('promedioEspera', $promedioEspera)->with('promedioRetorno', $promedioRetorno);
});

Route::get('/sjf', function () {
    $procesos = Proceso::orderBy('duracion', 'ASC')->get();

    $tiempoEspera = collect([]);
    $tiempoRetorno = collect([]);
    $duracionTE = 0;
    $duracionTR = 0;

    // Recorre cada proceso y extrae la duración de la BD
    foreach ($procesos as $key => $proceso) {
        // Si es el primer proceso se añade un nuevo proceso con tiempo de duración = 0
        if ($key == 0) {
            $tiempoEspera->push([
                'duracion' => $duracionTE,
            ]);
        }

        // Si no es el primer proceso se añade la suma de la duración del proceso anterior con la actual
        $tiempoEspera->push([
            'duracion' => $duracionTE += $proceso->duracion,
        ]);

        $tiempoRetorno->push([
            'duracion' => $duracionTR += $proceso->duracion,
        ]);
    }

    // Se calcula el promedio del TR
    $promedioRetorno = $tiempoRetorno->sum('duracion') / count($tiempoRetorno);

    // Como se añade un proceso con tiempo de duración 0 se debe eliminar el último proceso para que quede la misma cantidad de la BD
    $tiempoEspera = $tiempoEspera->slice(0, -1);

    // Se calcula el promedio de TE
    $promedioEspera = $tiempoEspera->sum('duracion') / count($tiempoEspera);

    return view('sjf')->with('procesos', $procesos)->with('promedioEspera', $promedioEspera)->with('promedioRetorno', $promedioRetorno);
});

Route::get('/prioridad', function () {
    $procesos = Proceso::orderBy('prioridad', 'ASC')->get();

    $tiempoEspera = collect([]);
    $tiempoRetorno = collect([]);
    $duracionTE = 0;
    $duracionTR = 0;

    // Recorre cada proceso y extrae la duración de la BD
    foreach ($procesos as $key => $proceso) {
        // Si es el primer proceso se añade un nuevo proceso con tiempo de duración = 0
        if ($key == 0) {
            $tiempoEspera->push([
                'duracion' => $duracionTE,
            ]);
        }

        // Si no es el primer proceso se añade la suma de la duración del proceso anterior con la actual
        $tiempoEspera->push([
            'duracion' => $duracionTE += $proceso->duracion,
        ]);

        $tiempoRetorno->push([
            'duracion' => $duracionTR += $proceso->duracion,
        ]);
    }

    // Se calcula el promedio del TR
    $promedioRetorno = $tiempoRetorno->sum('duracion') / count($tiempoRetorno);

    // Como se añade un proceso con tiempo de duración 0 se debe eliminar el último proceso para que quede la misma cantidad de procesos guardados en la BD
    $tiempoEspera = $tiempoEspera->slice(0, -1);

    // Se calcula el promedio de TE
    $promedioEspera = $tiempoEspera->sum('duracion') / count($tiempoEspera);

    return view('prioridad')->with('procesos', $procesos)->with('promedioEspera', $promedioEspera)->with('promedioRetorno', $promedioRetorno);
});

Route::get('/rr', function () {
    $procesos = Proceso::orderBy('id')->get();
    $quantum  = Quantum::orderBy('id')->first();

    $procesosFaltantes = [];
    $arregloFinal = collect([]);
    $pos_fin = 0;
    $pos_ini = 0;

    // Recorre cada proceso, los divide según el quantum y nos indica los nuevos procesos faltantes
    foreach ($procesos as $key => $proceso) {
        $restante = $proceso->duracion - $quantum->q;

        // Se calcula el tiempo de llegada de cada proceso
        if ($key == 0) {
            // Si es el primer proceso le asigna el tiempo de llegada = 0
            $pos_ini = 0;
        } else if ($key == 1) {
            // Si es el segundo proceso le asigna el tiempo de llegada = quantum ya definido previamente
            $pos_ini += $quantum->q;
        } else {
            // El resto de procesos se le asigna el tiempo de llegada de acuerdo a: duración_proceso_actual o quantum + duracion_proceso_anterior o quantum
            $pos_ini += $procesos[$key - 1]['duracion'] > $quantum->q ? $quantum->q : $procesos[$key - 1]['duracion'];
        }

        array_push($procesosFaltantes, [
            'id'                => $proceso->id,
            'nombre'            => $proceso->nombre,
            'duracion'          => $proceso->duracion,
            'procesos_restante' => ceil($restante / $quantum->q) > 0 ? ceil($restante / $quantum->q) : 0,
            'tiempo_espera'     => $pos_ini - $key
        ]);
    }

    $faltantes = count($procesosFaltantes);
    $restante = 0;
    // Recorre los procesos faltantes según el quantum de cada proceso y los añade al arregloFinal
    while ($faltantes != 0) {
        foreach ($procesosFaltantes as $key => $procesoFaltante) {
            if ($procesoFaltante['procesos_restante'] >= 0) {
                $arregloFinal->push([
                    'id'                => count($arregloFinal) + 1,
                    'nombre'            => $procesoFaltante['nombre'],
                    'duracion'          => $procesoFaltante['duracion'] > $quantum->q ? $quantum->q : (int) $procesoFaltante['duracion'],
                    'pos_fin'           => $pos_fin += $procesoFaltante['duracion'] > $quantum->q ? $quantum->q : (int) $procesoFaltante['duracion'],
                ]);
            }

            if ($procesoFaltante['procesos_restante'] == 0) {
                $faltantes -= 1;
            }

            $procesosFaltantes[$key]['duracion'] -= $quantum->q;
            $procesosFaltantes[$key]['procesos_restante'] = $procesoFaltante['procesos_restante'] - 1;
        }
    }

    // Se calcula el promedio de TR
    $promedioRetorno = $arregloFinal->sortByDesc('pos_fin')->unique('nombre')->sum('pos_fin') / count($arregloFinal->sortByDesc('pos_fin')->unique('nombre'));

    // Se calcula el promedio de TE
    $promedioEspera = collect($procesosFaltantes)->sum('tiempo_espera') / count($procesosFaltantes);

    return view('rr')->with('procesos', $arregloFinal)->with('promedioEspera', $promedioEspera)->with('promedioRetorno', $promedioRetorno);
});

Route::post('procesos/store', [ProcesoController::class, 'store'])->name('procesos.store');
Route::post('quantum/store', [QuantumController::class, 'store'])->name('quantum.store');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

require __DIR__ . '/auth.php';
