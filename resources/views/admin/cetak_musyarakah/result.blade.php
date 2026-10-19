@extends('layouts.main')

@section('content-header')
<div class="container-fluid">
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1>Data Cetak Musyarakah</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <a href="{{ route('cetak_musyarakah') }}" class="btn btn-secondary">
          <i class="fas fa-arrow-left mr-1"></i> Kembali ke Pencarian
        </a>
      </div>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
  <div class="card shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
      <h6 class="font-weight-bold mb-0 text-dark">
        <i class="fas fa-table mr-1 text-primary"></i> Data Cetak Musyarakah
      </h6>
    </div>
    <div class="card-body p-0 table-responsive">
      @if(empty($data) || count($data) === 0)
        <div class="p-4 text-center text-muted">
          <i class="fas fa-inbox fa-3x mb-2 text-secondary"></i>
          <p class="mb-0">Tidak ada data yang ditemukan.</p>
        </div>
      @else
        <table class="table table-bordered table-striped table-hover mb-0 text-center">
          <thead class="bg-success text-white">
            <tr>
              <th style="width: 50px;">No</th>
              <th>Kode Unit</th>
              <th>Nama Unit</th>
              <th>NoA</th>
              <th>Total Plafond</th>
              <th style="width: 140px;">Cetak</th>
            </tr>
          </thead>
          <tbody>
            @php
              $grandNoa = 0;
              $grandPlafond = 0;
            @endphp
            @foreach($data as $index => $item)
              @php
                $grandNoa += $item['total_noa'];
                $grandPlafond += $item['total_plafond'];
              @endphp
              <tr>
                <td class="align-middle">{{ $loop->iteration }}</td>
                <td class="align-middle font-weight-bold">{{ $item['kode_unit'] }}</td>
                <td class="align-middle text-left font-weight-bold">{{ $item['nama_unit'] }}</td>
                <td class="align-middle">{{ $item['total_noa'] }}</td>
                <td class="align-middle text-right font-weight-bold">Rp {{ $item['formatted_plafond'] }}</td>
                <td class="align-middle">
                  <a href="{{ $item['print_url'] }}" target="_blank" class="btn btn-sm btn-primary shadow-sm font-weight-bold">
                    <i class="fas fa-print mr-1"></i> Cetak
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>
  </div>
</div>
@endsection