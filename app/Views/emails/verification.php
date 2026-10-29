<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Email</title>
</head>
<body style="margin:0;padding:24px;background:#eef0f4;
             font-family:Arial,sans-serif;color:#263244;">

    <div style="max-width:560px;margin:auto;background:#fff;
                border-radius:16px;padding:32px;">

        <h2 style="color:#293f99;margin-top:0;">
            Permata Bangsa School
        </h2>

        <p>Halo <?= esc($name) ?>,</p>

        <p style="line-height:1.7;">
            Silakan verifikasi alamat email Anda untuk melanjutkan
            penggunaan Portal Staff.
        </p>

        <p style="margin:28px 0;">
            <a
                href="<?= esc($link, 'attr') ?>"
                style="display:inline-block;background:#293f99;
                       color:#fff;padding:14px 22px;border-radius:8px;
                       text-decoration:none;"
            >
                Verifikasi email
            </a>
        </p>

        <p style="line-height:1.7;">
            Tautan berlaku selama <strong>24 jam</strong>.
            Jika Anda meminta email baru, gunakan tautan terbaru.
        </p>

        <p style="font-size:13px;color:#606c7e;">
            Jika tombol tidak bisa dibuka, salin alamat ini ke browser:
        </p>

        <p style="font-size:13px;word-break:break-all;">
            <?= esc($link) ?>
        </p>

        <hr style="border:0;border-top:1px solid #dce1e9;margin:24px 0;">

        <p style="font-size:12px;color:#606c7e;">
            Jika Anda tidak mendaftarkan akun, abaikan email ini.
        </p>
    </div>

</body>
</html>