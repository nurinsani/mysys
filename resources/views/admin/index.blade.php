@extends('layouts.main')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Selamat Datang, {{ Auth::user()->name }}</h1>
                <h5 class="mt-3">Tanggal SysDate : {{ Auth::user()->param_tanggal }}</h5>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div><!-- /.container-fluid -->
@endsection

@section('content')
    <div class="card">

        <div class="card-header">
            <h3 class="card-title">Dashboard</h3>
        </div>

        <div class="card-body">

            <div class="container-fluid">
                <!-- Small boxes (Stat box) -->
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>{{ $pembiayaan->noa ?? '0' }}</h3>

                                <p>NoA</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-bag"></i>
                            </div>
                            <a href="#" class="small-box-footer">Detail <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>{{ $pembiayaan->os ?? '0' }}<sup style="font-size: 20px"></sup></h3>
                                <p>Outstanding</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-stats-bars"></i>
                            </div>
                            <a href="#" class="small-box-footer">Detail <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3>{{ $jumlahPenunggak ?? '0' }} NoA</h3>
                                <p>Penunggak</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-person-add"></i>
                            </div>
                            <a href="#" class="small-box-footer">Detail <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>{{ $jumlahKelompok ?? '0' }}</h3>
                                <p>Kelompok</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-pie-graph"></i>
                            </div>
                            <a href="#" class="small-box-footer">Detail <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                </div>

                <div class="row">
                    <div class="col-lg-4 col-6">
                        <!-- small box -->
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3>Rp {{ number_format($totalSimpanan ?? 0, 0, ',', '.') }}</h3>
                                <p>Total Simpanan</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-cash"></i>
                            </div>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-4 col-6">
                        <!-- small box -->
                        <div class="small-box bg-secondary">
                            <div class="inner">
                                <h3>Rp {{ number_format($shuYtd ?? 0, 0, ',', '.') }}</h3>
                                <p>SHU YTD <small>(hasil Hitung SHU terakhir)</small></p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-social-usd"></i>
                            </div>
                            <a href="{{ route('hitung-shu.index') }}" class="small-box-footer">Detail <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-4 col-6">
                        <!-- small box -->
                        <div class="small-box bg-dark">
                            <div class="inner">
                                <h3>{{ $transaksiHariIni->jumlah ?? 0 }} Transaksi</h3>
                                <p>
                                    Hari Ini
                                    &mdash;
                                    Debet Rp {{ number_format($transaksiHariIni->total_debet ?? 0, 0, ',', '.') }} /
                                    Kredit Rp {{ number_format($transaksiHariIni->total_kredit ?? 0, 0, ',', '.') }}
                                </p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-clock"></i>
                            </div>
                        </div>
                    </div>
                    <!-- ./col -->
                </div>
            </div>

        </div>
        <!-- /.card-body -->

    </div>
    <!-- /.card -->
@endsection
