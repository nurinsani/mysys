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

    <!-- Modal untuk review gambar -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header d-flex justify-content-between align-items-center flex-wrap" style="background-color: #f8f9fa;">
                    <h5 class="modal-title font-weight-bold" id="imageModalLabel">Review Gambar</h5>

                    <!-- Toolbar di Bagian Atas (Warna Hitam Seragam) -->
                    <div class="d-flex align-items-center my-1">
                        <div class="btn-group mr-2 me-2" role="group" aria-label="Toolbar Gambar">
                            <button type="button" class="btn btn-outline-dark btn-sm" onclick="rotateLeft()" title="Rotasi Kiri (-90°)">
                                <i class="fas fa-undo"></i> Rotasi Kiri
                            </button>
                            <button type="button" class="btn btn-outline-dark btn-sm" onclick="rotateRight()" title="Rotasi Kanan (+90°)">
                                <i class="fas fa-redo"></i> Rotasi Kanan
                            </button>
                            <button type="button" class="btn btn-outline-dark btn-sm" onclick="zoomIn()" title="Perbesar">
                                <i class="fas fa-search-plus"></i> Zoom In
                            </button>
                            <button type="button" class="btn btn-outline-dark btn-sm" onclick="zoomOut()" title="Perkecil">
                                <i class="fas fa-search-minus"></i> Zoom Out
                            </button>
                            <button type="button" class="btn btn-outline-dark btn-sm" onclick="resetTransform()" title="Reset Ukuran & Rotasi">
                                <i class="fas fa-sync-alt"></i> Reset
                            </button>
                        </div>
                        <button type="button" class="close btn-close ml-2 ms-2" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body text-center p-0 position-relative" id="modalImageWrapper"
                    style="overflow: hidden; height: 75vh; background-color: #e9ecef; display: flex; align-items: center; justify-content: center; cursor: grab; user-select: none;">
                    <img id="modalImage" src="" alt="Gambar" class="img-fluid shadow-sm"
                        style="max-height: 70vh; max-width: 90%; user-select: none; pointer-events: auto; -webkit-user-drag: none;">
                </div>
                <div class="modal-footer justify-content-between py-2">
                    <small class="text-muted" id="imageStatusInfo">Zoom: 100% | Rotasi: 0° <span class="d-none d-md-inline">| Klik & tarik gambar untuk menggeser</span></small>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let currentScale = 1;
        let currentRotation = 0;
        let currentTranslateX = 0;
        let currentTranslateY = 0;
        let isDragging = false;
        let startX = 0;
        let startY = 0;

        function openImageModal(imageUrl, title = 'Review Gambar') {
            const img = document.getElementById('modalImage');
            const label = document.getElementById('imageModalLabel');
            if (label && title) {
                label.innerText = title.startsWith('Review') ? title : 'Review ' + title;
            }
            img.src = imageUrl;
            currentScale = 1;
            currentRotation = 0;
            currentTranslateX = 0;
            currentTranslateY = 0;
            applyImageTransform(false);
        }

        function rotateLeft() {
            currentRotation = (currentRotation - 90) % 360;
            applyImageTransform(true);
        }

        function rotateRight() {
            currentRotation = (currentRotation + 90) % 360;
            applyImageTransform(true);
        }

        function zoomIn() {
            if (currentScale < 5) {
                currentScale = parseFloat((currentScale + 0.25).toFixed(2));
                applyImageTransform(true);
            }
        }

        function zoomOut() {
            if (currentScale > 0.5) {
                currentScale = parseFloat((currentScale - 0.25).toFixed(2));
                if (currentScale <= 1) {
                    currentTranslateX = 0;
                    currentTranslateY = 0;
                }
                applyImageTransform(true);
            }
        }

        function resetTransform() {
            currentScale = 1;
            currentRotation = 0;
            currentTranslateX = 0;
            currentTranslateY = 0;
            applyImageTransform(true);
        }

        function applyImageTransform(animate = true) {
            const img = document.getElementById("modalImage");
            if (img) {
                img.style.transition = animate ? 'transform 0.2s ease' : 'none';
                img.style.transform = `translate(${currentTranslateX}px, ${currentTranslateY}px) rotate(${currentRotation}deg) scale(${currentScale})`;
            }
            const info = document.getElementById("imageStatusInfo");
            if (info) {
                info.innerText = `Zoom: ${Math.round(currentScale * 100)}% | Rotasi: ${currentRotation}°`;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const wrapper = document.getElementById('modalImageWrapper');
            const img = document.getElementById('modalImage');

            if (wrapper) {
                wrapper.addEventListener('mousedown', function(e) {
                    if (e.button !== 0) return;
                    isDragging = true;
                    startX = e.clientX - currentTranslateX;
                    startY = e.clientY - currentTranslateY;
                    wrapper.style.cursor = 'grabbing';
                    if (img) img.style.cursor = 'grabbing';
                    e.preventDefault();
                });

                window.addEventListener('mousemove', function(e) {
                    if (!isDragging) return;
                    e.preventDefault();
                    currentTranslateX = e.clientX - startX;
                    currentTranslateY = e.clientY - startY;
                    applyImageTransform(false);
                });

                window.addEventListener('mouseup', function() {
                    if (isDragging) {
                        isDragging = false;
                        if (wrapper) wrapper.style.cursor = 'grab';
                        if (img) img.style.cursor = 'grab';
                    }
                });

                wrapper.addEventListener('wheel', function(e) {
                    e.preventDefault();
                    if (e.deltaY < 0) {
                        zoomIn();
                    } else {
                        zoomOut();
                    }
                }, { passive: false });

                wrapper.addEventListener('touchstart', function(e) {
                    if (e.touches.length === 1) {
                        isDragging = true;
                        startX = e.touches[0].clientX - currentTranslateX;
                        startY = e.touches[0].clientY - currentTranslateY;
                    }
                }, { passive: true });

                window.addEventListener('touchmove', function(e) {
                    if (!isDragging || e.touches.length !== 1) return;
                    currentTranslateX = e.touches[0].clientX - startX;
                    currentTranslateY = e.touches[0].clientY - startY;
                    applyImageTransform(false);
                }, { passive: true });

                window.addEventListener('touchend', function() {
                    isDragging = false;
                });
            }
        });

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
            const nikInput = document.getElementById('nikInput');
            const nik = nikInput ? nikInput.value.trim() : '';
            const resultContainer = document.getElementById('resultContainer');
            const btnCari = document.getElementById('btnCariKtp');
            const iconCari = document.getElementById('iconCariKtp');

            if (!nik) {
                Swal.fire({
                    title: 'Peringatan!',
                    text: 'No Identitas (NIK) harus diisi terlebih dahulu!',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // Aktifkan indikator loading pada tombol
            if (btnCari) btnCari.disabled = true;
            if (iconCari) iconCari.className = 'fas fa-spinner fa-spin';

            // Tampilkan placeholder loading di kontainer hasil
            resultContainer.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="sr-only">Memuat...</span>
                    </div>
                    <h6 class="mt-3 font-weight-bold text-secondary">Sedang mencari data identitas...</h6>
                    <small class="text-muted">Mohon tunggu sebentar, sistem sedang mengambil berkas.</small>
                </div>
            `;

            // Tampilkan modal loading SweetAlert
            Swal.fire({
                title: 'Mencari Data...',
                text: 'Sedang mengambil data berkas identitas...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

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
                Swal.close();

                // Tampilkan hasil pencarian
                resultContainer.innerHTML = `
                    <div class="col-12 mt-4 mb-3">
                        <div class="form-group row text-center" style="border-bottom: 2px solid black; width: 100%; margin: 0 auto;">
                            <label class="col-form-label font-weight-bold text-uppercase mx-auto" style="letter-spacing: 1px;">HASIL PENCARIAN BERKAS</label>
                        </div>
                    </div>

                    <div class="col-md-6 col-sm-6">
                        <div class="card mb-3 shadow-sm">
                            <div class="card-header font-weight-bold bg-light py-2">KTP</div>
                            <div class="card-body text-center p-2">
                                <img src="http://rmc.nurinsani.co.id:9373/berkas/${data.data[0].ktp}" 
                                    alt="Gambar KTP" 
                                    class="img-thumbnail img-fluid" 
                                    style="max-height: 250px; width: auto; cursor: pointer;" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#imageModal"
                                    onclick="openImageModal('http://rmc.nurinsani.co.id:9373/berkas/${data.data[0].ktp}', 'KTP')">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-sm-6">
                        <div class="card mb-3 shadow-sm">
                            <div class="card-header font-weight-bold bg-light py-2">KTP Penjamin</div>
                            <div class="card-body text-center p-2">
                                <img src="http://rmc.nurinsani.co.id:9373/berkas/${data.data[0].penjamin}" 
                                    alt="Gambar KTP Penjamin" 
                                    class="img-thumbnail img-fluid" 
                                    style="max-height: 250px; width: auto; cursor: pointer;" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#imageModal"
                                    onclick="openImageModal('http://rmc.nurinsani.co.id:9373/berkas/${data.data[0].penjamin}', 'KTP Penjamin')">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-sm-6">
                        <div class="card mb-3 shadow-sm">
                            <div class="card-header font-weight-bold bg-light py-2">Kartu Keluarga</div>
                            <div class="card-body text-center p-2">
                                <img src="http://rmc.nurinsani.co.id:9373/berkas/${data.data[0].kk}" 
                                    alt="Gambar KK" 
                                    class="img-thumbnail img-fluid" 
                                    style="max-height: 250px; width: auto; cursor: pointer;" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#imageModal"
                                    onclick="openImageModal('http://rmc.nurinsani.co.id:9373/berkas/${data.data[0].kk}', 'Kartu Keluarga')">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-sm-6">
                        <div class="card mb-3 shadow-sm">
                            <div class="card-header font-weight-bold bg-light py-2">Usaha</div>
                            <div class="card-body text-center p-2">
                                <img src="http://rmc.nurinsani.co.id:9373/berkas/${data.data[0].usaha}" 
                                    alt="Gambar Usaha" 
                                    class="img-thumbnail img-fluid" 
                                    style="max-height: 250px; width: auto; cursor: pointer;" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#imageModal"
                                    onclick="openImageModal('http://rmc.nurinsani.co.id:9373/berkas/${data.data[0].usaha}', 'Usaha')">
                            </div>
                        </div>
                    </div>
                `;
            } catch (error) {
                Swal.fire({
                    title: 'Data Tidak Ditemukan',
                    text: 'Data berkas untuk NIK tersebut tidak ditemukan atau server sedang tidak dapat diakses.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });

                // Tampilkan pesan error di container
                resultContainer.innerHTML = `
                    <div class="col-12 mt-3">
                        <div class="card mb-3 border-danger shadow-sm">
                            <div class="card-body text-center py-4">
                                <i class="fas fa-exclamation-circle text-danger fa-2x mb-2"></i>
                                <p class="text-danger font-weight-bold mb-0">Data tidak ditemukan</p>
                            </div>
                        </div>
                    </div>
                `;
            } finally {
                if (btnCari) btnCari.disabled = false;
                if (iconCari) iconCari.className = 'fas fa-search';
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
