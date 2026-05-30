<?php
/*
 * Vista: app/views/maestro/payments.php
 * Muestra listado de pagos, resumen y modal para crear/editar.
 */
?>
<div class="page-header">
    <div class="page-title-group">
        <h1>Gestión de Pagos</h1>
        <p>Controla los pagos de tu organización y visualiza el estado de cada cuota.</p>
    </div>
    <button class="btn btn-primary" id="btnAddPayment" onclick="openPaymentModal();">Agregar Pago</button>
</div>

<div class="grid-2col">
    <div class="card card-primary">
        <div class="card-title"><span class="metric-icon"><svg><!-- icon --></svg></span>Pagos al día</div>
        <div class="metric-info">
            <div class="metric-value" id="paidCount">0</div>
        </div>
    </div>
    <div class="card card-danger">
        <div class="card-title"><span class="metric-icon"><svg><!-- icon --></svg></span>Pagos vencidos</div>
        <div class="metric-info">
            <div class="metric-value" id="overdueCount">0</div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Monto</th>
                <th>Fecha límite</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="paymentsTableBody">
            <!-- rows filled by JS -->
        </tbody>
    </table>
</div>

<!-- Modal de crear/editar pago -->
<div class="modal" id="paymentModal" style="display:none;">
    <div class="modal-content">
        <span class="modal-close" onclick="closePaymentModal();">&times;</span>
        <h2 id="modalTitle">Nuevo Pago</h2>
        <form id="paymentForm">
            <input type="hidden" name="id" id="paymentId">
            <div class="form-group">
                <label class="form-label" for="orgId">Organización ID</label>
                <input class="form-control" type="number" name="org_id" id="orgId" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="amount">Monto (CLP)</label>
                <input class="form-control" type="number" name="amount" id="amount" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="dueDate">Fecha límite</label>
                <input class="form-control" type="date" name="due_date" id="dueDate" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="status">Estado</label>
                <select class="form-control" name="status" id="status">
                    <option value="pending">Pendiente</option>
                    <option value="paid">Pagado</option>
                    <option value="overdue">Vencido</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Guardar</button>
        </form>
    </div>
</div>

<script>
// JavaScript simple para cargar datos y manejar modal (usa fetch API)
async function loadPayments(){
    const resp = await fetch('<?= URLROOT ?>/maestro/paymentsData');
    const data = await resp.json();
    document.getElementById('paidCount').textContent = data.summary.paid;
    document.getElementById('overdueCount').textContent = data.summary.overdue;
    const tbody = document.getElementById('paymentsTableBody');
    tbody.innerHTML = '';
    data.payments.forEach(p=>{
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${p.id}</td><td>$${p.amount}</td><td>${p.due_date}</td><td><span class="badge ${p.status==='overdue'?'badge-danger':'badge-success'}">${p.status}</span></td><td><button class="btn btn-sm btn-primary" onclick="editPayment(${p.id})">Editar</button> <button class="btn btn-sm btn-danger" onclick="deletePayment(${p.id})">Eliminar</button></td>`;
        tbody.appendChild(tr);
    });
}
function openPaymentModal(id=null){
    document.getElementById('paymentForm').reset();
    document.getElementById('paymentId').value = '';
    document.getElementById('modalTitle').textContent = id? 'Editar Pago' : 'Nuevo Pago';
    if(id){
        // cargar datos del pago
        fetch(`${URLROOT}/maestro/payment/${id}`).then(r=>r.json()).then(p=>{
            document.getElementById('paymentId').value = p.id;
            document.getElementById('orgId').value = p.org_id;
            document.getElementById('amount').value = p.amount;
            document.getElementById('dueDate').value = p.due_date;
            document.getElementById('status').value = p.status;
        });
    }
    document.getElementById('paymentModal').style.display='flex';
}
function closePaymentModal(){
    document.getElementById('paymentModal').style.display='none';
}
document.getElementById('paymentForm').addEventListener('submit', async e=>{
    e.preventDefault();
    const form = e.target;
    const payload = Object.fromEntries(new FormData(form));
    const method = payload.id ? 'PUT' : 'POST';
    const url = payload.id ? `${URLROOT}/maestro/payment/${payload.id}` : `${URLROOT}/maestro/payments`;
    const resp = await fetch(url, {method, headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
    if(resp.ok){
        closePaymentModal();
        loadPayments();
    } else { alert('Error al guardar'); }
});
async function deletePayment(id){
    if(!confirm('¿Eliminar este pago?')) return;
    const resp = await fetch(`${URLROOT}/maestro/payment/${id}`, {method:'DELETE'});
    if(resp.ok) loadPayments(); else alert('Error');
}
loadPayments();
</script>
