@extends('layouts.main')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Cetak Musyarakah</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Cetak Musyarakah</li>
                </ol>
            </div>
        </div>
    </div>
    <!-- /.container-fluid -->
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card card-success card-outline shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title font-weight-bold">
                            Kriteria Pencarian
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <form id="musyarakahForm" action="{{ route('form_musyarakah') }}" method="POST">
                                    @csrf
                                    <div class="form-group row mb-3">
                                        <label for="tanggalCetak" class="col-sm-4 col-form-label font-weight-bold">Tanggal
                                            Cetak</label>
                                        <div class="col-sm-8">
                                            <input type="date" name="tanggal_cetak"
                                                class="form-control @error('tanggal_cetak') is-invalid @enderror"
                                                id="tanggalCetak" required>
                                            @error('tanggal_cetak')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="form-group row mb-0">
                                        <div class="col-sm-8 offset-sm-4">
                                            <button type="submit" class="btn btn-primary px-4" id="btnCari">
                                                <i class="fas fa-search mr-1"></i> Cari
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Hasil Pengelompokan Data Berdasarkan Unit -->
        <div class="row mt-3" id="resultContainer" style="display: none;">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light py-2">
                        <h6 class="font-weight-bold mb-0 text-dark">
                            Data Cetak Musyarakah
                        </h6>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-bordered table-striped table-hover mb-0 text-center">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">No</th>
                                    <th>Kode Unit</th>
                                    <th>Nama Unit</th>
                                    <th>NoA</th>
                                    <th>Total Plafond</th>
                                    <th style="width: 170px;">Cetak</th>
                                </tr>
                            </thead>
                            <tbody id="tableBodyResult">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /.container-fluid -->
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // CSRF token setup
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Handle form submission
            $('#musyarakahForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const url = form.attr('action');
                const data = form.serialize();
                const btnSubmit = $('#btnCari');

                btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Mencari...');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        btnSubmit.prop('disabled', false).html(
                            '<i class="fas fa-search mr-1"></i> Cari');

                        if (response.success && response.data && response.data.length > 0) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });

                            let rowsHtml = '';

                            $.each(response.data, function(index, item) {
                                rowsHtml += `
                                    <tr>
                                        <td class="align-middle">${index + 1}</td>
                                        <td class="align-middle font-weight-bold">${item.kode_unit}</td>
                                        <td class="align-middle text-left font-weight-bold">${item.nama_unit}</td>
                                        <td class="align-middle">${item.total_noa}</td>
                                        <td class="align-middle text-right font-weight-bold">Rp ${item.formatted_plafond}</td>
                                        <td class="align-middle">
                                            <a href="${item.print_url}" target="_blank" class="btn btn-sm btn-primary shadow-sm font-weight-bold">
                                                <i class="fas fa-print mr-1"></i> Cetak
                                            </a>
                                        </td>
                                    </tr>
                                `;
                            });

                            $('#tableBodyResult').html(rowsHtml);
                            $('#resultContainer').slideDown();
                        } else {
                            $('#resultContainer').hide();
                            Swal.fire({
                                icon: 'warning',
                                title: 'Data Tidak Ditemukan!',
                                text: response.message ||
                                    'Tidak ada data yang ditemukan untuk tanggal tersebut.'
                            });
                        }
                    },
                    error: function(xhr) {
                        btnSubmit.prop('disabled', false).html(
                            '<i class="fas fa-search mr-1"></i> Cari');
                        let errMsg = 'Terjadi kesalahan saat memproses data.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errMsg
                        });
                    }
                });
            });
        });
    </script>
@endpush
