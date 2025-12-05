<?php

use Illuminate\Support\Facades\Route;

// Route::middleware(['auth.genuka'])->get('/', function () {
//     $company = request()->attributes->get('genuka_company');

//     return view('welcome', ['company' => $company]);
// });

// Route::get('/{any}', function () {
//     return file_get_contents(public_path('index.html'));
// })->where('any', '.*');

// 1. Pour la route racine
// Route::get('/', function () {
//     return view('welcome'); // Laravel sert index.html, qui est le frontend Angular
// });

// 2. Le 'catch-all' pour les routes internes d'Angular (Deep-linking)
// Cela assure que si l'utilisateur va sur /produit/1, c'est Angular qui prend le relais.
Route::middleware(['auth.genuka'])->any('{any}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '^(?!api\/).*$'); // Regex pour ignorer toute URL qui commence par 'api/'
