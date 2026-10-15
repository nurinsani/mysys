<form action="{{ route('pembiayaan.add', $pembiayaan->anggota_cif) }}" method="post" id="form">
  @csrf
  @method('post')
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Kode Unit <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" name="unit" id="unit" value="{{ $pembiayaan->unit_anggota }}"
                    readonly>
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">CIF <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" name="cif" value="{{ $pembiayaan->anggota_cif }}" readonly>
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Kode AO <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" name="cao" value="{{ $pembiayaan->anggota_cao }}" readonly>
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Kode Kelompok <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" name="kode_kel" value="{{ $pembiayaan->kode_kel }}" readonly>
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">No Identitas <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <div class="input-group">
                    <input type="text" class="form-control" name="ktp" id="ktp" value="{{ $pembiayaan->ktp }}" readonly>
                    <div class="input-group-append">
                      <button type="button" class="btn btn-primary" id="searchKtp" onclick="cariKtp()">
                        <i class="fas fa-search"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Nama <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" value="{{ $pembiayaan->nama_anggota }}" name="nama">
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Tempat Lahir <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" value="{{ $pembiayaan->tempat_lahir }}" name="tempat_lahir">
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Tanggal Lahir <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="date" class="form-control" value="{{ $pembiayaan->tanggal_lahir }}" name="tgl_lahir">
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Alamat <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <textarea name="alamat" class="form-control">{{ $pembiayaan->alamat }}</textarea>
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">RT/RW <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" value="{{ $pembiayaan->rtrw }}" name="rtrw">
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Kelurahan/Desa <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" value="{{ $pembiayaan->desa }}" name="desa">
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Kecamatan <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" value="{{ $pembiayaan->kecamatan }}" name="kecamatan">
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Kabupaten <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" value="{{ $pembiayaan->kota }}" name="kota">
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Kode Pos <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" value="{{ $pembiayaan->kode_pos }}" name="kode_pos">
                </div>
              </div>

              <fieldset id="alamatDomisili">
                <div class="form-group row">
                  <span class="col-sm-4 col-form-label">Alamat <span class="text-danger">*</span></span>
                  <div class="col-sm-7">
                    <textarea name="alamat_domisili" class="form-control">{{ $pembiayaan->alamat_domisili }}</textarea>
                  </div>
                </div>

                <div class="form-group row">
                  <span class="col-sm-4 col-form-label">RT/RW <span class="text-danger">*</span></span>
                  <div class="col-sm-7">
                    <input type="text" class="form-control" value="{{ $pembiayaan->rtrw_domisili }}"
                      name="rtrw_domisili">
                  </div>
                </div>

                <div class="form-group row">
                  <span class="col-sm-4 col-form-label">Kelurahan/Desa <span class="text-danger">*</span></span>
                  <div class="col-sm-7">
                    <input type="text" class="form-control" value="{{ $pembiayaan->desa_domisili }}"
                      name="desa_domisili">
                  </div>
                </div>

                <div class="form-group row">
                  <span class="col-sm-4 col-form-label">Kecamatan <span class="text-danger">*</span></span>
                  <div class="col-sm-7">
                    <input type="text" class="form-control" value="{{ $pembiayaan->kecamatan_domisili }}"
                      name="kecamatan_domisili">
                  </div>
                </div>

                <div class="form-group row">
                  <span class="col-sm-4 col-form-label">Kabupaten <span class="text-danger">*</span></span>
                  <div class="col-sm-7">
                    <input type="text" class="form-control" value="{{ $pembiayaan->kota_domisili }}"
                      name="kota_domisili">
                  </div>
                </div>

                <div class="form-group row">
                  <span class="col-sm-4 col-form-label">Kode Pos <span class="text-danger">*</span></span>
                  <div class="col-sm-7">
                    <input type="text" class="form-control" value="{{ $pembiayaan->kode_pos_domisili }}"
                      name="kode_pos_domisili">
                  </div>
                </div>
              </fieldset>

            </div>

            <div class="col-md-6">
              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Status Perkawinan <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" value="{{ $pembiayaan->status_menikah }}"
                    name="status_menikah">
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Agama <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" value="{{ $pembiayaan->agama }}" name="agama">
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Pendidikan <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" value="{{ $pembiayaan->pendidikan }}" name="pendidikan">
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Kewarganegaraan <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" value="{{ $pembiayaan->kewarganegaraan }}"
                    name="kewarganegaraan">
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Nama Pasangan <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" value="{{ $pembiayaan->waris }}" name="waris">
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Pekerjaan Pasangan <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" value="{{ $pembiayaan->pekerjaan_pasangan }}"
                    name="pekerjaan_pasangan">
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">No. Telp <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" value="{{ $pembiayaan->no_hp }}" name="no_hp">
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">No. Telp Pasangan <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" value="{{ $pembiayaan->hp_pasangan }}" name="hp_pasangan">
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Nama Ibu Kandung <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" value="{{ $pembiayaan->ibu_kandung }}" name="ibu_kandung" readonly>
                </div>
              </div>

              <div class="form-group row text-center" style="border-bottom: 2px solid black; width: 93%;">
                <label class="col-sm-4 col-form-label mx-auto">FORM PEMBIAYAAN</label>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Jenis Pembiayaan <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <select name="jenis_pembiayaan" id="jenis_pembiayaan" class="form-control" required>
                    <option hidden value="">-- Pilih Jenis Pembiayaan --</option>
                    <option value=1>Murabahah</option>
                    <option value=2>Musyarakah</option>
                  </select>
                  <span class="help-block with-errors text-danger"></span>
                </div>
              </div>

              <div class="form-group row" id="form-omzet" style="display:none;">
                  <span class="col-sm-4 col-form-label">
                      Omzet <span class="text-danger">*</span>
                  </span>
                  <div class="col-sm-7">
                      <input type="number"
                            name="omzet"
                            id="omzet"
                            class="form-control"
                            value="{{ $omzetMusyarakah ?? '' }}"
                            placeholder="Masukkan omzet">
                  </div>
              </div>



              <div class="form-group row">
                <span class="col-sm-4 col-form-label">No. Rekening <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" name="no_rek" id="no_rek"
                    value="{{ $pembiayaan->no_anggota }}" placeholder="Masukkan No. Rekening" readonly>
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Pengajuan <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="number" class="form-control" name="pengajuan" id="pengajuan"
                    placeholder="Masukkan Nominal Pengajuan" required>
                  <span id="preview-pengajuan" class="form-text text-muted mt-1">Rp 0</span>
                  <span class="help-block with-errors text-danger"></span>
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Tenor <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <select name="tenor" id="tenor" class="form-control" required>
                    <option hidden value="">-- Pilih Tenor --</option>
                    <option value=25>25</option>
                    <option value=35>35</option>
                    <option value=50>50</option>
                  </select>
                  <span class="help-block with-errors text-danger"></span>
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Disetujui <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="number" class="form-control" name="disetujui" id="disetujui"
                    placeholder="Masukkan Nominal Persetujuan" required>
                  <span id="preview-disetujui" class="form-text text-muted mt-1">Rp 0</span>
                  <span class="help-block with-errors text-danger"></span>
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Tanggal Wakalah <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="date" class="form-control" name="tgl_wakalah" id="tgl_wakalah"
                    placeholder="Masukkan Tanggal Wakalah" required>
                  <span class="help-block with-errors text-danger"></span>
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Tanggal Akad <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="date" class="form-control" name="tgl_akad" id="tgl_akad"
                    placeholder="Masukkan Tanggal Akad" required>
                  <span class="help-block with-errors text-danger"></span>
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Bidang Usaha <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <select name="bidang_usaha" id="bidang_usaha" class="form-control" required>
                    <option hidden value="">-- Pilih Bidang Usaha --</option>
                    <option value="Perdagangan">Perdagangan</option>
                    <option value="Perternakan">Perternakan</option>
                    <option value="Pertanian">Pertanian</option>
                    <option value="Produsen">Produsen</option>
                    <option value="Jasa">Jasa</option>
                  </select>
                  <span class="help-block with-errors text-danger"></span>
                </div>
              </div>

              <div class="form-group row">
                <span class="col-sm-4 col-form-label">Keterangan Usaha <span class="text-danger">*</span></span>
                <div class="col-sm-7">
                  <input type="text" class="form-control" name="keterangan_usaha" id="keterangan_usaha"
                    placeholder="Masukkan Keterangan Usaha" required>
                  <span class="help-block with-errors text-danger"></span>
                </div>
              </div>

              <div class="form-group row text-center" style="border-bottom: 2px solid black; width: 93%;">
                <label class="col-sm-4 col-form-label mx-auto">DOKUMEN ANGGOTA</label>
              </div>

              <div class="form-group row" id="resultContainer" style="width: 100%; margin: 0 auto;">
                <div class="col-12 px-0">
                  <ul class="nav nav-tabs nav-fill mb-3" id="dokumenTabs" role="tablist">
                    <li class="nav-item">
                      <a class="nav-link active font-weight-bold" id="tab-dokumen-lama" data-toggle="tab" data-bs-toggle="tab" href="#pane-dokumen-lama" role="tab" aria-controls="pane-dokumen-lama" aria-selected="true">
                        Dokumen Lama
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link font-weight-bold" id="tab-dokumen-lanjutan" data-toggle="tab" data-bs-toggle="tab" href="#pane-dokumen-lanjutan" role="tab" aria-controls="pane-dokumen-lanjutan" aria-selected="false">
                        Dokumen Lanjutan
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link font-weight-bold" id="tab-dokumen-baru" data-toggle="tab" data-bs-toggle="tab" href="#pane-dokumen-baru" role="tab" aria-controls="pane-dokumen-baru" aria-selected="false">
                        Dokumen Baru
                      </a>
                    </li>
                  </ul>
                  <div class="tab-content" id="dokumenTabsContent">
                    <div class="tab-pane fade show active" id="pane-dokumen-lama" role="tabpanel" aria-labelledby="tab-dokumen-lama">
                      <div class="row" id="docList-lama">
                        <div class="col-12 text-center text-muted py-4">
                          <small>Klik tombol cari pada No Identitas untuk memuat dokumen.</small>
                        </div>
                      </div>
                    </div>
                    <div class="tab-pane fade" id="pane-dokumen-lanjutan" role="tabpanel" aria-labelledby="tab-dokumen-lanjutan">
                      <div class="row" id="docList-lanjutan">
                        <div class="col-12 text-center text-muted py-4">
                          <small>Klik tombol cari pada No Identitas untuk memuat dokumen.</small>
                        </div>
                      </div>
                    </div>
                    <div class="tab-pane fade" id="pane-dokumen-baru" role="tabpanel" aria-labelledby="tab-dokumen-baru">
                      <div class="row" id="docList-baru">
                        <div class="col-12 text-center text-muted py-4">
                          <small>Klik tombol cari pada No Identitas untuk memuat dokumen.</small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Hidden input to store id -->
              <input type="hidden" name="id" id="id" value="{{ Auth::user()->role_id }}" required>
              <!-- Hidden input to store param tanggal -->
              <input type="hidden" name="param_tanggal" id="param_tanggal" value="{{ Auth::user()->param_tanggal }}"
                required>
              <!-- Hidden input to store suffix -->
              <input type="hidden" name="suffix" id="suffix" value="{{ $pembiayaan->suffix }}" required>

            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
          <a href="{{ route('pembiayaan.index') }}" class="btn btn-sm btn-danger">Kembali</a>
        </div>
      </div>

    </div>
  </div>
</form>