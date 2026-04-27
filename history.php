<?php 
require 'db.php'; 

// Check employee_id exists
if (!isset($_GET['employee_id']) || empty($_GET['employee_id'])) {
    echo "<script>location.href='dashboard.php';</script>";
    exit;
}

$employee_id = intval($_GET['employee_id']);

// Get current employee info
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    $stmt->close();
    $conn->close();
    echo "<script>location.href='dashboard.php';</script>";
    exit;
}

$employee = $res->fetch_assoc();
$stmt->close();

// Get history records
$hist = $conn->prepare("SELECT * FROM employee_history WHERE employee_id = ? ORDER BY id DESC");
$hist->bind_param("i", $employee_id);
$hist->execute();
$historyResult = $hist->get_result();
$historyRecords = $historyResult->fetch_all(MYSQLI_ASSOC);
$hist->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Employee History</title>
    <style>
        body { font-family: Arial, sans-serif; background: #fff; margin: 0; padding: 0; }
        .header { padding: 20px 40px; font-size: 28px; font-weight: bold; display: flex; align-items: center; gap: 10px; }
        .print-icon { width: 24px; height: 24px; cursor: pointer; vertical-align: middle; }
        .button-container { padding: 0 40px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .left-buttons { display: flex; gap: 10px; } .right-buttons { display: flex; }
        .button { padding: 8px 16px; border: none; border-radius: 20px; cursor: pointer; font-weight: bold; transition: background-color 0.3s ease; }
        .update { background-color: #00cc00; color: white; border: 2px solid black; }
        .update:hover { background-color: darkgreen; }
        .delete { background-color: #ff0000; color: white; border: 2px solid black; }
        .delete:hover { background-color: darkred; }
        .back { background-color: gray; color: white; border: 2px solid black; }
        .back:hover { background-color: #444; }
        .table-wrapper { width: 95%; margin: 0 auto 40px auto; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th, td { padding: 10px; text-align: center; border: 1px solid #ccc; font-size: 14px; }
        th { background: #f2f2f2; } tr:nth-child(even) { background: #fafafa; } tr:hover { background: #f5faff; }
        .edit-btn { background-color: #007bff; color: white;  border: 2px solid black; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-weight: bold; transition: 0.2s; }
        .edit-btn:hover {  background-color: #0056b3; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: rgba(0,0,0,0.5); display: none; justify-content: center; align-items: center; z-index: 1000; padding: 20px; }
        .modal-box { background-color: white; padding: 30px 40px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
        .modal-box p { font-size: 20px; margin-bottom: 20px; font-weight: bold; text-align: center; }
        .modal-buttons { display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; }
        .modal-button { padding: 10px 20px; font-size: 14px; border: none; border-radius: 50px; cursor: pointer; font-weight: bold; transition: background-color 0.3s ease; }
        .modal-button.delete { background-color: red; border: 2px solid black; color: white; }
        .modal-button.delete:hover { background-color: darkred; }
        .modal-button.cancel { background-color: darkgray; border: 2px solid black; color: white; }
        .modal-button.cancel:hover { background-color: gray; }
        .modal-button.save { background-color: #00cc00; border: 2px solid black; color: white; }
        .modal-button.save:hover { background-color: darkgreen; }
        .modal-box form { display: flex; flex-direction: column; gap: 12px; }
        .modal-box label { font-weight: bold; text-align: left; }
        .required { color: red; }
        .modal-box input { padding: 8px; border-radius: 5px; border: 1px solid #ccc; font-size: 14px; }
        .readonly { background: #f5f5f5; color: #555; }
        .error { color: red; font-size: 12px; margin-top: -8px; margin-bottom: 5px; }
        .toast { visibility: hidden; min-width: 250px; color: white; text-align: center; border-radius: 8px; padding: 14px 18px; position: fixed; z-index: 2000; bottom: 20px; right: 20px; font-size: 16px; box-shadow: 0px 4px 8px rgba(0,0,0,0.3); opacity: 0; transition: opacity 0.5s ease, bottom 0.5s ease; }
        .toast.show { visibility: visible; opacity: 1; bottom: 40px; }
        @media (max-width: 600px) { .button-container { justify-content: flex-start; flex-wrap: nowrap; gap: 10px; } .left-buttons, .right-buttons { flex-direction: row; gap: 10px; } }
        @media print { @page { size: auto; margin: 10mm; } body { background: #fff; margin: 0; padding: 0; } .button-container, .print-icon, .modal-overlay, .toast { display: none !important; } .table-wrapper { overflow: visible !important; } table { page-break-inside: auto; width: 100%; max-width: 100%; table-layout: auto; } th, td { font-size: 11px; padding: 4px; word-wrap: break-word; } tr { page-break-inside: avoid; page-break-after: auto; } @media print and (orientation:portrait) { body { transform: scale(0.85); transform-origin: top left; } } }
    </style>
</head>
<body>

<div class="header">
    <?php 
    $miWithDot = rtrim($employee['middle_initial'], '.') . '.';
    echo htmlspecialchars($employee['last_name']." ".$employee['first_name']." ".$miWithDot); 
    ?>
    <img src="printer_icon.jpg" alt="Print" class="print-icon" onclick="window.print()">
</div>

<div class="button-container">
    <div class="left-buttons">
        <button class="button update" onclick="showUpdateModal()">Update</button>
        <button class="button delete" onclick="showDeleteModal()">Delete</button>
    </div>
    <div class="right-buttons">
        <button class="button back" onclick="window.history.back()">Back</button>
    </div>
</div>

<!-- Current record -->
<h3 style="padding-left:40px;">Current Record</h3>
<div class="table-wrapper">
    <table>
        <tr>
            <th>eBCS</th><th>Bank Conf.</th><th>Paid On</th><th>Receipt</th><th>Due Month</th>
            <th>BPNO</th><th>Last Name</th><th>First Name</th><th>MI</th>
            <th>Salary</th><th>PS</th><th>GS</th><th>EC</th>
        </tr>
        <tr>
            <td><?= htmlspecialchars($employee['ebcs_transaction']); ?></td>
            <td><?= htmlspecialchars($employee['bank_confirmation']); ?></td>
            <td><?= htmlspecialchars($employee['paid_on']); ?></td>
            <td><?= htmlspecialchars($employee['receipt_no']); ?></td>
            <td><?= htmlspecialchars($employee['due_month']); ?></td>
            <td><?= htmlspecialchars($employee['bpno']); ?></td>
            <td><?= htmlspecialchars($employee['last_name']); ?></td>
            <td><?= htmlspecialchars($employee['first_name']); ?></td>
            <td><?= htmlspecialchars(str_replace('.', '', $employee['middle_initial'])); ?></td>
            <td><?= number_format($employee['salary'],2); ?></td>
            <td><?= number_format($employee['ps'],2); ?></td>
            <td><?= number_format($employee['gs'],2); ?></td>
            <td><?= number_format($employee['ec'],2); ?></td>
        </tr>
    </table>
</div>

<!-- Old Record History -->
<h3 style="padding-left:40px;">Old Records</h3>
<div class="table-wrapper">
    <table>
        <tr>
            <th>eBCS</th>
            <th>Bank Conf.</th>
            <th>Paid On</th>
            <th>Receipt</th>
            <th>Due Month</th>
            <th>Salary</th>
            <th>PS</th>
            <th>GS</th>
            <th>EC</th>
            <th>Saved On</th>
            <th>Action</th> <!-- ✅ Added Action column -->
        </tr>
        <?php if (count($historyRecords) > 0): ?>
            <?php foreach ($historyRecords as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['ebcs_transaction']); ?></td>
                    <td><?= htmlspecialchars($row['bank_confirmation']); ?></td>
                    <td><?= htmlspecialchars($row['paid_on']); ?></td>
                    <td><?= htmlspecialchars($row['receipt_no']); ?></td>
                    <td><?= htmlspecialchars($row['due_month']); ?></td>
                    <td><?= number_format($row['salary'], 2); ?></td>
                    <td><?= number_format($row['ps'], 2); ?></td>
                    <td><?= number_format($row['gs'], 2); ?></td>
                    <td><?= number_format($row['ec'], 2); ?></td>
                    <td><?= htmlspecialchars($row['created_at'] ?? ''); ?></td>
                    <td>
                        <!-- ✅ Added Edit button with onclick -->
                        <button class="edit-btn"
                            onclick='editHistory(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                            Edit
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="11">No history found.</td></tr>
        <?php endif; ?>
    </table>
</div>


<!-- Delete Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <p>Are you sure you want to delete this employee?</p>
        <div class="modal-buttons">
            <button class="modal-button delete" onclick="deleteEmployee(<?= intval($employee['id']); ?>)">Yes</button>
            <button class="modal-button cancel" onclick="closeModal()">No</button>
        </div>
    </div>
</div>

<!-- Update Modal -->
<div class="modal-overlay" id="updateModal">
    <div class="modal-box">
        <p>Update Current Record Employee</p>
        <form id="updateForm">
            <label>BPNO:</label>
            <input type="text" name="bpno" value="<?= htmlspecialchars($employee['bpno']); ?>" readonly class="readonly" />
            <label>Last Name:</label>
            <input type="text" value="<?= htmlspecialchars($employee['last_name']); ?>" readonly class="readonly" />
            <label>First Name:</label>
            <input type="text" value="<?= htmlspecialchars($employee['first_name']); ?>" readonly class="readonly" />
            <label>Middle Initial:</label>
            <input type="text" value="<?= htmlspecialchars(str_replace('.', '', $employee['middle_initial'])); ?>" readonly class="readonly" />
            
            <label>eBCS Transaction: <span class="required">*</span></label>
            <input type="number" name="ebcs_transaction" value="<?= htmlspecialchars($employee['ebcs_transaction']); ?>" oninput="this.value=this.value.replace(/[^0-9]/g,'')" />
            <div id="ebcsError" class="error"></div>

            <label>Bank Confirmation: <span class="required">*</span></label>
            <input type="number" name="bank_confirmation" value="<?= htmlspecialchars($employee['bank_confirmation']); ?>" oninput="this.value=this.value.replace(/[^0-9]/g,'')" />
            <div id="bankError" class="error"></div>

            <label>Paid On: <span class="required">*</span></label>
            <input type="date" name="paid_on" value="<?= htmlspecialchars($employee['paid_on']); ?>" />
            <div id="paidOnError" class="error"></div>

            <label>Receipt No: <span class="required">*</span></label>
            <input type="number" name="receipt_no" value="<?= htmlspecialchars($employee['receipt_no']); ?>" oninput="this.value=this.value.replace(/[^0-9]/g,'')" />
            <div id="receiptError" class="error"></div>

            <label>Due Month: <span class="required">*</span></label>
            <input type="month" name="due_month" value="<?= htmlspecialchars($employee['due_month']); ?>" />
            <div id="dueMonthError" class="error"></div>

            <label>Basic Salary: <span class="required">*</span></label>
            <input type="number" name="basic_salary" step="0.01" value="<?= htmlspecialchars($employee['salary']); ?>" />
            <div id="salaryError" class="error"></div>

            <label>PS: <span class="required">*</span></label>
            <input type="number" name="ps" step="0.01" value="<?= htmlspecialchars($employee['ps']); ?>" />
            <div id="psError" class="error"></div>

            <label>GS: <span class="required">*</span></label>
            <input type="number" name="gs" step="0.01" value="<?= htmlspecialchars($employee['gs']); ?>" />
            <div id="gsError" class="error"></div>

             <label>EC: <span class="required">*</span></label>
            <input type="number" step="0.01" name="ec" value="<?= htmlspecialchars($employee['ec']); ?>" />
            <div id="ecError" class="error"></div>

            <div class="modal-buttons">
                <button type="button" class="modal-button save" onclick="validateAndUpdate(<?= intval($employee['id']); ?>)">Update</button>
                <button type="button" class="modal-button cancel" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit History Modal -->
<div class="modal-overlay" id="editHistoryModal">
    <div class="modal-box">
        <p>Edit Old Record Employee</p>
        <form id="editHistoryForm">
            <input type="hidden" name="history_id">

            <label>eBCS Transaction: <span class="required">*</span></label>
            <input type="number" name="ebcs_transaction" oninput="this.value=this.value.replace(/[^0-9]/g,'')" required>
            <div id="editEbcsError" class="error"></div>

            <label>Bank Confirmation: <span class="required">*</span></label>
            <input type="number" name="bank_confirmation" oninput="this.value=this.value.replace(/[^0-9]/g,'')" required>
            <div id="editBankError" class="error"></div>

            <label>Paid On: <span class="required">*</span></label>
            <input type="date" name="paid_on" required>
            <div id="editPaidOnError" class="error"></div>

            <label>Receipt No: <span class="required">*</span></label>
            <input type="number" name="receipt_no" oninput="this.value=this.value.replace(/[^0-9]/g,'')" required>
            <div id="editReceiptError" class="error"></div>

            <label>Due Month: <span class="required">*</span></label>
            <input type="month" name="due_month" required>
            <div id="editDueMonthError" class="error"></div>

            <label>Salary: <span class="required">*</span></label>
            <input type="number" name="salary" step="0.01" required>
            <div id="editSalaryError" class="error"></div>

            <label>PS: <span class="required">*</span></label>
            <input type="number" name="ps" step="0.01" required>
            <div id="editPsError" class="error"></div>

            <label>GS: <span class="required">*</span></label>
            <input type="number" name="gs" step="0.01" required>
            <div id="editGsError" class="error"></div>

            <label>EC: <span class="required">*</span></label>
            <input type="number" name="ec" step="0.01" required>
            <div id="editEcError" class="error"></div>

            <div class="modal-buttons">
                <button type="button" class="modal-button save" onclick="saveHistoryEdit()">Save</button>
                <button type="button" class="modal-button cancel" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="toast"></div>

<script>
function showDeleteModal() { document.getElementById('deleteModal').style.display = 'flex'; }
function showUpdateModal() { document.getElementById('updateModal').style.display = 'flex'; }
function closeModal(){ ['deleteModal','updateModal','editHistoryModal'].forEach(id=>{  const el=document.getElementById(id);  if(el) el.style.display='none'; }); }

function showToast(msg, type='success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.backgroundColor = (type==='error') ? '#e74c3c' : '#4CAF50';
    t.classList.add('show');
    setTimeout(()=>t.classList.remove('show'),1800);
}

function deleteEmployee(id) {
    fetch('delete_employee.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'employee_id='+encodeURIComponent(id) })
    .then(r=>r.json()).then(d=>{
        closeModal();
        if(d.success){
            showToast("Deleted Employee Successfully!", "error");
            if(window.opener && !window.opener.closed) window.opener.location.reload();
            setTimeout(()=>{ window.location.href='dashboard.php'; },2000);
        } else showToast(d.message||'Delete failed', 'error');
    });
}

function validateAndUpdate(id) {
    let valid = true;

    // Fields
    const ebcs = document.querySelector('[name="ebcs_transaction"]');
    const bank = document.querySelector('[name="bank_confirmation"]');
    const receipt = document.querySelector('[name="receipt_no"]');
    const paidOn = document.querySelector('[name="paid_on"]');
    const dueMonth = document.querySelector('[name="due_month"]');
    const salary = document.querySelector('[name="basic_salary"]');
    const ps = document.querySelector('[name="ps"]');
    const gs = document.querySelector('[name="gs"]');
    const ec = document.querySelector('[name="ec"]');

    // Error fields
    const ebcsError = document.getElementById('ebcsError');
    const bankError = document.getElementById('bankError');
    const receiptError = document.getElementById('receiptError');
    const paidOnError = document.getElementById('paidOnError');
    const dueMonthError = document.getElementById('dueMonthError');
    const salaryError = document.getElementById('salaryError');
    const psError = document.getElementById('psError');
    const gsError = document.getElementById('gsError');
    const ecError = document.getElementById('ecError');

    // Reset all errors
    ebcsError.textContent = "";
    bankError.textContent = "";
    receiptError.textContent = "";
    paidOnError.textContent = "";
    dueMonthError.textContent = "";
    salaryError.textContent = "";
    psError.textContent = "";
    gsError.textContent = "";
    ecError.textContent = "";

    // Validate each field
    if (ebcs.value.trim() === "") { ebcsError.textContent = "eBCS Transaction is required."; valid = false; }
    if (bank.value.trim() === "") { bankError.textContent = "Bank Confirmation is required."; valid = false; }
    if (receipt.value.trim() === "") { receiptError.textContent = "Receipt No is required."; valid = false; }
    if (paidOn.value.trim() === "") { paidOnError.textContent = "Paid On date is required."; valid = false; }
    if (dueMonth.value.trim() === "") { dueMonthError.textContent = "Due Month is required."; valid = false; }
    if (salary.value.trim() === "") { salaryError.textContent = "Basic Salary is required."; valid = false; }
    if (ps.value.trim() === "") { psError.textContent = "PS is required."; valid = false; }
    if (gs.value.trim() === "") { gsError.textContent = "GS is required."; valid = false; }
    if (ec.value.trim() === "") { ecError.textContent = "EC is required."; valid = false; }

    if (!valid) return; // Stop submission if any field is blank

    // Submit the update
    const form = document.getElementById('updateForm');
    const fd = new FormData(form);
    fd.append('employee_id', id);

    fetch('update_employee.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            closeModal();
            if (d.success) {
                showToast("Updated Employee Successfully!", "success");
                setTimeout(() => { window.location.reload(); }, 2000);
            } else {
                showToast(d.message || 'Update failed', 'error');
            }
        });
}

// EDIT HISTORY FUNCTIONS
function editHistory(row) {
    const form = document.getElementById('editHistoryForm');
    form.history_id.value = row.id;
    form.ebcs_transaction.value = row.ebcs_transaction;
    form.bank_confirmation.value = row.bank_confirmation;
    form.paid_on.value = row.paid_on;
    form.receipt_no.value = row.receipt_no;
    form.due_month.value = row.due_month;
    form.salary.value = row.salary;
    form.ps.value = row.ps;
    form.gs.value = row.gs;
    form.ec.value = row.ec;

    // Clear previous error messages
    ['editEbcsError','editBankError','editPaidOnError','editReceiptError','editDueMonthError',
     'editSalaryError','editPsError','editGsError','editEcError'].forEach(id => {
        const el = document.getElementById(id);
        if(el) el.textContent = '';
    });

    document.getElementById('editHistoryModal').style.display = 'flex';
}

function saveHistoryEdit() {
    const form = document.getElementById('editHistoryForm');

    // Field references
    const ebcs = form.ebcs_transaction;
    const bank = form.bank_confirmation;
    const paidOn = form.paid_on;
    const receipt = form.receipt_no;
    const dueMonth = form.due_month;
    const salary = form.salary;
    const ps = form.ps;
    const gs = form.gs;
    const ec = form.ec;

    // Error elements
    const ebcsError = document.getElementById('editEbcsError');
    const bankError = document.getElementById('editBankError');
    const paidOnError = document.getElementById('editPaidOnError');
    const receiptError = document.getElementById('editReceiptError');
    const dueMonthError = document.getElementById('editDueMonthError');
    const salaryError = document.getElementById('editSalaryError');
    const psError = document.getElementById('editPsError');
    const gsError = document.getElementById('editGsError');
    const ecError = document.getElementById('editEcError');

    // Reset errors
    [ebcsError, bankError, paidOnError, receiptError, dueMonthError, salaryError, psError, gsError, ecError].forEach(e => e.textContent = '');

    let valid = true;

    // Validation
    if(ebcs.value.trim() === '') { ebcsError.textContent = 'eBCS Transaction is required.'; valid = false; }
    if(bank.value.trim() === '') { bankError.textContent = 'Bank Confirmation is required.'; valid = false; }
    if(paidOn.value.trim() === '') { paidOnError.textContent = 'Paid On date is required.'; valid = false; }
    if(receipt.value.trim() === '') { receiptError.textContent = 'Receipt No is required.'; valid = false; }
    if(dueMonth.value.trim() === '') { dueMonthError.textContent = 'Due Month is required.'; valid = false; }
    if(salary.value.trim() === '') { salaryError.textContent = 'Salary is required.'; valid = false; }
    if(ps.value.trim() === '') { psError.textContent = 'PS is required.'; valid = false; }
    if(gs.value.trim() === '') { gsError.textContent = 'GS is required.'; valid = false; }
    if(ec.value.trim() === '') { ecError.textContent = 'EC is required.'; valid = false; }

    if(!valid) return; // Stop submission if any field is invalid

    // Submit the update
    const fd = new FormData(form);

    fetch('update_history.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            closeModal();
            if(d.success){
                showToast("Edit Old Record Successfully!");
                setTimeout(() => { window.location.reload(); }, 1500);
            } else {
                showToast(d.message || 'Failed to update', 'error');
            }
        })
        .catch(err => {
            showToast('An error occurred', 'error');
            console.error(err);
        });
}

</script>

</body>
</html>
