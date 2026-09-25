<?php
class PublicoController extends Controller {
    private $censoModel;
    private $db;

    public function __construct() {
        $this->censoModel = $this->model('CensoFamiliar');
        $this->db = new Database();
    }

    /** GET /publico/censo/{token} */
    public function censo($token = '') {
        if (!$this->censoModel->hasTables()) {
            $this->view('publico/censo', [
                'title' => 'Registro no disponible',
                'public_layout' => true,
                'error' => 'El formulario aún no está habilitado. Contacte a la directiva.',
                'link' => null,
            ]);
            return;
        }

        $link = $this->censoModel->getValidLinkByToken((string)$token);
        if (!$link) {
            $this->view('publico/censo', [
                'title' => 'Enlace no válido',
                'public_layout' => true,
                'error' => 'El enlace no es válido o fue desactivado. Solicite uno nuevo a la directiva.',
                'link' => null,
            ]);
            return;
        }

        $this->renderForm($link, '', '', []);
    }

    /** POST /publico/censo_guardar */
    public function censo_guardar() {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('/');
            return;
        }
        require_once APPROOT . '/core/RutChile.php';
        require_once APPROOT . '/core/SocioInput.php';
        require_once APPROOT . '/core/OrgHelper.php';

        $post = $this->sanitizePost();
        $token = trim((string)($post['token'] ?? ''));
        $link = $this->censoModel->getValidLinkByToken($token);
        if (!$link) {
            $this->view('publico/censo', [
                'title' => 'Enlace no válido',
                'public_layout' => true,
                'error' => 'El enlace ya no es válido.',
                'link' => null,
            ]);
            return;
        }

        $juntaId = (int)$link->junta_id;
        $calles = $this->getCalles($juntaId);
        $usesCalles = OrgHelper::usesCallesJurisdiccion($link->junta_tipo ?? '');

        $rut = RutChile::normalize($post['rut'] ?? '');
        $nombre = mb_strtoupper(trim((string)($post['nombre'] ?? '')), 'UTF-8');
        $telefono = SocioInput::normalizeTelefono($post['telefono'] ?? '');
        $calleId = !empty($post['calle_id']) ? (int)$post['calle_id'] : null;
        $direccionTexto = trim((string)($post['direccion_texto'] ?? ''));

        $registraHijos = !empty($post['registra_hijos']);
        $registraDisc = !empty($post['registra_discapacidad']);
        $registraEmb = !empty($post['registra_embarazo']);

        $old = $post;
        $error = '';

        if ($rut === false) {
            $error = 'Ingrese un RUT válido con formato 11222333-K (sin puntos).';
        } elseif ($nombre === '') {
            $error = 'Ingrese el nombre completo del padre o adulto.';
        } elseif ($telefono === '' || !preg_match('/^\+569\d{8}$/', $telefono)) {
            $error = 'Ingrese un teléfono válido de 9 dígitos (ej: 950001071 → se guardará como +56950001071).';
        } elseif ($usesCalles && (empty($calleId) || empty($calles))) {
            $error = 'Seleccione una dirección del listado.';
        } elseif (!$usesCalles && $direccionTexto === '') {
            $error = 'Ingrese la dirección.';
        }

        $personas = [];
        if ($error === '') {
            $parsed = $this->parsePersonas($post, $registraHijos, $registraDisc, $registraEmb, $juntaId, [
                'rut' => $rut,
                'nombre' => $nombre,
            ]);
            if (!$parsed['ok']) {
                $error = $parsed['error'];
            } else {
                $personas = $parsed['personas'];
            }
        }

        if ($error !== '') {
            $this->renderForm($link, $error, '', $old);
            return;
        }

        $result = $this->censoModel->createRegistro([
            'junta_id' => $juntaId,
            'link_id' => (int)$link->id,
            'rut' => $rut,
            'nombre' => $nombre,
            'calle_id' => $usesCalles ? $calleId : null,
            'direccion_texto' => $usesCalles ? null : $direccionTexto,
            'telefono' => $telefono,
            'registra_hijos' => $registraHijos,
            'registra_discapacidad' => $registraDisc,
            'registra_embarazo' => $registraEmb,
        ], $personas);

