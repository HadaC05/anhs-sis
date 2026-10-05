export function numericGrade(value) {
    if (value === null || value === undefined || String(value).trim() === '') return null;
    const grade = Number(value);
    return Number.isFinite(grade) && grade >= 0 && grade <= 100 ? grade : null;
}

export function descriptorFor(value, bands) {
    const grade = numericGrade(value);
    return grade === null ? null : bands.find(band => grade >= band.min)?.description ?? null;
}

export function calculateStatistics(students, periods, bands) {
    const summarize = (values, key, label) => {
        const recorded = values.map(numericGrade).filter(value => value !== null);
        const passed = recorded.filter(value => value >= 75).length;
        const counts = Object.fromEntries(bands.map(band => [band.description, 0]));
        recorded.forEach(value => { const descriptor = descriptorFor(value, bands); if (descriptor) counts[descriptor]++; });
        return { key, label, passed, failed: recorded.length - passed, graded: recorded.length,
            missing: values.length - recorded.length, rate: recorded.length ? Math.round(passed / recorded.length * 10000) / 100 : null, counts };
    };
    const results = periods.map(period => summarize(students.map(row => row[period.key]), period.key, period.label));
    const averages = students.map(row => {
        const values = periods.map(period => numericGrade(row[period.key])).filter(value => value !== null);
        return values.length ? Math.round(values.reduce((sum, value) => sum + value, 0) / values.length * 100) / 100 : null;
    });
    results.push(summarize(averages, 'overall', 'Overall'));
    return { results, total: students.length, complete: periods.length ? students.filter(row => periods.every(period => numericGrade(row[period.key]) !== null)).length : 0 };
}

