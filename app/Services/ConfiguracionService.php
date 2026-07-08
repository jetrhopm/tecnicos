<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Auth;
use RuntimeException;

final class ConfiguracionService
{
    public function get(string $clave, mixed $default = null): mixed
    {
        $stmt = Database::connection()->prepare('SELECT valor FROM configuraciones WHERE clave = :clave LIMIT 1');
        $stmt->execute(['clave' => $clave]);
        $row = $stmt->fetch();
        return $row['valor'] ?? $default;
    }

    public function allGrouped(): array
    {
        $rows = Database::connection()->query('SELECT * FROM configuraciones ORDER BY grupo, clave')->fetchAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['grupo']][] = $row;
        }

        return $grouped;
    }

    public function actualizar(array $valores, ?array $logoFile = null): void
    {
        $db = Database::connection();
        $rows = $db->query('SELECT clave, valor, tipo FROM configuraciones ORDER BY clave')->fetchAll();
        $existentes = [];
        foreach ($rows as $row) {
            $existentes[$row['clave']] = $row;
        }

        if ($logoFile && (int) ($logoFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $valores['negocio.logo_url'] = $this->guardarLogo($logoFile);
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare('UPDATE configuraciones SET valor = :valor WHERE clave = :clave');
            $cambios = [];

            foreach ($existentes as $clave => $row) {
                $valor = $valores[$clave] ?? null;

                if ($row['tipo'] === 'bool') {
                    $valor = $valor ? '1' : '0';
                } elseif ($row['tipo'] === 'number') {
                    $valor = is_numeric($valor) ? (string) $valor : '0';
                } else {
                    $valor = trim((string) $valor);
                }

                if ((string) $row['valor'] !== (string) $valor) {
                    $stmt->execute(['clave' => $clave, 'valor' => $valor]);
                    $cambios[$clave] = ['antes' => $row['valor'], 'despues' => $valor];
                }
            }

            if ($cambios !== []) {
                (new AuditoriaService())->registrar('editar', 'configuracion', null, null, $cambios);
            }

            $db->commit();
        } catch (\Throwable) {
            $db->rollBack();
            throw new RuntimeException('No se pudo guardar la configuracion.');
        }
    }

    private function guardarLogo(array $file): string
    {
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo recibir el logo.');
        }

        $maxMb = min((int) env_value('UPLOAD_MAX_MB', 8), 4);
        if ((int) ($file['size'] ?? 0) > $maxMb * 1024 * 1024) {
            throw new RuntimeException("El logo supera el limite de {$maxMb} MB.");
        }

        $mime = $this->detectarMime((string) ($file['tmp_name'] ?? ''));
        $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($permitidos[$mime])) {
            throw new RuntimeException('Formato de logo no valido. Usa JPG, PNG o WEBP.');
        }

        $dirRelativa = 'assets/img/uploads';
        $dirAbsoluta = BASE_PATH . '/public/' . $dirRelativa;
        if (!is_dir($dirAbsoluta) && !mkdir($dirAbsoluta, 0775, true) && !is_dir($dirAbsoluta)) {
            throw new RuntimeException('No se pudo preparar la carpeta del logo.');
        }

        $nombre = 'logo-taller-' . bin2hex(random_bytes(6)) . '.' . $permitidos[$mime];
        $destino = $dirAbsoluta . '/' . $nombre;

        if (!move_uploaded_file((string) $file['tmp_name'], $destino)) {
            throw new RuntimeException('No se pudo guardar el logo.');
        }

        (new AuditoriaService())->registrar('logo_actualizado', 'configuracion', null, null, [
            'archivo' => $nombre,
            'usuario_id' => Auth::id(),
        ]);

        return $dirRelativa . '/' . $nombre;
    }

    private function detectarMime(string $tmpName): string
    {
        if ($tmpName === '' || !is_file($tmpName)) {
            return '';
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = (string) finfo_file($finfo, $tmpName);
                finfo_close($finfo);
                return $mime;
            }
        }

        return (string) mime_content_type($tmpName);
    }
}
