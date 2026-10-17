<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>Create Branch Accounts & Logistics Users — Lumora</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .badge-branch-elevated {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 999px;
            background: #EDE6F2;
            color: #3D1B3D;
        }
        .badge-role-elevated {
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            background: #F4EBE2;
            color: #8C4A15;
            text-transform: uppercase;
        }
        .card-panel-elevated {
            background: #FFFDFB;
            border: 1px solid #EBDDD8;
            border-radius: 18px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 4px 16px rgba(61, 27, 61, 0.03);
        }
        .form-grid-clean {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 18px;
            margin-bottom: 18px;
        }
        .form-group-clean label {
            display: block;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #8A7B7E;
            margin-bottom: 7px;
        }
        .form-control-clean {
            width: 100%;
            height: 42px;
            padding: 0 14px;
            font-size: 13px;
            border: 1px solid #EBDDD8;
            border-radius: 10px;
            background: #FAF6F0;
            color: #3A2E30;
            transition: all 0.15s;
        }
        .form-control-clean:focus {
            outline: none;
            border-color: #3D1B3D;
            background: #FFFDFB;
            box-shadow: 0 0 0 3px rgba(61, 27, 61, 0.12);
        }
        .accounts-table-clean {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }
        .accounts-table-clean th {
            text-align: left;
            padding: 13px 20px;
            background: #FAF6F0;
            color: #8A7B7E;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #EBDDD8;
        }
        .accounts-table-clean td {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(235, 221, 216, 0.6);
            vertical-align: middle;
        }
        .accounts-table-clean tr:last-child td {
            border-bottom: none;
        }
        .accounts-table-clean tr:hover {
            background: #FAF6F0;
        }
    </style>
