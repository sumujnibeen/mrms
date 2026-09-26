<?php
include(__DIR__ . '/config/db.php');

// ─── Fetch rooms from database ────────────────────────────────────────────────
$query = "
SELECT
    room_id          AS id,
    room_number      AS num,
    floor,
    type,
    capacity,
    price_per_night  AS price,
    status,
    description,
    image
FROM room
ORDER BY floor, room_number
";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

$rooms = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['guest']     = '';
    $row['check_in']  = '';
    $row['check_out'] = '';
    $rooms[] = $row;
}

// ─── Filters ─────────────────────────────────────────────────────────────────
$search        = isset($_GET['search']) ? trim($_GET['search']) : '';
$type_filter   = isset($_GET['type'])   ? $_GET['type']   : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$floor_filter  = isset($_GET['floor'])  ? $_GET['floor']  : '';
$view          = isset($_GET['view'])   ? $_GET['view']   : 'grid';

$filtered = array_filter($rooms, function($r) use ($search, $type_filter, $status_filter, $floor_filter) {
    if ($search && stripos($r['num'], $search) === false && stripos($r['type'], $search) === false) return false;
    if ($type_filter   && $r['type']          !== $type_filter)   return false;
    if ($status_filter && $r['status']        !== $status_filter) return false;
    if ($floor_filter  && (string)$r['floor'] !== $floor_filter)  return false;
    return true;
});

// ─── Stats ───────────────────────────────────────────────────────────────────
$total       = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM room"));
$avail       = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM room WHERE status='Available'"));
$occupied    = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM room WHERE status='Booked'"));
$maintenance = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM room WHERE status='Under Maintenance'"));
$occ_pct     = ($total > 0) ? round(($occupied / $total) * 100) : 0;

// ─── Helpers ─────────────────────────────────────────────────────────────────
$status_map = [
    'Available'         => ['label' => 'Available',         'bg' => '#EAF3DE', 'color' => '#3B6D11'],
    'Booked'            => ['label' => 'Booked',            'bg' => '#E6F1FB', 'color' => '#185FA5'],
    'Under Maintenance' => ['label' => 'Under Maintenance', 'bg' => '#FCEBEB', 'color' => '#A32D2D'],
];

// Get distinct types and floors from DB for filter dropdowns
$types_result  = mysqli_query($conn, "SELECT DISTINCT type FROM room ORDER BY type");
$floors_result = mysqli_query($conn, "SELECT DISTINCT floor FROM room ORDER BY floor");

function current_url_with($params) {
    $current = array_merge($_GET, $params);
    return '?' . http_build_query($current);
}

// Group by floor for grid view
$by_floor = [];
foreach ($filtered as $r) {
    $by_floor[$r['floor']][] = $r;
}
ksort($by_floor);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rooms – MRMS</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: system-ui, -apple-system, sans-serif; background: #f3f4f6; color: #111827; min-height: 100vh; }
.layout { display: flex; min-height: 100vh; }

