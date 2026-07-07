<?php

use Illuminate\Support\Facades\Route;

// A SPA (Vue) é servida por uma única view blade.
// Qualquer rota que não seja /api ou /up cai na SPA, que cuida do roteamento
// no cliente (mapa em "/" e área da equipe em "/admin").
Route::view('/{any?}', 'app')
    ->where('any', '^(?!api|up).*$');
