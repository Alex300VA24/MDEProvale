<?php

namespace App\DTOs;

class ReparticionDTO extends BaseDTO
{
    public int $associationId;
    public string $codigo;
    public string $nombre;
    public string $presidenta;
    public string $direccion;
    public string $sector;
    public int $beneficiarios;
    public int $dias;
    public float $lecheMl;
    public float $hojuelasGramos;
    public float $lecheLitros;
    public int $lecheCajas;
    public int $lecheTarros;
    public float $hojuelasKg;
    public int $hojuelasSacos;
    public int $hojuelasKilos;

    public function __construct(
        int $associationId,
        string $codigo,
        string $nombre,
        string $presidenta,
        string $direccion,
        string $sector,
        int $beneficiarios,
        int $dias,
        float $lecheMl,
        float $hojuelasGramos,
        float $lecheLitros,
        int $lecheCajas,
        int $lecheTarros,
        float $hojuelasKg,
        int $hojuelasSacos,
        int $hojuelasKilos
    ) {
        $this->associationId = $associationId;
        $this->codigo = $codigo;
        $this->nombre = $nombre;
        $this->presidenta = $presidenta;
        $this->direccion = $direccion;
        $this->sector = $sector;
        $this->beneficiarios = $beneficiarios;
        $this->dias = $dias;
        $this->lecheMl = $lecheMl;
        $this->hojuelasGramos = $hojuelasGramos;
        $this->lecheLitros = $lecheLitros;
        $this->lecheCajas = $lecheCajas;
        $this->lecheTarros = $lecheTarros;
        $this->hojuelasKg = $hojuelasKg;
        $this->hojuelasSacos = $hojuelasSacos;
        $this->hojuelasKilos = $hojuelasKilos;
    }
}