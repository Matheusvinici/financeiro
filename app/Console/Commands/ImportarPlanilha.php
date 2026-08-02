<?php

namespace App\Console\Commands;

use App\Models\Cartao;
use App\Models\Categoria;
use App\Models\ContaPagar;
use App\Models\Lancamento;
use App\Models\Subcategoria;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportarPlanilha extends Command
{
    protected $signature = 'importar:planilha
        {--user= : ID ou e-mail do usuário (padrão: primeiro usuário)}
        {--anos=* : Anos a importar, ex. --anos=2025 --anos=2026 (padrão: todos)}
        {--arquivo=database/contas.xlsx : Caminho da planilha}
        {--base : Importa apenas categorias e cartões, sem lançamentos nem contas a pagar}
        {--limpar : Apaga os dados financeiros do usuário antes de importar}';

    protected $description = 'Importa o arquivo contas.xlsx (2021-2027) para o sistema financeiro (--base = só categorias e cartões)';

    private const CORES = [
        'RECEITAS' => '#198754',
        'CARRO' => '#0d6efd',
        'CARTÕES' => '#dc3545',
        'CASA' => '#ffc107',
        'TERRENOS' => '#fd7e14',
        'POUPANÇA' => '#0dcaf0',
        'LAZER' => '#6f42c1',
        'SAÚDE' => '#20c997',
        'ESTUDOS' => '#e83e8c',
        'GASTOS ROTATIVOS' => '#6c757d',
        'VALLE PETROLINA' => '#6610f2',
        'PEDRA LINDA' => '#d63384',
        'INVESTIMENTOS' => '#0d6efd',
    ];

    private const ICONES = [
        'RECEITAS' => 'fa-money-bill-wave',
        'CARRO' => 'fa-car',
        'CARTÕES' => 'fa-credit-card',
        'CASA' => 'fa-house',
        'TERRENOS' => 'fa-city',
        'POUPANÇA' => 'fa-piggy-bank',
        'LAZER' => 'fa-futbol',
        'SAÚDE' => 'fa-heart-pulse',
        'ESTUDOS' => 'fa-graduation-cap',
        'GASTOS ROTATIVOS' => 'fa-rotate',
        'VALLE PETROLINA' => 'fa-building',
        'PEDRA LINDA' => 'fa-house-chimney',
        'INVESTIMENTOS' => 'fa-chart-line',
    ];

    private const NOMES_CARTOES = ['nubank', 'inter', 'bola', 'cartão', 'cartao', 'vale'];

    private const DIVIDAS_POR_ANO = [
        2025 => [
            ['Saldo devedor (2025) I', 2308, 'Saldo devedor anotado na planilha 2025'],
            ['Saldo devedor (2025) II', 4257, 'Saldo devedor anotado na planilha 2025'],
        ],
        2026 => [
            ['Sogra (2026)', 26003, 'Dívida com a sogra registrada na planilha 2026'],
            ['Matheus (2026)', 10849, 'Dívida registrada na planilha 2026'],
            ['Jessica/CredAmigo (2026)', 3900, 'Dívida registrada na planilha 2026'],
        ],
        2027 => [
            ['Sogra (2027)', 32097, 'Dívida com a sogra registrada na planilha 2027'],
            ['Matheus (2027)', 9937, 'Dívida registrada na planilha 2027'],
        ],
    ];

    public function handle(): int
    {
        $user = $this->resolverUsuario();

        if (!$user) {
            $this->error('Usuário não encontrado. Crie o admin com db:seed primeiro.');

            return self::FAILURE;
        }

        $arquivo = $this->option('arquivo');
        if (!file_exists($arquivo)) {
            $this->error("Arquivo não encontrado: {$arquivo}");

            return self::FAILURE;
        }

        if ($this->option('limpar')) {
            $this->limparDados($user);
            $this->warn('Dados anteriores do usuário removidos.');
        }

        $this->info("Importando para: {$user->name} ({$user->email})");

        $reader = IOFactory::createReaderForFile($arquivo);
        $reader->setReadEmptyCells(false);
        $spreadsheet = $reader->load($arquivo);

        $anosPedidos = $this->option('anos') ?: null;
        $totalLancamentos = 0;
        $anosImportados = [];

        foreach ($spreadsheet->getSheetNames() as $nomeAba) {
            if (!preg_match('/(\d{4})/', $nomeAba, $m)) {
                continue;
            }
            $ano = (int) $m[1];
            if ($anosPedidos && !in_array($ano, $anosPedidos)) {
                continue;
            }

            $sheet = $spreadsheet->getSheetByName($nomeAba);
            $rows = $sheet->toArray(null, true, false, false);

            if (empty($rows) || count($rows) < 2) {
                continue;
            }

            $this->info("Aba {$nomeAba} ({$ano}): " . count($rows) . ' linhas');

            if ($this->option('base')) {
                $importados = $this->importarBase($user, $rows);
            } elseif ($this->ehLayoutNovo($rows)) {
                $importados = $this->importarAnoNovo($user, $ano, $rows);
            } else {
                $importados = $this->importarAnoAntigo($user, $ano, $rows);
            }

            $totalLancamentos += $importados;
            $anosImportados[] = $ano;
        }

        $spreadsheet->disconnectWorksheets();

        if ($this->option('base')) {
            $this->garantirCartoesBase($user);
            $this->sincronizarPermissoesCategorias($user);
            $this->info('Modo base: contas a pagar ignoradas.');
        } else {
            $importadas = $this->importarDividas($user);
            $this->info("Contas a pagar criadas: {$importadas}");
        }

        $this->info("Lançamentos importados no total: {$totalLancamentos}");

        return self::SUCCESS;
    }

    private function garantirCartoesBase(User $user): void
    {
        foreach (['Nubank', 'Inter', 'Bola', 'Cartão'] as $nome) {
            Cartao::firstOrCreate(
                ['user_id' => $user->id, 'nome' => $nome],
                ['tipo' => 'credito', 'bandeira' => null, 'limite' => 0, 'ativo' => true]
            );
        }

        $this->info('Cartões garantidos: ' . $user->cartoes()->pluck('nome')->implode(', '));
    }

    private function sincronizarPermissoesCategorias(User $user): void
    {
        foreach ($user->categorias as $categoria) {
            PermissionSeeder::sincronizarCategoria($categoria);
        }
    }

    private function resolverUsuario(): ?User
    {
        $opt = $this->option('user');
        if (!$opt) {
            return User::first();
        }
        if (is_numeric($opt)) {
            return User::find((int) $opt);
        }

        return User::where('email', $opt)->first();
    }

    private function limparDados(User $user): void
    {
        $user->lancamentos()->delete();
        $user->contasPagar()->delete();
        $user->categorias()->delete();
        $user->cartoes()->delete();
    }

    private function ehLayoutNovo(array $rows): bool
    {
        $linhaCabecalho = $rows[1] ?? [];
        $texto = mb_strtolower((string) ($linhaCabecalho[2] ?? ''));

        return in_array($texto, ['jan', 'jan.']);
    }

    private function importarBase(User $user, array $rows): int
    {
        $grupoAtual = null;

        foreach ($rows as $linha) {
            $colA = trim((string) ($linha[0] ?? ''));
            $colB = trim((string) ($linha[1] ?? ''));

            if ($this->ehLinhaDeControle($colA, $colB)) {
                continue;
            }

            $grupo = $this->normalizarGrupo($colA);
            if ($grupo) {
                $grupoAtual = $grupo;
                $this->obterCategoria($user, $grupo);
                continue;
            }

            $ehItem = $colB !== '' && !is_numeric(str_replace(',', '.', $colB));
            if (!$ehItem || !$grupoAtual) {
                continue;
            }

            if ($grupoAtual === 'CARTÕES') {
                $this->cartaoParaItem($user, $colB, $grupoAtual);
            }
        }

        return 0;
    }

    private function importarAnoNovo(User $user, int $ano, array $rows): int
    {
        $grupoAtual = null;
        $contador = 0;

        foreach ($rows as $linha) {
            $colA = trim((string) ($linha[0] ?? ''));
            $colB = trim((string) ($linha[1] ?? ''));

            if ($this->ehLinhaDeControle($colA, $colB)) {
                continue;
            }

            $grupo = $this->normalizarGrupo($colA);
            if ($grupo) {
                $grupoAtual = $grupo;
            }

            $ehItem = $colB !== '' && !is_numeric(str_replace(',', '.', $colB));
            if (!$ehItem || !$grupoAtual) {
                continue;
            }

            $nomeGrupo = $this->nomeGrupoFinal($grupoAtual);
            $categoria = $this->obterCategoria($user, $nomeGrupo);
            $item = $colB;

            $valores = [];
            for ($m = 1; $m <= 12; $m++) {
                $idx = 2 + ($m - 1) * 2;
                $valores[$m] = $this->parseValor($linha[$idx] ?? null);
            }

            $todosMeses = collect($valores)->every(fn ($v) => $v !== null && $v > 0);
            $contador += $this->criarLancamentos($user, $ano, $categoria, $item, $valores, $todosMeses);
        }

        return $contador;
    }

    private function importarAnoAntigo(User $user, int $ano, array $rows): int
    {
        $headers = $rows[0] ?? [];
        $colunasDespesa = [];
        $colunasReceita = [];

        foreach ($headers as $idx => $header) {
            if ($idx === 0) {
                continue;
            }
            $norm = $this->norm($header);
            if ($norm === '') {
                continue;
            }
            if ($this->ehColunaDeControle($norm)) {
                continue;
            }
            if ($this->ehColunaReceita($norm)) {
                $colunasReceita[] = $idx;

                continue;
            }
            $colunasDespesa[] = $idx;
        }

        // Agrupa valores por coluna/mês para detectar recorrência
        $valoresPorColuna = [];
        foreach ($colunasDespesa as $idx) {
            $valoresPorColuna[$idx] = [];
        }

        foreach ($rows as $linha) {
            $mes = $this->mesPorNome($this->norm($linha[0] ?? ''));
            if (!$mes) {
                continue;
            }
            foreach ($colunasDespesa as $idx) {
                $valoresPorColuna[$idx][$mes] = $this->parseValor($linha[$idx] ?? null) ?? 0;
            }
        }

        $contador = 0;

        foreach ($valoresPorColuna as $idx => $valores) {
            $norm = $this->norm($headers[$idx] ?? '');
            [$nomeGrupo, $item] = $this->mapearColunaAntiga($norm);
            $categoria = $this->obterCategoria($user, $nomeGrupo);
            $recorrente = collect($valores)->every(fn ($v) => $v > 0);
            $contador += $this->criarLancamentos($user, $ano, $categoria, $item, $valores, $recorrente);
        }

        foreach ($colunasReceita as $idx) {
            $categoria = $this->obterCategoria($user, 'RECEITAS');
            $item = ucfirst(str_replace('_', ' ', $headers[$idx] ?? 'Salário'));

            $valores = [];
            foreach ($rows as $linha) {
                $mes = $this->mesPorNome($this->norm($linha[0] ?? ''));
                if ($mes) {
                    $valores[$mes] = $this->parseValor($linha[$idx] ?? null) ?? 0;
                }
            }

            $recorrente = collect($valores)->every(fn ($v) => $v > 0);
            $contador += $this->criarLancamentos($user, $ano, $categoria, $item, $valores, $recorrente);
        }

        return $contador;
    }

    private function criarLancamentos(User $user, int $ano, Categoria $categoria, string $item, array $valores, bool $recorrente): int
    {
        $sub = $this->obterSubcategoria($user, $categoria, $item);
        $cartao = $this->cartaoParaItem($user, $item, $categoria->nome);
        $forma = $cartao ? 'cartao' : 'pix';

        $contador = 0;
        foreach ($valores as $mes => $valor) {
            if (!$valor || $valor <= 0) {
                continue;
            }

            Lancamento::create([
                'user_id' => $user->id,
                'data' => \Carbon\Carbon::create($ano, (int) $mes, 15)->format('Y-m-d'),
                'descricao' => $item,
                'valor' => round($valor, 2),
                'tipo' => $categoria->tipo,
                'categoria_id' => $categoria->id,
                'subcategoria_id' => $sub?->id,
                'forma_pagamento' => $forma,
                'cartao_id' => $cartao?->id,
                'recorrente' => $recorrente,
                'qtd_parcelas' => 1,
                'parcela_atual' => 1,
                'origem_id' => null,
                'observacao' => "Importado da planilha {$ano}",
            ]);
            $contador++;
        }

        return $contador;
    }

    private function obterCategoria(User $user, string $nomeGrupo): Categoria
    {
        $tipo = $nomeGrupo === 'RECEITAS' || $nomeGrupo === 'INVESTIMENTOS' ? 'receita' : 'despesa';

        return Categoria::firstOrCreate(
            ['user_id' => $user->id, 'nome' => $nomeGrupo],
            [
                'tipo' => $tipo,
                'cor' => self::CORES[$nomeGrupo] ?? '#6c757d',
                'icone' => self::ICONES[$nomeGrupo] ?? 'fa-tag',
                'ordem' => 0,
            ]
        );
    }

    private function obterSubcategoria(User $user, Categoria $categoria, string $item): ?Subcategoria
    {
        return Subcategoria::firstOrCreate(
            ['categoria_id' => $categoria->id, 'nome' => $item],
            ['user_id' => $user->id, 'ordem' => 0]
        );
    }

    private function cartaoParaItem(User $user, string $item, string $grupo): ?Cartao
    {
        if ($grupo !== 'CARTÕES') {
            return null;
        }
        $nome = $this->norm($item);
        $nomeCartao = match (true) {
            str_starts_with($nome, 'nubank') => 'Nubank',
            str_starts_with($nome, 'inter') => 'Inter',
            str_starts_with($nome, 'bola') => 'Bola',
            in_array($nome, ['cartao', 'cartões', 'cartoes']) => 'Cartão',
            default => null,
        };

        if (!$nomeCartao) {
            return null;
        }

        return Cartao::firstOrCreate(
            ['user_id' => $user->id, 'nome' => $nomeCartao],
            ['tipo' => 'credito', 'bandeira' => null, 'limite' => 0, 'ativo' => true]
        );
    }

    private function importarDividas(User $user): int
    {
        $contador = 0;
        foreach (self::DIVIDAS_POR_ANO as $ano => $dividas) {
            foreach ($dividas as [$descricao, $valor, $obs]) {
                $exists = $user->contasPagar()->where('descricao', $descricao)->exists();
                if ($exists) {
                    continue;
                }
                ContaPagar::create([
                    'user_id' => $user->id,
                    'descricao' => $descricao,
                    'valor_total' => $valor,
                    'valor_pago' => 0,
                    'status' => 'aberto',
                    'data_vencimento' => null,
                    'categoria_id' => null,
                    'observacao' => $obs,
                ]);
                $contador++;
            }
        }

        return $contador;
    }

    // ----- Helpers -----

    private function ehLinhaDeControle(string $colA, string $colB): bool
    {
        $a = $this->norm($colA);
        $b = $this->norm($colB);

        if (in_array($a, ['balanco financeiro', 'total', 'despesas', 'retiradas', 'total de gastos'])) {
            return true;
        }
        if (in_array($b, ['total de gastos', 'fontes', 'valor acumulado', 'total recebido'])) {
            return true;
        }
        if ($a === '' && $b === '') {
            return true;
        }
        if ($a === 'mari' && is_numeric(str_replace(',', '.', $colB))) {
            return true;
        }
        if (in_array($a, ['sogra', 'matheus', 'jessica/credamigo', 'total debito', 'total'])) {
            return true;
        }
        if (is_numeric(str_replace(',', '.', $colA)) && is_numeric(str_replace(',', '.', $colB))) {
            return true;
        }

        return false;
    }

    private function normalizarGrupo(string $colA): ?string
    {
        $norm = $this->norm($colA);
        if ($norm === '') {
            return null;
        }
        $mapa = [
            'receitas' => 'RECEITAS',
            'carro' => 'CARRO',
            'cartoes' => 'CARTÕES',
            'casa' => 'CASA',
            'terrenos' => 'TERRENOS',
            'terreno' => 'TERRENOS',
            'poupanca' => 'POUPANÇA',
            'lazer' => 'LAZER',
            'saude' => 'SAÚDE',
            'estudos' => 'ESTUDOS',
            'gastos rotativos' => 'GASTOS ROTATIVOS',
            'valle petrolina' => 'VALLE PETROLINA',
            'pedra linda' => 'PEDRA LINDA',
            'investimentos' => 'INVESTIMENTOS',
        ];

        return $mapa[$norm] ?? null;
    }

    private function nomeGrupoFinal(?string $grupo): string
    {
        return $grupo;
    }

    private function mapearColunaAntiga(string $norm): array
    {
        $mapa = [
            'seguro' => ['CARRO', 'Seguro'],
            'consorcio' => ['CARRO', 'Consorcio'],
            'gasolina' => ['CARRO', 'Gasolina'],
            'nubank' => ['CARTÕES', 'Nubank'],
            'inter' => ['CARTÕES', 'Inter'],
            'bola' => ['CARTÕES', 'Bola'],
            'cartao' => ['CARTÕES', 'Cartão'],
            'cartoes' => ['CARTÕES', 'Cartão'],
            'energia' => ['CASA', 'Energia'],
            'internet' => ['CASA', 'Internet'],
            'feira' => ['CASA', 'Feira'],
            'casa' => ['VALLE PETROLINA', 'Casa'],
            'condominio' => ['VALLE PETROLINA', 'Condomínio'],
            'terreno' => ['TERRENOS', 'Terreno'],
            'juazeiro' => ['TERRENOS', 'Juazeiro'],
            'nova petrolina' => ['TERRENOS', 'Nova Petrolina'],
            'plano' => ['SAÚDE', 'Plano'],
            'gran cursos' => ['ESTUDOS', 'Gran Cursos'],
            'senac' => ['ESTUDOS', 'SENAC'],
            'tcc' => ['ESTUDOS', 'TCC'],
            'tcc mateus e michael' => ['ESTUDOS', 'TCC'],
            'samara' => ['GASTOS ROTATIVOS', 'Samara'],
            'mae' => ['GASTOS ROTATIVOS', 'Mãe'],
            'doglas' => ['GASTOS ROTATIVOS', 'Doglas'],
            'douglas' => ['GASTOS ROTATIVOS', 'Doglas'],
            'mari' => ['GASTOS ROTATIVOS', 'Mari'],
            'felipe' => ['GASTOS ROTATIVOS', 'Felipe'],
            'divida selma' => ['GASTOS ROTATIVOS', 'Dívida Selma'],
            'iptu' => ['GASTOS ROTATIVOS', 'IPTU'],
            'ipva' => ['GASTOS ROTATIVOS', 'IPVA'],
            'bicicleta' => ['GASTOS ROTATIVOS', 'Bicicleta'],
            'itau' => ['GASTOS ROTATIVOS', 'Itaú'],
            'catiane' => ['GASTOS ROTATIVOS', 'Catiane'],
            'faespe' => ['GASTOS ROTATIVOS', 'Faespe'],
        ];

        return $mapa[$norm] ?? ['GASTOS ROTATIVOS', ucfirst($norm)];
    }

    private function ehColunaDeControle(string $norm): bool
    {
        return in_array($norm, [
            'total', 'total_contas', 'liquido', 'saldo', 'cartoes', 'total de gastos', 'total ',
        ]);
    }

    private function ehColunaReceita(string $norm): bool
    {
        return in_array($norm, ['salario', 'salario_final', 'extras', 'a receber']);
    }

    private function parseValor($v): ?float
    {
        if ($v === null || trim((string) $v) === '') {
            return null;
        }
        if (is_numeric($v)) {
            return (float) $v;
        }
        $s = trim((string) $v);
        $negativo = str_starts_with($s, '-') || str_starts_with($s, '(');
        $s = trim($s, 'R$ ();-');
        if (str_contains($s, ',')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }
        $f = (float) $s;

        return $negativo ? -$f : $f;
    }

    private function norm($v): string
    {
        $s = Str::lower(trim((string) $v));
        $s = str_replace(['ç', 'ã', 'á', 'à', 'â', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ü'], ['c', 'a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'u'], $s);

        return preg_replace('/\s+/', ' ', $s);
    }

    private function mesPorNome(string $nome): ?int
    {
        $mapa = [
            'janeiro' => 1, 'fevereiro' => 2, 'marco' => 3, 'abril' => 4,
            'maio' => 5, 'junho' => 6, 'julho' => 7, 'agosto' => 8,
            'setembro' => 9, 'outubro' => 10, 'novembro' => 11, 'dezembro' => 12,
        ];

        return $mapa[$nome] ?? null;
    }
}
