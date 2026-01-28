<?php
declare(strict_types=1);

final class Teatro {
  private ?int $idTeatro;
  private string $sala;
  private ?string $entidad;
  private string $provincia;
  private string $municipio;
  private ?string $direccion;
  private ?string $cp;
  private ?string $telefono;
  private ?string $email;
  private int $capacidadMax;
  private ?float $latitud;
  private ?float $longitud;

  public function __construct(
    string $sala,
    string $provincia,
    string $municipio,
    int $capacidadMax,
    ?string $entidad = null,
    ?string $direccion = null,
    ?string $cp = null,
    ?string $telefono = null,
    ?string $email = null,
    ?float $latitud = null,
    ?float $longitud = null,
    ?int $idTeatro = null
  ) {
    $this->idTeatro = $idTeatro;
    $this->sala = $sala;
    $this->entidad = $entidad;
    $this->provincia = $provincia;
    $this->municipio = $municipio;
    $this->direccion = $direccion;
    $this->cp = $cp;
    $this->telefono = $telefono;
    $this->email = $email;
    $this->capacidadMax = $capacidadMax;
    $this->latitud = $latitud;
    $this->longitud = $longitud;
  }

  public function getIdTeatro(): ?int { return $this->idTeatro; }
  public function getSala(): string { return $this->sala; }
  public function getEntidad(): ?string { return $this->entidad; }
  public function getProvincia(): string { return $this->provincia; }
  public function getMunicipio(): string { return $this->municipio; }
  public function getDireccion(): ?string { return $this->direccion; }
  public function getCP(): ?string { return $this->cp; }
  public function getTelefono(): ?string { return $this->telefono; }
  public function getEmail(): ?string { return $this->email; }
  public function getCapacidadMax(): int { return $this->capacidadMax; }
  public function getLatitud(): ?float { return $this->latitud; }
  public function getLongitud(): ?float { return $this->longitud; }

  public function setIdTeatro(int $id): void { $this->idTeatro = $id; }
}
