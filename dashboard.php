<?php
session_start();
require_once 'db_conn.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: register.php');
    exit;
}

 $user_id = $_SESSION['user_id'];

// Fetch existing personal info to check if form is already filled
 $existingInfo = null;
try {
    $checkStmt = $pdo->prepare("SELECT * FROM tbl_personal_info WHERE user_id = ?");
    $checkStmt->execute([$user_id]);
    $existingInfo = $checkStmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ignore fetch errors here, form will just start fresh
}

// Fetch existing uploaded requirements
 $existingReqs = [];
try {
    $reqStmt = $pdo->prepare("SELECT * FROM tbl_requirements WHERE user_id = ?");
    $reqStmt->execute([$user_id]);
    $existingReqs = $reqStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: register.php');
    exit;
}

// Handle AJAX Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $user_id = $_SESSION['user_id'];

    try {
        // SAVE PERSONAL INFO
        if ($action === 'save_personal_info') {
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
                $cols = 'user_id, ' . implode(', ', $fields) . ', app_status';
                $placeholders = '?, ' . implode(', ', array_fill(0, count($fields), '?')) . ', ?';
                $stmt = $pdo->prepare("INSERT INTO tbl_personal_info ($cols) VALUES ($placeholders)");
                $values = array_values($data);
                array_unshift($values, $user_id);
                $values[] = 'draft';
                $stmt->execute($values);
            }
            echo json_encode(['status' => 'success', 'message' => 'Personal information saved!']);
            exit;
        }

        // UPLOAD PHOTO
        if ($action === 'upload_photo') {
            if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['status' => 'error', 'message' => 'No photo uploaded or upload error.']);
                exit;
            }

            $file = $_FILES['photo'];
            $maxSize = 5 * 1024 * 1024; 
            if ($file['size'] > $maxSize) {
                echo json_encode(['status' => 'error', 'message' => 'Photo too large. Max 5MB.']);
                exit;
            }

            $allowed = ['jpg', 'jpeg', 'png'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG and PNG allowed.']);
                exit;
            }

            $uploadDir = 'photos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $newFileName = 'photo_' . $user_id . '_' . uniqid() . '.' . $ext;
            $filePath = $uploadDir . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $oldStmt = $pdo->prepare("SELECT photo_path FROM tbl_personal_info WHERE user_id = ?");
                $oldStmt->execute([$user_id]);
                $oldPhoto = $oldStmt->fetchColumn();
                if ($oldPhoto && file_exists($oldPhoto)) {
                    unlink($oldPhoto);
                }

                $check = $pdo->prepare("SELECT info_id FROM tbl_personal_info WHERE user_id = ?");
                $check->execute([$user_id]);

                if ($check->fetch()) {
                    $stmt = $pdo->prepare("UPDATE tbl_personal_info SET photo_path = ? WHERE user_id = ?");
                    $stmt->execute([$filePath, $user_id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO tbl_personal_info (user_id, photo_path, app_status) VALUES (?, ?, 'draft')");
                    $stmt->execute([$user_id, $filePath]);
                }

                echo json_encode(['status' => 'success', 'message' => 'Photo uploaded!', 'photo_path' => $filePath]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to save photo.']);
            }
            exit;
        }

        // REMOVE PHOTO
        if ($action === 'remove_photo') {
            $oldStmt = $pdo->prepare("SELECT photo_path FROM tbl_personal_info WHERE user_id = ?");
            $oldStmt->execute([$user_id]);
            $oldPhoto = $oldStmt->fetchColumn();

            if ($oldPhoto && file_exists($oldPhoto)) {
                unlink($oldPhoto);
            }

            $stmt = $pdo->prepare("UPDATE tbl_personal_info SET photo_path = NULL WHERE user_id = ?");
            $stmt->execute([$user_id]);

            echo json_encode(['status' => 'success', 'message' => 'Photo removed.']);
            exit;
        }

        // UPLOAD REQUIREMENTS
        if ($action === 'upload_requirement') {
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error.']);
                exit;
            }

            $file = $_FILES['file'];
            $maxSize = 5 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                echo json_encode(['status' => 'error', 'message' => 'File too large. Max 5MB.']);
                exit;
            }

            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid file type.']);
                exit;
            }

            $uploadDir = 'requirements/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $newFileName = uniqid() . '_' . $user_id . '.' . $ext;
            $filePath = $uploadDir . $newFileName;

            // Get requirement type (cor, grades, id)
            $reqType = $_POST['requirement_type'] ?? 'other';

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $stmt = $pdo->prepare("INSERT INTO tbl_requirements (user_id, file_name, file_path, file_size, requirement_type, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$user_id, $file['name'], $filePath, $file['size'], $reqType]);
                echo json_encode(['status' => 'success', 'message' => 'File uploaded successfully!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to move file.']);
            }
            exit;
        }

        // SUBMIT APPLICATION
        if ($action === 'submit_application') {
            $stmt = $pdo->prepare("UPDATE tbl_personal_info SET app_status = 'pending' WHERE user_id = ? AND (app_status = 'draft' OR app_status IS NULL)");
            $stmt->execute([$user_id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Application submitted successfully!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Application already submitted or no record found.']);
            }
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrantGate | Student Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --green: #00c853; --green-glow: rgba(0,200,83,0.4); --green-dim: rgba(0,200,83,0.08);
            --dark-bg: #0f172a; --darker: #0b1121; --card-bg: rgba(15,23,42,0.85); --sidebar-bg: #0d1424;
            --fg: #ffffff; --muted: #64748b; --input-bg: rgba(255,255,255,0.05); --input-border: rgba(255,255,255,0.1);
            --input-focus: rgba(0,200,83,0.25); --danger: #ff4757; --warning: #ffc93c; --success: #00c853;
            --radius: 14px;
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        html{scroll-behavior:smooth}
        body{font-family:'Poppins',sans-serif;background:var(--dark-bg);color:var(--fg);min-height:100vh;display:flex;overflow-x:hidden}

        .topbar{position:fixed;top:0;left:0;right:0;height:64px;background:rgba(11,20,36,0.95);backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:space-between;padding:0 32px;z-index:1000}
        .topbar-logo{font-family:'Orbitron',sans-serif;font-size:1.3rem;font-weight:900;color:#fff}
        .topbar-logo span{color:var(--green)}
        .topbar-right{display:flex;align-items:center;gap:16px}
        .topbar-user{display:flex;align-items:center;gap:10px;color:var(--muted);font-size:14px;font-weight:500;text-decoration:none;cursor:pointer;transition:all 0.3s;border-radius:50px;padding:8px 8px;margin-right:-8px}
        .topbar-user:hover{color:var(--fg);background:rgba(255,255,255,0.05)}
        .topbar-user:hover .topbar-avatar{box-shadow:0 0 0 2px var(--green)}
        .topbar-avatar{width:36px;height:36px;border-radius:50%;background:var(--green);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px}
        .logout-btn{background:rgba(255,71,87,0.1);border:1px solid rgba(255,71,87,0.3);color:#ff6b7a;padding:8px 18px;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.3s;text-decoration:none}
        .logout-btn:hover{background:var(--danger);color:#fff;border-color:var(--danger)}

        .sidebar{position:fixed;top:64px;left:0;bottom:0;width:280px;background:var(--sidebar-bg);border-right:1px solid rgba(255,255,255,0.05);padding:32px 0;z-index:900;display:flex;flex-direction:column}
        .sidebar-title{font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;color:var(--muted);letter-spacing:0.15em;text-transform:uppercase;padding:0 24px;margin-bottom:20px}
        .step-list{list-style:none;flex:1}
        .step-item{display:flex;align-items:center;gap:14px;padding:14px 24px;cursor:pointer;transition:all 0.3s;position:relative;color:var(--muted);font-size:14px;font-weight:500}
        .step-item:hover{background:rgba(255,255,255,0.03);color:var(--fg)}
        .step-item.active{color:#fff;background:var(--green-dim)}
        .step-item.active::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--green);border-radius:0 3px 3px 0}
        .step-item.completed{color:var(--green)}
        .step-num{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Orbitron',sans-serif;font-size:0.75rem;font-weight:700;border:2px solid var(--input-border);flex-shrink:0;transition:all 0.3s}
        .step-item.active .step-num{border-color:var(--green);background:var(--green);color:#fff;box-shadow:0 0 15px var(--green-glow)}
        .step-item.completed .step-num{border-color:var(--green);background:var(--green);color:#fff}
        .step-connector{width:2px;height:20px;background:var(--input-border);margin-left:39px;margin-top:-4px;margin-bottom:-4px}
        .step-connector.done{background:var(--green)}

        .main-content{margin-left:280px;margin-top:64px;flex:1;padding:40px;min-height:calc(100vh - 64px)}
        .page-header{margin-bottom:36px}
        .page-header h1{font-family:'Orbitron',sans-serif;font-size:1.8rem;font-weight:900;margin-bottom:8px}
        .page-header h1 span{color:var(--green)}
        .page-header p{color:var(--muted);font-size:0.95rem;line-height:1.6}

        .step-panel{display:none;animation:panelIn 0.4s ease forwards}
        .step-panel.active{display:block}
        @keyframes panelIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

        .form-card{background:var(--card-bg);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.06);border-radius:var(--radius);padding:36px;margin-bottom:24px}
        .form-card h2{font-family:'Orbitron',sans-serif;font-size:1.1rem;font-weight:700;margin-bottom:24px;color:var(--fg)}
        .form-card h2 i{color:var(--green);margin-right:10px}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
        .form-row.full{grid-template-columns:1fr}
        .field{position:relative}
        .field label{display:block;font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px}
        .field input,.field select,.field textarea{width:100%;padding:13px 16px;background:var(--input-bg);border:1.5px solid var(--input-border);border-radius:10px;color:var(--fg);font-family:'Poppins',sans-serif;font-size:14px;outline:none;transition:all 0.3s}
        .field input:focus,.field select:focus,.field textarea:focus{border-color:var(--green);background:var(--green-dim);box-shadow:0 0 0 3px var(--input-focus)}
        .field select{cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center}
        .field select option{background:var(--dark-bg);color:var(--fg)}
        .field textarea{resize:vertical;min-height:100px}

        /* PHOTO UPLOAD */
        .photo-upload-row{display:flex;align-items:center;gap:28px;margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid rgba(255,255,255,0.06)}
        .photo-preview-wrap{position:relative;width:140px;height:140px;flex-shrink:0}
        .photo-preview{width:140px;height:140px;border-radius:50%;border:2px dashed var(--input-border);display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--muted);transition:all 0.3s;overflow:hidden;background:var(--input-bg);cursor:pointer}
        .photo-preview:hover{border-color:var(--green);background:var(--green-dim)}
        .photo-preview i{font-size:28px;margin-bottom:6px;transition:color 0.3s}
        .photo-preview:hover i{color:var(--green)}
        .photo-preview span{font-size:11px;font-weight:600}
        .photo-preview img{width:100%;height:100%;object-fit:cover}
        .photo-preview.has-photo{border-style:solid;border-color:var(--green)}
        .photo-preview.has-photo:hover{border-color:var(--warning)}
        .photo-preview-wrap input[type="file"]{position:absolute;inset:0;opacity:0;cursor:pointer;border-radius:50%}
        .photo-upload-info{flex:1}
        .photo-upload-info h3{font-size:15px;font-weight:600;margin-bottom:6px;color:var(--fg)}
        .photo-upload-info p{font-size:12px;color:var(--muted);line-height:1.6;margin-bottom:10px}
        .photo-upload-info .photo-status{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;padding:5px 12px;border-radius:20px}
        .photo-status.uploaded{background:rgba(0,200,83,0.1);color:var(--green);border:1px solid rgba(0,200,83,0.2)}
        .photo-status.missing{background:rgba(255,71,87,0.1);color:var(--danger);border:1px solid rgba(255,71,87,0.2)}
        .photo-remove-btn{background:none;border:none;color:var(--danger);cursor:pointer;font-size:12px;font-weight:600;margin-left:10px;text-decoration:underline;transition:opacity 0.3s}
        .photo-remove-btn:hover{opacity:0.7}
        .photo-preview.invalid{border-color:var(--danger)!important;box-shadow:0 0 0 3px rgba(255,71,87,0.2)!important;animation:shake 0.4s ease}
        @keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-6px)}75%{transform:translateX(6px)}}

        /* REQUIREMENTS UPLOAD ZONE */
        .upload-zone{border:2px dashed var(--input-border);border-radius:var(--radius);padding:40px;text-align:center;cursor:pointer;transition:all 0.3s;position:relative}
        .upload-zone:hover{border-color:var(--green);background:var(--green-dim)}
        .upload-zone i.upload-icon{font-size:40px;color:var(--muted);margin-bottom:12px;display:block;transition:color 0.3s}
        .upload-zone:hover i.upload-icon{color:var(--green)}
        .upload-zone p{color:var(--muted);font-size:14px}
        .upload-zone p strong{color:var(--green)}
        .upload-zone input{position:absolute;inset:0;opacity:0;cursor:pointer}
        .upload-zone.invalid{border-color:var(--danger)!important;box-shadow:0 0 0 3px rgba(255,71,87,0.2)!important;animation:shake 0.4s ease}

        .upload-list{margin-top:16px;display:flex;flex-direction:column;gap:8px}
        .upload-item{display:flex;align-items:center;justify-content:space-between;padding:10px 16px;background:rgba(0,200,83,0.06);border:1px solid rgba(0,200,83,0.15);border-radius:10px}
        .upload-item-info{display:flex;align-items:center;gap:10px;font-size:13px}
        .upload-item-info i{color:var(--green)}
        .upload-item-remove{background:none;border:none;color:var(--danger);cursor:pointer;font-size:14px;transition:transform 0.2s}
        .upload-item-remove:hover{transform:scale(1.2)}

        /* STATUS CARDS */
        .status-card{background:var(--card-bg);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.06);border-radius:var(--radius);padding:32px;text-align:center}
        .status-icon{width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:32px}
        .status-icon.pending{background:rgba(255,201,60,0.1);border:2px solid rgba(255,201,60,0.3);color:var(--warning)}
        .status-icon.approved{background:rgba(0,200,83,0.1);border:2px solid rgba(0,200,83,0.3);color:var(--success)}
        .status-icon.rejected{background:rgba(255,71,87,0.1);border:2px solid rgba(255,71,87,0.3);color:var(--danger)}
        .status-card h2{font-family:'Orbitron',sans-serif;font-size:1.3rem;margin-bottom:8px}
        .status-card p{color:var(--muted);font-size:14px;line-height:1.6;max-width:400px;margin:0 auto}
        .status-timeline{margin-top:32px;text-align:left;max-width:500px;margin-left:auto;margin-right:auto}
        .timeline-item{display:flex;gap:16px;padding-bottom:24px;position:relative}
        .timeline-item:last-child{padding-bottom:0}
        .timeline-dot{width:14px;height:14px;border-radius:50%;border:2px solid var(--input-border);flex-shrink:0;margin-top:4px;position:relative;z-index:1}
        .timeline-dot.done{background:var(--green);border-color:var(--green)}
        .timeline-dot.current{background:var(--warning);border-color:var(--warning);box-shadow:0 0 10px rgba(255,201,60,0.3)}
        .timeline-item:not(:last-child)::before{content:'';position:absolute;left:6px;top:18px;bottom:0;width:2px;background:var(--input-border)}
        .timeline-item:not(:last-child) .timeline-dot.done::after{content:'';position:absolute;left:5px;top:12px;bottom:-24px;width:2px;background:var(--green)}
        .timeline-text h4{font-size:14px;font-weight:600;margin-bottom:2px}
        .timeline-text p{font-size:12px;color:var(--muted)}

        /* BUTTONS */
        .btn-group{display:flex;gap:12px;margin-top:32px;justify-content:flex-end}
        .btn{padding:13px 32px;border-radius:10px;font-family:'Orbitron',sans-serif;font-size:0.8rem;font-weight:700;cursor:pointer;transition:all 0.3s;letter-spacing:0.05em;border:none;display:inline-flex;align-items:center;gap:8px}
        .btn-primary{background:var(--green);color:#fff;box-shadow:0 4px 20px var(--green-glow)}
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 30px var(--green-glow)}
        .btn-secondary{background:var(--input-bg);color:var(--fg);border:1.5px solid var(--input-border)}
        .btn-secondary:hover{border-color:var(--green);color:var(--green)}
        .btn:active{transform:scale(0.97)!important}

        /* TOAST */
        .toast-box{position:fixed;top:80px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px}
        .toast{padding:14px 22px;border-radius:12px;font-size:13px;font-weight:500;backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:10px;min-width:240px;box-shadow:0 8px 30px rgba(0,0,0,0.4);animation:toastIn 0.4s cubic-bezier(0.22,1,0.36,1) forwards}
        .toast.success{background:rgba(0,200,83,0.15);border-color:rgba(0,200,83,0.3);color:#a7f3d0}
        .toast.error{background:rgba(255,71,87,0.15);border-color:rgba(255,71,87,0.3);color:#fca5a5}
        .toast.info{background:rgba(96,165,250,0.15);border-color:rgba(96,165,250,0.3);color:#bfdbfe}
        .toast.out{animation:toastOut 0.35s forwards}
        @keyframes toastIn{from{transform:translateX(120%);opacity:0}to{transform:translateX(0);opacity:1}}
        @keyframes toastOut{to{transform:translateX(120%);opacity:0}}

        /* RESPONSIVE */
        @media(max-width:900px){
            .sidebar{width:64px;padding:20px 0}.sidebar-title{display:none}.step-item span{display:none}.step-item{padding:14px 0;justify-content:center}.step-connector{margin-left:15px}.main-content{margin-left:64px;padding:24px}.form-row{grid-template-columns:1fr}.photo-upload-row{flex-direction:column;text-align:center}
        }
        @media(max-width:600px){.sidebar{display:none}.main-content{margin-left:0;padding:20px}.topbar{padding:0 16px}}

        .field input.invalid, .field select.invalid {border-color: var(--danger) !important;box-shadow: 0 0 0 3px rgba(255,71,87,0.2) !important;}
    </style>
</head>
<body>

    <div class="topbar">
        <div class="topbar-logo">GRANT<span>GATE</span></div>
        <div class="topbar-right">
            <a href="student_profile.php" class="topbar-user">
                <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['firstname'] ?? 'U', 0, 1)) ?></div>
                <span><?= htmlspecialchars(($_SESSION['firstname'] ?? '') . ' ' . ($_SESSION['lastname'] ?? '')) ?></span>
            </a>
            <a href="dashboard.php?logout=true" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <nav class="sidebar">
        <div class="sidebar-title">Application Steps</div>
        <ul class="step-list">
            <li class="step-item active" onclick="goToStep(1)"><div class="step-num">1</div><span>Application Form</span></li>
            <div class="step-connector" id="conn1"></div>
            <li class="step-item" onclick="goToStep(2)"><div class="step-num">2</div><span>Upload Requirements</span></li>
            <div class="step-connector" id="conn2"></div>
            <li class="step-item" onclick="goToStep(3)"><div class="step-num">3</div><span>Review & Submit</span></li>
            <div class="step-connector" id="conn3"></div>
            <li class="step-item" onclick="goToStep(4)"><div class="step-num">4</div><span>Status Tracker</span></li>
        </ul>
    </nav>

    <div class="toast-box" id="toastBox"></div>

    <div class="main-content">

        <!-- ===== STEP 1: APPLICATION FORM ===== -->
        <div class="step-panel active" id="step1">
            <div class="page-header">
                <h1>Fill Out <span>Application Form</span></h1>
                <p>Complete your personal information, family income details, and academic records to proceed with your scholarship application.</p>
            </div>

            <div class="form-card">
                <h2><i class="fas fa-user"></i>Personal Information</h2>
                <div class="photo-upload-row">
                    <div class="photo-preview-wrap" id="photoPreviewWrap">
                        <div class="photo-preview" id="photoPreview">
                            <i class="fas fa-camera"></i>
                            <span>Upload Photo</span>
                        </div>
                        <input type="file" accept=".jpg,.jpeg,.png" id="photoInput" onchange="handlePhotoUpload(this)">
                    </div>
                    <div class="photo-upload-info">
                        <h3>2×2 ID Photo <span style="color:var(--danger)">*</span></h3>
                        <p>Upload a recent 2×2 ID photo with white background.</p>
                        <div class="photo-status missing" id="photoStatusBar">
                            <i class="fas fa-exclamation-circle"></i>
                            <span id="photoStatusText">No photo uploaded</span>
                            <button type="button" class="photo-remove-btn" id="photoRemoveBtn" onclick="removePhoto()" style="display:none">Remove</button>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="field"><label>First Name</label><input type="text" id="fname" name="firstname" placeholder="Enter first name"></div>
                    <div class="field"><label>Last Name</label><input type="text" id="lname" name="lastname" placeholder="Enter last name"></div>
                </div>
                <div class="form-row">
                    <div class="field"><label>Date of Birth</label><input type="date" id="dob" name="birthday"></div>
                    <div class="field"><label>Gender</label><select id="gender" name="gender"><option value="">Select gender</option><option>Male</option><option>Female</option></select></div>
                </div>
                <div class="form-row">
                    <div class="field"><label>Contact Number</label><input type="tel" id="phone" name="contact_no" placeholder="+63 9XX XXX XXXX"></div>
                    <div class="field"><label>Email Address</label><input type="email" id="email" name="email_add" placeholder="student@email.com"></div>
                </div>
                <div class="form-row full">
                    <div class="field"><label>Home Address</label><input type="text" id="address" name="home_address" placeholder="Complete address"></div>
                </div>
            </div>

            <div class="form-card">
                <h2><i class="fas fa-wallet"></i>Parent / Guardian</h2>
                <div class="form-row">
                    <div class="field"><label>Parent/Guardian Name</label><input type="text" id="parentName" name="guardian_fullname" placeholder="Full name"></div>
                    <div class="field"><label>Contact Number</label><input type="tel" id="parentPhone" name="guardian_contact_no" placeholder="+63 9XX XXX XXXX"></div>
                </div>
                <div class="form-row">
                    <div class="field"><label>Occupation</label><input type="text" id="occupation" name="occupation" placeholder="e.g. Farmer, Teacher"></div>
                    <div class="field"><label>Estimated Annual Income</label><select id="income" name="income"><option value="">Select range</option><option>Below ₱100,000</option><option>₱100,000 - ₱250,000</option><option>₱250,000 - ₱500,000</option><option>Above ₱500,000</option></select></div>
                </div>
            </div>

            <div class="form-card">
                <h2><i class="fas fa-graduation-cap"></i>Academic Records</h2>
                <div class="form-row">
                    <div class="field"><label>School / University</label><input type="text" id="school" name="school" placeholder="Current school name"></div>
                    <div class="field"><label>Year Level</label><select id="yearLevel" name="year_level"><option value="">Select year</option><option>1st Year</option><option>2nd Year</option><option>3rd Year</option><option>4th Year</option></select></div>
                </div>
                <div class="form-row">
                    <div class="field"><label>Course / Program</label><input type="text" id="course" name="course" placeholder="e.g. BSIT, BSED"></div>
                    <div class="field"><label>GWA (Previous Semester)</label><input type="text" id="gwa" name="gwa" placeholder="e.g. 1.50"></div>
                </div>
            </div>

            <div class="btn-group">
                <button class="btn btn-primary" onclick="nextStep(2)">Next Step <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>

        <!-- ===== STEP 2: UPLOAD REQUIREMENTS ===== -->
        <div class="step-panel" id="step2">
            <div class="page-header">
                <h1>Upload <span>Requirements</span></h1>
                <p>Upload the required documents for your scholarship application. Accepted formats: PDF, JPG, PNG (max 5MB each).</p>
            </div>

            <div class="form-card">
                <h2><i class="fas fa-file-alt"></i>Certificate of Registration (COR) <span style="color:var(--danger)">*</span></h2>
                <div class="upload-zone" id="corZone">
                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                    <p>Drag & drop or <strong>click to browse</strong></p>
                    <input type="file" accept=".pdf,.jpg,.jpeg,.png" onchange="handleUpload(this,'corList','cor')">
                </div>
                <div class="upload-list" id="corList"></div>
            </div>

            <div class="form-card">
                <h2><i class="fas fa-chart-bar"></i>Report Card / Grades <span style="color:var(--danger)">*</span></h2>
                <div class="upload-zone" id="gradesZone">
                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                    <p>Drag & drop or <strong>click to browse</strong></p>
                    <input type="file" accept=".pdf,.jpg,.jpeg,.png" onchange="handleUpload(this,'gradesList','grades')">
                </div>
                <div class="upload-list" id="gradesList"></div>
            </div>

            <div class="form-card">
                <h2><i class="fas fa-id-card"></i>Valid ID <span style="color:var(--danger)">*</span></h2>
                <div class="upload-zone" id="idZone">
                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                    <p>Drag & drop or <strong>click to browse</strong></p>
                    <input type="file" accept=".pdf,.jpg,.jpeg,.png" onchange="handleUpload(this,'idList','id')">
                </div>
                <div class="upload-list" id="idList"></div>
            </div>

            <div class="btn-group">
                <button class="btn btn-secondary" onclick="goToStep(1)"><i class="fas fa-arrow-left"></i> Back</button>
                <button class="btn btn-primary" onclick="nextStep(3)">Next Step <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>

        <!-- ===== STEP 3: REVIEW & SUBMIT ===== -->
        <div class="step-panel" id="step3">
            <div class="page-header">
                <h1>Review & <span>Submit</span></h1>
                <p>Review all your information before submitting. Once submitted, your application will be pending for admin review.</p>
            </div>

            <div class="form-card">
                <h2><i class="fas fa-clipboard-check"></i>Application Summary</h2>
                <div id="reviewSummary" style="color:var(--muted);font-size:14px;line-height:2"></div>
            </div>

            <div class="form-card">
                <h2><i class="fas fa-paper-plane"></i>Submit Application</h2>
                <p style="color:var(--muted);font-size:14px;line-height:1.8;margin-bottom:20px">By clicking submit, you confirm that all information provided is true and correct.</p>
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:14px;color:var(--muted);user-select:none">
                    <input type="checkbox" id="confirmCheck" style="width:18px;height:18px;accent-color:var(--green)">
                    I confirm that all information is accurate and complete.
                </label>
            </div>

            <div class="btn-group">
                <button class="btn btn-secondary" onclick="goToStep(2)"><i class="fas fa-arrow-left"></i> Back</button>
                <button class="btn btn-primary" onclick="submitApplication()"><i class="fas fa-paper-plane"></i> Submit Application</button>
            </div>
        </div>

        <!-- ===== STEP 4: STATUS TRACKER ===== -->
        <div class="step-panel" id="step4">
            <div class="page-header">
                <h1>Application <span>Status</span></h1>
                <p>Track the progress of your scholarship application below.</p>
            </div>

            <?php 
                $appStatus = $existingInfo['app_status'] ?? 'pending';
                if ($appStatus === 'draft') {
                    $statusIcon = 'pending'; $statusText = 'Draft (Not Submitted)'; $statusDesc = 'Your application is still in progress. Please complete all steps and submit.';
                } else {
                    $statusIcon = $appStatus === 'approved' ? 'approved' : ($appStatus === 'rejected' ? 'rejected' : 'pending');
                    $statusText = ucfirst($appStatus);
                    $statusDesc = match($appStatus) {
                        'approved' => 'Congratulations! Your scholarship application has been approved.', 'rejected' => 'We\'re sorry, your application was not approved.',
                        default => 'Your application has been submitted and is currently being reviewed by our admin team.'
                    };
                }
            ?>

            <div class="status-card" id="statusCard">
                <div class="status-icon <?= $statusIcon ?>" id="statusIcon">
                    <i class="fas fa-<?= $appStatus === 'approved' ? 'check-circle' : ($appStatus === 'rejected' ? 'times-circle' : 'clock') ?>"></i>
                </div>
                <h2 id="statusTitle"><?= $statusText ?></h2>
                <p id="statusDesc"><?= $statusDesc ?></p>

                <div class="status-timeline" id="statusTimeline">
                    <div class="timeline-item"><div class="timeline-dot done"></div><div class="timeline-text"><h4>Application Submitted</h4><p>Your form and documents were received.</p></div></div>
                    <div class="timeline-item"><div class="timeline-dot <?= $appStatus !== 'pending' ? 'done' : 'current' ?>"></div><div class="timeline-text"><h4><?= $appStatus !== 'pending' ? 'Reviewed' : 'Under Admin Review' ?></h4><p><?= $appStatus !== 'pending' ? 'Admin has reviewed your application.' : 'Admin is verifying your documents.' ?></p></div></div>
                    <div class="timeline-item"><div class="timeline-dot <?= $appStatus !== 'pending' ? 'done' : '' ?>"></div><div class="timeline-text"><h4>Result Notification</h4><p><?= $appStatus !== 'pending' ? 'A decision has been made.' : 'You will receive the result via portal or email.' ?></p></div></div>
                </div>
                
                <div style="margin-top: 32px; display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
                    <button class="btn btn-secondary" onclick="goToStep(1)"><i class="fas fa-pen"></i> Update Application</button>
                    <a href="student_profile.php" class="btn btn-primary" style="text-decoration:none"><i class="fas fa-user-circle"></i> View Full Profile</a>
                </div>
            </div>
        </div>

    </div>

    <script>
        let currentStep = 1;
        const completedSteps = new Set();
        let isSubmitted = false;
        let photoUploaded = false; 

        const hasExistingInfo = <?= $existingInfo ? 'true' : 'false' ?>;
        const appStatus = '<?= htmlspecialchars($existingInfo['app_status'] ?? 'pending') ?>';
        const isLocked = (appStatus === 'approved' || appStatus === 'rejected');
        const existingPhotoPath = <?= json_encode($existingInfo['photo_path'] ?? null) ?>;
        const existingReqs = <?= json_encode($existingReqs ?? []) ?>;

        if (existingPhotoPath) photoUploaded = true;

        if (hasExistingInfo && appStatus !== 'draft') {
            currentStep = 4;
            completedSteps.add(1); completedSteps.add(2); completedSteps.add(3);
            isSubmitted = true;
        }

        function goToStep(step) {
            if (step > currentStep && !completedSteps.has(step - 1) && step !== 1) {
                showToast('Complete the current step first.', 'error'); return;
            }
            currentStep = step; updateUI();
        }

        async function nextStep(step) {
            if (isLocked) { showToast('Your application has been ' + appStatus + '. Editing is disabled.', 'error'); return; }

            if (step === 2) {
                const photoPreview = document.getElementById('photoPreview');
                if (!photoUploaded) {
                    photoPreview.classList.add('invalid'); showToast('Please upload your 2×2 ID photo before proceeding.', 'error');
                    document.getElementById('photoPreviewWrap').scrollIntoView({ behavior: 'smooth', block: 'center' }); return;
                }
                photoPreview.classList.remove('invalid');

                let allFilled = true;
                document.querySelectorAll('#step1 input[name], #step1 select[name]').forEach(el => {
                    el.classList.remove('invalid'); if (!el.value.trim()) { allFilled = false; el.classList.add('invalid'); }
                });
                if (!allFilled) { showToast('All fields are mandatory. Please complete the form.', 'error'); return; }

                const btn = document.querySelector('#step1 .btn-primary');
                btn.innerHTML = 'Saving... <i class="fas fa-spinner fa-spin"></i>'; btn.disabled = true;
                const data = new URLSearchParams(); data.append('action', 'save_personal_info');
                document.querySelectorAll('#step1 input[name], #step1 select[name]').forEach(el => { if (el.name) data.append(el.name, el.value); });

                try {
                    const res = await fetch('dashboard.php', { method: 'POST', body: data });
                    const json = await res.json();
                    if (json.status === 'success') { completedSteps.add(currentStep); currentStep = step; updateUI(); showToast(json.message, 'success'); }
                    else { showToast(json.message || 'Failed to save info.', 'error'); return; }
                } catch(e) { showToast('Network error. Failed to save info.', 'error'); return; }
                btn.innerHTML = 'Next Step <i class="fas fa-arrow-right"></i>'; btn.disabled = false; return;
            }

            // ========== MANDATORY REQUIREMENTS VALIDATION ==========
            if (step === 3) {
                let missingDocs = [];
                document.querySelectorAll('.upload-zone').forEach(z => z.classList.remove('invalid')); // clear previous

                if (!document.getElementById('corList').querySelector('.upload-item')) {
                    missingDocs.push('COR'); document.getElementById('corZone').classList.add('invalid');
                }
                if (!document.getElementById('gradesList').querySelector('.upload-item')) {
                    missingDocs.push('Grades'); document.getElementById('gradesZone').classList.add('invalid');
                }
                if (!document.getElementById('idList').querySelector('.upload-item')) {
                    missingDocs.push('Valid ID'); document.getElementById('idZone').classList.add('invalid');
                }

                if (missingDocs.length > 0) {
                    showToast('Please upload: ' + missingDocs.join(', '), 'error'); return;
                }

                completedSteps.add(currentStep); currentStep = step; updateUI(); buildSummary();
                showToast('Step 2 completed!', 'success'); return;
            }

            completedSteps.add(currentStep); currentStep = step; updateUI();
            if (step === 3) buildSummary();
            showToast('Step ' + (step - 1) + ' completed!', 'success');
        }

        function updateUI() {
            document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
            document.getElementById('step' + currentStep).classList.add('active');
            document.querySelectorAll('.step-item').forEach((item, i) => {
                item.classList.remove('active', 'completed');
                if (i + 1 === currentStep) item.classList.add('active');
                if (completedSteps.has(i + 1)) item.classList.add('completed');
            });
            for (let i = 1; i <= 3; i++) { const conn = document.getElementById('conn' + i); if (conn) conn.classList.toggle('done', completedSteps.has(i)); }
            document.querySelectorAll('.step-num').forEach((num, i) => {
                if (completedSteps.has(i + 1)) num.innerHTML = '<i class="fas fa-check" style="font-size:12px"></i>';
                else num.textContent = i + 1;
            });
        }

        function buildSummary() {
            const fields = [['First Name','fname'],['Last Name','lname'],['Date of Birth','dob'],['Gender','gender'],['Contact','phone'],['Email','email'],['Address','address'],['Parent/Guardian','parentName'],['Parent Contact','parentPhone'],['Occupation','occupation'],['Annual Income','income'],['School','school'],['Year Level','yearLevel'],['Course','course'],['GWA','gwa']];
            const photoPreview = document.getElementById('photoPreview'); const photoImg = photoPreview.querySelector('img');
            let html = '<div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;padding:16px;background:rgba(0,200,83,0.05);border-radius:12px;border:1px solid rgba(0,200,83,0.1)">';
            if (photoImg) html += '<img src="'+photoImg.src+'" style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid var(--green)">';
            else html += '<div style="width:60px;height:60px;border-radius:50%;background:rgba(255,71,87,0.1);border:2px solid rgba(255,71,87,0.3);display:flex;align-items:center;justify-content:center"><i class="fas fa-user" style="color:var(--danger)"></i></div>';
            html += '<div><strong style="color:var(--green)">ID Photo:</strong> '+(photoImg?'<span style="color:var(--green)"><i class="fas fa-check-circle"></i> Uploaded</span>':'<em style="color:var(--danger)">Missing</em>')+'</div></div>';
            
            // Requirements Status
            const corUploaded = !!document.getElementById('corList').querySelector('.upload-item');
            const gradesUploaded = !!document.getElementById('gradesList').querySelector('.upload-item');
            const idUploaded = !!document.getElementById('idList').querySelector('.upload-item');
            html += '<div style="margin-bottom:20px;padding:16px;background:rgba(0,200,83,0.05);border-radius:12px;border:1px solid rgba(0,200,83,0.1)">';
            html += '<strong style="color:var(--green)">Requirements:</strong><br>COR: '+(corUploaded?'<span style="color:var(--green)"><i class="fas fa-check-circle"></i></span>':'<em style="color:var(--danger)">Missing</em>');
            html += ' &nbsp;|&nbsp; Grades: '+(gradesUploaded?'<span style="color:var(--green)"><i class="fas fa-check-circle"></i></span>':'<em style="color:var(--danger)">Missing</em>');
            html += ' &nbsp;|&nbsp; Valid ID: '+(idUploaded?'<span style="color:var(--green)"><i class="fas fa-check-circle"></i></span>':'<em style="color:var(--danger)">Missing</em>')+'</div>';

            html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 32px">';
            fields.forEach(([label, id]) => { const val = document.getElementById(id).value || '<em style="color:var(--danger)">Not filled</em>'; html += '<div><strong style="color:var(--green)">'+label+':</strong> '+val+'</div>'; });
            html += '</div>';
            document.getElementById('reviewSummary').innerHTML = html;
        }

        async function submitApplication() {
            const checked = document.getElementById('confirmCheck').checked;
            if (!checked) { showToast('Please confirm your information is accurate.', 'error'); return; }
            const btn = document.querySelector('#step3 .btn-primary'); const origHTML = btn.innerHTML;
            btn.innerHTML = 'Submitting... <i class="fas fa-spinner fa-spin"></i>'; btn.disabled = true;
            const data = new URLSearchParams(); data.append('action', 'submit_application');
            try {
                const res = await fetch('dashboard.php', { method: 'POST', body: data }); const json = await res.json();
                if (json.status === 'success') { isSubmitted = true; completedSteps.add(3); currentStep = 4; updateUI(); showToast(json.message, 'success'); }
                else showToast(json.message || 'Failed to submit application.', 'error');
            } catch(e) { showToast('Network error. Failed to submit application.', 'error'); }
            btn.innerHTML = origHTML; btn.disabled = false;
        }

        async function handlePhotoUpload(input) {
            const file = input.files[0]; if (!file) return;
            if (file.size > 5 * 1024 * 1024) { showToast('Photo too large. Max 5MB.', 'error'); return; }
            const allowed = ['jpg', 'jpeg', 'png']; const ext = file.name.split('.').pop().toLowerCase();
            if (!allowed.includes(ext)) { showToast('Only JPG and PNG files are allowed.', 'error'); return; }
            const preview = document.getElementById('photoPreview');
            preview.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:28px;color:var(--green)"></i><span style="font-size:11px;margin-top:6px">Uploading...</span>';
            preview.classList.remove('invalid');
            const formData = new FormData(); formData.append('action', 'upload_photo'); formData.append('photo', file);
            try {
                const res = await fetch('dashboard.php', { method: 'POST', body: formData }); const json = await res.json();
                if (json.status === 'success') { photoUploaded = true; preview.innerHTML = '<img src="'+json.photo_path+'" alt="ID Photo">'; preview.classList.add('has-photo'); updatePhotoStatus(true); showToast('Photo uploaded successfully!', 'success'); }
                else { resetPhotoPreview(); showToast(json.message || 'Photo upload failed.', 'error'); }
            } catch(e) { resetPhotoPreview(); showToast('Network error during photo upload.', 'error'); }
            input.value = '';
        }

        async function removePhoto() {
            if (isLocked) return;
            const preview = document.getElementById('photoPreview');
            preview.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:28px;color:var(--green)"></i><span style="font-size:11px;margin-top:6px">Removing...</span>';
            const data = new URLSearchParams(); data.append('action', 'remove_photo');
            try {
                const res = await fetch('dashboard.php', { method: 'POST', body: data }); const json = await res.json();
                if (json.status === 'success') { photoUploaded = false; resetPhotoPreview(); updatePhotoStatus(false); showToast('Photo removed.', 'info'); }
                else showToast(json.message || 'Failed to remove photo.', 'error');
            } catch(e) { showToast('Network error.', 'error'); }
        }

        function resetPhotoPreview() { const preview = document.getElementById('photoPreview'); preview.innerHTML = '<i class="fas fa-camera"></i><span>Upload Photo</span>'; preview.classList.remove('has-photo', 'invalid'); }
        function updatePhotoStatus(uploaded) {
            const statusBar = document.getElementById('photoStatusBar'); const statusText = document.getElementById('photoStatusText'); const removeBtn = document.getElementById('photoRemoveBtn');
            if (uploaded) { statusBar.className = 'photo-status uploaded'; statusBar.querySelector('i').className = 'fas fa-check-circle'; statusText.textContent = 'Photo uploaded'; removeBtn.style.display = 'inline-block'; }
            else { statusBar.className = 'photo-status missing'; statusBar.querySelector('i').className = 'fas fa-exclamation-circle'; statusText.textContent = 'No photo uploaded'; removeBtn.style.display = 'none'; }
        }

        async function handleUpload(input, listId, reqType) {
            const list = document.getElementById(listId); const file = input.files[0]; if (!file) return;
            if (file.size > 5 * 1024 * 1024) { showToast('File too large. Max 5MB.', 'error'); return; }
            const item = document.createElement('div'); item.className = 'upload-item';
            item.innerHTML = '<div class="upload-item-info"><i class="fas fa-spinner fa-spin" style="color:var(--green)"></i><span>Uploading '+file.name+'...</span></div>';
            list.appendChild(item);
            const formData = new FormData(); formData.append('action', 'upload_requirement'); formData.append('file', file); formData.append('requirement_type', reqType);
            try {
                const res = await fetch('dashboard.php', { method: 'POST', body: formData }); const json = await res.json();
                if (json.status === 'success') {
                    item.innerHTML = '<div class="upload-item-info"><i class="fas fa-file"></i><span>'+file.name+'</span><span style="color:var(--muted);font-size:11px">('+(file.size / 1024).toFixed(0)+' KB)</span></div><button class="upload-item-remove" onclick="removeRequirementItem(this)"><i class="fas fa-times"></i></button>';
                    showToast('File uploaded: '+file.name, 'success');
                    // Remove invalid border if it exists
                    const zone = input.closest('.upload-zone'); if (zone) zone.classList.remove('invalid');
                } else { item.remove(); showToast(json.message || 'Upload failed.', 'error'); }
            } catch(e) { item.remove(); showToast('Network error during upload.', 'error'); }
            input.value = '';
        }

        function removeRequirementItem(btn) {
            btn.parentElement.remove(); // Simple DOM removal. User must re-upload if proceeding.
        }

        function populateForm() {
            if (!hasExistingInfo) return;
            const data = <?= json_encode($existingInfo ?? []) ?>;
            const map = { firstname:'fname',lastname:'lname',birthday:'dob',gender:'gender',contact_no:'phone',email_add:'email',home_address:'address',guardian_fullname:'parentName',guardian_contact_no:'parentPhone',occupation:'occupation',income:'income',school:'school',year_level:'yearLevel',course:'course',gwa:'gwa' };
            Object.keys(map).forEach(key => { const el = document.getElementById(map[key]); if (el && data[key]) el.value = data[key]; });
            if (existingPhotoPath) { const preview = document.getElementById('photoPreview'); preview.innerHTML = '<img src="'+existingPhotoPath+'" alt="ID Photo">'; preview.classList.add('has-photo'); updatePhotoStatus(true); }

            // Pre-populate existing requirements
            const reqLists = { 'cor':'corList', 'grades':'gradesList', 'id':'idList' };
            existingReqs.forEach(req => {
                const listId = reqLists[req.requirement_type]; if(!listId) return;
                const list = document.getElementById(listId); if(!list) return;
                const item = document.createElement('div'); item.className = 'upload-item';
                const fileSizeKB = req.file_size ? (req.file_size / 1024).toFixed(0) : '?';
                item.innerHTML = '<div class="upload-item-info"><i class="fas fa-file"></i><span>'+req.file_name+'</span><span style="color:var(--muted);font-size:11px">('+fileSizeKB+' KB)</span></div><button class="upload-item-remove" onclick="removeRequirementItem(this)"><i class="fas fa-times"></i></button>';
                list.appendChild(item);
            });
        }

        function applyLockRules() {
            if (isLocked) {
                document.querySelectorAll('#step1 input, #step1 select').forEach(el => { el.disabled = true; el.style.opacity = '0.5'; el.style.cursor = 'not-allowed'; });
                const photoInput = document.getElementById('photoInput'); if (photoInput) { photoInput.disabled = true; photoInput.style.cursor = 'not-allowed'; }
                const photoRemoveBtn = document.getElementById('photoRemoveBtn'); if (photoRemoveBtn) photoRemoveBtn.style.display = 'none';
                const photoPreview = document.getElementById('photoPreview'); if (photoPreview) photoPreview.style.cursor = 'default';
                const step1Btn = document.querySelector('#step1 .btn-primary'); if (step1Btn) { step1Btn.disabled = true; step1Btn.style.opacity = '0.5'; step1Btn.style.cursor = 'not-allowed'; step1Btn.innerHTML = '<i class="fas fa-lock"></i> Application Locked'; }
                document.querySelectorAll('#step2 input[type="file"]').forEach(el => { el.disabled = true; el.style.opacity = '0.5'; el.style.cursor = 'not-allowed'; });
                document.querySelectorAll('#step2 .upload-item-remove').forEach(btn => btn.style.display = 'none');
                const updateBtn = document.querySelector('#step4 .btn-secondary'); if (updateBtn && updateBtn.textContent.includes('Update')) updateBtn.style.display = 'none';
                const step1Panel = document.getElementById('step1'); const lockNotice = document.createElement('div');
                lockNotice.style.cssText = 'background:rgba(255,71,87,0.1);border:1px solid rgba(255,71,87,0.3);color:#fca5a5;padding:14px 20px;border-radius:10px;margin-bottom:24px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600';
                lockNotice.innerHTML = '<i class="fas fa-lock"></i> Your application has been '+appStatus+'. Editing is no longer allowed.';
                step1Panel.insertBefore(lockNotice, step1Panel.firstChild.nextSibling.nextSibling);
                document.querySelectorAll('#step2 .btn-primary, #step2 .upload-zone').forEach(el => { el.style.pointerEvents = 'none'; el.style.opacity = '0.5'; });
            }
        }

        populateForm(); applyLockRules(); updateUI();

        function showToast(msg, type) {
            const box = document.getElementById('toastBox'); const t = document.createElement('div'); t.classList.add('toast', type || 'info');
            const icons = { success:'fa-circle-check', error:'fa-circle-xmark', info:'fa-circle-info' };
            t.innerHTML = '<i class="fas '+(icons[type]||icons.info)+'"></i><span>'+msg+'</span>'; box.appendChild(t);
            setTimeout(() => { t.classList.add('out'); t.addEventListener('animationend', () => t.remove()); }, 3500);
        }

        document.querySelectorAll('#step1 input, #step1 select').forEach(el => {
            el.addEventListener('input', () => el.classList.remove('invalid'));
            el.addEventListener('change', () => el.classList.remove('invalid'));
        });
    </script>
</body>
</html>