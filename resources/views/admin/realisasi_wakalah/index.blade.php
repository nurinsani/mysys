@extends('layouts.main')
@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Realisasi Wakalah</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Realisasi Wakalah</li>
                </ol>
            </div>
        </div>
    </div><!-- /.container-fluid -->
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Realisasi Wakalah</h3>
        </div>

        <div class="card-body">
            <div class="container mt-5">
                <h3>Realisasi Wakalah</h3>
                <div class="card p-4 mt-0">

                    <div class="mb-3">
                        <label for="kodeKelompok" class="form-label">Kode Kelompok</label>
                        <select class="form-control select2bs3" id="kodeKelompok">
                            <option value="">Cari kode kelompok...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="tanggalRealisasi" class="form-label">Tanggal Realisasi</label>
                        <input type="date" class="form-control" id="tanggalRealisasi" value="">
                    </div>
                    <button type="button" class="btn btn-primary" id="btnSearch">Cari</button>

                </div>
            </div>

        </div>
        <div id="loading" class="text-center my-4" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">...</span>
            </div>

        </div>

        <div class="card-body">
            <form method="POST">
                @csrf
                <div class="container mt-5">
                    <div class="card p-4 mt-0">
                        <table class="table table-bordered text-center">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Pilih</th>
                                    <th>Nama Kelompok</th>
                                    <th>Nama</th>
                                    <th>Pembiayaan</th>
                                    <th>Margin</th>
                                    <th>Tgl Murabahah</th>
                                    <th>Tgl Jatuh Tempo</th>
                                </tr>
                            </thead>
                            <tbody id="dataTable">
                                <tr>
                                    <td colspan="8">Tidak ada data.</td>
                                </tr>
                            </tbody>
                        </table>
                        <P>
                            <button type="button" class="btn btn-primary btn-realisasi">Realisasi Wakalah</button>


                        </P>

            </form>
        </div>
    </div>
    </div>

    </div>


    <!-- /.card-footer-->
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('sweetalert::alert')
    <script>
        $(document).ready(function() {
            $('#kodeKelompok').select2({
                theme: 'bootstrap3',
                placeholder: 'Cari kode kelompok...',
                allowClear: false,
                ajax: {
                    url: '{{ url('realisasi_wakalah/cari-kelompok') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
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

            $('#btnSearch').on('click', function() {
                const kodeKelompok = $('#kodeKelompok').val().trim();
                const tanggalRealisasi = $('#tanggalRealisasi').val().trim();

                // Validasi Input
                if (kodeKelompok === '') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Kode Kelompok Kosong',
                        text: 'Harap isi kode kelompok sebelum mencari!',
                    });
                    return;
                }

                if (tanggalRealisasi === '') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Tanggal Realisasi Kosong',
                        text: 'Harap isi tanggal realisasi sebelum mencari!',
                    });
                    return;
                }

                $('#loading').show();
                $('#dataTable').html('<tr><td colspan="8">...</td></tr>');

                $.ajax({
                    url: '/realisasi_wakalah/getData',
                    type: 'GET',
                    data: {
                        kode_kelompok: kodeKelompok,
                        tanggal_realisasi: tanggalRealisasi,
                    },
                    success: function(data) {
                        let rows = '';
                        if (data.length > 0) {
                            data.forEach((item, index) => {
                                rows += `
                                <tr data-id="${item.cif}">
                                    <td>${index + 1}</td>
                                    <td><input type="checkbox" data-id="${item.cif}" checked></td>
                                    <td>${item.nama_kel}</td>
                                    <td>${item.nama}</td>
                                    <td>${new Intl.NumberFormat('id-ID').format(item.plafond)}</td>
                                    <td>${new Intl.NumberFormat('id-ID').format(item.saldo_margin)}</td>
                                    <td>${item.tgl_murab}</td>
                                    <td>${item.maturity_date}</td>
                                </tr>
                            `;
                            });
                        } else {
                            rows = '<tr><td colspan="8">Tidak ada data.</td></tr>';
                        }
                        $('#dataTable').html(rows);
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);

                        Swal.fire({
                            icon: 'error',
                            title: 'Data tidak ditemukan',
                            text: 'Harap isi tanggal realisasi dan kelompok lain!',
                        });
                        return;
                    },
                    complete: function() {

                        $('#loading').hide();
                    }
                });
            });
        });


        $(document).on('click', '.btn-realisasi', function(e) {
            e.preventDefault();
            const selectedIds = [];
            $('input[type="checkbox"]:checked').each(function() {
                const id = $(this).data('id');
                if (id) {
                    selectedIds.push(id);
                }
            });
            if (selectedIds.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Tidak ada data yang dipilih',
                    text: 'Pilih minimal satu data untuk direalisasi.',
                });
                return;
            }

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Proses ini akan merealisasi pembiayaan yang dipilih.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Realisasi!',
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/proses_realisasi_wakalah',
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                                'content')
                        },
                        data: {
                            ids: selectedIds,

                        },
                        success: function(response) {
                            Swal.fire(
                                'Berhasil!',
                                response.message,
                                'success'
                            ).then(() => {
                                $('#kodeKelompok').val('').trigger('change');
                                $('#tanggalRealisasi').val('');
                                location.reload();
                            });


                            selectedIds.forEach(id => {
                                $(`tr[data-id="${id}"]`).addClass('table-success');
                            });

                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: xhr.responseJSON?.message || 'Terjadi kesalahan.',
                            });
                        }
                    });
                }
            });
        });
    </script>
    @endpush
@endsection
