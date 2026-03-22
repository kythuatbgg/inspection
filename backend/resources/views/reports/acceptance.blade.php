<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; line-height: 1.4; }
        .header { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 14px; }
        .header h1 { font-size: 15px; font-weight: bold; text-transform: uppercase; }
        .header p { font-size: 9px; color: #64748b; }
        .info-grid { display: table; width: 100%; margin-bottom: 12px; font-size: 10px; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; width: 130px; font-weight: bold; padding: 2px 6px 2px 0; color: #475569; }
        .info-value { display: table-cell; padding: 2px 0; }
        .kpi-row { display: table; width: 100%; margin-bottom: 14px; }
        .kpi-box { display: table-cell; text-align: center; padding: 10px; border: 1px solid #e2e8f0; }
        .kpi-box .num { font-size: 20px; font-weight: bold; }
        .kpi-box .label { font-size: 8px; color: #64748b; text-transform: uppercase; }
        .kpi-pass .num { color: #16a34a; }
        .kpi-fail .num { color: #dc2626; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 9px; }
        table.data th { background: #f1f5f9; font-weight: bold; text-align: left; padding: 5px 6px; border: 1px solid #cbd5e1; }
        table.data td { padding: 4px 6px; border: 1px solid #e2e8f0; }
        table.data tr:nth-child(even) { background: #f8fafc; }
        .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 8px; font-weight: bold; }
        .badge-pass { background: #dcfce7; color: #16a34a; }
        .badge-fail { background: #fee2e2; color: #dc2626; }
        .conclusion { border: 2px solid #0f172a; padding: 14px; margin-top: 20px; text-align: center; }
        .conclusion h2 { font-size: 13px; margin-bottom: 8px; }
        .signatures { margin-top: 40px; display: table; width: 100%; }
        .sig-col { display: table-cell; width: 33%; text-align: center; vertical-align: top; }
        .sig-col .title { font-weight: bold; font-size: 10px; }
        .sig-col .subtitle { font-size: 8px; color: #64748b; margin-bottom: 50px; }
        .sig-col .name { font-size: 9px; color: #475569; }
        .footer { margin-top: 16px; text-align: right; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Biên bản nghiệm thu</h1>
        <p>CNN Telecom — Hệ thống FBB Inspection</p>
    </div>

    <div class="info-grid">
        <div class="info-row"><span class="info-label">Đợt kiểm tra:</span><span class="info-value">{{ $batch->name }}</span></div>
        <div class="info-row"><span class="info-label">Nhân viên KT:</span><span class="info-value">{{ $user->name ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Checklist:</span><span class="info-value">{{ $checklist->name ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Thời gian:</span><span class="info-value">{{ $batch->start_date?->format('d/m/Y') }} — {{ $batch->end_date?->format('d/m/Y') }}</span></div>
    </div>

    <div class="kpi-row">
        <div class="kpi-box">
            <div class="num">{{ $summary['total'] }}</div>
            <div class="label">Tổng tủ</div>
        </div>
        <div class="kpi-box kpi-pass">
            <div class="num">{{ $acceptance['approved'] }}</div>
            <div class="label">Đã duyệt</div>
        </div>
        <div class="kpi-box kpi-fail">
            <div class="num">{{ $acceptance['rejected'] }}</div>
            <div class="label">Từ chối</div>
        </div>
        <div class="kpi-box">
            <div class="num">{{ $summary['pass_rate'] }}%</div>
            <div class="label">Tỷ lệ đạt</div>
        </div>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th>#</th>
                <th>Mã tủ</th>
                <th>Trạm BTS</th>
                <th>Kết quả KT</th>
                <th>Điểm</th>
                <th>Duyệt</th>
                <th>Ngày KT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($results as $i => $r)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $r['cabinet_code'] }}</td>
                <td>{{ $r['bts_site'] }}</td>
                <td>
                    @if($r['final_result'] === 'PASS')
                        <span class="badge badge-pass">ĐẠT</span>
                    @elseif($r['final_result'] === 'FAIL')
                        <span class="badge badge-fail">FAIL</span>
                    @else
                        —
                    @endif
                </td>
                <td>{{ $r['total_score'] ?? '—' }}</td>
                <td>
                    @if($r['review_status'] === 'approved')
                        <span class="badge badge-pass">Đã duyệt</span>
                    @elseif($r['review_status'] === 'rejected')
                        <span class="badge badge-fail">Từ chối</span>
                    @else
                        Chờ duyệt
                    @endif
                </td>
                <td>{{ $r['inspected_at'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="conclusion">
        <h2>Kết luận nghiệm thu</h2>
        <p>Tổng số tủ kiểm tra: <strong>{{ $summary['total'] }}</strong> &nbsp;|&nbsp;
           Đạt: <strong>{{ $summary['passed'] }}</strong> &nbsp;|&nbsp;
           Không đạt: <strong>{{ $summary['failed'] }}</strong> &nbsp;|&nbsp;
           Tỷ lệ đạt: <strong>{{ $summary['pass_rate'] }}%</strong>
        </p>
    </div>

    <div class="signatures">
        <div class="sig-col">
            <div class="title">Bên giao</div>
            <div class="subtitle">(Ký, ghi rõ họ tên)</div>
            <div class="name">&nbsp;</div>
        </div>
        <div class="sig-col">
            <div class="title">Bên nhận</div>
            <div class="subtitle">(Ký, ghi rõ họ tên)</div>
            <div class="name">&nbsp;</div>
        </div>
        <div class="sig-col">
            <div class="title">Giám sát</div>
            <div class="subtitle">(Ký, ghi rõ họ tên)</div>
            <div class="name">&nbsp;</div>
        </div>
    </div>

    <div class="footer">Xuất lúc: {{ $generated_at }}</div>
</body>
</html>
