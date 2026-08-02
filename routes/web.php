<?php

use App\Http\Controllers\CartaoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CompartilhamentoController;
use App\Http\Controllers\ConfiguracaoController;
use App\Http\Controllers\ContaPagarController;
use App\Http\Controllers\LancamentoController;
use App\Http\Controllers\PendenciaController;
use App\Http\Controllers\RelatorioController;
use App\Livewire\Dashboard;
use App\Livewire\LancamentoForm;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/dashboard'));

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::resource('categorias', CategoriaController::class)->except(['create', 'show', 'edit']);
    Route::post('/categorias/{categoria}/subcategorias', [CategoriaController::class, 'storeSubcategoria'])->name('categorias.subcategorias.store');
    Route::put('/subcategorias/{subcategoria}', [CategoriaController::class, 'updateSubcategoria'])->name('subcategorias.update');
    Route::delete('/subcategorias/{subcategoria}', [CategoriaController::class, 'destroySubcategoria'])->name('subcategorias.destroy');

    Route::resource('cartoes', CartaoController::class)->except(['create', 'show', 'edit'])->parameters(['cartoes' => 'cartao']);

    Route::get('/lancamentos/subcategorias', [LancamentoController::class, 'subcategorias'])->name('lancamentos.subcategorias');
    Route::get('/lancamentos/create', LancamentoForm::class)->name('lancamentos.create');
    Route::post('/lancamentos', [LancamentoController::class, 'store'])->name('lancamentos.store');
    Route::get('/lancamentos/{lancamento}/edit', LancamentoForm::class)->name('lancamentos.edit');
    Route::put('/lancamentos/{lancamento}', [LancamentoController::class, 'update'])->name('lancamentos.update');
    Route::delete('/lancamentos/{lancamento}', [LancamentoController::class, 'destroy'])->name('lancamentos.destroy');
    Route::get('/lancamentos', [LancamentoController::class, 'index'])->name('lancamentos.index');

    Route::get('/pendencias', [PendenciaController::class, 'index'])->name('pendencias.index');
    Route::post('/pendencias/{lancamento}/pagar', [PendenciaController::class, 'pagar'])->name('pendencias.pagar');
    Route::post('/pendencias/{lancamento}/desfazer', [PendenciaController::class, 'desfazerPagamento'])->name('pendencias.desfazer');
    Route::post('/pendencias/{lancamento}/abate', [PendenciaController::class, 'alternarAbate'])->name('pendencias.abate');
    Route::post('/pendencias/{cartao}/fatura', [PendenciaController::class, 'pagarFatura'])->name('pendencias.fatura');

    Route::get('/relatorios/mensal', [RelatorioController::class, 'mensal'])->name('relatorios.mensal');
    Route::get('/relatorios/pdf', [RelatorioController::class, 'exportarPdf'])->name('relatorios.pdf');

    Route::resource('contas-pagar', ContaPagarController::class)->except(['create', 'show', 'edit']);
    Route::post('/contas-pagar/{conta}/pagar', [ContaPagarController::class, 'pagar'])->name('contas-pagar.pagar');

    Route::resource('compartilhamentos', CompartilhamentoController::class)->except(['create', 'show', 'edit']);
    Route::get('/compartilhados', [CompartilhamentoController::class, 'visao'])->name('compartilhamentos.visao');
    Route::get('/compartilhamentos/{compartilhamento}/ver', [CompartilhamentoController::class, 'ver'])->name('compartilhamentos.ver');

    Route::get('/configuracoes', [ConfiguracaoController::class, 'index'])->name('configuracoes.index');
    Route::put('/configuracoes', [ConfiguracaoController::class, 'update'])->name('configuracoes.update');
});
