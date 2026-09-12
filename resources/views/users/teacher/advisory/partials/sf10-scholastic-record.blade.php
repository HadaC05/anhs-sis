@php
    $periodCount = count($periods);
    $periodGroupLabel = \App\Models\GradingTerm::periodGroupLabel($periods);
    $periodRatingLabel = \App\Models\GradingTerm::periodRatingLabel($periods);
    $periodColumnWidth = $periodCount > 0 ? round(28 / $periodCount, 2) : 7;
@endphp
<table class="scholastic-meta">
    <tr>
        <td>School: <span class="line">{{ $record['school'] }}</span></td>
        <td>School ID: <span class="line">{{ $record['school_id'] }}</span></td>
        <td>District: <span class="line">{{ $record['district'] }}</span></td>
    </tr>
    <tr>
        <td>Division: <span class="line">{{ $record['division'] }}</span></td>
        <td colspan="2">Region: <span class="line">{{ $record['region'] }}</span></td>
    </tr>
    <tr>
        <td>Classified as Grade: <span class="line">{{ $record['grade'] }}</span></td>
        <td>Section: <span class="line">{{ $record['section'] }}</span></td>
        <td>School Year: <span class="line">{{ $record['school_year'] }}</span></td>
    </tr>
    <tr>
        <td colspan="2">Name of Adviser/Teacher: <span class="line">{{ $record['adviser'] }}</span></td>
        <td>Signature: <span class="line signature"></span></td>
    </tr>
</table>

<table class="grades">
    <thead>
        <tr>
            <th rowspan="2" class="learning-area">Learning Areas</th>
            <th colspan="{{ max($periodCount, 1) }}">{{ $periodRatingLabel }}</th>
            <th rowspan="2" class="final-col">Final<br>Rating</th>
            <th rowspan="2" class="remarks-col">Remarks</th>
        </tr>
        <tr>
            @foreach($periods as $period)
                <th class="period-col">{{ \App\Models\GradingTerm::periodColumnLabel($period['label']) }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($record['subjects'] as $row)
            <tr class="{{ $row['child'] ? 'child-row' : '' }}">
                <td class="{{ $row['child'] ? 'child-label' : 'bold' }}">{{ $row['label'] }}</td>
                @foreach($periods as $period)
                    <td class="center">{{ $row['quarters'][$period['key']] ?? '' }}</td>
                @endforeach
                <td class="center bold">{{ $row['final'] ?? '' }}</td>
                <td class="center">{{ $row['remarks'] }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="{{ 1 + max($periodCount, 1) }}" class="center bold">General Average</td>
            <td class="center bold">{{ $record['general_average'] ?? '' }}</td>
            <td class="center">{{ $record['general_remarks'] }}</td>
        </tr>
    </tbody>
</table>

<table class="remedial">
    <tr>
        <td colspan="{{ 4 + max($periodCount, 1) }}" class="small">
            Remedial Classes Conducted from <span class="line short"></span> (mm/dd/yyyy) to <span class="line short"></span> (mm/dd/yyyy)
        </td>
    </tr>
    <tr>
        <th>Learning Areas</th>
        <th>Final Rating</th>
        <th>Remedial Class Mark</th>
        <th colspan="{{ max($periodCount, 1) }}">Recomputed Final Grade</th>
        <th>Remarks</th>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td></td>
        <td></td>
        <td colspan="{{ max($periodCount, 1) }}"></td>
        <td></td>
    </tr>
</table>
