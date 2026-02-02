<?php
declare(strict_types=1);

final class Admin
{
    private ?int $idAdmin;
    private string $nombre;
    private string $email;
    private string $passwordHash;
    private ?string $creadoEn;      
    private ?string $ultimoLogin;   

    public function __construct(
        string $nombre,
        string $email,
        string $passwordHash,
        ?int $idAdmin = null,
        ?string $creadoEn = null,
        ?string $ultimoLogin = null
    ) {
        $this->idAdmin = $idAdmin;
        $this->nombre = $nombre;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->creadoEn = $creadoEn;
        $this->ultimoLogin = $ultimoLogin;
    }

    public function getIdAdmin(): ?int { return $this->idAdmin; }
    public function getNombre(): string { return $this->nombre; }
    public function getEmail(): string { return $this->email; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function getCreadoEn(): ?string { return $this->creadoEn; }
    public function getUltimoLogin(): ?string { return $this->ultimoLogin; }

    public function setIdAdmin(int $id): void { $this->idAdmin = $id; }
}
