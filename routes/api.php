<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DemandeController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\EmpruntController;

// Routes publiques (pas besoin d'être connecté)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Routes protégées (utilisateur connecté requis)
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Réservé aux demandeurs (commerciaux, techniciens)
    Route::middleware('role:demandeur')->group(function () {
        Route::post('/demandes', [DemandeController::class, 'store']);
    });

    // Réservé au gestionnaire (assistante de direction)
    Route::middleware('role:gestionnaire')->group(function () {
        Route::post('/demandes/{id}/valider', [DemandeController::class, 'valider']);
        Route::post('/emprunts', [EmpruntController::class, 'store']);
    });

    // Réservé au superviseur / direction (+ gestionnaire pour les rapports)
    Route::middleware('role:superviseur,gestionnaire')->group(function () {
        Route::get('/rapports', [RapportController::class, 'index']);
    });
});
