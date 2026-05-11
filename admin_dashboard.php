<?php 
session_start(); 
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
    header('Location: admin_login.php');
    exit;
}


// Fetch all personal info joined with users and requirements
try {
    // Using LEFT JOIN so we still get users even if they haven't uploaded requirements yet
    $stmt = $pdo->query("
        SELECT 
            p.info_id, p.user_id, p.firstname, p.lastname, p.birthday, p.gender, 
            p.contact_no, p.email_add, p.home_address, p.guardian_fullname, 
            p.guardian_contact_no, p.occupation, p.income, p.school, p.year_level, 
            p.course, p.gwa, p.app_status,
            u.firstname AS user_fname, u.lastname AS user_lname, u.email, u.created_at,
            COUNT(r.req_id) as doc_count
        FROM tbl_personal_info p
        LEFT JOIN tbl_users u ON p.user_id = u.user_id
        LEFT JOIN tbl_requirements r ON p.user_id = r.user_id
        GROUP BY p.info_id
        ORDER BY u.created_at DESC
    ");
    $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
// Handle Status Update via AJAX POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    $user_id = $_POST['user_id'];
    $status = $_POST['status'];
    
    // Check if app_status column exists, otherwise we handle it via session/JS mapping
    try {
        $check = $pdo->query("SHOW COLUMNS FROM `tbl_personal_info` LIKE 'app_status'");
        if ($check->rowCount() > 0) {
            $upd = $pdo->prepare("UPDATE tbl_personal_info SET app_status = ? WHERE user_id = ?");
            $upd->execute([$status, $user_id]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
    exit;
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
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        html{scroll-behavior:smooth}
        body{font-family:'Poppins',sans-serif;background:var(--dark-bg);color:var(--fg);min-height:100vh;overflow-x:hidden}

        /* ===== TOP BAR ===== */
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

        /* ===== MAIN ===== */
        .main{margin-top:64px;padding:40px;max-width:1400px;margin-left:auto;margin-right:auto}

        .page-title{margin-bottom:36px}
        .page-title h1{font-family:'Orbitron',sans-serif;font-size:2rem;font-weight:900;margin-bottom:8px}
        .page-title h1 span{color:var(--green)}
        .page-title p{color:var(--muted);font-size:0.95rem}

        /* ===== STATS GRID ===== */
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;margin-bottom:40px}

        .stat-card{
            background:var(--card-bg);backdrop-filter:blur(20px);
            border:1px solid rgba(255,255,255,0.06);
            border-radius:var(--radius);padding:28px;
            position:relative;overflow:hidden;
            transition:transform 0.3s,box-shadow 0.3s;
        }
        .stat-card:hover{transform:translateY(-4px);box-shadow:0 12px 40px rgba(0,0,0,0.3)}

        .stat-card::before{
            content:'';position:absolute;top:0;left:0;right:0;height:3px;
        }
        .stat-card.total::before{background:linear-gradient(90deg,#3b82f6,#60a5fa)}
        .stat-card.approved::before{background:linear-gradient(90deg,var(--green),#1de9b6)}
        .stat-card.rejected::before{background:linear-gradient(90deg,var(--danger),#ff6b7a)}
        .stat-card.pending::before{background:linear-gradient(90deg,var(--warning),#fbbf24)}

        .stat-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px}
        .stat-label{font-size:13px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em}
        .stat-icon{
            width:44px;height:44px;border-radius:12px;
            display:flex;align-items:center;justify-content:center;font-size:18px;
        }
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

        /* Mini bar chart */
        .stat-bar{margin-top:16px;display:flex;align-items:center;gap:8px}
        .stat-bar-track{flex:1;height:6px;background:rgba(255,255,255,0.06);border-radius:3px;overflow:hidden}
        .stat-bar-fill{height:100%;border-radius:3px;transition:width 1s cubic-bezier(0.22,1,0.36,1)}
        .stat-card.total .stat-bar-fill{background:linear-gradient(90deg,#3b82f6,#60a5fa)}
        .stat-card.approved .stat-bar-fill{background:linear-gradient(90deg,var(--green),#1de9b6)}
        .stat-card.rejected .stat-bar-fill{background:linear-gradient(90deg,var(--danger),#ff6b7a)}
        .stat-card.pending .stat-bar-fill{background:linear-gradient(90deg,var(--warning),#fbbf24)}
        .stat-bar-pct{font-size:12px;font-weight:700;min-width:36px;text-align:right}

        /* ===== TABLE SECTION ===== */
        .section-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:16px}
        .section-header h2{font-family:'Orbitron',sans-serif;font-size:1.2rem;font-weight:700}
        .section-header h2 i{color:var(--green);margin-right:10px}

        .filter-row{display:flex;gap:10px;flex-wrap:wrap}
        .filter-btn{
            padding:8px 18px;border-radius:8px;
            background:var(--input-bg);border:1.5px solid var(--input-border);
            color:var(--muted);font-family:'Poppins',sans-serif;
            font-size:13px;font-weight:600;cursor:pointer;
            transition:all 0.3s;
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

        /* Table */
        .table-card{
            background:var(--card-bg);backdrop-filter:blur(20px);
            border:1px solid rgba(255,255,255,0.06);
            border-radius:var(--radius);overflow:hidden;
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

        /* Status badges */
        .badge{
            display:inline-flex;align-items:center;gap:6px;
            padding:5px 14px;border-radius:20px;
            font-size:12px;font-weight:600;letter-spacing:0.03em;
        }
        .badge.pending{background:rgba(255,201,60,0.1);color:var(--warning);border:1px solid rgba(255,201,60,0.2)}
        .badge.approved{background:rgba(0,200,83,0.1);color:var(--success);border:1px solid rgba(0,200,83,0.2)}
        .badge.rejected{background:rgba(255,71,87,0.1);color:var(--danger);border:1px solid rgba(255,71,87,0.2)}
        .badge i{font-size:10px}

        /* Action buttons */
        .action-group{display:flex;gap:8px}
        .action-btn{
            width:36px;height:36px;border-radius:8px;
            display:flex;align-items:center;justify-content:center;
            border:none;cursor:pointer;font-size:14px;
            transition:all 0.3s;
        }
        .action-btn.approve{background:rgba(0,200,83,0.1);color:var(--green);border:1px solid rgba(0,200,83,0.2)}
        .action-btn.approve:hover{background:var(--green);color:#fff;box-shadow:0 4px 16px var(--green-glow)}
        .action-btn.reject{background:rgba(255,71,87,0.1);color:var(--danger);border:1px solid rgba(255,71,87,0.2)}
        .action-btn.reject:hover{background:var(--danger);color:#fff}
        .action-btn.view{background:rgba(96,165,250,0.1);color:#60a5fa;border:1px solid rgba(96,165,250,0.2)}
        .action-btn.view:hover{background:#3b82f6;color:#fff}

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

        .modal-actions{display:flex;gap:12px;margin-top:28px;justify-content:flex-end}
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

        /* ===== TOAST ===== */
        .toast-box{position:fixed;top:80px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px}
        .toast{padding:14px 22px;border-radius:12px;font-size:13px;font-weight:500;backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:10px;min-width:240px;box-shadow:0 8px 30px rgba(0,0,0,0.4);animation:toastIn 0.4s cubic-bezier(0.22,1,0.36,1) forwards}
        .toast.success{background:rgba(0,200,83,0.15);border-color:rgba(0,200,83,0.3);color:#a7f3d0}
        .toast.error{background:rgba(255,71,87,0.15);border-color:rgba(255,71,87,0.3);color:#fca5a5}
        .toast.info{background:rgba(96,165,250,0.15);border-color:rgba(96,165,250,0.3);color:#bfdbfe}
        .toast.out{animation:toastOut 0.35s forwards}
        @keyframes toastIn{from{transform:translateX(120%);opacity:0}to{transform:translateX(0);opacity:1}}
        @keyframes toastOut{to{transform:translateX(120%);opacity:0}}

        /* ===== CHART AREA ===== */
        .chart-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:40px}
        .chart-card{
            background:var(--card-bg);backdrop-filter:blur(20px);
            border:1px solid rgba(255,255,255,0.06);
            border-radius:var(--radius);padding:28px;
        }
        .chart-card h3{font-family:'Orbitron',sans-serif;font-size:0.85rem;font-weight:700;margin-bottom:20px;color:var(--muted)}
        .chart-card h3 i{color:var(--green);margin-right:8px}

        .donut-chart{position:relative;width:200px;height:200px;margin:0 auto}
        .donut-chart svg{transform:rotate(-90deg)}
        .donut-chart circle{fill:none;stroke-width:24;transition:stroke-dashoffset 1.2s cubic-bezier(0.22,1,0.36,1)}
        .donut-chart .bg{stroke:rgba(255,255,255,0.04)}
        .donut-chart .fill-approved{stroke:var(--green);stroke-linecap:round}
        .donut-chart .fill-rejected{stroke:var(--danger);stroke-linecap:round}
        .donut-chart .fill-pending{stroke:var(--warning);stroke-linecap:round}
        .donut-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center}
        .donut-center .num{font-family:'Orbitron',sans-serif;font-size:1.8rem;font-weight:900;color:var(--fg)}
        .donut-center .lbl{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:0.1em}

        .bar-chart{display:flex;flex-direction:column;gap:14px}
        .bar-row{display:flex;align-items:center;gap:12px}
        .bar-label{font-size:13px;font-weight:600;min-width:80px;text-align:right}
        .bar-track{flex:1;height:28px;background:rgba(255,255,255,0.04);border-radius:8px;overflow:hidden;position:relative}
        .bar-fill{height:100%;border-radius:8px;transition:width 1.2s cubic-bezier(0.22,1,0.36,1);display:flex;align-items:center;justify-content:flex-end;padding-right:10px;font-size:12px;font-weight:700}
        .bar-fill.green{background:linear-gradient(90deg,rgba(0,200,83,0.3),var(--green));color:#fff}
        .bar-fill.yellow{background:linear-gradient(90deg,rgba(255,201,60,0.3),var(--warning));color:#111}
        .bar-fill.red{background:linear-gradient(90deg,rgba(255,71,87,0.3),var(--danger));color:#fff}

        /* ===== RESPONSIVE ===== */
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
        }
    </style>
</head>
<body>

    <!-- TOP BAR -->
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-logo">GRANT<span>GATE</span></div>
            <div class="admin-badge"><i class="fas fa-shield-halved"></i> Admin</div>
        </div>
        <div class="topbar-right">
            <div class="topbar-user">
                <div class="topbar-avatar">AD</div>
                <span>Admin</span>
            </div>
            <a href="landing_page.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- TOAST -->
    <div class="toast-box" id="toastBox"></div>

    <!-- MODAL -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal" id="modalContent">
            <div class="modal-header">
                <h2><i class="fas fa-user-circle"></i> Applicant Details</h2>
                <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Filled by JS -->
            </div>
            <div class="modal-actions" id="modalActions">
                <!-- Filled by JS -->
            </div>
        </div>
    </div>

    <!-- MAIN -->
    <div class="main">

        <div class="page-title">
            <h1>Admin <span>Dashboard</span></h1>
            <p>Manage scholarship applications, review documents, and track reports.</p>
        </div>

        <!-- ===== STATS ===== -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-top">
                    <div class="stat-label">Total Applicants</div>
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                </div>
                <div class="stat-number" id="statTotal">0</div>
                <div class="stat-sub">All registered applications</div>
                <div class="stat-bar">
                    <div class="stat-bar-track"><div class="stat-bar-fill" id="barTotal" style="width:0%"></div></div>
                    <div class="stat-bar-pct" id="pctTotal">0%</div>
                </div>
            </div>
            <div class="stat-card approved">
                <div class="stat-top">
                    <div class="stat-label">Approved</div>
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="stat-number" id="statApproved">0</div>
                <div class="stat-sub">Scholars approved</div>
                <div class="stat-bar">
                    <div class="stat-bar-track"><div class="stat-bar-fill" id="barApproved" style="width:0%"></div></div>
                    <div class="stat-bar-pct" id="pctApproved">0%</div>
                </div>
            </div>
            <div class="stat-card rejected">
                <div class="stat-top">
                    <div class="stat-label">Rejected</div>
                    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                </div>
                <div class="stat-number" id="statRejected">0</div>
                <div class="stat-sub">Applications denied</div>
                <div class="stat-bar">
                    <div class="stat-bar-track"><div class="stat-bar-fill" id="barRejected" style="width:0%"></div></div>
                    <div class="stat-bar-pct" id="pctRejected">0%</div>
                </div>
            </div>
            <div class="stat-card pending">
                <div class="stat-top">
                    <div class="stat-label">Pending</div>
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                </div>
                <div class="stat-number" id="statPending">0</div>
                <div class="stat-sub">Awaiting review</div>
                <div class="stat-bar">
                    <div class="stat-bar-track"><div class="stat-bar-fill" id="barPending" style="width:0%"></div></div>
                    <div class="stat-bar-pct" id="pctPending">0%</div>
                </div>
            </div>
        </div>

        <!-- ===== CHARTS ===== -->
        <div class="chart-grid">
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> Application Overview</h3>
                <div class="donut-chart">
                    <svg width="200" height="200" viewBox="0 0 200 200">
                        <circle class="bg" cx="100" cy="100" r="76"></circle>
                        <circle class="fill-approved" id="donutApproved" cx="100" cy="100" r="76" stroke-dasharray="477.5" stroke-dashoffset="477.5"></circle>
                        <circle class="fill-pending" id="donutPending" cx="100" cy="100" r="76" stroke-dasharray="477.5" stroke-dashoffset="477.5"></circle>
                        <circle class="fill-rejected" id="donutRejected" cx="100" cy="100" r="76" stroke-dasharray="477.5" stroke-dashoffset="477.5"></circle>
                    </svg>
                    <div class="donut-center">
                        <div class="num" id="donutTotal">0</div>
                        <div class="lbl">Total</div>
                    </div>
                </div>
                <div style="display:flex;justify-content:center;gap:20px;margin-top:20px">
                    <div style="display:flex;align-items:center;gap:6px;font-size:12px"><div style="width:10px;height:10px;border-radius:50%;background:var(--green)"></div>Approved</div>
                    <div style="display:flex;align-items:center;gap:6px;font-size:12px"><div style="width:10px;height:10px;border-radius:50%;background:var(--warning)"></div>Pending</div>
                    <div style="display:flex;align-items:center;gap:6px;font-size:12px"><div style="width:10px;height:10px;border-radius:50%;background:var(--danger)"></div>Rejected</div>
                </div>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-chart-bar"></i> Comparison</h3>
                <div class="bar-chart" id="barChart">
                    <!-- Filled by JS -->
                </div>
            </div>
        </div>

        <!-- ===== APPLICANTS TABLE ===== -->
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
                <tbody id="tableBody">
                    <!-- Filled by JS -->
                </tbody>
            </table>
        </div>

    </div>

    <script>
        /* ===== FETCH REAL DATA FROM PHP ===== */
        const applicants = <?php echo json_encode($applicants ?: []); ?>;

        let currentFilter = 'all';

        /* ===== COMPUTE STATS ===== */
        function getStats() {
            const total = applicants.length;
            const approved = applicants.filter(a => a.app_status === 'approved').length;
            const rejected = applicants.filter(a => a.app_status === 'rejected').length;
            const pending = applicants.filter(a => a.app_status === 'pending').length;
            return { total, approved, rejected, pending };
        }

        /* ===== ANIMATE NUMBERS ===== */
        function animateNumber(el, target) {
            let current = 0;
            const duration = 1000;
            const step = target / (duration / 16);
            const timer = setInterval(() => {
                current += step;
                if (current >= target) { current = target; clearInterval(timer); }
                el.textContent = Math.round(current);
            }, 16);
        }

        /* ===== UPDATE DASHBOARD ===== */
        function updateDashboard() {
            const s = getStats();

            /* Stat numbers */
            animateNumber(document.getElementById('statTotal'), s.total);
            animateNumber(document.getElementById('statApproved'), s.approved);
            animateNumber(document.getElementById('statRejected'), s.rejected);
            animateNumber(document.getElementById('statPending'), s.pending);

            /* Stat bars */
            setTimeout(() => {
                const pctA = s.total ? Math.round(s.approved/s.total*100) : 0;
                const pctR = s.total ? Math.round(s.rejected/s.total*100) : 0;
                const pctP = s.total ? Math.round(s.pending/s.total*100) : 0;

                document.getElementById('barTotal').style.width = '100%';
                document.getElementById('pctTotal').textContent = '100%';

                document.getElementById('barApproved').style.width = pctA + '%';
                document.getElementById('pctApproved').textContent = pctA + '%';

                document.getElementById('barRejected').style.width = pctR + '%';
                document.getElementById('pctRejected').textContent = pctR + '%';

                document.getElementById('barPending').style.width = pctP + '%';
                document.getElementById('pctPending').textContent = pctP + '%';

                /* Donut chart */
                const circ = 2 * Math.PI * 76; // ≈477.5
                const offsetA = circ - (circ * pctA / 100);
                const offsetP = circ - (circ * pctP / 100);
                const offsetR = circ - (circ * pctR / 100);

                document.getElementById('donutApproved').style.strokeDashoffset = offsetA;
                document.getElementById('donutPending').style.strokeDashoffset = offsetP;
                document.getElementById('donutRejected').style.strokeDashoffset = offsetR;
                document.getElementById('donutTotal').textContent = s.total;

                /* Bar chart */
                document.getElementById('barChart').innerHTML =
                    '<div class="bar-row"><div class="bar-label" style="color:var(--green)">Approved</div><div class="bar-track"><div class="bar-fill green" style="width:'+pctA+'%">'+s.approved+'</div></div></div>' +
                    '<div class="bar-row"><div class="bar-label" style="color:var(--warning)">Pending</div><div class="bar-track"><div class="bar-fill yellow" style="width:'+pctP+'%">'+s.pending+'</div></div></div>' +
                    '<div class="bar-row"><div class="bar-label" style="color:var(--danger)">Rejected</div><div class="bar-track"><div class="bar-fill red" style="width:'+pctR+'%">'+s.rejected+'</div></div></div>';
            }, 200);

            /* Table */
            renderTable();
        }

        /* ===== RENDER TABLE ===== */
        function renderTable() {
            const tbody = document.getElementById('tableBody');
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

            tbody.innerHTML = filtered.map(a => {
                const statusBadge = {
                    pending: '<span class="badge pending"><i class="fas fa-clock"></i> Pending</span>',
                    approved: '<span class="badge approved"><i class="fas fa-check"></i> Approved</span>',
                    rejected: '<span class="badge rejected"><i class="fas fa-times"></i> Rejected</span>'
                };

                            const actions = 
                    '<div class="action-group">' +
                    '<button class="action-btn view" onclick="viewApplicant('+a.user_id+')" title="View"><i class="fas fa-eye"></i></button>' +
                    '<button class="action-btn approve" onclick="updateStatus('+a.user_id+',\'approved\')" title="Approve"><i class="fas fa-check"></i></button>' +
                    '<button class="action-btn reject" onclick="updateStatus('+a.user_id+',\'rejected\')" title="Reject"><i class="fas fa-times"></i></button>' +
                    '</div>';

                // Format date applied
                const dateApplied = a.created_at ? new Date(a.created_at).toISOString().split('T')[0] : 'N/A';

                return '<tr data-status="'+a.app_status+'">' +
                    '<td><div class="applicant-name">'+a.firstname+' '+a.lastname+'</div><div class="applicant-email">'+a.email_add+'</div></td>' +
                    '<td>'+a.school+'<br><span style="font-size:12px;color:var(--muted)">'+a.course+' · '+a.year_level+'</span></td>' +
                    '<td>'+dateApplied+'</td>' +
                    '<td>'+statusBadge[a.app_status]+'</td>' +
                    '<td>'+actions+'</td>' +
                    '</tr>';
            }).join('');
        }

        /* ===== FILTER ===== */
        function filterTable(status, btn) {
            currentFilter = status;
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderTable();
        }

        /* ===== SEARCH ===== */
        function searchTable() { renderTable(); }

        /* ===== UPDATE STATUS ===== */
        function updateStatus(id, newStatus) {
            // Send AJAX request to update database
            fetch('admin_dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=update_status&user_id=' + id + '&status=' + newStatus
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    // Update local JS object
                    const a = applicants.find(a => a.user_id == id);
                    if (a) a.app_status = newStatus;
                    updateDashboard();
                    const verb = newStatus === 'approved' ? 'approved' : 'rejected';
                    showToast(a.firstname + ' ' + a.lastname + ' has been ' + verb + '.', newStatus === 'approved' ? 'success' : 'error');
                } else {
                    showToast('Failed to update status.', 'error');
                }
            })
            .catch(err => showToast('Network error.', 'error'));
        }

        /* ===== VIEW APPLICANT ===== */
        function viewApplicant(id) {
            const a = applicants.find(a => a.user_id == id);
            if (!a) return;

            // Document status logic
            const hasDocs = a.doc_count > 0;

            const body = document.getElementById('modalBody');
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
                '<div class="modal-field"><label>Annual Income</label><div class="val">₱'+a.income+'</div></div>' +
                '<div class="modal-field"><label>School</label><div class="val">'+a.school+'</div></div>' +
                '<div class="modal-field"><label>Year Level</label><div class="val">'+a.year_level+'</div></div>' +
                '<div class="modal-field"><label>Course</label><div class="val">'+a.course+'</div></div>' +
                                '<div class="modal-field"><label>GWA</label><div class="val">'+a.gwa+'</div></div>' +
                '<div class="modal-field"><label>Documents</label><div class="val">'+(a.doc_count > 0 ? '<span style="color:var(--green)"><i class="fas fa-check-circle"></i> '+a.doc_count+' Uploaded</span>' : '<span style="color:var(--danger)"><i class="fas fa-exclamation-circle"></i> Missing</span>')+'</div></div>' +
                '<div class="modal-field"><label>Status</label><div class="val"><span class="badge '+(a.app_status || 'pending')+'">'+(a.app_status ? a.app_status.charAt(0).toUpperCase()+a.app_status.slice(1) : 'Pending')+'</span></div></div>';

                        const actions = document.getElementById('modalActions');
            actions.innerHTML =
                '<button class="modal-btn cancel" onclick="closeModal()">Close</button>' +
                '<button class="modal-btn reject" onclick="updateStatus('+a.user_id+',\'rejected\');closeModal()"><i class="fas fa-times"></i> Reject</button>' +
                '<button class="modal-btn approve" onclick="updateStatus('+a.user_id+',\'approved\');closeModal()"><i class="fas fa-check"></i> Approve</button>';

            document.getElementById('modalOverlay').classList.add('open');
        }

        function closeModal() {
            document.getElementById('modalOverlay').classList.remove('open');
        }

        document.getElementById('modalOverlay').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        /* ===== TOAST ===== */
        function showToast(msg, type) {
            const box = document.getElementById('toastBox');
            const t = document.createElement('div');
            t.classList.add('toast', type || 'info');
            const icons = {success:'fa-circle-check',error:'fa-circle-xmark',info:'fa-circle-info'};
            t.innerHTML = '<i class="fas '+(icons[type]||icons.info)+'"></i><span>'+msg+'</span>';
            box.appendChild(t);
            setTimeout(()=>{t.classList.add('out');t.addEventListener('animationend',()=>t.remove())},3500);
        }

        /* ===== INIT ===== */
        updateDashboard();
    </script>
</body>
</html>