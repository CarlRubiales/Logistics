<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lumora Admin | {{ $title ?? 'Dashboard' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#FBF1EC; --card-bg:#FFFFFF; --sidebar-bg:#FFFFFF; --maroon:#5B1A35; --maroon-dark:#45132A; --coral:#E8674A; --peach-1:#F3C9A0; --peach-2:#E0966B; --terra-1:#D98A5E; --terra-2:#B85C3B; --sage-1:#7C9473; --sage-2:#5C7355; --text-dark:#2B1C22; --text-muted:#8B7A80; --border:#F0E2DA; --radius:18px; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; background:var(--bg); color:var(--text-dark); display:flex; min-height:100vh; }
        .sidebar { width:230px; background:var(--sidebar-bg); border-right:1px solid var(--border); padding:28px 18px; display:flex; flex-direction:column; gap:6px; position:fixed; top:0; left:0; height:100vh; }
        .nav-link { display:flex; align-items:center; gap:10px; padding:11px 14px; border-radius:12px; color:var(--text-muted); text-decoration:none; font-size:14.5px; font-weight:500; transition:all .18s ease; }
        .nav-link:hover { background:var(--bg); color:var(--maroon); }
        .nav-link.active { background:var(--maroon); color:#fff; font-weight:600; }
        .logout-form { margin-top:auto; }
        .logout-btn { width:100%; padding:11px 14px; border-radius:12px; border:1px solid var(--border); background:transparent; color:var(--text-muted); font-weight:500; font-size:14px; cursor:pointer; transition:all .18s ease; font-family:inherit; }
        .logout-btn:hover { border-color:var(--coral); color:var(--coral); }
        .main { flex:1; padding:34px 40px; margin-left:230px; }
        .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:32px; }
        .topbar h1 { font-size:28px; font-weight:800; }
        .admin-pill { background:#fff; border:1px solid var(--border); padding:8px 16px; border-radius:999px; font-size:13.5px; font-weight:600; color:var(--maroon); }
        .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:22px; margin-bottom:22px; }
        .stat-card { background:var(--card-bg); border-radius:var(--radius); padding:24px; display:flex; flex-direction:column; gap:14px; border:1px solid var(--border); box-shadow:0 4px 18px rgba(91,26,53,.04); }
        .stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px; }
        .icon-sales { background:linear-gradient(135deg,var(--terra-1),var(--terra-2)); }
        .icon-orders { background:linear-gradient(135deg,var(--maroon),var(--maroon-dark)); }
        .icon-products { background:linear-gradient(135deg,var(--sage-1),var(--sage-2)); }
        .icon-buyers { background:linear-gradient(135deg,var(--peach-1),var(--peach-2)); }
        .stat-label { font-size:13.5px; color:var(--text-muted); font-weight:500; }
        .stat-value { font-size:26px; font-weight:800; }
        .panel { background:var(--card-bg); border-radius:var(--radius); border:1px solid var(--border); padding:24px; box-shadow:0 4px 18px rgba(91,26,53,.04); margin-bottom:22px; }
        .panel h2 { font-size:18px; font-weight:800; margin-bottom:16px; }
        .success-box { background:#eef3ec; color:var(--sage-2); padding:10px 14px; border-radius:10px; margin-bottom:16px; font-size:.88rem; }
        table { width:100%; border-collapse:collapse; }
        th, td { text-align:left; padding:10px 8px; border-bottom:1px solid var(--border); font-size:.9rem; }
        th { color:var(--text-muted); font-weight:600; }
        tr:hover td { background-color:var(--bg); }
        .btn { display:inline-block; padding:6px 12px; border-radius:8px; border:1px solid transparent; font-size:.82rem; cursor:pointer; text-decoration:none; font-weight:500; font-family:inherit; }
        .btn-dark { background:var(--maroon); color:#fff; }
        .btn-dark:hover { background:var(--maroon-dark); }
        .btn-danger { background:#fff; border-color:var(--terra-2); color:var(--terra-2); }
        .btn-danger:hover { background:#fbeee8; }
        .btn-outline { background:#fff; border-color:var(--border); color:var(--text-dark); }
        .btn-outline:hover { background:var(--bg); }
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.75rem; color:#fff; }
        .mismatch-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:600; background:#fbeee8; color:var(--terra-2); }
        .match-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:600; background:#eef3ec; color:var(--sage-2); }
        .muted-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:500; background:#f2ecee; color:var(--text-muted); }
        @media (max-width:900px) { .stats-grid { grid-template-columns:repeat(2,1fr); } }
    </style>
</head>
<body>
<aside class="sidebar">
    <x-logo class="mb-9 max-[680px]:origin-center max-[680px]:scale-[0.45]" />
    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
    <a href="{{ route('admin.users') }}" class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}">Users</a>
    <a href="{{ route('admin.applications') }}" class="nav-link {{ request()->routeIs('admin.applications') ? 'active' : '' }}">Applications</a>
    <a href="{{ route('admin.compliance') }}" class="nav-link {{ request()->routeIs('admin.compliance') ? 'active' : '' }}">Compliance</a>
    <a href="{{ route('admin.complaints') }}" class="nav-link {{ request()->routeIs('admin.complaints') ? 'active' : '' }}">Complaints</a>
    <a href="{{ route('admin.chat.index') }}" class="nav-link {{ request()->routeIs('admin.chat.*') ? 'active' : '' }}">Messages</a>
    <a href="{{ route('admin.commission') }}" class="nav-link {{ request()->routeIs('admin.commission') ? 'active' : '' }}">Commission</a>
    <a href="{{ route('admin.vouchers.index') }}" class="nav-link {{ request()->routeIs('admin.vouchers.*') ? 'active' : '' }}">Vouchers</a>
    <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">Settings</a>
    <form method="POST" action="{{ route('logout') }}" class="logout-form">
        @csrf
        <button type="submit" class="logout-btn">Logout</button>
    </form>
</aside>

<main class="main">
    <div class="topbar">
        <h1>{{ $title ?? 'Dashboard' }}</h1>
        <span class="admin-pill">Admin &mdash; {{ Auth::user()->name }}</span>
    </div>

    @if (session('flash_success'))
        <div class="success-box">{{ session('flash_success') }}</div>
    @endif

    {{ $slot }}
</main>
</body>
</html>
