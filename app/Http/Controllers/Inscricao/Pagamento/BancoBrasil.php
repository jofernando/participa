<?php

namespace App\Http\Controllers\Inscricao\Pagamento;

use App\Http\Controllers\Inscricao\Pagamento\PagamentoInterface;
use App\Models\Inscricao\CategoriaParticipante;
use App\Models\Inscricao\Inscricao;
use App\Models\Inscricao\Pagamento;
use App\Models\Inscricao\TipoPagamento;
use App\Models\Submissao\Evento;
use App\Models\Users\User;
use App\Services\BB\Pix;
use Illuminate\Http\Request;

class BancoBrasil implements PagamentoInterface
{

    private Pix $pix;

    public function __construct()
    {
        $this->pix = new Pix();
    }

    public function telaPagamento(Evento $evento)
    {
        return view('inscricao.pagamento.bancobrasil.criar_pagamento', compact('evento'));
    }

    public function processarPagamento(Request $request)
    {
        $user = auth()->user();
        $evento = Evento::find($request->evento);
        $inscricao = $evento->inscricaos()->where('user_id', $user->id)->first();
        $categoria = $inscricao->categoria;
        $descricao = 'Inscrição no evento '.$evento->nome.' com valor de '.$categoria->valor_total;
        $tipo_pagamento = TipoPagamento::where('descricao', 'pix')->first();

        $devedor = $this->devedor($request);
        $valor = $this->formatarValor($categoria->valor);
        $response = $this->pix->criarCobrancaImediata($devedor, $valor);

        $pagamento = Pagamento::create([
            'valor' => $categoria->valor_total,
            'tipo_pagamento_id' => $tipo_pagamento->id,
            'descricao' => $descricao,
            'codigo' => $response['txid'],
            'status' => $response['status'],
        ]);

        $inscricao->pagamento_id = $pagamento->id;
        $inscricao->save();
        return redirect()->route('checkout.statusPagamento', $evento);
    }

    public function telaStatus(Evento $evento)
    {
        return view('inscricao.pagamento.bancobrasil.status', compact('evento'));
    }

    public function webhook(Request $request)
    {
        // TODO: Implement webhook() method.
    }

    private function somenteDigitos($cep)
    {
        return preg_replace('/[^0-9]/', '', $cep);
    }

    private function formatarValor(float $valor)
    {
        return number_format($valor, 2, '.');
    }

    private function devedor(Request $request)
    {
        $devedor = [
            'logradouro' => $request->rua,
            'cidade' => $request->cidade,
            'uf' => $request->uf,
            'cep' => $this->somenteDigitos($request->cep),
            'email' => $request->email,
            'nome' => $request->name,
        ];
        if ($request->ehCpf)
            $devedor['cpf'] = $this->somenteDigitos($request->cpf);
        else
            $devedor['cnpj'] = $this->somenteDigitos($request->cnpj);
        return $devedor;
    }
}
