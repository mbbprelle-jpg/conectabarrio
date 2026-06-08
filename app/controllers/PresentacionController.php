<?php
class PresentacionController extends Controller {
    public function index() {
        $data = [
            'header_title' => 'Presentación Ejecutiva',
            'header_subtitle' => 'Descubre las ventajas de ConectaBarrio para organizaciones territoriales',
            'active_menu' => 'presentacion',
            'public_layout' => true,
        ];
        $this->view('presentacion', $data);
    }
}
