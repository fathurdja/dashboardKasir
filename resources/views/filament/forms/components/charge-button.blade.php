<div style="margin-top:1rem;">
    {{-- Pay Button --}}
    <button 
        type="submit"
        style="
            width:100%;
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding:0.65rem 1rem;
            background:linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color:#fff;
            border:none;
            border-radius:10px;
            cursor:pointer;
            font-family:inherit;
            transition:all 0.2s ease;
            box-shadow:0 4px 14px rgba(99,102,241,0.35);
        "
        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 20px rgba(99,102,241,0.45)';"
        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 14px rgba(99,102,241,0.35)';"
        onmousedown="this.style.transform='scale(0.97)';"
        onmouseup="this.style.transform='translateY(-1px)';"
    >
        <div style="display:flex; align-items:center; gap:0.6rem;">
            <div style="background:rgba(255,255,255,0.2); padding:0.35rem; border-radius:8px; display:flex; align-items:center; justify-content:center;">
                <svg style="width:1.1rem; height:1.1rem; color:#fff;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
            </div>
            <span style="font-size:0.85rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em;">Bayar Sekarang</span>
        </div>
        
        <div style="font-size:1rem; font-weight:900; letter-spacing:-0.02em;">
            @php
                $total = $this->data['total_price'] ?? 0;
            @endphp
            Rp {{ number_format($total, 0, ',', '.') }}
        </div>
    </button>
    
    {{-- Clear Cart Button --}}
    <button 
        type="button"
        style="
            width:100%;
            margin-top:0.5rem;
            padding:0.45rem 0.75rem;
            background:#f8fafc;
            border:1px solid #e2e8f0;
            border-radius:8px;
            color:#64748b;
            font-size:0.7rem;
            font-weight:600;
            cursor:pointer;
            font-family:inherit;
            transition:all 0.15s ease;
        "
        onmouseover="this.style.background='#fee2e2'; this.style.borderColor='#fca5a5'; this.style.color='#dc2626';"
        onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0'; this.style.color='#64748b';"
        wire:click="$set('data.items', [])"
    >
        🗑️ Kosongkan Keranjang
    </button>
</div>
