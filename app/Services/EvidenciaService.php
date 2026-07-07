<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Repositories\ArchivoRepository;
use App\Repositories\OrdenRepository;
use RuntimeException;

final class EvidenciaService
{
    private const MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public function __construct(
        private readonly ArchivoRepository $archivos = new ArchivoRepository(),
        private readonly OrdenRepository $ordenes = new OrdenRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService()
    ) {
    }

    public function listar(int $ordenId): array
    {
        return $this->archivos->forEntidad('orden', $ordenId);
    }

    public function subir(int $ordenId, ?array $file, bool $aceptaTerminos, string $nota = ''): int
    {
        /*
         * Sube la foto del ticket firmado como evidencia de la orden.
         * La foto SI se guarda (en storage/uploads, fuera del webroot); el PDF
         * no se guarda, se genera al vuelo. Si el cliente acepta presupuesto y
         * terminos, se deja constancia en la orden y en la bitacora.
         */
        $orden = $this->ordenes->find($ordenId);
        if (!$orden) {
            throw new RuntimeException('La orden no existe.');
        }

        if (!$file || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Selecciona una foto de la evidencia.');
        }

        $maxMb = (int) env_value('UPLOAD_MAX_MB', 8);
        if ((int) $file['size'] > $maxMb * 1024 * 1024) {
            throw new RuntimeException("La imagen supera el limite de {$maxMb} MB.");
        }

        $mime = $this->detectarMime((string) $file['tmp_name']);
        if (!isset(self::MIMES[$mime])) {
            throw new RuntimeException('Formato no valido. Sube una imagen JPG, PNG o WEBP.');
        }

        $dirRelativa = 'ordenes/' . $ordenId;
        $dirAbsoluta = BASE_PATH . '/storage/uploads/' . $dirRelativa;
        if (!is_dir($dirAbsoluta) && !mkdir($dirAbsoluta, 0775, true) && !is_dir($dirAbsoluta)) {
            throw new RuntimeException('No se pudo preparar el almacenamiento de evidencias.');
        }

        $nombreOriginal = (string) ($file['name'] ?? 'evidencia.' . self::MIMES[$mime]);
        $nombreArchivo = 'evidencia-' . bin2hex(random_bytes(6)) . '.' . self::MIMES[$mime];
        $destino = $dirAbsoluta . '/' . $nombreArchivo;

        if (!move_uploaded_file((string) $file['tmp_name'], $destino)) {
            throw new RuntimeException('No se pudo guardar la imagen.');
        }

        $archivoId = $this->archivos->create([
            'entidad_tipo' => 'orden',
            'entidad_id' => $ordenId,
            'categoria' => 'recepcion',
            'nombre_original' => mb_substr($nombreOriginal, 0, 255),
            'nombre_archivo' => $nombreArchivo,
            'ruta' => $dirRelativa . '/' . $nombreArchivo,
            'mime' => $mime,
            'tamano' => (int) $file['size'],
            'visible_cliente' => 0,
            'uploaded_by' => Auth::id(),
        ]);

        $this->auditoria->registrar('evidencia_subida', 'ordenes', $ordenId, null, [
            'archivo_id' => $archivoId,
            'nombre' => $nombreOriginal,
            'nota' => $nota !== '' ? $nota : null,
        ]);

        if ($aceptaTerminos) {
            $sello = sprintf(
                'Cliente acepto presupuesto y terminos | %s | registro: %s',
                date('Y-m-d H:i'),
                (string) (Auth::user()['name'] ?? 'sistema')
            );
            $this->ordenes->updateAcceptance($ordenId, $sello);
            $this->auditoria->registrar('terminos_aceptados', 'ordenes', $ordenId, null, [
                'evidencia_id' => $archivoId,
                'sello' => $sello,
            ]);
        }

        return $archivoId;
    }

    public function archivoDeOrden(int $ordenId, int $archivoId): ?array
    {
        $archivo = $this->archivos->find($archivoId);
        if (!$archivo || $archivo['entidad_tipo'] !== 'orden' || (int) $archivo['entidad_id'] !== $ordenId) {
            return null;
        }

        return $archivo;
    }

    private function detectarMime(string $tmpName): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = (string) finfo_file($finfo, $tmpName);
                finfo_close($finfo);
                return $mime;
            }
        }

        $info = @getimagesize($tmpName);
        return $info['mime'] ?? '';
    }
}
