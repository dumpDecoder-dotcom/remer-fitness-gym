<?php
/**
 * Unified role-aware notifications endpoint
 * Modes:
 *   - list       : returns {unread_count, unread[], read[]}
 *   - mark_one   : body {id} or {notification_id}  -> marks one as read
 *   - mark_all   : marks all visible as read
 *   - clear      : (Member only) deletes user's visible notifications
 */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$mode = $_GET['mode'] ?? 'list';
$user_id = (int)$_SESSION['user_id'];
$role    = $_SESSION['role'] ?? 'Member';

/* ---------- Helpers ---------- */
function base_where_clause() {
    // per-user OR targeted by this role OR targeted to 'all'
    return "( (user_id = ? AND (target_user_type IS NULL OR target_user_type = '')) OR target_user_type = ? OR target_user_type = 'all' )";
}
function extra_filter_for_role($role) {
    // For Admin/Staff: hide login/logout noise
    if (in_array($role, ['Admin','Staff'], true)) {
        return " AND (type NOT IN ('login','logout','member_login','member_logout')) ";
    }
    return "";
}

/* ---------- LIST ---------- */
if ($mode === 'list') {
    $where = base_where_clause() . extra_filter_for_role($role);
    $sql = "
        SELECT id, user_id, type, title, message, is_read, created_at
          FROM notifications
         WHERE $where
      ORDER BY created_at DESC, id DESC
         LIMIT 200
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $user_id, $role);
    $stmt->execute();
    $res = $stmt->get_result();

    $unread = [];
    $read = [];
    while ($row = $res->fetch_assoc()) {
        if ((int)$row['is_read'] === 1) $read[] = $row;
        else $unread[] = $row;
    }
    echo json_encode([
        'success'      => true,
        'unread_count' => count($unread),
        'unread'       => $unread,
        'read'         => $read,
    ]);
    exit;
}

/* ---------- MARK ONE ---------- */
if ($mode === 'mark_one') {
    $payload = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($payload['id'] ?? $payload['notification_id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid id']); exit;
    }

    // ensure the user can see this notif (same filters as list)
    $visibleWhere = base_where_clause() . extra_filter_for_role($role);

    $sql = "
        UPDATE notifications
           SET is_read = 1
         WHERE id = ?
           AND id IN (
                SELECT id FROM (
                    SELECT id FROM notifications WHERE $visibleWhere
                ) x
           )
    ";
    $stmt = $conn->prepare($sql);
    // bind order: (id) for outer WHERE, then (user_id, role) inside subquery
    // but MySQL won’t allow reusing placeholders across scopes, so we bind in the exact order used:
    // First the subquery (user_id, role), then the outer id — to keep it simple, swap order in SQL:
    $sql = "
        UPDATE notifications n
           JOIN (
               SELECT id FROM notifications WHERE $visibleWhere
           ) v ON v.id = n.id
           SET n.is_read = 1
         WHERE n.id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isi', $user_id, $role, $id);
    $ok = $stmt->execute();

    echo json_encode(['success' => (bool)$ok]);
    exit;
}

/* ---------- MARK ALL ---------- */
if ($mode === 'mark_all') {
    $where = base_where_clause() . extra_filter_for_role($role);
    $sql = "
        UPDATE notifications n
           JOIN (
               SELECT id FROM notifications
               WHERE $where
                 AND is_read = 0
           ) v ON v.id = n.id
           SET n.is_read = 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $user_id, $role);
    $ok = $stmt->execute();

    echo json_encode(['success' => (bool)$ok]);
    exit;
}

/* ---------- CLEAR (Member only) ---------- */
if ($mode === 'clear') {
    if ($role !== 'Member') {
        echo json_encode(['success' => false, 'error' => 'Clear is for Member only']); exit;
    }
    $where = "( (user_id = ? AND (target_user_type IS NULL OR target_user_type = '')) OR target_user_type = 'Member' OR target_user_type = 'all' )";
    $sql = "
        DELETE n FROM notifications n
        JOIN (
            SELECT id FROM notifications WHERE $where
        ) v ON v.id = n.id
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $ok = $stmt->execute();

    echo json_encode(['success' => (bool)$ok]);
    exit;
}

/* ---------- Fallback ---------- */
echo json_encode(['success' => false, 'error' => 'Invalid mode']);
