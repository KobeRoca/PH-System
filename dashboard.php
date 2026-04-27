<?php
session_start();

// ✅ BLOCK ACCESS IF NOT LOGGED IN
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit();
}

require 'db.php';

// Handle search input
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($searchTerm !== '') {
    $stmt = $conn->prepare("SELECT * FROM employees WHERE bpno = ? ORDER BY id DESC");
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT * FROM employees ORDER BY id ASC";
    $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>List of Employee</title>
  <style>
    body { font-family: Arial, sans-serif; background-color: #f9f9f9; margin: 0; padding: 20px; }
    h1 { text-align: center; font-size: 28px; margin-bottom: 20px; }

    .top-bar { 
      display: grid; 
      grid-template-columns: 1fr auto 1fr; 
      align-items: center; 
      margin-bottom: 20px; 
      height: 60px; 
      gap: 10px;
    }

    .search-box { justify-self: center; }
    .search-box input {
      padding: 8px 12px 8px 36px;
      font-size: 14px;
      border: 1px solid #ccc;
      border-radius: 20px;
      width: 250px;
      box-shadow: 0px 2px 4px rgba(0,0,0,0.1);
      background: #fff url('search_icon.png') no-repeat 10px center;
      background-size: 16px 16px;
    }

    .button-group { justify-self: end; display: flex; gap: 10px; }
    .logout-btn { background-color: darkgray; color: white; border: 2px solid black; padding: 8px 16px; border-radius: 20px; cursor: pointer; transition: background-color 0.3s ease; }
    .logout-btn:hover,.logout-btn:focus{background-color: gray; outline: none;}
    .add-btn { background-color: #4CAF50; color: white; border: none; padding: 8px 14px; border-radius: 50%; font-size: 20px; cursor: pointer; }

    @media (max-width: 768px) {
      .top-bar {
        display: flex;
        flex-direction: column;
        align-items: center;
        height: auto;
        gap: 15px;
      }
      .search-box { width: 100%; display: flex; justify-content: center; }
      .search-box input { width: 90%; }
      .button-group { width: 100%; justify-content: center; }
    }

    .table-container { overflow-x: auto; margin-top: 15px; }
    table { width: 100%; border-collapse: collapse; background-color: white; box-shadow: 0px 2px 6px rgba(0,0,0,0.2); }
    th, td { padding: 12px; text-align: center; border: 1px solid #ddd; font-size: 14px; }
    th { font-weight: bold; white-space: nowrap; }
    tr:nth-child(even) { background-color: #f2f2f2; }

    .submit-btn { background-color: #4CAF50; color: white; padding: 6px 14px; border: 2px solid black; border-radius: 50px; font-size: 13px; cursor: pointer; text-decoration: none; transition: background-color 0.3s ease; display: inline-block; }
    .submit-btn:hover { background-color: #45a049; }

    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
    .modal-content { background-color: white; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); padding: 20px; border: 1px solid #888; width: 95%; max-width: 500px; max-height: 90vh; overflow-y: auto; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
    .modal-content h2 { font-size: 20px; margin-bottom: 24px; text-align: center; font-weight: bold; }
    .modal-buttons { display: flex; justify-content: center; gap: 20px; }

    .close-btn { background-color: gray; color: white; padding: 6px 20px; border: 2px solid black; border-radius: 50px; cursor: pointer; font-size: 14px; }
    .close-btn:hover { background-color: #555; }

    .form-group { margin-bottom: 12px; }
    .form-group label { display: block; margin-bottom: 6px; font-weight: bold; font-size: 14px; }
    .required-star { color: red; }
    .form-group input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; }

    .toast { visibility: hidden; min-width: 200px; color: white; text-align: center; border-radius: 8px; padding: 12px 16px; position: fixed; z-index: 2000; bottom: 20px; right: 20px; font-size: 16px; box-shadow: 0px 4px 8px rgba(0,0,0,0.3); opacity: 0; transition: opacity 0.5s ease, bottom 0.5s ease; }
    .toast.show { visibility: visible; opacity: 1; bottom: 40px; }

    .modal-button { padding: 10px 20px; font-size: 14px; border: none; border-radius: 50px; cursor: pointer; font-weight: bold; transition: background-color 0.3s ease; }
    .modal-button.delete { background-color: red; border: 2px solid black; color: white; }
    .modal-button.delete:hover { background-color: darkred; }
    .modal-button.cancel { background-color: darkgray; border: 2px solid black; color: white; }
    .modal-button.cancel:hover { background-color: gray; }

    .submit-btn-small { background-color: blue; color: white; padding: 10px 20px; border: 2px solid black; border-radius: 50px; cursor: pointer; font-weight: bold; transition: background-color 0.3 ease; }
    .submit-btn-small:hover { background-color: darkblue; }

  /* ✅ ADD THIS BELOW EVERYTHING INSIDE STYLE */
  #loadingOverlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    z-index: 9999;
    text-align: center;
    color: white;
    font-size: 18px;
  }

  #loadingOverlay .spinner {
    margin: 20% auto 10px;
    width: 60px;
    height: 60px;
    border: 6px solid #f3f3f3;
    border-top: 6px solid #4CAF50;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
  </style>
</head>
<body>  

<h1>List of Employee</h1>

<div class="top-bar">
  <div></div>
  <div class="search-box">
    <form method="GET" action="dashboard.php">
      <input type="text" name="search" id="searchInput" placeholder="Search BPNO..." value="<?php echo htmlspecialchars($searchTerm); ?>" onkeypress="handleSearch(event)"/>
    </form>
  </div>
  <div class="button-group">
    <img src="folder_icon_delete.jpg" alt="Folder Icon Delete" style="width:30px; height:30px; vertical-align:middle; cursor:pointer;" onclick="openDeleteOptionsModal()">
    <img src="folder_icon.jpg" alt="Folder Icon" style="width:30px; height:30px; vertical-align:middle; cursor:pointer;" onclick="openUploadModal()">
    <button class="add-btn" onclick="showAddModal()">+</button>
    <button class="logout-btn" onclick="openLogoutModal()">Log Out</button>
  </div>
</div>

<div class="table-container" id="employeeTable">
  <table>
    <tr>
      <th>#</th>
      <th>eBCS Transaction</th>
      <th>Bank Confirmation</th>
      <th>Paid On</th>
      <th>Receipt No</th>
      <th>Due Month</th>
      <th>BPNO</th>
      <th>Last Name</th>
      <th>First Name</th>
      <th>MI</th>
      <th>Basic Monthly Salary</th>
      <th>PS</th>
      <th>GS</th>
      <th>EC</th>
      <th>History</th>
    </tr>
    <?php
    if ($result && $result->num_rows > 0) {
        $count = 1;
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $count++ . "</td>";
            echo "<td>" . htmlspecialchars($row['ebcs_transaction']) . "</td>";
            echo "<td>" . htmlspecialchars($row['bank_confirmation']) . "</td>";
            echo "<td>" . htmlspecialchars($row['paid_on']) . "</td>";
            echo "<td>" . htmlspecialchars($row['receipt_no']) . "</td>";
            echo "<td>" . htmlspecialchars($row['due_month']) . "</td>";
            echo "<td>" . htmlspecialchars($row['bpno']) . "</td>";
            echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['middle_initial']) . "</td>";
            echo "<td>" . number_format($row['salary'], 2) . "</td>";
            echo "<td>" . number_format($row['ps'], 2) . "</td>";
            echo "<td>" . number_format($row['gs'], 2) . "</td>";
            echo "<td>" . number_format($row['ec'], 2) . "</td>";
            echo '<td><a href="history.php?employee_id=' . urlencode($row['id']) . '" class="submit-btn">History</a></td>';
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='15'>No employees found.</td></tr>";
    }
    ?>
  </table>
</div>

<!-- Upload File Modal -->
<div id="uploadModal" class="modal">
  <div class="modal-content">
    <h2 style="text-align:center;">Upload File</h2>

    <form action="upload_file.php" method="POST" enctype="multipart/form-data" onsubmit="showLoading()">
      <div class="form-group">
        <label>Select File</label>
        <input type="file" name="uploaded_file" required>
      </div>

      <div class="modal-buttons">
        <button type="submit" class="submit-btn-small">Upload</button>
        <button type="button" class="close-btn" onclick="closeUploadModal()">Cancel</button>
      </div>
    </form>

  </div>
</div>

<!-- Add Employee Modal -->
<div id="addEmployeeModal" class="modal">
  <div class="modal-content">
    <h2>Add New Employee</h2>
    <form id="employeeForm">
      <!-- form fields (same as your original) -->
      <div class="form-group">
        <label for="lastName">Last Name <span class="required-star">*</span></label>
        <input type="text" id="lastName" placeholder="Enter Last Name" required pattern="[A-Za-z\s]+" title="Only letters are allowed"/>
      </div>
      <div class="form-group">
        <label for="firstName">First Name <span class="required-star">*</span></label>
        <input type="text" id="firstName" placeholder="Enter First Name" required pattern="[A-Za-z\s]+" title="Only letters are allowed"/>
      </div>
      <div class="form-group">
        <label for="middleInitial">Middle Initial <span class="required-star">*</span></label>
        <input type="text" id="middleInitial" maxlength="1" placeholder="Enter Middle Initial" required pattern="[A-Za-z]" title="Only one letter is allowed"/>
      </div>
      <div class="form-group">
        <label for="ebcsTransaction">eBCS Transaction <span class="required-star">*</span></label>
        <input type="number" id="ebcsTransaction" placeholder="Enter eBCS Transaction" required/>
      </div>
      <div class="form-group">
        <label for="bankConfirmation">Bank Confirmation <span class="required-star">*</span></label>
        <input type="number" id="bankConfirmation" placeholder="Enter Bank Confirmation" required/>
      </div>
      <div class="form-group">
        <label for="paidOn">Paid On <span class="required-star">*</span></label>
        <input type="date" id="paidOn" required/>
      </div>
      <div class="form-group">
        <label for="receiptNo">Receipt No <span class="required-star">*</span></label>
        <input type="number" id="receiptNo" placeholder="Enter Receipt Number" required/>
      </div>
      <div class="form-group">
        <label for="dueMonth">Due Month <span class="required-star">*</span></label>
        <input type="month" id="dueMonth" required/>
      </div>
      <div class="form-group">
        <label for="bpno">BPNO <span class="required-star">*</span></label>
        <input type="number" id="bpno" placeholder="Enter BPNO" required/>
      </div>
      <div class="form-group">
        <label for="salary">Salary <span class="required-star">*</span></label>
        <input type="number" id="salary" step="0.01" placeholder="Enter Salary" required/>
      </div>
      <div class="form-group">
        <label for="ps">PS <span class="required-star">*</span></label>
        <input type="number" id="ps" step="0.01" placeholder="Enter PS" required/>
      </div>
      <div class="form-group">
        <label for="gs">GS <span class="required-star">*</span></label>
        <input type="number" id="gs" step="0.01" placeholder="Enter GS" required/>
      </div>
      <div class="form-group">
        <label for="ec">EC <span class="required-star">*</span></label>
        <input type="number" id="ec" step="0.01" placeholder="Enter EC" required/>
      </div>
      <div class="modal-buttons">
        <button type="submit" class="submit-btn">Add</button>
        <button type="button" class="close-btn" onclick="hideAddModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>
</div>

<!-- ✅ ADD THIS HERE -->
<div id="deleteOptionsModal" class="modal">
  <div class="modal-content">
    <h2>Selected Delete Records</h2>

    <div class="form-group">
    <label>Select Month & Year</label>
    <input type="month" id="deleteMonth">
    <small id="monthError" style="color:red; display:none;">Please select a month & year</small>
  </div>

    <div class="modal-buttons">
      <button class="submit-btn-small" onclick="openDeleteSelectedModal()">Delete Selected</button>
      <button class="modal-button delete" onclick="openDeleteModal()">Delete All</button>
      <button class="close-btn" onclick="closeDeleteOptionsModal()">Cancel</button>
    </div>
  </div>
</div>

<!-- Delete All Modal -->
<div id="deleteModal" class="modal">
  <div class="modal-content">
    <h2>Are you sure you want to delete all records?</h2>
    <div class="modal-buttons">
      <button class="modal-button delete" onclick="confirmDeleteAll()">Yes</button>
      <button class="modal-button cancel" onclick="closeDeleteModal()">No</button>
    </div>
  </div>
</div>

<!-- Delete Selected Confirmation Modal -->
<div id="deleteSelectedModal" class="modal">
  <div class="modal-content">
    <h2>Are you sure you want to delete this file?</h2>

    <div class="modal-buttons">
      <button class="modal-button delete" onclick="confirmDeleteSelected()">Yes</button>
      <button class="modal-button cancel" onclick="closeDeleteSelectedModal()">Cancel</button>
    </div>
  </div>
</div>

<!-- Logout Modal -->
<div id="logoutModal" class="modal">
  <div class="modal-content">
    <h2>Are you sure you want to logout?</h2>
    <div class="modal-buttons">
      <button class="modal-button delete" onclick="confirmLogout()">Yes</button>
      <button class="modal-button cancel" onclick="closeLogoutModal()">No</button>
    </div>
  </div>
</div>

<!-- Toast -->
<div id="toast" class="toast">Employee Added Successfully!</div>

<!-- LOADING OVERLAY -->
<div id="loadingOverlay">
  <div class="spinner"></div>
  <p>Uploading file... please wait</p>
</div>

<?php if (isset($_SESSION['login_success'])): ?>
<script>
window.addEventListener("load", function () {
    showToast("<?= $_SESSION['login_success']; ?>", "success");
});
</script>
<?php unset($_SESSION['login_success']); endif; ?>

<script>
  function showLoading() { document.getElementById("loadingOverlay").style.display = "block"; }
  function closeUploadModal() { document.getElementById("uploadModal").style.display = "none"; }
  function openUploadModal() { document.getElementById("uploadModal").style.display = "block"; }
  function showAddModal() { document.getElementById('addEmployeeModal').style.display = 'block'; }
  function hideAddModal() { document.getElementById('addEmployeeModal').style.display = 'none'; }

  function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.style.backgroundColor = type === 'error' ? '#e74c3c' : '#4CAF50';
    toast.classList.add("show");
    setTimeout(() => { toast.classList.remove("show"); }, 1500);
  }

  function handleSearch(event) {
    if (event.key === "Enter") {
      event.preventDefault();
      const value = document.getElementById('searchInput').value.trim();
      window.location.href = `dashboard.php?search=${encodeURIComponent(value)}`;
    }
  }

  function openLogoutModal() { document.getElementById("logoutModal").style.display = "block"; }
  function closeLogoutModal() { document.getElementById("logoutModal").style.display = "none"; }
  function confirmLogout() { window.location.href = "index.php"; }

  // ✅ Placeholder animation safe fix
  window.addEventListener("load", function () {
    const searchInput = document.getElementById('searchInput');
    const placeholderText = "Search BPNO...";
    let index = 0;

    function animatePlaceholder() {
      if (!searchInput) return;
      searchInput.placeholder = placeholderText.substring(0, index + 1);
      index++;
      if (index >= placeholderText.length) index = 0;
      setTimeout(animatePlaceholder, 200);
    }
    animatePlaceholder();
  });

  // ✅ IMPORTANT: Refresh table WITHOUT reload
  function refreshEmployeeTable() {
    fetch('fetch_employees.php')
      .then(res => res.text())
      .then(data => {
        document.querySelector('#employeeTable table').innerHTML = `
          <tr>
            <th>#</th>
            <th>eBCS Transaction</th>
            <th>Bank Confirmation</th>
            <th>Paid On</th>
            <th>Receipt No</th>
            <th>Due Month</th>
            <th>BPNO</th>
            <th>Last Name</th>
            <th>First Name</th>
            <th>MI</th>
            <th>Basic Monthly Salary</th>
            <th>PS</th>
            <th>GS</th>
            <th>EC</th>
            <th>History</th>
          </tr>
        ` + data;
      });
  }

  // ✅ ADD EMPLOYEE (FIXED)
  document.getElementById('employeeForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const data = {
      lastName: document.getElementById('lastName').value.trim(),
      firstName: document.getElementById('firstName').value.trim(),
      middleInitial: document.getElementById('middleInitial').value.trim(),
      ebcsTransaction: document.getElementById('ebcsTransaction').value,
      bankConfirmation: document.getElementById('bankConfirmation').value,
      paidOn: document.getElementById('paidOn').value,
      receiptNo: document.getElementById('receiptNo').value,
      dueMonth: document.getElementById('dueMonth').value,
      bpno: document.getElementById('bpno').value,
      salary: document.getElementById('salary').value,
      ps: document.getElementById('ps').value,
      gs: document.getElementById('gs').value,
      ec: document.getElementById('ec').value
    };

    if (!/^[A-Za-z]$/.test(data.middleInitial)) {
      showToast("Middle Initial must be a single letter.", "error");
      return;
    }

    fetch('add_employee.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(response => {
      if (response.success) {
        showToast(response.message);
        hideAddModal();

        // ✅ THIS IS THE FIX (NO RELOAD)
        refreshEmployeeTable();

      } else {
        showToast(response.message, "error");
      }
    })
    .catch(err => {
      console.error(err);
      showToast("Unexpected error occurred.", "error");
    });
  });

  // ✅ DELETE MODAL
  function openDeleteModal() {
    closeDeleteOptionsModal();
    document.getElementById("deleteModal").style.display = "block";
  }

  function closeDeleteModal() {
    document.getElementById("deleteModal").style.display = "none";
  }

  // ✅ DELETE ALL (FIXED)
  function confirmDeleteAll() {
    fetch('delete_all.php', { method: 'POST' })
    .then(res => res.json())
    .then(response => {

      showToast(response.message, "error");

      // ❗ CLOSE BOTH MODALS
      closeDeleteModal();
      closeDeleteOptionsModal();

      if (response.success) {
        refreshEmployeeTable();
      }
    })
    .catch(error => {
      console.error(error);
      showToast("Error occurred.", "error");

      // ❗ ALSO CLOSE BOTH IN ERROR
      closeDeleteModal();
      closeDeleteOptionsModal();
    });
  }

  // ✅ 👉 ADD YOUR NEW CODE HERE (RIGHT AFTER confirmDeleteAll)

  function openDeleteSelectedModal() {
    const month = document.getElementById("deleteMonth").value;
    const monthError = document.getElementById("monthError");

    monthError.style.display = "none";

    if (!month) {
      monthError.style.display = "block";
      return;
    }

      // ✅ CLOSE the parent modal first (same as Delete All)
      closeDeleteOptionsModal();

    document.getElementById("deleteSelectedModal").style.display = "block";
  }

  function closeDeleteSelectedModal() {
    document.getElementById("deleteSelectedModal").style.display = "none";
  }

  function confirmDeleteSelected() {
    const month = document.getElementById("deleteMonth").value;

    fetch('delete_by_date.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ month })
    })
    .then(res => res.json())
    .then(response => {

      showToast("Records Deleted Successfully", "error");

      closeDeleteSelectedModal();
      closeDeleteOptionsModal();

      if (response.success) {
        refreshEmployeeTable();
        document.getElementById("deleteMonth").value = "";
      }
    })
    .catch(err => {
      console.error(err);
      showToast("Error occurred.", "error");
    });
  }

  function openDeleteOptionsModal() {
    document.getElementById("deleteMonth").value = "";

  // Hide error messages (if any)
    document.getElementById("monthError").style.display = "none";

  // Open modal
  document.getElementById("deleteOptionsModal").style.display = "block";
  }

  function closeDeleteOptionsModal() {
    document.getElementById("deleteOptionsModal").style.display = "none";
  }

  function deleteByFilter() {
    const month = document.getElementById("deleteMonth").value;
    const monthError = document.getElementById("monthError");

    // Reset error
    monthError.style.display = "none";

    if (!month) {
      monthError.style.display = "block";
      return;
    }

    fetch('delete_by_date.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ month }) // ✅ ONLY month
    })
    .then(res => res.json())
    .then(response => {

      showToast(response.message, "error"); // red

      if (response.success) {
        refreshEmployeeTable();

        // clear field
        document.getElementById("deleteMonth").value = "";

        closeDeleteOptionsModal();
      }
    })
    .catch(err => {
      console.error(err);
      showToast("Error occurred.", "error");
    });
  }

  // ✅ SESSION TOAST
  window.addEventListener("load", function () {
    <?php if (isset($_SESSION['upload_success'])): ?>
      showToast("<?= $_SESSION['upload_success']; ?>", "success");
    <?php unset($_SESSION['upload_success']); endif; ?>

    <?php if (isset($_SESSION['upload_error'])): ?>
      showToast("<?= $_SESSION['upload_error']; ?>", "error");
    <?php unset($_SESSION['upload_error']); endif; ?>
  });

</script>

<?php if (isset($_SESSION['upload_success'])): ?>
<script>
    alert("Uploading File Successfully!");
</script>
<?php unset($_SESSION['upload_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['upload_error'])): ?>
<script>
    alert("<?php echo $_SESSION['upload_error']; ?>");
</script>
<?php unset($_SESSION['upload_error']); ?>
<?php endif; ?>

</body>
</html>
