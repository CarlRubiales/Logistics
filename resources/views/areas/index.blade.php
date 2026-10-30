<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>Delivery areas — Lumora Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="logistics-page">
<div class="app-shell">
    @include('partials.sidebar')
    <main class="main-content">
        @include('partials.topbar', ['category' => 'Fleet & Logistics', 'title' => 'Delivery Areas'])

        <div class="content-wrap">
            @if(session('success'))
                <div class="server-alert" role="status"><span>✓</span> {{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="server-alert error" role="alert"><span>!</span> {{ $errors->first() }}</div>
            @endif

            <section class="welcome-row">
                <div>
                    <div class="eyebrow">DESTINATION MAPPING & ROUTING</div>
                    <h1>Delivery areas</h1>
                    <p class="intro">Configure the province, city, and postal-code rules that automatically route parcels to the right sorting center bin and courier.</p>
                </div>
            </section>

            <section class="dashboard-grid">
                <!-- Create Area Form -->
                <article class="panel">
                    <div class="panel-heading">
                        <div>
                            <div class="section-kicker">NEW AREA</div>
                            <h2>Create delivery area</h2>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('areas.store') }}" class="form-grid">
                        @csrf
                        <label>
                            Area name
                            <input name="name" value="{{ old('name') }}" placeholder="e.g. SCS-PILA, District 4" required>
                            @error('name')<span style="color:#A52222;font-size:11px;font-weight:600;display:block;margin-top:4px;">{{ $message }}</span>@enderror
                        </label>
                        <label>
                            Province
                            <input name="province" value="{{ old('province', 'Laguna') }}" placeholder="e.g. Laguna, Metro Manila, Cavite" required>
                            @error('province')<span style="color:#A52222;font-size:11px;font-weight:600;display:block;margin-top:4px;">{{ $message }}</span>@enderror
                        </label>
                        <label class="full-span">
                            Cities / municipalities (comma-separated)
                            <input name="cities" value="{{ old('cities') }}" placeholder="e.g. Pila, Santa Cruz, Victoria">
                            <small style="color:#8A7B7E;font-size:11px;margin-top:3px;display:block;">Parcels addressed to these cities will automatically route to this zone.</small>
                        </label>
                        <label class="full-span">
                            Postal codes (comma-separated)
                            <input name="postal_codes" value="{{ old('postal_codes') }}" placeholder="e.g. 4009, 4010">
                        </label>
                        <label class="full-span">
                            Coverage notes
                            <textarea name="coverage_rules" placeholder="Optional notes, landmarks, or special dispatch instructions">{{ old('coverage_rules') }}</textarea>
                        </label>
                        <button class="primary-button full-span" type="submit">＋ Create area</button>
                    </form>
                </article>

                <!-- How Mapping Works -->
                <article class="panel">
                    <div class="panel-heading">
                        <div>
                            <div class="section-kicker">HOW MAPPING WORKS</div>
                            <h2>Automatic area detection</h2>
                        </div>
                    </div>
                    <p class="intro" style="line-height:1.7">
                        When a parcel is received or scanned, the system compares its destination city, postal code, and province against these rules. If matched, it automatically assigns the delivery zone.
                    </p>
                    <div class="detail-divider" style="margin: 20px 0;"></div>
                    <div class="section-kicker">ACTIVE PROVINCES & ZONES</div>
                    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:8px;">
                        @php
                            $uniqueProvinces = $areas->pluck('province')->filter()->unique();
                        @endphp
                        @forelse($uniqueProvinces as $prov)
                            <span style="font-size:12px; font-weight:700; background:#FBEFEA; color:#3D1B3D; padding:4px 10px; border-radius:6px; border:1px solid #EBDDD8; display:inline-flex; align-items:center; gap:4px;">
                                📍 {{ $prov }}
                            </span>
                        @empty
                            <small style="color:#8A7B7E;">No areas configured yet.</small>
                        @endforelse
                    </div>
                    <small style="display:block; color:#8A7B7E; font-size:11px; margin-top:12px;">
                        Tip: You can create multiple delivery zones under the same province (for example, separate zones for different cities like Pila, Santa Cruz, or San Pablo).
                    </small>
                </article>
            </section>

            <!-- Table of Configured Areas -->
            <section class="panel shipments-panel">
                <div class="panel-heading shipments-heading">
                    <div>
                        <div class="section-kicker">ACTIVE CONFIGURATION</div>
                        <h2>Configured delivery areas</h2>
                    </div>
                    <span style="font-size:12px; color:#8A7B7E;">{{ $areas->count() }} areas registered</span>
                </div>
                <div class="table-wrap">
                    <table class="shipments-table">
                        <thead>
                            <tr>
                                <th>Area Name & Province</th>
                                <th>Coverage Rules</th>
                                <th>Parcels</th>
                                <th>Riders</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($areas as $area)
                                <tr>
                                    <td>
                                        <strong>{{ $area->name }}</strong>
                                        <small style="display:block; margin-top:2px;">
                                            <span style="display:inline-flex; align-items:center; gap:3px; font-weight: 700; background: #FBEFEA; color: #3D1B3D; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                                                📍 {{ $area->province ?: 'Laguna' }}
                                            </span>
                                        </small>
                                    </td>
                                    <td>
                                        <span style="color:#3A2E30; font-weight:500;">
                                            {{ collect($area->cities ?? [])->join(', ') ?: 'All cities in province' }}
                                        </span>
                                        <small style="display:block; color:#8A7B7E;">
                                            {{ collect($area->postal_codes ?? [])->join(', ') ?: 'No postal filter' }}
                                        </small>
                                        @if($area->coverage_rules)
                                            <small style="display:block; color:#8A7B7E; font-style:italic;">
                                                Note: {{ $area->coverage_rules }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>{{ $area->parcels_count }}</td>
                                    <td>{{ $area->riders_count }}</td>
                                    <td>
                                        <span class="status-chip {{ $area->is_active ? 'in-transit' : 'exception' }}">
                                            <b></b>{{ $area->is_active ? 'Active' : 'Paused' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex; justify-content:flex-end; gap:8px; align-items:center;">
                                            <button
                                                type="button"
                                                class="text-button"
                                                onclick="openEditAreaModal({{ json_encode([
                                                    'id' => $area->id,
                                                    'name' => $area->name,
                                                    'province' => $area->province ?: 'Laguna',
                                                    'cities' => collect($area->cities ?? [])->join(', '),
                                                    'postal_codes' => collect($area->postal_codes ?? [])->join(', '),
                                                    'coverage_rules' => $area->coverage_rules ?? '',
                                                ]) }})"
                                                style="color: #3D1B3D; font-weight: 600;"
                                            >
                                                Edit
                                            </button>
                                            <form method="POST" action="{{ route('areas.toggle', $area) }}" style="display:inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button class="text-button" type="submit" style="color: {{ $area->is_active ? '#A52222' : '#1E6B37' }};">
                                                    {{ $area->is_active ? 'Pause' : 'Activate' }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align:center; padding:32px; color:#8A7B7E;">
                                        No delivery areas configured yet. Create your first delivery area above.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</div>

<!-- Edit Area Modal -->
<dialog class="shipment-status-dialog" id="editAreaDialog">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="margin: 0; font-size: 20px;">Edit Delivery Area</h2>
        <button type="button" class="text-button" onclick="document.getElementById('editAreaDialog').close()" style="font-size: 18px; color: #8A7B7E;">✕</button>
    </div>
    <form method="POST" id="editAreaForm" class="form-grid">
        @csrf
        @method('PUT')
        <label>
            Area name
            <input name="name" id="editAreaName" required>
        </label>
        <label>
            Province
            <input name="province" id="editAreaProvince" required>
        </label>
        <label class="full-span">
            Cities / municipalities (comma-separated)
            <input name="cities" id="editAreaCities" placeholder="e.g. Santa Cruz, Pila">
        </label>
        <label class="full-span">
            Postal codes (comma-separated)
            <input name="postal_codes" id="editAreaPostalCodes" placeholder="e.g. 4009, 4010">
        </label>
        <label class="full-span">
            Coverage notes
            <textarea name="coverage_rules" id="editAreaCoverageRules" placeholder="Optional notes"></textarea>
        </label>
        <div class="shipment-dialog-actions full-span" style="margin-top: 14px;">
            <button type="button" class="secondary-button" onclick="document.getElementById('editAreaDialog').close()">Cancel</button>
            <button type="submit" class="primary-button">Save changes</button>
        </div>
    </form>
</dialog>

<script>
function openEditAreaModal(area) {
    const dialog = document.getElementById('editAreaDialog');
    const form = document.getElementById('editAreaForm');
    form.action = '/areas/' + area.id;

    document.getElementById('editAreaName').value = area.name || '';
    document.getElementById('editAreaProvince').value = area.province || 'Laguna';
    document.getElementById('editAreaCities').value = area.cities || '';
    document.getElementById('editAreaPostalCodes').value = area.postal_codes || '';
    document.getElementById('editAreaCoverageRules').value = area.coverage_rules || '';

    dialog.showModal();
}
</script>
</body>
</html>