/* Sidebar */
.sidebar { width: 240px; background: #fff; border-right: 0.5px solid #e5e7eb; display: flex; flex-direction: column; flex-shrink: 0; }
.sidebar-logo { padding: 1.25rem 1.5rem; border-bottom: 0.5px solid #e5e7eb; }
.sidebar-section-label { font-size: 10px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.08em; padding: 1.25rem 1.5rem 0.4rem; }
.sidebar-nav a { display: flex; align-items: center; gap: 10px; padding: 9px 1.5rem; font-size: 14px; color: #374151; text-decoration: none; transition: background 0.1s; }
.sidebar-nav a:hover { background: #f9fafb; }
.sidebar-nav a.active { background: #EFF6FF; color: #185FA5; font-weight: 500; border-right: 2px solid #185FA5; }
.sidebar-nav a .icon { font-size: 16px; opacity: 0.7; }
.sidebar-nav a .badge { margin-left: auto; background: #185FA5; color: #fff; border-radius: 10px; font-size: 11px; padding: 1px 7px; }
.sidebar-bottom { margin-top: auto; padding: 1rem 1.5rem; border-top: 0.5px solid #e5e7eb; display: flex; align-items: center; gap: 10px; }
.avatar-sm { width: 32px; height: 32px; border-radius: 50%; background: #185FA5; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; flex-shrink: 0; }

/* Main */
.main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.topbar { background: #fff; border-bottom: 0.5px solid #e5e7eb; padding: 1.25rem 1.5rem 1rem; display: flex; align-items: center; justify-content: space-between; }
.topbar h1 { font-size: 20px; font-weight: 500; }
.topbar-date { font-size: 13px; color: #6b7280; margin-top: 2px; }
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 8px; font-size: 14px; cursor: pointer; text-decoration: none; border: 0.5px solid #e5e7eb; background: #fff; color: #374151; transition: background 0.15s; }
.btn:hover { background: #f9fafb; }
.btn-primary { background: #185FA5; color: #fff; border-color: #185FA5; }
.btn-primary:hover { background: #0C447C; }

/* Stats */
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; padding: 1rem 1.5rem; background: #f3f4f6; }
.stat-card { background: #fff; border: 0.5px solid #e5e7eb; border-radius: 10px; padding: 0.875rem 1rem; }
.stat-label { font-size: 12px; color: #9ca3af; margin-bottom: 4px; }
.stat-val { font-size: 24px; font-weight: 500; }
.stat-sub { font-size: 12px; color: #9ca3af; margin-top: 2px; }

/* Filters */
.filters { background: #fff; border-bottom: 0.5px solid #e5e7eb; padding: 0.875rem 1.5rem; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.search-box { display: flex; align-items: center; gap: 8px; background: #f3f4f6; border: 0.5px solid #e5e7eb; border-radius: 8px; padding: 7px 12px; flex: 1; min-width: 180px; max-width: 240px; }
.search-box input { border: none; background: transparent; font-size: 14px; color: #111827; outline: none; width: 100%; }
.filter-select { background: #f3f4f6; border: 0.5px solid #e5e7eb; border-radius: 8px; padding: 7px 12px; font-size: 13px; color: #374151; cursor: pointer; outline: none; }
.view-toggle { display: flex; gap: 4px; margin-left: auto; }
.vbtn { padding: 7px 12px; border-radius: 8px; border: 0.5px solid #e5e7eb; background: #f3f4f6; font-size: 13px; cursor: pointer; color: #6b7280; text-decoration: none; }
.vbtn.active { background: #fff; color: #185FA5; border-color: #185FA5; }

/* Content */
.content { flex: 1; overflow: auto; padding: 1rem 1.5rem; background: #f3f4f6; }
.floor-label { font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .08em; margin: 0.75rem 0 0.5rem; }
.floor-label:first-child { margin-top: 0; }

/* Grid */
.grid-view { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; margin-bottom: 0.5rem; }
.room-card { background: #fff; border: 0.5px solid #e5e7eb; border-radius: 12px; padding: 1rem; text-decoration: none; color: inherit; display: block; transition: box-shadow 0.15s, border-color 0.15s; }
.room-card:hover { border-color: #b5d4f4; box-shadow: 0 2px 8px rgba(24,95,165,.08); }
.room-card.available-card    { border-left: 3px solid #3B6D11; }
.room-card.booked-card       { border-left: 3px solid #185FA5; }
.room-card.maintenance-card  { border-left: 3px solid #A32D2D; }
.room-num { font-size: 22px; font-weight: 500; margin-bottom: 3px; }
.room-type-tag { font-size: 11px; color: #9ca3af; margin-bottom: 10px; }
.status-badge { display: inline-flex; align-items: center; gap: 5px; border-radius: 20px; padding: 3px 10px; font-size: 11px; font-weight: 500; white-space: nowrap; }
.status-dot { width: 5px; height: 5px; border-radius: 50%; display: inline-block; }
.room-price { font-size: 12px; font-weight: 500; color: #374151; margin-top: 8px; }
.room-desc { font-size: 11px; color: #9ca3af; margin-top: 4px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }

/* List Table */
.list-table { width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; border-radius: 12px; overflow: hidden; border: 0.5px solid #e5e7eb; }
.list-table thead th { text-align: left; font-size: 11px; font-weight: 500; color: #9ca3af; text-transform: uppercase; letter-spacing: .06em; padding: 12px; border-bottom: 0.5px solid #e5e7eb; background: #fafafa; }
.list-table tbody tr { border-bottom: 0.5px solid #f3f4f6; transition: background .1s; }
.list-table tbody tr:last-child { border-bottom: none; }
.list-table tbody tr:hover { background: #f9fafb; }
.list-table td { padding: 12px; vertical-align: middle; }
.room-pill { display: inline-block; background: #f3f4f6; border: 0.5px solid #e5e7eb; border-radius: 6px; padding: 3px 9px; font-size: 12px; font-weight: 500; }
.action-btn { background: none; border: 0.5px solid #e5e7eb; border-radius: 7px; padding: 5px 10px; font-size: 12px; color: #6b7280; cursor: pointer; text-decoration: none; }
.action-btn:hover { background: #f3f4f6; }
.empty-msg { text-align: center; padding: 2.5rem; color: #9ca3af; }

/* Footer */
.footer-bar { background: #fff; border-top: 0.5px solid #e5e7eb; padding: 12px 1.5rem; display: flex; align-items: center; justify-content: space-between; }
.footer-text { font-size: 12px; color: #9ca3af; }

/* Legend */
.legend { display: flex; flex-wrap: wrap; gap: 10px; padding: 0.5rem 0 0.75rem; }
.legend-item { display: flex; align-items: center; gap: 5px; font-size: 11px; color: #6b7280; }
.legend-dot { width: 8px; height: 8px; border-radius: 50%; }

/* Modal */
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 200; align-items: center; justify-content: center; }
.modal-overlay.open { display: flex; }
.modal { background: #fff; border-radius: 12px; border: 0.5px solid #e5e7eb; padding: 1.5rem; width: 460px; max-width: 95vw; }
.modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.modal-title { font-size: 16px; font-weight: 500; }
.modal-close { background: none; border: none; font-size: 22px; color: #9ca3af; cursor: pointer; }
.modal-close:hover { color: #374151; }
.form-row { margin-bottom: 1rem; }
.form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1rem; }
.form-label { font-size: 12px; color: #6b7280; display: block; margin-bottom: 4px; }
.form-input { width: 100%; background: #f9fafb; border: 0.5px solid #e5e7eb; border-radius: 8px; padding: 8px 12px; font-size: 14px; color: #111827; outline: none; }
.form-input:focus { border-color: #185FA5; background: #fff; }
.modal-footer { display: flex; gap: 8px; justify-content: flex-end; margin-top: 1.25rem; }

@media (max-width: 768px) {
    .sidebar { display: none; }
    .stats-row { grid-template-columns: repeat(2, 1fr); }
}
</style>
</head>
<body>
<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <img src="meghdut_photo-removebg-preview.png" width="100" height="100">
    </div>
    <div class="sidebar-section-label">Main</div>
    <nav class="sidebar-nav">
      <a href="index.php"><span class="icon">⊞</span> Dashboard</a>
      <a href="reservations.php"><span class="icon">📅</span> Reservations <span class="badge">4</span></a>
      <a href="rooms.php" class="active"><span class="icon">🛏</span> Rooms</a>
      <a href="guests.php"><span class="icon">👤</span> Guests</a>
    </nav>
    <div class="sidebar-section-label">Operations</div>
    <nav class="sidebar-nav">
      <a href="housekeeping.php"><span class="icon">🧹</span> Housekeeping</a>
      <a href="billing.php"><span class="icon">🧾</span> Billing</a>
      <a href="report.php"><span class="icon">📊</span> Reports</a>
      <a href="restaurant.php"><span class="icon">🍽</span> Restaurant</a>
    </nav>
    <div class="sidebar-section-label">System</div>
    <nav class="sidebar-nav">
      <a href="settings.php"><span class="icon">⚙</span> Settings</a>
      <a href="staff.php"><span class="icon">👥</span> Staff</a>
    </nav>
    <div class="sidebar-bottom">
      <div class="avatar-sm">AD</div>
      <div>
        <div style="font-size:13px;font-weight:500;">Admin</div>
        <div style="font-size:11px;color:#9ca3af;">Manager</div>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <div class="main">

    <!-- Topbar -->
    <div class="topbar">
      <div>
        <h1>Rooms</h1>
        <div class="topbar-date"><?= date('l, d F Y') ?></div>
      </div>
      <div style="display:flex;gap:8px;">
        <button class="btn btn-primary" onclick="openModal()">+ Add Room</button>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Total Rooms</div>
        <div class="stat-val"><?= $total ?></div>
        <div class="stat-sub">All floors</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Available</div>
        <div class="stat-val" style="color:#3B6D11;"><?= $avail ?></div>
        <div class="stat-sub">Ready to book</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Booked</div>
        <div class="stat-val" style="color:#185FA5;"><?= $occupied ?></div>
        <div class="stat-sub"><?= $occ_pct ?>% occupancy</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Maintenance</div>
        <div class="stat-val" style="color:#A32D2D;"><?= $maintenance ?></div>
        <div class="stat-sub">Under repair</div>
      </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="">
      <div class="filters">
        <div class="search-box">
          <span style="color:#9ca3af;">🔍</span>
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search room, type…" />
        </div>

        <!-- Type filter — pulled from DB -->
        <select name="type" class="filter-select" onchange="this.form.submit()">
          <option value="">All types</option>
          <?php
          mysqli_data_seek($types_result, 0);
          while ($t = mysqli_fetch_assoc($types_result)):
          ?>
            <option value="<?= $t['type'] ?>" <?= $type_filter===$t['type']?'selected':'' ?>><?= $t['type'] ?></option>
          <?php endwhile; ?>
        </select>

        <!-- Status filter -->
        <select name="status" class="filter-select" onchange="this.form.submit()">
          <option value="">All statuses</option>
          <?php foreach (['Available'=>'Available','Booked'=>'Booked','Under Maintenance'=>'Under Maintenance'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $status_filter===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>

        <!-- Floor filter — pulled from DB -->
        <select name="floor" class="filter-select" onchange="this.form.submit()">
          <option value="">All floors</option>
          <?php
          mysqli_data_seek($floors_result, 0);
          while ($fl = mysqli_fetch_assoc($floors_result)):
          ?>
            <option value="<?= $fl['floor'] ?>" <?= $floor_filter==(string)$fl['floor']?'selected':'' ?>>Floor <?= $fl['floor'] ?></option>
          <?php endwhile; ?>
        </select>

        <button type="submit" class="btn" style="flex-shrink:0;">Apply</button>
        <?php if ($search || $type_filter || $status_filter || $floor_filter): ?>
          <a href="rooms.php" class="btn">✕ Clear</a>
        <?php endif; ?>
        <div class="view-toggle">
          <a href="<?= current_url_with(['view'=>'grid']) ?>" class="vbtn <?= $view==='grid'?'active':'' ?>">⊞ Grid</a>
          <a href="<?= current_url_with(['view'=>'list']) ?>" class="vbtn <?= $view==='list'?'active':'' ?>">☰ List</a>
        </div>
      </div>
    </form>

    <!-- Content -->
    <div class="content">

      <!-- Legend -->
      <div class="legend">
        <?php foreach ($status_map as $k => $s): ?>
          <div class="legend-item">
            <div class="legend-dot" style="background:<?= $s['color'] ?>;"></div>
            <?= $s['label'] ?>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($view === 'grid'): ?>

        <?php if (empty($by_floor)): ?>
          <div class="empty-msg">No rooms found.</div>
        <?php else: ?>
          <?php foreach ($by_floor as $floor => $floor_rooms): ?>
            <div class="floor-label">Floor <?= $floor ?></div>
            <div class="grid-view">
              <?php foreach ($floor_rooms as $r):
                $st  = $status_map[$r['status']] ?? ['label'=>$r['status'],'bg'=>'#f3f4f6','color'=>'#374151'];
                $css = match($r['status']) {
                    'Available'         => 'available-card',
                    'Booked'            => 'booked-card',
                    'Under Maintenance' => 'maintenance-card',
                    default             => ''
                };
              ?>
              <a href="room_detail.php?id=<?= $r['id'] ?>" class="room-card <?= $css ?>">
                <div class="room-num"><?= htmlspecialchars($r['num']) ?></div>
                <div class="room-type-tag"><?= $r['type'] ?> · <?= $r['capacity'] ?> pax</div>
                <span class="status-badge" style="background:<?= $st['bg'] ?>;color:<?= $st['color'] ?>;">
                  <span class="status-dot" style="background:<?= $st['color'] ?>;"></span>
                  <?= $st['label'] ?>
                </span>
                <div class="room-price">৳<?= number_format($r['price']) ?>/night</div>
                <?php if ($r['description']): ?>
                  <div class="room-desc"><?= htmlspecialchars($r['description']) ?></div>
                <?php endif; ?>
              </a>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

      <?php else: ?>

        <table class="list-table">
          <thead>
            <tr>
              <th>Room</th>
              <th>Type</th>
              <th>Floor</th>
              <th>Capacity</th>
              <th>Price / Night</th>
              <th>Status</th>
              <th>Description</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($filtered)): ?>
              <tr><td colspan="8" class="empty-msg">No rooms found.</td></tr>
            <?php else: ?>
              <?php foreach ($filtered as $r):
                $st = $status_map[$r['status']] ?? ['label'=>$r['status'],'bg'=>'#f3f4f6','color'=>'#374151'];
              ?>
              <tr>
                <td><span class="room-pill"><?= htmlspecialchars($r['num']) ?></span></td>
                <td style="color:#6b7280;font-size:12px;"><?= $r['type'] ?></td>
                <td style="color:#9ca3af;font-size:12px;">Floor <?= $r['floor'] ?></td>
                <td style="color:#9ca3af;font-size:12px;"><?= $r['capacity'] ?> pax</td>
                <td style="font-weight:500;">৳<?= number_format($r['price']) ?></td>
                <td>
                  <span class="status-badge" style="background:<?= $st['bg'] ?>;color:<?= $st['color'] ?>;">
                    <span class="status-dot" style="background:<?= $st['color'] ?>;"></span>
                    <?= $st['label'] ?>
                  </span>
                </td>
                <td style="font-size:11px;color:#9ca3af;max-width:160px;"><?= htmlspecialchars($r['description'] ?? '') ?></td>
                <td>
                  <div style="display:flex;gap:5px;">
                    <a href="room_detail.php?id=<?= $r['id'] ?>" class="action-btn" title="View">👁</a>
                    <a href="room_edit.php?id=<?= $r['id'] ?>" class="action-btn" title="Edit">✏</a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

      <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="footer-bar">
      <span class="footer-text">Showing <?= count($filtered) ?> of <?= $total ?> rooms</span>
    </div>

  </div>
</div>

<!-- Add Room Modal -->
<div class="modal-overlay" id="modal-overlay" onclick="closeModalOut(event)">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add New Room</span>
      <button class="modal-close" onclick="closeModal()" type="button">×</button>
    </div>
    <form method="POST" action="save_room.php">
      <div class="form-row-2">
        <div>
          <label class="form-label">Room Number</label>
          <input class="form-input" type="text" name="room_num" placeholder="e.g. 105" required />
        </div>
        <div>
          <label class="form-label">Floor</label>
          <select class="form-input" name="floor">
            <option value="1">Floor 1</option>
            <option value="2">Floor 2</option>
            <option value="3">Floor 3</option>
            <option value="4">Floor 4</option>
          </select>
        </div>
      </div>
      <div class="form-row-2">
        <div>
          <label class="form-label">Room Type</label>
          <select class="form-input" name="room_type" id="modal-type" onchange="autoPrice()">
            <option value="Single">Single</option>
            <option value="Double">Double</option>
            <option value="Deluxe">Deluxe</option>
            <option value="Suite">Suite</option>
          </select>
        </div>
        <div>
          <label class="form-label">Capacity</label>
          <select class="form-input" name="capacity">
            <option value="1">1 person</option>
            <option value="2" selected>2 persons</option>
            <option value="3">3 persons</option>
            <option value="4">4 persons</option>
          </select>
        </div>
      </div>
      <div class="form-row-2">
        <div>
          <label class="form-label">Price / Night (৳)</label>
          <input class="form-input" type="number" name="price" id="modal-price" value="2500" required />
        </div>
        <div>
          <label class="form-label">Status</label>
          <select class="form-input" name="status">
            <option value="Available">Available</option>
            <option value="Booked">Booked</option>
            <option value="Under Maintenance">Under Maintenance</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <label class="form-label">Description</label>
        <input class="form-input" type="text" name="description" placeholder="Short room description…" />
      </div>
      <div class="modal-footer">
        <button type="button" class="btn" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Room</button>
      </div>
    </form>
  </div>
</div>

<script>
const typePrices = { Single: 2500, Double: 4000, Deluxe: 6500, Suite: 9500 };
function autoPrice() {
    const t = document.getElementById('modal-type').value;
    document.getElementById('modal-price').value = typePrices[t] || 2500;
}
function openModal()      { document.getElementById('modal-overlay').classList.add('open'); }
function closeModal()     { document.getElementById('modal-overlay').classList.remove('open'); }
function closeModalOut(e) { if (e.target === document.getElementById('modal-overlay')) closeModal(); }
document.querySelector('input[name="search"]')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') this.closest('form').submit();
});
</script>
</body>
</html>