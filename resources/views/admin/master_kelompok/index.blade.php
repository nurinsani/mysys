@extends('layouts.main')

@section('content-header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>{{ $title }}</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="#">Dahsboard</a></li>
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
                    <button onclick="addForm('{{ route('kelompok.store') }}')" class="btn btn-sm btn-primary">+ Tambah</button>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode Kelompok</th>
                                <th>Kode Unit</th>
                                <th>Nama Kelompok</th>
                                <th>Alamat</th>
                                <th>CAO</th>
                                <th>CIF Ketua Kel</th>
                                <th>No Telp Ketua</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

@include('admin.master_kelompok.form')
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let table;

        $(function() {
            table = $('.table').DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                autoWidth: false,
                ajax: {
                    url: '{{ route('kelompok.data') }}',
                },
                columns: [{
                        data: 'DT_RowIndex',
                        searchable: false,
                        sortable: false
                    },
                    {
                        data: 'code_kel'
                    },
                    {
                        data: 'code_unit'
                    },
                    {
                        data: 'nama_kel'
                    },
                    {
                        data: 'alamat'
                    },
                    {
                        data: 'cao'
                    },
                    {
                        data: 'cif'
                    },
                    {
                        data: 'no_tlp'
                    },
                    {
                        data: 'aksi',
                        searchable: false,
                        sortable: false
                    },
                ]
            })

            $('#modal-form').validator().on('submit', function (e) {
                if (! e.preventDefault()) {
                    $.ajax({
                        url: $('#modal-form form').attr('action'),
                        type: 'post',
                        data: $('#modal-form form').serialize()
                    })
                    .done((response) => {
                        Swal.fire({
                            title: 'Data berhasil disimpan!',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                        $('#modal-form').modal('hide');
                        table.ajax.reload();
                    })
                    .fail((errors) => {
                        let msg = 'Tidak dapat menyimpan data!';
                        if (errors.responseJSON && errors.responseJSON.message) {
                            msg = errors.responseJSON.message;
                        }
                        Swal.fire({
                            title: 'Gagal!',
                            text: msg,
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        });
                        return;
                    })
                }
            });
        });

        function addForm(url) {
            $('#modal-form').modal('show');
            $('#modal-form .modal-title').text('Tambah Kelompok');

            $('#modal-form form')[0].reset();
            $('#modal-form form').attr('action', url);
            $('#modal-form [name=_method]').val('post');
            $('#modal-form [name=code_kel]').attr('readonly', false);
            $('#modal-form [name=code_unit]').val('{{ Auth::user()->unit }}');

            // Ambil kode kelompok otomatis format (unit)-(nomor urut)
            $.get('{{ route("kelompok.nextCode") }}', function(response) {
                if (response && response.code_kel) {
                    $('#modal-form [name=code_kel]').val(response.code_kel);
                }
            });

            $('#modal-form [name=nama_kel]').focus();
        }

        function editForm(url) {
            $('#modal-form').modal('show');
            $('#modal-form .modal-title').text('Edit Kelompok');

            $('#modal-form form')[0].reset();
            $('#modal-form form').attr('action', url);
            $('#modal-form [name=_method]').val('put');
            $('#modal-form [name=code_kel]').attr('readonly', true);
            $('#modal-form [name=nama_kel]').focus();

            $.get(url)
            .done((response) => {
                $('#modal-form [name=code_kel]').val(response.code_kel);
                $('#modal-form [name=code_unit]').val(response.code_unit);
                $('#modal-form [name=nama_kel]').val(response.nama_kel);
                $('#modal-form [name=alamat]').val(response.alamat);
                $('#modal-form [name=cao]').val(response.cao);
                $('#modal-form [name=cif]').val(response.cif);
                $('#modal-form [name=no_tlp]').val(response.no_tlp);
            })
            .fail((errors) => {
                alert('Tidak dapat menampilkan data.');
                return;
            })
        }

        function hapusData(url) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(url, {
                        '_token': $('[name=csrf-token]').attr('content'),
                        '_method': 'delete'
                    })
                    .done((response) => {
                        Swal.fire(
                            'Terhapus!',
                            'Data berhasil dihapus.',
                            'success'
                        );
                        table.ajax.reload();
                    })
                    .fail((errors) => {
                        Swal.fire(
                            'Gagal!',
                            'Data tidak dapat dihapus.',
                            'error'
                        );
                    });
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const uppercaseElements = document.querySelectorAll('.uppercase');

            uppercaseElements.forEach(function(element) {
                element.addEventListener('input', function() {
                    element.value = element.value.toUpperCase();
                });
            });
        });


    </script>
@endpush
