@extends('layouts.main')

@section('content-header')
<div class="container-fluid">
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1>Realisasi Murabahah</h1>
    </div>
    <div class="col-sm-6">
      <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="#">Home</a></li>
        <li class="breadcrumb-item active">Realisasi Murabahah</li>
      </ol>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Realisasi Murabahah</h3>
    </div>

    <div class="card-body">
      <form id="searchForm">
        <div class="mb-3">
          <label for="kodeKelompok" class="form-label">Kode Kelompok</label>
          <select class="form-control select2bs3" id="kodeKelompok" required>
            <option value="">Cari kode kelompok...</option>
          </select>
        </div>
        <div class="mb-3">
          <label for="tanggalRealisasi" class="form-label">Tanggal Realisasi</label>
          <input type="date" class="form-control" id="tanggalRealisasi" required>
        </div>

        <!-- Hidden input to store unit -->
        <input type="hidden" name="unit" id="userUnit" value="{{ Auth::user()->unit }}">
        <!-- Hidden input to store id -->
        <input type="hidden" name="id" id="userId" value="{{ Auth::user()->role_id }}">
        <!-- Hidden input to store param tanggal -->
        <input type="hidden" name="param_tanggal" id="userDate" value="{{ Auth::user()->param_tanggal }}">

        <button type="submit" class="btn btn-primary">Cari</button>
      </form>
    </div>

    <div class="mt-2">
      <div class="card-body">
        <table class="table table-bordered align-middle text-center">
          <thead class="table-light">
            <tr>
              <th>NO</th>
              <th>PILIH</th>
              <th>NAMA KELOMPOK</th>
              <th>NAMA</th>
              <th>PEMBIAYAAN</th>
              <th>MARGIN</th>
              <th>TGL MURABAHAH</th>
              <th>TGL JATUH TEMPO</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <tr id="emptyState">
              <td colspan="8">Belum ada data</td>
            </tr>
          </tbody>
        </table>
        <div class="mt-3">
          <button id="realisasiBtn" class="btn btn-primary" disabled>Realisasi Pembiayaan</button>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
@include('sweetalert::alert')

<script>
  $(document).ready(function () {
    $('#kodeKelompok').select2({
      theme: 'bootstrap3',
      placeholder: 'Cari kode kelompok...',
      allowClear: false,
      ajax: {
        url: '{{ route('realisasiMurabahah.cariKelompok') }}',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            q: params.term
          };
        },
        processResults: function (data) {
          return {
            results: data.map(item => ({
              id: item.code_kel,
              text: item.code_kel + ' - ' + item.nama_kel
            }))
          };
        },
        cache: true
      }
    });

    // Search Form Submission
    $('#searchForm').submit(function (e) {
      e.preventDefault();
      let kodeKelompok = $('#kodeKelompok').val();
      let tanggalRealisasi = $('#tanggalRealisasi').val();
      let userUnit = $('#userUnit').val();

      if (!kodeKelompok || !tanggalRealisasi) {
        Swal.fire("Error", "Kode Kelompok dan Tanggal Realisasi harus diisi!", "error");
        return;
      }

      $.ajax({
        url: "{{ route('realisasi.search') }}",
        type: "GET",
        data: { kode_kel: kodeKelompok, tgl_akad: tanggalRealisasi, unit: userUnit },
        success: function (response) {
          let tableBody = $('#tableBody');
          tableBody.empty();

          if (response.length === 0) {
            tableBody.append('<tr><td colspan="8">Belum ada data</td></tr>');
          } else {
            response.forEach((item, index) => {
              let row = `
                <tr data-id="${item.cif}">
                  <td>${index + 1}</td>
                  <td><input type="checkbox" class="selectRecord" checked></td>
                  <td>${item.nama_kelompok}</td>
                  <td>${item.nama}</td>
                  <td>${item.plafond}</td>
                  <td>${item.saldo_margin}</td>
                  <td>${item.tgl_murab}</td>
                  <td>${item.maturity_date}</td>
                </tr>`;
              tableBody.append(row);
            });

            let anyChecked = $('.selectRecord:checked').length > 0;
            $('#realisasiBtn').prop('disabled', !anyChecked);
          }
        }
      });
    });

    // Enable/Disable Realisasi Button
    $(document).on('change', '.selectRecord', function () {
      let anyChecked = $('.selectRecord:checked').length > 0;
      $('#realisasiBtn').prop('disabled', !anyChecked);
    });

    // tambah prevent preventDefault
    $(document).on('click', '#realisasiBtn', function (e) {
      e.preventDefault();
      let kodeKelompok = $('#kodeKelompok').val();
      let tanggalRealisasi = $('#tanggalRealisasi').val();
      let userUnit = $('#userUnit').val();
      let userId = $('#userId').val();
      let userDate = $('#userDate').val();
      let selectedCifs = $('.selectRecord:checked').map(function () {
        return $(this).closest('tr').data('id');
      }).get();

      if (selectedCifs.length === 0) {
        Swal.fire("Warning", "Pilih minimal satu data untuk direalisasikan!", "warning");
        return;
      }

      Swal.fire({
        title: "Konfirmasi",
        text: "Apakah Anda yakin ingin merealisasikan pembiayaan?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, Lanjutkan",
        cancelButtonText: "Batal"
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: "{{ route('realisasi.update') }}",
            type: "POST",
            data: {
              _token: "{{ csrf_token() }}",
              cifs: selectedCifs,
              kode_kel: kodeKelompok,
              tgl_akad: tanggalRealisasi,
              unit: userUnit,
              id: userId,
              param_tanggal: userDate
            },
            success: function (response) {
              if (response.message) {
                Swal.fire({
                  title: "Sukses",
                  text: response.message,
                  icon: "success"
                }).then(() => {
                  resetPage();
                });
              } else if (response.failed_cifs && response.failed_cifs.length > 0) {
                // partial success case
                let failedNames = response.failed_cifs.map(item => item.nama).join(', ');
                Swal.fire({
                  title: "Sebagian Berhasil",
                  text: `Beberapa transaksi gagal: ${failedNames}`,
                  icon: "warning"
                }).then(() => {
                  resetPage();
                });
              }
            },
            error: function (xhr, status, error) {
              let errorMessage = "Terjadi kesalahan, coba lagi!";
              if (xhr.responseJSON && xhr.responseJSON.error) {
                errorMessage = xhr.responseJSON.error;
              }
              Swal.fire("Error", errorMessage, "error");
            }
          });
        }
      });
    });

    function resetPage() {
      $('#searchForm')[0].reset();
      $('#kodeKelompok').val('').trigger('change');
      $('#tableBody').empty().append('<tr id="emptyState"><td colspan="8">Belum ada data</td></tr>');
      $('#realisasiBtn').prop('disabled', true);
    }
  });
</script>
@endpush
@endsection