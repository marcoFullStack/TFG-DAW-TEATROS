<?php
// app/models/Usuario.php
declare(strict_types=1);

final class Usuario
{
    private ?int $idUsuario;
    private string $nombre;
    private string $email;
    private string $passwordHash;
    private ?string $fotoPerfil;
    private int $puntos;
    private ?string $fechaAlta; // o DateTimeImmutable si quieres

    public function __construct(
        string $nombre,
        string $email,
        string $passwordHash,
        ?string $fotoPerfil = null,
        ?int $idUsuario = null,
        int $puntos = 0,
        ?string $fechaAlta = null
    ) {
        $this->idUsuario = $idUsuario;
        $this->nombre = $nombre;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->fotoPerfil = $fotoPerfil;
        $this->puntos = $puntos;
        $this->fechaAlta = $fechaAlta;
    }

    public function getIdUsuario(): ?int { return $this->idUsuario; }
    public function getNombre(): string { return $this->nombre; }
    public function getEmail(): string { return $this->email; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function getFotoPerfil(): ?string { return $this->fotoPerfil; }
    public function getPuntos(): int { return $this->puntos; }
    public function getFechaAlta(): ?string { return $this->fechaAlta; }

    public function setIdUsuario(int $id): void { $this->idUsuario = $id; }
}
