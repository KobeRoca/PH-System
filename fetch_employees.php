<?php
require 'db.php';

$sql = "SELECT * FROM employees ORDER BY id ASC";
$result = $conn->query($sql);

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