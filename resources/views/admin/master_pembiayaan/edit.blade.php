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

<style>
  .swal-wide {
    width: 850px;
  }
  #dokumenTabs .nav-link {
    color: #495057;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-bottom: none;
    margin-right: 2px;
    border-radius: 4px 4px 0 0;
  }
  #dokumenTabs .nav-link:hover {
    background-color: #e9ecef;
  }
  #dokumenTabs .nav-link.active {
    color: #007bff;
    background-color: #fff;
    border-top: 3px solid #007bff;
    border-bottom: 1px solid #fff;
    font-weight: bold;
  }
</style>

@include('admin.master_pembiayaan.form-edit')

<!-- Modal untuk review gambar -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header d-flex justify-content-between align-items-center flex-wrap"
        style="background-color: #f8f9fa;">
        <h5 class="modal-title font-weight-bold" id="imageModalLabel">Review Gambar</h5>

        <!-- Toolbar di Bagian Atas -->
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

@push('scripts')
<script>
  $(function () {
    // Set the suffix value from pembiayaan data
    $('#suffix').val('{{ $pembiayaan->suffix }}');

    function formatRupiah(angka) {
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
      }).format(angka);
    }

    function updatePreview(inputSelector, previewSelector) {
      const val = parseFloat($(inputSelector).val());
      if (!isNaN(val) && val >= 0) {
        $(previewSelector).text(formatRupiah(val));
      } else {
        $(previewSelector).text('Rp 0');
      }
    }

    $('#pengajuan').on('input keyup change', function () {
      updatePreview('#pengajuan', '#preview-pengajuan');
    });

    $('#disetujui').on('input keyup change', function () {
      updatePreview('#disetujui', '#preview-disetujui');
    });

    // Inisialisasi awal saat halaman dibuka
    updatePreview('#pengajuan', '#preview-pengajuan');
    updatePreview('#disetujui', '#preview-disetujui');

    // Handle form submission
    $('#form').on('submit', function (e) {
      e.preventDefault();

      // Show loading state
      Swal.fire({
        title: 'Memproses...',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      // Submit form via AJAX
      $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        success: function (response) {
          Swal.close();

          if (response.status === 'success') {
            Swal.fire({
              title: 'Berhasil!',
              text: response.message || 'Data berhasil disimpan',
              icon: 'success',
              confirmButtonText: 'OK'
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href = "{{ route('pembiayaan.index') }}";
              }
            });
          } else if (response.status === 'warning') {
            Swal.fire({
              title: 'Peringatan!',
              text: response.message,
              icon: 'warning',
              confirmButtonText: 'OK'
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href = "{{ route('pembiayaan.index') }}";
              }
            });
          } else {
            Swal.fire({
              title: 'Error!',
              text: response.message || 'Terjadi kesalahan saat menyimpan data',
              icon: 'error',
              confirmButtonText: 'OK'
            });
          }
        },
        error: function (xhr) {
          Swal.close();
          Swal.fire({
            title: 'Error!',
            text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan data',
            icon: 'error',
            confirmButtonText: 'OK'
          });
        }
      });
    });
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
    if (img) {
      img.src = imageUrl;
    }
    currentScale = 1;
    currentRotation = 0;
    currentTranslateX = 0;
    currentTranslateY = 0;
    applyImageTransform(false);
    $('#imageModal').modal('show');
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

  function renderDocCard(title, fileName) {
    const hasFile = fileName && fileName !== 'null' && String(fileName).trim() !== '';
    const imgUrl = hasFile ? `http://rmc.nurinsani.co.id:9373/berkas/${fileName}` : null;

    return `
      <div class="col-md-6 col-sm-6">
        <div class="card mb-3 shadow-sm">
          <div class="card-header font-weight-bold bg-light py-2">${title}</div>
          <div class="card-body text-center p-2">
            ${hasFile ? `
              <img src="${imgUrl}" 
                alt="Gambar ${title}" 
                class="img-thumbnail img-fluid" 
                style="max-height: 250px; width: auto; cursor: pointer;" 
                data-bs-toggle="modal" 
                data-bs-target="#imageModal"
                data-toggle="modal" 
                data-target="#imageModal"
                onclick="openImageModal('${imgUrl}', '${title}')">
            ` : `
              <div class="py-4 text-muted">
                <p class="small mb-0">Berkas belum tersedia</p>
              </div>
            `}
          </div>
        </div>
      </div>
    `;
  }

  // Konfigurasi endpoint pembiayaan untuk masing-masing tab
  const DOKUMEN_ENDPOINTS = {
    lama: '/pembiayaan/cari-ktp',      // Tab Dokumen Lama
    lanjutan: '/pembiayaan/cari-ktp',  // Tab Dokumen Lanjutan (sementara sama)
    baru: '/pembiayaan/cari-ktp'       // Tab Dokumen Baru (sementara sama)
  };

  async function fetchTabDokumen(tabKey, tabTitle, containerId, nik) {
    const endpoint = DOKUMEN_ENDPOINTS[tabKey] || '/pembiayaan/cari-ktp';
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = `
      <div class="col-12 text-center py-4">
        <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;">
          <span class="sr-only">Memuat berkas...</span>
        </div>
        <h6 class="mt-3 font-weight-bold text-dark">Sedang memuat ${tabTitle}...</h6>
        <p class="small text-muted mb-0">Mohon tunggu, sedang mengambil berkas dokumen.</p>
      </div>
    `;

    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ nik, tipe: tabKey })
      });

      if (!response.ok) {
        throw new Error('Data tidak ditemukan');
      }

      const data = await response.json();

      if (!data.data || !data.data[0]) {
        throw new Error('Data tidak ditemukan');
      }

      const doc = data.data[0];

      if (tabKey === 'baru') {
        // Tab Dokumen Baru hanya menampilkan KTP Penjamin dan Foto Usaha
        container.innerHTML = `
          ${renderDocCard('KTP Penjamin', doc.penjamin)}
          ${renderDocCard('Usaha', doc.usaha)}
        `;
      } else {
        container.innerHTML = `
          ${renderDocCard('KTP', doc.ktp)}
          ${renderDocCard('KTP Penjamin', doc.penjamin)}
          ${renderDocCard('Kartu Keluarga', doc.kk)}
          ${renderDocCard('Usaha', doc.usaha)}
        `;
      }
    } catch (error) {
      container.innerHTML = `
        <div class="col-12 my-2">
          <div class="alert alert-warning text-center shadow-sm py-3 mb-0">
            <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2 d-block"></i>
            <strong class="d-block mb-1">${tabTitle} Tidak Ditemukan</strong>
            <span class="small text-muted">Belum ada berkas untuk kategori ini atau berkas belum diunggah.</span>
          </div>
        </div>
      `;
    }
  }

  async function cariKtp() {
    const ktpInput = document.getElementById('ktp');
    const nik = ktpInput ? ktpInput.value.trim() : '';
    const btnSearch = document.getElementById('searchKtp');

    if (!nik) {
      Swal.fire({
        title: 'Peringatan!',
        text: 'NIK harus diisi!',
        icon: 'warning',
        confirmButtonText: 'OK'
      });
      return;
    }

    // 1. Indikator loading pada tombol pencarian
    if (btnSearch) {
      btnSearch.disabled = true;
      btnSearch.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }

    // 2. Loading pop-up SweetAlert
    Swal.fire({
      title: 'Memuat Dokumen...',
      text: 'Sedang mengambil berkas identitas anggota...',
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    try {
      // 3. Muat berkas untuk masing-masing tab secara paralel
      await Promise.all([
        fetchTabDokumen('lama', 'Dokumen Lama', 'docList-lama', nik),
        fetchTabDokumen('lanjutan', 'Dokumen Lanjutan', 'docList-lanjutan', nik),
        fetchTabDokumen('baru', 'Dokumen Baru', 'docList-baru', nik)
      ]);
    } catch (error) {
      console.error(error);
    } finally {
      Swal.close();
      if (btnSearch) {
        btnSearch.disabled = false;
        btnSearch.innerHTML = '<i class="fas fa-search"></i>';
      }
    }
  }

  // Handle klik tab untuk memastikan tab berpindah dengan lancar
  $(document).on('click', '#dokumenTabs a', function (e) {
    e.preventDefault();
    $(this).tab('show');
  });

  $('#jenis_pembiayaan').on('change', function () {
      let jenis = $(this).val();

      if (jenis == 2) {
          $('#form-omzet').show();
      } else {
          $('#form-omzet').hide();
          $('#omzet').val('');
      }
  });

  // trigger saat halaman edit dibuka
  $(document).ready(function () {
      if ($('#jenis_pembiayaan').val() == 2) {
          $('#form-omzet').show();
      }
  });

</script>
@endpush
@endsection