<?php

namespace App\DTOs;

class BeneficiaryReportItemDTO extends BaseDTO
{
    public string $nombre;
    public string $dni;
    public string $tipo;
    public string $fechaNacimiento;
    public string $sexo;
    public string $parentesco;
    public int $edadAnos;
    public int $edadMeses;
    public int $edadDias;
    public bool $esBaja;
    public bool $observation;
    public string $fechaInicio;

    public function __construct(
        string $nombre,
        string $dni,
        string $tipo,
        string $fechaNacimiento,
        string $sexo,
        string $parentesco,
        int $edadAnos,
        int $edadMeses,
        int $edadDias,
        bool $esBaja,
        bool $observation,
        string $fechaInicio
    ) {
        $this->nombre = $nombre;
        $this->dni = $dni;
        $this->tipo = $tipo;
        $this->fechaNacimiento = $fechaNacimiento;
        $this->sexo = $sexo;
        $this->parentesco = $parentesco;
        $this->edadAnos = $edadAnos;
        $this->edadMeses = $edadMeses;
        $this->edadDias = $edadDias;
        $this->esBaja = $esBaja;
        $this->observation = $observation;
        $this->fechaInicio = $fechaInicio;
    }
}