<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório Financeiro {{ $ano }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h2 { margin: 0 0 4px; }
        .sub { color: #666; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        th, td { border: 1px solid #999; padding: 3px 5px; }
        th { background: #eee; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .receita { color: #1a7f37; font-weight: bold; }
        .despesa { color: #c62828; font-weight: bold; }
        .negrito { font-weight: bold; }
        .bg-suave { background: #f5f5f5; }
    </style>
</head>
<body>
    @php $listaMeses = $mesAtual ? [$mesAtual] : range(1, 12); $colspan = count($listaMeses) + 2; @endphp
    <h2>Meu Financeiro — Relatório {{ $ano }}{{ $mesAtual ? ' · ' . \Carbon\Carbon::create()->month($mesAtual)->translatedFormat('F') : '' }}</h2>
    <div class="sub">Balanço financeiro {{ $mesAtual ? 'mensal' : 'anual' }} gerado em {{ now()->translatedFormat('d/m/Y H:i') }}</div>

    <table>
        <tr class="bg-suave">
            <td colspan="13"><strong>RECEITAS TOTAIS</strong></td>
            <td class="text-end receita">{{ number_format($totaisAno['receitas'], 2, ',', '.') }}</td>
        </tr>
        <tr class="bg-suave">
            <td colspan="13"><strong>DESPESAS TOTAIS</strong></td>
            <td class="text-end despesa">{{ number_format($totaisAno['despesas'], 2, ',', '.') }}</td>
        </tr>
        <tr class="bg-suave">
            <td colspan="13"><strong>SALDO</strong></td>
            <td class="text-end {{ $totaisAno['saldo'] >= 0 ? 'receita' : 'despesa' }}">{{ number_format($totaisAno['saldo'], 2, ',', '.') }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Categoria / Item</th>
                @foreach ($listaMeses as $m)
                    <th class="text-center">{{ \Carbon\Carbon::create()->month($m)->translatedFormat('M') }}</th>
                @endforeach
                <th class="text-center">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach (['receitas' => 'Receitas', 'despesas' => 'Despesas'] as $tipo => $titulo)
                <tr>
                    <td colspan="{{ $colspan }}" class="negrito">{{ $titulo }}</td>
                </tr>
                @foreach ($agrupadas[$tipo] ?? [] as $categoriaNome => $itens)
                    <tr class="bg-suave">
                        <td class="negrito">{{ $categoriaNome }}</td>
                        @foreach ($listaMeses as $m)
                            <td class="text-end">{{ number_format(collect($itens)->sum(fn ($i) => $i[$m] ?? 0), 2, ',', '.') }}</td>
                        @endforeach
                        <td class="text-end negrito">{{ number_format(collect($itens)->sum(fn ($i) => collect($i)->sum()), 2, ',', '.') }}</td>
                    </tr>
                    @foreach ($itens as $itemNome => $valores)
                        <tr>
                            <td>{{ $itemNome }}</td>
                            @foreach ($listaMeses as $m)
                                <td class="text-end">{{ ($valores[$m] ?? 0) ? number_format($valores[$m], 2, ',', '.') : '' }}</td>
                            @endforeach
                            <td class="text-end">{{ number_format(collect($valores)->sum(), 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>
