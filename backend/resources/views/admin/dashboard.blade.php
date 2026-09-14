@extends('layouts.app')

@section('title', 'Nexus Admin — Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Overview Dashboard</h1>
        <p class="page-subtitle mb-0">
            <span class="live-dot">Live</span>&nbsp;&nbsp; Monday, 19 May 2026 &mdash; Auto-updates every 5 minutes
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-nexus-outline btn-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
            <i class="fa-solid fa-download"></i> Export
        </button>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fa-solid fa-plus"></i> Add New
        </button>
    </div>
</div>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card animate-in">
            <div class="stat-icon-wrap" style="background: rgba(79,70,229,0.12); color: #4f46e5"><i class="fa-solid fa-dollar-sign"></i></div>
            <div class="stat-value" data-count="284750" data-prefix="$">$0</div>
            <div class="stat-label">Total Revenue</div>
            <div class="d-flex align-items-center justify-content-between">
                <div class="stat-trend trend-up"><i class="fa-solid fa-arrow-up"></i> 12.4% vs last month</div>
                <div class="sparkline" style="width: 60px">
                    <div class="sparkline-bar" style="height: 40%"></div>
                    <div class="sparkline-bar" style="height: 55%"></div>
                    <div class="sparkline-bar" style="height: 48%"></div>
                    <div class="sparkline-bar" style="height: 70%"></div>
                    <div class="sparkline-bar" style="height: 65%"></div>
                    <div class="sparkline-bar" style="height: 85%"></div>
                    <div class="sparkline-bar" style="height: 100%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card animate-in">
            <div class="stat-icon-wrap" style="background: rgba(34,197,94,0.12); color: #22c55e"><i class="fa-solid fa-users"></i></div>
            <div class="stat-value" data-count="18429">0</div>
            <div class="stat-label">Active Users</div>
            <div class="stat-trend trend-up"><i class="fa-solid fa-arrow-up"></i> 8.2% new signups this week</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card animate-in">
            <div class="stat-icon-wrap" style="background: rgba(245,158,11,0.12); color: #f59e0b"><i class="fa-solid fa-cart-shopping"></i></div>
            <div class="stat-value" data-count="3842">0</div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-trend trend-down"><i class="fa-solid fa-arrow-down"></i> 2.1% decrease</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-card-gradient animate-in">
            <div class="stat-icon-wrap" style="background: rgba(255,255,255,0.15); color: white"><i class="fa-solid fa-chart-line"></i></div>
            <div class="stat-value" data-count="4.7" data-suffix="%">0%</div>
            <div class="stat-label">Conversion Rate</div>
            <div class="stat-trend" style="color: rgba(255,255,255,0.7)"><i class="fa-solid fa-arrow-up"></i> Top performer this quarter</div>
        </div>
    </div>
</div>

<!-- CHARTS -->
<div class="row g-3 mb-4">
    <div class="col-12 col-lg-7">
        <div class="card-nexus h-100">
            <div class="card-header-nexus">
                <div>
                    <h5 class="card-title">Monthly Revenue</h5>
                    <p class="card-subtitle">Jan — Dec 2025 Performance</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-sm" style="width: auto; font-size: 12px"><option>2025</option><option>2024</option></select>
                    <button class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-ellipsis"></i></button>
                </div>
            </div>
            <div class="card-body-nexus">
                <div class="d-flex gap-4 mb-3">
                    <div>
                        <div style="font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em;">Total</div>
                        <div style="font-size: 22px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.03em;">$284,750</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em;">Growth</div>
                        <div class="stat-trend trend-up" style="font-size: 16px; font-weight: 700"><i class="fa-solid fa-arrow-up"></i>12.4%</div>
                    </div>
                </div>
                <canvas id="revenueChart" width="640" height="200" style="width: 100%; height: 200px" aria-label="Revenue Chart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-5 col-xl-3">
        <div class="card-nexus h-100">
            <div class="card-header-nexus">
                <div>
                    <h5 class="card-title">Traffic Sources</h5>
                    <p class="card-subtitle">This month breakdown</p>
                </div>
            </div>
            <div class="card-body-nexus">
                <canvas id="trafficChart" width="220" height="180" style="width: 100%; max-width: 220px; height: 180px; display: block; margin: 0 auto 16px;" aria-label="Traffic Donut"></canvas>
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2"><div style="width: 10px; height: 10px; border-radius: 2px; background: #4f46e5;"></div><span style="font-size: 12.5px; color: var(--text-secondary);">Organic</span></div>
                        <span style="font-size: 12.5px; font-weight: 700; color: var(--text-primary);">45%</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2"><div style="width: 10px; height: 10px; border-radius: 2px; background: #7c3aed;"></div><span style="font-size: 12.5px; color: var(--text-secondary);">Direct</span></div>
                        <span style="font-size: 12.5px; font-weight: 700; color: var(--text-primary);">28%</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2"><div style="width: 10px; height: 10px; border-radius: 2px; background: #f59e0b;"></div><span style="font-size: 12.5px; color: var(--text-secondary);">Social</span></div>
                        <span style="font-size: 12.5px; font-weight: 700; color: var(--text-primary);">17%</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2"><div style="width: 10px; height: 10px; border-radius: 2px; background: #22c55e;"></div><span style="font-size: 12.5px; color: var(--text-secondary);">Referral</span></div>
                        <span style="font-size: 12.5px; font-weight: 700; color: var(--text-primary);">10%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-5 col-xl-2 d-none d-xl-block">
        <div class="card-nexus mb-3">
            <div class="card-header-nexus"><div><h5 class="card-title" style="font-size: 13px">User Growth</h5></div></div>
            <div class="card-body-nexus py-2">
                <canvas id="usersChart" width="200" height="100" style="width: 100%; height: 100px" aria-label="Users Chart"></canvas>
            </div>
        </div>
        <div class="card-nexus">
            <div class="card-header-nexus"><div><h5 class="card-title" style="font-size: 13px">Performance</h5></div></div>
            <div class="card-body-nexus py-2">
                <canvas id="perfChart" width="200" height="100" style="width: 100%; height: 100px" aria-label="Performance Chart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- RECENT ORDERS TABLE -->
<div class="row g-3 mb-4">
    <div class="col-12 col-xl-8">
        <div class="card-nexus">
            <div class="card-header-nexus">
                <div>
                    <h5 class="card-title">Recent Orders</h5>
                    <p class="card-subtitle">Latest 6 transactions</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-filter"></i> Filter</button>
                    <a href="#" class="btn btn-primary btn-sm"><i class="fa-solid fa-arrow-right"></i> View All</a>
                </div>
            </div>
            <div class="card-body-nexus p-0">
                <div class="table-responsive">
                    <table class="table-nexus w-100" aria-label="Recent Orders">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="masterCheck" class="form-check-input" aria-label="Select all" /></th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input type="checkbox" class="form-check-input row-check" aria-label="Select row" /></td>
                                <td><span class="font-mono" style="font-size: 12.5px; color: var(--text-muted);">#4821</span></td>
                                <td><div class="user-cell"><div class="avatar avatar-sm" style="background: linear-gradient(135deg, #f59e0b, #ef4444);">S</div><div><div class="u-name">Sarah Johnson</div><div class="u-email">sarah@email.com</div></div></div></td>
                                <td style="font-size: 13px; color: var(--text-secondary);">Nexus Pro Bundle</td>
                                <td style="font-weight: 700; color: var(--text-primary);">$249.00</td>
                                <td><span class="badge-nexus badge-success">Shipped</span></td>
                                <td style="font-size: 12.5px; color: var(--text-muted);">19 May 2026</td>
                                <td><div class="dropdown"><button class="btn-icon btn btn-nexus-outline btn-sm" data-bs-toggle="dropdown" aria-label="Actions"><i class="fa-solid fa-ellipsis"></i></button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#"><i class="fa-solid fa-eye"></i> View</a></li><li><a class="dropdown-item" href="#"><i class="fa-solid fa-pencil"></i> Edit</a></li><li><hr class="dropdown-divider" /></li><li><a class="dropdown-item text-danger" href="#" data-confirm="Delete this order?"><i class="fa-solid fa-trash"></i> Delete</a></li></ul></div></td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="form-check-input row-check" aria-label="Select row" /></td>
                                <td><span class="font-mono" style="font-size: 12.5px; color: var(--text-muted);">#4820</span></td>
                                <td><div class="user-cell"><div class="avatar avatar-sm" style="background: linear-gradient(135deg, #4f46e5, #7c3aed);">M</div><div><div class="u-name">Marcus Lee</div><div class="u-email">marcus@corp.io</div></div></div></td>
                                <td style="font-size: 13px; color: var(--text-secondary);">Analytics Dashboard</td>
                                <td style="font-weight: 700; color: var(--text-primary);">$89.00</td>
                                <td><span class="badge-nexus badge-warning">Pending</span></td>
                                <td style="font-size: 12.5px; color: var(--text-muted);">18 May 2026</td>
                                <td><div class="dropdown"><button class="btn-icon btn btn-nexus-outline btn-sm" data-bs-toggle="dropdown" aria-label="Actions"><i class="fa-solid fa-ellipsis"></i></button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#"><i class="fa-solid fa-eye"></i> View</a></li><li><a class="dropdown-item" href="#"><i class="fa-solid fa-pencil"></i> Edit</a></li><li><hr class="dropdown-divider" /></li><li><a class="dropdown-item text-danger" href="#" data-confirm="Delete this order?"><i class="fa-solid fa-trash"></i> Delete</a></li></ul></div></td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="form-check-input row-check" aria-label="Select row" /></td>
                                <td><span class="font-mono" style="font-size: 12.5px; color: var(--text-muted);">#4819</span></td>
                                <td><div class="user-cell"><div class="avatar avatar-sm" style="background: linear-gradient(135deg, #22c55e, #16a34a);">P</div><div><div class="u-name">Priya Patel</div><div class="u-email">priya@startup.co</div></div></div></td>
                                <td style="font-size: 13px; color: var(--text-secondary);">Team License x5</td>
                                <td style="font-weight: 700; color: var(--text-primary);">$445.00</td>
                                <td><span class="badge-nexus badge-success">Completed</span></td>
                                <td style="font-size: 12.5px; color: var(--text-muted);">17 May 2026</td>
                                <td><div class="dropdown"><button class="btn-icon btn btn-nexus-outline btn-sm" data-bs-toggle="dropdown" aria-label="Actions"><i class="fa-solid fa-ellipsis"></i></button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="#"><i class="fa-solid fa-eye"></i> View</a></li><li><a class="dropdown-item" href="#"><i class="fa-solid fa-pencil"></i> Edit</a></li></ul></div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TASKS + TIMELINE -->
    <div class="col-12 col-xl-4 d-flex flex-column gap-3">
        <div class="card-nexus">
            <div class="card-header-nexus">
                <div>
                    <h5 class="card-title">Tasks</h5>
                    <p class="card-subtitle">3 of 7 completed</p>
                </div>
                <button class="btn btn-primary btn-sm btn-icon" aria-label="Add task"><i class="fa-solid fa-plus"></i></button>
            </div>
            <div class="card-body-nexus">
                <div class="task-item">
                    <div class="task-check checked" role="checkbox" aria-checked="true" tabindex="0"><i class="fa-solid fa-check"></i></div>
                    <div class="task-text completed">Review Q2 financial reports</div>
                    <span class="badge-nexus badge-neutral ms-auto" style="font-size: 10px">Finance</span>
                </div>
                <div class="task-item">
                    <div class="task-check checked" role="checkbox" aria-checked="true" tabindex="0"><i class="fa-solid fa-check"></i></div>
                    <div class="task-text completed">Update user onboarding flow</div>
                    <span class="badge-nexus badge-info ms-auto" style="font-size: 10px">UX</span>
                </div>
                <div class="task-item">
                    <div class="task-check" role="checkbox" aria-checked="false" tabindex="0"></div>
                    <div class="task-text">Deploy v3.1.0 to production</div>
                    <span class="badge-nexus badge-danger ms-auto" style="font-size: 10px">Dev</span>
                </div>
                <div class="task-item">
                    <div class="task-check" role="checkbox" aria-checked="false" tabindex="0"></div>
                    <div class="task-text">Prepare investor deck for Q3</div>
                    <span class="badge-nexus badge-warning ms-auto" style="font-size: 10px">Strategy</span>
                </div>
                <div class="task-item">
                    <div class="task-check" role="checkbox" aria-checked="false" tabindex="0"></div>
                    <div class="task-text">Schedule security audit review</div>
                    <span class="badge-nexus badge-purple ms-auto" style="font-size: 10px">Security</span>
                </div>
                <div class="mt-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size: 12px; color: var(--text-muted);">Progress</span>
                        <span style="font-size: 12px; font-weight: 700; color: var(--text-primary);">42%</span>
                    </div>
                    <div class="progress-nexus"><div class="progress-fill" style="width: 42%"></div></div>
                </div>
            </div>
        </div>

        <div class="card-nexus">
            <div class="card-header-nexus">
                <div>
                    <h5 class="card-title">Activity Feed</h5>
                    <p class="card-subtitle">Today's events</p>
                </div>
            </div>
            <div class="card-body-nexus">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-dot" style="border-color: #22c55e; color: #22c55e"><i class="fa-solid fa-check"></i></div>
                        <div class="timeline-content"><strong>Deploy Successful</strong> — v3.0.9 is live on production</div>
                        <div class="timeline-time">10:42 AM</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-dot" style="border-color: #4f46e5; color: #4f46e5"><i class="fa-solid fa-user"></i></div>
                        <div class="timeline-content"><strong>Sarah Johnson</strong> joined as Enterprise user</div>
                        <div class="timeline-time">9:18 AM</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-dot" style="border-color: #f59e0b; color: #f59e0b"><i class="fa-solid fa-exclamation"></i></div>
                        <div class="timeline-content"><strong>Low Stock Alert</strong> — 3 products need restocking</div>
                        <div class="timeline-time">8:55 AM</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-dot" style="border-color: #3b82f6; color: #3b82f6"><i class="fa-solid fa-file-lines"></i></div>
                        <div class="timeline-content"><strong>Report Generated</strong> — Monthly analytics exported</div>
                        <div class="timeline-time">8:30 AM</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-dot" style="border-color: #ec4899; color: #ec4899"><i class="fa-solid fa-star"></i></div>
                        <div class="timeline-content"><strong>New Review</strong> — 5★ rating from Aiko Tanaka</div>
                        <div class="timeline-time">7:14 AM</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('modals')
<!-- MODALS -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel"><i class="fa-solid fa-user-plus me-2" style="color: var(--accent-primary);"></i>Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="newFirstName" placeholder="First Name" />
                            <label for="newFirstName">First Name</label>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="newLastName" placeholder="Last Name" />
                            <label for="newLastName">Last Name</label>
                        </div>
                    </div>
                    <div class="col-12"><div class="form-floating"><input type="email" class="form-control" id="newEmail" placeholder="Email" /><label for="newEmail">Email Address</label></div></div>
                    <div class="col-12">
                        <label class="form-label">Role</label>
                        <select class="form-select"><option>Select role...</option><option>Admin</option><option>Editor</option><option>Viewer</option></select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-nexus-outline" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary"><i class="fa-solid fa-user-check"></i> Add User</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">Export Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p style="font-size: 13.5px; color: var(--text-secondary);">Choose format to export dashboard data:</p>
                <div class="d-flex flex-column gap-2">
                    <button class="btn btn-nexus-outline text-start d-flex align-items-center gap-3 p-3"><i class="fa-solid fa-file-csv" style="font-size: 22px; color: #22c55e;"></i><div><div style="font-weight: 600; font-size: 13px;">CSV File</div><div style="font-size: 11px; color: var(--text-muted);">Comma separated values</div></div></button>
                    <button class="btn btn-nexus-outline text-start d-flex align-items-center gap-3 p-3"><i class="fa-solid fa-file-excel" style="font-size: 22px; color: #16a34a;"></i><div><div style="font-weight: 600; font-size: 13px;">Excel Spreadsheet</div><div style="font-size: 11px; color: var(--text-muted);">.xlsx format</div></div></button>
                    <button class="btn btn-nexus-outline text-start d-flex align-items-center gap-3 p-3"><i class="fa-solid fa-file-pdf" style="font-size: 22px; color: #ef4444;"></i><div><div style="font-weight: 600; font-size: 13px;">PDF Report</div><div style="font-size: 11px; color: var(--text-muted);">Formatted report</div></div></button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="mb-3" style="width: 52px; height: 52px; background: rgba(239,68,68,0.12); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;"><i class="fa-solid fa-trash" style="font-size: 22px; color: #ef4444;"></i></div>
                <h6 style="font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">Delete Record?</h6>
                <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 20px;">This action cannot be undone.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-nexus-outline btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger btn-sm">Yes, Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endpush
