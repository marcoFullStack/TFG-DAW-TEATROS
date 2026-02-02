<?php
declare(strict_types=1);

final class Horario {
  private ?int $idHorario;
  private int $idTeatro;
  private int $idObra;
  private string $fechaHora;   
  private float $precio;

  public function __construct(
    int $idTeatro,
    int $idObra,
    string $fechaHora,
    float $precio,
    ?int $idHorario = null
  ) {
    $this->idHorario = $idHorario;
    $this->idTeatro = $idTeatro;
    $this->idObra = $idObra;
    $this->fechaHora = $fechaHora;
    $this->precio = $precio;
  }

  public function getIdHorario(): ?int { return $this->idHorario; }
  public function getIdTeatro(): int { return $this->idTeatro; }
  public function getIdObra(): int { return $this->idObra; }
  public function getFechaHora(): string { return $this->fechaHora; }
  public function getPrecio(): float { return $this->precio; }

  public function setIdHorario(int $id): void { $this->idHorario = $id; }
}
