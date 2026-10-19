<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>APPROVAL</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 10px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 10px;
    }

    th,
    td {
      border: 1px solid black;
      padding: 3px;
      text-align: center;
    }

    th {
      background-color: #f2f2f2;
    }

    th.left {
      text-align: left;
    }

    .center {
      text-align: center;
    }

    .signature-section {
      width: 100%;
      text-align: center;
      margin-top: 50px;
    }

    .signature-table {
      width: 100%;
      border-collapse: collapse;
    }

    .signature-table td {
      padding: 4px 0;
      text-align: center;
      vertical-align: bottom;
    }

    .signature-line {
      display: block;
      margin-top: 30px;
    }
  </style>
</head>

<body>

  <h2 >KOPERASI SIMPAN PINJAM DAN PEMBIAYAAN SYARIAH NUR INSANI AREA PEMASARAN BRANGSONG</h2>
  <h2 >ALAMAT KEL SIDOREJO RT 001 RW 008 KEC BRANGSONG KAB KENDAL </h2>
  <h3>REKAP PERMOHONAN DAN REKOMENDASI PEMBIAYAAN KELOMPOK</h3>

  <p>Kode Area Pemasaran: <strong>{{ $unit }}</strong></p>
  <p>Tanggal Cair       : <strong>{{ $anggota[0]->tgl_wakalah }}</strong></p>
  <p>Nama MM            : <strong>{{ $namaMM }}</strong></p>

  <table>
    <thead>
      <tr>
        <th>No</th>
        <th>CIF</th>
        <th>Nama Anggota</th>
        <th>Kode KEL</th>
        <th>Nama Suami</th>
        <th>Alamat</th>
        <th>Jenis Usaha</th>
        <th>Pengajuan AO</th>
        <th>Tanggal PK</th>
        <th>JML</th>
        <th>Sebelumnya</th>
        <th>Ke</th>
        <th>Disetujui MM</th>
        <th>Pinj. ke</th>
        <th>Status Pembiayaan</th>
      </tr>
    </thead>
    <tbody>
      @foreach($anggota as $index => $anggota)
      <tr>
        <td>{{ $index + 1 }}</td>
        <td>{{ $anggota->cif_anggota }}</td>
        <td>{{ $anggota->nama_anggota }}</td>
        <td>{{ $anggota->kode_kel }}</td>
        <td>{{ $anggota->waris }}</td>
        <td>{{ $anggota->kota }}, {{ $anggota->kecamatan }}, {{ $anggota->desa }}</td>
        <td>{{ $anggota->nama_usaha }}</td>
        <td>{{ $anggota->plafond }}</td>
        <td>{{ $anggota->tgl_akad }}</td>
        <td>{{ $anggota->tenor }}</td>
        <td>{{ $anggota->nominal }}</td>
        <td>{{ $anggota->run_tenor }}</td>
        <td>{{ $anggota->plafond }}</td>
        <td>{{ $anggota->ke }}</td>
        <td>Disetujui</td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <th scope="row" colspan="12" class="left">Total</th>
        <td colspan="3">{{ $totalPlafond }}</td>
      </tr>
    </tfoot>
  </table>

  <br>

  <table>
    <thead>
      <tr>
        <th>Kode Kel</th>
        <th>Nama Kelompok</th>
        <th>Total Pembiayaan</th>
        <th>Noa</th>
        <th>Nama AO</th>
      </tr>
    </thead>
    <tbody>
      @foreach($kelompok as $kelompok)
      <tr>
        <td>{{ $kelompok->code_kel }}</td>
        <td>{{ $kelompok->nama_kel }}</td>
        <td>{{ $kelompok->total_plafond }}</td>
        <td>{{ $kelompok->count }}</td>
        <td>{{ $kelompok->nama_ao }}</td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <th scope="row" colspan="2" class="left">Total</th>
        <td>{{ $totalPlafond }}</td>
        <td colspan="2">{{ $totalAnggota }}</td>
      </tr>
    </tfoot>
  </table>

  <br>

  <div class="signature-section">
    <table class="signature-table" border="1" width="100%" cellspacing="0">

      <tr>
        <td colspan="4" align="center"><strong>Dibuat Oleh,</strong></td>
        <td colspan="1" align="center"><strong>Direkomendasi Oleh,</strong></td>
        <td colspan="1" align="center"><strong>Disetujui Oleh,</strong></td>
      </tr>
      <tr>
        <td colspan="4">
          <table width="100%" cellspacing="0">
            <tr>
              <td align="center">
                <span class="signature-line">(.................................)</span><br>
                SA/AL
              </td>
            </tr>
          </table>
        </td>
        <td align="center">
          <table width="100%" cellspacing="0">
            <tr>
              <td align="center">
                <span class="signature-line">(.................................)</span><br>
                Marketing Manager
              </td>
            </tr>
          </table>
        </td>
        <td align="center">
          <table width="100%" cellspacing="0">
            <tr>
              <td align="center">
                <span class="signature-line">(.................................)</span><br>
                BM
              </td>
            </tr>
          </table>
        </td>
      </tr>

    </table>
  </div>

</body>

</html>