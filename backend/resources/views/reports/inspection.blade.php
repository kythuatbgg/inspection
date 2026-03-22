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
        .note-text { font-size: 9px; color: #dc2626; font-style: italic; margin-top: 2px; }
        .error-photo { margin-top: 4px; }
        .error-photo img { max-width: 200px; max-height: 150px; border: 1px solid #e2e8f0; border-radius: 3px; }
        .overall-section { margin-bottom: 16px; }
        .overall-section h3 { font-size: 12px; font-weight: bold; margin-bottom: 8px; color: #475569; }
        .overall-photos { display: block; text-align: center; }
        .overall-photos img { max-width: 160px; max-height: 120px; margin: 4px; border: 1px solid #e2e8f0; border-radius: 3px; }
        .photo-grid { margin-top: 8px; page-break-inside: avoid; }
        .photo-pair { display: block; margin-bottom: 8px; text-align: center; }
        .photo-pair img { width: 48%; max-height: 140px; margin: 0 1%; border: 1px solid #e2e8f0; border-radius: 3px; }
    </style>
</head>
<body>
    @php
        $labels = match($lang ?? 'en') {
            'vn', 'vi' => [
                'title' => 'Biên bản kiểm tra tủ cáp GPON',
                'subtitle' => 'CNN Telecom — Hệ thống FBB Inspection',
                'cabinet_code' => 'Mã tủ',
                'bts_site' => 'Trạm BTS',
                'coordinates' => 'Tọa độ',
                'inspector' => 'Nhân viên kiểm tra',
                'checklist' => 'Checklist',
                'date' => 'Ngày kiểm tra',
                'overall_photos' => 'Ảnh tổng quan tủ',
                'content' => 'Nội dung',
                'max_score' => 'Điểm tối đa',
                'score' => 'Điểm đạt',
                'result' => 'Kết quả',
                'pass' => 'ĐẠT',
                'fail' => 'KHÔNG ĐẠT',
                'result_label' => 'Kết quả kiểm tra',
                'total_score' => 'Tổng điểm',
                'critical_errors' => 'Lỗi Critical',
                'inspector_sig' => 'Nhân viên kiểm tra',
                'supervisor_sig' => 'Giám sát',
                'sign' => '(Ký tên)',
                'generated' => 'Xuất lúc',
            ],
            'kh' => [
                'title' => 'របាយការណ៍ត្រួតពិនិត្យទូ GPON',
                'subtitle' => 'CNN Telecom — FBB Inspection System',
                'cabinet_code' => 'Cabinet Code',
                'bts_site' => 'BTS Site',
                'coordinates' => 'Coordinates',
                'inspector' => 'Inspector',
                'checklist' => 'Checklist',
                'date' => 'Inspection Date',
                'overall_photos' => 'Cabinet Overview Photos',
                'content' => 'Content',
                'max_score' => 'Max Score',
                'score' => 'Score',
                'result' => 'Result',
                'pass' => 'PASS',
                'fail' => 'FAIL',
                'result_label' => 'Inspection Result',
                'total_score' => 'Total Score',
                'critical_errors' => 'Critical Errors',
                'inspector_sig' => 'Inspector',
                'supervisor_sig' => 'Supervisor',
                'sign' => '(Signature)',
                'generated' => 'Generated at',
            ],
            default => [
                'title' => 'GPON Cabinet Inspection Report',
                'subtitle' => 'CNN Telecom — FBB Inspection System',
                'cabinet_code' => 'Cabinet Code',
                'bts_site' => 'BTS Site',
                'coordinates' => 'Coordinates',
                'inspector' => 'Inspector',
                'checklist' => 'Checklist',
                'date' => 'Inspection Date',
                'overall_photos' => 'Cabinet Overview Photos',
                'content' => 'Content',
                'max_score' => 'Max Score',
                'score' => 'Score',
                'result' => 'Result',
                'pass' => 'PASS',
                'fail' => 'FAIL',
                'result_label' => 'Inspection Result',
                'total_score' => 'Total Score',
                'critical_errors' => 'Critical Errors',
                'inspector_sig' => 'Inspector',
                'supervisor_sig' => 'Supervisor',
                'sign' => '(Signature)',
                'generated' => 'Generated at',
            ],
        };
    @endphp

    <div class="header">
        <h1>{{ $labels['title'] }}</h1>
        <p>{{ $labels['subtitle'] }}</p>
    </div>

    <div class="info-grid">
        <div class="info-row"><span class="info-label">{{ $labels['cabinet_code'] }}:</span><span class="info-value">{{ $cabinet->cabinet_code ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">{{ $labels['bts_site'] }}:</span><span class="info-value">{{ $cabinet->bts_site ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">{{ $labels['coordinates'] }}:</span><span class="info-value">{{ $inspection->lat }}, {{ $inspection->lng }}</span></div>
        <div class="info-row"><span class="info-label">{{ $labels['inspector'] }}:</span><span class="info-value">{{ $user->name ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">{{ $labels['checklist'] }}:</span><span class="info-value">{{ $checklist->name ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">{{ $labels['date'] }}:</span><span class="info-value">{{ $inspection->created_at?->format('d/m/Y H:i') }}</span></div>
    </div>

    {{-- Overall photos --}}
    @if(!empty($inspection->overall_photos) && count($inspection->overall_photos) > 0)
    <div class="overall-section">
        <h3>{{ $labels['overall_photos'] }}</h3>
        <div class="photo-grid">
            @foreach(array_chunk($inspection->overall_photos, 2) as $pair)
            <div class="photo-pair">
                @foreach($pair as $photoUrl)
                    @php
                        $absPath = public_path(str_replace(request()->getSchemeAndHttpHost(), '', $photoUrl));
                        $exists = file_exists($absPath);
                    @endphp
                    @if($exists)
                        <img src="{{ $absPath }}" />
                    @endif
                @endforeach
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @foreach($grouped as $category => $items)
        <div class="category-header">{{ $category }}</div>
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 40px">#</th>
                    <th>{{ $labels['content'] }}</th>
                    <th style="width: 70px">{{ $labels['max_score'] }}</th>
                    <th style="width: 70px">{{ $labels['score'] }}</th>
                    <th style="width: 70px">{{ $labels['result'] }}</th>
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
                            <span class="badge badge-pass">{{ $labels['pass'] }}</span>
                        @endif
                    </td>
                </tr>
                {{-- Error details: note + photo --}}
                @if($item['is_failed'] && (!empty($item['note']) || !empty($item['image_url'])))
                <tr>
                    <td></td>
                    <td colspan="4">
                        @if(!empty($item['note']))
                            <div class="note-text">📝 {{ $item['note'] }}</div>
                        @endif
                        @if(!empty($item['image_url']))
                            @php
                                $imgPath = public_path(str_replace(request()->getSchemeAndHttpHost(), '', $item['image_url']));
                                $imgExists = file_exists($imgPath);
                            @endphp
                            @if($imgExists)
                            <div class="error-photo">
                                <img src="{{ $imgPath }}" />
                            </div>
                            @endif
                        @endif
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="result-box {{ $inspection->final_result === 'PASS' ? 'result-pass' : 'result-fail' }}">
        <div class="label">{{ $labels['result_label'] }}</div>
        <div class="value">{{ $inspection->final_result === 'PASS' ? $labels['pass'] : $labels['fail'] }}</div>
        <div class="label">{{ $labels['total_score'] }}: {{ $inspection->total_score }} | {{ $labels['critical_errors'] }}: {{ $inspection->critical_errors_count }}</div>
    </div>

    <div class="signatures">
        <div class="sig-col">
            <div class="title">{{ $labels['inspector_sig'] }}</div>
            <div class="name">{{ $labels['sign'] }}</div>
        </div>
        <div class="sig-col">
            <div class="title">{{ $labels['supervisor_sig'] }}</div>
            <div class="name">{{ $labels['sign'] }}</div>
        </div>
    </div>

    <div class="footer">{{ $labels['generated'] }}: {{ $generated_at }}</div>
</body>
</html>
