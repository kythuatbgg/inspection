<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.5; }
        .header { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 16px; }
        .header h1 { font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .header p { font-size: 10px; color: #64748b; margin-top: 4px; }
        .info-grid { display: table; width: 100%; margin-bottom: 16px; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; width: 140px; font-weight: bold; padding: 3px 8px 3px 0; color: #475569; }
        .info-value { display: table-cell; padding: 3px 0; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 10px; }
        table.data th { background: #f1f5f9; font-weight: bold; text-align: left; padding: 6px 8px; border: 1px solid #cbd5e1; }
        table.data td { padding: 5px 8px; border: 1px solid #e2e8f0; }
        table.data tr:nth-child(even) { background: #f8fafc; }
        .category-header { background: #e2e8f0; font-weight: bold; padding: 6px 8px; margin-top: 12px; }
        .result-box { text-align: center; padding: 12px; margin-top: 16px; border: 2px solid; }
        .result-pass { border-color: #16a34a; color: #16a34a; }
        .result-fail { border-color: #dc2626; color: #dc2626; }
        .result-box .label { font-size: 10px; color: #64748b; }
        .result-box .value { font-size: 22px; font-weight: bold; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 9px; font-weight: bold; }
        .badge-pass { background: #dcfce7; color: #16a34a; }
        .badge-fail { background: #fee2e2; color: #dc2626; }
        .badge-critical { background: #fef3c7; color: #d97706; }
        .signatures { margin-top: 32px; display: table; width: 100%; }
        .sig-col { display: table-cell; width: 50%; text-align: center; }
        .sig-col .title { font-weight: bold; font-size: 11px; margin-bottom: 60px; }
        .sig-col .name { font-size: 10px; color: #64748b; }
        .footer { margin-top: 20px; text-align: right; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Biên bản kiểm tra tủ cáp GPON</h1>
        <p>CNN Telecom — Hệ thống FBB Inspection</p>
    </div>

    <div class="info-grid">
        <div class="info-row"><span class="info-label">Mã tủ:</span><span class="info-value">{{ $cabinet->cabinet_code ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Trạm BTS:</span><span class="info-value">{{ $cabinet->bts_site ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Tọa độ:</span><span class="info-value">{{ $inspection->lat }}, {{ $inspection->lng }}</span></div>
        <div class="info-row"><span class="info-label">Nhân viên kiểm tra:</span><span class="info-value">{{ $user->name ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Checklist:</span><span class="info-value">{{ $checklist->name ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Ngày kiểm tra:</span><span class="info-value">{{ $inspection->created_at?->format('d/m/Y H:i') }}</span></div>
    </div>

    @foreach($grouped as $category => $items)
        <div class="category-header">{{ $category }}</div>
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 40px">#</th>
                    <th>Nội dung</th>
                    <th style="width: 70px">Điểm tối đa</th>
                    <th style="width: 70px">Điểm đạt</th>
                    <th style="width: 70px">Kết quả</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $item['content'] }}
                        @if($item['is_critical'])
                            <span class="badge badge-critical">CRITICAL</span>
                        @endif
                    </td>
                    <td style="text-align: center">{{ $item['max_score'] }}</td>
                    <td style="text-align: center">{{ $item['score_awarded'] }}</td>
                    <td style="text-align: center">
                        @if($item['is_failed'])
                            <span class="badge badge-fail">FAIL</span>
                        @else
                            <span class="badge badge-pass">ĐẠT</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="result-box {{ $inspection->final_result === 'PASS' ? 'result-pass' : 'result-fail' }}">
        <div class="label">Kết quả kiểm tra</div>
        <div class="value">{{ $inspection->final_result === 'PASS' ? 'ĐẠT' : 'KHÔNG ĐẠT' }}</div>
        <div class="label">Tổng điểm: {{ $inspection->total_score }} | Lỗi Critical: {{ $inspection->critical_errors_count }}</div>
    </div>

    <div class="signatures">
        <div class="sig-col">
            <div class="title">Nhân viên kiểm tra</div>
            <div class="name">(Ký tên)</div>
        </div>
        <div class="sig-col">
            <div class="title">Giám sát</div>
            <div class="name">(Ký tên)</div>
        </div>
    </div>

    <div class="footer">Xuất lúc: {{ $generated_at }}</div>
</body>
</html>
