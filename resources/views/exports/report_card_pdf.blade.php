<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Student Report Card - {{ $branding['school_name'] }}</title>
    <style>
        @page {
            margin: 10mm 12mm 12mm 12mm;
            size: a4 portrait;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 8pt;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.35;
        }
        .page-break {
            page-break-after: always;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header .school-name {
            font-size: 14pt;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin: 0 0 2px 0;
        }
        .header .school-meta {
            font-size: 7.5pt;
            color: #475569;
            margin: 0;
        }
        .header .report-title {
            margin-top: 6px;
            display: inline-block;
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 8pt;
            font-weight: bold;
            padding: 2px 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .student-card {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            margin-bottom: 10px;
            padding: 6px 8px;
        }
        .student-table {
            width: 100%;
            border-collapse: collapse;
        }
        .student-table td {
            padding: 2px 4px;
            font-size: 7.5pt;
            vertical-align: top;
            border: none;
        }
        .label {
            color: #64748b;
            font-weight: normal;
            width: 18%;
        }
        .val {
            color: #0f172a;
            font-weight: bold;
            width: 32%;
        }
        .summary-box {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: separate;
            border-spacing: 6px 0;
        }
        .summary-cell {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 6px;
            text-align: center;
            width: 25%;
        }
        .summary-cell .num {
            font-size: 15pt;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0;
        }
        .summary-cell .lbl {
            font-size: 6.5pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-top: 2px;
        }
        .section-title {
            font-size: 9pt;
            font-weight: bold;
            color: #1e3a8a;
            margin: 8px 0 4px 0;
            padding-bottom: 2px;
            border-bottom: 1px solid #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 10px;
        }
        table.data-table th, table.data-table td {
            border-bottom: 0.5pt solid #cbd5e1;
            padding: 4px 5px;
            font-size: 7.5pt;
            text-align: left;
        }
        table.data-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-top: 0.5pt solid #1e3a8a;
            border-bottom: 1pt solid #1e3a8a;
        }
        .row-even td {
            background-color: #f8fafc;
        }
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-quiz { background-color: #dbeafe; color: #1e40af; }
        .badge-seatwork { background-color: #d1fae5; color: #065f46; }
        .badge-exam { background-color: #fef3c7; color: #92400e; }
        .badge-practical { background-color: #ede9fe; color: #5b21b6; }
        .signatures-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .signatures-table td {
            width: 45%;
            text-align: center;
            vertical-align: bottom;
            border: none;
            padding: 0 10px;
        }
        .sig-line {
            border-top: 1px solid #334155;
            margin-top: 35px;
            padding-top: 4px;
        }
        .sig-name {
            font-size: 8pt;
            font-weight: bold;
            color: #0f172a;
        }
        .sig-role {
            font-size: 7pt;
            color: #64748b;
        }
        .footer {
            margin-top: 10px;
            padding-top: 5px;
            border-top: 0.5pt solid #e2e8f0;
            text-align: center;
            font-size: 6.5pt;
            color: #94a3b8;
        }
    </style>
</head>
<body>

@foreach($reports as $index => $rep)
    @if(!$loop->first)
        <div class="page-break"></div>
    @endif

    <div class="header">
        <h1 class="school-name">{{ $branding['school_name'] }}</h1>
        <p class="school-meta">
            @if(!empty($branding['school_address']))
                {{ $branding['school_address'] }}
            @endif
            @if(!empty($branding['school_id']))
                &bull; School ID: {{ $branding['school_id'] }}
            @endif
            @if(!empty($branding['support_email']))
                &bull; Contact: {{ $branding['support_email'] }}
            @endif
        </p>
        <div class="report-title">Official Student Academic Performance Record</div>
    </div>

    <!-- Student Details -->
    <div class="student-card">
        <table class="student-table">
            <tr>
                <td class="label">Student Name:</td>
                <td class="val">{{ $rep['student']->name }}</td>
                <td class="label">Academic Advisor:</td>
                <td class="val">{{ $rep['teacher_name'] }}</td>
            </tr>
            <tr>
                <td class="label">Grade Level:</td>
                <td class="val">{{ $rep['student']->grade ?? 'N/A' }}</td>
                <td class="label">Section:</td>
                <td class="val">{{ $rep['section'] ?: 'Unassigned' }}</td>
            </tr>
            <tr>
                <td class="label">Email Address:</td>
                <td class="val">{{ $rep['student']->email }}</td>
                <td class="label">Date Generated:</td>
                <td class="val">{{ $branding['generated_at'] }}</td>
            </tr>
        </table>
    </div>

    <!-- Performance Summary Cards -->
    <table class="summary-box">
        <tr>
            <td class="summary-cell" style="border-top: 3px solid {{ $rep['grade_rating']['color'] }};">
                <div class="num" style="color: {{ $rep['grade_rating']['color'] }};">{{ $rep['overall_percentage'] }}%</div>
                <div class="lbl">Overall Average</div>
            </td>
            <td class="summary-cell" style="border-top: 3px solid {{ $rep['grade_rating']['color'] }};">
                <div class="num" style="color: {{ $rep['grade_rating']['color'] }};">{{ $rep['grade_rating']['letter'] }}</div>
                <div class="lbl">{{ $rep['grade_rating']['rating'] }}</div>
            </td>
            <td class="summary-cell" style="border-top: 3px solid #1e3a8a;">
                <div class="num">{{ $rep['total_score'] }} / {{ $rep['total_max'] }}</div>
                <div class="lbl">Total Points Earned</div>
            </td>
            <td class="summary-cell" style="border-top: 3px solid #10b981;">
                <div class="num">{{ count($rep['activities']) }}</div>
                <div class="lbl">Completed Activities</div>
            </td>
        </tr>
    </table>

    <!-- Category Performance Breakdown -->
    <div class="section-title">Assessment Domain Breakdown</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30%;">Assessment Category</th>
                <th class="text-center" style="width: 20%;">Activities Completed</th>
                <th class="text-center" style="width: 25%;">Points (Earned / Total)</th>
                <th class="text-center" style="width: 25%;">Category Average</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rep['categories'] as $cat)
                <tr class="{{ $loop->even ? 'row-even' : '' }}">
                    <td><strong>{{ $cat['type'] }}</strong></td>
                    <td class="text-center">{{ $cat['count'] }}</td>
                    <td class="text-center">{{ $cat['score'] }} / {{ $cat['total'] }}</td>
                    <td class="text-center"><strong>{{ $cat['percentage'] }}%</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Activity Records -->
    <div class="section-title">Detailed Assessment Records</div>
    @if(empty($rep['activities']))
        <p style="text-align: center; color: #64748b; font-style: italic; margin: 15px 0;">No graded assessment records found for this student.</p>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 14%;">Type</th>
                    <th style="width: 38%;">Activity Title</th>
                    <th class="text-center" style="width: 18%;">Completion Date</th>
                    <th class="text-center" style="width: 15%;">Score</th>
                    <th class="text-center" style="width: 15%;">Percentage</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rep['activities'] as $act)
                    <tr class="{{ $loop->even ? 'row-even' : '' }}">
                        <td>
                            @php
                                $badgeClass = match(strtolower($act['type'])) {
                                    'quiz' => 'badge-quiz',
                                    'seatwork' => 'badge-seatwork',
                                    'exam' => 'badge-exam',
                                    'practical' => 'badge-practical',
                                    default => 'badge-quiz',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $act['type'] }}</span>
                        </td>
                        <td>{{ $act['title'] }}</td>
                        <td class="text-center">{{ $act['date'] }}</td>
                        <td class="text-center">{{ $act['score'] }} / {{ $act['total'] }}</td>
                        <td class="text-center"><strong>{{ $act['percentage'] }}%</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Certification & Signatures -->
    <table class="signatures-table">
        <tr>
            <td>
                <div class="sig-line">
                    <div class="sig-name">{{ $rep['teacher_name'] }}</div>
                    <div class="sig-role">Subject Teacher / Class Advisor</div>
                </div>
            </td>
            <td style="width: 10%;"></td>
            <td>
                <div class="sig-line">
                    <div class="sig-name">School Principal / Registrar</div>
                    <div class="sig-role">{{ $branding['school_name'] }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Generated on {{ $branding['generated_at'] }} via {{ $branding['app_name'] }} Learning Management System &bull; Official Educational Document
    </div>
@endforeach

</body>
</html>
