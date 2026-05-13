<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Forms - {{ count($enrollments) }} student(s)</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; line-height: 1.3; color: #000; background: #fff; margin: 0; padding: 16px; }
        @media print { body { padding: 0; } .no-print { display: none !important; } .form-page { page-break-after: always; } .form-page:last-child { page-break-after: auto; } }
        .print-container { max-width: 900px; margin: 0 auto; width: 100%; }
        .header-row { display: grid; grid-template-columns: 60px 1fr 70px; align-items: center; gap: 8px; margin-bottom: 8px; }
        .seal { width: 50px; height: 50px; border: 2px solid #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 9px; }
        .form-title { font-size: 13px; font-weight: bold; text-align: center; margin: 0; text-transform: uppercase; }
        .form-sub { font-size: 9px; text-align: center; margin: 2px 0 0; }
        .header-annex { border: 1px solid #000; text-align: center; font-size: 9px; font-weight: bold; padding: 2px 4px; }
        .top-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 6px; }
        .inline-field { margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
        .label { font-weight: 600; }
        .boxes { display: inline-flex; gap: 0; }
        .boxes span { display: inline-block; width: 14px; height: 16px; border: 1px solid #000; text-align: center; font-size: 10px; line-height: 14px; }
        .dash { font-weight: bold; }
        .checkbox-box { border: 1px solid #000; padding: 6px; }
        .checkbox-title { font-weight: bold; font-size: 9px; margin-bottom: 4px; }
        .checkbox-row { font-size: 9px; display: flex; flex-wrap: wrap; gap: 6px; }
        .check { display: inline-block; width: 10px; height: 10px; border: 1px solid #000; text-align: center; line-height: 9px; font-size: 9px; margin: 0 4px; }
        .spacer { display: inline-block; width: 14px; }
        .instructions { border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 6px; font-size: 9px; margin-bottom: 6px; }
        .section-header { background: #e5e7eb; border: 1px solid #000; padding: 4px 6px; font-weight: bold; text-transform: uppercase; margin: 8px 0 4px; text-align: center; }
        .form-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; table-layout: fixed; }
        .form-table td { padding: 3px 4px; vertical-align: top; overflow: hidden; }
        .form-table .label { white-space: nowrap; }
        .line { border-bottom: 1px solid #000; min-width: 120px; height: 16px; display: block; max-width: 100%; }
        .btn { display: inline-block; padding: 8px 16px; background: #296374; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; margin-right: 8px; border: none; cursor: pointer; font-size: 14px; }
        .btn:hover { opacity: 0.9; }
        .form-page { margin-bottom: 24px; }
    </style>
</head>
<body>
    <div class="no-print actions" style="margin-bottom: 16px;">
        <button type="button" onclick="window.print()" class="btn">Print all</button>
        <a href="{{ route('guidance.enrollments.index') }}" class="btn" style="background: #6b7280;">Back to enrollment list</a>
    </div>

    <div class="print-container">
        @forelse($enrollments as $enrollment)
            <div class="form-page">
                @include('users.guidance.enrollments.partials.form-body', ['enrollment' => $enrollment])
            </div>
        @empty
            <p class="no-print">No enrollments selected. Go back and select at least one student.</p>
        @endforelse
    </div>
</body>
</html>
