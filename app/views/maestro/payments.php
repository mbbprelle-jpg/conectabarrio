<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div style="display: flex; justify-content: flex-end; margin-bottom: 1.5rem;">
    <button class="btn btn-primary btn-sm" id="btnAddPayment" type="button" onclick="openPaymentModal();">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Agregar Pago
    </button>
</div>

<div class="metrics-grid">
    <div class="card metric-card card-success">
        <div class="metric-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="metric-info">
            <span class="metric-label">Pagos al día</span>
            <span class="metric-value" id="paidCount">0</span>
        </div>
    </div>
    <div class="card metric-card card-danger">
        <div class="metric-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="metric-info">
            <span class="metric-label">Pagos vencidos</span>
            <span class="metric-value" id="overdueCount">0</span>
        </div>
    </div>
</div>

<div class="card">
    <h3 class="card-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2" ry="2"></rect><line x1="3" y1="11" x2="21" y2="11"></line></svg>
        Registro de Pagos
    </h3>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Organización</th>
                    <th>Monto</th>
                    <th>Fecha límite</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="paymentsTableBody">
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">Cargando pagos...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div id="paymentModal" class="glass-modal-overlay">
    <div class="glass-modal-container">
        <button type="button" class="modal-close" onclick="closePaymentModal();" aria-label="Cerrar">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>

        <div style="text-align: center; margin-bottom: 2rem;">
            <h3 id="modalTitle" style="font-family: var(--font-heading); color: var(--text-main); font-size: 1.5rem; margin: 0;">Nuevo Pago</h3>
        </div>

        <form id="paymentForm">
            <input type="hidden" name="id" id="paymentId">
            <div class="form-group">
                <label class="form-label" for="orgId">Organización</label>
                <select class="form-control" name="org_id" id="orgId" required>
                    <option value="">Seleccione una organización</option>
                    <?php foreach ($data['juntas'] as $junta): ?>
                        <option value="<?php echo (int)$junta->id; ?>"><?php echo htmlspecialchars($junta->nombre); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="amount">Monto (CLP)</label>
                <input class="form-control" type="number" name="amount" id="amount" min="0" step="1" required>
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
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closePaymentModal();">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<style>
.glass-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(10, 10, 12, 0.65);
    backdrop-filter: blur(15px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.glass-modal-overlay.active {
    display: flex;
    opacity: 1;
}
.glass-modal-container {
    background: rgba(20, 20, 25, 0.75);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: var(--radius-md);
    padding: 2.5rem;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), 0 0 40px rgba(6, 182, 212, 0.1);
    backdrop-filter: blur(25px);
    transform: scale(0.9);
    transition: transform 0.3s ease;
    position: relative;
}
.glass-modal-overlay.active .glass-modal-container {
    transform: scale(1);
}
</style>

<script>
const URLROOT = '<?php echo URLROOT; ?>';

const statusLabels = {
    pending: 'Pendiente',
    paid: 'Pagado',
    overdue: 'Vencido'
};

function formatAmount(amount) {
    return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(amount);
}

function statusBadgeClass(status) {
    if (status === 'paid') return 'badge-success';
    if (status === 'overdue') return 'badge-danger';
    return 'badge-warning';
}

async function loadPayments() {
    const tbody = document.getElementById('paymentsTableBody');
    try {
        const resp = await fetch(`${URLROOT}/maestro/paymentsData`);
        const data = await resp.json();
        document.getElementById('paidCount').textContent = data.summary.paid;
        document.getElementById('overdueCount').textContent = data.summary.overdue;
        tbody.innerHTML = '';

        if (!data.payments.length) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay pagos registrados.</td></tr>';
            return;
        }

        data.payments.forEach(p => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${p.id}</td>
                <td>${p.org_nombre || '-'}</td>
                <td>${formatAmount(p.amount)}</td>
                <td>${p.due_date}</td>
                <td><span class="badge ${statusBadgeClass(p.status)}">${statusLabels[p.status] || p.status}</span></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editPayment(${p.id})">Editar</button>
                    <button class="btn btn-sm btn-danger" onclick="deletePayment(${p.id})">Eliminar</button>
                </td>`;
            tbody.appendChild(tr);
        });
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--danger); padding: 2rem;">Error al cargar los pagos.</td></tr>';
    }
}

function openPaymentModal(id = null) {
    document.getElementById('paymentForm').reset();
    document.getElementById('paymentId').value = '';
    document.getElementById('modalTitle').textContent = id ? 'Editar Pago' : 'Nuevo Pago';
    document.getElementById('orgId').disabled = false;

    if (id) {
        fetch(`${URLROOT}/maestro/payment/${id}`)
            .then(r => r.json())
            .then(p => {
                document.getElementById('paymentId').value = p.id;
                document.getElementById('orgId').value = p.org_id;
                document.getElementById('amount').value = p.amount;
                document.getElementById('dueDate').value = p.due_date;
                document.getElementById('status').value = p.status;
            });
    }

    const modal = document.getElementById('paymentModal');
    modal.style.display = 'flex';
    requestAnimationFrame(() => modal.classList.add('active'));
}

function closePaymentModal() {
    const modal = document.getElementById('paymentModal');
    modal.classList.remove('active');
    setTimeout(() => { modal.style.display = 'none'; }, 300);
}

function editPayment(id) {
    openPaymentModal(id);
}

document.getElementById('paymentForm').addEventListener('submit', async e => {
    e.preventDefault();
    const form = e.target;
    const payload = Object.fromEntries(new FormData(form));
    const method = payload.id ? 'PUT' : 'POST';
    const url = payload.id ? `${URLROOT}/maestro/payment/${payload.id}` : `${URLROOT}/maestro/payment`;
    const resp = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    if (resp.ok) {
        closePaymentModal();
        loadPayments();
    } else {
        alert('Error al guardar el pago');
    }
});

async function deletePayment(id) {
    if (!confirm('¿Eliminar este pago?')) return;
    const resp = await fetch(`${URLROOT}/maestro/payment/${id}`, { method: 'DELETE' });
    if (resp.ok) loadPayments();
    else alert('Error al eliminar');
}

loadPayments();
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
