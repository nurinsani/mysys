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
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <div>
                        <a href="{{ route('anggota.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Tambah</a>
                        <button onclick="addForm('{{ route('kelompok.store') }}')" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Tambah Kelompok</button>
                        <a href="{{ route('anggota.export') }}" class="btn btn-sm btn-success ml-auto"><i class="fas fa-file-export"></i> Export Excel</a>
                    </div>
                    <div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No Anggota</th>
                                <th>CIF</th>
                                <th>Nama</th>
                                <th>Alamat</th>
                                <th>TTL</th>
                                <th>AP</th>
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
                    url: '{{ route('anggota.data') }}',
                },
                columns: [{
                        data: 'DT_RowIndex',
                        searchable: false,
                        sortable: false
                    },
                    {
                        data: 'no'
                    },
                    {
                        data: 'cif'
                    },
                    {
                        data: 'nama'
                    },
                    {
                        data: 'alamat'
                    },
                    {
                        data: null, // Gabungkan field tempat_lahir dan tgl_lahir
                        render: function(data, type, row) {
                            return `${row.tempat_lahir}, ${row.tgl_lahir}`;
                        }
                    },
                    {
                        data: 'unit'
                    },
                    {
                        data: 'aksi',
                        searchable: false,
                        sortable: false
                    },
                ]
            })
        });

        $('#modal-form').validator().on('submit', function (e) {
            if (! e.preventDefault()) {
                $.ajax({
                    url: $('#modal-form form').attr('action'),
                    type: 'post',
                    data: $('#modal-form form').serialize()
                })
                .done((response) => {
                    Swal.fire({
                        title: 'Data berhasil diinput!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                    $('#modal-form').modal('hide');
                    table.ajax.reload();
                })
                .fail((errors) => {
                    Swal.fire({
                        title: 'Tidak dapat menyimpan data!',
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    });
                    return;
                })
            }
        });

        function addForm(url) {
            $('#modal-form').modal('show');
            $('#modal-form .modal-title').text('Tambah Kelompok');

            $('#modal-form form')[0].reset();
            $('#modal-form form').attr('action', url);
            $('#modal-form [name=_method]').val('post');
            $('#modal-form [name=code_kel]').focus();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const uppercaseElements = document.querySelectorAll('.uppercase');

            uppercaseElements.forEach(function(element) {
                element.addEventListener('input', function() {
                    element.value = element.value.toUpperCase();
                });
            });
        });

        $(document).ready(function() {
            $('.select2-ajax-cif').select2({
                dropdownParent: $('#modal-form'),
                placeholder: '-- Pilih CIF Ketua --',
                minimumInputLength: 3,
                ajax: {
                    url: "{{ route('anggota.cari') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            cari: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: $.map(data, function(item) {
                                return {
                                    id: item.cif,
                                    text: item.cif + ' - ' + item.nama + ' - ' + item.no_hp
                                }
                            })
                        };
                    },
                    cache: true
                }
            });
        });

        $(document).ready(function() {
            $('#cif').change(function() {
                var cif = $(this).val();

                if (cif) {
                    $.ajax({
                        url: '/get-anggota/' + cif,
                        type: 'GET',
                        success: function(response) {
                            if (response.success) {
                                $('#no_tlp').val(response.data.no_hp);
                            } else {
                                $('#no_tlp').val('');
                                alert(response.message);
                            }
                        },
                        error: function() {
                            alert('Terjadi kesalahan saat memuat data.');
                        }
                    });
                } else {
                    $('#no_tlp').val('');
                }
            });
        });

    </script>
@endpush
