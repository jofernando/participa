<?php

namespace App\Models\Submissao;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Certificado extends Model
{
    protected $fillable = ['caminho', 'data', 'local', 'nome', 'texto', 'tipo', 'tipo_comissao_id'];

    public const TIPO_ENUM = [
        'apresentador'          => 1,
        'comissao_cientifica'   => 2,
        'comissao_organizadora' => 3,
        'revisor'               => 4,
        'participante'          => 5,
        'expositor'             => 6,
        'coordenador_comissao_cientifica' => 7,
        'outras_comissoes' => 8,
    ];

    public function assinaturas()
    {
        return $this->belongsToMany(Assinatura::class, 'assinatura_certificado', 'certificado_id', 'assinatura_id')->orderBy('nome');
    }

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'evento_id');
    }

    public function setAtributes($request)
    {
        $this->local = $request['local'];
        $this->nome = $request['nome'];
        $texto = substr($request['texto'], 3);
        $texto = substr_replace($texto ,"", -4);
        $this->texto =  $texto;
        $this->tipo =  $request['tipo'];
        $this->data =  $request['data'];
        $this->tipo_comissao_id =  $request['tipo_comissao_id'];
    }
}
