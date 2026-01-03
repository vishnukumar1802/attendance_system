<?php
// employee/holidays.php
require_once '../config/db.php';
require_once '../includes/emp_header.php';

// Access Check
if (!isset($_SESSION['employee_logged_in']) || $_SESSION['employee_logged_in'] !== true) {
    header("Location: ../employee/login.php");
    exit;
}
?>

<style>
    /* SAAS Calendar Styles (Employee View) */
    .calendar-year-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .month-card {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
        overflow: hidden;
        transition: transform 0.2s;
    }

    .month-header {
        background: #f8fafc;
        padding: 1rem;
        font-weight: 700;
        text-align: center;
        color: #333;
        border-bottom: 1px solid #eee;
    }

    .month-days-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        padding: 0.5rem;
    }

    .day-cell {
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        cursor: default;
        /* Read-only */
        border-radius: 50%;
        margin: 2px;
        position: relative;
    }

    .day-cell.active-item {
        cursor: pointer;
        /* Allowed to click to see details? Prompt says Read Only. I'll afford a tooltip. */
    }

    .day-cell.header {
        font-weight: 600;
        color: #94a3b8;
        background: transparent !important;
    }

    /* Color Indicators */
    .day-holiday {
        background-color: #fee2e2 !important;
        /* Red-100 */
        color: #dc2626 !important;
        /* Red-600 */
        font-weight: 700;
    }

    .day-event {
        background-color: #dbeafe !important;
        color: #2563eb !important;
        font-weight: 700;
    }

    .day-mixed {
        background: linear-gradient(135deg, #fee2e2 50%, #dbeafe 50%) !important;
        color: #333 !important;
    }

    .day-today {
        border: 2px solid #22c55e;
        /* Green */
    }

    /* Tooltip */
    .day-tooltip {
        display: none;
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: #334155;
        color: #fff;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        white-space: nowrap;
        z-index: 10;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .day-cell:hover .day-tooltip {
        display: block;
    }

    .legend-item {
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .legend-box {
        width: 12px;
        height: 12px;
        border-radius: 2px;
    }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Holiday & Event Calendar</h1>
    <div class="btn-toolbar mb-2 mb-md-0 gap-3 align-items-center">
        <!-- Legend -->
        <div class="d-none d-md-flex gap-3 me-3">
            <span class="legend-item"><span class="legend-box" style="background:#fee2e2;"></span> Holiday</span>
            <span class="legend-item"><span class="legend-box" style="background:#dbeafe;"></span> Event</span>
        </div>

        <select id="yearSelect" class="form-select form-select-sm" style="width: auto;">
            <!-- JS will populate -->
        </select>
        <button class="btn btn-sm btn-saas-outline" onclick="loadYearData()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>
</div>

<div class="container-fluid px-0">
    <div id="calendar-loader" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Loading Calendar Data...</p>
    </div>

    <!-- Calendar Grid -->
    <div id="calendar-container" class="calendar-year-grid" style="display:none;">
        <!-- Months will be injected here -->
    </div>
</div>

<!-- Simple Info Modal for Details -->
<div class="modal fade" id="infoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center pt-0 pb-4">
                <div id="modal-icon" class="mb-3 fs-1"></div>
                <h5 class="fw-bold mb-1" id="modal-title"></h5>
                <p class="text-muted small text-uppercase fw-bold mb-3" id="modal-date"></p>
                <div id="modal-desc" class="text-muted small"></div>

                <span id="modal-badge" class="badge mt-3"></span>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const API_URL = '../ajax/calendar_api.php';
    let currentYear = new Date().getFullYear();
    let calendarData = [];
    const infoModal = new bootstrap.Modal(document.getElementById('infoModal'));

    // Init
    document.addEventListener('DOMContentLoaded', () => {
        setupYearSelect();
        loadYearData();
    });

    function setupYearSelect() {
        const select = document.getElementById('yearSelect');
        const start = currentYear - 1;
        const end = currentYear + 1;
        for (let y = start; y <= end; y++) {
            let opt = document.createElement('option');
            opt.value = y;
            opt.text = y;
            if (y === currentYear) opt.selected = true;
            select.appendChild(opt);
        }
        select.addEventListener('change', (e) => {
            currentYear = parseInt(e.target.value);
            loadYearData();
        });
    }

    // Load Data
    function loadYearData() {
        document.getElementById('calendar-loader').style.display = 'block';
        document.getElementById('calendar-container').style.display = 'none';

        const formData = new FormData();
        formData.append('action', 'fetch_year');
        formData.append('year', currentYear);

        fetch(API_URL, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    calendarData = res.data;
                    renderCalendar();
                } else {
                    console.error(res.message);
                }
            })
            .finally(() => {
                document.getElementById('calendar-loader').style.display = 'none';
                document.getElementById('calendar-container').style.display = 'grid';
            });
    }

    // Render Logic
    function renderCalendar() {
        const container = document.getElementById('calendar-container');
        container.innerHTML = '';

        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

        for (let m = 0; m < 12; m++) {
            const monthDiv = document.createElement('div');
            monthDiv.className = 'month-card';

            // Header
            const header = document.createElement('div');
            header.className = 'month-header';
            header.innerText = monthNames[m];
            monthDiv.appendChild(header);

            // Grid
            const grid = document.createElement('div');
            grid.className = 'month-days-grid';

            // Day Names
            ['S', 'M', 'T', 'W', 'T', 'F', 'S'].forEach(d => {
                const dh = document.createElement('div');
                dh.className = 'day-cell header';
                dh.innerText = d;
                grid.appendChild(dh);
            });

            // Days Calculation
            const daysInMonth = new Date(currentYear, m + 1, 0).getDate();
            const firstDay = new Date(currentYear, m, 1).getDay();

            // Empty slots
            for (let i = 0; i < firstDay; i++) {
                grid.appendChild(document.createElement('div'));
            }

            // Days
            for (let d = 1; d <= daysInMonth; d++) {
                const dayDiv = document.createElement('div');
                dayDiv.className = 'day-cell';
                dayDiv.innerText = d;

                // Format YYYY-MM-DD
                const dateStr = `${currentYear}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;

                // Check Data
                const items = calendarData.filter(x => x.date === dateStr);

                if (items.length > 0) {
                    const hasHoliday = items.some(x => x.category === 'holiday');
                    const hasEvent = items.some(x => x.category === 'event');

                    if (hasHoliday && hasEvent) dayDiv.classList.add('day-mixed');
                    else if (hasHoliday) dayDiv.classList.add('day-holiday');
                    else if (hasEvent) dayDiv.classList.add('day-event');

                    dayDiv.classList.add('active-item');

                    // Tooltip
                    const tip = document.createElement('div');
                    tip.className = 'day-tooltip';
                    tip.innerText = items.map(x => x.title).join(', ');
                    dayDiv.appendChild(tip);

                    // Click for Detail Modal
                    dayDiv.onclick = () => showDetails(items[0]); // Show first item details
                }

                // Check Today
                const today = new Date();
                if (currentYear === today.getFullYear() && m === today.getMonth() && d === today.getDate()) {
                    dayDiv.classList.add('day-today');
                }

                grid.appendChild(dayDiv);
            }

            monthDiv.appendChild(grid);
            container.appendChild(monthDiv);
        }
    }

    function showDetails(item) {
        document.getElementById('modal-title').innerText = item.title;

        const dateObj = new Date(item.date);
        document.getElementById('modal-date').innerText = dateObj.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });

        const badge = document.getElementById('modal-badge');
        const icon = document.getElementById('modal-icon');
        const desc = document.getElementById('modal-desc');

        if (item.category === 'holiday') {
            badge.className = 'badge bg-danger-subtle text-danger';
            badge.innerText = item.type || 'Holiday';
            icon.innerHTML = '<i class="bi bi-calendar-event text-danger"></i>';
            desc.innerText = 'Office is closed.';
        } else {
            badge.className = 'badge bg-primary-subtle text-primary';
            badge.innerText = item.type || 'Event';
            icon.innerHTML = '<i class="bi bi-info-circle text-primary"></i>';
            desc.innerText = item.description || 'No description.';
        }

        infoModal.show();
    }
</script>

<?php require_once '../includes/emp_footer.php'; ?>