        if (!$result['ok']) {
            $this->renderForm($link, $result['error'] ?? 'Error al guardar.', '', $old);
            return;
        }

        $this->view('publico/censo', [
            'title' => 'Registro enviado',
            'public_layout' => true,
            'success' => 'Sus datos fueron registrados correctamente. Gracias por participar.',
            'link' => $link,
            'calles' => [],
            'uses_calles' => $usesCalles,
            'old' => [],
            'error' => '',
        ]);
    }

    private function parsePersonas(array $post, bool $hijos, bool $disc, bool $emb, int $juntaId, array $adulto): array {
        require_once APPROOT . '/core/RutChile.php';
        $personas = [];
        $rutsEnForm = [];

        if ($hijos) {
            $rows = $this->rowsFromPost($post, 'hijo');
            if (empty($rows)) {
                return ['ok' => false, 'error' => 'Indique al menos un hijo o desmarque la opción.'];
            }
            foreach ($rows as $i => $row) {
                $n = $i + 1;
                $rut = RutChile::normalize($row['rut'] ?? '');
                $nombre = mb_strtoupper(trim((string)($row['nombre'] ?? '')), 'UTF-8');
                $sexo = trim((string)($row['sexo'] ?? ''));
                $edad = (int)($row['edad'] ?? -1);
                if ($rut === false) {
                    return ['ok' => false, 'error' => "Hijo #$n: RUT inválido."];
                }
                if ($nombre === '' || !in_array($sexo, ['MASCULINO', 'FEMENINO', 'NO ESPECIFICAR'], true)) {
                    return ['ok' => false, 'error' => "Hijo #$n: complete nombre y sexo."];
                }
                if ($edad < 0 || $edad > 8) {
                    return ['ok' => false, 'error' => "Hijo #$n: la edad debe ser entre 0 y 8 años."];
                }
                if (isset($rutsEnForm[$rut]) || $this->censoModel->rutNinoYaRegistrado($juntaId, $rut, 'hijo')) {
                    return ['ok' => false, 'error' => "Hijo #$n: el RUT $rut ya está registrado."];
                }
                $rutsEnForm[$rut] = true;
                $personas[] = [
                    'tipo' => 'hijo',
                    'rut' => $rut,
                    'nombre_completo' => $nombre,
                    'sexo' => $sexo,
                    'edad' => $edad,
                    'fecha_parto' => null,
                    'usa_datos_adulto' => 0,
                ];
            }
        }

        if ($disc) {
            $rows = $this->rowsFromPost($post, 'disc');
            if (empty($rows)) {
                return ['ok' => false, 'error' => 'Indique al menos una persona con discapacidad o desmarque la opción.'];
            }
            foreach ($rows as $i => $row) {
                $n = $i + 1;
                $rut = RutChile::normalize($row['rut'] ?? '');
                $nombre = mb_strtoupper(trim((string)($row['nombre'] ?? '')), 'UTF-8');
                $sexo = trim((string)($row['sexo'] ?? ''));
                $edad = (int)($row['edad'] ?? -1);
                if ($rut === false) {
                    return ['ok' => false, 'error' => "Discapacidad #$n: RUT inválido."];
                }
                if ($nombre === '' || !in_array($sexo, ['MASCULINO', 'FEMENINO', 'NO ESPECIFICAR'], true)) {
                    return ['ok' => false, 'error' => "Discapacidad #$n: complete nombre y sexo."];
                }
                if ($edad < 0 || $edad > 18) {
                    return ['ok' => false, 'error' => "Discapacidad #$n: la edad debe ser entre 0 y 18 años."];
                }
                $personas[] = [
                    'tipo' => 'discapacidad',
                    'rut' => $rut,
                    'nombre_completo' => $nombre,
                    'sexo' => $sexo,
                    'edad' => $edad,
                    'fecha_parto' => null,
                    'usa_datos_adulto' => 0,
                ];
            }
        }

        if ($emb) {
            $usaMismos = !empty($post['embarazo_usa_adulto']);
            $hoy = date('Y-m-d');
            $maxParto = '2026-12-31';

            if ($usaMismos) {
                $fechaParto = trim((string)($post['embarazo_fecha_parto'] ?? ''));
                $sexo = 'FEMENINO';
                if ($fechaParto === '' || $fechaParto < $hoy || $fechaParto > $maxParto) {
                    return ['ok' => false, 'error' => 'Embarazo: indique fecha probable de parto entre hoy y diciembre 2026.'];
                }
                $personas[] = [
                    'tipo' => 'embarazo',
                    'rut' => $adulto['rut'],
                    'nombre_completo' => $adulto['nombre'],
                    'sexo' => $sexo,
                    'edad' => null,
                    'fecha_parto' => $fechaParto,
                    'usa_datos_adulto' => 1,
                ];
            } else {
                $rut = RutChile::normalize($post['embarazo_rut'] ?? '');
                $nombre = mb_strtoupper(trim((string)($post['embarazo_nombre'] ?? '')), 'UTF-8');
                $sexo = trim((string)($post['embarazo_sexo'] ?? 'FEMENINO'));
                $fechaParto = trim((string)($post['embarazo_fecha_parto'] ?? ''));
                if ($rut === false) {
                    return ['ok' => false, 'error' => 'Embarazo: RUT inválido.'];
                }
                if ($nombre === '') {
                    return ['ok' => false, 'error' => 'Embarazo: indique el nombre completo.'];
                }
                if (!in_array($sexo, ['MASCULINO', 'FEMENINO', 'NO ESPECIFICAR'], true)) {
                    $sexo = 'FEMENINO';
                }
                if ($fechaParto === '' || $fechaParto < $hoy || $fechaParto > $maxParto) {
                    return ['ok' => false, 'error' => 'Embarazo: indique fecha probable de parto entre hoy y diciembre 2026.'];
                }
                $personas[] = [
                    'tipo' => 'embarazo',
                    'rut' => $rut,
                    'nombre_completo' => $nombre,
                    'sexo' => $sexo,
                    'edad' => null,
                    'fecha_parto' => $fechaParto,
                    'usa_datos_adulto' => 0,
                ];
            }
        }

        return ['ok' => true, 'personas' => $personas];
    }

    private function rowsFromPost(array $post, string $prefix): array {
        $ruts = $post[$prefix . '_rut'] ?? [];
        $nombres = $post[$prefix . '_nombre'] ?? [];
        $sexos = $post[$prefix . '_sexo'] ?? [];
        $edades = $post[$prefix . '_edad'] ?? [];
        if (!is_array($ruts)) {
            return [];
        }
        $rows = [];
        foreach ($ruts as $i => $rut) {
            $rut = trim((string)$rut);
            $nombre = trim((string)($nombres[$i] ?? ''));
            $sexo = trim((string)($sexos[$i] ?? ''));
            $edad = $edades[$i] ?? '';
            if ($rut === '' && $nombre === '') {
                continue;
            }
            $rows[] = [
                'rut' => $rut,
                'nombre' => $nombre,
                'sexo' => $sexo,
                'edad' => $edad,
            ];
        }
        return $rows;
    }

    private function renderForm(object $link, string $error, string $success, array $old): void {
        require_once APPROOT . '/core/OrgHelper.php';
        $juntaId = (int)$link->junta_id;
        $usesCalles = OrgHelper::usesCallesJurisdiccion($link->junta_tipo ?? '');
        $this->view('publico/censo', [
            'title' => $link->titulo ?: 'Registro familiar',
            'public_layout' => true,
            'link' => $link,
            'calles' => $this->getCalles($juntaId),
            'uses_calles' => $usesCalles,
            'old' => $old,
            'error' => $error,
            'success' => $success,
        ]);
    }

    private function getCalles(int $juntaId): array {
        $this->db->query('SELECT * FROM calles WHERE junta_id = :junta_id ORDER BY nombre ASC');
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }
}
