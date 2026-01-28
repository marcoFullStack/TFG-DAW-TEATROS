<?php
declare(strict_types=1);

final class Obra {
  private ?int $idObra;
  private string $titulo;
  private ?string $autor;
  private ?string $subtitulo;
  private ?int $anio;
  private ?string $urlDracor;

  public function __construct(
    string $titulo,
    ?string $autor = null,
    ?string $subtitulo = null,
    ?int $anio = null,
    ?string $urlDracor = null,
    ?int $idObra = null
  ) {
    $this->idObra = $idObra;
    $this->titulo = $titulo;
    $this->autor = $autor;
    $this->subtitulo = $subtitulo;
    $this->anio = $anio;
    $this->urlDracor = $urlDracor;
  }

  public function getIdObra(): ?int { return $this->idObra; }
  public function getTitulo(): string { return $this->titulo; }
  public function getAutor(): ?string { return $this->autor; }
  public function getSubtitulo(): ?string { return $this->subtitulo; }
  public function getAnio(): ?int { return $this->anio; }
  public function getUrlDracor(): ?string { return $this->urlDracor; }

  public function setIdObra(int $id): void { $this->idObra = $id; }
}
