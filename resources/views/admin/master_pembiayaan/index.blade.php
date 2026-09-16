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
        <h3 class="card-title">{{ $title }}</h3>
      </div>
      <div class="card-body">
        <!-- Search Form -->
        <form id="search-form" class="mb-4">
          <div class="form-group">
            <label>Kode Kelompok <span class="text-danger">*</span></label>
            <div class="row">
              <div class="col-sm-4">
                <select class="form-control select2-ajax" id="kode_kelompok" name="kode_kelompok"
                  style="width: 100%;">
                  <!-- Opsi akan di-load via AJAX -->
                </select>
              </div>
              <div class="col-sm-2">
                <button type="submit" class="btn btn-primary">Cari</button>
              </div>
            </div>
          </div>
        </form>

        <!-- Results Table -->
        <div class="table-responsive">
          <table class="table table-bordered table-hover">
            <thead>
              <tr>
                <th>No</th>
                <th>Kelompok</th>
                <th>Kode Kel</th>
                <th>No Anggota</th>
                <th>CIF</th>
                <th>Nama</th>
                <th>Plafond</th>
                <th>OS</th>
                <th>Tenor</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody id="results-body">
              <tr>
                <td colspan="10" class="text-center">Memuat data...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  $(function () {
    $('.select2-ajax').select2({
      placeholder: 'Cari Kode/Nama Kelompok...',
      allowClear: true,
      ajax: {
        url: "{{ route('pembiayaan.cariKelompok') }}",
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            cari: params.term
          };
        },
        processResults: function (data) {
          return {
            results: $.map(data, function (item) {
              return {
                id: item.code_kel,
                text: item.code_kel + ' - ' + item.nama_kel
              };
            })
          };
        },
        cache: true
      }
    });

    function muatData(kodeKelompok, pesanKosong) {
      $('#results-body').html('<tr><td colspan="10" class="text-center">Loading...</td></tr>');

      $.ajax({
        url: "{{ route('pembiayaan.data') }}",
        type: 'GET',
        data: kodeKelompok ? { kode_kelompok: kodeKelompok } : {},
        success: function (response) {
          if (response.status === 'success' && response.data && response.data.length > 0) {
            let html = '';
            response.data.forEach((item, index) => {
              html += `
              <tr>
                <td>${index + 1}</td>
                <td>${item.nama_kelompok}</td>
                <td>${item.kode_kel}</td>
                <td>${item.no_anggota}</td>
                <td>${item.anggota_cif}</td>
                <td>${item.nama_anggota}</td>
                <td>${item.plafond}</td>
                <td>${item.os}</td>
                <td>${item.tenor}</td>
                <td>
                  <a href="{{ route('pembiayaan.edit', '') }}/${item.anggota_cif}" class="btn btn-sm btn-primary">Edit</a>
                </td>
              </tr>
            `;
            });
            $('#results-body').html(html);
          } else {
            $('#results-body').html(`<tr><td colspan="10" class="text-center">${pesanKosong}</td></tr>`);
          }
        },
        error: function (xhr) {
          $('#results-body').html('<tr><td colspan="10" class="text-center text-danger">Terjadi kesalahan saat mengambil data</td></tr>');
          console.error('Error:', xhr);
        }
      });
    }

    // Saat halaman dibuka (belum ada pencarian kelompok), tampilkan data
    // pengajuan pembiayaan yang masih pending di temp_akad_mus.
    muatData(null, 'Belum ada pengajuan pembiayaan yang pending');

    $('#search-form').on('submit', function (e) {
      e.preventDefault();
      const kodeKelompok = $('#kode_kelompok').val();

      if (!kodeKelompok) {
        Swal.fire({
          title: 'Peringatan!',
          text: 'Kode Kelompok harus diisi!',
          icon: 'warning',
          confirmButtonText: 'OK'
        });
        return;
      }

      muatData(kodeKelompok, 'Tidak ada data ditemukan');
    });
  });
</script>
@endpush
@endsection