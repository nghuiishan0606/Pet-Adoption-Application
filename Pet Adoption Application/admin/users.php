<?php
require_once 'includes/admin_header.php';

$success = '';
$error = '';

// Handle Administrative Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $target_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if ($target_id) {
        // Prevent deleting or blocking yourself
        if ($target_id === (int)$_SESSION['admin_session']['id'] && ($action === 'delete' || $action === 'block' || $action === 'change_role')) {
            $error = 'You cannot perform administrative actions on your own active session account.';
        } else {
            try {
                if ($action === 'block') {
                    // Fetch current status
                    $st = $pdo->prepare("SELECT status FROM users WHERE id = ?");
                    $st->execute([$target_id]);
                    $curr_status = $st->fetchColumn();
                    $new_status = $curr_status === 'active' ? 'blocked' : 'active';
                    
                    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                    $stmt->execute([$new_status, $target_id]);
                    $success = "User status toggled to " . strtoupper($new_status) . " successfully!";
                }
                
                elseif ($action === 'delete') {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$target_id]);
                    $success = "User account deleted successfully!";
                }
                
                elseif ($action === 'change_role') {
                    $new_role = filter_input(INPUT_GET, 'role', FILTER_SANITIZE_SPECIAL_CHARS);
                    if ($new_role === 'admin' || $new_role === 'user') {
                        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                        $stmt->execute([$new_role, $target_id]);
                        $success = "User role updated to " . strtoupper($new_role) . " successfully!";
                    }
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Fetch all users
try {
    $stmt_u = $pdo->query("SELECT * FROM users ORDER BY id ASC");
    $users = $stmt_u->fetchAll();
} catch (PDOException $e) {
    $users = [];
}
?>

<?php if ($success): ?>
    <div style="background-color: #dcfce7; color: #15803d; border: 1px solid rgba(21,128,61,0.2); padding: 14px 20px; border-radius: var(--radius-sm); margin-bottom: 25px; font-weight: 600;">
        ✅ <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background-color: #fef2f2; color: #b91c1c; border: 1px solid rgba(185,28,28,0.2); padding: 14px 20px; border-radius: var(--radius-sm); margin-bottom: 25px; font-weight: 600;">
        ⚠️ <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="admin-table-container">
    <div class="admin-table-header">System User Accounts</div>
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email / Username</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($users) === 0): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--admin-text-muted);">No users found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): 
                        $formatted_id = sprintf("U-%04d", $u['id']);
                        $is_self = $u['id'] === (int)$_SESSION['admin_session']['id'];
                    ?>
                        <tr>
                            <td style="font-weight: 700;"><?= $formatted_id ?></td>
                            <td style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($u['name']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['phone']) ?></td>
                            <td>
                                <span class="role-badge <?= $u['role'] === 'admin' ? 'role-admin' : 'role-user' ?>">
                                    <?= htmlspecialchars($u['role']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?= $u['status'] === 'active' ? 'status-active' : 'status-blocked' ?>">
                                    <?= htmlspecialchars($u['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-btn-list">
                                    <!-- View Details (JS popup modal) -->
                                    <button class="action-btn btn-view" onclick="viewUserDetails(<?= htmlspecialchars(json_encode($u)) ?>)">
                                        👁️ View Details
                                    </button>
                                    
                                    <!-- Edit Role -->
                                    <?php if (!$is_self): ?>
                                        <button class="action-btn btn-edit" onclick="editUserRole(<?= $u['id'] ?>, '<?= $u['role'] ?>')">
                                            ⚙️ Edit Role
                                        </button>
                                        
                                        <!-- Block / Unblock -->
                                        <a href="users.php?action=block&id=<?= $u['id'] ?>" class="action-btn btn-block" onclick="return confirmAction('Are you sure you want to toggle block status for this user?')">
                                            🚫 <?= $u['status'] === 'active' ? 'Block' : 'Unblock' ?>
                                        </a>
                                        
                                        <!-- Delete -->
                                        <a href="users.php?action=delete&id=<?= $u['id'] ?>" class="action-btn btn-delete" onclick="return confirmAction('WARNING: Deleting this user will permanently remove all their wishlists and adoption records. Proceed?')">
                                            🗑️
                                        </a>
                                    <?php else: ?>
                                        <span style="font-size:0.8rem; color:var(--admin-text-muted); font-style:italic;">Logged In</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: User Details Modal -->
<div id="user-details-modal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.4); display: none; justify-content: center; align-items: center; z-index: 2000; padding: 20px;">
    <div style="background-color:#ffffff; border-radius: var(--radius-lg); width:100%; max-width:500px; padding:30px; box-shadow: var(--shadow-lg); position:relative;">
        <button type="button" onclick="closeUserModal()" style="position:absolute; top:20px; right:20px; width:30px; height:30px; border-radius:50%; background-color:#f1f5f9; border:none; cursor:pointer; font-weight:700;">&times;</button>
        
        <h3 style="font-size:1.5rem; font-weight:800; color:var(--admin-blue-primary); margin-bottom:20px; border-bottom:1px solid var(--admin-border); padding-bottom:10px;">User Profile Details</h3>
        
        <div style="display:flex; flex-direction:column; gap:15px;">
            <div>
                <span style="font-size:0.75rem; font-weight:700; color:var(--admin-text-muted); text-transform:uppercase;">User Identifier</span>
                <p id="modal-uid" style="font-weight:700; color:#1e293b; font-size:1.1rem;"></p>
            </div>
            <div>
                <span style="font-size:0.75rem; font-weight:700; color:var(--admin-text-muted); text-transform:uppercase;">Full Name</span>
                <p id="modal-name" style="font-weight:700; color:#1e293b;"></p>
            </div>
            <div>
                <span style="font-size:0.75rem; font-weight:700; color:var(--admin-text-muted); text-transform:uppercase;">Email Address</span>
                <p id="modal-email"></p>
            </div>
            <div>
                <span style="font-size:0.75rem; font-weight:700; color:var(--admin-text-muted); text-transform:uppercase;">Phone Number</span>
                <p id="modal-phone"></p>
            </div>
            <div>
                <span style="font-size:0.75rem; font-weight:700; color:var(--admin-text-muted); text-transform:uppercase;">Home Address</span>
                <p id="modal-address" style="background-color:#f8fafc; border:1px solid var(--admin-border); padding:10px; border-radius:6px; font-size:0.9rem; line-height:1.5;"></p>
            </div>
            <div>
                <span style="font-size:0.75rem; font-weight:700; color:var(--admin-text-muted); text-transform:uppercase;">Account Registered</span>
                <p id="modal-date" style="font-size:0.85rem; color:var(--admin-text-muted);"></p>
            </div>
        </div>
        
        <div style="margin-top: 30px; display: flex; justify-content: flex-end;">
            <button type="button" class="admin-btn-primary" onclick="closeUserModal()" style="padding:10px 20px; font-size:0.9rem;">Close Detail Viewer</button>
        </div>
    </div>
</div>

<!-- Modal: Edit Role Modal -->
<div id="edit-role-modal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.4); display: none; justify-content: center; align-items: center; z-index: 2000; padding: 20px;">
    <div style="background-color:#ffffff; border-radius: var(--radius-lg); width:100%; max-width:400px; padding:30px; box-shadow: var(--shadow-lg); position:relative;">
        <button type="button" onclick="closeRoleModal()" style="position:absolute; top:20px; right:20px; width:30px; height:30px; border-radius:50%; background-color:#f1f5f9; border:none; cursor:pointer; font-weight:700;">&times;</button>
        
        <h3 style="font-size:1.3rem; font-weight:800; color:var(--admin-blue-primary); margin-bottom:20px;">Edit Account Role</h3>
        
        <form method="GET" action="users.php">
            <input type="hidden" name="action" value="change_role">
            <input type="hidden" name="id" id="role-user-id">
            
            <div class="admin-form-group">
                <label for="role-select">Select Access Level</label>
                <select name="role" id="role-select" class="admin-form-control" style="background-color:#ffffff;">
                    <option value="user">User (Standard Access)</option>
                    <option value="admin">Admin (System Access)</option>
                </select>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="action-btn" onclick="closeRoleModal()" style="padding:10px 20px;">Cancel</button>
                <button type="submit" class="admin-btn-primary" style="padding:10px 20px; font-size:0.9rem;">Save Access Role</button>
            </div>
        </form>
    </div>
</div>

<script>
    // JS modal handles
    function viewUserDetails(user) {
        document.getElementById('modal-uid').innerText = "U-" + String(user.id).padStart(4, '0');
        document.getElementById('modal-name').innerText = user.name;
        document.getElementById('modal-email').innerText = user.email;
        document.getElementById('modal-phone').innerText = user.phone;
        document.getElementById('modal-address').innerText = user.address;
        
        const regDate = new Date(user.created_at);
        document.getElementById('modal-date').innerText = regDate.toLocaleString();
        
        document.getElementById('user-details-modal').style.display = 'flex';
    }
    
    function closeUserModal() {
        document.getElementById('user-details-modal').style.display = 'none';
    }
    
    function editUserRole(id, role) {
        document.getElementById('role-user-id').value = id;
        document.getElementById('role-select').value = role;
        document.getElementById('edit-role-modal').style.display = 'flex';
    }
    
    function closeRoleModal() {
        document.getElementById('edit-role-modal').style.display = 'none';
    }
</script>

<?php
require_once 'includes/admin_footer.php';
?>
