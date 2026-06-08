<?php
class PresentacionController extends Controller {
    public function index() {
        $data = [
            'title' => 'Presentación Ejecutiva — ConectaBarrio',
            'active_menu' => 'presentacion',
            'public_layout' => true,
            'skip_chartjs' => true,
        ];
        $this->view('presentacion', $data);
    }
}
