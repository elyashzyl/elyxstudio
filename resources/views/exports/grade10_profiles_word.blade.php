<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:w="urn:schemas-microsoft-com:office:word"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Grade 10 Profiles</title>
    <!--[if gte mso 9]>
    <xml>
        <w:WordDocument>
            <w:View>Print</w:View>
            <w:Zoom>100</w:Zoom>
            <w:DoNotOptimizeForBrowser/>
        </w:WordDocument>
    </xml>
    <![endif]-->
    <style>
        @page WordSection1 {
            size: 841.9pt 595.3pt;
            mso-page-orientation: landscape;
            margin: 28.35pt 28.35pt 28.35pt 28.35pt;
        }
        div.WordSection1 {
            page: WordSection1;
        }
        body {
            font-family: Calibri, Arial, sans-serif;
            font-size: 9.5pt;
            color: #111827;
        }
        h1 {
            font-size: 16pt;
            color: #1e3a8a;
            margin: 0 0 4pt 0;
            text-align: center;
        }
        p.subtitle {
            font-size: 9pt;
            color: #4b5563;
            text-align: center;
            margin: 0 0 12pt 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        th, td {
            border: 1pt solid #4b5563;
            padding: 4pt 5pt;
            vertical-align: top;
            font-size: 8.5pt;
        }
        th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .center {
            text-align: center;
        }
        .bold {
            font-weight: bold;
        }
        .italic {
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="WordSection1">
        <h1>GRADE 10 PROFILES &amp; YEARBOOK RECORDS</h1>
        <p class="subtitle">Generated: {{ $generatedAt ?? now()->format('F j, Y g:i A') }} &bull; Total Submissions: {{ count($profiles) }}</p>

        <table>
            <thead>
                <tr>
                    <th style="width: 25pt;">#</th>
                    <th style="width: 50pt;">PHOTO</th>
                    <th style="width: 105pt;">FULL NAME<br><span style="font-size: 7pt; font-weight: normal;">(Last Name, First Name, Middle Name)</span></th>
                    <th style="width: 75pt;">CHINESE NAME<br><span style="font-size: 7pt; font-weight: normal;">(Chinese Class Students)</span></th>
                    <th style="width: 70pt;">BIRTHDAY<br><span style="font-size: 7pt; font-weight: normal;">(Month, Day, Year)</span></th>
                    <th style="width: 75pt;">HOMETOWN</th>
                    <th style="width: 85pt;">AMBITION</th>
                    <th style="width: 105pt;">MOTTO</th>
                    <th style="width: 140pt;">MESSAGE<br><span style="font-size: 7pt; font-weight: normal;">(3-5 sentences only)</span></th>
                    <th style="width: 80pt;">SOCIAL MEDIA ACCOUNT<br><span style="font-size: 7pt; font-weight: normal;">(Optional)</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($profiles as $index => $profile)
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td class="center">
                            @if ($profile->getPhotoBase64())
                                <img src="{{ $profile->getPhotoBase64() }}" width="45" height="45" style="border-radius: 4px;" alt="Photo" />
                            @else
                                —
                            @endif
                        </td>
                        <td class="bold">{{ $profile->full_name }}</td>
                        <td class="center">{{ $profile->chinese_name ?: '—' }}</td>
                        <td>{{ $profile->birthday }}</td>
                        <td>{{ $profile->hometown }}</td>
                        <td>{{ $profile->ambition }}</td>
                        <td class="italic">{{ $profile->motto }}</td>
                        <td>{{ $profile->message }}</td>
                        <td>{{ $profile->social_media ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="center">No Grade 10 profiles submitted yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
