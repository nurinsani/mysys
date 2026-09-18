<div class="modal fade" id="modal-form" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="" method="post">
            @csrf
            @method('post')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <div class="form-group">
                        <label>Kode Unit</label>
                        <input type="text" class="form-control" name="code_unit" id="code_unit" value="{{ Auth::user()->unit }}" readonly required>
                        <span class="help-block with-errors text-danger"></span>
                    </div>

                    <div class="form-group">
                        <label>Nama Kelompok</label>
                        <input type="text" class="form-control uppercase" name="nama_kel" id="nama_kel" required autofocus>
                        <span class="help-block with-errors text-danger"></span>
                    </div>


                    <div class="form-group">
                        <label>Pilih AO</label>
                        <select name="cao" id="cao" class="form-control" required>
                            <option hidden value="">-- Pilih AO --</option>
                            @foreach ($ao as $item)
                                <option value="{{ $item->cao }}">{{ $item->nama_ao }}</option>
                            @endforeach
                        </select>
                        <span class="help-block with-errors text-danger"></span>
                    </div>

                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" class="form-control uppercase" id="alamat" cols="2" rows="2" required></textarea>
                        <span class="help-block with-errors text-danger"></span>
                    </div>

                    <div class="form-group">
                        <label>CIF Ketua</label>
                        <input type="text" class="form-control uppercase" name="cif" id="cif" required>
                        <span class="help-block with-errors text-danger"></span>
                    </div>

                    <div class="form-group">
                        <label>No Telp Ketua</label>
                        <input type="text" class="form-control" name="no_tlp" id="no_tlp" required>
                        <span class="help-block with-errors text-danger"></span>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>
