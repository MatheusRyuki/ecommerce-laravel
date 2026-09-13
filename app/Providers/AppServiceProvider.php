<?php

namespace App\Providers;

use App\Cart\CarrinhoSessao;
use App\Models\Usuario;
use App\Services\AtualizadorProduto;
use App\Services\CriadorProduto;
use App\Services\ExcluirProduto;
use App\Support\DiscoArquivosProduto;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->when([CriadorProduto::class, AtualizadorProduto::class, ExcluirProduto::class])
            ->needs(Filesystem::class)
            ->give(fn (): Filesystem => DiscoArquivosProduto::disco());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('e2e')) {
            $raiz = storage_path('e2e');
            config([
                'session.files' => $raiz.'/framework/sessions',
                'cache.stores.file.path' => $raiz.'/framework/cache/data',
                'cache.stores.file.lock_path' => $raiz.'/framework/cache/data',
                'view.compiled' => $raiz.'/framework/views',
                'logging.channels.correio_e2e.path' => $raiz.'/correio/mensagens.log',
            ]);
        }

        Gate::define('acessar-admin', function (Usuario $usuario): bool {
            return $usuario->administrador === true;
        });

        View::composer('loja.partials.header', function (\Illuminate\View\View $view): void {
            $view->with('quantidadeCarrinho', app(CarrinhoSessao::class)->quantidadeTotal());
        });
    }
}
