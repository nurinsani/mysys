@foreach ($results as $result)
    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PEMBIAYAAN AKAD MUSYARAKAH</title>
        <style>
            @page {
                margin: 20px 20px 20px 20px;
            }

            body {
                font-family: 'Courier', 'Courier New', monospace;
                margin: 0;
                line-height: 1.6;
                font-size: 10.5pt;
            }

            h1,
            h2 {
                text-align: center;
                text-transform: uppercase;
                font-family: 'Courier', 'Courier New', monospace;
            }

            h1 {
                font-size: 13.5pt;
                margin: 6px 0;
                line-height: 1.3;
            }

            h2 {
                font-size: 11pt;
                margin-top: 18px;
                margin-bottom: 8px;
                line-height: 1.3;
            }

            p {
                text-align: justify;
                margin: 8px 0;
                line-height: 1.6;
            }

            .text-align-center {
                text-align: center;
            }

            .logo-container {
                text-align: center;
                margin-bottom: 12px;
            }

            .logo-container img {
                width: 220px;
                height: auto;
            }

            .signature {
                margin-top: 30px;
                width: 100%;
            }

            .signature table {
                width: 100%;
                border-collapse: collapse;
            }

            .signature td {
                text-align: center;
                vertical-align: middle;
                font-family: 'Courier', 'Courier New', monospace;
                line-height: 1.4;
            }

            .table-pasal {
                width: 100%;
                border-collapse: collapse;
                margin: 8px 0 12px 0;
            }

            .table-pasal td {
                vertical-align: top;
                padding: 4px 0;
                font-family: 'Courier', 'Courier New', monospace;
                font-size: 10.5pt;
                line-height: 1.6;
                border: none;
            }

            .table-pasal .col-num {
                width: 25px;
                text-align: left;
            }

            .table-pasal .col-label {
                width: 320px;
                white-space: nowrap;
            }

            .table-pasal .col-colon {
                width: 15px;
                text-align: center;
            }

            .table-pasal .col-val {
                text-align: left;
            }

            ol {
                margin: 6px 0 8px 20px;
                padding-left: 10px;
            }

            li {
                margin-bottom: 6px;
                line-height: 1.6;
                text-align: justify;
            }

            @if (!$loop->last)
                .page-break {
                    page-break-after: always;
                }
            @endif
        </style>
    </head>

    <body>
        <div class="logo-container">
            <img src="{{ str_replace('\\', '/', public_path('assets/img/logo-kspps.png')) }}" alt="Logo KSPPS">
        </div>

        <h2>PEMBIAYAAN AKAD MUSYARAKAH</h2>
        <p class="text-align-center">Nomor : {{ $result->no_anggota }}</p>

        <p>Dengan memohon petunjuk dan ridha Allah, pada hari ini {{ $result->namaHari }} tanggal {{ $result->tanggal }}
            {{ $result->bulan }} {{ $result->tahun }}, yang bertanda tangan di bawah ini :</p>

        <p>
            Nama {{ $result->namaMM }}, jabatan <strong>Marketing Manager (MM)</strong> dalam hal ini bertindak untuk
            dan
            atas nama <strong>Koperasi Simpan Pinjam dan Pembiayaan Syariah (KSPPS) Nur Insani</strong>, yang
            berkedudukan di
            kota Tangerang Selatan berdasarkan Akta Pendirian No 02 tanggal 7 bulan Mei tahun 2011 melalui notaries
            Neilly
            Iralita Iswari, SH, MSi, MKn dan telah memperoleh persetujuan Kementerian Koperasi dan Usaha Kecil dan
            Menengah No.
            1024/BH/M.KUM.2/XI/2011, serta dengan perubahan-perubahannya, selanjutnya disebut sebagai <strong>PIHAK
                PERTAMA</strong>.
        </p>

        <p>
            Nyonya <strong>{{ $result->anggotaNama }}</strong> bertempat tinggal di {{ $result->desa }},
            {{ $result->kecamatan }}
            Pemegang KTP
            dengan NIK {{ $result->ktp }}, selanjutnya disebut sebagai <strong>PIHAK KEDUA</strong>.
        </p>

        <p>PIHAK PERTAMA dan PIHAK KEDUA selanjutnya bersama-sama disebut 'Para Pihak', sepakat untuk membuat
            dan menandatangani pembiayaan Musyarakah, selanjutnya disebut 'Akad' dengan syarat dan ketentuan sebagai
            berikut:
        </p>

        <h2>PASAL 1<br>KETENTUAN POKOK AKAD</h2>
        <p>Ketentuan-ketentuan pokok Akad ini meliputi sebagai berikut :</p>
        <table class="table-pasal">
            <tr>
                <td class="col-num">1.</td>
                <td class="col-label">Jenis Usaha</td>
                <td class="col-colon">:</td>
                <td class="col-val">{{ $result->nama_usaha }}</td>
            </tr>
            <tr>
                <td class="col-num">2.</td>
                <td class="col-label">Modal Pihak Pertama</td>
                <td class="col-colon">:</td>
                <td class="col-val">{{ $result->totalPinjaman }}</td>
            </tr>
            <tr>
                <td class="col-num">3.</td>
                <td class="col-label">Prosentase Margin Pihak Pertama</td>
                <td class="col-colon">:</td>
                <td class="col-val">{{ $result->persentaseMarginNI }}</td>
            </tr>
            <tr>
                <td class="col-num">4.</td>
                <td class="col-label">Prosentase Margin Pihak Kedua</td>
                <td class="col-colon">:</td>
                <td class="col-val">{{ $result->persentaseMargin }}</td>
            </tr>
            <tr>
                <td class="col-num">5.</td>
                <td class="col-label">Jumlah Angsuran Mingguan</td>
                <td class="col-colon">:</td>
                <td class="col-val">{{ $result->nominalAngsuran }}</td>
            </tr>
            <tr>
                <td class="col-num">6.</td>
                <td class="col-label">Hari Pembayaran Angsuran</td>
                <td class="col-colon">:</td>
                <td class="col-val">Setiap hari {{ $result->hari }}</td>
            </tr>
            <tr>
                <td class="col-num">7.</td>
                <td class="col-label">Jangka Waktu Pembiayaan</td>
                <td class="col-colon">:</td>
                <td class="col-val">{{ $result->tenor }} Minggu</td>
            </tr>
            <tr>
                <td class="col-num">8.</td>
                <td class="col-label">Tanggal Jatuh Tempo Pembiayaan</td>
                <td class="col-colon">:</td>
                <td class="col-val">Tanggal {{ $result->maturityTanggal }} {{ $result->maturityBulan }}
                    {{ $result->maturityTahun }}</td>
            </tr>
        </table>

        <h2>PASAL 2<br>JENIS USAHA</h2>
        <p>Usaha sebagaimana dimaksud Pasal 1 huruf a adalah aktivitas yang tidak bertentangan dengan prinsip-prinsip
            syariah.</p>

        <h2>PASAL 3<br>MODAL PIHAK PERTAMA</h2>
        <p>Pelaksanaan prinsip Musyarakah yang berlangsung antara PIHAK PERTAMA dengan PIHAK KEDUA, dilaksanakan sebagai
            berikut:</p>
        <ol>
            <li>PIHAK KEDUA meminta kepada PIHAK PERTAMA untuk menyediakan modal.</li>
            <li>PIHAK PERTAMA memberikan modal kepada PIHAK KEDUA sesuai dengan pertimbangan dari pembiayaan terakhir
                dan omset terkecilnya.</li>
        </ol>

        <h2>PASAL 4<br>SYARAT PEMBERIAN MODAL</h2>
        <ol>
            <li>PIHAK KEDUA telah menyerahkan semua persyaratan yang diminta kepada PIHAK PERTAMA.</li>
            <li>PIHAK PERTAMA dan PIHAK KEDUA telah menandatangani perjanjian ini.</li>
        </ol>
        <p>Selanjutnya PIHAK KEDUA dengan ini mengakui dengan sebenarnya dan secara sah menerima modal tersebut dan
            menyatakan diri secara sah bahwa PIHAK PERTAMA adalah partner dalam bidang usahanya. </p>

        <h2>PASAL 5<br>KUASA</h2>
        <p>PIHAK PERTAMA memberikan kuasa kepada PIHAK KEDUA untuk melakukan usaha sesuai dengan apa yang diinginkan.
            Dalam hal ini, PIHAK KEDUA sebagai wakil dalam usaha bagi PIHAK PERTAMA.</p>

        <h2>PASAL 6<br>SURAT PERNYATAAN SUKA RELA</h2>
        <p>PIHAK KEDUA menyatakan dengan sukarela dan kesadaran sendiri untuk :</p>
        <ol>
            <li>Akan membayar angsuran tepat waktu sesuai tempat dan waktu yang telah disepakati.</li>
            <li>Tidak menggunakan pembiayaan ini untuk kepentingan pihak lain. Apabila di kemudian hari terbukti
                digunakan oleh pihak lain, akan tetap bertanggung jawab membayar angsuran tepat waktu</li>
            <li>Tidak melibatkan pihak luar (LSM, Ormas atau sejenisnya) untuk tidak membayar angsuran. Apabila hal
                diatas tidak dipenuhi, maka PIHAK KEDUA bersedia diiaporkan kepada pihak berwajib sesuai undang-undang
                yang berlaku.</li>
        </ol>

        <h2>PASAL 7<br>PERHITUNGAN PADA AKHIR PELUNASAN</h2>
        <p>Pada akhir pelunasan akan diadakan penghitungan ulang, apabila ada margin yang lebih diambil oleh PIHAK
            PERTAMA setelah diprediksi pada awal akad, maka akan dikembalikan kepada PIHAK KEDUA. Namun jika margin yang
            diambil oleh PIHAK PERTAMA kurang, maka kekurangan itu akan diberikan secara suka rela kepada PIHAK KEDUA.
        </p>

        <h2>PASAL 9<br>PASAL TAMBAHAN</h2>
        <p>Hal-hal yang belum diatur akan dibuat secara terpisah dan menjadi satu kesatuan dengan akad ini.</p>
        <p>Demikian perjanjian ini dibuat dan ditandatangani di {{ $result->kota ?? $result->desa }} pada hari
            {{ $result->namaHari }} tanggal {{ $result->tanggal }} {{ $result->bulan }} {{ $result->tahun }}.</p>

        <div class="signature">
            <table style="width: 100%; margin-top: 10px; text-align: center;">
                <tr>
                    <td style="width: 50%; vertical-align: top; text-align: center;">
                        <p class="text-align-center">PIHAK PERTAMA</p>
                        <br><br><br><br>
                        <p class="text-align-center">
                            ({{ $result->namaMM }})
                            <br>{{ $result->jabatan ?? 'Marketing Manager' }}
                        </p>
                        <br>
                        <p class="text-align-center">KETUA KELOMPOK</p>
                        <br><br><br><br>
                        <p class="text-align-center">(____________________________)</p>
                    </td>
                    <td style="width: 50%; vertical-align: top; text-align: center;">
                        <p class="text-align-center">PIHAK KEDUA</p>
                        <br><br><br><br>
                        <p class="text-align-center">({{ $result->anggotaNama }})</p>
                    </td>
                </tr>
            </table>
        </div>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    </body>

    </html>
@endforeach
