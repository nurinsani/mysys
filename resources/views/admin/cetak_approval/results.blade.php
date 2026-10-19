@extends('layouts.main')

@section('content-header')
<div class="container-fluid">
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1>Hasil Pencarian</h1>
    </div>
    <div class="col-sm-6">
      <div class="float-sm-right">
        <button type="button" class="btn btn-primary" onclick="javascript:history.back()">Kembali</button>
      </div>
    </div>
  </div>
</div>
<!-- /.container-fluid -->
@endsection

@section('content')
<div class="container-fluid">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Cetak La Risywah</h3>
    </div>
    <div class="card-body">
      @if($results->isEmpty())
      <p class="text-center">Tidak ada data yang ditemukan.</p>
      @else
      <div class="editor-container">
        <iframe
          src="{{ route('pdf.generateApproval', ['feature' => 'cetak_approval', 'date' => $results->first()->tgl_akad]) }}"
          width="100%" height="800" frameborder="0">
        </iframe>
      </div>
      @endif
    </div>
  </div>
</div>
<!-- /.container-fluid -->

<!-- /.card-footer-->
@endsection