<div class="dashboard-date-selector-card" style="background: #FFFDFB; border: 1px solid #EBDDD8; border-radius: 16px; padding: 18px 24px; margin-bottom: 24px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; box-shadow: 0 2px 10px rgba(61, 27, 61, 0.04);">
    <div style="display: flex; flex-direction: column; gap: 4px;">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <h2 style="font-family: 'Playfair Display', Georgia, serif; font-size: 24px; font-weight: 700; color: #3D1B3D; margin: 0; line-height: 1.2;">
                Dashboard — {{ $formattedDate }}
            </h2>
            @if($isToday)
                <span style="display: inline-flex; align-items: center; gap: 6px; background: rgba(30, 107, 55, 0.1); color: #1E6B37; border: 1px solid rgba(30, 107, 55, 0.25); padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;">
                    <span style="width: 7px; height: 7px; border-radius: 50%; background: #1E6B37; display: inline-block;"></span>
                    Today's Live Queue
                </span>
            @else
                <span style="display: inline-flex; align-items: center; gap: 6px; background: rgba(226, 112, 58, 0.1); color: #E2703A; border: 1px solid rgba(226, 112, 58, 0.25); padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;">
                    📅 Historical Archive
                </span>
            @endif
        </div>
        <p style="margin: 0; font-size: 12px; color: #8A7B7E;">
            @if($isToday)
                Displaying operational metrics for today. Automatically rolls over to the new day at 12:00 AM Philippine Time (PHT).
            @else
                Displaying recorded statistics for {{ $formattedDate }}. Historical data is permanently preserved.
            @endif
        </p>
    </div>

    <form method="GET" action="{{ url()->current() }}" id="dateSelectorForm" style="display: flex; align-items: center; gap: 10px; margin: 0; flex-wrap: wrap;">
        @foreach(request()->except(['date', 'page']) as $key => $val)
            @if(is_string($val))
                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
            @endif
        @endforeach
        
        <label for="dashboardDateInput" style="font-size: 12.5px; font-weight: 700; color: #3D1B3D; margin: 0; display: flex; align-items: center; gap: 6px;">
            Date:
        </label>
        
        <div style="position: relative; display: inline-flex; align-items: center;">
            <input 
                type="date" 
                id="dashboardDateInput" 
                name="date" 
                value="{{ $selectedDateStr }}" 
                max="{{ \Carbon\Carbon::now('Asia/Manila')->toDateString() }}"
                onchange="document.getElementById('dateSelectorForm').submit()"
                style="height: 40px; padding: 0 14px; border: 1.5px solid #EBDDD8; border-radius: 10px; font-size: 13px; font-weight: 600; color: #3D1B3D; background: #FFFDFB; outline: none; cursor: pointer; transition: border-color 0.2s, box-shadow 0.2s;"
                onfocus="this.style.borderColor='#E2703A'; this.style.boxShadow='0 0 0 3px rgba(226, 112, 58, 0.15)';"
                onblur="this.style.borderColor='#EBDDD8'; this.style.boxShadow='none';"
            >
        </div>

        @if(!$isToday)
            <a href="{{ url()->current() }}" style="display: inline-flex; align-items: center; height: 38px; padding: 0 14px; border-radius: 10px; background: #3D1B3D; color: #FFFDFB; font-size: 12px; font-weight: 700; text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='#5A2E5A'" onmouseout="this.style.background='#3D1B3D'">
                Jump to Today &rarr;
            </a>
        @endif
    </form>
</div>