</head>
<body class="logistics-page">
<div class="app-shell">
    @include('partials.sidebar')
    <main class="main-content">
        @include('partials.topbar', [
            'category' => 'Master Administration',
            'title' => 'Create Account & Branch Access'
        ])

        <div class="content-wrap">
            @if(session('success'))
                <div class="server-alert" role="status"><span>✓</span> {{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="server-alert error" role="alert"><span>!</span> {{ $errors->first() }}</div>
            @endif

            <!-- Hero Banner -->
            <section class="dash-hero-banner">
                <div class="dash-hero-title">
                    <div class="eyebrow" style="color: #793779;">BRANCH ACCESS & LIFECYCLE</div>
                    <h1>Create Account & Branch Provisioning</h1>
                    <p>
                        Provision secure login credentials and dedicated workspaces for newly established regional branch hubs.
                    </p>
                </div>
                <div class="dash-quick-links">
                    <a href="{{ route('admin.branches') }}" class="dash-link-pill primary">
                        📍 Branches Directory
                    </a>
                    <a href="{{ route('admin.dashboard') }}" class="dash-link-pill secondary">
                        🏢 Headquarters Command
                    </a>
                </div>
            </section>

            <!-- Metric Cards -->
            <section class="metric-grid-elevated">
                <article class="metric-card-elevated plum">
                    <div class="metric-header">
                        <span class="metric-label-clean">Branch Accounts</span>
                        <div class="metric-icon-bubble" style="background: rgba(61, 27, 61, 0.08); color: #3D1B3D;">🏢</div>
                    </div>
                    <div class="metric-value-clean">{{ $branchAccounts->whereNotNull('branch')->where('branch', '!=', '')->count() }}</div>
                    <div class="metric-sub-clean positive">
                        <span>●</span> Active branch logins
                    </div>
                </article>

                <article class="metric-card-elevated green">
                    <div class="metric-header">
                        <span class="metric-label-clean">Master HQ Admins</span>
                        <div class="metric-icon-bubble" style="background: rgba(30, 107, 55, 0.08); color: #1E6B37;">🛡️</div>
                    </div>
                    <div class="metric-value-clean">{{ $branchAccounts->where('role', 'super_admin')->count() }}</div>
                    <div class="metric-sub-clean positive">
                        <span>●</span> Global administration rights
                    </div>
                </article>

                <article class="metric-card-elevated orange">
                    <div class="metric-header">
                        <span class="metric-label-clean">Configured Hubs</span>
                        <div class="metric-icon-bubble" style="background: rgba(226, 112, 58, 0.1); color: #E2703A;">📍</div>
                    </div>
                    <div class="metric-value-clean">{{ $knownBranches->count() }}</div>
                    <div class="metric-sub-clean positive">
                        <span>●</span> Active regional centers
                    </div>
                </article>
            </section>

            <!-- 1. Provision New Branch Account Form -->
            <section class="card-panel-elevated">
                <div style="margin-bottom: 22px;">
                    <div class="section-kicker" style="color: #793779;">ACCOUNT PROVISIONING</div>
                    <h2 style="margin: 4px 0; font-size: 20px; color: #3D1B3D; font-family: 'Playfair Display', Georgia, serif;">
                        Register New Branch User Account
                    </h2>
                    <p style="margin: 0; font-size: 13px; color: #8A7B7E;">
                        Set up an account for a new branch operator. The system will automatically create their account, isolate their data, and initialize their central coverage area.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <div class="form-grid-clean">
                        <div class="form-group-clean">
                            <label for="branchInput">Branch / Hub Name *</label>
                            <input
                                id="branchInput"
                                name="branch"
                                class="form-control-clean"
                                placeholder="e.g. Los Baños, Calamba, San Pablo, Biñan..."
                                value="{{ old('branch') }}"
                                required
                            >
                            <small style="font-size: 11px; color: #8A7B7E; margin-top: 4px; display: block;">
                                Existing: {{ $knownBranches->implode(', ') }}
                            </small>
                        </div>

                        <div class="form-group-clean">
                            <label for="nameInput">Branch Operator / Manager Name *</label>
                            <input
                                id="nameInput"
                                name="name"
                                class="form-control-clean"
                                placeholder="e.g. Calamba Dispatch Head or Juan Dela Cruz"
                                value="{{ old('name') }}"
                                required
                            >
                        </div>

                        <div class="form-group-clean">
                            <label for="emailInput">Login Email Address *</label>
                            <input
                                id="emailInput"
                                name="email"
                                type="email"
                                class="form-control-clean"
                                placeholder="e.g. calamba@gmail.com"
                                value="{{ old('email') }}"
                                required
                            >
                        </div>

                        <div class="form-group-clean">
                            <label for="passwordInput">Initial Account Password *</label>
                            <input
                                id="passwordInput"
                                name="password"
                                type="password"
                                class="form-control-clean"
                                placeholder="Minimum 6 characters"
                                required
                            >
                        </div>

                        <div class="form-group-clean">
                            <label for="roleInput">Account Role / Access Level *</label>
                            <select id="roleInput" name="role" class="form-control-clean" required>
                                <option value="admin" selected>Branch Hub Admin (Full Branch Workspace Access)</option>
                                <option value="operations_manager">Operations Manager (Sorting & Dispatch)</option>
                                <option value="warehouse_staff">Warehouse Sorting Staff</option>
                                <option value="support">Customer Support Agent</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; margin-top: 14px;">
                        <button type="submit" class="primary-button" style="padding: 11px 26px; font-weight: 700; font-size: 13px;">
                            + Create Branch Account & Initialize Hub
                        </button>
                    </div>
                </form>
            </section>

            <!-- 2. Existing Logistics & Branch Accounts Table -->
            <section class="panel shipments-panel" style="padding: 0; overflow: hidden; border-radius: 18px;">
                <div class="panel-heading" style="padding: 20px 24px; border-bottom: 1px solid #EBDDD8; background: #FAF6F0; margin-bottom: 0;">
                    <div>
                        <div class="section-kicker">ROSTER</div>
                        <h2 style="font-family: 'Playfair Display', Georgia, serif;">Registered Logistics Accounts</h2>
                    </div>
                    <span style="font-size: 12px; color: #8A7B7E; font-weight: 600;">
                        {{ $branchAccounts->count() }} active accounts in system
                    </span>
                </div>

                <div style="overflow-x: auto;">
                    <table class="accounts-table-clean">
                        <thead>
                            <tr>
                                <th>Branch Hub</th>
                                <th>User & Login Email</th>
                                <th>Role</th>
                                <th>Registered On</th>
                                <th>Quick Password Reset</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branchAccounts as $account)
                                <tr>
                                    <td>
                                        @if($account->branch)
                                            <span class="badge-branch-elevated">🏢 {{ $account->branch }} Hub</span>
                                        @elseif($account->isMasterAdmin())
                                            <span class="badge-branch-elevated" style="background: #3D1B3D; color: #FFFDFB;">Central HQ</span>
                                        @else
                                            <span style="color: #8A7B7E; font-size: 11px;">Unassigned</span>
                                        @endif
                                    </td>

                                    <td>
                                        <strong style="color: #3D1B3D; font-size: 13px;">{{ $account->name }}</strong>
                                        <small style="display: block; color: #8A7B7E; font-size: 11px;">{{ $account->email }}</small>
                                    </td>

                                    <td>
                                        <span class="badge-role-elevated">{{ str_replace('_', ' ', $account->role) }}</span>
                                    </td>

                                    <td style="font-size: 12px; color: #8A7B7E;">
                                        {{ $account->created_at ? $account->created_at->format('M d, Y') : 'System Initial' }}
                                    </td>

                                    <td>
                                        <form method="POST" action="{{ route('admin.users.password', $account) }}" style="display: flex; gap: 6px; align-items: center;">
                                            @csrf
                                            <input
                                                type="password"
                                                name="password"
                                                placeholder="New password"
                                                required
                                                minlength="6"
                                                style="height: 32px; font-size: 11px; padding: 0 10px; border: 1px solid #EBDDD8; border-radius: 8px; width: 130px; background: #FAF6F0;"
                                            >
                                            <button type="submit" class="secondary-button" style="height: 32px; padding: 0 10px; font-size: 11px;">
                                                Update
                                            </button>
                                        </form>
                                    </td>

                                    <td style="text-align: right;">
                                        @if($account->id === auth()->id())
                                            <span style="color: #8A7B7E; font-size: 11px; font-weight: 600;">(Current Session)</span>
                                        @else
                                            <form method="POST" action="{{ route('admin.users.destroy', $account) }}" onsubmit="return confirm('Are you sure you want to permanently delete account for {{ $account->name }} ({{ $account->email }})?');" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-button" style="color: #C62828; font-weight: 700; font-size: 12px;">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</div>
</body>
</html>
