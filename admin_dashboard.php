<?php 
session_start(); 
ob_start();
require_once 'db_conn.php';

// Protect this page — only allow logged-in admins
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: admin_index.php');
    exit;
}

// Handle AJAX POST Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_id'])) {
        echo json_encode(['success' => false, 'msg' => 'Session expired.']);
        exit;
    }

    $action = $_POST['action'];

    // ── UPDATE STATUS ──
    if ($action === 'update_status') {
        $user_id = $_POST['user_id'];
        $status  = $_POST['status'];
        try {
            $upd = $pdo->prepare("UPDATE tbl_personal_info SET app_status = ? WHERE user_id = ?");
            $upd->execute([$status, $user_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // ── DELETE APPLICANT (Frontend-only removal — no backend delete) ──
if ($action === 'delete_applicant') {
    $user_id = $_POST['user_id'];
    // Do NOT delete from the database.
    // The frontend JavaScript will remove the row from the local array
    // and re-render the table. On page refresh, the applicant will reappear.
    echo json_encode(['success' => true]);
    exit;
}

    echo json_encode(['success' => false, 'msg' => 'Unknown action.']);
    exit;
}

// Fetch all personal info joined with users and requirements
 $applicants = [];
try {
    $stmt = $pdo->query("
        SELECT 
            p.info_id, p.user_id, p.firstname, p.lastname, p.birthday, p.gender, 
            p.contact_no, p.email_add, p.home_address, p.guardian_fullname, 
            p.guardian_contact_no, p.occupation, p.income, p.school, p.year_level, 
            p.course, p.gwa, 
            COALESCE(p.app_status, 'pending') AS app_status,
            u.firstname AS user_fname, u.lastname AS user_lname, u.email, u.created_at,
            COUNT(r.req_id) as doc_count,
            GROUP_CONCAT(r.file_path SEPARATOR '||') as doc_paths,
            GROUP_CONCAT(r.file_name SEPARATOR '||') as doc_names
        FROM tbl_personal_info p
        LEFT JOIN tbl_users u ON p.user_id = u.user_id
        LEFT JOIN tbl_requirements r ON p.user_id = r.user_id
        GROUP BY p.info_id, p.user_id, p.firstname, p.lastname, p.birthday, p.gender, 
            p.contact_no, p.email_add, p.home_address, p.guardian_fullname, 
            p.guardian_contact_no, p.occupation, p.income, p.school, p.year_level, 
            p.course, p.gwa, p.app_status,
            u.user_id, u.firstname, u.lastname, u.email, u.created_at
        ORDER BY u.created_at DESC
    ");
    $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrantGate | Admin Dashboard</title>
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
            --fg: #ffffff;
            --muted: #64748b;
            --input-bg: rgba(255,255,255,0.05);
            --input-border: rgba(255,255,255,0.1);
            --input-focus: rgba(0,200,83,0.25);
            --danger: #ff4757;
            --warning: #ffc93c;
            --success: #00c853;
            --radius: 14px;
            --chart-approved: #00e676;
            --chart-approved-glow: rgba(0,230,118,0.5);
            --chart-pending: #ffab00;
            --chart-pending-glow: rgba(255,171,0,0.5);
            --chart-rejected: #ff5252;
            --chart-rejected-glow: rgba(255,82,82,0.5);
            --chart-total: #448aff;
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        html{scroll-behavior:smooth}
        body{font-family:'Poppins',sans-serif;background:var(--dark-bg);color:var(--fg);min-height:100vh;overflow-x:hidden}

        .topbar{
            position:fixed;top:0;left:0;right:0;height:64px;
            background:rgba(11,20,36,0.95);backdrop-filter:blur(20px);
            border-bottom:1px solid rgba(255,255,255,0.06);
            display:flex;align-items:center;justify-content:space-between;
            padding:0 32px;z-index:1000;
        }
        .topbar-left{display:flex;align-items:center;gap:16px}
        .topbar-logo{font-family:'Orbitron',sans-serif;font-size:1.3rem;font-weight:900;color:#fff}
        .topbar-logo span{color:var(--green)}
        .admin-badge{
            background:rgba(0,200,83,0.1);border:1px solid rgba(0,200,83,0.25);
            color:var(--green);padding:4px 14px;border-radius:20px;
            font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;
            font-family:'Orbitron',sans-serif;
        }
        .topbar-right{display:flex;align-items:center;gap:16px}
        .topbar-user{display:flex;align-items:center;gap:10px;color:var(--muted);font-size:14px;font-weight:500}
        .topbar-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#00c853,#1de9b6);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px}
        .logout-btn{background:rgba(255,71,87,0.1);border:1px solid rgba(255,71,87,0.3);color:#ff6b7a;padding:8px 18px;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.3s;text-decoration:none}
        .logout-btn:hover{background:var(--danger);color:#fff;border-color:var(--danger)}

        .main{margin-top:64px;padding:40px;max-width:1400px;margin-left:auto;margin-right:auto}

        .page-title{margin-bottom:36px}
        .page-title h1{font-family:'Orbitron',sans-serif;font-size:2rem;font-weight:900;margin-bottom:8px}
        .page-title h1 span{color:var(--green)}
        .page-title p{color:var(--muted);font-size:0.95rem}

        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;margin-bottom:40px}

        .stat-card{
            background:var(--card-bg);backdrop-filter:blur(20px);
            border:1px solid rgba(255,255,255,0.06);
            border-radius:var(--radius);padding:28px;
            position:relative;overflow:hidden;
            transition:transform 0.3s,box-shadow 0.3s;
        }
        .stat-card:hover{transform:translateY(-4px);box-shadow:0 12px 40px rgba(0,0,0,0.3)}

        .stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
        .stat-card.total::before{background:linear-gradient(90deg,#3b82f6,#60a5fa)}
        .stat-card.approved::before{background:linear-gradient(90deg,var(--green),#1de9b6)}
        .stat-card.rejected::before{background:linear-gradient(90deg,var(--danger),#ff6b7a)}
        .stat-card.pending::before{background:linear-gradient(90deg,var(--warning),#fbbf24)}

        .stat-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px}
        .stat-label{font-size:13px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em}
        .stat-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px}
        .stat-card.total .stat-icon{background:rgba(59,130,246,0.1);color:#60a5fa}
        .stat-card.approved .stat-icon{background:rgba(0,200,83,0.1);color:var(--green)}
        .stat-card.rejected .stat-icon{background:rgba(255,71,87,0.1);color:var(--danger)}
        .stat-card.pending .stat-icon{background:rgba(255,201,60,0.1);color:var(--warning)}

        .stat-number{font-family:'Orbitron',sans-serif;font-size:2.4rem;font-weight:900;line-height:1;margin-bottom:6px}
        .stat-card.total .stat-number{color:#60a5fa}
        .stat-card.approved .stat-number{color:var(--green)}
        .stat-card.rejected .stat-number{color:var(--danger)}
        .stat-card.pending .stat-number{color:var(--warning)}

        .stat-sub{font-size:12px;color:var(--muted)}

        .stat-bar{margin-top:16px;display:flex;align-items:center;gap:8px}
        .stat-bar-track{flex:1;height:6px;background:rgba(255,255,255,0.06);border-radius:3px;overflow:hidden}
        .stat-bar-fill{height:100%;border-radius:3px;transition:width 1s cubic-bezier(0.22,1,0.36,1)}
        .stat-card.total .stat-bar-fill{background:linear-gradient(90deg,#3b82f6,#60a5fa)}
        .stat-card.approved .stat-bar-fill{background:linear-gradient(90deg,var(--green),#1de9b6)}
        .stat-card.rejected .stat-bar-fill{background:linear-gradient(90deg,var(--danger),#ff6b7a)}
        .stat-card.pending .stat-bar-fill{background:linear-gradient(90deg,var(--warning),#fbbf24)}
        .stat-bar-pct{font-size:12px;font-weight:700;min-width:36px;text-align:right}

        .section-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:16px}
        .section-header h2{font-family:'Orbitron',sans-serif;font-size:1.2rem;font-weight:700}
        .section-header h2 i{color:var(--green);margin-right:10px}

        .filter-row{display:flex;gap:10px;flex-wrap:wrap}
        .filter-btn{
            padding:8px 18px;border-radius:8px;
            background:var(--input-bg);border:1.5px solid var(--input-border);
            color:var(--muted);font-family:'Poppins',sans-serif;
            font-size:13px;font-weight:600;cursor:pointer;transition:all 0.3s;
        }
        .filter-btn:hover{border-color:var(--green);color:var(--green)}
        .filter-btn.active{background:var(--green);border-color:var(--green);color:#fff;box-shadow:0 4px 16px var(--green-glow)}

        .search-box{position:relative;margin-bottom:24px}
        .search-box input{
            width:100%;padding:14px 18px 14px 46px;
            background:var(--input-bg);border:1.5px solid var(--input-border);
            border-radius:12px;color:var(--fg);font-family:'Poppins',sans-serif;font-size:14px;
            outline:none;transition:all 0.3s;
        }
        .search-box input:focus{border-color:var(--green);box-shadow:0 0 0 3px var(--input-focus)}
        .search-box i{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:15px}

        .table-card{
            background:var(--card-bg);backdrop-filter:blur(20px);
            border:1px solid rgba(255,255,255,0.06);
            border-radius:var(--radius);overflow-x:auto;
        }

        .data-table{width:100%;border-collapse:collapse}
        .data-table thead{background:rgba(0,200,83,0.05);border-bottom:1px solid var(--input-border)}
        .data-table th{
            padding:16px 20px;text-align:left;
            font-size:11px;font-weight:700;text-transform:uppercase;
            letter-spacing:0.1em;color:var(--muted);
        }
        .data-table td{
            padding:16px 20px;border-bottom:1px solid rgba(255,255,255,0.03);
            font-size:14px;vertical-align:middle;
        }
        .data-table tbody tr{transition:background 0.2s}
        .data-table tbody tr:hover{background:rgba(255,255,255,0.02)}
        .data-table tbody tr:last-child td{border-bottom:none}

        .applicant-name{font-weight:600;color:var(--fg)}
        .applicant-email{font-size:12px;color:var(--muted)}

        .badge{
            display:inline-flex;align-items:center;gap:6px;
            padding:5px 14px;border-radius:20px;
            font-size:12px;font-weight:600;letter-spacing:0.03em;
        }
        .badge.pending{background:rgba(255,201,60,0.1);color:var(--warning);border:1px solid rgba(255,201,60,0.2)}
        .badge.approved{background:rgba(0,200,83,0.1);color:var(--success);border:1px solid rgba(0,200,83,0.2)}
        .badge.rejected{background:rgba(255,71,87,0.1);color:var(--danger);border:1px solid rgba(255,71,87,0.2)}
        .badge i{font-size:10px}

        .action-group{display:flex;gap:8px}
        .action-btn{
            width:36px;height:36px;border-radius:8px;
            display:flex;align-items:center;justify-content:center;
            border:none;cursor:pointer;font-size:14px;transition:all 0.3s;
        }
        .action-btn.approve{background:rgba(0,200,83,0.1);color:var(--green);border:1px solid rgba(0,200,83,0.2)}
        .action-btn.approve:hover{background:var(--green);color:#fff;box-shadow:0 4px 16px var(--green-glow)}
        .action-btn.reject{background:rgba(255,71,87,0.1);color:var(--danger);border:1px solid rgba(255,71,87,0.2)}
        .action-btn.reject:hover{background:var(--danger);color:#fff}
        .action-btn.view{background:rgba(96,165,250,0.1);color:#60a5fa;border:1px solid rgba(96,165,250,0.2)}
        .action-btn.view:hover{background:#3b82f6;color:#fff}
        .action-btn.delete{background:rgba(255,71,87,0.08);color:#ff6b7a;border:1px solid rgba(255,71,87,0.15)}
        .action-btn.delete:hover{background:#c62828;color:#fff;box-shadow:0 4px 16px rgba(255,71,87,0.3)}

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
            width:90%;max-width:600px;max-height:85vh;overflow-y:auto;
            animation:modalIn 0.4s cubic-bezier(0.22,1,0.36,1) forwards;
            transform:translateY(30px) scale(0.95);opacity:0;
        }
        @keyframes modalIn{to{transform:translateY(0) scale(1);opacity:1}}

        .modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
        .modal-header h2{font-family:'Orbitron',sans-serif;font-size:1.1rem;font-weight:700}
        .modal-header h2 i{color:var(--green);margin-right:8px}
        .modal-close{background:none;border:none;color:var(--muted);font-size:20px;cursor:pointer;transition:color 0.3s}
        .modal-close:hover{color:var(--fg)}

        .modal-body{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .modal-field{margin-bottom:8px}
        .modal-field label{display:block;font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:4px}
        .modal-field .val{font-size:14px;color:var(--fg)}
        .modal-field.full{grid-column:1/-1}

        .modal-actions{display:flex;gap:12px;margin-top:28px;justify-content:flex-end;flex-wrap:wrap}
        .modal-btn{
            padding:12px 28px;border-radius:10px;
            font-family:'Orbitron',sans-serif;font-size:0.75rem;font-weight:700;
            cursor:pointer;border:none;transition:all 0.3s;
            display:inline-flex;align-items:center;gap:8px;
        }
        .modal-btn.approve{background:var(--green);color:#fff}
        .modal-btn.approve:hover{box-shadow:0 6px 24px var(--green-glow)}
        .modal-btn.reject{background:var(--danger);color:#fff}
        .modal-btn.reject:hover{box-shadow:0 6px 24px rgba(255,71,87,0.3)}
        .modal-btn.cancel{background:var(--input-bg);color:var(--fg);border:1px solid var(--input-border)}
        .modal-btn.cancel:hover{border-color:var(--muted)}
        .modal-btn.delete{background:#c62828;color:#fff}
        .modal-btn.delete:hover{background:#b71c1c;box-shadow:0 6px 24px rgba(255,71,87,0.35)}

        .confirm-body{text-align:center;padding:16px 0}
        .confirm-icon{
            width:72px;height:72px;border-radius:50%;
            background:rgba(255,71,87,0.1);border:2px solid rgba(255,71,87,0.2);
            display:flex;align-items:center;justify-content:center;
            margin:0 auto 20px;animation:confirmPulse 2s infinite;
        }
        .confirm-icon i{font-size:30px;color:var(--danger)}
        @keyframes confirmPulse{0%,100%{box-shadow:0 0 0 0 rgba(255,71,87,0.2)}50%{box-shadow:0 0 0 12px rgba(255,71,87,0)}}
        .confirm-body h3{font-size:18px;font-weight:700;margin-bottom:8px}
        .confirm-body .confirm-name{color:var(--fg);font-weight:600;font-size:15px;margin-bottom:6px}
        .confirm-body .confirm-warn{color:var(--danger);font-size:13px;margin-top:14px}
        .confirm-body .confirm-warn i{margin-right:4px}

        .toast-box{position:fixed;top:80px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px}
        .toast{padding:14px 22px;border-radius:12px;font-size:13px;font-weight:500;backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:10px;min-width:240px;box-shadow:0 8px 30px rgba(0,0,0,0.4);animation:toastIn 0.4s cubic-bezier(0.22,1,0.36,1) forwards}
        .toast.success{background:rgba(0,200,83,0.15);border-color:rgba(0,200,83,0.3);color:#a7f3d0}
        .toast.error{background:rgba(255,71,87,0.15);border-color:rgba(255,71,87,0.3);color:#fca5a5}
        .toast.info{background:rgba(96,165,250,0.15);border-color:rgba(96,165,250,0.3);color:#bfdbfe}
        .toast.out{animation:toastOut 0.35s forwards}
        @keyframes toastIn{from{transform:translateX(120%);opacity:0}to{transform:translateX(0);opacity:1}}
        @keyframes toastOut{to{transform:translateX(120%);opacity:0}}

        .chart-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:40px}
        .chart-card{
            background:var(--card-bg);backdrop-filter:blur(20px);
            border:1px solid rgba(255,255,255,0.06);
            border-radius:var(--radius);padding:28px;
        }
        .chart-card h3{font-family:'Orbitron',sans-serif;font-size:0.85rem;font-weight:700;margin-bottom:20px;color:var(--muted)}
        .chart-card h3 i{color:var(--green);margin-right:8px}

        .donut-wrap{position:relative;width:220px;height:220px;margin:0 auto}
        .donut-wrap canvas{width:220px;height:220px;display:block}
        .donut-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none}
        .donut-center .num{font-family:'Orbitron',sans-serif;font-size:1.8rem;font-weight:900;color:var(--fg)}
        .donut-center .lbl{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:0.1em}

        .donut-legend{display:flex;justify-content:center;gap:24px;margin-top:24px;flex-wrap:wrap}
        .donut-legend-item{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:500}
        .donut-legend-dot{width:12px;height:12px;border-radius:3px}
        .donut-legend-val{font-family:'Orbitron',sans-serif;font-weight:700;font-size:12px;margin-left:2px}

        .bar-chart{display:flex;flex-direction:column;gap:18px}
        .bar-row{display:flex;align-items:center;gap:12px}
        .bar-label{font-size:13px;font-weight:600;min-width:80px;text-align:right}
        .bar-track{flex:1;height:32px;background:rgba(255,255,255,0.04);border-radius:10px;overflow:hidden;position:relative}
        .bar-fill{height:100%;border-radius:10px;transition:width 1.2s cubic-bezier(0.22,1,0.36,1);display:flex;align-items:center;justify-content:flex-end;padding-right:12px;font-size:12px;font-weight:700;letter-spacing:0.03em}
        .bar-fill.green{background:linear-gradient(90deg,rgba(0,230,118,0.25),var(--chart-approved));color:#fff}
        .bar-fill.amber{background:linear-gradient(90deg,rgba(255,171,0,0.25),var(--chart-pending));color:#111}
        .bar-fill.red{background:linear-gradient(90deg,rgba(255,82,82,0.25),var(--chart-rejected));color:#fff}
        .bar-count{font-family:'Orbitron',sans-serif;font-size:12px;font-weight:700;min-width:30px;text-align:right}

        .doc-preview-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px}
        .doc-preview-item{
            border:1px solid rgba(255,255,255,0.06);border-radius:10px;overflow:hidden;
            transition:all 0.3s;cursor:pointer;position:relative;
        }
        .doc-preview-item:hover{border-color:rgba(0,200,83,0.3);transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,0.3)}
        .doc-preview-item img{width:100%;height:140px;object-fit:cover;display:block}
        .doc-preview-item .doc-overlay{
            position:absolute;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;
            opacity:0;transition:opacity 0.3s;
        }
        .doc-preview-item:hover .doc-overlay{opacity:1}
        .doc-overlay i{font-size:24px;color:#fff}
        .doc-preview-name{
            padding:8px 10px;font-size:11px;color:var(--muted);
            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
            background:rgba(0,0,0,0.2);
        }
        .doc-preview-item.pdf-type{display:flex;flex-direction:column;align-items:center;justify-content:center;height:140px;background:rgba(255,71,87,0.05)}
        .doc-preview-item.pdf-type i{font-size:32px;color:var(--danger);margin-bottom:8px}
        .doc-preview-item.pdf-type span{font-size:11px;color:var(--muted)}

        .lightbox{
            position:fixed;inset:0;background:rgba(0,0,0,0.85);backdrop-filter:blur(10px);
            z-index:10000;display:none;align-items:center;justify-content:center;padding:40px;
            animation:modalBgIn 0.3s forwards;cursor:pointer;
        }
        .lightbox.open{display:flex}
        .lightbox img{max-width:90%;max-height:90vh;border-radius:8px;box-shadow:0 20px 60px rgba(0,0,0,0.5)}
        .lightbox .close-lb{
            position:absolute;top:20px;right:24px;width:40px;height:40px;
            border-radius:50%;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);
            color:#fff;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;
            transition:background 0.3s;
        }
        .lightbox .close-lb:hover{background:var(--danger);border-color:var(--danger)}

        @media(max-width:900px){
            .chart-grid{grid-template-columns:1fr}
            .stats-grid{grid-template-columns:1fr 1fr}
            .modal-body{grid-template-columns:1fr}
        }
        @media(max-width:600px){
            .main{padding:20px}
            .stats-grid{grid-template-columns:1fr}
            .data-table{font-size:13px}
            .data-table th,.data-table td{padding:12px 14px}
            .filter-row{width:100%}
            .filter-btn{flex:1;text-align:center}
            .action-group{gap:4px}
            .action-btn{width:32px;height:32px;font-size:12px}
        }
    </style>
</head>
<body>

    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-logo">GRANT<span>GATE</span></div>
            <div class="admin-badge"><i class="fas fa-shield-halved"></i> Admin</div>
        </div>
        <div class="topbar-right">
            <div class="topbar-user">
                <div class="topbar-avatar">AD</div>
                <span><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
            </div>
            <a href="admin_dashboard.php?logout=true" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="toast-box" id="toastBox"></div>

    <div class="lightbox" id="lightbox" onclick="closeLightbox()">
        <button class="close-lb" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
        <img id="lightboxImg" src="" alt="Document Preview">
    </div>

    <div class="modal-overlay" id="modalOverlay">
        <div class="modal" id="modalContent">
            <div class="modal-header">
                <h2><i class="fas fa-user-circle"></i> Applicant Details</h2>
                <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" id="modalBody"></div>
            <div class="modal-actions" id="modalActions"></div>
        </div>
    </div>

    <div class="modal-overlay" id="confirmOverlay">
        <div class="modal" style="max-width:440px">
            <div class="modal-header">
                <h2><i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i> Confirm Deletion</h2>
                <button class="modal-close" onclick="cancelDelete()"><i class="fas fa-times"></i></button>
            </div>
            <div class="confirm-body">
                <div class="confirm-icon"><i class="fas fa-trash-alt"></i></div>
                <h3>Remove this applicant?</h3>
                <p class="confirm-name" id="confirmName"></p>
                <p style="color:var(--muted);font-size:13px">Their account, personal info, and all documents will be permanently deleted.</p>
                <p class="confirm-warn"><i class="fas fa-exclamation-circle"></i> This action cannot be undone.</p>
            </div>
            <div class="modal-actions" style="justify-content:center">
                <button class="modal-btn cancel" onclick="cancelDelete()"><i class="fas fa-arrow-left"></i> Cancel</button>
                <button class="modal-btn delete" onclick="confirmDelete()"><i class="fas fa-trash-alt"></i> Delete</button>
            </div>
        </div>
    </div>

    <div class="main">
        <div class="page-title">
            <h1>Admin <span>Dashboard</span></h1>
            <p>Manage scholarship applications, review documents, and track reports.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-top"><div class="stat-label">Total Applicants</div><div class="stat-icon"><i class="fas fa-users"></i></div></div>
                <div class="stat-number" id="statTotal">0</div><div class="stat-sub">All registered applications</div>
                <div class="stat-bar"><div class="stat-bar-track"><div class="stat-bar-fill" id="barTotal" style="width:0%"></div></div><div class="stat-bar-pct" id="pctTotal">0%</div></div>
            </div>
            <div class="stat-card approved">
                <div class="stat-top"><div class="stat-label">Approved</div><div class="stat-icon"><i class="fas fa-check-circle"></i></div></div>
                <div class="stat-number" id="statApproved">0</div><div class="stat-sub">Scholars approved</div>
                <div class="stat-bar"><div class="stat-bar-track"><div class="stat-bar-fill" id="barApproved" style="width:0%"></div></div><div class="stat-bar-pct" id="pctApproved">0%</div></div>
            </div>
            <div class="stat-card rejected">
                <div class="stat-top"><div class="stat-label">Rejected</div><div class="stat-icon"><i class="fas fa-times-circle"></i></div></div>
                <div class="stat-number" id="statRejected">0</div><div class="stat-sub">Applications denied</div>
                <div class="stat-bar"><div class="stat-bar-track"><div class="stat-bar-fill" id="barRejected" style="width:0%"></div></div><div class="stat-bar-pct" id="pctRejected">0%</div></div>
            </div>
            <div class="stat-card pending">
                <div class="stat-top"><div class="stat-label">Pending</div><div class="stat-icon"><i class="fas fa-clock"></i></div></div>
                <div class="stat-number" id="statPending">0</div><div class="stat-sub">Awaiting review</div>
                <div class="stat-bar"><div class="stat-bar-track"><div class="stat-bar-fill" id="barPending" style="width:0%"></div></div><div class="stat-bar-pct" id="pctPending">0%</div></div>
            </div>
        </div>

        <div class="chart-grid">
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> Application Overview</h3>
                <div class="donut-wrap">
                    <canvas id="donutCanvas"></canvas>
                    <div class="donut-center"><div class="num" id="donutTotal">0</div><div class="lbl">Total</div></div>
                </div>
                <div class="donut-legend" id="donutLegend"></div>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-chart-bar"></i> Comparison</h3>
                <div class="bar-chart" id="barChart"></div>
            </div>
        </div>

        <div class="section-header">
            <h2><i class="fas fa-list"></i> All Applicants</h2>
            <div class="filter-row">
                <button class="filter-btn active" onclick="filterTable('all',this)">All</button>
                <button class="filter-btn" onclick="filterTable('pending',this)">Pending</button>
                <button class="filter-btn" onclick="filterTable('approved',this)">Approved</button>
                <button class="filter-btn" onclick="filterTable('rejected',this)">Rejected</button>
            </div>
        </div>

        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by name, email, or school..." oninput="searchTable()">
        </div>

        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>School / Course</th>
                        <th>Date Applied</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody"></tbody>
            </table>
        </div>
    </div>

    <script>
        const applicants = <?php echo json_encode($applicants ?: []); ?>;
        let currentFilter = 'all';
        let pendingDeleteId = null; 

        function getStats() {
            const total    = applicants.length;
            const approved = applicants.filter(a => a.app_status === 'approved').length;
            const rejected = applicants.filter(a => a.app_status === 'rejected').length;
            const pending  = applicants.filter(a => a.app_status === 'pending').length;
            return { total, approved, rejected, pending };
        }

        function animateNumber(el, target) {
            let current = 0;
            const step = target / 60;
            const timer = setInterval(() => {
                current += step;
                if (current >= target) { current = target; clearInterval(timer); }
                el.textContent = Math.round(current);
            }, 16);
        }

        function drawDonut(progress) {
            const canvas = document.getElementById('donutCanvas');
            if (!canvas) return;

            const dpr = window.devicePixelRatio || 1;
            const size = 220;
            canvas.style.width  = size + 'px';
            canvas.style.height = size + 'px';
            canvas.width  = size * dpr;
            canvas.height = size * dpr;

            const ctx = canvas.getContext('2d');
            ctx.scale(dpr, dpr);
            ctx.clearRect(0, 0, size, size);

            const cx = size / 2;
            const cy = size / 2;
            const outerR = 98;
            const innerR = 66;
            const midR   = (outerR + innerR) / 2;
            const lineW  = outerR - innerR;

            const s = getStats();
            const total = s.total || 1;

            const segments = [
                { label: 'Approved', value: s.approved, color: '#00e676', glow: 'rgba(0,230,118,0.5)' },
                { label: 'Pending',  value: s.pending,  color: '#ffab00', glow: 'rgba(255,171,0,0.5)' },
                { label: 'Rejected', value: s.rejected, color: '#ff5252', glow: 'rgba(255,82,82,0.5)' }
            ].filter(seg => seg.value > 0);

            ctx.beginPath();
            ctx.arc(cx, cy, midR, 0, Math.PI * 2);
            ctx.lineWidth = lineW;
            ctx.strokeStyle = 'rgba(255,255,255,0.04)';
            ctx.stroke();

            if (segments.length === 0) return;

            const gapAngle = segments.length > 1 ? 0.04 : 0;
            const totalGap = gapAngle * segments.length;
            const availAngle = (Math.PI * 2 - totalGap) * progress;

            let currentAngle = -Math.PI / 2;

            segments.forEach(seg => {
                const sliceAngle = (seg.value / total) * availAngle;
                if (sliceAngle <= 0.001) { currentAngle += gapAngle; return; }

                ctx.save();
                ctx.shadowColor = seg.glow;
                ctx.shadowBlur  = 22;
                ctx.shadowOffsetX = 0;
                ctx.shadowOffsetY = 0;

                ctx.beginPath();
                ctx.arc(cx, cy, midR, currentAngle, currentAngle + sliceAngle);
                ctx.lineWidth  = lineW;
                ctx.lineCap    = 'round';
                ctx.strokeStyle = seg.color;
                ctx.stroke();
                ctx.restore();

                currentAngle += sliceAngle + gapAngle;
            });
        }

        function animateDonut() {
            let start = null;
            const duration = 1400;
            function step(ts) {
                if (!start) start = ts;
                const p = Math.min((ts - start) / duration, 1);
                const eased = 1 - Math.pow(1 - p, 3);
                drawDonut(eased);
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        }

        function updateLegend() {
            const s = getStats();
            document.getElementById('donutLegend').innerHTML =
                '<div class="donut-legend-item"><div class="donut-legend-dot" style="background:#00e676"></div>Approved <span class="donut-legend-val" style="color:#00e676">' + s.approved + '</span></div>' +
                '<div class="donut-legend-item"><div class="donut-legend-dot" style="background:#ffab00"></div>Pending <span class="donut-legend-val" style="color:#ffab00">' + s.pending + '</span></div>' +
                '<div class="donut-legend-item"><div class="donut-legend-dot" style="background:#ff5252"></div>Rejected <span class="donut-legend-val" style="color:#ff5252">' + s.rejected + '</span></div>';
        }

        function updateDashboard() {
            const s = getStats();

            animateNumber(document.getElementById('statTotal'), s.total);
            animateNumber(document.getElementById('statApproved'), s.approved);
            animateNumber(document.getElementById('statRejected'), s.rejected);
            animateNumber(document.getElementById('statPending'), s.pending);

            setTimeout(() => {
                const pctA = s.total ? Math.round(s.approved / s.total * 100) : 0;
                const pctR = s.total ? Math.round(s.rejected / s.total * 100) : 0;
                const pctP = s.total ? Math.round(s.pending  / s.total * 100) : 0;

                document.getElementById('barTotal').style.width    = '100%';
                document.getElementById('pctTotal').textContent     = '100%';
                document.getElementById('barApproved').style.width  = pctA + '%';
                document.getElementById('pctApproved').textContent  = pctA + '%';
                document.getElementById('barRejected').style.width  = pctR + '%';
                document.getElementById('pctRejected').textContent  = pctR + '%';
                document.getElementById('barPending').style.width   = pctP + '%';
                document.getElementById('pctPending').textContent   = pctP + '%';

                document.getElementById('barChart').innerHTML =
                    '<div class="bar-row"><div class="bar-label" style="color:#00e676">Approved</div><div class="bar-track"><div class="bar-fill green" style="width:'+pctA+'%">' + s.approved + '</div></div><div class="bar-count" style="color:#00e676">' + pctA + '%</div></div>' +
                    '<div class="bar-row"><div class="bar-label" style="color:#ffab00">Pending</div><div class="bar-track"><div class="bar-fill amber" style="width:'+pctP+'%">' + s.pending + '</div></div><div class="bar-count" style="color:#ffab00">' + pctP + '%</div></div>' +
                    '<div class="bar-row"><div class="bar-label" style="color:#ff5252">Rejected</div><div class="bar-track"><div class="bar-fill red" style="width:'+pctR+'%">' + s.rejected + '</div></div><div class="bar-count" style="color:#ff5252">' + pctR + '%</div></div>';

                document.getElementById('donutTotal').textContent = s.total;
                updateLegend();
                animateDonut();
            }, 200);

            renderTable();
        }

        function renderTable() {
            const tbody  = document.getElementById('tableBody');
            const search = document.getElementById('searchInput').value.toLowerCase();

            let filtered = applicants;
            if (currentFilter !== 'all') filtered = filtered.filter(a => a.app_status === currentFilter);
            if (search) filtered = filtered.filter(a =>
                (a.firstname + ' ' + a.lastname).toLowerCase().includes(search) ||
                a.email_add.toLowerCase().includes(search) ||
                a.school.toLowerCase().includes(search)
            );

            if (filtered.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--muted)"><i class="fas fa-inbox" style="font-size:32px;display:block;margin-bottom:12px"></i>No applicants found</td></tr>';
                return;
            }

            const statusBadge = {
                pending:  '<span class="badge pending"><i class="fas fa-clock"></i> Pending</span>',
                approved: '<span class="badge approved"><i class="fas fa-check"></i> Approved</span>',
                rejected: '<span class="badge rejected"><i class="fas fa-times"></i> Rejected</span>'
            };

            tbody.innerHTML = filtered.map(a => {
                const status = a.app_status || 'pending';
                const dateApplied = a.created_at ? new Date(a.created_at).toISOString().split('T')[0] : 'N/A';

                const actions =
                    '<div class="action-group">' +
                    '<button class="action-btn view" onclick="viewApplicant('+a.user_id+')" title="View"><i class="fas fa-eye"></i></button>' +
                    '<button class="action-btn approve" onclick="updateStatus('+a.user_id+',\'approved\')" title="Approve"><i class="fas fa-check"></i></button>' +
                    '<button class="action-btn reject" onclick="updateStatus('+a.user_id+',\'rejected\')" title="Reject"><i class="fas fa-times"></i></button>' +
                    '<button class="action-btn delete" onclick="deleteApplicant('+a.user_id+')" title="Delete"><i class="fas fa-trash-alt"></i></button>' +
                    '</div>';

                return '<tr data-status="'+status+'">' +
                    '<td><div class="applicant-name">'+a.firstname+' '+a.lastname+'</div><div class="applicant-email">'+a.email_add+'</div></td>' +
                    '<td>'+a.school+'<br><span style="font-size:12px;color:var(--muted)">'+a.course+' · '+a.year_level+'</span></td>' +
                    '<td>'+dateApplied+'</td>' +
                    '<td>'+statusBadge[status]+'</td>' +
                    '<td>'+actions+'</td>' +
                    '</tr>';
            }).join('');
        }

        function filterTable(status, btn) {
            currentFilter = status;
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderTable();
        }
        function searchTable() { renderTable(); }

        function updateStatus(id, newStatus) {
            fetch('admin_dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=update_status&user_id=' + id + '&status=' + newStatus
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const a = applicants.find(a => a.user_id == id);
                    if (a) a.app_status = newStatus;
                    updateDashboard();
                    const verb = newStatus === 'approved' ? 'approved' : 'rejected';
                    showToast((a ? a.firstname + ' ' + a.lastname : 'Applicant') + ' has been ' + verb + '.', newStatus === 'approved' ? 'success' : 'error');
                } else {
                    showToast('Failed to update status.', 'error');
                }
            })
            .catch(() => showToast('Network error.', 'error'));
        }

        function deleteApplicant(id) {
            const a = applicants.find(a => a.user_id == id);
            if (!a) return;
            pendingDeleteId = id;
            document.getElementById('confirmName').textContent = a.firstname + ' ' + a.lastname;
            document.getElementById('confirmOverlay').classList.add('open');
        }

        function cancelDelete() {
            pendingDeleteId = null;
            document.getElementById('confirmOverlay').classList.remove('open');
        }

        function confirmDelete() {
            if (!pendingDeleteId) return;
            const id = pendingDeleteId;
            const a = applicants.find(a => a.user_id == id);
            const name = a ? a.firstname + ' ' + a.lastname : 'Applicant';

            cancelDelete(); 

            fetch('admin_dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=delete_applicant&user_id=' + id
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const idx = applicants.findIndex(a => a.user_id == id);
                    if (idx !== -1) applicants.splice(idx, 1);
                    updateDashboard();
                    showToast(name + ' has been permanently deleted.', 'error');
                } else {
                    showToast('Delete failed: ' + (data.msg || 'Unknown error'), 'error');
                }
            })
            .catch(() => showToast('Network error.', 'error'));
        }

        function viewApplicant(id) {
            const a = applicants.find(a => a.user_id == id);
            if (!a) return;

            const body = document.getElementById('modalBody');

            let docsHtml = '';
            if (a.doc_count > 0 && a.doc_paths) {
                const paths = a.doc_paths.split('||');
                const names = a.doc_names ? a.doc_names.split('||') : [];
                docsHtml = '<div class="modal-field full"><label>Submitted Documents</label><div class="doc-preview-grid">';
                paths.forEach((path, i) => {
                    const name = names[i] || 'Document';
                    const ext = name.split('.').pop().toLowerCase();
                    const isImg = ['jpg','jpeg','png','gif','webp'].includes(ext);
                    if (isImg) {
                        docsHtml += '<div class="doc-preview-item" onclick="event.stopPropagation();openLightbox(\''+path+'\')">' +
                            '<img src="'+path+'" alt="'+name+'">' +
                            '<div class="doc-overlay"><i class="fas fa-search-plus"></i></div>' +
                            '<div class="doc-preview-name">'+name+'</div></div>';
                    } else {
                        docsHtml += '<a href="'+path+'" target="_blank" class="doc-preview-item pdf-type" onclick="event.stopPropagation()">' +
                            '<i class="fas fa-file-pdf"></i><span>'+name+'</span></a>';
                    }
                });
                docsHtml += '</div></div>';
            } else {
                docsHtml = '<div class="modal-field full"><label>Documents</label><div class="val"><span style="color:var(--danger)"><i class="fas fa-exclamation-circle"></i> No documents uploaded</span></div></div>';
            }

            body.innerHTML =
                '<div class="modal-field"><label>Full Name</label><div class="val">'+a.firstname+' '+a.lastname+'</div></div>' +
                '<div class="modal-field"><label>Gender</label><div class="val">'+a.gender+'</div></div>' +
                '<div class="modal-field"><label>Date of Birth</label><div class="val">'+a.birthday+'</div></div>' +
                '<div class="modal-field"><label>Contact</label><div class="val">'+a.contact_no+'</div></div>' +
                '<div class="modal-field full"><label>Email</label><div class="val">'+a.email_add+'</div></div>' +
                '<div class="modal-field full"><label>Address</label><div class="val">'+a.home_address+'</div></div>' +
                '<div class="modal-field"><label>Parent/Guardian</label><div class="val">'+a.guardian_fullname+'</div></div>' +
                '<div class="modal-field"><label>Parent Contact</label><div class="val">'+a.guardian_contact_no+'</div></div>' +
                '<div class="modal-field"><label>Occupation</label><div class="val">'+a.occupation+'</div></div>' +
                '<div class="modal-field"><label>Annual Income</label><div class="val">'+a.income+'</div></div>' +
                '<div class="modal-field"><label>School</label><div class="val">'+a.school+'</div></div>' +
                '<div class="modal-field"><label>Year Level</label><div class="val">'+a.year_level+'</div></div>' +
                '<div class="modal-field"><label>Course</label><div class="val">'+a.course+'</div></div>' +
                '<div class="modal-field"><label>GWA</label><div class="val">'+a.gwa+'</div></div>' +
                docsHtml +
                '<div class="modal-field"><label>Status</label><div class="val"><span class="badge '+(a.app_status || 'pending')+'">'+(a.app_status ? a.app_status.charAt(0).toUpperCase()+a.app_status.slice(1) : 'Pending')+'</span></div></div>';

            document.getElementById('modalActions').innerHTML =
                '<button class="modal-btn cancel" onclick="closeModal()"><i class="fas fa-arrow-left"></i> Close</button>' +
                '<button class="modal-btn delete" onclick="deleteApplicant('+a.user_id+');closeModal()"><i class="fas fa-trash-alt"></i> Delete</button>' +
                '<button class="modal-btn reject" onclick="updateStatus('+a.user_id+',\'rejected\');closeModal()"><i class="fas fa-times"></i> Reject</button>' +
                '<button class="modal-btn approve" onclick="updateStatus('+a.user_id+',\'approved\');closeModal()"><i class="fas fa-check"></i> Approve</button>';

            document.getElementById('modalOverlay').classList.add('open');
        }

        function openLightbox(src) {
            document.getElementById('lightboxImg').src = src;
            document.getElementById('lightbox').classList.add('open');
        }
        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('open');
        }

        function closeModal() {
            document.getElementById('modalOverlay').classList.remove('open');
        }
        document.getElementById('modalOverlay').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        document.getElementById('confirmOverlay').addEventListener('click', function(e) {
            if (e.target === this) cancelDelete();
        });

        function showToast(msg, type) {
            const box = document.getElementById('toastBox');
            const t = document.createElement('div');
            t.classList.add('toast', type || 'info');
            const icons = {success:'fa-circle-check',error:'fa-circle-xmark',info:'fa-circle-info'};
            t.innerHTML = '<i class="fas '+(icons[type]||icons.info)+'"></i><span>'+msg+'</span>';
            box.appendChild(t);
            setTimeout(()=>{t.classList.add('out');t.addEventListener('animationend',()=>t.remove())},3500);
        }

        updateDashboard();
    </script>
</body>
</html>