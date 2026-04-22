<style>
.storestock-ui .content-header h1 {
    font-size: 1.35rem;
    font-weight: 700;
    color: #012970;
    margin-bottom: 1rem;
}

.storestock-ui .content-header h1 small {
    color: #6c757d;
    font-weight: 500;
    margin-left: .45rem;
}

.storestock-ui .box {
    background: #fff;
    border: 1px solid #ebeef4;
    border-radius: 12px;
    box-shadow: 0 0 24px rgba(1, 41, 112, 0.06);
    margin-bottom: 1rem;
}

.storestock-ui .box-header,
.storestock-ui .box-footer {
    padding: .85rem 1rem;
    border-color: #ebeef4;
}

.storestock-ui .box-body {
    padding: 1rem;
}

.storestock-ui .box-title {
    color: #012970;
    font-size: 1rem;
    font-weight: 700;
    margin: 0;
}

.storestock-ui .btn-app {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    min-width: 128px;
    min-height: 94px;
    margin: .35rem;
    padding: .75rem;
    border: 1px solid #ebeef4;
    border-radius: 10px;
    background: #fff;
    color: #012970;
    text-decoration: none;
    font-weight: 600;
    transition: all .18s ease;
}

.storestock-ui .btn-app i {
    font-size: 1.25rem;
    margin-bottom: .35rem;
    color: #4154f1;
}

.storestock-ui .btn-app:hover {
    transform: translateY(-1px);
    border-color: #dbe3ff;
    box-shadow: 0 6px 20px rgba(65, 84, 241, 0.12);
}

.storestock-ui .table {
    margin-bottom: 0;
}

.storestock-ui .table > thead > tr > th {
    background: #f6f9ff;
    color: #012970;
    border-bottom-width: 1px;
    font-weight: 700;
    font-size: .86rem;
}

.storestock-ui .table > tbody > tr > td,
.storestock-ui .table > tfoot > tr > th,
.storestock-ui .table > tfoot > tr > td {
    vertical-align: middle;
}

.storestock-ui .form-control,
.storestock-ui .input-sm {
    border-radius: 8px;
    border-color: #dfe3ea;
}

.storestock-ui .input-group-addon {
    background: #f6f9ff;
    border: 1px solid #dfe3ea;
    border-right: 0;
    border-radius: 8px 0 0 8px;
    color: #4154f1;
    padding: .45rem .7rem;
}

.storestock-ui .btn-xs,
.storestock-ui .btn-sm {
    border-radius: 8px;
}

.storestock-ui .nav-stacked > li > a {
    display: block;
    padding: .5rem .2rem;
    color: #4154f1;
    text-decoration: none;
    font-weight: 600;
}

.storestock-ui .nav-stacked > li > a:hover {
    color: #012970;
}

.storestock-ui .bg-gray {
    background: #f6f9ff;
    color: #012970;
    border-bottom: 1px solid #ebeef4;
}

.storestock-ui .modal-content {
    border-radius: 12px;
    border: 1px solid #ebeef4;
    box-shadow: 0 0 30px rgba(1, 41, 112, 0.15);
}

.storestock-ui .modal-header {
    background: #f6f9ff;
    border-bottom: 1px solid #ebeef4;
}

.storestock-ui .modal-title {
    color: #012970;
    font-weight: 700;
}

.storestock-ui .module-hero {
    border: 1px solid #e9edf5;
    background: linear-gradient(135deg, #f6f9ff 0%, #ffffff 85%);
    border-radius: 14px;
    padding: 1rem 1.1rem;
    margin-bottom: 1rem;
}

.storestock-ui .module-hero h4 {
    margin: 0;
    color: #012970;
    font-weight: 700;
}

.storestock-ui .module-hero p {
    margin: .35rem 0 0 0;
    color: #6c757d;
}

.storestock-ui .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(120px, 1fr));
    gap: .75rem;
    margin-bottom: 1rem;
}

.storestock-ui .stat-card {
    border: 1px solid #ebeef4;
    border-radius: 12px;
    background: #fff;
    padding: .75rem .9rem;
}

.storestock-ui .stat-card .label {
    display: block;
    color: #6c757d;
    font-size: .76rem;
    font-weight: 600;
    margin-bottom: .35rem;
}

