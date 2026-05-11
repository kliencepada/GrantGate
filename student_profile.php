<?php
session_start();
require_once 'db_conn.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: register.php');
    exit;
}

 $user_id = $_SESSION['user_id'];

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: register.php');
    exit;
}

// Fetch user data
try {
    $stmt = $pdo->prepare("
        SELECT u.*, p.*
        FROM tbl_users u
        LEFT JOIN tbl_personal_info p ON u.user_id = p.user_id
        WHERE u.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch requirements
    $reqStmt = $pdo->prepare("SELECT * FROM tbl_requirements WHERE user_id = ? ORDER BY uploaded_at DESC");
    $reqStmt->execute([$user_id]);
    $documents = $reqStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle AJAX Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        // UPDATE PERSONAL INFO
        if ($_POST['action'] === 'update_profile') {
            $fields = [
                'firstname', 'lastname', 'birthday', 'gender', 'contact_no',
                'email_add', 'home_address', 'guardian_fullname', 'guardian_contact_no',
                'occupation', 'income', 'school', 'year_level', 'course', 'gwa'
            ];
            $data = [];
            foreach ($fields as $f) $data[$f] = trim($_POST[$f] ?? '');

            $check = $pdo->prepare("SELECT info_id FROM tbl_personal_info WHERE user_id = ?");
            $check->execute([$user_id]);

            if ($check->fetch()) {
                $setClause = implode('=?, ', $fields) . '=?';
                $stmt = $pdo->prepare("UPDATE tbl_personal_info SET $setClause WHERE user_id=?");
                $values = array_values($data);
                $values[] = $user_id;
                $stmt->execute($values);
            } else {
                $cols = 'user_id, ' . implode(', ', $fields);
                $placeholders = '?, ' . implode(', ', array_fill(0, count($fields), '?'));
                $stmt = $pdo->prepare("INSERT INTO tbl_personal_info ($cols) VALUES ($placeholders)");
                $values = array_values($data);
                array_unshift($values, $user_id);
                $stmt->execute($values);
            }
            echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully!']);
            exit;
        }

        // CHANGE PASSWORD
        if ($_POST['action'] === 'change_password') {
            $current = $_POST['current_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (empty($current) || empty($new) || empty($confirm)) {
                echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
                exit;
            }

            $pwStmt = $pdo->prepare("SELECT password FROM tbl_users WHERE user_id = ?");
            $pwStmt->execute([$user_id]);
            $user = $pwStmt->fetch(PDO::FETCH_ASSOC);

            if (!password_verify($current, $user['password'])) {
                echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect.']);
                exit;
            }

            if (strlen($new) < 6) {
                echo json_encode(['status' => 'error', 'message' => 'New password must be at least 6 characters.']);
                exit;
            }

            if ($new !== $confirm) {
                echo json_encode(['status' => 'error', 'message' => 'New passwords do not match.']);
                exit;
            }

            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE tbl_users SET password = ? WHERE user_id = ?");
            $upd->execute([$hashed, $user_id]);
            echo json_encode(['status' => 'success', 'message' => 'Password changed successfully!']);
            exit;
        }

        // DELETE DOCUMENT
        if ($_POST['action'] === 'delete_document') {
            $req_id = $_POST['req_id'] ?? '';

            $docStmt = $pdo->prepare("SELECT file_path FROM tbl_requirements WHERE req_id = ? AND user_id = ?");
            $docStmt->execute([$req_id, $user_id]);
            $doc = $docStmt->fetch(PDO::FETCH_ASSOC);

            if ($doc) {
                if (file_exists($doc['file_path'])) unlink($doc['file_path']);
                $del = $pdo->prepare("DELETE FROM tbl_requirements WHERE req_id = ? AND user_id = ?");
                $del->execute([$req_id, $user_id]);
                echo json_encode(['status' => 'success', 'message' => 'Document deleted.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Document not found.']);
            }
            exit;
        }

        // UPLOAD DOCUMENT
        if ($_POST['action'] === 'upload_document') {
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error.']);
                exit;
            }

            $file = $_FILES['file'];
            if ($file['size'] > 5 * 1024 * 1024) {
                echo json_encode(['status' => 'error', 'message' => 'File too large. Max 5MB.']);
                exit;
            }

            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Use PDF, JPG, or PNG.']);
                exit;
            }

            $uploadDir = 'requirements/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $newFileName = uniqid() . '_' . $user_id . '.' . $ext;
            $filePath = $uploadDir . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $stmt = $pdo->prepare("INSERT INTO tbl_requirements (user_id, file_name, file_path, file_size, uploaded_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$user_id, $file['name'], $filePath, $file['size']]);
                echo json_encode(['status' => 'success', 'message' => 'File uploaded successfully!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to move file.']);
            }
            exit;
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrantGate | My Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --green: #00c853;
            --green-glow: rgba(0,200,83,0.4);
            --green-dim: rgba(0,200,83,0.08);
            --dark-bg: #0f172a;
            --darker: #0b1121;
            --card-bg: rgba(15,23,42,0.85);
            --sidebar-bg: #0d1424;
            --fg: #ffffff;
            --muted: #64748b;
            --input-bg: rgba(255,255,255,0.05);
            --input-border: rgba(255,255,255,0.1);
            --input-focus: rgba(0,200,83,0.25);
            --danger: #ff4757;
            --warning: #ffc93c;
            --success: #00c853;
            --radius: 14px;
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        html{scroll-behavior:smooth}
        body{font-family:'Poppins',sans-serif;background:var(--dark-bg);color:var(--fg);min-height:100vh;display:flex;overflow-x:hidden}

        /* ===== TOP BAR ===== */
        .topbar{
            position:fixed;top:0;left:0;right:0;height:64px;
            background:rgba(11,20,36,0.95);backdrop-filter:blur(20px);
            border-bottom:1px solid rgba(255,255,255,0.06);
            display:flex;align-items:center;justify-content:space-between;
            padding:0 32px;z-index:1000;
        }
        .topbar-logo{font-family:'Orbitron',sans-serif;font-size:1.3rem;font-weight:900;color:#fff;text-decoration:none}
        .topbar-logo span{color:var(--green)}
        .topbar-right{display:flex;align-items:center;gap:16px}
        .topbar-user{display:flex;align-items:center;gap:10px;color:var(--muted);font-size:14px;font-weight:500}
        .topbar-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#00c853,#1de9b6);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px}
        .logout-btn{background:rgba(255,71,87,0.1);border:1px solid rgba(255,71,87,0.3);color:#ff6b7a;padding:8px 18px;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.3s;text-decoration:none}
        .logout-btn:hover{background:var(--danger);color:#fff;border-color:var(--danger)}

        /* ===== SIDEBAR ===== */
        .sidebar{
            position:fixed;top:64px;left:0;bottom:0;width:280px;
            background:var(--sidebar-bg);
            border-right:1px solid rgba(255,255,255,0.05);
            padding:32px 0;z-index:900;
            display:flex;flex-direction:column;
        }
        .sidebar-profile{padding:0 24px 28px;border-bottom:1px solid rgba(255,255,255,0.05);margin-bottom:20px}
        .sidebar-avatar{
            width:72px;height:72px;border-radius:50%;
            background:linear-gradient(135deg,#00c853,#1de9b6);
            display:flex;align-items:center;justify-content:center;
            font-family:'Orbitron',sans-serif;font-size:1.5rem;font-weight:900;color:#fff;
            margin:0 auto 14px;position:relative;
        }
        .sidebar-avatar .status-dot{
            position:absolute;bottom:2px;right:2px;width:14px;height:14px;
            border-radius:50%;border:3px solid var(--sidebar-bg);
        }
        .status-dot.pending{background:var(--warning)}
        .status-dot.approved{background:var(--success)}
        .status-dot.rejected{background:var(--danger)}
        .sidebar-name{text-align:center;font-weight:600;font-size:15px;margin-bottom:4px}
        .sidebar-email{text-align:center;font-size:12px;color:var(--muted);word-break:break-all}
        .sidebar-badge{display:flex;justify-content:center;margin-top:12px}
        .sidebar-badge .badge{
            display:inline-flex;align-items:center;gap:6px;
            padding:5px 14px;border-radius:20px;
            font-size:11px;font-weight:600;letter-spacing:0.03em;
        }
        .badge.pending{background:rgba(255,201,60,0.1);color:var(--warning);border:1px solid rgba(255,201,60,0.2)}
        .badge.approved{background:rgba(0,200,83,0.1);color:var(--success);border:1px solid rgba(0,200,83,0.2)}
        .badge.rejected{background:rgba(255,71,87,0.1);color:var(--danger);border:1px solid rgba(255,71,87,0.2)}

        .sidebar-title{font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;color:var(--muted);letter-spacing:0.15em;text-transform:uppercase;padding:0 24px;margin-bottom:16px}

        .nav-list{list-style:none;flex:1}
        .nav-item{
            display:flex;align-items:center;gap:14px;
            padding:13px 24px;cursor:pointer;
            transition:all 0.3s;position:relative;
            color:var(--muted);font-size:14px;font-weight:500;
        }
        .nav-item:hover{background:rgba(255,255,255,0.03);color:var(--fg)}
        .nav-item.active{color:#fff;background:var(--green-dim)}
        .nav-item.active::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--green);border-radius:0 3px 3px 0}
        .nav-item i{width:20px;text-align:center;font-size:15px}
        .nav-item span{font-size:14px;font-weight:500}

        .sidebar-footer{padding:20px 24px;border-top:1px solid rgba(255,255,255,0.05)}
        .sidebar-footer a{
            display:flex;align-items:center;gap:12px;
            color:var(--muted);font-size:13px;font-weight:500;
            text-decoration:none;transition:color 0.3s;
        }
        .sidebar-footer a:hover{color:var(--danger)}

        /* ===== MAIN CONTENT ===== */
        .main-content{
            margin-left:280px;margin-top:64px;flex:1;
            padding:40px;min-height:calc(100vh - 64px);
        }

        .page-header{margin-bottom:36px}
        .page-header h1{font-family:'Orbitron',sans-serif;font-size:1.8rem;font-weight:900;margin-bottom:8px}
        .page-header h1 span{color:var(--green)}
        .page-header p{color:var(--muted);font-size:0.95rem;line-height:1.6}

        /* ===== PANELS ===== */
        .panel{display:none;animation:panelIn 0.4s ease forwards}
        .panel.active{display:block}
        @keyframes panelIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

        /* ===== PROFILE HERO ===== */
        .profile-hero{
            background:var(--card-bg);backdrop-filter:blur(20px);
            border:1px solid rgba(255,255,255,0.06);
            border-radius:var(--radius);padding:36px;
            margin-bottom:24px;display:flex;align-items:center;gap:28px;
            position:relative;overflow:hidden;
        }
        .profile-hero::before{
            content:'';position:absolute;top:0;left:0;right:0;height:4px;
            background:linear-gradient(90deg,var(--green),#1de9b6);
        }
        .profile-hero-avatar{
            width:100px;height:100px;border-radius:50%;
            background:linear-gradient(135deg,#00c853,#1de9b6);
            display:flex;align-items:center;justify-content:center;
            font-family:'Orbitron',sans-serif;font-size:2.2rem;font-weight:900;color:#fff;
            flex-shrink:0;position:relative;
        }
        .profile-hero-avatar .edit-badge{
            position:absolute;bottom:0;right:0;width:30px;height:30px;
            background:var(--darker);border:2px solid var(--green);border-radius:50%;
            display:flex;align-items:center;justify-content:center;
            font-size:12px;color:var(--green);cursor:pointer;
            transition:all 0.3s;
        }
        .profile-hero-avatar .edit-badge:hover{background:var(--green);color:#fff}
        .profile-hero-info h2{font-family:'Orbitron',sans-serif;font-size:1.4rem;font-weight:700;margin-bottom:4px}
        .profile-hero-info .sub{color:var(--muted);font-size:14px;margin-bottom:12px}
        .profile-hero-info .meta{display:flex;gap:20px;flex-wrap:wrap}
        .profile-hero-info .meta-item{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--muted)}
        .profile-hero-info .meta-item i{color:var(--green);font-size:12px}

        /* ===== INFO CARDS ===== */
        .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}

        .info-card{
            background:var(--card-bg);backdrop-filter:blur(20px);
            border:1px solid rgba(255,255,255,0.06);
            border-radius:var(--radius);padding:28px;
            position:relative;overflow:hidden;
        }
        .info-card-header{
            display:flex;justify-content:space-between;align-items:center;
            margin-bottom:22px;
        }
        .info-card-header h3{font-family:'Orbitron',sans-serif;font-size:0.85rem;font-weight:700;color:var(--muted)}
        .info-card-header h3 i{color:var(--green);margin-right:8px}
        .edit-btn{
            padding:6px 16px;border-radius:8px;
            background:rgba(0,200,83,0.08);border:1px solid rgba(0,200,83,0.2);
            color:var(--green);font-family:'Poppins',sans-serif;font-size:12px;font-weight:600;
            cursor:pointer;transition:all 0.3s;display:flex;align-items:center;gap:6px;
        }
        .edit-btn:hover{background:var(--green);color:#fff;border-color:var(--green);box-shadow:0 4px 16px var(--green-glow)}

        .info-rows{display:flex;flex-direction:column;gap:14px}
        .info-row{display:flex;justify-content:space-between;align-items:center;padding-bottom:12px;border-bottom:1px solid rgba(255,255,255,0.03)}
        .info-row:last-child{border-bottom:none;padding-bottom:0}
        .info-row .label{font-size:13px;color:var(--muted);font-weight:500}
        .info-row .value{font-size:14px;font-weight:600;color:var(--fg);text-align:right;max-width:60%;word-break:break-word}
        .info-row .value.empty{color:rgba(255,71,87,0.6);font-style:italic;font-weight:400}

        .info-card.full-width{grid-column:1/-1}

        /* ===== DOCUMENTS ===== */
        .doc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-top:20px}
        .doc-card{
            background:rgba(255,255,255,0.02);
            border:1px solid rgba(255,255,255,0.06);
            border-radius:12px;padding:18px;
            display:flex;align-items:center;gap:14px;
            transition:all 0.3s;
        }
        .doc-card:hover{border-color:rgba(0,200,83,0.2);background:var(--green-dim)}
        .doc-icon{
            width:44px;height:44px;border-radius:10px;
            display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;
        }
        .doc-icon.pdf{background:rgba(255,71,87,0.1);color:var(--danger)}
        .doc-icon.img{background:rgba(96,165,250,0.1);color:#60a5fa}
        .doc-info{flex:1;min-width:0}
        .doc-info .doc-name{font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .doc-info .doc-meta{font-size:11px;color:var(--muted);margin-top:2px}
        .doc-actions{display:flex;gap:6px}
        .doc-action-btn{
            width:32px;height:32px;border-radius:8px;
            display:flex;align-items:center;justify-content:center;
            border:none;cursor:pointer;font-size:13px;transition:all 0.3s;
        }
        .doc-action-btn.download{background:rgba(96,165,250,0.1);color:#60a5fa;border:1px solid rgba(96,165,250,0.2)}
        .doc-action-btn.download:hover{background:#3b82f6;color:#fff}
        .doc-action-btn.delete{background:rgba(255,71,87,0.1);color:var(--danger);border:1px solid rgba(255,71,87,0.2)}
        .doc-action-btn.delete:hover{background:var(--danger);color:#fff}

        /* Upload zone */
        .upload-zone{
            border:2px dashed var(--input-border);border-radius:var(--radius);
            padding:36px;text-align:center;cursor:pointer;
            transition:all 0.3s;position:relative;margin-top:20px;
        }
        .upload-zone:hover{border-color:var(--green);background:var(--green-dim)}
        .upload-zone i.upload-icon{font-size:36px;color:var(--muted);margin-bottom:10px;display:block;transition:color 0.3s}
        .upload-zone:hover i.upload-icon{color:var(--green)}
        .upload-zone p{color:var(--muted);font-size:13px}
        .upload-zone p strong{color:var(--green)}
        .upload-zone input{position:absolute;inset:0;opacity:0;cursor:pointer}

        /* ===== SECURITY CARD ===== */
        .security-section{max-width:480px}
        .pw-strength{margin-top:12px;display:flex;gap:4px}
        .pw-strength .bar{flex:1;height:4px;border-radius:2px;background:rgba(255,255,255,0.06);transition:background 0.3s}
        .pw-strength .bar.weak{background:var(--danger)}
        .pw-strength .bar.medium{background:var(--warning)}
        .pw-strength .bar.strong{background:var(--success)}
        .pw-strength-label{font-size:11px;margin-top:6px;color:var(--muted)}

        .account-item{
            display:flex;justify-content:space-between;align-items:center;
            padding:16px 0;border-bottom:1px solid rgba(255,255,255,0.03);
        }
        .account-item:last-child{border-bottom:none}
        .account-item .label{font-size:14px;color:var(--muted)}
        .account-item .value{font-size:14px;font-weight:600}

        /* ===== FORM FIELDS (EDIT MODE) ===== */
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
        .form-row.full{grid-template-columns:1fr}
        .field{position:relative}
        .field label{display:block;font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px}
        .field input,.field select,.field textarea{
            width:100%;padding:13px 16px;
            background:var(--input-bg);border:1.5px solid var(--input-border);
            border-radius:10px;color:var(--fg);font-family:'Poppins',sans-serif;font-size:14px;
            outline:none;transition:all 0.3s;
        }
        .field input:focus,.field select:focus,.field textarea:focus{
            border-color:var(--green);background:var(--green-dim);
            box-shadow:0 0 0 3px var(--input-focus);
        }
        .field select{cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center}
        .field select option{background:var(--dark-bg);color:var(--fg)}

        /* ===== BUTTONS ===== */
        .btn-group{display:flex;gap:12px;margin-top:28px;justify-content:flex-end}
        .btn{
            padding:13px 28px;border-radius:10px;
            font-family:'Orbitron',sans-serif;font-size:0.78rem;font-weight:700;
            cursor:pointer;transition:all 0.3s;letter-spacing:0.05em;
            border:none;display:inline-flex;align-items:center;gap:8px;
        }
        .btn-primary{background:var(--green);color:#fff;box-shadow:0 4px 20px var(--green-glow)}
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 30px var(--green-glow)}
        .btn-secondary{background:var(--input-bg);color:var(--fg);border:1.5px solid var(--input-border)}
        .btn-secondary:hover{border-color:var(--green);color:var(--green)}
        .btn-danger{background:rgba(255,71,87,0.1);color:var(--danger);border:1.5px solid rgba(255,71,87,0.3)}
        .btn-danger:hover{background:var(--danger);color:#fff;border-color:var(--danger)}
        .btn:active{transform:scale(0.97)!important}

        /* ===== MODAL ===== */
        .modal-overlay{
            position:fixed;inset:0;background:rgba(0,0,0,0.6);
            backdrop-filter:blur(8px);z-index:5000;
            display:none;align-items:center;justify-content:center;
            animation:modalBgIn 0.3s forwards;
        }
        .modal-overlay.open{display:flex}
        @keyframes modalBgIn{from{opacity:0}to{opacity:1}}

        .modal{
            background:var(--darker);border:1px solid rgba(255,255,255,0.08);
            border-radius:var(--radius);padding:36px;
            width:90%;max-width:640px;max-height:85vh;overflow-y:auto;
            animation:modalIn 0.4s cubic-bezier(0.22,1,0.36,1) forwards;
            transform:translateY(30px) scale(0.95);opacity:0;
        }
        @keyframes modalIn{to{transform:translateY(0) scale(1);opacity:1}}

        .modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px}
        .modal-header h2{font-family:'Orbitron',sans-serif;font-size:1.05rem;font-weight:700}
        .modal-header h2 i{color:var(--green);margin-right:8px}
        .modal-close{background:none;border:none;color:var(--muted);font-size:20px;cursor:pointer;transition:color 0.3s}
        .modal-close:hover{color:var(--fg)}

        /* ===== TOAST ===== */
        .toast-box{position:fixed;top:80px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px}
        .toast{padding:14px 22px;border-radius:12px;font-size:13px;font-weight:500;backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:10px;min-width:240px;box-shadow:0 8px 30px rgba(0,0,0,0.4);animation:toastIn 0.4s cubic-bezier(0.22,1,0.36,1) forwards}
        .toast.success{background:rgba(0,200,83,0.15);border-color:rgba(0,200,83,0.3);color:#a7f3d0}
        .toast.error{background:rgba(255,71,87,0.15);border-color:rgba(255,71,87,0.3);color:#fca5a5}
        .toast.info{background:rgba(96,165,250,0.15);border-color:rgba(96,165,250,0.3);color:#bfdbfe}
        .toast.out{animation:toastOut 0.35s forwards}
        @keyframes toastIn{from{transform:translateX(120%);opacity:0}to{transform:translateX(0);opacity:1}}
        @keyframes toastOut{to{transform:translateX(120%);opacity:0}}

        /* ===== EMPTY STATE ===== */
        .empty-state{
            text-align:center;padding:48px 20px;
        }
        .empty-state i{font-size:48px;color:var(--muted);opacity:0.4;margin-bottom:16px;display:block}
        .empty-state h3{font-size:16px;font-weight:600;margin-bottom:6px;color:var(--muted)}
        .empty-state p{font-size:13px;color:var(--muted);opacity:0.7}

        /* ===== RESPONSIVE ===== */
        @media(max-width:900px){
            .sidebar{width:64px;padding:20px 0}
            .sidebar-title,.sidebar-profile,.sidebar-footer span,.nav-item span{display:none}
            .nav-item{padding:14px 0;justify-content:center}
            .main-content{margin-left:64px;padding:24px}
            .info-grid{grid-template-columns:1fr}
            .profile-hero{flex-direction:column;text-align:center}
            .profile-hero-info .meta{justify-content:center}
            .form-row{grid-template-columns:1fr}
        }
        @media(max-width:600px){
            .sidebar{display:none}
            .main-content{margin-left:0;padding:20px}
            .topbar{padding:0 16px}
            .profile-hero-avatar{width:80px;height:80px;font-size:1.8rem}
            .doc-grid{grid-template-columns:1fr}
        }
    </style>
</head>
<body>

    <!-- TOP BAR -->
    <div class="topbar">
        <a href="dashboard.php" class="topbar-logo">GRANT<span>GATE</span></a>
        <div class="topbar-right">
            <div class="topbar-user">
                <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['firstname'] ?? 'U', 0, 1)) ?></div>
                <span><?= htmlspecialchars(($_SESSION['firstname'] ?? '') . ' ' . ($_SESSION['lastname'] ?? '')) ?></span>
            </div>
            <a href="student_profile.php?logout=true" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- SIDEBAR -->
    <nav class="sidebar">
        <div class="sidebar-profile">
            <div class="sidebar-avatar">
                <?= strtoupper(substr($profile['firstname'] ?? 'U', 0, 1)) ?>
                <div class="status-dot <?= $profile['app_status'] ?? 'pending' ?>"></div>
            </div>
            <div class="sidebar-name"><?= htmlspecialchars(($profile['firstname'] ?? '') . ' ' . ($profile['lastname'] ?? '')) ?></div>
            <div class="sidebar-email"><?= htmlspecialchars($profile['email'] ?? $profile['email_add'] ?? '') ?></div>
            <div class="sidebar-badge">
                <span class="badge <?= $profile['app_status'] ?? 'pending' ?>">
                    <i class="fas fa-<?= ($profile['app_status'] ?? 'pending') === 'approved' ? 'check' : (($profile['app_status'] ?? 'pending') === 'rejected' ? 'times' : 'clock') ?>"></i>
                    <?= ucfirst($profile['app_status'] ?? 'Pending') ?>
                </span>
            </div>
        </div>

        <div class="sidebar-title">Navigation</div>
        <ul class="nav-list">
            <li class="nav-item active" onclick="switchPanel('overview',this)">
                <i class="fas fa-th-large"></i><span>Overview</span>
            </li>
            <li class="nav-item" onclick="switchPanel('personal',this)">
                <i class="fas fa-user"></i><span>Personal Info</span>
            </li>
            <li class="nav-item" onclick="switchPanel('academic',this)">
                <i class="fas fa-graduation-cap"></i><span>Academic Info</span>
            </li>
            <li class="nav-item" onclick="switchPanel('documents',this)">
                <i class="fas fa-file-alt"></i><span>Documents</span>
            </li>
            <li class="nav-item" onclick="switchPanel('security',this)">
                <i class="fas fa-shield-halved"></i><span>Security</span>
            </li>
        </ul>

        <div class="sidebar-footer">
            <a href="dashboard.php"><i class="fas fa-arrow-left"></i><span>Back to Dashboard</span></a>
        </div>
    </nav>

    <!-- TOAST -->
    <div class="toast-box" id="toastBox"></div>

    <!-- EDIT PROFILE MODAL -->
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-header">
                <h2><i class="fas fa-pen-to-square"></i> Edit Profile</h2>
                <button class="modal-close" onclick="closeModal('editModal')"><i class="fas fa-times"></i></button>
            </div>
            <div id="editModalBody">
                <!-- Filled by JS -->
            </div>
        </div>
    </div>

    <!-- CHANGE PASSWORD MODAL -->
    <div class="modal-overlay" id="passwordModal">
        <div class="modal">
            <div class="modal-header">
                <h2><i class="fas fa-lock"></i> Change Password</h2>
                <button class="modal-close" onclick="closeModal('passwordModal')"><i class="fas fa-times"></i></button>
            </div>
            <div>
                <div class="form-row full">
                    <div class="field">
                        <label>Current Password</label>
                        <input type="password" id="currentPw" placeholder="Enter current password">
                    </div>
                </div>
                <div class="form-row full">
                    <div class="field">
                        <label>New Password</label>
                        <input type="password" id="newPw" placeholder="At least 6 characters" oninput="checkPwStrength(this.value)">
                    </div>
                </div>
                <div class="pw-strength" id="pwStrength" style="display:none">
                    <div class="bar" id="pwBar1"></div>
                    <div class="bar" id="pwBar2"></div>
                    <div class="bar" id="pwBar3"></div>
                    <div class="bar" id="pwBar4"></div>
                </div>
                <div class="pw-strength-label" id="pwLabel" style="display:none"></div>
                <div class="form-row full" style="margin-top:16px">
                    <div class="field">
                        <label>Confirm New Password</label>
                        <input type="password" id="confirmPw" placeholder="Re-enter new password">
                    </div>
                </div>
                <div class="btn-group">
                    <button class="btn btn-secondary" onclick="closeModal('passwordModal')">Cancel</button>
                    <button class="btn btn-primary" onclick="changePassword()"><i class="fas fa-check"></i> Update Password</button>
                </div>
            </div>
        </div>
    </div>

    <!-- DELETE CONFIRM MODAL -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal" style="max-width:420px;text-align:center">
            <div style="margin-bottom:20px">
                <div style="width:64px;height:64px;border-radius:50%;background:rgba(255,71,87,0.1);border:2px solid rgba(255,71,87,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px;color:var(--danger)">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <h2 style="font-family:'Orbitron',sans-serif;font-size:1rem;font-weight:700;margin-bottom:8px">Delete Document?</h2>
                <p style="color:var(--muted);font-size:14px;line-height:1.6">This action cannot be undone. The file will be permanently removed.</p>
            </div>
            <div class="btn-group" style="justify-content:center">
                <button class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteBtn"><i class="fas fa-trash"></i> Delete</button>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <div class="page-header">
            <h1>My <span>Profile</span></h1>
            <p>View and manage your personal information, academic records, and uploaded documents.</p>
        </div>

        <!-- ===== OVERVIEW PANEL ===== -->
        <div class="panel active" id="panel-overview">
            <div class="profile-hero">
                <div class="profile-hero-avatar">
                    <?= strtoupper(substr($profile['firstname'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="profile-hero-info">
                    <h2><?= htmlspecialchars(($profile['firstname'] ?? '') . ' ' . ($profile['lastname'] ?? '')) ?></h2>
                    <div class="sub"><?= htmlspecialchars($profile['school'] ?? 'School not set') ?> · <?= htmlspecialchars($profile['course'] ?? 'N/A') ?></div>
                    <div class="meta">
                        <div class="meta-item"><i class="fas fa-envelope"></i> <?= htmlspecialchars($profile['email_add'] ?? $profile['email'] ?? 'N/A') ?></div>
                        <div class="meta-item"><i class="fas fa-phone"></i> <?= htmlspecialchars($profile['contact_no'] ?? 'N/A') ?></div>
                        <div class="meta-item"><i class="fas fa-calendar"></i> Joined <?= date('M Y', strtotime($profile['created_at'] ?? 'now')) ?></div>
                    </div>
                </div>
            </div>

            <div class="info-grid">
                <!-- Quick Personal -->
                <div class="info-card">
                    <div class="info-card-header">
                        <h3><i class="fas fa-user"></i> Personal Details</h3>
                        <button class="edit-btn" onclick="openEditPersonal()"><i class="fas fa-pen"></i> Edit</button>
                    </div>
                    <div class="info-rows">
                        <div class="info-row"><span class="label">Gender</span><span class="value <?= empty($profile['gender']) ? 'empty' : '' ?>"><?= $profile['gender'] ?? 'Not set' ?></span></div>
                        <div class="info-row"><span class="label">Birthday</span><span class="value <?= empty($profile['birthday']) ? 'empty' : '' ?>"><?= $profile['birthday'] ?? 'Not set' ?></span></div>
                        <div class="info-row"><span class="label">Address</span><span class="value <?= empty($profile['home_address']) ? 'empty' : '' ?>"><?= $profile['home_address'] ?? 'Not set' ?></span></div>
                    </div>
                </div>

                <!-- Quick Family -->
                <div class="info-card">
                    <div class="info-card-header">
                        <h3><i class="fas fa-wallet"></i> Family & Income</h3>
                        <button class="edit-btn" onclick="openEditFamily()"><i class="fas fa-pen"></i> Edit</button>
                    </div>
                    <div class="info-rows">
                        <div class="info-row"><span class="label">Guardian</span><span class="value <?= empty($profile['guardian_fullname']) ? 'empty' : '' ?>"><?= $profile['guardian_fullname'] ?? 'Not set' ?></span></div>
                        <div class="info-row"><span class="label">Occupation</span><span class="value <?= empty($profile['occupation']) ? 'empty' : '' ?>"><?= $profile['occupation'] ?? 'Not set' ?></span></div>
                        <div class="info-row"><span class="label">Annual Income</span><span class="value <?= empty($profile['income']) ? 'empty' : '' ?>"><?= $profile['income'] ?? 'Not set' ?></span></div>
                    </div>
                </div>

                <!-- Quick Academic -->
                <div class="info-card">
                    <div class="info-card-header">
                        <h3><i class="fas fa-graduation-cap"></i> Academic Info</h3>
                        <button class="edit-btn" onclick="openEditAcademic()"><i class="fas fa-pen"></i> Edit</button>
                    </div>
                    <div class="info-rows">
                        <div class="info-row"><span class="label">School</span><span class="value <?= empty($profile['school']) ? 'empty' : '' ?>"><?= $profile['school'] ?? 'Not set' ?></span></div>
                        <div class="info-row"><span class="label">Year Level</span><span class="value <?= empty($profile['year_level']) ? 'empty' : '' ?>"><?= $profile['year_level'] ?? 'Not set' ?></span></div>
                        <div class="info-row"><span class="label">Course</span><span class="value <?= empty($profile['course']) ? 'empty' : '' ?>"><?= $profile['course'] ?? 'Not set' ?></span></div>
                        <div class="info-row"><span class="label">GWA</span><span class="value <?= empty($profile['gwa']) ? 'empty' : '' ?>"><?= $profile['gwa'] ?? 'Not set' ?></span></div>
                    </div>
                </div>

                <!-- Application Status -->
                <div class="info-card">
                    <div class="info-card-header">
                        <h3><i class="fas fa-chart-line"></i> Application Status</h3>
                    </div>
                    <div class="info-rows">
                        <div class="info-row"><span class="label">Status</span><span class="value"><span class="badge <?= $profile['app_status'] ?? 'pending' ?>"><?= ucfirst($profile['app_status'] ?? 'Pending') ?></span></span></div>
                        <div class="info-row"><span class="label">Documents</span><span class="value"><?= count($documents) ?> uploaded</span></div>
                        <div class="info-row"><span class="label">Account Created</span><span class="value"><?= date('F j, Y', strtotime($profile['created_at'] ?? 'now')) ?></span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== PERSONAL INFO PANEL ===== -->
        <div class="panel" id="panel-personal">
            <div class="info-grid">
                <div class="info-card full-width">
                    <div class="info-card-header">
                        <h3><i class="fas fa-user"></i> Personal Information</h3>
                        <button class="edit-btn" onclick="openEditPersonal()"><i class="fas fa-pen"></i> Edit</button>
                    </div>
                    <div class="info-rows">
                        <div class="info-row"><span class="label">First Name</span><span class="value <?= empty($profile['firstname']) ? 'empty' : '' ?>"><?= $profile['firstname'] ?? 'Not set' ?></span></div>
                        <div class="info-row"><span class="label">Last Name</span><span class="value <?= empty($profile['lastname']) ? 'empty' : '' ?>"><?= $profile['lastname'] ?? 'Not set' ?></span></div>
                        <div class="info-row"><span class="label">Gender</span><span class="value <?= empty($profile['gender']) ? 'empty' : '' ?>"><?= $profile['gender'] ?? 'Not set' ?></span></div>
                        <div class="info-row"><span class="label">Birthday</span><span class="value <?= empty($profile['birthday']) ? 'empty' : '' ?>"><?= $profile['birthday'] ?? 'Not set' ?></span></div>
                        <div class="info-row"><span class="label">Contact Number</span><span class="value <?= empty($profile['contact_no']) ? 'empty' : '' ?>"><?= $profile['contact_no'] ?? 'Not set' ?></span></div>
                        <div class="info-row"><span class="label">Email Address</span><span class="value <?= empty($profile['email_add']) ? 'empty' : '' ?>"><?= $profile['email_add'] ?? 'Not set' ?></span></div>
                        <div class="info-row"><span class="label">Home Address</span><span class="value <?= empty($profile['home_address']) ? 'empty' : '' ?>"><?= $profile['home_address'] ?? 'Not set' ?></span></div>
                    </div>
                </div>
                <div class="info-card full-width">
                    <div class="info-card-header">
                        <h3><i class="fas fa-wallet"></i> Family & Income</h3>
                        <button class="edit-btn" onclick="openEditFamily()"><i class="fas fa-pen"></i> Edit</button>
                    </div>
                    <div class="info-rows">
                        <div class="info-row"><span class="label">Parent/Guardian</span><span class="value <?= empty($profile['guardian_fullname']) ? 'empty' : '' ?>"><?= $profile['guardian_fullname'] ?? 'Not set' ?></span></div>
                        <div class="info-row"><span class="label">Guardian Contact</span><span class="value <?= empty($profile['guardian_contact_no']) ? 'empty' : '' ?>"><?= $profile['guardian_contact_no'] ?? 'Not set' ?></span></div>
                        <div class="info-row"><span class="label">Occupation</span><span class="value <?= empty($profile['occupation']) ? 'empty' : '' ?>"><?= $profile['occupation'] ?? 'Not set' ?></span></div>
                        <div class="info-row"><span class="label">Annual Income</span><span class="value <?= empty($profile['income']) ? 'empty' : '' ?>"><?= $profile['income'] ?? 'Not set' ?></span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== ACADEMIC INFO PANEL ===== -->
        <div class="panel" id="panel-academic">
            <div class="info-grid">
                <div class="info-card full-width">
                    <div class="info-card-header">
                        <h3><i class="fas fa-graduation-cap"></i> Academic Records</h3>
                        <button class="edit-btn" onclick="openEditAcademic()"><i class="fas fa-pen"></i> Edit</button>
                    </div>
                    <div class="info-rows">
                        <div class="info-row"><span class="label">School / University</span><span class="value <?= empty($profile['school']) ? 'empty' : '' ?>"><?= $profile['school'] ?? 'Not set' ?></span></div>
                        <div class="info-row"><span class="label">Year Level</span><span class="value <?= empty($profile['year_level']) ? 'empty' : '' ?>"><?= $profile['year_level'] ?? 'Not set' ?></span></div>
                        <div class="info-row"><span class="label">Course / Program</span><span class="value <?= empty($profile['course']) ? 'empty' : '' ?>"><?= $profile['course'] ?? 'Not set' ?></span></div>
                        <div class="info-row"><span class="label">GWA (Previous Semester)</span><span class="value <?= empty($profile['gwa']) ? 'empty' : '' ?>"><?= $profile['gwa'] ?? 'Not set' ?></span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== DOCUMENTS PANEL ===== -->
        <div class="panel" id="panel-documents">
            <div class="info-card full-width" style="background:var(--card-bg);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.06);border-radius:var(--radius);padding:28px">
                <div class="info-card-header">
                    <h3><i class="fas fa-file-alt"></i> Uploaded Documents (<?= count($documents) ?>)</h3>
                </div>

                <?php if (count($documents) > 0): ?>
                    <div class="doc-grid">
                        <?php foreach ($documents as $doc): ?>
                            <?php
                                $ext = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
                                $isImg = in_array($ext, ['jpg', 'jpeg', 'png']);
                                $sizeKB = round($doc['file_size'] / 1024);
                                $sizeDisplay = $sizeKB > 1024 ? round($sizeKB / 1024, 1) . ' MB' : $sizeKB . ' KB';
                            ?>
                            <div class="doc-card" id="doc-<?= $doc['req_id'] ?>">
                                <div class="doc-icon <?= $isImg ? 'img' : 'pdf' ?>">
                                    <i class="fas fa-<?= $isImg ? 'image' : 'file-pdf' ?>"></i>
                                </div>
                                <div class="doc-info">
                                    <div class="doc-name"><?= htmlspecialchars($doc['file_name']) ?></div>
                                    <div class="doc-meta"><?= $sizeDisplay ?> · <?= date('M j, Y g:i A', strtotime($doc['uploaded_at'])) ?></div>
                                </div>
                                <div class="doc-actions">
                                    <a href="<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="doc-action-btn download" title="View/Download"><i class="fas fa-download"></i></a>
                                    <button class="doc-action-btn delete" onclick="confirmDelete(<?= $doc['req_id'] ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h3>No Documents Uploaded</h3>
                        <p>Upload your requirements below to complete your application.</p>
                    </div>
                <?php endif; ?>

                <div class="upload-zone" id="profileUploadZone">
                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                    <p>Drag & drop or <strong>click to browse</strong></p>
                    <p style="font-size:11px;margin-top:6px;opacity:0.6">PDF, JPG, PNG — Max 5MB</p>
                    <input type="file" accept=".pdf,.jpg,.jpeg,.png" onchange="handleProfileUpload(this)">
                </div>
            </div>
        </div>

        <!-- ===== SECURITY PANEL ===== -->
        <div class="panel" id="panel-security">
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-card-header">
                        <h3><i class="fas fa-shield-halved"></i> Account Details</h3>
                    </div>
                    <div class="info-rows">
                        <div class="account-item">
                            <span class="label">Email</span>
                            <span class="value"><?= htmlspecialchars($profile['email'] ?? 'N/A') ?></span>
                        </div>
                        <div class="account-item">
                            <span class="label">Password</span>
                            <span class="value" style="color:var(--muted)">••••••••</span>
                        </div>
                        <div class="account-item">
                            <span class="label">Account Created</span>
                            <span class="value"><?= date('F j, Y', strtotime($profile['created_at'] ?? 'now')) ?></span>
                        </div>
                    </div>
                    <div style="margin-top:20px">
                        <button class="btn btn-secondary" onclick="openModal('passwordModal')"><i class="fas fa-key"></i> Change Password</button>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-card-header">
                        <h3><i class="fas fa-info-circle"></i> Security Tips</h3>
                    </div>
                    <div style="font-size:14px;color:var(--muted);line-height:2">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                            <i class="fas fa-check-circle" style="color:var(--green)"></i> Use a strong, unique password
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                            <i class="fas fa-check-circle" style="color:var(--green)"></i> Never share your login credentials
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                            <i class="fas fa-check-circle" style="color:var(--green)"></i> Update your password regularly
                        </div>
                        <div style="display:flex;align-items:center;gap:10px">
                            <i class="fas fa-check-circle" style="color:var(--green)"></i> Log out from shared devices
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        /* ===== PROFILE DATA (from PHP) ===== */
        const profileData = <?= json_encode($profile ?: []) ?>;

        /* ===== PANEL NAVIGATION ===== */
        function switchPanel(panelId, navEl) {
            document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
            document.getElementById('panel-' + panelId).classList.add('active');
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            navEl.classList.add('active');
        }

        /* ===== MODAL HELPERS ===== */
        function openModal(id) {
            document.getElementById(id).classList.add('open');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('open');
        }
        // Close modal on overlay click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) this.classList.remove('open');
            });
        });

        /* ===== EDIT PERSONAL INFO ===== */
        function openEditPersonal() {
            const body = document.getElementById('editModalBody');
            body.innerHTML =
                '<div class="form-row">' +
                    '<div class="field"><label>First Name</label><input type="text" id="edt_firstname" value="' + escHtml(profileData.firstname || '') + '"></div>' +
                    '<div class="field"><label>Last Name</label><input type="text" id="edt_lastname" value="' + escHtml(profileData.lastname || '') + '"></div>' +
                '</div>' +
                '<div class="form-row">' +
                    '<div class="field"><label>Date of Birth</label><input type="date" id="edt_birthday" value="' + escHtml(profileData.birthday || '') + '"></div>' +
                    '<div class="field"><label>Gender</label><select id="edt_gender"><option value="">Select</option><option' + (profileData.gender === 'Male' ? ' selected' : '') + '>Male</option><option' + (profileData.gender === 'Female' ? ' selected' : '') + '>Female</option></select></div>' +
                '</div>' +
                '<div class="form-row">' +
                    '<div class="field"><label>Contact Number</label><input type="tel" id="edt_contact_no" value="' + escHtml(profileData.contact_no || '') + '"></div>' +
                    '<div class="field"><label>Email Address</label><input type="email" id="edt_email_add" value="' + escHtml(profileData.email_add || '') + '"></div>' +
                '</div>' +
                '<div class="form-row full">' +
                    '<div class="field"><label>Home Address</label><input type="text" id="edt_home_address" value="' + escHtml(profileData.home_address || '') + '"></div>' +
                '</div>' +
                '<div class="form-row">' +
                    '<div class="field"><label>Parent/Guardian Name</label><input type="text" id="edt_guardian_fullname" value="' + escHtml(profileData.guardian_fullname || '') + '"></div>' +
                    '<div class="field"><label>Guardian Contact</label><input type="tel" id="edt_guardian_contact_no" value="' + escHtml(profileData.guardian_contact_no || '') + '"></div>' +
                '</div>' +
                '<div class="form-row">' +
                    '<div class="field"><label>Occupation</label><input type="text" id="edt_occupation" value="' + escHtml(profileData.occupation || '') + '"></div>' +
                    '<div class="field"><label>Annual Income</label><select id="edt_income"><option value="">Select range</option><option' + (profileData.income === 'Below ₱100,000' ? ' selected' : '') + '>Below ₱100,000</option><option' + (profileData.income === '₱100,000 - ₱250,000' ? ' selected' : '') + '>₱100,000 - ₱250,000</option><option' + (profileData.income === '₱250,000 - ₱500,000' ? ' selected' : '') + '>₱250,000 - ₱500,000</option><option' + (profileData.income === 'Above ₱500,000' ? ' selected' : '') + '>Above ₱500,000</option></select></div>' +
                '</div>' +
                '<div class="btn-group">' +
                    '<button class="btn btn-secondary" onclick="closeModal(\'editModal\')">Cancel</button>' +
                    '<button class="btn btn-primary" onclick="saveProfile()"><i class="fas fa-check"></i> Save Changes</button>' +
                '</div>';
            openModal('editModal');
        }

        /* ===== EDIT ACADEMIC INFO ===== */
        function openEditAcademic() {
            const body = document.getElementById('editModalBody');
            body.innerHTML =
                '<div class="form-row">' +
                    '<div class="field"><label>School / University</label><input type="text" id="edt_school" value="' + escHtml(profileData.school || '') + '"></div>' +
                    '<div class="field"><label>Year Level</label><select id="edt_year_level"><option value="">Select year</option><option' + (profileData.year_level === '1st Year' ? ' selected' : '') + '>1st Year</option><option' + (profileData.year_level === '2nd Year' ? ' selected' : '') + '>2nd Year</option><option' + (profileData.year_level === '3rd Year' ? ' selected' : '') + '>3rd Year</option><option' + (profileData.year_level === '4th Year' ? ' selected' : '') + '>4th Year</option></select></div>' +
                '</div>' +
                '<div class="form-row">' +
                    '<div class="field"><label>Course / Program</label><input type="text" id="edt_course" value="' + escHtml(profileData.course || '') + '"></div>' +
                    '<div class="field"><label>GWA (Previous Semester)</label><input type="text" id="edt_gwa" value="' + escHtml(profileData.gwa || '') + '" placeholder="e.g. 1.50"></div>' +
                '</div>' +
                '<div class="btn-group">' +
                    '<button class="btn btn-secondary" onclick="closeModal(\'editModal\')">Cancel</button>' +
                    '<button class="btn btn-primary" onclick="saveProfile()"><i class="fas fa-check"></i> Save Changes</button>' +
                '</div>';
            openModal('editModal');
        }

        /* ===== EDIT FAMILY INFO ===== */
        function openEditFamily() {
            const body = document.getElementById('editModalBody');
            body.innerHTML =
                '<div class="form-row">' +
                    '<div class="field"><label>Parent/Guardian Name</label><input type="text" id="edt_guardian_fullname" value="' + escHtml(profileData.guardian_fullname || '') + '"></div>' +
                    '<div class="field"><label>Guardian Contact</label><input type="tel" id="edt_guardian_contact_no" value="' + escHtml(profileData.guardian_contact_no || '') + '"></div>' +
                '</div>' +
                '<div class="form-row">' +
                    '<div class="field"><label>Occupation</label><input type="text" id="edt_occupation" value="' + escHtml(profileData.occupation || '') + '"></div>' +
                    '<div class="field"><label>Annual Income</label><select id="edt_income"><option value="">Select range</option><option' + (profileData.income === 'Below ₱100,000' ? ' selected' : '') + '>Below ₱100,000</option><option' + (profileData.income === '₱100,000 - ₱250,000' ? ' selected' : '') + '>₱100,000 - ₱250,000</option><option' + (profileData.income === '₱250,000 - ₱500,000' ? ' selected' : '') + '>₱250,000 - ₱500,000</option><option' + (profileData.income === 'Above ₱500,000' ? ' selected' : '') + '>Above ₱500,000</option></select></div>' +
                '</div>' +
                '<div class="btn-group">' +
                    '<button class="btn btn-secondary" onclick="closeModal(\'editModal\')">Cancel</button>' +
                    '<button class="btn btn-primary" onclick="saveProfile()"><i class="fas fa-check"></i> Save Changes</button>' +
                '</div>';
            openModal('editModal');
        }

        /* ===== SAVE PROFILE ===== */
        async function saveProfile() {
            const fields = ['firstname','lastname','birthday','gender','contact_no','email_add',
                           'home_address','guardian_fullname','guardian_contact_no','occupation',
                           'income','school','year_level','course','gwa'];

            const data = new URLSearchParams();
            data.append('action', 'update_profile');
            fields.forEach(f => {
                const el = document.getElementById('edt_' + f);
                data.append(f, el ? el.value : (profileData[f] || ''));
            });

            try {
                const res = await fetch('student_profile.php', { method: 'POST', body: data });
                const json = await res.json();
                if (json.status === 'success') {
                    showToast(json.message, 'success');
                    closeModal('editModal');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(json.message || 'Update failed.', 'error');
                }
            } catch(e) {
                showToast('Network error.', 'error');
            }
        }

        /* ===== CHANGE PASSWORD ===== */
        async function changePassword() {
            const current = document.getElementById('currentPw').value;
            const newPw = document.getElementById('newPw').value;
            const confirm = document.getElementById('confirmPw').value;

            if (!current || !newPw || !confirm) {
                showToast('All fields are required.', 'error');
                return;
            }

            const data = new URLSearchParams();
            data.append('action', 'change_password');
            data.append('current_password', current);
            data.append('new_password', newPw);
            data.append('confirm_password', confirm);

            try {
                const res = await fetch('student_profile.php', { method: 'POST', body: data });
                const json = await res.json();
                if (json.status === 'success') {
                    showToast(json.message, 'success');
                    closeModal('passwordModal');
                    document.getElementById('currentPw').value = '';
                    document.getElementById('newPw').value = '';
                    document.getElementById('confirmPw').value = '';
                } else {
                    showToast(json.message, 'error');
                }
            } catch(e) {
                showToast('Network error.', 'error');
            }
        }

        /* ===== PASSWORD STRENGTH ===== */
        function checkPwStrength(pw) {
            const bars = [document.getElementById('pwBar1'), document.getElementById('pwBar2'),
                          document.getElementById('pwBar3'), document.getElementById('pwBar4')];
            const label = document.getElementById('pwLabel');
            const container = document.getElementById('pwStrength');

            if (!pw) { container.style.display = 'none'; label.style.display = 'none'; return; }
            container.style.display = 'flex'; label.style.display = 'block';

            let score = 0;
            if (pw.length >= 6) score++;
            if (pw.length >= 10) score++;
            if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
            if (/[0-9]/.test(pw) && /[^A-Za-z0-9]/.test(pw)) score++;

            const levels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            const classes = ['', 'weak', 'weak', 'medium', 'strong'];

            bars.forEach((bar, i) => {
                bar.className = 'bar' + (i < score ? ' ' + classes[score] : '');
            });
            label.textContent = levels[score] || 'Too short';
            label.style.color = score <= 1 ? 'var(--danger)' : score === 2 ? 'var(--warning)' : 'var(--success)';
        }

        /* ===== FILE UPLOAD ===== */
        async function handleProfileUpload(input) {
            const file = input.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                showToast('File too large. Max 5MB.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'upload_document');
            formData.append('file', file);

            try {
                const res = await fetch('student_profile.php', { method: 'POST', body: formData });
                const json = await res.json();
                if (json.status === 'success') {
                    showToast('File uploaded: ' + file.name, 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(json.message || 'Upload failed.', 'error');
                }
            } catch(e) {
                showToast('Network error during upload.', 'error');
            }
            input.value = '';
        }

        /* ===== DELETE DOCUMENT ===== */
        let pendingDeleteId = null;

        function confirmDelete(reqId) {
            pendingDeleteId = reqId;
            openModal('deleteModal');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
            if (!pendingDeleteId) return;

            const data = new URLSearchParams();
            data.append('action', 'delete_document');
            data.append('req_id', pendingDeleteId);

            try {
                const res = await fetch('student_profile.php', { method: 'POST', body: data });
                const json = await res.json();
                if (json.status === 'success') {
                    showToast('Document deleted.', 'success');
                    closeModal('deleteModal');
                    const docEl = document.getElementById('doc-' + pendingDeleteId);
                    if (docEl) docEl.style.transition = 'opacity 0.3s, transform 0.3s';
                    if (docEl) { docEl.style.opacity = '0'; docEl.style.transform = 'scale(0.9)'; }
                    setTimeout(() => location.reload(), 600);
                } else {
                    showToast(json.message || 'Delete failed.', 'error');
                }
            } catch(e) {
                showToast('Network error.', 'error');
            }
            pendingDeleteId = null;
        });

        /* ===== TOAST ===== */
        function showToast(msg, type) {
            const box = document.getElementById('toastBox');
            const t = document.createElement('div');
            t.classList.add('toast', type || 'info');
            const icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', info: 'fa-circle-info' };
            t.innerHTML = '<i class="fas ' + (icons[type] || icons.info) + '"></i><span>' + msg + '</span>';
            box.appendChild(t);
            setTimeout(() => { t.classList.add('out'); t.addEventListener('animationend', () => t.remove()); }, 3500);
        }

        /* ===== ESCAPE HTML ===== */
        function escHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    </script>
</body>
</html>