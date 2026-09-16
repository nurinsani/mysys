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
    @include('admin.master_anggota.form-edit')
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>

        function toggleAlamatDomisili() {
            const checkbox = document.getElementById("sameAddress");
            const domisiliForm = document.getElementById("alamatDomisili");
            if (checkbox.checked) {
                domisiliForm.classList.add("d-none");
            } else {
                domisiliForm.classList.remove("d-none");
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Fungsi untuk menghasilkan string acak
            function generateRandomString(length) {
                const characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                let result = '';
                for (let i = 0; i < length; i++) {
                    result += characters.charAt(Math.floor(Math.random() * characters.length));
                }
                return result;
            }

            // Mengambil elemen input CIF
            document.getElementById('cif').value = generateRandomString(7);
        });

        function openImageModal(imageUrl) {
            // Set sumber gambar di modal
            document.getElementById('modalImage').src = imageUrl;
        }

        $(document).ready(function() {
            // Ketika dropdown kelompok dipilih
            $('#kode_kel').change(function() {
                // Ambil nilai yang dipilih
                var selectedCode = $(this).val();
                var selectedCodeString = selectedCode ? selectedCode.toString() : '';
                console.log("Kode Kelompok yang dikirim:", selectedCodeString); // Debug

                if (selectedCode) {
                    $.ajax({
                        url: '/get-kelompok-data',
                        type: 'GET',
                        data: { code_kel: selectedCodeString },
                        success: function(response) {
                            console.log("Response dari Server:", response); // Debug
                            $('#nama_ao').val(response.nama_ao);
                            $('#no_tlp').val(response.no_tlp);
                        },
                        error: function(xhr) {
                            console.log(xhr.responseText);
                        }
                    });
                } else {
                    // Kosongkan input fields jika tidak ada kelompok yang dipilih
                    $('#nama_ao').val('');
                    $('#no_tlp').val('');
                }
            });
        });

        async function cariKtp() {
            const nik = document.getElementById('nikInput').value;
            const resultContainer = document.getElementById('resultContainer');

            // Kosongkan hasil sebelumnya
            resultContainer.innerHTML = '';

            if (!nik) {
                Swal.fire({
                    title: 'Peringatan!',
                    text: 'NIK harus diisi!',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }

            try {
                // Kirim request ke controller Laravel
                const response = await fetch('/anggota/cari-ktp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ nik })
                });

                if (!response.ok) {
                    throw new Error('Data tidak ditemukan');
                }

                const data = await response.json();

                // Tampilkan hasil pencarian
                resultContainer.innerHTML = `
                    <div class="col-sm-11">
                        <div class="card mb-3">
                            <div class="card-header">KTP</div>
                            <div class="card-body">
                                <img src="http://rmc.nurinsani.co.id:9373/berkas/${data.data[0].ktp}" 
                                    width="580px" 
                                    alt="Gambar KTP" 
                                    class="img-thumbnail" 
                                    style="cursor: pointer;" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#imageModal"
                                    onclick="openImageModal('http://rmc.nurinsani.co.id:9373/berkas/${data.data[0].ktp}')">
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-11">
                        <div class="card mb-3">
                            <div class="card-header">Kartu Keluarga</div>
                            <div class="card-body">
                                <img src="http://rmc.nurinsani.co.id:9373/berkas/${data.data[0].kk}" 
                                    width="580px" 
                                    alt="Gambar KK" 
                                    class="img-thumbnail" 
                                    style="cursor: pointer;" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#imageModal"
                                    onclick="openImageModal('http://rmc.nurinsani.co.id:9373/berkas/${data.data[0].kk}')">
                            </div>
                        </div>
                    </div>
                `;
            } catch (error) {
                // Tampilkan pesan error
                resultContainer.innerHTML = `
                    <div class="col-sm-11">
                        <div class="card">
                            <div class="card-body">
                                <p style="color: red;">Data tidak ditemukan</p>
                        </div>
                    </div>
                `;
            }
        }

        function getKelompokByCao(cao) {
            if (cao) {
                fetch(`/get-kelompok/${cao}`)
                    .then(response => response.json())
                    .then(data => {
                        let kelompokSelect = document.getElementById('kode_kel');
                        kelompokSelect.innerHTML = '<option value="">-- PILIH KELOMPOK --</option>';
                        data.forEach(kelompok => {
                            let option = document.createElement('option');
                            option.value = kelompok.code_kel;
                            option.text = kelompok.nama_kel;
                            kelompokSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error:', error));
            } else {
                document.getElementById('kode_kel').innerHTML = '<option value="">-- PILIH KELOMPOK --</option>';
            }
        }
    </script>
@endpush
