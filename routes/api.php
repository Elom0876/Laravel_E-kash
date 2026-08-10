<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DemandeController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\EmpruntController;
use App\Http\Controllers\CaisseController;
use App\Http\Controllers\Preuve_depenseController;
use App\Http\Controllers\EntrepriseController;
use App\Http\Controllers\UserController;


// Routes publiques (pas besoin d'être connecté)
Route::post('/login', [AuthController::class, 'login']);

// Routes protégées (utilisateur connecté requis)
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/mot-de-passe/oublie', [AuthController::class, 'motDePasseOublie']);
    Route::post('/mot-de-passe/reinitialiser', [AuthController::class, 'reinitialiserMotDePasse']);

    Route::middleware('role:demandeur')->group(function () {
        Route::post('/demandes', [DemandeController::class, 'store']);
        Route::get('/demandes/mes-demandes', [DemandeController::class, 'mesDemandes']);
        Route::post('/demandes/{demande}/preuve', [DemandeController::class, 'soumettrePreuve']);
    });

    Route::middleware('role:gestionnaire')->group(function () {
        Route::get('/demandes/en-attente', [DemandeController::class, 'enAttente']);
        Route::post('/demandes/{demande}/accepter', [DemandeController::class, 'accepter']);
        Route::post('/demandes/{demande}/rejeter', [DemandeController::class, 'rejeter']);

        Route::get('/preuves/en-attente', [Preuve_depenseController::class, 'enAttente']);
        Route::post('/preuves/{preuve}/valider', [Preuve_depenseController::class, 'valider']);
        Route::post('/preuves/{preuve}/rejeter', [Preuve_depenseController::class, 'rejeter']);
        Route::post('/demandes/{demande}/valider-sans-preuve', [DemandeController::class, 'validerSansPreuve']);
        Route::get('/demandes/historique', [DemandeController::class, 'historique']);
    });

    // Réservé au superviseur / direction (+ gestionnaire pour les rapports)
    Route::middleware('role:superviseur,gestionnaire')->group(function () {
        Route::get('/rapports', [RapportController::class, 'index']);
    });
    Route::middleware('role:superviseur,gestionnaire')->group(function () {
        Route::get('/emprunts', [EmpruntController::class, 'index']);
        Route::post('/emprunts', [EmpruntController::class, 'store']);
        Route::post('/emprunts/{emprunt}/rembourser', [EmpruntController::class, 'rembourser']);
        Route::post('/caisses/{caisse}/approvisionner', [CaisseController::class, 'approvisionner']);
    });

    Route::middleware('role:superviseur,gestionnaire')->group(function () {
        Route::get('/caisses', [CaisseController::class, 'index']);
        Route::get('/caisses/{caisse}', [CaisseController::class, 'show']);
        Route::get('/demandes/historique', [DemandeController::class, 'historique']);
    });
    Route::middleware('role:superviseur,gestionnaire')->group(function () {
        Route::get('/rapports', [RapportController::class, 'index']);
        Route::get('/rapports/tableau-de-bord', [RapportController::class, 'tableauDeBord']);
    });
    Route::middleware('role:superviseur,gestionnaire')->group(function () {
        Route::get('/rapports', [RapportController::class, 'index']);
        Route::get('/rapports/tableau-de-bord', [RapportController::class, 'tableauDeBord']);
        Route::get('/rapports/export/pdf', [RapportController::class, 'exporterPdf']);
        Route::get('/rapports/export/excel', [RapportController::class, 'exporterExcel']);
    });
    Route::middleware('role:gestionnaire,superviseur')->group(function () {
        Route::post('/entreprises', [EntrepriseController::class, 'store']);
        Route::post('/caisses', [CaisseController::class, 'store']);
    });
    Route::middleware('role:gestionnaire,superviseur')->group(function () {

        Route::post('/users', [UserController::class, 'store']);
    });
});
