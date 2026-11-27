<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Grade 10 Profiles</title>
    <style>
        body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
        table { border-collapse: collapse; width: 100%; }
        th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            border: 1px solid #000000;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }
        td {
            border: 1px solid #9ca3af;
            padding: 6px 8px;
            vertical-align: top;
            mso-number-format: "\@";
        }
        .text-center { text-align: center; }
        .num-col { mso-number-format: "0"; text-align: center; }
        .title-row {
            font-size: 16pt;
            font-weight: bold;
            color: #1e3a8a;
            text-align: center;
        }
        .meta-row {
            font-size: 10pt;
            color: #4b5563;
            text-align: center;
        }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="10" class="title-row">GRADE 10 PROFILES &amp; YEARBOOK RECORDS</td>
        </tr>
        <tr>
            <td colspan="10" class="meta-row">Exported: {{ $generatedAt ?? now()->format('F j, Y g:i A') }} &bull; Total Submissions: {{ count($profiles) }}</td>
        </tr>
        <tr><td colspan="10"></td></tr>
        <thead>
            <tr>
                <th style="width: 50px;">#</th>
                <th style="width: 100px;">PHOTO URL</th>
                <th style="width: 250px;">FULL NAME (Last Name, First Name, Middle Name)</th>
                <th style="width: 180px;">CHINESE NAME (Chinese Class Students)</th>
                <th style="width: 160px;">BIRTHDAY (Month, Day, Year)</th>
                <th style="width: 180px;">HOMETOWN</th>
                <th style="width: 200px;">AMBITION</th>
                <th style="width: 240px;">MOTTO</th>
                <th style="width: 350px;">MESSAGE (3-5 sentences only)</th>
                <th style="width: 200px;">SOCIAL MEDIA ACCOUNT (Optional)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($profiles as $index => $profile)
                <tr>
                    <td class="num-col">{{ $index + 1 }}</td>
                    <td>{{ $profile->getPhotoUrl() ?: '—' }}</td>
                    <td>{{ $profile->full_name }}</td>
                    <td class="text-center">{{ $profile->chinese_name }}</td>
                    <td>{{ $profile->birthday }}</td>
                    <td>{{ $profile->hometown }}</td>
                    <td>{{ $profile->ambition }}</td>
                    <td>{{ $profile->motto }}</td>
                    <td>{{ $profile->message }}</td>
                    <td>{{ $profile->social_media }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No Grade 10 profiles submitted yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
