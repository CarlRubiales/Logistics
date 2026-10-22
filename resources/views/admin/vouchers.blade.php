<x-admin-layout title="Vouchers">
    @if ($errors->any())
        <div style="background:#fdece6;color:#b8451f;padding:12px 16px;border-radius:12px;margin-bottom:16px;font-size:.88rem;">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="panel">
        <h2>Create Voucher</h2>
        <form method="POST" action="{{ route('admin.vouchers.store') }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;align-items:end;">
            @csrf
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:5px;">Voucher Code</label>
                <input type="text" name="code" placeholder="e.g. LUMORA10" required style="width:100%;height:40px;padding:0 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit;text-transform:uppercase;">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:5px;">Discount Type</label>
                <select name="type" style="width:100%;height:40px;padding:0 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit;background:#fff;">
                    <option value="percent">Percentage (%)</option>
                    <option value="shipping">Free Shipping</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:5px;">Discount % (ignored for Free Shipping)</label>
                <input type="number" name="value" step="0.01" min="0" max="100" placeholder="e.g. 10" style="width:100%;height:40px;padding:0 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit;">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:5px;">Min. Spend (₱)</label>
                <input type="number" name="min_spend" step="0.01" min="0" value="0" style="width:100%;height:40px;padding:0 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit;">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:5px;">Expiry Date (optional)</label>
                <input type="date" name="expires_at" style="width:100%;height:40px;padding:0 12px;border:1px solid var(--border);border-radius:10px;font-family:inherit;">
            </div>
            <div>
                <button type="submit" class="btn btn-dark" style="width:100%;height:40px;font-size:.86rem;">+ Save Voucher</button>
            </div>
        </form>
    </div>

    <div class="panel">
        <h2>All Vouchers</h2>
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Discount</th>
                    <th>Min. Spend</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vouchers as $voucher)
                    <tr>
                        <td><strong>{{ $voucher->code }}</strong></td>
                        <td>{{ ucfirst($voucher->type) }}</td>
                        <td>
                            @if ($voucher->type === 'percent')
                                {{ rtrim(rtrim(number_format((float) $voucher->value, 2), '0'), '.') }}% OFF
                            @else
                                Free Shipping
                            @endif
                        </td>
                        <td>₱{{ number_format((float) $voucher->min_spend, 2) }}</td>
                        <td>{{ $voucher->expires_at ? \Carbon\Carbon::parse($voucher->expires_at)->format('M d, Y') : 'No expiry' }}</td>
                        <td>
                            @if ($voucher->is_active)
                                <span class="match-badge">Active</span>
                            @else
                                <span class="muted-badge">Inactive</span>
                            @endif
                        </td>
                        <td style="text-align:right;white-space:nowrap;">
                            <form method="POST" action="{{ route('admin.vouchers.toggle', $voucher->id) }}" style="display:inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline">{{ $voucher->is_active ? 'Disable' : 'Enable' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.vouchers.destroy', $voucher->id) }}" style="display:inline;" onsubmit="return confirm('Delete this voucher?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;color:#999;padding:20px;">No vouchers created yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
