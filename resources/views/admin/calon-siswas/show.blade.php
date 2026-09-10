@extends('layouts.app')

@section('title', 'Nexus Admin — Detail Calon Siswa')
@section('breadcrumb', 'Detail Calon Siswa')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        /* Modern CSS Variables */
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);

            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --card-hover-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            --border-radius: 1rem;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Global Styles */
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* Header Section */
        .header-section {
            background: var(--primary-gradient);
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            overflow: hidden;
            position: relative;
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .breadcrumb-modern {
            background: none;
            padding: 0;
            margin: 0;
        }

        .breadcrumb-link {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb-link:hover {
            color: white;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .page-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            margin: 0.5rem 0 0 0;
        }

        /* Status Badge */
        .status-badge {
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .status-badge.status-diterima {
            background: linear-gradient(135deg, #51cf66 0%, #40c057 100%);
            color: white;
        }

        .status-badge.status-ditolak {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
        }

        .status-badge.status-menunggu,
        .status-badge.status-daftar_ulang {
            background: linear-gradient(135deg, #ffd43b 0%, #fab005 100%);
            color: #333;
        }

        .status-badge.status-berhasil {
            background: linear-gradient(135deg, #51cf66 0%, #40c057 100%);
            color: white;
        }

        .status-badge.status-pending {
            background: linear-gradient(135deg, #ffd43b 0%, #fab005 100%);
            color: #333;
        }

        /* Modern Cards */
        .modern-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            overflow: hidden;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }

        .modern-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-hover-shadow);
        }

        /* Card Headers */
        .card-header-primary {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1.5rem;
        }

        .card-header-success {
            background: var(--success-gradient);
            color: white;
            border: none;
            padding: 1.5rem;
        }

        .card-header-warning {
            background: var(--warning-gradient);
            color: white;
            border: none;
            padding: 1.5rem;
        }

        .card-header-info {
            background: var(--info-gradient);
            color: #333;
            border: none;
            padding: 1.5rem;
        }

        .card-header-dark {
            background: var(--dark-gradient);
            color: white;
            border: none;
            padding: 1.5rem;
        }

        .card-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            margin: 0;
            font-weight: 600;
            font-size: 1.25rem;
        }

        .card-actions {
            display: flex;
            gap: 0.5rem;
        }

        /* Info Sections */
        .info-section {
            height: 100%;
        }

        .section-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }

        .info-grid {
            display: grid;
            gap: 1.25rem;
        }

        .info-item {
            padding: 1rem;
            border-radius: 0.75rem;
            background: #f8f9fa;
            transition: var(--transition);
            border-left: 4px solid transparent;
        }

        .info-item:hover {
            background: #e9ecef;
            border-left-color: #667eea;
            transform: translateX(5px);
        }

        .info-label {
            font-size: 0.875rem;
            color: #6c757d;
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
        }

        .info-value {
            font-weight: 600;
            color: #212529;
            font-size: 1rem;
        }

        .info-value.primary {
            font-size: 1.25rem;
            color: #667eea;
        }

        .contact-link {
            color: inherit;
            text-decoration: none;
            transition: var(--transition);
        }

        .contact-link:hover {
            color: #667eea;
        }

        /* Parent Cards */
        .parent-card {
            background: #f8f9fa;
            border-radius: var(--border-radius);
            overflow: hidden;
            height: 100%;
            transition: var(--transition);
        }

        .parent-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .parent-father .parent-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .parent-mother .parent-header {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        .parent-header {
            padding: 1rem 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .parent-info {
            padding: 1.5rem;
        }

        /* Document List */
        .document-list {
            padding: 0;
        }

        .document-item {
            display: flex;
            align-items: center;
            padding: 1.25rem 1.5rem;
            text-decoration: none;
            color: inherit;
            transition: var(--transition);
            border-bottom: 1px solid #e9ecef;
        }

        .document-item:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }

        .document-item:last-child {
            border-bottom: none;
        }

        .document-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: white;
            font-size: 1.25rem;
        }

        .document-info {
            flex: 1;
        }

        .document-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .document-desc {
            font-size: 0.875rem;
            color: #6c757d;
        }

        .document-action {
            color: #667eea;
            font-size: 1.25rem;
        }

        /* Payment History */
        .payment-history {
            padding: 0;
        }

        .payment-item {
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            gap: 1rem;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            transition: var(--transition);
        }

        .payment-item:hover {
            background: #f8f9fa;
        }

        .payment-item:last-child {
            border-bottom: none;
        }

        .payment-date {
            text-align: center;
        }

        .date-main {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .date-code {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .payment-details {
            flex: 1;
        }

        .payment-type {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .payment-method,
        .payment-note {
            font-size: 0.875rem;
            color: #6c757d;
        }

        .payment-amount {
            text-align: right;
        }

        .amount {
            font-weight: 700;
            color: #51cf66;
            margin-bottom: 0.25rem;
        }

        /* Payment Summary */
        .payment-summary {
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .payment-summary-pending {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #ffc107;
        }

        .payment-summary-complete {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            border: 2px solid #17a2b8;
        }

        .summary-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .summary-title {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .payment-summary-pending .summary-title {
            color: #856404;
        }

        .payment-summary-complete .summary-title {
            color: #0c5460;
        }

        .summary-amount {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .payment-summary-pending .summary-amount {
            color: #856404;
        }

        .payment-summary-complete .summary-amount {
            color: #0c5460;
        }

        .summary-detail {
            font-size: 0.875rem;
            opacity: 0.8;
        }

        /* Installment Info */
        .installment-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 2px solid #2196f3;
        }

        .installment-title,
        .installment-list-title {
            color: #1976d2;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .installment-item {
            text-align: center;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 0.5rem;
        }

        .installment-item label {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
            display: block;
        }

        .installment-item .value {
            font-weight: 700;
            color: #1976d2;
        }

        .installment-list {
            background: rgba(255, 255, 255, 0.7);
            border-radius: 0.75rem;
            overflow: hidden;
        }

        .installment-row {
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            gap: 1rem;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(25, 118, 210, 0.1);
        }

        .installment-row:last-child {
            border-bottom: none;
        }

        .installment-number {
            width: 2rem;
            height: 2rem;
            background: #1976d2;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .installment-date {
            font-size: 0.875rem;
            color: #6c757d;
        }

        .installment-amount {
            font-weight: 600;
            color: #1976d2;
        }

        /* Fee Items */
        .unpaid-title {
            color: #dc3545;
            font-weight: 600;
            margin-bottom: 1rem;
            margin-top: 2rem;
        }

        .unpaid-fees {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        }

        .fee-item {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: var(--border-radius);
            padding: 1.25rem;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }

        .fee-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 193, 7, 0.2);
        }

        .fee-info {
            margin-bottom: 1rem;
            flex: 1;
        }

        .fee-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

        .fee-name {
            margin: 0;
            font-weight: 600;
            color: #856404;
        }

        .fee-badges {
            display: flex;
            gap: 0.5rem;
        }

        .fee-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: #856404;
            margin-bottom: 0.5rem;
        }

        .fee-desc {
            font-size: 0.875rem;
            color: #6c757d;
        }

        .fee-action {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        /* Badges */
        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success {
            background: linear-gradient(135deg, #51cf66 0%, #40c057 100%);
            color: white;
        }

        .badge-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
        }

        .badge-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        /* Alerts */
        .alert-modern {
            border: none;
            border-radius: var(--border-radius);
            padding: 1.25rem 1.5rem;
            padding-right: 3rem;
            position: relative;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .alert-modern .btn-close {
            width: 1em;
            height: 1em;
            padding: 0.25rem;
            background-color: transparent;
            font-size: 0.875rem;
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
        }

        .alert-content {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .alert-icon {
            font-size: 1.25rem;
            margin-top: 0.125rem;
        }

        .alert-text {
            flex: 1;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
        }

        .empty-icon {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }

        .empty-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .empty-text {
            color: #6c757d;
            margin: 0;
        }

        /* Buttons */
        .btn {
            border-radius: 0.75rem;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: var(--transition);
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffd43b 0%, #fab005 100%);
            color: #333;
            box-shadow: 0 4px 12px rgba(255, 212, 59, 0.3);
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 212, 59, 0.4);
        }

        .btn-outline-light {
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .btn-outline-light:hover {
            border-color: white;
            background: white;
            color: #333;
        }

        .btn-outline-primary {
            border: 2px solid #667eea;
            color: #667eea;
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: #667eea;
            color: white;
        }

        /* Forms */
        .form-control,
        .form-select {
            border: 2px solid #e9ecef;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            transition: var(--transition);
            background: #fff;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: #fff;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
        }

        .input-group-text {
            border: 2px solid #e9ecef;
            border-right: none;
            background: #667eea;
            color: white;
            font-weight: 600;
            border-radius: 0.75rem 0 0 0.75rem;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 0.75rem 0.75rem 0;
        }

        /* Modal */
        .modal-content {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1.5rem;
        }

        .modal-title {
            font-weight: 600;
            margin: 0;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            background: #f8f9fa;
            border: none;
            padding: 1.5rem 2rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .card-header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .summary-content {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .fee-header {
                flex-direction: column;
                gap: 0.5rem;
            }

            .payment-item {
                grid-template-columns: 1fr;
                gap: 0.5rem;
                text-align: center;
            }

            .installment-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
                text-align: center;
            }

            .document-item {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .modal-body {
                padding: 1rem;
            }

            .btn-lg {
                padding: 1rem 2rem;
                font-size: 1.1rem;
            }
        }

        /* Animation Classes */
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn 0.6s ease forwards;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .slide-in-left {
            opacity: 0;
            transform: translateX(-20px);
            animation: slideInLeft 0.6s ease forwards;
        }

        @keyframes slideInLeft {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .slide-in-right {
            opacity: 0;
            transform: translateX(20px);
            animation: slideInRight 0.6s ease forwards;
        }

        @keyframes slideInRight {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Utility Classes */
        .text-gradient {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .glass-effect {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.9);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        /* Loading Animation */
        .loading {
            position: relative;
            overflow: hidden;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% {
                left: -100%;
            }

            100% {
                left: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <!-- Modern Header Section -->
    <div class="header-section">
        <div class="container-fluid px-4">
            <div class="header-content">
                <div class="header-info">
                    <nav aria-label="breadcrumb" class="mb-3">
                        <ol class="breadcrumb breadcrumb-modern">
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.calon-siswas.index') }}" class="breadcrumb-link">
                                    <i class="bi bi-house-door me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.calon-siswas.index') }}" class="breadcrumb-link">
                                    Data Pendaftaran
                                </a>
                            </li>
                            <li class="breadcrumb-item active">Detail Pendaftaran</li>
                        </ol>
                    </nav>
                    <h1 class="page-title">
                        <i class="bi bi-person-badge me-3"></i>Detail Pendaftaran
                    </h1>
                    <p class="page-subtitle">{{ $calon->nama_lengkap }} • {{ $calon->no_pendaftaran }}</p>
                </div>
                <div class="header-actions d-flex align-items-center gap-2 flex-wrap">
                    <span class="status-badge status-{{ $calon->status_pendaftaran }}">
                        <i class="bi {{ $calon->status_pendaftaran === 'diterima' ? 'bi-check-circle' : ($calon->status_pendaftaran === 'ditolak' ? 'bi-x-circle' : 'bi-clock') }}"></i>
                        {{ ucfirst(str_replace('_', ' ', $calon->status_pendaftaran)) }}
                    </span>
                    <a href="{{ route('admin.calon-siswas.index') }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                    @can('calon-siswas.edit')
                        <a href="{{ route('admin.calon-siswas.edit', $calon) }}" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-pencil me-1"></i>Ubah
                        </a>
                    @endcan
                    <button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    @if (session('success'))
        <div class="alert alert-success alert-modern alert-dismissible" role="alert">
            <div class="alert-content">
                <i class="bi bi-check-circle-fill alert-icon"></i>
                <div class="alert-text">{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-modern alert-dismissible" role="alert">
            <div class="alert-content">
                <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                <div class="alert-text">{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-modern alert-dismissible" role="alert">
            <div class="alert-content">
                <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                <div class="alert-text">
                    <strong>Terjadi kesalahan:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <!-- Student Data Card -->
            <div class="card modern-card">
                <div class="card-header card-header-primary">
                    <div class="card-header-content">
                        <h5 class="card-title">
                            <i class="bi bi-person-fill me-2"></i>Data Pendaftar
                        </h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <!-- Personal Info -->
                        <div class="col-md-6">
                            <div class="info-section">
                                <h6 class="section-title">
                                    <i class="bi bi-person-vcard me-2"></i>Informasi Pribadi
                                </h6>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label class="info-label">No. Pendaftaran</label>
                                        <div class="info-value">{{ $calon->no_pendaftaran }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">NIK</label>
                                        <div class="info-value">{{ $calon->nik }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">NISN</label>
                                        <div class="info-value">{{ $calon->nisn ?? '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Nama Lengkap</label>
                                        <div class="info-value primary">{{ $calon->nama_lengkap }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Jenis Kelamin</label>
                                        <div class="info-value">
                                            <i class="bi {{ $calon->jenis_kelamin === 'L' ? 'bi-gender-male text-primary' : 'bi-gender-female text-danger' }} me-2"></i>
                                            {{ $calon->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Tempat, Tanggal Lahir</label>
                                        <div class="info-value">
                                            <i class="bi bi-calendar-event text-info me-2"></i>
                                            {{ $calon->tempat_lahir }},
                                            {{ \Carbon\Carbon::parse($calon->tanggal_lahir)->format('d F Y') }}
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Agama</label>
                                        <div class="info-value">{{ $calon->agama }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Contact & Education Info -->
                        <div class="col-md-6">
                            <div class="info-section">
                                <h6 class="section-title">
                                    <i class="bi bi-geo-alt me-2"></i>Kontak & Pendidikan
                                </h6>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label class="info-label">Alamat</label>
                                        <div class="info-value">{{ $calon->alamat }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">No. HP</label>
                                        <div class="info-value">
                                            <i class="bi bi-telephone text-success me-2"></i>
                                            <a href="tel:{{ $calon->no_hp }}" class="contact-link">{{ $calon->no_hp ?? '-' }}</a>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Email</label>
                                        <div class="info-value">
                                            <i class="bi bi-envelope text-info me-2"></i>
                                            <a href="mailto:{{ $calon->email }}" class="contact-link">{{ $calon->email ?? '-' }}</a>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Asal Sekolah</label>
                                        <div class="info-value">
                                            <i class="bi bi-building text-warning me-2"></i>
                                            {{ $calon->asal_sekolah ?? '-' }}
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Tahun Ajaran</label>
                                        <div class="info-value">{{ $calon->tahunAjaran->nama_tahun_ajaran ?? '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Jalur Pendaftaran</label>
                                        <div class="info-value">
                                            @if ($calon->jalurPendaftaran)
                                                {{ $calon->jalurPendaftaran->nama_jalur }}
                                                <span class="badge badge-{{ $calon->jalurPendaftaran->aktif ? 'success' : 'danger' }}">
                                                    {{ $calon->jalurPendaftaran->aktif ? 'Aktif' : 'Tidak Aktif' }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Parent Data Card -->
            <div class="card modern-card">
                <div class="card-header card-header-success">
                    <h5 class="card-title">
                        <i class="bi bi-people-fill me-2"></i>Data Orang Tua
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="parent-card parent-father">
                                <div class="parent-header">
                                    <i class="bi bi-person-fill-gear me-2"></i>Data Ayah
                                </div>
                                <div class="parent-info">
                                    <div class="info-item">
                                        <label class="info-label">Nama Ayah</label>
                                        <div class="info-value">{{ $calon->nama_ayah ?? '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Pekerjaan</label>
                                        <div class="info-value">
                                            <i class="bi bi-briefcase text-primary me-2"></i>
                                            {{ $calon->pekerjaan_ayah ?? '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="parent-card parent-mother">
                                <div class="parent-header">
                                    <i class="bi bi-person-heart me-2"></i>Data Ibu
                                </div>
                                <div class="parent-info">
                                    <div class="info-item">
                                        <label class="info-label">Nama Ibu</label>
                                        <div class="info-value">{{ $calon->nama_ibu ?? '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Pekerjaan</label>
                                        <div class="info-value">
                                            <i class="bi bi-briefcase text-danger me-2"></i>
                                            {{ $calon->pekerjaan_ibu ?? '-' }}
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">No. HP Orang Tua</label>
                                        <div class="info-value">
                                            <i class="bi bi-telephone text-success me-2"></i>
                                            <a href="tel:{{ $calon->no_hp_orang_tua }}" class="contact-link">{{ $calon->no_hp_orang_tua ?? '-' }}</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status & Log Card -->
            <div class="card modern-card">
                <div class="card-header card-header-primary">
                    <h5 class="card-title">
                        <i class="bi bi-clipboard-data me-2"></i>Status Pendaftaran
                    </h5>
                </div>
                <div class="card-body">
                    @can('calon-siswas.edit')
                        <form method="POST" action="{{ route('admin.calon-siswas.status', $calon) }}" id="form-status">
                            @csrf @method('PATCH')
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="status_pendaftaran" class="form-label">
                                        <i class="bi bi-clipboard-data text-primary me-1"></i>Status Pendaftaran
                                    </label>
                                    <select class="form-select" id="status_pendaftaran" name="status" required>
                                        <option value="">Pilih keputusan...</option>
                                        <option value="menunggu" @selected($calon->status_pendaftaran === 'menunggu')>⏳ Menunggu</option>
                                        <option value="diterima" @selected($calon->status_pendaftaran === 'diterima')>✅ Diterima</option>
                                        <option value="ditolak" @selected($calon->status_pendaftaran === 'ditolak')>❌ Ditolak</option>
                                        <option value="daftar_ulang" @selected($calon->status_pendaftaran === 'daftar_ulang')>📝 Daftar Ulang</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="catatan_status" class="form-label">
                                        <i class="bi bi-chat-text text-info me-1"></i>Catatan (opsional)
                                    </label>
                                    <textarea class="form-control" id="catatan_status" name="catatan" rows="3" placeholder="Berikan catatan untuk keputusan ini..."></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="bi bi-check-circle me-2"></i>Update Status Pendaftaran
                                    </button>
                                </div>
                            </div>
                        </form>
                    @else
                        <p style="font-size: 14px;">{{ ucfirst(str_replace('_', ' ', $calon->status_pendaftaran)) }}</p>
                    @endcan

                    <h6 class="mt-4 mb-3" style="font-weight:600;color:#495057;">Riwayat Status</h6>
                    <div class="d-flex flex-column gap-2">
                        @forelse ($calon->logStatusPendaftaran->sortByDesc('created_at') as $log)
                            <div style="font-size: 12px; border-left: 3px solid #667eea; padding-left: 10px;">
                                <div><strong>{{ $log->status_sebelumnya ?? '—' }} → {{ $log->status_baru }}</strong> • {{ $log->created_at->format('d M Y H:i') }}</div>
                                <div style="color: #6c757d;">oleh {{ $log->user->name ?? 'sistem' }} @if ($log->catatan) — {{ $log->catatan }} @endif</div>
                            </div>
                        @empty
                            <span style="font-size: 12px; color: #6c757d;">Belum ada log.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <!-- Documents Card -->
            <div class="card modern-card">
                <div class="card-header card-header-info">
                    <h5 class="card-title">
                        <i class="bi bi-folder2-open me-2"></i>Berkas Pendaftaran
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if ($calon->berkasCalonSiswa)
                        @php $b = $calon->berkasCalonSiswa; @endphp
                        <div class="document-list">
                            @foreach (['foto_path' => ['Pas Foto', 'Foto 3x4', 'bg-primary', 'bi-camera-fill'], 'ijazah_path' => ['Ijazah', 'Ijazah/STTB', 'bg-success', 'bi-award-fill'], 'kk_path' => ['Kartu Keluarga', 'KK Asli', 'bg-warning', 'bi-people-fill'], 'akta_path' => ['Akta Kelahiran', 'Akta Asli', 'bg-info', 'bi-file-earmark-text-fill'], 'skl_path' => ['Surat Keterangan Lulus', 'SKL Asli', 'bg-secondary', 'bi-mortarboard-fill'], 'krm_path' => ['KRM', 'Kartu Indonesia Pintar (opsional)', 'bg-danger', 'bi-card-text-fill'], 'kip_path' => ['KIP', 'Kartu Indonesia Pintar (opsional)', 'bg-info', 'bi-wallet-fill']] as $field => [$label, $desc, $bg, $icon])
                                @if ($b->$field)
                                    <a href="{{ Storage::disk('public')->url($b->$field) }}" target="_blank" class="document-item">
                                        <div class="document-icon {{ $bg }}">
                                            <i class="bi {{ $icon }}"></i>
                                        </div>
                                        <div class="document-info">
                                            <div class="document-name">{{ $label }}</div>
                                            <div class="document-desc">{{ $desc }}</div>
                                        </div>
                                        <div class="document-action">
                                            <i class="bi bi-eye-fill"></i>
                                        </div>
                                    </a>
                                @endif
                            @endforeach
                            @foreach ($calon->sertifikatPrestasis as $s)
                                <a href="{{ Storage::disk('public')->url($s->file_path) }}" target="_blank" class="document-item">
                                    <div class="document-icon bg-warning">
                                        <i class="bi bi-trophy-fill"></i>
                                    </div>
                                    <div class="document-info">
                                        <div class="document-name">{{ $s->nama_sertifikat }}</div>
                                        <div class="document-desc">Sertifikat Prestasi</div>
                                    </div>
                                    <div class="document-action">
                                        <i class="bi bi-eye-fill"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        <div class="px-4 py-3" style="font-size: 12px; color: #6c757d; border-top: 1px solid #e9ecef;">
                            Verifikasi: {{ $b->status_verifikasi ? 'Terverifikasi' : 'Belum' }}
                            @if ($b->alasan_penolakan) • Alasan: {{ $b->alasan_penolakan }} @endif
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="bi bi-folder2-open empty-icon"></i>
                            <p class="empty-text">Belum ada berkas yang diupload</p>
                        </div>
                    @endif

                    @can('berkas-calon-siswas.edit')
                        <div class="p-4" style="border-top: 1px solid #e9ecef;">
                            <form method="POST" action="{{ route('admin.calon-siswas.berkas', $calon) }}" class="d-flex flex-column gap-3" enctype="multipart/form-data">
                                @csrf @method('PATCH')
                                <div class="form-check">
                                    <input type="hidden" name="status_verifikasi" value="0" />
                                    <input type="checkbox" name="status_verifikasi" value="1" id="status_verifikasi" class="form-check-input" @checked(old('status_verifikasi', $calon->berkasCalonSiswa->status_verifikasi ?? false)) />
                                    <label class="form-check-label" for="status_verifikasi">Terverifikasi</label>
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:13px;">Ganti Berkas (kosongkan bila tidak diubah)</div>
                                    <div style="font-size:11px;color:#6c757d;">PDF 5MB, Foto JPG/PNG 2MB. File lama dihapus bila diganti.</div>
                                </div>
                                <div class="row g-2">
                                    @foreach (['ijazah_path' => 'Ijazah', 'kk_path' => 'KK', 'akta_path' => 'Akta', 'foto_path' => 'Foto', 'skl_path' => 'SKL', 'krm_path' => 'KRM', 'kip_path' => 'KIP'] as $fld => $lbl)
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label" style="font-size:12px;" for="berkas-{{ $fld }}">{{ $lbl }}</label>
                                            <input type="file" name="{{ $fld }}" id="berkas-{{ $fld }}" class="form-control form-control-sm @error($fld) is-invalid @enderror" />
                                            @error($fld)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                    @endforeach
                                </div>
                                <div>
                                    <label class="form-label" style="font-size: 12px;">Berkas perlu perbaikan</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach (['ijazah_path', 'kk_path', 'akta_path', 'foto_path', 'skl_path', 'krm_path', 'kip_path', 'sertifikat'] as $f)
                                            <div class="form-check">
                                                <input type="checkbox" name="berkas_perlu_perbaikan[]" value="{{ $f }}" id="perlu-{{ $f }}" class="form-check-input" @checked(in_array($f, old('berkas_perlu_perbaikan', $calon->berkasCalonSiswa->berkas_perlu_perbaikan ?? []))) />
                                                <label class="form-check-label" style="font-size: 12px;" for="perlu-{{ $f }}">{{ $f }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="form-floating">
                                    <textarea name="alasan_penolakan" id="alasan_penolakan" class="form-control" placeholder="Alasan" style="height: 70px">{{ old('alasan_penolakan', $calon->berkasCalonSiswa->alasan_penolakan ?? '') }}</textarea>
                                    <label for="alasan_penolakan">Alasan Penolakan</label>
                                </div>
                                <div class="form-floating">
                                    <textarea name="catatan_berkas" id="catatan_berkas" class="form-control" placeholder="Catatan" style="height: 70px">{{ old('catatan_berkas', $calon->berkasCalonSiswa->catatan_berkas ?? '') }}</textarea>
                                    <label for="catatan_berkas">Catatan Berkas</label>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Simpan Verifikasi</button>
                            </form>
                        </div>
                    @endcan
                </div>
            </div>

            <!-- Payment History Card -->
            <div class="card modern-card">
                <div class="card-header card-header-warning">
                    <h5 class="card-title">
                        <i class="bi bi-clock-history me-2"></i>Riwayat Pembayaran
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        @if ($calon->pembayaran->count() > 0)
                            <div class="payment-history">
                                @foreach ($calon->pembayaran as $pembayaran)
                                    <div class="payment-item">
                                        <div class="payment-date">
                                            <div class="date-main">
                                                {{ $pembayaran->tanggal_pembayaran ? \Carbon\Carbon::parse($pembayaran->tanggal_pembayaran)->format('d/m/Y') : '-' }}
                                            </div>
                                            <div class="date-code">{{ $pembayaran->kode_pembayaran }}</div>
                                        </div>
                                        <div class="payment-details">
                                            <div class="payment-type">{{ $pembayaran->biayaPendaftaran->jenis_biaya ?? '-' }}</div>
                                            <div class="payment-method">{{ ucfirst(str_replace('_', ' ', $pembayaran->jenis_pembayaran)) }}</div>
                                            @if ($pembayaran->keterangan_angsuran)
                                                <div class="payment-note">{{ $pembayaran->keterangan_angsuran }}</div>
                                            @endif
                                        </div>
                                        <div class="payment-amount">
                                            <div class="amount">Rp {{ number_format($pembayaran->jumlah, 0, ',', '.') }}</div>
                                            <div class="status-badge status-{{ $pembayaran->status === 'menunggu' ? 'pending' : $pembayaran->status }}">
                                                <i class="bi {{ $pembayaran->status === 'berhasil' ? 'bi-check-circle' : ($pembayaran->status === 'menunggu' ? 'bi-clock' : 'bi-x-circle') }}"></i>
                                                {{ ucfirst($pembayaran->status) }}
                                            </div>
                                        </div>
                                        <div class="payment-proof">
                                            @if ($pembayaran->bukti_pembayaran_path)
                                                <a href="{{ Storage::disk('public')->url($pembayaran->bukti_pembayaran_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="empty-state">
                                <i class="bi bi-receipt empty-icon"></i>
                                <p class="empty-text">Belum ada riwayat pembayaran</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Fee Details Card -->
            <div class="card modern-card">
                <div class="card-header card-header-dark">
                    <div class="card-header-content">
                        <h5 class="card-title">
                            <i class="bi bi-currency-dollar me-2"></i>Rincian Biaya
                        </h5>
                        @can('pembayarans.create')
                            <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalPembayaran">
                                <i class="bi bi-plus-circle me-1"></i>Tambah
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @php
                        $totalBiaya = 0;
                        $totalBayar = 0;
                        $biayaBelumLunas = [];

                        foreach ($calon->tahunAjaran->biayaPendaftaran as $biaya) {
                            $totalBiaya += $biaya->jumlah;
                            $totalPembayaranBiaya = $calon->pembayaran
                                ->where('biaya_pendaftaran_id', $biaya->id)
                                ->where('status', 'berhasil')
                                ->sum('jumlah');

                            $totalBayar += $totalPembayaranBiaya;

                            if ($totalPembayaranBiaya < $biaya->jumlah) {
                                $tagihanMenunggu = $calon->pembayaran
                                    ->where('biaya_pendaftaran_id', $biaya->id)
                                    ->where('status', 'menunggu')
                                    ->first();

                                $biayaBelumLunas[] = [
                                    'id' => $biaya->id,
                                    'jenis' => $biaya->jenis_biaya,
                                    'jumlah' => $biaya->jumlah,
                                    'terbayar' => $totalPembayaranBiaya,
                                    'sisa' => $biaya->jumlah - $totalPembayaranBiaya,
                                    'wajib' => $biaya->wajib_bayar,
                                    'mata_uang' => $biaya->mata_uang,
                                    'keterangan' => $biaya->keterangan,
                                    'dapat_diangsur' => $biaya->dapat_diangsur,
                                    'tagihan_id' => $tagihanMenunggu?->id,
                                ];
                            }
                        }

                        $totalBelumBayar = $totalBiaya - $totalBayar;
                    @endphp

                    <!-- Payment Summary -->
                    <div class="payment-summary payment-summary-{{ $totalBelumBayar > 0 ? 'pending' : 'complete' }}">
                        <div class="summary-content">
                            <div class="summary-info">
                                <div class="summary-title">
                                    <i class="bi {{ $totalBelumBayar > 0 ? 'bi-exclamation-triangle' : 'bi-check-circle' }}"></i>
                                    {{ $totalBelumBayar > 0 ? 'Sisa Pembayaran' : 'Lunas' }}
                                </div>
                                <div class="summary-amount">Rp {{ number_format($totalBelumBayar, 0, ',', '.') }}</div>
                                <div class="summary-detail">
                                    Total: Rp {{ number_format($totalBiaya, 0, ',', '.') }} •
                                    Terbayar: Rp {{ number_format($totalBayar, 0, ',', '.') }}
                                </div>
                            </div>
                            @if ($totalBelumBayar > 0)
                                @can('pembayarans.create')
                                    <button type="button" class="btn btn-warning btn-lg" data-bs-toggle="modal" data-bs-target="#modalPembayaran">
                                        <i class="bi bi-credit-card me-2"></i>Bayar
                                    </button>
                                @endcan
                            @endif
                        </div>
                    </div>

                    <!-- Installment Info -->
                    @foreach ($calon->rencanaAngsuran as $rencana)
                        <div class="installment-info">
                            <h6 class="installment-title">
                                <i class="bi bi-calendar-check me-2"></i>Angsuran {{ $rencana->kode_angsuran }} ({{ $rencana->status }})
                            </h6>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="installment-item">
                                        <label>Total Biaya</label>
                                        <div class="value">Rp {{ number_format($rencana->total_biaya, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="installment-item">
                                        <label>DP Dibayar</label>
                                        <div class="value">Rp {{ number_format($rencana->dp_dibayar, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="installment-item">
                                        <label>Sisa Hutang</label>
                                        <div class="value">Rp {{ number_format($rencana->sisa_hutang, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="installment-item">
                                        <label>Jumlah Cicilan</label>
                                        <div class="value">{{ $rencana->jumlah_cicilan }}x</div>
                                    </div>
                                </div>
                            </div>

                            <h6 class="installment-list-title">
                                <i class="bi bi-list-ol me-2"></i>Daftar Cicilan
                            </h6>
                            <div class="installment-list">
                                <div class="table-responsive">
                                    @foreach ($rencana->detailAngsuran->sortBy('cicilan_ke') as $angsuran)
                                        <div class="installment-row">
                                            <div class="installment-number">{{ $angsuran->cicilan_ke }}</div>
                                            <div class="installment-details">
                                                <div class="installment-date">
                                                    {{ \Carbon\Carbon::parse($angsuran->tanggal_jatuh_tempo)->format('d/m/Y') }}
                                                </div>
                                                <div class="installment-amount">Rp {{ number_format($angsuran->nominal_cicilan, 0, ',', '.') }}</div>
                                            </div>
                                            <div class="installment-status">
                                                <span class="status-badge status-{{ $angsuran->status === 'dibayar' ? 'berhasil' : 'pending' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $angsuran->status)) }}
                                                </span>
                                            </div>
                                            <div class="installment-action">
                                                @if ($rencana->pembayaran_id)
                                                    <a href="{{ route('admin.pembayarans.show', $rencana->pembayaran_id) }}" class="btn btn-sm btn-primary">Kelola</a>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @if (count($biayaBelumLunas) > 0)
                        <h6 class="unpaid-title">
                            <i class="bi bi-list-ul me-2"></i>Biaya yang Belum Dibayar
                        </h6>
                        <div class="unpaid-fees">
                            @foreach ($biayaBelumLunas as $biaya)
                                <div class="fee-item">
                                    <div class="fee-info">
                                        <div class="fee-header">
                                            <h6 class="fee-name">{{ $biaya['jenis'] }}</h6>
                                            <div class="fee-badges">
                                                @if ($biaya['wajib'])
                                                    <span class="badge badge-danger">Wajib</span>
                                                @endif
                                                @if ($biaya['dapat_diangsur'])
                                                    <span class="badge badge-info">Dapat Diangsur</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="fee-amount">{{ $biaya['mata_uang'] }} {{ number_format($biaya['sisa'], 0, ',', '.') }}</div>
                                        @if ($biaya['keterangan'])
                                            <div class="fee-desc">{{ $biaya['keterangan'] }}</div>
                                        @endif
                                        @if ($biaya['terbayar'] > 0)
                                            <div class="fee-desc">Terbayar: {{ $biaya['mata_uang'] }} {{ number_format($biaya['terbayar'], 0, ',', '.') }}</div>
                                        @endif
                                    </div>
                                    <div class="fee-action">
                                        @can('pembayarans.create')
                                            <button type="button" class="btn btn-primary"
                                                onclick="setBiayaId('{{ $biaya['id'] }}', '{{ $biaya['sisa'] }}', '{{ $biaya['mata_uang'] }}', '{{ $biaya['dapat_diangsur'] ? 1 : 0 }}')"
                                                data-bs-toggle="modal" data-bs-target="#modalPembayaran">
                                                <i class="bi bi-credit-card me-1"></i>Bayar
                                            </button>
                                            @if ($biaya['dapat_diangsur'] && $biaya['tagihan_id'])
                                                <a href="{{ route('admin.pembayarans.show', $biaya['tagihan_id']) }}" class="btn btn-outline-primary">
                                                    <i class="bi bi-calendar-range me-1"></i>Angsuran
                                                </a>
                                            @endif
                                        @endcan
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="bi bi-check-circle-fill empty-icon text-success"></i>
                            <h5 class="empty-title">Pembayaran Lunas!</h5>
                            <p class="empty-text">Semua biaya pendaftaran sudah dibayar</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Pembayaran Lainnya -->
            <div class="card modern-card mt-4">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="bi bi-receipt me-2"></i>Pembayaran Lainnya
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.pembayaran-lainnyas.index', ['search' => $calon->no_pendaftaran]) }}" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-list-ul me-1"></i>Lihat Semua
                        </a>
                        @can('pembayaran-lainnyas.create')
                            <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalPembayaranLainnya">
                                <i class="bi bi-plus-circle me-1"></i>Tambah
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @if ($calon->pembayaranLainnya->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table-nexus w-100" style="font-size: 13px;">
                                <thead><tr><th>Kode</th><th>Nama Biaya</th><th>Jumlah</th><th>Status</th><th>Bukti</th><th>Aksi</th></tr></thead>
                                <tbody>
                                    @foreach ($calon->pembayaranLainnya as $lain)
                                        <tr>
                                            <td style="font-size:12px;">{{ $lain->kode_pembayaran }}</td>
                                            <td>{{ $lain->nama_biaya }}</td>
                                            <td>Rp {{ number_format($lain->jumlah, 0, ',', '.') }}</td>
                                            <td>
                                                @php $badgeLain = $lain->status === 'berhasil' ? 'badge-info' : ($lain->status === 'gagal' ? 'badge-danger' : 'badge-neutral'); @endphp
                                                <span class="badge-nexus {{ $badgeLain }}">{{ $lain->status }}</span>
                                            </td>
                                            <td>
                                                @if($lain->bukti_pembayaran_path)
                                                    <a href="{{ Storage::disk('public')->url($lain->bukti_pembayaran_path) }}" target="_blank" class="text-primary">Lihat</a>
                                                @else — @endif
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="{{ route('admin.pembayaran-lainnyas.show', $lain) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-eye"></i></a>
                                                    @if($lain->status !== 'berhasil')
                                                        <form action="{{ route('admin.pembayaran-lainnyas.status', $lain) }}" method="POST" class="d-inline">
                                                            @csrf @method('PATCH')
                                                            <input type="hidden" name="status" value="berhasil" />
                                                            <button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-success" title="Verifikasi berhasil"><i class="fa-solid fa-check"></i></button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="empty-text">Belum ada pembayaran lainnya untuk calon ini.</p>
                    @endif
                </div>
            </div>

            <!-- Payment Modal -->
            @can('pembayarans.create')
                <div class="modal fade" id="modalPembayaran" tabindex="-1" aria-labelledby="modalPembayaranLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalPembayaranLabel">
                                    <i class="bi bi-plus-circle me-2"></i>Tambah Pembayaran
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="{{ route('admin.pembayarans.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="calon_siswa_id" value="{{ $calon->id }}" />
                                <input type="hidden" name="jenis_pembayaran" value="penuh" />
                                <input type="hidden" name="redirect_to" value="{{ route('admin.calon-siswas.show', $calon) }}" />
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="selected_biaya_id" class="form-label">
                                            <i class="bi bi-tag text-primary me-1"></i>Biaya <span class="text-danger">*</span>
                                        </label>
                                        @php $sisaMap = collect($biayaBelumLunas ?? [])->keyBy('id'); @endphp
                                        <select class="form-select" id="selected_biaya_id" name="biaya_pendaftaran_id" required>
                                            <option value="">— Pilih Biaya (wajib & non-wajib) —</option>
                                            @foreach ($calon->tahunAjaran->biayaPendaftaran as $biayaOpt)
                                                @php $sisaOpt = $sisaMap[$biayaOpt->id]['sisa'] ?? $biayaOpt->jumlah; @endphp
                                                <option value="{{ $biayaOpt->id }}"
                                                    data-dapat-diangsur="{{ $biayaOpt->dapat_diangsur ? 1 : 0 }}"
                                                    data-sisa="{{ $sisaOpt }}"
                                                    data-mata-uang="{{ $biayaOpt->mata_uang }}">
                                                    {{ $biayaOpt->jenis_biaya }} — {{ $biayaOpt->wajib_bayar ? 'Wajib' : 'Opsional' }} — Sisa Rp {{ number_format($sisaOpt, 0, ',', '.') }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">
                                            <i class="bi bi-info-circle me-1"></i>Pilih termasuk biaya non-wajib (mis. Ekstrakurikuler) untuk pembayaran susulan.
                                        </div>
                                    </div>
                                    <div class="form-check mb-3 d-none" id="angsuran-check-wrap">
                                        <input type="checkbox" name="buat_angsuran" value="1" id="modal_buat_angsuran" class="form-check-input" />
                                        <label class="form-check-label" for="modal_buat_angsuran">Buat sebagai angsuran <span class="text-muted" style="font-size:11px;">(tagihan + DP + jadwal cicilan sekaligus)</span></label>
                                    </div>
                                    <div class="row g-3 mb-3 d-none" id="modal-angsuran-fields">
                                        <div class="col-md-4">
                                            <label for="modal_dp_dibayar" class="form-label">DP Dibayar</label>
                                            <input type="number" step="0.01" class="form-control" id="modal_dp_dibayar" name="dp_dibayar" min="0" />
                                        </div>
                                        <div class="col-md-4">
                                            <label for="modal_jumlah_cicilan" class="form-label">Jumlah Cicilan</label>
                                            <input type="number" class="form-control" id="modal_jumlah_cicilan" name="jumlah_cicilan" min="1" max="60" />
                                        </div>
                                        <div class="col-md-4">
                                            <label for="modal_tanggal_mulai" class="form-label">Mulai Cicilan</label>
                                            <input type="date" class="form-control" id="modal_tanggal_mulai" name="tanggal_mulai" />
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="jumlah" class="form-label">
                                                <i class="bi bi-currency-dollar text-success me-1"></i>Jumlah Pembayaran
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text" id="mata-uang-addon">Rp</span>
                                                <input type="number" class="form-control" id="jumlah" name="jumlah" required placeholder="0" min="0" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="metode_pembayaran" class="form-label">
                                                <i class="bi bi-credit-card text-primary me-1"></i>Metode Pembayaran
                                            </label>
                                            <select class="form-select" id="metode_pembayaran" name="metode_pembayaran" required>
                                                <option value="">Pilih metode pembayaran</option>
                                                <option value="transfer">Transfer Bank</option>
                                                <option value="tunai">Tunai</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="tanggal_pembayaran" class="form-label">
                                                <i class="bi bi-calendar-event text-info me-1"></i>Tanggal Pembayaran
                                            </label>
                                            <input type="date" class="form-control" id="tanggal_pembayaran" name="tanggal_pembayaran" value="{{ date('Y-m-d') }}" />
                                        </div>
                                        <div class="col-md-6">
                                            <label for="bukti_pembayaran" class="form-label">
                                                <i class="bi bi-cloud-upload text-warning me-1"></i>Bukti Pembayaran
                                            </label>
                                            <input type="file" class="form-control" id="bukti_pembayaran" name="bukti_pembayaran_path" accept=".pdf,.jpg,.jpeg,.png" />
                                            <div class="form-text">
                                                <i class="bi bi-info-circle me-1"></i>Format: JPG, PNG, PDF. Maksimal: 5MB. <strong>Wajib</strong> bila transfer, opsional bila tunai.
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label for="catatan" class="form-label">
                                                <i class="bi bi-chat-text text-secondary me-1"></i>Catatan
                                            </label>
                                            <textarea class="form-control" id="catatan" name="catatan" rows="3" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="bi bi-x-lg me-1"></i>Batal
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg me-1"></i>Simpan Pembayaran
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan

            <!-- Modal Pembayaran Lainnya -->
            @can('pembayaran-lainnyas.create')
                <div class="modal fade" id="modalPembayaranLainnya" tabindex="-1" aria-labelledby="modalPembayaranLainnyaLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalPembayaranLainnyaLabel">
                                    <i class="bi bi-plus-circle me-2"></i>Tambah Pembayaran Lainnya
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="{{ route('admin.pembayaran-lainnyas.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="calon_siswa_id" value="{{ $calon->id }}" />
                                <input type="hidden" name="redirect_to" value="{{ route('admin.calon-siswas.show', $calon) }}" />
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="lain_nama_biaya" class="form-label">
                                                <i class="bi bi-tag text-success me-1"></i>Nama Biaya <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="lain_nama_biaya" name="nama_biaya" required placeholder="cth: Denda keterlambatan" />
                                        </div>
                                        <div class="col-md-6">
                                            <label for="lain_jumlah" class="form-label">
                                                <i class="bi bi-currency-dollar text-success me-1"></i>Jumlah <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" class="form-control" id="lain_jumlah" name="jumlah" required placeholder="0" min="0" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="lain_metode" class="form-label">
                                                <i class="bi bi-credit-card text-primary me-1"></i>Metode Pembayaran <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="lain_metode" name="metode_pembayaran" required>
                                                <option value="">Pilih metode pembayaran</option>
                                                <option value="transfer">Transfer Bank</option>
                                                <option value="tunai">Tunai</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="lain_tanggal" class="form-label">
                                                <i class="bi bi-calendar-event text-info me-1"></i>Tanggal Pembayaran
                                            </label>
                                            <input type="date" class="form-control" id="lain_tanggal" name="tanggal_pembayaran" value="{{ date('Y-m-d') }}" />
                                        </div>
                                        <div class="col-md-6">
                                            <label for="lain_bukti" class="form-label">
                                                <i class="bi bi-cloud-upload text-warning me-1"></i>Bukti Pembayaran
                                            </label>
                                            <input type="file" class="form-control" id="lain_bukti" name="bukti_pembayaran_path" accept=".pdf,.jpg,.jpeg,.png" />
                                            <div class="form-text">
                                                <i class="bi bi-info-circle me-1"></i><strong>Wajib</strong> bila transfer, opsional bila tunai.
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="lain_catatan" class="form-label">
                                                <i class="bi bi-chat-text text-secondary me-1"></i>Catatan
                                            </label>
                                            <textarea class="form-control" id="lain_catatan" name="catatan" rows="3" placeholder="Opsional..."></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="bi bi-x-lg me-1"></i>Batal
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg me-1"></i>Simpan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Set biaya ID for payment modal (bayar penuh per biaya, atau angsuran bila bisa)
        function setBiayaId(biayaId, jumlah, mataUang, dapatDiangsur) {
            var select = document.getElementById('selected_biaya_id');
            if (select) {
                select.value = biayaId;
                select.dispatchEvent(new Event('change'));
            }
            document.getElementById('jumlah').value = jumlah;
            document.getElementById('mata-uang-addon').textContent = mataUang;
            syncAngsuranToggle(dapatDiangsur);
        }

        function syncAngsuranToggle(dapatDiangsur) {
            var wrap = document.getElementById('angsuran-check-wrap');
            var check = document.getElementById('modal_buat_angsuran');
            var fields = document.getElementById('modal-angsuran-fields');
            if (wrap && check && fields) {
                var bisa = String(dapatDiangsur) === '1';
                wrap.classList.toggle('d-none', !bisa);
                if (!bisa) {
                    check.checked = false;
                    fields.classList.add('d-none');
                }
            }
        }

        document.getElementById('selected_biaya_id')?.addEventListener('change', function () {
            var opt = this.selectedOptions[0];
            if (opt && opt.dataset.sisa) {
                document.getElementById('jumlah').value = opt.dataset.sisa;
            }
            syncAngsuranToggle(opt ? opt.dataset.dapatDiangsur : '0');
        });

        document.getElementById('modal_buat_angsuran')?.addEventListener('change', function () {
            document.getElementById('modal-angsuran-fields')?.classList.toggle('d-none', !this.checked);
        });

        // Bukti wajib bila metode transfer
        document.getElementById('metode_pembayaran')?.addEventListener('change', function () {
            document.getElementById('bukti_pembayaran').required = this.value === 'transfer';
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Animate cards on load with stagger effect
            const cards = document.querySelectorAll('.modern-card');
            cards.forEach((card, index) => {
                card.classList.add('fade-in');
                card.style.animationDelay = `${index * 0.1}s`;
            });

            // Animate info items
            const infoItems = document.querySelectorAll('.info-item');
            infoItems.forEach((item, index) => {
                item.classList.add('slide-in-left');
                item.style.animationDelay = `${index * 0.05}s`;
            });

            // Animate document items
            const docItems = document.querySelectorAll('.document-item');
            docItems.forEach((item, index) => {
                item.classList.add('slide-in-right');
                item.style.animationDelay = `${index * 0.1}s`;
            });

            // Confirm before changing status
            const statusForm = document.getElementById('form-status');
            if (statusForm) {
                statusForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const status = document.getElementById('status_pendaftaran').value;
                    const studentName = document.querySelector('.page-subtitle').textContent.split(' • ')[0];
                    const confirmMessage = status === 'diterima'
                        ? `Apakah Anda yakin ingin MENERIMA ${studentName}?`
                        : `Apakah Anda yakin ingin mengubah status ${studentName} menjadi ${status}?`;
                    if (confirm(confirmMessage)) {
                        const submitBtn = this.querySelector('button[type="submit"]');
                        submitBtn.innerHTML = 'Memproses...';
                        submitBtn.disabled = true;
                        this.submit();
                    }
                });
            }

            // Initialize Bootstrap tooltips if available
            if (typeof bootstrap !== 'undefined') {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        });
    </script>
@endpush
