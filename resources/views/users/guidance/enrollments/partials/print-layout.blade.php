<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Enrollment Form')</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.25;
            background: #d1d5db;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            background: #fff;
            border-bottom: 1px solid #d1d5db;
        }

        .toolbar .hint {
            margin-right: auto;
            color: #4b5563;
            font-size: 12px;
            font-weight: 600;
        }

        .toolbar button,
        .toolbar a {
            display: inline-flex;
            align-items: center;
            border: 0;
            border-radius: 6px;
            background: #296374;
            color: #fff;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            padding: 8px 14px;
            text-decoration: none;
            text-transform: uppercase;
        }

        .toolbar a.secondary {
            background: #6b7280;
        }

        .sheet {
            display: flex;
            flex-direction: column;
            width: 210mm;
            min-height: 297mm;
            margin: 16px auto;
            padding: 8mm;
            background: #fff;
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.18);
            page-break-after: always;
        }

        .sheet:last-child {
            page-break-after: auto;
        }

        .sheet-body {
            flex: 1;
        }

        .page-foot {
            margin-top: auto;
            padding-top: 4mm;
            font-size: 8px;
            font-weight: 700;
            text-align: right;
            text-transform: uppercase;
        }

        .beef-header {
            display: grid;
            grid-template-columns: 18mm 1fr 24mm;
            align-items: center;
            gap: 4mm;
            margin-bottom: 3mm;
        }

        .beef-logo {
            width: 16mm;
            height: 16mm;
            object-fit: contain;
        }

        .beef-title {
            text-align: center;
        }

        .beef-title h1 {
            margin: 0;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.2px;
            text-transform: uppercase;
        }

        .beef-title .not-for-sale {
            margin: 1px 0 0;
            font-size: 8px;
            font-weight: 700;
        }

        .beef-title .school {
            margin: 2px 0 0;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .annex {
            border: 1px solid #111;
            padding: 3px 2px;
            text-align: center;
            font-size: 9px;
            font-weight: 800;
            line-height: 1.2;
        }

        .beef {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .beef + .beef {
            margin-top: -1px;
        }

        .beef th,
        .beef td {
            border: 1px solid #111;
            padding: 4px 5px;
            vertical-align: top;
        }

        .beef td.tall {
            height: 11mm;
        }

        .section-bar {
            background: #d4d4d4;
            font-size: 8.5px;
            font-weight: 800;
            letter-spacing: 0.3px;
            text-align: center;
            text-transform: uppercase;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        .instructions {
            font-size: 8px;
            font-weight: 700;
            padding: 3px 5px;
        }

        .field-label {
            display: block;
            margin-bottom: 1px;
            color: #111;
            font-size: 7.5px;
            font-weight: 700;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .field-value {
            min-height: 16px;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.2;
            text-transform: uppercase;
            word-break: break-word;
        }

        .name-col {
            width: 32%;
        }

        .inline-field {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 4px 8px;
            margin: 0 0 3px;
            font-size: 9px;
            font-weight: 700;
        }

        .inline-field:last-child {
            margin-bottom: 0;
        }

        .boxes {
            display: inline-flex;
            vertical-align: middle;
        }

        .boxes span {
            display: inline-flex;
            width: 3.2mm;
            height: 4.2mm;
            align-items: center;
            justify-content: center;
            margin-right: -1px;
            border: 1px solid #111;
            font-size: 9px;
            font-weight: 700;
            line-height: 1;
            text-transform: uppercase;
        }

        .checkbox-panel {
            padding: 3px 5px;
        }

        .checkbox-panel .panel-title {
            margin-bottom: 3px;
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .check-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 4px 8px;
            margin-bottom: 2px;
            font-size: 8.5px;
            font-weight: 700;
        }

        .check {
            display: inline-flex;
            width: 9px;
            height: 9px;
            align-items: center;
            justify-content: center;
            border: 1px solid #111;
            font-size: 8px;
            font-weight: 800;
            line-height: 1;
        }

        .certify {
            font-size: 8px;
            line-height: 1.35;
            text-align: justify;
        }

        .sign-line {
            margin-top: 8px;
            border-bottom: 1px solid #111;
            min-height: 16px;
            text-align: center;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .sign-caption {
            margin-top: 2px;
            font-size: 7.5px;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
        }

        .muted {
            font-weight: 600;
            text-transform: none;
        }

        .dlm-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 6px 10px;
            padding: 6px 4px;
        }

        .sign-block {
            height: 38mm;
        }

        .page-title {
            margin: 0 0 4mm;
            text-align: center;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none !important;
            }

            .sheet {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        @yield('toolbar')
    </div>

    @yield('content')
</body>
</html>