export function initializeGradeStatistics(root = document) {
    const configElement = root.getElementById('grade-statistics-config');
    if (!configElement) return;
    const config = JSON.parse(configElement.textContent);
    const periods = config.periods;
    const bands = config.bands;
    const passingCanvas = root.getElementById('passing-rate-chart');
    const descriptorCanvas = root.getElementById('descriptor-chart');
    const selector = root.getElementById('descriptor-period');
    let stats;
    const colors = ['#2563eb', '#d97706', '#059669', '#9333ea', '#dc2626', '#0891b2', '#db2777'];
    const rateText = value => value === null ? 'No grades' : `${value.toFixed(2)}%`;
    function cell(row, value, align = 'center') {
        const td = root.createElement('td'); td.className = `border-b border-gray-100 p-2 text-${align}`;
        td.textContent = value; row.appendChild(td);
    }
    function wrap(ctx, text, x, y, width, lineHeight, draw = true) {
        let line = '';
        for (const word of String(text).split(/\s+/)) {
            if (line && ctx.measureText(`${line} ${word}`).width > width) { if (draw) ctx.fillText(line, x, y); y += lineHeight; line = word; }
            else line = line ? `${line} ${word}` : word;
        }
        if (line && draw) ctx.fillText(line, x, y);
        return y + lineHeight;
    }
    function frame(canvas, title, rowCount) {
        const width = 1200;
        const measure = canvas.getContext('2d');
        const details = [
            {label: 'GRADE LEVEL', value: config.grade || 'Not set', x: 32, width: 180},
            {label: 'SECTION', value: config.section || 'Not set', x: 232, width: 420},
            {label: 'SCHOOL YEAR', value: config.year || 'Not set', x: 672, width: 260},
            {label: 'TOTAL STUDENTS', value: String(stats.total), x: 952, width: 216},
        ];
        measure.font = 'bold 20px Arial';
        const subjectEnd = wrap(measure, config.subject, 32, 104, 1136, 26, false);
        measure.font = 'bold 18px Arial';
        const detailsEnd = Math.max(...details.map(item => wrap(measure, item.value, item.x, subjectEnd + 36, item.width, 24, false)));
        const top = detailsEnd + 48, height = top + rowCount * 68 + 76;
        canvas.width = width * 2; canvas.height = height * 2;
        const ctx = canvas.getContext('2d'); ctx.scale(2, 2);
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, width, height);
        ctx.fillStyle = '#172b35'; ctx.font = 'bold 25px Arial'; ctx.fillText(title, 32, 42);
        ctx.fillStyle = '#f1f5f9'; ctx.fillRect(16, 58, 1168, detailsEnd - 42);
        ctx.fillStyle = '#64748b'; ctx.font = 'bold 12px Arial'; ctx.fillText('SUBJECT', 32, 78);
        ctx.fillStyle = '#172b35'; ctx.font = 'bold 20px Arial';
        wrap(ctx, config.subject, 32, 104, 1136, 26);
        details.forEach(item => {
            ctx.fillStyle = '#64748b'; ctx.font = 'bold 12px Arial'; ctx.fillText(item.label, item.x, subjectEnd + 8);
            ctx.fillStyle = '#172b35'; ctx.font = 'bold 18px Arial';
            wrap(ctx, item.value, item.x, subjectEnd + 36, item.width, 24);
        });
        ctx.fillStyle = '#64748b'; ctx.font = '14px Arial';
        ctx.fillText('Current grade sheet, including unsaved inputs. Overall = average of available terms (provisional).', 32, height - 40);
        ctx.fillText(`Missing grades excluded from passing rates. ${stats.complete} of ${stats.total} learners have every term recorded.`, 32, height - 18);
        return {ctx, top};
    }
    function passingChart() {
        const {ctx, top} = frame(passingCanvas, 'Passing rate per term and overall', stats.results.length);
        const labels = stats.results.map(result => result.rate === null ? 'No grades' : `${rateText(result.rate)} (${result.passed})`);
        ctx.font = 'bold 16px Arial';
        const labelWidth = Math.max(...labels.map(label => ctx.measureText(label).width));
        const left = 260, width = 1200 - 32 - left - 16 - labelWidth;
        ctx.font = '13px Arial';
        [0,25,50,75,100].forEach(value => { ctx.fillStyle = '#64748b'; ctx.fillText(`${value}%`, left + width * value / 100 - 10, top - 16); });
        stats.results.forEach((result, index) => {
            const y = top + index * 68;
            ctx.font = 'bold 17px Arial'; ctx.fillStyle = '#172b35'; ctx.fillText(result.label, 32, y + 22);
            ctx.fillStyle = '#f1f5f9'; ctx.fillRect(left, y, width, 30);
            ctx.fillStyle = colors[index % colors.length]; ctx.fillRect(left, y, width * (result.rate ?? 0) / 100, 30);
            ctx.font = 'bold 16px Arial'; ctx.fillText(labels[index], left + width + 16, y + 22);
        });
    }
    function descriptorsChart() {
        const result = stats.results.find(item => item.key === selector.value) ?? stats.results.at(-1);
        const rows = bands.map((band, index) => ({label: band.description, scale: band.scale, count: result.counts[band.description], color: colors[index]}));
        rows.push({label: 'No grade', scale: '', count: result.missing, color: '#94a3b8'});
        const {ctx, top} = frame(descriptorCanvas, `Students by descriptor - ${result.label}`, rows.length);
        const max = Math.max(1, ...rows.map(row => row.count));
        rows.forEach((row, index) => {
            const y = top + index * 68;
            ctx.fillStyle = '#172b35'; ctx.font = 'bold 16px Arial'; ctx.fillText(row.label, 32, y + 19);
            ctx.fillStyle = '#64748b'; ctx.font = '13px Arial'; ctx.fillText(row.scale, 32, y + 39);
            ctx.fillStyle = '#f1f5f9'; ctx.fillRect(290, y, 720, 32);
            ctx.fillStyle = row.color; ctx.fillRect(290, y, row.count / max * 720, 32);
            ctx.font = 'bold 17px Arial'; ctx.fillText(String(row.count), 1030, y + 23);
        });
        const tbody = root.getElementById('descriptor-rows'); tbody.replaceChildren();
        rows.forEach(item => { const tr = root.createElement('tr'); cell(tr, item.label, 'left'); cell(tr, item.scale || '—', 'left'); cell(tr, item.count, 'right'); tbody.appendChild(tr); });
    }
    function refresh() {
        const students = [...root.querySelectorAll('[data-summary-average]')].map(cell => {
            const row = cell.parentElement;
            const values = Object.fromEntries([...row.querySelectorAll('[data-summary-enrollment]')].map(td => [td.dataset.periodColumn, numericGrade(td.textContent)]));
            row.querySelector('[data-summary-descriptor]').textContent = descriptorFor(cell.textContent, bands) ?? '—';
            return values;
        });
        stats = calculateStatistics(students, periods, bands);
        const tbody = root.getElementById('passing-rate-rows'); tbody.replaceChildren();
        [['Passed', 'passed'], ['Failed', 'failed'], ['With grades', 'graded'], ['No grade', 'missing'], ['Passing rate', 'rate']].forEach(([label, key]) => {
            const tr = root.createElement('tr');
            const heading = root.createElement('th');
            heading.scope = 'row'; heading.className = 'border-b border-gray-100 p-2 text-left font-medium';
            heading.textContent = label; tr.appendChild(heading);
            stats.results.forEach(item => cell(tr, key === 'rate' ? rateText(item.rate) : item[key]));
            tbody.appendChild(tr);
        });
        root.getElementById('statistics-completeness').textContent = `${stats.complete} of ${stats.total} learners have grades for all ${periods.length} configured terms.`;
        passingChart(); descriptorsChart();
    }
    selector.addEventListener('change', descriptorsChart);
    root.addEventListener('grade-sheet-updated', refresh);
    root.querySelectorAll('[data-download-chart]').forEach(button => button.addEventListener('click', () => {
        const error = root.getElementById('statistics-export-error'); error.hidden = true;
        const type = button.dataset.downloadChart;
        const canvas = type === 'passing' ? passingCanvas : descriptorCanvas;
        try {
            canvas.toBlob(blob => {
                if (!blob) { error.textContent = 'The graph could not be downloaded. Please try again.'; error.hidden = false; return; }
                const url = URL.createObjectURL(blob), link = root.createElement('a');
                link.href = url; link.download = `${config.filename}-${type}${type === 'descriptors' ? '-'+selector.value : ''}.png`;
                root.body.appendChild(link); link.click(); link.remove(); setTimeout(() => URL.revokeObjectURL(url), 1000);
            }, 'image/png');
        } catch { error.textContent = 'The graph could not be downloaded. Please try again.'; error.hidden = false; }
    }));
    refresh();
}

if (typeof document !== 'undefined') initializeGradeStatistics();
