<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
</head>
<body style="margin:0;padding:24px;background:#eef0f4;font-family:Arial,sans-serif;color:#263244;">
    <div style="max-width:560px;margin:auto;background:#ffffff;padding:28px;border-radius:14px;">
        <h2 style="color:#293f99;margin-top:0;">
            Permata Bangsa School
        </h2>

        <p>Halo <?= esc($name) ?>,</p>

        <p>
            Kami menerima permintaan untuk mengatur ulang
            password akun PBS Staff Portal kamu.
        </p>

        <p style="margin:28px 0;">
            <a
                href="<?= esc($link, 'attr') ?>"
                style="display:inline-block;padding:13px 22px;background:#293f99;color:#ffffff;text-decoration:none;border-radius:8px;"
            >
                Buat Password Baru
            </a>
        </p>

        <p>
            Tautan berlaku selama <strong>30 menit</strong>
            dan hanya dapat digunakan sekali.
        </p>

        <p>
            Jika tombol tidak bekerja, salin alamat berikut ke browser:
        </p>

        <p style="word-break:break-all;">
            <?= esc($link) ?>
        </p>

        <p style="color:#68758a;font-size:13px;">
            Jika kamu tidak meminta reset password, abaikan email ini.
            Password tidak berubah sampai proses reset diselesaikan.
        </p>
    </div>
</body>
</html>