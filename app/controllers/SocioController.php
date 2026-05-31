<?php
class SocioController extends Controller {
    private $userModel;
    private $transaccionModel;
    private $cuotaModel;

    public function __construct() {
        // Cargar los modelos correspondientes
        $this->userModel = $this->model('User');
        $this->transaccionModel = $this->model('Transaccion');
        $this->cuotaModel = $this->model('CuotaConfig');
    }

    // Dashboard del Socio Vecino
    public function dashboard() {
        $socioId = $_SESSION['user_id'];
        $juntaId = $_SESSION['user_junta_id'];

        // Obtener datos del socio
        $socio = $this->userModel->getSocioById($socioId);
        if (!$socio) {
            $socio = $this->userModel->getUserById($socioId);
        }
        if (!$socio) {
            die('Error al cargar la información del socio.');
        }

        // Obtener historial completo de transacciones del socio (Cuotas, Donaciones, Condonaciones)
        $transacciones = $this->transaccionModel->getTransaccionesBySocio($socioId);

        // Obtener el valor de cuota vigente para el mes actual
        $mesActual = date('Y-m');
        $cuotaVigente = $this->cuotaModel->getCuotaVigente($juntaId, $mesActual);

        // Estadísticas básicas para tarjetas métricas
        $totalAportado = 0;
        $cantidadCuotasPagadas = 0;
        foreach ($transacciones as $t) {
            if ($t->tipo === 'ingreso' && $t->categoria !== 'Cuota Condonada') {
                $totalAportado += $t->monto;
            }
            if ($t->categoria === 'Cuota Socio') {
                $cantidadCuotasPagadas++;
            }
        }

        $data = [
            'title' => 'Mi Perfil Vecinal',
            'header_title' => 'Panel de Socio Vecino',
            'header_subtitle' => 'Revise su información de afiliación, estado de cuotas y descargue comprobantes oficiales',
            'active_menu' => 'dashboard',
            'socio' => $socio,
            'transacciones' => $transacciones,
            'cuota_vigente' => $cuotaVigente ? $cuotaVigente->monto : 0,
            'total_pagado' => $totalAportado,
            'cantidad_pagos' => $cantidadCuotasPagadas
        ];

        $this->view('socio/dashboard', $data);
    }

    // Listado completo de Comprobantes de Pago del Socio
    public function comprobantes() {
        $socioId = $_SESSION['user_id'];

        // Obtener todos los movimientos asociados a este socio
        $transacciones = $this->transaccionModel->getTransaccionesBySocio($socioId);

        $data = [
            'title' => 'Mis Comprobantes de Pago',
            'header_title' => 'Historial de Comprobantes',
            'header_subtitle' => 'Visualice e imprima los comprobantes de sus cuotas registradas por la directiva',
            'active_menu' => 'comprobantes',
            'transacciones' => $transacciones,
            'success' => $_SESSION['success_msg'] ?? '',
            'error' => $_SESSION['error_msg'] ?? '',
        ];
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);

        $this->view('socio/comprobantes', $data);
    }

    // Visualizar un comprobante de pago específico para impresión
    public function comprobante($id) {
        $socioId = (int)$_SESSION['user_id'];
        $pago = $this->transaccionModel->getComprobanteById($id);

        if (!$pago || (int)$pago->socio_id !== $socioId) {
            $_SESSION['error_msg'] = 'No tiene autorización para visualizar este comprobante.';
            $this->redirect('/socio/comprobantes');
            return;
        }

        $data = [
            'title' => 'Comprobante Folio #' . str_pad($pago->id, 6, '0', STR_PAD_LEFT),
            'pago' => $pago,
            'is_socio_view' => true,
            'back_url' => URLROOT . '/socio/comprobantes',
        ];

        $this->view('admin/comprobante_detalle', $data);
    }
}
