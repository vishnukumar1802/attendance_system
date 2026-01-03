<?php
// admin/manage_holidays.<?php
require_once '../config/db.php';
require_once '../includes/admin_header.php';
// Admin Access Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>

<style>
    /* SAAS Calendar Styles */
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

    .month-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.04);
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
        cursor: pointer;
        border-radius: 50%;
        margin: 2px;
        position: relative;
        transition: background 0.2s;
    }

    .day-cell:hover {
        background: #f1f5f9;
    }

    .day-cell.header {
        font-weight: 600;
        color: #94a3b8;
        cursor: default;
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
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.75rem;
        white-space: nowrap;
        z-index: 10;
    }

    .day-cell:hover .day-tooltip {
        display: block;
    }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Calendar Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <select id="yearSelect" class="form-select form-select-sm" style="width: auto;">
            <!-- JS will populate -->
        </select>
        <button class="btn btn-sm btn-saas-primary" onclick="loadYearData()">
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

<!-- Modal: Add/Edit Item -->
<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalTitle">Manage Date</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-holiday" data-bs-toggle="pill"
                            data-bs-target="#pills-holiday" type="button">Holiday</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-event" data-bs-toggle="pill" data-bs-target="#pills-event"
                            type="button">Event</button>
                    </li>
                </ul>

                <div class="tab-content" id="pills-tabContent">
                    <!-- HOLIDAY FORM -->
                    <div class="tab-pane fade show active" id="pills-holiday">
                        <form id="holidayForm">
                            <input type="hidden" name="id" id="h_id">
                            <input type="hidden" name="date" id="h_date">
                            <input type="hidden" name="action" value="save_holiday">

                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">Selected Date</label>
                                <input type="text" class="form-control" id="display_date_h" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Holiday Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="h_name" class="form-control" required
                                    placeholder="e.g. New Year">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select name="type" id="h_type" class="form-select">
                                    <option value="Public Holiday">Public Holiday</option>
                                    <option value="Company Holiday">Company Holiday</option>
                                    <option value="Optional Holiday">Optional Holiday</option>
                                    <option value="Festival">Festival</option>
                                </select>
                            </div>
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" id="btnDeleteHoliday" class="btn btn-outline-danger btn-sm"
                                    style="display:none;">Delete</button>
                                <button type="submit" class="btn btn-danger text-white ms-auto">Save Holiday</button>
                            </div>
                        </form>
                    </div>

                    <!-- EVENT FORM -->
                    <div class="tab-pane fade" id="pills-event">
                        <form id="eventForm">
                            <input type="hidden" name="id" id="e_id">
                            <input type="hidden" name="date" id="e_date">
                            <input type="hidden" name="action" value="save_event">

                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">Selected Date</label>
                                <input type="text" class="form-control" id="display_date_e" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Event Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="e_title" class="form-control" required
                                    placeholder="e.g. Team Meeting">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Event Type</label>
                                <select name="type" id="e_type" class="form-select">
                                    <option value="Meeting">Meeting</option>
                                    <option value="Training">Training</option>
                                    <option value="Celebration">Celebration</option>
                                    <option value="Reminder" selected>Reminder</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description (Optional)</label>
                                <textarea name="description" id="e_desc" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" id="btnDeleteEvent" class="btn btn-outline-danger btn-sm"
                                    style="display:none;">Delete</button>
                                <button type="submit" class="btn btn-primary ms-auto">Save Event</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const API_URL = '../ajax/calendar_api.php';
    let currentYear = new Date().getFullYear();
    let calendarData = [];

    // Init
    document.addEventListener('DOMContentLoaded', () => {
        setupYearSelect();
        loadYearData();
    });

    function setupYearSelect() {
        const select = document.getElementById('yearSelect');
        const start = currentYear - 2;
        const end = currentYear + 2;
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
                    alert('Error fetching data: ' + res.message);
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

                    // Tooltip
                    const tip = document.createElement('div');
                    tip.className = 'day-tooltip';
                    tip.innerText = items.map(x => x.title).join(', ');
                    dayDiv.appendChild(tip);
                }

                // Check Today
                const today = new Date();
                if (currentYear === today.getFullYear() && m === today.getMonth() && d === today.getDate()) {
                    dayDiv.classList.add('day-today');
                }

                // Click Event
                dayDiv.onclick = () => openModal(dateStr, items);

                grid.appendChild(dayDiv);
            }

            monthDiv.appendChild(grid);
            container.appendChild(monthDiv);
        }
    }

    // Modal Logic
    const itemModal = new bootstrap.Modal(document.getElementById('itemModal'));

    function openModal(dateStr, items) {
        // Reset Forms
        document.getElementById('holidayForm').reset();
        document.getElementById('eventForm').reset();

        // Set Date Display
        const dateObj = new Date(dateStr);
        const niceDate = dateObj.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

        document.getElementById('h_date').value = dateStr;
        document.getElementById('e_date').value = dateStr;
        document.getElementById('display_date_h').value = niceDate;
        document.getElementById('display_date_e').value = niceDate;

        // Hide delete buttons default
        document.getElementById('btnDeleteHoliday').style.display = 'none';
        document.getElementById('btnDeleteEvent').style.display = 'none';

        // Pre-fill if exists
        const holiday = items.find(x => x.category === 'holiday');
        const event = items.find(x => x.category === 'event'); // Can handle multiple? For now take first.

        if (holiday) {
            document.getElementById('h_id').value = holiday.id;
            document.getElementById('h_name').value = holiday.title;
            document.getElementById('h_type').value = holiday.type;

            const btnDel = document.getElementById('btnDeleteHoliday');
            btnDel.style.display = 'inline-block';
            btnDel.onclick = () => deleteItem(holiday.id, 'holiday');

            // Switch to Holiday Tab logic could go here, but default is fine.
        }

        if (event) {
            document.getElementById('e_id').value = event.id;
            document.getElementById('e_title').value = event.title;
            document.getElementById('e_type').value = event.type;
            document.getElementById('e_desc').value = event.description || '';

            const btnDel = document.getElementById('btnDeleteEvent');
            btnDel.style.display = 'inline-block';
            btnDel.onclick = () => deleteItem(event.id, 'event');
        }

        itemModal.show();
    }

    // Form Submissions
    document.getElementById('holidayForm').onsubmit = (e) => submitForm(e, 'holidayForm');
    document.getElementById('eventForm').onsubmit = (e) => submitForm(e, 'eventForm');

    function submitForm(e, formId) {
        e.preventDefault();
        const form = document.getElementById(formId);
        const data = new FormData(form);

        fetch(API_URL, { method: 'POST', body: data })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    itemModal.hide();
                    loadYearData(); // Refresh UI
                } else {
                    alert(res.message);
                }
            });
    }

    function deleteItem(id, category) {
        if (!confirm('Are you sure you want to delete this ' + category + '?')) return;

        const data = new FormData();
        data.append('action', 'delete_item');
        data.append('id', id);
        data.append('category', category);

        fetch(API_URL, { method: 'POST', body: data })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    itemModal.hide();
                    loadYearData();
                } else {
                    alert(res.message);
                }
            });
    }
</script>

<?php require_once '../includes/admin_footer.php'; ?>