.storestock-ui .stat-card .value {
    color: #012970;
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1;
}

.storestock-ui .action-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(180px, 1fr));
    gap: .75rem;
}

.storestock-ui .action-card {
    display: block;
    border: 1px solid #e6eaf2;
    border-radius: 12px;
    background: #fff;
    padding: .95rem;
    text-decoration: none;
    color: #212529;
    transition: all .18s ease;
}

.storestock-ui .action-card:hover {
    transform: translateY(-1px);
    border-color: #dbe3ff;
    box-shadow: 0 8px 22px rgba(65, 84, 241, 0.14);
    text-decoration: none;
}

.storestock-ui .action-card .icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #eef2ff;
    color: #4154f1;
    font-size: 1.1rem;
    margin-bottom: .55rem;
}

.storestock-ui .action-card h5 {
    margin: 0;
    font-size: .98rem;
    color: #012970;
    font-weight: 700;
}

.storestock-ui .action-card p {
    margin: .35rem 0 0 0;
    color: #6c757d;
    font-size: .82rem;
}

.storestock-ui .toolbar-row {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    align-items: center;
    justify-content: space-between;
    margin-bottom: .8rem;
}

.storestock-ui .toolbar-row .left,
.storestock-ui .toolbar-row .right {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    align-items: center;
}

.storestock-ui .pill-btn {
    border: 1px solid #dfe3ea;
    border-radius: 999px;
    background: #fff;
    color: #012970;
    padding: .4rem .8rem;
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
}

.storestock-ui .pill-btn.active,
.storestock-ui .pill-btn:hover {
    border-color: #4154f1;
    color: #4154f1;
}

.storestock-ui .panel-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(260px, 1fr));
    gap: .9rem;
}

.storestock-ui .control-card {
    border: 1px solid #ebeef4;
    border-radius: 12px;
    background: #fff;
    padding: .95rem;
}

.storestock-ui .control-card h5 {
    margin: 0 0 .65rem 0;
    color: #012970;
    font-size: .95rem;
    font-weight: 700;
}

.storestock-ui .control-card .help {
    margin-top: .55rem;
    color: #6c757d;
    font-size: .8rem;
}

.storestock-ui .menu-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(220px, 1fr));
    gap: .9rem;
}

.storestock-ui .menu-section {
    border: 1px solid #e9edf5;
    border-radius: 12px;
    background: #fff;
    overflow: hidden;
}

.storestock-ui .menu-section .title {
    background: #f6f9ff;
    color: #012970;
    font-weight: 700;
    padding: .75rem .9rem;
    border-bottom: 1px solid #e9edf5;
}

.storestock-ui .menu-list {
    padding: .5rem .9rem .75rem;
}

.storestock-ui .menu-list a {
    display: flex;
    align-items: center;
    gap: .45rem;
    color: #4154f1;
    padding: .38rem 0;
    font-weight: 600;
    text-decoration: none;
}

.storestock-ui .menu-list a:hover {
    color: #012970;
}

.storestock-ui .search-bar {
    display: grid;
    grid-template-columns: 170px 1fr 300px;
    gap: .7rem;
    align-items: center;
}

.storestock-ui .table-wrap {
    border: 1px solid #ebeef4;
    border-radius: 12px;
    overflow: hidden;
}

.storestock-ui .insight-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(280px, 1fr));
    gap: .9rem;
    margin-top: 1rem;
}

.storestock-ui .mini-badge {
    display: inline-block;
    min-width: 46px;
    text-align: center;
    border-radius: 999px;
    padding: .2rem .45rem;
    font-size: .75rem;
    font-weight: 700;
}

.storestock-ui .mini-badge.warn {
    background: #fff6e5;
    color: #ad6800;
}

.storestock-ui .mini-badge.danger {
    background: #ffe8e8;
    color: #b42318;
}

@media (max-width: 991px) {
    .storestock-ui .stats-grid,
    .storestock-ui .action-grid,
    .storestock-ui .panel-grid,
    .storestock-ui .menu-grid,
    .storestock-ui .search-bar {
        grid-template-columns: 1fr;
    }

    .storestock-ui .insight-grid {
        grid-template-columns: 1fr;
    }
}
</style>