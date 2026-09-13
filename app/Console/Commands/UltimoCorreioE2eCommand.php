<?php

namespace App\Console\Commands;

use App\Support\IsolamentoE2e;
use Illuminate\Console\Command;

class UltimoCorreioE2eCommand extends Command
{
    protected $signature = 'e2e:ultimo-correio';

    protected $description = 'Mostra o conteúdo capturado do correio local do ambiente E2E.';

    public function handle(): int
    {
        IsolamentoE2e::garantir();

        $caminho = IsolamentoE2e::caminhoCorreio();

        if (! is_file($caminho)) {
            $this->warn('Nenhuma mensagem capturada.');

            return self::SUCCESS;
        }

        $this->output->write((string) file_get_contents($caminho));

        return self::SUCCESS;
    }
}
