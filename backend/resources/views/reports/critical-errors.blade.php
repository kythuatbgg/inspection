<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; line-height: 1.4; }
        .header { text-align: center; border-bottom: 2px solid #dc2626; padding-bottom: 10px; margin-bottom: 14px; }
        .header h1 { font-size: 15px; font-weight: bold; text-transform: uppercase; color: #dc2626; }
        .header p { font-size: 9px; color: #64748b; }
        .summary { background: #fef2f2; border: 1px solid #fecaca; padding: 10px; margin-bottom: 14px; text-align: center; }
        .summary .num { font-size: 22px; font-weight: bold; color: #dc2626; }
        .summary .label { font-size: 9px; color: #64748b; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 9px; }
        table.data th { background: #fef2f2; font-weight: bold; text-align: left; padding: 5px 6px; border: 1px solid #fecaca; color: #991b1b; }
        table.data td { padding: 4px 6px; border: 1px solid #e2e8f0; }
        table.data tr:nth-child(even) { background: #f8fafc; }
        .category-section { margin-top: 16px; }
        .category-title { font-weight: bold; font-size: 11px; padding: 6px 8px; background: #fee2e2; border-left: 3px solid #dc2626; margin-bottom: 6px; }
        .footer { margin-top: 16px; text-align: right; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Báo cáo lỗi Critical</h1>
        <p>CNN Telecom — Hệ thống FBB Inspection</p>
    </div>

    <div class="summary">
        <div class="num">{{ $total_count }}</div>
        <div class="label">TỔNG SỐ LỖI CRITICAL PHÁT HIỆN</div>
    </div>

    @foreach($by_category as $category => $errors)
        <div class="category-section">
            <div class="category-title">{{ $category }} ({{ $errors->count() }} lỗi)</div>
            <table class="data">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Mã tủ</th>
                        <th>Trạm BTS</th>
                        <th>Đợt</th>
                        <th>Nội dung lỗi</th>
                        <th>Inspector</th>
                        <th>Ngày KT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($errors as $i => $err)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $err['cabinet_code'] }}</td>
                        <td>{{ $err['bts_site'] }}</td>
                        <td>{{ $err['batch_name'] }}</td>
                        <td>{{ $err['item_content'] }}</td>
                        <td>{{ $err['inspector'] }}</td>
                        <td>{{ $err['inspected_at'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    @if($total_count === 0)
        <div style="text-align: center; padding: 30px; color: #64748b;">Không tìm thấy lỗi Critical nào trong khoảng thời gian đã chọn.</div>
    @endif

    <div class="footer">Xuất lúc: {{ $generated_at }}</div>
</body>
</html>
