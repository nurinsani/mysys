@extends('layouts.main')

@section('content-header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>{{ $title }}</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                <li class="breadcrumb-item active">{{ $title }}</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <p class="mb-0 text-muted">
                        Log <code>log_name = default</code> mencatat perubahan data lewat model Eloquent (Anggota, Pembiayaan, Simpanan) —
                        transaksi yang ditulis langsung lewat query database mentah tidak tercatat di sana.
                        Log <code>log_name = akses</code> mencatat riwayat login, akses tiap request (menu, AJAX), dan logout tiap user
                        (dipakai untuk evaluasi &amp; KPI pemakaian aplikasi) — dibersihkan otomatis tiap 3 bulan, menyisakan 2 bulan terakhir.
                    </p>
                    <form method="GET" class="form-inline mt-2">
                        <select name="log_name" class="form-control mr-2 mb-1" onchange="this.form.submit()">
                            <option value="">Semua log_name</option>
                            @foreach ($logNames as $name)
                                <option value="{{ $name }}" @selected(request('log_name') === $name)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <select name="event" class="form-control mr-2 mb-1" onchange="this.form.submit()">
                            <option value="">Semua event</option>
                            @foreach ($events as $event)
                                <option value="{{ $event }}" @selected(request('event') === $event)>{{ $event }}</option>
                            @endforeach
                        </select>
                        @if (request('log_name') || request('event'))
                            <a href="{{ route('activity-log.index') }}" class="btn btn-default mb-1">Reset</a>
                        @endif
                    </form>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th style="width: 150px;">Waktu</th>
                                <th>Log</th>
                                <th>Deskripsi</th>
                                <th>Subjek</th>
                                <th>Perubahan</th>
                                <th>Oleh</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td>{{ $log->log_name }}</td>
                                    <td>{{ $log->description }}</td>
                                    <td>
                                        {{ class_basename($log->subject_type) }}
                                        @if ($log->subject_id)
                                            #{{ $log->subject_id }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($log->properties->isNotEmpty())
                                            <details>
                                                <summary>Lihat detail</summary>
                                                <pre style="white-space: pre-wrap; font-size: 11px;">{{ json_encode($log->properties, JSON_PRETTY_PRINT) }}</pre>
                                            </details>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $log->causer->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">Belum ada activity log.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
