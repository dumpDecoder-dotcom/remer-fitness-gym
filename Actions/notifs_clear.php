<?php
// Actions/notifs_clear.php
session_start();
require_once __DIR__ . '/dbconnect.php';
header('Content-Type: application/json');

$uid  = (int)($_SESSION['user_id'] ?? 0);
$role = $_SESSION['role'] ?? '';

if ($uid <= 0 || !in_array($role, ['Member','Staff','Admin'], true)) {
  echo json_encode(['success' => false, 'error' => 'Unauthorized']);
  exit;
}

$possibleTables  = ['notifications', 'member_notifications', 'staff_notifications', 'admin_notifications'];
$possibleColumns = ['user_id', 'member_id', 'staff_id', 'admin_id', 'recipient_id'];

$totalDeleted = 0;

/* helpers */
function table_exists(mysqli $conn, string $tbl): bool {
  $db = $conn->real_escape_string($conn->query("SELECT DATABASE()")->fetch_row()[0]);
  $stmt = $conn->prepare("
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = ? AND table_name = ?
    LIMIT 1
  ");
  $stmt->bind_param('ss', $db, $tbl);
  $stmt->execute();
  $res = $stmt->get_result();
  $ok  = (bool)($res && $res->num_rows);
  $stmt->close();
  return $ok;
}

function column_exists(mysqli $conn, string $tbl, string $col): bool {
  $db = $conn->real_escape_string($conn->query("SELECT DATABASE()")->fetch_row()[0]);
  $stmt = $conn->prepare("
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = ? AND table_name = ? AND column_name = ?
    LIMIT 1
  ");
  $stmt->bind_param('sss', $db, $tbl, $col);
  $stmt->execute();
  $res = $stmt->get_result();
  $ok  = (bool)($res && $res->num_rows);
  $stmt->close();
  return $ok;
}

foreach ($possibleTables as $tbl) {
  if (!table_exists($conn, $tbl)) continue;

  // which recipient column?
  $recipientCol = null;
  foreach ($possibleColumns as $col) {
    if (column_exists($conn, $tbl, $col)) { $recipientCol = $col; break; }
  }
  if (!$recipientCol) continue;

  // build WHERE: recipient + "read" condition (+ optional role/type)
  $where = ["`$recipientCol` = ?"];
  $types = "i";
  $args  = [$uid];

  // role column if present
  $roleCol = null;
  if (column_exists($conn, $tbl, 'role'))        $roleCol = 'role';
  elseif (column_exists($conn, $tbl, 'user_role')) $roleCol = 'user_role';
  elseif (column_exists($conn, $tbl, 'type'))      $roleCol = 'type';

  if ($roleCol) {
    $where[] = "`$roleCol` = ?";
    $types  .= "s";
    $args[]  = $role;
  }

  // "read" flag detection
  if (column_exists($conn, $tbl, 'is_read')) {
    $where[] = "`is_read` = 1";
  } elseif (column_exists($conn, $tbl, 'read_flag')) {
    $where[] = "`read_flag` = 1";
  } elseif (column_exists($conn, $tbl, 'read_at')) {
    $where[] = "`read_at` IS NOT NULL";
  } elseif (column_exists($conn, $tbl, 'status')) {
    // handles enum/text variants (read/seen)
    $where[] = "LOWER(`status`) IN ('read','seen')";
  } else {
    // No readable flag found — skip this table to avoid deleting unread
    continue;
  }

  $sql = "DELETE FROM `$tbl` WHERE " . implode(' AND ', $where);
  if ($stmt = $conn->prepare($sql)) {
    // bind dynamic params
    $stmt->bind_param($types, ...$args);
    $stmt->execute();
    $totalDeleted += max(0, (int)$stmt->affected_rows);
    $stmt->close();
  }
}

echo json_encode(['success' => true, 'deleted' => $totalDeleted]);
