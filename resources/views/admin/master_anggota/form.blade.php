<form action="{{ route('anggota.store') }}" method="post" id="form">
    @csrf
    @method('post')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Kode Unit <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <input type="text" class="form-control" name="unit" id="unit"
                                        value="{{ auth()->check() ? auth()->user()->unit : 'User Belum Login' }}"
                                        readonly>
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">CIF <span class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <input type="text" class="form-control" name="cif" id="cif"
                                        value="" readonly>
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Kode AO <span class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <select name="cao" id="cao"
                                        class="form-control @error('cao') is-invalid @enderror"
                                        onchange="getKelompokByCao(this.value)">
                                        <option value="">-- PILIH AO --</option>
                                        @foreach ($ao as $item)
                                            <option value="{{ $item->cao }}"
                                                {{ old('cao') == $item->cao ? 'selected' : '' }}>{{ $item->nama_ao }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('cao')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Kode Kelompok <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <select name="kode_kel" id="kode_kel" value="{{ old('kode_kel') }}"
                                        class="form-control @error('kode_kel') is-invalid @enderror">
                                        <option value="">-- PILIH KELOMPOK --</option>
                                        @foreach ($kelompok as $item)
                                            <option value="{{ $item->code_kel }}"
                                                {{ old('kode_kel') == $item->code_kel ? 'selected' : '' }}>
                                                {{ $item->nama_kel }}</option>
                                        @endforeach
                                    </select>
                                    @error('kode_kel')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Nama Ketua Kel <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <input type="text" class="form-control" style="text-transform: uppercase;"
                                        id="nama_ketua" value="" readonly>
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Telp Ketua Kel <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <input type="text" class="form-control" style="text-transform: uppercase;"
                                        id="no_tlp" value="" readonly>
                                </div>
                            </div>


                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">No Identitas <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <div class="input-group">
                                        <input type="text" class="form-control @error('ktp') is-invalid @enderror"
                                            name="ktp" value="{{ old('ktp') }}" id="nikInput" maxlength="16"
                                            onkeypress="return hanyaAngka(event)"
                                            onkeyup="this.value = this.value.replace(/[^0-9]/g, '')" minlength="16"
                                            required oninvalid="this.setCustomValidity('No Identitas Harus 16 Digit')"
                                            placeholder="Masukkan No Identitas" style="text-transform: uppercase;">

                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary" onclick="cariKtp()"
                                                id="btnCariKtp" title="Cari No Identitas">
                                                <i class="fas fa-search" id="iconCariKtp"></i>
                                            </button>
                                        </div>
                                        @error('ktp')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Nama <span class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <input type="text" class="form-control @error('nama') is-invalid @enderror"
                                        value="{{ old('nama') }}" name="nama" id="nama"
                                        placeholder="Masukkan Nama" style="text-transform: uppercase;">
                                    @error('nama')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Tempat Lahir <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <input type="text"
                                        class="form-control @error('tempat_lahir') is-invalid @enderror"
                                        name="tempat_lahir" value="{{ old('tempat_lahir') }}" id="tempat_lahir"
                                        placeholder="Masukkan Tempat Lahir" style="text-transform: uppercase;">
                                    @error('tempat_lahir')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Tanggal Lahir <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <input type="date"
                                        class="form-control @error('tgl_lahir') is-invalid @enderror" name="tgl_lahir"
                                        value="{{ old('tgl_lahir') }}" id="tgl_lahir"
                                        placeholder="Masukkan Tempat Lahir" style="text-transform: uppercase;">
                                    @error('tgl_lahir')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row text-center"
                                style="border-bottom: 2px solid black; width: 93%;">
                                <label class="col-sm-4 col-form-label mx-auto">ISI ALAMAT SESUAI KTP</label>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Alamat <span class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <textarea name="alamat" class="form-control @error('alamat') is-invalid @enderror" id="alamat" cols="5"
                                        rows="3" placeholder="Masukkan Alamat" style="text-transform: uppercase;">{{ old('alamat') }}</textarea>
                                    @error('alamat')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">RT/RW <span class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <input type="text" class="form-control @error('rtrw') is-invalid @enderror"
                                        name="rtrw" value="{{ old('rtrw') }}" id="rtrw"
                                        placeholder="Masukkan RT/RW" style="text-transform: uppercase;">
                                    @error('rtrw')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Kelurahan/Desa <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <input type="text" class="form-control @error('desa') is-invalid @enderror"
                                        name="desa" value="{{ old('desa') }}" id="desa"
                                        placeholder="Masukkan Kelurahan/Desa" style="text-transform: uppercase;">
                                    @error('desa')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Kecamatan <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <input type="text"
                                        class="form-control @error('kecamatan') is-invalid @enderror" name="kecamatan"
                                        value="{{ old('kecamatan') }}" id="kecamatan"
                                        placeholder="Masukkan Kecamatan" style="text-transform: uppercase;">
                                    @error('kecamatan')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Kabupaten <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <input type="text" class="form-control @error('kota') is-invalid @enderror"
                                        name="kota" value="{{ old('kota') }}" id="kota"
                                        placeholder="Masukkan Kabupaten" style="text-transform: uppercase;">
                                    @error('kota')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Kode Pos <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <input type="text"
                                        class="form-control @error('kode_pos') is-invalid @enderror" name="kode_pos"
                                        value="{{ old('kode_pos') }}" id="kode_pos" placeholder="Masukkan Kode POS"
                                        style="text-transform: uppercase;">
                                    @error('kode_pos')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="sameAddress"
                                    onclick="toggleAlamatDomisili()">
                                <label class="form-check-label" for="sameAddress">
                                    ALAMAT DOMISILI SAMA DENGAN ALAMAT SESUAI
                                </label>
                            </div>

                            <fieldset id="alamatDomisili">

                                <div class="form-group row text-center"
                                    style="border-bottom: 2px solid black; width: 93%;">
                                    <label class="col-sm-4 col-form-label mx-auto">ISI ALAMAT DOMISILI</label>
                                </div>

                                <div class="form-group row">
                                    <span class="col-sm-4 col-form-label">Alamat <span
                                            class="text-danger">*</span></span>
                                    <div class="col-sm-7">
                                        <textarea name="alamat_domisili" class="form-control @error('alamat_domisili') is-invalid @enderror"
                                            id="alamat_domisili" cols="5" rows="3" placeholder="Masukkan Alamat"
                                            style="text-transform: uppercase;">{{ old('alamat_domisili') }}</textarea>
                                        @error('alamat_domisili')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <span class="col-sm-4 col-form-label">RT/RW <span
                                            class="text-danger">*</span></span>
                                    <div class="col-sm-7">
                                        <input type="text"
                                            class="form-control @error('rtrw_domisili') is-invalid @enderror"
                                            name="rtrw_domisili" value="{{ old('rtrw_domisili') }}" id="rtrw"
                                            placeholder="Masukkan RT/RW" style="text-transform: uppercase;">
                                        @error('rtrw_domisili')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <span class="col-sm-4 col-form-label">Kelurahan/Desa <span
                                            class="text-danger">*</span></span>
                                    <div class="col-sm-7">
                                        <input type="text"
                                            class="form-control @error('desa_domisili') is-invalid @enderror"
                                            name="desa_domisili" value="{{ old('desa_domisili') }}" id="desa"
                                            placeholder="Masukkan Kelurahan/Desa" style="text-transform: uppercase;">
                                        @error('desa_domisili')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <span class="col-sm-4 col-form-label">Kecamatan <span
                                            class="text-danger">*</span></span>
                                    <div class="col-sm-7">
                                        <input type="text"
                                            class="form-control @error('kecamatan_domisili') is-invalid @enderror"
                                            name="kecamatan_domisili" value="{{ old('kecamatan_domisili') }}"
                                            id="kecamatan" placeholder="Masukkan Kecamatan"
                                            style="text-transform: uppercase;">
                                        @error('kecamatan_domisili')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <span class="col-sm-4 col-form-label">Kabupaten <span
                                            class="text-danger">*</span></span>
                                    <div class="col-sm-7">
                                        <input type="text"
                                            class="form-control @error('kota_domisili') is-invalid @enderror"
                                            name="kota_domisili" value="{{ old('kota_domisili') }}" id="kota"
                                            placeholder="Masukkan Kabupaten" style="text-transform: uppercase;">
                                        @error('kota_domisili')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <span class="col-sm-4 col-form-label">Kode Pos <span
                                            class="text-danger">*</span></span>
                                    <div class="col-sm-7">
                                        <input type="text"
                                            class="form-control @error('kode_pos_domisili') is-invalid @enderror"
                                            name="kode_pos_domisili" value="{{ old('kode_pos_domisili') }}"
                                            id="kode_pos" placeholder="Masukkan Kode POS"
                                            style="text-transform: uppercase;">
                                        @error('kode_pos_domisili')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </fieldset>

                            {{-- buat form alamat berdasarkan domisili,
                            jika alamat domisili sesuai dengan alamat ktp maka bisa di hide dengan javascript --}}

                        </div>

                        <div class="col-md-6">
                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Jenis Kelamin <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <select name="kelamin" id="kelamin"
                                        class="form-control @error('kelamin') is-invalid @enderror">
                                        <option hidden value="">-- PILIH JENIS KELAMIN --</option>
                                        <option value="L" {{ old('kelamin') == 'L' ? 'selected' : '' }}>LAKI-LAKI
                                        </option>
                                        <option value="P" {{ old('kelamin') == 'P' ? 'selected' : '' }}>PEREMPUAN
                                        </option>
                                    </select>
                                    @error('kelamin')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Status Perkawinan <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <select name="status_menikah" id="status_menikah"
                                        class="form-control @error('status_menikah') is-invalid @enderror">
                                        <option hidden value="">-- PILIH STATUS PERKAWINAN --</option>
                                        <option value="MENIKAH"
                                            {{ old('status_menikah') == 'MENIKAH' ? 'selected' : '' }}>MENIKAH</option>
                                        <option value="BELUM MENIKAH"
                                            {{ old('status_menikah') == 'BELUM MENIKAH' ? 'selected' : '' }}>BELUM
                                            MENIKAH</option>
                                        <option value="JANDA"
                                            {{ old('status_menikah') == 'JANDA' ? 'selected' : '' }}>JANDA</option>
                                    </select>
                                    @error('status_menikah')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Agama <span class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <select name="agama" id="agama"
                                        class="form-control @error('agama') is-invalid @enderror">
                                        <option hidden value="">-- PILIH AGAMA --</option>
                                        <option value="ISLAM" {{ old('agama') == 'ISLAM' ? 'selected' : '' }}>ISLAM
                                        </option>
                                        <option value="KRISTEN" {{ old('agama') == 'KKRISTEN' ? 'selected' : '' }}>
                                            KRISTEN</option>
                                        <option value="HINDU" {{ old('agama') == 'HINDU' ? 'selected' : '' }}>HINDU
                                        </option>
                                        <option value="BUDDHA" {{ old('agama') == 'BUDDHA' ? 'selected' : '' }}>BUDDHA
                                        </option>
                                        <option value="KONGHUCU" {{ old('agama') == 'KONGHUCU' ? 'selected' : '' }}>
                                            KONGHUCU</option>
                                    </select>
                                    @error('agama')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Pendidikan <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <select name="pendidikan" id="pendidikan"
                                        class="form-control @error('pendidikan') is-invalid @enderror">
                                        <option hidden value="">-- PILIH PENDIDIKAN --</option>
                                        <option value="SD" {{ old('pendidikan') == 'SD' ? 'selected' : '' }}>SD
                                        </option>
                                        <option value="SMP" {{ old('pendidikan') == 'SMP' ? 'selected' : '' }}>SMP
                                        </option>
                                        <option value="SMA" {{ old('pendidikan') == 'SMA' ? 'selected' : '' }}>SMA
                                        </option>
                                    </select>
                                    @error('pendidikan')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Kewarganegaraan <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <select name="kewarganegaraan" id="kewarganegaraan"
                                        class="form-control @error('kewarganegaraan') is-invalid @enderror">
                                        <option hidden value="">-- PILIH KEWARGANEGARAAN --</option>
                                        <option selected value="INDONESIA">INDONESIA</option>
                                    </select>
                                    @error('kewarganegaraan')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Nama Pasangan <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <input type="text" class="form-control @error('waris') is-invalid @enderror"
                                        name="waris" value="{{ old('waris') }}" id="waris"
                                        placeholder="Masukkan Nama Pasangan" style="text-transform: uppercase;">
                                    @error('waris')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Pekerjaan Pasangan <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <input type="text"
                                        class="form-control @error('pekerjaan_pasangan') is-invalid @enderror"
                                        name="pekerjaan_pasangan" value="{{ old('pekerjaan_pasangan') }}"
                                        id="pekerjaan_pasangan" placeholder="Masukkan Pekerjaan Pasangan"
                                        style="text-transform: uppercase;">
                                    @error('pekerjaan_pasangan')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">No. Telp <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <input type="number" class="form-control @error('no_hp') is-invalid @enderror"
                                        name="no_hp" value="{{ old('no_hp') }}" id="no_hp"
                                        placeholder="Masukkan No. Telp" style="text-transform: uppercase;">
                                    @error('no_hp')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">No. Telp Pasangan <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <input type="number"
                                        class="form-control @error('hp_pasangan') is-invalid @enderror"
                                        name="hp_pasangan" value="{{ old('hp_pasangan') }}" id="hp_pasangan"
                                        placeholder="Masukkan No. Telp Pasangan" style="text-transform: uppercase;">
                                    @error('hp_pasangan')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <span class="col-sm-4 col-form-label">Nama Ibu Kandung <span
                                        class="text-danger">*</span></span>
                                <div class="col-sm-7">
                                    <input type="text"
                                        class="form-control @error('ibu_kandung') is-invalid @enderror"
                                        name="ibu_kandung" value="{{ old('ibu_kandung') }}" id="ibu_kandung"
                                        placeholder="Masukkan Ibu Kandung" style="text-transform: uppercase;">
                                    @error('ibu_kandung')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row" id="resultContainer">

                            </div>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                    <a href="{{ route('anggota.index') }}" class="btn btn-sm btn-danger">Kembali</a>
                </div>
            </div>

        </div>
    </div>
</form>
